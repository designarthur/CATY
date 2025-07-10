<?php
// api/payments.php - Handles Braintree payment processing and booking creation

// --- JSON Error Handler ---
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        // Don't output detailed errors in production, but useful for debugging.
        echo json_encode([
            'success' => false,
            'message' => 'A fatal server error occurred. Please check server logs.',
            // 'error_details' => [ 'message' => $error['message'], 'file' => $error['file'], 'line' => $error['line'] ] // Uncomment for debug
        ]);
    }
});


// Start session and include necessary files
session_start();
// *** CORRECTED PATHS ***
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Initialize Braintree Gateway
try {
    $gateway = new Braintree\Gateway([
        'environment' => $_ENV['BRAINTREE_ENVIRONMENT'] ?? 'sandbox',
        'merchantId' => $_ENV['BRAINTREE_MERCHANT_ID'],
        'publicKey' => $_ENV['BRAINTREE_PUBLIC_KEY'],
        'privateKey' => $_ENV['BRAINTREE_PRIVATE_KEY']
    ]);
} catch (Exception $e) {
    error_log("Braintree Gateway initialization failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment system configuration error.']);
    exit;
}


/**
 * Saves a new payment method to the database.
 */
function saveNewPaymentMethod(mysqli $conn, int $user_id): ?string
{
    $cardholderName = trim($_POST['cardholder_name'] ?? '');
    $cardNumber = trim(str_replace(' ', '', $_POST['card_number'] ?? ''));
    $expiryDate = trim($_POST['expiry_date'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $setNewCardDefault = isset($_POST['set_new_card_default']) && $_POST['set_new_card_default'] === 'on';

    if (empty($cardholderName) || empty($cardNumber) || empty($expiryDate) || empty($cvv) || empty($billingAddress)) {
        throw new Exception('All card details are required to save a new payment method.');
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiryDate, $matches)) {
        throw new Exception('Invalid expiration date format (MM/YY).');
    }
    $expMonth = $matches[1];
    $expYear = '20' . $matches[2];
    $lastFour = substr($cardNumber, -4);
    
    $braintreeToken = 'token_' . uniqid() . $lastFour;
    $firstDigit = substr($cardNumber, 0, 1);
    $cardType = 'Unknown';
    if ($firstDigit == '4') $cardType = 'Visa';
    elseif ($firstDigit == '5') $cardType = 'MasterCard';
    elseif ($firstDigit == '3') $cardType = 'Amex';

    if ($setNewCardDefault) {
        $stmt_unset = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
        $stmt_unset->bind_param("i", $user_id);
        if (!$stmt_unset->execute()) {
            throw new Exception("Failed to unset other default cards.");
        }
        $stmt_unset->close();
    }

    $stmt_insert_card = $conn->prepare("INSERT INTO user_payment_methods (user_id, braintree_payment_token, card_type, last_four, expiration_month, expiration_year, cardholder_name, is_default, billing_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_insert_card->bind_param("issssssis", $user_id, $braintreeToken, $cardType, $lastFour, $expMonth, $expYear, $cardholderName, $setNewCardDefault, $billingAddress);
    if (!$stmt_insert_card->execute()) {
        throw new Exception("Failed to save new payment method to database: " . $stmt_insert_card->error);
    }
    $stmt_insert_card->close();

    return $braintreeToken;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'process_payment') {
    $invoiceNumber = trim($_POST['invoice_number'] ?? '');
    $amountToPay = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
    $paymentMethodToken = $_POST['payment_method_token'] ?? null;
    $saveNewCard = isset($_POST['save_new_card']) && $_POST['save_new_card'] === 'on';

    if (empty($invoiceNumber) || $amountToPay <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid invoice details or amount.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Fetch Invoice Data
        $stmt_invoice = $conn->prepare("SELECT i.id AS invoice_id, i.quote_id, q.service_type, q.location AS delivery_location, q.delivery_date, q.removal_date, q.live_load_needed, q.is_urgent, q.driver_instructions AS delivery_instructions, qed.duration_days, jrd.junk_items_json, jrd.recommended_dumpster_size, jrd.additional_comment, jrd.media_urls_json, qed.equipment_name, qed.quantity FROM invoices i LEFT JOIN quotes q ON i.quote_id = q.id LEFT JOIN quote_equipment_details qed ON q.id = qed.quote_id LEFT JOIN junk_removal_details jrd ON q.id = jrd.quote_id WHERE i.invoice_number = ? AND i.user_id = ? AND i.status IN ('pending', 'partially_paid')");
        $stmt_invoice->bind_param("si", $invoiceNumber, $user_id);
        $stmt_invoice->execute();
        $invoice_data = $stmt_invoice->get_result()->fetch_assoc();
        $stmt_invoice->close();
        if (!$invoice_data) {
            throw new Exception("Invoice not found, already paid, or not authorized.");
        }
        $invoice_id = $invoice_data['invoice_id'];
        $quote_id = $invoice_data['quote_id'];

        if (empty($paymentMethodToken) && $saveNewCard) {
            $newlyCreatedToken = saveNewPaymentMethod($conn, $user_id);
            $paymentMethodToken = $newlyCreatedToken;
        }

        // Process Transaction
        $saleRequest = ['amount' => (string)$amountToPay, 'options' => ['submitForSettlement' => true]];
        if (!empty($paymentMethodToken)) {
            $saleRequest['paymentMethodToken'] = $paymentMethodToken;
        } else {
            $saleRequest['paymentMethodNonce'] = 'fake-valid-nonce';
        }
        
        $result = $gateway->transaction()->sale($saleRequest);
        if (!$result->success) {
            throw new Exception("Braintree transaction failed: " . $result->message);
        }
        $transaction_id = $result->transaction->id;

        // Update Invoice
        $stmt_update_invoice = $conn->prepare("UPDATE invoices SET status = 'paid', payment_method = ?, transaction_id = ? WHERE id = ?");
        $cardUsedDisplay = !empty($paymentMethodToken) ? "Saved Card" : "New Card";
        $stmt_update_invoice->bind_param("ssi", $cardUsedDisplay, $transaction_id, $invoice_id);
        if (!$stmt_update_invoice->execute()) throw new Exception("Failed to update invoice status.");
        $stmt_update_invoice->close();

        // Update Quote
        if ($quote_id) {
            $stmt_update_quote = $conn->prepare("UPDATE quotes SET status = 'converted_to_booking' WHERE id = ?");
            $stmt_update_quote->bind_param("i", $quote_id);
            if (!$stmt_update_quote->execute()) throw new Exception("Failed to update quote status.");
            $stmt_update_quote->close();
        }

        // Create Booking
        $booking_number = 'BK-' . str_pad($invoice_id, 6, '0', STR_PAD_LEFT);
        $service_type = $invoice_data['service_type'];
        $delivery_location = $invoice_data['delivery_location'];
        $start_date = $invoice_data['delivery_date'] ?? $invoice_data['removal_date'];
        $end_date = ($service_type == 'equipment_rental' && $start_date) ? (new DateTime($start_date))->modify("+".($invoice_data['duration_days'] ?? 7)." days")->format('Y-m-d') : $start_date;
        $equipment_details_json = ($service_type == 'equipment_rental') ? json_encode([['equipment_name' => $invoice_data['equipment_name'], 'quantity' => $invoice_data['quantity'], 'specific_needs' => $invoice_data['specific_needs'] ?? null, 'duration_days' => $invoice_data['duration_days']]]) : null;
        $junk_details_json = ($service_type == 'junk_removal') ? json_encode(['junkItems' => json_decode($invoice_data['junk_items_json'] ?? '[]', true), 'recommendedDumpsterSize' => $invoice_data['recommended_dumpster_size'], 'additionalComment' => $invoice_data['additional_comment'], 'mediaUrls' => json_decode($invoice_data['media_urls_json'] ?? '[]', true)]) : null;
        $live_load_requested = (int)($invoice_data['live_load_needed'] ?? 0);
        $is_urgent = (int)($invoice_data['is_urgent'] ?? 0);
        $driver_instructions = $invoice_data['delivery_instructions'];
        
        $stmt_create_booking = $conn->prepare("INSERT INTO bookings (invoice_id, user_id, booking_number, service_type, status, start_date, end_date, delivery_location, delivery_instructions, live_load_requested, is_urgent, total_price, equipment_details, junk_details) VALUES (?, ?, ?, ?, 'scheduled', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_create_booking->bind_param("iissssssiiidss", $invoice_id, $user_id, $booking_number, $service_type, $start_date, $end_date, $delivery_location, $driver_instructions, $live_load_requested, $is_urgent, $amountToPay, $equipment_details_json, $junk_details_json);
        if (!$stmt_create_booking->execute()) throw new Exception("Failed to create booking.");
        $booking_id = $stmt_create_booking->insert_id;
        $stmt_create_booking->close();
        
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Payment successful and booking confirmed!',
            'transaction_id' => $transaction_id,
            'booking_id' => $booking_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Payment processing failed for Invoice: $invoiceNumber, User: $user_id. Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request or action.']);
}
