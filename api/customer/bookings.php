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

// --- CSRF Protection ---
// Important: Assumes you have added a CSRF token to your new modals in bookings.php
// try {
//     validate_csrf_token();
// } catch (Exception $e) {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
//     exit;
// }

// --- Action Routing ---
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'request_relocation':
            handleRelocationRequest($conn);
            break;
        case 'request_swap':
            handleSwapRequest($conn);
            break;
        case 'schedule_pickup':
            handleSchedulePickup($conn);
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

/**
 * Handles a service request that requires payment (Relocation, Swap).
 * Creates a new invoice for the service charge.
 */
function handlePaidServiceRequest($conn, $serviceType) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$booking_id) {
        throw new Exception('A valid Booking ID is required.');
    }

    $charge_column = ($serviceType === 'relocation') ? 'relocation_charge' : 'swap_charge';
    $service_name = ucwords($serviceType);

    $conn->begin_transaction();

    // 1. Fetch the original booking and the specific charge from its quote
    $stmt_fetch = $conn->prepare("
        SELECT q.{$charge_column}
        FROM bookings b
        JOIN invoices i ON b.invoice_id = i.id
        JOIN quotes q ON i.quote_id = q.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt_fetch->bind_param("ii", $booking_id, $user_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$result) {
        throw new Exception("Could not find the specified booking or its original quote.");
    }

    $charge_amount = (float) $result[$charge_column];
    if ($charge_amount <= 0) {
        throw new Exception("This service is not available or has no charge associated with it. Please contact support.");
    }

    // 2. Create a new invoice for this service request
    $invoice_number = 'INV-' . strtoupper($serviceType) . '-' . generateToken(6);
    $due_date = date('Y-m-d', strtotime('+3 days')); // Due in 3 days

    $stmt_invoice = $conn->prepare("INSERT INTO invoices (user_id, booking_id, invoice_number, amount, status, due_date, notes) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
    $notes = "Invoice for {$service_name} Request on Booking ID {$booking_id}";
    $stmt_invoice->bind_param("iisdss", $user_id, $booking_id, $invoice_number, $charge_amount, $due_date, $notes);
    $stmt_invoice->execute();
    $new_invoice_id = $conn->insert_id;
    $stmt_invoice->close();

    // 3. Create a notification for the customer
    $notification_message = "Your {$service_name} request has been submitted. Please pay the new invoice (#{$invoice_number}) to proceed.";
    $notification_link = "invoices?invoice_id={$new_invoice_id}"; // Link to the new invoice
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'payment_due', ?, ?)");
    $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $conn->commit();

    return ['new_invoice_id' => $new_invoice_id];
}

function handleRelocationRequest($conn) {
    $response = handlePaidServiceRequest($conn, 'relocation');
    echo json_encode([
        'success' => true,
        'message' => 'Relocation request submitted! Please complete the payment for the new invoice.',
        'invoice_id' => $response['new_invoice_id']
    ]);
}

function handleSwapRequest($conn) {
    $response = handlePaidServiceRequest($conn, 'swap');
    echo json_encode([
        'success' => true,
        'message' => 'Swap request submitted! Please complete the payment for the new invoice.',
        'invoice_id' => $response['new_invoice_id']
    ]);
}


/**
 * Handles scheduling a pickup, which does not require payment.
 */
function handleSchedulePickup($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $pickup_date = trim($_POST['pickup_date'] ?? '');
    $pickup_time = trim($_POST['pickup_time'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (!$booking_id || empty($pickup_date) || empty($pickup_time)) {
        throw new Exception('Booking ID, pickup date, and pickup time are required.');
    }

    $conn->begin_transaction();

    // 1. Update the booking with pickup info and change status to 'awaiting_pickup'
    $stmt_update = $conn->prepare("UPDATE bookings SET pickup_date = ?, pickup_time = ?, status = 'awaiting_pickup' WHERE id = ? AND user_id = ?");
    $stmt_update->bind_param("ssii", $pickup_date, $pickup_time, $booking_id, $user_id);
    $stmt_update->execute();
    
    if ($stmt_update->affected_rows === 0) {
        throw new Exception("Booking not found or you don't have permission to update it.");
    }
    $stmt_update->close();
    
    // 2. Log this event to the history table
    $notes = "Customer scheduled pickup for {$pickup_date} at {$pickup_time}.";
    $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, 'awaiting_pickup', ?)");
    $stmt_log->bind_param("is", $booking_id, $notes);
    $stmt_log->execute();
    $stmt_log->close();

    // 3. Create notification for the customer
    $notification_message = "Your pickup for booking #{$booking_id} has been successfully scheduled for {$pickup_date}.";
    $notification_link = "bookings?booking_id={$booking_id}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_status_update', ?, ?)");
    $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Pickup scheduled successfully!']);
}
?>