<?php
// includes/functions.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // For PHPMailer and Dotenv

// Load environment variables if not already loaded (e.g., if called directly)
if (!isset($_ENV['DB_HOST'])) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

/**
 * Redirects to a specified URL.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Hashes a password.
 * @param string $password The plain text password.
 * @return string The hashed password.
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifies a password against a hash.
 * @param string $password The plain text password.
 * @param string $hash The hashed password.
 * @return bool True if passwords match, false otherwise.
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Sends an email using PHPMailer.
 * @param string $to Recipient email address.
 * @param string $subject Email subject.
 * @param string $body HTML or plain text body of the email.
 * @param string $altBody Optional: plain text alternative body.
 * @return bool True on success, false on failure.
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    $mail = new PHPMailer(true); // Enable exceptions

    try {
        //Server settings
        $mail->isSMTP(); // Send using SMTP
        $mail->Host       = 'smtp.example.com'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true; // Enable SMTP authentication
        $mail->Username   = 'your_email@example.com'; // SMTP username
        $mail->Password   = 'your_smtp_password'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
        $mail->Port       = 465; // TCP port to connect to; use 587 if `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //Recipients
        $mail->setFrom('no-reply@' . parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST), $_ENV['COMPANY_NAME']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody; // Plain-text for non-HTML mail clients

        $mail->send();
        error_log("Email sent successfully to: " . $to . " with subject: " . $subject);
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent to $to. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Generates a unique token for email verification, etc.
 * @param int $length
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get system setting value from database
 * @param string $key
 * @return string|null
 */
function getSystemSetting($key) {
    global $conn; // Access the global database connection

    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return null;
}
?>