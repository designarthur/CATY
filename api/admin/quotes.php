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

// --- Action Routing ---
try {
    switch ($action) {
        case 'submit_quote':
            handleSubmitQuote($conn);
            break;
        case 'resend_quote':
            handleResendQuote($conn, $quoteId);
            break;
        case 'reject_quote':
            handleRejectQuote($conn, $quoteId);
            break;
        case 'delete_bulk':
            handleDeleteBulk($conn);
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
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}


// --- Handler Functions ---

/**
 * Submits a price for a quote, including new charges, and notifies the customer.
 */
function handleSubmitQuote($conn) {
    // --- Input Validation ---
    $quoteId = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);
    $quotedPrice = filter_input(INPUT_POST, 'quoted_price', FILTER_VALIDATE_FLOAT);
    $dailyRate = filter_input(INPUT_POST, 'daily_rate', FILTER_VALIDATE_FLOAT); 
    $relocationCharge = filter_input(INPUT_POST, 'relocation_charge', FILTER_VALIDATE_FLOAT); 
    // Correctly get boolean from checkbox '1' or '0'
    $isRelocationIncluded = filter_input(INPUT_POST, 'is_relocation_included', FILTER_VALIDATE_INT) === 1; 
    $swapCharge = filter_input(INPUT_POST, 'swap_charge', FILTER_VALIDATE_FLOAT); 
    // Correctly get boolean from checkbox '1' or '0'
    $isSwapIncluded = filter_input(INPUT_POST, 'is_swap_included', FILTER_VALIDATE_INT) === 1; 
    $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT);
    $tax = filter_input(INPUT_POST, 'tax', FILTER_VALIDATE_FLOAT);
    $adminNotes = trim($_POST['admin_notes'] ?? '');
    $attachmentPath = null;

    if (!$quoteId) {
        throw new Exception('A valid Quote ID is required.');
    }
    
    if ($quotedPrice === false || $quotedPrice < 0) { // Allow 0 for quotedPrice if needed, but not negative
        throw new Exception('A valid main quoted price is required (must be 0 or greater).');
    }
    
    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/quote_attachments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['attachment']['name']);
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadFile)) {
            $attachmentPath = '/uploads/quote_attachments/' . $fileName;
        } else {
            throw new Exception('Failed to move uploaded file.');
        }
    }


    $conn->begin_transaction();

    // Fetch customer info for email/notification
    $stmt_fetch = $conn->prepare("SELECT u.id, u.email, u.first_name FROM users u JOIN quotes q ON u.id = q.user_id WHERE q.id = ?");
    $stmt_fetch->bind_param("i", $quoteId);
    $stmt_fetch->execute();
    $quote_user_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();
    if (!$quote_user_data) throw new Exception('User for the quote not found.');

    // 1. Update the quote in the database
    // Ensure all relevant fields are saved to the 'quotes' table
    $stmt_update = $conn->prepare("UPDATE quotes SET status = 'quoted', quoted_price = ?, daily_rate = ?, relocation_charge = ?, is_relocation_included = ?, swap_charge = ?, is_swap_included = ?, discount = ?, tax = ?, admin_notes = ?, attachment_path = ? WHERE id = ?");
    $stmt_update->bind_param("dddiddddssi", 
        $quotedPrice, 
        $dailyRate, 
        $relocationCharge, 
        $isRelocationIncluded, // bind as int (0 or 1)
        $swapCharge, 
        $isSwapIncluded,     // bind as int (0 or 1)
        $discount, 
        $tax, 
        $adminNotes, 
        $attachmentPath, 
        $quoteId
    );

    if (!$stmt_update->execute()) { // Check for execution success
        throw new Exception('Failed to update quote in the database: ' . $stmt_update->error);
    }
    $stmt_update->close();

    // 2. Prepare and send email notification
    $customerEmail = $quote_user_data['email'];
    // Calculate final price for email ONLY based on quoted_price, discount, and tax
    $final_email_price = ($quotedPrice ?? 0) - ($discount ?? 0) + ($tax ?? 0);
    $final_email_price = max(0, $final_email_price); // Ensure not negative

    $template_vars = [
        'template_companyName' => getSystemSetting('company_name') ?? 'CAT Dump',
        'template_quoteId' => $quoteId,
        'template_quotedPrice' => number_format($final_email_price, 2), // Use final calculated price for initial quote
        'template_adminNotes' => $adminNotes,
        'template_customerQuoteLink' => "https://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#quotes?quote_id={$quoteId}"
    ];
    ob_start();
    extract($template_vars);
    include __DIR__ . '/../../includes/mail_templates/quote_ready_email.php';
    $emailBody = ob_get_clean();
    sendEmail($customerEmail, "Your Quote #Q{$quoteId} is Ready!", $emailBody);

    // 3. Create a system notification for the user
    $notification_message = "Your quote #{$quoteId} is ready! The quoted price is $" . number_format($final_email_price, 2) . ".";
    $notification_link = "quotes?quote_id={$quoteId}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'new_quote', ?, ?)");
    $stmt_notify->bind_param("iss", $quote_user_data['id'], $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

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
    $stmt_fetch->close();
    if (!$quote_user_data) throw new Exception('User for the quote not found.');

    // 1. Update quote status
    $stmt_update = $conn->prepare("UPDATE quotes SET status = 'rejected' WHERE id = ?");
    $stmt_update->bind_param("i", $quoteId);
    if (!$stmt_update->execute()) { // Check for execution success
        throw new Exception('Failed to update quote status: ' . $stmt_update->error);
    }
    $stmt_update->close();

    // 2. Notify customer via email
    $emailBody = "<p>Dear {$quote_user_data['first_name']},</p><p>We regret to inform you that your quote request #Q{$quoteId} has been rejected at this time. Please contact us if you have any questions.</p>";
    sendEmail($quote_user_data['email'], "Update on your Quote Request #Q{$quoteId}", $emailBody);

    // 3. Create system notification
    $notification_message = "Your quote request #{$quoteId} has been rejected.";
    $notification_link = "quotes?quote_id={$quoteId}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'quote_rejected', ?, ?)");
    $stmt_notify->bind_param("iss", $quote_user_data['id'], $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Quote has been rejected and the customer notified.']);
}

