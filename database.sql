-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2026 at 02:15 PM
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
-- Database: `hirenest_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `applicant_profiles`
--

CREATE TABLE `applicant_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `education` varchar(200) DEFAULT NULL,
  `experience` varchar(100) DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applicant_profiles`
--

INSERT INTO `applicant_profiles` (`id`, `user_id`, `phone`, `skills`, `education`, `experience`, `resume_path`, `created_at`) VALUES
(1, 1, '9876543210', 'Java, PHP, MySQL, HTML, CSS, JavaScript,laravale.', 'BCA Student, MAKAUT', 'Built a Quiz Question Bank project using React, Node.js and MongoDB.', NULL, '2026-10-29 18:36:29'),
(2, 3, NULL, NULL, NULL, NULL, NULL, '2026-10-31 07:46:52'),
(3, 4, NULL, NULL, NULL, NULL, NULL, '2026-10-31 10:45:05'),
(4, 5, '9864123564', 'Java,PHP,SQL,Linux,Git,Github,HTML,CSS,JS', NULL, NULL, NULL, '2026-10-31 10:52:07');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(10) UNSIGNED NOT NULL,
  `job_id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` enum('Applied','Shortlisted','Rejected','Withdrawn') NOT NULL DEFAULT 'Applied',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `job_id`, `applicant_id`, `resume_path`, `cover_letter`, `status`, `applied_at`, `updated_at`) VALUES
(1, 1, 1, 'storage/resumes/cbc0a35c4d4f748ad00f2887b1692cbe.pdf', 'I am interested in applying for the Junior PHP Developer position. I have basic knowledge of PHP, MySQL, HTML, CSS, and JavaScript. I am eager to learn and contribute to your development team.', 'Rejected', '2026-10-31 02:46:37', '2026-10-31 03:39:40'),
(2, 2, 3, 'storage/resumes/9065f6a01f93daf1a26fcd502a0fca19.pdf', NULL, 'Shortlisted', '2026-10-31 07:51:32', '2026-10-31 07:55:22'),
(3, 2, 1, 'storage/resumes/f22f12974c6d4065f306e582a2102469.pdf', NULL, 'Withdrawn', '2026-10-31 08:09:24', '2026-10-31 08:10:24'),
(4, 2, 4, 'storage/resumes/faf3a81aa3ab61d9a3c7e11e8ae8ede6.pdf', NULL, 'Applied', '2026-10-31 10:47:17', '2026-10-31 10:47:17'),
(5, 1, 4, 'storage/resumes/9911310c2c2d2bcbd5b9bcee90b23326.pdf', NULL, 'Applied', '2026-10-31 10:48:11', '2026-10-31 10:48:11'),
(6, 3, 5, 'storage/resumes/5693304d41beb45d08aee39182038552.pdf', NULL, 'Applied', '2026-10-31 11:04:12', '2026-10-31 11:04:12'),
(7, 4, 5, 'storage/resumes/b4981b060d384f4fd3962f92327a5b22.pdf', NULL, 'Applied', '2026-10-31 11:04:32', '2026-10-31 11:04:32');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `user_id`, `company_name`, `description`, `website`, `location`, `created_at`) VALUES
(1, 2, 'XYZ company', NULL, NULL, NULL, '2026-10-29 18:38:17'),
(2, 6, 'Ebay', NULL, NULL, NULL, '2026-10-31 10:55:27');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `job_type` enum('Full-time','Part-time','Internship','Contract') NOT NULL DEFAULT 'Full-time',
  `salary` decimal(10,2) DEFAULT NULL,
  `status` enum('active','closed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `company_id`, `title`, `description`, `requirements`, `location`, `job_type`, `salary`, `status`, `created_at`) VALUES
(1, 1, 'Junior PHP Developer', 'Help develop and maintain web applications using PHP and MySQL.', 'Basic PHP, MySQL, HTML, CSS, and problem-solving skills.', 'Kolkata', 'Full-time', 30000.00, 'active', '2026-10-31 01:30:29'),
(2, 1, 'java backend developer', 'java backend buildiing', 'java,backend knowledge ,linux,,git and github,API', 'Kolkata', 'Full-time', 39999.91, 'active', '2026-10-31 07:42:07'),
(3, 2, 'Customer Support Executive', 'Handle customer queries through calls and emails, solve their problems, and keep a record of every conversation. You will also help improve the support process by sharing common customer feedback with the team.', 'Graduate in any stream with good communication skills in English and Bengali or Hindi. Basic computer knowledge is needed. Prior experience is not required.', 'Remote', 'Full-time', 24000.00, 'active', '2026-10-31 11:00:28'),
(4, 2, 'Digital Marketing Executive', 'Plan and run social media campaigns, write posts and ad copy, and track how well each campaign performs. You will prepare a short monthly report on reach, clicks and leads.', 'Degree in Marketing, Mass Communication or a related field. Knowledge of Instagram, Facebook and Google Ads basics. Good writing skills and 1 year of experience preferred.', 'Howrah, West Bengal', 'Full-time', 28000.00, 'active', '2026-10-31 11:01:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('applicant','company','admin') NOT NULL DEFAULT 'applicant',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_active`, `created_at`) VALUES
(1, 'Lokesh Ghosh', 'lokesh62@gmail.com', '$2y$10$ohtGxeLl9CPGV//tIrEnK.UYtaycYVA0JVayGN17G0Fz8B7AgsGv.', 'applicant', 1, '2026-10-29 18:36:29'),
(2, 'Sk Zishan', 'zishan10@gmail.com', '$2y$10$JLbf1Ky/lOfeEAWwDY3JzOa/F1IMkPVSNoQaMFT8qyygvZMrTp6D6', 'company', 1, '2026-10-29 18:38:17'),
(3, 'Md Jasim', 'jasim71@gmail.com', '$2y$10$yn1VF7lfehvCxlRExlTaduz/mBhLo4aZKoBCdT0T.hIZ42DD2GmJ2', 'applicant', 1, '2026-10-31 07:46:52'),
(4, 'Sahil Molla', 'sahil01@gmail.com', '$2y$10$HPEK28ex.9orKv0NK692PuhpL//W2JzlDP046xMnUKQ3NhRRM7Ys2', 'applicant', 1, '2026-10-31 10:45:04'),
(5, 'Suhana Parvin', 'suhana02@gmail.com', '$2y$10$ZcnQAxDOopjxGFRCWRthwurJ0zpwKzV4OzhxBFZMl.yhv0u4MHxru', 'applicant', 1, '2026-10-31 10:52:07'),
(6, 'Naman Ghosh', 'naman03@gmail.com', '$2y$10$SPDmWJ3Ru6hfaQ7Ju0pHAuchJoXVx15TEZshpMuqyoVfmVdgSMTOW', 'company', 1, '2026-10-31 10:55:27'),
(7, 'skfares', 'pubg06172@gmail.com', '$2y$10$a0fW2r0n6aaDWvMoavEOgO.6YRIVxQvD4Z9e24D4c07CviPnj0kvO', 'admin', 1, '2026-10-31 11:53:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applicant_profiles`
--
ALTER TABLE `applicant_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_job_applicant` (`job_id`,`applicant_id`),
  ADD KEY `idx_applications_applicant` (`applicant_id`),
  ADD KEY `idx_applications_job_status` (`job_id`,`status`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jobs_company` (`company_id`),
  ADD KEY `idx_jobs_status_created` (`status`,`created_at`);

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
-- AUTO_INCREMENT for table `applicant_profiles`
--
ALTER TABLE `applicant_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicant_profiles`
--
ALTER TABLE `applicant_profiles`
  ADD CONSTRAINT `fk_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_applications_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_applications_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `fk_companies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `fk_jobs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
