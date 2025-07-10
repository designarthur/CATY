<?php
// customer/pages/bookings.php

// --- Setup & Includes ---
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

// --- Data Fetching ---
// This query now joins with quotes to get the new service charges and also gets the latest status from the history.
$stmt_all_bookings = $conn->prepare("
    SELECT
        b.id, b.booking_number, b.service_type, b.start_date, b.end_date,
        b.delivery_location, b.status,
        q.swap_charge, q.relocation_charge,
        (SELECT COUNT(*) FROM reviews r WHERE r.booking_id = b.id) AS review_count
    FROM bookings b
    LEFT JOIN invoices i ON b.invoice_id = i.id
    LEFT JOIN quotes q ON i.quote_id = q.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt_all_bookings->bind_param("i", $user_id);
$stmt_all_bookings->execute();
$result_all_bookings = $stmt_all_bookings->get_result();
while ($row = $result_all_bookings->fetch_assoc()) {
    // Fetch the status history for each booking
    $history_stmt = $conn->prepare("SELECT status, status_time, notes FROM booking_status_history WHERE booking_id = ? ORDER BY status_time DESC");
    $history_stmt->bind_param("i", $row['id']);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    $row['status_history'] = [];
    while ($history_row = $history_result->fetch_assoc()) {
        $row['status_history'][] = $history_row;
    }
    $history_stmt->close();
    $bookings[] = $row;
}
$stmt_all_bookings->close();
$conn->close();

// --- Helper Functions ---
function getStatusBadgeClass($status) {
    // ... (This function remains the same)
    switch ($status) {
        case 'delivered': case 'completed': return 'bg-green-100 text-green-700';
        case 'in_use': case 'awaiting_pickup': return 'bg-blue-100 text-blue-700';
        case 'cancelled': return 'bg-red-100 text-red-700';
        default: return 'bg-yellow-100 text-yellow-700';
    }
}

function getTimelineIconClass($status) {
    // ... (This function remains the same)
     switch ($status) {
        case 'delivered': case 'completed': return 'fas fa-check-circle text-white bg-green-500';
        case 'in_use': return 'fas fa-star text-white bg-blue-500';
        case 'awaiting_pickup': return 'fas fa-clock text-white bg-pink-500';
        case 'cancelled': return 'fas fa-times-circle text-white bg-red-500';
        default: return 'fas fa-truck text-white bg-yellow-500';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">My Bookings</h1>

<div class="space-y-6">
    <?php if (empty($bookings)): ?>
        <div class="bg-white p-6 rounded-lg shadow-md text-center text-gray-600">
            <p>You have no current or past bookings.</p>
        </div>
    <?php else: ?>
        <?php foreach ($bookings as $booking): ?>
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700 mb-2 md:mb-0">Booking #<?php echo htmlspecialchars($booking['booking_number']); ?></h2>
                    <span class="px-3 py-1 <?php echo getStatusBadgeClass($booking['status']); ?> text-sm font-medium rounded-full"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $booking['status']))); ?></span>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Status Timeline</h3>
                    <ol class="relative border-l border-gray-200">
                        <?php if (empty($booking['status_history'])): ?>
                            <li class="ml-6 mb-4">
                                <span class="absolute flex items-center justify-center w-6 h-6 bg-gray-200 rounded-full -left-3 ring-8 ring-white">
                                    <i class="fas fa-question-circle text-gray-500"></i>
                                </span>
                                <h4 class="text-md font-semibold text-gray-900">No History</h4>
                                <p class="text-sm font-normal text-gray-500">No status history available for this booking yet.</p>
                            </li>
                        <?php else: ?>
                            <?php foreach ($booking['status_history'] as $index => $history): ?>
                                <li class="ml-6 mb-6">
                                    <span class="absolute flex items-center justify-center w-6 h-6 rounded-full -left-3 ring-8 ring-white <?php echo getTimelineIconClass($history['status']); ?>">
                                        <i class="<?php echo getTimelineIconClass($history['status']); ?>"></i>
                                    </span>
                                    <div class="ml-2">
                                        <h4 class="flex items-center text-md font-semibold text-gray-900">
                                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $history['status']))); ?>
                                            <?php if ($index === 0): ?>
                                                <span class="bg-blue-100 text-blue-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded ml-3">Latest</span>
                                            <?php endif; ?>
                                        </h4>
                                        <time class="block mb-2 text-sm font-normal leading-none text-gray-400"><?php echo (new DateTime($history['status_time']))->format('F j, Y, g:i A'); ?></time>
                                        <p class="text-base font-normal text-gray-500"><?php echo htmlspecialchars($history['notes']); ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ol>
                </div>
                <?php if ($booking['status'] === 'delivered' || $booking['status'] === 'in_use'): ?>
                <div class="mt-6 pt-4 border-t border-gray-200">
                     <h3 class="text-lg font-semibold text-gray-700 mb-4">Service Requests for this Booking</h3>
                     <div class="flex flex-wrap gap-4">
                        <button class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 shadow-md request-relocation-btn"
                                data-booking-id="<?php echo $booking['id']; ?>"
                                data-charge="<?php echo htmlspecialchars($booking['relocation_charge'] ?? '0.00'); ?>">
                            <i class="fas fa-truck-loading mr-2"></i>Request Relocation
                        </button>
                        <button class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200 shadow-md request-swap-btn"
                                data-booking-id="<?php echo $booking['id']; ?>"
                                data-charge="<?php echo htmlspecialchars($booking['swap_charge'] ?? '0.00'); ?>">
                            <i class="fas fa-exchange-alt mr-2"></i>Request Swap
                        </button>
                        <button class="flex-1 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors duration-200 shadow-md schedule-pickup-btn"
                                data-booking-id="<?php echo $booking['id']; ?>">
                            <i class="fas fa-calendar-check mr-2"></i>Schedule Pickup
                        </button>
                     </div>
                </div>
                <?php endif; ?>

                <?php if ($booking['status'] === 'completed' && $booking['review_count'] == 0): ?>
                    <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                        <button class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors duration-200 leave-review-btn" data-booking-id="<?php echo $booking['id']; ?>" data-booking-number="<?php echo htmlspecialchars($booking['booking_number']); ?>">
                            <i class="fas fa-star mr-2"></i>Leave a Review
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="relocation-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-md text-gray-800">
        <h3 class="text-xl font-bold mb-4">Request Relocation</h3>
        <p class="mb-4">A one-time charge for this service will be applied: <span class="font-bold text-blue-600" id="relocation-charge-display">$0.00</span></p>
        <form id="relocation-form">
            <input type="hidden" id="relocation-booking-id" name="booking_id">
            <div class="mb-5">
                <label for="relocation-address" class="block text-sm font-medium text-gray-700 mb-2">New Destination Address</label>
                <input type="text" id="relocation-address" name="new_address" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Enter new full address" required>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('relocation-request-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Proceed to Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
