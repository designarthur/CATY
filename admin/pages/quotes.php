<?php
// admin/pages/quotes.php

// --- Setup & Includes ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php'; // Required for generate_csrf_token() and getSystemSetting

if (!is_logged_in() || !has_role('admin')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

// Generate CSRF token for this page
generate_csrf_token();
$csrf_token = $_SESSION['csrf_token'];


$quotes = [];
$quote_detail_view_data = null;
$requested_quote_id = filter_input(INPUT_GET, 'quote_id', FILTER_VALIDATE_INT);

// Fetch global tax rate and service fee for use in new quote calculations
$global_tax_rate = getSystemSetting('global_tax_rate') ?? 0;
$global_service_fee = getSystemSetting('global_service_fee') ?? 0;


if ($requested_quote_id) {
    // --- Fetch data for the detail/edit view ---
    $stmt_detail = $conn->prepare("
        SELECT
            q.id, q.service_type, q.status, q.created_at, q.location, q.quoted_price, q.customer_type,
            q.delivery_date, q.delivery_time, q.removal_date, q.removal_time, q.is_urgent, q.live_load_needed,
            q.swap_charge, q.relocation_charge, q.admin_notes, q.quote_details, q.driver_instructions,
            q.discount, q.tax, q.attachment_path, q.daily_rate, q.is_swap_included, q.is_relocation_included,
            u.id as user_id, u.first_name, u.last_name, u.email, u.phone_number
        FROM quotes q
        JOIN users u ON q.user_id = u.id
        WHERE q.id = ?
    ");
    $stmt_detail->bind_param("i", $requested_quote_id);
    $stmt_detail->execute();
    $result = $stmt_detail->get_result();
    if ($result->num_rows > 0) {
        $quote_detail_view_data = $result->fetch_assoc();
        $quote_detail_view_data['is_viewed_by_admin'] = true; // Mark as viewed
        $stmt_mark_viewed = $conn->prepare("UPDATE quotes SET is_viewed_by_admin = 1 WHERE id = ?");
        $stmt_mark_viewed->bind_param("i", $requested_quote_id);
        $stmt_mark_viewed->execute();
        $stmt_mark_viewed->close();


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
            $junk_result = $stmt_junk->get_result()->fetch_assoc();
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
                WHEN 'customer_draft' THEN 0 -- New status, highest priority
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
        case 'customer_draft': return 'bg-gray-200 text-gray-700'; // New status
        default: return 'bg-gray-100 text-gray-700';
    }
}
?>

<div id="quotes-list-section" class="<?php echo $quote_detail_view_data ? 'hidden' : ''; ?>">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Quote Management</h1>
    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-700"><i class="fas fa-file-invoice mr-2 text-blue-600"></i>All Customer Quotes</h2>
             <button id="bulk-delete-quotes-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 shadow-md hidden">
                <i class="fas fa-trash-alt mr-2"></i>Delete Selected
            </button>
        </div>
        <?php if (empty($quotes)): ?>
            <p class="text-gray-600 text-center p-4">No quote requests found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox" id="select-all-quotes" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </th>
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                     <input type="checkbox" class="quote-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" value="<?php echo htmlspecialchars($quote['id']); ?>">
                                </td>
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
                    <div class="flex justify-between items-start">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">Quote #Q<?php echo htmlspecialchars($quote_detail_view_data['id']); ?> Details</h2>
                        <a href="/api/admin/download.php?type=quote&id=<?php echo htmlspecialchars($quote_detail_view_data['id']); ?>" target="_blank" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">
                            <i class="fas fa-file-pdf mr-2"></i>Download PDF
                        </a>
                    </div>
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
                            <p><strong>Live Load Needed:</strong> <?php echo ($quote_detail_view_data['live_load_needed'] ? 'Yes' : 'No'); ?></p>
                            <p><strong>Urgent Request:</strong> <?php echo ($quote_detail_view_data['is_urgent'] ? 'Yes' : 'No'); ?></p>
                            <p><strong>Driver Instructions:</strong> <?php echo htmlspecialchars($quote_detail_view_data['driver_instructions'] ?? 'None provided.'); ?></p>
                        </div>
                    </div>
                    
                    <div id="quote-items-view-mode">
                        <?php if ($quote_detail_view_data['service_type'] === 'equipment_rental'): ?>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Equipment Requested</h3>
                            <?php
                                $rental_start_date = $quote_detail_view_data['delivery_date'] ?? null;
                                $max_duration_days = 0;
                                foreach ($quote_detail_view_data['equipment_details'] as $item) {
                                    if (isset($item['duration_days']) && $item['duration_days'] > $max_duration_days) {
                                        $max_duration_days = $item['duration_days'];
                                    }
                                }
                                $rental_end_date = null;
                                if ($rental_start_date && $max_duration_days > 0) {
                                    try {
                                        $start_dt = new DateTime($rental_start_date);
                                        $end_dt = clone $start_dt; // Clone to avoid modifying original DateTime object
                                        $end_dt->modify("+$max_duration_days days");
                                        $rental_end_date = $end_dt->format('Y-m-d');
                                    } catch (Exception $e) {
                                        error_log("Date calculation error for admin quote ID {$quote_detail_view_data['id']}: " . $e->getMessage());
                                    }
                                }
                            ?>
                            <?php if ($rental_start_date): ?>
                                <p class="text-sm text-gray-700"><strong>Rental Start Date:</strong> <?php echo htmlspecialchars($rental_start_date); ?></p>
                            <?php endif; ?>
                            <?php if ($rental_end_date): ?>
                                <p class="text-sm text-gray-700"><strong>Rental End Date:</strong> <?php echo htmlspecialchars($rental_end_date); ?></p>
                            <?php endif; ?>
                            <?php if ($max_duration_days > 0): ?>
                                <p class="text-sm text-gray-700 mb-4"><strong>Duration:</strong> <?php echo htmlspecialchars($max_duration_days); ?> Days</p>
                            <?php endif; ?>
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
                        <?php elseif ($quote_detail_view_data['service_type'] === 'junk_removal' && !empty($quote_detail_view_data['junk_details'])): ?>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Junk Items Requested</h3>
                            <?php if (!empty($quote_detail_view_data['junk_details']['junk_items_json'])): ?>
                                <div class="overflow-x-auto mb-6">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item Type</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Qty</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Est. Dims</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Est. Wt.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($quote_detail_view_data['junk_details']['junk_items_json'] as $item): ?>
                                                <tr>
                                                    <td class="p-2 text-sm text-gray-900"><?php echo htmlspecialchars($item['itemType'] ?? 'Unknown Item'); ?></td>
                                                    <td class="p-2 text-sm text-gray-600"><?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?></td>
                                                    <td class="p-2 text-sm text-gray-600"><?php echo htmlspecialchars($item['estDimensions'] ?? 'N/A'); ?></td>
                                                    <td class="p-2 text-sm text-gray-600"><?php echo htmlspecialchars($item['estWeight'] ?? 'N/A'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-600 mb-6">No specific junk items listed.</p>
                            <?php endif; ?>
                            <ul class="list-disc list-inside space-y-3 pl-2">
                                <?php if (!empty($quote_detail_view_data['junk_details']['recommended_dumpster_size'])): ?>
                                    <li>Recommended Dumpster Size: <?php echo htmlspecialchars($quote_detail_view_data['junk_details']['recommended_dumpster_size']); ?></li>
                                <?php endif; ?>
                                <?php if (!empty($quote_detail_view_data['junk_details']['additional_comment'])): ?>
                                    <li>Additional Comments: <?php echo htmlspecialchars($quote_detail_view_data['junk_details']['additional_comment']); ?></li>
                                <?php endif; ?>
                            </ul>
                            <?php if (!empty($quote_detail_view_data['junk_details']['media_urls_json'])): ?>
                                <h4 class="text-md font-semibold text-gray-700 mt-4 mb-2">Uploaded Media:</h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                    <?php foreach ($quote_detail_view_data['junk_details']['media_urls_json'] as $media_url): ?>
                                        <?php $fileExtension = pathinfo($media_url, PATHINFO_EXTENSION); ?>
                                        <?php if (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                            <img src="<?php echo htmlspecialchars($media_url); ?>" class="w-full h-24 object-cover rounded-lg cursor-pointer" onclick="showImageModal('<?php echo htmlspecialchars($media_url); ?>')">
                                        <?php elseif (in_array(strtolower($fileExtension), ['mp4', 'webm', 'ogg'])): ?>
                                            <video src="<?php echo htmlspecialchars($media_url); ?>" controls class="w-full h-24 object-cover rounded-lg"></video>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div> <div id="quote-items-edit-mode" class="hidden mt-6 pt-4 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Edit Requested Items</h3>
                        <table class="min-w-full divide-y divide-gray-200 mb-4" id="editable-quote-items-table">
                            <thead>
                                </thead>
                            <tbody>
                                </tbody>
                        </table>
                        <button type="button" id="add-item-to-quote-btn" class="mb-4 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm hover:bg-blue-200">
                            <i class="fas fa-plus-circle mr-2"></i>Add Item
                        </button>
                        <div class="flex justify-end space-x-3 mt-4">
                            <button id="save-item-details-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                                <i class="fas fa-save mr-2"></i>Save Item Details
                            </button>
                            <button id="cancel-item-edit-btn" class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">
                                Cancel
                            </button>
                        </div>
                    </div> <?php if ($quote_detail_view_data['status'] === 'pending' || $quote_detail_view_data['status'] === 'customer_draft'): ?>
                        <div class="flex justify-end mt-4">
                            <button id="edit-quote-items-toggle-btn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-md">
                                <i class="fas fa-edit mr-2"></i>Edit Items
                            </button>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 sticky top-24">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Actions</h3>
                    <?php if (in_array($quote_detail_view_data['status'], ['pending', 'customer_draft'])): ?>
                        <form id="submit-quote-form" enctype="multipart/form-data">
                            <input type="hidden" name="quote_id" value="<?php echo htmlspecialchars($quote_detail_view_data['id']); ?>">
                            <input type="hidden" name="service_type" value="<?php echo htmlspecialchars($quote_detail_view_data['service_type']); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <?php if ($quote_detail_view_data['service_type'] === 'equipment_rental'): ?>
                                <div class="mb-4">
                                    <label for="quote-price" class="block text-sm font-medium text-gray-700">Base Price ($)</label>
                                    <input type="number" id="quote-price" name="quoted_price" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($quote_detail_view_data['quoted_price'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-4">
                                    <label for="daily-rate" class="block text-sm font-medium text-gray-700">Daily Rate (for extensions) ($)</label>
                                    <input type="number" id="daily-rate" name="daily_rate" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($quote_detail_view_data['daily_rate'] ?? ''); ?>">
                                </div>
                                <div class="mb-4">
                                    <label for="relocation-charge" class="block text-sm font-medium text-gray-700">Relocation Charge ($)</label>
                                    <input type="number" id="relocation-charge" name="relocation_charge" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($quote_detail_view_data['relocation_charge'] ?? ''); ?>">
                                    <div class="flex items-center mt-2">
                                        <input type="checkbox" id="is-relocation-included" name="is_relocation_included" class="h-4 w-4 text-blue-600 border-gray-300 rounded" <?php echo ($quote_detail_view_data['is_relocation_included'] ?? false) ? 'checked' : ''; ?>>
                                        <label for="is-relocation-included" class="ml-2 block text-sm text-gray-900">Include in Base Price</label>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="swap-charge" class="block text-sm font-medium text-gray-700">Swap Charge ($)</label>
                                    <input type="number" id="swap-charge" name="swap_charge" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($quote_detail_view_data['swap_charge'] ?? ''); ?>">
                                    <div class="flex items-center mt-2">
                                        <input type="checkbox" id="is-swap-included" name="is_swap_included" class="h-4 w-4 text-blue-600 border-gray-300 rounded" <?php echo ($quote_detail_view_data['is_swap_included'] ?? false) ? 'checked' : ''; ?>>
                                        <label for="is-swap-included" class="ml-2 block text-sm text-gray-900">Include in Base Price</label>
                                    </div>
                                </div>
                            <?php elseif ($quote_detail_view_data['service_type'] === 'junk_removal'): ?>
                                <div class="mb-4">
                                    <label for="total-cost" class="block text-sm font-medium text-gray-700">Total Cost ($)</label>
                                    <input type="number" id="total-cost" name="total_cost" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($quote_detail_view_data['quoted_price'] ?? ''); ?>" required>
                                </div>
                            <?php endif; ?>

                            <div class="mb-4">
                                <label for="discount" class="block text-sm font-medium text-gray-700">Discount ($)</label>
                                <input type="number" id="discount" name="discount" step="0.01" min="0" placeholder="e.g., 50.00" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($quote_detail_view_data['discount'] ?? ''); ?>">
                            </div>
                            <div class="mb-4">
                                <label for="tax" class="block text-sm font-medium text-gray-700">Tax ($)</label>
                                <input type="number" id="tax" name="tax" step="0.01" min="0" placeholder="e.g., 15.00" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($quote_detail_view_data['tax'] ?? $global_tax_rate); ?>">
                            </div>
                            <div class="mb-4">
                                <label for="attachment" class="block text-sm font-medium text-gray-700">Attach File (Optional)</label>
                                <input type="file" id="attachment" name="attachment" class="mt-1 p-2 border border-gray-300 rounded-md w-full text-sm">
                                <?php if (!empty($quote_detail_view_data['attachment_path'])): ?>
                                    <p class="text-xs text-gray-500 mt-1">Current: <a href="<?php echo htmlspecialchars($quote_detail_view_data['attachment_path']); ?>" target="_blank" class="text-blue-500 hover:underline">View Attachment</a></p>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <label for="admin_notes" class="block text-sm font-medium text-gray-700">Admin Notes</label>
                                <textarea id="admin_notes" name="admin_notes" rows="3" class="mt-1 p-2 border border-gray-300 rounded-md w-full"><?php echo htmlspecialchars($quote_detail_view_data['admin_notes'] ?? ''); ?></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Submit Quote</button>
                            </div>
                        </form>
                    <?php elseif ($quote_detail_view_data['status'] === 'quoted' || $quote_detail_view_data['status'] === 'accepted' || $quote_detail_view_data['status'] === 'converted_to_booking'): ?>
                        <div class="space-y-3">
                            <h4 class="text-lg font-bold text-gray-800 mb-2">Our Quotation:</h4>
                            <p class="text-gray-700 mb-2"><span class="font-medium">Quoted Price:</span> <span class="text-green-600 text-xl font-bold">$<?php echo number_format($quote_detail_view_data['quoted_price'], 2); ?></span></p>
                            <?php if (!empty($quote_detail_view_data['daily_rate']) && $quote_detail_view_data['daily_rate'] > 0): ?>
                                <p class="text-gray-700 mb-2"><span class="font-medium">Daily Rate:</span> $<?php echo number_format($quote_detail_view_data['daily_rate'], 2); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($quote_detail_view_data['relocation_charge']) && $quote_detail_view_data['relocation_charge'] > 0): ?>
                                <p class="text-gray-700 mb-2"><span class="font-medium">Relocation Charge:</span> $<?php echo number_format($quote_detail_view_data['relocation_charge'], 2); ?> (<?php echo ($quote_detail_view_data['is_relocation_included'] ?? false) ? 'Included' : 'Extra'; ?>)</p>
                            <?php endif; ?>
                            <?php if (!empty($quote_detail_view_data['swap_charge']) && $quote_detail_view_data['swap_charge'] > 0): ?>
                                <p class="text-gray-700 mb-2"><span class="font-medium">Swap Charge:</span> $<?php echo number_format($quote_detail_view_data['swap_charge'], 2); ?> (<?php echo ($quote_detail_view_data['is_swap_included'] ?? false) ? 'Included' : 'Extra'; ?>)</p>
                            <?php endif; ?>
                            <?php if (!empty($quote_detail_view_data['discount']) && $quote_detail_view_data['discount'] > 0): ?>
                                <p class="text-gray-700 mb-2"><span class="font-medium">Discount:</span> -$<?php echo number_format($quote_detail_view_data['discount'], 2); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($quote_detail_view_data['tax']) && $quote_detail_view_data['tax'] > 0): ?>
                                <p class="text-gray-700 mb-2"><span class="font-medium">Tax:</span> $<?php echo number_format($quote_detail_view_data['tax'], 2); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($quote_detail_view_data['admin_notes'])): ?>
                                <p class="text-gray-700 mb-4"><span class="font-medium">Admin Notes:</span> <?php echo nl2br(htmlspecialchars($quote_detail_view_data['admin_notes'])); ?></p>
                            <?php endif; ?>

                             <button class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 resend-quote-btn" data-id="<?php echo htmlspecialchars($quote_detail_view_data['id']); ?>">Resend Quote</button>
                             <button class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 reject-quote-btn" data-id="<?php echo htmlspecialchars($quote_detail_view_data['id']); ?>">Reject Quote</button>
                        </div>
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

<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center hidden z-50">
    <button class="absolute top-4 right-4 text-white text-4xl" onclick="hideModal('image-modal')">&times;</button>
    <img id="image-modal-content" src="" class="max-w-full max-h-[90%] object-contain">
</div>

<script>
(function() {
    // These elements need to be accessed consistently.
    const quotesListSection = document.getElementById('quotes-list-section');
    const quoteDetailSection = document.getElementById('quote-detail-section');
    const selectAllCheckbox = document.getElementById('select-all-quotes');
    const bulkDeleteBtn = document.getElementById('bulk-delete-quotes-btn');

    function toggleBulkDeleteButton() {
        const quoteCheckboxes = document.querySelectorAll('.quote-checkbox');
        const selectedCount = Array.from(quoteCheckboxes).filter(cb => cb.checked).length;
        if (bulkDeleteBtn) { // Ensure button exists
            if (selectedCount > 0) {
                bulkDeleteBtn.classList.remove('hidden');
            } else {
                bulkDeleteBtn.classList.add('hidden');
            }
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.quote-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            toggleBulkDeleteButton();
        });
    }

    // Attach listeners to dynamically loaded content via delegation
    document.body.addEventListener('change', function(event) {
        if (event.target.classList.contains('quote-checkbox')) {
            if (selectAllCheckbox && !event.target.checked) {
                selectAllCheckbox.checked = false;
            }
            toggleBulkDeleteButton();
        }
    });

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.quote-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) {
                window.showToast('Please select at least one quote to delete.', 'warning');
                return;
            }

            window.showConfirmationModal(
                'Delete Selected Quotes',
                `Are you sure you want to delete ${selectedIds.length} selected quote(s)? This action cannot be undone and will delete related bookings.`,
                async (confirmed) => {
                    if (confirmed) {
                        const formData = new FormData();
                        formData.append('action', 'delete_bulk');
                        selectedIds.forEach(id => formData.append('quote_ids[]', id));
                        
                        await handleQuoteAction(formData);
                    }
                },
                'Delete Selected', 'bg-red-600'
            );
        });
    }


    document.body.addEventListener('click', function(event) {
        const target = event.target.closest('button');
        if (!target) return;

        if (target.classList.contains('view-quote-details-btn')) {
            const quoteId = target.dataset.id;
            window.loadAdminSection('quotes', { quote_id: quoteId });
        }

        if (target.classList.contains('resend-quote-btn')) {
            const quoteId = target.dataset.id;
            window.showConfirmationModal(
                'Resend Quote',
                `Are you sure you want to resend the quote notification for #Q${quoteId}?`,
                async (confirmed) => {
                    if (confirmed) { 
                        const formData = new FormData();
                        formData.append('action', 'resend_quote');
                        formData.append('quote_id', quoteId);
                        await handleQuoteAction(formData);
                    }
                },
                'Resend', 'bg-indigo-600'
            );
        }

        if (target.classList.contains('reject-quote-btn')) {
            const quoteId = target.dataset.id;
            window.showConfirmationModal(
                'Reject Quote',
                `Are you sure you want to reject quote #Q${quoteId}?`,
                async (confirmed) => {
                    if (confirmed) { 
                        const formData = new FormData();
                        formData.append('action', 'reject_quote');
                        formData.append('quote_id', quoteId);
                        await handleQuoteAction(formData);
                    }
                }
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
                        window.showToast(data.message || 'Could not find a booking for this quote.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching booking ID for quote:', error);
                    window.showToast('An error occurred while trying to find the booking.', 'error');
                });
        }

        // Image modal for junk removal media
        if (event.target.tagName === 'IMG' && event.target.closest('#quote-detail-section .grid.gap-2')) {
            showImageModal(event.target.src);
        }
    });

    // Image modal function
    function showImageModal(imageUrl) {
        document.getElementById('image-modal-content').src = imageUrl;
        window.showModal('image-modal');
    }


    // --- New JavaScript for GUI-based Editing and Conditional Pricing ---
    const quoteItemsViewMode = document.getElementById('quote-items-view-mode');
    const quoteItemsEditMode = document.getElementById('quote-items-edit-mode');
    const editQuoteItemsToggleBtn = document.getElementById('edit-quote-items-toggle-btn');
    const saveItemDetailsBtn = document.getElementById('save-item-details-btn');
    const cancelItemEditBtn = document.getElementById('cancel-item-edit-btn');
    const addItemToQuoteBtn = document.getElementById('add-item-to-quote-btn');
    const editableQuoteItemsTableBody = document.getElementById('editable-quote-items-table')?.querySelector('tbody');
    const serviceType = "<?php echo htmlspecialchars($quote_detail_view_data['service_type'] ?? ''); ?>"; // Get service type from PHP

    let originalQuoteItems = []; // To store the original state for 'Cancel'


    function renderEditableItems(items) {
        if (!editableQuoteItemsTableBody) return;
        editableQuoteItemsTableBody.innerHTML = ''; // Clear existing rows
        if (serviceType === 'equipment_rental') {
            items.forEach(item => addEquipmentItemRow(item));
        } else if (serviceType === 'junk_removal') {
            items.forEach(item => addJunkItemRow(item));
        }
    }

    function addEquipmentItemRow(item = {}) {
        if (!editableQuoteItemsTableBody) return;
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td class="p-2"><input type="text" class="w-full p-2 border rounded" value="${item.equipment_name ?? ''}" placeholder="e.g., 10-yard dumpster" required></td>
            <td class="p-2"><input type="number" class="w-full p-2 border rounded" value="${item.quantity ?? 1}" min="1" required></td>
            <td class="p-2"><input type="number" class="w-full p-2 border rounded" value="${item.duration_days ?? ''}" min="1" placeholder="Days"></td>
            <td class="p-2"><input type="text" class="w-full p-2 border rounded" value="${item.specific_needs ?? ''}" placeholder="e.g., soft ground delivery"></td>
            <td class="p-2 text-center"><button type="button" class="text-red-500 hover:text-red-700 remove-item-row-btn">&times;</button></td>
        `;
        editableQuoteItemsTableBody.appendChild(newRow);
        newRow.querySelector('.remove-item-row-btn')?.addEventListener('click', (e) => e.target.closest('tr').remove());
    }

    function addJunkItemRow(item = {}) {
        if (!editableQuoteItemsTableBody) return;
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td class="p-2"><input type="text" class="w-full p-2 border rounded" value="${item.itemType ?? ''}" placeholder="e.g., Old Sofa" required></td>
            <td class="p-2"><input type="number" class="w-full p-2 border rounded" value="${item.quantity ?? 1}" min="1" required></td>
            <td class="p-2"><input type="text" class="w-full p-2 border rounded" value="${item.estDimensions ?? ''}" placeholder="e.g., 6x3x3 ft"></td>
            <td class="p-2"><input type="text" class="w-full p-2 border rounded" value="${item.estWeight ?? ''}" placeholder="e.g., 100 lbs"></td>
            <td class="p-2 text-center"><button type="button" class="text-red-500 hover:text-red-700 remove-item-row-btn">&times;</button></td>
        `;
        editableQuoteItemsTableBody.appendChild(newRow);
        newRow.querySelector('.remove-item-row-btn')?.addEventListener('click', (e) => e.target.closest('tr').remove());
    }

    function collectEditedItems() {
        const items = [];
        if (!editableQuoteItemsTableBody) return items;
        editableQuoteItemsTableBody.querySelectorAll('tr').forEach(row => {
            const inputs = row.querySelectorAll('input');
            if (serviceType === 'equipment_rental') {
                items.push({
                    equipment_name: inputs[0].value.trim(),
                    quantity: parseInt(inputs[1].value) || 1,
                    duration_days: parseInt(inputs[2].value) || null,
                    specific_needs: inputs[3].value.trim()
                });
            } else if (serviceType === 'junk_removal') {
                items.push({
                    itemType: inputs[0].value.trim(),
                    quantity: parseInt(inputs[1].value) || 1,
                    estDimensions: inputs[2].value.trim(),
                    estWeight: inputs[3].value.trim()
                });
            }
        });
        return items;
    }

    // Toggle Edit Mode for Items
    if (editQuoteItemsToggleBtn) {
        editQuoteItemsToggleBtn.addEventListener('click', () => {
            quoteItemsViewMode.classList.add('hidden');
            quoteItemsEditMode.classList.remove('hidden');
            editQuoteItemsToggleBtn.classList.add('hidden'); // Hide toggle button
            saveItemDetailsBtn.classList.remove('hidden'); // Show save button
            cancelItemEditBtn.classList.remove('hidden'); // Show cancel button
            addItemToQuoteBtn.classList.remove('hidden'); // Show add item button

            // Populate editable table with current data
            const currentItems = serviceType === 'equipment_rental' ? 
                                 <?php echo json_encode($quote_detail_view_data['equipment_details'] ?? []); ?> : 
                                 <?php echo json_encode($quote_detail_view_data['junk_details']['junk_items_json'] ?? []); ?>;
            originalQuoteItems = JSON.parse(JSON.stringify(currentItems)); // Deep copy for cancel
            renderEditableItems(currentItems);

            // Dynamically set table header based on serviceType if it wasn't done by PHP directly
            const tableHeaderRow = document.getElementById('editable-quote-items-table').querySelector('thead tr');
            if (tableHeaderRow) { // Clear and rebuild header if it wasn't pre-set
                tableHeaderRow.innerHTML = '';
                if (serviceType === 'equipment_rental') {
                    tableHeaderRow.innerHTML = `
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Equipment Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Qty</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Duration (Days)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Specific Needs</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16"></th>
                    `;
                } else if (serviceType === 'junk_removal') {
                    tableHeaderRow.innerHTML = `
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item Type</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Qty</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Est. Dims</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Est. Wt.</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16"></th>
                    `;
                }
            }
        });
    }

    if (cancelItemEditBtn) {
        cancelItemEditBtn.addEventListener('click', () => {
            // Reload the section to effectively cancel edits and revert to the saved state
            const currentQuoteId = <?php echo htmlspecialchars($quote_detail_view_data['id'] ?? 'null'); ?>;
            if (currentQuoteId) {
                window.loadAdminSection('quotes', { quote_id: currentQuoteId });
            } else {
                window.loadAdminSection('quotes');
            }
        });
    }

    if (addItemToQuoteBtn) {
        addItemToQuoteBtn.addEventListener('click', () => {
            if (serviceType === 'equipment_rental') {
                addEquipmentItemRow();
            } else if (serviceType === 'junk_removal') {
                addJunkItemRow();
            }
        });
    }

    if (saveItemDetailsBtn) {
        saveItemDetailsBtn.addEventListener('click', async () => {
            const editedItems = collectEditedItems();
            const quoteId = <?php echo htmlspecialchars($quote_detail_view_data['id'] ?? 'null'); ?>;

            if (editedItems.some(item => (serviceType === 'equipment_rental' && !item.equipment_name.trim()) || 
                                         (serviceType === 'junk_removal' && !item.itemType.trim()))) {
                window.showToast('Item Name/Type cannot be empty for any item.', 'error');
                return;
            }
            if (editedItems.length === 0) {
                 window.showToast('Please add at least one item.', 'error');
                 return;
            }

            window.showConfirmationModal(
                'Save Item Details',
                'Are you sure you want to save these changes to the requested items?',
                async (confirmed) => {
                    if (confirmed) {
                        window.showToast('Saving item details...', 'info');
                        const formData = new FormData();
                        formData.append('action', 'update_items_only'); // New action for backend API
                        formData.append('quote_id', quoteId);
                        formData.append('service_type', serviceType);
                        formData.append('items', JSON.stringify(editedItems)); // Send items as JSON string
                        formData.append('csrf_token', '<?php echo htmlspecialchars($csrf_token); ?>');

                        try {
                            const response = await fetch('/api/admin/quotes.php', { // Use existing quotes API
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                window.showToast(result.message, 'success');
                                // Reload details view to show updated items in view mode
                                window.loadAdminSection('quotes', { quote_id: quoteId });
                            } else {
                                window.showToast(result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Save item details API Error:', error);
                            window.showToast('An error occurred while saving item details. Please try again.', 'error');
                        }
                    }
                },
                'Save',
                'bg-green-600'
            );
        });
    }


    const submitQuoteForm = document.getElementById('submit-quote-form');
    if (submitQuoteForm) {
        submitQuoteForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent default form submission.
            const formData = new FormData(this); // Collect all current form data.
            formData.append('action', 'submit_quote');
            
            // Add current item details from the page's PHP data to the form data
            // This is crucial if the items were edited but not "saved" yet, or if they are from view mode.
            // For simplicity in this implementation, we assume if items are being edited, they are saved first.
            // A more robust solution might collect the *currently displayed* items here whether in view or edit.
            // For now, it relies on the item details being correctly loaded from the PHP backend.

            // Get the current items from quote_detail_view_data (which reflects the last saved state)
            const currentServiceType = "<?php echo htmlspecialchars($quote_detail_view_data['service_type'] ?? ''); ?>";
            let itemsToSubmit = [];
            // Check if the current items are defined and not empty, then pass them
            <?php 
                if (isset($quote_detail_view_data['equipment_details'])) {
                    echo "if (currentServiceType === 'equipment_rental') { itemsToSubmit = " . json_encode($quote_detail_view_data['equipment_details']) . "; }";
                }
                if (isset($quote_detail_view_data['junk_details']['junk_items_json'])) {
                    echo "else if (currentServiceType === 'junk_removal') { itemsToSubmit = " . json_encode($quote_detail_view_data['junk_details']['junk_items_json']) . "; }";
                }
            ?>
            formData.append('items', JSON.stringify(itemsToSubmit)); // Send items as a JSON string


            if (currentServiceType === 'equipment_rental') {
                if (!formData.get('quoted_price') || parseFloat(formData.get('quoted_price')) <= 0) {
                    window.showToast('Please enter a valid base price for equipment rental.', 'error');
                    return;
                }
            } else if (currentServiceType === 'junk_removal') {
                if (!formData.get('total_cost') || parseFloat(formData.get('total_cost')) <= 0) {
                    window.showToast('Please enter a valid total cost for junk removal.', 'error');
                    return;
                }
            }

            window.showConfirmationModal(
                'Submit Quote',
                `Are you sure you want to submit this quote?`,
                async (confirmed) => {
                    if (confirmed) { await handleQuoteAction(formData); }
                },
                'Submit', 'bg-blue-600'
            );
        });
    }

    async function handleQuoteAction(formData) {
        const actionText = formData.get('action').replace(/_/g, ' ');
        window.showToast(`Processing ${actionText}...`, 'info');

        try {
            const response = await fetch('/api/admin/quotes.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                window.showToast(result.message, 'success');
                window.loadAdminSection('quotes', { quote_id: formData.get('quote_id') }); // Reload specific quote after action
            } else {
                window.showToast(result.message, 'error');
            }
        } catch (error) {
            console.error(`Error during ${actionText}:`, error);
            window.showToast('An unexpected error occurred.', 'error');
        }
    }
})();
</script>