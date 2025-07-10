<?php
// api/openai_chat.php - Handles AI Chat interactions and creates quote requests.

// --- Setup & Includes ---
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');
if (!file_exists(__DIR__ . '/../../logs')) {
    mkdir(__DIR__ . '/../../logs', 0775, true);
}


session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// --- Global Exception Handler for JSON Responses ---
set_exception_handler(function ($exception) {
    error_log("FATAL EXCEPTION in openai_chat.php: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Our team has been notified.']);
    exit;
});

header('Content-Type: application/json');

// --- Configuration & Pre-checks ---
$openaiApiKey = $_ENV['OPENAI_API_KEY'] ?? '';
if (empty($openaiApiKey)) {
    throw new Exception("OpenAI API key is not configured in the .env file.");
}
$companyName = getSystemSetting('company_name') ?? 'Catdump';
$aiModel = 'gpt-4o-mini';


// --- Re-usable getOpenAIResponse function ---
function getOpenAIResponse(array $messages, array $tools, string $apiKey, string $model): array {
    $url = "https://api.openai.com/v1/chat/completions";
    $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey];
    $payload = ['model' => $model, 'messages' => $messages, 'tools' => $tools, 'tool_choice' => 'auto'];

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 90]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) throw new Exception("cURL Error: " . $error);

    $responseData = json_decode($response, true);
    if ($httpCode !== 200) {
        throw new Exception("OpenAI API Error (HTTP {$httpCode}): " . ($responseData['error']['message'] ?? 'Unknown Error'));
    }
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode JSON response from OpenAI.");
    }

    return $responseData;
}


