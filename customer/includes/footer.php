<?php
// customer/includes/footer.php
// This file holds modals and all shared JavaScript for the customer dashboard.
// It will dynamically load page content from customer/pages/ via AJAX.
?>

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

    <div id="payment-success-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-center text-gray-800">
            <i class="fas fa-check-circle text-green-500 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Payment Successful!</h3>
            <p class="mb-6">Your payment has been processed successfully.</p>
            <button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" onclick="hideModal('payment-success-modal')">Great!</button>
        </div>
    </div>

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
            <div id="ai-chat-file-upload-section" class="hidden mb-4 p-3 border border-gray-300 rounded-lg bg-gray-50">
                <label for="ai-chat-file-input" class="block text-sm font-medium text-gray-700 mb-2">Attach Files (Images/Videos for Junk Removal)</label>
                <input type="file" id="ai-chat-file-input" name="media_files[]" multiple class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <div id="ai-chat-selected-files" class="mt-2 text-xs text-gray-600"></div>
            </div>
            <div class="flex">
                <input type="text" id="ai-chat-input" placeholder="Type your message..." class="flex-1 p-3 border border-gray-300 rounded-l-lg focus:ring-blue-500 focus:border-blue-500">
                <button id="ai-chat-send-btn" class="px-4 py-3 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 transition-colors duration-200">Send</button>
            </div>
        </div>
    </div>

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