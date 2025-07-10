<?php
// customer/pages/invoices.php

// Ensure session is started and user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For has_role and user_id

if (!is_logged_in()) {
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];
$invoices = [];
$invoice_detail = null; // To hold data for a single invoice detail view if requested

// Check if a specific invoice ID is requested for detail view
$requested_invoice_number = $_GET['invoice_id'] ?? null;
$requested_quote_id = $_GET['quote_id'] ?? null; // For direct link from pending quotes

// Fetch all invoices for the list
$stmt_all_invoices = $conn->prepare("SELECT id, invoice_number, amount, status, created_at, due_date FROM invoices WHERE user_id = ? ORDER BY created_at DESC");
$stmt_all_invoices->bind_param("i", $user_id);
$stmt_all_invoices->execute();
$result_all_invoices = $stmt_all_invoices->get_result();
while ($row = $result_all_invoices->fetch_assoc()) {
    $invoices[] = $row;
}
$stmt_all_invoices->close();

// If a specific invoice number is requested, fetch its details
if ($requested_invoice_number || $requested_quote_id) { // Combined condition
    $stmt_detail = $conn->prepare("SELECT
                                    i.id, i.invoice_number, i.amount, i.status, i.created_at, i.due_date, i.transaction_id, i.payment_method,
                                    u.first_name, u.last_name, u.email, u.address, u.city, u.state, u.zip_code,
                                    q.service_type, q.quote_details, q.quoted_price,
                                    qed.equipment_name, qed.quantity, qed.specific_needs, qed.duration_days,
                                    jrd.junk_items_json, jrd.recommended_dumpster_size, jrd.additional_comment, jrd.media_urls_json,
                                    b.equipment_details AS booking_equipment_details, b.junk_details AS booking_junk_details, b.total_price AS booking_total_price
                                FROM
                                    invoices i
                                JOIN
                                    users u ON i.user_id = u.id
                                LEFT JOIN
                                    quotes q ON i.quote_id = q.id
                                LEFT JOIN
                                    quote_equipment_details qed ON q.id = qed.quote_id
                                LEFT JOIN
                                    junk_removal_details jrd ON q.id = jrd.quote_id
                                LEFT JOIN
                                    bookings b ON i.id = b.invoice_id
                                WHERE
                                    i.user_id = ? AND (i.invoice_number = ? OR q.id = ?)"); // Added OR q.id = ? for consistent fetching
    $stmt_detail->bind_param("isi", $user_id, $requested_invoice_number, $requested_quote_id); // Changed bind_param to include new parameter
    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();
    if ($result_detail->num_rows > 0) {
        $invoice_detail = $result_detail->fetch_assoc();
        // Decode JSON fields with null coalescing to prevent deprecation warnings
        $invoice_detail['quote_details'] = json_decode($invoice_detail['quote_details'] ?? '{}', true);
        $invoice_detail['junk_items_json'] = json_decode($invoice_detail['junk_items_json'] ?? '[]', true); // Added for junk removal details from quote
        $invoice_detail['media_urls_json'] = json_decode($invoice_detail['media_urls_json'] ?? '[]', true); // Added for junk removal media from quote
        $invoice_detail['booking_equipment_details'] = json_decode($invoice_detail['booking_equipment_details'] ?? '[]', true); // Ensure JSON is decoded
        $invoice_detail['booking_junk_details'] = json_decode($invoice_detail['booking_junk_details'] ?? '{}', true); // Ensure JSON is decoded
    }
    $stmt_detail->close();
}


$conn->close();

// Function to get status badge classes (re-used from bookings.php)
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'paid':
            return 'bg-green-100 text-green-800';
        case 'partially_paid':
            return 'bg-yellow-100 text-yellow-800';
        case 'pending':
            return 'bg-red-100 text-red-800';
        case 'cancelled':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Invoices</h1>

<div id="invoice-list" class="<?php echo $invoice_detail ? 'hidden' : ''; ?>">
    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
        <?php if (empty($invoices)): ?>
            <div class="text-center text-gray-600 p-4">You have no invoices yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Invoice ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($invoice['created_at']))->format('Y-m-d'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($invoice['amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($invoice['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $invoice['status']))); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900 view-invoice-details" data-invoice-id="<?php echo htmlspecialchars($invoice['invoice_number']); ?>">View</button>
                                    <?php if ($invoice['status'] == 'pending' || $invoice['status'] == 'partially_paid'): ?>
                                        <button class="ml-3 text-green-600 hover:text-green-900 pay-invoice-btn" data-invoice-id="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" data-amount="<?php echo htmlspecialchars($invoice['amount']); ?>">Pay Now</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="invoice-detail-view" class="bg-white p-6 rounded-lg shadow-md border border-blue-200 mt-8 <?php echo $invoice_detail ? '' : 'hidden'; ?>">
    <button class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="window.hideInvoiceDetails()">
        <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
    </button>
    <?php if ($invoice_detail): ?>
        <h2 class="text-2xl font-bold text-gray-800 mb-6" id="detail-invoice-number">Invoice Details for #<?php echo htmlspecialchars($invoice_detail['invoice_number']); ?></h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <p class="text-gray-600"><span class="font-medium">Invoice Date:</span> <span id="detail-invoice-date"><?php echo (new DateTime($invoice_detail['created_at']))->format('Y-m-d'); ?></span></p>
                <p class="text-gray-600"><span class="font-medium">Due Date:</span> <?php echo $invoice_detail['due_date'] ? (new DateTime($invoice_detail['due_date']))->format('Y-m-d') : 'N/A'; ?></p>
                <p class="text-gray-600"><span class="font-medium">Status:</span> <span id="detail-invoice-status" class="font-semibold <?php echo getStatusBadgeClass($invoice_detail['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $invoice_detail['status']))); ?></span></p>
                <p class="text-gray-600"><span class="font-medium">Transaction ID:</span> <?php echo htmlspecialchars($invoice_detail['transaction_id'] ?? 'N/A'); ?></p>
                <p class="text-gray-600"><span class="font-medium">Payment Method:</span> <?php echo htmlspecialchars($invoice_detail['payment_method'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-gray-600"><span class="font-medium">Billed To:</span> <?php echo htmlspecialchars($invoice_detail['first_name'] . ' ' . $invoice_detail['last_name']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Address:</span> <?php echo htmlspecialchars($invoice_detail['address'] . ', ' . $invoice_detail['city'] . ', ' . $invoice_detail['state'] . ' ' . $invoice_detail['zip_code']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($invoice_detail['email']); ?></p>
            </div>
        </div>

        <h3 class="text-xl font-semibold text-gray-700 mb-4">Items</h3>
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Unit Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $subtotal = 0;
                    $items_to_display = [];

                    // Determine total amount for calculation, prioritizing booking_total_price if available and correct
                    $base_amount = $invoice_detail['booking_total_price'] ?? $invoice_detail['amount'];

                    // Try to derive items from linked quote details (if no booking yet) or booking details (after conversion)
                    if ($invoice_detail['service_type'] == 'equipment_rental') {
                        // Prioritize QED details if available, otherwise fall back to booking_equipment_details
                        $equipment_name_display = $invoice_detail['equipment_name'] ?? ($invoice_detail['booking_equipment_details'][0]['equipment_name'] ?? 'Equipment Rental');
                        $quantity_display = $invoice_detail['quantity'] ?? ($invoice_detail['booking_equipment_details'][0]['quantity'] ?? 1);
                        $duration_days_display = $invoice_detail['duration_days'] ?? ($invoice_detail['booking_equipment_details'][0]['duration_days'] ?? null);
                        $specific_needs_display = $invoice_detail['specific_needs'] ?? ($invoice_detail['booking_equipment_details'][0]['specific_needs'] ?? null);

                        $desc = htmlspecialchars($equipment_name_display);
                        if (!empty($duration_days_display)) {
                            $desc .= " (for {$duration_days_display} days)";
                        }
                        if (!empty($specific_needs_display)) {
                            $desc .= " - Needs: {$specific_needs_display}";
                        }

                        $qty = $quantity_display;
                        $unit_price = ($base_amount / $qty); // Estimate unit price based on total amount
                        $item_total = $base_amount;
                        $subtotal += $item_total;

                        $items_to_display[] = [
                            'desc' => $desc,
                            'qty' => $qty,
                            'unit_price' => $unit_price,
                            'total' => $item_total
                        ];
                    } elseif ($invoice_detail['service_type'] == 'junk_removal') {
                        $junk_desc = 'Junk Removal Service';
                        $junk_items_from_quote = $invoice_detail['junk_items_json'] ?? []; // Already decoded
                        $junk_items_from_booking = $invoice_detail['booking_junk_details']['junkItems'] ?? []; // Already decoded

                        $items_source = !empty($junk_items_from_quote) ? $junk_items_from_quote : $junk_items_from_booking;

                        if (!empty($items_source)) {
                            $junk_types = array_column($items_source, 'itemType');
                            $junk_desc .= ' (' . htmlspecialchars(implode(', ', $junk_types)) . ')';
                        }
                        $recommended_dumpster_size = $invoice_detail['recommended_dumpster_size'] ?? ($invoice_detail['booking_junk_details']['recommendedDumpsterSize'] ?? 'N/A');
                        if (!empty($recommended_dumpster_size) && $recommended_dumpster_size != 'N/A') {
                            $junk_desc .= " - Recommended: {$recommended_dumpster_size}";
                        }

                        $item_total = $invoice_detail['quoted_price'] ?? $base_amount;
                        $subtotal = $item_total;

                        $items_to_display[] = [
                            'desc' => $junk_desc,
                            'qty' => 1,
                            'unit_price' => $item_total,
                            'total' => $item_total
                        ];
                    } else {
                        // Fallback: If service type is unknown or no details derived, show a generic line item
                        $item_total = $invoice_detail['quoted_price'] ?? $base_amount;
                        $subtotal = $item_total;
                        $items_to_display[] = [
                            'desc' => 'General Service or Item', // More generic fallback
                            'qty' => 1,
                            'unit_price' => $item_total,
                            'total' => $item_total
                        ];
                    }

                    foreach ($items_to_display as $item):
                    ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $item['desc']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $item['qty']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($item['total'], 2); ?></td>
                        </tr>
                    <?php endforeach;
                    // Calculate tax and grand total based on the invoice's actual amount, assuming tax is included
                    // If your system adds tax later, you'd calculate it differently.
                    $tax_amount = $invoice_detail['amount'] - $subtotal;
                    if ($tax_amount < 0) $tax_amount = 0; // Prevent negative tax if subtotal calculation is imprecise
                    ?>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mt-4">
            <div class="w-full md:w-1/2 space-y-2 text-gray-700">
                <div class="flex justify-between"><span class="font-medium">Subtotal:</span> <span>$<?php echo number_format($subtotal, 2); ?></span></div>
                <div class="flex justify-between"><span class="font-medium">Discount:</span> <span>$0.00</span></div>
                <div class="flex justify-between"><span class="font-medium">Tax (Est.):</span> <span>$<?php echo number_format($tax_amount, 2); ?></span></div>
                <div class="flex justify-between text-xl font-bold border-t pt-2 border-gray-300"><span class="font-medium">Grand Total:</span> <span class="text-blue-700">$<?php echo number_format($invoice_detail['amount'], 2); ?></span></div>
            </div>
        </div>

        <div id="payment-actions" class="flex justify-end mt-6">
            <?php if ($invoice_detail['status'] == 'pending' || $invoice_detail['status'] == 'partially_paid'): ?>
                <button class="py-2 px-5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 show-payment-form-btn pay-now-detail-btn" data-invoice-id="<?php echo htmlspecialchars($invoice_detail['invoice_number']); ?>" data-amount="<?php echo htmlspecialchars($invoice_detail['amount']); ?>">
                <i class="fas fa-dollar-sign mr-2"></i>Pay Now
            </button>
            <?php endif; ?>
        </div>
    <?php else: // No invoice found for detail view ?>
        <p class="text-center text-gray-600">Invoice details not found or invalid invoice ID.</p>
    <?php endif; ?>
</div>

<div id="payment-form-view" class="bg-white p-6 rounded-lg shadow-md border border-blue-200 hidden mt-8">
    <button class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="window.hidePaymentForm()">
        <i class="fas fa-arrow-left mr-2"></i>Back to Invoice Details
    </button>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Process Payment for <span id="payment-invoice-id"></span></h2>

    <form id="payment-form" method="POST" action="/api/payments.php">
        <input type="hidden" name="action" value="process_payment">
        <input type="hidden" name="invoice_number" id="payment-form-invoice-number-hidden">
        <div class="mb-5">
            <label for="card-number" class="block text-sm font-medium text-gray-700 mb-2">Card Number</label>
            <input type="text" id="card-number" name="card_number" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="**** **** **** ****" required pattern="[0-9\s]{13,19}" maxlength="19">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
            <div>
                <label for="expiry-date" class="block text-sm font-medium text-gray-700 mb-2">Expiration Date (MM/YY)</label>
                <input type="text" id="expiry-date" name="expiry_date" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="MM/YY" required pattern="(0[1-9]|1[0-2])\/[0-9]{2}">
            </div>
            <div>
                <label for="cvv" class="block text-sm font-medium text-gray-700 mb-2">CVV</label>
                <input type="text" id="cvv" name="cvv" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="***" required pattern="[0-9]{3,4}">
            </div>
        </div>
        <div class="mb-5">
            <label for="billing-address" class="block text-sm font-medium text-gray-700 mb-2">Billing Address</label>
            <input type="text" id="billing-address" name="billing_address" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="123 Example St, City, State, Zip" required>
        </div>
        <div class="mb-5">
            <label for="payment-amount" class="block text-sm font-medium text-gray-700 mb-2">Amount to Pay</label>
            <input type="number" id="payment-amount" name="amount" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" step="0.01" value="0.00" readonly>
        </div>
        <button type="submit" class="w-full py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-lg font-semibold">
            <i class="fas fa-dollar-sign mr-2"></i>Confirm Payment
        </button>
    </form>
</div>

<script>
    // These functions are specific to invoices.php and are made global for onclick attributes.

    // Global helper from dashboard.php, ensuring it's available for this script
    // function showToast(message, type) { /* ... defined in dashboard.php */ }
    // function showModal(id) { /* ... defined in dashboard.php */ }
    // function hideModal(id) { /* ... defined in dashboard.php */ }
    // window.loadCustomerSection = function() { /* ... defined in dashboard.php */ };


    window.showInvoiceDetails = function(invoiceNumber) {
        window.loadCustomerSection('invoices', { invoice_id: invoiceNumber });
    };

    window.hideInvoiceDetails = function() {
        window.loadCustomerSection('invoices');
        history.replaceState(null, '', '#invoices'); // Adjust URL hash
    };

    window.showPaymentForm = function(invoiceId, amount) {
        document.getElementById('payment-invoice-id').textContent = invoiceId;
        document.getElementById('payment-form-invoice-number-hidden').value = invoiceId;
        document.getElementById('payment-amount').value = parseFloat(amount).toFixed(2);

        document.getElementById('invoice-detail-view').classList.add('hidden');
        document.getElementById('invoice-list').classList.add('hidden');
        document.getElementById('payment-form-view').classList.remove('hidden');
    };

    window.hidePaymentForm = function() {
        const invoiceDetailView = document.getElementById('invoice-detail-view');
        const isDetailViewVisible = !invoiceDetailView.classList.contains('hidden');

        document.getElementById('payment-form-view').classList.add('hidden');
        if (isDetailViewVisible) {
            invoiceDetailView.classList.remove('hidden');
        } else {
            window.loadCustomerSection('invoices');
            history.replaceState(null, '', '#invoices');
        }
    };


    // --- Invoice Page Specific JavaScript (wrapped in IIFE to prevent variable re-declaration) ---
    (function() { // Start IIFE

        // Function from payment-methods.php, needed here for payment form validation (remains local to IIFE)
        function isValidExpiryDate(month, year) {
            if (!/^(0[1-9]|1[0-2])$/.test(month) || !/^\d{4}$/.test(year)) {
                return false;
            }

            const currentYear = new Date().getFullYear();
            const currentMonth = new Date().getMonth() + 1; // Month is 0-indexed

            const expMonth = parseInt(month, 10);
            const expYear = parseInt(year, 10);

            if (expYear < currentYear) {
                return false; // Expired year
            }
            if (expYear === currentYear && expMonth < currentMonth) {
                return false; // Expired month in current year
            }
            return true;
        }

        // --- Event Listeners ---
        // These can still be inside the IIFE and attached directly, as they run after the HTML is loaded.

         // Attach listeners for "View" buttons in the invoice list (KEEP THIS)
    document.querySelectorAll('.view-invoice-details').forEach(button => {
        button.removeEventListener('click', showInvoiceDetailsWrapper);
        button.addEventListener('click', showInvoiceDetailsWrapper);
    });

    // Wrapper to call the global function
    function showInvoiceDetailsWrapper(event) {
        window.showInvoiceDetails(this.dataset.invoiceId);
    }

    // Attach listeners for "Pay Now" buttons in the invoice list and detail view (REMOVE THIS ENTIRE BLOCK)
    document.querySelectorAll('.pay-invoice-btn').forEach(button => {
        button.removeEventListener('click', showPaymentFormWrapper);
        button.addEventListener('click', showPaymentFormWrapper);
    });

    function showPaymentFormWrapper(event) {
        window.showPaymentForm(this.dataset.invoiceId, this.dataset.amount);
    }


        // Handle payment form submission (AJAX to /api/payments.php)
        const paymentForm = document.getElementById('payment-form');
        // Only attach listener if element exists and listener is not already attached (using a data attribute flag)
        if (paymentForm && !paymentForm.dataset.listenerAttached) {
            paymentForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                const invoiceNumber = document.getElementById('payment-form-invoice-number-hidden').value;
                const amount = document.getElementById('payment-amount').value;
                const cardNumber = document.getElementById('card-number').value.trim();
                const expiryDate = document.getElementById('expiry-date').value.trim();
                const cvv = document.getElementById('cvv').value.trim();
                const billingAddress = document.getElementById('billing-address').value.trim();

                if (!cardNumber || !expiryDate || !cvv || !billingAddress) {
                    window.showToast('Please fill in all payment details.', 'error');
                    return;
                }
                if (!/^\d{13,16}$/.test(cardNumber.replace(/\s/g, ''))) {
                    window.showToast('Please enter a valid card number (13-16 digits).', 'error');
                    return;
                }
                const expiryParts = expiryDate.split('/');
                if (expiryParts.length !== 2 || !isValidExpiryDate(expiryParts[0], '20' + expiryParts[1])) {
                    window.showToast('Please enter a valid expiration date (MM/YY) that is not expired.', 'error');
                    return;
                }
                if (!/^\d{3,4}$/.test(cvv)) {
                    window.showToast('Please enter a valid CVV (3 or 4 digits).', 'error');
                    return;
                }

                window.showToast('Processing payment...', 'info');

                try {
                    const response = await fetch(paymentForm.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'process_payment',
                            invoice_number: invoiceNumber,
                            amount: amount,
                            payment_method_nonce: 'fake-valid-nonce',
                            card_number: cardNumber,
                            expiry_date: expiryDate,
                            cvv: cvv,
                            billing_address: billingAddress
                        }).toString()
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.hidePaymentForm(); // Call global hidePaymentForm
                        window.showToast('Payment successful! Your booking is now confirmed.', 'success');
                        window.loadCustomerSection('bookings');
                    } else {
                        window.showToast('Payment failed: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Payment API Error:', error);
                    window.showToast('An error occurred during payment. Please try again.', 'error');
                }
            });
            paymentForm.dataset.listenerAttached = 'true';
        }

    })(); // End IIFE
</script>