<?php
// admin/pages/bookings.php

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
$bookings = [];
$booking_detail_view_data = null;
$vendors = [];
$filter_status = $_GET['status'] ?? 'all';
$requested_booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);

// Set a default timezone to ensure consistent date calculations
date_default_timezone_set('UTC');

// Fetch all vendors for the "Assign Vendor" dropdown
$stmt_vendors = $conn->prepare("SELECT id, name FROM vendors WHERE is_active = TRUE ORDER BY name ASC");
$stmt_vendors->execute();
$result_vendors = $stmt_vendors->get_result();
while ($row = $result_vendors->fetch_assoc()) {
    $vendors[] = $row;
}
$stmt_vendors->close();

// Fetch a single booking's details if an ID is provided
if ($requested_booking_id) {
    $stmt_detail = $conn->prepare("
        SELECT
            b.id, b.booking_number, b.service_type, b.status, b.start_date, b.end_date,
            b.delivery_location, b.pickup_location, b.delivery_instructions, b.pickup_instructions,
            b.total_price, b.created_at, b.live_load_requested, b.is_urgent,
            b.equipment_details, b.junk_details, b.vendor_id,
            u.id as user_id, u.first_name, u.last_name, u.email, u.phone_number, u.address, u.city, u.state, u.zip_code,
            inv.invoice_number, inv.amount AS invoice_amount, inv.status AS invoice_status,
            q.id AS quote_id, q.daily_rate,
            v.name AS vendor_name, v.email AS vendor_email, v.phone_number AS vendor_phone,
            ext.id AS extension_request_id, ext.requested_days AS extension_requested_days, ext.status AS extension_status
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN invoices inv ON b.invoice_id = inv.id
        LEFT JOIN quotes q ON inv.quote_id = q.id
        LEFT JOIN vendors v ON b.vendor_id = v.id
        LEFT JOIN booking_extension_requests ext ON b.id = ext.booking_id AND ext.status = 'pending'
        WHERE b.id = ?
    ");
    $stmt_detail->bind_param("i", $requested_booking_id);
    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();
    if ($result_detail->num_rows > 0) {
        $booking_detail_view_data = $result_detail->fetch_assoc();
        $booking_detail_view_data['equipment_details'] = json_decode($booking_detail_view_data['equipment_details'] ?? '[]', true);
        $booking_detail_view_data['junk_details'] = json_decode($booking_detail_view_data['junk_details'] ?? '{}', true);

        // Safely calculate remaining days, handling potential NULL end_date
        if ($booking_detail_view_data && 
            in_array($booking_detail_view_data['status'], ['delivered', 'in_use', 'awaiting_pickup']) && 
            !empty($booking_detail_view_data['end_date'])) { // The !empty check prevents processing if it's null or empty string
             try {
                $endDate = new DateTime($booking_detail_view_data['end_date']);
                $today = new DateTime('today');
                if ($endDate >= $today) {
                    $interval = $today->diff($endDate);
                    $booking_detail_view_data['remaining_days'] = $interval->days;
                } else {
                    $booking_detail_view_data['remaining_days'] = 0;
                }
            } catch (Exception $e) {
                // Log the exception if needed, and set a fallback
                error_log("DateTime conversion error for booking ID {$requested_booking_id}: {$e->getMessage()}");
                $booking_detail_view_data['remaining_days'] = 'N/A';
            }
        } else {
            $booking_detail_view_data['remaining_days'] = 'N/A'; // No remaining days if status is not applicable or end_date is missing
        }
    }
    $stmt_detail->close();
} else {
    // Fetch all bookings for the list view
    $query = "
        SELECT
            b.id, b.booking_number, b.service_type, b.status, b.start_date,
            u.first_name, u.last_name, v.name AS vendor_name,
            (SELECT COUNT(*) FROM booking_extension_requests ext WHERE ext.booking_id = b.id AND ext.status = 'pending') AS pending_extensions
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN vendors v ON b.vendor_id = v.id
    ";
    $params = [];
    $types = "";

    if ($filter_status !== 'all') {
        $query .= " WHERE b.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    $query .= " ORDER BY pending_extensions DESC, b.created_at DESC";

    $stmt_list = $conn->prepare($query);
    if (!empty($params)) {
        $stmt_list->bind_param($types, ...$params);
    }
    $stmt_list->execute();
    $result_list = $stmt_list->get_result();
    while ($row = $result_list->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt_list->close();
}

$conn->close();

// --- Helper Functions ---
function getAdminStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'scheduled': return 'bg-blue-100 text-blue-800';
        case 'assigned': return 'bg-indigo-100 text-indigo-800';
        case 'out_for_delivery': return 'bg-purple-100 text-purple-800';
        case 'delivered': return 'bg-green-100 text-green-800';
        case 'in_use': return 'bg-teal-100 text-teal-800';
        case 'awaiting_pickup': return 'bg-pink-100 text-pink-800';
        case 'completed': return 'bg-gray-100 text-gray-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-700';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Booking Management</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 <?php echo $booking_detail_view_data ? 'hidden' : ''; ?>" id="bookings-list-section">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-book-open mr-2 text-blue-600"></i>All System Bookings</h2>

    <div class="mb-4 flex flex-wrap gap-2">
        <button class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $filter_status === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>" onclick="loadAdminSection('bookings', {status: 'all'})">All</button>
        <button class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $filter_status === 'pending' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>" onclick="loadAdminSection('bookings', {status: 'pending'})">Pending</button>
        <button class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $filter_status === 'scheduled' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>" onclick="loadAdminSection('bookings', {status: 'scheduled'})">Scheduled</button>
        <button class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $filter_status === 'assigned' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>" onclick="loadAdminSection('bookings', {status: 'assigned'})">Assigned</button>
        <button class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $filter_status === 'out_for_delivery' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>" onclick="loadAdminSection('bookings', {status: 'out_for_delivery'})">Out for Delivery</button>
        <button class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $filter_status === 'delivered' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>" onclick="loadAdminSection('bookings', {status: 'delivered'})">Delivered</button>
        <button class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $filter_status === 'completed' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>" onclick="loadAdminSection('bookings', {status: 'completed'})">Completed</button>
        <button class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $filter_status === 'cancelled' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>" onclick="loadAdminSection('bookings', {status: 'cancelled'})">Cancelled</button>
    </div>

    <?php if (empty($bookings)): ?>
        <p class="text-gray-600 text-center p-4">No bookings found for this filter.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Booking ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Service Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Vendor</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($bookings as $booking): ?>
                        <tr class="<?php echo $booking['pending_extensions'] > 0 ? 'bg-yellow-50' : '' ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #BK-<?php echo htmlspecialchars($booking['booking_number']); ?>
                                <?php if ($booking['pending_extensions'] > 0): ?>
                                    <span class="ml-2 text-yellow-600" title="Pending Extension Request"><i class="fas fa-hourglass-half"></i></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['service_type']))); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['vendor_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($booking['start_date']))->format('Y-m-d'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getAdminStatusBadgeClass($booking['status']); ?>">
                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $booking['status']))); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button class="text-blue-600 hover:text-blue-900 view-booking-details-btn" data-id="<?php echo htmlspecialchars($booking['id']); ?>">View</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="booking-detail-view" class="bg-white p-6 rounded-lg shadow-md border border-blue-200 mt-8 <?php echo $booking_detail_view_data ? '' : 'hidden'; ?>">
    <button class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="hideBookingDetails()">
        <i class="fas fa-arrow-left mr-2"></i>Back to Bookings
    </button>
    <?php if ($booking_detail_view_data): ?>
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Booking #BK-<?php echo htmlspecialchars($booking_detail_view_data['booking_number']); ?> Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-200">
            <div>
                <p class="text-gray-600"><span class="font-medium">Customer:</span> <?php echo htmlspecialchars($booking_detail_view_data['first_name'] . ' ' . $booking_detail_view_data['last_name']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Customer Email:</span> <?php echo htmlspecialchars($booking_detail_view_data['email']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Customer Phone:</span> <?php echo htmlspecialchars($booking_detail_view_data['phone_number']); ?></p>
                <p class="text-gray-600 mt-2"><a href="#" onclick="loadAdminSection('users', {user_id: '<?php echo $booking_detail_view_data['user_id'] ?? ''; ?>'}); return false;" class="text-blue-600 hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Customer Profile</a></p>
            </div>
            <div>
                <p class="text-gray-600"><span class="font-medium">Service Type:</span> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking_detail_view_data['service_type']))); ?></p>
                <p class="text-gray-600"><span class="font-medium">Current Status:</span> <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo getAdminStatusBadgeClass($booking_detail_view_data['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $booking_detail_view_data['status']))); ?></span></p>
                <p class="text-gray-600"><span class="font-medium">Start Date:</span> <?php echo htmlspecialchars($booking_detail_view_data['start_date']); ?></p>
                 <p class="text-gray-600"><span class="font-medium">End Date:</span> <?php echo htmlspecialchars($booking_detail_view_data['end_date'] ?? 'N/A'); ?>
                    <?php if (isset($booking_detail_view_data['remaining_days']) && $booking_detail_view_data['remaining_days'] !== 'N/A'): // Only show if remaining_days is calculated and not 'N/A' ?>
                        <span class="font-bold <?php echo $booking_detail_view_data['remaining_days'] < 3 ? 'text-red-500' : 'text-green-500'; ?>">
                            (<?php echo $booking_detail_view_data['remaining_days']; ?> days remaining)
                        </span>
                    <?php endif; ?>
                 </p>
            </div>
        </div>

        <h3 class="text-xl font-semibold text-gray-700 mb-4">Admin Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="booking-status-select" class="block text-sm font-medium text-gray-700 mb-2">Update Status</label>
                <select id="booking-status-select" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                     <option value="pending" <?php echo ($booking_detail_view_data['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="scheduled" <?php echo ($booking_detail_view_data['status'] === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="assigned" <?php echo ($booking_detail_view_data['status'] === 'assigned') ? 'selected' : ''; ?>>Assigned</option>
                    <option value="out_for_delivery" <?php echo ($booking_detail_view_data['status'] === 'out_for_delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                    <option value="delivered" <?php echo ($booking_detail_view_data['status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                    <option value="in_use" <?php echo ($booking_detail_view_data['status'] === 'in_use') ? 'selected' : ''; ?>>In Use</option>
                    <option value="awaiting_pickup" <?php echo ($booking_detail_view_data['status'] === 'awaiting_pickup') ? 'selected' : ''; ?>>Awaiting Pickup</option>
                    <option value="completed" <?php echo ($booking_detail_view_data['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($booking_detail_view_data['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200" id="update-booking-status-btn" data-id="<?php echo htmlspecialchars($booking_detail_view_data['id']); ?>">Update Status</button>
            </div>
             <div>
                <label for="assign-vendor-select" class="block text-sm font-medium text-gray-700 mb-2">Assign Vendor</label>
                <select id="assign-vendor-select" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                    <option value="">-- Select Vendor --</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?php echo htmlspecialchars($vendor['id']); ?>"
                            <?php echo ($booking_detail_view_data['vendor_id'] == $vendor['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vendor['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="mt-3 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200" id="assign-vendor-btn" data-id="<?php echo htmlspecialchars($booking_detail_view_data['id']); ?>">Assign Vendor</button>
            </div>
            <div>
                 <label class="block text-sm font-medium text-gray-700 mb-2">Additional Charges</label>
                 <button class="mt-1 w-full px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors duration-200" id="add-charge-btn" data-id="<?php echo htmlspecialchars($booking_detail_view_data['id']); ?>">
                    <i class="fas fa-plus-circle mr-2"></i>Add Charge
                 </button>
            </div>
            <div>
                 <label class="block text-sm font-medium text-gray-700 mb-2">Rental Extension</label>
                 <?php if($booking_detail_view_data['extension_request_id']): ?>
                    <button class="mt-1 w-full px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors duration-200 animate-pulse" id="approve-extension-btn" 
                        data-id="<?php echo htmlspecialchars($booking_detail_view_data['id']); ?>"
                        data-request-id="<?php echo htmlspecialchars($booking_detail_view_data['extension_request_id']); ?>"
                        data-requested-days="<?php echo htmlspecialchars($booking_detail_view_data['extension_requested_days']); ?>"
                        data-daily-rate="<?php echo htmlspecialchars($booking_detail_view_data['daily_rate'] ?? '0.00'); ?>">
                        <i class="fas fa-calendar-plus mr-2"></i>Approve Extension Request
                    </button>
                 <?php else: ?>
                    <p class="text-sm text-gray-500 mt-2">No pending extension request from the customer.</p>
                 <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <p class="text-center text-gray-600">Booking details not found or invalid ID.</p>
    <?php endif; ?>
</div>

<div id="add-charge-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg text-gray-800">
        <h3 class="text-xl font-bold mb-4">Add Additional Charge</h3>
        <form id="add-charge-form">
            <input type="hidden" name="booking_id" id="add-charge-booking-id">
            <div class="mb-4">
                <label for="charge-type" class="block text-sm font-medium text-gray-700">Charge Type</label>
                <select id="charge-type" name="charge_type" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
                    <option value="">Select a type</option>
                    <option value="tonnage_overage">Tonnage Overage</option>
                    <option value="rental_extension">Rental Extension</option>
                    <option value="damage_fee">Damage Fee</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="charge-amount" class="block text-sm font-medium text-gray-700">Amount ($)</label>
                <input type="number" id="charge-amount" name="amount" step="0.01" min="0.01" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="charge-description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="charge-description" name="description" rows="3" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="e.g., Overage: 1.5 tons @ $50/ton" required></textarea>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('add-charge-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Generate Invoice</button>
            </div>
        </form>
    </div>
</div>

<div id="approve-extension-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg text-gray-800">
        <h3 class="text-xl font-bold mb-4">Approve Rental Extension</h3>
        <form id="approve-extension-form">
            <input type="hidden" name="booking_id" id="approve-extension-booking-id">
            <input type="hidden" name="extension_request_id" id="approve-extension-request-id">
            <div class="mb-4">
                <label for="extension-days" class="block text-sm font-medium text-gray-700">Extension Days (Requested)</label>
                <input type="number" id="extension-days" name="extension_days" min="1" class="mt-1 p-2 border border-gray-300 rounded-md w-full bg-gray-100" readonly>
            </div>
            <div class="mb-4">
                 <label for="extension-price-option" class="block text-sm font-medium text-gray-700">Pricing Method</label>
                 <select id="extension-price-option" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                     <option value="daily_rate">Use Daily Rate</option>
                     <option value="custom_total">Set Custom Total Price</option>
                 </select>
            </div>
            <div class="mb-4" id="daily-rate-section">
                <label for="daily-rate" class="block text-sm font-medium text-gray-700">Daily Rate ($)</label>
                <input type="number" id="daily-rate" name="daily_rate" step="0.01" min="0.01" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="mb-4 hidden" id="custom-total-section">
                 <label for="custom-total-price" class="block text-sm font-medium text-gray-700">Custom Total Price ($)</label>
                 <input type="number" id="custom-total-price" name="custom_total_price" step="0.01" min="0.01" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>

            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-5 text-center">
                <p class="text-sm text-yellow-700">Total Extension Cost:</p>
                <p id="extension-total-cost" class="text-2xl font-bold text-yellow-800">$0.00</p>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('approve-extension-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-semibold">Approve & Generate Invoice</button>
            </div>
        </form>
    </div>
</div>


<script>
    function showBookingDetails(bookingId) {
        window.loadAdminSection('bookings', { booking_id: bookingId });
    }

    function hideBookingDetails() {
        const currentParams = new URLSearchParams(window.location.search);
        const statusFilter = currentParams.get('status') || 'all';
        window.loadAdminSection('bookings', { status: statusFilter });
    }

    document.addEventListener('click', function(event) {
        const target = event.target.closest('button');
        if (!target) return;

        if (target.classList.contains('view-booking-details-btn')) {
            const bookingId = target.dataset.id;
            showBookingDetails(bookingId);
        }

        if (target.id === 'update-booking-status-btn') {
            const bookingId = target.dataset.id;
            const statusSelect = document.getElementById('booking-status-select');
            const newStatus = statusSelect.value;
            const newStatusText = statusSelect.options[statusSelect.selectedIndex].text;

            showConfirmationModal(
                'Confirm Status Change',
                `Are you sure you want to change the status to "${newStatusText}"?`,
                async (confirmed) => {
                    if (confirmed) {
                        showToast('Updating status...', 'info');
                        const formData = new FormData();
                        formData.append('action', 'update_status');
                        formData.append('booking_id', bookingId);
                        formData.append('status', newStatus);

                        try {
                            const response = await fetch('/api/admin/bookings.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            if (result.success) {
                                showToast(result.message, 'success');
                                showBookingDetails(bookingId);
                            } else {
                                showToast('Error: ' + result.message, 'error');
                            }
                        } catch (error) {
                            showToast('An unexpected error occurred.', 'error');
                            console.error('Update status error:', error);
                        }
                    }
                },
                'Update Status',
                'bg-blue-600'
            );
        }

        if (target.id === 'assign-vendor-btn') {
            const bookingId = target.dataset.id;
            const vendorSelect = document.getElementById('assign-vendor-select');
            const newVendorId = vendorSelect.value;

            if (!newVendorId) {
                showToast('Please select a vendor.', 'warning');
                return;
            }

            showConfirmationModal(
                'Confirm Vendor Assignment',
                'Are you sure you want to assign this vendor to the booking?',
                async (confirmed) => {
                    if(confirmed) {
                        showToast('Assigning vendor...', 'info');
                         const formData = new FormData();
                         formData.append('action', 'assign_vendor');
                         formData.append('booking_id', bookingId);
                         formData.append('vendor_id', newVendorId);

                         try {
                            const response = await fetch('/api/admin/bookings.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            if(result.success){
                                showToast(result.message, 'success');
                                showBookingDetails(bookingId);
                            } else {
                                showToast('Error: ' + result.message, 'error');
                            }
                         } catch(error) {
                            showToast('An unexpected error occurred.', 'error');
                         }
                    }
                },
                'Assign Vendor',
                'bg-green-600'
            );
        }

        if (target.id === 'add-charge-btn') {
            const bookingId = target.dataset.id;
            document.getElementById('add-charge-booking-id').value = bookingId;
            document.getElementById('add-charge-form').reset();
            showModal('add-charge-modal');
        }

        if (target.id === 'approve-extension-btn') {
            const bookingId = target.dataset.id;
            const dailyRate = target.dataset.dailyRate;
            const requestedDays = target.dataset.requestedDays;
            const requestId = target.dataset.requestId;

            document.getElementById('approve-extension-booking-id').value = bookingId;
            document.getElementById('approve-extension-request-id').value = requestId;
            document.getElementById('extension-days').value = requestedDays;
            document.getElementById('daily-rate').value = dailyRate;
            
            // Trigger calculation
            const dailyRateInput = document.getElementById('daily-rate');
            const event = new Event('input', { bubbles: true, cancelable: true });
            dailyRateInput.dispatchEvent(event);

            showModal('approve-extension-modal');
        }
    });

    const addChargeForm = document.getElementById('add-charge-form');
    if (addChargeForm) {
        addChargeForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add_charge');

            if (!formData.get('charge_type') || !formData.get('amount') || !formData.get('description')) {
                showToast('All fields are required.', 'error');
                return;
            }

            showToast('Adding charge and generating invoice...', 'info');

            try {
                const response = await fetch('/api/admin/bookings.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    hideModal('add-charge-modal');
                    showBookingDetails(formData.get('booking_id'));
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            } catch (error) {
                showToast('An unexpected error occurred.', 'error');
            }
        });
    }

    const approveExtensionForm = document.getElementById('approve-extension-form');
    if (approveExtensionForm) {
        const daysInput = document.getElementById('extension-days');
        const rateInput = document.getElementById('daily-rate');
        const customPriceInput = document.getElementById('custom-total-price');
        const costDisplay = document.getElementById('extension-total-cost');
        const pricingOption = document.getElementById('extension-price-option');
        const dailyRateSection = document.getElementById('daily-rate-section');
        const customTotalSection = document.getElementById('custom-total-section');

        function calculateExtensionCost() {
            if (pricingOption.value === 'daily_rate') {
                const days = parseInt(daysInput.value) || 0;
                const rate = parseFloat(rateInput.value) || 0;
                costDisplay.textContent = `$${(days * rate).toFixed(2)}`;
            } else {
                 const customPrice = parseFloat(customPriceInput.value) || 0;
                 costDisplay.textContent = `$${customPrice.toFixed(2)}`;
            }
        }
        
        pricingOption.addEventListener('change', function() {
            if(this.value === 'daily_rate'){
                dailyRateSection.classList.remove('hidden');
                customTotalSection.classList.add('hidden');
            } else {
                dailyRateSection.classList.add('hidden');
                customTotalSection.classList.remove('hidden');
            }
            calculateExtensionCost();
        });

        daysInput.addEventListener('input', calculateExtensionCost);
        rateInput.addEventListener('input', calculateExtensionCost);
        customPriceInput.addEventListener('input', calculateExtensionCost);

        approveExtensionForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'approve_extension');
            formData.append('pricing_option', pricingOption.value);

            if (!formData.get('extension_days')) {
                showToast('Extension days are required.', 'error');
                return;
            }

            showToast('Approving extension and generating invoice...', 'info');
            try {
                const response = await fetch('/api/admin/bookings.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    hideModal('approve-extension-modal');
                    showBookingDetails(formData.get('booking_id'));
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            } catch (error) {
                showToast('An unexpected error occurred.', 'error');
            }
        });
    }

</script>
