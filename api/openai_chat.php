<?php
// api/openai_chat.php

// --- Setup & Includes ---
ini_set('display_errors', 0);
error_reporting(E_ALL);
// It's recommended to set a central error log path in your php.ini
// ini_set('error_log', '/path/to/your/php_errors.log');

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php'; // Use for user session data

// Set a custom error handler for JSON responses
set_exception_handler(function ($exception) {
    error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Our team has been notified.']);
    exit;
});

header('Content-Type: application/json');

// --- Configuration ---
$openaiApiKey = $_ENV['OPENAI_API_KEY'] ?? '';
$companyName = getSystemSetting('company_name') ?? 'Catdump';
$aiModel = 'gpt-4o-mini';

// --- Functions ---

/**
 * Handles secure file uploads, validating type and size.
 *
 * @param string $base64Data The base64 encoded file data.
 * @param string $mimeType The MIME type of the file.
 * @return string The web-accessible path to the saved file.
 * @throws Exception If the file type is invalid or saving fails.
 */
function handle_file_upload($base64Data, $mimeType) {
    $uploadDir = __DIR__ . '/../../assets/uploads/junk_media/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];
    if (!in_array($mimeType, $allowedMimeTypes)) {
        throw new Exception("Invalid file type: {$mimeType}.");
    }

    $extension = explode('/', $mimeType)[1] ?? 'bin';
    $fileName = uniqid('media_') . '.' . $extension;
    $filePath = $uploadDir . $fileName;

    if (file_put_contents($filePath, base64_decode($base64Data)) === false) {
        throw new Exception('Failed to save uploaded file.');
    }

    // Return the web-accessible path
    return '/assets/uploads/junk_media/' . $fileName;
}


