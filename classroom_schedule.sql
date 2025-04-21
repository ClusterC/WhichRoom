-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2025 at 12:10 PM
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
-- Database: `classroom_schedule`
--

-- --------------------------------------------------------

--
-- Table structure for table `reserved_status`
--

CREATE TABLE `reserved_status` (
  `room_id` int(11) NOT NULL,
  `is_reserved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reserved_status`
--

INSERT INTO `reserved_status` (`room_id`, `is_reserved`) VALUES
(6110, 0);

-- --------------------------------------------------------

--
-- Table structure for table `room_details`
--

CREATE TABLE `room_details` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(255) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `has_plug` tinyint(1) DEFAULT NULL,
  `has_computer` tinyint(1) DEFAULT NULL,
  `room_size` enum('small','medium','large') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_details`
--

INSERT INTO `room_details` (`room_id`, `room_name`, `capacity`, `has_plug`, `has_computer`, `room_size`) VALUES
(6110, '6110', 30, 1, 1, 'medium');

-- --------------------------------------------------------

--
-- Table structure for table `room_status`
--

CREATE TABLE `room_status` (
  `status_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('available','occupied') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_status`
--

INSERT INTO `room_status` (`status_id`, `room_id`, `day`, `start_time`, `end_time`, `status`) VALUES
(1, 6110, 'Monday', '08:00:00', '08:50:00', 'available'),
(2, 6110, 'Monday', '09:00:00', '11:50:00', 'occupied'),
(3, 6110, 'Monday', '12:00:00', '15:25:00', 'available'),
(4, 6110, 'Monday', '15:25:00', '17:25:00', 'occupied'),
(5, 6110, 'Monday', '17:25:00', '20:50:00', 'available'),
(6, 6110, 'Tuesday', '08:00:00', '08:50:00', 'available'),
(7, 6110, 'Tuesday', '09:00:00', '11:50:00', 'occupied'),
(8, 6110, 'Tuesday', '12:00:00', '13:25:00', 'available'),
(9, 6110, 'Tuesday', '13:25:00', '15:25:00', 'occupied'),
(10, 6110, 'Tuesday', '15:25:00', '20:50:00', 'available'),
(11, 6110, 'Wednesday', '08:00:00', '08:25:00', 'available'),
(12, 6110, 'Wednesday', '08:25:00', '10:25:00', 'occupied'),
(13, 6110, 'Wednesday', '10:25:00', '20:50:00', 'available'),
(14, 6110, 'Thursday', '08:00:00', '08:50:00', 'available'),
(15, 6110, 'Thursday', '09:00:00', '11:50:00', 'occupied'),
(16, 6110, 'Thursday', '12:00:00', '20:50:00', 'available'),
(17, 6110, 'Friday', '08:00:00', '08:50:00', 'available'),
(18, 6110, 'Friday', '09:00:00', '11:50:00', 'occupied'),
(19, 6110, 'Friday', '12:00:00', '13:25:00', 'available'),
(20, 6110, 'Friday', '13:25:00', '16:25:00', 'occupied'),
(21, 6110, 'Friday', '16:25:00', '20:50:00', 'available'),
(22, 6110, 'Saturday', '08:00:00', '20:50:00', 'available'),
(23, 6110, 'Sunday', '08:00:00', '20:50:00', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '1234', 'admin'),
(2, '6630611005', '1234', 'user'),
(4, '6630611006', '1234', 'user'),
(5, '6630611035', '1234', 'user'),
(6, '6630611038', '1234', 'user'),
(7, '6630611078', '1234', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `reserved_status`
--
ALTER TABLE `reserved_status`
  ADD PRIMARY KEY (`room_id`);

--
-- Indexes for table `room_details`
--
ALTER TABLE `room_details`
  ADD PRIMARY KEY (`room_id`);

--
-- Indexes for table `room_status`
--
ALTER TABLE `room_status`
  ADD PRIMARY KEY (`status_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `room_status`
--
ALTER TABLE `room_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reserved_status`
--
ALTER TABLE `reserved_status`
  ADD CONSTRAINT `reserved_status_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `room_details` (`room_id`);

--
-- Constraints for table `room_status`
--
ALTER TABLE `room_status`
  ADD CONSTRAINT `room_status_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `room_details` (`room_id`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `reset_daily_reservations` ON SCHEDULE EVERY 1 DAY STARTS '2025-04-20 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE reserved_status SET is_reserved = FALSE WHERE is_reserved = TRUE$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
