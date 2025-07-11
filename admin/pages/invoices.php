<?php
// admin/pages/invoices.php

// Ensure session is started and user is logged in as admin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php'; // Required for generate_csrf_token()

if (!is_logged_in() || !has_role('admin')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

// Generate CSRF token for this page
generate_csrf_token();
$csrf_token = $_SESSION['csrf_token'];


$invoices = [];
$invoice_detail_view_data = null;
$requested_invoice_id = filter_input(INPUT_GET, 'invoice_id', FILTER_VALIDATE_INT);


if ($requested_invoice_id) {
    // --- Fetch data for the detail/edit view ---
    $stmt_detail = $conn->prepare("
        SELECT 
            i.id, i.invoice_number, i.amount, i.status, i.created_at, i.due_date, i.discount, i.tax,
            u.first_name, u.last_name
        FROM invoices i
        JOIN users u ON i.user_id = u.id
        WHERE i.id = ?
    ");
    $stmt_detail->bind_param("i", $requested_invoice_id);
    $stmt_detail->execute();
    $invoice_detail_view_data = $stmt_detail->get_result()->fetch_assoc();
    $stmt_detail->close();

    if ($invoice_detail_view_data) {
        $invoice_detail_view_data['items'] = [];
        $stmt_items = $conn->prepare("SELECT id, description, quantity, unit_price, total FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
        $stmt_items->bind_param("i", $requested_invoice_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while($row = $result_items->fetch_assoc()) {
            $invoice_detail_view_data['items'][] = $row;
        }
        $stmt_items->close();
    }

} else {
    //--- Fetch all invoices for the list view ---
    $stmt = $conn->prepare("
        SELECT 
            i.id, i.invoice_number, i.amount, i.status, i.created_at, i.due_date,
            u.first_name, u.last_name
        FROM invoices i
        JOIN users u ON i.user_id = u.id
        ORDER BY i.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }
    $stmt->close();
}

$conn->close();

// Helper function for status badges
function getAdminInvoiceStatusBadge($status) {
    switch ($status) {
        case 'paid':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-red-100 text-red-800';
        case 'partially_paid':
            return 'bg-yellow-100 text-yellow-800';
        case 'cancelled':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-200 text-gray-800';
    }
}
?>

<div id="invoice-list-section" class="<?php echo $invoice_detail_view_data ? 'hidden' : ''; ?>">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Invoice Management</h1>
    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-700"><i class="fas fa-file-invoice-dollar mr-2 text-blue-600"></i>All System Invoices</h2>
             <button id="bulk-delete-invoices-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 shadow-md hidden">
                <i class="fas fa-trash-alt mr-2"></i>Delete Selected
            </button>
        </div>
        <?php if (empty($invoices)): ?>
            <p class="text-gray-600 text-center p-4">No invoices found in the system.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-6 py-3">
                                <input type="checkbox" id="select-all-invoices" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Invoice #</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td class="px-6 py-4">
                                     <input type="checkbox" class="invoice-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" value="<?php echo $invoice['id']; ?>">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($invoice['amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($invoice['created_at']))->format('Y-m-d'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getAdminInvoiceStatusBadge($invoice['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($invoice['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900 manage-invoice-btn" data-id="<?php echo $invoice['id']; ?>">Manage</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="invoice-edit-section" class="<?php echo $invoice_detail_view_data ? '' : 'hidden'; ?>">
    <?php if ($invoice_detail_view_data): ?>
    <button class="mb-6 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="window.loadAdminSection('invoices')">
        <i class="fas fa-arrow-left mr-2"></i>Back to All Invoices
    </button>
    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit Invoice #<?php echo htmlspecialchars($invoice_detail_view_data['invoice_number']); ?></h2>
            <a href="/api/admin/download.php?type=invoice&id=<?php echo htmlspecialchars($invoice_detail_view_data['id']); ?>" target="_blank" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">
                <i class="fas fa-file-pdf mr-2"></i>Download PDF
            </a>
        </div>
        <form id="edit-invoice-form">
            <input type="hidden" name="invoice_id" value="<?php echo $invoice_detail_view_data['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="overflow-x-auto mb-4">
                <table class="min-w-full" id="invoice-items-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Qty</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-32">Unit Price</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-32">Total</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($invoice_detail_view_data['items'] as $item): ?>
                        <tr>
                            <td class="p-2"><input type="text" name="items[description][]" class="w-full p-2 border rounded" value="<?php echo htmlspecialchars($item['description']); ?>" required></td>
                            <td class="p-2"><input type="number" name="items[quantity][]" class="w-full p-2 border rounded" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" required></td>
                            <td class="p-2"><input type="number" name="items[unit_price][]" class="w-full p-2 border rounded" value="<?php echo htmlspecialchars($item['unit_price']); ?>" step="0.01" min="0" required></td>
                            <td class="p-2"><input type="text" name="items[total][]" class="w-full p-2 border rounded bg-gray-100" value="<?php echo htmlspecialchars($item['total']); ?>" readonly></td>
                            <td class="p-2 text-center"><button type="button" class="text-red-500 hover:text-red-700 remove-item-btn">&times;</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" id="add-item-btn" class="mb-4 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm hover:bg-blue-200">+ Add Line Item</button>
            
            <div class="flex justify-end mt-4">
                <div class="w-full md:w-1/2 lg:w-1/3 space-y-2">
                    <div class="flex justify-between items-center">
                        <label for="subtotal" class="font-medium">Subtotal</label>
                        <input id="subtotal" type="text" class="p-2 border rounded bg-gray-100 text-right w-32" readonly>
                    </div>
                     <div class="flex justify-between items-center">
                        <label for="discount" class="font-medium">Discount ($)</label>
                        <input id="discount" name="discount" type="number" class="p-2 border rounded text-right w-32" step="0.01" min="0" value="<?php echo htmlspecialchars($invoice_detail_view_data['discount']); ?>">
                    </div>
                    <div class="flex justify-between items-center">
                        <label for="tax" class="font-medium">Tax ($)</label>
                        <input id="tax" name="tax" type="number" class="p-2 border rounded text-right w-32" step="0.01" min="0" value="<?php echo htmlspecialchars($invoice_detail_view_data['tax']); ?>">
                    </div>
                    <div class="flex justify-between items-center font-bold text-xl border-t pt-2">
                        <label>Grand Total</label>
                        <input id="grand-total" type="text" class="p-2 border-0 rounded bg-white font-bold text-right w-32" readonly>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Invoice Status</h3>
                <label for="invoice-status-select" class="block text-sm font-medium text-gray-700 mb-2">Update Invoice Status</label>
                <select id="invoice-status-select" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                    <option value="pending" <?php echo ($invoice_detail_view_data['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="paid" <?php echo ($invoice_detail_view_data['status'] === 'paid') ? 'selected' : ''; ?>>Paid</option>
                    <option value="partially_paid" <?php echo ($invoice_detail_view_data['status'] === 'partially_paid') ? 'selected' : ''; ?>>Partially Paid</option>
                    <option value="cancelled" <?php echo ($invoice_detail_view_data['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="button" id="update-invoice-status-btn" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-md"
                    data-invoice-id="<?php echo htmlspecialchars($invoice_detail_view_data['id']); ?>"
                    data-csrf-token="<?php echo htmlspecialchars($csrf_token); ?>">
                    <i class="fas fa-sync-alt mr-2"></i>Update Status
                </button>
            </div>


            <div class="flex justify-end mt-6">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-md">
                    <i class="fas fa-save mr-2"></i>Save Invoice Changes
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
// Remove the DOMContentLoaded wrapper to ensure script runs immediately when loaded via AJAX.

const listSection = document.getElementById('invoice-list-section');
const editSection = document.getElementById('invoice-edit-section');

/**
 * Calculates and updates the subtotal, discount, tax, and grand total fields
 * in the invoice edit form.
 */
function calculateTotals() {
    let subtotal = 0;
    // Iterate over each row in the invoice items table to calculate line item totals and overall subtotal.
    document.querySelectorAll('#invoice-items-table tbody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('input[name="items[quantity][]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name="items[unit_price][]"]').value) || 0;
        const total = qty * price;
        // Update the total for the current line item.
        row.querySelector('input[name="items[total][]"]').value = total.toFixed(2);
        subtotal += total;
    });

    // Update the displayed subtotal.
    document.getElementById('subtotal').value = '$' + subtotal.toFixed(2);

    // Get discount and tax values, defaulting to 0 if not a valid number.
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const tax = parseFloat(document.getElementById('tax').value) || 0;
    
    // Calculate the grand total.
    const grandTotal = (subtotal - discount) + tax;
    // Update the displayed grand total.
    document.getElementById('grand-total').value = '$' + grandTotal.toFixed(2);
}

/**
 * Toggles the visibility of the "Delete Selected" button based on
 * whether any invoice checkboxes are checked.
 */
function toggleBulkDeleteButton() {
    const anyChecked = document.querySelector('.invoice-checkbox:checked');
    document.getElementById('bulk-delete-invoices-btn').classList.toggle('hidden', !anyChecked);
}


// --- Event Delegation for the entire page ---
// Attaching a single click listener to the document body to handle events
// from dynamically loaded content.
document.body.addEventListener('click', function(event) {
    // --- List View Actions ---
    // Handle click on "Manage" button for an invoice.
    if (event.target.classList.contains('manage-invoice-btn')) {
        const invoiceId = event.target.dataset.id;
        // Load the invoice edit section with the specific invoice ID.
        window.loadAdminSection('invoices', { invoice_id: invoiceId });
    }

    // Handle click on "Select All" checkbox.
    if (event.target.id === 'select-all-invoices') {
        // Set all individual invoice checkboxes to the same state as the "Select All" checkbox.
        document.querySelectorAll('.invoice-checkbox').forEach(cb => cb.checked = event.target.checked);
        toggleBulkDeleteButton(); // Update bulk delete button visibility.
    }
    
    // Handle click on individual invoice checkboxes.
    if(event.target.classList.contains('invoice-checkbox')) {
        toggleBulkDeleteButton(); // Update bulk delete button visibility.
    }

    // Handle click on "Delete Selected" button for bulk deletion.
    if(event.target.id === 'bulk-delete-invoices-btn'){
        // Get IDs of all checked invoices.
        const selectedIds = Array.from(document.querySelectorAll('.invoice-checkbox:checked')).map(cb => cb.value);
        if(selectedIds.length > 0) {
            // Show a confirmation modal before proceeding with deletion.
            showConfirmationModal('Delete Selected Invoices', `Are you sure you want to delete ${selectedIds.length} invoice(s)? This action cannot be undone.`, async (confirmed) => {
                if (confirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete_bulk');
                    // Append each selected ID to the FormData object.
                    selectedIds.forEach(id => formData.append('invoice_ids[]', id));
                    try {
                        // Send the delete request to the API.
                        const response = await fetch('/api/admin/invoices.php', { method: 'POST', body: formData });
                        const result = await response.json();
                        if(result.success) {
                            showToast(result.message, 'success');
                            window.loadAdminSection('invoices'); // Reload the invoices section to reflect changes.
                        } else {
                            showToast(result.message, 'error');
                        }
                    } catch(error) { 
                        console.error('Bulk delete invoices API Error:', error);
                        showToast('An error occurred during bulk deletion. Please try again.', 'error'); 
                    }
                }
            }, 'Delete Selected', 'bg-red-600');
        }
    }

    // --- Edit View Actions ---
    // Handle click on "Add Line Item" button.
    if (event.target.id === 'add-item-btn') {
        const tableBody = document.getElementById('invoice-items-table').querySelector('tbody');
        // HTML for a new invoice line item row.
        const newRow = `
            <tr>
                <td class="p-2"><input type="text" name="items[description][]" class="w-full p-2 border rounded" required></td>
                <td class="p-2"><input type="number" name="items[quantity][]" class="w-full p-2 border rounded" value="1" min="1" required></td>
                <td class="p-2"><input type="number" name="items[unit_price][]" class="w-full p-2 border rounded" value="0.00" step="0.01" min="0" required></td>
                <td class="p-2"><input type="text" name="items[total][]" class="w-full p-2 border rounded bg-gray-100" readonly></td>
                <td class="p-2 text-center"><button type="button" class="text-red-500 hover:text-red-700 remove-item-btn">&times;</button></td>
            </tr>
        `;
        tableBody.insertAdjacentHTML('beforeend', newRow); // Add new row to the table.
        calculateTotals(); // Recalculate totals after adding a new row.
    }

    // Handle click on "Remove Item" button for a line item.
    if (event.target.classList.contains('remove-item-btn')) {
        event.target.closest('tr').remove(); // Remove the entire row.
        calculateTotals(); // Recalculate totals after removing a row.
    }

    // Handle click on "Update Status" button in edit view.
    if (event.target.id === 'update-invoice-status-btn') {
        const invoiceId = event.target.dataset.invoiceId;
        const newStatus = document.getElementById('invoice-status-select').value;
        const newStatusText = document.getElementById('invoice-status-select').options[document.getElementById('invoice-status-select').selectedIndex].text;
        const csrfToken = event.target.dataset.csrfToken;


        showConfirmationModal(
            'Confirm Status Change',
            `Are you sure you want to change the invoice status to "${newStatusText}"?`,
            async (confirmed) => {
                if (confirmed) {
                    showToast('Updating invoice status...', 'info');
                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('invoice_id', invoiceId);
                    formData.append('status', newStatus);
                    formData.append('csrf_token', csrfToken); // Add CSRF token here

                    try {
                        const response = await fetch('/api/admin/invoices.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        if (result.success) {
                            showToast(result.message, 'success');
                            window.loadAdminSection('invoices', {invoice_id: invoiceId}); // Reload to show updated status
                        } else {
                            showToast(result.message, 'error');
                        }
                    } catch (error) {
                        console.error('Update invoice status API Error:', error);
                        showToast('An unexpected error occurred during status update.', 'error');
                    }
                }
            },
            'Update Status',
            'bg-blue-600'
        );
    }
});

// Recalculate totals on input change in edit view.
// This listener is attached to the `editSection` which is part of the dynamically loaded content.
if(editSection) { // Ensure editSection exists before attaching listeners
    editSection.addEventListener('input', function(event){
        // Check if the input change is relevant to total calculations.
        if(event.target.matches('input[name^="items"], #discount, #tax')) {
            calculateTotals();
        }
    });
    // Perform an initial calculation if the edit section is currently visible.
    if(!editSection.classList.contains('hidden')) {
        calculateTotals();
    }
}

// Save Invoice Form Submission.
const editInvoiceForm = document.getElementById('edit-invoice-form');
if(editInvoiceForm) {
    editInvoiceForm.addEventListener('submit', async function(e) {
        e.preventDefault(); // Prevent default form submission.
        const items = [];
        // Collect all line item data from the form.
        document.querySelectorAll('#invoice-items-table tbody tr').forEach(row => {
            items.push({
                description: row.querySelector('input[name="items[description][]"]').value,
                quantity: row.querySelector('input[name="items[quantity][]"]').value,
                unit_price: row.querySelector('input[name="items[unit_price][]"]').value,
            });
        });

        const formData = new FormData();
        formData.append('action', 'update_invoice');
        formData.append('invoice_id', document.querySelector('input[name="invoice_id"]').value);
        formData.append('items', JSON.stringify(items)); // Send items as a JSON string.
        formData.append('discount', document.getElementById('discount').value);
        formData.append('tax', document.getElementById('tax').value);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value); // Add CSRF token here

        try {
            // Send the update request to the API.
            const response = await fetch('/api/admin/invoices.php', { method: 'POST', body: formData });
            const result = await response.json();
            if(result.success) {
                showToast(result.message, 'success');
                // Reload the invoice detail view to show updated data.
                window.loadAdminSection('invoices', {invoice_id: formData.get('invoice_id')});
            } else {
                showToast(result.message, 'error');
            }
        } catch(error) { 
            console.error('Update invoice API Error:', error);
            showToast('An error occurred during invoice update. Please try again.', 'error'); 
        }
    });
}