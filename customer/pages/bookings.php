<?php
// customer/pages/bookings.php

// --- Setup & Includes ---
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php'; // For CSRF token

if (!is_logged_in()) {
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_detail = null;
$bookings_list = [];

// Check if a specific booking ID is requested for the detail view
$requested_booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);

if ($requested_booking_id) {
    // --- Fetch Data for Detail View ---
    $stmt = $conn->prepare("
        SELECT
            b.id, b.booking_number, b.service_type, b.start_date, b.status,
            q.swap_charge, q.relocation_charge
        FROM bookings b
        LEFT JOIN invoices i ON b.invoice_id = i.id
        LEFT JOIN quotes q ON i.quote_id = q.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->bind_param("ii", $requested_booking_id, $user_id);
    $stmt->execute();
    $booking_detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($booking_detail) {
        // Fetch the status history for the timeline
        $history_stmt = $conn->prepare("SELECT status, status_time, notes FROM booking_status_history WHERE booking_id = ? ORDER BY status_time DESC, id DESC");
        $history_stmt->bind_param("i", $booking_detail['id']);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        $booking_detail['status_history'] = [];
        while ($history_row = $history_result->fetch_assoc()) {
            $booking_detail['status_history'][] = $history_row;
        }
        $history_stmt->close();
    }
} else {
    // --- Fetch Data for List View ---
    $stmt = $conn->prepare("SELECT id, booking_number, service_type, start_date, status FROM bookings WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookings_list[] = $row;
    }
    $stmt->close();
}

$conn->close();
generate_csrf_token(); // Generate CSRF token for the forms

// --- Helper Functions ---
function getStatusBadgeClass($status) { /* ... same as before ... */ }
function getTimelineIconClass($status) { /* ... same as before ... */ }
?>

<div id="booking-list-view" class="<?php echo $requested_booking_id ? 'hidden' : ''; ?>">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">My Bookings</h1>
    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
        <?php if (empty($bookings_list)): ?>
            <p class="text-center text-gray-500 py-4">You have no bookings yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($bookings_list as $booking): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($booking['booking_number']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['service_type']))); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($booking['start_date']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['status']))); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                    <button class="text-blue-600 hover:underline view-details-btn" data-booking-id="<?php echo $booking['id']; ?>">View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>


<div id="booking-detail-view" class="<?php echo $requested_booking_id ? '' : 'hidden'; ?>">
    <?php if ($booking_detail): ?>
        <button class="mb-6 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 back-to-list-btn">
            <i class="fas fa-arrow-left mr-2"></i>Back to All Bookings
        </button>
        <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Booking #<?php echo htmlspecialchars($booking_detail['booking_number']); ?></h1>
            <p class="mb-6 text-lg text-gray-500">Current Status:
                <span class="font-semibold text-blue-600"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking_detail['status']))); ?></span>
            </p>

            <div class="mb-8">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Status Timeline</h3>
                <ol class="relative border-l-2 border-blue-200">
                    <?php foreach ($booking_detail['status_history'] as $history): ?>
                    <li class="ml-6 mb-8">
                        <span class="absolute flex items-center justify-center w-8 h-8 bg-blue-500 rounded-full -left-4 ring-4 ring-white">
                            <i class="fas fa-check text-white"></i>
                        </span>
                        <div class="ml-4">
                            <h4 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $history['status']))); ?></h4>
                            <time class="block mb-1 text-sm font-normal text-gray-400"><?php echo (new DateTime($history['status_time']))->format('F j, Y, g:i A'); ?></time>
                            <p class="text-base font-normal text-gray-500"><?php echo htmlspecialchars($history['notes']); ?></p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </div>

            <?php if (in_array($booking_detail['status'], ['delivered', 'in_use'])): ?>
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Service Requests</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button class="w-full px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 shadow-md request-relocation-btn"
                            data-booking-id="<?php echo $booking_detail['id']; ?>"
                            data-charge="<?php echo htmlspecialchars($booking_detail['relocation_charge'] ?? '0.00'); ?>">
                        <i class="fas fa-truck-loading mr-2"></i>Request Relocation
                    </button>
                    <button class="w-full px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200 shadow-md request-swap-btn"
                            data-booking-id="<?php echo $booking_detail['id']; ?>"
                            data-charge="<?php echo htmlspecialchars($booking_detail['swap_charge'] ?? '0.00'); ?>">
                        <i class="fas fa-exchange-alt mr-2"></i>Request Swap
                    </button>
                    <button class="w-full px-4 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors duration-200 shadow-md schedule-pickup-btn"
                            data-booking-id="<?php echo $booking_detail['id']; ?>">
                        <i class="fas fa-calendar-check mr-2"></i>Schedule Pickup
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="text-center text-red-500 py-8">The requested booking was not found.</p>
    <?php endif; ?>
</div>

