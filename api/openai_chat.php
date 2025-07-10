<?php
// api/openai_chat.php - Handles AI Chat interactions and backend data processing

session_start(); // Start PHP session to maintain chat history

// Enable error reporting for debugging (REMOVE IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// DIRECTORY FOR ERROR LOGS
// Make sure this directory exists and is writable by your web server
$log_file = __DIR__ . '/../../logs/openai_chat_errors.log';
ini_set('error_log', $log_file);
error_log("DEBUG: --- SCRIPT START: " . date('Y-m-d H:i:s') . " ---");

// Include necessary files
require_once __DIR__ . '/../includes/db.php'; // Database connection
error_log("DEBUG: db.php included.");
require_once __DIR__ . '/../includes/functions.php'; // Utility functions (email, hashing, system settings)
error_log("DEBUG: functions.php included.");

// Clear chat history on fresh page load (though aibookingchat.php also does this)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['reset_chat'])) {
    unset($_SESSION['chat_history']);
    error_log("DEBUG: Chat history cleared via reset parameter.");
}

// --- Configuration (fetched dynamically from .env and database) ---
$openaiApiKey = $_ENV['OPENAI_API_KEY'] ?? ''; // Fallback for safety
error_log("DEBUG: OpenAI API Key fetched (first few chars): " . substr($openaiApiKey, 0, 5) . "...");
$adminEmail = getSystemSetting('admin_email') ?? ($_ENV['ADMIN_EMAIL_RECIPIENT'] ?? 'admin@example.com'); // Fallback to .env if DB fails
$companyName = getSystemSetting('company_name') ?? ($_ENV['COMPANY_NAME'] ?? 'Catdump'); // Fallback to .env if DB fails
$aiModel = 'gpt-4o-mini'; // Can be fetched from system_settings later if needed
error_log("DEBUG: Configuration loaded. Company: {$companyName}, Admin Email: {$adminEmail}, AI Model: {$aiModel}");

// --- Functions ---

/**
 * Sends a chat completion request to the OpenAI API and returns the AI's response.
 * @param array $messages An array of message objects.
 * @param string $apiKey Your OpenAI API key.
 * @param string $model The name of the AI model to use.
 * @return string The AI's raw generated response content (either plain text or JSON string).
 */
function getOpenAIResponse($messages, $apiKey, $model) {
    $url = "https://api.openai.com/v1/chat/completions";
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ];

    $payload = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 1500, // Increased for more comprehensive JSON outputs
        'response_format' => ['type' => 'text'] // Default to text. AI should explicitly output JSON in content when needed.
    ];

    error_log("DEBUG: OpenAI Request Payload: " . json_encode($payload));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    error_log("DEBUG: OpenAI Raw Response: " . $response);
    error_log("DEBUG: OpenAI HTTP Code: " . $httpCode);

    if ($response === false) {
        error_log("ERROR: cURL error: " . $error);
        return "Error: Could not connect to OpenAI API. Please check your network or API key. cURL error: " . $error;
    }

    if ($httpCode !== 200) {
        error_log("ERROR: OpenAI API error (HTTP $httpCode): " . $response);
        $responseData = json_decode($response, true);
        $errorMessage = $responseData['error']['message'] ?? 'Unknown API error.';
        return "Error: OpenAI API returned an error (HTTP $httpCode): " . $errorMessage;
    }

    $responseData = json_decode($response, true);
    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    } else {
        error_log("ERROR: Unexpected OpenAI API response structure: " . $response);
        return "Error: Unexpected response structure from OpenAI API. Please try again.";
    }
}

