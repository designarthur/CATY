<?php
// index.php - Homepage

// Start a PHP session if not already started (though aibookingchat.php also does this)
// For a multi-page app, it's good practice to ensure session is started early.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary database and utility functions
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Fetch company name from system settings
$companyName = getSystemSetting('company_name');
if (!$companyName) {
    $companyName = 'Catdump'; // Fallback if not set in DB
}

// The core HTML from Homepage.html will go below, with dynamic elements.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($companyName); ?> - Your Gateway to Equipment Rental Excellence</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #2d3748;
            overflow-x: hidden;
            line-height: 1.6;
            transition: background 1.5s ease;
        }

        .container-box {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .section-box {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 4rem;
            margin-bottom: 3.5rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .section-box-alt {
            background-color: #eef2f6;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 4rem;
            margin-bottom: 3.5rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .btn-primary {
            background-color: #1a73e8;
            color: white;
            padding: 1.2rem 3.5rem;
            border-radius: 0.75rem;
            font-weight: 800;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(26, 115, 232, 0.4);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .btn-primary:hover {
            background-color: #155bb5;
            transform: translateY(-7px);
            box-shadow: 0 15px 35px rgba(26, 115, 232, 0.6);
        }
        .btn-secondary {
            background-color: transparent;
            color: #1a73e8;
            padding: 1.2rem 3.5rem;
            border-radius: 0.75rem;
            font-weight: 700;
            transition: all 0.3s ease;
            border: 2px solid #1a73e8;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .btn-secondary:hover {
            background-color: #1a73e8;
            color: white;
            transform: translateY(-7px);
            box-shadow: 0 8px 20px rgba(26, 115, 232, 0.2);
        }
        .icon-box {
            background-color: #34a853;
            color: white;
            border-radius: 50%;
            padding: 1.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .text-blue-custom {
            color: #1a73e8;
        }
        .text-green-custom {
            color: #34a853;
        }

        .animate-on-scroll {
            opacity: 0;
            transform: translateY(40px) scale(0.95);
            transition: opacity 1s ease-out, transform 1s ease-out;
        }

        .animate-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .delay-100 { transition-delay: 0.1s; animation-delay: 0.1s; }
        .delay-200 { transition-delay: 0.2s; animation-delay: 0.2s; }
        .delay-300 { transition-delay: 0.3s; animation-delay: 0.3s; }
        .delay-400 { transition-delay: 0.4s; animation-delay: 0.4s; }
        .delay-500 { transition-delay: 0.5s; animation-delay: 0.5s; }
        .delay-600 { transition-delay: 0.6s; animation-delay: 0.6s; }
        .delay-700 { transition-delay: 0.7s; animation-delay: 0.7s; }
        .delay-800 { transition-delay: 0.8s; animation-delay: 0.8s; }
        .delay-900 { transition-delay: 0.9s; animation-delay: 0.9s; }
        .delay-1000 { transition-delay: 1s; animation-delay: 1s; }

        .card-hover-effect {
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            background-color: #ffffff;
            color: #2d3748;
            border: 1px solid rgba(0, 0, 0, 0.08);
            transform-style: preserve-3d;
        }
        .card-hover-effect:hover {
            transform: translateY(-10px) scale(1.04) rotateX(2deg) rotateY(2deg);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
            background-color: #f8f9fa;
            border-color: #1a73e8;
        }
        .card-hover-effect .icon-box {
            background-color: #34a853;
            color: white;
        }

        .testimonial-card {
            background-color: #ffffff;
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .testimonial-quote {
            font-size: 1.25rem;
            font-style: italic;
            color: #4a5568;
            margin-bottom: 1.5rem;
        }
        .testimonial-author {
            font-weight: 600;
            color: #1a73e8;
        }
        .testimonial-source {
            color: #718096;
            font-size: 0.9rem;
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
            z-index: 1;
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

        .header-scrolled {
            background-color: rgba(255, 255, 255, 0.98);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .header-logo-text {
            font-size: 2.5rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        .header-logo-text img {
            height: 3.5rem;
            width: 3.5rem;
            margin-right: 0.75rem;
        }

        .how-it-works-container {
            display: flex;
            flex-direction: column;
            gap: 3rem;
        }

        .how-it-works-row {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }

        .how-it-works-image-box {
            background-color: #f8f9fa;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .how-it-works-image-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .how-it-works-image-box img {
            max-width: 90%;
            height: auto;
            border-radius: 0.5rem;
        }

        .how-it-works-content {
            flex: 1;
            text-align: center;
        }

        .how-it-works-step-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a73e8;
            margin-bottom: 0.5rem;
        }

        .how-it-works-step-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .how-it-works-step-description {
            color: #4a5568;
            font-size: 1.05rem;
            line-height: 1.7;
        }

        @media (min-width: 768px) {
            .how-it-works-row {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            .how-it-works-row:nth-child(even) {
                flex-direction: row-reverse;
            }
            .how-it-works-content {
                text-align: left;
            }
            .how-it-works-image-box {
                width: 50%;
            }
        }

        .accordion-item {
            border-bottom: 1px solid #e2e8f0;
        }
        .accordion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 1rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.25rem;
            color: #2d3748;
            transition: background-color 0.2s ease;
        }
        .accordion-header:hover {
            background-color: #f0f4f8;
        }
        .accordion-header svg {
            transition: transform 0.3s ease;
        }
        .accordion-header.active svg {
            transform: rotate(180deg);
        }
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
            padding: 0 1rem;
            color: #4a5568;
        }
        .accordion-content.open {
            max-height: 200px;
            padding-bottom: 1.5rem;
        }

        .highlight-word {
            display: inline-block;
            padding: 0.1em 0.3em;
            border-radius: 0.25em;
            transition: background-color 0.1s ease-in-out;
        }

        .highlight-word.active-highlight {
            background-color: rgba(26, 115, 232, 0.2);
            animation: pulse-highlight 0.5s infinite alternate;
        }

        @keyframes pulse-highlight {
            0% { background-color: rgba(26, 115, 232, 0.2); }
            100% { background-color: rgba(26, 115, 232, 0.4); }
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2.5rem;
        }
        @media (min-width: 1024px) {
            .contact-grid {
                grid-template-columns: 1fr 1.5fr;
            }
        }

        .contact-info-box {
            background-color: #1a73e8;
            color: white;
            border-radius: 1.5rem;
            padding: 3rem;
            box-shadow: 0 15px 40px rgba(26, 115, 232, 0.3);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 450px;
        }
        .contact-info-box h2 {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }
        .contact-info-box p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2rem;
        }
        .contact-info-box .testimonial-quote-contact {
            font-style: italic;
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            margin-top: auto;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        .contact-info-box .testimonial-author-contact {
            font-weight: 600;
            color: white;
            margin-top: 0.5rem;
        }

        .contact-form-box {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 3rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .contact-form-box .form-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            color: #2d3748;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .contact-form-box .form-input:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
        }
        .contact-form-box textarea.form-input {
            min-height: 120px;
            resize: vertical;
        }
        .contact-form-box .form-label {
            display: block;
            font-size: 1rem;
            font-weight: 500;
            color: #4a5568;
        }
        .contact-form-box .privacy-text {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 1.5rem;
        }
        .contact-form-box .privacy-text a {
            color: #1a73e8;
            text-decoration: underline;
        }
        .contact-form-box .btn-submit {
            background-color: #1a73e8;
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 0.75rem;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 115, 232, 0.4);
            width: 100%;
            margin-top: 2rem;
        }
        .contact-form-box .btn-submit:hover {
            background-color: #155bb5;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(26, 115, 232, 0.6);
        }

        .why-choose-us-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 768px) {
            .why-choose-us-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .why-choose-us-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .large-feature-card {
            grid-column: 1 / -1;
            background-color: #1a73e8;
            color: white;
            padding: 3rem;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(26, 115, 232, 0.3);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .large-feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(26, 115, 232, 0.5);
        }
        .large-feature-card .icon-wrapper {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .large-feature-card .icon {
            font-size: 2.5rem;
            color: white;
        }
        .large-feature-card h4 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        .large-feature-card p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
        }
        .large-feature-card .btn-start-trial {
            background-color: #34a853;
            color: white;
            padding: 1rem 2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(52, 168, 83, 0.4);
        }
        .large-feature-card .btn-start-trial:hover {
            background-color: #2b8e45;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(52, 168, 83, 0.6);
        }
        .large-feature-card .btn-start-trial svg {
            margin-left: 0.75rem;
        }

        .feature-card {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        .feature-card .icon-wrapper {
            background-color: #eef2f6;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .feature-card .icon {
            font-size: 2rem;
            color: #1a73e8;
        }
        .feature-card h4 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.75rem;
        }
        .feature-card p {
            font-size: 1rem;
            line-height: 1.6;
            color: #4a5568;
        }

        @media (min-width: 1024px) {
            .why-choose-us-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .large-feature-card {
                grid-column: 3 / 4;
                grid-row: 1 / 3;
            }
        }

        /* Wavy Corner Effect */
        .wavy-corners {
            position: relative;
            z-index: 1;
        }
        .wavy-corners::before, .wavy-corners::after {
            content: '';
            position: absolute;
            width: 13%;
            height: 12%;
            background-color: #e5e5f7;
            opacity: 0.5;
            background-image: repeating-radial-gradient(circle at 0 0, transparent 0, #e5e5f7 10px), repeating-linear-gradient(#45f78b55, #45f78b);
            z-index: -1;
        }

        .wavy-corners::before {
            top: 0;
            right: 0;
            clip-path: circle(66% at 100% 0);
        }

        .wavy-corners::after {
            bottom: 0;
            left: 0;
            clip-path: circle(100% at 0 149%);
        }

        /* Chat Widget Styles */
        #chat-widget {
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            height: 70vh;
            max-height: 600px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        #chat-header {
            padding: 1rem 1.5rem;
            background-color: rgba(255, 255, 255, 0.7);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            border-top-left-radius: 1.5rem;
            border-top-right-radius: 1.5rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: #2d3748;
            display: flex;
            align-items: center;
        }

        #chat-header::before {
            content: '';
            width: 12px;
            height: 12px;
            background-color: #34a853;
            border-radius: 50%;
            margin-right: 0.75rem;
            animation: pulse-live 2s infinite;
        }

        @keyframes pulse-live {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 168, 83, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(52, 168, 83, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(52, 168, 83, 0); }
        }


        #chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .chat-bubble {
            padding: 0.75rem 1.25rem;
            border-radius: 1.25rem;
            max-width: 80%;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .ai-bubble {
            background-color: #eef2f6;
            color: #2d3748;
            border-bottom-left-radius: 0.25rem;
            align-self: flex-start;
        }

        .user-bubble {
            background-color: #1a73e8;
            color: white;
            border-bottom-right-radius: 0.25rem;
            align-self: flex-end;
        }

        #chat-form {
            display: flex;
            padding: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        #chat-input {
            flex-grow: 1;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            margin-right: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        #chat-input:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
        }

        #chat-send-btn {
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #chat-send-btn:hover {
            background-color: #155bb5;
        }

        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.75rem 1.25rem;
        }
        .typing-indicator span {
            height: 8px;
            width: 8px;
            background-color: #a0aec0;
            border-radius: 50%;
            animation: bounce 1.4s infinite both;
        }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }

        /* Floating Chat Bubble */
        #chat-bubble-floating {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background-color: #1a73e8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 999;
            transform: scale(0);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        #chat-bubble-floating.visible {
            transform: scale(1);
        }
        #chat-bubble-floating svg {
            color: white;
            width: 32px;
            height: 32px;
        }

        /* Full Screen Chat Overlay */
        #chat-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        #chat-overlay.visible {
            opacity: 1;
            visibility: visible;
        }
        #chat-overlay #chat-widget-overlay {
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }
        #chat-overlay.visible #chat-widget-overlay {
            transform: scale(1);
        }
        #chat-close-btn {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            background: none;
            border: none;
            color: white;
            font-size: 2.5rem;
            cursor: pointer;
            line-height: 1;
        }

    </style>
