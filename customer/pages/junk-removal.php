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
        $junk_detail_view_data['junk_items_json'] = json_decode($junk_detail_view_data['junk_items_json'] ?? '[]', true); // Ensure it's an array
        $junk_detail_view_data['media_urls_json'] = json_decode($junk_detail_view_data['media_urls_json'] ?? '[]', true); // Ensure it's an array
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
        case 'customer_draft': // Added for new draft status
            return 'bg-gray-200 text-gray-700';
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
                                    <button class="ml-3 text-green-600 hover:text-green-900" onclick="window.loadCustomerSection('invoices', {quote_id: <?php echo $request['quote_id']; ?>});">Review Quote</button>
                                <?php elseif ($request['status'] === 'customer_draft'): ?>
                                     <button class="ml-3 text-orange-600 hover:text-orange-900 edit-junk-request-details" data-quote-id="<?php echo htmlspecialchars($request['quote_id']); ?>">Edit Draft</button>
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

            <div id="junk-items-view-mode">
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
            </div>

            <div id="junk-items-edit-mode" class="hidden">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Edit Junk Items</h3>
                <table class="min-w-full divide-y divide-gray-200 mb-4" id="editable-junk-items-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item Type</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Qty</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Est. Dims</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Est. Wt.</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16"></th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
                <button type="button" id="add-junk-item-btn" class="mb-4 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm hover:bg-blue-200">
                    <i class="fas fa-plus-circle mr-2"></i>Add Item
                </button>
            </div>

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

            <div class="flex justify-end mt-6 space-x-3">
                <?php if ($junk_detail_view_data['status'] === 'customer_draft'): ?>
                    <button id="edit-junk-items-btn" class="py-2 px-5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 shadow-lg">
                        <i class="fas fa-edit mr-2"></i>Edit Items
                    </button>
                    <button id="save-junk-items-btn" class="py-2 px-5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-lg hidden">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <button id="cancel-edit-junk-items-btn" class="py-2 px-5 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition-colors duration-200 shadow-lg hidden">
                        Cancel
                    </button>
                    <button id="submit-junk-request-btn" class="py-2 px-5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Request
                    </button>
                <?php elseif ($junk_detail_view_data['status'] === 'quoted'): ?>
                    <div class="text-right mt-6">
                        <p class="text-xl font-bold text-gray-800 mb-3">Quoted Price: <span class="text-green-600">$<?php echo number_format($junk_detail_view_data['quoted_price'], 2); ?></span></p>
                        <button class="py-2 px-5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-lg" onclick="window.loadCustomerSection('invoices', {quote_id: <?php echo $junk_detail_view_data['quote_id']; ?>});">
                            <i class="fas fa-hand-holding-usd mr-2"></i>Review & Pay Quote
                        </button>
                    </div>
                <?php elseif ($junk_detail_view_data['status'] === 'converted_to_booking'): ?>
                    <div class="text-center mt-6">
                        <p class="text-xl font-bold text-green-600 mb-3"><i class="fas fa-check-circle mr-2"></i>This request has been successfully converted to a booking!</p>
                        <button class="py-2 px-5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-lg" onclick="window.loadCustomerSection('bookings', {});">
                            <i class="fas fa-book-open mr-2"></i>View Bookings
                        </button>
                    </div>
                <?php endif; ?>
            </div>
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
    // Encapsulate JavaScript in an IIFE to prevent global variable conflicts
    (function() {
        // Function to show the junk removal request detail view
        function showJunkRemovalDetails(quoteId) {
            // Reload the junk-removal page with the specific quote_id parameter
            window.loadCustomerSection('junk-removal', { quote_id: quoteId });
        }

        // Function to hide the junk removal detail view and show the list
        function hideJunkRemovalDetails() {
            window.loadCustomerSection('junk-removal'); // Loads the junk-removal page without a specific ID, showing the list
        }

        // Attach listeners for "View Details" buttons in the junk removal list
        document.querySelectorAll('.view-junk-request-details').forEach(button => {
            button.addEventListener('click', function() {
                showJunkRemovalDetails(this.dataset.quoteId);
            });
        });
        
        // Attach listeners for "Edit Draft" buttons in the junk removal list
        document.querySelectorAll('.edit-junk-request-details').forEach(button => {
            button.addEventListener('click', function() {
                showJunkRemovalDetails(this.dataset.quoteId);
                // After loading details, trigger edit mode
                // Use a small delay to ensure the DOM is fully updated after loadCustomerSection
                setTimeout(() => {
                    document.getElementById('edit-junk-items-btn')?.click();
                }, 100); 
            });
        });

        // Function to show image in a modal
        function showImageModal(imageUrl) {
            document.getElementById('image-modal-content').src = imageUrl;
            window.showModal('image-modal');
        }

        // --- New JavaScript for GUI-based Editing ---
        const junkItemsViewMode = document.getElementById('junk-items-view-mode');
        const junkItemsEditMode = document.getElementById('junk-items-edit-mode');
        const editJunkItemsBtn = document.getElementById('edit-junk-items-btn');
        const saveJunkItemsBtn = document.getElementById('save-junk-items-btn');
        const cancelEditJunkItemsBtn = document.getElementById('cancel-edit-junk-items-btn');
        const addJunkItemBtn = document.getElementById('add-junk-item-btn');
        const editableJunkItemsTableBody = document.getElementById('editable-junk-items-table')?.querySelector('tbody');
        const submitJunkRequestBtn = document.getElementById('submit-junk-request-btn');

        let originalJunkItems = []; // To store the original state for 'Cancel'

        function renderEditableJunkItems(items) {
            if (!editableJunkItemsTableBody) return;
            editableJunkItemsTableBody.innerHTML = ''; // Clear existing rows
            items.forEach(item => {
                addJunkItemRow(item);
            });
        }

        function addJunkItemRow(item = {}) {
            if (!editableJunkItemsTableBody) return;
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td class="p-2"><input type="text" class="w-full p-2 border rounded" value="${item.itemType ?? ''}" placeholder="e.g., Sofa" required></td>
                <td class="p-2"><input type="number" class="w-full p-2 border rounded" value="${item.quantity ?? 1}" min="1" required></td>
                <td class="p-2"><input type="text" class="w-full p-2 border rounded" value="${item.estDimensions ?? ''}" placeholder="e.g., 6x3x3 ft"></td>
                <td class="p-2"><input type="text" class="w-full p-2 border rounded" value="${item.estWeight ?? ''}" placeholder="e.g., 100 lbs"></td>
                <td class="p-2 text-center"><button type="button" class="text-red-500 hover:text-red-700 remove-junk-item-btn">&times;</button></td>
            `;
            editableJunkItemsTableBody.appendChild(newRow);
            newRow.querySelector('.remove-junk-item-btn')?.addEventListener('click', (e) => e.target.closest('tr').remove());
        }

        function collectEditedJunkItems() {
            const items = [];
            if (!editableJunkItemsTableBody) return items;
            editableJunkItemsTableBody.querySelectorAll('tr').forEach(row => {
                const inputs = row.querySelectorAll('input');
                items.push({
                    itemType: inputs[0].value.trim(),
                    quantity: parseInt(inputs[1].value) || 1,
                    estDimensions: inputs[2].value.trim(),
                    estWeight: inputs[3].value.trim()
                });
            });
            return items;
        }

        // Event Listeners for Edit Mode
        if(editJunkItemsBtn) {
            editJunkItemsBtn.addEventListener('click', () => {
                junkItemsViewMode.classList.add('hidden');
                junkItemsEditMode.classList.remove('hidden');
                editJunkItemsBtn.classList.add('hidden');
                saveJunkItemsBtn.classList.remove('hidden');
                cancelEditJunkItemsBtn.classList.remove('hidden');
                if(submitJunkRequestBtn) submitJunkRequestBtn.classList.add('hidden'); // Hide submit if editing
                
                // Populate editable table with current data
                // Ensure junk_detail_view_data is accessible and defined when this script runs
                const currentItems = <?php echo json_encode($junk_detail_view_data['junk_items_json'] ?? []); ?>;
                originalJunkItems = JSON.parse(JSON.stringify(currentItems)); // Deep copy
                renderEditableJunkItems(currentItems);
            });
        }

        if(cancelEditJunkItemsBtn) {
            cancelEditJunkItemsBtn.addEventListener('click', () => {
                // Reload the section to effectively cancel edits and revert to the saved state
                const currentQuoteId = <?php echo htmlspecialchars($junk_detail_view_data['quote_id'] ?? 'null'); ?>;
                if (currentQuoteId) {
                    window.loadCustomerSection('junk-removal', { quote_id: currentQuoteId });
                } else {
                    window.loadCustomerSection('junk-removal');
                }
            });
        }

        if(addJunkItemBtn) {
            addJunkItemBtn.addEventListener('click', () => {
                addJunkItemRow(); // Add an empty row
            });
        }

        if(saveJunkItemsBtn) {
            saveJunkItemsBtn.addEventListener('click', async () => {
                const editedItems = collectEditedJunkItems();
                const quoteId = <?php echo htmlspecialchars($junk_detail_view_data['quote_id'] ?? 'null'); ?>;

                if (editedItems.some(item => !item.itemType.trim())) {
                    window.showToast('Item Type cannot be empty for any item.', 'error');
                    return;
                }
                if (editedItems.length === 0) {
                     window.showToast('Please add at least one junk item.', 'error');
                     return;
                }

                window.showConfirmationModal(
                    'Save Changes',
                    'Are you sure you want to save these changes to your junk removal request?',
                    async (confirmed) => {
                        if (confirmed) {
                            window.showToast('Saving items...', 'info');
                            const formData = new FormData();
                            formData.append('action', 'update_junk_items'); // New action for backend
                            formData.append('quote_id', quoteId);
                            formData.append('junk_items', JSON.stringify(editedItems)); // Send as JSON string

                            try {
                                const response = await fetch('/api/customer/junk_removal_update.php', { // New API endpoint
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();

                                if (result.success) {
                                    window.showToast(result.message, 'success');
                                    // Reload details view to show updated items in view mode
                                    window.loadCustomerSection('junk-removal', { quote_id: quoteId });
                                } else {
                                    window.showToast(result.message, 'error');
                                }
                            } catch (error) {
                                console.error('Save junk items API Error:', error);
                                window.showToast('An error occurred while saving. Please try again.', 'error');
                            }
                        }
                    },
                    'Save',
                    'bg-green-600'
                );
            });
        }

        // Handle "Submit Request" button for customer_draft
        if(submitJunkRequestBtn) {
            submitJunkRequestBtn.addEventListener('click', () => {
                const quoteId = <?php echo htmlspecialchars($junk_detail_view_data['quote_id'] ?? 'null'); ?>;
                const currentStatus = "<?php echo htmlspecialchars($junk_detail_view_data['status'] ?? ''); ?>";

                if (currentStatus !== 'customer_draft') {
                    window.showToast('This request is not in a draft state and cannot be re-submitted.', 'error');
                    return;
                }
                
                window.showConfirmationModal(
                    'Submit Request for Quote',
                    'Are you sure you want to submit this request for a quote from our team?',
                    async (confirmed) => {
                        if (confirmed) {
                            window.showToast('Submitting request...', 'info');
                            const formData = new FormData();
                            formData.append('action', 'submit_customer_draft'); // New action for backend
                            formData.append('quote_id', quoteId);

                            try {
                                const response = await fetch('/api/customer/junk_removal_update.php', { // New API endpoint
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();

                                if (result.success) {
                                    window.showToast(result.message, 'success');
                                    window.loadCustomerSection('junk-removal', { quote_id: quoteId }); // Reload
                                } else {
                                    window.showToast(result.message, 'error');
                                }
                            } catch (error) {
                                console.error('Submit draft API Error:', error);
                                window.showToast('An error occurred during submission. Please try again.', 'error');
                            }
                        }
                    },
                    'Submit',
                    'bg-blue-600'
                );
            });
        }
    })(); // End of IIFE
</script>