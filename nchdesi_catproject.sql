-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 10, 2025 at 02:05 PM
-- Server version: 8.0.42-0ubuntu0.22.04.1
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nchdesi_catproject`
--

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `api_key_value` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `api_keys`
--

INSERT INTO `api_keys` (`id`, `service_name`, `api_key_value`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'OpenAI', 'YOUR_LIVE_OPENAI_KEY', 1, '2025-07-09 16:10:57', '2025-07-09 16:10:57'),
(2, 'Braintree_Public', 'YOUR_LIVE_BRAINTREE_PUBLIC_KEY', 1, '2025-07-09 16:10:57', '2025-07-09 16:10:57'),
(3, 'Braintree_Private', 'YOUR_LIVE_BRAINTREE_PRIVATE_KEY', 1, '2025-07-09 16:10:57', '2025-07-09 16:10:57'),
(4, 'Braintree_MerchantId', 'YOUR_LIVE_BRAINTREE_MERCHANT_ID', 1, '2025-07-09 16:10:57', '2025-07-09 16:10:57');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `user_id` int NOT NULL,
  `vendor_id` int DEFAULT NULL,
  `booking_number` varchar(100) NOT NULL,
  `service_type` enum('equipment_rental','junk_removal') NOT NULL,
  `status` enum('pending','scheduled','out_for_delivery','delivered','in_use','awaiting_pickup','completed','cancelled','relocated','swapped') NOT NULL DEFAULT 'pending',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `delivery_location` varchar(255) NOT NULL,
  `pickup_location` varchar(255) DEFAULT NULL,
  `delivery_instructions` text,
  `pickup_instructions` text,
  `live_load_requested` tinyint(1) DEFAULT '0',
  `is_urgent` tinyint(1) DEFAULT '0',
  `equipment_details` json DEFAULT NULL,
  `junk_details` json DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `driver_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('Dumpster','Temporary Toilet','Storage Container','Handwash Station') NOT NULL,
  `size_capacity` varchar(100) DEFAULT NULL,
  `description` text,
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int NOT NULL,
  `quote_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','partially_paid','cancelled') NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `quote_id`, `user_id`, `invoice_number`, `amount`, `status`, `due_date`, `payment_method`, `transaction_id`, `created_at`, `updated_at`) VALUES
(3, 25, 29, 'INV-6DBF538F', 50.00, 'paid', '2025-07-17', NULL, NULL, '2025-07-10 03:33:26', '2025-07-10 18:41:12');

-- --------------------------------------------------------

--
-- Table structure for table `junk_removal_details`
--

CREATE TABLE `junk_removal_details` (
  `id` int NOT NULL,
  `quote_id` int NOT NULL,
  `junk_items_json` json DEFAULT NULL,
  `recommended_dumpster_size` varchar(50) DEFAULT NULL,
  `additional_comment` text,
  `media_urls_json` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `junk_removal_media`
--

CREATE TABLE `junk_removal_media` (
  `id` int NOT NULL,
  `junk_removal_detail_id` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` enum('new_quote','quote_accepted','quote_rejected','payment_due','payment_received','booking_status_update','system_message') NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `link`, `is_read`, `created_at`) VALUES
(4, 1, 'quote_accepted', 'Quote #Q25 has been accepted by User ID: 29. Invoice #INV3 created.', 'quotes?quote_id=25', 1, '2025-07-10 03:33:26'),
(5, 29, 'payment_received', 'The status of your invoice #INV-6DBF538F has been updated to: Paid', 'invoices?invoice_id=3', 1, '2025-07-10 18:41:12'),
(6, 29, 'new_quote', 'Your quote #26 for Equipment Rental is ready! Quoted price: $20.00.', 'quotes?quote_id=26', 1, '2025-07-10 18:56:43');

-- --------------------------------------------------------

--
-- Table structure for table `quotes`
--

