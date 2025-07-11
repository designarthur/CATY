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

try {
    switch ($action) {
        case 'update_status':
            handleUpdateStatus($conn);
            break;
        case 'update_invoice':
            handleUpdateInvoice($conn);
            break;
        case 'delete_bulk':
            handleDeleteBulk($conn);
            break;
        default:
            throw new Exception('Invalid action specified.');
    }
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
 * Handles updating the status of a single invoice.
 *
 * @param mysqli $conn The database connection object.
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

    // 1. Fetch current invoice and customer details for notification
    $stmt_fetch = $conn->prepare("SELECT i.invoice_number, i.status AS old_status, u.id AS user_id, u.first_name, u.email FROM invoices i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
    $stmt_fetch->bind_param("i", $invoice_id);
    $stmt_fetch->execute();
    $invoice_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$invoice_data) {
        throw new Exception('Invoice not found.');
    }
    if ($invoice_data['old_status'] === $new_status) {
        echo json_encode(['success' => true, 'message' => 'Invoice status is already set. No update needed.']);
        $conn->rollback();
        return;
    }

    // 2. Update the invoice status
    $stmt_update = $conn->prepare("UPDATE invoices SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $new_status, $invoice_id);
    if (!$stmt_update->execute()) {
        throw new Exception("Database error on status update: " . $stmt_update->error);
    }
    $stmt_update->close();

    // 3. Notify Customer
    $email_subject = "Update on your invoice #{$invoice_data['invoice_number']}";
    $email_body = "<p>Dear {$invoice_data['first_name']},</p><p>The payment status for your invoice #<strong>{$invoice_data['invoice_number']}</strong> has been updated to: <strong>" . strtoupper($new_status) . "</strong>.</p>";
    sendEmail($invoice_data['email'], $email_subject, $email_body);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Invoice status updated to '{$new_status}' and customer notified."]);
}

/**
 * Handles updating the details of an invoice, including its line items, discount, and tax.
 *
 * @param mysqli $conn The database connection object.
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

    // 1. Delete old line items
    $stmt_delete = $conn->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
    $stmt_delete->bind_param("i", $invoice_id);
    $stmt_delete->execute();
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
        $stmt_insert->execute();
    }
    $stmt_insert->close();

    // 3. Apply discount and tax to the final amount
    $final_amount = ($total_amount - $discount) + $tax;

    // 4. Update the main invoice record with new totals
    $stmt_update_invoice = $conn->prepare("UPDATE invoices SET amount = ?, discount = ?, tax = ? WHERE id = ?");
    $stmt_update_invoice->bind_param("dddi", $final_amount, $discount, $tax, $invoice_id);
    $stmt_update_invoice->execute();
    $stmt_update_invoice->close();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Invoice updated successfully.']);
}

/**
 * Handles bulk deletion of invoices.
 * Before deleting invoices, it deletes any associated bookings
 * to satisfy foreign key constraints.
 *
 * @param mysqli $conn The database connection object.
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
        // We fetch the booking IDs to delete them explicitly, as invoice_id is NOT NULL
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
        // This will also trigger ON DELETE CASCADE for booking_status_history and booking_charges
        if (!empty($booking_ids_to_delete)) {
            $booking_placeholders = implode(',', array_fill(0, count($booking_ids_to_delete), '?'));
            $booking_types = str_repeat('i', count($booking_ids_to_delete));
            
            $stmt_delete_bookings = $conn->prepare("DELETE FROM bookings WHERE id IN ($booking_placeholders)");
            $stmt_delete_bookings->bind_param($booking_types, ...$booking_ids_to_delete);
            if (!$stmt_delete_bookings->execute()) {
                throw new Exception("Failed to delete associated bookings.");
            }
            $stmt_delete_bookings->close();
        }

        // 3. Delete the invoices
        // Note: invoice_items will be automatically deleted due to ON DELETE CASCADE constraint.
        $stmt_delete_invoices = $conn->prepare("DELETE FROM invoices WHERE id IN ($placeholders)");
        $stmt_delete_invoices->bind_param($types, ...$invoice_ids);
        
        if ($stmt_delete_invoices->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Selected invoices and their associated bookings have been deleted.']);
        } else {
            throw new Exception("Failed to delete invoices.");
        }
        $stmt_delete_invoices->close();
    } catch (Exception $e) {
        $conn->rollback();
        throw $e; // Re-throw the exception
    }
}
?>
