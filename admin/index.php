<?php
// admin/index.php - Admin Dashboard Main Page

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php'; // For is_logged_in() and has_role()
require_once __DIR__ . '/../includes/functions.php'; // For getSystemSetting()

// Redirect if not logged in or not an admin
require_login('admin', '/admin/login.php'); // Redirect to admin login if not admin

// Fetch company name for dynamic display in header/sidebar
$companyName = getSystemSetting('company_name');
if (!$companyName) {
    $companyName = 'Catdump'; // Fallback if not set in DB
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars($companyName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        #dashboard-wrapper {
            display: flex;
            flex-grow: 1;
        }
        #main-content-area {
            flex-grow: 1;
            padding: 1.5rem;
            background-color: #f0f4f8;
        }
        .nav-link-desktop.active {
            background-color: #1a73e8; /* Active background for desktop */
            color: white;
        }
        .nav-link-mobile.active {
            background-color: #1a73e8; /* Active background for mobile */
            color: white;
        }
        .custom-scroll::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Toast styles */
        #toast-container {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            z-index: 9999;
            display: flex;
            flex-direction: column-reverse; /* New toasts appear on top */
            gap: 0.5rem;
        }
        .toast {
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0;
            transform: translateY(100%);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            min-width: 250px;
            max-width: 350px;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .toast.bg-success { background-color: #48bb78; } /* Green */
        .toast.bg-error { background-color: #ef4444; } /* Red */
        .toast.bg-info { background-color: #3b82f6; } /* Blue */
        .toast.bg-warning { background-color: #f59e0b; } /* Orange */

    </style>
</head>
<body class="antialiased">
    <?php include __DIR__ . '/includes/header.php'; // Corrected path ?>

    <div id="dashboard-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; // Corrected path ?>

        <main id="main-content-area" class="p-6 md:p-8">
            <div class="flex items-center justify-center h-full min-h-[300px] text-gray-500 text-lg">
                <i class="fas fa-spinner fa-spin mr-3 text-blue-500 text-2xl"></i> Loading Dashboard...
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; // Corrected path ?>

    <script>
        // --- Global Helper Functions for Toast and Modals ---
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || (() => {
                const div = document.createElement('div');
                div.id = 'toast-container';
                div.className = 'fixed bottom-4 right-4 z-50 space-y-2';
                document.body.appendChild(div);
                return div;
            })();

            const toast = document.createElement('div');
            let bgColorClass = 'bg-info';
            if (type === 'success') bgColorClass = 'bg-success';
            if (type === 'error') bgColorClass = 'bg-error';
            if (type === 'warning') bgColorClass = 'bg-warning';

            toast.className = `toast ${bgColorClass}`;
            toast.textContent = message;

            toastContainer.appendChild(toast);

            // Trigger reflow to enable transition
            void toast.offsetWidth;

            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
                toast.addEventListener('transitionend', () => toast.remove());
            }, 3000);
        }

        function showModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function hideModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // --- Custom Confirmation Modal Logic (Admin Version) ---
        let confirmationCallback = null;

        function showConfirmationModal(title, message, callback, confirmBtnText = 'Confirm', confirmBtnColor = 'bg-red-600') {
            document.getElementById('admin-confirmation-modal-title').textContent = title;
            document.getElementById('admin-confirmation-modal-message').textContent = message;
            const confirmBtn = document.getElementById('admin-confirmation-modal-confirm');
            confirmBtn.textContent = confirmBtnText;
            confirmBtn.classList.remove('bg-red-600', 'bg-green-600', 'bg-blue-600', 'bg-orange-600', 'bg-indigo-600', 'bg-purple-600', 'bg-teal-600');
            confirmBtn.classList.add(confirmBtnColor);

            confirmationCallback = callback;
            showModal('admin-confirmation-modal');
        }

        document.getElementById('admin-confirmation-modal-confirm').addEventListener('click', () => {
            hideModal('admin-confirmation-modal');
            if (confirmationCallback) {
                confirmationCallback(true);
            }
            confirmationCallback = null;
        });

        document.getElementById('admin-confirmation-modal-cancel').addEventListener('click', () => {
            hideModal('admin-confirmation-modal');
            if (confirmationCallback) {
                confirmationCallback(false);
            }
            confirmationCallback = null;
        });


        // --- Core Navigation and Content Loading Logic ---
        const mainContentArea = document.getElementById('main-content-area');
        const navLinks = document.querySelectorAll('.nav-link-desktop, .nav-link-mobile'); // Select both types of links

        /**
         * Loads content into the main content area dynamically via AJAX.
         * @param {string} sectionId The ID of the section to load (e.g., 'dashboard', 'users').
         * @param {object} [params={}] Optional parameters to pass to the loaded page.
         */
        window.loadAdminSection = async function(sectionId, params = {}) { // Made global
            let url = `/admin/pages/${sectionId}.php`;
            let queryString = new URLSearchParams(params).toString();
            if (queryString) {
                url += '?' + queryString;
            }

            // Handle logout action locally before fetching page
            if (sectionId === 'logout') {
                showModal('admin-logout-modal');
                return;
            }

            try {
                mainContentArea.innerHTML = `
                    <div class="flex items-center justify-center h-full min-h-[300px] text-gray-500 text-lg">
                        <i class="fas fa-spinner fa-spin mr-3 text-blue-500 text-2xl"></i> Loading ${sectionId.replace('-', ' ')}...
                    </div>
                `;

                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const htmlContent = await response.text();
                mainContentArea.innerHTML = htmlContent;

                // Update active class for navigation links
                navLinks.forEach(link => link.classList.remove('active')); // Remove from all
                const activeLink = document.querySelector(`[data-section="${sectionId}"]`);
                if (activeLink) {
                    activeLink.classList.add('active'); // Add to the current one
                }

                // Update URL hash for direct linking and browser history
                history.pushState({ section: sectionId, params: params }, '', `#${sectionId}`);

                // Re-run scripts in the loaded content
                mainContentArea.querySelectorAll('script').forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });

            } catch (error) {
                console.error('Error loading admin section:', error);
                mainContentArea.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full min-h-[300px] text-red-500 text-lg">
                        <i class="fas fa-exclamation-triangle mr-3 text-red-600 text-2xl"></i>
                        Failed to load section: ${sectionId.replace('-', ' ')}. Please try again.
                        <p class="text-sm text-gray-500 mt-2">Details: ${error.message}</p>
                    </div>
                `;
                showToast(`Failed to load ${sectionId.replace('-', ' ')}`, 'error');
            }
        }

        // Add event listeners to navigation links
        navLinks.forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                const section = this.dataset.section;
                window.loadAdminSection(section);
            });
        });

        // Handle browser back/forward buttons for dynamic content
        window.addEventListener('popstate', (event) => {
            if (event.state && event.state.section) {
                window.loadAdminSection(event.state.section, event.state.params);
            } else {
                window.loadAdminSection('dashboard'); // Default to dashboard if no state
            }
        });

        // Initial page load based on URL hash or default to dashboard
        document.addEventListener('DOMContentLoaded', () => {
            const initialHash = window.location.hash.substring(1);
            if (initialHash && document.querySelector(`[data-section="${initialHash}"]`)) {
                const urlParams = new URLSearchParams(window.location.search);
                const params = Object.fromEntries(urlParams.entries()); // Convert URLSearchParams to object
                window.loadAdminSection(initialHash, params);
            } else {
                window.loadAdminSection('dashboard'); // Default page
            }
        });
    </script>
</body>
</html>