// --- API Endpoint Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    global $conn; // Access the database connection

    $userMessage = trim($_POST['message'] ?? '');
    $hasFiles = false; // Flag to check if any files were sent

    $imageParts = [];
    $uploadedFilePaths = []; // To store paths of saved files for junk removal

    // Directory for uploads (ensure this directory exists and is writable)
    $uploadDir = __DIR__ . '/../../assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Create with full permissions if it doesn't exist
    }

    error_log("DEBUG: User message: " . $userMessage);

    // Process uploaded video frames and images
    foreach ($_POST as $key => $value) {
        if (str_starts_with($key, 'video_frame_')) {
            $base64Data = $value;
            $fileName = uniqid('video_frame_') . '.jpeg';
            $filePath = $uploadDir . $fileName;
            if (file_put_contents($filePath, base64_decode($base64Data)) !== false) {
                $uploadedFilePaths[] = '/assets/uploads/' . $fileName; // Store web-accessible path
                $imageParts[] = [
                    'type' => 'image_url',
                    'image_url' => ['url' => 'data:image/jpeg;base64,' . $value]
                ];
                $hasFiles = true;
                error_log("DEBUG: Detected and saved video frame: " . $fileName);
            } else {
                error_log("ERROR: Failed to save video frame: " . $fileName);
            }
        }
        if (str_starts_with($key, 'image_') && !str_ends_with($key, '_mime')) {
            $index = substr($key, strlen('image_'));
            $mimeType = $_POST["image_{$index}_mime"] ?? 'image/jpeg';
            $base64Data = $value;
            $extension = explode('/', $mimeType)[1] ?? 'jpeg';
            $fileName = uniqid('image_') . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            if (file_put_contents($filePath, base64_decode($base64Data)) !== false) {
                $uploadedFilePaths[] = '/assets/uploads/' . $fileName; // Store web-accessible path
                $imageParts[] = [
                    'type' => 'image_url',
                    'image_url' => ['url' => "data:{$mimeType};base64," . $value]
                ];
                $hasFiles = true;
                error_log("DEBUG: Detected and saved image: " . $fileName);
            } else {
                error_log("ERROR: Failed to save image: " . $fileName);
            }
        }
    }
    error_log("DEBUG: Total uploaded files: " . count($uploadedFilePaths));

    $currentUserMessageParts = [];
    if (!empty($userMessage)) {
        $currentUserMessageParts[] = ['type' => 'text', 'text' => $userMessage];
    }
    $currentUserMessageParts = array_merge($currentUserMessageParts, $imageParts);

    if (empty($userMessage) && !$hasFiles) {
        echo json_encode(['ai_response' => 'Please type a message or upload a file.']);
        exit;
    }

    // Initialize chat history and system message
    if (!isset($_SESSION['chat_history']) || empty($_SESSION['chat_history'])) {
        error_log("DEBUG: Initializing new chat history.");
        $_SESSION['chat_history'] = [];
        $_SESSION['chat_history'][] = [
            'role' => 'system',
            'content' => [
                ['type' => 'text', 'text' => <<<EOT
You are an AI assistant for {$companyName}, an equipment rental platform. Your services include both equipment rentals (Dumpsters, Temporary toilets, Storage containers, Handwash stations) and junk removal services. Your goal is to gather detailed information from customers. Be polite, friendly, and convincing to ensure they feel confident in placing an order and using our services. Act as a helpful sales assistant.

For conversational responses (when you are still gathering information), simply provide your textual message directly. DO NOT wrap these conversational responses in JSON.

For EQUIPMENT RENTAL inquiries, ensure you collect:
1) Residential or Commercial need.
2) Specific equipment types and details (e.g., size, quantity, duration).
3) Location for service.
4) Preferred delivery date (YYYY-MM-DD format).
5) If they need "live load" service (where the driver waits while they load the dumpster/equipment) - Yes/No.
6) If the need is urgent - Yes/No.
7) Suitable delivery time (e.g., morning, afternoon, specific window).
8) Any placement instructions for the driver or other delivery notes.

For JUNK REMOVAL inquiries, you will be provided with images or video frames. Based on these, you MUST:
1) List all visible junk items with their 'itemType' (e.g., 'furniture', 'electronics', 'yard waste'), 'quantity' (integer), 'estDimensions' (e.g., '3x2x1 ft', 'small', 'large'), and 'estWeight' (e.g., '50 lbs', 'heavy').
2) Recommend a suitable dumpster size (e.g., '10-yard', '20-yard', '30-yard') based on the estimated volume and weight of the junk.
3) Ask the user if they want to include more items in the list, or remove any item, or if there are other items not shown.
4) Then ask for the service 'location' for junk removal.
5) Preferred removal date (YYYY-MM-DD format).
6) If they need "live load" service for junk removal - Yes/No.
7) If the removal is urgent - Yes/No.
8) Suitable removal time (e.g., morning, afternoon, specific window).
9) Any placement instructions or access notes for the driver.

