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

/**
 * Handles updating the status of a booking and logs the event.
 */
function handleUpdateStatus($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $newStatus = trim($_POST['status'] ?? '');
    $notes = "Status updated to " . ucwords(str_replace('_', ' ', $newStatus)) . " by admin.";

    if (!$booking_id || empty($newStatus)) {
        throw new Exception('Booking ID and new status are required.');
    }

    // This list MUST match the ENUM in your database and the dropdown in the admin panel.
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

    // 1. Update the booking status in the main `bookings` table
    $stmt_update = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $newStatus, $booking_id);
    if (!$stmt_update->execute()) {
        throw new Exception("Database error on status update: " . $stmt_update->error);
    }
    $stmt_update->close();

    // 2. **NEW**: Log this event to the `booking_status_history` table for the timeline feature.
    $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, ?, ?)");
    $stmt_log->bind_param("iss", $booking_id, $newStatus, $notes);
    if (!$stmt_log->execute()) {
        throw new Exception("Failed to log status history: " . $stmt_log->error);
    }
    $stmt_log->close();


    // 3. Create a notification and send an email to the customer
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

    $conn->begin_transaction();
    
    // Update booking with vendor and set status to 'assigned'
    $stmt_update = $conn->prepare("UPDATE bookings SET vendor_id = ?, status = 'assigned' WHERE id = ?");
    $stmt_update->bind_param("ii", $vendor_id, $booking_id);
    $stmt_update->execute();
    
    if($stmt_update->affected_rows > 0) {
        // Log the assignment event
        $notes = "Booking assigned to a vendor by admin.";
        $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, 'assigned', ?)");
        $stmt_log->bind_param("is", $booking_id, $notes);
        $stmt_log->execute();
        $stmt_log->close();

        // You should also add logic here to notify the customer and/or the vendor.
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Vendor assigned successfully and status updated.']);
    } else {
        $conn->rollback();
        throw new Exception('Failed to assign vendor or vendor was already assigned.');
    }
    $stmt_update->close();
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