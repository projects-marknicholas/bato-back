-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 03:19 AM
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
(27, '6f3804c3900c1f6c', '01609563fcdce35f', 'Long Tables'),
(28, '6f3804c3900c1f6c', '01609563fcdce35f', 'Benches'),
(29, '6f3804c3900c1f6c', '01609563fcdce35f', 'Fan'),
(30, '6f3804c3900c1f6c', '01609563fcdce35f', 'Lighting'),
(40, '45d982e5e783b9af', '3c17c56f13ceddc7', 'Lifeguard on Duty'),
(41, '45d982e5e783b9af', '3c17c56f13ceddc7', 'Floaters'),
(42, '45d982e5e783b9af', '3c17c56f13ceddc7', 'Shower Area'),
(43, '3c100418e8f9420f', '1a10b3c79eb4efc9', 'Picnic Table'),
(44, '3c100418e8f9420f', '1a10b3c79eb4efc9', 'Chairs'),
(45, '3c100418e8f9420f', '1a10b3c79eb4efc9', 'Electric Outlet'),
(46, '3c100418e8f9420f', '1a10b3c79eb4efc9', 'Drinking Water Access'),
(55, 'e3b67d82f60c727d', '1c0011772c6bdaec', 'Grills'),
(56, 'e3b67d82f60c727d', '1c0011772c6bdaec', 'Benches'),
(57, 'e3b67d82f60c727d', '1c0011772c6bdaec', 'Open Air-Space'),
(58, 'e3b67d82f60c727d', '1c0011772c6bdaec', 'Nearby Restroom Access');

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
(3, '9c4678f200713fee', '2cc06220f4be13897cb6d284a9994c40', '6f3804c3900c1f6c', '2025-10-06 14:00:00', '2025-10-06 23:00:00', '4', 'confirmed', 'paid', 'night', 'Need an extra bed and late check-in.', '2025-09-30 11:42:42', '2025-10-06 12:47:40'),
(4, '91746ed53d609768', '163369283afb8f594e003a4c8f7d5f61', 'e3b67d82f60c727d', '2025-10-08 08:00:00', '2025-10-08 15:00:00', '4', 'pending', 'pending', 'day', 'Need an extra bed and late check-in.', '2025-09-30 11:46:05', '2025-10-06 12:53:34');

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
(5, 'c443191689bd11c4', 'How do I make a reservation online?', 'Visit our official website, choose your desired date, select amenities (cottage, pool, event space), and complete the booking form.', 'booking', 'active', '2025-10-05 11:41:17', '2025-10-05 11:41:17'),
(6, '99e6c88865913956', 'Do I need to create an account to book?', 'No. You can book as a guest, but creating an account helps you manage and track your reservations.', 'booking', 'active', '2025-10-05 11:41:34', '2025-10-05 11:41:34'),
(7, '71e0d3aeb0e6ccf7', 'What payment methods are accepted?', 'We accept online payments via credit/debit card, GCash, bank transfer, and on-site cash payments for walk-ins.', 'payment', 'active', '2025-10-05 11:41:50', '2025-10-05 11:41:50'),
(8, '1f93326b25f658a2', 'How will I know if my booking is confirmed?', 'You will receive an email confirmation with booking details once payment is completed or your reservation is processed.', 'booking', 'active', '2025-10-05 11:42:04', '2025-10-05 11:42:04'),
(9, 'c721a1bf4bf75e46', 'Can I book multiple cottages or amenities at once?', 'Yes. Our booking system allows multiple selections in a single reservation.', 'facilities', 'active', '2025-10-05 11:42:19', '2025-10-05 11:42:19'),
(10, 'a759665d7a3e943b', 'Is walk-in booking still available?', 'Yes, but availability is not guaranteed. We highly recommend booking online in advance.', 'booking', 'active', '2025-10-05 11:42:41', '2025-10-05 11:42:41'),
(11, '80d75c2f6599e257', 'Do I need to pay a deposit to confirm my booking?', 'Yes. A minimum deposit may be required to secure your slot, depending on the reservation type.', 'payment', 'active', '2025-10-05 11:43:00', '2025-10-05 11:43:00'),
(12, '0e9c4ab51bea3550', 'Can I cancel or reschedule my booking?', 'Yes. Cancellations and reschedules must be done at least 3 days before your reserved date. Fees may apply.', 'booking', 'active', '2025-10-05 11:43:11', '2025-10-05 11:43:11'),
(13, 'b7bb679033491380', 'What happens if I don’t show up on my reserved date?', 'No-shows are considered a forfeited booking and deposits will not be refunded.', 'general', 'active', '2025-10-05 11:43:35', '2025-10-05 11:43:35'),
(14, '0d5abb4965b2425d', 'Can I transfer my booking to someone else?', 'Yes, provided you notify our booking office at least 24 hours before your reserved date.', 'booking', 'active', '2025-10-05 11:43:53', '2025-10-05 11:43:53'),
(15, '88ab76f92d14e73d', 'How far in advance can I book?', 'Reservations can be made up to 3 months in advance, subject to availability.', 'booking', 'archive', '2025-10-05 11:44:06', '2025-10-05 11:44:26'),
(16, '3017a3eb651ff53d', 'What if the resort is fully booked on my preferred date?', 'You may choose another available date or join the waitlist.', 'booking', 'active', '2025-10-05 11:44:22', '2025-10-05 11:44:22');

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
  `event_date` date DEFAULT NULL,
  `status` enum('published','draft') NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `user_id`, `news_id`, `title`, `description`, `image_url`, `event_date`, `status`, `created_at`, `updated_at`) VALUES
