-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: May 11, 2026 at 12:34 AM
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
-- Database: `elgu_monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Disapproved') DEFAULT 'Pending',
  `failed_attempts` int(11) DEFAULT 0,
  `lockout_time` timestamp NULL DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires` timestamp NULL DEFAULT NULL,
  `otp_requests` int(11) DEFAULT 0,
  `otp_lockout` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`, `email`, `status`, `failed_attempts`, `lockout_time`, `otp_code`, `otp_expires`, `otp_requests`, `otp_lockout`) VALUES
(2, 'sarah', '$2y$10$p6omAqyt8M6k1Z7hrEleGejPAHgve3wEJwgP7Zw9Fha4d1OxnnCHm', '2026-05-10 21:03:55', 'hello@ascendbeyond.org', 'Approved', 0, NULL, NULL, NULL, 0, NULL),
(3, 'bbbbb ', '$2y$10$iSDlCdwobN3ie2RI7U4vD.TmCe4EssQFOMwZchJ8IF5jC.TGpPtpG', '2026-05-10 21:22:05', 'clifford@wedaretochange.com', 'Approved', 0, '2026-05-10 21:44:02', NULL, NULL, 0, NULL),
(4, 'asda', '$2y$10$jzo8Zhce/HD6ATinO1pILuUasHBFLcB/ZHDHEHnR2xlNzAg119vsW', '2026-05-10 22:25:00', 'ae202403685@wmsu.edu.ph', 'Approved', 0, NULL, NULL, NULL, 0, NULL);

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
