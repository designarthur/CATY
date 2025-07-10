<?php
// customer/pages/bookings.php

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
$bookings = [];
$booking_detail = null; // To hold data for a single booking detail view if requested

// Check if a specific booking ID is requested for detail view
$requested_booking_id = $_GET['booking_id'] ?? null;

if ($requested_booking_id) {
    // Fetch a single booking for detail view
    $stmt_detail = $conn->prepare("SELECT
                                    b.id, b.booking_number, b.service_type, b.start_date, b.end_date,
                                    b.delivery_location, b.pickup_location, b.delivery_instructions, b.pickup_instructions,
                                    b.status, b.total_price, b.created_at, b.live_load_requested, b.is_urgent,
                                    b.equipment_details, b.junk_details,
                                    inv.invoice_number, inv.amount AS invoice_amount, inv.status AS invoice_status,
                                    v.name as vendor_name -- Fetch vendor name
                                FROM
                                    bookings b
                                LEFT JOIN
                                    invoices inv ON b.invoice_id = inv.id
                                LEFT JOIN
                                    vendors v ON b.vendor_id = v.id
                                WHERE
                                    b.user_id = ? AND b.id = ?");
    $stmt_detail->bind_param("ii", $user_id, $requested_booking_id);
    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();
    if ($result_detail->num_rows > 0) {
        $booking_detail = $result_detail->fetch_assoc();
        // Decode JSON fields with null coalescing to prevent deprecation warnings
        $booking_detail['equipment_details'] = json_decode($booking_detail['equipment_details'] ?? '[]', true);
        $booking_detail['junk_details'] = json_decode($booking_detail['junk_details'] ?? '{}', true); // Junk details often an object
    }
    $stmt_detail->close();
}

// If no specific booking ID requested or if ID not found, fetch all bookings for the list
if (!$booking_detail) {
    $stmt_all_bookings = $conn->prepare("SELECT
                                            b.id, b.booking_number, b.service_type, b.start_date, b.end_date,
                                            b.delivery_location, b.status, b.equipment_details, b.junk_details
                                        FROM
                                            bookings b
                                        WHERE
                                            b.user_id = ?
                                        ORDER BY b.created_at DESC");
    $stmt_all_bookings->bind_param("i", $user_id);
    $stmt_all_bookings->execute();
    $result_all_bookings = $stmt_all_bookings->get_result();
    while ($row = $result_all_bookings->fetch_assoc()) {
        // Decode JSON fields for listing with null coalescing
        $row['equipment_details'] = json_decode($row['equipment_details'] ?? '[]', true);
        $row['junk_details'] = json_decode($row['junk_details'] ?? '{}', true); // Junk details often an object
        $bookings[] = $row;
    }
    $stmt_all_bookings->close();
}

$conn->close();

// Function to get status badge classes
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'delivered':
        case 'completed':
            return 'bg-green-100 text-green-700';
        case 'out_for_delivery':
        case 'scheduled':
        case 'pending':
            return 'bg-yellow-100 text-yellow-700';
        case 'in_use':
        case 'awaiting_pickup':
            return 'bg-blue-100 text-blue-700';
        case 'cancelled':
            return 'bg-red-100 text-red-700';
        case 'relocated':
        case 'swapped':
            return 'bg-purple-100 text-purple-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Equipment Bookings</h1>

<div id="booking-list" class="<?php echo $booking_detail ? 'hidden' : ''; ?> space-y-6">
    <?php if (empty($bookings)): ?>
        <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 text-center text-gray-600">
            <p>You have no current or past bookings. Start a new service request via AI chat!</p>
            <button class="mt-4 py-2 px-5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors duration-200 shadow-md" onclick="showAIChat('create-booking');">
                <i class="fas fa-calendar-plus mr-2"></i>New Equipment Booking
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($bookings as $booking):
            $equipment_name = 'N/A';
            $rental_period = 'N/A';
            if ($booking['service_type'] == 'equipment_rental' && !empty($booking['equipment_details'])) {
                // Ensure 'equipment_name' is the correct key if your JSON structure differs
                $eq_names = array_column($booking['equipment_details'], 'equipment_name');
                $equipment_name = htmlspecialchars(implode(', ', $eq_names));
                $rental_period = htmlspecialchars((new DateTime($booking['start_date']))->format('Y-m-d') . ' to ' . (new DateTime($booking['end_date']))->format('Y-m-d'));
            } elseif ($booking['service_type'] == 'junk_removal' && !empty($booking['junk_details']['junkItems'])) {
                $junk_items_names = array_column($booking['junk_details']['junkItems'], 'itemType');
                $equipment_name = 'Junk Removal: ' . htmlspecialchars(implode(', ', $junk_items_names));
                $rental_period = htmlspecialchars((new DateTime($booking['start_date']))->format('Y-m-d')); // For junk removal, end_date might be same or null
            }
        ?>
            <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">Booking #<?php echo htmlspecialchars($booking['booking_number']); ?></h2>
                    <span class="px-3 py-1 <?php echo getStatusBadgeClass($booking['status']); ?> text-sm font-medium rounded-full"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $booking['status']))); ?></span>
                </div>
                <p class="text-gray-600 mb-2"><span class="font-medium">Service Type:</span> <?php echo htmlspecialchars(str_replace('_', ' ', $booking['service_type'])); ?></p>
                <p class="text-gray-600 mb-2"><span class="font-medium">Equipment/Service:</span> <?php echo $equipment_name; ?></p>
                <p class="text-gray-600 mb-2"><span class="font-medium">Date(s):</span> <?php echo $rental_period; ?></p>
                <p class="text-gray-600 mb-4"><span class="font-medium">Location:</span> <?php echo htmlspecialchars($booking['delivery_location']); ?></p>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 show-booking-details" data-booking-id="<?php echo $booking['id']; ?>" data-status="<?php echo htmlspecialchars($booking['status']); ?>">View Details</button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="booking-detail-view" class="<?php echo $booking_detail ? '' : 'hidden'; ?> bg-white p-6 rounded-lg shadow-md border border-blue-200 mt-8">
    <button class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="hideBookingDetails()">
        <i class="fas fa-arrow-left mr-2"></i>Back to Bookings
    </button>
    <?php if ($booking_detail): ?>
        <h2 class="text-2xl font-bold text-gray-800 mb-6" id="detail-booking-number">Booking Details for #<?php echo htmlspecialchars($booking_detail['booking_number']); ?></h2>

        <div class="mb-6 pb-6 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-info-circle mr-2 text-blue-600"></i>Booking Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-600">
                <div><span class="font-medium">Booking Number:</span> <span id="detail-booking-id"><?php echo htmlspecialchars($booking_detail['booking_number']); ?></span></div>
                <div><span class="font-medium">Booking Date:</span> <span id="detail-booking-date"><?php echo (new DateTime($booking_detail['created_at']))->format('Y-m-d'); ?></span></div>
                <div><span class="font-medium">Service Type:</span> <?php echo htmlspecialchars(str_replace('_', ' ', $booking_detail['service_type'])); ?></div>
                <div><span class="font-medium">Vendor:</span> <?php echo htmlspecialchars($booking_detail['vendor_name'] ?? 'Not Assigned'); ?></div>
                <?php if ($booking_detail['service_type'] == 'equipment_rental' && !empty($booking_detail['equipment_details'])): ?>
                    <div><span class="font-medium">Equipment:</span> <span id="detail-equipment-name"><?php echo htmlspecialchars(implode(', ', array_column($booking_detail['equipment_details'], 'equipment_name'))); ?></span></div>
                    <div><span class="font-medium">Quantity:</span> <?php echo htmlspecialchars($booking_detail['equipment_details'][0]['quantity'] ?? '1'); ?></div>
                    <div><span class="font-medium">Specific Needs:</span> <?php echo htmlspecialchars($booking_detail['equipment_details'][0]['specific_needs'] ?? 'N/A'); ?></div>
                    <div><span class="font-medium">Rental Period:</span> <span id="detail-rental-period"><?php echo (new DateTime($booking_detail['start_date']))->format('Y-m-d') . ' to ' . (new DateTime($booking_detail['end_date']))->format('Y-m-d'); ?></span></div>
                <?php elseif ($booking_detail['service_type'] == 'junk_removal' && !empty($booking_detail['junk_details'])): ?>
                     <div><span class="font-medium">Junk Items:</span> <?php echo htmlspecialchars(implode(', ', array_column($booking_detail['junk_details']['junkItems'] ?? [], 'itemType'))); ?></div>
                     <div><span class="font-medium">Recommended Dumpster:</span> <?php echo htmlspecialchars($booking_detail['junk_details']['recommendedDumpsterSize'] ?? 'N/A'); ?></div>
                     <div><span class="font-medium">Additional Comment:</span> <?php echo htmlspecialchars($booking_detail['junk_details']['additionalComment'] ?? 'N/A'); ?></div>
                     <div><span class="font-medium">Removal Date:</span> <?php echo (new DateTime($booking_detail['start_date']))->format('Y-m-d'); ?></div>
                <?php endif; ?>
                <div class="md:col-span-2"><span class="font-medium">Delivery Location:</span> <span id="detail-delivery-location"><?php echo htmlspecialchars($booking_detail['delivery_location']); ?></span></div>
                <?php if (!empty($booking_detail['delivery_instructions'])): ?>
                    <div class="md:col-span-2"><span class="font-medium">Delivery Instructions:</span> <?php echo htmlspecialchars($booking_detail['delivery_instructions']); ?></div>
                <?php endif; ?>
                <?php if (!empty($booking_detail['pickup_location'])): ?>
                    <div class="md:col-span-2"><span class="font-medium">Pickup Location:</span> <?php echo htmlspecialchars($booking_detail['pickup_location']); ?></div>
                <?php endif; ?>
                <?php if (!empty($booking_detail['pickup_instructions'])): ?>
                    <div class="md:col-span-2"><span class="font-medium">Pickup Instructions:</span> <?php echo htmlspecialchars($booking_detail['pickup_instructions']); ?></div>
                <?php endif; ?>
                <div><span class="font-medium">Live Load Requested:</span> <?php echo $booking_detail['live_load_requested'] ? 'Yes' : 'No'; ?></div>
                <div><span class="font-medium">Urgent Request:</span> <?php echo $booking_detail['is_urgent'] ? 'Yes' : 'No'; ?></div>
            </div>
        </div>

        <div class="mb-6 pb-6 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-receipt mr-2 text-green-600"></i>Billing Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-600 mb-4">
                <div><span class="font-medium">Invoice Number:</span> <a href="#invoices" onclick="loadCustomerSection('invoices', {invoice_id: '<?php echo htmlspecialchars($booking_detail['invoice_number']); ?>'}); return false;" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($booking_detail['invoice_number']); ?></a></div>
                <div><span class="font-medium">Payment Method:</span> <span id="detail-payment-method"><?php echo htmlspecialchars($booking_detail['payment_method'] ?? 'N/A'); ?></span></div> </div>
            <div class="space-y-2 text-gray-700">
                <div class="flex justify-between"><span class="font-medium">Total Price:</span> <span class="text-blue-700 font-semibold" id="detail-grand-total">$<?php echo number_format($booking_detail['total_price'], 2); ?></span></div>
                <div class="flex justify-between"><span class="font-medium">Payment Status:</span> <span class="<?php echo getStatusBadgeClass($booking_detail['invoice_status']); ?> font-semibold" id="detail-payment-status"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $booking_detail['invoice_status']))); ?></span></div>
            </div>
        </div>

        <div class="mb-6 pb-6 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-clock mr-2 text-yellow-600"></i>Status Timeline</h3>
            <ol class="relative border-l border-gray-200 ml-4">
                <?php
                // Define the full sequence of possible statuses for a more comprehensive timeline
                $full_timeline_sequence = [
                    'pending' => 'Booking request received and awaiting processing.',
                    'scheduled' => 'Service scheduled with vendor/driver.',
                    'assigned' => 'Your booking has been assigned to a driver.',
                    'pickedup' => 'Equipment has been picked up by the driver.',
                    'out_for_delivery' => 'Your equipment is now out for delivery.',
                    'delivered' => 'Equipment successfully delivered.',
                    'in_use' => 'Equipment is currently in use at your site.',
                    'awaiting_pickup' => 'Pickup requested and awaiting vendor confirmation.',
                    'completed' => 'Service completed and equipment picked up.',
                    'cancelled' => 'Booking cancelled.',
                    'delayed' => 'Delivery/service delayed. Please check for updates.',
                    'relocated' => 'Equipment has been relocated to new address.',
                    'swapped' => 'Equipment has been swapped for a new unit.'
                ];

                $current_status_index = array_search($booking_detail['status'], array_keys($full_timeline_sequence));

                foreach ($full_timeline_sequence as $key => $description) {
                    // Only show statuses up to and including the current one, or if it's 'pending' always show
                    // In a real system, you'd fetch actual timestamps for each status from a `booking_history` table.
                    // For this demo, we'll just show up to the current status and use booking creation time.
                    if (array_search($key, array_keys($full_timeline_sequence)) <= $current_status_index || $key == 'pending') :
                        $is_current_status = ($key == $booking_detail['status']);
                        $event_date_time = (new DateTime($booking_detail['created_at']))->format('M d, Y, h:i A'); // Using booking creation date for simplicity
                ?>
                        <li class="mb-5 ml-6">
                            <span class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -left-3 ring-8 ring-white">
                                <i class="fas fa-check text-blue-500 text-sm"></i>
                            </span>
                            <h4 class="flex items-center mb-1 text-lg font-semibold text-gray-800">
                                <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $key))); ?>
                                <?php echo $is_current_status ? '<span class="bg-blue-100 text-blue-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded ml-3">Current</span>' : ''; ?>
                            </h4>
                            <time class="block mb-2 text-sm font-normal leading-none text-gray-500">
                                <?php echo $event_date_time; ?>
                            </time>
                            <p class="text-base font-normal text-gray-600"><?php echo htmlspecialchars($description); ?></p>
                        </li>
                <?php
                    endif;
                }
                ?>
            </ol>
        </div>

        <div class="space-y-4">
            <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-cogs mr-2 text-purple-600"></i>Additional Features</h3>
            <?php
            $show_driver_features = in_array($booking_detail['status'], ['out_for_delivery', 'scheduled', 'assigned', 'pickedup']);
            $show_post_delivery_features = in_array($booking_detail['status'], ['delivered', 'in_use']);
            ?>
            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg shadow-sm border border-blue-200 <?php echo $show_driver_features ? '' : 'hidden'; ?>" id="driver-tracking-card">
                <p class="text-gray-600 font-medium">Driver Live Location:</p>
                <a href="#" class="text-blue-600 hover:underline"><i class="fas fa-map-marker-alt mr-2"></i>Track Driver</a>
            </div>
            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg shadow-sm border border-blue-200 <?php echo $show_driver_features ? '' : 'hidden'; ?>" id="contact-driver-card">
                <p class="text-gray-600 font-medium">Contact Driver:</p>
                <div class="flex space-x-3">
                    <button class="px-3 py-1 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm">
                        <i class="fas fa-phone mr-1"></i> Call
                    </button>
                    <button class="px-3 py-1 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors duration-200 text-sm">
                        <i class="fas fa-comment-dots mr-1"></i> Chat
                    </button>
                </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg shadow-sm border border-blue-200 <?php echo ($show_driver_features || $show_post_delivery_features) ? '' : 'hidden'; ?>">
                <p class="text-gray-600 font-medium">Delivery Photo Uploads:</p>
                <a href="#" class="text-blue-600 hover:underline"><i class="fas fa-images mr-2"></i>View Photos (<?php echo rand(0,3); ?>)</a>
            </div>
            <?php // Example of additional invoices related to THIS booking (e.g. cleaning fees, late fees) ?>
            <div class="p-4 bg-blue-50 rounded-lg shadow-sm border border-blue-200">
                <p class="text-gray-600 font-medium mb-2">Additional Invoices for Services:</p>
                <ul class="list-disc list-inside text-gray-600 ml-4">
                    <li><a href="#" class="text-blue-600 hover:underline" onclick="loadCustomerSection('invoices', {invoice_id: 'INV-00987'}); return false;">Invoice #INV-00987 (Cleaning) - $50.00</a></li>
                    <li><a href="#" class="text-blue-600 hover:underline" onclick="loadCustomerSection('invoices', {invoice_id: 'INV-00988'}); return false;">Invoice #INV-00988 (Late Return Fee) - $75.00</a></li>
                    <?php if ($booking_detail['invoice_status'] == 'partially_paid'): ?>
                        <li><span class="text-orange-600">Outstanding Balance: $<?php echo number_format($booking_detail['invoice_amount'] - 50.00, 2); ?></span></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div id="booking-service-requests" class="space-y-4 mt-6">
            <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-tools mr-2 text-orange-600"></i>Service Requests for this Booking</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button id="request-relocation-btn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 shadow-md <?php echo $show_post_delivery_features ? '' : 'hidden'; ?>" onclick="showModal('relocation-request-modal')">
                    <i class="fas fa-truck-moving mr-2"></i>Request Relocation
                </button>
                <button id="request-swap-btn" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200 shadow-md <?php echo $show_post_delivery_features ? '' : 'hidden'; ?>" onclick="showModal('swap-request-modal')">
                    <i class="fas fa-sync-alt mr-2"></i>Request Swap
                </button>
                <button id="schedule-pickup-btn" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors duration-200 shadow-md <?php echo $show_post_delivery_features ? '' : 'hidden'; ?>" onclick="showModal('pickup-request-modal')">
                    <i class="fas fa-box-open mr-2"></i>Schedule Pickup
                </button>
            </div>
            <?php if (!$show_post_delivery_features): ?>
                <p id="booking-service-unavailable-msg" class="text-red-500 text-sm mt-2">These services are available once the equipment has been delivered and is in use.</p>
            <?php endif; ?>
        </div>
    <?php else: // No booking found for detail view ?>
        <p class="text-center text-gray-600">Booking details not found or invalid booking ID.</p>
    <?php endif; ?>
</div>

<script>
    // This script runs when the bookings.php content is loaded into the main-content-area
    // It attaches event listeners for the "View Details" buttons.

    document.querySelectorAll('.show-booking-details').forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.dataset.bookingId;
            // Use loadCustomerSection function to load this page again with a booking_id parameter
            // Assuming 'loadCustomerSection' is a global function for your customer dashboard
            window.loadCustomerSection('bookings', { booking_id: bookingId });
        });
    });

    // This function is called by the "Back to Bookings" button.
    // It clears the booking_id parameter and reloads the bookings list.
    function hideBookingDetails() {
        // Assuming 'loadCustomerSection' is a global function for your customer dashboard
        window.loadCustomerSection('bookings'); // Loads the bookings page without a specific ID, showing the list
    }

    // Since `showModal` (and possibly other modal/toast functions)
    // are assumed to be global functions from your main layout or shared JS,
    // they are not redefined here.
</script>