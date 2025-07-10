<?php
// customer/pages/quotes.php

// Ensure session is started and user is logged in as a customer
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For has_role and user_id

if (!is_logged_in() || !has_role('customer')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];
$quotes = [];
$expanded_quote_id = $_GET['quote_id'] ?? null; // For direct linking/expanding a specific quote

// Fetch quotes for the logged-in customer
$query = "SELECT
            q.id, q.service_type, q.status, q.created_at, q.location, q.quoted_price, q.admin_notes, q.customer_type,
            q.delivery_date, q.delivery_time, q.removal_date, q.removal_time, q.live_load_needed, q.is_urgent, q.driver_instructions,
            jrd.junk_items_json, jrd.recommended_dumpster_size, jrd.additional_comment, jrd.media_urls_json,
            qed.equipment_name, qed.quantity, qed.specific_needs, qed.duration_days
          FROM
            quotes q
          LEFT JOIN
            junk_removal_details jrd ON q.id = jrd.quote_id
          LEFT JOIN
            quote_equipment_details qed ON q.id = qed.quote_id
          WHERE
            q.user_id = ?
          ORDER BY q.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Safely decode JSON fields, defaulting to empty array/object if NULL
    $row['junk_items_json'] = json_decode($row['junk_items_json'] ?? '[]', true);
    $row['media_urls_json'] = json_decode($row['media_urls_json'] ?? '[]', true);
    $quotes[] = $row;
}
$stmt->close();
$conn->close();

