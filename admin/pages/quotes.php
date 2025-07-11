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

$quotes = [];
$quote_detail_view_data = null;
$requested_quote_id = filter_input(INPUT_GET, 'quote_id', FILTER_VALIDATE_INT);

if ($requested_quote_id) {
    // --- Fetch Data for Detail View ---
    $stmt_detail = $conn->prepare("
        SELECT
            q.id, q.service_type, q.status, q.created_at, q.location, q.quoted_price, q.customer_type,
            q.delivery_date, q.delivery_time, q.removal_date, q.removal_time, q.is_urgent, q.live_load_needed,
            q.swap_charge, q.relocation_charge, q.admin_notes, q.quote_details, q.driver_instructions,
            u.first_name, u.last_name, u.email, u.phone_number
        FROM quotes q
        JOIN users u ON q.user_id = u.id
        WHERE q.id = ?
    ");
    $stmt_detail->bind_param("i", $requested_quote_id);
    $stmt_detail->execute();
    $result = $stmt_detail->get_result();
    if ($result->num_rows > 0) {
        $quote_detail_view_data = $result->fetch_assoc();

        // Fetch related details based on service type
        if ($quote_detail_view_data['service_type'] === 'equipment_rental') {
            $stmt_eq = $conn->prepare("SELECT equipment_name, quantity, duration_days, specific_needs FROM quote_equipment_details WHERE quote_id = ?");
            $stmt_eq->bind_param("i", $requested_quote_id);
            $stmt_eq->execute();
            $eq_result = $stmt_eq->get_result();
            $quote_detail_view_data['equipment_details'] = [];
            while ($eq_row = $eq_result->fetch_assoc()) {
                $quote_detail_view_data['equipment_details'][] = $eq_row;
            }
            $stmt_eq->close();
        } elseif ($quote_detail_view_data['service_type'] === 'junk_removal') {
            $stmt_junk = $conn->prepare("SELECT junk_items_json, recommended_dumpster_size, additional_comment, media_urls_json FROM junk_removal_details WHERE quote_id = ?");
            $stmt_junk->bind_param("i", $requested_quote_id);
            $stmt_junk->execute();
            $junk_result = $junk_result->get_result()->fetch_assoc();
            if($junk_result) {
                $quote_detail_view_data['junk_details'] = $junk_result;
                $quote_detail_view_data['junk_details']['junk_items_json'] = json_decode($junk_result['junk_items_json'] ?? '[]', true);
                $quote_detail_view_data['junk_details']['media_urls_json'] = json_decode($junk_result['media_urls_json'] ?? '[]', true);
            }
            $stmt_junk->close();
        }
    }
    $stmt_detail->close();
} else {
    // --- Fetch Data for List View ---
    $query = "
        SELECT
            q.id, q.service_type, q.status, q.created_at, q.location, q.quoted_price,
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
}

$conn->close();

function getAdminStatusBadgeClass($status) {
    // ... same as before
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

<div id="quotes-list-section" class="<?php echo $quote_detail_view_data ? 'hidden' : ''; ?>">
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $quote['quoted_price'] ? '$' . number_format($quote['quoted_price'], 2) : 'N/A'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900 view-quote-details-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="quote-detail-section" class="<?php echo $quote_detail_view_data ? '' : 'hidden'; ?>">
    <?php if ($quote_detail_view_data): ?>
        <button class="mb-6 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="window.loadAdminSection('quotes')">
            <i class="fas fa-arrow-left mr-2"></i>Back to All Quotes
        </button>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Quote #Q<?php echo htmlspecialchars($quote_detail_view_data['id']); ?> Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 mb-6 pb-4 border-b">
                        <div>
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($quote_detail_view_data['first_name'] . ' ' . $quote_detail_view_data['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($quote_detail_view_data['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($quote_detail_view_data['phone_number']); ?></p>
                            <p><strong>Customer Type:</strong> <?php echo htmlspecialchars($quote_detail_view_data['customer_type'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p><strong>Status:</strong> <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getAdminStatusBadgeClass($quote_detail_view_data['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $quote_detail_view_data['status']))); ?></span></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($quote_detail_view_data['location']); ?></p>
                            <p><strong>Requested Date:</strong> <?php echo htmlspecialchars($quote_detail_view_data['delivery_date'] ?? $quote_detail_view_data['removal_date'] ?? 'N/A'); ?></p>
                            <p><strong>Requested Time:</strong> <?php echo htmlspecialchars($quote_detail_view_data['delivery_time'] ?? $quote_detail_view_data['removal_time'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($quote_detail_view_data['service_type'] === 'equipment_rental'): ?>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Equipment Requested</h3>
                        <ul class="list-disc list-inside space-y-3 pl-2">
                            <?php foreach ($quote_detail_view_data['equipment_details'] as $item): ?>
                                <li>
                                    <span class="font-semibold"><?php echo htmlspecialchars($item['quantity']); ?>x <?php echo htmlspecialchars($item['equipment_name']); ?></span>
                                    <?php if (!empty($item['duration_days'])): ?>
                                        for <span class="font-semibold"><?php echo htmlspecialchars($item['duration_days']); ?> days</span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['specific_needs'])): ?>
                                        <p class="text-gray-600 text-sm pl-6"> - Notes: <?php echo htmlspecialchars($item['specific_needs']); ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 sticky top-24">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Actions</h3>
                    <?php if ($quote_detail_view_data['status'] === 'pending'): ?>
                        <form id="submit-quote-form">
                            <input type="hidden" name="quote_id" value="<?php echo htmlspecialchars($quote_detail_view_data['id']); ?>">
                            <div class="mb-4">
                                <label for="quote-price" class="block text-sm font-medium text-gray-700">Main Quoted Price ($)</label>
                                <input type="number" id="quote-price" name="quoted_price" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
                            </div>
                            <div class="mb-4">
                                <label for="daily-rate" class="block text-sm font-medium text-gray-700">Daily Rate (for extensions)</label>
                                <input type="number" id="daily-rate" name="daily_rate" step="0.01" min="0" placeholder="e.g., 25.00" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
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
                            <div class="flex justify-end">
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Submit Quote</button>
                            </div>
                        </form>
                    <?php elseif ($quote_detail_view_data['status'] === 'quoted'): ?>
                        <div class="space-y-3">
                             <button class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 resend-quote-btn" data-id="<?php echo htmlspecialchars($quote_detail_view_data['id']); ?>">Resend Quote</button>
                             <button class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 reject-quote-btn" data-id="<?php echo htmlspecialchars($quote_detail_view_data['id']); ?>">Reject Quote</button>
                        </div>
                    <?php elseif (in_array($quote_detail_view_data['status'], ['accepted', 'converted_to_booking'])): ?>
                        <button class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 view-related-booking-btn" data-quote-id="<?php echo htmlspecialchars($quote_detail_view_data['id']); ?>">View Related Booking</button>
                    <?php else: ?>
                        <p class="text-gray-500">No actions available for this quote status.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <p class="text-red-500 text-center p-8">The requested quote could not be found.</p>
    <?php endif; ?>
</div>

<script>
(function() {
    const contentArea = document.getElementById('main-content-area');
    if (!contentArea) return;

    contentArea.addEventListener('click', function(event) {
        const target = event.target.closest('button');
        if (!target) return;

        if (target.classList.contains('view-quote-details-btn')) {
            const quoteId = target.dataset.id;
            window.loadAdminSection('quotes', { quote_id: quoteId });
        }

        if (target.classList.contains('resend-quote-btn')) {
            const quoteId = target.dataset.id;
            showConfirmationModal(
                'Resend Quote',
                `Are you sure you want to resend the quote notification for #Q${quoteId}?`,
                async (confirmed) => {
                    if (confirmed) { handleQuoteAction('resend_quote', quoteId); }
                },
                'Resend', 'bg-indigo-600'
            );
        }

        if (target.classList.contains('reject-quote-btn')) {
            const quoteId = target.dataset.id;
            showConfirmationModal(
                'Reject Quote',
                `Are you sure you want to reject quote #Q${quoteId}?`,
                async (confirmed) => {
                    if (confirmed) { handleQuoteAction('reject_quote', quoteId); }
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
        });
    }

    async function handleQuoteAction(actionOrFormData, quoteId = null) {
        let formData;
        if (typeof actionOrFormData === 'string') {
            formData = new FormData();
            formData.append('action', actionOrFormData);
            if (quoteId) formData.append('quote_id', quoteId);
        } else {
            formData = actionOrFormData;
        }

        const actionText = formData.get('action').replace(/_/g, ' ');
        showToast(`Processing ${actionText}...`, 'info');

        try {
            const response = await fetch('/api/admin/quotes.php', { method: 'POST', body: formData });
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