<?php
// admin/pages/notifications.php

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

$admin_user_id = $_SESSION['user_id'];
$notifications = [];

// Fetch notifications for the admin.
// For simplicity, we'll fetch notifications where user_id is the admin's ID,
// or potentially system-wide notifications if a 'null' user_id is used for them.
// A more robust system might have an 'admin_notifications' table or specific notification types for admin.
// For now, let's fetch all notifications that are not specifically linked to a 'customer' role user_id,
// or directly linked to the current admin. You might need to refine this logic based on how admin notifications are generated.
$stmt = $conn->prepare("SELECT id, type, message, link, is_read, created_at FROM notifications ORDER BY created_at DESC");
// A more advanced query might be:
// SELECT n.id, n.type, n.message, n.link, n.is_read, n.created_at, u.role
// FROM notifications n
// LEFT JOIN users u ON n.user_id = u.id
// WHERE n.user_id IS NULL OR u.role = 'admin' OR n.user_id = ?
// ORDER BY created_at DESC

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();
$conn->close();

// Function to get notification icon based on type (re-using logic from customer/notifications.php)
function getNotificationIcon($type) {
    switch ($type) {
        case 'booking_status_update':
        case 'booking_confirmed':
        case 'junk_removal_confirmed':
        case 'booking_assigned_vendor':
            return 'fas fa-truck text-blue-600';
        case 'new_invoice':
        case 'payment_due':
        case 'payment_received':
        case 'payment_failed':
        case 'partial_payment':
            return 'fas fa-receipt text-green-600';
        case 'new_quote':
        case 'quote_accepted':
        case 'quote_rejected':
            return 'fas fa-file-invoice text-purple-600';
        case 'relocation_request_confirmation':
        case 'relocation_scheduled':
        case 'relocation_completed':
        case 'swap_request_confirmation':
        case 'swap_scheduled':
        case 'swap_completed':
        case 'pickup_request_confirmation':
        case 'pickup_completed':
            return 'fas fa-tools text-orange-600';
        case 'profile_update':
        case 'password_change':
        case 'new_payment_method':
        case 'account_deletion_request':
        case 'account_deletion_confirmation':
            return 'fas fa-user-cog text-indigo-600';
        case 'discount_offer':
        case 'new_feature':
            return 'fas fa-gift text-pink-600';
        case 'system_message':
        case 'system_maintenance':
            return 'fas fa-info-circle text-gray-600';
        // Admin specific types, if implemented
        case 'admin_new_user':
        case 'admin_new_vendor':
        case 'admin_error':
            return 'fas fa-exclamation-triangle text-red-600';
        default:
            return 'fas fa-bell text-gray-500';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Admin Notifications</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-bell mr-2 text-blue-600"></i>System Alerts & Updates</h2>

    <div class="flex justify-end space-x-2 mb-4">
        <button id="mark-all-read-btn" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors duration-200 text-sm">
            <i class="fas fa-eye mr-2"></i>Mark All as Read
        </button>
        <button id="delete-all-btn" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors duration-200 text-sm">
            <i class="fas fa-trash-alt mr-2"></i>Delete All
        </button>
    </div>

    <?php if (empty($notifications)): ?>
        <p class="text-gray-600 text-center p-4">No notifications for the admin dashboard.</p>
    <?php else: ?>
        <div id="notifications-list" class="space-y-4">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item flex items-start p-4 rounded-lg shadow-sm border
                    <?php echo $notification['is_read'] ? 'bg-gray-50 border-gray-200' : 'bg-blue-50 border-blue-200 font-semibold'; ?>"
                    data-id="<?php echo htmlspecialchars($notification['id']); ?>"
                    data-is-read="<?php echo $notification['is_read'] ? 'true' : 'false'; ?>">
                    <div class="flex-shrink-0 mr-4 mt-1">
                        <i class="<?php echo getNotificationIcon($notification['type']); ?> text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <p class="text-sm text-gray-500 mb-1"><?php echo (new DateTime($notification['created_at']))->format('M d, Y H:i A'); ?></p>
                        <p class="<?php echo $notification['is_read'] ? 'text-gray-700' : 'text-gray-800 font-medium'; ?> mb-2">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </p>
                        <?php if (!empty($notification['link'])): ?>
                            <button class="text-blue-600 hover:underline text-sm view-notification-link"
                                data-link="<?php echo htmlspecialchars($notification['link']); ?>"
                                data-id="<?php echo htmlspecialchars($notification['id']); ?>">
                                View Details <i class="fas fa-arrow-right text-xs ml-1"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-shrink-0 items-center space-x-2 ml-4">
                        <?php if (!$notification['is_read']): ?>
                            <button class="mark-read-btn text-blue-500 hover:text-blue-700 text-lg" title="Mark as Read">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        <?php endif; ?>
                        <button class="delete-notification-btn text-red-500 hover:text-red-700 text-lg" title="Delete Notification">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // --- Event listener for notification actions ---
    document.addEventListener('click', async function(event) {
        // Mark individual notification as read
        if (event.target.closest('.mark-read-btn')) {
            const button = event.target.closest('.mark-read-btn');
            const notificationItem = button.closest('.notification-item');
            const notificationId = notificationItem.dataset.id;

            showToast('Marking notification as read...', 'info');
            try {
                const formData = new FormData();
                formData.append('action', 'mark_read');
                formData.append('id', notificationId);

                // IMPORTANT: This will call /api/admin/notifications.php (needs to be created)
                const response = await fetch('/api/admin/notifications.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message || 'Notification marked as read.', 'success');
                    window.loadAdminSection('notifications'); // Reload section to reflect change
                } else {
                    showToast(result.message || 'Failed to mark notification as read.', 'error');
                }
            } catch (error) {
                console.error('Admin Mark as read API Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        // Delete individual notification
        if (event.target.closest('.delete-notification-btn')) {
            const button = event.target.closest('.delete-notification-btn');
            const notificationItem = button.closest('.notification-item');
            const notificationId = notificationItem.dataset.id;

            showConfirmationModal(
                'Delete Notification',
                'Are you sure you want to delete this notification? This cannot be undone.',
                async (confirmed) => {
                    if (confirmed) {
                        showToast('Deleting notification...', 'info');
                        try {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('id', notificationId);

                            // IMPORTANT: This will call /api/admin/notifications.php (needs to be created)
                            const response = await fetch('/api/admin/notifications.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message || 'Notification deleted.', 'success');
                                window.loadAdminSection('notifications'); // Reload section to reflect change
                            } else {
                                showToast(result.message || 'Failed to delete notification.', 'error');
                            }
                        } catch (error) {
                            console.error('Admin Delete notification API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Delete', // Confirm button text
                'bg-red-600' // Confirm button color
            );
        }

        // Handle "View Details" link within notification
        if (event.target.closest('.view-notification-link')) {
            const button = event.target.closest('.view-notification-link');
            const link = button.dataset.link;
            const notificationId = button.dataset.id;

            // Mark notification as read when its link is clicked
            try {
                const formData = new FormData();
                formData.append('action', 'mark_read');
                formData.append('id', notificationId);
                // IMPORTANT: This will call /api/admin/notifications.php (needs to be created)
                await fetch('/api/admin/notifications.php', { method: 'POST', body: formData });
            } catch (error) {
                console.error('Failed to mark notification as read on link click (admin):', error);
            }

            // Navigate to the linked section, potentially with parameters
            const urlParts = link.split('?');
            const section = urlParts[0];
            let params = {};
            if (urlParts.length > 1) {
                const queryString = urlParts[1];
                const paramPairs = queryString.split('&');
                paramPairs.forEach(pair => {
                    const [key, value] = pair.split('=');
                    params[key] = value;
                });
            }
            window.loadAdminSection(section, params);
        }
    });

    // --- Global buttons for all notifications ---
    const markAllReadBtn = document.getElementById('mark-all-read-btn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', async function() {
            showConfirmationModal(
                'Mark All as Read',
                'Are you sure you want to mark all notifications as read?',
                async (confirmed) => {
                    if (confirmed) {
                        showToast('Marking all as read...', 'info');
                        try {
                            const formData = new FormData();
                            formData.append('action', 'mark_read');
                            formData.append('id', 'all');

                            // IMPORTANT: This will call /api/admin/notifications.php (needs to be created)
                            const response = await fetch('/api/admin/notifications.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message || 'All notifications marked as read.', 'success');
                                window.loadAdminSection('notifications'); // Reload section
                            } else {
                                showToast(result.message || 'Failed to mark all notifications as read.', 'error');
                            }
                        } catch (error) {
                            console.error('Admin Mark all read API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Mark All', // Confirm button text
                'bg-blue-600' // Confirm button color
            );
        });
    }

    const deleteAllBtn = document.getElementById('delete-all-btn');
    if (deleteAllBtn) {
        deleteAllBtn.addEventListener('click', async function() {
            showConfirmationModal(
                'Delete All Notifications',
                'Are you sure you want to delete all notifications? This cannot be undone.',
                async (confirmed) => {
                    if (confirmed) {
                        showToast('Deleting all notifications...', 'info');
                        try {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('id', 'all');

                            // IMPORTANT: This will call /api/admin/notifications.php (needs to be created)
                            const response = await fetch('/api/admin/notifications.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message || 'All notifications deleted.', 'success');
                                window.loadAdminSection('notifications'); // Reload section
                            } else {
                                showToast(result.message || 'Failed to delete all notifications.', 'error');
                            }
                        } catch (error) {
                            console.error('Admin Delete all notifications API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Delete All', // Confirm button text
                'bg-red-600' // Confirm button color
            );
        });
    }
</script>