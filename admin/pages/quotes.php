<?php
// admin/pages/quotes.php

// --- Setup & Includes ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';

if (!is_logged_in() || !has_role('admin')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

// --- Data Fetching ---
$quotes = [];
// Fetch all necessary data for display and for the modals
$query = "
    SELECT
        q.id, q.service_type, q.status, q.created_at, q.location, q.quoted_price,
        q.swap_charge, q.relocation_charge, q.admin_notes, q.quote_details,
        u.first_name, u.last_name, u.email
    FROM quotes q
    JOIN users u ON q.user_id = u.id
    ORDER BY
        CASE q.status
            WHEN 'pending' THEN 1
            WHEN 'quoted' THEN 2
            ELSE 3
        END,
        q.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $quotes[] = $row;
}
$stmt->close();
$conn->close();

// --- Helper Functions ---
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
    <h2 class="text-xl font-semibold text-gray-700 mb-4"><i class="fas fa-file-invoice mr-2 text-blue-600"></i>All Customer Quotes</h2>

    <?php if (empty($quotes)): ?>
        <p class="text-gray-600 text-center p-4">No quote requests found.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quote ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Price</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($quotes as $quote): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#Q<?php echo htmlspecialchars($quote['id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($quote['first_name'] . ' ' . $quote['last_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getAdminStatusBadgeClass($quote['status']); ?>">
                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $quote['status']))); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $quote['quoted_price'] ? '$' . number_format($quote['quoted_price'], 2) : 'N/A'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php if ($quote['status'] === 'pending'): ?>
                                    <button class="text-green-600 hover:text-green-900 submit-quote-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">Submit Quote</button>
                                <?php elseif ($quote['status'] === 'quoted'): ?>
                                    <button class="text-indigo-600 hover:text-indigo-900 mr-2 resend-quote-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">Resend</button>
                                    <button class="text-red-600 hover:text-red-900 reject-quote-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">Reject</button>
                                <?php elseif (in_array($quote['status'], ['accepted', 'converted_to_booking'])): ?>
                                     <button class="text-purple-600 hover:text-purple-900 view-related-booking-btn" data-quote-id="<?php echo htmlspecialchars($quote['id']); ?>">View Booking</button>
                                <?php else: ?>
                                    <span class="text-gray-400">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="submit-quote-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-md text-gray-800">
        <h3 class="text-xl font-bold mb-4">Submit Quote for Request #<span id="submit-quote-id"></span></h3>
        <form id="submit-quote-form">
            <input type="hidden" name="quote_id" id="submit-quote-hidden-id">
            <div class="mb-4">
                <label for="quote-price" class="block text-sm font-medium text-gray-700">Main Quoted Price ($)</label>
                <input type="number" id="quote-price" name="quoted_price" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="swap-charge" class="block text-sm font-medium text-gray-700">Swap Charge ($)</label>
                    <input type="number" id="swap-charge" name="swap_charge" step="0.01" min="0" placeholder="e.g., 30.00" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
                <div>
                    <label for="relocation-charge" class="block text-sm font-medium text-gray-700">Relocation Charge ($)</label>
                    <input type="number" id="relocation-charge" name="relocation_charge" step="0.01" min="0" placeholder="e.g., 40.00" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
            </div>
            <div class="mb-4 bg-gray-50 p-3 rounded-md border border-gray-200">
                <p class="text-sm font-medium text-gray-700 mb-2">Included Services</p>
                <div class="flex items-center mb-2">
                    <input type="checkbox" id="is_swap_included" name="is_swap_included" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="is_swap_included" class="ml-2 block text-sm text-gray-900">Include one free Swap in this quote</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="is_relocation_included" name="is_relocation_included" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="is_relocation_included" class="ml-2 block text-sm text-gray-900">Include one free Relocation in this quote</label>
                </div>
            </div>
            <div class="mb-4">
                <label for="admin-notes" class="block text-sm font-medium text-gray-700">Notes for Customer (Optional)</label>
                <textarea id="admin-notes" name="admin_notes" rows="4" class="mt-1 p-2 border border-gray-300 rounded-md w-full"></textarea>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('submit-quote-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Submit Quote</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const contentArea = document.getElementById('main-content-area');
    if (!contentArea) return;

    contentArea.addEventListener('click', function(event) {
        const target = event.target;

        if (target.classList.contains('submit-quote-btn')) {
            const quoteId = target.dataset.id;
            document.getElementById('submit-quote-id').textContent = quoteId;
            document.getElementById('submit-quote-hidden-id').value = quoteId;
            document.getElementById('submit-quote-form').reset();
            showModal('submit-quote-modal');
        }

        if (target.classList.contains('resend-quote-btn')) {
            const quoteId = target.dataset.id;
            showConfirmationModal(
                'Resend Quote',
                `Are you sure you want to resend the quote notification for #Q${quoteId}?`,
                async (confirmed) => {
                    if (confirmed) {
                        handleQuoteAction('resend_quote', quoteId);
                    }
                },
                'Resend', 'bg-indigo-600'
            );
        }

        if (target.classList.contains('reject-quote-btn')) {
            const quoteId = target.dataset.id;
            showConfirmationModal(
                'Reject Quote',
                `Are you sure you want to reject quote #Q${quoteId}? This cannot be undone.`,
                async (confirmed) => {
                    if (confirmed) {
                        handleQuoteAction('reject_quote', quoteId);
                    }
                },
                'Reject Quote', 'bg-red-600'
            );
        }
        
        if (target.classList.contains('view-related-booking-btn')) {
             const quoteId = target.dataset.quoteId;
             fetch(`/api/admin/bookings.php?action=get_booking_by_quote_id&quote_id=${quoteId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.booking_id) {
                        window.loadAdminSection('bookings', { booking_id: data.booking_id });
                    } else {
                        showToast(data.message || 'Could not find a booking for this quote.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching booking ID:', error);
                    showToast('An error occurred while trying to find the booking.', 'error');
                });
        }
    });

    const submitQuoteForm = document.getElementById('submit-quote-form');
    if (submitQuoteForm) {
        submitQuoteForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'submit_quote');
            
            if (!formData.get('quoted_price') || parseFloat(formData.get('quoted_price')) <= 0) {
                showToast('Please enter a valid quoted price.', 'error');
                return;
            }
            
            await handleQuoteAction(formData);
            hideModal('submit-quote-modal');
        });
    }

    async function handleQuoteAction(action, quoteId = null) {
        let formData;
        if (typeof action === 'string') {
            formData = new FormData();
            formData.append('action', action);
            if(quoteId) formData.append('quote_id', quoteId);
        } else {
            formData = action;
        }

        const actionText = formData.get('action').replace(/_/g, ' ');
        showToast(`Processing ${actionText}...`, 'info');

        try {
            const response = await fetch('/api/admin/quotes.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                window.loadAdminSection('quotes');
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error(`Error during ${actionText}:`, error);
            showToast('An unexpected error occurred.', 'error');
        }
    }
})();
</script>