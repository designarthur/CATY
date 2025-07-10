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

// Determine request method and route to the appropriate logic
$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

try {
    if ($request_method === 'POST') {
        switch ($action) {
            case 'update_status':
                handleUpdateStatus($conn);
                break;
            case 'assign_vendor':
                handleAssignVendor($conn);
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
    http_response_code(400); // Bad Request for most client-side errors
    error_log("Admin Bookings API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}


// --- Handler Functions ---

/**
 * Handles updating the status of a booking.
 */
function handleUpdateStatus($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $newStatus = trim($_POST['status'] ?? '');

    if (!$booking_id || empty($newStatus)) {
        throw new Exception('Booking ID and new status are required.');
    }

    // **THE FIX IS HERE**: The list of allowed statuses now matches the updated database schema
    // and the dropdown menu in the admin panel.
    $allowedStatuses = [
        'pending', 'scheduled', 'assigned', 'pickedup', 'out_for_delivery',
        'delivered', 'in_use', 'awaiting_pickup', 'completed', 'cancelled',
        'relocated', 'swapped'
    ];
    if (!in_array($newStatus, $allowedStatuses)) {
        throw new Exception('Invalid status value provided.');
    }

    $conn->begin_transaction();

    // Fetch booking details for notification
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

    // 1. Update the booking status
    $stmt_update = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $newStatus, $booking_id);
    if (!$stmt_update->execute()) {
        throw new Exception("Database error on status update: " . $stmt_update->error);
    }
    $stmt_update->close();

    // 2. Create a notification and send email to the customer
    $notification_message = "Your booking #BK-{$booking_data['booking_number']} has been updated to: " . ucwords(str_replace('_', ' ', $newStatus)) . ".";
    $notification_link = "bookings?booking_id={$booking_id}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_status_update', ?, ?)");
    $stmt_notify->bind_param("iss", $booking_data['user_id'], $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    // Send email
    $emailBody = "<p>Dear {$booking_data['first_name']},</p><p>The status of your booking #BK-{$booking_data['booking_number']} has been updated to: <strong>" . ucwords(str_replace('_', ' ', $newStatus)) . "</strong>.</p>";
    sendEmail($booking_data['email'], "Update on your Booking #BK-{$booking_data['booking_number']}", $emailBody);


    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Booking status updated successfully!']);
}


/**
 * Handles assigning a vendor to a booking.
 */
function handleAssignVendor($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $vendor_id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);

    if (!$booking_id || !$vendor_id) {
        throw new Exception('Booking ID and Vendor ID are required.');
    }
    
    // In a real-world scenario, you might also want to automatically
    // change the booking status to 'assigned' here.
    $stmt = $conn->prepare("UPDATE bookings SET vendor_id = ?, status = 'assigned' WHERE id = ?");
    $stmt->bind_param("ii", $vendor_id, $booking_id);
    $stmt->execute();

    if($stmt->affected_rows > 0) {
        // You should also add logic here to notify the customer and/or the vendor.
        echo json_encode(['success' => true, 'message' => 'Vendor assigned successfully and status updated to "Assigned".']);
    } else {
        throw new Exception('Failed to assign vendor or vendor was already assigned.');
    }
    $stmt->close();
}


/**
 * Handles retrieving a booking ID from a quote ID.
 */
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