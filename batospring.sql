-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 30, 2025 at 02:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `batospring`
--

-- --------------------------------------------------------

--
-- Table structure for table `ammenities`
--

CREATE TABLE `ammenities` (
  `id` int(11) NOT NULL,
  `resource_id` varchar(255) NOT NULL,
  `ammenities_id` varchar(255) NOT NULL,
  `ammenity` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ammenities`
--

INSERT INTO `ammenities` (`id`, `resource_id`, `ammenities_id`, `ammenity`) VALUES
(12, 'f831c562c7c2cb99', 'ed37cdbaa3484bea', 'Air Conditioning'),
(13, 'f831c562c7c2cb99', 'ed37cdbaa3484bea', 'Private Bathroom'),
(14, 'f831c562c7c2cb99', 'ed37cdbaa3484bea', 'Mini Fridge');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `resource_id` varchar(255) NOT NULL,
  `check_in` datetime NOT NULL,
  `check_out` datetime NOT NULL,
  `guests` varchar(10) NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid') DEFAULT 'pending',
  `rate` enum('day','night') DEFAULT NULL,
  `special_request` varchar(1000) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_id`, `user_id`, `resource_id`, `check_in`, `check_out`, `guests`, `status`, `payment_status`, `rate`, `special_request`, `created_at`, `updated_at`) VALUES
(3, '9c4678f200713fee', '2cc06220f4be13897cb6d284a9994c40', 'f831c562c7c2cb99', '2025-10-05 14:00:00', '2025-10-07 11:00:00', '4', 'confirmed', 'paid', 'day', 'Need an extra bed and late check-in.', '2025-09-30 11:42:42', '2025-09-30 11:42:42'),
(4, '91746ed53d609768', '2cc06220f4be13897cb6d284a9994c40', 'f831c562c7c2cb99', '2025-10-05 14:00:00', '2025-10-07 11:00:00', '4', 'confirmed', 'paid', 'day', 'Need an extra bed and late check-in.', '2025-09-30 11:46:05', '2025-09-30 11:46:05');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `faq_id` varchar(255) NOT NULL,
  `question` varchar(3000) NOT NULL,
  `answer` varchar(5000) NOT NULL,
  `category` varchar(255) NOT NULL,
  `status` enum('active','archive') NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `faq_id`, `question`, `answer`, `category`, `status`, `created_at`, `updated_at`) VALUES
(1, 'aaaae93fd9f8a89a', 'sample question', 'sample answer', 'booking', 'active', '2025-09-29 22:03:27', '2025-09-29 22:03:27');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `news_id` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(10000) NOT NULL,
  `image_url` varchar(1000) NOT NULL,
  `event_date` datetime NOT NULL,
  `status` enum('published','draft') NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `user_id`, `news_id`, `title`, `description`, `image_url`, `event_date`, `status`, `created_at`, `updated_at`) VALUES
(19, '80f811834855326f91e540b31cfc6af8', 'fc8cea92c12aa769b4cdcc450801ff41', 'Upcoming Resort Events', 'We are excited to announce our special event this weekends!', 'https://example.com/event.jpg', '2025-10-05 00:00:00', 'published', '2025-09-26 12:23:46', '2025-09-26 12:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `requests` int(11) NOT NULL,
  `last_request` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `api_key`, `requests`, `last_request`) VALUES
(1, '8783ad9b46e0caba0746955f4618799e', 1, 1758857999),
(2, '0b40ed149f9d195d28db1003889d8601', 2, 1759141967),
(3, '755a292d669e00fa9543ecd0e6357c75', 1, 1759204826);

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `resource_id` varchar(255) NOT NULL,
  `ammenities_id` varchar(255) NOT NULL,
  `image_id` varchar(255) NOT NULL,
  `resource_name` varchar(255) NOT NULL,
  `resource_type` varchar(255) NOT NULL,
  `capacity` varchar(255) NOT NULL,
  `status` enum('available','occupied','reserved','closed') NOT NULL,
  `day_rate` varchar(255) NOT NULL,
  `night_rate` varchar(255) NOT NULL,
  `description` varchar(5000) NOT NULL,
  `latitude` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `resource_id`, `ammenities_id`, `image_id`, `resource_name`, `resource_type`, `capacity`, `status`, `day_rate`, `night_rate`, `description`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(1, 'f831c562c7c2cb99', '676fd8e6b9fc77ad', '318207a277756d0b', 'Deluxe Cottage', 'Cottage', '8', 'available', '2650', '3500', 'A cozy deluxe cottage with air-conditioning, perfect for families or small groups.', '14.1623', '121.3254', '2025-09-29 22:27:19', '2025-09-29 22:34:33');

-- --------------------------------------------------------

--
-- Table structure for table `resource_images`
--

CREATE TABLE `resource_images` (
  `id` int(11) NOT NULL,
  `resource_id` varchar(255) NOT NULL,
  `image_id` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resource_images`
--

INSERT INTO `resource_images` (`id`, `resource_id`, `image_id`, `path`) VALUES
(8, 'f831c562c7c2cb99', 'c192918b2b181776', 'uploads/resources/cottage1.jpg'),
(9, 'f831c562c7c2cb99', 'c192918b2b181776', 'uploads/resources/cottage2.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `csrf_token` varchar(255) DEFAULT NULL,
  `role` enum('guest','admin') NOT NULL DEFAULT 'guest',
  `status` enum('active','suspended','banned') DEFAULT NULL,
  `profile` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address` varchar(3000) NOT NULL,
  `latitude` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `google_id`, `user_id`, `api_key`, `csrf_token`, `role`, `status`, `profile`, `first_name`, `last_name`, `email_address`, `password`, `phone_number`, `address`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(4, '103660197201019436641', '2cc06220f4be13897cb6d284a9994c40', '755a292d669e00fa9543ecd0e6357c75', '31f5e97e4d041aeff5e95d4038d89f1d', 'admin', 'active', 'https://lh3.googleusercontent.com/a/ACg8ocKetm6E898FBy7hsx8xgTGnWTTt1UK6k9erEgfah9fpYV7DjeI=s96-c', 'Mark nicholas', 'Razon', 'razonmarknicholas.cdlb@gmail.com', '$2y$10$upyEd1jJG5IdWgiQh/fxUekbZcXzVwbjkBNwd2cTNYZGrS4byjGiO', '', '', '', '', '2025-09-29 18:26:39', '2025-09-30 12:30:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ammenities`
--
ALTER TABLE `ammenities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resource_images`
--
ALTER TABLE `resource_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ammenities`
--
ALTER TABLE `ammenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `resource_images`
--
ALTER TABLE `resource_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
