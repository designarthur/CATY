<?php
// api/admin/quotes.php - Handles admin actions related to quotes

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// --- Input Processing ---
$action = $_POST['action'] ?? '';
$quoteId = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);

if (!$quoteId) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'A valid Quote ID is required.']);
    exit;
}

// --- Action Routing ---
try {
    switch ($action) {
        case 'submit_quote':
            handleSubmitQuote($conn, $quoteId);
            break;
        case 'resend_quote':
            handleResendQuote($conn, $quoteId);
            break;
        case 'reject_quote':
            handleRejectQuote($conn, $quoteId);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Admin Quote API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred. ' . $e->getMessage()]);
} finally {
    $conn->close();
}


// --- Handler Functions ---

/**
 * Submits a price for a quote and notifies the customer.
 */
function handleSubmitQuote($conn, $quoteId) {
    $quotedPrice = filter_var($_POST['quoted_price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    if ($quotedPrice <= 0) {
        throw new Exception('Quoted price must be a positive number.');
    }

    $conn->begin_transaction();

    // Fetch customer info for email/notification
    $stmt_fetch = $conn->prepare("SELECT u.id, u.email, u.first_name FROM users u JOIN quotes q ON u.id = q.user_id WHERE q.id = ?");
    $stmt_fetch->bind_param("i", $quoteId);
    $stmt_fetch->execute();
    $quote_user_data = $stmt_fetch->get_result()->fetch_assoc();
    if (!$quote_user_data) throw new Exception('User for the quote not found.');

    // 1. Update the quote in the database
    $stmt_update = $conn->prepare("UPDATE quotes SET status = 'quoted', quoted_price = ?, admin_notes = ? WHERE id = ?");
    $stmt_update->bind_param("dsi", $quotedPrice, $adminNotes, $quoteId);
    $stmt_update->execute();

    // 2. Prepare and send email notification
    $customerEmail = $quote_user_data['email'];
    $template_vars = [
        'template_companyName' => getSystemSetting('company_name') ?? 'CAT Dump',
        'template_quoteId' => $quoteId,
        'template_quotedPrice' => number_format($quotedPrice, 2),
        'template_adminNotes' => $adminNotes,
        'template_customerQuoteLink' => "https://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#quotes?quote_id={$quoteId}"
    ];
    // This is a cleaner way to pass variables to a template file
    ob_start();
    extract($template_vars);
    include __DIR__ . '/../../includes/mail_templates/quote_ready_email.php';
    $emailBody = ob_get_clean();

    sendEmail($customerEmail, "Your Quote #Q{$quoteId} is Ready!", $emailBody);

    // 3. Create a system notification for the user
    $notification_message = "Your quote #{$quoteId} is ready! The quoted price is $" . number_format($quotedPrice, 2) . ".";
    $notification_link = "quotes?quote_id={$quoteId}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'new_quote', ?, ?)");
    $stmt_notify->bind_param("iss", $quote_user_data['id'], $notification_message, $notification_link);
    $stmt_notify->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Quote submitted and customer notified!']);
}

/**
 * Rejects a quote and notifies the customer.
 */
function handleRejectQuote($conn, $quoteId) {
    $conn->begin_transaction();

    // Fetch customer info for notification
    $stmt_fetch = $conn->prepare("SELECT u.id, u.email, u.first_name FROM users u JOIN quotes q ON u.id = q.user_id WHERE q.id = ?");
    $stmt_fetch->bind_param("i", $quoteId);
    $stmt_fetch->execute();
    $quote_user_data = $stmt_fetch->get_result()->fetch_assoc();
    if (!$quote_user_data) throw new Exception('User for the quote not found.');

    // 1. Update quote status
    $stmt_update = $conn->prepare("UPDATE quotes SET status = 'rejected' WHERE id = ?");
    $stmt_update->bind_param("i", $quoteId);
    $stmt_update->execute();

    // 2. Notify customer via email
    $emailBody = "<p>Dear {$quote_user_data['first_name']},</p><p>We regret to inform you that your quote request #Q{$quoteId} has been rejected at this time. Please contact us if you have any questions.</p>";
    sendEmail($quote_user_data['email'], "Update on your Quote Request #Q{$quoteId}", $emailBody);

    // 3. Create system notification
    $notification_message = "Your quote request #{$quoteId} has been rejected.";
    $notification_link = "quotes?quote_id={$quoteId}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'quote_rejected', ?, ?)");
    $stmt_notify->bind_param("iss", $quote_user_data['id'], $notification_message, $notification_link);
    $stmt_notify->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Quote has been rejected and the customer notified.']);
}

// Implement handleResendQuote similarly...