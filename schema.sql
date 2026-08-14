-- Water Billing System Schema for database `ramos_db`

CREATE DATABASE IF NOT EXISTS `ramos_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ramos_db`;

-- Drop existing tables if re-initializing
DROP TABLE IF EXISTS `bills`;
DROP TABLE IF EXISTS `users`;

-- Table 1: Users
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: Water Bills
CREATE TABLE IF NOT EXISTS `bills` (
  `bill_id` INT AUTO_INCREMENT PRIMARY KEY,
  `consumer_name` VARCHAR(150) NOT NULL,
  `meter_number` VARCHAR(50) NOT NULL,
  `billing_month` VARCHAR(30) NOT NULL,
  `consumption` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_due` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `due_date` DATE NOT NULL,
  `status` ENUM('paid', 'unpaid') NOT NULL DEFAULT 'unpaid',
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`),
  INDEX `idx_consumer` (`consumer_name`),
  INDEX `idx_meter` (`meter_number`),
  INDEX `idx_billing_month` (`billing_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Seed Data for Admin User (Password: admin123)
-- Password hash generated using PHP password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `users` (`username`, `full_name`, `email`, `password`, `role`, `status`) VALUES
('admin', 'System Administrator', 'admin@ramoswater.com', '$2y$10$FgGKqX43xrQmgJ7auKgPDeGmyeTnSvhvHsiXe3fil66k6VunjW/5e', 'admin', 'active'),
('staff1', 'Juan Dela Cruz', 'juan@ramoswater.com', '$2y$10$FgGKqX43xrQmgJ7auKgPDeGmyeTnSvhvHsiXe3fil66k6VunjW/5e', 'staff', 'active');

-- Insert Sample Bill Records
INSERT INTO `bills` (`consumer_name`, `meter_number`, `billing_month`, `consumption`, `amount_due`, `due_date`, `status`, `remarks`) VALUES
('Maria Santos', 'MTR-10001', 'August 2026', 25.50, 637.50, '2026-08-25', 'paid', 'Payment received via OTC'),
('Pedro Penduko', 'MTR-10002', 'August 2026', 18.00, 450.00, '2026-08-28', 'unpaid', 'First notice sent'),
('Clara Gonzales', 'MTR-10003', 'August 2026', 32.20, 805.00, '2026-08-20', 'paid', 'Paid on time'),
('Antonio Luna', 'MTR-10004', 'August 2026', 45.00, 1125.00, '2026-08-15', 'unpaid', 'Overdue notice pending'),
('Jose Rizal', 'MTR-10005', 'July 2026', 22.00, 550.00, '2026-07-25', 'paid', 'Settled full balance'),
('Andres Bonifacio', 'MTR-10006', 'July 2026', 15.80, 395.00, '2026-07-28', 'unpaid', 'Follow-up call requested'),
('Emilio Aguinaldo', 'MTR-10007', 'August 2026', 28.00, 700.00, '2026-08-30', 'unpaid', 'Regular billing statement');
