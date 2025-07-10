<?php
// customer/includes/footer.php
// This file holds modals and all shared JavaScript for the customer dashboard.
// It will dynamically load page content from customer/pages/ via AJAX.
?>

    <!-- Modals (Hidden by default) -->
    <!-- Logout Confirmation Modal -->
    <div id="logout-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-gray-800">
            <h3 class="text-xl font-bold mb-4">Confirm Logout</h3>
            <p class="mb-6">Are you sure you want to log out?</p>
            <div class="flex justify-end space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('logout-modal')">Cancel</button>
                <a href="/customer/logout.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Logout</a>
            </div>
        </div>
    </div>

    <!-- Delete Account Confirmation Modal -->
    <div id="delete-account-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-gray-800">
            <h3 class="text-xl font-bold mb-4 text-red-600">Confirm Account Deletion</h3>
            <p class="mb-6">This action is irreversible. Are you absolutely sure you want to delete your account?</p>
            <div class="flex justify-end space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('delete-account-modal')">Cancel</button>
                <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700" id="confirm-delete-account">Delete Account</button>
            </div>
        </div>
    </div>

    <!-- Payment Success Modal -->
    <div id="payment-success-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-center text-gray-800">
            <i class="fas fa-check-circle text-green-500 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Payment Successful!</h3>
            <p class="mb-6">Your payment has been processed successfully.</p>
            <button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" onclick="hideModal('payment-success-modal')">Great!</button>
        </div>
    </div>

    <!-- AI Chat Modal (Customer can launch AI chat for new requests) -->
    <div id="ai-chat-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg h-3/4 flex flex-col text-gray-800">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800" id="ai-chat-title">AI Assistant</h3>
                <button class="text-gray-500 hover:text-gray-700 text-2xl" onclick="hideModal('ai-chat-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="ai-chat-messages" class="flex-1 overflow-y-auto border border-gray-300 rounded-lg p-4 mb-4 custom-scroll">
                </div>
            <div class="flex">
                <input type="text" id="ai-chat-input" placeholder="Type your message..." class="flex-1 p-3 border border-gray-300 rounded-l-lg focus:ring-blue-500 focus:border-blue-500">
                <button id="ai-chat-send-btn" class="px-4 py-3 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 transition-colors duration-200">Send</button>
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Modal (NEW) -->
    <div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-gray-800">
            <h3 class="text-xl font-bold mb-4" id="confirmation-modal-title">Confirm Action</h3>
            <p class="mb-6" id="confirmation-modal-message">Are you sure you want to proceed with this action?</p>
            <div class="flex justify-end space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" id="confirmation-modal-cancel">Cancel</button>
                <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700" id="confirmation-modal-confirm">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Tutorial Overlay -->
    <div id="tutorial-overlay" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl w-11/12 max-w-3xl text-gray-800">
            <h2 class="text-2xl font-bold text-gray-800 mb-4" id="tutorial-title">Welcome to Your Dashboard!</h2>
            <p class="text-gray-700 mb-6" id="tutorial-text">
                This short tour will guide you through the key features of your <?php echo htmlspecialchars($companyName); ?> Customer Dashboard.
            </p>
            <div class="flex justify-between items-center">
                <button id="tutorial-prev-btn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 hidden">
                    <i class="fas fa-arrow-left mr-2"></i>Previous
                </button>
                <button id="tutorial-next-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Next <i class="fas fa-arrow-right ml-2"></i>
                </button>
                <button id="tutorial-end-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    End Tutorial
                </button>
            </div>
        </div>
    </div>

    <!-- Relocation Request Modal -->
    <div id="relocation-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-gray-800">
            <h3 class="text-xl font-bold mb-4">Request Relocation</h3>
            <p class="mb-4">Fixed relocation charge: <span class="font-bold text-blue-600">$40.00</span></p>
            <div class="mb-5">
                <label for="relocation-address" class="block text-sm font-medium text-gray-700 mb-2">New Destination Address</label>
                <input type="text" id="relocation-address" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Enter new address" required>
            </div>
            <div class="flex justify-end space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('relocation-request-modal')">Cancel</button>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700" onclick="confirmRelocation()">Confirm Relocation</button>
            </div>
        </div>
    </div>

    <!-- Swap Request Modal -->
    <div id="swap-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-gray-800">
            <h3 class="text-xl font-bold mb-4">Request Equipment Swap</h3>
            <p class="mb-4">Fixed swap charge: <span class="font-bold text-blue-600">$30.00</span></p>
            <p class="mb-6">Are you sure you want to request an equipment swap for this booking?</p>
            <div class="flex justify-end space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('swap-request-modal')">Cancel</button>
                <button class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700" onclick="confirmSwap()">Confirm Swap</button>
            </div>
        </div>
    </div>

    <!-- Pickup Request Modal -->
    <div id="pickup-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-gray-800">
            <h3 class="text-xl font-bold mb-4">Schedule Pickup</h3>
            <div class="mb-5">
                <label for="pickup-date" class="block text-sm font-medium text-gray-700 mb-2">Preferred Pickup Date</label>
                <input type="date" id="pickup-date" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div class="mb-5">
                <label for="pickup-time" class="block text-sm font-medium text-gray-700 mb-2">Preferred Pickup Time</label>
                <input type="time" id="pickup-time" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div class="flex justify-end space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('pickup-request-modal')">Cancel</button>
                <button class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700" onclick="confirmPickup()">Schedule Pickup</button>
            </div>
        </div>
    </div>


    <script>
        // --- Global Helper Functions ---
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
            let bgColor = 'bg-blue-500';
            if (type === 'success') bgColor = 'bg-green-500';
            if (type === 'error') bgColor = 'bg-red-500';
            if (type === 'warning') bgColor = 'bg-orange-500';

            toast.className = `p-3 rounded-lg shadow-lg text-white ${bgColor} transform transition-transform duration-300 ease-out translate-y-full opacity-0`;
            toast.textContent = message;

            toastContainer.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('translate-y-full', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            }, 10);

            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-full', 'opacity-0');
                toast.addEventListener('transitionend', () => toast.remove());
            }, 3000);
        }

        // --- Custom Confirmation Modal Logic ---
        let confirmationCallback = null;

        /**
         * Displays a custom confirmation modal.
         * @param {string} title The title of the modal.
         * @param {string} message The message to display.
         * @param {function(boolean): void} callback A function to call with true (confirmed) or false (cancelled).
         * @param {string} confirmBtnText Text for the confirm button (default 'Confirm').
         * @param {string} confirmBtnColor Tailwind CSS class for confirm button background (default 'bg-red-600').
         */
        function showConfirmationModal(title, message, callback, confirmBtnText = 'Confirm', confirmBtnColor = 'bg-red-600') {
            document.getElementById('confirmation-modal-title').textContent = title;
            document.getElementById('confirmation-modal-message').textContent = message;
            const confirmBtn = document.getElementById('confirmation-modal-confirm');
            confirmBtn.textContent = confirmBtnText;
            // Reset and set color class (remove previous color classes first)
            confirmBtn.classList.remove('bg-red-600', 'bg-green-600', 'bg-blue-600', 'bg-orange-600', 'bg-indigo-600', 'bg-purple-600', 'bg-teal-600'); // Add any other btn colors used
            confirmBtn.classList.add(confirmBtnColor);
            
            confirmationCallback = callback; // Store the callback function
            showModal('confirmation-modal');
        }

        document.getElementById('confirmation-modal-confirm').addEventListener('click', () => {
            hideModal('confirmation-modal');
            if (confirmationCallback) {
                confirmationCallback(true); // Execute callback with true for confirmation
            }
            confirmationCallback = null; // Clear the callback
        });

        document.getElementById('confirmation-modal-cancel').addEventListener('click', () => {
            hideModal('confirmation-modal');
            if (confirmationCallback) {
                confirmationCallback(false); // Execute callback with false for cancellation
            }
            confirmationCallback = null; // Clear the callback
        });


        // --- Core Navigation and Content Loading Logic ---
        const contentArea = document.getElementById('content-area'); // Assuming this ID is in dashboard.php
        const navLinksDesktop = document.querySelectorAll('.nav-link-desktop');
        const navLinksMobile = document.querySelectorAll('.nav-link-mobile');
        const welcomePrompt = document.getElementById('welcome-prompt');

        /**
         * Loads content into the main content area dynamically via AJAX.
         * @param {string} sectionId The ID of the section to load (e.g., 'dashboard', 'bookings').
         * @param {object} [params={}] Optional parameters to pass to the loaded page (e.g., booking_id).
         */
        async function loadSection(sectionId, params = {}) {
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
                contentArea.innerHTML = `
                    <div class="flex items-center justify-center h-full min-h-[300px] text-gray-500 text-lg">
                        <i class="fas fa-spinner fa-spin mr-3 text-blue-500 text-2xl"></i> Loading ${sectionId.replace('-', ' ')}...
                    </div>
                `;

                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const htmlContent = await response.text();
                contentArea.innerHTML = htmlContent;

                // Update active class for desktop links
                navLinksDesktop.forEach(link => link.classList.remove('bg-blue-700', 'text-white'));
                const activeLinkDesktop = document.querySelector(`.nav-link-desktop[data-section="${sectionId}"]`);
                if (activeLinkDesktop) {
                    activeLinkDesktop.classList.add('bg-blue-700', 'text-white');
                }

                // Update active class for mobile links
                navLinksMobile.forEach(link => link.classList.remove('bg-blue-700', 'text-white'));
                const activeLinkMobile = document.querySelector(`.nav-link-mobile[data-section="${sectionId}"]`);
                if (activeLinkMobile) {
                    activeLinkMobile.classList.add('bg-blue-700', 'text-white');
                }

                // Push state to history for back/forward navigation
                history.pushState({ section: sectionId, params: params }, '', `#${sectionId}`);

                // Re-run scripts in the loaded content if any (common for dynamic content)
                // This will re-attach listeners for dynamically loaded pages.
                const loadedScripts = contentArea.querySelectorAll('script');
                loadedScripts.forEach(script => {
                    const newScript = document.createElement('script');
                    Array.from(script.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(script.innerHTML));
                    script.parentNode.replaceChild(newScript, script);
                });


            } catch (error) {
                console.error('Error loading section:', error);
                contentArea.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full min-h-[300px] text-red-500 text-lg">
                        <i class="fas fa-exclamation-triangle mr-3 text-red-600 text-2xl"></i>
                        Failed to load section: ${sectionId.replace('-', ' ')}. Please try again.
                        <p class="text-sm text-gray-500 mt-2">Details: ${error.message}</p>
                    </div>
                `;
                showToast(`Failed to load ${sectionId.replace('-', ' ')}`, 'error');
            }
        }

        // Add event listeners to navigation links (desktop and mobile)
        navLinksDesktop.forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default link behavior
                const section = this.dataset.section;
                loadSection(section);
            });
        });

        navLinksMobile.forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default link behavior
                const section = this.dataset.section;
                loadSection(section);
            });
        });

        // Handle browser back/forward buttons
        window.addEventListener('popstate', (event) => {
            if (event.state && event.state.section) {
                loadSection(event.state.section, event.state.params);
            } else {
                loadSection('dashboard'); // Default to dashboard if no state
            }
        });


        // --- Welcome Prompt (Initial Dashboard Load) ---
        function showWelcomePrompt() {
            // userName is already set in header.php from PHP session
            const userName = "<?php echo $_SESSION['user_first_name'] ?? 'Customer'; ?>";
            welcomePrompt.textContent = `Welcome back, ${userName}!`;
            showToast(`Welcome back, ${userName}! Explore your dashboard.`, 'info');
        }


        // --- AI Chat Logic for Service Request Button ---
        /**
         * Launches the AI Chat Modal and optionally pre-populates based on service type.
         * Note: This connects to the homepage's AI chat endpoint (api/openai_chat.php)
         * to allow customer to initiate new AI-driven bookings/junk removal.
         * @param {string} serviceType 'create-booking' or 'junk-removal-service'
         */
        let aiChatHistory = []; // Local history for AI chat modal, separate from main chat.

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
            // Potentially add a serviceType hint to the backend if needed for initial prompt setup
            formData.append('initial_service_type', serviceType); // Send this if AI needs a hint for first turn

            try {
                const response = await fetch('/api/openai_chat.php', { // Endpoint to AI chat from homepage
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                // Remove loading dots
                aiChatMessagesDiv.removeChild(loadingDiv);

                addAIChatMessage(data.ai_response, 'ai');

                if (data.is_info_collected) {
                    addAIChatMessage("Your request has been submitted and your account updated! Our team will get back to you with a quote shortly.", 'ai');
                    aiChatInput.value = '';
                    aiChatInput.disabled = true;
                    aiChatSendBtn.disabled = true;
                    showToast("Service request submitted successfully! Check your dashboard for updates.", 'success');
                    // Optionally refresh the dashboard or relevant section after submission
                    // loadSection('dashboard');
                } else {
                    aiChatInput.disabled = false;
                    aiChatSendBtn.disabled = false;
                    aiChatInput.focus();
                }

            } catch (error) {
                console.error('Error in AI chat:', error);
                // Remove loading dots
                aiChatMessagesDiv.removeChild(loadingDiv);
                addAIChatMessage('Sorry, there was an error processing your request. Please try again.', 'ai');
                aiChatInput.disabled = false;
                aiChatSendBtn.disabled = false;
            }
        }

        function showAIChat(serviceType) {
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
        }
        


        // --- Service Request Dropdown Logic ---
        const serviceRequestBtn = document.getElementById('service-request-btn');
        const serviceRequestDropdown = document.getElementById('service-request-dropdown');

        if(serviceRequestBtn) { // Check if element exists
            serviceRequestBtn.addEventListener('click', function() {
                serviceRequestDropdown.classList.toggle('hidden');
            });

            // Close dropdown if clicked outside
            document.addEventListener('click', function(event) {
                if (!serviceRequestBtn.contains(event.target) && !serviceRequestDropdown.contains(event.target)) {
                    serviceRequestDropdown.classList.add('hidden');
                }
            });
        }


        // --- General Request functions for Modals (Relocation, Swap, Pickup) ---
        // These will be called from loaded 'bookings' page content, so they need to be global or attached to window
        window.confirmRelocation = function() {
            const newAddress = document.getElementById('relocation-address').value;
            if (newAddress) {
                hideModal('relocation-request-modal');
                // In a real app, send this request via AJAX to a backend endpoint
                showToast(`Relocation to "${newAddress}" requested successfully! Charges: $40.00 (Dummy)`, 'success');
            } else {
                showToast('Please enter a new destination address.', 'error');
            }
        }

        window.confirmSwap = function() {
            hideModal('swap-request-modal');
            // In a real app, send this request via AJAX to a backend endpoint
                showToast('Equipment swap requested successfully! Charges: $30.00 (Dummy)', 'success');
        }

        window.confirmPickup = function() {
            const pickupDate = document.getElementById('pickup-date').value;
            const pickupTime = document.getElementById('pickup-time').value;
            if (pickupDate && pickupTime) {
                hideModal('pickup-request-modal');
                // In a real app, send this request via AJAX to a backend endpoint
                showToast(`Pickup scheduled for ${pickupDate} at ${pickupTime}. (Dummy)`, 'success');
            } else {
                showToast('Please select a preferred pickup date and time.', 'error');
            }
        }

        // --- Tutorial Logic ---
        const tutorialSteps = [
            {
                title: "Welcome to Your Dashboard!",
                text: "This short tour will guide you through the key features of your <?php echo htmlspecialchars($companyName); ?> Customer Dashboard. Click 'Next' to continue."
            },
            {
                title: "Navigation Menu",
                text: "On the left (desktop) or bottom (mobile), you'll find the main navigation menu. Click any item to explore different sections like 'Equipment Bookings', 'Invoices', and 'Edit Profile'."
            },
            {
                title: "Quick Statistics",
                text: "The main dashboard area provides a quick overview of your active bookings, pending items, and invoice statuses."
            },
            {
                title: "Service Requests",
                text: "Use the 'Service Request' button at the top right to quickly initiate new bookings or general junk removal services via our AI chat."
            },
            {
                title: "Notifications",
                text: "The bell icon shows your unread notifications. Click it to view important updates and messages."
            },
            {
                title: "Booking Details & Tracking",
                text: "In the 'Equipment Bookings' section, click 'View Details' for any booking to see its status timeline, driver tracking (if available), and contact options."
            },
            {
                title: "Relocation, Swap, and Pickup Requests",
                text: "For delivered dumpsters, you can directly request relocation, swap, or schedule a pickup from within the booking details page."
            },
            {
                title: "Account Management",
                text: "Manage your personal information, change your password, and update payment methods in the 'Edit Profile', 'Change Password', and 'Payment Methods' sections."
            },
            {
                title: "End of Tutorial",
                text: "That's it for the tour! You can always restart the tutorial by clicking the 'Start Tutorial' button in the top right. Enjoy using your dashboard!"
            }
        ];
        let currentTutorialStep = 0;

        const tutorialOverlay = document.getElementById('tutorial-overlay');
        const tutorialTitle = document.getElementById('tutorial-title');
        const tutorialText = document.getElementById('tutorial-text');
        const tutorialPrevBtn = document.getElementById('tutorial-prev-btn');
        const tutorialNextBtn = document.getElementById('tutorial-next-btn');
        const tutorialEndBtn = document.getElementById('tutorial-end-btn');
        const startTutorialBtn = document.getElementById('start-tutorial-btn');

        function showTutorialStep(stepIndex) {
            if (stepIndex >= 0 && stepIndex < tutorialSteps.length) {
                currentTutorialStep = stepIndex;
                tutorialTitle.textContent = tutorialSteps[stepIndex].title;
                tutorialText.textContent = tutorialSteps[stepIndex].text;

                tutorialPrevBtn.classList.toggle('hidden', currentTutorialStep === 0);
                tutorialNextBtn.classList.toggle('hidden', currentTutorialStep === tutorialSteps.length - 1);
                tutorialEndBtn.textContent = (currentTutorialStep === tutorialSteps.length - 1) ? 'Got It!' : 'End Tutorial';

                showModal('tutorial-overlay');
            }
        }
        window.startTutorial = function() { showTutorialStep(0); } // Make global

        if(startTutorialBtn) { // Ensure button exists before attaching listener
            startTutorialBtn.addEventListener('click', window.startTutorial);
        }

        if(tutorialNextBtn) {
            tutorialNextBtn.addEventListener('click', () => {
                if (currentTutorialStep < tutorialSteps.length - 1) {
                    showTutorialStep(currentTutorialStep + 1);
                }
            });
        }

        if(tutorialPrevBtn) {
            tutorialPrevBtn.addEventListener('click', () => {
                if (currentTutorialStep > 0) {
                    showTutorialStep(currentTutorialStep - 1);
                }
            });
        }

        if(tutorialEndBtn) {
            tutorialEndBtn.addEventListener('click', () => {
                hideModal('tutorial-overlay');
            });
        }


        // Load the dashboard content by default when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            // Check for hash in URL to load specific section, otherwise default to dashboard
            const initialHash = window.location.hash.substring(1);
            if (initialHash && document.querySelector(`.nav-link-desktop[data-section="${initialHash}"]`)) {
                loadSection(initialHash);
            } else {
                loadSection('dashboard');
            }

            // Show welcome prompt on first visit (using sessionStorage)
            if (!sessionStorage.getItem('welcomeShown')) {
                showWelcomePrompt();
                sessionStorage.setItem('welcomeShown', 'true');
            }
        });
    </script>