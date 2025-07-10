<?php
// admin/pages/quotes.php

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

$quotes = [];
$customer_data_for_email = []; // To store customer data for email sending

// Fetch all quotes, joining with users table to get customer info for email
$query = "SELECT
            q.id, q.service_type, q.status, q.created_at, q.location, q.quoted_price, q.admin_notes,
            u.first_name, u.last_name, u.email,
            jrd.junk_items_json, jrd.recommended_dumpster_size, jrd.additional_comment, jrd.media_urls_json,
            qed.equipment_name, qed.quantity, qed.specific_needs, qed.duration_days
          FROM
            quotes q
          JOIN
            users u ON q.user_id = u.id
          LEFT JOIN
            junk_removal_details jrd ON q.id = jrd.quote_id
          LEFT JOIN
            quote_equipment_details qed ON q.id = qed.quote_id
          ORDER BY q.created_at DESC";

$stmt = $conn->prepare($query);
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

<h1 class="text-3xl font-bold text-gray-800 mb-8">Quote Management</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-file-invoice mr-2 text-blue-600"></i>All Customer Quotes</h2>

    <?php if (empty($quotes)): ?>
        <p class="text-gray-600 text-center p-4">No quote requests found.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quote ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Service Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Submitted On</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quoted Price</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($quotes as $quote): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#Q<?php echo htmlspecialchars($quote['id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($quote['first_name'] . ' ' . $quote['last_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $quote['service_type']))); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($quote['created_at']))->format('Y-m-d H:i'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getAdminStatusBadgeClass($quote['status']); ?>">
                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $quote['status']))); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $quote['quoted_price'] ? '$' . number_format($quote['quoted_price'], 2) : 'N/A'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button class="text-blue-600 hover:text-blue-900 mr-2 view-quote-details-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">
                                    View
                                </button>
                                <?php if ($quote['status'] === 'pending'): ?>
                                    <button class="text-green-600 hover:text-green-900 mr-2 submit-quote-btn"
                                        data-id="<?php echo htmlspecialchars($quote['id']); ?>"
                                        data-customer-name="<?php echo htmlspecialchars($quote['first_name'] . ' ' . $quote['last_name']); ?>"
                                        data-customer-email="<?php echo htmlspecialchars($quote['email']); ?>"
                                        data-location="<?php echo htmlspecialchars($quote['location']); ?>"
                                        data-service-type="<?php echo htmlspecialchars($quote['service_type']); ?>"
                                        data-equipment-name="<?php echo htmlspecialchars($quote['equipment_name'] ?? ''); ?>"
                                        data-junk-items="<?php echo htmlspecialchars(json_encode($quote['junk_items_json'])); ?>"
                                        >
                                        Submit Quote
                                    </button>
                                <?php elseif ($quote['status'] === 'quoted'): ?>
                                    <button class="text-indigo-600 hover:text-indigo-900 mr-2 resend-quote-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>" data-customer-name="<?php echo htmlspecialchars($quote['first_name'] . ' ' . $quote['last_name']); ?>" data-customer-email="<?php echo htmlspecialchars($quote['email']); ?>">
                                        Resend Quote
                                    </button>
                                    <button class="text-red-600 hover:text-red-900 reject-quote-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">
                                        Reject
                                    </button>
                                <?php elseif ($quote['status'] === 'accepted' || $quote['status'] === 'converted_to_booking'): ?>
                                    <button class="text-purple-600 hover:text-purple-900 view-related-booking-btn" data-quote-id="<?php echo htmlspecialchars($quote['id']); ?>">
                                        View Booking
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="quote-details-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-2xl text-gray-800 relative">
        <button class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl" onclick="hideModal('quote-details-modal')">
            <i class="fas fa-times"></i>
        </button>
        <h3 class="text-xl font-bold mb-4">Quote Request #<span id="detail-quote-id"></span> Details</h3>
        <div class="space-y-3 text-gray-700 text-sm">
            <p><span class="font-semibold">Customer:</span> <span id="detail-customer-name"></span></p>
            <p><span class="font-semibold">Email:</span> <span id="detail-customer-email"></span></p>
            <p><span class="font-semibold">Service Type:</span> <span id="detail-service-type"></span></p>
            <p><span class="font-semibold">Location:</span> <span id="detail-location"></span></p>
            <p><span class="font-semibold">Submitted On:</span> <span id="detail-created-at"></span></p>
            <p><span class="font-semibold">Delivery/Removal Date:</span> <span id="detail-date"></span></p>
            <p><span class="font-semibold">Delivery/Removal Time:</span> <span id="detail-time"></span></p>
            <p><span class="font-semibold">Live Load Needed:</span> <span id="detail-live-load"></span></p>
            <p><span class="font-semibold">Is Urgent:</span> <span id="detail-is-urgent"></span></p>
            <p><span class="font-semibold">Driver Instructions:</span> <span id="detail-driver-instructions"></span></p>

            <div id="detail-equipment-section" class="border-t pt-3 mt-3 hidden">
                <h4 class="font-semibold mb-2">Equipment Details:</h4>
                <p><span class="font-medium">Name:</span> <span id="detail-equipment-name"></span></p>
                <p><span class="font-medium">Quantity:</span> <span id="detail-equipment-quantity"></span></p>
                <p><span class="font-medium">Specific Needs:</span> <span id="detail-equipment-specific-needs"></span></p>
                <p><span class="font-medium">Duration Days:</span> <span id="detail-equipment-duration-days"></span></p>
            </div>

            <div id="detail-junk-section" class="border-t pt-3 mt-3 hidden">
                <h4 class="font-semibold mb-2">Junk Removal Details:</h4>
                <p><span class="font-medium">Junk Items:</span></p>
                <ul id="detail-junk-items" class="list-disc list-inside ml-4"></ul>
                <p><span class="font-medium">Recommended Dumpster Size:</span> <span id="detail-recommended-dumpster-size"></span></p>
                <p><span class="font-medium">Additional Comment:</span> <span id="detail-additional-comment"></span></p>
                <div class="mt-4">
                    <p class="font-medium mb-2">Uploaded Media:</p>
                    <div id="detail-media-urls" class="grid grid-cols-2 gap-2"></div>
                </div>
            </div>

            <div id="detail-quoted-info-section" class="border-t pt-3 mt-3 hidden">
                <h4 class="font-semibold mb-2">Quotation Details:</h4>
                <p><span class="font-medium">Quoted Price:</span> <span id="detail-quoted-price" class="text-green-600 font-bold"></span></p>
                <p><span class="font-medium">Admin Notes:</span> <span id="detail-admin-notes" class="whitespace-pre-wrap"></span></p>
            </div>
        </div>
    </div>
