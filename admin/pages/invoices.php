<?php
// admin/pages/invoices.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';

if (!is_logged_in() || !has_role('admin')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

$invoices = [];

// Fetch all invoices with customer information
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

<h1 class="text-3xl font-bold text-gray-800 mb-8">Invoice Management</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-file-invoice-dollar mr-2 text-blue-600"></i>All System Invoices</h2>

    <?php if (empty($invoices)): ?>
        <p class="text-gray-600 text-center p-4">No invoices found in the system.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
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
                                <select class="invoice-status-dropdown p-1 border border-gray-300 rounded-md text-xs" data-id="<?php echo $invoice['id']; ?>" data-current-status="<?php echo $invoice['status']; ?>">
                                    <option value="pending" <?php echo ($invoice['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo ($invoice['status'] === 'paid') ? 'selected' : ''; ?>>Paid</option>
                                    <option value="partially_paid" <?php echo ($invoice['status'] === 'partially_paid') ? 'selected' : ''; ?>>Partially Paid</option>
                                    <option value="cancelled" <?php echo ($invoice['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('change', function(event) {
    if (event.target.classList.contains('invoice-status-dropdown')) {
        const dropdown = event.target;
        const invoiceId = dropdown.dataset.id;
        const newStatus = dropdown.value;
        const currentStatus = dropdown.dataset.currentStatus;

        if (newStatus === currentStatus) {
            return; // No change, do nothing
        }

        showConfirmationModal(
            'Confirm Status Change',
            `Are you sure you want to change this invoice status to "${newStatus}"? The customer will be notified.`,
            async (confirmed) => {
                if (confirmed) {
                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('invoice_id', invoiceId);
                    formData.append('status', newStatus);
                    
                    try {
                        const response = await fetch('/api/admin/invoices.php', { method: 'POST', body: formData });
                        const result = await response.json();
                        
                        if (result.success) {
                            showToast(result.message, 'success');
                            window.loadAdminSection('invoices'); // Reload the page to reflect changes
                        } else {
                            showToast(result.message, 'error');
                            dropdown.value = currentStatus; // Revert dropdown on failure
                        }
                    } catch (error) {
                        showToast('An error occurred.', 'error');
                        dropdown.value = currentStatus; // Revert dropdown on failure
                    }
                } else {
                    dropdown.value = currentStatus; // Revert dropdown if admin cancels
                }
            },
            'Update Status',
            'bg-blue-600'
        );
    }
});
</script>