(19, '80f811834855326f91e540b31cfc6af8', 'fc8cea92c12aa769b4cdcc450801ff41', 'Upcoming Resort Events', 'We are excited to announce our special event this weekends!', 'https://example.com/event.jpg', '2025-10-05', 'published', '2025-09-26 12:23:46', '2025-09-26 12:23:46'),
(24, '2cc06220f4be13897cb6d284a9994c40', '3f72f9f07a5878cc6d7cdb64f94facfc', 'Bato Spring Resort Launches Online Booking System', 'San Pablo City, Laguna — Guests of Bato Spring Resort can now enjoy a more convenient way to reserve their visits with the launch of the resort’s new online booking system.\n\nWith this system, visitors can easily check availability, secure their preferred dates, and receive instant confirmation—all from the comfort of their homes. The move is part of the resort’s commitment to providing hassle-free service, especially for families and groups planning their vacations.\n\n“We want our guests to focus on enjoying their time at the resort, not on long reservation processes. This system makes everything faster, simpler, and more reliable,” said the resort’s management team.\n\nThe online booking feature is now live on the resort’s official website. Walk-in reservations are still accepted, but guests are encouraged to book online to guarantee their slots, especially during weekends and peak seasons.', 'https://dynamic-media-cdn.tripadvisor.com/media/photo-o/13/5a/8c/2a/nice-waterfalls-sarap.jpg?w=1200&h=-1&s=1', NULL, 'published', '2025-10-05 00:47:18', '2025-10-05 00:47:18'),
(26, '2cc06220f4be13897cb6d284a9994c40', '9a00aa7b7f555b2c91c0b1c1cfec8799', 'Bato Spring Resort Introduces Seamless Reservations for Visitors', 'San Pablo City, Laguna — Guests planning their vacation at Bato Spring Resort can now enjoy a smoother reservation process with the resort’s newly developed online booking system.\n\nThe platform allows visitors to plan their trips in advance, select their preferred amenities, and confirm reservations instantly. This innovation is designed to minimize waiting times and ensure a better experience for families, friends, and corporate groups.\n\nManagement encourages everyone to take advantage of this system, especially during holidays and peak seasons, when slots fill up quickly.', 'https://perhapsmelisa.wordpress.com/wp-content/uploads/2015/03/img_4530.jpg', NULL, 'published', '2025-10-05 00:48:35', '2025-10-05 00:48:35'),
(27, '2cc06220f4be13897cb6d284a9994c40', 'aa93c4d19efb990b35cab1a992fb03ff', '🌊✨ Exciting News from Bato Spring Resort! ✨🌊', 'No more long waits — you can now book your stay online!\n✔️ Reserve your cottages & amenities in advance\n✔️ Get real-time updates on availability\n✔️ Enjoy hassle-free confirmation\n\n📅 Plan ahead and secure your slots today!\n👉 Book now through our official website.\n\nWe can’t wait to welcome you to paradise here at Bato Spring Resort! 💙', '', NULL, 'published', '2025-10-05 00:49:16', '2025-10-05 00:49:16'),
(28, '2cc06220f4be13897cb6d284a9994c40', '5fbeaf02700882de41b941f4d313478d', '📢 ANNOUNCEMENT TO OUR VALUED GUESTS 📢', 'Due to the upcoming holiday season 🎉, we expect a high volume of visitors at Bato Spring Resort.\n\nTo guarantee your slots, we highly recommend using our Online Booking System for:\n🏡 Cottages\n🏊 Swimming pools\n🎉 Event spaces\n\nDon’t miss out — book early and secure your perfect getaway at Bato Spring Resort! 💦', 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjARvH19xKpntT_NPsq5qvDRydzpbPzi-qbY9Q56fnSwQkSRb1IdHUog50DKSES9xC0P_AlaCVNfta_g_hxhHrR_6fDjjD_DFycQn4ygNX7Jp883HfO3TvMg8eQ7z2d5FNvBx7R2VNoikE/s1600/DSC_9897.JPG', NULL, 'published', '2025-10-05 00:49:49', '2025-10-05 00:49:49'),
(29, '2cc06220f4be13897cb6d284a9994c40', '3519723a1c37553690588fc0d4d9cceb', 'Guests Welcome Digital Booking at Bato Spring Resort', 'San Pablo City — Bato Spring Resort’s new online booking system has received positive feedback from guests since its launch. Many visitors praised the convenience of securing their reservations in advance without the need to travel just to inquire about availability.\n\nThe resort management emphasized that the system was designed to make the guest experience smoother, especially for families and groups planning ahead for weekends and holidays. Walk-in guests remain welcome, but online booking ensures guaranteed slots.', '', NULL, 'published', '2025-10-05 00:50:18', '2025-10-05 00:50:18'),
(30, '2cc06220f4be13897cb6d284a9994c40', '9e80764c119a86321f424194a7dcdc3b', 'ANNOUNCEMENT!!', 'Please be advised that the Bato Spring Resort Online Booking System will undergo scheduled maintenance on:\n\n📅 Date: [Insert Date]\n🕒 Time: [Insert Time]\n\nDuring this period, online reservations will be temporarily unavailable. Guests may still book directly through our resort hotline or front desk.\n\nWe apologize for any inconvenience this may cause and thank you for your understanding.\n— Bato Spring Resort Management', '', NULL, 'published', '2025-10-05 00:50:35', '2025-10-05 00:50:35'),
(31, '2cc06220f4be13897cb6d284a9994c40', '9e2861c56d00dcc7e802a1d602e4e04b', '💦 Ready for a hassle-free getaway? 💦', 'Skip the long lines and book your Bato Spring Resort experience online! 🌿✨\n✔️ Easy reservations\n✔️ Real-time availability\n✔️ Instant confirmation\n\nYour perfect weekend escape is just a few clicks away. 📲\n👉 Reserve now through our official booking page!', '', NULL, 'published', '2025-10-05 00:50:54', '2025-10-05 00:50:54'),
(32, '2cc06220f4be13897cb6d284a9994c40', 'dc0b7c616a6674ea4d01e65cf60aee8e', '📢 IMPORTANT ANNOUNCEMENT 📢', 'We would like to inform our valued guests that Bato Spring Resort is fully booked on [Insert Dates].\n\n✅ Reservations for other dates remain open via our online booking system.\n✅ Walk-in guests on fully booked dates may not be accommodated.\n\nThank you for your continued support, and we encourage you to book early to secure your preferred dates! 🌊', '', NULL, 'published', '2025-10-05 00:51:08', '2025-10-05 00:51:08'),
(33, '2cc06220f4be13897cb6d284a9994c40', 'e03feb5fad4a60374eda7b51cc520e73', 'ANNOUNCEMENT!', 'Planning a family reunion, company outing, or special celebration? 🎉\nBato Spring Resort now accepts group and event reservations through our online booking system.\n\nWith just a few clicks, you can:\n\nReserve event spaces and cottages in advance\nCustomize your booking for large groups\nEnsure hassle-free arrangements before your visit\n\nFor inquiries and special requests, please contact our reservations office.\n— Bato Spring Resort Management', 'https://wanderera.com/wp-content/uploads/2017/07/IMG_7177-1024x683.jpg', NULL, 'published', '2025-10-05 00:51:36', '2025-10-05 00:51:36'),
(34, '2cc06220f4be13897cb6d284a9994c40', '178a0b4729f650f66d739061aa426d3b', 'Holiday Event', 'The holiday season is fast approaching! 🎄✨\nTo avoid last-minute rush and ensure your preferred dates, we encourage all our guests to book early using our Online Booking Platform.\n\nSlots fill up quickly during Christmas and New Year holidays. Walk-ins will be subject to availability only.\n\nPlan ahead and make your holidays stress-free at Bato Spring Resort.\n— Bato Spring Resort Management', '', NULL, 'published', '2025-10-05 00:52:12', '2025-10-05 00:52:12');

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
(3, '755a292d669e00fa9543ecd0e6357c75', 2, 1759728108),
(4, '61f66ba6ab6f78df7cc00444c9ebb793', 8, 1759728430);

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
(2, '6f3804c3900c1f6c', '37f70abbf88a5a58', '9bcef87831652e07', 'Cottage B – Garden View', 'cottage', '20', 'available', '2500', '3200', 'Spacious cottage with a view of the garden area, suitable for medium-sized gatherings.', '14.114803814647', '121.37233074076', '2025-10-05 12:03:36', '2025-10-05 12:14:44'),
(3, '45d982e5e783b9af', '003ec3ad68f971a9', '6fd110bb2aa5c212', 'Swimming Pool – Main', 'pool', '50', 'available', '5000', '7000', 'Large pool suitable for both adults and kids, open for shared use.', '14.115243132596', '121.37480912285', '2025-10-05 12:15:28', '2025-10-05 12:19:13'),
(4, '3c100418e8f9420f', 'd1d60beba711f1f1', '7130e0f49b336a4e', 'Cottage A - River Side', 'room', '10', 'available', '1500', '2000', 'A cozy riverside cottage perfect for small families or groups. Comes with seating and picnic tables.', '14.113140176844', '121.37194945065', '2025-10-05 12:20:12', '2025-10-05 12:21:31'),
(5, 'e3b67d82f60c727d', '0768fd3d6293e364', '66ba171aa6689377', 'Open Space – Picnic Grounds', 'hut', '30', 'available', '1000', '1500', 'Shaded open grounds near the spring, perfect for outdoor bonding.', '14.113451170748', '121.37497593691', '2025-10-05 12:20:31', '2025-10-05 12:27:39'),
(6, '1418fd362ff1da9d', '6176bca058acf45f', 'bdb43cbc24026c77', 'Family Room – Luxury Deluxe', 'room', '4', 'available', '4000', '5000', 'Air-conditioned room with beds, private bathroom, and TV, great for families who prefer comfort.', '14.115128678644', '121.37306948899', '2025-10-05 12:28:19', '2025-10-05 12:28:41'),
(7, 'be5cf355fe588e38', '131f847c9d94e46a', '9274d80946d95f9e', 'Pavilion Hall', 'villa', '100', 'occupied', '9000', '10000', 'Covered pavilion ideal for birthdays, weddings, reunions, or company outings.', '14.113949453606', '121.37554787108', '2025-10-05 12:29:12', '2025-10-06 12:36:00');

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
(15, '6f3804c3900c1f6c', 'a66b04b7706db77d', 'https://shoestringdiary.wordpress.com/wp-content/uploads/2024/10/bato_springs11-ssd.jpg'),
(20, '45d982e5e783b9af', '07611f7f85c8164d', 'https://hanapphonline.com/wp-content/uploads/2024/07/Bato-Spring-Resort.jpg'),
(21, '3c100418e8f9420f', '61dc8496bd758266', 'https://hanapphonline.com/wp-content/uploads/2024/07/Bato-Spring-Resort.jpg'),
(22, '3c100418e8f9420f', '61dc8496bd758266', 'https://wanderera.com/wp-content/uploads/2017/07/IMG_7177-1024x683.jpg'),
(26, 'e3b67d82f60c727d', 'f9f2b4ebde8be501', 'https://rowiewanderlist.wordpress.com/wp-content/uploads/2018/04/13697018_10206815403489695_5025050105982441475_n.jpg');

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
(4, '103660197201019436641', '2cc06220f4be13897cb6d284a9994c40', '755a292d669e00fa9543ecd0e6357c75', '2024448fd852bffaad3423de240bc5bc', 'admin', 'active', 'https://lh3.googleusercontent.com/a/ACg8ocKetm6E898FBy7hsx8xgTGnWTTt1UK6k9erEgfah9fpYV7DjeI=s96-c', 'Mark nicholas', 'Razon', 'razonmarknicholas.cdlb@gmail.com', '$2y$10$upyEd1jJG5IdWgiQh/fxUekbZcXzVwbjkBNwd2cTNYZGrS4byjGiO', '09631877961', 'Batong Malake, Los Banos Laguna, Philippines', '', '', '2025-09-29 18:26:39', '2025-10-06 13:08:24'),
(5, '115997369130456484027', '163369283afb8f594e003a4c8f7d5f61', '61f66ba6ab6f78df7cc00444c9ebb793', 'e9769efc517d3a60e03fecf1539618cf', 'guest', 'active', 'https://lh3.googleusercontent.com/a/ACg8ocIpix2hiQZWdU1WZM3J1O-q16kevMNZQvNzEMP0f2lX0TepeiI=s96-c', 'CheapDevs', 'PH', 'cheapdevsph@gmail.com', '$2y$10$1gZ0E2XsAKK0a7cOSaVlYurtoejBfZhyMElMie.igY0GyJRcnOh.y', '09856103168', 'Los Banos Philippines', '', '', '2025-10-04 20:09:02', '2025-10-06 13:26:53');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `resource_images`
--
ALTER TABLE `resource_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
