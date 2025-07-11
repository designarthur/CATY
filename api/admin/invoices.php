<?php
// api/admin/invoices.php - Handles admin actions for invoices, like status changes

session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Security check: Ensure user is a logged-in admin
if (!is_logged_in() || !has_role('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$invoice_id = filter_input(INPUT_POST, 'invoice_id', FILTER_VALIDATE_INT);
$new_status = $_POST['status'] ?? '';

if ($action !== 'update_status' || !$invoice_id || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing parameters.']);
    exit;
}

// Validate the new status to ensure it's one of the allowed enum values
$allowed_statuses = ['pending', 'paid', 'partially_paid', 'cancelled'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status provided.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Fetch current invoice and customer details for notification
    $stmt_fetch = $conn->prepare("
        SELECT 
            i.invoice_number, i.status AS old_status, i.quote_id, i.booking_id,
            u.id AS user_id, u.first_name, u.email
        FROM invoices i
        JOIN users u ON i.user_id = u.id
        WHERE i.id = ?
    ");
    $stmt_fetch->bind_param("i", $invoice_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    $invoice_data = $result->fetch_assoc();
    $stmt_fetch->close();

    if (!$invoice_data) {
        throw new Exception('Invoice not found.');
    }

    if ($invoice_data['old_status'] === $new_status) {
        throw new Exception('Invoice is already set to this status.');
    }

    // 2. Update the invoice status in the database
    $stmt_update = $conn->prepare("UPDATE invoices SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $new_status, $invoice_id);
    if (!$stmt_update->execute()) {
        throw new Exception('Failed to update invoice status in the database.');
    }
    $stmt_update->close();

    // 3. If status is updated to 'paid', create a booking ONLY if it's from a quote and has no booking yet.
    if ($new_status === 'paid') {
        // A booking should only be created from an initial quote payment.
        // If quote_id exists AND booking_id is NULL, it's a new booking.
        if (!empty($invoice_data['quote_id']) && is_null($invoice_data['booking_id'])) {
            createBookingFromInvoice($conn, $invoice_id);
        }
        // If it's an extension payment, we need to update the booking's end date.
        elseif (strpos($invoice_data['invoice_number'], 'INV-EXT-') === 0) {
            $stmt_ext = $conn->prepare("SELECT requested_days FROM booking_extension_requests WHERE invoice_id = ?");
            $stmt_ext->bind_param("i", $invoice_id);
            $stmt_ext->execute();
            $ext_data = $stmt_ext->get_result()->fetch_assoc();
            $stmt_ext->close();

            if ($ext_data && !empty($invoice_data['booking_id'])) {
                $stmt_update_booking = $conn->prepare("UPDATE bookings SET end_date = DATE_ADD(end_date, INTERVAL ? DAY) WHERE id = ?");
                $stmt_update_booking->bind_param("ii", $ext_data['requested_days'], $invoice_data['booking_id']);
                $stmt_update_booking->execute();
                $stmt_update_booking->close();
            }
        }
        // For other charge types (swap, relocation, etc.), no booking creation or update is needed here.
    }
    
    // For ALL status changes (including paid), send a simple notification.
    // The createBookingFromInvoice function sends its own specific "Booking Confirmed" notification.
    $customer_name = htmlspecialchars($invoice_data['first_name']);
    $invoice_number = htmlspecialchars($invoice_data['invoice_number']);
    $customer_email = $invoice_data['email'];
    $companyName = getSystemSetting('company_name') ?? 'Your Company';
    
    $email_subject = "Update on your invoice #{$invoice_number}";
    $email_body = "<p>Dear {$customer_name},</p><p>The payment status for your invoice #<strong>{$invoice_number}</strong> has been updated to: <strong>" . strtoupper($new_status) . "</strong>.</p>";
    sendEmail($customer_email, $email_subject, $email_body);

    $notification_message = "The status of your invoice #{$invoice_number} has been updated to: " . ucfirst($new_status);
    $notification_link = "invoices?invoice_id={$invoice_id}";
    $notification_type = ($new_status === 'paid') ? 'payment_received' : 'payment_due';
    
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
    $stmt_notify->bind_param("isss", $invoice_data['user_id'], $notification_type, $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();
    

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Invoice status updated to '{$new_status}' and customer notified."]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Admin Invoice Status Update Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
