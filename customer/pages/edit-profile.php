<?php
// customer/pages/edit-profile.php

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
$user_data = [];
$update_message = '';
$update_type = ''; // 'success' or 'error'

// Fetch current user data
$stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number, address, city, state, zip_code FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    // Should not happen if user is logged in, but as a fallback
    echo '<div class="text-red-500 text-center p-8">User data not found.</div>';
    exit;
}
$stmt->close();

// Handle form submission for profile update via AJAX (simulated for now, actual API endpoint will process)
// This PHP file serves the HTML, actual update logic will be in api/customer/profile.php
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Edit Profile</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 max-w-2xl mx-auto">
    <?php if ($update_message): ?>
        <div class="mb-4 p-3 rounded-lg <?php echo $update_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($update_message); ?>
        </div>
    <?php endif; ?>
    <form id="edit-profile-form">
        <div class="mb-5 text-center">
            <img src="https://placehold.co/120x120/E0E7FF/4F46E5?text=<?php echo substr($user_data['first_name'] ?? 'J', 0, 1) . substr($user_data['last_name'] ?? 'D', 0, 1); ?>" alt="Profile Picture" class="w-32 h-32 rounded-full mx-auto border-4 border-blue-300 object-cover">
            <input type="file" id="profile-photo-upload" class="hidden" accept="image/*">
            <label for="profile-photo-upload" class="mt-4 inline-block px-4 py-2 bg-blue-100 text-blue-700 rounded-lg cursor-pointer hover:bg-blue-200 transition-colors duration-200">
                <i class="fas fa-camera mr-2"></i>Upload Photo
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div>
                <label for="first-name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                <input type="text" id="first-name" name="first_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="last-name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                <input type="text" id="last-name" name="last_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" id="email" name="email" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input type="tel" id="phone" name="phone_number" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="mb-5">
            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
            <input type="text" id="address" name="address" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>" required>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                <input type="text" id="city" name="city" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="state" class="block text-sm font-medium text-gray-700 mb-2">State</label>
                <input type="text" id="state" name="state" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['state'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="zip" class="block text-sm font-medium text-gray-700 mb-2">Zip Code</label>
                <input type="text" id="zip" name="zip_code" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['zip_code'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="py-3 px-6 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-lg font-semibold">
                <i class="fas fa-save mr-2"></i>Save Changes
            </button>
        </div>
    </form>
</div>

<script>
    // Client-side validation function for phone number
    function isValidPhoneNumber(phone) {
        // Updated regex to be more flexible, matching common phone number formats.
        // Allows for formats like (123) 456-7890, 123-456-7890, 123 456 7890, 1234567890
        const re = /^\(?(\d{3})\)?[-\s]?(\d{3})[-\s]?(\d{4})$/;
        return re.test(phone);
    }

    const editProfileForm = document.getElementById('edit-profile-form');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent default form submission

            const formData = new FormData(this); // Get all form data
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const firstNameInput = document.getElementById('first-name');
            const lastNameInput = document.getElementById('last-name');
            const addressInput = document.getElementById('address');
            const cityInput = document.getElementById('city');
            const stateInput = document.getElementById('state');
            const zipInput = document.getElementById('zip');

            // Client-side validation checks
            if (!firstNameInput.value.trim() || !lastNameInput.value.trim() || !addressInput.value.trim() || !cityInput.value.trim() || !stateInput.value.trim() || !zipInput.value.trim()) {
                showToast('All fields are required.', 'error');
                return;
            }
            if (!emailInput.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
                showToast('Please enter a valid email address.', 'error');
                emailInput.focus();
                return;
            }
            if (!phoneInput.value || !isValidPhoneNumber(phoneInput.value)) {
                showToast('Please enter a valid phone number (e.g., 123-456-7890).', 'error');
                phoneInput.focus();
                return;
            }

            // Simulate AJAX call to a backend API endpoint for profile update
            showToast('Saving changes...', 'info');

            try {
                const response = await fetch('/api/customer/profile.php', {
                    method: 'POST',
                    body: formData // FormData will correctly set Content-Type for file uploads too (if implemented)
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message || 'Profile updated successfully!', 'success');
                    // Update welcome message in header if name changed
                    if (window.parent.document.getElementById('welcome-prompt')) {
                        window.parent.document.getElementById('welcome-prompt').textContent = `Welcome back, ${formData.get('first_name')}!`;
                    }
                    // Since the session might need to be reloaded for full effect on other pages,
                    // and we want to ensure the data on this page is fresh, we can reload the section.
                    loadSection('edit-profile'); // This re-fetches data from the DB.
                } else {
                    showToast(result.message || 'Failed to update profile.', 'error');
                }
            } catch (error) {
                console.error('Profile update API Error:', error);
                showToast('An error occurred during profile update. Please try again.', 'error');
            }
        });
    }

    // Profile photo upload preview (client-side only, backend needed for saving)
    const profilePhotoUpload = document.getElementById('profile-photo-upload');
    if (profilePhotoUpload) {
        profilePhotoUpload.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('#edit-profile-form img').src = e.target.result;
                    showToast('Photo selected. Click "Save Changes" to upload and save to your profile.', 'info');
                };
                reader.readAsDataURL(file);
            }
        });
    }
</script>