CREATE TABLE `quotes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `service_type` enum('equipment_rental','junk_removal') NOT NULL,
  `status` enum('pending','quoted','accepted','rejected','converted_to_booking') NOT NULL DEFAULT 'pending',
  `customer_type` enum('Residential','Commercial') DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `delivery_time` varchar(100) DEFAULT NULL,
  `removal_date` date DEFAULT NULL,
  `removal_time` varchar(100) DEFAULT NULL,
  `live_load_needed` tinyint(1) DEFAULT '0',
  `is_urgent` tinyint(1) DEFAULT '0',
  `driver_instructions` text,
  `quoted_price` decimal(10,2) DEFAULT NULL,
  `quote_details` json DEFAULT NULL,
  `admin_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quotes`
--

INSERT INTO `quotes` (`id`, `user_id`, `service_type`, `status`, `customer_type`, `location`, `delivery_date`, `delivery_time`, `removal_date`, `removal_time`, `live_load_needed`, `is_urgent`, `driver_instructions`, `quoted_price`, `quote_details`, `admin_notes`, `created_at`, `updated_at`) VALUES
(10, 1, 'equipment_rental', 'pending', NULL, 'Test City', NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, '{\"test_data\": \"This is a test.\"}', NULL, '2025-07-09 23:25:16', '2025-07-09 23:25:16'),
(24, 29, 'equipment_rental', 'pending', 'Residential', 'Dallas, Texas (7110)', '2023-10-11', 'Morning', NULL, NULL, 0, 0, '', NULL, '{\"name\": \"Jon Vital\", \"type\": \"equipmentRental\", \"email\": \"jjony@test.com\", \"location\": \"Dallas, Texas (7110)\", \"is_urgent\": false, \"phoneNumber\": \"121321312\", \"customer_type\": \"Residential\", \"delivery_date\": \"2023-10-11\", \"delivery_time\": \"Morning\", \"specific_needs\": \"14 days rental\", \"equipment_types\": [\"20-yard dumpster\"], \"customer_message\": \"Thank you for your order, Jon Vital! We have noted down your details. You will receive your personalized quote within a maximum of 1 hour as we search for the best price in your area. Your account has been created, and you will be able to view your quote in your account dashboard as soon as it\'s ready. If you have any further questions or need assistance, feel free to ask.\", \"live_load_needed\": false, \"driver_instructions\": \"\"}', NULL, '2025-07-10 00:51:45', '2025-07-10 00:51:45'),
(25, 29, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas', '2023-10-04', 'Morning', NULL, NULL, 0, 0, '', 50.00, '{\"name\": \"Jon Vital\", \"type\": \"equipmentRental\", \"email\": \"jjony@test.com\", \"location\": \"Dallas, Texas\", \"is_urgent\": false, \"phoneNumber\": \"121321312\", \"customer_type\": \"Residential\", \"delivery_date\": \"2023-10-04\", \"delivery_time\": \"Morning\", \"specific_needs\": \"14 days rental\", \"equipment_types\": [\"15-yard dumpster\"], \"customer_message\": \"Thank you for your order, Jon! We have noted down your details. You will receive your personalized quote within a maximum of 1 hour as we search for the best price in your area. Your account has been created, and you will be able to view your quote in your account dashboard as soon as it\'s ready. If you have any further questions or need assistance, feel free to ask.\", \"live_load_needed\": false, \"driver_instructions\": \"\"}', 'this include 2 week service', '2025-07-10 01:32:36', '2025-07-10 03:33:26'),
(26, 29, 'equipment_rental', 'quoted', 'Residential', 'Dallas, Texas', '2023-10-04', 'Morning', NULL, NULL, 0, 0, 'Place it on the footpath.', 20.00, '{\"name\": \"Jon Vital\", \"type\": \"equipmentRental\", \"email\": \"jjony@test.com\", \"location\": \"Dallas, Texas\", \"is_urgent\": false, \"phoneNumber\": \"121321312\", \"customer_type\": \"Residential\", \"delivery_date\": \"2023-10-04\", \"delivery_time\": \"Morning\", \"specific_needs\": \"14 days rental\", \"equipment_types\": [\"10-yard dumpster\"], \"customer_message\": \"Thank you for your order, Jon! We have noted down your details. You will receive your personalized quote within a maximum of 1 hour as we search for the best price in your area. Your account has been created, and you will be able to view your quote in your account dashboard as soon as it\'s ready. If you have any further questions or need assistance, feel free to ask.\", \"live_load_needed\": false, \"driver_instructions\": \"Place it on the footpath.\"}', 'this includes weekly servicing', '2025-07-10 18:55:49', '2025-07-10 18:56:43');

-- --------------------------------------------------------

--
-- Table structure for table `quote_equipment_details`
--

CREATE TABLE `quote_equipment_details` (
  `id` int NOT NULL,
  `quote_id` int NOT NULL,
  `equipment_id` int DEFAULT NULL,
  `equipment_name` varchar(255) DEFAULT NULL,
  `quantity` int NOT NULL,
  `duration_days` int DEFAULT NULL,
  `specific_needs` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quote_equipment_details`
--

