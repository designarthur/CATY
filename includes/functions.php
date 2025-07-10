<?php
// includes/functions.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // For PHPMailer and Dotenv

// Load environment variables if not already loaded
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
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'];

        //Recipients
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody;

        $mail->send();
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
    global $conn;

    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return null;
}

/**
 * Creates a booking from a given invoice ID.
 * This function is now the single source of truth for booking creation.
 *
 * @param mysqli $conn The database connection object.
 * @param int $invoice_id The ID of the paid invoice.
 * @return int|null The new booking ID on success, or null on failure.
 * @throws Exception on failure.
 */
function createBookingFromInvoice(mysqli $conn, int $invoice_id): ?int
{
    // 1. Check if a booking already exists for this invoice to prevent duplicates
    $stmt_check = $conn->prepare("SELECT id FROM bookings WHERE invoice_id = ?");
    $stmt_check->bind_param("i", $invoice_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        // Booking already exists, so we don't need to do anything.
        $stmt_check->close();
        return $result_check->fetch_assoc()['id'];
    }
    $stmt_check->close();

    // 2. Fetch all necessary data from the invoice and its related quote
    $stmt_fetch = $conn->prepare("
        SELECT 
            i.user_id, i.amount, i.quote_id,
            q.service_type, q.location, q.delivery_date, q.removal_date, q.live_load_needed, 
            q.is_urgent, q.driver_instructions,
            qed.equipment_name, qed.quantity, qed.specific_needs, qed.duration_days,
            jrd.junk_items_json, jrd.recommended_dumpster_size, jrd.additional_comment, jrd.media_urls_json,
            u.first_name, u.last_name, u.email
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
    
    // 3. Update the related quote status to 'converted_to_booking'
    if ($data['quote_id']) {
        $stmt_quote = $conn->prepare("UPDATE quotes SET status = 'converted_to_booking' WHERE id = ?");
        $stmt_quote->bind_param("i", $data['quote_id']);
        if (!$stmt_quote->execute()) {
            throw new Exception("Failed to update quote status.");
        }
        $stmt_quote->close();
    }

    // 4. Prepare booking details
    $booking_number = 'BK-' . str_pad($invoice_id, 6, '0', STR_PAD_LEFT);
    $start_date = $data['delivery_date'] ?? $data['removal_date'];
    $end_date = null;
    $equipment_details_json = null;
    $junk_details_json = null;

    if ($data['service_type'] == 'equipment_rental') {
        $duration = $data['duration_days'] ?? 7;
        $end_date = $start_date ? (new DateTime($start_date))->modify("+$duration days")->format('Y-m-d') : null;
        $equipment_details_json = json_encode([[
            'equipment_name' => $data['equipment_name'],
            'quantity' => $data['quantity'],
            'specific_needs' => $data['specific_needs'],
            'duration_days' => $data['duration_days']
        ]]);
    } else if ($data['service_type'] == 'junk_removal') {
        $end_date = $start_date; // Junk removal is typically a single-day event
        $junk_details_json = json_encode([
            'junkItems' => json_decode($data['junk_items_json'] ?? '[]', true),
            'recommendedDumpsterSize' => $data['recommended_dumpster_size'],
            'additionalComment' => $data['additional_comment'],
            'mediaUrls' => json_decode($data['media_urls_json'] ?? '[]', true)
        ]);
    }

    // 5. Insert the new booking record
    $stmt_booking = $conn->prepare("
        INSERT INTO bookings 
            (invoice_id, user_id, booking_number, service_type, status, start_date, end_date, delivery_location, 
            delivery_instructions, live_load_requested, is_urgent, total_price, equipment_details, junk_details) 
        VALUES (?, ?, ?, ?, 'scheduled', ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_booking->bind_param("iissssssiiidss", 
        $invoice_id, $data['user_id'], $booking_number, $data['service_type'], $start_date, $end_date, $data['location'], 
        $data['driver_instructions'], $data['live_load_needed'], $data['is_urgent'], $data['amount'], 
        $equipment_details_json, $junk_details_json
    );

    if (!$stmt_booking->execute()) {
        throw new Exception("Database insert failed for booking: " . $stmt_booking->error);
    }
    
    $booking_id = $conn->insert_id;
    $stmt_booking->close();
    
    // 6. Send notifications (email and system)
    $companyName = getSystemSetting('company_name');
    $dashboardLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/customer/dashboard.php#bookings?booking_id={$booking_id}";

    $emailSubject = "Your {$companyName} Booking #{$booking_number} is Confirmed!";
    $emailBody = "<p>Dear " . htmlspecialchars($data['first_name']) . ",</p><p>Your booking for " . htmlspecialchars(ucwords(str_replace('_', ' ', $data['service_type']))) . " (Booking #{$booking_number}) has been confirmed.</p><p>You can view full details here: <a href='{$dashboardLink}'>View Your Booking</a></p>";
    sendEmail($data['email'], $emailSubject, $emailBody, "Your booking #{$booking_number} is confirmed.");

    $notification_message = "Your booking #{$booking_number} has been confirmed!";
    $notification_link = "bookings?booking_id={$booking_id}";
    $notification_type = 'booking_confirmed';
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
    $stmt_notify->bind_param("isss", $data['user_id'], $notification_type, $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();
    
    return $booking_id;
}
?>