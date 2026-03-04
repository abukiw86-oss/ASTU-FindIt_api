-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 04, 2026 at 03:55 AM
-- Server version: 10.6.20-MariaDB-cll-lve
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `zuiilmml_astufindit1`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `user_string_id` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_name` varchar(255) DEFAULT NULL,
  `admin_name` varchar(244) NOT NULL,
  `admin_mssage` varchar(344) NOT NULL,
  `user_message` varchar(234) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `user_string_id`, `details`, `created_at`, `user_name`, `admin_name`, `admin_mssage`, `user_message`) VALUES
(1, 'uo3dA1vK', 'Admin logged in', '2026-02-28 09:59:30', 'uo3dA1vK', 'Abuki', 'login: Admin logged in', ''),
(2, 'uo3dA1vK', 'Admin logged in', '2026-02-28 12:25:06', 'uo3dA1vK', 'Abuki', 'login: Admin logged in', ''),
(3, 'uo3dA1vK', 'Admin logged in', '2026-02-28 12:29:52', 'uo3dA1vK', 'Abuki', 'login: Admin logged in', ''),
(4, 'uo3dA1vK', 'Admin logged in', '2026-02-28 12:31:57', 'uo3dA1vK', 'Abuki', 'login: Admin logged in', ''),
(5, 'uo3dA1vK', 'Admin logged in', '2026-02-28 12:48:13', 'uo3dA1vK', 'Abuki', 'login: Admin logged in', ''),
(6, 'uo3dA1vK', 'Admin logged in', '2026-02-28 13:11:40', 'uo3dA1vK', 'Abuki', 'login: Admin logged in', ''),
(7, 'uo3dA1vK', 'Admin logged in', '2026-02-28 13:18:23', 'uo3dA1vK', 'Abuki', 'login: Admin logged in', ''),
(8, 'uo3dA1vK', 'Admin logged in', '2026-02-28 13:26:13', 'uo3dA1vK', 'Abuki', 'login: Admin logged in', ''),
(9, 'uo3dA1vK', 'Deleted item #9: eydyhshsgsggs', '2026-02-28 13:30:37', 'uo3dA1vK', '', 'delete_item: Deleted item #9: eydyhshsgsggs', ''),
(10, 'uo3dA1vK', 'Sent message to uo3dA1vK', '2026-02-28 13:31:07', 'uo3dA1vK', '', 'send_message: Sent message to uo3dA1vK', ''),
(11, 'jP0oRkfj', 'Deleted item #13: charger', '2026-03-04 03:47:05', 'jP0oRkfj', '', 'delete_item: Deleted item #13: charger', ''),
(12, 'jP0oRkfj', 'Deleted item #14: charger', '2026-03-04 03:47:14', 'jP0oRkfj', '', 'delete_item: Deleted item #14: charger', '');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `item_string_id` varchar(12) NOT NULL,
  `user_string_id` varchar(255) DEFAULT NULL,
  `type` enum('lost','found') NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'other',
  `found_item_property` varchar(1000) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `reporter_name` varchar(100) NOT NULL,
  `reporter_phone` varchar(30) NOT NULL,
  `status` enum('pending','open','matching','claimed','admin_approval','rejected','pending_match') DEFAULT 'admin_approval',
  `when_lost` varchar(255) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `item_string_id`, `user_string_id`, `type`, `title`, `description`, `location`, `category`, `found_item_property`, `image_path`, `reporter_name`, `reporter_phone`, `status`, `when_lost`, `admin_notes`, `created_at`) VALUES
(11, 'hJ2308R3PWe2', 'uo3dA1vK', 'lost', 'aaa', 'xhrmhshbrabheagbae', 'yyrzurjyjzt', 'other', NULL, '\'uploads/lost_1772310220_69a34eccb0e7e.jpg|uploads/lost_1772310220_69a34eccb1197.jpg|uploads/lost_1772310220_69a34eccb13f0.jpg|uploads/lost_1772310220_69a34eccb15fb.jpg\'', 'Abuki', '0981566599', 'admin_approval', NULL, NULL, '2026-02-28 15:23:40'),
(6, 'y73K22yyOe4o', 'bPe3yLHA', 'lost', 'utskjtusdjiytsjujywudyuyur', 'xymixmjkudtmitddji6fujUtdm', 'roksei7ttkiek67or649o', 'other', NULL, '\'uploads/lost_1772281761_69a2dfa19ff30.jpg|uploads/lost_1772281761_69a2dfa1a028c.jpg|uploads/lost_1772281761_69a2dfa1a0506.jpg|uploads/lost_1772281761_69a2dfa1a0727.jpg\'', 'Aaa', '0982365265', 'open', NULL, NULL, '2026-02-28 07:29:21'),
(7, 'or4R42yC6KK2', 'uo3dA1vK', 'found', 'i Found : utskjtusdjiytsjujywudyuyur', 'Description match: idtutdnsginudutnewjuuheher\n        Extra info: keu7rkdj75shhytsryjasyjrnsyjyd', 'sutkjietdj75dttsdykdudjdju', 'other', '{hasSerialNumber: true, hasDistinctiveMark: false, hasReceipt: false, hasPackaging: false}', 'uploads/found_1772286334_69a2f17e345e5.jpg', 'Abuki', '0981566599', 'open', '2/2/2026 â€”', NULL, '2026-02-28 08:45:34');

-- --------------------------------------------------------

--
-- Table structure for table `item_claims`
--

CREATE TABLE `item_claims` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `claimant_string_id` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `lost_location` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_claim_attachments`
--

