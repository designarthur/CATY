<?php
// api/admin/users.php - Admin API for Users Management

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in() and has_role()
require_once __DIR__ . '/../../includes/functions.php'; // For hashPassword(), generateToken(), sendEmail(), getSystemSetting()

header('Content-Type: application/json');

// Check if user is logged in and has admin role
if (!is_logged_in() || !has_role('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$acting_admin_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? ''; // Expected actions: 'update_user', 'reset_password', 'delete_user'
$user_id_to_manage = $_POST['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($user_id_to_manage)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required.']);
        exit;
    }

    // Prevent admin from modifying or deleting their own critical role/account
    if ($action === 'delete_user' && (int)$user_id_to_manage === (int)$acting_admin_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own admin account.']);
        exit;
    }
    // For role update, prevent admin from changing their own role
    if ($action === 'update_user' && (int)$user_id_to_manage === (int)$acting_admin_id && isset($_POST['role']) && $_POST['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'You cannot change your own role.']);
        exit;
    }

    switch ($action) {
        case 'update_user':
            handleUpdateUser($conn, $user_id_to_manage);
            break;
        case 'reset_password':
            handleResetPassword($conn, $user_id_to_manage);
            break;
        case 'delete_user':
            handleDeleteUser($conn, $user_id_to_manage);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();

function handleUpdateUser($conn, $user_id_to_manage) {
    // Retrieve and sanitize input data
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zipCode = trim($_POST['zip_code'] ?? '');
    $role = trim($_POST['role'] ?? '');

    // Server-side validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phoneNumber)) {
        echo json_encode(['success' => false, 'message' => 'First Name, Last Name, Email, and Phone Number are required.']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        return;
    }
    if (!preg_match('/^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/', $phoneNumber)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format.']);
        return;
    }
    $allowedRoles = ['customer', 'admin', 'vendor'];
    if (!in_array($role, $allowedRoles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid user role specified.']);
        return;
    }

    // Check if new email already exists for another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id_to_manage);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This email is already in use by another account.']);
        $stmt->close();
        return;
    }
    $stmt->close();

    $conn->begin_transaction();
    try {
        // Update user data in the database
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, city = ?, state = ?, zip_code = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssssssssi", $firstName, $lastName, $email, $phoneNumber, $address, $city, $state, $zipCode, $role, $user_id_to_manage);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update user in DB: " . $stmt->error);
        }
        $stmt->close();

        // If the user's role changed to 'vendor', and they don't exist in 'vendors' table, add them.
        // Or if role changed from 'vendor', set vendor to inactive.
        // This is a simplified example; actual logic might involve more robust vendor management.
        if ($role === 'vendor') {
            $stmt_check_vendor = $conn->prepare("SELECT id FROM vendors WHERE email = ?");
            $stmt_check_vendor->bind_param("s", $email);
            $stmt_check_vendor->execute();
            $result_vendor = $stmt_check_vendor->get_result();
            if ($result_vendor->num_rows === 0) {
                $stmt_insert_vendor = $conn->prepare("INSERT INTO vendors (name, email, phone_number) VALUES (?, ?, ?)");
                $vendor_name = $firstName . ' ' . $lastName;
                $stmt_insert_vendor->bind_param("sss", $vendor_name, $email, $phoneNumber);
                $stmt_insert_vendor->execute();
                $stmt_insert_vendor->close();
            }
            $stmt_check_vendor->close();
        } else {
            // If user role is no longer 'vendor', you might deactivate or remove them from the vendors table
            $stmt_deactivate_vendor = $conn->prepare("UPDATE vendors SET is_active = FALSE WHERE email = ?");
            $stmt_deactivate_vendor->bind_param("s", $email);
            $stmt_deactivate_vendor->execute();
            $stmt_deactivate_vendor->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'User updated successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update user transaction failed for user ID $user_id_to_manage: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update user: ' . $e->getMessage()]);
    }
}

function handleResetPassword($conn, $user_id_to_manage) {
    // Fetch user's email for sending new password
    $stmt = $conn->prepare("SELECT email, first_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id_to_manage);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user_data) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        return;
    }

    $new_password = generateToken(8); // Generate a temporary password
    $hashed_password = hashPassword($new_password);

    $conn->begin_transaction();
    try {
        $stmt_update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt_update_pass->bind_param("si", $hashed_password, $user_id_to_manage);
        if (!$stmt_update_pass->execute()) {
            throw new Exception("Failed to update password in DB: " . $stmt_update_pass->error);
        }
        $stmt_update_pass->close();

        // Send new password via email
        $customerEmail = $user_data['email'];
        $customerName = htmlspecialchars($user_data['first_name']);
        $companyName = getSystemSetting('company_name');
        $loginLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/customer/login.php";

        ob_start();
        include __DIR__ . '/../../includes/mail_templates/account_creation_email.php'; // Re-use template for temporary password
        $emailBody = ob_get_clean();

        $emailSent = sendEmail($customerEmail, "Your Password Has Been Reset for {$companyName}", $emailBody, "Your temporary password is: {$new_password}. Login here: {$loginLink}");

        if (!$emailSent) {
            throw new Exception("Failed to send new password email to user.");
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Password reset and emailed successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Password reset transaction failed for user ID $user_id_to_manage: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to reset password: ' . $e->getMessage()]);
    }
}

function handleDeleteUser($conn, $user_id_to_manage) {
    $conn->begin_transaction();
    try {
        // First, check if the user exists and is not the current admin
        $stmt_check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt_check_user->bind_param("i", $user_id_to_manage);
        $stmt_check_user->execute();
        if ($stmt_check_user->get_result()->num_rows === 0) {
            throw new Exception("User not found.");
        }
        $stmt_check_user->close();

        // Delete the user. Cascading deletes should handle related records (quotes, invoices, bookings, notifications, payment methods).
        // Ensure your SQL schema has ON DELETE CASCADE for foreign keys on `quotes`, `invoices`, `bookings`, `notifications`, `user_payment_methods`, `junk_removal_details`, `quote_equipment_details`, `junk_removal_media`.
        $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_delete->bind_param("i", $user_id_to_manage);

        if (!$stmt_delete->execute()) {
            throw new Exception("Failed to delete user from DB: " . $stmt_delete->error);
        }
        $stmt_delete->close();

        // Optional: If you had vendor records that map to users, update/delete them here too
        // or ensure your `vendors` table foreign key to `users` has ON DELETE CASCADE.
        $stmt_deactivate_vendor = $conn->prepare("UPDATE vendors SET is_active = FALSE WHERE email = (SELECT email FROM users_archive WHERE id = ?)"); // Assuming archive for email lookup
        // More robust: use a JOIN or pass email directly
        $stmt_deactivate_vendor = $conn->prepare("UPDATE vendors SET is_active = FALSE WHERE email = (SELECT email FROM (SELECT email FROM users WHERE id = ?) AS temp_user)");
        $stmt_deactivate_vendor->bind_param("i", $user_id_to_manage);
        $stmt_deactivate_vendor->execute();
        $stmt_deactivate_vendor->close();


        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'User deleted successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete user transaction failed for user ID $user_id_to_manage: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()]);
    }
}
?>