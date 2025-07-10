<?php
// api/customer/quotes.php

// Production-safe error reporting for API endpoint
ini_set('display_errors', 0); // DO NOT display errors in production API responses
ini_set('display_startup_errors', 0); // DO NOT display errors in production API responses
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT); // Log all, but don't output notices/deprecations

session_start(); // Session start should be the very first thing after error reporting setup

require_once __DIR__ . '/../../includes/db.php'; // Corrected path
require_once __DIR__ . '/../../includes/session.php'; // Corrected path
require_once __DIR__ . '/../../includes/functions.php'; // Corrected path

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (!is_logged_in() || !has_role('customer')) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$quote_id = $_POST['quote_id'] ?? null;

if (empty($action) || empty($quote_id)) {
    $response['message'] = 'Invalid request. Missing action or quote ID.';
    echo json_encode($response);
    exit;
}

// Fetch quote details to ensure it belongs to the user and is in a valid state
$stmt = $conn->prepare("SELECT id, user_id, status, service_type, quoted_price, location,
                               delivery_date, delivery_time, removal_date, removal_time,
                               live_load_needed, is_urgent, driver_instructions, quote_details
                        FROM quotes WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $quote_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$quote = $result->fetch_assoc();
$stmt->close();

if (!$quote) {
    $response['message'] = 'Quote not found or you do not have permission to access it.';
    echo json_encode($response);
    exit;
}

try {
    $conn->begin_transaction();

    switch ($action) {
        case 'accept_quote':
            if ($quote['status'] !== 'quoted') {
                throw new Exception('Quote must be in "quoted" status to be accepted.');
            }

            // 1. Update Quote Status
            $stmt_update_quote = $conn->prepare("UPDATE quotes SET status = 'accepted' WHERE id = ?");
            $stmt_update_quote->bind_param("i", $quote_id);
            if (!$stmt_update_quote->execute()) {
                throw new Exception('Failed to update quote status: ' . $stmt_update_quote->error);
            }
            $stmt_update_quote->close();

            // 2. Create Invoice
            $invoice_number = 'INV-' . strtoupper(generateToken(8));
            $amount = $quote['quoted_price'];
            $due_date = date('Y-m-d', strtotime('+7 days')); // Example: Due in 7 days

            $stmt_insert_invoice = $conn->prepare("INSERT INTO invoices (quote_id, user_id, invoice_number, amount, status, due_date) VALUES (?, ?, ?, ?, 'pending', ?)");
            $stmt_insert_invoice->bind_param("iisds", $quote_id, $user_id, $invoice_number, $amount, $due_date);
            if (!$stmt_insert_invoice->execute()) {
                throw new Exception('Failed to create invoice: ' . $stmt_insert_invoice->error);
            }
            $invoice_id = $conn->insert_id;
            $stmt_insert_invoice->close();

            // 3. Create Notification for Customer
            $notification_message = "Your quote #Q{$quote_id} has been accepted! An invoice (INV-{$invoice_id}) has been created for $". number_format($amount, 2) .". Please view and pay.";
            $notification_link = "invoices?invoice_id={$invoice_id}";
            $stmt_notify_customer = $conn->prepare("INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, ?, ?, ?, FALSE)");
            $notification_type = 'payment_due'; // Changed to a valid ENUM value from schema
            $stmt_notify_customer->bind_param("isss", $user_id, $notification_type, $notification_message, $notification_link);
            if (!$stmt_notify_customer->execute()) {
                error_log("ERROR: Failed to add customer notification about new invoice: " . $stmt_notify_customer->error);
            }
            $stmt_notify_customer->close();

            // 4. Create Notification for Admin (optional, but good for tracking)
            $admin_message = "Quote #Q{$quote_id} has been accepted by User ID: {$user_id}. Invoice #INV{$invoice_id} created.";
            $admin_link = "quotes?quote_id={$quote_id}";
            $first_admin_id = null;
            $stmt_get_admin = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $stmt_get_admin->execute();
            $result_admin = $stmt_get_admin->get_result();
            if ($row_admin = $result_admin->fetch_assoc()) {
                $first_admin_id = $row_admin['id'];
            }
            $stmt_get_admin->close();

            if ($first_admin_id) {
                $stmt_notify_admin = $conn->prepare("INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, ?, ?, ?, FALSE)");
                $notification_type_admin = 'quote_accepted'; // Changed to 'quote_accepted'
                $stmt_notify_admin->bind_param("isss", $first_admin_id, $notification_type_admin, $admin_message, $admin_link);
                if (!$stmt_notify_admin->execute()) {
                    error_log("ERROR: Failed to add admin notification about accepted quote: " . $stmt_notify_admin->error);
                }
                $stmt_notify_admin->close();
            }

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Quote accepted successfully! Redirecting to invoice for payment.';
            $response['invoice_id'] = $invoice_id; // Pass invoice ID for redirection

            break;

        case 'reject_quote':
            if ($quote['status'] !== 'quoted' && $quote['status'] !== 'pending') {
                throw new Exception('Quote cannot be rejected in its current status.');
            }

            // Update Quote Status
            $stmt_update_quote = $conn->prepare("UPDATE quotes SET status = 'rejected' WHERE id = ?");
            $stmt_update_quote->bind_param("i", $quote_id);
            if (!$stmt_update_quote->execute()) {
                throw new Exception('Failed to update quote status: ' . $stmt_update_quote->error);
            }
            $stmt_update_quote->close();

            // Create Notification for Customer
            $notification_message = "Your quote #Q{$quote_id} has been rejected.";
            $notification_link = "quotes?quote_id={$quote_id}";
            $stmt_notify_customer = $conn->prepare("INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, ?, ?, ?, FALSE)");
            $notification_type = 'quote_rejected'; // This is a valid ENUM value
            $stmt_notify_customer->bind_param("isss", $user_id, $notification_type, $notification_message, $notification_link);
            if (!$stmt_notify_customer->execute()) {
                error_log("ERROR: Failed to add customer notification about rejected quote: " . $stmt_notify_customer->error);
            }
            $stmt_notify_customer->close();

            // Create Notification for Admin (optional)
            $admin_message = "Quote #Q{$quote_id} has been rejected by User ID: {$user_id}.";
            $admin_link = "quotes?quote_id={$quote_id}";
            $first_admin_id = null;
            $stmt_get_admin = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $stmt_get_admin->execute();
            $result_admin = $stmt_get_admin->get_result();
            if ($row_admin = $result_admin->fetch_assoc()) {
                $first_admin_id = $row_admin['id'];
            }
            $stmt_get_admin->close();

            if ($first_admin_id) {
                $stmt_notify_admin = $conn->prepare("INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, ?, ?, ?, FALSE)");
                $notification_type_admin = 'quote_rejected'; // Changed to 'quote_rejected'
                $stmt_notify_admin->bind_param("isss", $first_admin_id, $notification_type_admin, $admin_message, $admin_link);
                if (!$stmt_notify_admin->execute()) {
                    error_log("ERROR: Failed to add admin notification about rejected quote: " . $stmt_notify_admin->error);
                }
                $stmt_notify_admin->close();
            }

            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Quote rejected successfully.';

            break;

        default:
            $response['message'] = 'Invalid action.';
            break;
    }
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("API Error (customer/quotes.php): " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile());
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    echo json_encode($response);
}