// Helper function for status badges
function getCustomerStatusBadgeClass($status) {
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

<h1 class="text-3xl font-bold text-gray-800 mb-8">My Quotes</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-file-invoice mr-2 text-blue-600"></i>Your Quote Requests</h2>

    <?php if (empty($quotes)): ?>
        <p class="text-gray-600 text-center p-4">You have not submitted any quote requests yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quote ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Service Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Location</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Submitted On</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($quotes as $quote): ?>
                        <tr class="quote-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#Q<?php echo htmlspecialchars($quote['id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $quote['service_type']))); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($quote['location']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($quote['created_at']))->format('Y-m-d H:i'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getCustomerStatusBadgeClass($quote['status']); ?>">
                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $quote['status']))); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button class="text-blue-600 hover:text-blue-900 view-quote-request-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">
                                    <i class="fas fa-eye mr-1"></i>View Request
                                </button>
                            </td>
                        </tr>
                        <tr id="quote-details-<?php echo htmlspecialchars($quote['id']); ?>" class="quote-details-row bg-gray-50 hidden">
                            <td colspan="6" class="px-6 py-4">
                                <div class="p-4 border border-gray-200 rounded-lg shadow-sm">
                                    <h3 class="text-lg font-bold text-gray-800 mb-4">Details for Quote #Q<?php echo htmlspecialchars($quote['id']); ?></h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 mb-4">
                                        <div>
                                            <p><span class="font-medium">Customer Type:</span> <?php echo htmlspecialchars($quote['customer_type'] ?? 'N/A'); ?></p>
                                            <p><span class="font-medium">Requested Date:</span> <?php echo htmlspecialchars($quote['delivery_date'] ?? $quote['removal_date'] ?? 'N/A'); ?></p>
                                            <p><span class="font-medium">Requested Time:</span> <?php echo htmlspecialchars($quote['delivery_time'] ?? $quote['removal_time'] ?? 'N/A'); ?></p>
                                            <p><span class="font-medium">Live Load Needed:</span> <?php echo $quote['live_load_needed'] ? 'Yes' : 'No'; ?></p>
                                            <p><span class="font-medium">Urgent Request:</span> <?php echo $quote['is_urgent'] ? 'Yes' : 'No'; ?></p>
                                        </div>
                                        <div>
                                            <p><span class="font-medium">Driver Instructions:</span> <?php echo htmlspecialchars($quote['driver_instructions'] ?? 'None provided.'); ?></p>
                                            </div>
                                    </div>

                                    <?php if ($quote['service_type'] === 'equipment_rental'): ?>
                                        <h4 class="text-md font-semibold text-gray-700 mb-2">Equipment Details:</h4>
                                        <p class="text-sm text-gray-700"><span class="font-medium">Equipment Name:</span> <?php echo htmlspecialchars($quote['equipment_name'] ?? 'N/A'); ?></p>
                                        <p class="text-sm text-gray-700"><span class="font-medium">Quantity:</span> <?php echo htmlspecialchars($quote['quantity'] ?? 'N/A'); ?></p>
                                        <p class="text-sm text-gray-700"><span class="font-medium">Specific Needs:</span> <?php echo htmlspecialchars($quote['specific_needs'] ?? 'N/A'); ?></p>
                                    <?php elseif ($quote['service_type'] === 'junk_removal'): ?>
                                        <h4 class="text-md font-semibold text-gray-700 mb-2">Junk Removal Details:</h4>
                                        <p class="text-sm text-gray-700 font-medium mb-1">Junk Items:</p>
                                        <?php if (!empty($quote['junk_items_json'])): ?>
                                            <ul class="list-disc list-inside text-sm text-gray-700 ml-4 mb-2">
                                                <?php foreach ($quote['junk_items_json'] as $item): ?>
                                                    <li><?php echo htmlspecialchars($item['itemType'] ?? 'N/A'); ?> (Qty: <?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?>, Dims: <?php echo htmlspecialchars($item['estDimensions'] ?? 'N/A'); ?>, Weight: <?php echo htmlspecialchars($item['estWeight'] ?? 'N/A'); ?>)</li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-sm text-gray-700 ml-4 mb-2">No specific junk items listed.</p>
                                        <?php endif; ?>
                                        <p class="text-sm text-gray-700"><span class="font-medium">Recommended Dumpster Size:</span> <?php echo htmlspecialchars($quote['recommended_dumpster_size'] ?? 'N/A'); ?></p>
                                        <p class="text-sm text-gray-700"><span class="font-medium">Additional Comment:</span> <?php echo htmlspecialchars($quote['additional_comment'] ?? 'None'); ?></p>

                                        <h4 class="font-semibold text-gray-700 mt-4 mb-2">Uploaded Media:</h4>
                                        <?php if (!empty($quote['media_urls_json'])): ?>
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                <?php foreach ($quote['media_urls_json'] as $media_url): ?>
                                                    <div class="relative group">
                                                        <?php
                                                        $fileExtension = pathinfo($media_url, PATHINFO_EXTENSION);
                                                        $isImage = in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif']);
                                                        ?>
                                                        <?php if ($isImage): ?>
                                                            <img src="<?php echo htmlspecialchars($media_url); ?>" alt="Junk item photo" class="w-full h-24 object-cover rounded-md shadow-sm cursor-pointer" onclick="showImageModal('<?php echo htmlspecialchars($media_url); ?>');">
                                                        <?php else: ?>
                                                            <video controls src="<?php echo htmlspecialchars($media_url); ?>" class="w-full h-24 object-cover rounded-md shadow-sm"></video>
                                                        <?php endif; ?>
                                                        <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-md">
                                                            <a href="<?php echo htmlspecialchars($media_url); ?>" target="_blank" class="text-white text-xl hover:text-blue-300" title="Open Media">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-gray-600">No media uploaded for this request.</p>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($quote['status'] === 'quoted' || $quote['status'] === 'accepted' || $quote['status'] === 'converted_to_booking'): ?>
                                        <div class="mt-6 pt-4 border-t border-gray-200">
                                            <h4 class="text-lg font-bold text-gray-800 mb-2">Our Quotation:</h4>
                                            <p class="text-gray-700 mb-2"><span class="font-medium">Quoted Price:</span> <span class="text-green-600 text-xl font-bold">$<?php echo number_format($quote['quoted_price'], 2); ?></span></p>
                                            <?php if (!empty($quote['admin_notes'])): ?>
                                                <p class="text-gray-700 mb-4"><span class="font-medium">Notes from Admin:</span> <?php echo nl2br(htmlspecialchars($quote['admin_notes'])); ?></p>
                                            <?php endif; ?>

                                            <?php if ($quote['status'] === 'quoted'): ?>
                                                <div class="flex space-x-3 mt-4">
                                                    <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 accept-quote-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>" data-price="<?php echo htmlspecialchars($quote['quoted_price']); ?>">
                                                        <i class="fas fa-check-circle mr-2"></i>Accept Quote
                                                    </button>
                                                    <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 reject-quote-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">
                                                        <i class="fas fa-times-circle mr-2"></i>Reject Quote
                                                    </button>
                                                </div>
                                            <?php elseif ($quote['status'] === 'accepted'): ?>
                                                <div class="mt-4 p-3 bg-green-50 text-green-700 border border-green-200 rounded-lg text-center font-medium">
                                                    <i class="fas fa-info-circle mr-2"></i>This quote has been accepted.
                                                </div>
                                            <?php elseif ($quote['status'] === 'converted_to_booking'): ?>
                                                <div class="mt-4 p-3 bg-purple-50 text-purple-700 border border-purple-200 rounded-lg text-center font-medium">
                                                    <i class="fas fa-check-double mr-2"></i>This quote has been converted to a booking. You can view it in your bookings.
                                                    <br><button class="text-purple-600 hover:underline mt-2" onclick="loadCustomerSection('bookings')">Go to Bookings</button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($quote['status'] === 'pending'): ?>
                                        <div class="mt-6 pt-4 border-t border-gray-200 p-3 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-lg text-center font-medium">
                                            <i class="fas fa-hourglass-half mr-2"></i>Your quote request is pending. Our team will provide a quotation soon.
                                        </div>
                                    <?php elseif ($quote['status'] === 'rejected'): ?>
                                        <div class="mt-6 pt-4 border-t border-gray-200 p-3 bg-red-50 text-red-700 border border-red-200 rounded-lg text-center font-medium">
                                            <i class="fas fa-ban mr-2"></i>This quote request has been rejected.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center hidden z-50">
    <button class="absolute top-4 right-4 text-white text-4xl" onclick="hideModal('image-modal')">&times;</button>
    <img id="image-modal-content" src="" class="max-w-full max-h-[90%] object-contain">
