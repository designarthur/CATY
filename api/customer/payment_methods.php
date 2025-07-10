<?php
// api/customer/payment_methods.php

// Production-safe error reporting for API endpoint
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);


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
$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST to handle both GET and POST

if ($request_method === 'POST') {
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
        case 'update_method':
            handleUpdatePaymentMethod($conn, $user_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid POST action.']);
            break;
    }
} elseif ($request_method === 'GET') {
    switch($action) {
        case 'get_default_method':
            handleGetDefaultMethod($conn, $user_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid GET action.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();

function handleAddPaymentMethod($conn, $user_id) {
    $cardholderName = trim($_POST['cardholder_name'] ?? '');
    $cardNumber = trim(str_replace(' ', '', $_POST['card_number'] ?? ''));
    $expiryDate = trim($_POST['expiry_date'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $setDefault = isset($_POST['set_default']) && $_POST['set_default'] === 'on';

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
    $expYear = '20' . $matches[2];
    if (strtotime("$expYear-$expMonth-01") < strtotime(date('Y-m-01'))) {
        echo json_encode(['success' => false, 'message' => 'Expiration date is in the past.']);
        return;
    }
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CVV format (3 or 4 digits).']);
        return;
    }

    $braintreeToken = 'token_' . uniqid() . substr($cardNumber, -4);
    $firstDigit = substr($cardNumber, 0, 1);
    $cardType = 'Unknown';
    if ($firstDigit == '4') $cardType = 'Visa';
    elseif ($firstDigit == '5') $cardType = 'MasterCard';
    elseif ($firstDigit == '3') $cardType = 'Amex';

    $lastFour = substr($cardNumber, -4);

    $conn->begin_transaction();
    try {
        if ($setDefault) {
            $stmt_unset_default = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
            $stmt_unset_default->bind_param("i", $user_id);
            $stmt_unset_default->execute();
            $stmt_unset_default->close();
        }

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
        error_log("Add payment method failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to add payment method.']);
    }
}

function handleSetDefaultPaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null;
    if (empty($methodId)) {
        echo json_encode(['success' => false, 'message' => 'Payment method ID required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmt_unset = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
        $stmt_unset->bind_param("i", $user_id);
        $stmt_unset->execute();
        $stmt_unset->close();

        $stmt_set = $conn->prepare("UPDATE user_payment_methods SET is_default = TRUE WHERE id = ? AND user_id = ?");
        $stmt_set->bind_param("ii", $methodId, $user_id);
        if ($stmt_set->execute() && $stmt_set->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Default payment method updated.']);
        } else {
            throw new Exception("Payment method not found or failed to set as default.");
        }
        $stmt_set->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Set default failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to set default payment method.']);
    }
}

function handleDeletePaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null;
    if (empty($methodId)) {
        echo json_encode(['success' => false, 'message' => 'Payment method ID required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmt_check = $conn->prepare("SELECT is_default FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt_check->bind_param("ii", $methodId, $user_id);
        $stmt_check->execute();
        $method = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if ($method && $method['is_default']) {
            $stmt_count = $conn->prepare("SELECT COUNT(*) FROM user_payment_methods WHERE user_id = ?");
            $stmt_count->bind_param("i", $user_id);
            $stmt_count->execute();
            $count = $stmt_count->get_result()->fetch_row()[0];
            $stmt_count->close();
            if ($count <= 1) {
                throw new Exception("Cannot delete the only default payment method.");
            }
        }

        $stmt_delete = $conn->prepare("DELETE FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt_delete->bind_param("ii", $methodId, $user_id);
        if ($stmt_delete->execute() && $stmt_delete->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Payment method deleted.']);
        } else {
            throw new Exception("Payment method not found or failed to delete.");
        }
        $stmt_delete->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete method failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleUpdatePaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null;
    $cardholderName = trim($_POST['cardholder_name'] ?? '');
    $expirationMonth = trim($_POST['expiration_month'] ?? '');
    $expirationYear = trim($_POST['expiration_year'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $setDefault = isset($_POST['set_default']) && $_POST['set_default'] === 'on';

    if (empty($methodId) || empty($cardholderName) || empty($expirationMonth) || empty($expirationYear) || empty($billingAddress)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        return;
    }
    if (!preg_match('/^(0[1-9]|1[0-2])$/', $expirationMonth) || !preg_match('/^\d{4}$/', $expirationYear)) {
        echo json_encode(['success' => false, 'message' => 'Invalid expiration date format (MM/YYYY).']);
        return;
    }

    $conn->begin_transaction();
    try {
        if ($setDefault) {
            $stmt_unset = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
            $stmt_unset->bind_param("i", $user_id);
            $stmt_unset->execute();
            $stmt_unset->close();
        }

        $stmt_update = $conn->prepare("UPDATE user_payment_methods SET cardholder_name = ?, expiration_month = ?, expiration_year = ?, billing_address = ?, is_default = ? WHERE id = ? AND user_id = ?");
        $stmt_update->bind_param("ssssiii", $cardholderName, $expirationMonth, $expirationYear, $billingAddress, $setDefault, $methodId, $user_id);

        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Payment method updated successfully!']);
            } else {
                $conn->rollback();
                echo json_encode(['success' => true, 'message' => 'No changes made or payment method not found.']);
            }
        } else {
            throw new Exception("Database update failed: " . $stmt_update->error);
        }
        $stmt_update->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update method failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update payment method.']);
    }
}

function handleGetDefaultMethod($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? AND is_default = TRUE LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $method = $result->fetch_assoc();
    $stmt->close();

    if ($method) {
        echo json_encode(['success' => true, 'method' => $method]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No default payment method found.']);
    }
}
?>