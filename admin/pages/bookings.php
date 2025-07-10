<?php
// admin/pages/bookings.php

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

$bookings = [];
$booking_detail_view_data = null;
$vendors = []; // To populate the vendor dropdown

// Determine current filter status (e.g., 'scheduled', 'completed', 'all')
$filter_status = $_GET['status'] ?? 'all';
$requested_booking_id = $_GET['booking_id'] ?? null;

// Fetch all vendors for the "Assign Vendor" dropdown
$stmt_vendors = $conn->prepare("SELECT id, name FROM vendors WHERE is_active = TRUE ORDER BY name ASC");
$stmt_vendors->execute();
$result_vendors = $stmt_vendors->get_result();
while ($row = $result_vendors->fetch_assoc()) {
    $vendors[] = $row;
}
$stmt_vendors->close();

// Fetch bookings for the list view
$query = "SELECT
            b.id, b.booking_number, b.service_type, b.status, b.start_date, b.end_date, b.delivery_location,
            u.first_name, u.last_name, u.email,
            v.name AS vendor_name
          FROM
            bookings b
          JOIN
            users u ON b.user_id = u.id
          LEFT JOIN
            vendors v ON b.vendor_id = v.id";
$params = [];
$types = "";

if ($filter_status !== 'all') {
    $query .= " WHERE b.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
$query .= " ORDER BY b.created_at DESC";

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


// Fetch specific booking details if an ID is provided
if ($requested_booking_id) {
    $stmt_detail = $conn->prepare("SELECT
                                    b.id, b.booking_number, b.service_type, b.status, b.start_date, b.end_date,
                                    b.delivery_location, b.pickup_location, b.delivery_instructions, b.pickup_instructions,
                                    b.total_price, b.created_at, b.live_load_requested, b.is_urgent,
                                    b.equipment_details, b.junk_details, b.vendor_id,
                                    u.first_name, u.last_name, u.email, u.phone_number, u.address, u.city, u.state, u.zip_code,
                                    inv.invoice_number, inv.amount AS invoice_amount, inv.status AS invoice_status,
                                    q.id AS quote_id, q.quoted_price,
                                    v.name AS vendor_name, v.email AS vendor_email, v.phone_number AS vendor_phone
                                FROM
                                    bookings b
                                JOIN
                                    users u ON b.user_id = u.id
                                LEFT JOIN
                                    invoices inv ON b.invoice_id = inv.id
                                LEFT JOIN
                                    quotes q ON inv.quote_id = q.id
                                LEFT JOIN
                                    vendors v ON b.vendor_id = v.id
                                WHERE
                                    b.id = ?");
    $stmt_detail->bind_param("i", $requested_booking_id);
    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();
    if ($result_detail->num_rows > 0) {
        $booking_detail_view_data = $result_detail->fetch_assoc();
        $booking_detail_view_data['equipment_details'] = json_decode($booking_detail_view_data['equipment_details'], true);
        $booking_detail_view_data['junk_details'] = json_decode($booking_detail_view_data['junk_details'], true);
    }
    $stmt_detail->close();
}

$conn->close();

// Helper function for status badges
function getAdminStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'scheduled': return 'bg-blue-100 text-blue-800';
        case 'assigned': return 'bg-indigo-100 text-indigo-800';
        case 'pickedup': return 'bg-orange-100 text-orange-800';
        case 'out_for_delivery': return 'bg-purple-100 text-purple-800';
        case 'delivered': return 'bg-green-100 text-green-800';
        case 'in_use': return 'bg-teal-100 text-teal-800';
        case 'awaiting_pickup': return 'bg-pink-100 text-pink-800';
        case 'completed': return 'bg-gray-100 text-gray-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        case 'relocated': return 'bg-lime-100 text-lime-800';
        case 'swapped': return 'bg-cyan-100 text-cyan-800';
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
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Location</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#BK-<?php echo htmlspecialchars($booking['booking_number']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['service_type']))); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['vendor_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['delivery_location']); ?></td>
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
                <p class="text-gray-600"><span class="font-medium">Customer Address:</span> <?php echo htmlspecialchars($booking_detail_view_data['address'] . ', ' . $booking_detail_view_data['city'] . ', ' . $booking_detail_view_data['state'] . ' ' . $booking_detail_view_data['zip_code']); ?></p>
                <p class="text-gray-600 mt-2"><a href="#" onclick="loadAdminSection('users', {user_id: '<?php echo $booking_detail_view_data['user_id'] ?? ''; ?>'}); return false;" class="text-blue-600 hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Customer Profile</a></p>
            </div>
            <div>
                <p class="text-gray-600"><span class="font-medium">Service Type:</span> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking_detail_view_data['service_type']))); ?></p>
                <p class="text-gray-600"><span class="font-medium">Current Status:</span> <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo getAdminStatusBadgeClass($booking_detail_view_data['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $booking_detail_view_data['status']))); ?></span></p>
                <p class="text-gray-600"><span class="font-medium">Booking Date:</span> <?php echo (new DateTime($booking_detail_view_data['created_at']))->format('Y-m-d H:i A'); ?></p>
                <p class="text-gray-600"><span class="font-medium">Start Date:</span> <?php echo htmlspecialchars($booking_detail_view_data['start_date']); ?></p>
                <p class="text-gray-600"><span class="font-medium">End Date:</span> <?php echo htmlspecialchars($booking_detail_view_data['end_date'] ?? 'N/A'); ?></p>
                <p class="text-gray-600"><span class="font-medium">Live Load Requested:</span> <?php echo $booking_detail_view_data['live_load_requested'] ? 'Yes' : 'No'; ?></p>
                <p class="text-gray-600"><span class="font-medium">Urgent Request:</span> <?php echo $booking_detail_view_data['is_urgent'] ? 'Yes' : 'No'; ?></p>
            </div>
            <div class="md:col-span-2">
                <p class="text-gray-600"><span class="font-medium">Delivery Location:</span> <?php echo htmlspecialchars($booking_detail_view_data['delivery_location']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Delivery Instructions:</span> <?php echo htmlspecialchars($booking_detail_view_data['delivery_instructions'] ?? 'None provided.'); ?></p>
                <?php if (!empty($booking_detail_view_data['pickup_location'])): ?>
                    <p class="text-gray-600"><span class="font-medium">Pickup Location:</span> <?php echo htmlspecialchars($booking_detail_view_data['pickup_location']); ?></p>
                <?php endif; ?>
                <?php if (!empty($booking_detail_view_data['pickup_instructions'])): ?>
                    <p class="text-gray-600"><span class="font-medium">Pickup Instructions:</span> <?php echo htmlspecialchars($booking_detail_view_data['pickup_instructions'] ?? 'None provided.'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($booking_detail_view_data['service_type'] === 'equipment_rental' && !empty($booking_detail_view_data['equipment_details'])): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Equipment Details</h3>
            <div class="mb-6 pb-6 border-b border-gray-200">
                <ul class="list-disc list-inside text-gray-600 ml-4">
                    <?php foreach ($booking_detail_view_data['equipment_details'] as $item): ?>
                        <li>
                            <span class="font-medium"><?php echo htmlspecialchars($item['equipment_name'] ?? 'N/A'); ?></span>
                            (Quantity: <?php echo htmlspecialchars($item['quantity'] ?? '1'); ?>, Needs: <?php echo htmlspecialchars($item['specific_needs'] ?? 'N/A'); ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif ($booking_detail_view_data['service_type'] === 'junk_removal' && !empty($booking_detail_view_data['junk_details'])): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Junk Removal Details</h3>
            <div class="mb-6 pb-6 border-b border-gray-200">
                <p class="text-gray-600 font-medium mb-2">Junk Items:</p>
                <?php if (!empty($booking_detail_view_data['junk_details']['junkItems'])): ?>
                    <ul class="list-disc list-inside text-gray-600 ml-4 mb-2">
                        <?php foreach ($booking_detail_view_data['junk_details']['junkItems'] as $item): ?>
                            <li><?php echo htmlspecialchars($item['itemType'] ?? 'N/A'); ?> (Qty: <?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?>, Dims: <?php echo htmlspecialchars($item['estDimensions'] ?? 'N/A'); ?>, Weight: <?php echo htmlspecialchars($item['estWeight'] ?? 'N/A'); ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-600 ml-4 mb-2">No specific junk items detailed.</p>
                <?php endif; ?>
                <p class="text-gray-600"><span class="font-medium">Recommended Dumpster Size:</span> <?php echo htmlspecialchars($booking_detail_view_data['junk_details']['recommendedDumpsterSize'] ?? 'N/A'); ?></p>
                <p class="text-gray-600"><span class="font-medium">Additional Comment:</span> <?php echo htmlspecialchars($booking_detail_view_data['junk_details']['additionalComment'] ?? 'None'); ?></p>
            </div>
        <?php endif; ?>

        <h3 class="text-xl font-semibold text-gray-700 mb-4">Billing & Vendor</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-200">
            <div>
                <p class="text-gray-600"><span class="font-medium">Total Price:</span> <span class="text-blue-700 font-semibold">$<?php echo number_format($booking_detail_view_data['total_price'], 2); ?></span></p>
                <p class="text-gray-600"><span class="font-medium">Invoice Number:</span>
                    <?php if (!empty($booking_detail_view_data['invoice_number'])): ?>
                        <a href="#" onclick="loadAdminSection('invoices', {invoice_id: '<?php echo htmlspecialchars($booking_detail_view_data['invoice_number']); ?>'}); return false;" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($booking_detail_view_data['invoice_number']); ?></a>
                        (Status: <span class="<?php echo getAdminStatusBadgeClass($booking_detail_view_data['invoice_status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $booking_detail_view_data['invoice_status']))); ?></span>)
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </p>
                <p class="text-gray-600"><span class="font-medium">Original Quote ID:</span>
                    <?php if (!empty($booking_detail_view_data['quote_id'])): ?>
                        <a href="#" onclick="loadAdminSection('quotes', {quote_id: <?php echo htmlspecialchars($booking_detail_view_data['quote_id']); ?>}); return false;" class="text-blue-600 hover:underline">#Q<?php echo htmlspecialchars($booking_detail_view_data['quote_id']); ?></a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <p class="text-gray-600"><span class="font-medium">Assigned Vendor:</span> <?php echo htmlspecialchars($booking_detail_view_data['vendor_name'] ?? 'Not Assigned'); ?></p>
                <?php if (!empty($booking_detail_view_data['vendor_email'])): ?>
                    <p class="text-gray-600"><span class="font-medium">Vendor Contact:</span> <?php echo htmlspecialchars($booking_detail_view_data['vendor_email']); ?> (Email)</p>
                    <p class="text-gray-600"> <?php echo htmlspecialchars($booking_detail_view_data['vendor_phone']); ?> (Phone)</p>
                <?php endif; ?>
            </div>
        </div>

        <h3 class="text-xl font-semibold text-gray-700 mb-4">Admin Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="booking-status-select" class="block text-sm font-medium text-gray-700 mb-2">Update Status</label>
                <select id="booking-status-select" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                    <option value="pending" <?php echo ($booking_detail_view_data['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="scheduled" <?php echo ($booking_detail_view_data['status'] === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="assigned" <?php echo ($booking_detail_view_data['status'] === 'assigned') ? 'selected' : ''; ?>>Assigned</option>
                    <option value="pickedup" <?php echo ($booking_detail_view_data['status'] === 'pickedup') ? 'selected' : ''; ?>>Picked Up</option>
                    <option value="out_for_delivery" <?php echo ($booking_detail_view_data['status'] === 'out_for_delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                    <option value="delivered" <?php echo ($booking_detail_view_data['status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                    <option value="in_use" <?php echo ($booking_detail_view_data['status'] === 'in_use') ? 'selected' : ''; ?>>In Use</option>
                    <option value="awaiting_pickup" <?php echo ($booking_detail_view_data['status'] === 'awaiting_pickup') ? 'selected' : ''; ?>>Awaiting Pickup</option>
                    <option value="completed" <?php echo ($booking_detail_view_data['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($booking_detail_view_data['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="relocated" <?php echo ($booking_detail_view_data['status'] === 'relocated') ? 'selected' : ''; ?>>Relocated</option>
                    <option value="swapped" <?php echo ($booking_detail_view_data['status'] === 'swapped') ? 'selected' : ''; ?>>Swapped</option>
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
            <div class="md:col-span-2 mt-4 text-right">
                <button class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200" id="cancel-booking-btn" data-id="<?php echo htmlspecialchars($booking_detail_view_data['id']); ?>">Cancel Booking</button>
            </div>
        </div>

    <?php else: ?>
        <p class="text-center text-gray-600">Booking details not found or invalid ID.</p>
    <?php endif; ?>
</div>

<script>
    // Function to show booking details (reloads the section with a parameter)
    function showBookingDetails(bookingId) {
        window.loadAdminSection('bookings', { booking_id: bookingId });
    }

    // Function to hide booking details and show the list
    function hideBookingDetails() {
        window.loadAdminSection('bookings', { status: '<?php echo htmlspecialchars($filter_status); ?>' }); // Maintain current filter
    }

    document.addEventListener('click', function(event) {
        // View Booking Details button
        if (event.target.classList.contains('view-booking-details-btn')) {
            const bookingId = event.target.dataset.id;
            showBookingDetails(bookingId);
        }
    });

    // Update Booking Status
    const updateBookingStatusBtn = document.getElementById('update-booking-status-btn');
    if (updateBookingStatusBtn) {
        updateBookingStatusBtn.addEventListener('click', function() {
            const bookingId = this.dataset.id;
            const newStatus = document.getElementById('booking-status-select').value;

            showConfirmationModal(
                'Update Booking Status',
                `Are you sure you want to change the status of booking #BK-<?php echo htmlspecialchars($booking_detail_view_data['booking_number'] ?? ''); ?> to "${newStatus.replace(/_/g, ' ').toUpperCase()}"?`,
                async (confirmed) => {
                    if (confirmed) {
                        showToast('Updating booking status...', 'info');
                        const formData = new FormData();
                        formData.append('action', 'update_status');
                        formData.append('booking_id', bookingId);
                        formData.append('status', newStatus);

                        try {
                            const response = await fetch('/api/admin/bookings.php', { // This endpoint needs to be created
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message, 'success');
                                window.loadAdminSection('bookings', { booking_id: bookingId }); // Reload details
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Update booking status API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Update Status',
                'bg-blue-600'
            );
        });
    }

    // Assign Vendor
    const assignVendorBtn = document.getElementById('assign-vendor-btn');
    if (assignVendorBtn) {
        assignVendorBtn.addEventListener('click', function() {
            const bookingId = this.dataset.id;
            const newVendorId = document.getElementById('assign-vendor-select').value;
            const newVendorName = document.getElementById('assign-vendor-select').options[document.getElementById('assign-vendor-select').selectedIndex].text;

            if (!newVendorId) {
                showToast('Please select a vendor to assign.', 'warning');
                return;
            }

            showConfirmationModal(
                'Assign Vendor',
                `Are you sure you want to assign booking #BK-<?php echo htmlspecialchars($booking_detail_view_data['booking_number'] ?? ''); ?> to ${newVendorName}?`,
                async (confirmed) => {
                    if (confirmed) {
                        showToast('Assigning vendor...', 'info');
                        const formData = new FormData();
                        formData.append('action', 'assign_vendor');
                        formData.append('booking_id', bookingId);
                        formData.append('vendor_id', newVendorId);

                        try {
                            const response = await fetch('/api/admin/bookings.php', { // This endpoint needs to be created
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message, 'success');
                                window.loadAdminSection('bookings', { booking_id: bookingId }); // Reload details
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Assign vendor API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Assign Vendor',
                'bg-green-600'
            );
        });
    }

    // Cancel Booking (Example - could be a separate status update or distinct action)
    const cancelBookingBtn = document.getElementById('cancel-booking-btn');
    if (cancelBookingBtn) {
        cancelBookingBtn.addEventListener('click', function() {
            const bookingId = this.dataset.id;
            showConfirmationModal(
                'Cancel Booking',
                `Are you sure you want to cancel booking #BK-<?php echo htmlspecialchars($booking_detail_view_data['booking_number'] ?? ''); ?>? This action cannot be undone.`,
                async (confirmed) => {
                    if (confirmed) {
                        showToast('Cancelling booking...', 'info');
                        const formData = new FormData();
                        formData.append('action', 'update_status'); // Re-use update_status
                        formData.append('booking_id', bookingId);
                        formData.append('status', 'cancelled'); // Set status to cancelled

                        try {
                            const response = await fetch('/api/admin/bookings.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message, 'success');
                                window.loadAdminSection('bookings', { booking_id: bookingId }); // Reload details
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Cancel booking API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Cancel Booking',
                'bg-red-600'
            );
        });
    }
</script>