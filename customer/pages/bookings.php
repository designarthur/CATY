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
$booking_detail = null; 

$requested_booking_id = $_GET['booking_id'] ?? null;

if ($requested_booking_id) {
    // Fetch a single booking for detail view
    $stmt_detail = $conn->prepare("SELECT
                                    b.id, b.booking_number, b.service_type, b.start_date, b.end_date,
                                    b.delivery_location, b.pickup_location, b.delivery_instructions, b.pickup_instructions,
                                    b.status, b.total_price, b.created_at, b.live_load_requested, b.is_urgent,
                                    b.equipment_details, b.junk_details,
                                    inv.invoice_number, inv.amount AS invoice_amount, inv.status AS invoice_status,
                                    v.name as vendor_name 
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
        $booking_detail['equipment_details'] = json_decode($booking_detail['equipment_details'] ?? '[]', true);
        $booking_detail['junk_details'] = json_decode($booking_detail['junk_details'] ?? '{}', true);
    }
    $stmt_detail->close();
}


if (!$booking_detail) {
    // Fetch all bookings and check if they have been reviewed
    $stmt_all_bookings = $conn->prepare("SELECT
                                            b.id, b.booking_number, b.service_type, b.start_date, b.end_date,
                                            b.delivery_location, b.status, b.equipment_details, b.junk_details,
                                            (SELECT COUNT(*) FROM reviews r WHERE r.booking_id = b.id) AS review_count
                                        FROM
                                            bookings b
                                        WHERE
                                            b.user_id = ?
                                        ORDER BY b.created_at DESC");
    $stmt_all_bookings->bind_param("i", $user_id);
    $stmt_all_bookings->execute();
    $result_all_bookings = $stmt_all_bookings->get_result();
    while ($row = $result_all_bookings->fetch_assoc()) {
        $row['equipment_details'] = json_decode($row['equipment_details'] ?? '[]', true);
        $row['junk_details'] = json_decode($row['junk_details'] ?? '{}', true);
        $bookings[] = $row;
    }
    $stmt_all_bookings->close();
}

$conn->close();

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
                $eq_names = array_column($booking['equipment_details'], 'equipment_name');
                $equipment_name = htmlspecialchars(implode(', ', $eq_names));
                $rental_period = htmlspecialchars((new DateTime($booking['start_date']))->format('Y-m-d') . ' to ' . (new DateTime($booking['end_date']))->format('Y-m-d'));
            } elseif ($booking['service_type'] == 'junk_removal' && !empty($booking['junk_details']['junkItems'])) {
                $junk_items_names = array_column($booking['junk_details']['junkItems'], 'itemType');
                $equipment_name = 'Junk Removal: ' . htmlspecialchars(implode(', ', $junk_items_names));
                $rental_period = htmlspecialchars((new DateTime($booking['start_date']))->format('Y-m-d'));
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
                <div class="flex items-center space-x-4">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 show-booking-details" data-booking-id="<?php echo $booking['id']; ?>">View Details</button>
                    <?php if ($booking['status'] === 'completed' && $booking['review_count'] == 0): ?>
                        <button class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors duration-200 leave-review-btn" data-booking-id="<?php echo $booking['id']; ?>" data-booking-number="<?php echo htmlspecialchars($booking['booking_number']); ?>">
                            <i class="fas fa-star mr-2"></i>Leave a Review
                        </button>
                    <?php elseif ($booking['status'] === 'completed' && $booking['review_count'] > 0): ?>
                        <span class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-2"></i>Review Submitted</span>
                    <?php endif; ?>
                </div>
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
    <?php else: ?>
        <p class="text-center text-gray-600">Booking details not found or invalid booking ID.</p>
    <?php endif; ?>
</div>

<div id="review-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg text-gray-800">
        <h3 class="text-xl font-bold mb-4">Leave a Review for Booking #<span id="review-booking-number"></span></h3>
        <form id="review-form">
            <input type="hidden" id="review-booking-id" name="booking_id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Your Rating</label>
                <div class="flex items-center text-3xl text-gray-300" id="star-rating">
                    <i class="fas fa-star cursor-pointer" data-rating="1"></i>
                    <i class="fas fa-star cursor-pointer" data-rating="2"></i>
                    <i class="fas fa-star cursor-pointer" data-rating="3"></i>
                    <i class="fas fa-star cursor-pointer" data-rating="4"></i>
                    <i class="fas fa-star cursor-pointer" data-rating="5"></i>
                </div>
                <input type="hidden" id="rating-value" name="rating" required>
            </div>
            <div class="mb-6">
                <label for="review-text" class="block text-sm font-medium text-gray-700 mb-2">Your Review (Optional)</label>
                <textarea id="review-text" name="review_text" rows="5" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Tell us about your experience..."></textarea>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('review-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Submit Review</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.show-booking-details').forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.dataset.bookingId;
            window.loadCustomerSection('bookings', { booking_id: bookingId });
        });
    });
    
    function hideBookingDetails() {
        window.loadCustomerSection('bookings');
    }

    // --- Review Modal Logic ---
    document.querySelectorAll('.leave-review-btn').forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.dataset.bookingId;
            const bookingNumber = this.dataset.bookingNumber;
            document.getElementById('review-booking-id').value = bookingId;
            document.getElementById('review-booking-number').textContent = bookingNumber;
            // Reset form
            document.getElementById('review-form').reset();
            document.getElementById('rating-value').value = '';
            document.querySelectorAll('#star-rating i').forEach(star => {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            });
            showModal('review-modal');
        });
    });

    const stars = document.querySelectorAll('#star-rating i');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            document.getElementById('rating-value').value = rating;
            stars.forEach(s => {
                s.classList.toggle('text-yellow-400', s.dataset.rating <= rating);
                s.classList.toggle('text-gray-300', s.dataset.rating > rating);
            });
        });
    });

    document.getElementById('review-form').addEventListener('submit', async function(event) {
        event.preventDefault();
        const rating = document.getElementById('rating-value').value;
        if (!rating) {
            showToast('Please select a star rating.', 'error');
            return;
        }

        const formData = new FormData(this);
        showToast('Submitting your review...', 'info');

        try {
            const response = await fetch('/api/customer/reviews.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showToast('Thank you! Your review has been submitted.', 'success');
                hideModal('review-modal');
                // Reload the bookings section to update the button state
                window.loadCustomerSection('bookings');
            } else {
                showToast(result.message || 'Failed to submit review.', 'error');
            }
        } catch (error) {
            console.error('Review submission error:', error);
            showToast('An error occurred. Please try again.', 'error');
        }
    });

</script>