Finally, for BOTH services, you need the Customer's full name, valid email address, AND a valid phone number. Once you have collected all of the above information (full name, valid email, valid phone number, location, and all relevant service-specific details like dates/times/instructions), you MUST generate a JSON object containing the overall summary details for our internal team email. This JSON object should ONLY include a 'customer_message' field for the customer-facing message. This 'customer_message' field should contain the human-readable summary of the request, including a polite closing, the 1-hour quote timeframe, and information about the account dashboard. DO NOT include the full JSON structure in the 'customer_message' itself.

Example JSON structure for your FINAL summary JSON response after collecting ALL info:
If only equipment rental:
```json
{
  "type": "equipmentRental",
  "name": "[Customer Name]",
  "email": "[Customer Email]",
  "phoneNumber": "[Customer Phone Number]",
  "customer_type": "[Residential/Commercial]",
  "equipment_types": ["[Equipment 1]", "[Equipment 2]"],
  "specific_needs": "[Detailed needs]",
  "location": "[Service Location]",
  "delivery_date": "[YYYY-MM-DD]",
  "live_load_needed": [true/false],
  "is_urgent": [true/false],
  "delivery_time": "[Morning/Afternoon/Specific Time]",
  "driver_instructions": "[Any specific instructions for driver]",
  "customer_message": "Thank you for your order, [Customer Name]! We have noted down your details. You will receive your personalized quote within a maximum of 1 hour as we search for the best price in your area. Your account has been created, and you will be able to view your quote in your account dashboard as soon as it's ready. If you have any further questions or need assistance, feel free to ask."
}
If junk removal:

JSON

{
  "type": "junkRemoval",
  "name": "[Customer Name]",
  "email": "[Customer Email]",
  "phoneNumber": "[Customer Phone Number]",
  "location": "[Service Location]",
  "removal_date": "[YYYY-MM-DD]",
  "live_load_needed": [true/false],
  "is_urgent": [true/false],
  "removal_time": "[Morning/Afternoon/Specific Time]",
  "driver_instructions": "[Any specific instructions for driver]",
  "junkRemovalDetails": {
    "junkItems": [
      {"itemType": "[type]", "quantity": [qty], "estDimensions": "[dims]", "estWeight": "[weight]"},
      // ... more items
    ],
    "recommendedDumpsterSize": "[size]",
    "additionalComment": "[Optional comment]"
  },
  "customer_message": "Thank you for your request, [Customer Name]! We have received your junk removal details. You will receive your personalized quote within a maximum of 1 hour as we search for the best price in your area. Your account has been created, and you will be able to view your quote in your account dashboard as soon as it's ready. If you have any further questions or need assistance, feel free to ask."
}
If both, combine relevant fields. When you generate the final summary JSON, ENSURE that the 'customer_message' field contains ONLY the human-readable text for the customer, without any nested JSON or additional formatting characters from your internal summary. The full JSON structure should be the ONLY thing returned by the API call, and the 'customer_message' field is the ONLY part of that JSON that should be shown to the user.
EOT
]
]
];
    }

    // Add current user message to history
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $currentUserMessageParts];
    error_log("DEBUG: Current chat history length: " . count($_SESSION['chat_history']));

    // Get AI response
    $aiRawResponse = getOpenAIResponse($_SESSION['chat_history'], $openaiApiKey, $aiModel);
    error_log("DEBUG: AI raw response received: " . $aiRawResponse);

    // Attempt to extract JSON from the AI's response string
    $jsonPattern = '/```json\s*(.*?)\s*```/s';
    $extractedJsonString = '';
    if (preg_match($jsonPattern, $aiRawResponse, $matches)) {
        $extractedJsonString = trim($matches[1]);
        error_log("DEBUG: Extracted JSON String: " . $extractedJsonString);
    } else {
        error_log("DEBUG: No JSON block found in AI raw response. Trying direct decode.");
    }

    $aiResponseData = null;
    $isInfoCollected = false; // Flag to indicate if full info was collected and processed
    $displayMessage = ''; // Message for frontend
    $structuredData = null; // To pass the full structured JSON to frontend if available

    if (!empty($extractedJsonString)) {
        $aiResponseData = json_decode($extractedJsonString, true);
        error_log("DEBUG: JSON Decode result from extracted string. Error: " . json_last_error_msg());
    } else {
        // Fallback: try to decode the whole raw response if no code block was found
        $aiResponseData = json_decode($aiRawResponse, true);
        error_log("DEBUG: JSON Decode result from raw response. Error: " . json_last_error_msg());
    }

    if (json_last_error() === JSON_ERROR_NONE && isset($aiResponseData['type'])) {
        // AI returned a structured summary JSON
        error_log("DEBUG: AI response detected as structured summary. Type: " . ($aiResponseData['type'] ?? 'N/A'));
        $structuredData = $aiResponseData; // Store the full structured data

        // Extract customer contact info
        $customerName = $structuredData['name'] ?? null;
        $customerEmail = $structuredData['email'] ?? null;
        $customerPhoneNumber = $structuredData['phoneNumber'] ?? null;
        $customerLocation = $structuredData['location'] ?? null;

        error_log("DEBUG: Extracted: Name: {$customerName}, Email: {$customerEmail}, Phone: {$customerPhoneNumber}, Location: {$customerLocation}");

        // Check if all critical contact info is present
        $requiredContactInfoPresent = !empty($customerName) &&
                                      !empty($customerEmail) &&
                                      !empty($customerPhoneNumber) &&
                                      !empty($customerLocation);
        error_log("DEBUG: Required contact info present: " . ($requiredContactInfoPresent ? 'Yes' : 'No'));

        $requiredServiceDetailsPresent = false;
        if (isset($structuredData['type'])) {
            if ($structuredData['type'] === 'equipmentRental') {
                $requiredServiceDetailsPresent = !empty($structuredData['equipment_types']) &&
                                                 !empty($structuredData['delivery_date']) &&
                                                 !empty($structuredData['delivery_time']);
                error_log("DEBUG: Equipment rental details present: " . ($requiredServiceDetailsPresent ? 'Yes' : 'No') . " - Equipment: " . json_encode($structuredData['equipment_types'] ?? []) . ", Date: " . ($structuredData['delivery_date'] ?? 'N/A'));
            } elseif ($structuredData['type'] === 'junkRemoval') {
                $requiredServiceDetailsPresent = !empty($structuredData['junkRemovalDetails']['junkItems']) &&
                                                 !empty($structuredData['removal_date']) &&
                                                 !empty($structuredData['removal_time']);
                error_log("DEBUG: Junk removal details present: " . ($requiredServiceDetailsPresent ? 'Yes' : 'No') . " - Items: " . json_encode($structuredData['junkRemovalDetails']['junkItems'] ?? []) . ", Date: " . ($structuredData['removal_date'] ?? 'N/A'));
            }
        }
        error_log("DEBUG: Required service details present: " . ($requiredServiceDetailsPresent ? 'Yes' : 'No'));


        if ($requiredContactInfoPresent && $requiredServiceDetailsPresent) {
            error_log("DEBUG: All required contact info and service details found. Initiating database transaction...");

            $conn->begin_transaction(); // Start transaction for atomicity
            error_log("DEBUG: Transaction started.");

            try {
                // 1. Find or Create User Account
                $user_id = null;
                $stmt_check_user = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
                $stmt_check_user->bind_param("s", $customerEmail);
                $stmt_check_user->execute();
                $result_user = $stmt_check_user->get_result();
                error_log("DEBUG: User check query executed. Rows: " . $result_user->num_rows);

                if ($result_user->num_rows > 0) {
                    $existing_user = $result_user->fetch_assoc();
                    $user_id = $existing_user['id'];
                    error_log("DEBUG: Existing user found with ID: " . $user_id);
                } else {
                    // Create new user account
                    error_log("DEBUG: No existing user found. Attempting to create new user.");
                    $temp_password = generateToken(8); // Generate a temporary password
                    $hashed_password = hashPassword($temp_password);

                    $parts = explode(' ', $customerName);
                    $firstName = array_shift($parts);
                    $lastName = implode(' ', $parts);

                    // Ensure all required fields are passed to the INSERT statement
                    $stmt_insert_user = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, password, role, address, city, state, zip_code) VALUES (?, ?, ?, ?, ?, 'customer', ?, ?, ?, ?)");
                    
                    $dummyAddress = $structuredData['location'] ?? 'N/A';
                    $dummyCity = 'N/A';
                    $dummyState = 'N/A';
                    $dummyZip = 'N/A';

                    $location_str = $structuredData['location'] ?? '';
                    // More robust address parsing could be implemented here if AI output is consistent
                    // For now, a simple heuristic or defaults
                    if (preg_match('/^(.*?),\s*(.*?),\s*(\S+)\s*(\S+)$/', $location_str, $address_matches)) {
                        $dummyAddress = $address_matches[1];
                        $dummyCity = $address_matches[2];
                        $dummyState = $address_matches[3];
                        $dummyZip = $address_matches[4];
                    } else if (strpos($location_str, 'Texas') !== false && strpos($location_str, 'Dallas') !== false) {
                        $dummyAddress = 'Dallas, Texas'; 
                        $dummyCity = 'Dallas';
                        $dummyState = 'TX';
                        $dummyZip = '7110'; 
                    } else {
                        // Fallback: If no structured address, try to use location as address and default others
                        $dummyAddress = $location_str;
                        // Attempt to extract city/state if common patterns exist
                        if (preg_match('/(?:,\s*([A-Za-z\s]+?))\s*,\s*([A-Z]{2})$/', $location_str, $matches)) {
                            $dummyCity = trim($matches[1]);
                            $dummyState = trim($matches[2]);
                        }
                    }

                    $stmt_insert_user->bind_param("sssssssss", $firstName, $lastName, $customerEmail, $customerPhoneNumber, $hashed_password, $dummyAddress, $dummyCity, $dummyState, $dummyZip);

                    if ($stmt_insert_user->execute()) {
                        $user_id = $stmt_insert_user->insert_id;
                        error_log("DEBUG: New user created with ID: " . $user_id);
                        // No email sending for account creation as per request
                    } else {
                        throw new Exception("Failed to create user account: " . $stmt_insert_user->error);
                    }
                    $stmt_insert_user->close();
                }

                // 2. Create Quote Entry
                error_log("DEBUG: Attempting to create quote entry for user ID: " . $user_id);
                $service_type = 'equipment_rental'; // Default service type
                if (isset($structuredData['type'])) {
                    if ($structuredData['type'] === 'equipmentRental') {
                        $service_type = 'equipment_rental';
                    } elseif ($structuredData['type'] === 'junkRemoval') {
                        $service_type = 'junk_removal';
                    } else {
                        error_log("ERROR: AI provided unknown service type: " . $structuredData['type']);
                    }
                }
                
                $customer_type = $structuredData['customer_type'] ?? null;
                $delivery_date_val = $structuredData['delivery_date'] ?? null;
                $delivery_time_val = $structuredData['delivery_time'] ?? null;
                $removal_date_val = $structuredData['removal_date'] ?? null;
                $removal_time_val = $structuredData['removal_time'] ?? null;

                $live_load_needed = filter_var($structuredData['live_load_needed'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $is_urgent = filter_var($structuredData['is_urgent'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $driver_instructions = $structuredData['driver_instructions'] ?? null;
                $quoteDetailsJson = json_encode($structuredData);

                $stmt_insert_quote = $conn->prepare("INSERT INTO quotes (user_id, service_type, status, customer_type, location, delivery_date, delivery_time, removal_date, removal_time, live_load_needed, is_urgent, driver_instructions, quote_details) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $bind_customer_type = $customer_type;
                $bind_location = $customerLocation;
                $bind_delivery_date = $delivery_date_val;
                $bind_delivery_time = $delivery_time_val;
                $bind_removal_date = $removal_date_val;
                $bind_removal_time = $removal_time_val;
                $bind_live_load_needed = (int)$live_load_needed;
                $bind_is_urgent = (int)$is_urgent;
                $bind_driver_instructions = $driver_instructions;
                $bind_quoteDetailsJson = $quoteDetailsJson;

                error_log("DEBUG: Final \$service_type value for bind_param: '" . $service_type . "' (len: " . strlen($service_type) . ")");
                error_log("DEBUG: Binding values for quotes table:");
                error_log("DEBUG:   user_id: {$user_id}");
                error_log("DEBUG:   service_type: '{$service_type}'");
                error_log("DEBUG:   customer_type: '" . ($bind_customer_type ?? 'NULL') . "'");
                error_log("DEBUG:   location: '" . ($bind_location ?? 'NULL') . "'");
                error_log("DEBUG:   delivery_date: '" . ($bind_delivery_date ?? 'NULL') . "'");
                error_log("DEBUG:   delivery_time: '" . ($bind_delivery_time ?? 'NULL') . "'");
                error_log("DEBUG:   removal_date: '" . ($bind_removal_date ?? 'NULL') . "'");
                error_log("DEBUG:   removal_time: '" . ($bind_removal_time ?? 'NULL') . "'");
                error_log("DEBUG:   live_load_needed: {$bind_live_load_needed}");
                error_log("DEBUG:   is_urgent: {$bind_is_urgent}");
                error_log("DEBUG:   driver_instructions: '" . ($bind_driver_instructions ?? 'NULL') . "'");
                error_log("DEBUG:   quote_details_json: (truncated for log) " . substr($bind_quoteDetailsJson, 0, 100));


                $stmt_insert_quote->bind_param("isssssssiiss",
                    $user_id, $service_type, $bind_customer_type, $bind_location,
                    $bind_delivery_date, $bind_delivery_time, $bind_removal_date, $bind_removal_time,
                    $bind_live_load_needed, $bind_is_urgent, $bind_driver_instructions, $bind_quoteDetailsJson
                );

                if (!$stmt_insert_quote->execute()) {
                    throw new Exception("Failed to create quote: " . $stmt_insert_quote->error);
                }
                $quote_id = $stmt_insert_quote->insert_id;
                error_log("DEBUG: Quote created with ID: " . $quote_id);
                $stmt_insert_quote->close();

                // 3. Store Service-Specific Details
                if ($service_type === 'equipment_rental' && !empty($structuredData['equipment_types'])) {
                    error_log("DEBUG: Storing equipment rental details.");
                    foreach ($structuredData['equipment_types'] as $eq_type) {
                        $stmt_insert_eq = $conn->prepare("INSERT INTO quote_equipment_details (quote_id, equipment_name, quantity, specific_needs) VALUES (?, ?, ?, ?)");
                        
                        if ($stmt_insert_eq === false) {
                            error_log("ERROR: Failed to prepare statement for quote_equipment_details: " . $conn->error);
                            continue;
                        }

                        $bind_quote_id = $quote_id;
                        $bind_eq_name = trim($eq_type);
                        $bind_quantity = $eq_type['quantity'] ?? 1; // Assuming quantity might be in AI response for each item
                        $specific_needs_data = $structuredData['specific_needs'] ?? null;
                        $bind_specific_needs = is_string($specific_needs_data) ? trim($specific_needs_data) : $specific_needs_data;

                        $stmt_insert_eq->bind_param(
                            "isis",
                            $bind_quote_id,
                            $bind_eq_name,
                            $bind_quantity,
                            $bind_specific_needs
                        );

                        if (!$stmt_insert_eq->execute()) {
                            error_log("ERROR: Failed to insert equipment details: " . $stmt_insert_eq->error);
                        }
                        $stmt_insert_eq->close();
                    }
                } elseif ($service_type === 'junk_removal' && !empty($structuredData['junkRemovalDetails'])) {
                    error_log("DEBUG: Storing junk removal details.");
                    $junkDetails = $structuredData['junkRemovalDetails'];
                    $junkItemsJson = json_encode($junkDetails['junkItems'] ?? []);
                    $recommendedDumpsterSize = $junkDetails['recommendedDumpsterSize'] ?? null;
                    $additionalComment = $junkDetails['additionalComment'] ?? null;
                    $mediaUrlsJson = json_encode($uploadedFilePaths); 

                    $stmt_insert_junk = $conn->prepare("INSERT INTO junk_removal_details (quote_id, junk_items_json, recommended_dumpster_size, additional_comment, media_urls_json) VALUES (?, ?, ?, ?, ?)");
                    $stmt_insert_junk->bind_param("issss", $quote_id, $junkItemsJson, $recommendedDumpsterSize, $additionalComment, $mediaUrlsJson);
                    if (!$stmt_insert_junk->execute()) {
                        throw new Exception("Failed to insert junk removal details: " . $stmt_insert_junk->error);
                    }
                    $junk_detail_id = $stmt_insert_junk->insert_id;
                    $stmt_insert_junk->close();

                    // Store individual media records if uploadedFilePaths contains data
                    foreach ($uploadedFilePaths as $path) {
                        $calculated_file_type = 'application/octet-stream';
                        if (function_exists('mime_content_type') && file_exists($uploadDir . basename($path))) {
                            $detected_mime_type = mime_content_type($uploadDir . basename($path));
                            if ($detected_mime_type !== false) {
                                $calculated_file_type = $detected_mime_type;
                            }
                        } else {
                            $extension = pathinfo($path, PATHINFO_EXTENSION);
                            switch (strtolower($extension)) {
                                case 'jpg': case 'jpeg': $calculated_file_type = 'image/jpeg'; break;
                                case 'png': $calculated_file_type = 'image/png'; break;
                                case 'gif': $calculated_file_type = 'image/gif'; break;
                                case 'mp4': $calculated_file_type = 'video/mp4'; break;
                                case 'mov': $calculated_file_type = 'video/quicktime'; break;
                                default: break;
                            }
                        }
                        $final_file_type_for_bind = (string)$calculated_file_type;
                        if (empty($final_file_type_for_bind)) {
                           $final_file_type_for_bind = 'application/octet-stream';
                        }

                        $stmt_insert_media = $conn->prepare("INSERT INTO junk_removal_media (junk_removal_detail_id, file_path, file_type) VALUES (?, ?, ?)");
                        $stmt_insert_media->bind_param("iss", $junk_detail_id, $path, $final_file_type_for_bind);
                        if (!$stmt_insert_media->execute()) {
                            error_log("ERROR: Failed to insert junk media record for path: " . $path . ", error: " . $stmt_insert_media->error);
                        }
                        $stmt_insert_media->close();
                    }
                }

                // No admin email or admin notification as per request

                $conn->commit(); // Commit transaction
                error_log("DEBUG: Transaction committed for quote ID: " . $quote_id);
                $isInfoCollected = true; // Mark as successful
                $displayMessage = $structuredData['customer_message']; // Use AI's suggested customer message
                // Clear chat history after successful collection to allow a fresh start or new inquiry
                unset($_SESSION['chat_history']);

            } catch (Exception $e) {
                $conn->rollback(); // Rollback on error
                error_log("ERROR: Transaction failed: " . $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getFile());
                $displayMessage = "Thank you for your inquiry. There was an issue processing your request on our end. Our team has been notified and will reach out to you shortly. Error: " . $e->getMessage();
                $isInfoCollected = false; // Indicate failure to process
                // Do NOT clear chat history if there was an error in processing the final request
            }
        } else {
            error_log("DEBUG: Required contact or service details NOT present. Not processing structured data fully. AI Response: " . $aiRawResponse);
            $displayMessage = $aiResponseData['customer_message'] ?? $aiRawResponse; // Use AI's message if partially structured, else raw
        }

    } else {
        // AI response is not a structured summary, treat as conversational text.
        error_log("DEBUG: AI response detected as conversational text (or unexpected JSON). Response: " . $aiRawResponse);
        $displayMessage = $aiRawResponse;
        $isInfoCollected = false; // Still ongoing conversation
    }

    // Add the AI's response to session chat history for future turns
    $conversational_ai_response_for_history = $displayMessage;
    if (isset($structuredData['customer_message'])) {
        $conversational_ai_response_for_history = $structuredData['customer_message'];
    }

    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => [['type' => 'text', 'text' => $conversational_ai_response_for_history]]];
    error_log("DEBUG: Added AI response to session history. History length: " . count($_SESSION['chat_history']));


    echo json_encode([
        'ai_response' => $displayMessage,
        'is_info_collected' => $isInfoCollected,
        'structured_data' => $structuredData // Send structured data if available, even if not fully processed
    ]);

    $conn->close();
    exit;
}
?>