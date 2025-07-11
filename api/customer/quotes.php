<?php
// api/customer/quotes.php - Handles customer actions on quotes (accept/reject).

// --- Setup & Includes ---
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// --- Security & Authorization ---
if (!is_logged_in() || !has_role('customer')) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// --- CSRF Token Validation ---
// This is the crucial step to prevent CSRF attacks.
try {
    validate_csrf_token();
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
    exit;
}

// --- Input Processing & Action Routing ---
$action = $_POST['action'] ?? '';
$quote_id = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (empty($action) || !$quote_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid request. Missing action or quote ID.']);
    exit;
}

// --- Main Logic ---
try {
    // First, verify the quote belongs to the user and is in a valid state for the action.
    $stmt_verify = $conn->prepare("SELECT status, quoted_price, service_type, daily_rate, swap_charge, relocation_charge, discount, tax FROM quotes WHERE id = ? AND user_id = ?");
    $stmt_verify->bind_param("ii", $quote_id, $user_id);
    $stmt_verify->execute();
    $quote = $stmt_verify->get_result()->fetch_assoc();
    $stmt_verify->close();

    if (!$quote) {
        throw new Exception('Quote not found or you do not have permission to access it.');
    }

    switch ($action) {
        case 'accept_quote':
            if ($quote['status'] !== 'quoted') {
                throw new Exception('This quote cannot be accepted in its current state.');
            }
            $response = handleAcceptQuote($conn, $quote_id, $user_id, $quote);
            break;
        case 'reject_quote':
            if (!in_array($quote['status'], ['quoted', 'pending'])) {
                throw new Exception('This quote cannot be rejected in its current state.');
            }
            $response = handleRejectQuote($conn, $quote_id, $user_id);
            break;
        default:
            throw new Exception('Invalid action specified.');
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400); // Use 400 for most action-related errors
    error_log("Customer Quote API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

// --- Handler Functions ---

/**
 * Handles accepting a quote, which creates a new invoice and populates its line items.
 *
 * @param mysqli $conn The database connection object.
 * @param int $quote_id The ID of the quote being accepted.
 * @param int $user_id The ID of the user accepting the quote.
 * @param array $quote_data The full quote data including pricing and service type.
 * @return array A success/failure response array, including the new invoice_id on success.
 * @throws Exception If invoice creation or item insertion fails.
 */
function handleAcceptQuote($conn, $quote_id, $user_id, $quote_data) {
    $conn->begin_transaction();

    try {
        // Explicitly set timezone to avoid any server configuration issues with date functions.
        date_default_timezone_set('UTC'); 

        // 1. Update Quote Status to 'accepted'
        $stmt_update = $conn->prepare("UPDATE quotes SET status = 'accepted' WHERE id = ?");
        $stmt_update->bind_param("i", $quote_id);
        if (!$stmt_update->execute()) {
            throw new Exception('Failed to update quote status: ' . $stmt_update->error);
        }
        $stmt_update->close();

        // Calculate final amount including discount and tax
        $final_amount = ($quote_data['quoted_price'] ?? 0) - ($quote_data['discount'] ?? 0) + ($quote_data['tax'] ?? 0);
        $final_amount = max(0, $final_amount); // Ensure amount is not negative

        // 2. Create a new Invoice
        $invoice_number = 'INV-' . strtoupper(generateToken(8));
        
        // Calculate due_date
        $seven_days_later_timestamp = strtotime('+7 days');
        if ($seven_days_later_timestamp === false) {
            error_log("Failed to generate timestamp for due_date in handleAcceptQuote.");
            throw new Exception('Failed to generate a valid timestamp for the due date.');
        }
        $due_date = date('Y-m-d', $seven_days_later_timestamp);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
            error_log("Invalid due_date format: " . $due_date);
            throw new Exception('Generated due date is not in valid YYYY-MM-DD format.');
        }

        // Log for debugging
        error_log("[DEBUG] Current time: " . date('Y-m-d H:i:s'));
        error_log("[DEBUG] strtotime('+7 days') result: " . ($seven_days_later_timestamp === false ? 'false' : $seven_days_later_timestamp));
        error_log("[DEBUG] Generated due_date: " . $due_date);

        $stmt_invoice = $conn->prepare("INSERT INTO invoices (quote_id, user_id, invoice_number, amount, status, due_date, discount, tax) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)");
        $stmt_invoice->bind_param("iisdsdd", $quote_id, $user_id, $invoice_number, $final_amount, $due_date, $quote_data['discount'], $quote_data['tax']);
        if (!$stmt_invoice->execute()) {
            error_log('Failed to create invoice: ' . $stmt_invoice->error . ' with due_date: ' . $due_date);
            throw new Exception('Failed to create invoice: ' . $stmt_invoice->error);
        }
        $invoice_id = $conn->insert_id;
        $stmt_invoice->close();

        // 3. Populate Invoice Items based on service type
        if ($quote_data['service_type'] === 'equipment_rental') {
            $stmt_eq_details = $conn->prepare("SELECT equipment_name, quantity, duration_days, specific_needs FROM quote_equipment_details WHERE quote_id = ?");
            $stmt_eq_details->bind_param("i", $quote_id);
            $stmt_eq_details->execute();
            $eq_details = $stmt_eq_details->get_result();
            $stmt_eq_details->close();

            $stmt_insert_item = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)");
            
            // Add a single line item for the base quoted price
            $description = "Equipment Rental (Quote #{$quote_id})";
            $quantity = 1;
            $unit_price = $quote_data['quoted_price'];
            $total = $quote_data['quoted_price'];
            $stmt_insert_item->bind_param("isidd", $invoice_id, $description, $quantity, $unit_price, $total);
            if (!$stmt_insert_item->execute()) {
                throw new Exception('Failed to insert base rental item into invoice_items: ' . $stmt_insert_item->error);
            }

            // Add Daily Rate as a separate line item if applicable and not zero
            if (($quote_data['daily_rate'] ?? 0) > 0) {
                $description = "Daily Rate for Extensions";
                $quantity = 1; 
                $unit_price = $quote_data['daily_rate'];
                $total = $quote_data['daily_rate'];
                $stmt_insert_item->bind_param("isidd", $invoice_id, $description, $quantity, $unit_price, $total);
                if (!$stmt_insert_item->execute()) {
                    error_log('Failed to insert daily rate item: ' . $stmt_insert_item->error);
                }
            }

            // Add Relocation Charge as a separate line item if applicable and not zero
            if (($quote_data['relocation_charge'] ?? 0) > 0) {
                $description = "Relocation Service Charge";
                $quantity = 1;
                $unit_price = $quote_data['relocation_charge'];
                $total = $quote_data['relocation_charge'];
                $stmt_insert_item->bind_param("isidd", $invoice_id, $description, $quantity, $unit_price, $total);
                if (!$stmt_insert_item->execute()) {
                    error_log('Failed to insert relocation charge item: ' . $stmt_insert_item->error);
                }
            }

            // Add Swap Charge as a separate line item if applicable and not zero
            if (($quote_data['swap_charge'] ?? 0) > 0) {
                $description = "Equipment Swap Service Charge";
                $quantity = 1;
                $unit_price = $quote_data['swap_charge'];
                $total = $quote_data['swap_charge'];
                $stmt_insert_item->bind_param("isidd", $invoice_id, $description, $quantity, $unit_price, $total);
                if (!$stmt_insert_item->execute()) {
                    error_log('Failed to insert swap charge item: ' . $stmt_insert_item->error);
                }
            }
            $stmt_insert_item->close();

        } elseif ($quote_data['service_type'] === 'junk_removal') {
            $stmt_junk_details = $conn->prepare("SELECT junk_items_json, additional_comment FROM junk_removal_details WHERE quote_id = ?");
            $stmt_junk_details->bind_param("i", $quote_id);
            $stmt_junk_details->execute();
            $junk_details = $stmt_junk_details->get_result()->fetch_assoc();
            $stmt_junk_details->close();

            $stmt_insert_item = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)");

            // Main junk removal service item
            $description = "Junk Removal Service (Quote #{$quote_id})";
            $quantity = 1;
            $unit_price = $quote_data['quoted_price'];
            $total = $quote_data['quoted_price'];
            $stmt_insert_item->bind_param("isidd", $invoice_id, $description, $quantity, $unit_price, $total);
            if (!$stmt_insert_item->execute()) {
                throw new Exception('Failed to insert junk removal item into invoice_items: ' . $stmt_insert_item->error);
            }

            // Add detailed junk items as separate lines if available
            if (!empty($junk_details['junk_items_json'])) {
                $parsed_junk_items = json_decode($junk_details['junk_items_json'], true);
                foreach ($parsed_junk_items as $item) {
                    $item_desc = " - " . ($item['itemType'] ?? 'Unknown Item');
                    $item_qty = $item['quantity'] ?? 1;
                    $item_unit_price = 0; // Descriptive items, not priced individually
                    $item_total = 0;
                    $stmt_insert_item->bind_param("isidd", $invoice_id, $item_desc, $item_qty, $item_unit_price, $item_total);
                    if (!$stmt_insert_item->execute()) {
                        error_log('Failed to insert detailed junk item: ' . $stmt_insert_item->error);
                    }
                }
            }
            $stmt_insert_item->close();
        }

        // 4. Create Notification for the customer to pay the new invoice
        $notification_message = "Quote #Q{$quote_id} accepted! Please pay the new invoice to confirm your booking.";
        $notification_link = "invoices?invoice_id={$invoice_id}";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'payment_due', ?, ?)");
        $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
        if (!$stmt_notify->execute()) {
            error_log('Failed to create notification: ' . $stmt_notify->error);
        }
        $stmt_notify->close();

        $conn->commit();
        return [
            'success' => true,
            'message' => 'Quote accepted! Redirecting to your new invoice for payment.',
            'invoice_id' => $invoice_id
        ];

    } catch (Exception $e) {
        $conn->rollback();
        throw $e; // Re-throw to be caught by the main try-catch block
    }
}