</div>

<div id="submit-quote-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-md text-gray-800">
        <h3 class="text-xl font-bold mb-4">Submit Quote for Request #<span id="submit-quote-id"></span></h3>
        <form id="submit-quote-form">
            <input type="hidden" name="quote_id" id="submit-quote-hidden-id">
            <div class="mb-4">
                <label for="quote-price" class="block text-sm font-medium text-gray-700">Quoted Price ($)</label>
                <input type="number" id="quote-price" name="quoted_price" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="admin-notes" class="block text-sm font-medium text-gray-700">Notes for Customer (Optional)</label>
                <textarea id="admin-notes" name="admin_notes" rows="4" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="Add any specific details, terms, or recommendations for the customer."></textarea>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('submit-quote-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Submit Quote</button>
            </div>
        </form>
    </div>
</div>

<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center hidden z-50">
    <button class="absolute top-4 right-4 text-white text-4xl" onclick="hideModal('image-modal')">&times;</button>
    <img id="image-modal-content" src="" class="max-w-full max-h-[90%] object-contain">
</div>

<script>
    // Global function to show image modal (re-used from junk_removal.php for consistency)
    function showImageModal(imageUrl) {
        document.getElementById('image-modal-content').src = imageUrl;
        showModal('image-modal');
    }

    // --- View Quote Details Button Logic ---
    document.querySelectorAll('.view-quote-details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const quoteId = this.dataset.id;
            // Find the corresponding quote data from the PHP-rendered array
            const quoteData = <?php echo json_encode($quotes); ?>.find(q => q.id == quoteId);

            if (quoteData) {
                // Populate the modal with data
                document.getElementById('detail-quote-id').textContent = quoteData.id;
                document.getElementById('detail-customer-name').textContent = quoteData.first_name + ' ' + quoteData.last_name;
                document.getElementById('detail-customer-email').textContent = quoteData.email;
                document.getElementById('detail-service-type').textContent = quoteData.service_type.replace(/_/g, ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                document.getElementById('detail-location').textContent = quoteData.location;
                document.getElementById('detail-created-at').textContent = new Date(quoteData.created_at).toLocaleString();
                document.getElementById('detail-date').textContent = quoteData.delivery_date || quoteData.removal_date || 'N/A';
                document.getElementById('detail-time').textContent = quoteData.delivery_time || quoteData.removal_time || 'N/A';
                document.getElementById('detail-live-load').textContent = quoteData.live_load_needed ? 'Yes' : 'No';
                document.getElementById('detail-is-urgent').textContent = quoteData.is_urgent ? 'Yes' : 'No';
                document.getElementById('detail-driver-instructions').textContent = quoteData.driver_instructions || 'None provided.';


                // Hide/show sections based on service type
                const equipmentSection = document.getElementById('detail-equipment-section');
                const junkSection = document.getElementById('detail-junk-section');
                const quotedInfoSection = document.getElementById('detail-quoted-info-section');


                equipmentSection.classList.add('hidden');
                junkSection.classList.add('hidden');
                quotedInfoSection.classList.add('hidden'); // Always hidden by default, shown if price exists

                if (quoteData.service_type === 'equipment_rental') {
                    equipmentSection.classList.remove('hidden');
                    document.getElementById('detail-equipment-name').textContent = quoteData.equipment_name || 'N/A';
                    document.getElementById('detail-equipment-quantity').textContent = quoteData.quantity || 'N/A';
                    document.getElementById('detail-equipment-specific-needs').textContent = quoteData.specific_needs || 'None';
                    document.getElementById('detail-equipment-duration-days').textContent = quoteData.duration_days || 'N/A';
                } else if (quoteData.service_type === 'junk_removal') {
                    junkSection.classList.remove('hidden');
                    const junkItemsList = document.getElementById('detail-junk-items');
                    junkItemsList.innerHTML = ''; // Clear previous items
                    if (quoteData.junk_items_json && quoteData.junk_items_json.length > 0) {
                        quoteData.junk_items_json.forEach(item => {
                            const li = document.createElement('li');
                            li.textContent = `${item.itemType || 'N/A'} (Qty: ${item.quantity || 'N/A'}, Dims: ${item.estDimensions || 'N/A'}, Weight: ${item.estWeight || 'N/A'})`;
                            junkItemsList.appendChild(li);
                        });
                    } else {
                        junkItemsList.innerHTML = '<li>No specific junk items listed.</li>';
                    }
                    document.getElementById('detail-recommended-dumpster-size').textContent = quoteData.recommended_dumpster_size || 'N/A';
                    document.getElementById('detail-additional-comment').textContent = quoteData.additional_comment || 'None';

                    const mediaUrlsDiv = document.getElementById('detail-media-urls');
                    mediaUrlsDiv.innerHTML = ''; // Clear previous media
                    if (quoteData.media_urls_json && quoteData.media_urls_json.length > 0) {
                        quoteData.media_urls_json.forEach(url => {
                            const colDiv = document.createElement('div');
                            colDiv.classList.add('relative', 'group');
                            const fileExtension = url.split('.').pop().toLowerCase();
                            const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension);

                            if (isImage) {
                                colDiv.innerHTML = `<img src="${url}" alt="Junk item photo" class="w-full h-24 object-cover rounded-md shadow-sm cursor-pointer" onclick="showImageModal('${url}');">`;
                            } else {
                                colDiv.innerHTML = `<video controls src="${url}" class="w-full h-24 object-cover rounded-md shadow-sm"></video>`;
                            }
                             colDiv.innerHTML += `
                                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-md">
                                    <a href="${url}" target="_blank" class="text-white text-xl hover:text-blue-300" title="Open Media">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>`;
                            mediaUrlsDiv.appendChild(colDiv);
                        });
                    } else {
                        mediaUrlsDiv.innerHTML = '<p class="text-gray-600">No media uploaded for this request.</p>';
                    }
                }

                // Show quoted info if available
                if (quoteData.quoted_price) {
                    quotedInfoSection.classList.remove('hidden');
                    document.getElementById('detail-quoted-price').textContent = `$${parseFloat(quoteData.quoted_price).toFixed(2)}`;
                    document.getElementById('detail-admin-notes').textContent = quoteData.admin_notes || 'None provided.';
                } else {
                     quotedInfoSection.classList.add('hidden'); // Ensure it's hidden if no price
                }

                showModal('quote-details-modal');
            } else {
                showToast('Could not retrieve quote details.', 'error');
            }
        });
    });

    // --- Submit Quote Button Logic ---
    document.querySelectorAll('.submit-quote-btn').forEach(button => {
        button.addEventListener('click', function() {
            const quoteId = this.dataset.id;
            document.getElementById('submit-quote-id').textContent = quoteId;
            document.getElementById('submit-quote-hidden-id').value = quoteId;
            // Clear previous values in the form
            document.getElementById('quote-price').value = '';
            document.getElementById('admin-notes').value = ''; // Clear notes field
            showModal('submit-quote-modal');
        });
    });

    // --- Handle Submit Quote Form Submission ---
    const submitQuoteForm = document.getElementById('submit-quote-form');
    if (submitQuoteForm) {
        submitQuoteForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'submit_quote');
            // 'admin_notes' is now part of the form via name attribute
            
            const quotedPrice = document.getElementById('quote-price').value;
            if (parseFloat(quotedPrice) <= 0) {
                showToast('Quoted price must be greater than 0.', 'error');
                return;
            }

            showToast('Submitting quote...', 'info');

            try {
                const response = await fetch('/api/admin/quotes.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message || 'Quote submitted successfully!', 'success'); // Added success toast
                    hideModal('submit-quote-modal');
                    window.loadAdminSection('quotes'); // Reload quotes list to reflect status change
                } else {
                    showToast(result.message || 'Failed to submit quote.', 'error');
                }
            } catch (error) {
                console.error('Submit quote API Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }

    // --- Resend Quote Button Logic ---
    document.querySelectorAll('.resend-quote-btn').forEach(button => {
        button.addEventListener('click', function() {
            const quoteId = this.dataset.id;
            const customerName = this.dataset.customerName;
            const customerEmail = this.dataset.customerEmail;

            showConfirmationModal(
                'Resend Quote',
                `Are you sure you want to resend the quote for #Q${quoteId} to ${customerName} (${customerEmail})?`,
                async (confirmed) => {
                    if (confirmed) {
                        showToast(`Resending quote to ${customerName}...`, 'info');
                        const formData = new FormData();
                        formData.append('action', 'resend_quote');
                        formData.append('quote_id', quoteId);

                        try {
                            const response = await fetch('/api/admin/quotes.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message || 'Quote resent successfully!', 'success');
                            } else {
                                showToast(result.message || 'Failed to resend quote.', 'error');
                            }
                        } catch (error) {
                            console.error('Resend quote API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Resend',
                'bg-blue-600'
            );
        });
    });

    // --- Reject Quote Button Logic ---
    document.querySelectorAll('.reject-quote-btn').forEach(button => {
        button.addEventListener('click', function() {
            const quoteId = this.dataset.id;

            showConfirmationModal(
                'Reject Quote',
                `Are you sure you want to reject quote #Q${quoteId}? This action cannot be undone.`,
                async (confirmed) => {
                    if (confirmed) {
                        showToast(`Rejecting quote #Q${quoteId}...`, 'info');
                        const formData = new FormData();
                        formData.append('action', 'reject_quote');
                        formData.append('quote_id', quoteId);

                        try {
                            const response = await fetch('/api/admin/quotes.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message || 'Quote rejected successfully!', 'success');
                                window.loadAdminSection('quotes'); // Reload quotes list
                            } else {
                                showToast(result.message || 'Failed to reject quote.', 'error');
                            }
                        } catch (error) {
                            console.error('Reject quote API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Reject',
                'bg-red-600'
            );
        });
    });

    // --- View Related Booking Button (links to bookings.php) ---
    document.querySelectorAll('.view-related-booking-btn').forEach(button => {
        button.addEventListener('click', function() {
            const quoteId = this.dataset.quoteId;
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
        });
    });

    // Initial load logic for quote details modal if quote_id is present in URL
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const quoteIdFromUrl = urlParams.get('quote_id');
        if (quoteIdFromUrl) {
            // Simulate click on the 'View' button for the specific quote
            const viewButton = document.querySelector(`.view-quote-details-btn[data-id="${quoteIdFromUrl}"]`);
            if (viewButton) {
                viewButton.click();
            }
        }
    });
</script>