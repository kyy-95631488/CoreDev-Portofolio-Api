-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2025 at 09:56 AM
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
-- Database: `db_porto_coredev`
--

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `frameworks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`frameworks`)),
  `preview_link` varchar(255) DEFAULT NULL,
  `github_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_team_members`
--

CREATE TABLE `project_team_members` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `short_story` text NOT NULL,
  `photo_url` varchar(255) NOT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`skills`)),
  `linkedin` varchar(255) DEFAULT NULL,
  `github` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `portfolio_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `name`, `email`, `role`, `description`, `short_story`, `photo_url`, `skills`, `linkedin`, `github`, `instagram`, `whatsapp`, `portfolio_link`, `created_at`) VALUES
(3, 'index.php', 'musikgratis1x@gmail.com', 'android_developer', '<p>Saya adalah seorang pengembang perangkat lunak yang bersemangat dengan pengalaman luas dalam membangun solusi teknologi yang inovatif dan efisien. Dengan keahlian dalam JavaScript, TypeScript, React, Next.js, Node.js, dan Python untuk pengembangan web, serta Kotlin, Java Android, dan Jetpack Compose untuk aplikasi mobile, saya mampu menciptakan aplikasi yang responsif dan user-friendly. Saya juga mahir dalam pengembangan backend menggunakan Laravel, PHP, dan Node.js, dengan keahlian dalam desain arsitektur OOP dan pendekatan MVP untuk memastikan kode yang terstruktur dan skalabel.\r<br />\r<br />Saya memiliki pengalaman mendalam dalam pengelolaan database menggunakan SQL dan platform seperti Supabase, serta integrasi layanan cloud seperti Google Cloud, Firebase, dan AWS. Dalam hal desain antarmuka, saya menguasai UI/UX Design, CSS, HTML, dan Tailwind untuk menciptakan tampilan yang estetis dan fungsional. Selain itu, saya terampil dalam DevOps, memastikan proses pengembangan dan deployment yang efisien.\r<br />\r<br />Dengan pendekatan yang berorientasi pada hasil dan fokus pada pengalaman pengguna, saya berkomitmen untuk menghasilkan solusi teknologi yang berdampak dan sesuai dengan kebutuhan bisnis. Mari kita wujudkan ide Anda menjadi kenyataan!</p>', '<p>Saya adalah seorang pengembang perangkat lunak yang bersemangat dengan pengalaman luas dalam membangun solusi teknologi yang inovatif dan efisien. Dengan keahlian dalam JavaScript, TypeScript, React, Next.js, Node.js, dan Python untuk pengembangan web, serta Kotlin, Java Android, dan Jetpack Compose untuk aplikasi mobile, saya mampu menciptakan aplikasi yang responsif dan user-friendly. Saya juga mahir dalam pengembangan backend menggunakan Laravel, PHP, dan Node.js, dengan keahlian dalam desain arsitektur OOP dan pendekatan MVP untuk memastikan kode yang terstruktur dan skalabel.\r<br />\r<br />Saya memiliki pengalaman mendalam dalam pengelolaan database menggunakan SQL dan platform seperti Supabase, serta integrasi layanan cloud seperti Google Cloud, Firebase, dan AWS. Dalam hal desain antarmuka, saya menguasai UI/UX Design, CSS, HTML, dan Tailwind untuk menciptakan tampilan yang estetis dan fungsional. Selain itu, saya terampil dalam DevOps, memastikan proses pengembangan dan deployment yang efisien.\r<br />\r<br />Dengan pendekatan yang berorientasi pada hasil dan fokus pada pengalaman pengguna, saya berkomitmen untuk menghasilkan solusi teknologi yang berdampak dan sesuai dengan kebutuhan bisnis. Mari kita wujudkan ide Anda menjadi kenyataan!</p>', 'https://iwxecxadsjmiofmemcga.supabase.co/storage/v1/object/public/team-member-photos/public/1748155616889_9gz2kqa5ong.png', '[\"PHP\"]', '', '', '', '', '', '2025-05-25 06:46:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','anggota','dosen') NOT NULL DEFAULT 'user',
  `salt` varchar(64) DEFAULT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_code` varchar(10) DEFAULT NULL,
  `code_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `reset_code` varchar(6) DEFAULT NULL,
  `reset_code_expiry` datetime DEFAULT NULL,
  `role_request_count` int(11) DEFAULT 0,
  `last_role_request` datetime DEFAULT NULL,
  `role_request_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `salt`, `session_token`, `token_expiry`, `verified`, `verification_code`, `code_expiry`, `reset_token`, `reset_expiry`, `created_at`, `last_login`, `reset_code`, `reset_code_expiry`, `role_request_count`, `last_role_request`, `role_request_date`) VALUES
(5, 'musikgratis1@gmail.com', '329bab235256b4c65a6481ea8e3084d290e735685c47d54430f78ddfe6f49054261a05121ba0ee959dbc72caba67c5cdb341028cab25795698894f09b3c43504', 'anggota', 'ec447669087ae7b5df3cfa33d57e054768f2414a0eeb45706c49a524bcf389ec', '174b41e2686a111a937d74d91066713f0e1bb99a4c7f16c6cdc34b3fe0c29757', '2025-05-26 08:23:29', 1, NULL, NULL, NULL, NULL, '2025-05-23 16:40:05', '2025-05-25 08:23:29', NULL, NULL, 0, NULL, NULL),
(6, 'cerberus404x@gmail.com', 'cdaa62489891c6d343890aa4e18817da06ad22bb4e4b40f0372e8f5d198594f41e4527fe7e04b2cf53e19e324f3e983915ef216741f018cd0fe574b33e28dd97', 'user', 'b663e48c1694f09bc47734e9673f2d82e8629a1ed908e6ed0f2d0c1eb28a4964', '629903603dfbb735b71afa994d6eff3699e9922b66babc401155346cdee6a84d', '2025-05-25 11:54:33', 1, NULL, NULL, NULL, NULL, '2025-05-23 17:17:33', '2025-05-24 11:54:33', NULL, NULL, 1, '2025-05-24 17:04:36', '2025-05-24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_team_members`
--
ALTER TABLE `project_team_members`
  ADD PRIMARY KEY (`project_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `project_team_members`
--
ALTER TABLE `project_team_members`
  ADD CONSTRAINT `project_team_members_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