CREATE TABLE `item_claim_attachments` (
  `id` int(11) NOT NULL,
  `claim_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `lost_item_id` varchar(255) NOT NULL,
  `found_item_id` varchar(255) NOT NULL,
  `match_confidence` int(11) DEFAULT 80,
  `status` enum('pending','confirmed','rejected') DEFAULT 'pending',
  `created_by` varchar(255) DEFAULT NULL,
  `owner_of_item` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`id`, `lost_item_id`, `found_item_id`, `match_confidence`, `status`, `created_by`, `owner_of_item`, `created_at`) VALUES
(1, 'YF02fYS6H4C8', 'wfoby2aR9ZFh', 80, 'pending', 'Tff8y2Ue', 'w3bJXsob', '2026-02-28 06:06:40'),
(2, 'y73K22yyOe4o', 'or4R42yC6KK2', 80, 'pending', 'uo3dA1vK', 'bPe3yLHA', '2026-02-28 13:45:34');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_string_id` varchar(255) NOT NULL,
  `type` enum('claim_approved','claim_rejected','match_found','item_claimed','item_review','admin_message') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `reviewed_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_string_id`, `type`, `title`, `message`, `reference_id`, `admin_notes`, `is_read`, `reviewed_at`, `resolved_at`, `created_at`) VALUES
(1, 'w3bJXsob', 'item_review', 'Item Approved âœ…', 'Your FOUND item \'i foubd my puonw\' has been approved and is now public. Admin notes: vjvjhvjhvjhjjbjkbkjb', 2, 'vjvjhvjhvjhjjbjkbkjb', 0, NULL, NULL, '2026-02-27 17:20:33'),
(2, 'uo3dA1vK', 'admin_message', 'Message from Admin', 'jlbkjbkjb', NULL, 'Sent by ', 0, NULL, NULL, '2026-02-28 13:31:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_string_id` varchar(8) NOT NULL,
  `student_id` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `role` enum('student','admin') DEFAULT 'student',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_string_id`, `student_id`, `password_hash`, `full_name`, `phone`, `role`, `created_at`) VALUES
(12, 'jP0oRkfj', '33636', '$2y$10$J1GHGCXBGBZc6VNptUdZ4.2W/fkdvrk/6nBEtSp1utNOhYzOE9sBC', 'dsgd fsafhsa', '0900000000', 'student', '2026-03-02 07:59:10'),
(11, 'uo3dA1vK', 'ugr/38863/18', '$2y$10$yEmRJ4adkXYUfk/RSWoi8OkXEcScYwvk7fnYtEBgueZNcafDYWkru', 'Abuki', '0981566599', 'admin', '2026-02-28 07:47:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_string_id` (`item_string_id`),
  ADD UNIQUE KEY `item_string_id_2` (`item_string_id`),
  ADD KEY `idx_user_string_id` (`user_string_id`);

--
-- Indexes for table `item_claims`
--
ALTER TABLE `item_claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `claimant_string_id` (`claimant_string_id`),
  ADD KEY `idx_item_status` (`item_id`,`status`);

--
-- Indexes for table `item_claim_attachments`
--
ALTER TABLE `item_claim_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_claim` (`claim_id`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lost` (`lost_item_id`),
  ADD KEY `idx_found` (`found_item_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_string_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`student_id`),
  ADD UNIQUE KEY `user_string_id` (`user_string_id`),
  ADD UNIQUE KEY `user_string_id_2` (`user_string_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `item_claims`
--
ALTER TABLE `item_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `item_claim_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