INSERT INTO `quote_equipment_details` (`id`, `quote_id`, `equipment_id`, `equipment_name`, `quantity`, `duration_days`, `specific_needs`) VALUES
(23, 24, NULL, '20-yard dumpster', 1, NULL, '14 days rental'),
(24, 25, NULL, '15-yard dumpster', 1, NULL, '14 days rental'),
(25, 26, NULL, '10-yard dumpster', 1, NULL, '14 days rental');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` tinyint(1) NOT NULL COMMENT 'Rating from 1 to 5',
  `review_text` text,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Admin can approve reviews before they are public',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'admin_email', 'webdesigner.xpt@gmail.com', 'Email address for admin notifications', '2025-07-09 14:11:43'),
(2, 'company_name', 'CAT Dump', 'Name of the company', '2025-07-09 21:34:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `role` enum('customer','admin','vendor') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone_number`, `password`, `address`, `city`, `state`, `zip_code`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'User', 'admin@example.com', '1234567890', '$2y$10$YourHashedPasswordHere.SomeSaltAndHashString', NULL, NULL, NULL, NULL, 'admin', '2025-07-09 15:24:46', '2025-07-09 15:24:46'),
(2, 'Manies new', 'check', 'jadic213343@hotmail.com', '1213213132', '$2y$10$lbdQqYPt3kcfeNgHAy43xuCTYRp6L76O1k2dqw8KL9HfUCQVnt.oe', '1111 Marcus Avenue, New Hyde Park, NY, USA', 'New York', 'new jersey', '75000', 'customer', '2025-07-09 16:57:25', '2025-07-09 17:34:13'),
(3, 'demo', 'testing', 'jadic213@hotmail.com', '1213213132', '$2y$10$cz5hfTmrqlpMwQzKd.XZDeuMOdsWfXw29mq0TdVpyP2xJ9TE7sf5S', NULL, NULL, NULL, NULL, 'customer', '2025-07-09 21:05:03', '2025-07-09 21:05:03'),
(4, 'Admin', 'User', 'admin@admin.com', '123-456-7890', '$2y$10$bbS/xE9All5u9Ze1xqOAIOKIdnb5i494bTelbhjZYsqm.Dojdk9Bu', '123 Admin St', 'Adminville', 'CA', '90210', 'admin', '2025-07-09 21:16:00', '2025-07-09 21:16:00'),
(29, 'Jon', 'Vital', 'jjony@test.com', '121321312', '$2y$10$fc4oW9nVn5qOaHI/7oMG5eRT7XVFES9OLKoMAaF.i3CY02XoAVraq', 'Dallas, Texas', 'Dallas', 'TX', '7110', 'customer', '2025-07-10 00:51:45', '2025-07-10 00:59:55');

-- --------------------------------------------------------

--
-- Table structure for table `user_payment_methods`
--

CREATE TABLE `user_payment_methods` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `braintree_payment_token` varchar(255) NOT NULL,
  `card_type` varchar(50) DEFAULT NULL,
  `last_four` varchar(4) DEFAULT NULL,
  `expiration_month` varchar(2) DEFAULT NULL,
  `expiration_year` varchar(4) DEFAULT NULL,
  `cardholder_name` varchar(255) DEFAULT NULL,
  `billing_address` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_payment_methods`
--

INSERT INTO `user_payment_methods` (`id`, `user_id`, `braintree_payment_token`, `card_type`, `last_four`, `expiration_month`, `expiration_year`, `cardholder_name`, `billing_address`, `is_default`, `created_at`, `updated_at`) VALUES
(3, 29, 'braintree_token_686feba095f3d4444', 'Discover', '4444', '06', '2030', 'testing one', '9330 Lyndon B Johnson Fwy', 1, '2025-07-10 16:34:40', '2025-07-10 18:15:41'),
(4, 29, 'braintree_token_686ff978ab3dc4444', 'Discover', '4444', '04', '2030', 'checking testing', '907 Nobel Street', 0, '2025-07-10 17:33:44', '2025-07-10 18:15:41');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_name` (`service_name`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_id` (`invoice_id`),
  ADD UNIQUE KEY `booking_number` (`booking_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_bookings_vendor_id` (`vendor_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD UNIQUE KEY `quote_id` (`quote_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `junk_removal_details`
--
ALTER TABLE `junk_removal_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quote_id` (`quote_id`);

--
-- Indexes for table `junk_removal_media`
--
ALTER TABLE `junk_removal_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `junk_removal_detail_id` (`junk_removal_detail_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `quotes`
--
ALTER TABLE `quotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_quotes_user_id` (`user_id`);

--
-- Indexes for table `quote_equipment_details`
--
ALTER TABLE `quote_equipment_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quote_id` (`quote_id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_booking_review` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_payment_methods`
--
ALTER TABLE `user_payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `junk_removal_details`
--
ALTER TABLE `junk_removal_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `junk_removal_media`
--
ALTER TABLE `junk_removal_media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `quotes`
--
ALTER TABLE `quotes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `quote_equipment_details`
--
ALTER TABLE `quote_equipment_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `user_payment_methods`
--
ALTER TABLE `user_payment_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_bookings_vendor_id` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`);

--
-- Constraints for table `junk_removal_details`
--
ALTER TABLE `junk_removal_details`
  ADD CONSTRAINT `junk_removal_details_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`);

--
-- Constraints for table `junk_removal_media`
--
ALTER TABLE `junk_removal_media`
  ADD CONSTRAINT `junk_removal_media_ibfk_1` FOREIGN KEY (`junk_removal_detail_id`) REFERENCES `junk_removal_details` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `quotes`
--
ALTER TABLE `quotes`
  ADD CONSTRAINT `fk_quotes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `quotes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `quote_equipment_details`
--
ALTER TABLE `quote_equipment_details`
  ADD CONSTRAINT `quote_equipment_details_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`),
  ADD CONSTRAINT `quote_equipment_details_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_payment_methods`
--
ALTER TABLE `user_payment_methods`
  ADD CONSTRAINT `user_payment_methods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
