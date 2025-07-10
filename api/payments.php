<?php
// api/payments.php - Handles Braintree payment processing and booking creation

// --- Production-Ready Error Handling ---
// This setup ensures that no sensitive error details are ever displayed to the user.
// Instead, they are logged for you to review, and the user sees a generic error message.
ini_set('display_errors', 0);
error_reporting(E_ALL);
// It's recommended to set a central error log path in your php.ini for production, e.g.:
// ini_set('error_log', '/path/to/your/php_errors.log');

// This function will catch any fatal errors that aren't caught by the try-catch block.
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        // Log the detailed error
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        // Send a generic response to the user
        echo json_encode([
            'success' => false,
            'message' => 'A critical server error occurred. Our team has been notified.'
        ]);
    }
});


// Start the session and include all necessary files.
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Set the content type to JSON for all responses from this file.
header('Content-Type: application/json');

// Security check: Ensure the user is logged in before proceeding.
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

// Ensure the request is a POST request, as it modifies data.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get the current user's ID from the session.
$user_id = $_SESSION['user_id'];

// --- Initialize Braintree Gateway ---
// This block connects to the Braintree service using credentials from your .env file.
try {
    $gateway = new Braintree\Gateway([
        'environment' => $_ENV['BRAINTREE_ENVIRONMENT'] ?? 'sandbox',
        'merchantId'  => $_ENV['BRAINTREE_MERCHANT_ID'],
        'publicKey'   => $_ENV['BRAINTREE_PUBLIC_KEY'],
        'privateKey'  => $_ENV['BRAINTREE_PRIVATE_KEY']
    ]);
} catch (Exception $e) {
    error_log("Braintree Gateway initialization failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment system configuration error.']);
    exit;
}

// --- Input Validation ---
// Sanitize and validate all incoming data from the payment form.
$invoiceNumber      = trim($_POST['invoice_number'] ?? '');
$amountToPay        = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
// A nonce is a one-time-use token from the Braintree JS SDK for a new card.
$paymentMethodNonce = $_POST['payment_method_nonce'] ?? null;
// A token is a permanent, safe identifier for a card already saved in Braintree's Vault.
$paymentMethodToken = $_POST['payment_method_token'] ?? null;
// A boolean flag to indicate if the user wants to save a new card for future use.
$saveCard           = filter_var($_POST['save_card'] ?? false, FILTER_VALIDATE_BOOLEAN);


if (empty($invoiceNumber) || $amountToPay <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid invoice details or amount.']);
    exit;
}

// A payment can only be processed if we have either a one-time nonce or a saved token.
if (empty($paymentMethodNonce) && empty($paymentMethodToken)) {
    echo json_encode(['success' => false, 'message' => 'A valid payment method is required.']);
    exit;
}


// Start a database transaction. This ensures that all database operations
// (updating invoice, creating booking, etc.) either all succeed or all fail together.
$conn->begin_transaction();

try {
    // 1. Fetch the invoice from the database to verify it exists and belongs to the user.
    $stmt_invoice = $conn->prepare("SELECT id FROM invoices WHERE invoice_number = ? AND user_id = ? AND status IN ('pending', 'partially_paid')");
    $stmt_invoice->bind_param("si", $invoiceNumber, $user_id);
    $stmt_invoice->execute();
    $result_invoice = $stmt_invoice->get_result();
    if ($result_invoice->num_rows === 0) {
        throw new Exception("Invoice not found, already paid, or you are not authorized to pay it.");
    }
    $invoice_id = $result_invoice->fetch_assoc()['id'];
    $stmt_invoice->close();

    // 2. Prepare the transaction request for Braintree.
    $saleRequest = [
        'amount'   => (string)$amountToPay,
        'orderId'  => "INV-" . $invoiceNumber, // A unique ID for the transaction.
        'options'  => [
            'submitForSettlement' => true, // Process the payment immediately.
        ],
        // Associate the transaction with a customer in Braintree for better tracking.
        'customer' => [
            'id'        => $user_id,
            'firstName' => $_SESSION['user_first_name'],
            'lastName'  => $_SESSION['user_last_name'],
            'email'     => $_SESSION['user_email']
        ]
    ];

    // Use the appropriate payment identifier.
    if (!empty($paymentMethodNonce)) {
        $saleRequest['paymentMethodNonce'] = $paymentMethodNonce;
        // If the user checked "Save card", instruct Braintree to store it securely in the Vault.
        if ($saveCard) {
            $saleRequest['options']['storeInVaultOnSuccess'] = true;
        }
    } else {
        $saleRequest['paymentMethodToken'] = $paymentMethodToken;
    }


    // 3. Process the transaction by sending the request to Braintree.
    $result = $gateway->transaction()->sale($saleRequest);

    if (!$result->success) {
        // If Braintree rejects the transaction, throw an exception with their error message.
        throw new Exception("Payment gateway error: " . $result->message);
    }

    $transaction = $result->transaction;
    $transaction_id = $transaction->id;
    // Get card details from the successful transaction for display/record-keeping.
    $payment_method_used = $transaction->creditCardDetails->cardType . " ending in " . $transaction->creditCardDetails->last4;

    // If a new card was successfully saved to the Vault, Braintree will return a new token.
    // We must save this new token in our database for future use.
    if ($saveCard && isset($transaction->creditCardDetails->token)) {
        $newPaymentToken = $transaction->creditCardDetails->token;
        $cardDetails = $transaction->creditCardDetails;

        // Note: We are only storing the SAFE token from Braintree, not the actual card number.
        $stmt_save_token = $conn->prepare(
            "INSERT INTO user_payment_methods (user_id, braintree_payment_token, card_type, last_four, expiration_month, expiration_year, cardholder_name, billing_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt_save_token->bind_param("isssssss",
            $user_id,
            $newPaymentToken,
            $cardDetails->cardType,
            $cardDetails->last4,
            $cardDetails->expirationMonth,
            $cardDetails->expirationYear,
            $cardDetails->cardholderName,
            $_POST['billing_address'] ?? '' // Get billing address from the form.
        );
        $stmt_save_token->execute();
        $stmt_save_token->close();
    }


    // 4. Update the invoice status in our database to 'paid'.
    $stmt_update_invoice = $conn->prepare("UPDATE invoices SET status = 'paid', payment_method = ?, transaction_id = ? WHERE id = ?");
    $stmt_update_invoice->bind_param("ssi", $payment_method_used, $transaction_id, $invoice_id);
    if (!$stmt_update_invoice->execute()) {
        throw new Exception("Failed to update invoice status in the database.");
    }
    $stmt_update_invoice->close();

    // 5. Create the corresponding booking using the centralized function from functions.php.
    $booking_id = createBookingFromInvoice($conn, $invoice_id);
    if (!$booking_id) {
        throw new Exception("Booking could not be created after successful payment.");
    }

    // If all steps are successful, commit the transaction to make the changes permanent.
    $conn->commit();
    echo json_encode([
        'success'        => true,
        'message'        => 'Payment successful and booking confirmed!',
        'transaction_id' => $transaction_id,
        'booking_id'     => $booking_id
    ]);

} catch (Exception $e) {
    // If any step in the try block fails, roll back all database changes.
    $conn->rollback();
    error_log("Payment processing failed for Invoice: $invoiceNumber, User: $user_id. Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()]);
}

$conn->close();
?>