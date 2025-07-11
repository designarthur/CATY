<?php
// customer/login.php - Customer Login and Signup Page

// Include necessary database, function, and session utilities
require_once __DIR__ . '/../includes/db.php'; // Correct path to db.php
require_once __DIR__ . '/../includes/functions.php'; // Correct path to functions.php
require_once __DIR__ . '/../includes/session.php'; // Correct path to session.php

// Redirect if already logged in (optional, but good UX)
if (is_logged_in()) {
    redirect('/customer/dashboard.php'); // Redirect to dashboard if already authenticated
}

// Fetch company name from system settings for dynamic display
$companyName = getSystemSetting('company_name');
if (!$companyName) {
    $companyName = 'Catdump'; // Fallback if not set in DB
}

$login_error = '';
$signup_success_message = '';
$signup_error = '';

// --- Handle Login Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'login') {
    $email = trim($_POST['login-email'] ?? '');
    $password = trim($_POST['login-password'] ?? '');

    if (empty($email) || empty($password)) {
        $login_error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (verifyPassword($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_first_name'] = $user['first_name'];
                $_SESSION['user_last_name'] = $user['last_name'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('/admin/index.php');
                } else { // default to customer dashboard
                    redirect('/customer/dashboard.php');
                }
            } else {
                $login_error = 'Invalid email or password.';
            }
        } else {
            $login_error = 'Invalid email or password.';
        }
        $stmt->close();
    }
}

