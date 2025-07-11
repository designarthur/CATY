<?php
// api/admin/invoices.php - Handles admin actions for invoices, like status changes

session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Security check: Ensure user is a logged-in admin
if (!is_logged_in() || !has_role('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    // CSRF token validation with enhanced logging
    if (!isset($_POST['csrf_token'])) {
        error_log("CSRF token missing in POST data for action: $action");
        throw new Exception('CSRF token missing. Please refresh and try again.');
    }
    error_log("Received CSRF token: " . $_POST['csrf_token']);
    validate_csrf_token();

    switch ($action) {
        case 'update_status':
            $response = handleUpdateStatus($conn);
            break;
        case 'update_invoice':
            $response = handleUpdateInvoice($conn);
            break;
        case 'delete_bulk':
            $response = handleDeleteBulk($conn);
            break;
        default:
            throw new Exception('Invalid action specified.');
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400); // Bad Request for most client-side errors
    error_log("Admin Invoice API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

/**
 * Handles updating the status of a single invoice and creates a booking if status is 'paid'.
 *
 * @param mysqli $conn The database connection object.
 * @return array Response array with success status and message.
 * @throws Exception If parameters are invalid, invoice not found, or database error occurs.
 */
function handleUpdateStatus($conn) {
    $invoice_id = filter_input(INPUT_POST, 'invoice_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['status'] ?? '';

    if (!$invoice_id || empty($new_status)) {
        throw new Exception('Invalid or missing parameters.');
    }
    
    // Validate the new status to ensure it's one of the allowed enum values
    $allowed_statuses = ['pending', 'paid', 'partially_paid', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        throw new Exception('Invalid status provided.');
    }

    $conn->begin_transaction();

    try {
        // 1. Fetch current invoice and customer details for notification
        $stmt_fetch = $conn->prepare("
            SELECT i.invoice_number, i.status AS old_status, i.quote_id, u.id AS user_id, u.first_name, u.email, q.service_type 
            FROM invoices i 
            JOIN users u ON i.user_id = u.id 
            JOIN quotes q ON i.quote_id = q.id 
            WHERE i.id = ?
        ");
        $stmt_fetch->bind_param("i", $invoice_id);
        $stmt_fetch->execute();
        $invoice_data = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();

        if (!$invoice_data) {
            throw new Exception('Invoice or associated quote not found.');
        }
        if ($invoice_data['old_status'] === $new_status) {
            $conn->rollback();
            return ['success' => true, 'message' => 'Invoice status is already set. No update needed.'];
        }

        // 2. Update the invoice status
        $stmt_update = $conn->prepare("UPDATE invoices SET status = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_status, $invoice_id);
        if (!$stmt_update->execute()) {
            throw new Exception("Database error on status update: " . $stmt_update->error);
        }
        $stmt_update->close();

        // 3. If status is 'paid', create a booking
        $booking_id = null;
        if ($new_status === 'paid') {
            // Check if a booking already exists to avoid duplicates
            $stmt_check_booking = $conn->prepare("SELECT id FROM bookings WHERE invoice_id = ?");
            $stmt_check_booking->bind_param("i", $invoice_id);
            $stmt_check_booking->execute();
            $existing_booking = $stmt_check_booking->get_result()->fetch_assoc();
            $stmt_check_booking->close();

            if ($existing_booking) {
                error_log("Booking already exists for invoice_id: $invoice_id, booking_id: {$existing_booking['id']}");
            } else {
                // Create a booking
                $booking_number = 'BOOK-' . strtoupper(generateToken(8));
                $start_date = date('Y-m-d'); // Adjust based on quote or requirements
                $booking_status = 'confirmed';

                $stmt_booking = $conn->prepare("
                    INSERT INTO bookings (quote_id, invoice_id, user_id, booking_number, service_type, start_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_booking->bind_param(
                    "iiissss",
                    $invoice_data['quote_id'],
                    $invoice_id,
                    $invoice_data['user_id'],
                    $booking_number,
                    $invoice_data['service_type'],
                    $start_date,
                    $booking_status
                );
                if (!$stmt_booking->execute()) {
                    throw new Exception("Failed to create booking: " . $stmt_booking->error);
                }
                $booking_id = $conn->insert_id;
                $stmt_booking->close();

                // Create notification for the user about the booking
                $notification_message = "Booking #$booking_number created for your paid invoice #{$invoice_data['invoice_number']}.";
                $notification_link = "bookings?booking_id=$booking_id";
                $stmt_notify_booking = $conn->prepare("
                    INSERT INTO notifications (user_id, type, message, link)
                    VALUES (?, 'booking_created', ?, ?)
                ");
                $stmt_notify_booking->bind_param("iss", $invoice_data['user_id'], $notification_message, $notification_link);
                if (!$stmt_notify_booking->execute()) {
                    error_log("Failed to create booking notification for booking_id: $booking_id");
                }
                $stmt_notify_booking->close();
            }
        }

        // 4. Notify Customer about status update
        $email_subject = "Update on your invoice #{$invoice_data['invoice_number']}";
        $email_body = "<p>Dear {$invoice_data['first_name']},</p><p>The payment status for your invoice #<strong>{$invoice_data['invoice_number']}</strong> has been updated to: <strong>" . strtoupper($new_status) . "</strong>.</p>";
        if ($booking_id) {
            $email_body .= "<p>A booking (#$booking_number) has been created for your service.</p>";
        }
        sendEmail($invoice_data['email'], $email_subject, $email_body);

        $conn->commit();
        $response = ['success' => true, 'message' => "Invoice status updated to '{$new_status}' and customer notified."];
        if ($booking_id) {
            $response['booking_id'] = $booking_id;
            $response['message'] .= " Booking #$booking_number created.";
        }
        return $response;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update status error for invoice_id: $invoice_id, status: $new_status - " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handles updating the details of an invoice, including its line items, discount, and tax.
 *
 * @param mysqli $conn The database connection object.
 * @return array Response array with success status and message.
 * @throws Exception If input is invalid or a database error occurs.
 */
function handleUpdateInvoice($conn) {
    $invoice_id = filter_input(INPUT_POST, 'invoice_id', FILTER_VALIDATE_INT);
    $items = json_decode($_POST['items'] ?? '[]', true);
    $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT);
    $tax = filter_input(INPUT_POST, 'tax', FILTER_VALIDATE_FLOAT);

    if (!$invoice_id || !is_array($items)) {
        throw new Exception("Invalid input. Invoice ID and items are required.");
    }

    $conn->begin_transaction();

    try {
        // 1. Delete old line items
        $stmt_delete = $conn->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
        $stmt_delete->bind_param("i", $invoice_id);
        if (!$stmt_delete->execute()) {
            throw new Exception("Failed to delete old invoice items: " . $stmt_delete->error);
        }
        $stmt_delete->close();

        // 2. Insert new line items and calculate total amount
        $total_amount = 0;
        $stmt_insert = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($items as $item) {
            $description = $item['description'] ?? 'No description';
            $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
            $unit_price = filter_var($item['unit_price'], FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $item_total = $quantity * $unit_price;
            $total_amount += $item_total;

            $stmt_insert->bind_param("isidd", $invoice_id, $description, $quantity, $unit_price, $item_total);
            if (!$stmt_insert->execute()) {
                throw new Exception("Failed to insert invoice item: " . $stmt_insert->error);
            }
        }
        $stmt_insert->close();

        // 3. Apply discount and tax to the final amount
        $final_amount = ($total_amount - ($discount ?? 0)) + ($tax ?? 0);

        // 4. Update the main invoice record with new totals
        $stmt_update_invoice = $conn->prepare("UPDATE invoices SET amount = ?, discount = ?, tax = ? WHERE id = ?");
        $stmt_update_invoice->bind_param("dddi", $final_amount, $discount, $tax, $invoice_id);
        if (!$stmt_update_invoice->execute()) {
            throw new Exception("Failed to update invoice: " . $stmt_update_invoice->error);
        }
        $stmt_update_invoice->close();
        
        $conn->commit();
        return ['success' => true, 'message' => 'Invoice updated successfully.'];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update invoice error for invoice_id: $invoice_id - " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handles bulk deletion of invoices.
 * Before deleting invoices, it deletes any associated bookings
 * to satisfy foreign key constraints.
 *
 * @param mysqli $conn The database connection object.
 * @return array Response array with success status and message.
 * @throws Exception If no invoice IDs are provided or a database error occurs.
 */
function handleDeleteBulk($conn) {
    $invoice_ids = $_POST['invoice_ids'] ?? [];
    if (empty($invoice_ids) || !is_array($invoice_ids)) {
        throw new Exception("No invoice IDs provided for bulk deletion.");
    }

    $conn->begin_transaction();

    try {
        $placeholders = implode(',', array_fill(0, count($invoice_ids), '?'));
        $types = str_repeat('i', count($invoice_ids));
        
        // 1. Get booking IDs associated with these invoices
        $stmt_fetch_bookings = $conn->prepare("SELECT id FROM bookings WHERE invoice_id IN ($placeholders)");
        $stmt_fetch_bookings->bind_param($types, ...$invoice_ids);
        $stmt_fetch_bookings->execute();
        $result_bookings = $stmt_fetch_bookings->get_result();
        $booking_ids_to_delete = [];
        while($row = $result_bookings->fetch_assoc()) {
            $booking_ids_to_delete[] = $row['id'];
        }
        $stmt_fetch_bookings->close();

        // 2. If there are associated bookings, delete them
        if (!empty($booking_ids_to_delete)) {
            $booking_placeholders = implode(',', array_fill(0, count($booking_ids_to_delete), '?'));
            $booking_types = str_repeat('i', count($booking_ids_to_delete));
            
            $stmt_delete_bookings = $conn->prepare("DELETE FROM bookings WHERE id IN ($booking_placeholders)");
            $stmt_delete_bookings->bind_param($booking_types, ...$booking_ids_to_delete);
            if (!$stmt_delete_bookings->execute()) {
                throw new Exception("Failed to delete associated bookings: " . $stmt_delete_bookings->error);
            }
            $stmt_delete_bookings->close();
        }

        // 3. Delete the invoices
        $stmt_delete_invoices = $conn->prepare("DELETE FROM invoices WHERE id IN ($placeholders)");
        $stmt_delete_invoices->bind_param($types, ...$invoice_ids);
        if (!$stmt_delete_invoices->execute()) {
            throw new Exception("Failed to delete invoices: " . $stmt_delete_invoices->error);
        }
        $stmt_delete_invoices->close();

        $conn->commit();
        return ['success' => true, 'message' => 'Selected invoices and their associated bookings have been deleted.'];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Bulk delete error: " . $e->getMessage());
        throw $e;
    }
}
?>