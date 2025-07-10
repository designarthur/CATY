<?php
// api/payments.php - Handles Braintree payment processing and booking creation

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php'; // For is_logged_in() and $_SESSION['user_id']
require_once __DIR__ . '/../includes/functions.php'; // For sendEmail() and other utilities

// Include Braintree SDK
require_once __DIR__ . '/../vendor/autoload.php';

// Set response header
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Initialize Braintree Gateway
try {
    $gateway = new Braintree\Gateway([
        'environment' => $_ENV['BRAINTREE_ENVIRONMENT'] ?? 'sandbox', // Default to sandbox if env not set
        'merchantId' => $_ENV['BRAINTREE_MERCHANT_ID'],
        'publicKey' => $_ENV['BRAINTREE_PUBLIC_KEY'],
        'privateKey' => $_ENV['BRAINTREE_PRIVATE_KEY']
    ]);
} catch (Exception $e) {
    error_log("Braintree Gateway initialization failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment system configuration error.']);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'process_payment') {
    $invoiceNumber = trim($_POST['invoice_number'] ?? '');
    $amountToPay = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);

    // IMPORTANT: In a real Braintree integration, the client-side would pass a `payment_method_nonce`
    // from Braintree.js, NOT raw card details.
    // For this demo, we'll simulate the nonce, assuming payment details were handled client-side or are dummy.
    // Replace this with the actual nonce from your frontend integration:
    $paymentMethodNonce = 'fake-valid-nonce'; // Example: 'fake-valid-nonce', 'fake-valid-visa-nonce', etc.
    // Or, if using saved payment methods, pass the Braintree payment method token instead of nonce:
    // $paymentMethodToken = $_POST['braintree_payment_token'] ?? $paymentMethodNonce;


    if (empty($invoiceNumber) || $amountToPay <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid invoice details or amount.']);
        exit;
    }

    $conn->begin_transaction(); // Start transaction

    try {
        // 1. Fetch Invoice and associated Quote/Detail Data
        // IMPORTANT: Ensure junk_items_json and media_urls_json are read with ?? '[]'
        $stmt_invoice = $conn->prepare("SELECT
                                            i.id AS invoice_id, i.status AS invoice_status, i.amount AS invoice_total_amount, i.quote_id,
                                            q.user_id, q.service_type, q.location AS delivery_location, q.delivery_date, q.removal_date,
                                            q.live_load_needed, q.is_urgent, q.driver_instructions AS delivery_instructions, -- Map to delivery_instructions for booking
                                            q.quote_details,
                                            qed.quantity AS equipment_quantity, qed.equipment_name, qed.specific_needs, qed.duration_days, -- Added duration_days
                                            jrd.junk_items_json, jrd.recommended_dumpster_size, jrd.additional_comment, jrd.media_urls_json
                                        FROM
                                            invoices i
                                        LEFT JOIN
                                            quotes q ON i.quote_id = q.id
                                        LEFT JOIN
                                            quote_equipment_details qed ON q.id = qed.quote_id
                                        LEFT JOIN
                                            junk_removal_details jrd ON q.id = jrd.quote_id
                                        WHERE
                                            i.invoice_number = ? AND i.user_id = ? AND i.status IN ('pending', 'partially_paid')");
        $stmt_invoice->bind_param("si", $invoiceNumber, $user_id);
        $stmt_invoice->execute();
        $result_invoice = $stmt_invoice->get_result();

        if ($result_invoice->num_rows === 0) {
            throw new Exception("Invoice not found, already paid, or not authorized for this user.");
        }
        $invoice_data = $result_invoice->fetch_assoc();
        $invoice_id = $invoice_data['invoice_id'];
        $quote_id = $invoice_data['quote_id'];
        $invoice_current_status = $invoice_data['invoice_status'];
        $invoice_total_amount = $invoice_data['invoice_total_amount'];

        // Ensure the payment amount matches the outstanding balance (simplified for now to total amount)
        if ($amountToPay < $invoice_total_amount) {
            throw new Exception("Partial payments not supported for this demo. Please pay the full amount of $" . number_format($invoice_total_amount, 2));
        }


        // 2. Process Transaction with Braintree (Simulated for demo)
        // In a real application, you'd use the actual paymentMethodNonce from Braintree.js
        $result = $gateway->transaction()->sale([
            'amount' => (string) $amountToPay,
            'paymentMethodNonce' => $paymentMethodNonce,
            'options' => [
                'submitForSettlement' => true
            ]
        ]);

        if (!$result->success) {
            $braintreeErrorMessage = $result->message;
            if ($result->transaction) {
                $braintreeErrorMessage .= " Status: " . $result->transaction->status;
            }
            throw new Exception("Braintree transaction failed: " . $braintreeErrorMessage);
        }

        $transaction = $result->transaction;
        $transaction_id = $transaction->id;
        $transaction_status = $transaction->status;

        // 3. Update Invoice Status
        $stmt_update_invoice = $conn->prepare("UPDATE invoices SET status = 'paid', payment_method = ?, transaction_id = ? WHERE id = ?");
        $payment_method_name = 'Credit Card'; // Or dynamically determined by Braintree result
        $stmt_update_invoice->bind_param("ssi", $payment_method_name, $transaction_id, $invoice_id);
        if (!$stmt_update_invoice->execute()) {
            throw new Exception("Failed to update invoice status: " . $stmt_update_invoice->error);
        }
        $stmt_update_invoice->close();

        // 4. Update Quote Status
        if ($quote_id) { // Ensure quote_id exists before attempting to update
            $stmt_update_quote_status = $conn->prepare("UPDATE quotes SET status = 'converted_to_booking' WHERE id = ?");
            $stmt_update_quote_status->bind_param("i", $quote_id);
            if (!$stmt_update_quote_status->execute()) {
                throw new Exception("Failed to update quote status to converted_to_booking: " . $stmt_update_quote_status->error);
            }
            $stmt_update_quote_status->close();
        }

        // 5. Create Booking Entry
        $booking_number = 'BK-' . str_pad($invoice_id, 6, '0', STR_PAD_LEFT);
        $service_type = $invoice_data['service_type'];
        $delivery_location = $invoice_data['delivery_location'];

        $start_date = $invoice_data['delivery_date'] ?? $invoice_data['removal_date'];
        $end_date = null;

        // Determine end_date based on service type and available duration
        if ($service_type == 'equipment_rental') {
            $duration_days = $invoice_data['duration_days'] ?? 7; // Default 7 days if not specified in quote_equipment_details
            if ($start_date) {
                $end_date = (new DateTime($start_date))->modify("+$duration_days days")->format('Y-m-d');
            }
        } elseif ($service_type == 'junk_removal') {
            $end_date = $start_date; // Junk removal is typically a single-day service
        }

        // Prepare JSON data for booking table
        $equipment_details_json = null;
        if ($service_type == 'equipment_rental') {
            $equipment_details = [
                [
                    'equipment_name' => $invoice_data['equipment_name'],
                    'quantity' => $invoice_data['equipment_quantity'],
                    'specific_needs' => $invoice_data['specific_needs'],
                    'duration_days' => $invoice_data['duration_days'] // Include duration here
                ]
            ];
            $equipment_details_json = json_encode($equipment_details);
        }

        $junk_details_json = null;
        if ($service_type == 'junk_removal') {
            $junk_details_array = [
                'junkItems' => json_decode($invoice_data['junk_items_json'] ?? '[]', true), // Ensure decoding with null check
                'recommendedDumpsterSize' => $invoice_data['recommended_dumpster_size'],
                'additionalComment' => $invoice_data['additional_comment'],
                'mediaUrls' => json_decode($invoice_data['media_urls_json'] ?? '[]', true) // Ensure decoding with null check
            ];
            $junk_details_json = json_encode($junk_details_array);
        }

        $live_load_requested = (int)($invoice_data['live_load_needed'] ?? 0);
        $is_urgent = (int)($invoice_data['is_urgent'] ?? 0);
        $driver_instructions = $invoice_data['delivery_instructions'] ?? null; // Renamed from q.driver_instructions in SELECT

        $stmt_create_booking = $conn->prepare("INSERT INTO bookings (
                                                invoice_id, user_id, booking_number, service_type,
                                                status, start_date, end_date, delivery_location,
                                                delivery_instructions, live_load_requested, is_urgent,
                                                total_price, equipment_details, junk_details
                                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $booking_status = 'scheduled'; // Initial status for a new booking
        $stmt_create_booking->bind_param("iisssssssiiis",
            $invoice_id, $user_id, $booking_number, $service_type, $booking_status,
            $start_date, $end_date, $delivery_location,
            $driver_instructions, $live_load_requested, $is_urgent,
            $amountToPay, $equipment_details_json, $junk_details_json
        );

        if (!$stmt_create_booking->execute()) {
            throw new Exception("Failed to create booking: " . $stmt_create_booking->error);
        }
        $booking_id = $stmt_create_booking->insert_id;
        $stmt_create_booking->close();
        error_log("Booking created with ID: " . $booking_id . " for Invoice ID: " . $invoice_id);

        // 6. Send Booking Confirmation Email to Customer
        // Ensure $_SESSION['user_email'], etc., are set during login
        $customerEmail = $_SESSION['user_email'] ?? '';
        $customerName = ($_SESSION['user_first_name'] ?? '') . ' ' . ($_SESSION['user_last_name'] ?? '');
        $companyName = getSystemSetting('company_name') ?? ($_ENV['COMPANY_NAME'] ?? 'Your Company');
        $dashboardLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/customer/?page=bookings&booking_id={$booking_id}";

        $emailSubject = "Your {$companyName} Booking #{$booking_number} is Confirmed!";
        $emailBody = "
            <p>Dear {$customerName},</p>
            <p>Your booking for <strong>" . ucwords(str_replace('_', ' ', $service_type)) . "</strong> (Booking #<strong>{$booking_number}</strong>) has been successfully confirmed and payment of $".number_format($amountToPay, 2)." received (Transaction ID: {$transaction_id}).</p>
            <p>Your service is scheduled for <strong>{$start_date}</strong>." . ($end_date && $start_date !== $end_date ? " End Date: <strong>{$end_date}</strong>." : "") . "</p>
            <p>You can view full details and track your order in your dashboard:</p>
            <p style='text-align: center; margin-top: 20px;'><a href=\"{$dashboardLink}\" style='display: inline-block; background-color: #007bff; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Your Booking</a></p>
            <p>Thank you for choosing {$companyName}!</p>
        ";
        $emailAltBody = "Dear {$customerName},\nYour booking for {$service_type} (Booking #{$booking_number}) has been successfully confirmed and payment of $".number_format($amountToPay, 2)." received.\nView details in your dashboard: {$dashboardLink}";

        if (!empty($customerEmail) && function_exists('sendEmail')) {
            sendEmail($customerEmail, $emailSubject, $emailBody, $emailAltBody);
            error_log("Booking confirmation email sent to: " . $customerEmail);
        } else {
             error_log("WARNING: Could not send booking confirmation email. Customer email empty or sendEmail function missing.");
        }


        // 7. Add Notification for Customer
        $stmt_add_notification = $conn->prepare("INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, ?, ?, ?, FALSE)");
        $notification_message = "Your booking #{$booking_number} for " . ucwords(str_replace('_', ' ', $service_type)) . " has been confirmed!";
        $notification_link = "bookings?booking_id={$booking_id}";
        $notification_type_booking = 'booking_confirmed'; // General type for confirmed bookings
        $stmt_add_notification->bind_param("isss", $user_id, $notification_type_booking, $notification_message, $notification_link);
        if (!$stmt_add_notification->execute()) {
             error_log("ERROR: Failed to add customer notification for confirmed booking: " . $stmt_add_notification->error);
        }
        $stmt_add_notification->close();

        // 8. Add Notification for Admin about new booking
        $first_admin_id = null;
        $stmt_get_admin = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $stmt_get_admin->execute();
        $result_admin = $stmt_get_admin->get_result();
        if ($row_admin = $result_admin->fetch_assoc()) {
            $first_admin_id = $row_admin['id'];
        }
        $stmt_get_admin->close();

        if ($first_admin_id) {
            $admin_notification_message = "New Booking #{$booking_number} created by {$customerName} for " . ucwords(str_replace('_', ' ', $service_type)) . ".";
            $admin_notification_link = "bookings?booking_id={$booking_id}";
            $stmt_admin_notification = $conn->prepare("INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, ?, ?, ?, FALSE)");
            $admin_notification_type = 'new_booking_admin';
            $stmt_admin_notification->bind_param("isss", $first_admin_id, $admin_notification_type, $admin_notification_message, $admin_notification_link);
            if (!$stmt_admin_notification->execute()) {
                error_log("ERROR: Failed to add admin notification for new booking: " . $stmt_admin_notification->error);
            }
            $stmt_admin_notification->close();
        } else {
            error_log("DEBUG: No admin user found to send new booking notification to.");
        }


        $conn->commit(); // Commit the transaction
        echo json_encode([
            'success' => true,
            'message' => 'Payment successful and booking confirmed!',
            'transaction_id' => $transaction_id,
            'booking_id' => $booking_id,
            'booking_number' => $booking_number
        ]);

    } catch (Braintree\Exception\NotFound $e) {
        $conn->rollback();
        error_log("Braintree NotFound exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Payment method not found in Braintree. Please try again or use a different method.']);
    } catch (Braintree\Exception\Validation $e) {
        $conn->rollback();
        $errorMessage = 'Payment validation failed.';
        if ($e->errorFor('transaction')) {
            foreach ($e->errorFor('transaction')->errors as $error) {
                $errorMessage .= " " . $error->message;
            }
        }
        error_log("Braintree Validation exception: " . $errorMessage);
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    } catch (Braintree\Exception\Authentication $e) {
        $conn->rollback();
        error_log("Braintree Authentication exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Braintree authentication error. Please contact support.']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Payment processing transaction failed for user ID $user_id: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        echo json_encode(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request or action.']);
}

$conn->close();
?>