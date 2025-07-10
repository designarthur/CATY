<?php
// api/customer/payment_methods.php

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in() and $_SESSION['user_id']

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? ''; // Expected actions: 'add_method', 'set_default', 'delete_method', 'update_method'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add_method':
            handleAddPaymentMethod($conn, $user_id);
            break;
        case 'set_default':
            handleSetDefaultPaymentMethod($conn, $user_id);
            break;
        case 'delete_method':
            handleDeletePaymentMethod($conn, $user_id);
            break;
        case 'update_method': // NEW ACTION
            handleUpdatePaymentMethod($conn, $user_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close(); // Close DB connection at the end of script execution

function handleAddPaymentMethod($conn, $user_id) {
    $cardholderName = trim($_POST['cardholder_name'] ?? '');
    $cardNumber = trim(str_replace(' ', '', $_POST['card_number'] ?? '')); // Remove spaces
    $expiryDate = trim($_POST['expiry_date'] ?? ''); // MM/YY
    $cvv = trim($_POST['cvv'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $setDefault = isset($_POST['set_default']) && $_POST['set_default'] === 'on';

    // Basic server-side validation
    if (empty($cardholderName) || empty($cardNumber) || empty($expiryDate) || empty($cvv) || empty($billingAddress)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        return;
    }
    if (!preg_match('/^\d{13,16}$/', $cardNumber)) {
        echo json_encode(['success' => false, 'message' => 'Invalid card number format.']);
        return;
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiryDate, $matches)) {
        echo json_encode(['success' => false, 'message' => 'Invalid expiration date format (MM/YY).']);
        return;
    }
    $expMonth = $matches[1];
    $expYear = '20' . $matches[2]; // Convert YY to YYYY
    if (strtotime("$expMonth/01/$expYear") < strtotime(date('m/01/Y'))) {
        echo json_encode(['success' => false, 'message' => 'Expiration date is in the past.']);
        return;
    }
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CVV format (3 or 4 digits).']);
        return;
    }

    // For DEMO: Generate a dummy Braintree token and card type
    // In a real Braintree integration, these values would come from the Braintree API response.
    $braintreeToken = uniqid('braintree_token_') . substr($cardNumber, -4); // Dummy token based on last 4 digits for uniqueness
    
    // Determine card type based on first digit (simplified for demo)
    $firstDigit = substr($cardNumber, 0, 1);
    $cardType = 'Unknown';
    if ($firstDigit == '4') {
        $cardType = 'Visa';
    } elseif ($firstDigit == '5') {
        $cardType = 'MasterCard';
    } elseif ($firstDigit == '3') {
        $cardType = 'Amex';
    } else {
        $cardType = 'Discover'; // Fallback for other numbers
    }

    $lastFour = substr($cardNumber, -4); // Extract last four digits

    $conn->begin_transaction();
    try {
        // If 'set_default' is true, first unset all other defaults for this user
        if ($setDefault) {
            $stmt_unset_default = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
            $stmt_unset_default->bind_param("i", $user_id);
            $stmt_unset_default->execute();
            $stmt_unset_default->close();
        }

        // Insert new payment method into your database
        $stmt_insert = $conn->prepare("INSERT INTO user_payment_methods (user_id, braintree_payment_token, card_type, last_four, expiration_month, expiration_year, cardholder_name, is_default, billing_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("issssssis", $user_id, $braintreeToken, $cardType, $lastFour, $expMonth, $expYear, $cardholderName, $setDefault, $billingAddress);

        if ($stmt_insert->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Payment method added successfully!']);
        } else {
            throw new Exception("Database insert failed: " . $stmt_insert->error);
        }
        $stmt_insert->close();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Add payment method transaction failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to add payment method: ' . $e->getMessage()]);
    }
}

function handleSetDefaultPaymentMethod($conn, $user_id) {
    // The frontend is sending 'id' (database ID) for this action
    $methodId = $_POST['id'] ?? null;

    if (empty($methodId)) {
        echo json_encode(['success' => false, 'message' => 'Payment method ID required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Unset all other defaults for this user
        $stmt_unset_default = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
        $stmt_unset_default->bind_param("i", $user_id);
        $stmt_unset_default->execute();
        $stmt_unset_default->close();

        // Set the specified method as default
        $stmt_set_default = $conn->prepare("UPDATE user_payment_methods SET is_default = TRUE WHERE user_id = ? AND id = ?"); // Use 'id' here
        $stmt_set_default->bind_param("ii", $user_id, $methodId);
        if ($stmt_set_default->execute() && $stmt_set_default->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Default payment method updated.']);
        } else {
            throw new Exception("Payment method not found or failed to set as default.");
        }
        $stmt_set_default->close();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Set default payment method transaction failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to set default payment method: ' . $e->getMessage()]);
    }
}

function handleDeletePaymentMethod($conn, $user_id) {
    // The frontend is sending 'id' (database ID) for this action
    $methodId = $_POST['id'] ?? null;

    if (empty($methodId)) {
        echo json_encode(['success' => false, 'message' => 'Payment method ID required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Check if the method being deleted is the last default method
        $stmt_check_default = $conn->prepare("SELECT is_default FROM user_payment_methods WHERE user_id = ? AND id = ?");
        $stmt_check_default->bind_param("ii", $user_id, $methodId);
        $stmt_check_default->execute();
        $result_check = $stmt_check_default->get_result();
        $method = $result_check->fetch_assoc();
        $stmt_check_default->close();

        if ($method && $method['is_default']) {
            $stmt_count_methods = $conn->prepare("SELECT COUNT(*) FROM user_payment_methods WHERE user_id = ?");
            $stmt_count_methods->bind_param("i", $user_id);
            $stmt_count_methods->execute();
            $count = $stmt_count_methods->get_result()->fetch_row()[0];
            $stmt_count_methods->close();

            if ($count <= 1) { // If it's the last method and it's default
                echo json_encode(['success' => false, 'message' => 'Cannot delete the last payment method if it is set as default. Please set another as default first or delete all.']);
                $conn->rollback();
                return;
            }
        }

        // Delete from your database
        $stmt_delete = $conn->prepare("DELETE FROM user_payment_methods WHERE user_id = ? AND id = ?"); // Use 'id' here
        $stmt_delete->bind_param("ii", $user_id, $methodId);
        if ($stmt_delete->execute() && $stmt_delete->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Payment method deleted.']);
        } else {
            throw new Exception("Payment method not found or failed to delete.");
        }
        $stmt_delete->close();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete payment method transaction failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete payment method: ' . $e->getMessage()]);
    }
}

// NEW FUNCTION: handleUpdatePaymentMethod
function handleUpdatePaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null;
    $cardholderName = trim($_POST['cardholder_name'] ?? '');
    $expirationMonth = trim($_POST['expiration_month'] ?? '');
    $expirationYear = trim($_POST['expiration_year'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $setDefault = isset($_POST['set_default']) && $_POST['set_default'] === 'on';

    if (empty($methodId) || empty($cardholderName) || empty($expirationMonth) || empty($expirationYear) || empty($billingAddress)) {
        echo json_encode(['success' => false, 'message' => 'All fields (except CVV/Card Number for update) are required.']);
        return;
    }

    // Server-side validation for expiration date format and validity
    if (!preg_match('/^(0[1-9]|1[0-2])$/', $expirationMonth)) {
        echo json_encode(['success' => false, 'message' => 'Invalid expiration month format (MM).']);
        return;
    }
    if (!preg_match('/^\d{4}$/', $expirationYear)) {
        echo json_encode(['success' => false, 'message' => 'Invalid expiration year format (YYYY).']);
        return;
    }
    if (strtotime("$expirationMonth/01/$expirationYear") < strtotime(date('m/01/Y'))) {
        echo json_encode(['success' => false, 'message' => 'Expiration date is in the past.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // If 'set_default' is true, first unset all other defaults for this user
        if ($setDefault) {
            $stmt_unset_default = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
            $stmt_unset_default->bind_param("i", $user_id);
            $stmt_unset_default->execute();
            $stmt_unset_default->close();
        }

        $stmt_update = $conn->prepare("UPDATE user_payment_methods SET cardholder_name = ?, expiration_month = ?, expiration_year = ?, billing_address = ?, is_default = ? WHERE id = ? AND user_id = ?");
        $stmt_update->bind_param("ssssiii", $cardholderName, $expirationMonth, $expirationYear, $billingAddress, $setDefault, $methodId, $user_id);

        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Payment method updated successfully!']);
            } else {
                // If no rows affected, it means either data was identical or record not found/owned by user
                echo json_encode(['success' => true, 'message' => 'No changes made or payment method not found.']);
                $conn->rollback(); // No changes, so rollback is also fine to ensure no partial ops
            }
        } else {
            throw new Exception("Database update failed: " . $stmt_update->error);
        }
        $stmt_update->close();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update payment method transaction failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update payment method: ' . $e->getMessage()]);
    }
}


function handleFetchPaymentMethods($conn, $user_id) {
    $stmt = $conn->prepare("SELECT id, braintree_payment_token, card_type, last_four, expiration_month, expiration_year, cardholder_name, is_default FROM user_payment_methods WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $methods = [];
    while($row = $result->fetch_assoc()) {
        $methods[] = $row;
    }
    echo json_encode(['success' => true, 'methods' => $methods]);
    $stmt->close();
}