/**
 * Resends the quote notification email to the customer.
 */
function handleResendQuote($conn, $quoteId) {
    // Fetch current quote details to get quoted price and admin notes
    $stmt_fetch = $conn->prepare("
        SELECT q.quoted_price, q.admin_notes, q.discount, q.tax, u.email, u.first_name
        FROM quotes q
        JOIN users u ON q.user_id = u.id
        WHERE q.id = ? AND q.status = 'quoted'
    ");
    $stmt_fetch->bind_param("i", $quoteId);
    $stmt_fetch->execute();
    $quote_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$quote_data) {
        throw new Exception('Quote not found or is not in a "quoted" status.');
    }

    // Calculate final price for email
    $final_email_price = ($quote_data['quoted_price'] ?? 0) - ($quote_data['discount'] ?? 0) + ($quote_data['tax'] ?? 0);
    $final_email_price = max(0, $final_email_price); // Ensure not negative

    // Prepare and send email notification
    $template_vars = [
        'template_companyName' => getSystemSetting('company_name') ?? 'CAT Dump',
        'template_quoteId' => $quoteId,
        'template_quotedPrice' => number_format($final_email_price, 2), // Use final calculated price
        'template_adminNotes' => $quote_data['admin_notes'],
        'template_customerQuoteLink' => "https://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#quotes?quote_id={$quoteId}"
    ];
    ob_start();
    extract($template_vars);
    include __DIR__ . '/../../includes/mail_templates/quote_ready_email.php';
    $emailBody = ob_get_clean();

    if (sendEmail($quote_data['email'], "[Resend] Your Quote #Q{$quoteId} is Ready!", $emailBody)) {
        echo json_encode(['success' => true, 'message' => 'Quote resent successfully!']);
    } else {
        throw new Exception('Failed to resend email notification.');
    }
}

/**
 * Deletes multiple quotes in bulk.
 */
function handleDeleteBulk($conn) {
    $quote_ids = $_POST['quote_ids'] ?? [];
    if (empty($quote_ids) || !is_array($quote_ids)) {
        throw new Exception("No quote IDs provided for bulk deletion.");
    }

    $conn->begin_transaction();

    try {
        $placeholders = implode(',', array_fill(0, count($quote_ids), '?'));
        $types = str_repeat('i', count($quote_ids));
        
        $stmt = $conn->prepare("DELETE FROM quotes WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$quote_ids);
        
        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Selected quotes have been deleted.']);
        } else {
            throw new Exception("Failed to delete quotes.");
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        throw $e; // Re-throw the exception to be caught by the main handler
    }
}