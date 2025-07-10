<?php
// api/admin/bookings.php - Admin API for Bookings Management

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in() and has_role()
require_once __DIR__ . '/../../includes/functions.php'; // For sendEmail(), getSystemSetting()

header('Content-Type: application/json');

// Check if user is logged in and has admin role
if (!is_logged_in() || !has_role('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id']; // Admin user ID performing the action
$action = $_REQUEST['action'] ?? ''; // Using $_REQUEST to handle both POST and GET for 'get_booking_by_quote_id'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? null;

    if (empty($booking_id)) {
        echo json_encode(['success' => false, 'message' => 'Booking ID is required for this action.']);
        exit;
    }

    switch ($action) {
        case 'update_status':
            handleUpdateStatus($conn, $booking_id, $user_id);
            break;
        case 'assign_vendor':
            handleAssignVendor($conn, $booking_id, $user_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid POST action.']);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'get_booking_by_quote_id':
            handleGetBookingByQuoteId($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid GET action.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();

function handleUpdateStatus($conn, $booking_id, $admin_user_id) {
    $newStatus = $_POST['status'] ?? '';

    if (empty($newStatus)) {
        echo json_encode(['success' => false, 'message' => 'New status is required.']);
        return;
    }

    $allowedStatuses = [
        'pending', 'scheduled', 'assigned', 'pickedup', 'out_for_delivery',
        'delivered', 'in_use', 'awaiting_pickup', 'completed', 'cancelled',
        'relocated', 'swapped'
    ];
    if (!in_array($newStatus, $allowedStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status provided.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Fetch current booking details and customer info for notification
        $stmt_fetch = $conn->prepare("SELECT b.booking_number, b.status AS old_status, u.id AS user_id, u.first_name, u.email FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.id = ?");
        $stmt_fetch->bind_param("i", $booking_id);
        $stmt_fetch->execute();
        $booking_data = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();

        if (!$booking_data) {
            throw new Exception("Booking not found.");
        }

        $oldStatus = $booking_data['old_status'];
        $customer_user_id = $booking_data['user_id'];
        $customer_email = $booking_data['email'];
        $customer_first_name = $booking_data['first_name'];
        $booking_number = $booking_data['booking_number'];
        $companyName = getSystemSetting('company_name');

        if ($oldStatus === $newStatus) {
            echo json_encode(['success' => true, 'message' => 'Booking status is already ' . htmlspecialchars(strtoupper(str_replace('_', ' ', $newStatus))) . '. No update needed.']);
            $conn->rollback(); // Rollback to ensure no changes
            return;
        }

        // Update booking status
        $stmt_update = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt_update->bind_param("si", $newStatus, $booking_id);
        if (!$stmt_update->execute()) {
            throw new Exception("Failed to update booking status: " . $stmt_update->error);
        }
        $stmt_update->close();

        // Add notification for customer
        $notification_message = "Your booking #BK-{$booking_number} status has been updated to: " . ucwords(str_replace('_', ' ', $newStatus)) . ".";
        $notification_link = "bookings?booking_id={$booking_id}";
        $stmt_add_notification = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
        $notification_type = 'booking_status_update'; // Generic type, could be specific
        $stmt_add_notification->bind_param("isss", $customer_user_id, $notification_type, $notification_message, $notification_link);
        $stmt_add_notification->execute();
        $stmt_add_notification->close();

        // Send email to customer about status change
        $emailSubject = "Your {$companyName} Booking #BK-{$booking_number} Status Update";
        $emailBody = "<p>Dear {$customer_first_name},</p>
                      <p>The status of your booking #BK-{$booking_number} has been updated to: <strong>" . ucwords(str_replace('_', ' ', $newStatus)) . "</strong>.</p>
                      <p>You can view full details in your dashboard: <a href=\"" . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#bookings?booking_id={$booking_id}\">View Booking</a></p>
                      <p>Thank you for choosing {$companyName}!</p>";
        $emailAltBody = "Dear {$customer_first_name},\nThe status of your booking #BK-{$booking_number} has been updated to: " . ucwords(str_replace('_', ' ', $newStatus)) . ".\nView details in your dashboard: " . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#bookings?booking_id={$booking_id}";

        sendEmail($customer_email, $emailSubject, $emailBody, $emailAltBody);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Booking status updated successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update booking status transaction failed for booking ID $booking_id: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update booking status: ' . $e->getMessage()]);
    }
}

function handleAssignVendor($conn, $booking_id, $admin_user_id) {
    $vendorId = $_POST['vendor_id'] ?? null;

    if (empty($vendorId)) {
        echo json_encode(['success' => false, 'message' => 'Vendor ID is required.']);
        return;
    }

    // Validate vendor_id exists
    $stmt_vendor_check = $conn->prepare("SELECT id, name, email, phone_number FROM vendors WHERE id = ? AND is_active = TRUE");
    $stmt_vendor_check->bind_param("i", $vendorId);
    $stmt_vendor_check->execute();
    $vendor_data = $stmt_vendor_check->get_result()->fetch_assoc();
    $stmt_vendor_check->close();

    if (!$vendor_data) {
        echo json_encode(['success' => false, 'message' => 'Selected vendor not found or is inactive.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Fetch current booking details and customer info for notification
        $stmt_fetch = $conn->prepare("SELECT b.booking_number, b.vendor_id, u.id AS user_id, u.first_name, u.email FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.id = ?");
        $stmt_fetch->bind_param("i", $booking_id);
        $stmt_fetch->execute();
        $booking_data = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();

        if (!$booking_data) {
            throw new Exception("Booking not found.");
        }

        $customer_user_id = $booking_data['user_id'];
        $customer_email = $booking_data['email'];
        $customer_first_name = $booking_data['first_name'];
        $booking_number = $booking_data['booking_number'];
        $companyName = getSystemSetting('company_name');

        if ($booking_data['vendor_id'] == $vendorId) {
             echo json_encode(['success' => true, 'message' => 'Vendor is already assigned to this booking. No update needed.']);
             $conn->rollback();
             return;
        }

        // Update booking to assign vendor
        $stmt_update = $conn->prepare("UPDATE bookings SET vendor_id = ? WHERE id = ?");
        $stmt_update->bind_param("ii", $vendorId, $booking_id);
        if (!$stmt_update->execute()) {
            throw new Exception("Failed to assign vendor: " . $stmt_update->error);
        }
        $stmt_update->close();

        // Add notification for customer about vendor assignment
        $notification_message = "Your booking #BK-{$booking_number} has been assigned to {$vendor_data['name']}.";
        $notification_link = "bookings?booking_id={$booking_id}";
        $stmt_add_notification = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
        $notification_type = 'booking_assigned_vendor'; // New type
        $stmt_add_notification->bind_param("isss", $customer_user_id, $notification_type, $notification_message, $notification_link);
        $stmt_add_notification->execute();
        $stmt_add_notification->close();

        // Send email to customer about vendor assignment
        $emailSubject = "Vendor Assigned for Your {$companyName} Booking #BK-{$booking_number}";
        $emailBody = "<p>Dear {$customer_first_name},</p>
                      <p>Your booking #BK-{$booking_number} has been assigned to our trusted partner: <strong>{$vendor_data['name']}</strong>.</p>
                      <p>You can view updated details in your dashboard: <a href=\"" . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#bookings?booking_id={$booking_id}\">View Booking</a></p>
                      <p>Thank you for choosing {$companyName}!</p>";
        $emailAltBody = "Dear {$customer_first_name},\nYour booking #BK-{$booking_number} has been assigned to {$vendor_data['name']}.\nView details in your dashboard: " . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#bookings?booking_id={$booking_id}";

        sendEmail($customer_email, $emailSubject, $emailBody, $emailAltBody);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Vendor assigned successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Assign vendor transaction failed for booking ID $booking_id: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to assign vendor: ' . $e->getMessage()]);
    }
}

function handleGetBookingByQuoteId($conn) {
    $quoteId = $_GET['quote_id'] ?? null;

    if (empty($quoteId)) {
        echo json_encode(['success' => false, 'message' => 'Quote ID is required.']);
        return;
    }

    $stmt = $conn->prepare("SELECT id FROM bookings WHERE invoice_id IN (SELECT id FROM invoices WHERE quote_id = ?)");
    $stmt->bind_param("i", $quoteId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        echo json_encode(['success' => true, 'booking_id' => $booking['id']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No booking found for this quote.']);
    }
    $stmt->close();
}
?>