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
        // 1. Fetch Invoice ID
        $stmt_invoice = $conn->prepare("SELECT id FROM invoices WHERE invoice_number = ? AND user_id = ? AND status IN ('pending', 'partially_paid')");
        $stmt_invoice->bind_param("si", $invoiceNumber, $user_id);
        $stmt_invoice->execute();
        $result_invoice = $stmt_invoice->get_result();
        if ($result_invoice->num_rows === 0) {
            throw new Exception("Invoice not found, already paid, or not authorized.");
        }
        $invoice_id = $result_invoice->fetch_assoc()['id'];
        $stmt_invoice->close();
        
        // 2. Save new payment method if requested.
        if (empty($paymentMethodToken) && $saveNewCard) {
            $newlyCreatedToken = saveNewPaymentMethod($conn, $user_id);
            $paymentMethodToken = $newlyCreatedToken;
        }

        // 3. Process Transaction with Braintree
        $saleRequest = ['amount' => (string)$amountToPay, 'options' => ['submitForSettlement' => true]];
        if (!empty($paymentMethodToken)) {
            $saleRequest['paymentMethodToken'] = $paymentMethodToken;
        } else {
            // This is a placeholder for a real implementation.
            // In production, the Braintree JS SDK on your frontend would generate a one-time-use nonce.
            // You would pass that nonce here instead of the fake one.
            $saleRequest['paymentMethodNonce'] = 'fake-valid-nonce';
        }
        
        $result = $gateway->transaction()->sale($saleRequest);
        if (!$result->success) {
            throw new Exception("Braintree transaction failed: " . $result->message);
        }
        $transaction_id = $result->transaction->id;

        // 4. Update Invoice Status
        $stmt_update_invoice = $conn->prepare("UPDATE invoices SET status = 'paid', payment_method = ?, transaction_id = ? WHERE id = ?");
        $cardUsedDisplay = !empty($paymentMethodToken) ? "Saved Card" : "New Card";
        $stmt_update_invoice->bind_param("ssi", $cardUsedDisplay, $transaction_id, $invoice_id);
        if (!$stmt_update_invoice->execute()) throw new Exception("Failed to update invoice status.");
        $stmt_update_invoice->close();
        
        // 5. Create the booking using the centralized function
        $booking_id = createBookingFromInvoice($conn, $invoice_id);
        if (!$booking_id) {
            throw new Exception("Booking could not be created.");
        }

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

$conn->close();
?>