<div id="relocation-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-md text-gray-800">
        <h3 class="text-xl font-bold mb-4">Request Relocation</h3>
        <p class="mb-4">A one-time charge for this service will be applied: <span class="font-bold text-blue-600" id="relocation-charge-display">$0.00</span></p>
        <form id="relocation-form">
            <input type="hidden" name="action" value="request_relocation">
            <input type="hidden" name="booking_id" id="relocation-booking-id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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

<div id="swap-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-md text-gray-800">
        <h3 class="text-xl font-bold mb-4">Request Swap</h3>
        <p class="mb-6">A one-time charge of <span class="font-bold text-purple-600" id="swap-charge-display">$0.00</span> will be applied to swap your equipment. Are you sure you want to proceed?</p>
        <form id="swap-form">
            <input type="hidden" name="action" value="request_swap">
            <input type="hidden" name="booking_id" id="swap-booking-id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('swap-request-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Yes, Proceed to Payment</button>
            </div>
        </form>
    </div>
</div>

<div id="pickup-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-md text-gray-800">
        <h3 class="text-xl font-bold mb-4">Schedule Pickup</h3>
        <p class="mb-4 text-sm text-gray-600">Please select your preferred date and time for pickup. Our team will confirm availability.</p>
        <form id="pickup-form">
            <input type="hidden" name="action" value="schedule_pickup">
            <input type="hidden" name="booking_id" id="pickup-booking-id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-5">
                <label for="pickup-date" class="block text-sm font-medium text-gray-700 mb-2">Preferred Date</label>
                <input type="date" id="pickup-date" name="pickup_date" class="w-full p-3 border border-gray-300 rounded-lg" required>
            </div>
             <div class="mb-5">
                <label for="pickup-time" class="block text-sm font-medium text-gray-700 mb-2">Preferred Time</label>
                <input type="time" id="pickup-time" name="pickup_time" class="w-full p-3 border border-gray-300 rounded-lg" required>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('pickup-request-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">Schedule Pickup</button>
            </div>
        </form>
    </div>
</div>


<script>
(function() {
    // This script runs when the bookings page is loaded.
    const contentArea = document.getElementById('content-area');
    if (!contentArea) return;

    // --- Navigation ---
    const handleNavigation = (bookingId) => {
        const params = bookingId ? { booking_id: bookingId } : {};
        window.loadCustomerSection('bookings', params);
    };

    // --- API Call Helper ---
    const callBookingApi = async (form) => {
        const formData = new FormData(form);
        const actionText = (formData.get('action') || 'action').replace(/_/g, ' ');
        showToast(`Submitting ${actionText}...`, 'info');

        try {
            const response = await fetch('/api/customer/bookings.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                // If a new invoice was created, redirect to pay it
                if (result.invoice_id) {
                    window.loadCustomerSection('invoices', { invoice_id: result.invoice_id });
                } else {
                    // Otherwise, just reload the current booking view
                    handleNavigation(formData.get('booking_id'));
                }
            } else {
                showToast(result.message || `Failed to submit ${actionText}.`, 'error');
            }
        } catch (error) {
            console.error('API Error:', error);
            showToast('An unexpected error occurred.', 'error');
        } finally {
             // Hide any open modals
            hideModal('relocation-request-modal');
            hideModal('swap-request-modal');
            hideModal('pickup-request-modal');
        }
    };

    // --- Event Listeners using Delegation ---
    contentArea.addEventListener('click', function(event) {
        const target = event.target.closest('button');
        if (!target) return;

        // View Details button from the list
        if (target.classList.contains('view-details-btn')) {
            handleNavigation(target.dataset.bookingId);
        }
        // Back to List button from the detail view
        if (target.classList.contains('back-to-list-btn')) {
            handleNavigation(null);
        }
        
        const bookingId = target.dataset.bookingId;
        const charge = parseFloat(target.dataset.charge || '0').toFixed(2);

        // Relocation Button
        if (target.classList.contains('request-relocation-btn')) {
            document.getElementById('relocation-booking-id').value = bookingId;
            document.getElementById('relocation-charge-display').textContent = `$${charge}`;
            showModal('relocation-request-modal');
        }
        // Swap Button
        if (target.classList.contains('request-swap-btn')) {
            document.getElementById('swap-booking-id').value = bookingId;
            document.getElementById('swap-charge-display').textContent = `$${charge}`;
            showModal('swap-request-modal');
        }
        // Pickup Button
        if (target.classList.contains('schedule-pickup-btn')) {
            document.getElementById('pickup-booking-id').value = bookingId;
            showModal('pickup-request-modal');
        }
    });

    // --- Form Submission Listeners ---
    document.getElementById('relocation-form').addEventListener('submit', function(e) {
        e.preventDefault();
        callBookingApi(this);
    });
    document.getElementById('swap-form').addEventListener('submit', function(e) {
        e.preventDefault();
        callBookingApi(this);
    });
    document.getElementById('pickup-form').addEventListener('submit', function(e) {
        e.preventDefault();
        callBookingApi(this);
    });

})();
</script>