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
        SELECT i.invoice_number, i.status AS old_status, u.id AS user_id, u.first_name, u.email
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

    // 3. If status is updated to 'paid', create the booking
    if ($new_status === 'paid') {
        // This function now contains all the logic for creating the booking and sending notifications
        createBookingFromInvoice($conn, $invoice_id);
    } else {
        // For other status changes, just send a simple notification
        $customer_name = htmlspecialchars($invoice_data['first_name']);
        $invoice_number = htmlspecialchars($invoice_data['invoice_number']);
        $customer_email = $invoice_data['email'];
        $companyName = getSystemSetting('company_name') ?? 'Your Company';
        
        $email_subject = "Update on your invoice #{$invoice_number}";
        $email_body = "<p>Dear {$customer_name},</p><p>The payment status for your invoice #<strong>{$invoice_number}</strong> has been updated to: <strong>" . strtoupper($new_status) . "</strong>.</p>";
        sendEmail($customer_email, $email_subject, $email_body);

        $notification_message = "The status of your invoice #{$invoice_number} has been updated to: " . ucfirst($new_status);
        $notification_link = "invoices?invoice_id={$invoice_id}";
        $notification_type = 'payment_due'; // Generic type
        
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
        $stmt_notify->bind_param("isss", $invoice_data['user_id'], $notification_type, $notification_message, $notification_link);
        $stmt_notify->execute();
        $stmt_notify->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Invoice status updated to '{$new_status}' and customer notified."]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Admin Invoice Status Update Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>