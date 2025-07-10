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
    $stmt_verify = $conn->prepare("SELECT status, quoted_price FROM quotes WHERE id = ? AND user_id = ?");
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
            $response = handleAcceptQuote($conn, $quote_id, $user_id, $quote['quoted_price']);
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
 * Handles accepting a quote, which creates a new invoice.
 */
function handleAcceptQuote($conn, $quote_id, $user_id, $amount) {
    $conn->begin_transaction();

    // 1. Update Quote Status to 'accepted'
    $stmt_update = $conn->prepare("UPDATE quotes SET status = 'accepted' WHERE id = ?");
    $stmt_update->bind_param("i", $quote_id);
    $stmt_update->execute();
    $stmt_update->close();

    // 2. Create a new Invoice
    $invoice_number = 'INV-' . strtoupper(generateToken(8));
    $due_date = date('Y-m-d', strtotime('+7 days')); // Invoice due in 7 days

    $stmt_invoice = $conn->prepare("INSERT INTO invoices (quote_id, user_id, invoice_number, amount, status, due_date) VALUES (?, ?, ?, ?, 'pending', ?)");
    $stmt_invoice->bind_param("iisds", $quote_id, $user_id, $invoice_number, $amount, $due_date);
    if (!$stmt_invoice->execute()) {
        throw new Exception('Failed to create invoice: ' . $stmt_invoice->error);
    }
    $invoice_id = $conn->insert_id;
    $stmt_invoice->close();

    // 3. Create Notification for the customer to pay the new invoice
    $notification_message = "Quote #Q{$quote_id} accepted! Please pay the new invoice to confirm your booking.";
    $notification_link = "invoices?invoice_id={$invoice_id}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'payment_due', ?, ?)");
    $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    // You could also notify an admin here if desired.

    $conn->commit();
    return [
        'success' => true,
        'message' => 'Quote accepted! Redirecting to your new invoice for payment.',
        'invoice_id' => $invoice_id
    ];
}

/**
 * Handles rejecting a quote.
 */
function handleRejectQuote($conn, $quote_id, $user_id) {
    $conn->begin_transaction();

    // 1. Update Quote Status
    $stmt_update = $conn->prepare("UPDATE quotes SET status = 'rejected' WHERE id = ?");
    $stmt_update->bind_param("i", $quote_id);
    $stmt_update->execute();
    $stmt_update->close();
    
    // 2. Create Notification
    $notification_message = "You have rejected quote #Q{$quote_id}.";
    $notification_link = "quotes?quote_id={$quote_id}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'quote_rejected', ?, ?)");
    $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $conn->commit();
    return ['success' => true, 'message' => 'Quote has been rejected.'];
}
?>