// --- Main API Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMessageText = trim($_POST['message'] ?? '');
    if (empty($userMessageText)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
        exit;
    }

    global $conn;
    $userId = $_SESSION['user_id'] ?? null;
    $conversationId = $_SESSION['conversation_id'] ?? null;

    if (!$conversationId) {
        $stmt_conv = $conn->prepare("INSERT INTO conversations (user_id) VALUES (?)");
        $stmt_conv->bind_param("i", $userId);
        $stmt_conv->execute();
        $conversationId = $conn->insert_id;
        $_SESSION['conversation_id'] = $conversationId;
        $stmt_conv->close();
    }

    $messages = [['role' => 'system', 'content' => "You are a helpful and friendly AI assistant for {$companyName}. Your goal is to gather all necessary information from a customer to create a service quote. Ask for customer type (Residential or Commercial) and rental duration. When you have ALL the required information (name, email, phone, location, service date, customer type, and all service-specific details), you MUST call the `submit_quote_request` tool."]];
    $stmt_fetch = $conn->prepare("SELECT role, content FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC");
    $stmt_fetch->bind_param("i", $conversationId);
    $stmt_fetch->execute();
    $result_messages = $stmt_fetch->get_result();
    while ($row = $result_messages->fetch_assoc()) {
        $messages[] = ['role' => $row['role'], 'content' => $row['content']];
    }
    $stmt_fetch->close();
    $messages[] = ['role' => 'user', 'content' => $userMessageText];

    // --- AI Tool Definition ---
    $tools = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'submit_quote_request',
                'description' => 'Submits the collected information to create a quote request.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_type' => ['type' => 'string', 'enum' => ['equipment_rental', 'junk_removal']],
                        'customer_type' => ['type' => 'string', 'enum' => ['Residential', 'Commercial'], 'description' => 'The type of customer.'],
                        'customer_name' => ['type' => 'string'],
                        'customer_email' => ['type' => 'string'],
                        'customer_phone' => ['type' => 'string'],
                        'location' => ['type' => 'string'],
                        'service_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'service_time' => ['type' => 'string'],
                        'is_urgent' => ['type' => 'boolean'],
                        'live_load_needed' => ['type' => 'boolean'],
                        'driver_instructions' => ['type' => 'string'],
                        'equipment_details' => [
                            'type' => 'object',
                            'description' => 'Details for an equipment rental request.',
                            'properties' => [
                                'equipment_name' => ['type' => 'string', 'description' => 'The name and size of the equipment, e.g., "15-yard dumpster".'],
                                'quantity' => ['type' => 'integer', 'description' => 'The number of units required.'],
                                'duration_days' => ['type' => 'integer', 'description' => 'The total number of days for the rental period.'],
                                'specific_needs' => ['type' => 'string', 'description' => 'Any other specific requirements or details from the customer.']
                            ]
                        ],
                        'junk_details' => [
                            'type' => 'object',
                            'description' => 'Details for a junk removal request.',
                             'properties' => [
                                'junk_items' => ['type' => 'array', 'items' => ['type' => 'string']],
                                'additional_comment' => ['type' => 'string']
                            ]
                        ]
                    ],
                    'required' => ['service_type', 'customer_name', 'customer_email', 'customer_phone', 'location', 'service_date', 'customer_type']
                ]
            ]
        ]
    ];
    
    // --- Call OpenAI API ---
    $apiResponse = getOpenAIResponse($messages, $tools, $openaiApiKey, $aiModel);
    $responseMessage = $apiResponse['choices'][0]['message'];
    $aiResponseText = $responseMessage['content'] ?? "I'm sorry, I'm having trouble processing that. Could you try rephrasing?";

    // --- Process AI Response ---
    $isInfoCollected = false;
    if (isset($responseMessage['tool_calls'])) {
        $toolCall = $responseMessage['tool_calls'][0]['function'];
        if ($toolCall['name'] === 'submit_quote_request') {
            $arguments = json_decode($toolCall['arguments'], true);

            $conn->begin_transaction();
            try {
                $stmt_user_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt_user_check->bind_param("s", $arguments['customer_email']);
                $stmt_user_check->execute();
                $user_result = $stmt_user_check->get_result();
                if ($user_result->num_rows > 0) {
                    $userId = $user_result->fetch_assoc()['id'];
                } else {
                    $name_parts = explode(' ', $arguments['customer_name'], 2);
                    $firstName = $name_parts[0];
                    $lastName = $name_parts[1] ?? '';
                    $temp_password = generateToken(8);
                    $hashed_password = hashPassword($temp_password);
                    $stmt_create_user = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, password, role) VALUES (?, ?, ?, ?, ?, 'customer')");
                    $stmt_create_user->bind_param("sssss", $firstName, $lastName, $arguments['customer_email'], $arguments['customer_phone'], $hashed_password);
                    $stmt_create_user->execute();
                    $userId = $conn->insert_id;
                    $stmt_create_user->close();
                }
                $stmt_user_check->close();

                $stmt_quote = $conn->prepare("INSERT INTO quotes (user_id, service_type, customer_type, location, delivery_date, removal_date, delivery_time, removal_time, live_load_needed, is_urgent, driver_instructions, quote_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $details_json = json_encode($arguments);
                $delivery_date = $arguments['service_type'] === 'equipment_rental' ? ($arguments['service_date'] ?? null) : null;
                $removal_date = $arguments['service_type'] === 'junk_removal' ? ($arguments['service_date'] ?? null) : null;
                $delivery_time = $arguments['service_type'] === 'equipment_rental' ? ($arguments['service_time'] ?? null) : null;
                $removal_time = $arguments['service_type'] === 'junk_removal' ? ($arguments['service_time'] ?? null) : null;
                $live_load = (int)($arguments['live_load_needed'] ?? 0);
                $is_urgent = (int)($arguments['is_urgent'] ?? 0);
                $customer_type = $arguments['customer_type'] ?? 'Residential'; // Default to Residential

                $stmt_quote->bind_param("isssssssiiss", $userId, $arguments['service_type'], $customer_type, $arguments['location'], $delivery_date, $removal_date, $delivery_time, $removal_time, $live_load, $is_urgent, $arguments['driver_instructions'], $details_json);
                $stmt_quote->execute();
                $quoteId = $conn->insert_id;
                $stmt_quote->close();

                if ($arguments['service_type'] === 'equipment_rental' && !empty($arguments['equipment_details'])) {
                    $stmt_eq = $conn->prepare("INSERT INTO quote_equipment_details (quote_id, equipment_name, quantity, duration_days, specific_needs) VALUES (?, ?, ?, ?, ?)");
                    $eq_details = $arguments['equipment_details'];
                    // Use null coalescing operator to provide defaults
                    $equipment_name = $eq_details['equipment_name'] ?? 'N/A';
                    $quantity = $eq_details['quantity'] ?? 1;
                    $duration_days = $eq_details['duration_days'] ?? null;
                    $specific_needs = $eq_details['specific_needs'] ?? null;
                    
                    $stmt_eq->bind_param("isiss", $quoteId, $equipment_name, $quantity, $duration_days, $specific_needs);
                    $stmt_eq->execute();
                    $stmt_eq->close();
                }

                $conn->commit();
                $aiResponseText = "Thank you! Your quote request (#Q{$quoteId}) has been successfully submitted. Our team will review the details and send you the best price within the hour.";
                $isInfoCollected = true;
                unset($_SESSION['conversation_id']);
                
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                error_log("SQL Error during quote creation: " . $e->getMessage() . " - Arguments: " . json_encode($arguments));
                throw new Exception("Failed to save your request to the database due to a data error.");
            }
        }
    }

    $stmt_save_user = $conn->prepare("INSERT INTO chat_messages (conversation_id, role, content) VALUES (?, 'user', ?)");
    $stmt_save_user->bind_param("is", $conversationId, $userMessageText);
    $stmt_save_user->execute();
    $stmt_save_user->close();
    
    $stmt_save_ai = $conn->prepare("INSERT INTO chat_messages (conversation_id, role, content) VALUES (?, 'assistant', ?)");
    $stmt_save_ai->bind_param("is", $conversationId, $aiResponseText);
    $stmt_save_ai->execute();
    $stmt_save_ai->close();

    echo json_encode(['success' => true, 'ai_response' => trim($aiResponseText), 'is_info_collected' => $isInfoCollected]);
    
    exit;
}
?>