<?php
// customer/dashboard.php - Main Customer Dashboard Page

// Include essential files for session, database, and common functions
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in as a customer
require_login('customer', '/customer/login.php');

// Fetch company name for display in various parts
$companyName = getSystemSetting('company_name');
if (!$companyName) {
    $companyName = 'Catdump'; // Fallback if not set in DB
}

// User data from session (these are used in header.php and footer.php)
$user_id = $_SESSION['user_id'];
$user_first_name = $_SESSION['user_first_name'];
$user_last_name = $_SESSION['user_last_name'];
$user_email = $_SESSION['user_email'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($companyName); ?> Customer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #2d3748;
            overflow-x: hidden; /* Prevent horizontal scrolling */
        }
        #dashboard-wrapper {
            display: flex;
            flex-grow: 1;
            width: 100%;
        }
        #content-area {
            flex-grow: 1;
            padding: 1.5rem;
            background-color: #f0f4f8;
            overflow-y: auto; /* Enable scrolling for content */
        }
        /* Mobile fixed bottom nav padding for main content */
        @media (max-width: 767px) {
            body {
                padding-bottom: 64px; /* Space for the fixed bottom nav on mobile */
            }
        }
        /* Custom scrollbar for content area */
        .custom-scroll::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: #c8d3f6; /* Lighter blue for track */
            border-radius: 10px;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #8498f7; /* Medium blue for thumb */
            border-radius: 10px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #6a7ecc; /* Slightly darker blue on hover */
        }
        /* Hide scrollbar for junk removal steps (if used directly in pages) */
        .scroll-hidden::-webkit-scrollbar {
            display: none;
        }
        .scroll-hidden {
            -ms-overflow-style: none; /* IE and Edge */
            scrollbar-width: none; /* Firefox */
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
<body class="flex flex-col md:flex-row min-h-screen">

    <script>
        // --- Global Helper Functions (defined upfront to ensure availability) ---
        function showModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function hideModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

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

        // Custom Confirmation Modal Logic - Must be global
        let confirmationCallback = null;
        function showConfirmationModal(title, message, callback, confirmBtnText = 'Confirm', confirmBtnColor = 'bg-red-600') {
            document.getElementById('confirmation-modal-title').textContent = title;
            document.getElementById('confirmation-modal-message').textContent = message;
            const confirmBtn = document.getElementById('confirmation-modal-confirm');
            confirmBtn.textContent = confirmBtnText;
            confirmBtn.classList.remove('bg-red-600', 'bg-green-600', 'bg-blue-600', 'bg-orange-600', 'bg-indigo-600', 'bg-purple-600', 'bg-teal-600');
            confirmBtn.classList.add(confirmBtnColor);
            
            confirmationCallback = callback;
            showModal('confirmation-modal');
        }

        // --- Core Navigation and Content Loading Logic for Customer Dashboard ---
        // Defined here to ensure it's always globally available before any AJAX content is loaded.
        const contentArea = document.getElementById('content-area'); // Will be set after DOMContentLoaded
        const navLinksDesktop = document.querySelectorAll('.nav-link-desktop'); // Will be set after DOMContentLoaded
        const navLinksMobile = document.querySelectorAll('.nav-link-mobile'); // Will be set after DOMContentLoaded

        window.loadCustomerSection = async function(sectionId, params = {}) {
            // Re-fetch references if they are null, for safety on initial call or after dynamic re-parsing
            const currentContentArea = document.getElementById('content-area');
            const currentNavLinksDesktop = document.querySelectorAll('.nav-link-desktop');
            const currentNavLinksMobile = document.querySelectorAll('.nav-link-mobile');

            let url = `/customer/pages/${sectionId}.php`;
            let queryString = new URLSearchParams(params).toString();
            if (queryString) {
                url += '?' + queryString;
            }

            // Handle special cases first
            if (sectionId === 'logout') {
                showModal('logout-modal');
                return;
            } else if (sectionId === 'delete-account') {
                showModal('delete-account-modal');
                return;
            }

            try {
                // Show a loading indicator in content area
                if (currentContentArea) {
                    currentContentArea.innerHTML = `
                        <div class="flex items-center justify-center h-full min-h-[300px] text-gray-500 text-lg">
                            <i class="fas fa-spinner fa-spin mr-3 text-blue-500 text-2xl"></i> Loading ${sectionId.replace('-', ' ')}...
                        </div>
                    `;
                }


                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const htmlContent = await response.text();
                if (currentContentArea) {
                    currentContentArea.innerHTML = htmlContent;
                }
                

                // Update active class for desktop links
                currentNavLinksDesktop.forEach(link => link.classList.remove('bg-blue-700', 'text-white'));
                const activeLinkDesktop = document.querySelector(`.nav-link-desktop[data-section="${sectionId}"]`);
                if (activeLinkDesktop) {
                    activeLinkDesktop.classList.add('bg-blue-700', 'text-white');
                }

                // Update active class for mobile links
                currentNavLinksMobile.forEach(link => link.classList.remove('bg-blue-700', 'text-white'));
                const activeLinkMobile = document.querySelector(`.nav-link-mobile[data-section="${sectionId}"]`);
                if (activeLinkMobile) {
                    activeLinkMobile.classList.add('bg-blue-700', 'text-white');
                }

                // Push state to history for back/forward navigation
                history.pushState({ section: sectionId, params: params }, '', `#${sectionId}`);

                // Re-run scripts in the loaded content if any (common for dynamic content)
                // This is crucial for event listeners and other JS in the loaded page fragments
                if (currentContentArea) {
                    currentContentArea.querySelectorAll('script').forEach(oldScript => {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                }


            } catch (error) {
                console.error('Error loading customer section:', error);
                if (currentContentArea) {
                    currentContentArea.innerHTML = `
                        <div class="flex flex-col items-center justify-center h-full min-h-[300px] text-red-500 text-lg">
                            <i class="fas fa-exclamation-triangle mr-3 text-red-600 text-2xl"></i>
                            Failed to load section: ${sectionId.replace('-', ' ')}. Please try again.
                            <p class="text-sm text-gray-500 mt-2">Details: ${error.message}</p>
                        </div>
                    `;
                }
                showToast(`Failed to load ${sectionId.replace('-', ' ')}`, 'error');
            }
        };

        // --- AI Chat Logic for Service Request Button (Made Global) ---
        window.showAIChat = async function(serviceType) {
            const aiChatTitle = document.getElementById('ai-chat-title');
            const aiChatMessagesDiv = document.getElementById('ai-chat-messages');
            const aiChatInput = document.getElementById('ai-chat-input');
            const aiChatSendBtn = document.getElementById('ai-chat-send-btn');

            aiChatMessagesDiv.innerHTML = ''; // Clear previous messages
            aiChatInput.value = '';
            aiChatInput.disabled = false;
            aiChatSendBtn.disabled = false;

            if (serviceType === 'create-booking') {
                aiChatTitle.textContent = 'AI Assistant - Create Booking';
                addAIChatMessage("Hello! I can help you create a new equipment booking. Are you looking for a dumpster, temporary toilet, storage container, or handwash station? Is this for residential or commercial use?", 'ai');
            } else if (serviceType === 'junk-removal-service') {
                aiChatTitle.textContent = 'AI Assistant - Junk Removal';
                addAIChatMessage("Hello! I can help you with junk removal. Please describe the items you need removed, or even better, upload some images or a short video!", 'ai');
            }
            showModal('ai-chat-modal');

            // Add event listener for AI chat modal's send button
            aiChatSendBtn.onclick = () => {
                const message = aiChatInput.value.trim();
                if (message) {
                    addAIChatMessage(message, 'user');
                    aiChatInput.value = '';
                    sendAIChatMessageToApi(message, serviceType);
                }
            };
            aiChatInput.onkeydown = (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    aiChatSendBtn.click();
                }
            };
        };

        // Helper for AI chat messages
        function addAIChatMessage(message, sender) {
            const aiChatMessagesDiv = document.getElementById('ai-chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('text-gray-700', 'mb-2');
            if (sender === 'user') {
                messageDiv.classList.add('text-right');
                messageDiv.innerHTML = `<span class="font-semibold text-green-600">You:</span> ${message}`;
            } else {
                messageDiv.innerHTML = `<span class="font-semibold text-blue-600">AI:</span> ${message}`;
            }
            aiChatMessagesDiv.appendChild(messageDiv);
            aiChatMessagesDiv.scrollTop = aiChatMessagesDiv.scrollHeight;
        }

        async function sendAIChatMessageToApi(message, serviceType) {
            const aiChatInput = document.getElementById('ai-chat-input');
            const aiChatSendBtn = document.getElementById('ai-chat-send-btn');
            const aiChatMessagesDiv = document.getElementById('ai-chat-messages');

            // Show loading dots
            const loadingDiv = document.createElement('div');
            loadingDiv.classList.add('text-gray-700', 'mb-2');
            loadingDiv.innerHTML = '<span class="font-semibold text-blue-600">AI:</span> <i class="fas fa-spinner fa-spin text-blue-500"></i>';
            aiChatMessagesDiv.appendChild(loadingDiv);
            aiChatMessagesDiv.scrollTop = aiChatMessagesDiv.scrollHeight;
            aiChatInput.disabled = true;
            aiChatSendBtn.disabled = true;

            const formData = new FormData();
            formData.append('message', message);
            formData.append('initial_service_type', serviceType);

            try {
                const response = await fetch('/api/openai_chat.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                aiChatMessagesDiv.removeChild(loadingDiv); // Remove loading dots

                addAIChatMessage(data.ai_response, 'ai');

                if (data.is_info_collected) {
                    addAIChatMessage("Your request has been submitted and your account updated! Our team will get back to you with a quote shortly.", 'ai');
                    aiChatInput.value = '';
                    aiChatInput.disabled = true;
                    aiChatSendBtn.disabled = true;
                    showToast("Service request submitted successfully! Check your dashboard for updates.", 'success');
                } else {
                    aiChatInput.disabled = false;
                    aiChatSendBtn.disabled = false;
                    aiChatInput.focus();
                }

            } catch (error) {
                console.error('Error in AI chat:', error);
                aiChatMessagesDiv.removeChild(loadingDiv);
                addAIChatMessage('Sorry, there was an error processing your request. Please try again.', 'ai');
                aiChatInput.disabled = false;
                aiChatSendBtn.disabled = false;
            }
        }

        // --- Other Global Request functions for Modals (Relocation, Swap, Pickup) ---
        window.confirmRelocation = function() {
            const newAddress = document.getElementById('relocation-address').value;
            if (newAddress) {
                hideModal('relocation-request-modal');
                showToast(`Relocation to "${newAddress}" requested successfully! Charges: $40.00 (Dummy)`, 'success');
            } else {
                showToast('Please enter a new destination address.', 'error');
            }
        }

        window.confirmSwap = function() {
            hideModal('swap-request-modal');
            showToast('Equipment swap requested successfully! Charges: $30.00 (Dummy)', 'success');
        }

        window.confirmPickup = function() {
            const pickupDate = document.getElementById('pickup-date').value;
            const pickupTime = document.getElementById('pickup-time').value;
            if (pickupDate && pickupTime) {
                hideModal('pickup-request-modal');
                showToast(`Pickup scheduled for ${pickupDate} at ${pickupTime}. (Dummy)`, 'success');
            } else {
                showToast('Please select a preferred pickup date and time.', 'error');
            }
        }


    </script>


    <?php include __DIR__ . '/includes/sidebar.php'; // Includes the sidebar navigation ?>

    <div class="flex-1 flex flex-col">
        <?php include __DIR__ . '/includes/header.php'; // Includes the main content top bar ?>

        <main id="content-area" class="flex-1 p-8 overflow-y-auto custom-scroll">
            <div class="flex items-center justify-center h-full min-h-[300px] text-gray-500 text-lg">
                <i class="fas fa-spinner fa-spin mr-3 text-blue-500 text-2xl"></i> Loading Dashboard...
            </div>
        </main>
    </div>

    <div id="toast-container"></div>

    <?php include __DIR__ . '/includes/footer.php'; // Includes modals and global JS functions ?>

    <script>
        // --- Event Listeners and Initial Load Logic for Customer Dashboard ---
        // This script block runs AFTER all HTML content and PHP includes are processed.

        // Add event listeners for confirmation modal buttons (if footer loaded them as hidden)
        document.addEventListener('DOMContentLoaded', () => {
            const confirmBtn = document.getElementById('confirmation-modal-confirm');
            const cancelBtn = document.getElementById('confirmation-modal-cancel');
            if (confirmBtn && !confirmBtn.dataset.listenerAdded) { // Prevent adding multiple listeners
                confirmBtn.addEventListener('click', () => {
                    hideModal('confirmation-modal');
                    if (confirmationCallback) {
                        confirmationCallback(true);
                    }
                    confirmationCallback = null;
                });
                confirmBtn.dataset.listenerAdded = 'true';
            }
            if (cancelBtn && !cancelBtn.dataset.listenerAdded) {
                cancelBtn.addEventListener('click', () => {
                    hideModal('confirmation-modal');
                    if (confirmationCallback) {
                        confirmationCallback(false);
                    }
                    confirmationCallback = null;
                });
                cancelBtn.dataset.listenerAdded = 'true';
            }
        });


        // Add event listeners to navigation links (desktop and mobile)
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.nav-link-desktop, .nav-link-mobile').forEach(link => {
                link.addEventListener('click', function(event) {
                    event.preventDefault(); // Prevent default link behavior
                    const section = this.dataset.section;
                    window.loadCustomerSection(section);
                });
            });
        });


        // Handle browser back/forward buttons
        window.addEventListener('popstate', (event) => {
            if (event.state && event.state.section) {
                window.loadCustomerSection(event.state.section, event.state.params);
            } else {
                window.loadCustomerSection('dashboard'); // Default to dashboard if no state
            }
        });

        // Initial page load based on URL hash or default to dashboard
        document.addEventListener('DOMContentLoaded', () => {
            const initialHash = window.location.hash.substring(1);
            if (initialHash) {
                const urlParams = new URLSearchParams(window.location.search);
                const params = Object.fromEntries(urlParams.entries()); // Convert URLSearchParams to object
                window.loadCustomerSection(initialHash, params);
            } else {
                window.loadCustomerSection('dashboard'); // Default page
            }
            
            // Show welcome prompt on first visit (using sessionStorage)
            if (!sessionStorage.getItem('welcomeShown')) {
                const userName = "<?php echo $_SESSION['user_first_name'] ?? 'Customer'; ?>";
                showToast(`Welcome back, ${userName}! Explore your dashboard.`, 'info');
                sessionStorage.setItem('welcomeShown', 'true');
            }
        });

        // --- Service Request Dropdown Logic (from header.php) ---
        document.addEventListener('DOMContentLoaded', function() {
            const serviceRequestBtn = document.getElementById('service-request-btn');
            const serviceRequestDropdown = document.getElementById('service-request-dropdown');

            if(serviceRequestBtn) {
                serviceRequestBtn.addEventListener('click', function() {
                    serviceRequestDropdown.classList.toggle('hidden');
                });

                document.addEventListener('click', function(event) {
                    if (!serviceRequestBtn.contains(event.target) && !serviceRequestDropdown.contains(event.target)) {
                        serviceRequestDropdown.classList.add('hidden');
                    }
                });
            }
        });

        // --- Tutorial Logic (from footer.php) ---
        // These global functions are already defined above, but the logic to trigger them needs to be re-attached or exist.
        // Assuming tutorialSteps, tutorialOverlay, etc. are global variables or fetched within the footer script.
        // The event listeners for tutorial buttons will be handled by the script re-execution in footer.php when it's loaded.
        // Ensure that the 'start-tutorial-btn' has an onclick that calls window.startTutorial()
        // Or re-attach the event listener here.

        document.addEventListener('DOMContentLoaded', () => {
            const startTutorialBtn = document.getElementById('start-tutorial-btn');
            if (startTutorialBtn && !startTutorialBtn.dataset.listenerAdded) {
                startTutorialBtn.addEventListener('click', window.startTutorial);
                startTutorialBtn.dataset.listenerAdded = 'true';
            }
        });
        
        
        // --- Event Delegation for Dynamically Loaded Content ---
        // Attaches a single listener to the static content area
        document.addEventListener('DOMContentLoaded', () => { // Ensure this part of the script runs once after the main DOM is ready
            const contentAreaElement = document.getElementById('content-area'); // Get the main content area element

            if (contentAreaElement) {
                contentAreaElement.addEventListener('click', function(event) {
                    // Handle "View Invoice Details" button click (from list)
                    if (event.target.closest('.view-invoice-details')) {
                        const button = event.target.closest('.view-invoice-details');
                        if (typeof window.showInvoiceDetails === 'function') {
                            window.showInvoiceDetails(button.dataset.invoiceId);
                        }
                    }
                    // Handle "Pay Now" button click (from list or detail page)
                    else if (event.target.closest('.show-payment-form-btn')) { // This class is on both list and detail page buttons
                        const button = event.target.closest('.show-payment-form-btn');
                        if (typeof window.showPaymentForm === 'function') {
                            window.showPaymentForm(button.dataset.invoiceId, button.dataset.amount);
                        }
                    }
                    // Handle "Back to Invoice Details" button click (from payment form)
                    else if (event.target.closest('#payment-form-view button.bg-gray-200')) { // Targeting the back button specifically
                        // Assuming this button's onclick is now removed, or we just need its class/id
                        // It currently has onclick="hidePaymentForm()" which should now be handled via delegation
                        // Make sure its onclick is removed in invoices.php (if it isn't already handled by delegation below)
                        // If its onclick is kept, then window.hidePaymentForm needs to be global.
                        // For consistency, let's keep it handled by delegation.
                        if (typeof window.hidePaymentForm === 'function') {
                            window.hidePaymentForm();
                        }
                    }
                });
            }

            // Also, update direct onclicks if they still exist for "Back to Invoice Details" or "Back to Invoices" from payment form
            // Ensure these use the global functions directly or are handled by delegation
            // For example: <button onclick="window.hidePaymentForm()">
            // The safest bet is to remove all inline onclicks and handle via delegation.
            // Let's ensure the back button for invoices uses window.hideInvoiceDetails
            // and the back button for payment form uses window.hidePaymentForm.
            // These functions are already set as window. functions in invoices.php
            // The HTML: <button class="mb-4 px-4 py-2 bg-gray-200 ... " onclick="hideInvoiceDetails()">
            // Needs to be: <button class="mb-4 px-4 py-2 bg-gray-200 ... " onclick="window.hideInvoiceDetails()">

            // Similarly for the payment form's back button:
            // <button class="mb-4 px-4 py-2 bg-gray-200 ... " onclick="hidePaymentForm()">
            // Needs to be: <button class="mb-4 px-4 py-2 bg-gray-200 ... " onclick="window.hidePaymentForm()">

            // Let's update invoices.php one last time to fix these onclicks to use window.
            // Or, better, use data attributes and delegate. For now, let's fix the specific onclicks directly.
        });
    </script>
</body>
</html>