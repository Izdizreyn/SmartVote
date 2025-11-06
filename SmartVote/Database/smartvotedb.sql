-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2025 at 09:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smartvotedb`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_sessions`
--

DROP TABLE IF EXISTS `active_sessions`;
CREATE TABLE `active_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `active_sessions`
--

INSERT INTO `active_sessions` (`id`, `user_id`, `session_token`, `created_at`, `expires_at`, `ip_address`, `user_agent`) VALUES
(31, 3, 'a5d366a0c64aa6ab0c18f507d9350cab5190f15984b818dc95bbc95c8dd255f2', '2025-11-06 15:16:25', '2025-11-07 15:16:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`) VALUES
(1, 'admin', 'admin123', 'Administrator');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

DROP TABLE IF EXISTS `candidates`;
CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `election_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `party` varchar(100) DEFAULT NULL,
  `photo` varchar(500) DEFAULT NULL,
  `status` enum('active','withdrawn','disqualified') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `election_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `description`, `position`, `party`, `photo`, `status`, `created_at`) VALUES
(1, 1, 'Juan', 'Dela', 'Cruz', NULL, 'Experienced leader with vision for change', 'President', 'Bahog Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(2, 1, 'Maria', 'Santos', 'Garcia', NULL, 'Dedicated to student welfare', 'Vice President', 'Bahog Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(3, 1, 'Pedro', 'Miguel', 'Reyes', 'Jr.', 'Passionate about student activities', 'Secretary', 'Bahog Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(4, 1, 'Ana', 'Marie', 'Lopez', NULL, 'Detail-oriented financial expert', 'Treasurer', 'Bahog Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(5, 1, 'Carlos', 'Jose', 'Mendoza', NULL, 'Tech-savvy and innovative', 'Auditor', 'Bahog Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(7, 1, 'Roberto', 'Luis', 'Santos', 'III', 'Strategic business thinker', 'Business Manager', 'Bahog Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(8, 1, 'Apple', 'Jane', 'Fernandez', NULL, 'Fresh perspectives for student leadership', 'President', 'Fruit Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(9, 1, 'Mango', 'Luis', 'Villanueva', NULL, 'Sweet solutions for student concerns', 'Vice President', 'Fruit Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(10, 1, 'Banana', 'Marie', 'Torres', NULL, 'Organized and reliable', 'Secretary', 'Fruit Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(11, 1, 'Orange', 'Carl', 'Diaz', NULL, 'Transparent financial management', 'Treasurer', 'Fruit Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(12, 1, 'Grape', 'Nina', 'Aquino', NULL, 'Meticulous attention to detail', 'Auditor', 'Fruit Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(14, 1, 'Strawberry', 'Sofia', 'Cruz', NULL, 'Business-minded and resourceful', 'Business Manager', 'Fruit Party', 'logo/icon.png', 'active', '2025-10-31 06:49:18'),
(15, 1, 'Jollibee', 'D', 'Alingayo', '', 'bee', 'President', 'Test Party', 'logo/icon.png', 'active', '2025-10-31 07:16:01'),
(16, 3, 'Hello', '', 'World', '', 'pro1', 'President', 'Pro Party', 'logo/icon.png', 'active', '2025-11-03 14:54:34'),
(89, 10, 'Maria', 'Elena', 'Santos', NULL, 'Dedicated student leader with 3 years experience in student council.', 'President', 'Unity Party', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(90, 10, 'Ana', 'Marie', 'Lim', NULL, 'Experienced event organizer committed to inclusive programs.', 'Vice President', 'Unity Party', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(91, 10, 'Patricia', 'Rose', 'Flores', NULL, 'Detail-oriented with excellent organizational skills.', 'Secretary', 'Unity Party', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(92, 10, 'Robert', 'James', 'Chen', NULL, 'Accounting major focused on transparent budget management.', 'Treasurer', 'Unity Party', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(93, 10, 'Michelle', 'Grace', 'Torres', NULL, 'Known for integrity and financial accountability.', 'Auditor', 'Unity Party', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(94, 10, 'David', 'Michael', 'Ramos', NULL, 'Communication arts student skilled in public relations.', 'PIO', 'Unity Party', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(95, 10, 'Juan', 'Carlos', 'Dela Cruz', NULL, 'Former class president focused on academic excellence.', 'President', 'Progressive Alliance', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(96, 10, 'Carlos', 'Antonio', 'Mendoza', NULL, 'Tech-savvy leader advocating for digital transformation.', 'Vice President', 'Progressive Alliance', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(97, 10, 'Sarah', 'Mae', 'Gonzales', NULL, 'Organized with strong administrative skills.', 'Secretary', 'Progressive Alliance', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(98, 10, 'Linda', 'Joy', 'Reyes', NULL, 'Business student committed to strategic financial planning.', 'Treasurer', 'Progressive Alliance', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(99, 10, 'David', 'Lee', 'Wong', NULL, 'Finance student dedicated to transparent governance.', 'Auditor', 'Progressive Alliance', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(100, 10, 'Angela', 'Marie', 'Cruz', NULL, 'Creative multimedia artist excellent in digital content.', 'PIO', 'Progressive Alliance', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(101, 10, 'Sarah', 'Lynn', 'Chen', NULL, 'Environmental advocate passionate about sustainability.', 'President', 'Green Coalition', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(102, 10, 'Michael', 'John', 'Tan', NULL, 'Active in eco-clubs and community service.', 'Vice President', 'Green Coalition', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(103, 10, 'Emma', 'Claire', 'Rivera', NULL, 'Environmental science student with strong organizational abilities.', 'Secretary', 'Green Coalition', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(104, 10, 'Lisa', 'Anne', 'Tan', NULL, 'Dedicated to sustainable budgeting and eco-friendly allocation.', 'Treasurer', 'Green Coalition', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(105, 10, 'James', 'Patrick', 'Garcia', NULL, 'Committed to transparency in green fund management.', 'Auditor', 'Green Coalition', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(106, 10, 'Jessica', 'Nicole', 'Cruz', NULL, 'Journalism student skilled in environmental awareness campaigns.', 'PIO', 'Green Coalition', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(107, 10, 'Michael', 'Angelo', 'Reyes', NULL, 'Sports coordinator promoting balanced student life.', 'President', 'Student First Movement', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(108, 10, 'Sofia', 'Isabel', 'Martinez', NULL, 'Student welfare advocate focused on mental health.', 'Vice President', 'Student First Movement', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(109, 10, 'Daniel', 'Jose', 'Santos', NULL, 'Reliable with experience in organization management.', 'Secretary', 'Student First Movement', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(110, 10, 'Roberto', 'Luis', 'Garcia', NULL, 'Business major focused on student-centered budgeting.', 'Treasurer', 'Student First Movement', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(111, 10, 'Amanda', 'Grace', 'Lim', NULL, 'Advocate for accountability in student fund management.', 'Auditor', 'Student First Movement', 'logo/icon.png', 'active', '2025-11-06 07:35:19'),
(112, 10, 'Mark', 'Anthony', 'Villanueva', NULL, 'Creative communicator dedicated to student engagement.', 'PIO', 'Student First Movement', 'logo/icon.png', 'active', '2025-11-06 07:35:19');

-- --------------------------------------------------------

--
-- Table structure for table `elections`
--

DROP TABLE IF EXISTS `elections`;
CREATE TABLE `elections` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('upcoming','active','ended','cancelled') DEFAULT 'upcoming',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elections`
--

INSERT INTO `elections` (`id`, `title`, `description`, `start_date`, `end_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Testing Election', 'Testing', '2025-10-29 16:00:00', '2025-10-31 04:30:00', 'upcoming', 1, '2025-10-31 06:34:52', '2025-10-31 10:18:58'),
(2, 'ssg', '', '2025-10-30 00:00:00', '2025-10-30 12:00:00', 'upcoming', 1, '2025-10-31 06:39:11', '2025-10-31 10:17:44'),
(3, 'Hello World', 'HEHEHE', '2025-11-03 08:00:00', '2025-11-04 12:00:00', 'upcoming', 1, '2025-11-03 14:49:49', '2025-11-03 14:49:49'),
(10, 'SSG Elections 2026', 'Supreme Student Government Elections for Academic Year 2026-2027', '2025-11-06 08:00:00', '2025-11-10 18:00:00', 'active', 1, '2025-11-06 07:35:19', '2025-11-06 07:35:19');

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

DROP TABLE IF EXISTS `parties`;
CREATE TABLE `parties` (
  `id` int(11) NOT NULL,
  `election_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`id`, `election_id`, `name`, `description`, `created_at`) VALUES
(1, 1, 'Bahog Party', 'Baho', '2025-10-31 06:36:37'),
(2, 1, 'Fruit Party', 'Prutas', '2025-10-31 06:36:53'),
(3, 1, 'Test Party', 'qweqwe', '2025-10-31 07:15:35'),
(4, 3, 'Pro Party', 'mga pro', '2025-11-03 14:53:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `voter_id` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('student') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `voter_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `phone`, `profile_picture`, `password_hash`, `user_type`, `status`, `created_at`, `updated_at`) VALUES
(3, '2025-1234', 'Reigner Jhon', 'Sebedia', 'Torres', '', 'reignerjhontorres1@gmail.com', '09123456789', 'uploads/68fdd592f13f4_1761465746.png', '$2y$10$ltuNP2N/q7NoWgh09nqCf.E3./4UHDCnUYuTDzxyYi6U7BRK/tNa.', 'student', 'active', '2025-10-26 08:02:27', '2025-10-26 08:04:14'),
(4, '2025-00820', 'Jay R', '', 'Reyes', '', 'jayareyes@gmail.com', '09123456789', 'uploads/690044b440e04_1761625268.png', '$2y$10$DluvSJoAGHB/2vU4OnS4d..TgMqDAyXyv5HiCFp.GjFLvZ4QBK9sS', 'student', 'active', '2025-10-28 04:21:08', '2025-10-28 04:21:08'),
(5, '2025-4567', 'Jollibee', 'D', 'Alingayo', '', 'halop93803@hh7f.com', '09123456789', 'uploads/6904730f22505_1761899279.jpg', '$2y$10$Fj.kdnIBrK3Mw04.08OkxeSvPEK8tiFBV0oq7bWKfi4kr38tJB5wW', 'student', 'active', '2025-10-31 08:28:01', '2025-10-31 08:28:01');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `election_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `vote_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `votes`
--

INSERT INTO `votes` (`id`, `user_id`, `election_id`, `candidate_id`, `vote_timestamp`, `ip_address`, `user_agent`) VALUES
(1, 3, 1, 1, '2025-10-31 07:46:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(2, 3, 1, 9, '2025-10-31 07:46:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(3, 3, 1, 10, '2025-10-31 07:46:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(4, 3, 1, 4, '2025-10-31 07:46:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(5, 3, 1, 5, '2025-10-31 07:46:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(6, 3, 1, 7, '2025-10-31 07:46:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(7, 4, 1, 15, '2025-10-31 08:26:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(8, 4, 1, 2, '2025-10-31 08:26:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(9, 4, 1, 10, '2025-10-31 08:26:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(10, 4, 1, 11, '2025-10-31 08:26:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(11, 4, 1, 12, '2025-10-31 08:26:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(12, 4, 1, 7, '2025-10-31 08:26:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(13, 5, 1, 1, '2025-10-31 08:28:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(14, 5, 1, 2, '2025-10-31 08:28:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(15, 5, 1, 3, '2025-10-31 08:28:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(16, 5, 1, 4, '2025-10-31 08:28:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(17, 5, 1, 5, '2025-10-31 08:28:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(18, 5, 1, 14, '2025-10-31 08:28:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_sessions`
--
ALTER TABLE `active_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_election_name` (`election_id`,`first_name`,`last_name`);

--
-- Indexes for table `elections`
--
ALTER TABLE `elections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parties`
--
ALTER TABLE `parties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_party_per_election` (`election_id`,`name`),
  ADD KEY `idx_parties_election` (`election_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voter_id` (`voter_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_election_candidate` (`user_id`,`election_id`,`candidate_id`),
  ADD KEY `idx_votes_user_election_position` (`user_id`,`election_id`,`candidate_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `active_sessions`
--
ALTER TABLE `active_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `elections`
--
ALTER TABLE `elections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `parties`
--
ALTER TABLE `parties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `active_sessions`
--
ALTER TABLE `active_sessions`
  ADD CONSTRAINT `active_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parties`
--
ALTER TABLE `parties`
  ADD CONSTRAINT `fk_parties_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
