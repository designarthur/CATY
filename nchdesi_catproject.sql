-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 10, 2025 at 09:13 PM
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
  `status` enum('pending','scheduled','assigned','pickedup','out_for_delivery','delivered','in_use','awaiting_pickup','completed','cancelled','relocated','swapped') NOT NULL DEFAULT 'pending',
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `invoice_id`, `user_id`, `vendor_id`, `booking_number`, `service_type`, `status`, `start_date`, `end_date`, `delivery_location`, `pickup_location`, `delivery_instructions`, `pickup_instructions`, `live_load_requested`, `is_urgent`, `equipment_details`, `junk_details`, `total_price`, `driver_id`, `created_at`, `updated_at`, `pickup_date`, `pickup_time`) VALUES
(1, 4, 29, NULL, 'BK-000004', 'equipment_rental', 'delivered', '2023-10-04', '2023-10-11', 'Dallas, Texas', NULL, 'Place it on the footpath.', NULL, 0, 0, '[{\"quantity\": 1, \"equipment_name\": \"10-yard dumpster\", \"specific_needs\": \"14 days rental\"}]', NULL, 20.00, NULL, '2025-07-10 19:39:45', '2025-07-10 20:03:25', NULL, NULL),
(2, 5, 29, NULL, 'BK-000005', 'equipment_rental', 'delivered', '2023-10-24', '2023-10-31', 'Dallas, Texas 7110', NULL, NULL, NULL, 0, 0, '[{\"quantity\": null, \"equipment_name\": null, \"specific_needs\": null}]', NULL, 250.00, NULL, '2025-07-10 20:45:59', '2025-07-10 20:47:23', NULL, NULL),
(3, 6, 29, NULL, 'BK-000006', 'equipment_rental', 'delivered', '2023-10-04', '2023-10-11', 'Dallas, Texas 7110', NULL, NULL, NULL, 0, 0, '[{\"quantity\": null, \"equipment_name\": null, \"specific_needs\": null}]', NULL, 50.00, NULL, '2025-07-10 22:57:55', '2025-07-10 22:59:33', NULL, NULL),
(4, 8, 29, NULL, 'BK-000008', 'equipment_rental', 'assigned', '2023-10-05', '2023-11-03', 'Dallas, Texas 7110', NULL, NULL, NULL, 0, 0, '[{\"quantity\": 2, \"equipment_name\": \"15-yard dumpster\", \"specific_needs\": null}]', NULL, 100.00, NULL, '2025-07-10 23:38:46', '2025-07-11 01:37:06', NULL, NULL),
(5, 10, 29, NULL, 'BK-000010', 'equipment_rental', 'delivered', '2023-10-04', '2023-10-18', 'Dallas, Texas 7110', NULL, NULL, NULL, 0, 0, '[{\"quantity\": 2, \"equipment_name\": \"15-yard dumpster\", \"specific_needs\": null}]', NULL, 20.00, NULL, '2025-07-11 00:55:04', '2025-07-11 00:55:58', NULL, NULL),
(6, 11, 29, NULL, 'BK-000011', 'equipment_rental', 'delivered', '2023-10-14', '2023-11-07', 'Dallas, Texas, 7110', NULL, NULL, NULL, 0, 0, '[{\"quantity\": 2, \"equipment_name\": \"15-yard dumpster\", \"specific_needs\": null}]', NULL, 100.00, NULL, '2025-07-11 01:35:14', '2025-07-11 02:10:32', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_charges`
--

CREATE TABLE `booking_charges` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `charge_type` enum('tonnage_overage','rental_extension','damage_fee','other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `created_by_admin_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `booking_charges`
--

INSERT INTO `booking_charges` (`id`, `booking_id`, `invoice_id`, `charge_type`, `amount`, `description`, `created_by_admin_id`, `created_at`) VALUES
(1, 4, 9, 'rental_extension', 150.00, 'Rental extension of 15 days.', 4, '2025-07-11 00:24:05'),
(2, 6, 12, 'rental_extension', 100.00, 'Rental extension of 5 days.', 4, '2025-07-11 01:39:29'),
(3, 6, 13, 'rental_extension', 100.00, 'Rental extension of 5 days approved by admin.', 4, '2025-07-11 01:58:13');

-- --------------------------------------------------------

--
-- Table structure for table `booking_extension_requests`
--

CREATE TABLE `booking_extension_requests` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `user_id` int NOT NULL,
  `requested_days` int NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `invoice_id` int DEFAULT NULL,
  `admin_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `booking_extension_requests`
--

INSERT INTO `booking_extension_requests` (`id`, `booking_id`, `user_id`, `requested_days`, `status`, `invoice_id`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 6, 29, 5, 'approved', 13, NULL, '2025-07-11 01:38:39', '2025-07-11 01:58:13');

-- --------------------------------------------------------

--
-- Table structure for table `booking_status_history`
--

CREATE TABLE `booking_status_history` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_status_history`
--

INSERT INTO `booking_status_history` (`id`, `booking_id`, `status`, `status_time`, `notes`) VALUES
(1, 2, 'assigned', '2025-07-10 20:46:33', 'Status updated to Assigned by admin.'),
(2, 2, 'out_for_delivery', '2025-07-10 20:47:03', 'Status updated to Out For Delivery by admin.'),
(3, 2, 'delivered', '2025-07-10 20:47:23', 'Status updated to Delivered by admin.'),
(4, 3, 'assigned', '2025-07-10 22:58:45', 'Status updated to Assigned by admin.'),
(5, 3, 'out_for_delivery', '2025-07-10 22:59:18', 'Status updated to Out For Delivery by admin.'),
(6, 3, 'delivered', '2025-07-10 22:59:33', 'Status updated to Delivered by admin.'),
(7, 4, 'assigned', '2025-07-11 00:09:16', 'Status updated to Assigned by admin.'),
(8, 4, 'out_for_delivery', '2025-07-11 00:09:36', 'Status updated to Out For Delivery by admin.'),
(9, 4, 'delivered', '2025-07-11 00:09:49', 'Status updated to Delivered by admin.'),
(10, 5, 'assigned', '2025-07-11 00:55:37', 'Status updated to Assigned by admin.'),
(11, 5, 'out_for_delivery', '2025-07-11 00:55:47', 'Status updated to Out For Delivery by admin.'),
(12, 5, 'delivered', '2025-07-11 00:55:58', 'Status updated to Delivered by admin.'),
(13, 4, 'assigned', '2025-07-11 01:36:22', 'Status updated to Assigned by admin.'),
(14, 4, 'out_for_delivery', '2025-07-11 01:36:28', 'Status updated to Out For Delivery by admin.'),
(15, 4, 'delivered', '2025-07-11 01:36:33', 'Status updated to Delivered by admin.'),
(16, 4, 'assigned', '2025-07-11 01:37:06', 'Status updated to Assigned by admin.'),
(17, 6, 'assigned', '2025-07-11 01:37:54', 'Status updated to Assigned by admin.'),
(18, 6, 'out_for_delivery', '2025-07-11 01:38:06', 'Status updated to Out For Delivery by admin.'),
(19, 6, 'delivered', '2025-07-11 01:38:19', 'Status updated to Delivered by admin.');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int NOT NULL,
  `conversation_id` int NOT NULL,
  `role` enum('user','assistant','system','tool') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `conversation_id`, `role`, `content`, `created_at`) VALUES
