<?php
// api/admin/bookings.php - Admin API for Bookings Management

// --- Setup & Includes ---
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// --- Security & Authorization ---
if (!is_logged_in() || !has_role('admin')) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// --- Request Routing ---
$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST to handle GET or POST actions

try {
    if ($request_method === 'POST') {
        switch ($action) {
            case 'update_status':
                handleUpdateStatus($conn);
                break;
            case 'assign_vendor':
                handleAssignVendor($conn);
                break;
            case 'add_charge':
                handleAddCharge($conn);
                break;
            default:
                throw new Exception('Invalid POST action specified.');
        }
    } elseif ($request_method === 'GET') {
        switch ($action) {
            case 'get_booking_by_quote_id':
                handleGetBookingByQuoteId($conn);
                break;
            default:
                throw new Exception('Invalid GET action specified.');
        }
    } else {
        http_response_code(405); // Method Not Allowed
        throw new Exception('Invalid request method.');
    }
} catch (Exception $e) {
    // Catch any exceptions thrown from handler functions
    http_response_code(400); // Bad Request for most client-side errors
    error_log("Admin Bookings API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}


// --- Handler Functions ---

function handleUpdateStatus($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $newStatus = trim($_POST['status'] ?? '');
    $notes = "Status updated to " . ucwords(str_replace('_', ' ', $newStatus)) . " by admin.";

    if (!$booking_id || empty($newStatus)) {
        throw new Exception('Booking ID and new status are required.');
    }

    $allowedStatuses = [
        'pending', 'scheduled', 'assigned', 'pickedup', 'out_for_delivery',
        'delivered', 'in_use', 'awaiting_pickup', 'completed', 'cancelled',
        'relocated', 'swapped'
    ];
    if (!in_array($newStatus, $allowedStatuses)) {
        throw new Exception('Invalid status value provided.');
    }

    $conn->begin_transaction();

    $stmt_fetch = $conn->prepare("SELECT b.booking_number, b.status AS old_status, u.id as user_id, u.first_name, u.email FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.id = ?");
    $stmt_fetch->bind_param("i", $booking_id);
    $stmt_fetch->execute();
    $booking_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$booking_data) {
        throw new Exception("Booking not found.");
    }
    if ($booking_data['old_status'] === $newStatus) {
        echo json_encode(['success' => true, 'message' => 'Booking status is already set. No update needed.']);
        $conn->rollback();
        return;
    }

    $stmt_update = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $newStatus, $booking_id);
    if (!$stmt_update->execute()) {
        throw new Exception("Database error on status update: " . $stmt_update->error);
    }
    $stmt_update->close();

    $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, ?, ?)");
    $stmt_log->bind_param("iss", $booking_id, $newStatus, $notes);
    if (!$stmt_log->execute()) {
        throw new Exception("Failed to log status history: " . $stmt_log->error);
    }
    $stmt_log->close();

    $notification_message = "Your booking #BK-{$booking_data['booking_number']} has been updated to: " . ucwords(str_replace('_', ' ', $newStatus)) . ".";
    $notification_link = "bookings?booking_id={$booking_id}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_status_update', ?, ?)");
    $stmt_notify->bind_param("iss", $booking_data['user_id'], $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $emailBody = "<p>Dear {$booking_data['first_name']},</p><p>The status of your booking #BK-{$booking_data['booking_number']} has been updated to: <strong>" . ucwords(str_replace('_', ' ', $newStatus)) . "</strong>.</p>";
    sendEmail($booking_data['email'], "Update on your Booking #BK-{$booking_data['booking_number']}", $emailBody);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Booking status updated successfully!']);
}


