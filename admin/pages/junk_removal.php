<?php
// admin/pages/junk_removal.php

// Ensure session is started and user is logged in as admin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For has_role and user_id

if (!is_logged_in() || !has_role('admin')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

$junk_removal_requests = [];
$junk_detail_view_data = null;

// Check if a specific quote ID is requested for detail view
$requested_quote_id = $_GET['quote_id'] ?? null;

// Fetch all junk removal requests for the list view
$stmt_list = $conn->prepare("SELECT
                            q.id AS quote_id,
                            q.status,
                            q.created_at,
                            q.location,
                            q.removal_date,
                            u.first_name,
                            u.last_name,
                            jrd.recommended_dumpster_size,
                            jrd.junk_items_json
                        FROM
                            quotes q
                        JOIN
                            users u ON q.user_id = u.id
                        JOIN
                            junk_removal_details jrd ON q.id = jrd.quote_id
                        WHERE
                            q.service_type = 'junk_removal'
                        ORDER BY q.created_at DESC");
$stmt_list->execute();
$result_list = $stmt_list->get_result();

while ($row = $result_list->fetch_assoc()) {
    $row['junk_items_json'] = json_decode($row['junk_items_json'], true);
    $junk_removal_requests[] = $row;
}
$stmt_list->close();

// Fetch specific junk removal request details if an ID is provided
if ($requested_quote_id) {
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
                                u.first_name, u.last_name, u.email, u.phone_number, u.address, u.city, u.state, u.zip_code,
                                jrd.junk_items_json,
                                jrd.recommended_dumpster_size,
                                jrd.additional_comment,
                                jrd.media_urls_json
                            FROM
                                quotes q
                            JOIN
                                users u ON q.user_id = u.id
                            JOIN
                                junk_removal_details jrd ON q.id = jrd.quote_id
                            WHERE
                                q.service_type = 'junk_removal' AND q.id = ?");
    $stmt_detail->bind_param("i", $requested_quote_id);
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

// Helper function for status badges (re-using from quotes.php)
function getAdminStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'quoted': return 'bg-blue-100 text-blue-800';
        case 'accepted': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        case 'converted_to_booking': return 'bg-purple-100 text-purple-800';
        default: return 'bg-gray-100 text-gray-700';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Junk Removal Requests</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 <?php echo $junk_detail_view_data ? 'hidden' : ''; ?>" id="junk-removal-list-section">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-fire mr-2 text-blue-600"></i>All Junk Removal Requests</h2>

    <?php if (empty($junk_removal_requests)): ?>
        <p class="text-gray-600 text-center p-4">No junk removal requests found.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Request ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Location</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Est. Items</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Submitted On</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($junk_removal_requests as $request): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#Q<?php echo htmlspecialchars($request['quote_id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($request['created_at']))->format('Y-m-d H:i'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getAdminStatusBadgeClass($request['status']); ?>">
                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $request['status']))); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button class="text-blue-600 hover:text-blue-900 mr-2 view-junk-request-details-btn" data-id="<?php echo htmlspecialchars($request['quote_id']); ?>">View</button>
                                <?php if ($request['status'] !== 'converted_to_booking'): ?>
                                <button class="text-purple-600 hover:text-purple-900 view-related-quote-btn" data-id="<?php echo htmlspecialchars($request['quote_id']); ?>">Manage Quote</button>
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
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Junk Removal Request #Q<?php echo htmlspecialchars($junk_detail_view_data['quote_id']); ?> Details</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-200">
            <div>
                <p class="text-gray-600"><span class="font-medium">Customer:</span> <?php echo htmlspecialchars($junk_detail_view_data['first_name'] . ' ' . $junk_detail_view_data['last_name']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($junk_detail_view_data['email']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($junk_detail_view_data['phone_number']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Customer Address:</span> <?php echo htmlspecialchars($junk_detail_view_data['address'] . ', ' . $junk_detail_view_data['city'] . ', ' . $junk_detail_view_data['state'] . ' ' . $junk_detail_view_data['zip_code']); ?></p>
                <p class="text-gray-600 mt-2"><a href="#" onclick="loadAdminSection('users', {user_id: '<?php echo $junk_detail_view_data['user_id'] ?? ''; ?>'}); return false;" class="text-blue-600 hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Customer Profile</a></p>
            </div>
            <div>
                <p class="text-gray-600"><span class="font-medium">Status:</span> <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo getAdminStatusBadgeClass($junk_detail_view_data['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $junk_detail_view_data['status']))); ?></span></p>
                <p class="text-gray-600"><span class="font-medium">Submitted On:</span> <?php echo (new DateTime($junk_detail_view_data['created_at']))->format('Y-m-d H:i A'); ?></p>
                <p class="text-gray-600"><span class="font-medium">Location:</span> <?php echo htmlspecialchars($junk_detail_view_data['location']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Preferred Removal Date:</span> <?php echo htmlspecialchars($junk_detail_view_data['removal_date']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Preferred Removal Time:</span> <?php echo htmlspecialchars($junk_detail_view_data['removal_time'] ?? 'N/A'); ?></p>
                <p class="text-gray-600"><span class="font-medium">Live Load Needed:</span> <?php echo $junk_detail_view_data['live_load_needed'] ? 'Yes' : 'No'; ?></p>
                <p class="text-gray-600"><span class="font-medium">Urgent Request:</span> <?php echo $junk_detail_view_data['is_urgent'] ? 'Yes' : 'No'; ?></p>
                <p class="text-gray-600"><span class="font-medium">Driver Instructions:</span> <?php echo htmlspecialchars($junk_detail_view_data['driver_instructions'] ?? 'None provided.'); ?></p>
            </div>
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

        <div class="flex justify-end mt-6">
            <?php if ($junk_detail_view_data['status'] === 'pending' || $junk_detail_view_data['status'] === 'quoted'): ?>
                <button class="px-5 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200 font-semibold view-related-quote-btn" data-id="<?php echo htmlspecialchars($junk_detail_view_data['quote_id']); ?>">
                    <i class="fas fa-file-invoice mr-2"></i>Manage Quote
                </button>
            <?php elseif (in_array($junk_detail_view_data['status'], ['accepted', 'converted_to_booking'])): ?>
                <button class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 font-semibold view-related-booking-btn" data-id="<?php echo htmlspecialchars($junk_detail_view_data['quote_id']); ?>">
                    <i class="fas fa-book-open mr-2"></i>View Related Booking
                </button>
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
    // Function to show the image modal
    function showImageModal(imageUrl) {
        document.getElementById('image-modal-content').src = imageUrl;
        showModal('image-modal');
    }

    // Function to show junk removal details (reloads the section with a parameter)
    function showJunkRemovalDetails(quoteId) {
        window.loadAdminSection('junk_removal', { quote_id: quoteId });
    }

    // Function to hide junk removal details and show the list
    function hideJunkRemovalDetails() {
        window.loadAdminSection('junk_removal');
    }

    document.addEventListener('click', function(event) {
        // View Junk Removal Details button
        if (event.target.classList.contains('view-junk-request-details-btn')) {
            const quoteId = event.target.dataset.id;
            showJunkRemovalDetails(quoteId);
        }

        // View Related Quote button (links to quotes.php)
        if (event.target.classList.contains('view-related-quote-btn')) {
            const quoteId = event.target.dataset.id;
            window.loadAdminSection('quotes', { quote_id: quoteId });
        }

        // View Related Booking button (links to bookings.php)
        if (event.target.classList.contains('view-related-booking-btn')) {
            const quoteId = event.target.dataset.id;
            // Fetch the booking_id associated with this quote first, then load bookings section
            fetch(`/api/admin/bookings.php?action=get_booking_by_quote_id&quote_id=${quoteId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.booking_id) {
                        window.loadAdminSection('bookings', { booking_id: data.booking_id });
                    } else {
                        showToast(data.message || 'Booking not found for this quote.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching booking ID for quote:', error);
                    showToast('Error fetching booking details. Please try again.', 'error');
                });
        }
    });
</script>