/**
 * Handles rejecting a quote.
 *
 * @param mysqli $conn The database connection object.
 * @param int $quote_id The ID of the quote being rejected.
 * @param int $user_id The ID of the user rejecting the quote.
 * @return array A success/failure response array.
 * @throws Exception If quote status update or notification fails.
 */
function handleRejectQuote($conn, $quote_id, $user_id) {
    $conn->begin_transaction();

    try {
        // 1. Update Quote Status
        $stmt_update = $conn->prepare("UPDATE quotes SET status = 'rejected' WHERE id = ?");
        $stmt_update->bind_param("i", $quote_id);
        if (!$stmt_update->execute()) {
            throw new Exception('Failed to update quote status: ' . $stmt_update->error);
        }
        $stmt_update->close();
        
        // 2. Create Notification
        $notification_message = "You have rejected quote #Q{$quote_id}.";
        $notification_link = "quotes?quote_id={$quote_id}";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'quote_rejected', ?, ?)");
        $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
        if (!$stmt_notify->execute()) {
            error_log('Failed to create notification: ' . $stmt_notify->error);
        }
        $stmt_notify->close();

        $conn->commit();
        return ['success' => true, 'message' => 'Quote has been rejected.'];

    } catch (Exception $e) {
        $conn->rollback();
        throw $e; // Re-throw to be caught by the main try-catch block
    }
}
?>