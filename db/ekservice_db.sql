-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 15, 2026 at 04:03 PM
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
-- Database: `ekservice_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `full_name`, `email`, `status`, `created_at`) VALUES
(1, 'admin', '$2y$10$MaosTwyRl3jBfZIoIDv7POA83klMvwKD0Xfkw2JG9gyva1H1XQ1xC', 'ภควัฒน์ วันดี', 'borbeam18@gmail.com', 'Active', '2026-09-15 18:42:26');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `tech_id_1` int(11) DEFAULT NULL,
  `tech_id_2` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `status_service` varchar(30) NOT NULL DEFAULT 'รอรับงาน',
  `problem_description` text DEFAULT NULL,
  `problem_photo_url` varchar(255) DEFAULT NULL,
  `reschedule_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_id`, `customer_id`, `service_id`, `tech_id_1`, `tech_id_2`, `booking_date`, `booking_time`, `status_service`, `problem_description`, `problem_photo_url`, `reschedule_reason`, `created_at`) VALUES
(1, 1, 1, 1, NULL, '2026-10-15', '10:30:00', 'เสร็จสิ้น', 'แอร์มีน้ำหยดคอยล์เย็น', NULL, NULL, '2026-09-15 18:42:26');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `line_user_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `line_user_id`, `full_name`, `phone`, `email`, `password`, `address`, `latitude`, `longitude`, `created_at`) VALUES
(1, 'U8bd23c1000000000000000000000000', 'สมหญิง รักดี', '089-876-5432', 'som@gmail.com', NULL, '12 ถ.นิมมานเหมินท์ ต.สุเทพ อ.เมือง จ.เชียงใหม่', 18.796143, 98.979263, '2026-09-15 18:42:26'),
(3, '', 'ภควัฒน์ วันดี', '096 695 3094', 'borbeam188@gmail.com', '$2y$10$tUx/cXM/UUYZv4CzoMfm.eTkDsODbZI6MdlGDn5tDBddRrQ0Ndhw.', '133 ถนน เจริญประเทศ', NULL, NULL, '2026-09-15 20:10:52');

-- --------------------------------------------------------

--
-- Table structure for table `expense`
--

CREATE TABLE `expense` (
  `expense_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `expense_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense`
--

INSERT INTO `expense` (`expense_id`, `owner_id`, `expense_type`, `amount`, `expense_date`, `note`) VALUES
(1, 1, 'ค่าเน็ต', 800.00, '2026-09-15', '');

-- --------------------------------------------------------

--
-- Table structure for table `job_material`
--

CREATE TABLE `job_material` (
  `material_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `material_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `labor_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price` + `labor_cost`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `owner`
--

CREATE TABLE `owner` (
  `owner_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `line_user_id` varchar(50) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `shop_name` varchar(100) NOT NULL DEFAULT 'ร้านเอกเซอร์วิส',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `owner`
--

INSERT INTO `owner` (`owner_id`, `username`, `password`, `line_user_id`, `full_name`, `phone`, `shop_name`, `created_at`) VALUES
(1, 'owner01', '$2y$10$fzUnUx8cWs9aJl2fT6IOReElMNheDdD/DBC/p5yjnXBDhvzbd3/8i', 'U4af4980000000000000000000000000', 'ณัฐพล วันดี', '081-234-5678', 'ร้านเอกเซอร์วิส', '2026-09-15 18:42:26');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `slip_photo_url` varchar(255) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `verify_status` varchar(20) NOT NULL DEFAULT 'รอตรวจสอบ',
  `verified_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'เปิดใช้งาน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `category`, `base_price`, `description`, `status`) VALUES
(1, 'ซ่อมเครื่องปรับอากาศ', 'แอร์', 500.00, 'ล้างทำความสะอาด ตรวจเช็คน้ำยา และซ่อมแซมเครื่องปรับอากาศ', 'เปิดใช้งาน'),
(2, 'ซ่อมระบบไฟฟ้า', 'ไฟฟ้า', 400.00, 'ตรวจเช็คและซ่อมแซมระบบไฟฟ้าภายในบ้าน', 'เปิดใช้งาน'),
(3, 'ซ่อมระบบประปา', 'ประปา', 350.00, 'ตรวจเช็คและซ่อมแซมท่อประปา ก๊อกน้ำ', 'เปิดใช้งาน');

-- --------------------------------------------------------

--
-- Table structure for table `technician`
--

CREATE TABLE `technician` (
  `tech_id` int(11) NOT NULL,
  `line_user_id` varchar(50) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ว่าง',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `technician`
--

INSERT INTO `technician` (`tech_id`, `line_user_id`, `full_name`, `phone`, `specialty`, `status`, `created_at`) VALUES
(1, 'U9fe4471000000000000000000000000', 'วิชัย ช่างเก่ง', '086-111-2233', 'ไฟฟ้า, เครื่องปรับอากาศ', 'ว่าง', '2026-09-15 18:42:26'),
(2, 'U9fe4472000000000000000000000000', 'สมศักดิ์ ช่างมือทอง', '086-222-3344', 'ประปา, งานทั่วไป', 'ว่าง', '2026-09-15 18:42:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `uq_admin_username` (`username`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_booking_date` (`booking_date`,`booking_time`),
  ADD KEY `fk_booking_customer` (`customer_id`),
  ADD KEY `fk_booking_service` (`service_id`),
  ADD KEY `fk_booking_tech1` (`tech_id_1`),
  ADD KEY `fk_booking_tech2` (`tech_id_2`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `uq_customer_line_id` (`line_user_id`);

--
-- Indexes for table `expense`
--
ALTER TABLE `expense`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `fk_expense_owner` (`owner_id`);

--
-- Indexes for table `job_material`
--
ALTER TABLE `job_material`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `fk_material_booking` (`booking_id`);

--
-- Indexes for table `owner`
--
ALTER TABLE `owner`
  ADD PRIMARY KEY (`owner_id`),
  ADD UNIQUE KEY `uq_owner_username` (`username`),
  ADD UNIQUE KEY `uq_owner_line_id` (`line_user_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payment_booking` (`booking_id`),
  ADD KEY `fk_payment_owner` (`verified_by`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `technician`
--
ALTER TABLE `technician`
  ADD PRIMARY KEY (`tech_id`),
  ADD UNIQUE KEY `uq_tech_line_id` (`line_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `expense`
--
ALTER TABLE `expense`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `job_material`
--
ALTER TABLE `job_material`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `owner`
--
ALTER TABLE `owner`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `technician`
--
ALTER TABLE `technician`
  MODIFY `tech_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_tech1` FOREIGN KEY (`tech_id_1`) REFERENCES `technician` (`tech_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_tech2` FOREIGN KEY (`tech_id_2`) REFERENCES `technician` (`tech_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `expense`
--
ALTER TABLE `expense`
  ADD CONSTRAINT `fk_expense_owner` FOREIGN KEY (`owner_id`) REFERENCES `owner` (`owner_id`) ON UPDATE CASCADE;

--
-- Constraints for table `job_material`
--
ALTER TABLE `job_material`
  ADD CONSTRAINT `fk_material_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_owner` FOREIGN KEY (`verified_by`) REFERENCES `owner` (`owner_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