function handleAssignVendor($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $vendor_id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);

    if (!$booking_id || !$vendor_id) {
        throw new Exception('Booking ID and Vendor ID are required.');
    }

    $conn->begin_transaction();
    
    $stmt_update = $conn->prepare("UPDATE bookings SET vendor_id = ?, status = 'assigned' WHERE id = ?");
    $stmt_update->bind_param("ii", $vendor_id, $booking_id);
    $stmt_update->execute();
    
    if($stmt_update->affected_rows > 0) {
        $notes = "Booking assigned to a vendor by admin.";
        $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, 'assigned', ?)");
        $stmt_log->bind_param("is", $booking_id, $notes);
        $stmt_log->execute();
        $stmt_log->close();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Vendor assigned successfully and status updated.']);
    } else {
        $conn->rollback();
        throw new Exception('Failed to assign vendor or vendor was already assigned.');
    }
    $stmt_update->close();
}

function handleAddCharge($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $charge_type = trim($_POST['charge_type'] ?? '');
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $description = trim($_POST['description'] ?? '');
    $admin_user_id = $_SESSION['user_id'];

    if (!$booking_id || empty($charge_type) || !$amount || $amount <= 0 || empty($description)) {
        throw new Exception('Booking ID, charge type, a valid amount, and description are required.');
    }
    
    $conn->begin_transaction();
    
    // 1. Fetch booking user for invoice creation
    $stmt_user = $conn->prepare("SELECT user_id FROM bookings WHERE id = ?");
    $stmt_user->bind_param("i", $booking_id);
    $stmt_user->execute();
    $user_id = $stmt_user->get_result()->fetch_assoc()['user_id'];
    $stmt_user->close();

    if(!$user_id) {
        throw new Exception('Could not find the user associated with this booking.');
    }

    // 2. Insert into booking_charges
    $stmt_charge = $conn->prepare("INSERT INTO booking_charges (booking_id, charge_type, amount, description, created_by_admin_id) VALUES (?, ?, ?, ?, ?)");
    $stmt_charge->bind_param("isdsi", $booking_id, $charge_type, $amount, $description, $admin_user_id);
    if(!$stmt_charge->execute()) {
        throw new Exception("Failed to save the additional charge: " . $stmt_charge->error);
    }
    $charge_id = $conn->insert_id;
    $stmt_charge->close();

    // 3. Create a new invoice for this charge
    $invoice_number = 'INV-CHG-' . strtoupper(generateToken(6));
    $due_date = date('Y-m-d', strtotime('+14 days'));
    $notes = "Additional charge for Booking #{$booking_id}: " . $description;

    $stmt_invoice = $conn->prepare("INSERT INTO invoices (user_id, booking_id, invoice_number, amount, status, due_date, notes) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
    $stmt_invoice->bind_param("iisdss", $user_id, $booking_id, $invoice_number, $amount, $due_date, $notes);
     if(!$stmt_invoice->execute()) {
        throw new Exception("Failed to create invoice for the charge: " . $stmt_invoice->error);
    }
    $invoice_id = $conn->insert_id;
    $stmt_invoice->close();
    
    // 4. Link the charge to the new invoice
    $stmt_link = $conn->prepare("UPDATE booking_charges SET invoice_id = ? WHERE id = ?");
    $stmt_link->bind_param("ii", $invoice_id, $charge_id);
    $stmt_link->execute();
    $stmt_link->close();

    // 5. Notify Customer
    $notification_message = "An additional charge of $" . number_format($amount, 2) . " for '{$description}' has been added to Booking #{$booking_id}.";
    $notification_link = "invoices?invoice_id={$invoice_id}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'payment_due', ?, ?)");
    $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Additional charge added and invoice generated successfully.']);
}


function handleGetBookingByQuoteId($conn) {
    $quoteId = filter_input(INPUT_GET, 'quote_id', FILTER_VALIDATE_INT);
    if (!$quoteId) {
        throw new Exception('Quote ID is required.');
    }

    $stmt = $conn->prepare("SELECT b.id FROM bookings b JOIN invoices i ON b.invoice_id = i.id WHERE i.quote_id = ?");
    $stmt->bind_param("i", $quoteId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        echo json_encode(['success' => true, 'booking_id' => $booking['id']]);
    } else {
        throw new Exception('No booking found for this quote.');
    }
    $stmt->close();
}
?>