// This script assumes that functions like showModal, hideModal, and showToast are globally available.

// Use event delegation for buttons
document.addEventListener('click', function(event) {
    const target = event.target.closest('button');
    if (!target) return;

    // Handle Relocation Request Button
    if (target.classList.contains('request-relocation-btn')) {
        const bookingId = target.dataset.bookingId;
        const charge = parseFloat(target.dataset.charge).toFixed(2);
        
        document.getElementById('relocation-booking-id').value = bookingId;
        document.getElementById('relocation-charge-display').textContent = `$${charge}`;
        document.getElementById('relocation-address').value = ''; // Clear previous input
        
        if (charge > 0) {
            showModal('relocation-request-modal');
        } else {
            // If charge is zero, you might want a simpler confirmation
            showToast('Relocation service is free for this booking. Contact support to arrange.', 'info');
        }
    }

    // Add handlers for swap and pickup buttons similarly...
});

// Handle Relocation Form Submission
document.getElementById('relocation-form').addEventListener('submit', function(event) {
    event.preventDefault();
    const newAddress = document.getElementById('relocation-address').value.trim();
    if (!newAddress) {
        showToast('Please enter a valid new address.', 'error');
        return;
    }
    // In a real application, this would redirect to a payment page or open a payment modal.
    // For this example, we will simulate a successful request.
    hideModal('relocation-request-modal');
    showToast('Relocation request submitted! Please complete the payment for the new invoice created in your dashboard.', 'success');
    // You would then make an API call here to create a new invoice for the relocation charge.
});
</script>