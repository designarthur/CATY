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
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode JSON response from OpenAI. Raw response: " . $response);
    }
    if ($httpCode !== 200) {
        throw new Exception("OpenAI API Error (HTTP {$httpCode}): " . ($responseData['error']['message'] ?? 'Unknown Error'));
    }
    

    return $responseData;
}

// --- File Upload Handler (New Function) ---
function handleFileUploads(): array {
    $uploaded_urls = [];
    $uploadDir = __DIR__ . '/../uploads/junk_removal_media/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    if (!empty($_FILES['media_files']['name'][0])) {
        foreach ($_FILES['media_files']['name'] as $key => $name) {
            if ($_FILES['media_files']['error'][$key] == UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['media_files']['tmp_name'][$key];
                $fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $newFileName = uniqid('media_') . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $uploaded_urls[] = '/uploads/junk_removal_media/' . $newFileName;
                } else {
                    error_log("Failed to move uploaded file: " . $fileTmpPath);
                }
            } else {
                error_log("File upload error: " . $_FILES['media_files']['error'][$key]);
            }
        }
    }
    return $uploaded_urls;
}


// --- Main API Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMessageText = trim($_POST['message'] ?? '');
    $initialServiceType = $_POST['initial_service_type'] ?? null;
    $uploadedMediaUrls = [];

    // Handle file uploads if present
    if (isset($_FILES['media_files'])) {
        $uploadedMediaUrls = handleFileUploads();
    }

    if (empty($userMessageText) && empty($uploadedMediaUrls)) {
        echo json_encode(['success' => false, 'message' => 'Message or media cannot be empty.']);
        exit;
    }

    global $conn;
    $userId = $_SESSION['user_id'] ?? null;
    $conversationId = $_SESSION['conversation_id'] ?? null;

    if (!$conversationId) {
        $stmt_conv = $conn->prepare("INSERT INTO conversations (user_id, initial_service_type) VALUES (?, ?)");
        $stmt_conv->bind_param("is", $userId, $initialServiceType);
        $stmt_conv->execute();
        $conversationId = $conn->insert_id;
        $_SESSION['conversation_id'] = $conversationId;
        $stmt_conv->close();
    }

    $system_prompt = "You are a helpful and friendly AI assistant for {$companyName}. Your primary role is to gather all necessary information from the customer to create a detailed service quote. You must ask for the following details for any service: customer type (Residential or Commercial), customer name, customer email, customer phone, location, and service date.

For **equipment rental** requests, also ask for:
- Specific equipment items (e.g., 10-yard dumpster, temporary toilet, handwash station).
- Quantity for each item.
- Rental duration in days for each item.
- Preferred delivery time (e.g., morning, afternoon).
- Whether a live load is needed (boolean).
- If it's an urgent request (boolean).
- Any specific driver instructions for delivery.

For **junk removal** requests, also ask for:
- A description of the junk items. If images or videos are provided, you must analyze them to infer item types, estimated quantities, dimensions, and weights.
- Preferred removal date and time.
- Any additional comments or specific requests regarding the removal.
- Acknowledge and confirm uploaded media if provided.

