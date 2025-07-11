<?php
// includes/functions.php

// --- Dependency Loader & Namespace Imports ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// This ensures all Composer packages (like PHPMailer and Dotenv) are available.
require_once __DIR__ . '/../vendor/autoload.php';

// --- Environment Variable Loading ---
// **THE FIX**: This block is now simplified to ensure it runs every time.
// It loads the .env file from the root directory, populating the $_ENV superglobal
// with your database and SMTP credentials. This resolves the "Undefined array key" warnings.
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
    error_log("Could not load .env file: " . $e->getMessage());
    // In a production environment, you might want to die() here if the .env file is critical.
}


// --- Core Utility Functions ---

/**
 * Redirects the user to a specified URL and terminates the script.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Hashes a password using the modern and secure BCRYPT algorithm.
 * @param string $password The plain text password.
 * @return string The hashed password.
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifies a plain text password against a stored hash.
 * @param string $password The plain text password.
 * @param string $hash The hashed password from the database.
 * @return bool True if the passwords match, false otherwise.
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}


// --- CSRF Protection Functions ---

/**
 * Generates and stores a CSRF token in the session if one doesn't exist.
 * @return string The generated token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the submitted CSRF token against the one in the session.
 * @throws Exception if the token is invalid or missing.
 */
function validate_csrf_token() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('CSRF token validation failed. Please refresh and try again.');
    }
    unset($_SESSION['csrf_token']);
}


// --- Email Sending Function ---

/**
 * Sends an email using PHPMailer with credentials from environment variables.
 * @param string $to Recipient's email address.
 * @param string $subject The email subject.
 * @param string $body The HTML body of the email.
 * @param string $altBody Optional plain text alternative.
 * @return bool True on success, false on failure.
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    $mail = new PHPMailer(true);

    try {
        // Server settings from the now-loaded .env file
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'];

        // Recipients
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        // **Secondary Fix**: Safely generate the plain-text body to prevent the "Deprecated" notice.
        $mail->AltBody = empty($altBody) ? strip_tags($body) : $altBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent to {$to}. Mailer Error: {$mail->ErrorInfo}");
        return false; // Return false but don't crash the script.
    }
}


// --- Data & Business Logic Functions ---

/**
 * Generates a cryptographically secure random token.
 * @param int $length The desired length of the token string.
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Retrieves a system setting value from the database, using a static cache.
 * @param string $key The setting key to retrieve.
 * @return string|null
 */
function getSystemSetting($key) {
    global $conn;
    static $settings = [];

    if (!isset($settings[$key])) {
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $settings[$key] = $result['setting_value'] ?? null;
        $stmt->close();
    }
    return $settings[$key];
}


/**
 * Gets the count of unread items for the admin sidebar.
 * @return array Associative array with 'quotes' and 'invoices' counts.
 */
function get_admin_notification_counts() {
    global $conn;
    $counts = ['quotes' => 0, 'invoices' => 0];

    // Get count of unviewed quotes
    $result_quotes = $conn->query("SELECT COUNT(*) AS count FROM quotes WHERE is_viewed_by_admin = 0 AND status = 'pending'");
    if ($result_quotes) {
        $counts['quotes'] = $result_quotes->fetch_assoc()['count'];
    }

    // Get count of unviewed invoices
    $result_invoices = $conn->query("SELECT COUNT(*) AS count FROM invoices WHERE is_viewed_by_admin = 0 AND status = 'pending'");
    if ($result_invoices) {
        $counts['invoices'] = $result_invoices->fetch_assoc()['count'];
    }

    return $counts;
}


/**
 * Centralized function to create a booking after a successful payment.
 * @param mysqli $conn The database connection object.
 * @param int $invoice_id The ID of the paid invoice.
 * @return int|null The new booking ID on success, or null on failure.
 * @throws Exception on any failure.
 */