</div>

<script>
    // Function to show/hide modal (assuming you have a global hideModal function or adapt this)
    // These functions are already globally defined in customer/dashboard.php, so just use them directly.
    // function showModal(id) { document.getElementById(id).classList.remove('hidden'); }
    // function hideModal(id) { document.getElementById(id).classList.add('hidden'); }

    // Function to show the image modal
    function showImageModal(imageUrl) {
        document.getElementById('image-modal-content').src = imageUrl;
        window.showModal('image-modal'); // Use window.showModal
    }

    // Handle "View Request" button click to toggle details
    // Removed DOMContentLoaded wrapper as this script will be re-executed when loaded by AJAX.
    document.querySelectorAll('.view-quote-request-btn').forEach(button => {
        button.addEventListener('click', function() {
            const quoteId = this.dataset.id;
            const detailsRow = document.getElementById(`quote-details-${quoteId}`);
            detailsRow.classList.toggle('hidden');

            // Optional: Change button text/icon based on state
            if (detailsRow.classList.contains('hidden')) {
                this.innerHTML = '<i class="fas fa-eye mr-1"></i>View Request';
            } else {
                this.innerHTML = '<i class="fas fa-eye-slash mr-1"></i>Hide Details';
            }
        });
    });

    // If a quote_id is present in the URL, expand that specific quote
    // This logic runs when the page is loaded (via AJAX or directly)
    const urlParams = new URLSearchParams(window.location.search);
    const initialQuoteId = urlParams.get('quote_id');
    if (initialQuoteId) {
        const initialDetailsRow = document.getElementById(`quote-details-${initialQuoteId}`);
        if (initialDetailsRow) {
            initialDetailsRow.classList.remove('hidden');
            // Scroll to the quote
            initialDetailsRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // Update button text
            const viewButton = document.querySelector(`.view-quote-request-btn[data-id="${initialQuoteId}"]`);
            if (viewButton) {
                viewButton.innerHTML = '<i class="fas fa-eye-slash mr-1"></i>Hide Details';
            }
        }
    }


    // Handle "Accept Quote" button click
    document.querySelectorAll('.accept-quote-btn').forEach(button => {
        button.addEventListener('click', function() {
            const quoteId = this.dataset.id;
            const quotedPrice = this.dataset.price;

            // Assuming showConfirmationModal and showToast are global functions from your main layout
            if (typeof window.showConfirmationModal === 'function' && typeof window.showToast === 'function') {
                window.showConfirmationModal( // Use window.showConfirmationModal
                    'Accept Quote',
                    `Are you sure you want to accept this quote for $${parseFloat(quotedPrice).toFixed(2)}? This will proceed to payment.`,
                    async (confirmed) => {
                        if (confirmed) {
                            window.showToast('Accepting quote...', 'info'); // Use window.showToast
                            const formData = new FormData();
                            formData.append('action', 'accept_quote');
                            formData.append('quote_id', quoteId);

                            try {
                                const response = await fetch('/api/customer/quotes.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();

                                if (result.success) {
                                    window.showToast(result.message, 'success'); // Use window.showToast
                                    // On successful acceptance, redirect to the invoice payment page
                                    if (result.invoice_id) {
                                        window.loadCustomerSection('invoices', { invoice_id: result.invoice_id }); // Redirect to invoice
                                    } else {
                                        // Fallback if no invoice_id, just reload quotes to show updated status
                                        window.loadCustomerSection('quotes', { quote_id: quoteId });
                                    }
                                } else {
                                    window.showToast(result.message, 'error'); // Use window.showToast
                                }
                            } catch (error) {
                                console.error('Accept quote API Error:', error);
                                window.showToast('An error occurred. Please try again.', 'error'); // Use window.showToast
                            }
                        }
                    },
                    'Accept Quote',
                    'bg-green-600'
                );
            } else {
                console.error('showConfirmationModal or showToast not found. Ensure includes/modals.php or similar is loaded.');
                alert('Accepting quote functionality is not fully loaded. Please check console for errors.');
            }
        });
    });

    // Handle "Reject Quote" button click
    document.querySelectorAll('.reject-quote-btn').forEach(button => {
        button.addEventListener('click', function() {
            const quoteId = this.dataset.id;

            if (typeof window.showConfirmationModal === 'function' && typeof window.showToast === 'function') {
                window.showConfirmationModal( // Use window.showConfirmationModal
                    'Reject Quote',
                    'Are you sure you want to reject this quote? This action cannot be undone.',
                    async (confirmed) => {
                        if (confirmed) {
                            window.showToast('Rejecting quote...', 'info'); // Use window.showToast
                            const formData = new FormData();
                            formData.append('action', 'reject_quote');
                            formData.append('quote_id', quoteId);

                            try {
                                const response = await fetch('/api/customer/quotes.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();

                                if (result.success) {
                                    window.showToast(result.message, 'success'); // Use window.showToast
                                    window.loadCustomerSection('quotes', { quote_id: quoteId }); // Reload to show updated status
                                } else {
                                    window.showToast(result.message, 'error'); // Use window.showToast
                                }
                            } catch (error) {
                                console.error('Reject quote API Error:', error);
                                window.showToast('An error occurred. Please try again.', 'error'); // Use window.showToast
                            }
                        }
                    },
                    'Reject Quote',
                    'bg-red-600'
                );
            } else {
                console.error('showConfirmationModal or showToast not found. Ensure includes/modals.php or similar is loaded.');
                alert('Rejecting quote functionality is not fully loaded. Please check console for errors.');
            }
        });
    });
</script>