**Crucially, you MUST call the `submit_quote_request` tool only when you have ALL the required information for the requested service type, including inferred details from images/videos for junk removal. If any required piece of information is missing, ask for it clearly and concisely. If media is uploaded for junk removal, use the Vision capabilities to identify items and their approximate characteristics.**";


    $messages = [['role' => 'system', 'content' => $system_prompt]];
    $stmt_fetch = $conn->prepare("SELECT role, content FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC");
    $stmt_fetch->bind_param("i", $conversationId);
    $stmt_fetch->execute();
    $result_messages = $stmt_fetch->get_result();
    while ($row = $result_messages->fetch_assoc()) {
        $messages[] = ['role' => $row['role'], 'content' => $row['content']];
    }
    $stmt_fetch->close();

    // Prepare message content for OpenAI, including image URLs if available
    $message_content = [['type' => 'text', 'text' => $userMessageText]];
    foreach ($uploadedMediaUrls as $url) {
        $full_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . $url;
        $message_content[] = ['type' => 'image_url', 'image_url' => ['url' => $full_url]];
    }
    $messages[] = ['role' => 'user', 'content' => $message_content];


    // --- AI Tool Definition (Updated for structured junk_items and media_urls) ---
    $tools = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'submit_quote_request',
                'description' => 'Submits the collected information to create a quote request. Requires all essential details for the service type.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_type' => ['type' => 'string', 'enum' => ['equipment_rental', 'junk_removal'], 'description' => 'The type of service requested.'],
                        'customer_type' => ['type' => 'string', 'enum' => ['Residential', 'Commercial'], 'description' => 'The type of customer.'],
                        'customer_name' => ['type' => 'string', 'description' => 'Full name of the customer.'],
                        'customer_email' => ['type' => 'string', 'description' => 'Email address of the customer.'],
                        'customer_phone' => ['type' => 'string', 'description' => 'Phone number of the customer.'],
                        'location' => ['type' => 'string', 'description' => 'Full address or detailed location for the service.'],
                        'service_date' => ['type' => 'string', 'description' => 'The preferred date for the service in YYYY-MM-DD format.'],
                        'service_time' => ['type' => 'string', 'description' => 'The preferred time for the service (e.g., "morning", "afternoon", "10:00 AM").'],
                        'is_urgent' => ['type' => 'boolean', 'description' => 'True if the request is urgent, false otherwise.'],
                        'live_load_needed' => ['type' => 'boolean', 'description' => 'True if a live load is needed for equipment rental, false otherwise.'],
                        'driver_instructions' => ['type' => 'string', 'description' => 'Any specific instructions for the driver.'],
                        'equipment_details' => [
                            'type' => 'array',
                            'description' => 'A list of all equipment items for an equipment rental request.',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'equipment_name' => ['type' => 'string', 'description' => 'The name and size of the equipment, e.g., "15-yard dumpster", "temporary toilet".'],
                                    'quantity' => ['type' => 'integer', 'description' => 'The number of units required for this specific item.'],
                                    'duration_days' => ['type' => 'integer', 'description' => 'The total number of days for the rental period for this item.'],
                                    'specific_needs' => ['type' => 'string', 'description' => 'Any other specific requirements or details for this equipment item.']
                                ],
                                'required' => ['equipment_name', 'quantity', 'duration_days']
                            ]
                        ],
                        'junk_details' => [
                            'type' => 'object',
                            'description' => 'Details for a junk removal request, including inferred items from media analysis.',
                             'properties' => [
                                'junk_items' => [
                                    'type' => 'array',
                                    'description' => 'List of junk items, inferred from description or uploaded media.',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'itemType' => ['type' => 'string', 'description' => 'Type of junk item, e.g., "Sofa", "Refrigerator", "Construction Debris".'],
                                            'quantity' => ['type' => 'integer', 'description' => 'Estimated quantity of the item.'],
                                            'estDimensions' => ['type' => 'string', 'description' => 'Estimated dimensions, e.g., "8x3x3 ft", "Large".'],
                                            'estWeight' => ['type' => 'string', 'description' => 'Estimated weight, e.g., "100kg", "Heavy".']
                                        ],
                                        'required' => ['itemType', 'quantity']
                                    ]
                                ],
                                'recommended_dumpster_size' => ['type' => 'string', 'description' => 'Recommended dumpster size if applicable, e.g., "20-yard".'],
                                'additional_comment' => ['type' => 'string', 'description' => 'Any additional comments or specific requests regarding the removal.'],
                                'media_urls' => [
                                    'type' => 'array',
                                    'description' => 'URLs of uploaded images or video frames for junk removal, if provided by the user.',
                                    'items' => ['type' => 'string']
                                ]
                            ],
                            'required' => ['junk_items']
                        ]
                    ],
                    'required' => ['service_type', 'customer_type', 'customer_name', 'customer_email', 'customer_phone', 'location', 'service_date', 'service_time']
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
                // Ensure customer_type is handled correctly, defaulting if not provided
                $customer_type = $arguments['customer_type'] ?? 'Residential';

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
                
                $stmt_quote->bind_param("isssssssiiss", $userId, $arguments['service_type'], $customer_type, $arguments['location'], $delivery_date, $removal_date, $delivery_time, $removal_time, $live_load, $is_urgent, $arguments['driver_instructions'], $details_json);
                $stmt_quote->execute();
                $quoteId = $conn->insert_id;
                $stmt_quote->close();

                if ($arguments['service_type'] === 'equipment_rental' && !empty($arguments['equipment_details'])) {
                    $stmt_eq = $conn->prepare("INSERT INTO quote_equipment_details (quote_id, equipment_name, quantity, duration_days, specific_needs) VALUES (?, ?, ?, ?, ?)");
                    foreach ($arguments['equipment_details'] as $item) {
                        $equipment_name = $item['equipment_name'] ?? 'N/A';
                        $quantity = $item['quantity'] ?? 1;
                        $duration_days = $item['duration_days'] ?? null;
                        $specific_needs = $item['specific_needs'] ?? null;
                        
                        $stmt_eq->bind_param("isiss", $quoteId, $equipment_name, $quantity, $duration_days, $specific_needs);
                        $stmt_eq->execute();
                    }
                    $stmt_eq->close();
                } elseif ($arguments['service_type'] === 'junk_removal' && !empty($arguments['junk_details'])) {
                    $stmt_junk = $conn->prepare("INSERT INTO junk_removal_details (quote_id, junk_items_json, recommended_dumpster_size, additional_comment, media_urls_json) VALUES (?, ?, ?, ?, ?)");
                    
                    // Ensure junk_items is properly encoded as JSON
                    $junk_items_json = json_encode($arguments['junk_details']['junk_items'] ?? []);
                    $recommended_dumpster_size = $arguments['junk_details']['recommended_dumpster_size'] ?? null;
                    $additional_comment = $arguments['junk_details']['additional_comment'] ?? null;
                    
                    // Store media URLs from the tool call (prioritize tool's URLs, fallback to directly uploaded)
                    $media_urls_json = json_encode($arguments['junk_details']['media_urls'] ?? $uploadedMediaUrls);

                    $stmt_junk->bind_param("issss", $quoteId, $junk_items_json, $recommended_dumpster_size, $additional_comment, $media_urls_json);
                    $stmt_junk->execute();
                    $stmt_junk->close();
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

    // Save user's message to chat history
    $stmt_save_user = $conn->prepare("INSERT INTO chat_messages (conversation_id, role, content) VALUES (?, 'user', ?)");
    $stmt_save_user->bind_param("is", $conversationId, $userMessageText);
    $stmt_save_user->execute();
    $stmt_save_user->close();
    
    // Save AI's response to chat history
    $stmt_save_ai = $conn->prepare("INSERT INTO chat_messages (conversation_id, role, content) VALUES (?, 'assistant', ?)");
    $stmt_save_ai->bind_param("is", $conversationId, $aiResponseText);
    $stmt_save_ai->execute();
    $stmt_save_ai->close();

    echo json_encode(['success' => true, 'ai_response' => trim($aiResponseText), 'is_info_collected' => $isInfoCollected]);
    
    exit;
}
?>