// --- Handle Signup Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'signup') {
    // Honeypot check for bots
    if (!empty($_POST['honeypot'])) {
        // Silently ignore bot submission
        exit();
    }

    $firstName = trim($_POST['signup-firstname'] ?? '');
    $lastName = trim($_POST['signup-lastname'] ?? '');
    $email = trim($_POST['signup-email'] ?? '');
    $phone = trim($_POST['signup-phone'] ?? '');
    $password = trim($_POST['signup-password'] ?? '');

    // Server-side validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($password)) {
        $signup_error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $signup_error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/', $phone)) {
        $signup_error = 'Please enter a valid 10-digit phone number.';
    } elseif (strlen($password) < 8) {
        $signup_error = 'Password must be at least 8 characters long.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $signup_error = 'An account with this email already exists.';
        } else {
            // Hash the password
            $hashed_password = hashPassword($password);

            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, password, role) VALUES (?, ?, ?, ?, ?, 'customer')");
            $stmt->bind_param("sssss", $firstName, $lastName, $email, $phone, $hashed_password);

            if ($stmt->execute()) {
                // Account created successfully
                $signup_success_message = 'Account created successfully! A confirmation link has been sent to your email address.';

                // Send account creation email
                $loginLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/customer/login.php";
                ob_start(); // Start output buffering to capture email template HTML
                include __DIR__ . '/../includes/mail_templates/account_creation_email.php';
                $emailBody = ob_get_clean(); // Get the buffered content

                sendEmail($email, "Welcome to {$companyName}!", $emailBody, "Welcome to {$companyName}! Your temporary password is: {$password}. Login here: {$loginLink}");

                // Optionally, automatically log in the user after signup
                // For this project, since AI creates accounts and then users log in,
                // we'll just show success and let them manually log in.
                // If direct login is needed, uncomment and adjust:
                /*
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_first_name'] = $firstName;
                $_SESSION['user_last_name'] = $lastName;
                $_SESSION['user_role'] = 'customer';
                redirect('/customer/dashboard.php');
                */

            } else {
                $signup_error = 'Error creating account. Please try again.';
                error_log("Signup error: " . $stmt->error);
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal - <?php echo htmlspecialchars($companyName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .form-wrapper {
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }

        .form-hidden {
            transform: translateX(150%);
            opacity: 0;
            position: absolute;
            pointer-events: none;
        }

        .form-active {
            transform: translateX(0);
            opacity: 1;
            position: relative;
        }

        .toggle-button {
            transition: all 0.3s ease;
        }

        .toggle-button.active {
            background-color: #1a73e8;
            color: white;
            box-shadow: 0 4px 14px 0 rgba(26, 115, 232, 0.39);
        }

        .benefit-item svg {
            color: #34a853;
        }

        .container-box {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .text-blue-custom {
            color: #1a73e8;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #ffffff;
            min-width: 180px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1001;
            border-radius: 0.5rem;
            overflow: hidden;
            top: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
        }

        .dropdown-content a {
            color: #2d3748;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            font-weight: 500;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .dropdown-content a:hover {
            background-color: #eef2f6;
            color: #1a73e8;
        }

        .dropdown:hover .dropdown-content {
            display: block;
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        .mobile-nav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        .mobile-nav-overlay.open {
            opacity: 1;
            visibility: visible;
        }
        .mobile-nav-content {
            background-color: #ffffff;
            padding: 3rem;
            border-radius: 1.5rem;
            text-align: center;
            transform: translateY(-50px);
            opacity: 0;
            transition: transform 0.5s ease, opacity 0.5s ease;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .mobile-nav-overlay.open .mobile-nav-content {
            transform: translateY(0);
            opacity: 1;
        }
        .mobile-nav-content a {
            color: #2d3748;
            transition: color 0.3s ease;
            font-size: 2rem;
            font-weight: 600;
        }
        .mobile-nav-content a:hover {
            color: #1a73e8;
        }
        .mobile-dropdown-content {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
        .mobile-dropdown-content.open {
            max-height: 300px;
            opacity: 1;
        }
        .mobile-dropdown-content a {
            padding: 0.75rem 0;
            color: #4a5568;
            font-size: 1.5rem;
        }
        .mobile-dropdown-content a:hover {
            color: #1a73e8;
        }

        /* Form Validation Styles */
        .error-message {
            color: #e53e3e;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        .input-error {
            border-color: #e53e3e !important;
            box-shadow: 0 0 0 1px #e53e3e;
        }
        .success-prompt {
            text-align: center;
            padding: 2rem;
            background-color: #f0fff4;
            border: 1px solid #9ae6b4;
            border-radius: 1rem;
            color: #2f855a;
        }
        .honeypot {
            position: absolute;
            left: -5000px;
        }
    </style>
</head>
<body class="antialiased text-gray-800">

    <header id="main-header" class="bg-white shadow-md py-4">
        <div class="container-box flex justify-between items-center">
            <a href="/" class="flex items-center">
                <img src="/assets/images/logo.png" alt="<?php echo htmlspecialchars($companyName); ?> Logo" class="h-14 w-14 mr-4 rounded-full shadow-lg">
                <span class="text-4xl font-extrabold text-blue-custom"><?php echo htmlspecialchars($companyName); ?></span>
            </a>
            <nav class="hidden md:flex items-center space-x-8">
                <a href="/#how-it-works-section" class="text-gray-700 hover:text-blue-custom font-medium text-lg transition duration-300">How It Works</a>

                <div class="dropdown">
                    <a href="/#services-section" class="text-gray-700 hover:text-blue-custom font-medium text-lg transition duration-300 flex items-center">
                        Services
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </a>
                    <div class="dropdown-content">
                        <a href="#">Dumpster Rentals</a>
                        <a href="#">Temporary Toilets</a>
                        <a href="#">Storage Containers</a>
                        <a href="#">Junk Removal</a>
                        <a href="#">Relocation & Swap</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" class="text-gray-700 hover:text-blue-custom font-medium text-lg transition duration-300 flex items-center">
                        Company
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </a>
                    <div class="dropdown-content">
                        <a href="#">About Us</a>
                        <a href="#">Careers</a>
                        <a href="#">Press/Media</a>
                        <a href="#">Sustainability</a>
                        <a href="/#testimonials-section">Testimonials</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" class="text-gray-700 hover:text-blue-custom font-medium text-lg transition duration-300 flex items-center">
                        Resources
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </a>
                    <div class="dropdown-content">
                        <a href="#">Pricing & Finance</a>
                        <a href="#">Customer Resources</a>
                        <a href="#">Blog/News</a>
                        <a href="/#faq-section">FAQs</a>
                        <a href="#">Support Center</a>
                        <a href="/#contact-section">Contact</a>
                    </div>
                </div>
            </nav>
            <button id="mobile-menu-button" class="md:hidden p-3 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-custom">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center p-4">
        <div class="w-full max-w-6xl mx-auto my-8">
            <div class="bg-white rounded-3xl shadow-2xl flex flex-col lg:flex-row overflow-hidden">

                <div class="w-full lg:w-1/2 p-8 md:p-12 bg-gradient-to-br from-blue-50 to-gray-50 flex flex-col justify-center">
                    <div class="max-w-md mx-auto text-center lg:text-left">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 leading-tight">Your Project Command Center</h2>
                        <p class="text-gray-600 mb-8">Signing up gives you access to your personalized dashboard. It’s more than just an account; it’s the easiest way to manage all your rental needs from one place.</p>

                        <ul class="space-y-5 text-left">
                            <li class="flex items-start benefit-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 mr-3 flex-shrink-0 mt-1" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Faster Quotes & Booking</h3>
                                    <p class="text-gray-500 text-sm">Save your details and get the best local prices even faster. Re-book past orders with a single click.</p>
                                </div>
                            </li>
                            <li class="flex items-start benefit-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 mr-3 flex-shrink-0 mt-1" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Track Everything</h3>
                                    <p class="text-gray-500 text-sm">Monitor your delivery status in real-time and view your complete rental history and invoices anytime.</p>
                                </div>
                            </li>
                            <li class="flex items-start benefit-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 mr-3 flex-shrink-0 mt-1" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Exclusive Offers</h3>
                                    <p class="text-gray-500 text-sm">Get access to special pricing and promotions available only to our registered customers.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 p-8 md:p-12 flex flex-col justify-center">
                    <div class="max-w-md mx-auto w-full">
                        <div class="bg-gray-100 rounded-full p-1.5 flex mb-8">
                            <button id="show-login" class="toggle-button w-1/2 p-2.5 rounded-full font-semibold text-gray-500 <?php echo (!empty($signup_success_message) || (!empty($_POST['form_type']) && $_POST['form_type'] === 'signup')) ? '' : 'active'; ?>">Log In</button>
                            <button id="show-signup" class="toggle-button w-1/2 p-2.5 rounded-full font-semibold text-gray-500 <?php echo (!empty($signup_success_message) || (!empty($_POST['form_type']) && $_POST['form_type'] === 'signup')) ? 'active' : ''; ?>">Sign Up</button>
                        </div>

                        <div id="form-container" class="relative">
                            <div id="login-form-wrapper" class="form-wrapper <?php echo (!empty($signup_success_message) || (!empty($_POST['form_type']) && $_POST['form_type'] === 'signup')) ? 'form-hidden' : 'form-active'; ?>">
                                <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome Back!</h2>
                                <p class="text-gray-500 mb-6">Log in to access your dashboard.</p>
                                <?php if ($login_error): ?>
                                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <span class="block sm:inline"><?php echo htmlspecialchars($login_error); ?></span>
                                    </div>
                                <?php endif; ?>
                                <form id="login-form" method="POST" novalidate>
                                    <input type="hidden" name="form_type" value="login">
                                    <div class="space-y-5">
                                        <div>
                                            <label for="login-email" class="font-medium text-gray-700">Email Address</label>
                                            <input type="email" id="login-email" name="login-email" required class="w-full mt-2 px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <p class="error-message" id="login-email-error"></p>
                                        </div>
                                        <div>
                                            <label for="login-password" class="font-medium text-gray-700">Password</label>
                                            <input type="password" id="login-password" name="login-password" required class="w-full mt-2 px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                             <p class="error-message" id="login-password-error"></p>
                                        </div>
                                        <div class="text-right">
                                            <a href="#" class="text-sm font-medium text-blue-600 hover:underline">Forgot Password?</a>
                                        </div>
                                        <div>
                                            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">Log In</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div id="signup-form-wrapper" class="form-wrapper <?php echo (!empty($signup_success_message) || (!empty($_POST['form_type']) && $_POST['form_type'] === 'signup')) ? 'form-active' : 'form-hidden'; ?>">
                                <h2 class="text-2xl font-bold text-gray-900 mb-2">Create Your Account</h2>
                                <p class="text-gray-500 mb-6">Get started with your free account today.</p>
                                <?php if ($signup_error): ?>
                                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <span class="block sm:inline"><?php echo htmlspecialchars($signup_error); ?></span>
                                    </div>
                                <?php endif; ?>
                                <form id="signup-form" method="POST" novalidate>
                                     <input type="hidden" name="form_type" value="signup">
                                     <input type="text" name="honeypot" class="honeypot" aria-hidden="true">
                                    <div class="space-y-4">
                                        <div class="flex flex-col sm:flex-row sm:space-x-4 space-y-4 sm:space-y-0">
                                            <div class="w-full">
                                                <label for="signup-firstname" class="font-medium text-gray-700">First Name</label>
                                                <input type="text" id="signup-firstname" name="signup-firstname" required class="w-full mt-2 px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <p class="error-message" id="signup-firstname-error"></p>
                                            </div>
                                            <div class="w-full">
                                                <label for="signup-lastname" class="font-medium text-gray-700">Last Name</label>
                                                <input type="text" id="signup-lastname" name="signup-lastname" required class="w-full mt-2 px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <p class="error-message" id="signup-lastname-error"></p>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="signup-email" class="font-medium text-gray-700">Email Address</label>
                                            <input type="email" id="signup-email" name="signup-email" required class="w-full mt-2 px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <p class="error-message" id="signup-email-error"></p>
                                        </div>
                                        <div>
                                            <label for="signup-phone" class="font-medium text-gray-700">Phone Number</label>
                                            <input type="tel" id="signup-phone" name="signup-phone" required class="w-full mt-2 px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <p class="error-message" id="signup-phone-error"></p>
                                        </div>
                                        <div>
                                            <label for="signup-password" class="font-medium text-gray-700">Password</label>
                                            <input type="password" id="signup-password" name="signup-password" required class="w-full mt-2 px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <p class="error-message" id="signup-password-error"></p>
                                        </div>
                                        <div>
                                            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">Create Account</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                             <div id="success-prompt" class="form-wrapper <?php echo (!empty($signup_success_message)) ? 'form-active' : 'form-hidden'; ?>">
                                <div class="success-prompt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h2 class="text-2xl font-bold mb-2">Account Created!</h2>
                                    <p><?php echo htmlspecialchars($signup_success_message); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-20 mt-auto">
        <div class="container-box grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-12 text-center md:text-left">
            <div class="col-span-1 md:col-span-2 lg:col-span-1 flex flex-col items-center md:items-start">
                <div class="flex items-center mb-5">
                    <img src="/assets/images/logo_footer.png" alt="<?php echo htmlspecialchars($companyName); ?> Logo" class="h-16 w-16 mr-4 rounded-full shadow-md">
                    <div class="text-5xl font-extrabold text-blue-custom"><?php echo htmlspecialchars($companyName); ?></div>
                </div>
                <p class="leading-relaxed text-gray-400">Your premier marketplace for fast, easy, and affordable equipment rentals.</p>
            </div>

            <div>
                <h3 class="text-xl font-bold text-white mb-6">Quick Links</h3>
                <ul class="space-y-4">
                    <li><a href="/" class="hover:text-blue-custom transition duration-200">Home</a></li>
                    <li><a href="/#how-it-works-section" class="hover:text-blue-custom transition duration-200">How It Works</a></li>
                    <li><a href="/#services-section" class="hover:text-blue-custom transition duration-200">Equipment Rentals</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Blog/News</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-bold text-white mb-6">Company</h3>
                <ul class="space-y-4">
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">About Us</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Careers</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Press/Media</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Testimonials</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-bold text-white mb-6">Services</h3>
                <ul class="space-y-4">
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Dumpster Rentals</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Temporary Toilets</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Storage Containers</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Junk Removal</a></li>
                </ul>
            </div>
        </div>
        <div class="container-box text-center mt-20 pt-10 border-t border-gray-800">
            <p class="text-gray-400">&copy; 2025 <?php echo htmlspecialchars($companyName); ?>. All rights reserved.</p>
        </div>
    </footer>

    <div id="mobile-nav-overlay" class="mobile-nav-overlay">
        <button id="close-mobile-menu" class="absolute top-8 right-8 text-gray-600 hover:text-blue-custom">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <div class="mobile-nav-content">
            <nav class="flex flex-col space-y-8">
                <a href="/#how-it-works-section">How It Works</a>
                <div class="relative">
                    <button data-dropdown-toggle="mobile-services" class="w-full flex items-center justify-center text-2xl font-semibold">
                        Services <svg data-dropdown-arrow="mobile-services" class="w-6 h-6 ml-2 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="mobile-services" class="mobile-dropdown-content">
                        <a href="#">Dumpster Rentals</a>
                        <a href="#">Temporary Toilets</a>
                        <a href="#">Storage Containers</a>
                        <a href="#">Junk Removal</a>
                        <a href="#">Relocation & Swap</a>
                    </div>
                </div>
                <div class="relative">
                    <button data-dropdown-toggle="mobile-company" class="w-full flex items-center justify-center text-2xl font-semibold">
                        Company <svg data-dropdown-arrow="mobile-company" class="w-6 h-6 ml-2 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="mobile-company" class="mobile-dropdown-content">
                        <a href="#">About Us</a>
                        <a href="#">Careers</a>
                        <a href="#">Press/Media</a>
                        <a href="#">Sustainability</a>
                        <a href="/#testimonials-section">Testimonials</a>
                    </div>
                </div>
                 <div class="relative">
                    <button data-dropdown-toggle="mobile-resources" class="w-full flex items-center justify-center text-2xl font-semibold">
                        Resources <svg data-dropdown-arrow="mobile-resources" class="w-6 h-6 ml-2 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="mobile-resources" class="mobile-dropdown-content">
                        <a href="#">Pricing & Finance</a>
                        <a href="#">Customer Resources</a>
                        <a href="#">Blog/News</a>
                        <a href="/#faq-section">FAQs</a>
                        <a href="#">Support Center</a>
                        <a href="/#contact-section">Contact</a>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const showLoginBtn = document.getElementById('show-login');
            const showSignupBtn = document.getElementById('show-signup');
            const loginFormWrapper = document.getElementById('login-form-wrapper');
            const signupFormWrapper = document.getElementById('signup-form-wrapper');
            const successPrompt = document.getElementById('success-prompt');

            // Determine initial form state based on PHP messages
            const loginActive = <?php echo json_encode(empty($signup_success_message) && (empty($_POST['form_type']) || $_POST['form_type'] === 'login')); ?>;
            if (loginActive) {
                loginFormWrapper.classList.add('form-active');
                loginFormWrapper.classList.remove('form-hidden');
                signupFormWrapper.classList.add('form-hidden');
                signupFormWrapper.classList.remove('form-active');
                successPrompt.classList.add('form-hidden');
                showLoginBtn.classList.add('active');
                showSignupBtn.classList.remove('active');
            } else if (<?php echo json_encode(!empty($signup_success_message) || (!empty($_POST['form_type']) && $_POST['form_type'] === 'signup')); ?>) {
                signupFormWrapper.classList.add('form-active');
                signupFormWrapper.classList.remove('form-hidden');
                loginFormWrapper.classList.add('form-hidden');
                loginFormWrapper.classList.remove('form-active');
                if (!<?php echo json_encode(!empty($signup_success_message)); ?>) { // If it's a signup attempt with error, keep success prompt hidden
                    successPrompt.classList.add('form-hidden');
                } else {
                     successPrompt.classList.remove('form-hidden'); // Show success prompt if signup was successful
                     signupFormWrapper.classList.remove('form-active'); // Hide signup form itself if success
                }
                showSignupBtn.classList.add('active');
                showLoginBtn.classList.remove('active');
            }


            // Form toggle logic (client-side)
            showLoginBtn.addEventListener('click', () => {
                loginFormWrapper.classList.remove('form-hidden');
                loginFormWrapper.classList.add('form-active');
                signupFormWrapper.classList.remove('form-active');
                signupFormWrapper.classList.add('form-hidden');
                successPrompt.classList.add('form-hidden'); // Hide success prompt if switching to login

                showLoginBtn.classList.add('active');
                showSignupBtn.classList.remove('active');
            });

            showSignupBtn.addEventListener('click', () => {
                signupFormWrapper.classList.remove('form-hidden');
                signupFormWrapper.classList.add('form-active');
                loginFormWrapper.classList.remove('form-active');
                loginFormWrapper.classList.add('form-hidden');
                successPrompt.classList.add('form-hidden'); // Hide success prompt if switching to signup

                showSignupBtn.classList.add('active');
                showLoginBtn.classList.remove('active');
            });

            // --- Client-side Validation Logic ---
            // This is for instant feedback. Server-side validation (already implemented in PHP) is crucial for security.

            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');

            const setError = (id, message) => {
                const input = document.getElementById(id);
                const errorDisplay = document.getElementById(`${id}-error`);
                input.classList.add('input-error');
                errorDisplay.innerText = message;
                errorDisplay.style.display = 'block';
            };

            const clearError = (id) => {
                const input = document.getElementById(id);
                const errorDisplay = document.getElementById(`${id}-error`);
                input.classList.remove('input-error');
                errorDisplay.style.display = 'none';
            };

            const validateEmail = (email) => {
                const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(String(email).toLowerCase());
            };

            const validatePhone = (phone) => {
                // Allows for various formats like (123) 456-7890, 123-456-7890, 123 456 7890, 1234567890
                const re = /^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/;
                return re.test(phone);
            }

            // Client-side validation for Login Form
            loginForm.addEventListener('submit', function(e) {
                // Clear previous errors
                ['login-email', 'login-password'].forEach(id => clearError(id));

                const email = document.getElementById('login-email').value.trim();
                const password = document.getElementById('login-password').value.trim();

                let clientSideValid = true;

                if (email === '') {
                    setError('login-email', 'Email address is required.');
                    clientSideValid = false;
                } else if (!validateEmail(email)) {
                    setError('login-email', 'Please enter a valid email address.');
                    clientSideValid = false;
                }

                if (password === '') {
                    setError('login-password', 'Password is required.');
                    clientSideValid = false;
                }

                if (!clientSideValid) {
                    e.preventDefault(); // Prevent form submission if client-side validation fails
                }
                // Server-side validation will also run regardless
            });

            // Client-side validation for Signup Form
            signupForm.addEventListener('submit', function(e) {
                // Clear previous errors
                ['signup-firstname', 'signup-lastname', 'signup-email', 'signup-phone', 'signup-password'].forEach(id => clearError(id));

                const honeypot = document.querySelector('#signup-form input[name="honeypot"]').value;
                if (honeypot) {
                    e.preventDefault(); // Prevent form submission if honeypot is filled
                    return;
                }

                const firstName = document.getElementById('signup-firstname').value.trim();
                const lastName = document.getElementById('signup-lastname').value.trim();
                const email = document.getElementById('signup-email').value.trim();
                const phone = document.getElementById('signup-phone').value.trim();
                const password = document.getElementById('signup-password').value.trim();

                let clientSideValid = true;

                if (firstName === '') {
                    setError('signup-firstname', 'First name is required.');
                    clientSideValid = false;
                }
                if (lastName === '') {
                    setError('signup-lastname', 'Last name is required.');
                    clientSideValid = false;
                }
                if (email === '') {
                    setError('signup-email', 'Email address is required.');
                    clientSideValid = false;
                } else if (!validateEmail(email)) {
                    setError('signup-email', 'Please enter a valid email address.');
                    clientSideValid = false;
                }
                if (phone === '') {
                    setError('signup-phone', 'Phone number is required.');
                    clientSideValid = false;
                } else if (!validatePhone(phone)) {
                     setError('signup-phone', 'Please enter a valid phone number (e.g., 123-456-7890).');
                    clientSideValid = false;
                }
                if (password === '') {
                    setError('signup-password', 'Password is required.');
                    clientSideValid = false;
                } else if (password.length < 8) {
                    setError('signup-password', 'Password must be at least 8 characters long.');
                    clientSideValid = false;
                }

                if (!clientSideValid) {
                    e.preventDefault(); // Prevent form submission if client-side validation fails
                }
                // Server-side validation will also run regardless
            });


            // Mobile menu functionality from homepage
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const closeMobileMenuButton = document.getElementById('close-mobile-menu');
            const mobileNavOverlay = document.getElementById('mobile-nav-overlay');

            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', () => {
                    mobileNavOverlay.classList.add('open');
                });
            }

            if (closeMobileMenuButton) {
                closeMobileMenuButton.addEventListener('click', () => {
                    mobileNavOverlay.classList.remove('open');
                });
            }

            if (mobileNavOverlay) {
                mobileNavOverlay.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        mobileNavOverlay.classList.remove('open');
                    });
                });
            }

            document.querySelectorAll('[data-dropdown-toggle]').forEach(toggle => {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = toggle.dataset.dropdownToggle;
                    const targetContent = document.getElementById(targetId);
                    const arrowIcon = toggle.querySelector('[data-dropdown-arrow]');

                    if (targetContent) {
                        targetContent.classList.toggle('open');
                        if(arrowIcon) {
                            arrowIcon.classList.toggle('rotate-180');
                        }
                    }
                });
            });
        });
    </script>

</body>
</html>
