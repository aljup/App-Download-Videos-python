-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 03, 2025 at 06:12 AM
-- Server version: 10.11.11-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aljup_alj`
--

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `filetype` varchar(100) NOT NULL,
  `filesize` int(11) NOT NULL,
  `upload_date` timestamp NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `public` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `filename`, `filepath`, `filetype`, `filesize`, `upload_date`, `user_id`, `folder_id`, `public`) VALUES
(4, '8mdSQRNp0behedKtzF78zdA1ucPCAQliUAJ15QVe.webp', '1740980082_8mdSQRNp0behedKtzF78zdA1ucPCAQliUAJ15QVe.webp', 'image/webp', 240166, '2025-03-03 05:34:42', 1, 1, 0),
(5, 'Facebook 1061521414858119(SD).mp4', '1740981093_Facebook 1061521414858119(SD).mp4', 'video/mp4', 2025881, '2025-03-03 05:51:33', 3, 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `folders`
--

CREATE TABLE `folders` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `folders`
--

INSERT INTO `folders` (`id`, `name`, `parent_id`, `user_id`, `created_date`) VALUES
(1, 'n1', NULL, 1, '2025-03-03 05:06:08'),
(3, 'البرامج', NULL, 3, '2025-03-03 05:50:58');

-- --------------------------------------------------------

--
-- Table structure for table `shares`
--

CREATE TABLE `shares` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `share_code` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `max_views` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `shares`
--

INSERT INTO `shares` (`id`, `file_id`, `share_code`, `created_at`, `expires_at`, `views`, `max_views`) VALUES
(4, 4, 'QZBEFP2D', '2025-03-03 05:34:45', NULL, 0, NULL),
(5, 4, 'QKJYYAJ5', '2025-03-03 05:36:16', NULL, 1, NULL),
(6, 4, 'FYNWF25P', '2025-03-03 05:38:04', NULL, 0, NULL),
(7, 4, 'DWM45BWJ', '2025-03-03 05:44:42', NULL, 8, NULL),
(8, 5, '5HH77BNE', '2025-03-03 05:51:39', NULL, 4, NULL),
(9, 4, 'MM8SU9E2', '2025-03-03 06:10:13', '2025-03-04 03:10:13', 1, NULL),
(10, 4, 'KME4T4DV', '2025-03-03 06:11:01', '2025-03-04 03:11:01', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `share_logs`
--

CREATE TABLE `share_logs` (
  `id` int(11) NOT NULL,
  `share_id` int(11) NOT NULL,
  `accessed_at` timestamp NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `share_logs`
--

INSERT INTO `share_logs` (`id`, `share_id`, `accessed_at`, `ip_address`, `user_agent`) VALUES
(1, 5, '2025-03-03 05:41:02', '5.82.15.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36'),
(2, 7, '2025-03-03 05:44:49', '5.82.15.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36'),
(3, 7, '2025-03-03 05:45:54', '5.82.15.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36'),
(4, 7, '2025-03-03 05:46:47', '5.82.15.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36'),
(5, 7, '2025-03-03 05:46:54', '5.82.15.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36'),
(6, 7, '2025-03-03 05:47:10', '5.82.15.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36'),
(7, 7, '2025-03-03 05:47:37', '5.82.15.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36'),
(8, 7, '2025-03-03 05:48:02', '5.82.15.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36'),
(9, 8, '2025-03-03 05:51:46', '5.82.15.30', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Mobile Safari/537.36'),
(10, 8, '2025-03-03 05:51:51', '66.249.81.200', 'Mozilla/5.0 (Linux; Android 7.0; SM-G930V Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.125 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)'),
(11, 8, '2025-03-03 05:51:51', '66.249.81.200', 'Mozilla/5.0 (Linux; Android 7.0; SM-G930V Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.125 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)'),
(12, 8, '2025-03-03 05:51:51', '66.249.81.201', 'Mozilla/5.0 (Linux; Android 7.0; SM-G930V Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.125 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)'),
(13, 7, '2025-03-03 05:52:20', '5.82.15.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36'),
(14, 9, '2025-03-03 06:10:22', '5.82.15.30', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `storage_limit` bigint(20) NOT NULL DEFAULT 5368709120,
  `storage_used` bigint(20) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `storage_limit`, `storage_used`, `created_at`, `last_login`, `is_admin`) VALUES
(1, 'admin', '$2y$10$zOx4I2Vzv74k66Z61jXpD.gCBuKrOCzrjYyV/YxXvaTAuIprmDndW', 'admin@aljup.com', 5368709120, 240166, '2025-03-03 05:03:03', '2025-03-03 05:17:13', 1),
(3, 'aljailane', '$2y$10$mZs9oGIMZDcA6K5Tl/LtW.sI6qRJ9ZD6NCmcvb7Z7dUyiEU/DnQxe', 'aaljup@gmail.com', 5368709120, 2025881, '2025-03-03 05:50:26', '2025-03-03 05:50:47', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shares`
--
ALTER TABLE `shares`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `share_code` (`share_code`),
  ADD KEY `file_id` (`file_id`);

--
-- Indexes for table `share_logs`
--
ALTER TABLE `share_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `share_id` (`share_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `folders`
--
ALTER TABLE `folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shares`
--
ALTER TABLE `shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `share_logs`
--
ALTER TABLE `share_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `shares`
--
ALTER TABLE `shares`
  ADD CONSTRAINT `shares_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `share_logs`
--
ALTER TABLE `share_logs`
  ADD CONSTRAINT `share_logs_ibfk_1` FOREIGN KEY (`share_id`) REFERENCES `shares` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