function createBookingFromInvoice(mysqli $conn, int $invoice_id): ?int {
    // ... (This function remains the same as the previously corrected version) ...
    // Fetch all necessary data from the invoice, quote, and user tables.
    $stmt_fetch = $conn->prepare("
        SELECT
            i.user_id, i.amount, i.quote_id,
            q.service_type, q.location, q.delivery_date, q.removal_date, q.live_load_needed,
            q.is_urgent, q.driver_instructions,
            qed.equipment_name, qed.quantity, qed.specific_needs, qed.duration_days,
            jrd.junk_items_json, jrd.recommended_dumpster_size,
            u.first_name, u.email
        FROM invoices i
        JOIN users u ON i.user_id = u.id
        LEFT JOIN quotes q ON i.quote_id = q.id
        LEFT JOIN quote_equipment_details qed ON q.id = qed.quote_id
        LEFT JOIN junk_removal_details jrd ON q.id = jrd.quote_id
        WHERE i.id = ?
    ");
    $stmt_fetch->bind_param("i", $invoice_id);
    $stmt_fetch->execute();
    $data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$data) {
        throw new Exception("Could not retrieve necessary data to create booking for invoice ID: {$invoice_id}");
    }

    if ($data['quote_id']) {
        $stmt_quote = $conn->prepare("UPDATE quotes SET status = 'converted_to_booking' WHERE id = ?");
        $stmt_quote->bind_param("i", $data['quote_id']);
        $stmt_quote->execute();
        $stmt_quote->close();
    }

    $booking_number = 'BK-' . str_pad($invoice_id, 6, '0', STR_PAD_LEFT);
    $start_date = $data['delivery_date'] ?? $data['removal_date'];
    $end_date = null;
    $equipment_details_json = null;
    $junk_details_json = null;

    if ($data['service_type'] == 'equipment_rental') {
        $duration = $data['duration_days'] ?? 7;
        $end_date = $start_date ? (new DateTime($start_date))->modify("+$duration days")->format('Y-m-d') : null;
        $equipment_details_json = json_encode([['equipment_name' => $data['equipment_name'], 'quantity' => $data['quantity'], 'specific_needs' => $data['specific_needs']]]);
    } else if ($data['service_type'] == 'junk_removal') {
        $junk_details_json = json_encode(['junkItems' => json_decode($data['junk_items_json'] ?? '[]', true), 'recommendedDumpsterSize' => $data['recommended_dumpster_size']]);
    }
    
    $stmt_booking = $conn->prepare("
        INSERT INTO bookings (invoice_id, user_id, booking_number, service_type, status, start_date, end_date, delivery_location, delivery_instructions, live_load_requested, is_urgent, total_price, equipment_details, junk_details)
        VALUES (?, ?, ?, ?, 'scheduled', ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_booking->bind_param("iissssssiisss", $invoice_id, $data['user_id'], $booking_number, $data['service_type'], $start_date, $end_date, $data['location'], $data['driver_instructions'], $data['live_load_needed'], $data['is_urgent'], $data['amount'], $equipment_details_json, $junk_details_json);
    
    if (!$stmt_booking->execute()) {
        throw new Exception("Database insert failed for booking: " . $stmt_booking->error);
    }
    
    $booking_id = $conn->insert_id;
    $stmt_booking->close();
    
    $dashboardLink = "https://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#bookings?booking_id={$booking_id}";
    $emailSubject = "Your Booking #{$booking_number} is Confirmed!";
    $emailBody = "<p>Dear {$data['first_name']},</p><p>Your booking for " . ucwords(str_replace('_', ' ', $data['service_type'])) . " (Booking #{$booking_number}) has been confirmed.</p><p>You can view full details here: <a href='{$dashboardLink}'>View Your Booking</a></p>";
    sendEmail($data['email'], $emailSubject, $emailBody);

    $notification_message = "Your booking #{$booking_number} has been confirmed!";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_confirmed', ?, ?)");
    $stmt_notify->bind_param("iss", $data['user_id'], $notification_message, $dashboardLink);
    $stmt_notify->execute();
    $stmt_notify->close();

    return $booking_id;
}
?>