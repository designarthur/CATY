-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 11, 2025 at 11:39 AM
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
  `status` enum('pending','scheduled','assigned','pickedup','out_for_delivery','delivered','in_use','awaiting_pickup','completed','cancelled','relocated','swapped','relocation_requested','swap_requested') NOT NULL DEFAULT 'pending',
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
(2, 5, 1, NULL, 'BOOK-C2FCFCAC', 'equipment_rental', 'delivered', '2025-07-11', NULL, 'Dallas, Texas', NULL, NULL, NULL, 0, 0, NULL, NULL, 100.00, NULL, '2025-07-11 14:00:48', '2025-07-11 14:07:49', NULL, NULL),
(3, 6, 1, NULL, 'BOOK-CF050AA2', 'equipment_rental', 'delivered', '2025-07-11', NULL, 'Dallas, Texas 7110', NULL, NULL, NULL, 0, 0, NULL, NULL, 497.00, NULL, '2025-07-11 14:10:49', '2025-07-11 14:11:10', NULL, NULL),
(4, 7, 1, NULL, 'BOOK-B4B6CC43', 'equipment_rental', 'delivered', '2025-07-11', NULL, 'Dallas, Texas 7110', NULL, NULL, NULL, 0, 0, NULL, NULL, 112.00, NULL, '2025-07-11 14:23:44', '2025-07-11 14:24:02', NULL, NULL),
(5, 8, 1, NULL, 'BOOK-334C2310', 'equipment_rental', 'delivered', '2025-07-11', NULL, 'Dallas, Texas 7110', NULL, NULL, NULL, 0, 0, NULL, NULL, 146.00, NULL, '2025-07-11 14:35:58', '2025-07-11 14:36:13', NULL, NULL),
(6, 11, 1, NULL, 'BOOK-7352060F', 'equipment_rental', 'relocated', '2025-07-11', NULL, 'Dallas, Texas', NULL, NULL, NULL, 0, 0, NULL, NULL, 1008.00, NULL, '2025-07-11 14:56:20', '2025-07-11 15:19:06', NULL, NULL),
(7, 14, 1, NULL, 'BOOK-3367E7', 'equipment_rental', 'delivered', '2025-07-11', NULL, 'Dallas, Texas 7110', NULL, NULL, NULL, 0, 0, NULL, NULL, 20.00, NULL, '2025-07-11 15:50:55', '2025-07-11 15:55:41', NULL, NULL),
(8, 15, 1, NULL, 'BOOK-41440E', 'equipment_rental', 'scheduled', '2025-07-11', NULL, 'Dallas, Texas 7110', NULL, NULL, NULL, 0, 0, NULL, NULL, 1501.00, NULL, '2025-07-11 16:27:53', '2025-07-11 16:27:53', NULL, NULL),
(9, 16, 1, NULL, 'BOOK-56F5A8', 'equipment_rental', 'scheduled', '2025-07-11', NULL, 'dallas texas 7110', NULL, NULL, NULL, 0, 0, NULL, NULL, 1797.00, NULL, '2025-07-11 16:33:59', '2025-07-11 16:33:59', NULL, NULL),
(10, 17, 1, NULL, 'BOOK-D762B7', 'equipment_rental', 'scheduled', '2025-07-11', NULL, 'Dallas, Texas 7110', NULL, NULL, NULL, 0, 0, NULL, NULL, 1001.00, NULL, '2025-07-11 16:39:02', '2025-07-11 16:39:02', NULL, NULL);

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
(1, 5, 1, 2, 'pending', NULL, NULL, '2025-07-11 14:36:36', '2025-07-11 14:36:36');

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
(4, 2, 'assigned', '2025-07-11 14:07:06', 'Status updated to Assigned by admin.'),
(5, 2, 'out_for_delivery', '2025-07-11 14:07:44', 'Status updated to Out For Delivery by admin.'),
(6, 2, 'delivered', '2025-07-11 14:07:49', 'Status updated to Delivered by admin.'),
(7, 3, 'delivered', '2025-07-11 14:11:10', 'Status updated to Delivered by admin.'),
(8, 4, 'delivered', '2025-07-11 14:24:02', 'Status updated to Delivered by admin.'),
(9, 5, 'delivered', '2025-07-11 14:36:13', 'Status updated to Delivered by admin.'),
(10, 6, 'out_for_delivery', '2025-07-11 14:57:08', 'Status updated to Out For Delivery by admin.'),
(11, 6, 'delivered', '2025-07-11 14:57:23', 'Status updated to Delivered by admin.'),
(12, 6, 'relocation_requested', '2025-07-11 15:14:53', 'Customer requested Relocation for Booking #BOOK-7352060F. Payment required. Status updated to relocation_requested.'),
(13, 6, 'assigned', '2025-07-11 15:16:57', 'Status updated to Assigned by admin.'),
(14, 6, 'relocated', '2025-07-11 15:19:06', 'Status updated to Relocated by admin.'),
(15, 7, 'delivered', '2025-07-11 15:55:41', 'Status updated to Delivered by admin.');

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
(1, 1, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 03:07:26'),
(2, 1, 'assistant', 'To create a service quote for your equipment rental, I\'ll need a few more details:\n\n1. **Customer Type**: Are you representing a Residential or Commercial customer?\n2. **Service Date**: You\'ve mentioned tomorrow; just to confirm, is that the 2023-10-02?\n3. **Customer Phone Number**: What is the best phone number to reach you?\n\nOnce I have these details, I\'ll be able to submit your quote request!', '2025-07-11 03:07:26'),
(3, 1, 'user', '123131313', '2025-07-11 03:07:47'),
(4, 1, 'assistant', 'Thank you for providing your phone number. \n\nJust to confirm:\n- **Customer Type**: Is it Residential or Commercial? \n- **Service Date**: Tomorrow (2023-10-02) has been confirmed.\n\nOnce I have the customer type, I\'ll gather all the information to submit your quote request.', '2025-07-11 03:07:47'),
(5, 1, 'user', 'it is residential', '2025-07-11 03:08:16'),
(6, 1, 'assistant', 'Thank you! Your quote request (#Q1) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 03:08:16'),
(7, 2, 'user', '[{\"type\":\"text\",\"text\":\"i need junk removal service\"}]', '2025-07-11 04:13:37'),
(8, 2, 'assistant', 'I\'ll need some more details to create a quote for your junk removal service. Could you please provide the following information?\n\n1. Your name\n2. Email address\n3. Phone number\n4. Location (or address)\n5. Service date\n6. If you have images of the items to be removed, please share them to help estimate quantities and dimensions. \n\nOnce I have all this information, I\'ll be able to assist you further!', '2025-07-11 04:13:37');
INSERT INTO `chat_messages` (`id`, `conversation_id`, `role`, `content`, `created_at`) VALUES
(9, 3, 'user', '[{\"type\":\"image_url\",\"image_url\":{\"url\":\"data:image\\/jpeg;base64,\\/9j\\/4AAQSkZJRgABAQAAAQABAAD\\/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7\\/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7\\/wgARCAFcAVwDASIAAhEBAxEB\\/8QAHAAAAQUBAQEAAAAAAAAAAAAABQECAwQGBwAI\\/8QAGgEAAwEBAQEAAAAAAAAAAAAAAAECAwQFBv\\/aAAwDAQACEAMQAAAB5V5jslErlZ5YnDsRJaStOemgiPUGMmUImyKCL5wIj1BiuQGqvmIsrEJ56MZHI9ETJPN+jk8CSRIjyogKxfA1FahjHeAY5WSlcr0onIjeggMjs5Z6Rd6iS0oVFteZAluII0stBjZvBC2woQLM5EHp2hAlrwVm2VCr62gUX3Gp0lvKA\\/1xiKUk8YQMsMCKCdgV2+tZoZelvoAlxW2uqiGXLQMptzArjMggkGip5gmA5MkBZoEGKqaukAWLR1QCsJ1xMgJ0BQ+sqFT1vw6Tbngopc8FNt5oUfX0YLluqId4g4Y1hRR5+7PaMxRfbEWuXBOhc50NiKMG06F1TSeceflANOT8A5CyAMUm4A7iioGNMNGHcTewSpVAFNKIAzxRAGMKtAahNAGNJoMahHwxniKAM9f8Iat9Rgs57R82ZDZ8o1GbKcY6XzTpOl9B570TOsuft3OzMRISkaEtNeAS0y0BXjCAFcWcmJaZaAV5VwB0MIMKhloCWGYwEsMRoEsLtGI8T8AjxJUCWlGAK8RQYzxDwcd3uB6rxoD4GZaqiDYfQOXxxKVrrOd2fqcjL2etTZl0rs9oUsNCP0qhXZaREC2GjrrK4cCTeCBlqII47TArUSkIQLgeiBVbYanTWVQqx2WhU9N4daC9GjMgtRheYMDBBRAeZtfRaLUYnWbc240WNO3uXzJKVzn9PhtK40SCYM9jyVIHJJBYmp1aZkwncdXuTcPntYjXQhIxyCiGE86PhW950YJ7617SofOQcKP8FfzvIhR7R83ogzuREMdHS8aio0i4pkrQ\\/wBaplHFAU5fTNHx3oZmemycC5tLYXkGPodq9yInPR1+PkixPRalCrvkYIcxYzsALB3hbibnla11nLc0lJEF6lPTPv5Lk29WhyAPhXGt0+S1Y2ehlGxF8nzAzrRkPFRJV0zqPqaWaEF4gCFu5uOgqORgS2a1kS91B3BF+J34OfTXW8ZDl07l+W09QwRps\\/RJt8GukdIM8Up3HffnTTZNxBbpgqzlbCSNDPXOJxi18+Z66FehPhhr1EdQT0iZeUZtEE5zlMuVBXWrzlNooYTlRgh1+m0qWGDTZ4u2BLTgTcaQAdoRgyezHAc9iNTMBejn7APwerRcp4iBq+t29phW9TCo1OabZWlPYhCjYshsRmsrJQDc96gjy9rW2Zjerqqmo5fuAyMlG5nYkKTG65Nuo0dqpWvMbar3xRwrSGy1Vugd1+M6NnY4xaHi5nVLZehtbdacMNX6GF1jm5cwOlWxe02vXw8gn6yJ0jm5u4S8n1ANPeA5fOaXot5d5isls17YR6MRcDpXKNrgpZyxSsTQlsgXbIrOINbZCm2KuOtgrnic1XjivBNSmGi25DOFPR4yWs5gP4erqosrRQ0lmr\\/ueNI3H7fl2isRSdXAlKQJl0acuGHRuVwu3g4Ozmo\\/txHg7PnrxsRvMcvpmNOzLJeH6QeOAhVLjxM1Gqi9ScaucvKSouayWGJoQqkk6N73Vc9OVBu6CovA6PVO7+POsLUEDq1v3o8SU4CHpcGb0Ia\\/w9OtbON289K065bN1ePJ49NqrMHx22ZjnOs8f0+KV2u3E8xoHYxKZvU1wB2nPoc4LCN16hU1I7kwqJiDfznmitOlKZSiOHTpLxwf53cLjBQ9GRBlm21QKLe6cJpc7oufcVSxJr0+CtU1VvSYmmn7cAGzLR35nUy+d5e4o\\/N0ajRZy7VVgWkCfnejm49L4BUPRReemR+h\\/nf6A1zoBtlzya59VK02qrrEKWzCdLw3EUBbpe0n7jSuef25kZqr8WIK0M1U74Fg5OjmNRFtV28wFpqh5npBqeyDex5eaB6SnNZfXWSyc9Bgrj6VAFc96\\/lV5rdLn3G7DL6xMoD0UXneiJsyvQla34AuzGbTfGG0gS4wJDDW8N9UTioZvUbvkJ3hnJ7qrsuuspYrUOfqG0ukHN+biRvo+aqH6LmovWOyVMtpWRZbQBMugeVHjvWN1a57Z5ttZlQRXHHT4Hbct7OLXkMuW+m+bTJmjHkepjHXLXlelonUZ+XptLXna8q+AP1LnnS+jnpjLlKlyzoKrx9U+C9XuBTtFqIoBdPgCoky0LDwaxvnPOtQ+RBUzkqtZdDqm8nydBISfyvQgwfped6O3JESNnm+hiOgCeeEPMtzX974QATojPW87f3BFjyu+LObHH+f2aWWeTm6BkBryrJ3S9Cp0usoz9fMNrzQJQ4ojmMd5qROYW8keN59W1Ljo0ra4DtXjgLN4ddc\\/r9VGp4Q4yk10J4LnyfahWEP6Sbyu7td3HzgprBvn\\/UBiY3Qzw4KOqv0fzAe6Rt+b3oaEaAxoaIXHynQM8wdvtoYjdcrP5boY+aKjiIrTOvFJWkwdk1lOfarv2zMgzcjlZ4UYIc2zSg8bcSJBQoIafmRTOt0Ir1RVcroorMgvSskMRu8zpHIw5zqIfYU5KYkzdPeP68Mx09k3key21ng2XadFUtR6Xh9RENI9Pn4EoMg2jYUxkZRCraqxcFS+EaDEMuZ59tGPkbSCS6LIAG2IbdIlz1vLD1IiuBy0LxVy9zqBEwFFxKJnLStS2BGLECi4PWMxQ2olmaW6XKt7ttXyfZXLxYTWijxV31PBniNEHA7eikiuhZMdtN+IEyWDr5ph5DnuHT0YXkdDlty6QqNadONjK1JHHWA3xDmb4rpKc\\/KJ1m6G25DrsqDebZWriHvtTOYd9lN1Fgg84NaaKrkzOd2+geg8j2mZWfEa6VKmjqex86NkbWrPR6jK6nO8Kbztmp2VvAzTXXhvPi15nC4Iqq5vpBBxOKKwlZj6hyRmSg2VVrJWjA1EbadluOe8KTtEc\\/YVE9Di79TvV5ndl9Op5zaTWGD9ay4Z7odW55HtR565iMvRHD7Fn3PmI3FkQHQwLEfluhUsiRns0h6340D\\/W6txoapQbnpcv5zYB\\/\\/xAAuEAACAgICAgIBBAMAAgIDAAACAwEEAAUREhMhFCIGEBUxMiNBQiAkJTM0NUP\\/2gAIAQEAAQUCXxyyI54\\/TnI4xnXnmSmBjjiM4ic9R+nrPrxHHM9c5jI4zmM5yc9znOcTnvCieB5w+c+2TznE5xnScgPfWM6ZEcZOcRnrPWTxnrI\\/jOP\\/AAL3lUc6xn1jPrnA52HPrxhTGR\\/M9cjPrk52j9O2e87ZEzkyWcnk88Rzn2zjPf6SOcZ0zjjOvOdf0kf1gvUTznHv+J\\/mX1Q4UuAieM9Z6ycHiZLiMj3kyP6fXO0RnI8kWcj+kTGSWc5zOczkzOfbPeclk9s+2cTnB5AzkwWdJyB9EHuAyYzp+oY2OuT\\/ABH8xebKIGM6RnHOCPGTHOQMcyMZ\\/EdYz+c65A84QcYIZI+84zjOuRxnTnI6xnHORxGTEFkcRnrnrkZIxnHH6T0zmM7LyZDOYwFFOWVSlmKWMrZHBU6cmuK9jPityKjc+G3Iptz4jePjtiHqcvPEyTgWSblNVkCXearcEWcCphLMGLz\\/ACSZeSJmHxnLsgmzkk3OWZ985bgiwsnvGT2zpnT1A+5CM65IRkjGcZER+ipmCKe81VQcECuD9RU8SkQY89gnOwZBr58i+BNUQZq6WJAhgY7dBibHQ8la+5EBR1HqmRFVniZ4juYcmzrGRwWCBdZCeOmcTnWcAuudCzxnzKmTkJbkqZnhbngbngbnxm5KD6wkuIrnjS5YoQII\\/lFR7osUbSQUfib+5kcMuPWylMvryiJn4aSz4ieVq6x099MFf18fvx4K\\/Urzx5C8Jf1lfuVROQvjIDJX68edM6e\\/H9oXkLzx+vH9ZX9fH6Jfrx4YfXx5KvXjxyey9aqGHXorUk6yHTtwWVfPx2hZuEVOrXzVxE1umCH06Z0zrnTIH69ft19iP16++uQOGP1kPfX31zr9ZH1xnH24+3HsYyI9TH14+px6mMKMnCjOML9LaFlSMJBer3FjwRcWyfyOa06fPwljwO\\/CGO0if\\/R8PsVfXxZ4clOeL2Kvr4ffi9in6yr34shXo1fWVe\\/FnizxfUlZ4vfi+3i9+LIX9fH9DX9SX6NeSvCDJDJDOnsgzpi3nOX0SIeBp5r58eb44LW5+GHKmXtn8RdR3zFdcAfr1yBzpnX2If4+nvpgh9en26Z19EGdffX309dPRDnX31+\\/XBHJH\\/H1+pj6kcIfcj7kfcj7kfv19yP2643uq9bv1rSa5yDlPWOW\\/wDNVbWjy64OibvPx9MYhq52FXlDlmPE8xGcZxkR9ePfGDH1lcdQCBGI+pRnHvjOPXGFHvj3x9uMZ5IEo\\/xzGTHuY9lH+SY98fbj3x\\/k498fY+ebTCO7+RVEO1tKy+lY1tkGt2HUdXJFGa+PIkq85RrFdMaVQAsa3oOsuQ7I\\/TjI\\/r\\/1kfx\\/1kf1LOPYxk\\/xxhRzPGce\\/wDX9U6zeudbnP8Ac\\/z\\/AP0\\/3\\/1x7\\/7\\/AE685qtfUu6JNxTNfSMRvzRiQsOfGEYyNM\\/GVw\\/HH47\\/APrS\\/iP67aqQlrrNw2jYDmOJyP4\\/3\\/r\\/AF\\/vI\\/rP9sjC\\/rn\\/AF+k\\/wBdmfi1ammk9R+ROln\\/AF\\/v\\/vI\\/nByMjIz8LLtWvV65LqE6m5F1cxZPs0w5mtD5dejtmov1kVI2VM8G\\/UmPmVpi1wh1S6tyo8Gd7kP+QHeydiGd7g4zcDDFMsxYi2jgWgxnGcZPON+gAYlP6H\\/T8nnro5zQL8m3\\/wCv9\\/7yMjA\\/iP4j+sfx+LXJHZbJ3i2kckrww8oUajETjPP4oZcnCtlg3jHLVklTXvRLahNsunWWKsLtd8sW4rr\\/AHajODsqpZNsHDV6IObJFjLxqWjahYdY3sKhW77YG3jktynpTvLVk7wBmN0PE3ikfyXZLZq5zTWPjXtXc+bX5nvGNaCV1Nyltr\\/Q\\/wBZ\\/r\\/zmqZ4ru8WM3iUasWRYhULB1gYIKtl8OQtOVKgNdZEFwZB0IZGKlo1FG2ZYU29VmfmqblDQ1gW8lef4D+ngkEU\\/wB0acW9oWUWzFbcHZuEFITj464xa1Z8cygado8tMs0sY5+KSTZuiIGueC0Zy+5A3V4N4lBudkTi\\/H9d8VRegNygIv4n9H\\/j1sR2NZnx2o8mAELyy\\/mYqiOvqutMhVWIm2+zTtvv9wf1OtBYziMQzArvda\\/G9GvXhuziyv4bycda3YrhQhSgt66pibh2Yt2Cry1iDUv4nWq\\/TwKv29jV06w44wQnfEy1tQTA4dmEixhGYl70toKl0Xr8Oz2PlzSa+G58brF28+kulxduCxZxOTlxnhGuwtnXCu2CsO6YVOzIJABpNugoXXSLGM75HJ4LOuH6KIk4NDq7PwwkQjY7UrObHZzwnctXh7m82FKfZkNemM1DfhN3Ff7+NeNjrVAPq40oipv7qZs\\/lJvq\\/cpc7xwRdpiJytUJ03FLrGLWwuqljrCG0m1LdtSE11O29oQ4XbroUMTaVkbB0ZWuxcZqqf7XfefaTkCNl5rF2LUlP3PJjJ9YP8sznmFzObaBPXqaQzWWfi\\/aucs0yrtq6+o8x1GvUi6l1TFWlMyzeZZCSy4QQ515IqY19pgIeyVqBeEyJO2unNeQmM5nNVWZZpfCd5Iin01p0EZFTYWnvoq5Lb0qy2797DvoeGaW0LR8MZ83TV52m2rWU3C9BIwZtkiKO0tCFpGBgTCSHjBDGr6z\\/wBLkyzXx2tlBeRXOEn5VdahIvyF5pChtLFRcmUmi2wMqHHie0nuGOZrj1COSi1Zjj3nM8jMyX8TrF9rAU7TMarRoz93QjLW0uWMkymIHtkLOIqwF2paQ2m2js1NrTi\\/6WjyZyMR0Blk4Mu8zgGzK8QxRJ6n\\/ZRxHkrfzplyUKrBAErrlP8Ax1k7iyrH2YuYQjM9JzS0fIO1tzZKOcQAZAiOWGtfmu1\\/yiLVKO0vTWpcvTinG2NOnHbu71dZNuds\\/n9BjmRHrMxzOo2fxSNK307oEmzPPPPC7fvJjjF9OHr5wY4wIAc8kcq\\/x1zKfJ2yMP8AxK0zG1zqJWeHX+uzP5NT8l18VGrH7a\\/TOsLVUpU4v66xZq0CoJC0obDoSac0VQG4ysgwUkFrKlKEJ+e8V6gSyNQvi3rUJWfXv+v8Z5O0ioiilU+SqKxVVMnkx+xnMZbn0uwko8MxnYunvOJx0CsGz1Eiz+cQHQWMnyUYE888AX\\/pvNW\\/VGWaM3XAdOuVi5MHRhQ5stvXOrQCq1JVaxFXRTQV4jU0t2zsnawcixTs2HyVJdudxjdjeZhsYf8A4AElk9IGg5QLY+6yvrkubX2FFVetpaiWUxL7Txlj+J\\/lbCBg2VsW2MkozmSl88DM5XX6eztnvNKfsYKZfaLsiPv+P7FjC2goZtPocy0SpsZFOFDDa6JlbYlMHYkuBV1ZXrosp+FIt\\/8AYUPn6tratFsU\\/j+rCN2sF7Xr645FId8QhPXSHxjj4rar\\/wCjeGRRUq3lJifflXE2mhlhLFFnaMmZnOMiMvRGVldpLscu190B9xNRHXDUXwNJRTFQUVFrEymE+ZGem485GdoPOfjt2OLXrC942Zxce6zGKeJqsraTFm6WcV9nWBLL6lBbdNi2UFglGKLrK7MRiXiu2y4Mp19jx55Tbs43J8TOMiOUQHk+TE44FnkqngVFJEExNaOWSHmic02rGqh18Az911lrFVtQ7C1YiLNVb4+C9a2kHVxeNaWTWfZGu5HHdFEVIxnBrVM8Hgx6nmJXOFZYvG+QsoXwS7Y2oToYnGFz+sTxhFyUGXFChsXhY12zUQTIxEc5IfXic9xkMygiX45vxcmtWu1lq8BN\\/wAOfi+r97mw6w5VXsEaOtGWNPZ8shsa+K21sTDf9cr7alaY6jrHyejCJdTtrxpeM1MEM1ze6HR1aWBGGP1MjWXywLFeI52aZ8dpxtqSMgcxx+qKxsWCTlevXDbl9RBWTmw2gotlFUYlg4UTGKgZw6kHNFQeBuqsssXLkchIBmgoxZsbCx8Uayp6XNbvosxe21Ql7uxEFuarBQFS09+mrMFGuKrbtTC6MWrqJRur4YO6Asi1qrOJp68Zs642D8O0MCJhZsemXz8dssmSHK1thy9PWfExuft3OFqmYjVs5R6G4CwiO63\\/AI7sIvUH68hnYzbK0ZgWRGNkiIe0ZsqAU9cDxy01zhghCNXTZsrkhWoUVgTnRuvDdHa\\/Y9hU7TQ1VmLn4tUbB\\/i95M\\/C2iipjZipsTga\\/WuYftdNsv0MjjdXZDDVbAddcIkJtnIbW1MBWb5GbqYJvyAyHKPJ5h9at8pHUFLZP+YTwT9QeNQluXNdDFUK3ipw96cjZjw\\/U7FGeEgw2GUwsyxGunYfjl\\/8e2NNEJeYppmbtRTGlXurWYR4xItfRcsdHXibv4s\\/yzpt5Wlez3tYqW\\/vHFbZS5l2I5hK2lOtGRv6uxKmL21SB3tsMTu9ccwettDr1xLd4zi7HmBZqtWG\\/tEAvukBmeY01juuJwgWU+BeQgePDnjPFf2rjBMcOGHtGwtqgtgqxhr1JhQowKazLI7AODHYiuna0etMVbK4YsUbGJdQ2ZMcNtGDvdoiUflk5V\\/I6bcRsKToKvUbi1qQy5MFmyFuLsdJHZWVYneonJZq7uP\\/AB3Vuy3+MCnKJDGbFkN2FHmRR5QPbscSxr8rS4Qymcqvc+\\/f\\/hGamZZfq\\/8A0OnJwh6SoLLCq6rxlsQmvZRtLQRX3fEj+2Wdq60JVU0exp3OtQ6vttezFsUyLGt178s\\/i2uZDPxdkG\\/8d2oTWS2MNoKUk4sDuXwORZOarmJPFsqjDbNaAJ1hc67abG2ccqqT3ypdMYLcKiG2bFhiPx6TVsdZYpjSRC11ynjzwOQ9U5yM\\/oXofxtfr+BdOTlWs2GUawUAnni68LkuUaZhjgzT1YeG1NwsTuLIx\\/8AC24boVnlijuKk1NxsUkj8msxiPyVBZX31FhPdLp3gAOuQvorwNcbtbsgliiGGKesadP6LYM5TCPi7o5XrBZ5MqVCfCq6a+Uw8jL8tlavIa6rzcdZcDBqgoOtBRNeBWXIlEt+P+Po612T6Ofc4lUVyzb7KWZrKkIyaxXbC9cPdkjMbMp+N4WrSp0cAzsevW9VQryHT8XR2gj8aSUs\\/GrRMXXUpW8cbr0jHavfOCK0JQtoieyjtig6g8M1fufykuwU1rrJ2jCE12DjKc96Q9s8khlLW3WlRQxNf1OcFhcRgqApvAtYa8eFOnJycjjnaX\\/K3Ta\\/gdrbO878c1q9dTtM7F\\/u5rouwVUXnOppWUjpKKrT2clfOBuwwcW5qpr7m4EV9ofxEOpPwgkg\\/Yhz9r2Cs2lLYhYTy5xL4bP8a1Ekjd8M2TrCq62qsWjGr8cUshI\\/GhUoJNm2rYFrzUwDU\\/qdlWtUOft44+k5cMED2awgFP8A13FpsDr9d5G7m1Ls\\/F9eFi7aeMZ7jHVXTh84dI3GDfrfsS0wX3PZrR1P8YWxdjQbJOaqqxuw2LBKI+rE3racrfkDRmtu6roNdWw5urS1rNTb5LQkLK7IgLJxNgJQZQVZWIGbM1gk7t9cSjQKRTTsLJeSjVbFDW2U66U2UPCy404RfMrsreKTLgWfyU5M5UrvsMvtjqCex1a40K\\/8ZdunLVNsooUttNaVbus4rTfGBTEYkmFK3m3YFcMBC+eGbFrITObGvYkpgwnnK\\/LW0E\\/G1qdxaVKdyg8h82VtLw0zfGfYs17ZmwKvoqBrlNnvG1HxlpIbcv8AUeLlMTAKTqTq23VMsuqgSjPIYRyEyclGeUM2lhSA45yhW+GvNzZ6ZpavRam8t8gSNasmuMsJktBhjaYNNCLDUwvbuylZ+TLz8hdwY6Rh9liCOLGtZ3pa9xXtuXly02fNDBnBKYyvsba8O5Ss4zSosZUqoprM8sWhDGbQYWq02wnStTUGptadmTsAMWLUvGGoYyukIwuqSUYOFgyBAoVWAgTl9Cz21tfx4Zczds+ENbrV3H7u15JCXhOhizafcf5Ms2AQKLA\\/FhgOAl\\/GJ8TYsNEaqJKIiYBeOpd4mvbUDbGwRlJjP25n9HKgK8pMf05nNXTi2MzjD4y9chcS2bLGaezworNYSc43KuxGUCC7VGuK5u1o+RTQQS3w2XOQKxobDktgvitVNIJXsrQYreYvaUmQVOjaKa1hVNlK0ua6GOecAlLDgRFc3rezZHmf98hhwrTWRU8yk2SwLFubNfzV2InF8zKzMius7Ofz2jWfNC\\/rWa\\/JLvOr1s2TiBEWnxmyvQuGtJpDxi3GuV7V44xFU1Vo7Y2iPbW3tiq5ZOfDCu60Xxg7lofi002Zx1+0bzOAyD4wS9+sWYYqxbVidy+JHdRMrtU3ZboxYWNQq1FiHAXGUK02bG24+cdniv1itU8fMgrOzRzUeVWtmeuMb\\/irbJWbVrLOa3Xse2IgBafGbC30h5ERe4yJz1ylfyFbeOK4meI2NoMHZqYNJy0mF905YK1ZYSJaVlC6deuUu2V5vFg68YyvxMwwc5wCzyFOCIMLt1xbjHFbK2vA3RZF3X2MpfCATpHDIEkvttlz4HjOMp15s2bn9thM5sCzr71lIrLgEVgw8v2oCO\\/kcUAWGHpoZPYc0P8AlvbYolsFHeML3kTMQm+Y4O1iI1ex8mfk9pRabTcjalHeeudYw0QUzVxlTPE0MnnFQM5KomCWYz5Mg8Ay5XbeGWbLGZSuKBQfs1mC1KiHW1SqS+WDkn3bzMnUrFZahS0KYeXbEBD3SwowDjJL9HRBZ+NogXbA8XBzIiXHUxmZjP5wQMhqx49XbYTIoLhQrdWGMmOJj2P\\/ACcYQxhrHh6QjC5DBYWTEHjx8To\\/keeO05S95Po0WHLyvsLPalYNxHTquHY66vXXr1AqszLJTEX5kln\\/AFSASM1U8\\/DVh1ggJ+s6L\\/8ABu\\/\\/AEwA+Mf56xJEAi6wuFn52AbPWv474bCWPEcf\\/8QALhEAAgIBAwIFAwQCAwAAAAAAAAECEQMEEiEQMQUTICJBMFFhFDIzQAYjUmJx\\/9oACAEDAQE\\/Af7K\\/qxKRSKRSKRQ0ikV6ePW0I5LLL\\/orovqWWWWX6K6OW1WJ5MnKIzadS+nf0c9uoFUqJY96oipweyQ4tfHRySIttFdK6r6O6iy+TUTlKP3MWu1EVUkLVccxIxhP3OJJQapChJ\\/B5WU8mfyyOFr9zJ7V2I9yRYuraIc+mjhEIx+WeZFKkecPMyTOPuN0hKyuiXo23649hRSGklY2RJSJT+wo33KoT+4+\\/Wxj9aGzf8ABRLIoe0lFtWSzxhKh6xuRHsV6mSVjcoid+h6iWBuLIZZOG6SMuo8uG6h62bnuNbncoJnn5GqbZgmn3JYos0spJ7GbfU\\/Tjg5ksSXFj0MpTuTNVvxpKKNUppVIjj2P8EnF4Nr+DuiMvsbqIT5sxz3L0cdHx0yS2ogRTlwiMdqJTi3yj2fc8vOpWpHlqcf9iJ4tPPuqP0mOvbIl4bljLjsLDKEvcjHjjT5JYb7GGTxupCTZtZsOxBWxpiXwbWKPwjHj2olfwSY0OEN1J8k7UOB5NQn7laFPBPvwT027+OZqPbh5MOaGTiJNqPYxY1kdsVLrPv032KNixUxRpj2pEl+S5DnL5RplDN73Ez4lOLs0n+PvJHfvZj8H1adTpoyaJ4Z1OJ4k6goo8uW7g026a9zMMa6V0l36JDk3wiKb\\/8ADsOpGxG2SHOadUPIopGqy4YxUcj7mHxZVHHCX4F\\/2Z4l\\/CeJZYwZBSyK2aXBsiR4I8Lox9+nJ+Ebq4RXBOLQi6LLM0I5l7kY9FCElOPBj8TW3nuT1eXP+\\/sZML1eof2RLSxoxQlFUTyPzaQnuHPab7XWxEaFZJsXHJFxmSpdum0gnZLg3ENK79hptD\\/yJafTpe5Gpx479pRb3EZrsUMoa6KTLN5e1i7iaKPkoji3ukabTbeEarVxwLbHuZMjyO5Cv4NpljKPYvnkwJsrpuNwn02lCddE+mNvI9qNPp9qpGq1K08KXclcnbHwQ7CZuJQxvuRioqkKPWuikWWMRQ+5pNOoqzNL9Pi3k8jyPcxDKIwHGihIR\\/\\/EAC0RAAICAQMDAwMEAgMAAAAAAAABAhEDEiExBBBBEyAiBVFhFCMwMhVAM0JS\\/9oACAECAQE\\/Ae1C\\/wBTz2w699X81llllnDJT8F7FvtZKbXB6kj1JGpmtmpmpls1MuRcjc3Nzc3NW1icX5EP2UUUV\\/JH4tJmbGr2MC+O5PLPXSI7oooooooooooooorvRkaUoWZFvZidrY5nRphDZjje6\\/0ZKPxsmparFwYYKL1lilp3G4yWqIpRfksSGWX\\/ABaUVv2RQlsRShwOGGe72Y8VcMbnHbULFJS1WPJBcs9XEetDwmSz\\/wDlEHN89lv7Ypsl+PYuyXnwZFku4np5pcs\\/Sp\\/2ZHpoLgijgVtj96b98peCxNt0JEmRXsjuyShFd67VuLcr2vkRSRqMWCWRaja6IdM5KxdLHSTmk2hZRe1EJuPAlDJ+GSjpfs\\/Sw6hKaMuCMcmmDMXTPJk9M\\/QY1HSdJgUJtM9KH2Mqa4FkkjrIRr1EhZkvAt17UL2Zcqxox5pS30n+ShCGmHJ0ejK25M6R45SuI56icJLPqXkvckjknDajPh0S276SuyV9pOhjelWyU3J2yMZJfGR+4uUet01VKJ6vpy\\/aZjzdXHdOyXWz\\/wC8RfUMTjvserGcLiyU5EctcmWCyK0OSXJqRr7SlQpJDfk6bqov4ze5aM2XWxNLkiovyR1L+rFnz6LcbRjqU7lwKHSTXwlTHj6nH\\/V6hdW1\\/wAsDplr6j4mbBPFvIgmzNkeNbHO5RuYzccCW+zOr6b1d4KhzloURJvhCzNcxNeJ8oWHDLiW51SyYFoU7TOkyvHNNKyf1BSVSgjJLpnvC0z1MijakfSIXkc2Oq3OrjHE7j5M875E+8F2dsUKVjyx8cnJTjwa2uT4PlC6fDKLdmOLnKkdHizyk5YVwQ6XK25ZIlfYmfSMU9P4JyjDZHV9S5yM0texTKELjsqG\\/Jov5M1eT1LPyakfFijXBgnPA24Mf1HJVS3E4szyhSWMxZl0fSr7sXVzvcyS1uxRlKVlUKGo0U\\/ayTXFEMaW4\\/kzJjcWQiW0KaJST4NJo+xk6u61mfrXJ\\/AhkzvhmKU0qYm\\/JaocfPZexpDR6Zp1DWxLUjV+ChTRLIoK2Zs7yM6bpXN6nwRWjZDl9yUrMG63KMzovbtRpGu1iY12aQ0TjGC1GbM8jOmwObtidKjknyOJp+wpzXBbk9yUvHey77V2Q+\\/VZnKVGDHrlRFKK27I8jkJ2ITGf\\/\\/EAEAQAAEDAgIIAwcCBAUFAAMAAAEAAhEDIRIxBBATIjJBUWFxgZEUICNCUmKhM7FygpLBBTBD0eEkNFNjonPw8f\\/aAAgBAQAGPwLNSPc5rdy7qFFlaFyWazWaz181kVkVkuHVwrkuEauWrPVmfcy93MLNZ+5KPuYjyXJclmFmFms1\\/wAK0+iyXNZLIrhWRWS4VyXCsgrQuWrNZrNZlZlc9WX+RCj3LYhU8LEdlBhZhZrP3uermsjq5rJWCyXCslkrALkrRqzWazWZXNc\\/8mCQFEgnty1gQ7GOav8Asufoua4SrghWCuslkv8AlWV49VZXj1XI+avHqrAeqyXJclYhZifBWIVyfRZn0XzFcLl8y4T6rn6qw\\/KvHqvl9V8nqs2riCzb6KeSwHUcUhx4UW9EKog+JUW9Vwt9V8q+RfJ+V\\/p+ikbP0QnDfsi34duyDYYJ7IcJnsgwho8lmz0XCz0QfujyTeEz2WHdHkoxt9EN78I3HouL8LjXH+F+oVxlcblxu9V+o71QufVc9cQsvdhb0ZRJVzHJEYh9kqLWOapsFRh3ZO8uJvquNv8AUuJvquNn9S42f1LjZ6qz2eqs9h81ctUhzfVDL1TXBzc+quQjcIDEPRMhT\\/ZZfhXMdyjgId4Lhd6FcJ9FwH+lcLvRcLvRHi\\/pXCfRcBXAVwFcBXCpw\\/lZN9V8vqvlWbYWbFIbA5J17wsNOYKe5jMIGcp2OnLcE4gRkmPgHC6YRqeysAnkobsyPBNe5rZPRZKXUwTOa\\/Sb6KAIQ1c\\/cGo21Cyu2VYasvcPuZe\\/lq2zXbrTF0BkXqo52jMdUZvNeW3K9pqaO8Pi5a\\/JF2xduU8LakjD6aqmwDYaLynU\\/wDEKD6VblhNihAtJ90e6ENR1D\\/IKOry\\/wArC84QBu3tKaCTcwn0Kjt9jbFx5IBl34c1WfTp4ahjLx1aRsmuI3SYug\\/SNEa9jhhD5yTbdfdb7oQ1FDxHujx1lHx90ePuDUNTtGdw1DHgqLmMtInyUsp7xJmFTc0wal097HiHwD6\\/8aqz+RgFMeGbWTBvBXtGDBi5avPWNQ1hDUU3x90eOo+CK8\\/cCb7g8PcOOC9lS4803DNOoH\\/pu5IFoxHoqYIsyfOUyiMx18ZTnbMYcY9E9vDvCO907tXd\\/ZMLzGajGFDSJlZahqHuYLx4oNEwNTfH3RqOpmzAu6+seOoe55ajqqVHWcXyqdZuE1wQC8CCfFMe9hqsacgtCYILhUdI8Uwiz5fceIVUtcRhLfytIxPPw8vVVGsdlVi\\/SFswSzRqefdYW6NSj+GVtdD3Xf8AjmxWB\\/F39we+3x1HWBqOqT0Q0WtT2mN8Mc3Me55aiinIo6tMq1qDTpALt7mIFk1ntUPtjplsSeqpOmd5UalJxp1nVi2ZTqNUl0AhVMN7MVZv1OI\\/Cq\\/e6U3+I6\\/bNHG9\\/qNHPv4qRVp1qJ+U2d6oB80z9ykaz7rdR9+u\\/pSKD6bi145plHSW7WTGIZ63ajqd46vPVpmjutMftChlJzag5plXBiwGYVFr3YcNfGbclVcDxMJTi20NBssL6Tjvhxc0IweFYK1TC6clArD0X64W7WY7zXtOj2abvb07oTzU037M9kwsfo9Wh8wycgDu\\/wASYKWCH9VLqNIxzD1s9m5rzYWlB9V78A5bIq7o8U3Zua73C+DborG8XHTWfBaR3AGrRh96Oo6j4+4NVSTxs\\/ZV6MWx28EXG1kMNrX8UAKjS8jJCdId\\/KIRa15E\\/dmoWamU14eIfcQUCSAcsSZTbpAGN0SRK2lGttTzZEeijJ3MOssVYsjsVBqtHYoYdJZbLeWE1w4dnJz6UYnczdbzWuCxnDgbmnHZbJgbmc3FQ55HkVxgeK\\/Ub6p282fFPql9IVKmeJyhzAv0HOn6br\\/t3hPoYcL3EW1NrCJbOa2sRePFHpqNSoYaF7MWOa5x3SL6h4LyXlqp1CYjNMc4HebEqRXtlcIYBu8upKl13HNf3W0pD4Z+ZxhQ6jje7L4mXooeDEHhVqL6Z7qm59IOlvgsQc2OSsSEzG5ow5mc1NSqzF1Qp09pWJyGBHSf8QaBuzh5NTtlEYt1CpVcGjtmpp1KgPLeTmUdIqSBMSqlMnG1tnghNqVG4aoMQRmgMYZTZwiE\\/FBLeY5q1QBfPU8ipp6K+Oparf4fWPfZosO2ok8slL3uM8scrFU9EGtEIEgHsUKT3YG4Th2doXw9IbUHR9lOkUHtH1ZhdvlavaK369Qf0hHwQpuqNa4iwJ9z4VSk78KjjA2lNzSYWSJtPLsFAK9sdL3OO6BkPFbOkxrvvIyRdXqXPRHZVzgNwgbX5FB1O0ONl25oQsJyQ0ei3G48MLbVoqaQefJvgtmyuNlBxlqljC4ciEKbaJBsblN9r0hjMOcJ5oXLuJxKd7NRfUE3FIQFgrYdGcRw8Tytp7VW7jZYU46M2oBG\\/idKtRwDq+kppaTAPyAwiRSF+qdUIs0J8jhsV1cvv6Ik3Kum1KoJb+y22NuA3xL\\/ANY4R1R0zTBM8DT+6+DpFRnY3CO32Tukf7L2v\\/EHOFL5ARxLcc0+Gsl0t8VpYaIc2Wj0QdpNUTEwv7JtSpRrBjxunDbsqdCpyu4SsLAAOQCzW9dZ3UclIyKwi55INrMwuImFpFWoGNcyN89EaVDE2jlPNy2FEfDnePVbrWjyUbUjwUvq\\/lb5Lytm21KpY9im13MkHduv02+idu8boVy7+pYzu+aOCq\\/BytK2RofEnMf7LE\\/cn+oogWV1ZcbWptMVdofm7LZio7B9PJNeWh4zjstsKhHIjK\\/RF+IHojWqk7Bpjx7JtNrN0WwwsdQCj\\/C6D6L4ekYx0eFDtGk\\/aUdGrs29A\\/6sWlaaxzpoPZtA4pzs0J65ICtUc5rbNbOSgKfz7k81C8F\\/h+k4pc8OBnsnNY7PNQXxP4TSzSGuIkw9sSmsr0i3+6AdV2R+k7qlz6n8QqZLaMcNJ0c5PCjEmNqEQ3orc8kyn9Al1+akPlSG+AW+YChonqsOMN6u\\/wBkAx0uHMa6r6FVoqtMBpzTtvLYznmiMFQEcw5OI0jac7iPJe0U6QaHc5sv+t\\/xCkwDNlPePh2Qp6No263LGf7INDnYTypbq2zqdQNP1ZhDR6hl44e6yW68uEQGxkjSoNeC7Nx6K6LjlqnFJJQGVlciUThQ1CMjqa3FYZBOm8BfqO8NRb8zN5uplOkY6lPpCKlN3yOyB6rHN0J3gvbqvA0bg6lPqvdcr6lcw1fSxGnSy668ESeywvaD4omjO0A3QOax6ZSZQpkX2j4UP0irpHanafNRoOg0KP3OGJy+JXe\\/zsoxL\\/dF+QCpl5fVlu9LrJrwSGm7HBNdUfgfkdRKOqyG0fClpkdVHdFuYzTpAQZiFxKe0+IU9QpVar\\/KgAJRVaq+3wifwm42srNjnn6qpLcJO+PFS1uG2U6qlXSGltKIBK2dJsUWWAXDZC6L6hy\\/COyYdmMyuE4RxP6J1KhXOEZOcLT0TqbaT6hbnAt6qdK0yjR+1u8VGxqaY9vN5t+Fh0ak3RqfSk2FL3ucfuPuRqkkrA\\/9M\\/hCm87uEYSn0p4emoa81PNWW9mnGkc+Soxm4H90DOQR76sPzHNHG0uo1M45IFlTF0wlYqj2tZzLrL2TQ7U3GHVI5c1RNMBrS3CGjMQrCT0QqVXBjDkgA3E8+qc6pWbo7W3wdvFbKs0NPKoL\\/hY2HCyL+KmiXP6jDAW10lsvbwsIt4rZVKbcLvlhbOi0MAyACeNGdfk0lBlasWgclL8VT+IqAwDyTqlR0ATKOHLl7gVoRdvEDPC2U+pTaIbmXuQbV0ypsr8NkTcygF0UrBWaQOyxUyHDst9qsrlAB28cwqZ+mkNe0esSxU6pYc+yAqUgT1bZAPp1yeQlDR2hk5YcK29XQ9JqE5XsAjTZQo0nTBDrkFBja2KeYbZMr1jHc8kadNlUgmJcM0Kj6O9zfUzKxDaYG8WzcjhwOvZxMoVqciLoYdHk+KvNN3Qr4dbe+klOq6O1u1HI81xvp+DIW\\/pdY\\/zreqOd4n3LL5i\\/8L\\/tjUqdQ2U+NHaxkXxFPwVtkzFEDmto6rVq1DYSVjqQSXLyXlrDmGCsD24ey6ar8ky\\/yjVjdkranU8jmsV5J5lRSJHy25raFsxkthpTnBzBIccj2PdVq1AYi9oJ7qGm6wc2WcENlUmsTzvhCFQZ\\/MsLPmH5UjdJzasAJIXfNOGGHZ2WBwvyWEuLh9D8\\/JGm+litu9VirNb4YclHs4dPVaRTpNDGNfAA13NlGzan0vNVXdihT\\/8AYtHpN4nOKwU6Ic0cydVy31UAz4Leb5689XdN8B+yk5IU6YRc7RqseCi4W2a+S03CqVW\\/KBiIGSr6e8i27Snl3RLaxJZcWsnVdu\\/LKM1tXX3ZPZbakMswqdYZcJTao8Ctg98O5LEOSmVmUSSVI8YUix6dFv0sbBwPzITajt4cnRBTZc6YyDCU12F75+lVK0cbibqTz1HouEp1QNs5PbhNwsIbiVORGD8K7R5auyBzhXut0xqhokqDmg0c197UKVMSUK1eMcc\\/lW0p7ze2fiorAT\\/7KcrdLWz9DoRZR0qs5pM4XZL4Wzf3xLDXo1AD2lOo4SKhJ8CmNxNgtviWMNOA5hOOjGGnNhzaUWIPpZ53Ujmiw5jXiWNilgnqCjUpudj5g3UVWljvmHIqtVaRwYWoe5mpWa22jUHYOTjYJ1R1KplctvKh0gqx1W14zOD90AHuZ2am1aoh7vmCNFtSnBEl3Mr7yLoabpDb\\/IOndeyUJw\\/Mf7LYPkWve6+HUqMP3CUHUn03tF84\\/dSWVmoNc8HrIUOpu8kGbLE\\/kHU0XOpFruZa9YtE000z9L22XxtAo14+ekVJ0N7OxJRG9fso6KeusoYbKK7Y7r4NZvgtphuM02iartm0+UrCRHuF2E5bqe7DYKlSdk50ICkAKQAEDlqfSdo7nRzgXRdUaT0AUUmhoV1kvhmJTaIw4mCIlTVfFLmSVsNF4W2BT9pLiQhWrtOwa71Kwzd2QWOMTyE6vsXEu+ZjpQY6q7Efle1DbaMx\\/dphAbWtojwcyLIVDpbNJPMVIU02Fv8AA6yG+b2yVTDxQt2rVZ4rfLHjuFFagf5TKioKfg5ix6K8jqGvt+V8OoJ7hS6nPgm2cOywkJrDzCkrdMI06r8UdURyKp4GOc6MJ8kNpUh3a63KjH\\/hYq4t0CvlEJxZbFxd1EmyG3G\\/wn7vBYtHdiH0p3wKgw24VhnXktH0llV1Ta8UhdD1CwnS3lnQlbqFJtm5vd0CDcAwNEBvVGvVzKIbTD6AtiTXXLOyAdguI31+jRJP0WXwq1WkfVTQq6PWHRwhQKWkUT1BBasWlv2lT5ZFwhLC6XCVvUXDxhYWtZiHkpplw\\/KtDl+nVbHRNIqEHmoL5d\\/DZNDWjFiRFUQUHNvBVzqxNTTUDmM5dVhY2B7u80jwK2lI7zfyqFCL4cR8VnjHdb0jxEre0d5HVtwt+B4owWjsAv8AlUNHqVRSqDebzt3Tq5DHsbngKgtgJtJoLyeiwsb4nqm7UEv+W+S2eJmIDhcVv6LSxH6bI7F9Si08sSc+jpDXjlispp7T+R0rCcZ+1zUNtozL5QVs3aI9juRkIHqvjGOl4QwVCfG6Eb0H5Spp1K9uTwvj6PTq9eRXxaVWiVuV6Z7FHAIAsEyi3iaJKfsTvEXWxYxznLHpGktZ2aJKaKTTLebk+oBihOoON23b4IqTTZ6KzSPNcZVnBcvVUqObnvg9k5\\/lrmnWfHjK\\/wCs0SlV7xBUsdUoHpEhOq6O+lpPNpGaJqaMWQJx\\/UnMdcf2VSi4klroA5rbvbgquFuy9la\\/enfLeXZB3G\\/lJRfVZivJwgFTSrVqR9FvltQDsor6N\\/St8lnkoFam7xROzZfoiaIcSbSVh5hUt1xEZ91BdVou7hDFVJ7PC+LTqM7htkG1GUKk\\/U2FLKbmfwORqM0rdbchzURFgqr8RubQsLshyReyQVhLQ1qn907dtw3THN8PJGOvvF\\/043flCc9cNnF9q2TWl75iOaaNL3qgF2zZF9Ldafp5KNpjHR11v0o6lpTdPfV344HWBPVH2cy6LQi5\\/i4p9LfgO4uq3NJZ5qz2PB7r4miU\\/IR+yLqRrUz9plRR0oO7PZCkUsf\\/AONyZRe19MjidksQ3miwutpyJTaNoB4oTWYgWwt529HohDN4c4W8R4LaaDpddv2uEp1DSLtA3pCe8gTmtoAuFsq4Ln\\/asQpQD9S2tXSvZw7JgEx4p2FzatIcRAQqu43J0CYK3mELijxVnA6iegTnzBcYH9\\/caQDiPCwZlTZ2ku4nfSjBHiU+ix5cGDMfMgGPcZykL4tNe1V5FBpsD8621J5px9NlFTBVb3C+JorqB+qktpoH+IU3RyfYrFs6oH1Mv+y\\/7h3mt9rXeK+JRjzUbdjexzTH06hwHKENmAGtMuTGgjhCc9jw29sQRJAcPsKh7XA9MKv8MfeQtpWh7uQX09lOI4nOMTyVS94hQ0Pd4Bb7tn5Lk8gxfmrQGN3t7IL4dZtTrDk3aYgD15rYlwnlZOw3EqCoTvBNLHOAbT6803FULvhy6VRJzguPn7jnkN27syPk7anaHo7tz\\/UcOfZe1VThEWlFhcJdwBl48Vs6rfhtbdyDGtDabeFvRPcYF4ErabZrxMRCu3Cg3FmtpXq1RUPC3Fwot07QqdQjMgXTGNnRi3mOaDqWnBzPubn5hD9IsJF2uyCZSYIbTFgtH0Sg6A046h\\/sj2XAwieqkte3zlObjm6awsBcb+AWOq\\/waViDQFVL\\/qAVOiHcRusLM+Z6rZTAVt5NtBJMrC0oh0Ec0DRpkkHetl5rDVpuDgd6y3ajVwg+BUEEeSza7yutkww+pDQPNfge5vSR2Xsejvj63j9l7XpX6I4R9X\\/C2VEfDmAB8ympfSH3eenZEA21MJ0gsaw8OHNU2YmhpfgbOROd18fQWUX5bh\\/2W2bWqPp0jdj2806oTnl2Ra255qMj3U06jm+BW8W1B3XtVWngpeMrHTex9SZWEyJ6L4Vd7fESvh1adT8JtfBfIrE44MpnlCbidjdnfknOwkxyQfETdbObMCBBOLlKNYtbflKYa3z5QpjE13IIVagczEJ3rIUaTto7M4eSNCvTeaRO44cvFCpRcHtdkQU6m1wJHygLE5oLzmt0vb+VLWip4ZrRMdJ4c2peQoHubDRh\\/E+Vjq7tNpv37LYUpbSb0Taj3Q2leOpRp0gAOcKDMlS1wHkm0BLnHOENrTqMYyC3e1RNlHLmUHbGm57bMht5WL2lzdI+a0tlEsptrN6scvZ3scAWnaBwyCNGm2KbRhCLJuFu1nR0N18WnI+0qNph7OshUfUeT\\/FZOrCq7G4+ScWaYGyI4EK2l6S6oJ4ZssLRkqlSblyxvEuKkEBABrhS5uOSYwg\\/DM2T\\/iHFhOa+I+m2s+7hN1tadSDyA6KpWG7WrCenghT0um\\/HH6uax0ajajeyDmsxM5lE0KjmHKYyTatavtHtMjwV8uvufqkNHEYWwonC3nCwsBJKLBeu7jPTtqii6wtb5iga4LqxE4RchF1fRn43G5IQZgfLuQWzB3jmpJVowoOp3bS5nqrhs+KMinMcnf2TnVKhfVfzPIINp8RsqprODnzbCsL8TXdCF1WysC7hWN7YrVcp5LBVa2pFlvPq0T6hHDXZV6dU8C5hO535LFOQzTRsTpHT\\/wDiGK1sgnOpWccysOkNFVvhdGvTOIEYhuiVSbUDM94YYUQjDA7sjpGju2TjYsfdpWy0qn7O\\/rmw+awluzHJ3ynzU5g91h4m8uqscJXVXcAfFDRtH80ImVtXfruy+wLPzR0alxnjvl2Q0uu3+Gf3RqOcZKggFe17NuP\\/AE0S7MoBuyP21BMohrQ0xL8GSZsMZYRcNuVD2nwexOrGlTAbm4BYlspuM1s8UBpUHC8fddfDhU21G4WzJIRLSdzJSBIi6vZS0qNpjHRy\\/wCr0MYurUH0qlSlHI3WGi3xPM64HEngbzsWX\\/74J1dtFjamWHHKw7TBU+h2aJ5BO0wuLWzuN6N6poqNLfFuf+6Ps73N6t\\/4WIfBP23Yf5eXkodhY85Qd1yhwT3vl1J4kA5NKe8jN3JEjDV6lpXtFa7xwsPLuVJueZW7xHJCo8u2YvUJ59l7No\\/AMyP2VqjkXVnRQp3ef7LKAOFS5e0kwyLTzTsRa7HmgWfpuyvkUymycb8k3RWcruPUqSVufMcyh7O+83LijhFRzpthPJcLyB9TYQ0iqIfU4R2TuakkN2h5q0OHZdFmqdd0sDTvD69ea\\/UwBbSjUZV8CqntFOoIjNbag+D2WHT9D\\/naFsWVxUY61nX8CgAIDRDPBY9tsyTk2wP9kXbZpbE9lTa6jUa42xTl5pxpWnNClpFxMNenut1sgKjZOa4p8QofTlQ8YPEIvZpLsR8CjR0dzb+S3qTvK6bRaN4oaNT4RxHqUXuNgpdZvPshRF6dPktwBg7LA4BwOd1iqN3ojwKJOawhpIpc5sUW1HXC3ao9VILT5oMWBuTLKAg7aYfpshU2jHB1rKCZQqVBhoj\\/AOkGtAAGrNYnatx+FYaobUHdMraRQ2Exv03RCwivT0lncby2ujONGt9TCm09O0pp0fm4iVjFD2imc8G8tpoYDY4qSLXSypi4XCFUqvYWlmR6rdbmZ7IaITuMddAdlzC5FcwoktP1BYqVVzh2\\/wBlvYXQpNPCeoQvB6yopVxGZ7qKDdrUjkpq03tPUjUG8uZ6BY6PAGxA6KJ3zYLMBzrqTq3XwnV6zi51SzOwRn1RqusCuOPFMFJ4qAGc1Ndhaxuc81haIA5aiAiajSe\\/RXUagxsbVn\\/0EKQ6KWugq78fZ11hrUs81j0Ss6n2Dv7Lfp6PW7jcciTQpvof+N7r+oWywVhS+l7sin1MEOa2x7qmOQdJPUot6arLNXYtyoR2XxGB3dblbB\\/Fkt4Qtx5HmuIO8VFWlPgviUmjyTm0jZ3dGq1zX25rBUZBbdoXYa2UR8xv2C2VJu6xtk2kPmzTaTcgpXSmOJyDQIA1QFieV4otYZHQrI+OqmDm04l4CEbc1muikFRiKuDPVYsMEGSTkmYOOoRZNbb6nLGWziuuFWVkYUqy3mL9YNJ5FT+WFDZ1BWtyzCgrM+azVnlNc7iavjMD6RvlML\\/xk\\/Spo6SP5gnue0ue6wIyRkEJ9Y8IyRdzWBtup6IMYIA1ZouOq5hWU5lThIVStM7sKo7xVlkuyuPRbpTnYd0c0XfU5UmOc2GC1oVWs9zccdVBqFnadRUqdeSyViVJv4pgeMWLqixpMd9Wep7Dki0ZLcqOCiQfJYagaR4KH0GX6WRfTx+qbhGdzqKLifmjUJYDKiPyjdyJl0qx5KoepKcsUXR7KI7oNiQVDU1oiAciFTAWA5RiWBsXN+6sAPJf\\/8QAJhABAAICAgEEAgMBAQAAAAAAAQARITFBUWFxgZGhscHR4fAQ8f\\/aAAgBAQABPyGo3TmYyoeOp8iG5lhJcg8pm1QOLQC7ym4CUlbI4EU2kocxt\\/CVkLTuUSjOX0JbwfaWH6I7S70hbai6XzPBUF\\/6gvAg3qALWfE9EHiDCn6lWu0CKVgbDzt949j8s2ks9JgcPiemUqgHkiGbInWi9Ut4RO01XDJEJli8SsZanPcNzFY2wbaOkV5g4mN5JkPAi5Cz6QpSxqKFB+6HkviIcYeJdrOpYZDN2Pj\\/AIxez7Qy0+YHX5f1LbD8\\/wBRpmBP4f7h\\/wCSal\\/EMG\\/KvSC7SnDPPyRay\\/KaRkHCpkiTNETip4Jgi4lGCJoqXuXx\\/wANEYaPtr3aBChN3UDzApwldbH6\\/EwWfiXAz8Suo16TsL7Ti6+EG3MqajYIgumeKR7cQ5feJ\\/7gHoR1knIJoxXyhkegg\\/8ACXWunUeJXFCsE5D8oo3D5\\/mU6RHC69ZS2kGKXRENRM2Sq5hy8RVhxmDwzLgTdypxgK3hKccSntGTS9IrtT2gch9IWPqkBse5N417k9GeUhbi3vHigHcUHEfedCI4EfeGls+IEwKoo\\/XA3NCAchnqPrKClnk9wRIi8Hf7InLDCGoin+EjbL4ZTZ7JbSfrCM16CP7GX05A18oQxYIfKQbj4f8AnKkHkuiL+sR7GI1qYvxkOFvnxuEQvCFHZq0qOwH7435t8qB\\/W2dQfmJMVg5GPl\\/McGX1\\/wAygaXOscpwWZfzM2+7TiH5SZOV2X\\/cMKY\\/85jb4PtDas8f+51hSAL+aVnA1gwUBz4wbLFuyzMYX0CZGePESHCzwqZlzwPFNkzdYYOBg4xb9jMUU5U9wTLMu6joBuFrCXIlM\\/iYd6lHTLdsFyRGq6qL9xGHZF\\/7FlMNuHrHkCUacwvrrUduWeoEjv0JXVh6yK7V6IXPxI4d3jFWsE3JnzGY2uIisV0IKt7XPEQXCDL9SGFz8TBFIF4GjNEag\\/KCFKrzlMRMhVKSp9gxOl\\/rqWV7+\\/8AEvvh7fxOv5Mwv5soaM9txe231heDUfVP\\/JxYNQHKSz+86ief\\/X2jtQf54gM6UXzAtL3OZS\\/QyiuX0hIVyHmGnkBZNVFB0GPPMVqTO2244tQs00wfMIesdNtm+5nvzSoCvXpiF5ONsCFTBhTxPI3BrebiFMveOEhm4g8Zkw5ngT5CdXrjzA9R+ozQeog6J6EwuJd6UVjEXZicVcQzKmRxNlHM8Ext0lSxxHoiDi61FFQXHDUqfZl4QaD8kcie0V3QE3mXYQedp+IzHKoiK1XX5jOywrBvNbH9zmNSwt9D4jOVawQ7\\/wAxzroHzKW+kPhzK3qG+I5+v9Qy1OtHF7wzQdEeDzPTKTDuBXHJ+ZwE3Yhk45hD8COZifih1Q4kw+qBnAHiE8eP+EKqBVeYZi94\\/lBr1lExM80wWrMu42QYRNfqMX2ggMKpjU2WrgPD2QGmctXRs+f+cazIwHrnfEzPE1saSmptVvbe5S+OoPHllb1AZxAvDn9QPCDXERXHcDbEDRxOH5m+pwnLMZX\\/AKQa4lLcQFOJ4uJhw4RNMR6OJ0IZSyn1n4pk+k0vJMVI8XMymOZnJnNcxxTLFI0hqo5Wo+E9QUVfvFApZPAnIOW4O4tImBl\\/z8fEPuDws8XRe\\/Eq0DlhVKTlt7qNW5\\/i8wN+02+sftgeE6Aamn3hkwPajwO5tqdblmp4x6oZsTZiU4zPjD9U+snNUuFe0pXoxxTW8kz+qYrzMCYcwpgPVGH0Y+EswAoYFIGqsU6cG86qbu6GWbGZs+E69PSDSPZMqwtUQyQHEOFf1Ar20NnpB7cvyntr3CaXk8zR6S4cVmfmhn\\/ztcM31mEeJm7YOdphQFFt\\/wDF9ZPxzdjnjjLfX\\/Ufqhn+k1fWIZ1lnqZV7qccvCMafCs+hHr4j9c3eP2\\/qbY\\/jSrFrEyQyqKpuBloOg15RNwrTOk3HnLlMhSpXWvGU1g\\/cqJKObxsRFIaV8U\\/TDcgY9UNwxjmjb6mkw5C91iWKDb9D0wAxnHb0fP\\/ABWCWviHBKw95W4cJjC8hmVmD6zJ8\\/0n4ZobqUG8G46Q1L06ajl7Q+RKkoNBdlSVqorxZzDaesTH5g+JK+H7xM4GfoJ+BAyeh+\\/3A+UC1+ZkZQVwbda0YtM9HDqPLUe4w4EblDBb0QEfZ5gYT3NZy\\/PceFlm8jEY1W+xPuXwcUPglcX\\/AFJQlHovTwm30KaPTR95QHqivuIBLPE0SvrEgaR1lQ4Q\\/dDczdt7mz\\/iYeCcs795inwz\\/VSq\\/c0VwOzFRJ4I6N494j8Wc\\/Qhu8EdvxB8tfU79YMrt\\/x+oDNHM1XynL1h58VCq3dNVxbl5yPHowS1j7EfDdv0Rlv2gHiCY4tlaLUww9wXwCINTADgBbuxyfELEvNC+upkuerQqA55E5lurBoNsxBcot36fuY4vIqxlrZbvR8ajXiK0\\/k4\\/EKBWlcP6YrTJN91EYZH+NS7enG+lbmfNr2PEKD25II7ywY1tGWOIEVWYlZAqgt9phxQPJ6jiXucf8DbP2DHtiCl4vxn9Rynic4fZO\\/LNV5TSfar+5oZ9SDCbgvvVx0BuPlkjFBKqWJmla\\/vK0GOgebxBM+nAfaOKMzeXqjmlD1O0xEKLDe40hzwh5jyieUIY5oUBjUqqRyfvRlmE8se0CZhWBfiVPoxDu9aamAkashuJuK3s8SlOuMbYxZDi4fL24vg5l8m0BknDB2qlZdPpmJITFCOuLZ\\/D6xGh5EqUCaS4fdo1LnQYk4czrExWbKNQ0r5g48iU4i\\/N6Yrt8zvi3+IcwyH4PUMX8TCTCGr9fpK4mLA\\/cOKmNTkHr+qljqrGT+PuWclGpEkw3LrwTTrM4OfUmNHiV\\/l\\/EpqoyL\\/ABjEOo0zfrEJx5lOFDYuQ1eJaKWltwxvO5coGkr6p6QGc\\/JEVgKG1v1Mo6cI1le2WmtpqZrjcAvtAtyzPQa+0awVbtj3mLGIW05JVjla3sjPt933fM9iDnqmFQvm4Nx7TEunTyS\\/maQhwFUbGpnKcZzorPi5oYcWh8UGYJqjnQx4Cdknr8TSi4rfP\\/k8HeYPfiUcsGC\\/L5l2d3HycevccDVjsizecamrLqNd1oF\\/MKrN2y9P5+pZqqNXbUuu9aXHgIzKrlZjrEJjtp8sMoJNNlHniBkeKpOMCPFdS1igLyZlTdoekhOHbQh8Hpdsfs5DHoKwHHcA1lx9iFg+ZNPllFbdg+IL9OCCw\\/uBPDmVyvTjbL0ria6wBu6XmJslDL4H8wpZrZk9FXPniV00cK\\/xA6fgMPNFwP3qIpXJcoRo1q+pexdE8tv7qfXTzLgArfAY4VjliVMrhuBZjvzA10F74Y1Qa93dsPoR3M+f6l7nK25phqpkswQ21aFL+IO3hhGvSdfMsxAdPohWCU8Jxfm4YSM3Xp5YIQpu70H4\\/wB5iAnWq3b0uAzZ0C+vQgUg8AjNWDqbwwcXLuYMg4G\\/MBEjRPDN+kVcRZ1L4ZHB8\\/WZxLQV2+hCWdNbDODx+4VS3ulAC8QlvtPdmC35MwrfED4X9RqRtMvMDcD7YdcFBxUCi73TiY5tCog0qH2f6lU2JND65S3YrvGV9YQO\\/Nb93uO6ojRh2w+czF+VVVbiUmG22y9oEB8+xuOwQCUR4I1WcL5mUIrz6RjZMAsV6T\\/XcWH8SkajWt7wfy1sJri7gdB\\/ZCF+Zmhbt1i4QN2boS64MfupYRbypuFDDSB8f7rzGkGnau0TXMuqZjtUQhXyhkIBsc5S71F5KqNkqtDFyl8O36JiTm4lPm2L1lbounh1NW9w\\/wDhlb01ur0iYUbyWvaG0o8juHm11AaFuA7YL0rqEJjgaxzGL0PQJUHPf\\/kbtzqntLx1HYYjLTk8QDFpDTaSy8zKgXnv6J0RoOvSo\\/bKsZleXF8xvkQ1Y+t1Dm8N1fVB\\/im1QeyMWJVX57YIOXNcvlnaOLhBeHvChLUFz0dRX9GrjmiNQS3nP9wkvCHUdTZmWa1C+WelRRQpzPYwYsHqyI3ftLZejDNt5lhPLY4O5vA\\/vlhePRaJ20vFyzQspfs9EmQnszIt3XvWjqZsIdvQf7MeqjbUxK5aI3jzLfI\\/qKkssJh9plO4\\/wBzBczV7v8AEGXXMcwy3DQaYfCfBSW2QMIcyqItPc+nxNeCU7+nMuU3VX3JT8CvXm2O47rD4S+PaSmGB7U7YW1p9oZI4gXkK3UupC5xBQAUrt7jhTsf7uVICgPmIlQrpbK0fuSUYrwiVqU2MRdGHLxEitdniKhoErmAX9JMAoyekFJmp2roMoSOVldF41BZhF7\\/ADuMXANa8gEqeYWurn0mPaPlAGFi+Z0F+kIWWpXnIQJpsagW1TdEGqiOSiYsvPXogwS1Bq\\/LLw6e1BbeC37EmvEbrVKr1wF\\/qPrH3K061X+H4M0uWj99xtfd5GK4+Yrky4xAi2tj4h2K+Yjip16uIGxVLxjCSom1SjCxWt46iiZMXr+j8ywwjbu5iDYPPcOirLR1R1ls1Mqh+hMmb2R+GXAbtxi8ktEXQXaZrXUrlX\\/gQGxo87dwC07WCvPmH5Xih8xXw8TA6vFfM+3R1h29vcUoi4pNJbg5g8VUYtMJIaFVI4Q4+JThs5DPa3LeeVvPgHE3kMN+TmXrE1gp07ie7r0HrieHWglPti0FvVwYS\\/zr9TLvxPxMKXxB1M8Wo2oRO26\\/7cuzOIYAt5ahMpLtg9XicDbUvXAfuFxUoY73Wc11Gz8rvbMx0yhdirxNG5nFShzqXus3ImYidUYhhcr\\/AIJh8UKcSqvZc2ZZdoOorRFHZGyG0wWNwrk\\/I+pty7VJjwx2g0A2ZUh+GHQJ9xRZQzMNw2C3WZdPYQkuVDYJHjsip7ZCFu6m5+qzi+x\\/UqJ3K168S6Q+bxLQMuOej4feDjU5ig+RHTx5jMVT\\/a4m+eyPxBKK3nf+txOgHlmFuxvhMPLgN9o2yVXE1VtHltFy4cLcFzKcz6GJUmMGn3f3EOel7\\/uAxqo3YNTNgLaGF\\/UYqmnUIbidb3RLWC\\/1LWX6IiqsA1EWafiKW+DZ8xCbI5IXUDbb0Sm7S+UIU0NYfRBZXccgzUTCL0JzL3fzDmcEH\\/s4CuBh35iPAWH6RyB\\/6+O6tTieplsnxj2fuWgS\\/u9zUEI60\\/muYiszfhbpiMy3CDT9xBkNLl+dRLIRqAI7PiWBTU0aes2IxtLYqbFfbUrzZJnQyfQL\\/NQsWwD6IGdGKN4lPsqYpV\\/5xFGZUxwmcFOhphYjMOpsEwUP14EBUuwnrSZdoSzOtUczAxNjT6iXl3TKF2ur2MJcUBRquM9q34JhPKP3IAWMO3zzHqIL8y+vEaWKUC6axFG4pst+s3VS+KcyoJrtN+SZTEu43A\\/MuavSK6K5li8mHB7+YHo7GFx4MSg+TAGJsF1db8GI3wWaI1i85xMRqL0zB096E5B6k0aDP3RXFOSG29NvcRt9xmG8yIOdLnMD4TLGWXKCddg31KiUC5GA4H1A0q65gZnQFxFZTZEp2dXLu6EznRLl6jVBtlzHL0gbk04CiCrZur9Ll8thG153hvq5Riwa\\/r8XG7ROmvaZLptxVm2uGWXRSgQ\\/1QsYAGjj6lyEewTq4gUFs9TE53XwwG44JyYOGC5YAFo9Jal\\/JPoQUWxuDWp5LxMdE+58QMHeVUhtKwpC3Qyu1wfTKPROzLapBit+s\\/j5gGAlofhBmYaSfMtnqPwjEq9cyJmBoRlljXmIbS5NW4I0g0GrwrEGaaCMtM2tX5gBBC8dA5hslnCOvMIA9\\/hFTMt99cIj0HBoZkex0j8QHCkXL6YROldlpC+nsiPRQ5X+ozPLR+ZecFOY9mWvzVter\\/Utkbp34g+euSl\\/UMgdIV9zp6SjpiMo9F1aUR6EqGWCW7qrTUFu9y+Zlh\\/CgjlsFw8pmuTUYZJyzbj8NsbyMOTnqCAonxuEmAcAnDdb2kcFVx43kmYTYO4xfsKZyheNzYhgClKs6gmctAff5j+jt8D1EcCqQWmqidyp8xkx1ekVjxP3wqotvetRzLroA8Vd\\/Ua31aZ7w+q+wgtvDI+kFASgAT0J6yrdCJeYho7jsA5w5ZV8zq37lYejJfiG0\\/n8BjB7AvuCbTyK9v0mpxkKPuJSKd7jE2HYjLxdcbJaRDffMLb+eNZVd6i5dXkStTDjyQr7GoO38J8TCb\\/GND+Ye\\/frbMwDt2vvxA1ApQVQHiUvW4cLuMCBeHrpg5RbLpdxsubs0kJnRSxqJg87lyd3MGFDBiPOo8zhAgAHOCYi3xLST\\/At3+ZV8vPcvjH\\/AAuYNyvIu37YmY6TgIKrPmXl9JhPlC94o7Y5VmIM+5CXQLfrNhfdNH9T1VRb8ytNsHtxUsAV4YzBaQDWJSUTikopcoC3fSsMefUpSW1PRf5j4uO7D+oQAlZaSGs3vwesbUBT1nbn4yRotJ8EN9vrFaPxP2RE2Il+kKjHlrl8y3bUaonhE1h9YiLBJIh1jw6lz1Po1lh1YvbPzOYXX5UYv\\/ZpJc3ulKGBb4U22TeYMJebKG1K61rupfp3OI+KKA4+YeDbl9qXsPXD1VHdlhUb8Qc14t\\/ccS5qMj5VHNc2Lr0UlC0b\\/jQ1anLlpp0Z324955W1ROGLVCRcuYSrjCPtUKqi0aU+YhrN7B7NREKbQx+0ynnNWRQXZkc1LwMMrBUcVZETUuAG0h3l3FAf7mWFW237KI8xobFzyrTVXG\\/2mCV3NhHiy87Rscp1OgF8lwf7cq1b2RFRMDvqZUKsYdQU1ODCvimCCV35gdSIM7y0NOswkuFOTsX8\\/EZfwN8qIQILKOPzBY8OePUpKczMeKXxbUHwTJbWXc\\/cJ8eOHuMADpi6fiUS52qY5p4YJ2zh\\/wAwmz5PP4lFALlh6QnfZ6S1Xbgm0cDPAIZ9ZXuJqsNPLOZugOZL+Z6Ktw\\/DC0u5CepPCD+rlOfjM0amblOwcvrA\\/AeDZ1LhHyufbxK4Ire2ocNxuxcYTTYIbdwf8V\\/zMGdTIJF6No\\/UCPuLZggzMgtaJALGjtA2wztvy8+ko\\/z3S3cMeiv5TfVvJezDisF4wKPKZJP+1KNU3YxDylwcPKDn4SWfwARmyvbb\\/CcUHAj4f5jZ4NWuF7LlRJNIv1BySLLwrMFlYF09bmarQ3Ve0HZCaSw+0IZnDKwLOYgHtMlOwUJZKfBtf6lWBsYH0YMQY4LuMcavlGVs23bxD6UtsKHTNSLtHKLx1Mf22X8Ev\\/bRJnafuGwpiqZJerAHXESYo2z9dKlQSnpgdjXEXnyZUBLuayJgA1UyRZhF9PZP9\\/5KkBnmy4vuN5xS8B5nWKh3vjxKGpEV09QUt8OojwJ5DqHCGFOldTVVU4mvWI0Q+ge39TKKyrq9z+J3vl9PfhGw07QZ95h6LpUGzeZgRCpMwbibsTlmP1BWXH3KZiAzJBsyL8svi\\/DZ9YfqAlRo2LLZ4j9KojSLl1yeZgbFxB7V0BUWhsW1GGHcYIwHN88IyrgaF0LnDJ1LRWaUWRF2Hjrmz+oEJLTtkO09IMp0VzxKIY\\/4QyUba9Jbyhwi+J5hguHsePyy3GP+h4lEXwsxEq\\/iDp58zRe\\/M8OZXxPUzMwCtnf8QIfJwHt1MMKCvPgmGuJGBCECWFteor1BZN2bJULB6zcrsAqG6pinCPi\\/7jtOowhyC7Ijbu7GFXXEAt4ubXsMoHieIiah+xbRF32JjW0sHBgJIZEgxg+bumAburOu6MRB5GCI5j4suZ\\/2EvoLlw1bB1AJmMybskOZh7eDmM1sgXzHZoW6gi6SsNS+VBLDp5e0Wp+UaDFvzb0zLqwRLtKiqbN8PpKqG2PDAI1QfESshWWuE2HO5Qkbg17qjIlvUPVm8DlaFyuMddEwxB4XgeI1gWi4FzX8pt6rvcIB7QOQ8r16ym+FB+Y\\/HuWBeLPadaYDroE1SWB51ELkTkE9cEqliKdKfkmPbYLs3v0jF0UKmH0nmWlQMq70Uovwxoc4wnljCnxicHgOTo+ZXsHlpF2y9O0ZXqfL\\/iVNzQfX\\/XLPSaN+0vqPEqYDALWXs9MEC6xkfUhgwwnSFj8yD5Y47CW\\/I8Jlr5YCxsdKbzfmDp1qmA9P5l93fTh\\/M+pTfaV1qA41n9zgwRK1ExHccZqYtPB5mZNhHb\\/MwqoOq5f1BhZnR4juobaL1ojA4CQeq\\/5QxWvnZej3lq40xQj4ephDZjdRJZVxGKtX9CDxrcxYCEykXi7BW\\/uKAK9Br0f7js7FQ93jMwOCuKCDUcF3mGhW6woHO4aPphCtOh\\/KY9koKD4meebp9Ffuc8WLZPmE92Rosfid17cT6V\\/EYH1eceJlGdQVVbFXslfSr8eD5qXIpY2zwNyimQ0U6Pa5nA9jNc\\/mIgq4NdVhMLblBF+f5nrPjr1OI2SvM4f9zLQW2yP6S0IfAYhvEYEV2Gz\\/AILcHXUEjUC61thcYGuAGVY+QXH9M2t1zLv9J26PSN3ED9D6zjZgD6ZlF1KAu\\/Ee3UbcEHQeszGFmxuUA+iv8\\/c43OMmo2nGHljGj+sHplCFtLmlC5ykHKoMuHHolQ0Zsm3qcng8hici8kpmEVbzAR8PAHrKkqbBmF1br0QuW7yF4gnOtU3ff9pZk3\\/oQkPUy\\/MGw3dkPuQAvEDU1i8DGrDK0qOkxNGAPEyu+3pjMwVX8yHalgt7Ph7zMcOb6BiWe6bCSrfjLh\\/JHPT6HE3tHxLVqnDlDLpCkcSoBs57gYWvF+b1Z8L3tXrHKBAtg3\\/KasAx7RaIGqxicVGsy\\/kN0e+5dWJm5SDO0iOkmYHkiYFDGC1fQuAVDlLvqO6rLrquIOh7\\/CVSbJzzwRHgnS31LBY8GpXefDjMVFjTxLXGF3uftQiuG9jHMnQXMBw3P\\/MNo8Y8CV2i7dvlhm8QhzmMwb4vqC1UU0wjZ9PmbIcm6uc8SuRz4\\/Z3HxgLV4JjHgwp2urnE162fFY+UsZL6T4XGnZ5pY8\\/kgnzst9JjY2NjDTvJQV\\/764Vglgst\\/pWGUBXcXRLztOSUxRPhfvEBvKDo\\/uZErHiez0hRxNWzYiRN9DzD1aaF4CA3cv+ZlECywUU\\/FeI77J2f1TXxCvce+5BoQuYF05tmYeOZnbfEpKeC31blzeSKn5EoD0yLlBdFpljZSMZb1BLbqlRw0ruArQKMU1UAOCUotxlQb+17lMJ3pZiyCwq8\\/csS0HL0SVFgPRe0aL4yKnQ5CJo7q0yPcAhz46JhzfiMnTFptvOTnmX6lU+rphJchwu3uK7+kMb6+5VS+LhUGwv5SoaDdQaqPemZsTz\\/BPARr9EYf4W4dvrGg7mxR+IoteslV6xOn0xlgNluZk+LdHTqXcwLKU1xLZ8i5fMp5SuR8kq2cVfYfSZoE5iJ83zHmXvC1QSo9u0ixfc5MoAWu4b9Er7iQarx7RkjQoNgIarDVkdw6ApYkbZyujx5hMSwGiUtxKFmZCek6oXVXhh\\/GqGF0CpeW1ZCB4ID+3xBoS1QmYLAaj0scB84BU8nMFPpK3W9B7HccZTFp69YH5FeL\\/z6S3xQq78TSerG2fUgKQsl0pYNCjc+H2mGT2u\\/lZg2qbYphIenkmSWt4YQEvQl+6pQ5tWMsfW7auV9bh2AJchmnhsNjmWo5B2dzBORvLMrazJYJB5dV4YcRm35Jllrf5Q8nqnNcQoAq4AQFQDAlSxXtGfAgsBKBolCG4tqDyFaEJSihh7VKY4Zi11f72S+lbB6FEElB63DwpGgjAqcGRje0eN8o5bLuz59P1OK\\/D59hYh9bFC+m8yq1BBs6Qprc+UMq5wAitjmLSEgmE+sK4CoNeoKcAfl8mYQofnfyP4lgNR5sR94zfo1JWtQ4G44Krm+\\/aPZt7q\\/UVC8\\/Jvj2l7qYAg+8rnJ1ARrATrX7wYS0wMg9xistpAvHVS82j3aIriIW4l7YE1BzOjwQGwaCYIpTM47px+oJXPhiFQdqGPaZa+RMdtPUlbljE65PxMR4orVjLJE8B8agFm4Fpa6nVCHrpKBsi3BXo9NfqU5MqTWLld7OXdQmIk2vzHyDuNjbPkmiHu47YRTAU8k2E10xcz1IcoU0XfhlIAf5YlEVRap4EeY2wInWGBWr0Q+NPIyitV23E6SliEt2Bwm3nEua6yqGXjlMH7IKgtrT\\/fiWfLW2VG6V+ggkrlBFvJ+E\\/K3HmBUVsa3DU0y1zAGY1zeZZbonMUfSUS4pE1uVekFzi8v4JhmzhnzKqxa1iZG3LnUKX7sOF2X3D69KtL0R+UZ\\/ULRzuS\\/LFlAiyyDiotV3eLEAfiEA4hLJm5QCmbgKHCXMplF2\\/5RfQliKpfe3zK6O3Y9HcNICHrFktLLFjTxtxrBAzJ+NvEMHW5coHYiMfg5r+kY0PTYmw42dsTKQimLp7NzMPpMiwzNjhHAH3l2QFFpiXzHqnEl\\/TI31iYBSkVHRQKhasOEPlCWLr0ma1eYM53ywXKnAEv9wPEpFd3U0tFdM5QqaDpP\\/\\/aAAwDAQACAAMAAAAQQbwOKyg4++mceQzwUMQ0iKj3CJc2IUgAMq0IFhmEK\\/BXD8xysZaJK9coFjQSYKKH5EnpqGPLnSK\\/wwIfpv8AxkuieL\\/O6Cb0TBIgkevBj0DP75qwaY9XDpdWD49uvhnJCr1XE4gdfYHceFuborIG6tvSOV5JiEw9nGq1ygznPCCmCk9ocvbRGQZM1j9KeX\\/+44FGtu7F2BzGfLGK4xA\\/eBjpJ9\\/atVIRrC5JsoTEHNeQ8LaeCPWoDh+UJH6iY50+H6Cl\\/wCC8DyTgk0+klh1aX54cjfoIgOjaFdGKJN07lJeaM2BPCJWlI6i84aBaJYatIEIMHchwJlKadMxyc+pT4ACYO1sV2BVispe+lOG6iRMBanCoJTsF46jPinq0KJNvzRjWT\\/HHtFSQ5\\/f4kd\\/CpRnQesLC+ycP8x\\/253LHC9IeN5ZmDFQ7OCshj8UwRULMLY1OGHlNPGDRDqjH4mF5Wb0KFWvm6RSvieN\\/hH\\/xAAnEQEBAQACAwABAwMFAAAAAAABABEhMRBBUWEgcYGRwfAwQKGx0f\\/aAAgBAwEBPxB7\\/wBz2\\/Rvnf8ARxsbHxoRF588wRkwN8JwsfLCwuIyTVxZZBZZ4Ne4hw822w22\\/qPOw3VysIGbLzxa222w22222+Q8jYbNIU8RIngl9sI97\\/du+Nh8bbbb4NQ2\\/o+w5L7tvpXmCA6uqwA+fX5lsVYnZLZvPydKZasbXrw9SyP0p7nviTifhAZyEmamjqPbf3hzlrB5JsGXEEUeEhDALel8a5kdoY8TOnMeDcSxq2bGBhHE82MyT1yOa9oXHoknLC5YoeFs1kZK3JrbvUHjk1jAzzvkDULWI3qGsG2jkYbBdYM3hBeHU5mEAdR5Ch35PG5BzfAJ5QGeOm+7FnLFDbgpx6goZJzPfk8h7TO9ll3ymmXVB62Fcpg04yOenyB2gvGTwhXAGT4bQUqTPBeo68jwE5+J3HL8x4yHy5Ltf6H2S9b1EWdp539LEF9XqhoNj9J92e\\/HHuETz1b5sIm3KFzM3bAgsC1kKnTNsbj5BSek4miGwH8zRyr5FRA\\/a0D\\/AMLvrPDbNSNcgFk7RmGJAGJbsk6IODuy7WBiaxJzx\\/EI4V6JcryHu4N\\/guhLhAYP72H3qHeXoCwDlYt6gIEowFk4RjAHJCdTLnhn3+yFre0mPOxOeCaUCdbEjz8y3FvR\\/f8AwtefY9\\/9RzJ+Tr+tqTuVwHT5BRzH+c3pyKWvDgwBfFrax4S4lgMs4N6LsJW5sPVkmJnKqg+\\/zFUdP73Mp7cyKaanRddx9H\\/sJa8seZ9kALKZBDbn4KuCTKMDoneH3dNLAPc6S+xkJyQQJyRlRzxHHqPfTOZkDwEpPELB1nybD77tguCXgSjm7Q+CUEftpzFc7K4hnRGaQbBAJjLOrhWAsjhjbR2YQHmz8Af6Rh7FsepfpwXTeAyB9kOpc6vshbfM9TqS8pk4OGF5e5byyDzwn2SNrriwOXFhOJHekk5O4Nd0uTa2ogNh6tS8yDh7jjqZtw2ABAXZ9tpc7a5mTwbtZOb8rp0K6Zd84tTwG7U8dgPMAeJKAi3swqDW07zJDIaXAwi8REHF2v\\/EACURAQEBAAICAgIDAAMBAAAAAAEAESExEEFRYSBxgaGxweHw8f\\/aAAgBAgEBPxBOdtnhHnw+SY\\/J8v4b0kU4i9DPAfk\\/jvkfIV+iA4QvA1v3mofLadyf5u7GULi47DfaS5XAPKOQ92bBln1IfVifAB8WHxcWHx+eS2Xdzim7I3pRwWz45sybNizYs2Pw2WeGYP19WXFp\\/wDfGgDHcgORiP4ueMsssssssskmB7RMomNkrOQ4jfLdc27OITgQHplYA4I2FsWnuHbJ\\/JbB9R2MByZfDw0RbKLtz6I1y2SUbf2C2Z3l+oM1GNtRbBrreg+EdLPw5Js9CeLlgYZNvy4Hu0B0+7ZxEdgf5leFmvEL2hph4OLZ4lt2Hw9WPfgN\\/B9+BOzPFTnDZH7mCyULS4JYx5Y3Z178AiAjQ8AzyHNnW9w5tmkgOJlw49e\\/+5RlzaE4w+TzfAy04CDnP4L6u0hkMwf8JMPI46RwofeXRBc9Rhw359y5ckA7jf1IbANS7JPuCzhLkty3b3Yr5OLdu77hEa0fULCi+Y4+A059wHvO7m\\/MS6+1kQXsY4JJ9LZp0bMM8BFSGXJ3N1kxxhGQeMjaO418+owzYtC8fktruH7iAYkov\\/EG1o+ScQMpML7C9wIHO0ubtpWHZjwXMDO8oZ7pP74kCfE4C6\\/LcYtu\\/wBtpnP5P+bDDtuH+XID7urkxH1zcBqfqbiZz16iMNPq5N4LHvu5amV6uFtOZw2bwz1tsaDM\\/wDbN94OZLdMiMjD0MAcUBe6A+Pu3xD1HVn3vP8AmQH8K6t7QXfTJnOEfTAR0HXo\\/V6C5LSXbI2SawbO7jnLTv6jOkdxHyksjBynRPv7LZg\\/rzMaO3fUjZ0yY04U\\/vye2RYcFlOiSTj4PXI5iYC4Nwl2tsceo045tw1CmsHIwLlksM34nsQPz3ckuFgB+9jM5Cc0DsfEsj6L5oVJvZOZOZdMt9QNrgRIyLmkzyynic8xBMpqWR2COkJl0LAWSYe2Z32A+0mTZbcwjGe5\\/q0IWcQjz2SGJcBDXmGJXG83Se271ApYvTBuixIcSxF1CeoepzKA2jGZEtr78eW2dnHu+FA8SJNsfVmriLBB6T4kGckYnfJWXB0g23iWGxWbYkuZlwtYc1vgUjNdWbjy7dpd23F4ikVb5X\\/\\/xAAmEAEAAgICAgICAwEBAQAAAAABABEhMUFRYXGBkaGxwdHw4fEQ\\/9oACAEBAAE\\/EC0BH5VxFMbMcBFDdkGwl63fceHVLDklC4BbEPUczazNxTFnMm4D5O1JAs7GLsgbQvKMDqsfMUVt9olQrwJE6PwIqafKRpWCv83EzS58vzAgiovV7bAqr\\/aIIGKe3+oSzIN3FiodVnX1EUdN3qZYlaqOH\\/A8xNBPUB1lis1BayabJaAHS3E0Fr2mZQlwIoqObZiZFiUxgYWOiG65zmYcsPMNtc43Kb+QQSsU7+2WsX+I6sUrWtRZEwbau4AQ4B\\/MMYDsMNG30hrdHZFUhMJVU+YnAMLbVgYoB2kCJp5QlLbHBSRkvoxuYDV44gUlOBMQQwVdoXNG3\\/hDRVPLlilnCCaO1ZxBkNmKCMYEmFYB19x\\/ETNE4YWgM3V\\/+WItdVsk7F8ydJjXcP8A3SsArXm39xPBHmn9wKqmtYH+JdSrwH9Srm0ibFZndRGAiAJB5VCvJ8stVcPX\\/Ygih6mOoNeI5az1LboyVomDID4ghXUvqU+Yo5UilsuMSnDZc+YDRT3cKEbULjUiKaYgNW8q+I9gnJaLtHjCTXTvGwmHCB4b\\/UALVHt\\/UOgHt\\/URxtvDcfUNGS5ufxC7VyD\\/AKl7TKu6alDh34IGmKq0f3Cohbmg\\/ub1hjR\\/cKKPxVKDEN7LgWm+RiFaLnNH+oZjyLz+ogQJxbB4+\\/FFBebpxBHA\\/cLzkeAYIMJ3USqYeBULKDoYEMnwUtRl5gTKvcUsrPccN3ebUqLZgzmXn4S\\/uKCir2v9zxn5jhKoZwqCiyYFONa484gqlrdeDVPmFYWxSVqNvQBH5i1vigXZBt4\\/qOVC3i7e35hUpu7leA0OnKZk7QhQ5lgS1Kq\\/xmCszdBc\\/mUWDEo\\/6punlSP7mC6N0MpvpRMml\\/kstUEmWpLUWsUfxBAQN6kqBZNUtBAFatSq9VZaRSrUeLEN6U3Qscoh5IiAOfCUcVwWy8gDdTfTqOUOoB04iS9CqS2x+qUDRuQj+EvR\\/mFqoDvN\\/MQ7G8CuXBa\\/54l5RNh\\/zCZV5W\\/8jhKcmcuzJmdSaFlhbwtMT0HJMAsTucIMCG5jYoDFZU\\/wzQFEjm4iBvXAWloIQWbyfiou4Nf+EhwIRyg4K2Fes37j1Qm7tf5hUjWyRVBW1hl+4qy6rw5PhzAqDkQFS9bRQtqsSvzMsGMUD9xhMK0IvkhfACmNteMuY7BGxtOHzLWKHGQ0lzYYBmnmVWSXUqeG2MKk8ATUTGgajKFVUg5iV61rEfUFiltJr9TKEmKCnqKxWVgA\\/qYCrlQW\\/iIuMgdsdrFaS0eFSzNuXUGdisxSlEpXLkMi8rH2GY7uUPajV2TAw+CUzXvzL5yYI2It0CyxbbOwlhRFjzXJz4g0nvQ1w9qv6gDA2mcFqcKKc7hzsL7mZcDODnlYvKwXk1v7i2e4pMYl4SwlCAuGD\\/cEFqbD+6ISIchn8wMD6H7YUMg6o59zdLrn+6JMVbH6GGxisDZk8xCq7Noz4idCrkJcB0CSjEJeQgKBXM1HuK6PGeImsAa8opUBu9H6lMO2Q1m+odHQ27PxAB4zFX6iGkYANHaQ3SZss36uGsweboHc1tmbehse2BABcf7Ivaqv+MSioRTCPDqL2ZByWwTSBjR\\/MCy9o3h+YASm87X8w4ZgQfzDK0TGMrVGVdoP4i2GjmTBDBe0Z9JErhXxPHUhQe9eblriiTlYG+bqICGkrQN3jm2iARQi2202fMpolqRw0rcPXmZbIgANtOsVjNxA6XfjGmuGqYRSVS0l0F6txGsTGEAlpdqXUp1ANow1q41uYooJfTxC6KiN5OXmKhqvhERRbABUvbVt6d4f6lorpnMz1QqK7zGaTTfMWm1Qg3LvL7lo4YaAlhw8LeYe4oVQePfByIQQgq6Ov+JTFBdEx56PAfqKvzV1MA4PTmYzvcEFtPczoRcaZMGj3EVtlJjQ2qONrSLDAA21\\/wDECluBBIPGObmIrBliqhlKAOSrCEAGCBFUlQ+wulkEiqq2ruYegLRiG\\/Rd+YkLADkwhitHwzE9UPoo1KbMVbqFGpxRCNwrVjW1VEu02WnwDQGm1pfiN\\/IidMAbKvGODDQAYeHcAuOEKszNceYVMfmBwbr8QzD\\/AIoVzwJYHlB0mv4Qkq5JoC0\\/mHRSUo0Log4ZzFqpp\\/CYPgv6ZTJlUPT4fiUJCVHG3hDo7RqGtKGrDBLrxij8H9wxPL8QxQZtmImFCDDQg3Kq6MTStoRBRsECjG0lgGqSagYKM0hJcJbJQgQjwdfYd9HmL4ooA3fdIrzGGDAmxVbQ16GL0ZplfduLGqe7KlTk9hADa1QjjCdkQWszFKGkDbL5wRas8xWAjIhizYLXRT3KVA5q0PI\\/uD3HJ\\/KKvX\\/tOvwSxj2\\/qO\\/05S18iOfkX+ZofX6EF5hLNsQdByP4gLYYD9QP9rMOsMh\\/nxM47P4h6oS0HK5y00lRA0So9mBhwz+YC7gFQ9XL9Ezg3ZmBxma8ZofqEAHRBSGz9QCg2EF9pCprCv4l3cn6h01hbg1cmY9UEbzQUNKvfBnqBNBhM+CvgceIPAgizLx8DGYRwYNAUVlk10q9UVvKWtrWV6Wd0W71MdN9Zaud6KXdyhBVhf3vZfJZjMPDDtRYY2AP1CUHBBSpi5KDZuBdTf8AUFVRj+LKzsxugdjuIfR\\/CBd5JoEY8d\\/LjjuUH4F8cdcQNpS\\/2Q8hY\\/zzH8FlOFwTYBGidAYNFMj9MFGNJiUQ4f3\\/AFDKh1KHb\\/GpAYnNKSTgGG4tl+YVAf5cMoWqT4IZh8zF0VS\\/k\\/uYmjAsBpaS+wh0KyC\\/mC0rT\\/ZBFM5zbHavRfCleqiA7SBhGy3FiXqN\\/wAdiapoM6fxDmVvGuZWG1ivUqrUc8hDHdNPmDsU11o1\\/MPQ2plGpOPKCZFiWNZ\\/xuFLzawRNEqneM3BxSkTkZgCva9amcpKxE3Q4fpmBg3OG4JTR3\\/EPUaS3UGvq4gLjKVx88RiuQInlgX5u1+5Y4834YnbhQGrklGDYygFdShVzfymRgi+pyPwv8xrh5xmLUNhm8aXBcdEFqtXjxKgpzUxj2+pYdcLGRoof5oILasWr7hNaYt9s200R+42wax+VDmVoqWLWAPtf9QiGDbHtiH5ToYNnOOIE7swb2Bu1byEdqAgUWVkmFcleSX2GBgUPGxjqU9wIl0zDFCHsBKC8Z5YusLQbGms5IK60+hbL0r9VPLl35kPa5er9QGBUN89BV8w8q9olem\\/Fw6xiW\\/MTWrDTfofnEGGr273C6+YaJWyKI6Jo+P4RqPzCMJLQPBKeSEDrjlx5gKQhYDxlKbeH3KcjZtl2Ug6QKwmMoo5BrkmTH+51N3pmER8RC2CYQJEH0gGbOCAkpEVTZLe4GLT7I3b\\/lj4QP5i2AoH8zS8j9y\\/pK\\/UBVGRFJngnxp\\/B\\/cQ5BpDJ4JmjGwKY6N4N8ygJtamA5YCkXO4Dy3QtwXBcd6RQIZLPtAu6AUWdLzezliJjDLbc\\/8AccBuq7WIR7t4col\\/DA8ZQl82QwShu2vdsyXgDSXB4ONnqUoS3OHjVkvYYdMHCwvjR9kNC2lWPzMn8H7hhfL9z6B\\/UFfB\\/EGDof8A40X+zLj8\\/oQfUJYKA+BhMB1+yJaE+bZ+ggdef2nojfiAu1ivmAOCo+lcg7PZKjZ2DIFmhb7lQ0my3m4l1f6WD0B+7gz+Ysf\\/AARVr8\\/4noS\\/UAAwWgruJ9P\\/ACartf1\\/EMu46wbA+SXaABlgQbhWjPCyxlhZyIaUz8ytcswl7FXekT68tLdWPWfcdXELliWf3GPYoUBC8VVVb2Mu1oUOdhl0UPkmaYpQYF2Kyl9zPu4EK+DG4+yAXrsthBawK5QcF3f4gev40xeTp7dQCfLaKuswcW5Zb5Zb8EfGChsd12dYaRli4Di3gHk8LKm0M2DbZj4gGSGDRi8IIGrnSmX2fQgqqJbbYE5A+oiV6R0fmE5wVwghmYhaCoGS92uJW6Ejn2Swc1LK4dxEusyZshsWdM28IYrdXbDitwzXiWgpcvpjbM3uB8usfFmvkX2sMidB+5W\\/sH0TtrIw+9IWXtX8wWH\\/AKCwK8T+Wa\\/dvvP8wlFMl\\/eYBpT24oW+r+5kgLjVfpSIejQY1i\\/mF4UqNQF7dHuGqeANotFZBlSoDGLYoT01neIuTguNPlv5+I5vB2U+OIszb8ITsCv2M\\/eIZKagQzRNcxMT+BW3rMXPhOzIVacJdVDYBot772t8NQKKAvfoIEI\\/6FGZvushTrEvJlQaklKXKx1ZLh9wYiCjM6TIeahAMpBaHumy4BLtEaqtG+D4i\\/hShuUAw6zLNiRRVlymd+YtWoadz0s5vFuT+Yq7AHKaax8kBcXAyjKQaeXUMABz303EC1AB24auIxK4a0HpZYZeE7NFlTBtFhTJAxFoW+6Y7kD4hOTNZr4iEucLUQD8TkWW\\/mE9UrbK2tDleog6BQG1QTKrPRLGqathh8o0+Ii8cFvxf7hi\\/wDUZTGgXQH4gBL6BvE\\/ZKJ0lAVuQptwbCEL0yW10Bf6JaarpoZv5eMVWYjdL0E130uV37qK6TUu09m6vT32rDd0UMmYaMh7VEhKe0I2gVXtfcDOhCrBYeTLosVKla0ktFhfIrMi9ahnRVqHTZFhQKIhHEMWOizOFirDF3GJFsrfTAp4jUZ9J0F5MT8VL5IpFBM9FQl46KtrjkccBEv+4cg7BnTzL1ccmy82jniplaSEQlt+2mdNQAKBW2nRbEIJFiKYqtUrgyPcTWFDLvsbKcJxK41Co5C9M\\/mfGjXfm2eO4FA+KMypcqoS6GkHvDyb8jpJbH0hZYUHDs+yPMi1wWZaWuUjx2NKK2v8S49km\\/Fy5QoFh0kfXsqUFi0bxenMStnpsudUsddCUSVVpuLwa9LnJF2urwdyzUExzpTvVdWwr40ZwJMKvLWIZAib8J747h8wAeG8R4uEr4cRu3v\\/AM\\/iCppkw9crr+IsNTeQY12bQ98eEBaxSvGT+5TRxJSLjQcbfiVwJpHPV9+8wuHyjxAVgAwZZR7jcSsstK9cSuA4jR4uqL1gq0loeo9X7zSmvqIlliA7YcQ3NXxYCWjjNtQ5ocKKTydJHMqYUge8dalEvXHIH+pT1LaLw8Ct30xNchQp6Kx5YN2GShc5BA4yXuUz1A9DWBX+ZtUjJgbbusuJWj2RbNaLrt4i5S3ahd0MjwvjUy08QeHBtVZW8ytmwwR8YvzJZDlVloKhZcsdG4ymrjrXhwqvUuGyjMd5RX4j7PQbfeUoNujDxE6FZLfVxqhWlCjQDtqNIAUTYt1zeCNiWoKFr0de6lFGVZ7T\\/UUbZLt\\/4SnlFWoc03VmiseQ6iZ2otowr3jUHYSlvk+Lphl3F48A2Vw4tYL083Ze8nxH5Kx4fl2\\/MHOiQN1nVPG9ahaU0aQjpmD5IxUCWJ8tRaCbUP0vbNYx4lCnnmlcvbNatla6CUdiotThwPdGfKKJYviio1VqXfEbDRsLXcK7PmwcMOP9BD0Vh97b7IgDK4Yx\\/f8ARDVBs3ajp4Zd9nUGBS0eTi34lWeQ8eIjYo7heHtlhlCli5f6isnDCuNHLAfa1Ew3mUPCFFa01HaK+LvlybgMJul0S9HFUSHsFjk3IqfYtwjvi6T0huUHvRpMQN6tpfHUqId5K8MPecwYta7PolnkDBYPBXn6h7QlGPWNX8N9ROgg0MzXFtOfD8R7gWv4BfTFme0oIay8s4YZLSjKHnhfRrGZZyGgrT+TwI8AHIbjdKh5aNhX0fzHVK0AsKYuuZWAl2cfQxepkC3eLbscBjPKkRjMJrQdqKW9VVS1BGuyhuGCES0cj6efXuVLoEp4wbPdxKxEcK3grBFoCrsqVoF37xCNM0KwCJLOpeQ5T\\/0gQwnUqpzLDFYp5gOIxZy3YLcVt7S\\/nuxMW0s1ZV0HyzQIiShQDHBnLscBRKNUYUzxwPXGiJ527av4\\/PzCAPnCnCxUIkZEgorojSQAeiyvEFUGBXYwIvhwQu0rBqqxjcNDJBcDizS\\/6pjtBtFsbw2HjXcvk26CXBE+ceSNQ8ZdBusLi7qKwcFm98iinph4FRaow2oTxleBmCU3x7uieYoAhWIG8b\\/cs7Qz5QoVV86DcxkYR5mqO\\/cMd5wS1Z4t+4hymu0bomYEyF\\/wXLHdwXL6GIHU2r6O14+IsAYKdOk\\/lnH3j7HkTk\\/MJSgc7PWJgGRzib1DogtB1ijzEbkhWdzQ2rno+5kBwOw0W2VOzzDT0aJqsuKvthdcRcKhjxNWFUGjN0EVwa1nOQo\\/KFDsY3adqvKtw8kmUN6AfmmBQwliymW77uuIj0SO60XT5PzElVbyjOvEo12sOdcf2gT\\/AHNXWyXlAQ4locsGj7Ng8h8xyYLCuZuDhowXe6hZ\\/S1eylQn4it9i9udJ43GDEXJiwx0ZhZngALrkgIN2YS9qzo08j5mVik25qvEERvY29uhxbvuoCjeATA0pJ6dIjm7a3O3tAR0xzAEaPgB9lmYqrqrqynr\\/ZhTAglTYTJhW6g5BZFHXIN5TXKM9Tza3PHEWw6NGzujXzGmWByIKy9ZHz4gKHL74K0Y5ijRttpsO8zXGyFo8ezNKqyrR9+EBgGNz58xC5R7MTGMqZHHzEUVQor47lqkaGF67jo\\/U4PRnS\\/nqLtje5ls22CkOEj56a0E85j0FRcVmfhYcPqplXtNB8KPxLxK50EYC\\/lyGtalTyib9mim19QGYKuXQClKWmW83L7gCUlSr4QBz3DmQHtIYeGyHTpOJYGr1WrPp10yoXRAB5UAfNX5hpN5tiNN4YzIiLaC3sGJn8rfku83b958R+DcgZx91GE0qA2OA7Y2ArFeV1GgKFvZVh7iCW8NKyfJ+ogWtgDao48pWkw+4SkBBNJdq+K4gBAKVasfKBthk9Q0NQWkbVVayPUKGDqoUrINnItxTKH2YpB5o+EVuewYvLFH3FkLq0VXPXmAm8cX5XOwq65lE5BtKg\\/RGMDPiX8ygIrbMvDWpUBkQxL8FlLQQPif4IumdcB7QabfWuSJzQWLQouVuVZpMMSS9MZ0VCa6nxDH9Ty9w6RvN\\/RVjyMsSbBmHoUfNxd75Ld8y4qpemX5mQBOqmlGoZZXQ4IbrHHD7leKbFXBifVOR27q1+4PWJALMxeb3NPn7RbS2\\/FeJvDXSin\\/AHiJIO9mn5Qi5aAAB\\/S\\/uJQgWMwPEyphm6tYwKXCib9TNhak7fESokttrko78xIJCiKyZWcOIDANYW2gceI0GJLwBQEfakUuDDDod3cQyMkXxxATbAvCr2R5+DA\\/gc+iSzOsnAOFwH7gpKgthFG7pp2TiBTOB1bZsEew1iVQPQ7sczL3e+QY1RDhWNtXAA76mC\\/hbL6RNWPMbmYN0L5Qb1Vl\\/VNwoMnYS6HEtc1pUM03s1wQ2tyblVaFYt06jYSaxYaWGY1DjQAHeILDR7ewcRex9xisII33kpcvNRoujaIr4UQoWsNB+QhQ+oKaLoLy0MTdVGpOL88fEcYuDUGtEDsDhCwwtEHy\\/wAS0hVFgW2UfZg5SeQOVDL+VQx+I4BMim0xkU4j9yChS9W8tVBYGzN8EWBQ1qv6z+YyrWl3b95iOntIXpzUMohaLPCbvxL1jQChlbzzAFXbtXMNHToOGO2RIHD5lbCy3S9O3K\\/EcVZbvuHDtrB2xKYMm5YZ8CXGIwqAIVJdJw\\/1DfrdD89rfmWyJQe8LItnkKhgEurx7i22hDwg2Ub82w6\\/sr5xFjvzEKO++xxcnHPxLQ4CWpyL8Ey46Nzat2yFutHM3R6hLOBsL0YoNS7LcwTduNBi4ClV3BjNCEizDzlQHIqxHrUswVhWO9B3A+AVhZKQ0sSAgPaO15ovJ6mXTiPI0wxbLdDGPgxIHdAz8IJmyWH7dZnnrua+ZRVlRiA5Sg83KYFaKC\\/bK2m4wReFaa+KxEK\\/YLdR1LmVXqBQb8h1iYQReKstnPO2UANw9QDD4v5hIOROX\\/fqPLQ006\\/AjFA0O30qCDwbzdx8yZHPs0\\/Mooonwu7HhvMBViWB0eZUhPdR2yjFt1DAX1Brj8v3DoReaJRNth3E9QmhF1O85RzFKXgLt8FRDhhLVF5PmiJaIC+5wlMnJfMd79gjQnD5R47h0s2JnCVYbrozmOgKM1Qw24+pdggR7Bx8MZI2hw2rPkd34Yb1+ImTi6ULdY4Fgka4WRHwc9kUkSUyA277gUdShAXYxDQhYHr\\/AJEEQG+i3efqIlBGUb+7hdOXiBELKiY8iWvFwDaW6HSGvvMGllosx2YP37l507e4Aayy5uEPCeXW8KeGitwbLscUADB38xx1c6X+SOUAvHEd2xoRtlkn3KdtNZg5WAezYMRYn0oJ\\/ErVz9Iifw\\/M68Pyq\\/zSsES1ultdR3qm14W9yoKjLxbxpQzbgtZ9P+QzkaQB6SwDYFfmOUGFZYs1jmO5OAirGfVlt\\/czGDa8B1L6dqDBbbFHxe7D+YkzqsomZV2mPRVY9X+o1Ba0VXyA8kygpu+iFKt+IlKI0J8LlfqN5fQE5pNA6BfLuILnSrii+y9RGwiNUvT+Ymt9YXsv7ZlGZQ\\/I\\/wAQuoJbqrID3ipv8UvYufmElM5CXQ5xNCiqxr7ZfYYFoCO5F8L9ocFs38hSfuUQCSZHBy17l4Pjsw2I7GAJwFQq6LauN3BCfaPyA07Jg3YK0Kc9ygToK+CBabIicSm7Gqg67DkpBY6tulyv4lbEuHtEIhYsW2r48H1L2OJgEuObIw3RZdrAyi15P1GtPIZumJrkQUUbgPseWGO6FyNHQ9THazI6jAaWOVd0ceYVdxENPU5MFTQX5prNS46yuAAY+oKUObS3H++ZdYUHUq1XoNvxFCqjzJcornNdS8\\/S1kfCq3kSZhLJQij3BpKkYdG955a2Fyqhs4m60Mt\\/U2z5fDeXc\\/s8QN9CaiUVl19mH0QCHCsrYu29kIqAZXdg4T3D3DSUMhvY5MalarNwq6Wwr\\/bl+ysmQ2ePUqbY7djuXISyZRAeC0BBSkKbMIJuCF1AZ1vUKp3ataemI41bonil1ONGQ92hxBqCy8m47yfUK626K9vP9fE3si7rp7gTAO12QDBo+0CiXeaZ+5ntBY1kikW7h6h3wJ2juXyFS+A7KiijdcVipUV1bgzzeYbaz3LBdWBkRxWL23uOcieoTJTAHdLQ3mtf6zYpiaVX6wP8\\/sjMFqV8MmecYgTuJQBTrAGaqs5YL9EUaLI4OVq\\/nvIwY6RtpeR28HUKFmoWnhkygmTS4gRuoakGjr35iNGbATgvI\\/MCFthtPaB+WYu5jvP1iXehaUr0tVmPhtSlHzaG3qUIgy0Cju4yasGr4VLPAepe+34yiH1C+FATT5oNfEZgsry17pMdfMt\\/UlMneeoQIryvKNiQUpzAUUzwrNssElkCXNZvwTU8aSz5gPoOF4etQFmtub8KZSK4B5NX8TPq0hQ0jacDw+5hGCkvGdPkSm\\/JGVCOtRFLV3mLexzHG993CzZ2cRQG2eBv2IBElHIWSCxEqqlVRxVflY2ro4D5mIfAqgAS3scrmDBnCjbnMsVgoQw6uMhgrGTcUaiE4Zz0V\\/W5SEe3rQ1q8Kd+IShV83zA6\\/EIt4gUYuWSsUCl2o43qK2MDc8O+Dvto7q3YSPCCZXjAD\\/ir19KoclKFB+58KaNiGEOxE7ZarRtsiEGKAFWneS\\/qFuBfo8m1nyQuUbor2Y4XMcZD\\/ABUgzTYFCXZ4Ejt+k0EputtX5l4dbINLjaFUWr8gUXAY92Uf48w5402v0o\\/LC7+vTQmEtRX8Usjgwgom68ZfqHatpLUe4MJqQGTzFuAzxLSwzS01naWgUwtH4YaYxYqkBTGXXGyvY0nqCpM21Xc8ABthQebgDytNOj7h7ONU+AbPzD0pUDC8iwed+opZEuhGCKiIkWw3XtTFblq3TIPCPMU2dogELTtss4bDcUFRMHpdwM2TOBbOs2rmKkoMvD1AagZMZsOZ0vM\\/KDJo3DpwNQVAQ4Lze7gVg8PEriskJgwBVxWbSl+0V0q08R1UM1SsKBv5aCFwuNqGDOxyvti1IvED0HQG4eWhSJHCfDWcQlsSq8OAqyHXFl7hDsIlh4S6AzqPtVtXM50z9QACKgc9eERgTxB5Ql+pex5YVetX2R7nFgDimuVFfBA4I3lQW\\/19wrGlssezX3BWGcRau8XJ9QYOaP+xFWxfR+IyYO6LXwlEiNceAj4p+YpXEWUWxQctROSUrpfzUZAtL2T5l18zmqMEBR1WS\\/E2gExmq+I6aiBZy8MOuauVg9uPMbW+DkbVtui49ppVbvxuXJdwGzlwv8RasHoERMV2ofTZG00Bw8h59xmxUNKbveQ+IIu0BqhwDP3fuIAw4or+04p7i97bUySC20Wh1g5jnWJparfruOCqEQl1KQlXEyDsCU64MTILurr8wNdywitolvv3KluWAtyqrEHTKorRTujgixr6XwjtO47saAJejoeYrhRKl8hUxWhiqXeFywe7vBAbiseVmw9XeYuy1ak\\/Bv7zPG36fipp8yhe\\/CvdBLA1ajVGVpsrwME9GNewtU8Z1KM5e9Q1Snx3LoNAtx2LBJX8gsykDXxzK\\/vuxg2aXxBiqr7JV\\/Zh+5xqS7\\/Ff5mpfti6Kc+cwnXLiB0q9l8xZ2A4sNX5iABla43SGJu8Cq7Dqu3E845dvVj6LhJ5JU5MNeoaGgKbV5Owq+NRnSZc3aLRfT+4VcIAs5WEKbJp9iYBeij+7lqIuB\\/Kz9QG0Bqw\\/qFgA8V\\/NEixBZQeDogxxZRwdfQR7h8zLHQQAQVateEc\\/mAEzgD7Yfm03rX1vEswrED05QbHKTLzdmgIMIi6DYls1EDRZw\\/cBs2TY8TVgfmWddFu4wvxf13ANVjmCqc4xbGMLmgpkL1dRIKUnNqKbAY1NeryrVxQm6bBfltFUIU5X6wwcZJSrfjmDC3gp+IpitcFrw5U\\/UDUNjzaTt5lMLC4GQ\\/wB5gQFvBLLCtrRHehkv0BhfYS+\\/yqxzSmdBnMsYXKj7V2HubYvHc7tBfcy7XZIfFhFznKERyCV4jIMLLlwwvTxUNNsBczq3Rv6iQD562ObGauUqmGtto0kKhcXbLWFY5WsA\\/UIFjxncB21I0I1jyXFzAOWVUOPz8QhL08VlgBXdwdErGiWtzwEpsGKMIC\\/hwZsda2vP1UzMy5qypbN2K\\/MC4gO13A\\/6iH3dplWx3gxSDkoB4Rw0ONVHys2GPJy\\/MvwvEBB06fi5gY0YIkXhAGNYuPtXVIiLo9YfqUaui7Bza\\/uVM8MrGwjdQ8CvfH7lHZ0lR9O4O37jhnls+49\\/mxfp\\/cUwT7C0VKELq7qyLK9LC\\/YSYqHGub1ZKMvgNxytRgb5ltiuSuFghy1XHUchzqgbpelAcQVsEHguLUuvFEQjpMOxrGbrUCbQ0OZtxyPTLirKHS+7p7JfAl+DX3XhYeziuDCi9qt7gyk+y1lyeajmCgLCW8HZbAtntBBPhhQPz0nlZXRBZT0Dgv5gKj3yOVgW9F0RYAntmeTRhYc1MQA4bwVl899zCjAN2ryzE7rQRP7lbodX3+4c9OCxMorZBYw1HCHHRcdeA+RsH3+Yg1QoHrX4l9DuBaCKcehrOfBjGVG0uR4oOxW+yTPs2Hgy+4WMKoIaPoISWyEAuwu7w+7hAEdGS+OJU7ImjpvINWm+KzQMjMaHQDgiF0UNEqqVx3cZDeVzehS+4DRDSsekH8iNWcCVT3kp7CGzn6OtOWMh+mcq+bP7lQbAsT6By\\/DN6CCwDeCs8c1N\\/FQK0A2XbQysRPJSDHK3ULERwtFGK\\/zKMIAhJycZ8MQxkgFL4\\/CIrA+jbmqtp9pEbPGwWtBLfiXrVcJwNq+o5cygLLTZWiiIsINQFZurMYDVsxr0FLK4g5pheQwD+FlC+V1UIFowx4DdQBecwLdAbfLT1Ai2xRqLqmxrgFixmnJSgylA22t1iafKKC2gRpe5uq8LJTN88xg9XFyogAALLr5hiKsgy5fUuXJaCAL5dh8yuosRZQovcAwRmFW1p+n4mJxqIuMMyIQhgq37fmgWPRiF3LW3Qx\\/sxGqaVS456Dl51B846MLi18LX5QusCQhl7G8cwNE5jt0h3ZBEeVM2mu+bgWlYQwAwy+PDBvd0JbpvFf4gNCVpZ4N\\/LAuoC9gv+CpZs6wEVLbu1A+q7jdgt28tlOd4Y3Goh+3E8Wk41Qaugon0StoB+00oA0Ge4bAlTirPnnyzATRVrkWcW4zqAXbi7Wq7iotEEBeKrGu4zuKMLrocy5bSjsYx7W11RKq6GTTaaymIvzBeAhQYytbCMj6KXod0kVmilpC0g9pNsI61Cx+0+pZMBgw6u+4+BsTF8lvSU1K9h0DQ9Oz4mcdBs6U+tHuEGoFbdB44nA0kngj4YscuQNaUUkbMqsuPSBbxYLDqspFC0VsFMZHNjrZAeQFYGq8+ZlBcFq7zn9R4ltifbaLFK2w9EUPDUMh0Q4qr7uXOYQpAaVMUcsss124w1fzLnzqiRyy8GXnRKh3Z0T7eB+XBEGK99NAGfwIqQqpHJ6A57b4gkgwpxGS8ssGfZBW3bcKpZkoj380Ar8JR8l0SnKLQRKKcInhv8y6tbhUA4OQ6Pky7uriADHsbzLiKTtCsK3ccC0FBPcsKQ3V+NwEnC0BfNFe0g7iREUMgVy56HxHy0nSreEDj8RMl1Gir1Vby8wvUGC9l4\\/UaClkPwqV+ZdmrFDryWtJcIaQVawZZWOdepYqtAAXAGj3CwqNANDK20AGbgNwk1kLdfVPiYi+y4FT+36EZE1ApCmunG5WpghFFAYzwE66w21V0XRdo8NxRp7ung\\/nfEzb9U7bPJpyWSwircVwOi1oDtjG1v11gVru8pE6W2IuHuqd5rOIZa4GyFpoF5q8Eb1ELn3QGr79KmrxgX6h0+WViWm1VbP6EQyDcqow7MGy9Q26XNvluVnmOQe0RzjmFfu9oF\\/OdDGj5FtwPbKlQGnTWDh1Myai3F102wVTAY5mN0yKCOC80TBFZxaP6PMILhRaRfI35h8b\\/AGuO8oFXH6hNXSgb3XhWeGOmUdA\\/PEupVze05j4FQbJdw+47VyysVBPgeviCH1aHsGADV9NRQEYLK3iVeiEHYBJMUS1QB8y7AdalUw9RGC1oYMxYhOw\\/Mp8BWmf41FQLqwD4yPzBrPIKBsti6c8wz9jig7Uv4I+ZUn36HhVWInmygwVHFfCEFBv0GsPqottEzZRo\\/UIGQWxBoGw+IljSqWPqnEz1k9qzVtrqzuFUKZArf7A+Yf3CPNA9BdGPErWoSGro20t5uEOUrLaWTlea6IF87gUYowWVeJSckWwM0Qb4peKOJqD1ovrs+RiX8RRTqMXQ+DErRxLAvB+0OrsoMjsMuHiMzf5D2cQGIDWGKWS51Km6BVruhc2\\/9ZZMQZSjk\\/7LVb3JOC5aBlmZ8nFcvOepdhNHJP3\\/ABMgPs+hS9DR39RbSRwhYzKo31uGeuVi6wFVEgeALXGRyypjZe3UnG\\/xNTNamiHRAsO1ahnLtX0WFO3LnZ6Rg02gGFC9S7C2mrU3youXWNRCZAGD\\/p0wk2ESpbhccG31GTzIaV0ZLvWPEcclMCYcOajlDzKkgM0C1GWN81UExpALNgDp2+3xEtfySGsmNUyk41L33f6jlAMu4cPHgiUOFvA39SgOGbXdXb46\\/MGl5YV0\\/wCEHnAEvkaJ8COBVhiEHsYK6MQ0jRQGGqQ18SxsQXUiVZJr2AGuEgciua1ARJ1oyNhRe8m4VixQGFQ5Ozp2+n+MxRdaCXZ4Pd4rzEuotqhigz\\/q5YkUJPZnflIaQ3FBYDYxWChXw3f7KY488J6pwyzqYu8H\\/sDCZTSHOT5gevyQR89yyElVbU+IPvFQbSrfi6jdFi1Ww8od8lTCFoO0F31ESM5dldo+LJeYKpoLv3buXRBWlfzFtZhjLE11V74Lhx4YGb2\\/vX9wFi1JMStDUKTAiV3WTJuglHxUL\\/Thzv5lEA3AljsHfmWdphFwF4gnjAcIKMyBMHePRWSlh\\/QVC5bmux10lPOtp3foRx6qLpFSK4XdqB8wb4NDSpy\\/7iGd5QZlEorZogRO8SJ8mYUBZ43q9zlr8K\\/hwPuJacAWs1wr7vzcIBQxnuQstkvDNguFOFXh5VLKJigTGXBkIGGCpc1UxkVriVXwtIz+HwY9dhjgC1YlpIgGyjk+cFuUCFKUb9CwDS65wt4gKpoDn5Hr4q\\/Mtzff027raY+y9Sj9rpJ2+R8NS3+VEsmt5gRMg7p61mE7uCdB+wYcIbSVc00N+AlKP+cj4gcCZ3HpyC\\/K5qUMX0aCzca+CO+TyrjaVzfAXsjtgVGiGgOKGpVUrCyq5xHmCoBevkKP8wAXQXqsAZwRyxYAxQZ9IROYxMMCHUDuIba1g+vjcphH6no8KOkEJcUZa9Fr4uFappYsMr4W8eCGqxqYyvURzCxHNeXnl8UEQxsJxPBrJeaSLhUA+C0IrF8S10gbs8DQp2aqAHW0ABafFlsJ6dvWHFuP+RByTFZt8l4hRhl392I6N9jiFwAFd+ty+bNW2UnXS+I\\/KwQkVbqHYKXm4HIGuFbi+IvUcbN7LafshNU+aIjSYwZVdywYh8gfMNF8wru9V3YjKvQgl0UHzFdCcxCsaMuAA0Ts\\/MbePg7SPjFUqqgbo\\/EYYWGrbTJKKjVCxIs+9QaWWq530w9UV7eLDS\\/Ub06OUX2FvsOGyzAlc6CtGaxpEhqXsq4Mq+7+5ja22S\\/JK3VNk87jg2QZJ4WCCnuja4L0lepacyY0vZeUYRrbfGyD4ppWPL0CVtKUJY2q93sh27QaHUAKV0A4Kdsv2tCyDGRXBKaitp0K1fKipkNubjpnI6jQ+erRw9KkX13GnbZrdq\\/3HleozFCA3pqKIkOEPN\\/qXsVuLAlUdiAUfcWTULBQd+oxg61hX\\/jEdeiqvS3+PmLhWDucpe8Qd9SuKLNOihPmBFUtWj8xbCgl4B0bX8OYCZAehCiiXq4Qe+W62vUXKhWrYJVVqeombcUn5sjI3rK0x1hPiEiMuQ7oFZ7CUcjhJ8UpgeULN4bgY9PNeJUYNXiYBnLVx1Y0cRYQW29I5KiYSlCw8BcPFOGikogilhR2qbb4XvGcxmfKdowA5XmDR0ReZtxsA3eHcIPZ6C0RTzXUtziLeKxPTMUsnIENNL5h8wIXFnhmR7LH2J6YMtrIAr\\/A7iPghP26cQ0xMOqXqwuLl7rmh9MeuiLDDCuBEzqlgXasvNBx4i\\/IhIfmFGaoyGfxA7rUYFBx5we5VTzpxSP3nQS65MO5Yx9ce4DtBSrW77qtaxFUpS1q1WUDILeqloWFFV+4k4+TFr7OYJd2W65RYWKN0KU6u7gzslSNdk09FgCgp3v8x2RGKL\\/i3qHdthQOiHhaa4l5ZwDazQUQluvJ7iDJLGPyJ6dxCX5rwfcHEHcwLc50wvVWK4CT8R0e4qvtR+5SsXAPnZ9x4LUny7HU5OVT6Nxfwh3ZOUbB8sj6KEIEashL3jUsOM2FiiMacN4vMKAmwiKyMbqOSWg37H21DEATnqZxbTIfiIFxbdV9zC03QjkUtsuIdjdoXWq5PzN4uUAsHwD5uYCzdMjV0Apes\\/I3GvAiFF4Fl\\/Ny33CmyjOA9l1tysIFbkoUOVhinHTiLwMGWJYbQ0t0MtEGIUG5rpseH8SyMtCl+j0R8WPLinnH3AYuQdwcsArJWJ6hAQz8R+yGJAZsso8mPuOspcOUX8N18zAY9mF8HwSxpY2g2RbasFh6Jyv4ub\\/TW0H8wxW1jllj12bgrYFybqsxuW+7cn8StxYmh5thqABghdLH\\/IRTnDwdzDa9zCSk8L+UYFkvfe\\/y3HK0BNl8w7gex+0NN+tuPyRiacrp+4WUzuZl6YaOx54uVVAOHTDLiyAVz1EjJg4upa5qUcCW2V6zcXEw8igWqMp6hWsDVmvxFyFMgVnmEqibDFSwpj0PuOe3ALnICbDPxWY9MFsOV4CvhIYdpdBz51X1Aitmqt3mqVxZ1EK5OVD5GPpZ45Ib9dZ\\/MGRnCpHLFUxTkfEBUqDPCteGFMIrZb03YPwSyVCPXgo3+JWk7BQBw7yqMXjOe127u3r+IAgHmf8AFr+CG1JJWq6zKvDSWdnvxCOHxRlcrtYZscws8xKUsQxaVFbGG4SMmAEMOtEI5BKoY7rqFIChC1ZefxcDyvcHrxUColC0mhfeT5l13NI\\/ISnC7IqnkMBfy9QGrujtaZic6UbZRavgafuW0j6CWcviXASknsr6xMYHwkVAI5qpgI\\/EUsfmMwtlzMPPz8wxKwUD4JeBQEOpXQGtkwIQzMU+hTpibLbxM9tbOYooleV\\/qPZo4bB\\/ceud02Na2\\/MZD8HJ\\/tYVJrqHYX9RSjB4gll+4CsMSsh0KMMsRBC+ZY0bg1exj0RoXsHHNfiZX0bzRM+IbGFdF3tDuC2y0cqqtlu+WUHgUbblglL01FlGCcFBLjD+UdoF4Yia0paznwkwLrRfzMtAOA1+oBztcB4AI3KKtVHe46EDShVYCopnOz7hGALCfEIW4h7I0JmKzSscgOT2zdEqB2vy2uVmHauUrTi+PUAHcgOW\\/wBooqmRUo0P1K7GkVstnh+ofKWVPb22OZ\\/\\/2Q==\"}}]', '2025-07-11 04:19:13');
INSERT INTO `chat_messages` (`id`, `conversation_id`, `role`, `content`, `created_at`) VALUES
(10, 3, 'assistant', 'To assist you with junk removal, I need some details from you:\n\n1. **Your Name**\n2. **Your Email**\n3. **Your Phone Number**\n4. **Location**\n5. **Service Date**\n6. **Any additional comments or specific requests regarding the removal?**\n\nPlease provide this information so I can create a service quote for you!', '2025-07-11 04:19:13'),
(11, 3, 'user', 'attached', '2025-07-11 05:15:56'),
(12, 3, 'assistant', 'It seems that the details for the junk removal request were not provided in your response. Could you please share the following information?\n\n1. **Your Name**\n2. **Your Email**\n3. **Your Phone Number**\n4. **Location**\n5. **Service Date**\n6. **Any additional comments or specific requests regarding the removal?**\n\nOnce I have this information, I can assist you further!', '2025-07-11 05:15:56'),
(13, 3, 'user', 'i need a dumpster', '2025-07-11 05:56:20'),
(14, 3, 'assistant', 'Got it! Let\'s gather the necessary information to provide you with a dumpster rental quote. \n\n1. **Are you a Residential or Commercial customer?**\n2. **What is the duration of the rental? (e.g., number of days)**\n3. **What size of dumpster do you need? (e.g., 10-yard, 20-yard, etc.)**\n4. **Do you have any specific requirements or instructions for the rental?**\n5. **Your Name**\n6. **Your Email**\n7. **Your Phone Number**\n8. **Location**\n9. **Service Date**\n\nOnce I have all of this information, I\'ll be able to create a service quote for you!', '2025-07-11 05:56:20'),
(15, 3, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjonyes@test.com', '2025-07-11 05:58:01'),
(16, 3, 'assistant', 'Thank you! Your quote request (#Q2) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 05:58:01'),
(17, 4, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 05:58:57'),
(18, 4, 'assistant', 'Thank you for the information! Let me confirm a few details before I gather everything into a quote request.\n\n1. What type of customer are you? (Residential or Commercial)\n2. Is there anything specific you need for each of the equipment items? (e.g., specific requirements for the dumpsters or toilets)\n\nOnce I have this information, I\'ll prepare the quote request for you!', '2025-07-11 05:58:57'),
(19, 4, 'user', 'residential you can drop it on a footpath beside my house', '2025-07-11 05:59:45'),
(20, 4, 'assistant', 'Thank you! Your quote request (#Q3) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 05:59:45'),
(21, 5, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 06:56:53'),
(22, 5, 'assistant', 'Let\'s gather the necessary information for your service quote. \n\nFirst, can you please confirm whether you are a Residential or Commercial customer?', '2025-07-11 06:56:53'),
(23, 5, 'user', 'residential', '2025-07-11 06:57:08'),
(24, 5, 'assistant', 'Thank you! Here’s a quick summary of the information I\'ve gathered:\n\n- **Customer Type:** Residential\n- **Customer Name:** Jon Vital\n- **Customer Email:** jjony@test.com\n- **Customer Phone:** 121321312\n- **Location:** Dallas, Texas 7110\n- **Service Date:** Tomorrow\n- **Delivery Time:** Morning\n- **Urgent:** No\n- **Live Load Needed:** No\n- **Equipment Details:**\n  1. **15-yard dumpster**: Quantity 2, Duration 14 days\n  2. **20-yard dumpster**: Quantity 1, Duration 14 days\n  3. **Temporary Toilets**: Quantity 2, Duration 14 days\n\nIs there anything else you would like to add, or is all the information correct?', '2025-07-11 06:57:08'),
(25, 5, 'user', 'yes confirm', '2025-07-11 06:57:16'),
(26, 5, 'assistant', 'Thank you! Your quote request (#Q4) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 06:57:16'),
(27, 6, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 07:08:30'),
(28, 6, 'assistant', 'To create a service quote for your equipment rental, I need to gather some additional information.\n\n1. Are you a Residential or Commercial customer?\n2. Can you confirm your phone number? \n\nOnce I have this information, I\'ll compile everything for your quote!', '2025-07-11 07:08:30'),
(29, 6, 'user', 'residential 12313123231', '2025-07-11 07:08:41'),
(30, 6, 'assistant', 'Thank you! Your quote request (#Q5) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 07:08:41'),
(31, 7, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 07:32:55'),
(32, 7, 'assistant', 'To create a service quote, I need to confirm a few details:\n\n1. **Customer Type**: Are you a Residential or Commercial customer?\n2. **Service Type**: Is this request for equipment rental?\n3. **Duration**: It looks like you mentioned 14 days, but please confirm if that’s correct.\n\nOnce I have those details, I can proceed with your request.', '2025-07-11 07:32:55'),
(33, 7, 'user', 'residential', '2025-07-11 07:33:04'),
(34, 7, 'assistant', 'Thank you for confirming that you are a Residential customer. \n\nHere\'s a summary of the information I have so far:\n- **Customer Type**: Residential\n- **Service Type**: Equipment Rental\n- **Location**: Dallas, Texas\n- **Customer Name**: Jon Vital\n- **Email**: jjony@test.com\n- **Phone**: 121321312\n- **Service Date**: Tomorrow\n- **Duration**: 14 days\n- **Delivery Time**: Morning\n- **Live Load Needed**: No\n- **Urgent**: No\n\nNow, I will proceed with the equipment details:\n- **2 x 15-yard dumpster**\n- **1 x 20-yard dumpster**\n- **2 x Temporary toilets**\n\nPlease confirm if everything is correct or if there\'s anything you\'d like to change or add, and then I\'ll submit the quote request!', '2025-07-11 07:33:04'),
(35, 7, 'user', 'confirm', '2025-07-11 07:33:18'),
(36, 7, 'assistant', 'Thank you! Your quote request (#Q6) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 07:33:18'),
(37, 8, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 14:09:03'),
(38, 8, 'assistant', 'Thank you for the information! Before I proceed, can you please confirm if you are a residential or commercial customer?', '2025-07-11 14:09:03'),
(39, 8, 'user', 'residential', '2025-07-11 14:09:12'),
(40, 8, 'assistant', 'Got it! I have noted that you are a residential customer. \n\nHere\'s what I\'ve gathered so far:\n\n- **Customer Name:** Jon Vital\n- **Customer Email:** jjony@test.com\n- **Customer Phone:** 121321312\n- **Location:** Dallas, Texas 7110\n- **Service Date:** Tomorrow\n- **Service Type:** Equipment rental\n- **Rental Duration:** 14 days\n- **Delivery Time:** Morning\n- **Live Load:** No\n- **Urgent:** No\n\n### Equipment Details:\n1. **15-yard dumpster**: Quantity - 2\n2. **20-yard dumpster**: Quantity - 1\n3. **Temporary toilets**: Quantity - 2\n\nPlease confirm that everything is correct, and I will proceed to submit your quote request!', '2025-07-11 14:09:12'),
(41, 8, 'user', 'yes confirm', '2025-07-11 14:09:21'),
(42, 8, 'assistant', 'Thank you! Your quote request (#Q7) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 14:09:21'),
(43, 9, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 14:21:23'),
(44, 9, 'assistant', 'Thank you for the details! Before I proceed, can you please confirm whether you are a Residential or Commercial customer?', '2025-07-11 14:21:23'),
(45, 9, 'user', 'residential', '2025-07-11 14:21:35'),
(46, 9, 'assistant', 'Thank you for confirming! I have the following details:\n\n- Customer Type: Residential\n- Customer Name: Jon Vital\n- Customer Email: jjony@test.com\n- Customer Phone: 121321312\n- Location: Dallas, Texas 7110\n- Service Date: Tomorrow\n- Service Time: Morning\n- Rental Duration: 14 days\n- Live Load: No\n- Urgent: No\n\nNow, I\'ll list the equipment details:\n\n1. **15-yard dumpster**: Quantity 2\n2. **20-yard dumpster**: Quantity 1\n3. **Temporary toilets**: Quantity 2\n\nIf everything looks good, I\'ll submit the quote request! Please let me know if you have any specific needs for the equipment or any additional comments.', '2025-07-11 14:21:35'),
(47, 9, 'user', 'yes i confrim', '2025-07-11 14:21:52'),
(48, 9, 'assistant', 'Thank you! Your quote request (#Q8) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 14:21:52'),
(49, 10, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 14:32:57'),
(50, 10, 'assistant', 'Great! I can help you with that. \n\nTo get started, could you please confirm the following:\n\n1. What type of customer are you? (Residential or Commercial)\n2. Is there anything specific that you need for each piece of equipment, or are the details provided sufficient?\n\nOnce I have that information, I\'ll be able to create the service quote for you!', '2025-07-11 14:32:57'),
(51, 10, 'user', 'residential', '2025-07-11 14:33:06'),
(52, 10, 'assistant', 'Thank you for confirming that you\'re a residential customer! \n\nNow, just to summarize the details for your request:\n\n- **Customer Name:** Jon Vital\n- **Customer Email:** jjony@test.com\n- **Customer Phone:** 121321312\n- **Location:** Dallas, Texas 7110\n- **Service Date:** Tomorrow\n- **Delivery Time:** Morning\n- **Rental Duration:** 14 days\n- **Live Load Needed:** No\n- **Urgent Request:** No\n\n**Equipment Requested:**\n1. **15-yard dumpster**: Quantity 2\n2. **20-yard dumpster**: Quantity 1\n3. **Temporary toilets**: Quantity 2\n\nIs there any specific requirement for any of the equipment, or can I proceed with submitting the quote request with the details you\'ve provided?', '2025-07-11 14:33:06'),
(53, 10, 'user', 'drop it on footpath', '2025-07-11 14:33:25'),
(54, 10, 'assistant', 'Thank you! Your quote request (#Q9) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 14:33:25'),
(55, 11, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 14:52:01'),
(56, 11, 'assistant', 'To create a service quote for you, I need to confirm a few details:\n\n1. **Customer Type**: Are you classified as Residential or Commercial?\n2. **Service Type**: Is this request for equipment rental?\n\nOnce I have this information, I\'ll be able to submit your quote request!', '2025-07-11 14:52:01'),
(57, 11, 'user', 'residential', '2025-07-11 14:52:19'),
(58, 11, 'assistant', 'Thank you! Your quote request (#Q10) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 14:52:19'),
(59, 12, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 15:48:25'),
(60, 12, 'assistant', 'To create a quote for you, I need to confirm a few details.\n\n1. **Customer Type:** Is this rental for Residential or Commercial use?\n2. **Service Date:** You mentioned tomorrow, which is the date we will use.\n3. **Service Time:** You mentioned morning. Can I note that as 9:00 AM or do you have a specific time?\n4. **Delivery Instructions:** Is there any specific instruction for the driver upon delivery?\n\nPlease confirm these details, and I\'ll gather everything needed to create the quote!', '2025-07-11 15:48:25'),
(61, 12, 'user', 'can drop on footpath', '2025-07-11 15:48:51'),
(62, 12, 'assistant', 'Thank you for the information! Just to summarize:\n\n- **Customer Type:** (please provide if Residential or Commercial)\n- **Service Date:** Tomorrow\n- **Service Time:** Morning (I can set it to 9:00 AM unless you specify otherwise)\n- **Delivery Instructions:** Drop on footpath\n\nAlso, I have the equipment details:\n\n- **2** x 15 yard dumpsters\n- **1** x 20 yard dumpster\n- **2** temporary toilets\n\nPlease confirm the customer type, and let me know if you want to adjust the service time!', '2025-07-11 15:48:51'),
(63, 12, 'user', 'residential', '2025-07-11 15:49:04'),
(64, 12, 'assistant', 'Thank you! Your quote request (#Q11) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 15:49:04'),
(65, 13, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 16:25:59'),
(66, 13, 'assistant', 'To create a service quote, I need to confirm a few details:\n\n1. What type of customer are you? (Residential or Commercial)\n2. Can you please provide your phone number?\n3. Is there any specific need or instruction related to each piece of equipment?\n\nOnce I have this information, I can submit your quote request.', '2025-07-11 16:25:59'),
(67, 13, 'user', 'residential', '2025-07-11 16:26:06'),
(68, 13, 'assistant', 'Thank you for confirming that you\'re a residential customer. \n\nCan you please provide the following information:\n1. Your phone number?\n2. Any specific needs or instructions for each piece of equipment? \n\nOnce I have this information, I\'ll be ready to submit your quote request!', '2025-07-11 16:26:06'),
(69, 13, 'user', '12311232321 drop it to footpath', '2025-07-11 16:26:29'),
(70, 13, 'assistant', 'Here\'s a summary of the information I\'ve gathered so far:\n\n- **Customer Type**: Residential\n- **Customer Name**: Jon Vital\n- **Customer Email**: jjony@test.com\n- **Customer Phone**: 12311232321\n- **Location**: Dallas, Texas 7110\n- **Service Date**: Tomorrow\n- **Service Time**: Morning\n- **Is Urgent**: No\n- **Live Load Needed**: No\n- **Driver Instructions**: Drop it to footpath\n- **Equipment Details**:\n  - 2 units of 15-yard dumpster\n  - 1 unit of 20-yard dumpster\n  - 2 units of temporary toilets\n- **Rental Duration**: 14 days\n\nI will proceed to submit the quote request now.', '2025-07-11 16:26:29'),
(71, 13, 'user', 'yes confirm', '2025-07-11 16:26:36'),
(72, 13, 'assistant', 'Thank you! Your quote request (#Q12) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 16:26:36'),
(73, 14, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 16:32:14'),
(74, 14, 'assistant', 'To create your service quote, I need to confirm a few details:\n\n1. What is your customer type: Residential or Commercial?\n2. Could you please provide a phone number?\n\nOnce I have this information, I can finalize your quote request!', '2025-07-11 16:32:14'),
(75, 14, 'user', 'residential', '2025-07-11 16:32:21'),
(76, 14, 'assistant', 'Thank you! Now, could you please provide your phone number? Once I have that, I\'ll be able to submit your quote request.', '2025-07-11 16:32:21'),
(77, 14, 'user', '12122122222', '2025-07-11 16:32:37'),
(78, 14, 'assistant', 'Thank you! Your quote request (#Q13) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 16:32:37'),
(79, 15, 'user', 'i need 2 15 yard dumpster and 1 20 yard dumpster and 2 temporary toilets, for 14 days, tomorrow, can deliver in morning, not a live load, not urgent, location is dallas texas 7110,jon vital 121321312 jjony@test.com', '2025-07-11 16:37:04'),
(80, 15, 'assistant', 'To create your service quote, I need to gather a few more details first. \n\n1. Are you a Residential or Commercial customer?\n2. Could you please confirm your contact information:\n   - Customer Name: Jon Vital\n   - Customer Email: jjony@test.com\n   - Customer Phone: 121321312\n3. Do you have any specific needs for the dumpsters or temporary toilets?\n\nOnce I have this information, I can proceed with your request!', '2025-07-11 16:37:04'),
(81, 15, 'user', 'drop it on footpath', '2025-07-11 16:37:23'),
(82, 15, 'assistant', 'Thank you for the information! \n\nHere\'s a summary of what I have so far for your service quote:\n\n- **Customer Type:** (to be confirmed)\n- **Customer Name:** Jon Vital\n- **Customer Email:** jjony@test.com\n- **Customer Phone:** 121321312\n- **Location:** Dallas, Texas 7110\n- **Service Date:** Tomorrow\n- **Delivery Time:** Morning\n- **Is Urgent:** No\n- **Live Load Needed:** No\n- **Driver Instructions:** Drop it on footpath\n- **Equipment Details:**\n  - 2 units of 15-yard dumpster for 14 days\n  - 1 unit of 20-yard dumpster for 14 days\n  - 2 temporary toilets for 14 days\n\nPlease confirm whether you are a Residential or Commercial customer, and I\'ll submit the quote request!', '2025-07-11 16:37:23'),
(83, 15, 'user', 'yes confirm', '2025-07-11 16:37:30'),
(84, 15, 'assistant', 'Could you please specify whether you are a Residential or Commercial customer? Once I have that information, I will submit the quote request.', '2025-07-11 16:37:30'),
(85, 15, 'user', 'residential', '2025-07-11 16:37:38'),
(86, 15, 'assistant', 'Thank you! Your quote request (#Q14) has been successfully submitted. Our team will review the details and send you the best price within the hour.', '2025-07-11 16:37:38');

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
(1, NULL, NULL, '2025-07-11 03:07:24'),
(2, 1, NULL, '2025-07-11 04:13:35'),
(3, 1, NULL, '2025-07-11 04:19:11'),
(4, 1, NULL, '2025-07-11 05:58:50'),
(5, 1, NULL, '2025-07-11 06:56:52'),
(6, 1, NULL, '2025-07-11 07:08:28'),
(7, 1, NULL, '2025-07-11 07:32:52'),
(8, 1, NULL, '2025-07-11 14:09:01'),
(9, 1, NULL, '2025-07-11 14:21:21'),
(10, 1, NULL, '2025-07-11 14:32:55'),
(11, 1, NULL, '2025-07-11 14:51:59'),
(12, 1, NULL, '2025-07-11 15:48:21'),
(13, 1, NULL, '2025-07-11 16:25:57'),
(14, 1, NULL, '2025-07-11 16:32:13'),
(15, 1, NULL, '2025-07-11 16:37:02');

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
  `booking_id` int DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  `tax` decimal(10,2) DEFAULT '0.00',
  `is_viewed_by_admin` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `quote_id`, `user_id`, `invoice_number`, `amount`, `status`, `due_date`, `payment_method`, `transaction_id`, `created_at`, `updated_at`, `notes`, `booking_id`, `discount`, `tax`, `is_viewed_by_admin`) VALUES
(4, 5, 1, 'INV-2A8411D9', 600.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 07:20:37', '2025-07-11 07:30:03', NULL, NULL, 0.00, 0.00, 0),
(5, 6, 1, 'INV-7CECCB4B', 100.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 07:33:44', '2025-07-11 14:00:48', NULL, NULL, 0.00, 0.00, 0),
(6, 7, 1, 'INV-B12F8923', 497.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 14:10:14', '2025-07-11 14:10:49', NULL, NULL, 5.00, 2.00, 0),
(7, 8, 1, 'INV-4A5DD415', 112.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 14:23:30', '2025-07-11 14:23:44', NULL, NULL, 10.00, 2.00, 0),
(8, 9, 1, 'INV-8F9F0939', 146.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 14:35:28', '2025-07-11 14:35:58', NULL, NULL, 5.00, 1.00, 0),
(11, 10, 1, 'INV-2BC182C1', 1008.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 14:55:47', '2025-07-11 14:56:20', NULL, NULL, 5.00, 2.00, 0),
(12, NULL, 1, 'INV-REL-bbe112', 25.00, 'paid', '2025-07-14', NULL, NULL, '2025-07-11 15:03:07', '2025-07-11 15:06:57', 'Invoice for Relocation Request on Booking #BOOK-7352060F', 6, 0.00, 0.00, 0),
(13, NULL, 1, 'INV-REL-88a077', 25.00, 'paid', '2025-07-14', NULL, NULL, '2025-07-11 15:14:53', '2025-07-11 15:15:21', 'Invoice for Relocation Request on Booking #BOOK-7352060F', 6, 0.00, 0.00, 0),
(14, 11, 1, 'INV-F1C7A925', 20.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 15:50:31', '2025-07-11 15:50:55', NULL, NULL, 0.00, 0.00, 0),
(15, 12, 1, 'INV-8E1485C9', 1501.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 16:27:34', '2025-07-11 16:27:53', NULL, NULL, 2.00, 3.00, 0),
(16, 13, 1, 'INV-F5C8953E', 1797.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 16:33:44', '2025-07-11 16:33:59', NULL, NULL, 5.00, 2.00, 0),
(17, 14, 1, 'INV-355682AE', 1001.00, 'paid', '2025-07-18', NULL, NULL, '2025-07-11 16:38:50', '2025-07-11 16:39:02', NULL, NULL, 5.00, 6.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `description` text NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `quantity`, `unit_price`, `total`) VALUES
(2, 4, 'Equipment Rental (Quote #5)', 1, 600.00, 600.00),
(4, 5, 'Equipment Rental (Quote #6)', 1, 100.00, 100.00),
(5, 6, 'Equipment Rental (Quote #7)', 1, 500.00, 500.00),
(6, 7, 'Equipment Rental (Quote #8)', 1, 120.00, 120.00),
(7, 8, 'Equipment Rental (Quote #9)', 1, 150.00, 150.00),
(8, 8, 'Daily Rate for Extensions', 1, 10.00, 10.00),
(9, 8, 'Relocation Service Charge', 1, 12.00, 12.00),
(10, 8, 'Equipment Swap Service Charge', 1, 12.00, 12.00),
(13, 11, 'Equipment Rental (Quote #10)', 1, 1011.00, 1011.00),
(14, 11, ' - 15-yard dumpster (14 days)', 2, 0.00, 0.00),
(15, 11, ' - 20-yard dumpster (14 days)', 1, 0.00, 0.00),
(16, 11, ' - temporary toilet (14 days)', 2, 0.00, 0.00),
(17, 12, 'Relocation Service for Booking #BOOK-7352060F', 1, 25.00, 25.00),
(18, 13, 'Relocation Service for Booking #BOOK-7352060F', 1, 25.00, 25.00),
(19, 14, 'Equipment Rental (Quote #11)', 1, 20.00, 20.00),
(20, 14, ' - 15-yard dumpster (14 days)', 2, 0.00, 0.00),
(21, 14, ' - 20-yard dumpster (14 days)', 1, 0.00, 0.00),
(22, 14, ' - temporary toilets (14 days)', 2, 0.00, 0.00),
(23, 15, 'Equipment Rental (Quote #12)', 1, 1500.00, 1500.00),
(24, 15, ' - 15-yard dumpster (14 days)', 2, 0.00, 0.00),
(25, 15, ' - 20-yard dumpster (14 days)', 1, 0.00, 0.00),
(26, 15, ' - temporary toilet (14 days)', 2, 0.00, 0.00),
(27, 16, 'Equipment Rental (Quote #13)', 1, 1800.00, 1800.00),
(28, 16, ' - 15-yard dumpster (14 days)', 2, 0.00, 0.00),
(29, 16, ' - 20-yard dumpster (14 days)', 1, 0.00, 0.00),
(30, 16, ' - temporary toilet (14 days)', 2, 0.00, 0.00),
(31, 17, 'Equipment Rental (Quote #14)', 1, 1000.00, 1000.00),
(32, 17, ' - 15-yard dumpster (14 days)', 2, 0.00, 0.00),
(33, 17, ' - 20-yard dumpster (14 days)', 1, 0.00, 0.00),
(34, 17, ' - temporary toilet (14 days)', 2, 0.00, 0.00);

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
(1, 1, 'new_quote', 'Your quote #1 is ready! The quoted price is $125.00.', 'quotes?quote_id=1', 0, '2025-07-11 03:11:21'),
(2, 1, 'payment_due', 'Quote #Q1 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=1', 0, '2025-07-11 03:50:15'),
(3, 1, 'new_quote', 'Your quote #3 is ready! The quoted price is $500.00.', 'quotes?quote_id=3', 0, '2025-07-11 06:06:49'),
(4, 1, 'payment_due', 'Quote #Q3 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=2', 0, '2025-07-11 06:08:27'),
(5, 1, 'booking_status_update', 'Your booking #BK-BK-DUMMY-001 has been updated to: Assigned.', 'bookings?booking_id=1', 0, '2025-07-11 06:23:29'),
(6, 1, 'booking_status_update', 'Your booking #BK-BK-DUMMY-001 has been updated to: Out For Delivery.', 'bookings?booking_id=1', 0, '2025-07-11 06:24:29'),
(7, 1, 'booking_status_update', 'Your booking #BK-BK-DUMMY-001 has been updated to: Delivered.', 'bookings?booking_id=1', 0, '2025-07-11 06:24:57'),
(8, 1, 'new_quote', 'Your quote #4 is ready! The quoted price is $400.00.', 'quotes?quote_id=4', 0, '2025-07-11 07:01:57'),
(9, 1, 'payment_due', 'Quote #Q4 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=3', 0, '2025-07-11 07:05:20'),
(10, 1, 'new_quote', 'Your quote #5 is ready! The quoted price is $600.00.', 'quotes?quote_id=5', 0, '2025-07-11 07:09:09'),
(11, 1, 'payment_due', 'Quote #Q5 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=4', 0, '2025-07-11 07:20:37'),
(12, 1, 'new_quote', 'Your quote #6 is ready! The quoted price is $100.00.', 'quotes?quote_id=6', 0, '2025-07-11 07:33:34'),
(13, 1, 'payment_due', 'Quote #Q6 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=5', 0, '2025-07-11 07:33:44'),
(14, 1, 'booking_confirmed', 'Booking #BOOK-C2FCFCAC created for your paid invoice #INV-7CECCB4B.', 'bookings?booking_id=2', 0, '2025-07-11 14:00:48'),
(15, 1, 'booking_status_update', 'Your booking #BK-BOOK-C2FCFCAC has been updated to: Assigned.', 'bookings?booking_id=2', 0, '2025-07-11 14:07:06'),
(16, 1, 'booking_status_update', 'Your booking #BK-BOOK-C2FCFCAC has been updated to: Out For Delivery.', 'bookings?booking_id=2', 0, '2025-07-11 14:07:44'),
(17, 1, 'booking_status_update', 'Your booking #BK-BOOK-C2FCFCAC has been updated to: Delivered.', 'bookings?booking_id=2', 0, '2025-07-11 14:07:49'),
(18, 1, 'new_quote', 'Your quote #7 is ready! The quoted price is $500.00.', 'quotes?quote_id=7', 0, '2025-07-11 14:09:59'),
(19, 1, 'payment_due', 'Quote #Q7 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=6', 0, '2025-07-11 14:10:14'),
(20, 1, 'booking_confirmed', 'Booking #BOOK-CF050AA2 created for your paid invoice #INV-B12F8923.', 'bookings?booking_id=3', 0, '2025-07-11 14:10:49'),
(21, 1, 'booking_status_update', 'Your booking #BK-BOOK-CF050AA2 has been updated to: Delivered.', 'bookings?booking_id=3', 0, '2025-07-11 14:11:10'),
(22, 1, 'new_quote', 'Your quote #8 is ready! The quoted price is $120.00.', 'quotes?quote_id=8', 0, '2025-07-11 14:23:09'),
(23, 1, 'payment_due', 'Quote #Q8 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=7', 0, '2025-07-11 14:23:30'),
(24, 1, 'booking_confirmed', 'Booking #BOOK-B4B6CC43 created for your paid invoice #INV-4A5DD415.', 'bookings?booking_id=4', 0, '2025-07-11 14:23:44'),
(25, 1, 'booking_status_update', 'Your booking #BK-BOOK-B4B6CC43 has been updated to: Delivered.', 'bookings?booking_id=4', 0, '2025-07-11 14:24:02'),
(26, 1, 'new_quote', 'Your quote #9 is ready! The quoted price is $146.00.', 'quotes?quote_id=9', 0, '2025-07-11 14:34:55'),
(27, 1, 'payment_due', 'Quote #Q9 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=8', 0, '2025-07-11 14:35:28'),
(28, 1, 'booking_confirmed', 'Booking #BOOK-334C2310 created for your paid invoice #INV-8F9F0939.', 'bookings?booking_id=5', 0, '2025-07-11 14:35:58'),
(29, 1, 'booking_status_update', 'Your booking #BK-BOOK-334C2310 has been updated to: Delivered.', 'bookings?booking_id=5', 0, '2025-07-11 14:36:13'),
(30, 1, 'system_message', 'Customer has requested a 2-day extension for Booking ID #5. Please review and approve.', 'bookings?booking_id=5', 0, '2025-07-11 14:36:36'),
(31, 1, 'booking_status_update', 'Your request to extend Booking #5 by 2 days has been submitted for approval.', 'bookings?booking_id=5', 0, '2025-07-11 14:36:36'),
(32, 1, 'new_quote', 'Your quote #10 is ready! The quoted price is $1,008.00.', 'quotes?quote_id=10', 0, '2025-07-11 14:52:57'),
(33, 1, 'payment_due', 'Quote #Q10 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=11', 0, '2025-07-11 14:55:47'),
(34, 1, 'booking_confirmed', 'Booking #BOOK-7352060F created for your paid invoice #INV-2BC182C1.', 'bookings?booking_id=6', 0, '2025-07-11 14:56:20'),
(35, 1, 'booking_status_update', 'Your booking #BK-BOOK-7352060F has been updated to: Out For Delivery.', 'bookings?booking_id=6', 0, '2025-07-11 14:57:08'),
(36, 1, 'booking_status_update', 'Your booking #BK-BOOK-7352060F has been updated to: Delivered.', 'bookings?booking_id=6', 0, '2025-07-11 14:57:23'),
(37, 1, 'payment_due', 'Your Relocation request for booking #BOOK-7352060F requires payment. Please pay invoice #INV-REL-bbe112 to proceed.', 'invoices?invoice_id=12', 0, '2025-07-11 15:03:07'),
(38, 1, 'payment_due', 'Your Relocation request for booking #BOOK-7352060F requires payment. Please pay invoice #INV-REL-88a077 to proceed.', 'invoices?invoice_id=13', 0, '2025-07-11 15:14:53'),
(39, 1, 'system_message', 'Customer requested Relocation for Booking #BOOK-7352060F. Payment of $25.00 required.', 'bookings?booking_id=6', 0, '2025-07-11 15:14:53'),
(40, 1, 'booking_status_update', 'Your booking #BK-BOOK-7352060F has been updated to: Assigned.', 'bookings?booking_id=6', 0, '2025-07-11 15:16:57'),
(41, 1, 'booking_status_update', 'Your booking #BK-BOOK-7352060F has been updated to: Relocated.', 'bookings?booking_id=6', 0, '2025-07-11 15:19:06'),
(42, 1, 'new_quote', 'Your quote #11 is ready! The quoted price is $20.00.', 'quotes?quote_id=11', 0, '2025-07-11 15:50:10'),
(43, 1, 'payment_due', 'Quote #Q11 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=14', 0, '2025-07-11 15:50:31'),
(44, 1, 'booking_confirmed', 'Booking #BOOK-3367E7 created for your paid invoice #INV-F1C7A925.', 'bookings?booking_id=7', 0, '2025-07-11 15:50:55'),
(45, 1, 'booking_status_update', 'Your booking #BK-BOOK-3367E7 has been updated to: Delivered.', 'bookings?booking_id=7', 0, '2025-07-11 15:55:41'),
(46, 1, 'new_quote', 'Your quote #12 is ready! The quoted price is $1,501.00.', 'quotes?quote_id=12', 0, '2025-07-11 16:27:12'),
(47, 1, 'payment_due', 'Quote #Q12 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=15', 0, '2025-07-11 16:27:34'),
(48, 1, 'booking_confirmed', 'Booking #BOOK-41440E created for your paid invoice #INV-8E1485C9.', 'bookings?booking_id=8', 0, '2025-07-11 16:27:53'),
(49, 1, 'new_quote', 'Your quote #13 is ready! The quoted price is $1,797.00.', 'quotes?quote_id=13', 0, '2025-07-11 16:33:02'),
(50, 1, 'payment_due', 'Quote #Q13 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=16', 0, '2025-07-11 16:33:44'),
(51, 1, 'booking_confirmed', 'Booking #BOOK-56F5A8 created for your paid invoice #INV-F5C8953E.', 'bookings?booking_id=9', 0, '2025-07-11 16:33:59'),
(52, 1, 'new_quote', 'Your quote #14 is ready! The quoted price is $1,001.00.', 'quotes?quote_id=14', 0, '2025-07-11 16:38:38'),
(53, 1, 'payment_due', 'Quote #Q14 accepted! Please pay the new invoice to confirm your booking.', 'invoices?invoice_id=17', 0, '2025-07-11 16:38:50'),
(54, 1, 'booking_confirmed', 'Booking #BOOK-D762B7 created for your paid invoice #INV-355682AE.', 'bookings?booking_id=10', 0, '2025-07-11 16:39:02');

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
  `is_relocation_included` tinyint(1) DEFAULT '0',
  `discount` decimal(10,2) DEFAULT '0.00',
  `tax` decimal(10,2) DEFAULT '0.00',
  `attachment_path` varchar(255) DEFAULT NULL,
  `is_viewed_by_admin` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quotes`
--

INSERT INTO `quotes` (`id`, `user_id`, `service_type`, `status`, `customer_type`, `location`, `delivery_date`, `delivery_time`, `removal_date`, `removal_time`, `live_load_needed`, `is_urgent`, `driver_instructions`, `quoted_price`, `daily_rate`, `swap_charge`, `relocation_charge`, `quote_details`, `admin_notes`, `created_at`, `updated_at`, `is_swap_included`, `is_relocation_included`, `discount`, `tax`, `attachment_path`, `is_viewed_by_admin`) VALUES
(2, 2, 'equipment_rental', 'pending', 'Residential', 'Dallas, Texas 7110', '2023-10-07', 'morning', NULL, NULL, 0, 0, NULL, NULL, NULL, 0.00, 0.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-07\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjonyes@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"temporary toilets\"}]}', NULL, '2025-07-11 05:58:01', '2025-07-11 06:17:19', 0, 0, 0.00, 0.00, NULL, 1),
(3, 1, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas 7110', '2023-10-24', 'morning', NULL, NULL, 0, 0, 'Drop on the footpath beside the house', 500.00, NULL, 0.00, 0.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-24\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"Temporary toilet\"}], \"driver_instructions\": \"Drop on the footpath beside the house\"}', '', '2025-07-11 05:59:45', '2025-07-11 06:08:27', 0, 0, 0.00, 0.00, NULL, 1),
(4, 1, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas 7110', '2023-10-07', 'morning', NULL, NULL, 0, 0, NULL, 400.00, NULL, 0.00, 0.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-07\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"Temporary Toilets\"}]}', '', '2025-07-11 06:57:16', '2025-07-11 07:05:20', 0, 0, 60.00, 2.00, NULL, 1),
(5, 1, 'equipment_rental', 'accepted', 'Residential', 'dallas texas 7110', '2023-10-04', 'morning', NULL, NULL, 0, 0, NULL, 600.00, NULL, 0.00, 0.00, '{\"location\": \"dallas texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-04\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"jon vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"12313123231\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"temporary toilet\"}]}', '', '2025-07-11 07:08:41', '2025-07-11 07:20:37', 0, 0, 0.00, 0.00, NULL, 1),
(6, 1, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas', '2023-10-07', 'morning', NULL, NULL, 0, 0, NULL, 100.00, NULL, 0.00, 0.00, '{\"location\": \"Dallas, Texas\", \"is_urgent\": false, \"service_date\": \"2023-10-07\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"Temporary toilet\"}]}', '', '2025-07-11 07:33:18', '2025-07-11 07:33:44', 0, 0, 0.00, 0.00, NULL, 1),
(7, 1, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas 7110', '2023-10-16', 'morning', NULL, NULL, 0, 0, NULL, 500.00, NULL, 0.00, 0.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-16\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"Temporary toilets\"}]}', '', '2025-07-11 14:09:21', '2025-07-11 14:10:14', 0, 0, 5.00, 2.00, NULL, 1),
(8, 1, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas 7110', '2023-10-05', 'Morning', NULL, NULL, 0, 0, NULL, 120.00, NULL, 0.00, 0.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-05\", \"service_time\": \"Morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"Temporary toilets\"}]}', '', '2025-07-11 14:21:52', '2025-07-11 14:23:30', 0, 0, 10.00, 2.00, NULL, 1),
(9, 1, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas 7110', '2023-10-06', 'Morning', NULL, NULL, 0, 0, 'Drop on footpath', 150.00, 10.00, 12.00, 12.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-06\", \"service_time\": \"Morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\", \"specific_needs\": \"Drop on footpath\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\", \"specific_needs\": \"Drop on footpath\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"Temporary toilets\", \"specific_needs\": \"Drop on footpath\"}], \"driver_instructions\": \"Drop on footpath\"}', '', '2025-07-11 14:33:25', '2025-07-11 14:35:28', 0, 0, 5.00, 1.00, NULL, 1),
(10, 1, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas', '2023-10-20', 'morning', NULL, NULL, 0, 0, NULL, 1011.00, 10.00, 10.00, 25.00, '{\"location\": \"Dallas, Texas\", \"is_urgent\": false, \"service_date\": \"2023-10-20\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"temporary toilet\"}]}', '', '2025-07-11 14:52:19', '2025-07-11 14:55:47', 0, 0, 5.00, 2.00, NULL, 1),
(11, 1, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas 7110', '2023-10-06', '09:00', '2025-07-15', '3:00', 0, 0, 'Drop on footpath', 20.00, 10.00, 5.00, 10.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-06\", \"service_time\": \"09:00\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"temporary toilets\"}], \"driver_instructions\": \"Drop on footpath\"}', '', '2025-07-11 15:49:04', '2025-07-11 15:50:31', 0, 0, 0.00, 0.00, NULL, 1),
(12, 1, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas 7110', '2023-11-29', 'morning', NULL, NULL, 0, 0, 'Drop it to footpath', 1500.00, 10.00, 5.00, 6.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-11-29\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"12311232321\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"temporary toilet\"}], \"driver_instructions\": \"Drop it to footpath\"}', '', '2025-07-11 16:26:36', '2025-07-11 16:27:34', 0, 0, 2.00, 3.00, NULL, 1),
(13, 1, 'equipment_rental', 'accepted', 'Residential', 'dallas texas 7110', '2023-10-05', 'morning', NULL, NULL, 0, 0, NULL, 1800.00, 20.00, 23.00, 35.00, '{\"location\": \"dallas texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-05\", \"service_time\": \"morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"12122122222\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"temporary toilet\"}]}', '', '2025-07-11 16:32:37', '2025-07-11 16:33:44', 0, 0, 5.00, 2.00, NULL, 1),
(14, 1, 'equipment_rental', 'accepted', 'Residential', 'Dallas, Texas 7110', '2023-10-03', 'Morning', NULL, NULL, 0, 0, 'Drop it on footpath', 1000.00, 10.00, 10.00, 50.00, '{\"location\": \"Dallas, Texas 7110\", \"is_urgent\": false, \"service_date\": \"2023-10-03\", \"service_time\": \"Morning\", \"service_type\": \"equipment_rental\", \"customer_name\": \"Jon Vital\", \"customer_type\": \"Residential\", \"customer_email\": \"jjony@test.com\", \"customer_phone\": \"121321312\", \"live_load_needed\": false, \"equipment_details\": [{\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"15-yard dumpster\"}, {\"quantity\": 1, \"duration_days\": 14, \"equipment_name\": \"20-yard dumpster\"}, {\"quantity\": 2, \"duration_days\": 14, \"equipment_name\": \"temporary toilet\"}], \"driver_instructions\": \"Drop it on footpath\"}', '', '2025-07-11 16:37:38', '2025-07-11 16:38:50', 0, 0, 5.00, 6.00, NULL, 1);

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
(4, 2, NULL, '15-yard dumpster', 2, 14, NULL),
(5, 2, NULL, '20-yard dumpster', 1, 14, NULL),
(6, 2, NULL, 'temporary toilets', 2, 14, NULL),
(7, 3, NULL, '15-yard dumpster', 2, 14, NULL),
(8, 3, NULL, '20-yard dumpster', 1, 14, NULL),
(9, 3, NULL, 'Temporary toilet', 2, 14, NULL),
(10, 4, NULL, '15-yard dumpster', 2, 14, NULL),
(11, 4, NULL, '20-yard dumpster', 1, 14, NULL),
(12, 4, NULL, 'Temporary Toilets', 2, 14, NULL),
(13, 5, NULL, '15-yard dumpster', 2, 14, NULL),
(14, 5, NULL, '20-yard dumpster', 1, 14, NULL),
(15, 5, NULL, 'temporary toilet', 2, 14, NULL),
(16, 6, NULL, '15-yard dumpster', 2, 14, NULL),
(17, 6, NULL, '20-yard dumpster', 1, 14, NULL),
(18, 6, NULL, 'Temporary toilet', 2, 14, NULL),
(19, 7, NULL, '15-yard dumpster', 2, 14, NULL),
(20, 7, NULL, '20-yard dumpster', 1, 14, NULL),
(21, 7, NULL, 'Temporary toilets', 2, 14, NULL),
(22, 8, NULL, '15-yard dumpster', 2, 14, NULL),
(23, 8, NULL, '20-yard dumpster', 1, 14, NULL),
(24, 8, NULL, 'Temporary toilets', 2, 14, NULL),
(25, 9, NULL, '15-yard dumpster', 2, 14, 'Drop on footpath'),
(26, 9, NULL, '20-yard dumpster', 1, 14, 'Drop on footpath'),
(27, 9, NULL, 'Temporary toilets', 2, 14, 'Drop on footpath'),
(28, 10, NULL, '15-yard dumpster', 2, 14, NULL),
(29, 10, NULL, '20-yard dumpster', 1, 14, NULL),
(30, 10, NULL, 'temporary toilet', 2, 14, NULL),
(31, 11, NULL, '15-yard dumpster', 2, 14, NULL),
(32, 11, NULL, '20-yard dumpster', 1, 14, NULL),
(33, 11, NULL, 'temporary toilets', 2, 14, NULL),
(34, 12, NULL, '15-yard dumpster', 2, 14, NULL),
(35, 12, NULL, '20-yard dumpster', 1, 14, NULL),
(36, 12, NULL, 'temporary toilet', 2, 14, NULL),
(37, 13, NULL, '15-yard dumpster', 2, 14, NULL),
(38, 13, NULL, '20-yard dumpster', 1, 14, NULL),
(39, 13, NULL, 'temporary toilet', 2, 14, NULL),
(40, 14, NULL, '15-yard dumpster', 2, 14, NULL),
(41, 14, NULL, '20-yard dumpster', 1, 14, NULL),
(42, 14, NULL, 'temporary toilet', 2, 14, NULL);

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
(2, 'company_name', 'CAT Dump', 'Name of the company', '2025-07-09 21:34:19'),
(3, 'global_tax_rate', '8.25', 'Global tax rate in percent (e.g., 8.25 for 8.25%)', '2025-07-11 02:26:07'),
(4, 'global_service_fee', '25.00', 'Global flat service fee applied to quotes/invoices', '2025-07-11 02:26:07');

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
(1, 'jon', 'vital', 'jjony@test.com', '123131313', '$2y$10$IH1CJpttFvwjthD67pyiWer7nICgdu76NI3gpZzv7S2K4TEqDhxZC', NULL, NULL, NULL, NULL, 'customer', '2025-07-11 03:08:16', '2025-07-11 03:09:23'),
(2, 'Jon', 'Vital', 'jjonyes@test.com', '121321312', '$2y$10$4obf3n3aGQ/oT58i44GX..1js4j0F.ev5XTPffArvRblO3IcJGIe.', NULL, NULL, NULL, NULL, 'customer', '2025-07-11 05:58:01', '2025-07-11 05:58:01'),
(3, 'Admin', 'User', 'admin@admin.com', '1234567890', '$2y$10$pmYXW9RjE0CjZYE/09h/uu6gwa.TKKnSK9T2Z7UlOxtxYwvqyy0gO', NULL, NULL, NULL, NULL, 'admin', '2025-07-11 06:04:59', '2025-07-11 06:05:32');

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
(1, 1, 'token_6870aa97e1e3d4444', 'Unknown', '4444', '08', '2030', 'demo company', '907 Nobel Street', 1, '2025-07-11 06:09:27', '2025-07-11 07:32:01');

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
  ADD UNIQUE KEY `booking_number` (`booking_number`),
  ADD KEY `idx_bookings_user_id` (`user_id`),
  ADD KEY `idx_bookings_vendor_id` (`vendor_id`),
  ADD KEY `idx_bookings_status` (`status`),
  ADD KEY `fk_bookings_invoice` (`invoice_id`);

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
  ADD KEY `idx_invoices_user_id` (`user_id`),
  ADD KEY `idx_invoices_quote_id` (`quote_id`),
  ADD KEY `idx_invoices_status` (`status`),
  ADD KEY `idx_is_viewed_by_admin` (`is_viewed_by_admin`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

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
  ADD KEY `idx_quotes_status` (`status`),
  ADD KEY `idx_is_viewed_by_admin` (`is_viewed_by_admin`);

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
  ADD KEY `idx_reviews_user_id` (`user_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `booking_charges`
--
ALTER TABLE `booking_charges`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_extension_requests`
--
ALTER TABLE `booking_extension_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `quotes`
--
ALTER TABLE `quotes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `quote_equipment_details`
--
ALTER TABLE `quote_equipment_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_payment_methods`
--
ALTER TABLE `user_payment_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_bookings_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE RESTRICT,
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
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_invoice_items_invoice_id` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `junk_removal_details`
--
ALTER TABLE `junk_removal_details`
  ADD CONSTRAINT `junk_removal_details_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_quotes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