(1, 1, 'user', '[{\"type\":\"text\",\"text\":\"i need 15 yard dumpster, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com\"}]', '2025-07-10 20:23:51'),
(2, 1, 'assistant', '[{\"type\":\"text\",\"text\":\"Hi Jon! Thanks for reaching out to CAT Dump for your dumpster rental needs. I have a few more details to confirm to create your service quote.\\n\\n1. **Full Name**: I have your first name, Jon. Can you please provide your last name?\\n2. **Email**: I see your email is jjony@test.com. Is that correct?\\n3. **Phone Number**: Could you please provide your phone number for contact purposes?\\n4. **Service Details**: Just to confirm, you would like a 15-yard dumpster for 14 days, starting tomorrow, with delivery in the morning to Dallas, Texas. It\'s not a live load and not urgent. Is that all correct?\\n\\nOnce I have this information, I can summarize your request and send you a quote shortly!\"}]', '2025-07-10 20:23:51'),
(3, 1, 'user', '[{\"type\":\"text\",\"text\":\"yes i confirm\"}]', '2025-07-10 20:24:17'),
(4, 1, 'assistant', '[{\"type\":\"text\",\"text\":\"Great! Just to recap your request:\\n\\n- **Full Name**: Jon Vital\\n- **Email**: jjony@test.com\\n- **Phone Number**: 121321312\\n- **Service**: 15-yard dumpster rental\\n- **Rental Duration**: 14 days\\n- **Start Date**: Tomorrow, with morning delivery\\n- **Location**: Dallas, Texas\\n- **Special Notes**: Not a live load, not urgent\\n\\nI will prepare your quote and send it to you within the hour. Thank you for choosing CAT Dump!\\n\\n\"}]', '2025-07-10 20:24:17'),
(5, 2, 'user', '[{\"type\":\"text\",\"text\":\"i need 12 yard dumpster, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com\"}]', '2025-07-10 20:27:30'),
(6, 2, 'assistant', '[{\"type\":\"text\",\"text\":\"Great! I can help you with that. Just to confirm the details for your dumpster rental:\\n\\n1. **Full Name**: Jon Vital\\n2. **Email**: jjony@test.com\\n3. **Phone Number**: 121321312\\n4. **Service**: 12-yard dumpster rental\\n5. **Duration**: 14 days\\n6. **Delivery Date**: Tomorrow morning\\n7. **Location**: Dallas, Texas, 7110\\n8. **Load Type**: Not a live load\\n9. **Urgency**: Not urgent\\n\\nIs there anything else you would like to add or modify?\"}]', '2025-07-10 20:27:30'),
(7, 2, 'user', '[{\"type\":\"text\",\"text\":\"no that is all please confirm\"}]', '2025-07-10 20:27:53'),
(8, 2, 'assistant', '[{\"type\":\"text\",\"text\":\"Thank you for confirming! Here\\u2019s a summary of your request:\\n\\n- **Full Name**: Jon Vital\\n- **Email**: jjony@test.com\\n- **Phone Number**: 121321312\\n- **Service**: 12-yard dumpster rental\\n- **Duration**: 14 days\\n- **Delivery Date**: Tomorrow morning\\n- **Location**: Dallas, Texas, 7110\\n- **Load Type**: Not a live load\\n- **Urgency**: Not urgent\\n\\nWe will send you a quote within the hour. If you have any other questions or need further assistance, feel free to ask!\\n\\n\"}]', '2025-07-10 20:27:53'),
(9, 3, 'user', '[{\"type\":\"text\",\"text\":\"i need 15 yard dumpster, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com\"}]', '2025-07-10 20:28:58'),
(10, 3, 'assistant', '[{\"type\":\"text\",\"text\":\"Great! Thank you for providing the details, Jon. Just to confirm, I\\u2019ll summarize what I have:\\n\\n- **Service**: 15 yard dumpster rental\\n- **Duration**: 14 days\\n- **Delivery Date**: Tomorrow morning\\n- **Load Type**: Not a live load\\n- **Location**: Dallas, Texas 7110\\n- **Contact Name**: Jon Vital\\n- **Email**: jjony@test.com\\n- **Phone Number**: 121321312\\n\\nBefore I finalize it, could you please confirm your phone number? It seems a bit short. It usually includes an area code as well.\"}]', '2025-07-10 20:28:58'),
(11, 3, 'user', '[{\"type\":\"text\",\"text\":\"1212313132\"}]', '2025-07-10 20:29:17'),
(12, 3, 'assistant', '[{\"type\":\"text\",\"text\":\"Thank you for confirming your phone number! Here\\u2019s the final summary of your request:\\n\\n- **Service**: 15 yard dumpster rental\\n- **Duration**: 14 days\\n- **Delivery Date**: Tomorrow morning\\n- **Load Type**: Not a live load\\n- **Location**: Dallas, Texas 7110\\n- **Contact Name**: Jon Vital\\n- **Email**: jjony@test.com\\n- **Phone Number**: 1212313132\\n\\nI will send you a quote within the hour. If you have any other questions or need further assistance, feel free to ask!\\n\\nThank you for choosing CAT Dump!\\n\\n\"}]', '2025-07-10 20:29:17'),
(13, 4, 'user', 'i need 15 yard dumpster, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-10 20:35:19'),
(14, 4, 'assistant', 'I have gathered most of the information needed to create your service quote. Here’s what I have so far:\n\n- Name: Jon Vital\n- Email: jjony@test.com\n- Phone: 121321312\n- Location: Dallas, Texas 7110\n- Service Date: Tomorrow\n- Service Type: Equipment Rental (15-yard dumpster)\n- Service Duration: 14 days\n- Delivery: Morning\n- Live Load: No\n- Urgent: No\n\nI just need to confirm the service time. Can you please provide me with a specific time for the morning delivery?', '2025-07-10 20:35:19'),
(15, 4, 'user', 'yes', '2025-07-10 20:35:54'),
(16, 4, 'assistant', 'Could you please specify the exact time you would like the dumpster delivered in the morning?', '2025-07-10 20:35:54'),
(17, 4, 'user', 'i need 15 yard dumpster, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-10 20:39:25'),
(18, 4, 'assistant', 'Thank you! Your quote request (#Q30) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-10 20:39:25'),
(19, 5, 'user', 'i need 15 yard dumpster, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-10 22:52:28'),
(20, 5, 'assistant', 'Thank you! Your quote request (#Q31) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-10 22:52:28'),
(21, 6, 'user', 'i need 15 yard dumpster, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-10 23:08:06'),
(22, 6, 'assistant', 'I have gathered most of the necessary information, but I still need a few more details to create the service quote.\n\n1. Your phone number.\n2. Do you have a specific time in the morning for the delivery?\n3. Are there any specific needs or comments regarding the equipment rental?\n\nOnce I have this information, I can proceed with the quote request.', '2025-07-10 23:08:06'),
(23, 6, 'user', '05544888554 morning 10am no specific just drop it on footpath', '2025-07-10 23:09:04'),
(24, 6, 'assistant', 'Thank you! Your quote request (#Q32) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-10 23:09:04'),
(25, 7, 'user', 'i need 15 yard dumpster, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-10 23:11:55'),
(26, 7, 'assistant', 'Could you please confirm if you are a Residential or Commercial customer?', '2025-07-10 23:11:55'),
(27, 7, 'user', 'residential', '2025-07-10 23:12:27'),
(28, 7, 'assistant', 'Thank you for the information! Here’s a quick summary of what I have so far for your quote request:\n\n- **Name:** Jon Vital\n- **Email:** jjony@test.com\n- **Phone:** 121321312\n- **Location:** Dallas, Texas 7110\n- **Service Date:** Tomorrow\n- **Customer Type:** Residential\n- **Equipment Details:** 15-yard dumpster\n- **Quantity:** 1\n- **Duration:** 14 days\n- **Service Time:** Morning\n- **Is Urgent:** No\n- **Live Load Needed:** No\n\nPlease confirm that all this information is correct, and let me know if there are any specific needs you would like to add!', '2025-07-10 23:12:27'),
(29, 7, 'user', 'yes i confirm', '2025-07-10 23:12:46'),
(30, 7, 'assistant', 'Thank you! Your quote request (#Q33) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-10 23:12:46'),
(31, 8, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster , for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-10 23:16:42'),
(32, 8, 'assistant', 'I\'ll start by gathering your customer information and preferences.\n\nFirst off, please confirm if you are a Residential or Commercial customer.', '2025-07-10 23:16:42'),
(33, 8, 'user', 'i am residential', '2025-07-10 23:17:01'),
(34, 8, 'assistant', 'Great! Now, let me confirm your details:\n\n- **Name:** Jon Vital\n- **Email:** jjony@test.com\n- **Phone:** 121321312\n- **Location:** Dallas, Texas 7110\n- **Service Date:** Tomorrow\n\nHere are the service details:\n- **Customer Type:** Residential\n- **Equipment Needed:** \n  - 2 units of 15-yard dumpster\n  - 1 unit of 20-yard dumpster\n- **Rental Duration:** 14 days\n- **Service Time:** Morning\n- **Live Load Needed:** No\n- **Is Urgent:** No\n\nPlease confirm if all the information is correct or let me know if there are any changes.', '2025-07-10 23:17:01'),
(35, 8, 'user', 'yes confirm', '2025-07-10 23:17:09'),
(36, 8, 'assistant', 'Thank you! Your quote request (#Q34) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-10 23:17:09'),
(37, 9, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-10 23:28:59'),
(38, 9, 'assistant', 'To create your rental quote, I need to confirm a few details:\n\n1. What is your customer type: Residential or Commercial?\n2. Could you please provide your phone number? \n\nOnce I have this information, I can proceed with your request.', '2025-07-10 23:28:59'),
(39, 9, 'user', 'residential 12213132133', '2025-07-10 23:29:28'),
(40, 9, 'assistant', 'Thank you for the details! Here’s a summary of what I have for your quote:\n\n- **Customer Type:** Residential\n- **Customer Name:** Jon Vital\n- **Customer Email:** jjony@test.com\n- **Customer Phone:** 12213132133\n- **Location:** Dallas, Texas 7110\n- **Service Date:** Tomorrow\n- **Service Time:** Morning\n- **Rental Duration:** 14 days\n- **Equipment Details:**\n  - 2 units of 15-yard dumpster\n  - 1 unit of 20-yard dumpster\n  - 2 units of temporary toilets\n- **Is Urgent:** No\n- **Live Load Needed:** No\n\nPlease confirm if everything is correct, and I’ll submit the quote request.', '2025-07-10 23:29:28'),
(41, 9, 'user', 'yes confirm', '2025-07-10 23:29:40'),
(42, 9, 'assistant', 'Thank you! Your quote request (#Q35) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-10 23:29:40'),
(43, 10, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 00:53:10'),
(44, 10, 'assistant', 'To create a service quote, I need to first confirm the customer type. Is this request for Residential or Commercial use?', '2025-07-11 00:53:10'),
(45, 10, 'user', 'residential', '2025-07-11 00:53:25'),
(46, 10, 'assistant', 'Thank you for the information! Here’s a summary of what I have so far:\n\n- **Customer Type:** Residential\n- **Customer Name:** Jon Vital\n- **Customer Email:** jjony@test.com\n- **Customer Phone:** 121321312\n- **Location:** Dallas, Texas 7110\n- **Service Date:** Tomorrow\n- **Service Time:** Morning\n- **Is Urgent:** No\n- **Live Load Needed:** No\n- **Equipment Details:**\n  - 2 units of 15-yard dumpster for 14 days\n  - 1 unit of 20-yard dumpster for 14 days\n  - 2 temporary toilets for 14 days\n\nPlease confirm if this information is correct or if there are any additional details you\'d like to add!', '2025-07-11 00:53:25'),
(47, 10, 'user', 'yes i confrim', '2025-07-11 00:53:38'),
(48, 10, 'assistant', 'Thank you! Your quote request (#Q36) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 00:53:38'),
(49, 11, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 01:33:00'),
(50, 11, 'assistant', 'Thank you for the information! Could you please confirm if you are a residential or commercial customer? \n\nAdditionally, could you provide your phone number?', '2025-07-11 01:33:00'),
(51, 11, 'user', 'residential 132131231', '2025-07-11 01:33:17'),
(52, 11, 'assistant', 'Great! Here\'s a summary of the details I have so far:\n\n- **Customer Type:** Residential\n- **Customer Name:** Jon Vital\n- **Customer Email:** jjony@test.com\n- **Customer Phone:** 132131231\n- **Location:** Dallas, Texas, 7110\n- **Service Date:** Tomorrow\n- **Service Time:** Morning\n- **Urgent:** No\n- **Live Load Needed:** No\n- **Equipment Details:**\n  - 2 x 15-yard dumpsters\n  - 1 x 20-yard dumpster\n  - 2 x Temporary toilets\n- **Duration:** 14 days\n\nPlease confirm if everything is correct, or let me know if there\'s anything else you would like to add!', '2025-07-11 01:33:17'),
(53, 11, 'user', 'yes confirm', '2025-07-11 01:33:29'),
(54, 11, 'assistant', 'Thank you! Your quote request (#Q37) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 01:33:29');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `initial_service_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `user_id`, `initial_service_type`, `created_at`) VALUES
(1, 29, 'create-booking', '2025-07-10 20:20:43'),
(2, 29, NULL, '2025-07-10 20:27:27'),
(3, 29, NULL, '2025-07-10 20:28:55'),
(4, 29, NULL, '2025-07-10 20:34:57'),
(5, 29, NULL, '2025-07-10 22:52:24'),
(6, 29, NULL, '2025-07-10 23:08:04'),
(7, 29, NULL, '2025-07-10 23:11:54'),
(8, 29, NULL, '2025-07-10 23:16:40'),
(9, 29, NULL, '2025-07-10 23:28:58'),
(10, 29, NULL, '2025-07-11 00:53:09'),
(11, 29, NULL, '2025-07-11 01:32:58');

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notes` text,
  `booking_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `quote_id`, `user_id`, `invoice_number`, `amount`, `status`, `due_date`, `payment_method`, `transaction_id`, `created_at`, `updated_at`, `notes`, `booking_id`) VALUES
(3, 25, 29, 'INV-6DBF538F', 50.00, 'paid', '2025-07-17', NULL, NULL, '2025-07-10 03:33:26', '2025-07-10 18:41:12', NULL, NULL),
(4, 26, 29, 'INV-4C4DDB30', 20.00, 'paid', '2025-07-17', NULL, NULL, '2025-07-10 19:36:23', '2025-07-10 19:39:45', NULL, NULL),
(5, 30, 29, 'INV-A9E1D693', 250.00, 'paid', '2025-07-17', NULL, NULL, '2025-07-10 20:45:39', '2025-07-10 20:45:59', NULL, NULL),
(6, 31, 29, 'INV-99A67877', 50.00, 'paid', '2025-07-17', NULL, NULL, '2025-07-10 22:57:01', '2025-07-10 22:57:55', NULL, NULL),
(7, NULL, 29, 'INV-SWA-9882c8', 20.00, 'pending', '2025-07-13', NULL, NULL, '2025-07-10 22:59:50', '2025-07-10 22:59:50', 'Invoice for Swap Request on Booking ID 3', 3),
(8, 35, 29, 'INV-4B55C049', 100.00, 'paid', '2025-07-17', NULL, NULL, '2025-07-10 23:34:46', '2025-07-10 23:38:46', NULL, NULL),
(9, NULL, 29, 'INV-EXT-0D9384', 150.00, 'pending', '2025-07-18', NULL, NULL, '2025-07-11 00:24:05', '2025-07-11 00:24:05', 'Rental extension of 15 days for Booking #BK-000008', 4),
(10, 36, 29, 'INV-204FA9CB', 20.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 00:54:56', '2025-07-11 00:55:04', NULL, NULL),
(11, 37, 29, 'INV-DE744ACF', 100.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 01:34:51', '2025-07-11 01:35:14', NULL, NULL),
(12, NULL, 29, 'INV-EXT-78E3DF', 100.00, 'pending', '2025-07-18', NULL, NULL, '2025-07-11 01:39:29', '2025-07-11 01:39:29', 'Rental extension of 5 days for Booking #BK-000011', 6),
(13, NULL, 29, 'INV-EXT-294B52', 100.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 01:58:13', '2025-07-11 02:10:32', 'Rental extension of 5 days for Booking #BK-000011', 6);

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
  `type` enum('new_quote','quote_accepted','quote_rejected','payment_due','payment_received','payment_failed','partial_payment','booking_status_update','booking_confirmed','booking_assigned_vendor','junk_removal_confirmed','relocation_request_confirmation','relocation_scheduled','relocation_completed','swap_request_confirmation','swap_scheduled','swap_completed','pickup_request_confirmation','pickup_completed','profile_update','password_change','new_payment_method','account_deletion_request','account_deletion_confirmation','discount_offer','new_feature','system_message','system_maintenance','admin_new_user','admin_new_vendor','admin_error') NOT NULL,
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
(6, 29, 'new_quote', 'Your quote #26 for Equipment Rental is ready! Quoted price: $20.00.', 'quotes?quote_id=26', 1, '2025-07-10 18:56:43'),
(7, 29, 'payment_due', 'Your quote #Q26 has been accepted! An invoice (INV-4) has been created for $20.00. Please view and pay.', 'invoices?invoice_id=4', 0, '2025-07-10 19:36:23'),
(8, 1, 'quote_accepted', 'Quote #Q26 has been accepted by User ID: 29. Invoice #INV4 created.', 'quotes?quote_id=26', 0, '2025-07-10 19:36:23'),
(9, 29, 'booking_confirmed', 'Your booking #BK-000004 has been confirmed!', 'https://catproject.prelaunchdesigns.com/customer/dashboard.php#bookings?booking_id=1', 0, '2025-07-10 19:39:45'),
(10, 29, 'booking_status_update', 'Your booking #BK-BK-000004 has been updated to: Assigned.', 'bookings?booking_id=1', 0, '2025-07-10 19:49:44'),
(11, 29, 'booking_status_update', 'Your booking #BK-BK-000004 has been updated to: Out For Delivery.', 'bookings?booking_id=1', 0, '2025-07-10 19:50:22'),
(12, 29, 'booking_status_update', 'Your booking #BK-BK-000004 has been updated to: Delivered.', 'bookings?booking_id=1', 0, '2025-07-10 19:53:37'),
(13, 29, 'booking_status_update', 'Your booking #BK-BK-000004 has been updated to: Completed.', 'bookings?booking_id=1', 0, '2025-07-10 19:54:06'),
(14, 29, 'booking_status_update', 'Your booking #BK-BK-000004 has been updated to: Assigned.', 'bookings?booking_id=1', 0, '2025-07-10 19:54:31'),
(15, 29, 'booking_status_update', 'Your booking #BK-BK-000004 has been updated to: Delivered.', 'bookings?booking_id=1', 0, '2025-07-10 20:03:25'),
(16, 29, 'new_quote', 'Your quote #24 is ready! The quoted price is $250.00.', 'quotes?quote_id=24', 0, '2025-07-10 20:25:39'),
(17, 29, 'new_quote', 'Your quote #30 is ready! The quoted price is $250.00.', 'quotes?quote_id=30', 0, '2025-07-10 20:45:04'),
(18, 29, 'payment_due', 'Your quote #Q30 has been accepted! An invoice (INV-5) has been created for $250.00. Please view and pay.', 'invoices?invoice_id=5', 0, '2025-07-10 20:45:39'),
(19, 1, 'quote_accepted', 'Quote #Q30 has been accepted by User ID: 29. Invoice #INV5 created.', 'quotes?quote_id=30', 0, '2025-07-10 20:45:39'),
(20, 29, 'booking_confirmed', 'Your booking #BK-000005 has been confirmed!', 'https://catproject.prelaunchdesigns.com/customer/dashboard.php#bookings?booking_id=2', 0, '2025-07-10 20:45:59'),
(21, 29, 'booking_status_update', 'Your booking #BK-BK-000005 has been updated to: Assigned.', 'bookings?booking_id=2', 0, '2025-07-10 20:46:33'),
(22, 29, 'booking_status_update', 'Your booking #BK-BK-000005 has been updated to: Out For Delivery.', 'bookings?booking_id=2', 0, '2025-07-10 20:47:03'),
(23, 29, 'booking_status_update', 'Your booking #BK-BK-000005 has been updated to: Delivered.', 'bookings?booking_id=2', 0, '2025-07-10 20:47:23'),
(24, 1, 'new_quote', 'Your quote #10 is ready! The quoted price is $50.00.', 'quotes?quote_id=10', 0, '2025-07-10 22:50:50'),
(25, 29, 'new_quote', 'Your quote #31 is ready! The quoted price is $50.00.', 'quotes?quote_id=31', 0, '2025-07-10 22:53:02'),
(26, 29, 'payment_due', 'Quote #Q31 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=6', 0, '2025-07-10 22:57:01'),
(27, 29, 'booking_confirmed', 'Your booking #BK-000006 has been confirmed!', 'https://catproject.prelaunchdesigns.com/customer/dashboard.php#bookings?booking_id=3', 0, '2025-07-10 22:57:55'),
(28, 29, 'booking_status_update', 'Your booking #BK-BK-000006 has been updated to: Assigned.', 'bookings?booking_id=3', 0, '2025-07-10 22:58:45'),
(29, 29, 'booking_status_update', 'Your booking #BK-BK-000006 has been updated to: Out For Delivery.', 'bookings?booking_id=3', 0, '2025-07-10 22:59:18'),
(30, 29, 'booking_status_update', 'Your booking #BK-BK-000006 has been updated to: Delivered.', 'bookings?booking_id=3', 0, '2025-07-10 22:59:33'),
(31, 29, 'payment_due', 'Your Swap request requires payment. Please pay invoice #INV-SWA-9882c8 to proceed.', 'invoices?invoice_id=7', 0, '2025-07-10 22:59:50'),
(32, 29, 'new_quote', 'Your quote #35 is ready! The quoted price is $100.00.', 'quotes?quote_id=35', 0, '2025-07-10 23:34:08'),
(33, 29, 'payment_due', 'Quote #Q35 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=8', 0, '2025-07-10 23:34:46'),
(34, 29, 'booking_confirmed', 'Your booking #BK-000008 has been confirmed!', 'https://catproject.prelaunchdesigns.com/customer/dashboard.php#bookings?booking_id=4', 0, '2025-07-10 23:38:46'),
(35, 29, 'booking_status_update', 'Your booking #BK-BK-000008 has been updated to: Assigned.', 'bookings?booking_id=4', 0, '2025-07-11 00:09:16'),
(36, 29, 'booking_status_update', 'Your booking #BK-BK-000008 has been updated to: Out For Delivery.', 'bookings?booking_id=4', 0, '2025-07-11 00:09:36'),
(37, 29, 'booking_status_update', 'Your booking #BK-BK-000008 has been updated to: Delivered.', 'bookings?booking_id=4', 0, '2025-07-11 00:09:49'),
(38, 29, 'payment_due', 'Your rental for Booking #BK-000008 has been extended by 15 days. A new invoice has been generated.', 'invoices?invoice_id=9', 1, '2025-07-11 00:24:05'),
(39, 29, 'new_quote', 'Your quote #36 is ready! The quoted price is $20.00.', 'quotes?quote_id=36', 0, '2025-07-11 00:54:42'),
(40, 29, 'payment_due', 'Quote #Q36 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=10', 0, '2025-07-11 00:54:56'),
(41, 29, 'booking_confirmed', 'Your booking #BK-000010 has been confirmed!', 'https://catproject.prelaunchdesigns.com/customer/dashboard.php#bookings?booking_id=5', 0, '2025-07-11 00:55:04'),
(42, 29, 'booking_status_update', 'Your booking #BK-BK-000010 has been updated to: Assigned.', 'bookings?booking_id=5', 0, '2025-07-11 00:55:37'),
(43, 29, 'booking_status_update', 'Your booking #BK-BK-000010 has been updated to: Out For Delivery.', 'bookings?booking_id=5', 0, '2025-07-11 00:55:47'),
(44, 29, 'booking_status_update', 'Your booking #BK-BK-000010 has been updated to: Delivered.', 'bookings?booking_id=5', 0, '2025-07-11 00:55:58'),
(45, 29, 'new_quote', 'Your quote #37 is ready! The quoted price is $100.00.', 'quotes?quote_id=37', 0, '2025-07-11 01:34:26'),
(46, 29, 'payment_due', 'Quote #Q37 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=11', 0, '2025-07-11 01:34:51'),
(47, 29, 'booking_confirmed', 'Your booking #BK-000011 has been confirmed!', 'https://catproject.prelaunchdesigns.com/customer/dashboard.php#bookings?booking_id=6', 0, '2025-07-11 01:35:14'),
(48, 29, 'booking_status_update', 'Your booking #BK-BK-000008 has been updated to: Assigned.', 'bookings?booking_id=4', 0, '2025-07-11 01:36:22'),
(49, 29, 'booking_status_update', 'Your booking #BK-BK-000008 has been updated to: Out For Delivery.', 'bookings?booking_id=4', 0, '2025-07-11 01:36:28'),
(50, 29, 'booking_status_update', 'Your booking #BK-BK-000008 has been updated to: Delivered.', 'bookings?booking_id=4', 0, '2025-07-11 01:36:33'),
(51, 29, 'booking_status_update', 'Your booking #BK-BK-000008 has been updated to: Assigned.', 'bookings?booking_id=4', 0, '2025-07-11 01:37:06'),
(52, 29, 'booking_status_update', 'Your booking #BK-BK-000011 has been updated to: Assigned.', 'bookings?booking_id=6', 0, '2025-07-11 01:37:54'),
(53, 29, 'booking_status_update', 'Your booking #BK-BK-000011 has been updated to: Out For Delivery.', 'bookings?booking_id=6', 0, '2025-07-11 01:38:06'),
(54, 29, 'booking_status_update', 'Your booking #BK-BK-000011 has been updated to: Delivered.', 'bookings?booking_id=6', 0, '2025-07-11 01:38:19'),
(55, 1, 'system_message', 'Customer has requested a 5-day extension for Booking ID #6. Please review and approve.', 'bookings?booking_id=6', 0, '2025-07-11 01:38:39'),
(56, 29, 'booking_status_update', 'Your request to extend Booking #6 by 5 days has been submitted for approval.', 'bookings?booking_id=6', 0, '2025-07-11 01:38:39'),
(57, 29, 'payment_due', 'Your rental for Booking #BK-000011 has been extended by 5 days. A new invoice has been generated.', 'invoices?invoice_id=12', 0, '2025-07-11 01:39:29'),
(58, 29, 'payment_due', 'Your rental extension request for Booking #BK-000011 has been approved! Please pay the new invoice to confirm.', 'bookings?booking_id=6', 0, '2025-07-11 01:58:13'),
(59, 29, 'payment_received', 'The status of your invoice #INV-EXT-294B52 has been updated to: Paid', 'invoices?invoice_id=13', 0, '2025-07-11 02:10:32');

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
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `swap_charge` decimal(10,2) DEFAULT '0.00',
  `relocation_charge` decimal(10,2) DEFAULT '0.00',
  `quote_details` json DEFAULT NULL,
  `admin_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_swap_included` tinyint(1) DEFAULT '0',
  `is_relocation_included` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quotes`
--

INSERT INTO `quotes` (`id`, `user_id`, `service_type`, `status`, `customer_type`, `location`, `delivery_date`, `delivery_time`, `removal_date`, `removal_time`, `live_load_needed`, `is_urgent`, `driver_instructions`, `quoted_price`, `daily_rate`, `swap_charge`, `relocation_charge`, `quote_details`, `admin_notes`, `created_at`, `updated_at`, `is_swap_included`, `is_relocation_included`) VALUES
(10, 1, 'equipment_rental', 'quoted', NULL, 'Test City', NULL, NULL, NULL, NULL, 0, 0, NULL, 50.00, NULL, 50.00, 0.00, '{\"test_data\": \"This is a test.\"}', '', '2025-07-09 23:25:16', '2025-07-10 22:50:50', 0, 0),
(24, 29, 'equipment_rental', 'quoted', 'Residential', 'Dallas, Texas (7110)', '2023-10-11', 'Morning', NULL, NULL, 0, 0, '', 250.00, NULL, 20.00, 36.00, '{\"name\": \"Jon Vital\", \"type\": \"equipmentRental\", \"email\": \"jjony@test.com\", \"location\": \"Dallas, Texas (7110)\", \"is_urgent\": false, \"phoneNumber\": \"121321312\", \"customer_type\": \"Residential\", \"delivery_date\": \"2023-10-11\", \"delivery_time\": \"Morning\", \"specific_needs\": \"14 days rental\", \"equipment_types\": [\"20-yard dumpster\"], \"customer_message\": \"Thank you for your order, Jon Vital! We have noted down your details. You will receive your personalized quote within a maximum of 1 hour as we search for the best price in your area. Your account has been created, and you will be able to view your quote in your account dashboard as soon as it\'s ready. If you have any further questions or need assistance, feel free to ask.\", \"live_load_needed\": false, \"driver_instructions\": \"\"}', 'this includes pickup as well', '2025-07-10 00:51:45', '2025-07-10 20:25:39', 0, 0),
(25, 29, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas', '2023-10-04', 'Morning', NULL, NULL, 0, 0, '', 50.00, NULL, 0.00, 0.00, '{\"name\": \"Jon Vital\", \"type\": \"equipmentRental\", \"email\": \"jjony@test.com\", \"location\": \"Dallas, Texas\", \"is_urgent\": false, \"phoneNumber\": \"121321312\", \"customer_type\": \"Residential\", \"delivery_date\": \"2023-10-04\", \"delivery_time\": \"Morning\", \"specific_needs\": \"14 days rental\", \"equipment_types\": [\"15-yard dumpster\"], \"customer_message\": \"Thank you for your order, Jon! We have noted down your details. You will receive your personalized quote within a maximum of 1 hour as we search for the best price in your area. Your account has been created, and you will be able to view your quote in your account dashboard as soon as it\'s ready. If you have any further questions or need assistance, feel free to ask.\", \"live_load_needed\": false, \"driver_instructions\": \"\"}', 'this include 2 week service', '2025-07-10 01:32:36', '2025-07-10 03:33:26', 0, 0),
(26, 29, 'equipment_rental', 'converted_to_booking', 'Residential', 'Dallas, Texas', '2023-10-04', 'Morning', NULL, NULL, 0, 0, 'Place it on the footpath.', 20.00, NULL, 0.00, 0.00, '{\"name\": \"Jon Vital\", \"type\": \"equipmentRental\", \"email\": \"jjony@test.com\", \"location\": \"Dallas, Texas\", \"is_urgent\": false, \"phoneNumber\": \"121321312\", \"customer_type\": \"Residential\", \"delivery_date\": \"2023-10-04\", \"delivery_time\": \"Morning\", \"specific_needs\": \"14 days rental\", \"equipment_types\": [\"10-yard dumpster\"], \"customer_message\": \"Thank you for your order, Jon! We have noted down your details. You will receive your personalized quote within a maximum of 1 hour as we search for the best price in your area. Your account has been created, and you will be able to view your quote in your account dashboard as soon as it\'s ready. If you have any further questions or need assistance, feel free to ask.\", \"live_load_needed\": false, \"driver_instructions\": \"Place it on the footpath.\"}', 'this includes weekly servicing', '2025-07-10 18:55:49', '2025-07-10 19:39:45', 0, 0),
(30, 29, 'equipment_rental', 'converted_to_booking', NULL, 'Dallas, Texas 7110', '2023-10-24', '09:00', NULL, NULL, 0, 0, NULL, 250.00, NULL, 10.00, 28.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-24\", \"service_time\": \"09:00\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false}', 'this includes weekly service', '2025-07-10 20:39:25', '2025-07-10 20:45:59', 0, 0),
(31, 29, 'equipment_rental', 'converted_to_booking', NULL, 'Dallas, Texas 7110', '2023-10-04', 'morning', NULL, NULL, 0, 0, NULL, 50.00, NULL, 20.00, 0.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-04\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false}', '', '2025-07-10 22:52:28', '2025-07-10 22:57:55', 0, 0),
(32, 29, 'equipment_rental', 'pending', NULL, 'dallas texas 7110', '2023-10-11', '10:00', NULL, NULL, 0, 0, 'just drop it on footpath', NULL, NULL, 0.00, 0.00, '{\"location\": \"dallas texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-11\", \"service_time\": \"10:00\", \"service_type\": \"equipment_rental\", \"customer_name\": \"jon vital\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"05544888554\", \"live_load_needed\": false, \"equipment_details\": {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, \"driver_instructions\": \"just drop it on footpath\"}', NULL, '2025-07-10 23:09:04', '2025-07-10 23:09:04', 0, 0),
(33, 29, 'equipment_rental', 'pending', 'Residential', 'Dallas, Texas 7110', '2023-10-04', 'Morning', NULL, NULL, 0, 0, NULL, NULL, NULL, 0.00, 0.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-04\", \"service_time\": \"Morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}}', NULL, '2025-07-10 23:12:46', '2025-07-10 23:12:46', 0, 0),
(34, 29, 'equipment_rental', 'pending', 'Residential', 'Dallas, Texas 7110', '2023-10-14', 'morning', NULL, NULL, 0, 0, NULL, NULL, NULL, 0.00, 0.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-14\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}}', NULL, '2025-07-10 23:17:09', '2025-07-10 23:17:09', 0, 0),
(35, 29, 'equipment_rental', 'converted_to_booking', 'Residential', 'Dallas, Texas 7110', '2023-10-05', 'morning', NULL, NULL, 0, 0, NULL, 100.00, NULL, 20.00, 35.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-05\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"12213132133\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"temporary toilet\"}]}', '', '2025-07-10 23:29:40', '2025-07-10 23:38:46', 0, 0),
(36, 29, 'equipment_rental', 'converted_to_booking', 'Residential', 'Dallas, Texas 7110', '2023-10-04', 'Morning', NULL, NULL, 0, 0, NULL, 20.00, NULL, 10.00, 10.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-04\", \"service_time\": \"Morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"temporary toilet\"}]}', '', '2025-07-11 00:53:38', '2025-07-11 00:55:04', 0, 0),
(37, 29, 'equipment_rental', 'converted_to_booking', 'Residential', 'Dallas, Texas, 7110', '2023-10-14', 'Morning', NULL, NULL, 0, 0, NULL, 100.00, 20.00, 30.00, 50.00, '{\"location\": \"Dallas, Texas, 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-14\", \"service_time\": \"Morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"132131231\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"Temporary toilet\"}]}', '', '2025-07-11 01:33:29', '2025-07-11 01:35:14', 0, 0);

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
(1, 35, NULL, '15-yard dumpster', 2, 14, NULL),
(2, 35, NULL, '20-yard dumpster', 1, 14, NULL),
(3, 35, NULL, 'temporary toilet', 2, 14, NULL),
(4, 36, NULL, '15-yard dumpster', 2, 14, NULL),
(5, 36, NULL, '20-yard dumpster', 1, 14, NULL),
(6, 36, NULL, 'temporary toilet', 2, 14, NULL),
(7, 37, NULL, '15-yard dumpster', 2, 14, NULL),
(8, 37, NULL, '20-yard dumpster', 1, 14, NULL),
(9, 37, NULL, 'Temporary toilet', 2, 14, NULL);

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

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `booking_id`, `user_id`, `rating`, `review_text`, `is_approved`, `created_at`) VALUES
(1, 1, 29, 5, '', 0, '2025-07-10 19:54:15');

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
(3, 29, 'braintree_token_686feba095f3d4444', 'Discover', '4444', '06', '2030', 'testing one', '9330 Lyndon B Johnson Fwy', 0, '2025-07-10 16:34:40', '2025-07-10 19:35:35'),
(4, 29, 'braintree_token_686ff978ab3dc4444', 'Discover', '4444', '04', '2030', 'checking testing', '907 Nobel Street', 1, '2025-07-10 17:33:44', '2025-07-10 19:35:35');

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
  ADD KEY `idx_bookings_user_id` (`user_id`),
  ADD KEY `idx_bookings_vendor_id` (`vendor_id`),
  ADD KEY `idx_bookings_status` (`status`);

--
-- Indexes for table `booking_charges`
--
ALTER TABLE `booking_charges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `booking_extension_requests`
--
ALTER TABLE `booking_extension_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `fk_extension_requests_user_id` (`user_id`),
  ADD KEY `idx_invoice_id` (`invoice_id`);

--
-- Indexes for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking_id` (`booking_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_chat_messages_conversation_id_idx` (`conversation_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_conversations_user_id_idx` (`user_id`);

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
  ADD KEY `idx_invoices_user_id` (`user_id`),
  ADD KEY `idx_invoices_quote_id` (`quote_id`),
  ADD KEY `idx_invoices_status` (`status`);

--
-- Indexes for table `junk_removal_details`
--
ALTER TABLE `junk_removal_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_junk_quote_id` (`quote_id`);

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
  ADD KEY `idx_notifications_user_id` (`user_id`),
  ADD KEY `idx_notifications_is_read` (`is_read`);

--
-- Indexes for table `quotes`
--
ALTER TABLE `quotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quotes_user_id` (`user_id`),
  ADD KEY `idx_quotes_status` (`status`);

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
  ADD KEY `idx_reviews_user_id` (`user_id`),
  ADD KEY `idx_reviews_booking_id` (`booking_id`);

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
  ADD KEY `idx_payment_methods_user_id` (`user_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `booking_charges`
--
ALTER TABLE `booking_charges`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `booking_extension_requests`
--
ALTER TABLE `booking_extension_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `quotes`
--
ALTER TABLE `quotes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `quote_equipment_details`
--
ALTER TABLE `quote_equipment_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Constraints for table `booking_charges`
--
ALTER TABLE `booking_charges`
  ADD CONSTRAINT `fk_booking_charges_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_charges_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_extension_requests`
--
ALTER TABLE `booking_extension_requests`
  ADD CONSTRAINT `fk_extension_requests_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_extension_requests_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  ADD CONSTRAINT `fk_booking_status_history_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_messages_conversation_id` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conversations_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `fk_quotes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quotes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `quote_equipment_details`
--
ALTER TABLE `quote_equipment_details`
  ADD CONSTRAINT `quote_equipment_details_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quote_equipment_details_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE SET NULL;

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
