-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 10, 2026 at 09:20 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";



/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hospital_queue`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `admin_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `admin_id`, `password`, `name`, `created_at`) VALUES
(1, 'ADMIN001', 'admin123', 'Super Admin', '2026-01-30 16:01:53');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `icon`) VALUES
(1, 'General Medicine', '🏥'),
(2, 'Pediatrics', '👶'),
(3, 'Orthopedics', '🦴');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `time_slot` varchar(50) DEFAULT NULL,
  `qualification` varchar(200) DEFAULT 'MBBS',
  `fees` int(11) DEFAULT 300
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `name`, `department_id`, `time_slot`, `qualification`, `fees`) VALUES
(1, 'Dr. Rajesh Kumar', 1, '9:00 AM - 1:00 PM', 'MBBS, MD (General Medicine)', 300),
(2, 'Dr. Jayabarathi', 1, '2:00 PM - 6:00 PM', 'MBBS, MD (General Medicine)', 350),
(3, 'Dr. Meena Gupta', 2, '9:00 AM - 1:00 PM', 'MBBS, DCH (Pediatrics)', 400),
(4, 'Dr. Ramesh Iyer', 2, '2:00 PM - 6:00 PM', 'MBBS, DNB (Pediatrics)', 350),
(5, 'Dr. Sanjay Mehta', 3, '9:00 AM - 12:00 PM', 'MBBS, MS (Orthopedics)', 500),
(6, 'Dr. Lakshmi Nair', 3, '2:00 AM - 6:00 PM', 'MBBS MD', 300);

-- --------------------------------------------------------

--
-- Table structure for table `prebooked_appointments`
--

CREATE TABLE `prebooked_appointments` (
  `id` int(11) NOT NULL,
  `booking_id` varchar(20) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `patient_age` int(11) NOT NULL,
  `patient_phone` varchar(20) NOT NULL,
  `department_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('booked','confirmed','checked_in','completed','cancelled') DEFAULT 'booked',
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prebooked_appointments`
--

INSERT INTO `prebooked_appointments` (`id`, `booking_id`, `patient_name`, `patient_age`, `patient_phone`, `department_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `payment_status`, `created_at`, `updated_at`) VALUES
(1, 'PB001', 'Priya Sharma', 28, '9876543210', 1, 1, '2026-02-10', '10:00:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(2, 'PB002', 'Sneha Reddy', 35, '9876543211', 1, 1, '2026-02-10', '10:30:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(3, 'PB003', 'Rahul Menon', 42, '9876543212', 1, 1, '2026-02-10', '11:00:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(4, 'PB004', 'Riya Patel', 23, '3445562757', 1, 2, '2026-02-10', '14:00:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(5, 'PB005', 'Aakash Rao', 31, '9123456701', 1, 2, '2026-02-10', '14:30:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 07:13:20'),
(6, 'PB006', 'Meera Das', 27, '9876501234', 1, 2, '2026-02-10', '15:30:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 07:13:28'),
(7, 'PB007', 'Sandeep Jain', 45, '9988112233', 2, 3, '2026-02-10', '10:45:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(8, 'PB008', 'Ishita Singh', 34, '9765432109', 2, 3, '2026-02-10', '11:00:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(9, 'PB009', 'Harish Kumar', 29, '9345612870', 2, 3, '2026-02-10', '11:15:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(10, 'PB010', 'Lakshmi Nair', 38, '9012345678', 2, 4, '2026-02-10', '14:30:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(11, 'PB011', 'Ganesh Pillai', 41, '9234567890', 2, 4, '2026-02-10', '15:45:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(12, 'PB012', 'Divya George', 26, '9456123789', 2, 4, '2026-02-10', '16:00:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(13, 'PB013', 'Sameer Ali', 33, '9654321876', 3, 5, '2026-02-10', '09:15:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(14, 'PB014', 'Ananya Bose', 28, '9786543210', 3, 5, '2026-02-10', '10:30:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(15, 'PB015', 'Rahul Desai', 36, '9345678123', 3, 5, '2026-02-10', '11:45:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(16, 'PB016', 'Ali', 33, '9654321876', 3, 6, '2026-02-10', '09:15:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(17, 'PB017', 'Anna', 28, '9786543210', 3, 6, '2026-02-10', '10:30:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14'),
(18, 'PB018', 'Ram', 36, '9345678123', 3, 6, '2026-02-10', '11:45:00', 'booked', 'pending', '2026-02-03 18:54:52', '2026-02-10 06:07:14');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `staff_id`, `password`, `name`, `department`, `created_at`) VALUES
(1, 'STAFF001', 'password123', 'John Doe', 'General Medicine', '2026-01-30 16:01:53');

-- --------------------------------------------------------

--
-- Table structure for table `tokens`
--

CREATE TABLE `tokens` (
  `id` int(11) NOT NULL,
  `token_number` varchar(20) NOT NULL,
  `patient_name` varchar(100) DEFAULT NULL,
  `patient_age` int(11) DEFAULT 0,
  `patient_phone` varchar(15) DEFAULT '',
  `department_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `status` enum('waiting','consulting','completed','cancelled') NOT NULL DEFAULT 'waiting',
  `type` enum('walkin','prebooked','pharmacy','lab','review') NOT NULL DEFAULT 'walkin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expected_time` time DEFAULT NULL,
  `token_type` enum('walkin','prebooked','review') DEFAULT 'walkin',
  `booking_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `prebooked_appointments`
--
ALTER TABLE `prebooked_appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`);

--
-- Indexes for table `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `prebooked_appointments`
--
ALTER TABLE `prebooked_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `tokens`
--
ALTER TABLE `tokens`
  ADD CONSTRAINT `tokens_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `tokens_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
