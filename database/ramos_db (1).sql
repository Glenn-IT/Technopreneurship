-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2026 at 04:23 PM
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
-- Database: `ramos_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `tblaprilyn`
--

CREATE TABLE `tblaprilyn` (
  `bill_id` int(11) NOT NULL,
  `consumer_name` varchar(150) NOT NULL,
  `meter_number` varchar(50) NOT NULL,
  `billing_month` varchar(30) NOT NULL,
  `consumption` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_due` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `status` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblaprilyn`
--

INSERT INTO `tblaprilyn` (`bill_id`, `consumer_name`, `meter_number`, `billing_month`, `consumption`, `amount_due`, `due_date`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 'Maria Santos', 'MTR-10001', 'August 2026', 25.50, 637.50, '2026-08-25', 'paid', 'Payment received via OTC', '2026-08-13 15:49:57', '2026-08-13 15:49:57'),
(2, 'Pedro Penduko', 'MTR-10002', 'August 2026', 18.00, 450.00, '2026-08-28', 'unpaid', 'First notice sent', '2026-08-13 15:49:57', '2026-08-13 15:49:57'),
(3, 'Clara Gonzales', 'MTR-10003', 'August 2026', 32.20, 805.00, '2026-08-20', 'paid', 'Paid on time', '2026-08-13 15:49:57', '2026-08-13 15:49:57'),
(4, 'Antonio Luna', 'MTR-10004', 'August 2026', 45.00, 1125.00, '2026-08-15', 'unpaid', 'Overdue notice pending', '2026-08-13 15:49:57', '2026-08-13 15:49:57'),
(5, 'Jose Rizal', 'MTR-10005', 'July 2026', 22.00, 550.00, '2026-07-25', 'paid', 'Settled full balance', '2026-08-13 15:49:57', '2026-08-13 15:49:57'),
(6, 'Andres Bonifacio', 'MTR-10006', 'July 2026', 15.80, 395.00, '2026-07-28', 'unpaid', 'Follow-up call requested', '2026-08-13 15:49:57', '2026-08-13 15:49:57'),
(7, 'Emilio Aguinaldo', 'MTR-10007', 'August 2026', 28.00, 700.00, '2026-08-30', 'unpaid', 'Regular billing statement', '2026-08-13 15:49:57', '2026-08-13 15:49:57'),
(8, 'Juan Tamad', 'MTR-0032', 'August 2026', 50.00, 1250.00, '2026-08-28', 'unpaid', 'Notice Sent', '2026-08-14 04:39:37', '2026-08-14 04:39:37'),
(9, 'Juan Tamad', 'MTR-62747', 'July 2026', 500.00, 12500.00, '2026-08-28', 'unpaid', 'Regular Billing', '2026-08-14 04:47:44', '2026-08-14 04:47:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sq1_answer` varchar(255) DEFAULT NULL,
  `sq2_answer` varchar(255) DEFAULT NULL,
  `sq3_answer` varchar(255) DEFAULT NULL,
  `sq4_answer` varchar(255) DEFAULT NULL,
  `sq5_answer` varchar(255) DEFAULT NULL,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `full_name`, `email`, `password`, `role`, `status`, `created_at`, `sq1_answer`, `sq2_answer`, `sq3_answer`, `sq4_answer`, `sq5_answer`, `security_question`, `security_answer`) VALUES
(1, 'admin', 'System Administrator', 'admin@ramoswater.com', '$2y$10$WDApOxSvNPrliMRL391fi.fjh6OhZuxerQ0sERVSCNI5XDT9doJQm', 'admin', 'active', '2026-08-13 15:49:57', NULL, NULL, NULL, NULL, NULL, 'What was the name of your first pet?', 'sample'),
(2, 'staff1', 'Juan Dela Cruz', 'juan@ramoswater.com', '$2y$10$LIwYemWP4G5QrBz1Vrj8r.C/rBepwEzQArqdYSwPnFXeDVCSr216G', 'staff', 'active', '2026-08-13 15:49:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'jsmith', 'John Doe', 'jsmith@gmail.com', '$2y$10$iwyRLzU8JGjPpQF1ySaiUe9jZl7OssH1k46G7qty9kJJjQXSz5hFy', 'staff', 'active', '2026-08-13 16:00:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'sample', 'sample', 'sample@gmail.com', '$2y$10$tfi2rR6rmazef9GKVkOM/uMv3m/v3JT8KnUgvWbawWT510nkREjsa', 'staff', 'active', '2026-08-14 05:16:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblaprilyn`
--
ALTER TABLE `tblaprilyn`
  ADD PRIMARY KEY (`bill_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_consumer` (`consumer_name`),
  ADD KEY `idx_meter` (`meter_number`),
  ADD KEY `idx_billing_month` (`billing_month`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblaprilyn`
--
ALTER TABLE `tblaprilyn`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
