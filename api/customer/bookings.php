<?php
// api/customer/bookings.php - Handles customer-initiated booking actions like relocation, swap, and pickup requests.

// --- Setup & Includes ---
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// --- Security & Authorization ---
if (!is_logged_in()) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// --- Action Routing ---
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'request_relocation':
            handleServiceRequest($conn, 'relocation');
            break;
        case 'request_swap':
            handleServiceRequest($conn, 'swap');
            break;
        case 'schedule_pickup':
            handleSchedulePickup($conn);
            break;
        case 'request_extension':
            handleRequestExtension($conn);
            break;
        default:
            throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    http_response_code(400);
    error_log("Customer Booking API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}


// --- Handler Functions ---

function handleServiceRequest($conn, $serviceType) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];
    $new_address = trim($_POST['new_address'] ?? null);

    if (!$booking_id) {
        throw new Exception('A valid Booking ID is required.');
    }
    if ($serviceType === 'relocation' && empty($new_address)) {
        throw new Exception('A new address is required for relocation requests.');
    }

    $charge_column = ($serviceType === 'relocation') ? 'relocation_charge' : 'swap_charge';
    $included_column = ($serviceType === 'relocation') ? 'is_relocation_included' : 'is_swap_included';
    $service_name = ucwords($serviceType);

    $conn->begin_transaction();

    // 1. Fetch the booking, its original quote, and the specific service flags/charges
    $stmt_fetch = $conn->prepare("
        SELECT q.{$charge_column}, q.{$included_column}
        FROM bookings b
        JOIN invoices i ON b.invoice_id = i.id
        JOIN quotes q ON i.quote_id = q.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt_fetch->bind_param("ii", $booking_id, $user_id);
    $stmt_fetch->execute();
    $quote_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$quote_data) {
        throw new Exception("Could not find the specified booking or its original quote.");
    }

    $is_included = (bool)$quote_data[$included_column];
    $charge_amount = (float)$quote_data[$charge_column];

    // Scenario A: Service is pre-paid/included
    if ($is_included) {
        // Log the request and notify admin to schedule it
        $notes = "Customer initiated pre-paid {$service_name} request.";
        if ($serviceType === 'relocation') {
            $notes .= " New address: {$new_address}";
            // You might want to update the booking's delivery_location here or have admin confirm it first
        }
        $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, ?, ?)");
        $status_log = $serviceType . '_requested'; // e.g., 'relocation_requested'
        $stmt_log->bind_param("iss", $booking_id, $status_log, $notes);
        $stmt_log->execute();
        $stmt_log->close();

        // Notify customer that the request is being scheduled
        $notification_message = "Your pre-paid {$service_name} request for booking #{$booking_id} has been received and is being scheduled by our team.";
        $notification_link = "bookings?booking_id={$booking_id}";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_status_update', ?, ?)");
        $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
        $stmt_notify->execute();
        $stmt_notify->close();
        
        // TODO: Notify Admin to take action

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Your pre-paid ' . $service_name . ' request has been sent for scheduling!']);
        return;
    }

    // Scenario B: Service requires payment
    if ($charge_amount <= 0) {
        throw new Exception("This service is not available or has no charge associated with it. Please contact support.");
    }
    
    // Create a new invoice for this service request
    $invoice_number = 'INV-' . strtoupper(substr($serviceType, 0, 3)) . '-' . generateToken(6);
    $due_date = date('Y-m-d', strtotime('+3 days')); 

    $stmt_invoice = $conn->prepare("INSERT INTO invoices (user_id, booking_id, invoice_number, amount, status, due_date, notes) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
    $invoice_notes = "Invoice for {$service_name} Request on Booking ID {$booking_id}";
    $stmt_invoice->bind_param("iisdss", $user_id, $booking_id, $invoice_number, $charge_amount, $due_date, $invoice_notes);
    $stmt_invoice->execute();
    $new_invoice_id = $conn->insert_id;
    $stmt_invoice->close();

    // Create a notification for the customer
    $notification_message = "Your {$service_name} request requires payment. Please pay invoice #{$invoice_number} to proceed.";
    $notification_link = "invoices?invoice_id={$new_invoice_id}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'payment_due', ?, ?)");
    $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "{$service_name} request submitted! Please complete the payment for the new invoice.",
        'invoice_id' => $new_invoice_id
    ]);
}


function handleSchedulePickup($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $pickup_date = trim($_POST['pickup_date'] ?? '');
    $pickup_time = trim($_POST['pickup_time'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (!$booking_id || empty($pickup_date) || empty($pickup_time)) {
        throw new Exception('Booking ID, pickup date, and pickup time are required.');
    }

    $conn->begin_transaction();

    $stmt_update = $conn->prepare("UPDATE bookings SET pickup_date = ?, pickup_time = ?, status = 'awaiting_pickup' WHERE id = ? AND user_id = ?");
    $stmt_update->bind_param("ssii", $pickup_date, $pickup_time, $booking_id, $user_id);
    $stmt_update->execute();
    
    if ($stmt_update->affected_rows === 0) {
        throw new Exception("Booking not found or you don't have permission to update it.");
    }
    $stmt_update->close();
    
    $notes = "Customer scheduled pickup for {$pickup_date} at {$pickup_time}.";
    $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, 'awaiting_pickup', ?)");
    $stmt_log->bind_param("is", $booking_id, $notes);
    $stmt_log->execute();
    $stmt_log->close();

    $notification_message = "Your pickup for booking #{$booking_id} has been successfully scheduled for {$pickup_date}. Our team will confirm and process it.";
    $notification_link = "bookings?booking_id={$booking_id}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_status_update', ?, ?)");
    $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Pickup scheduled successfully!']);
}

function handleRequestExtension($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $extension_days = filter_input(INPUT_POST, 'extension_days', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$booking_id || !$extension_days || $extension_days <= 0) {
        throw new Exception('Booking ID and a valid number of extension days are required.');
    }

    $conn->begin_transaction();
    
    // Check if there's already a pending request
    $stmt_check = $conn->prepare("SELECT id FROM booking_extension_requests WHERE booking_id = ? AND status = 'pending'");
    $stmt_check->bind_param("i", $booking_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        throw new Exception('You already have a pending extension request for this booking.');
    }
    $stmt_check->close();

    // 1. Log the extension request
    $stmt_request = $conn->prepare("INSERT INTO booking_extension_requests (booking_id, user_id, requested_days) VALUES (?, ?, ?)");
    $stmt_request->bind_param("iii", $booking_id, $user_id, $extension_days);
    $stmt_request->execute();
    $stmt_request->close();

    // 2. Notify admin about the new request
    $admin_notification_message = "Customer has requested a {$extension_days}-day extension for Booking ID #{$booking_id}. Please review and approve.";
    $admin_notification_link = "bookings?booking_id={$booking_id}";
    // Assuming admin user_id is 1 for system-wide notifications
    $stmt_admin_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (1, 'system_message', ?, ?)");
    $stmt_admin_notify->bind_param("ss", $admin_notification_message, $admin_notification_link);
    $stmt_admin_notify->execute();
    $stmt_admin_notify->close();

    // 3. Notify customer that the request has been submitted
    $notification_message = "Your request to extend Booking #{$booking_id} by {$extension_days} days has been submitted for approval.";
    $notification_link = "bookings?booking_id={$booking_id}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_status_update', ?, ?)");
    $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();
    
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Your extension request has been submitted and is awaiting admin approval.'
    ]);
}
?>