// --- Main API Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMessage = trim($_POST['message'] ?? '');
    $initialServiceType = $_POST['initial_service_type'] ?? null; // e.g., 'create-booking' from dashboard button

    $userId = $_SESSION['user_id'] ?? null;
    $conversationId = $_SESSION['conversation_id'] ?? null;

    $uploadedFilePaths = [];
    $imagePartsForAI = [];

    // Process file uploads securely
    try {
        foreach ($_POST as $key => $value) {
            if (str_starts_with($key, 'image_') && !str_ends_with($key, '_mime')) {
                $index = substr($key, strlen('image_'));
                $mimeType = $_POST["image_{$index}_mime"] ?? 'application/octet-stream';
                $uploadedFilePaths[] = handle_file_upload($value, $mimeType);
                $imagePartsForAI[] = ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64," . $value]];
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    if (empty($userMessage) && empty($uploadedFilePaths)) {
        echo json_encode(['ai_response' => 'Please type a message or upload an image.']);
        exit;
    }

    // --- Database-backed Conversation History ---
    global $conn;
    if (!$conversationId) {
        // Create a new conversation
        $stmt = $conn->prepare("INSERT INTO conversations (user_id, initial_service_type) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $initialServiceType);
        $stmt->execute();
        $conversationId = $conn->insert_id;
        $_SESSION['conversation_id'] = $conversationId;
        $stmt->close();
    }

    // Fetch existing messages for this conversation
    $messages = [];
    $stmt = $conn->prepare("SELECT role, content FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $conversationId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = ['role' => $row['role'], 'content' => $row['content']];
    }
    $stmt->close();


    // --- System Prompt & Tool Definition ---
    // This is a more robust way to get structured data from the AI
    $system_prompt = "You are a helpful and friendly AI assistant for {$companyName}. Your goal is to gather all necessary information from a customer to create a service quote. Your services include Equipment Rental and Junk Removal. Be conversational and guide the user. When you have collected ALL the necessary information for a specific service, you MUST call the `submit_quote_request` tool with the collected data. Do not call the tool until you have the customer's full name, email, phone number, and all service-specific details.";

    $tools = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'submit_quote_request',
                'description' => 'Submits the collected information to create a quote request in the system.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_type' => ['type' => 'string', 'enum' => ['equipment_rental', 'junk_removal']],
                        'customer_name' => ['type' => 'string'],
                        'customer_email' => ['type' => 'string'],
                        'customer_phone' => ['type' => 'string'],
                        'location' => ['type' => 'string'],
                        'service_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD format'],
                        'service_time' => ['type' => 'string', 'description' => 'e.g., Morning, Afternoon, 2pm-4pm'],
                        'is_urgent' => ['type' => 'boolean'],
                        'driver_instructions' => ['type' => 'string'],
                        'equipment_details' => [
                            'type' => 'object',
                            'properties' => [
                                'items' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'quantity' => ['type' => 'integer'],
                                            'duration_days' => ['type' => 'integer'],
                                            'specific_needs' => ['type' => 'string']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'junk_details' => [
                            'type' => 'object',
                            'properties' => [
                                'items' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'itemType' => ['type' => 'string'],
                                            'quantity' => ['type' => 'integer']
                                        ]
                                    ]
                                ],
                                'recommended_dumpster_size' => ['type' => 'string']
                            ]
                        ]
                    ],
                    'required' => ['service_type', 'customer_name', 'customer_email', 'customer_phone', 'location', 'service_date']
                ]
            ]
        ]
    ];


    // --- Construct and Send Request to OpenAI ---
    $currentMessageContent = [['type' => 'text', 'text' => $userMessage]];
    if (!empty($imagePartsForAI)) {
        $currentMessageContent = array_merge($currentMessageContent, $imagePartsForAI);
    }
    $messages[] = ['role' => 'user', 'content' => json_encode($currentMessageContent)]; // Store user message in DB

    $payload = [
        'model' => $aiModel,
        'messages' => array_merge([['role' => 'system', 'content' => $system_prompt]], $messages),
        'tools' => $tools,
        'tool_choice' => 'auto'
    ];

    // Get response from OpenAI API (This function needs to be created or adapted)
    $apiResponse = getOpenAIResponse($payload['messages'], $openaiApiKey, $aiModel); // Simplified for this example
    $responseMessage = $apiResponse['choices'][0]['message'];


    // --- Process AI Response ---
    $aiResponseText = "An unexpected error occurred.";
    if (isset($responseMessage['tool_calls'])) {
        // The AI wants to call our function
        $toolCall = $responseMessage['tool_calls'][0]['function'];
        $functionName = $toolCall['name'];
        $arguments = json_decode($toolCall['arguments'], true);

        if ($functionName === 'submit_quote_request') {
            // --- Execute the tool call: Save to Database ---
            $conn->begin_transaction();
            try {
                // Find or Create User
                // ... (logic from your original file to find/create user)
                $userId = 1; // Placeholder for found/created user ID

                // Create Quote
                $stmt = $conn->prepare("INSERT INTO quotes (user_id, service_type, ...) VALUES (?, ?, ...)");
                // ... (bind params and execute)
                $quoteId = $conn->insert_id;

                // Create Quote Details
                // ... (insert into quote_equipment_details or junk_removal_details)

                $conn->commit();
                $aiResponseText = "Thank you, {$arguments['customer_name']}! Your quote request (#{$quoteId}) has been submitted. Our team will send you the best price within the hour.";
                unset($_SESSION['conversation_id']); // End conversation
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Failed to process tool call: " . $e->getMessage());
                $aiResponseText = "I'm sorry, there was an error submitting your request. Please try again.";
            }
        }
    } else {
        // Standard conversational response
        $aiResponseText = $responseMessage['content'];
    }

    // Save chat history to DB
    $stmt = $conn->prepare("INSERT INTO chat_messages (conversation_id, role, content) VALUES (?, 'user', ?), (?, 'assistant', ?)");
    $userMessageJson = json_encode($currentMessageContent);
    $stmt->bind_param("isis", $conversationId, $userMessageJson, $conversationId, $aiResponseText);
    $stmt->execute();
    $stmt->close();


    // Return response to frontend
    echo json_encode([
        'success' => true,
        'ai_response' => $aiResponseText
    ]);

    $conn->close();
    exit;
}