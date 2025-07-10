<?php
// customer/pages/junk_removal.php

// Ensure session is started and user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';

if (!is_logged_in()) {
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];
$junk_removal_requests = [];
$junk_detail_view_data = null; // To hold data for a single junk removal detail view if requested

// Check if a specific quote ID is requested for detail view
$requested_quote_id_for_detail = $_GET['quote_id'] ?? null;


// Fetch all junk removal requests for the current user for the list view
$stmt_list = $conn->prepare("SELECT
                            q.id AS quote_id,
                            q.status,
                            q.created_at,
                            q.location,
                            q.removal_date,
                            jrd.junk_items_json,
                            jrd.recommended_dumpster_size,
                            jrd.additional_comment
                        FROM
                            quotes q
                        JOIN
                            junk_removal_details jrd ON q.id = jrd.quote_id
                        WHERE
                            q.user_id = ? AND q.service_type = 'junk_removal'
                        ORDER BY q.created_at DESC");
$stmt_list->bind_param("i", $user_id);
$stmt_list->execute();
$result_list = $stmt_list->get_result();

while ($row = $result_list->fetch_assoc()) {
    $row['junk_items_json'] = json_decode($row['junk_items_json'], true);
    $junk_removal_requests[] = $row;
}
$stmt_list->close();

// Fetch specific junk removal request details if an ID is provided
if ($requested_quote_id_for_detail) {
    $stmt_detail = $conn->prepare("SELECT
                                q.id AS quote_id,
                                q.status,
                                q.created_at,
                                q.location,
                                q.removal_date,
                                q.removal_time,
                                q.live_load_needed,
                                q.is_urgent,
                                q.driver_instructions,
                                q.quoted_price,
                                jrd.junk_items_json,
                                jrd.recommended_dumpster_size,
                                jrd.additional_comment,
                                jrd.media_urls_json
                            FROM
                                quotes q
                            JOIN
                                junk_removal_details jrd ON q.id = jrd.quote_id
                            WHERE
                                q.user_id = ? AND q.service_type = 'junk_removal' AND q.id = ?");
    $stmt_detail->bind_param("ii", $user_id, $requested_quote_id_for_detail);
    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();
    if ($result_detail->num_rows > 0) {
        $junk_detail_view_data = $result_detail->fetch_assoc();
        $junk_detail_view_data['junk_items_json'] = json_decode($junk_detail_view_data['junk_items_json'], true);
        $junk_detail_view_data['media_urls_json'] = json_decode($junk_detail_view_data['media_urls_json'], true);
    }
    $stmt_detail->close();
}


$conn->close();

// Function to get status badge classes (re-used from other pages)
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-700';
        case 'quoted':
            return 'bg-blue-100 text-blue-700';
        case 'accepted':
        case 'converted_to_booking':
            return 'bg-green-100 text-green-700';
        case 'rejected':
        case 'cancelled':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Junk Removal Services</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 mb-8 text-center <?php echo $junk_detail_view_data ? 'hidden' : ''; ?>" id="junk-removal-intro-section">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center justify-center"><i class="fas fa-robot mr-2 text-teal-600"></i>Start a New Junk Removal Request</h2>
    <p class="text-gray-600 mb-4">Click the button below to chat with our AI assistant and quickly arrange your next junk removal service.</p>
    <button class="py-3 px-6 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200 shadow-lg" onclick="showAIChat('junk-removal-service');">
        <i class="fas fa-comments mr-2"></i>Launch AI Junk Removal Chat
    </button>
</div>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 <?php echo $junk_detail_view_data ? 'hidden' : ''; ?>" id="junk-removal-list">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-history mr-2 text-blue-600"></i>Your Past Junk Removal Requests</h2>

    <?php if (empty($junk_removal_requests)): ?>
        <p class="text-gray-600 text-center p-4">You have not submitted any junk removal requests yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Request ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date Submitted</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Location</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Items (Est.)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($junk_removal_requests as $request): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#Q<?php echo htmlspecialchars($request['quote_id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($request['created_at']))->format('Y-m-d H:i'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($request['location']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php
                                if (!empty($request['junk_items_json'])) {
                                    $item_types = array_column($request['junk_items_json'], 'itemType');
                                    echo htmlspecialchars(implode(', ', $item_types));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($request['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $request['status']))); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="text-blue-600 hover:text-blue-900 view-junk-request-details" data-quote-id="<?php echo htmlspecialchars($request['quote_id']); ?>">View Details</button>
                                <?php if ($request['status'] === 'quoted'): ?>
                                    <button class="ml-3 text-green-600 hover:text-green-900" onclick="loadSection('invoices', {quote_id: <?php echo $request['quote_id']; ?>});">Review Quote</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="junk-removal-detail-view" class="bg-white p-6 rounded-lg shadow-md border border-blue-200 mt-8 <?php echo $junk_detail_view_data ? '' : 'hidden'; ?>">
    <button class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="hideJunkRemovalDetails()">
        <i class="fas fa-arrow-left mr-2"></i>Back to Requests
    </button>
    <?php if ($junk_detail_view_data): ?>
        <h2 class="text-2xl font-bold text-gray-800 mb-6" id="detail-junk-request-number">Junk Removal Request #Q<?php echo htmlspecialchars($junk_detail_view_data['quote_id']); ?> Details</h2>
        <div id="junk-request-details-content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-200">
                <div><span class="font-medium">Request Date:</span> <?php echo (new DateTime($junk_detail_view_data['created_at']))->format('Y-m-d H:i'); ?></div>
                <div><span class="font-medium">Status:</span> <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($junk_detail_view_data['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $junk_detail_view_data['status']))); ?></span></div>
                <div><span class="font-medium">Location:</span> <?php echo htmlspecialchars($junk_detail_view_data['location']); ?></div>
                <div><span class="font-medium">Preferred Removal Date:</span> <?php echo htmlspecialchars($junk_detail_view_data['removal_date']); ?></div>
                <div><span class="font-medium">Preferred Removal Time:</span> <?php echo htmlspecialchars($junk_detail_view_data['removal_time'] ?? 'N/A'); ?></div>
                <div><span class="font-medium">Live Load Needed:</span> <?php echo $junk_detail_view_data['live_load_needed'] ? 'Yes' : 'No'; ?></div>
                <div><span class="font-medium">Urgent Request:</span> <?php echo $junk_detail_view_data['is_urgent'] ? 'Yes' : 'No'; ?></div>
                <div class="md:col-span-2"><span class="font-medium">Driver Instructions:</span> <?php echo htmlspecialchars($junk_detail_view_data['driver_instructions'] ?? 'None'); ?></div>
            </div>

            <h3 class="text-xl font-semibold text-gray-700 mb-4">Identified Junk Items</h3>
            <?php if (!empty($junk_detail_view_data['junk_items_json'])): ?>
                <ul class="list-disc list-inside space-y-2 mb-6">
                    <?php foreach ($junk_detail_view_data['junk_items_json'] as $item): ?>
                        <li>
                            <span class="font-medium"><?php echo htmlspecialchars($item['itemType'] ?? 'Unknown Item'); ?></span>
                            (Quantity: <?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?>,
                            Est. Dimensions: <?php echo htmlspecialchars($item['estDimensions'] ?? 'N/A'); ?>,
                            Est. Weight: <?php echo htmlspecialchars($item['estWeight'] ?? 'N/A'); ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-gray-600 mb-6">No specific junk items detailed.</p>
            <?php endif; ?>

            <div class="mb-6 pb-6 border-b border-gray-200">
                <p class="mb-2"><span class="font-medium">Recommended Dumpster Size:</span> <?php echo htmlspecialchars($junk_detail_view_data['recommended_dumpster_size'] ?? 'N/A'); ?></p>
                <p><span class="font-medium">Additional Comment:</span> <?php echo htmlspecialchars($junk_detail_view_data['additional_comment'] ?? 'None'); ?></p>
            </div>

            <h3 class="text-xl font-semibold text-gray-700 mb-4">Uploaded Media</h3>
            <?php if (!empty($junk_detail_view_data['media_urls_json'])): ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                    <?php foreach ($junk_detail_view_data['media_urls_json'] as $media_url): ?>
                        <div class="relative group">
                            <?php
                            $fileExtension = pathinfo($media_url, PATHINFO_EXTENSION);
                            $isImage = in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif']);
                            ?>
                            <?php if ($isImage): ?>
                                <img src="<?php echo htmlspecialchars($media_url); ?>" alt="Junk item photo" class="w-full h-32 object-cover rounded-lg shadow-md cursor-pointer" onclick="showImageModal('<?php echo htmlspecialchars($media_url); ?>');">
                            <?php else: ?>
                                <video controls src="<?php echo htmlspecialchars($media_url); ?>" class="w-full h-32 object-cover rounded-lg shadow-md"></video>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg">
                                <a href="<?php echo htmlspecialchars($media_url); ?>" target="_blank" class="text-white text-3xl hover:text-blue-300" title="Open Media">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600 mb-6">No media uploaded for this request.</p>
            <?php endif; ?>

            <?php if ($junk_detail_view_data['status'] === 'quoted'): ?>
                <div class="text-right mt-6">
                    <p class="text-xl font-bold text-gray-800 mb-3">Quoted Price: <span class="text-green-600">$<?php echo number_format($junk_detail_view_data['quoted_price'], 2); ?></span></p>
                    <button class="py-2 px-5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-lg" onclick="loadSection('invoices', {quote_id: <?php echo $junk_detail_view_data['quote_id']; ?>});">
                        <i class="fas fa-hand-holding-usd mr-2"></i>Review & Pay Quote
                    </button>
                </div>
            <?php elseif ($junk_detail_view_data['status'] === 'converted_to_booking'): ?>
                <div class="text-center mt-6">
                    <p class="text-xl font-bold text-green-600 mb-3"><i class="fas fa-check-circle mr-2"></i>This request has been successfully converted to a booking!</p>
                    <button class="py-2 px-5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-lg" onclick="loadSection('bookings', {});">
                        <i class="fas fa-book-open mr-2"></i>View Bookings
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="text-center text-gray-600">Junk removal request details not found or invalid ID.</p>
    <?php endif; ?>
</div>

<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center hidden z-50">
    <button class="absolute top-4 right-4 text-white text-4xl" onclick="hideModal('image-modal')">&times;</button>
    <img id="image-modal-content" src="" class="max-w-full max-h-[90%] object-contain">
</div>

<script>
    // Function to show the junk removal request detail view
    function showJunkRemovalDetails(quoteId) {
        // Reload the junk-removal page with the specific quote_id parameter
        loadSection('junk-removal', { quote_id: quoteId });
    }

    // Function to hide the junk removal detail view and show the list
    function hideJunkRemovalDetails() {
        loadSection('junk-removal'); // Loads the junk-removal page without a specific ID, showing the list
    }

    // Attach listeners for "View Details" buttons in the junk removal list
    document.querySelectorAll('.view-junk-request-details').forEach(button => {
        button.addEventListener('click', function() {
            showJunkRemovalDetails(this.dataset.quoteId);
        });
    });

    // Function to show image in a modal
    function showImageModal(imageUrl) {
        document.getElementById('image-modal-content').src = imageUrl;
        showModal('image-modal');
    }
</script>