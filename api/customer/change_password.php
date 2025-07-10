<?php
// api/customer/change_password.php

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in() and $_SESSION['user_id']
require_once __DIR__ . '/../../includes/functions.php'; // For hashPassword() and verifyPassword()

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    // Server-side validation
    if (empty($currentPassword) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Both current and new password are required.']);
        exit;
    }
    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']);
        exit;
    }

    // 1. Fetch user's current hashed password from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $storedHashedPassword = $user['password'];

        // 2. Verify current password
        if (verifyPassword($currentPassword, $storedHashedPassword)) {
            // Check if new password is the same as old password
            if (verifyPassword($newPassword, $storedHashedPassword)) {
                echo json_encode(['success' => false, 'message' => 'New password cannot be the same as your current password.']);
                $stmt->close();
                $conn->close();
                exit;
            }

            // 3. Hash the new password and update in DB
            $newHashedPassword = hashPassword($newPassword);

            $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $newHashedPassword, $user_id);

            if ($stmt_update->execute()) {
                echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
            } else {
                error_log("Failed to update password for user ID $user_id: " . $stmt_update->error);
                echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
            }
            $stmt_update->close();

        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
        }

    } else {
        // User not found (shouldn't happen if is_logged_in() passed)
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }

    $stmt->close();
    $conn->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>