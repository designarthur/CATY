<?php
// api/admin/quotes.php - Handles admin actions related to quotes

session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in() and has_role()
require_once __DIR__ . '/../../includes/functions.php'; // For sendEmail() and getSystemSetting()

header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_POST['action'] ?? '';
$quoteId = filter_var($_POST['quote_id'] ?? null, FILTER_VALIDATE_INT);

if (!$quoteId && $action !== 'get_all_quotes') { // Some actions might not require a quoteId
    echo json_encode(['success' => false, 'message' => 'Quote ID is required for this action.']);
    exit;
}

global $conn; // Access the global database connection

switch ($action) {
    case 'submit_quote':
        $quotedPrice = filter_var($_POST['quoted_price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $adminNotes = trim($_POST['admin_notes'] ?? ''); // Get admin notes from POST

        if ($quotedPrice <= 0) {
            echo json_encode(['success' => false, 'message' => 'Quoted price must be greater than 0.']);
            exit;
        }

        // Fetch the quote details and customer info for email
        $stmt_fetch_quote = $conn->prepare("SELECT q.user_id, q.service_type, q.location, q.delivery_date, q.removal_date, q.quote_details,
                                        u.first_name, u.last_name, u.email
                                    FROM quotes q JOIN users u ON q.user_id = u.id WHERE q.id = ?");
        $stmt_fetch_quote->bind_param("i", $quoteId);
        $stmt_fetch_quote->execute();
        $result_fetch_quote = $stmt_fetch_quote->get_result();
        if ($result_fetch_quote->num_rows === 0) {
            throw new Exception("Quote not found.");
        }
        $quote_data = $result_fetch_quote->fetch_assoc();
        $stmt_fetch_quote->close();

        $conn->begin_transaction();

        try {
            // Update quote status, price, and admin notes
            $stmt_update = $conn->prepare("UPDATE quotes SET status = 'quoted', quoted_price = ?, admin_notes = ? WHERE id = ?");
            $stmt_update->bind_param("dsi", $quotedPrice, $adminNotes, $quoteId); // 's' for string adminNotes
            if (!$stmt_update->execute()) {
                throw new Exception("Failed to update quote status and price: " . $stmt_update->error);
            }
            $stmt_update->close();

            // Prepare for email notification
            $customerEmail = $quote_data['email'];
            $customerName = htmlspecialchars($quote_data['first_name'] . ' ' . $quote_data['last_name']);
            $companyName = getSystemSetting('company_name');
            if (!$companyName) {
                $companyName = $_ENV['COMPANY_NAME'] ?? 'Your Company';
            }

            // Prepare template variables for quote_ready_email.php
            $template_companyName = htmlspecialchars($companyName);
            $template_quoteId = htmlspecialchars($quoteId);
            $template_quotedPrice = number_format($quotedPrice, 2);
            $template_adminNotes = htmlspecialchars($adminNotes); // Pass admin notes to template
            $template_customerQuoteLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/customer/dashboard.php#quotes?quote_id={$quoteId}";

            // Send email to customer about new quote
            if (function_exists('sendEmail')) {
                ob_start(); // Start output buffering
                include __DIR__ . '/../includes/mail_templates/quote_ready_email.php';
                $emailBody = ob_get_clean(); // Get the buffered content

                $emailAltBody = "Dear {$customerName},\nYour quote #Q{$quoteId} from {$companyName} is ready!\nQuoted Price: $" . $template_quotedPrice . "\nNotes: {$template_adminNotes}\nView and respond to your quote here: {$template_customerQuoteLink}";

                sendEmail($customerEmail, "Your Quote #Q{$quoteId} from {$companyName} is Ready!", $emailBody, $emailAltBody);
            } else {
                error_log("WARNING: sendEmail function not available or email not sent for quote #{$quoteId}.");
            }

            // Add notification for customer
            $stmt_add_notification = $conn->prepare("INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, ?, ?, ?, FALSE)");
            $notification_message = "Your quote #{$quoteId} for " . ucwords(str_replace('_', ' ', $quote_data['service_type'])) . " is ready! Quoted price: $".number_format($quotedPrice, 2).".";
            $notification_link = "quotes?quote_id={$quoteId}";
            $notification_type_quote = 'new_quote';
            $stmt_add_notification->bind_param("isss", $quote_data['user_id'], $notification_type_quote, $notification_message, $notification_link);
            if (!$stmt_add_notification->execute()) {
                 error_log("ERROR: Failed to add customer notification for new quote: " . $stmt_add_notification->error);
            }
            $stmt_add_notification->close();


            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Quote submitted and customer notified!']);

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Admin submit quote failed for quote ID $quoteId: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to submit quote: ' . $e->getMessage()]);
        }
        break;

    case 'resend_quote':
        // Fetch current quote details to get quoted price and admin notes
        $stmt_fetch_quote = $conn->prepare("SELECT q.service_type, q.location, q.quoted_price, q.admin_notes, q.user_id,
                                                u.first_name, u.last_name, u.email
                                            FROM quotes q JOIN users u ON q.user_id = u.id WHERE q.id = ?");
        $stmt_fetch_quote->bind_param("i", $quoteId);
        $stmt_fetch_quote->execute();
        $result_fetch_quote = $stmt_fetch_quote->get_result();
        if ($result_fetch_quote->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Quote not found.']);
            exit;
        }
        $quote_data = $result_fetch_quote->fetch_assoc();
        $stmt_fetch_quote->close();

        if ($quote_data['quoted_price'] === null) {
            echo json_encode(['success' => false, 'message' => 'Cannot resend a quote that has no price. Please submit a price first.']);
            exit;
        }

        // Prepare for email notification
        $customerEmail = $quote_data['email'];
        $customerName = htmlspecialchars($quote_data['first_name'] . ' ' . $quote_data['last_name']);
        $companyName = getSystemSetting('company_name');
        if (!$companyName) {
            $companyName = $_ENV['COMPANY_NAME'] ?? 'Your Company';
        }

        // Prepare template variables for quote_ready_email.php
        $template_companyName = htmlspecialchars($companyName);
        $template_quoteId = htmlspecialchars($quoteId);
        $template_quotedPrice = number_format($quote_data['quoted_price'], 2);
        $template_adminNotes = htmlspecialchars($quote_data['admin_notes'] ?? ''); // Use existing admin notes
        $template_customerQuoteLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/customer/dashboard.php#quotes?quote_id={$quoteId}";


        if (function_exists('sendEmail')) {
            ob_start();
            include __DIR__ . '/../includes/mail_templates/quote_ready_email.php';
            $emailBody = ob_get_clean();

            $emailAltBody = "Dear {$customerName},\nYour quote #Q{$quoteId} from {$companyName} is ready!\nQuoted Price: $" . $template_quotedPrice . "\nNotes: {$template_adminNotes}\nView and respond to your quote here: {$template_customerQuoteLink}";

            if (sendEmail($customerEmail, "Your Quote #Q{$quoteId} from {$companyName} is Ready! (Resend)", $emailBody, $emailAltBody)) {
                echo json_encode(['success' => true, 'message' => 'Quote resent successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to resend email.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Email function not available.']);
        }
        break;

    case 'reject_quote':
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE quotes SET status = 'rejected' WHERE id = ?");
            $stmt->bind_param("i", $quoteId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to reject quote: " . $stmt->error);
            }
            $stmt->close();

            // Optionally, send an email notification to the customer about rejection
            // Fetch customer email and name
            $stmt_fetch_customer = $conn->prepare("SELECT u.first_name, u.last_name, u.email FROM users u JOIN quotes q ON u.id = q.user_id WHERE q.id = ?");
            $stmt_fetch_customer->bind_param("i", $quoteId);
            $stmt_fetch_customer->execute();
            $customer_data = $stmt_fetch_customer->get_result()->fetch_assoc();
            $stmt_fetch_customer->close();

            if ($customer_data && function_exists('sendEmail')) {
                $customerEmail = $customer_data['email'];
                $customerName = htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['last_name']);
                $companyName = getSystemSetting('company_name') ?? ($_ENV['COMPANY_NAME'] ?? 'Your Company');
                $emailSubject = "Update on Your Quote Request #Q{$quoteId} from {$companyName}";
                $emailBody = "
                    <p>Dear {$customerName},</p>
                    <p>We regret to inform you that your quote request #Q<strong>{$quoteId}</strong> has been rejected. This could be due to various reasons, such as service unavailability in your area, or details that do not fit our current offerings.</p>
                    <p>If you believe this is an error or would like to discuss alternative solutions, please feel free to contact us.</p>
                    <p>Thank you for your understanding.</p>
                    <p>The {$companyName} Team</p>
                ";
                $emailAltBody = "Dear {$customerName},\nYour quote request #Q{$quoteId} from {$companyName} has been rejected. Please contact us for more information.";
                sendEmail($customerEmail, $emailSubject, $emailBody, $emailAltBody);
            }

            // Add notification for customer
            $stmt_add_notification = $conn->prepare("INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, ?, ?, ?, FALSE)");
            $notification_message = "Your quote #{$quoteId} has been rejected. Please contact support for more info.";
            $notification_link = "quotes?quote_id={$quoteId}"; // Link back to the quote details
            $notification_type_quote = 'quote_rejected';
            $stmt_add_notification->bind_param("isss", $quote_data['user_id'], $notification_type_quote, $notification_message, $notification_link);
            if (!$stmt_add_notification->execute()) {
                 error_log("ERROR: Failed to add customer notification for rejected quote: " . $stmt_add_notification->error);
            }
            $stmt_add_notification->close();


            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Quote rejected successfully!']);
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Admin reject quote failed for quote ID $quoteId: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to reject quote: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

$conn->close();
?>