</head>
<body class="antialiased">

    <header id="main-header" class="bg-white bg-opacity-90 backdrop-blur-sm text-gray-800 shadow-xl py-4 sticky top-0 z-50 transition-all duration-300 ease-in-out">
        <div class="container-box flex justify-between items-center">
            <div class="flex items-center">
                <img src="/assets/images/logo.png" alt="<?php echo htmlspecialchars($companyName); ?> Logo" class="h-14 w-14 mr-4 rounded-full shadow-lg">
                <div class="text-4xl font-extrabold text-blue-custom"><?php echo htmlspecialchars($companyName); ?></div>
            </div>
            <nav class="hidden md:flex items-center space-x-8">
                <a href="#how-it-works-section" class="text-gray-700 hover:text-blue-custom font-medium text-lg transition duration-300">How It Works</a>

                <div class="dropdown">
                    <a href="#services-section" class="text-gray-700 hover:text-blue-custom font-medium text-lg transition duration-300 flex items-center">
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
                    <a href="#company-section" class="text-gray-700 hover:text-blue-custom font-medium text-lg transition duration-300 flex items-center">
                        Company
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </a>
                    <div class="dropdown-content">
                        <a href="#">About Us</a>
                        <a href="#">Careers</a>
                        <a href="#">Press/Media</a>
                        <a href="#">Sustainability</a>
                        <a href="#testimonials-section">Testimonials</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#resources-section" class="text-gray-700 hover:text-blue-custom font-medium text-lg transition duration-300 flex items-center">
                        Resources
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </a>
                    <div class="dropdown-content">
                        <a href="#">Pricing & Finance</a>
                        <a href="#">Customer Resources</a>
                        <a href="#">Blog/News</a>
                        <a href="#faq-section">FAQs</a>
                        <a href="#">Support Center</a>
                        <a href="#contact-section">Contact</a>
                    </div>
                </div>
            </nav>
            <a href="/customer/login.php" class="btn-primary py-2.5 px-5 text-base shadow-md hover:shadow-lg transition duration-300">Customer Portal</a>
            <button id="mobile-menu-button" class="md:hidden p-3 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-custom">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
    </header>

    <main>
        <section id="hero-section" class="py-20 md:py-32">
            <div class="container-box">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div class="text-center lg:text-left animate-on-scroll">
                         <h1 class="text-5xl md:text-6xl font-extrabold leading-tight mb-6 text-gray-800">
                            Best Price Quotes in 60 Mins.
                            <span class="text-blue-custom" id="autotyping-text">Your AI Rental Assistant.</span>
                        </h1>
                        <p class="text-xl text-gray-700 mb-8 max-w-xl mx-auto lg:mx-0">
                           Just tell our AI what you need. We'll connect with our local network and get you the best price, guaranteed, within the hour. No more waiting, no more hassle.
                        </p>
                    </div>
                    <div id="chat-widget" class="w-full max-w-lg mx-auto lg:mx-0 animate-on-scroll delay-200">
                        <div id="chat-header">AI Booking Assistant</div>
                        <div id="chat-messages"></div>
                        <form id="chat-form">
                            <input type="text" id="chat-input" placeholder="e.g., 'I need a 20-yard dumpster for a week'" autocomplete="off">
                            <button type="submit" id="chat-send-btn">Send</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>


        <section class="container-box py-20 md:py-32">
            <div class="section-box">
                <div class="text-center mb-8 animate-on-scroll delay-100">
                    <span class="text-blue-custom text-lg font-semibold uppercase">Why Choose Us</span>
                    <h2 class="text-4xl md:text-5xl font-extrabold text-gray-800 mt-2">Why <?php echo htmlspecialchars($companyName); ?> is The Right Choice for You</h2>
                </div>
                <div class="why-choose-us-grid">
                    <div class="feature-card animate-on-scroll delay-200">
                        <div class="icon-wrapper">
                            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h4>Unmatched Speed</h4>
                        <p>Experience unparalleled efficiency. Get instant, accurate quotes and book confirmed, reliable service in minutes, not frustrating hours or days.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-300">
                        <div class="icon-wrapper">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-cpu"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>
                        </div>
                        <h4>AI-Powered Precision</h4>
                        <p>Our smart system leverages advanced AI algorithms to find the perfect solution for your specific needs, ensuring optimal efficiency and cost savings.</p>
                    </div>

                    <div class="large-feature-card animate-on-scroll delay-400">
                        <div class="icon-wrapper">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                        <h4>Best Price Guarantee</h4>
                        <p>Our innovative marketplace model ensures you always receive the most competitive pricing from a network of top local, vetted suppliers, guaranteeing exceptional value.</p>
                        <p class="mt-auto">Need financing for a large project? We offer flexible payment options and plans tailored to your needs, helping you acquire equipment faster.</p>
                        <a href="#contact-section" class="btn-start-trial mt-6">
                            Get a Free Quote
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </a>
                    </div>

                    <div class="feature-card animate-on-scroll delay-500">
                        <div class="icon-wrapper">
                           <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-briefcase"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        </div>
                        <h4>Comprehensive Solutions</h4>
                        <p>From essential dumpster rentals to specialized junk removal, get all your equipment and service needs covered in one convenient platform.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-600">
                        <div class="icon-wrapper">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        </div>
                        <h4>Total Control Dashboard</h4>
                        <p>Manage all your rentals, track payments, schedule services, and communicate with suppliers effortlessly from one intuitive, personalized dashboard.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="how-it-works-section" class="container-box py-20 md:py-32">
            <div class="section-box-alt wavy-corners">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">How <?php echo htmlspecialchars($companyName); ?> Works: Your Seamless Rental Journey</h2>
                <div class="how-it-works-container">
                    <div class="how-it-works-row animate-on-scroll delay-100">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/ai_chat_interface.png" alt="AI Chat Interface">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 1</p>
                            <h3 class="how-it-works-step-title">Chat with Our AI Booking System</h3>
                            <p class="how-it-works-step-description">Start your rental process by simply chatting with our intelligent AI booking system. Tell us your specific equipment requirements, project details, and timeline, and our AI will efficiently capture all necessary information to begin your quote request, making the initial step quick and effortless.</p>
                            <a href="#contact-section" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Start Chatting Now &rarr;</a>
                        </div>
                    </div>

                    <div class="how-it-works-row animate-on-scroll delay-300">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/price_comparison.png" alt="Price Comparison">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 2</p>
                            <h3 class="how-it-works-step-title">Best Prices from Our Partner Network</h3>
                            <p class="how-it-works-step-description">Once we have your requirements, our dedicated team immediately contacts our extensive network of trusted local partners. We leverage our relationships to secure the best possible pricing for your equipment, then list these competitive quotes directly into your personalized customer dashboard for easy review and comparison, ensuring you always get the optimal deal.</p>
                            <a href="#" class="text-blue-custom hover:underline font-medium mt-4 inline-block">View Pricing Details &rarr;</a>
                        </div>
                    </div>

                    <div class="how-it-works-row animate-on-scroll delay-500">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/dashboard_tracking.png" alt="Dashboard Tracking">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 3</p>
                            <h3 class="how-it-works-step-title">Confirm, Pay & Track Your Order</h3>
                            <p class="how-it-works-step-description">Review the pricing options on your dashboard. If you're satisfied, confirm your order directly. We'll then provide a secure payment link and add the invoice to your dashboard for convenient payment. You can track delivery status live if the partner allows, or receive direct notifications from the driver, ensuring complete transparency until delivery.</p>
                            <a href="#" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Access Your Dashboard &rarr;</a>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-20 animate-on-scroll delay-700">
                    <a href="#" class="btn-secondary inline-block">Explore Full Process</a>
                </div>
            </div>
        </section>

        <section id="services-section" class="container-box py-20 md:py-32">
            <div class="section-box">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Our Core Rental Services</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    <div class="p-10 rounded-2xl shadow-xl flex flex-col items-center text-center card-hover-effect animate-on-scroll delay-100">
                        <img src="/assets/images/dumpster_rental.png" alt="Dumpster Rentals" class="rounded-lg mb-6 shadow-md border border-gray-300">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">Dumpster Rentals</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">From extensive home cleanouts to large-scale construction projects, easily find the perfect size dumpster to efficiently handle any waste disposal need, ensuring a clean and safe site for your operations.</p>
                        <a href="#contact-section" class="text-blue-custom hover:underline font-medium flex items-center justify-center">Learn More & Get Quote <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg></a>
                    </div>
                    <div class="p-10 rounded-2xl shadow-xl flex flex-col items-center text-center card-hover-effect animate-on-scroll delay-200">
                        <img src="/assets/images/portable_toilet.png" alt="Temporary Toilets" class="rounded-lg mb-6 shadow-md border border-gray-300">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">Temporary Toilets</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">Ensure comfort and sanitation with our clean, reliable, and regularly serviced portable toilets, ideal for events, busy job sites, and emergency situations requiring immediate facilities on demand.</p>
                        <a href="#contact-section" class="text-blue-custom hover:underline font-medium flex items-center justify-center">Learn More & Get Quote <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg></a>
                    </div>
                    <div class="p-10 rounded-2xl shadow-xl flex flex-col items-center text-center card-hover-effect animate-on-scroll delay-300">
                        <img src="/assets/images/storage_container.png" alt="Storage Containers" class="rounded-lg mb-6 shadow-md border border-gray-300">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">Storage Containers</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">Secure your valuable equipment and materials with our robust, weatherproof on-site storage containers, conveniently delivered right to your specified location for maximum accessibility and protection.</p>
                        <a href="#contact-section" class="text-blue-custom hover:underline font-medium flex items-center justify-center">Learn More & Get Quote <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg></a>
                    </div>
                </div>
                <div class="text-center mt-20 animate-on-scroll delay-400">
                    <a href="#" class="btn-secondary inline-block">View All Services</a>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32 section-box-alt wavy-corners" id="beyond-rentals-section">
            <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Beyond Rentals: Comprehensive Solutions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="p-10 rounded-2xl shadow-xl flex flex-col items-center text-center card-hover-effect animate-on-scroll delay-100">
                    <img src="/assets/images/junk_removal.png" alt="Junk Removal Services" class="rounded-lg mb-6 shadow-md border border-gray-300">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Advanced Junk Removal Services</h3>
                    <p class="text-gray-600 leading-relaxed mb-6">For efficient junk removal, simply upload images and even videos of your items. Our advanced system analyzes the content to generate a precise, fair quote. If you're satisfied with the quotation, we'll generate an invoice for you to conveniently pay directly from your personalized dashboard.</p>
                    <a href="#contact-section" class="text-blue-custom hover:underline font-medium flex items-center justify-center">Learn More & Get Quote <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg></a>
                </div>
                <div class="p-10 rounded-2xl shadow-xl flex flex-col items-center text-center card-hover-effect animate-on-scroll delay-200">
                    <img src="/assets/images/relocation_swap.png" alt="Relocation & Swap Services" class="rounded-lg mb-6 shadow-md border border-gray-300">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Relocation & Swap Services</h3>
                    <p class="text-gray-600 leading-relaxed mb-6">Project running longer than expected? Need a different size unit for your evolving needs? We offer seamless relocation or swap services for your rental unit, ensuring your project stays on track and on budget without interruption.</p>
                    <a href="#contact-section" class="text-blue-custom hover:underline font-medium flex items-center justify-center">Learn More & Get Quote <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg></a>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="stats-section">
            <div class="section-box text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Our Impact in Numbers</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12">
                    <div class="animate-on-scroll delay-100">
                        <div class="icon-box mx-auto mb-6 bg-green-custom text-white">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-5xl font-extrabold text-blue-custom mb-3" data-target="10000">0</p>
                        <p class="text-xl text-gray-700">Rentals Completed</p>
                    </div>
                    <div class="animate-on-scroll delay-200">
                        <div class="icon-box mx-auto mb-6 bg-blue-custom text-white">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.105A9.763 9.763 0 0112 4c4.97 0 9 3.582 9 8z"></path></svg>
                        </div>
                        <p class="text-5xl font-extrabold text-green-custom mb-3" data-target="500">0</p>
                        <p class="text-xl text-gray-700">Verified Suppliers</p>
                    </div>
                    <div class="animate-on-scroll delay-300">
                        <div class="icon-box mx-auto mb-6 bg-green-custom text-white">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <p class="text-5xl font-extrabold text-blue-custom mb-3" data-target="150">0</p>
                        <p class="text-xl text-gray-700">Cities Served</p>
                    </div>
                    <div class="animate-on-scroll delay-400">
                        <div class="icon-box mx-auto mb-6 bg-blue-custom text-white">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 100 4m-4 12a2 2 0 100-4m14-4a2 2 0 100-4m-4 4a2 2 0 100 4m-6-4a2 2 0 100 4m-2 2a2 2 0 100 4m0-12a2 2 0 100 4"></path></svg>
                        </div>
                        <p class="text-5xl font-extrabold text-green-custom mb-3" data-target="98">0%</p>
                        <p class="text-xl text-gray-700">Customer Satisfaction</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="web-app-section">
            <div class="section-box-alt flex flex-col lg:flex-row items-center justify-between gap-16">
                <div class="lg:w-1/2 text-center lg:text-left animate-on-scroll delay-100">
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-8">Explore Our Cutting-Edge Web App</h2>
                    <p class="text-xl text-gray-700 mb-8 leading-relaxed">
                        Manage your entire rental experience from the palm of your hand. Our intuitive mobile app provides real-time tracking, instant communication with suppliers, and seamless payment processing.
                    </p>
                    <ul class="space-y-4 text-left inline-block lg:block text-gray-700 text-lg mb-10">
                        <li class="flex items-center"><svg class="w-6 h-6 mr-3 text-green-custom" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Book and manage equipment rentals anytime, anywhere.</li>
                        <li class="flex items-center"><svg class="w-6 h-6 mr-3 text-green-custom" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Receive instant notifications and updates.</li>
                        <li class="flex items-center"><svg class="w-6 h-6 mr-3 text-green-custom" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>View complete rental history and reorder with ease.</li>
                    </ul>
                    <a href="#" class="btn-primary inline-block">Download the App</a>
                </div>
                <div class="lg:w-1/2 flex justify-center items-center relative animate-on-scroll delay-300">
                    <img src="/assets/images/mobile_app_screen_1.png" alt="Mobile App Screen 1" class="w-1/2 md:w-1/3 lg:w-auto max-w-xs rounded-xl shadow-2xl transform rotate-3 translate-x-8 z-10 border-4 border-gray-300">
                    <img src="/assets/images/mobile_app_screen_2.png" alt="Mobile App Screen 2" class="w-1/2 md:w-1/3 lg:w-auto max-w-xs rounded-xl shadow-2xl transform -rotate-3 -translate-x-8 z-20 border-4 border-gray-300">
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="dashboard-section">
            <div class="section-box">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Your Personalized <?php echo htmlspecialchars($companyName); ?> Dashboard & Flexible Payments</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div class="p-10 rounded-2xl shadow-xl card-hover-effect animate-on-scroll delay-100">
                        <img src="/assets/images/dashboard_mockup.png" alt="<?php echo htmlspecialchars($companyName); ?> Dashboard" class="rounded-lg mb-6 mx-auto shadow-md border border-gray-300">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">Total Control at Your Fingertips</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">The <?php echo htmlspecialchars($companyName); ?> dashboard provides a centralized, intuitive hub for all your rental activities. Effortlessly track orders, manage service schedules, view detailed invoices, and communicate directly with suppliers, all from one convenient place. Access your complete rental history and reorder previous services with a single click, simplifying your workflow and saving you valuable time. <br><br> You can also track the delivery status of your rentals directly from your dashboard. If a partner allows live tracking, you'll see real-time updates. Otherwise, your driver will contact you directly with progress notifications.</p>
                        <a href="#" class="text-blue-custom hover:underline font-medium flex items-center justify-center">Learn More about the My Dumpster Portal <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg></a>
                    </div>
                    <div class="p-10 rounded-2xl shadow-xl card-hover-effect animate-on-scroll delay-200">
                        <img src="/assets/images/payment_options.png" alt="Flexible Payments" class="rounded-lg mb-6 mx-auto shadow-md border border-gray-300">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">Flexible & Secure Payment Solutions</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">We offer a wide variety of secure payment options to suit your preferences, including all major credit cards and convenient ACH transfers. For larger or ongoing projects, we provide flexible financing plans designed to help you manage your budget effectively without delaying crucial work. Rest assured, your financial data is always protected with state-of-the-art, bank-level security measures, giving you complete peace of mind.</p>
                        <a href="#" class="text-blue-custom hover:underline font-medium flex items-center justify-center">Learn More about Payments & Financing <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg></a>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="testimonials-section">
            <div class="section-box-alt">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">What Our Customers Say</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    <div class="testimonial-card animate-on-scroll delay-100">
                        <p class="testimonial-quote">"<?php echo htmlspecialchars($companyName); ?> transformed our project logistics. The speed and ease of finding exactly what we needed, when we needed it, at a fantastic price, was truly a game-changer. Highly recommend their service!"</p>
                        <p class="testimonial-author">- Sarah L.</p>
                        <p class="testimonial-source">Construction Manager, Apex Builds</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-200">
                        <p class="testimonial-quote">"The AI-powered quote system is incredibly accurate and fast. We saved so much time and money compared to traditional rental processes. <?php echo htmlspecialchars($companyName); ?> is now our go-to for all equipment needs."</p>
                        <p class="testimonial-author">- Mark T.</p>
                        <p class="testimonial-source">Event Coordinator, Elite Events</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-300">
                        <p class="testimonial-quote">"Their comprehensive solutions mean we don't have to juggle multiple vendors. From dumpsters to junk removal, <?php echo htmlspecialchars($companyName); ?> handles it all with professionalism and efficiency. Excellent service!"</p>
                        <p class="testimonial-author">- Jessica R.</p>
                        <p class="testimonial-source">Property Developer, Urban Living</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="faq-section">
            <div class="section-box wavy-corners">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Frequently Asked Questions</h2>
                <div class="max-w-3xl mx-auto">
                    <div class="accordion-item animate-on-scroll delay-100">
                        <div class="accordion-header" data-accordion-toggle="faq-1">
                            How does <?php echo htmlspecialchars($companyName); ?> ensure the best pricing for equipment rentals?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-1" class="accordion-content">
                            <p><?php echo htmlspecialchars($companyName); ?> leverages an extensive network of trusted local partners and an AI-powered system to compare prices and availability in real-time. Our marketplace model ensures competitive bidding among suppliers, allowing us to secure the best possible deals for your specific equipment needs. We pass these savings directly on to you, guaranteeing you never overpay.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-200">
                        <div class="accordion-header" data-accordion-toggle="faq-2">
                            Can I really track my rental delivery in real-time?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-2" class="accordion-content">
                            <p>Yes, for partners who support live tracking, you will see real-time updates of your equipment's delivery status directly on your <?php echo htmlspecialchars($companyName); ?> dashboard. If live tracking isn't available for a specific partner, the driver will contact you directly with timely notifications and updates regarding your delivery progress, ensuring you're always informed.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-300">
                        <div class="accordion-header" data-accordion-toggle="faq-3">
                            How does the AI-powered junk removal quote system work?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-3" class="accordion-content">
                            <p>For junk removal services, you can simply upload images and even short videos of the items you need removed. Our advanced AI system analyzes these visual inputs to identify the types and volume of junk. Based on this analysis, it generates a precise and fair quotation. Once you approve the quote, an invoice is generated for payment via your dashboard.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-400">
                        <div class="accordion-header" data-accordion-toggle="faq-4">
                            What payment options are available, and are they secure?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-4" class="accordion-content">
                            <p>We offer a wide range of secure payment options, including all major credit cards and ACH transfers, to suit your convenience. For larger projects, we also provide flexible financing plans to help manage your budget. All financial transactions are protected with state-of-art, bank-level security measures, giving you complete peace of mind.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-500">
                        <div class="accordion-header" data-accordion-toggle="faq-5">
                            How do I manage my rentals and invoices?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-5" class="accordion-content">
                            <p>Your personalized <?php echo htmlspecialchars($companyName); ?> dashboard serves as a centralized hub for all your rental activities. From here, you can effortlessly track current orders, manage service schedules, view detailed invoices, and communicate directly with suppliers. You can also access your complete rental history and reorder previous services with a single click, providing total control at your fingertips.</p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-16 animate-on-scroll delay-600">
                    <a href="#" class="btn-secondary inline-block">View All FAQs</a>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="contact-section">
            <div class="section-box-alt animate-on-scroll delay-100">
                <div class="contact-grid">
                    <div class="contact-info-box animate-on-scroll delay-200">
                        <div class="flex items-center mb-6">
                            <img src="/assets/images/icon_cd.png" alt="<?php echo htmlspecialchars($companyName); ?> Icon" class="h-10 w-10 mr-3 rounded-full">
                            <span class="text-xl font-semibold"><?php echo htmlspecialchars($companyName); ?> Support</span>
                        </div>
                        <h2 class="text-white">Request a call with our <br> Equipment Experts</h2>
                        <p>Request a call with our equipment experts, and let's bring your vision to life! Our team is ready to assist you in creating an unforgettable experience tailored to your needs.</p>
                        <div class="testimonial-quote-contact">
                            "<?php echo htmlspecialchars($companyName); ?> made our event unforgettable! Their attention to detail were beyond impressive."
                            <p class="testimonial-author-contact">- Fiona Jonna</p>
                            <p class="testimonial-source-contact">PS Global Partner Services</p>
                        </div>
                    </div>
                    <div class="contact-form-box animate-on-scroll delay-300">
                        <div class="flex mb-8">
                            <button class="flex-1 py-3 px-4 rounded-lg font-semibold text-blue-custom border border-blue-custom bg-blue-50 w-full">Contact via email</button>
                        </div>
                        <form class="space-y-4">
                            <div>
                                <label for="contact-first-name" class="form-label">Your first name</label>
                                <input type="text" id="contact-first-name" name="first_name" class="form-input" placeholder="Enter your first name" required>
                            </div>
                            <div>
                                <label for="contact-last-name" class="form-label">Your last name</label>
                                <input type="text" id="contact-last-name" name="last_name" class="form-input" placeholder="Enter your last name" required>
                            </div>
                            <div>
                                <label for="contact-email" class="form-label">Email</label>
                                <input type="email" id="contact-email" name="email" class="form-input" placeholder="Enter your email" required>
                            </div>
                            <div>
                                <label for="contact-message" class="form-label">How can we help you?</label>
                                <textarea id="contact-message" name="message" class="form-input" placeholder="Tell us a little about your project"></textarea>
                            </div>
                            <button type="submit" class="btn-submit">Send message</button>
                        </form>
                        <p class="privacy-text">By clicking on "send message" button, you agree to our <a href="#">Privacy Policy</a>.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box text-center wavy-corners">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-10 animate-on-scroll">Ready to Transform Your Operations?</h2>
                <p class="text-xl text-gray-700 mb-12 max-w-3xl mx-auto animate-on-scroll delay-100">
                    Experience the future of equipment rentals. Get a free, no-obligation quote in seconds and streamline your project needs with <?php echo htmlspecialchars($companyName); ?>'s powerful, intuitive platform.
                </p>
                <a href="#contact-section" class="btn-primary inline-block shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition duration-300 mb-16 animate-on-scroll delay-200">Get Started & Get a Quote</a>

                <div class="bg-gray-100 p-12 rounded-2xl shadow-xl flex flex-col md:flex-row items-center justify-center gap-10 animate-on-scroll delay-300">
                    <div class="icon-box bg-blue-custom text-white flex-shrink-0">
                        <svg class="w-14 h-14" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <div class="text-center md:text-left">
                        <h3 class="text-3xl font-bold text-gray-800 mb-4">Download Our Mobile App!</h3>
                        <p class="text-gray-700 max-w-xl leading-relaxed">
                            Access all the powerful features of your dashboard on the go. Manage your rentals, track deliveries, and communicate with suppliers anytime, anywhere, directly from your smartphone with the intuitive <?php echo htmlspecialchars($companyName); ?> mobile app. Your project management just got easier and more flexible.
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-5 mt-6 md:mt-0">
                        <a href="#" class="inline-block transform hover:scale-105 transition duration-300">
                            <img src="https://placehold.co/150x50/000000/FFFFFF?text=App+Store" alt="Download on App Store" class="rounded-lg shadow-md">
                        </a>
                        <a href="#" class="inline-block transform hover:scale-105 transition duration-300">
                            <img src="https://placehold.co/150x50/000000/FFFFFF?text=Google+Play" alt="Get it on Google Play" class="rounded-lg shadow-md">
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-20">
        <div class="container-box grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-12 text-center md:text-left">
            <div class="col-span-1 md:col-span-2 lg:col-span-1 flex flex-col items-center md:items-start">
                <div class="flex items-center mb-5">
                    <img src="/assets/images/logo_footer.png" alt="<?php echo htmlspecialchars($companyName); ?> Logo" class="h-16 w-16 mr-4 rounded-full shadow-md">
                    <div class="text-5xl font-extrabold text-blue-custom"><?php echo htmlspecialchars($companyName); ?></div>
                </div>
                <p class="leading-relaxed text-gray-400">Your premier marketplace for fast, easy, and affordable equipment rentals. We connect you with the best local deals for dumpsters, temporary toilets, storage, and heavy machinery, ensuring your projects run smoothly and efficiently.</p>
            </div>

            <div>
                <h3 class="text-xl font-bold text-white mb-6">Quick Links</h3>
                <ul class="space-y-4">
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Home</a></li>
                    <li><a href="#how-it-works-section" class="hover:text-blue-custom transition duration-200">How It Works</a></li>
                    <li><a href="#services-section" class="hover:text-blue-custom transition duration-200">Equipment Rentals</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Blog/News</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Customer Resources</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Pricing & Finance</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-bold text-white mb-6">Company</h3>
                <ul class="space-y-4">
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">About Us</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Careers</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Press/Media</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Sustainability</a></li>
                    <li><a href="#testimonials-section" class="hover:text-blue-custom transition duration-200">Testimonials</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-bold text-white mb-6">Services</h3>
                <ul class="space-y-4">
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Dumpster Rentals</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Temporary Toilets</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Storage Containers</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Junk Removal</a></li>
                    <li><a href="#" class="hover:text-blue-custom transition duration-200">Relocation & Swap</a></li>
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
                <a href="#how-it-works-section">How It Works</a>
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
                        <a href="#testimonials-section">Testimonials</a>
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
                        <a href="#faq-section">FAQs</a>
                        <a href="#">Support Center</a>
                        <a href="#contact-section">Contact</a>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <div id="chat-bubble-floating">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-message-square"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
    </div>

    <div id="chat-overlay">
        <button id="chat-close-btn">&times;</button>
        <div id="chat-widget-overlay" class="w-full max-w-lg mx-auto">
             <div id="chat-header">AI Booking Assistant</div>
             <div id="chat-messages-overlay" class="flex-grow overflow-y-auto p-6 flex flex-col gap-4 bg-white/80 backdrop-blur-md h-[60vh] max-h-[500px]"></div>
             <form id="chat-form-overlay" class="flex p-6 border-t border-gray-200 bg-white/80 backdrop-blur-md rounded-b-2xl">
                 <input type="text" id="chat-input-overlay" placeholder="e.g., 'I need a 20-yard dumpster for a week'" autocomplete="off" class="flex-grow border border-gray-300 rounded-lg py-2 px-4 mr-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                 <button type="submit" id="chat-send-btn-overlay" class="bg-blue-600 text-white rounded-lg px-6 py-2 font-semibold hover:bg-blue-700 transition-colors">Send</button>
             </form>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Background Gradient Shift on Scroll
            const gradients = [
                'linear-gradient(90deg, #00C9FF 0%, #92FE9D 100%)',
                'linear-gradient(90deg, #FDBB2D 0%, #22C1C3 100%)',
                'linear-gradient(90deg, #f8ff00 0%, #3ad59f 100%)',
                'linear-gradient(90deg, #0700b8 0%, #00ff88 100%)'
            ];

            let currentGradientIndex = 0;
            document.body.style.background = gradients[currentGradientIndex];

            window.addEventListener('scroll', () => {
                const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
                if (scrollableHeight <= 0) return;

                const scrollPercentage = (window.scrollY / scrollableHeight);

                let newGradientIndex;
                if (scrollPercentage < 0.25) {
                    newGradientIndex = 0;
                } else if (scrollPercentage < 0.5) {
                    newGradientIndex = 1;
                } else if (scrollPercentage < 0.75) {
                    newGradientIndex = 2;
                } else {
                    newGradientIndex = 3;
                }

                if (newGradientIndex !== currentGradientIndex) {
                    currentGradientIndex = newGradientIndex;
                    document.body.style.background = gradients[currentGradientIndex];
                }
            });

            // Mobile menu functionality
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

            // Animate on scroll
            const animateOnScrollElements = document.querySelectorAll('.animate-on-scroll');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

            animateOnScrollElements.forEach(element => {
                observer.observe(element);
            });

            // Number counter animation
            const counterObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        const endValue = parseInt(target.dataset.target);
                        const duration = 2000;
                        let startTimestamp = null;

                        const step = (timestamp) => {
                            if (!startTimestamp) startTimestamp = timestamp;
                            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                            let currentValue;
                            if (target.dataset.target.includes('%')) {
                                currentValue = Math.floor(progress * endValue);
                                target.textContent = currentValue + '%';
                            } else {
                                currentValue = Math.floor(progress * endValue);
                                target.textContent = currentValue.toLocaleString();
                            }

                            if (progress < 1) {
                                window.requestAnimationFrame(step);
                            }
                        };
                        window.requestAnimationFrame(step);
                        observer.unobserve(target);
                    }
                });
            }, { threshold: 0.5 });

            document.querySelectorAll('[data-target]').forEach(counter => {
                counterObserver.observe(counter);
            });

            // Header scroll effect
            const mainHeader = document.getElementById('main-header');
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 50) {
                    mainHeader.classList.add('header-scrolled');
                } else {
                    mainHeader.classList.remove('header-scrolled');
                }
            });

            // Mobile dropdown functionality
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

            // Accordion functionality
            document.querySelectorAll('.accordion-header').forEach(header => {
                header.addEventListener('click', () => {
                    const content = document.getElementById(header.dataset.accordionToggle);
                    const isActive = header.classList.contains('active');

                    document.querySelectorAll('.accordion-header.active').forEach(activeHeader => {
                        activeHeader.classList.remove('active');
                        document.getElementById(activeHeader.dataset.accordionToggle).classList.remove('open');
                    });

                    if (!isActive) {
                        header.classList.add('active');
                        content.classList.add('open');
                    }
                });
            });

            // Autotyping effect for hero text
            const autotypingTextElement = document.getElementById('autotyping-text');
            if(autotypingTextElement) {
                const phrases = ["Your AI Rental Assistant.", "For Project Success.", "For Construction Solutions.", "For Operational Excellence."];
                let phraseIndex = 0;
                let charIndex = 0;
                let isDeleting = false;
                const typingSpeed = 125;
                const deletingSpeed = 63;
                const pauseBeforeDelete = 2000;
                const pauseBeforeType = 500;

                function typeWriter() {
                    const currentPhrase = phrases[phraseIndex];
                    if (isDeleting) {
                        autotypingTextElement.textContent = currentPhrase.substring(0, charIndex - 1);
                        charIndex--;
                    } else {
                        autotypingTextElement.textContent = currentPhrase.substring(0, charIndex + 1);
                        charIndex++;
                    }

                    let currentTypingSpeed = isDeleting ? deletingSpeed : typingSpeed;

                    if (!isDeleting && charIndex === currentPhrase.length) {
                        currentTypingSpeed = pauseBeforeDelete;
                        isDeleting = true;
                    } else if (isDeleting && charIndex === 0) {
                        isDeleting = false;
                        phraseIndex = (phraseIndex + 1) % phrases.length;
                        currentTypingSpeed = pauseBeforeType;
                    }

                    setTimeout(typeWriter, currentTypingSpeed);
                }
                typeWriter();
            }

            // Chat Widget Functionality (Modified to call api/openai_chat.php)
            const chatMessages = document.getElementById('chat-messages');
            const chatForm = document.getElementById('chat-form');
            const chatInput = document.getElementById('chat-input');

            const chatOverlay = document.getElementById('chat-overlay');
            const floatingBubble = document.getElementById('chat-bubble-floating');
            const chatCloseBtn = document.getElementById('chat-close-btn');

            const chatMessagesOverlay = document.getElementById('chat-messages-overlay');
            const chatFormOverlay = document.getElementById('chat-form-overlay');
            const chatInputOverlay = document.getElementById('chat-input-overlay');


            const addMessage = (message, sender, container) => {
    const bubble = document.createElement('div');
    bubble.classList.add('chat-bubble', sender === 'user' ? 'user-bubble' : 'ai-bubble');
    // Use innerHTML and marked.parse() for rendering markdown
    bubble.innerHTML = marked.parse(message); // Change here
    container.appendChild(bubble);
    container.scrollTop = container.scrollHeight;
};

            const showTypingIndicator = (container) => {
                const indicator = document.createElement('div');
                indicator.classList.add('chat-bubble', 'ai-bubble', 'typing-indicator');
                indicator.innerHTML = '<span></span><span></span><span></span>';
                container.appendChild(indicator);
                container.scrollTop = container.scrollHeight;
            }

            const hideTypingIndicator = (container) => {
                const indicator = container.querySelector('.typing-indicator');
                if (indicator) {
                    indicator.remove();
                }
            }

            // Modified to send to api/openai_chat.php
            const getAIResponse = async (userInput, container) => {
                showTypingIndicator(container);
                try {
                    const formData = new FormData();
                    formData.append('message', userInput);

                    const response = await fetch('/api/openai_chat.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    hideTypingIndicator(container);

                    let aiDisplayMessage = data.ai_response; // This will be the conversational response

                    addMessage(aiDisplayMessage, 'ai', container);

                    // If it's a final structured summary, redirect to customer dashboard (optional, or handle message for frontend)
                    if (data.is_info_collected) {
                        // Optionally, you might redirect the user to their new dashboard after a short delay
                        // or provide a link for them to click.
                        // For now, we'll just display the message.
                        console.log("Customer info collected. User account created/updated.");
                        // You could add a direct link here if desired:
                        // setTimeout(() => { addMessage("You can now login to your dashboard here: <a href='/customer/dashboard.php' class='text-blue-600 underline'>Your Dashboard</a>", 'ai', container); }, 500);
                    }


                } catch (error) {
                    console.error('Error fetching AI response:', error);
                    hideTypingIndicator(container);
                    addMessage("Oops! There was an error connecting to the AI. Please try again later.", 'ai', container);
                }
            };

            chatForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const userMessage = chatInput.value.trim();
                if (userMessage) {
                    addMessage(userMessage, 'user', chatMessages);
                    chatInput.value = '';
                    getAIResponse(userMessage, chatMessages);
                }
            });

            chatFormOverlay.addEventListener('submit', (e) => {
                e.preventDefault();
                const userMessage = chatInputOverlay.value.trim();
                if (userMessage) {
                    addMessage(userMessage, 'user', chatMessagesOverlay);
                    chatInputOverlay.value = '';
                    getAIResponse(userMessage, chatMessagesOverlay);
                }
            });

            // Initial AI message
            setTimeout(() => {
                 addMessage("Hello! I'm the <?php echo htmlspecialchars($companyName); ?> AI assistant. Tell me what you need, and I'll get you the best price from our local partners within 60 minutes!", 'ai', chatMessages);
                 addMessage("Hello! I'm the <?php echo htmlspecialchars($companyName); ?> AI assistant. Tell me what you need, and I'll get you the best price from our local partners within 60 minutes!", 'ai', chatMessagesOverlay);
            }, 1000);

            // Floating bubble logic
            const heroSection = document.getElementById('hero-section');
            const heroObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) {
                        floatingBubble.classList.add('visible');
                    } else {
                        floatingBubble.classList.remove('visible');
                        chatOverlay.classList.remove('visible');
                    }
                });
            }, { threshold: 0.1 });

            heroObserver.observe(heroSection);

            floatingBubble.addEventListener('click', () => {
                chatOverlay.classList.add('visible');
            });

            chatCloseBtn.addEventListener('click', () => {
                chatOverlay.classList.remove('visible');
            });

            chatOverlay.addEventListener('click', (e) => {
                if (e.target === chatOverlay) {
                    chatOverlay.classList.remove('visible');
                }
            });

        });
    </script>

</body>
</html>