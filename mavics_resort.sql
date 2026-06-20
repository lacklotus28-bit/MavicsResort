-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2025 at 04:15 AM
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
-- Database: `mavics_resort`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CheckVenueAvailability` (IN `p_venue_id` INT, IN `p_date` DATE)   BEGIN
    SELECT 
        v.name as venue_name,
        v.capacity,
        v.status as venue_status,
        CASE 
            WHEN b.id IS NOT NULL THEN 'BOOKED'
            WHEN vb.id IS NOT NULL THEN 'BLOCKED'
            ELSE 'AVAILABLE'
        END as availability_status,
        COALESCE(b.event_type, vb.reason) as unavailable_reason,
        b.start_time,
        b.end_time
    FROM venues v
    LEFT JOIN bookings b ON v.id = b.venue_id 
        AND b.booking_date = p_date 
        AND b.status IN ('pending', 'confirmed')
    LEFT JOIN venue_blocks vb ON v.id = vb.venue_id 
        AND vb.block_date = p_date
    WHERE v.id = p_venue_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetDashboardStats` ()   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM bookings) as total_bookings,
        (SELECT COUNT(*) FROM bookings WHERE status = 'pending') as pending_bookings,
        (SELECT COUNT(*) FROM bookings WHERE status = 'confirmed') as confirmed_bookings,
        (SELECT COUNT(*) FROM bookings WHERE status = 'completed') as completed_bookings,
        (SELECT COUNT(*) FROM bookings WHERE status = 'cancelled') as cancelled_bookings,
        (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND status != 'cancelled') as monthly_revenue,
        (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE DATE(created_at) = CURRENT_DATE() AND status != 'cancelled') as today_revenue,
        (SELECT COUNT(*) FROM customers WHERE status = 'active') as active_customers;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `about_page_content`
--

CREATE TABLE `about_page_content` (
  `id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_content` text DEFAULT NULL,
  `section_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`section_data`)),
  `section_image` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_page_content`
--

INSERT INTO `about_page_content` (`id`, `section_name`, `section_title`, `section_content`, `section_data`, `section_image`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'hero', 'Hero Section', 'About Mavics Resort', '{\"title\": \"About Mavics Resort\", \"subtitle\": \"Your Premier Events Place for Unforgettable Celebrations\"}', 'uploads/about/about_section_1_1759288843.jpg', 1, 1, '2025-10-01 02:08:37', '2025-10-01 03:20:43', 2),
(2, 'intro', 'Introduction', 'Welcome to Mavics Resort and Events Place', '{\"lead\": \"Nestled in a serene and picturesque setting, Mavics Resort and Events Place is your perfect destination for creating unforgettable memories. We specialize in hosting a wide variety of events, from intimate gatherings to grand celebrations.\", \r\n           \"description\": \"Our resort offers a beautiful blend of natural beauty and modern amenities, providing the ideal backdrop for your special occasions. Whether you are planning a wedding, birthday party, corporate event, or family reunion, our dedicated team ensures every detail is perfect.\"}', 'uploads/about/about_section_2_1759288417.jpg', 2, 1, '2025-10-01 02:08:37', '2025-10-01 03:13:37', 2),
(3, 'statistics', 'Statistics', 'Our Achievements', '{\"stats\": [\r\n           {\"number\": \"100+\", \"label\": \"Happy Clients\"},\r\n           {\"number\": \"1000+\", \"label\": \"Events Hosted\"},\r\n           {\"number\": \"5+\", \"label\": \"Years of Service\"}\r\n         ]}', NULL, 3, 1, '2025-10-01 02:08:37', '2025-10-01 03:04:02', 2),
(4, 'story', 'Our Story', 'Creating Memorable Experiences Since Day One', '{\"title\": \"A Dream Brought to Life\",\r\n           \"paragraphs\": [\r\n             \"Mavics Resort and Events Place was born from a passion to create a special venue where people can celebrate lifes most precious moments. What started as a vision has grown into one of the regions most sought-after event venues.\",\r\n             \"Our founders believed in creating not just a venue, but an experience—a place where every celebration feels magical, every guest feels welcome, and every memory lasts a lifetime. Today, we continue to uphold these values while constantly improving our facilities and services.\",\r\n             \"Through the years, we have had the privilege of hosting countless weddings, birthdays, corporate functions, and family gatherings. Each event strengthens our commitment to excellence and reminds us why we do what we do.\"\r\n           ]}', 'uploads/about/about_section_4_1759289213.jpg', 4, 1, '2025-10-01 02:08:37', '2025-10-01 03:26:53', 2),
(5, 'contact_info', 'Contact Information', 'Get in Touch', '{\"address\": \"Mavics Resort and Events Place, Tanza, Cavite, Philippines\",\n           \"phone\": \"Contact us for inquiries\",\n           \"hours\": \"Available for events daily - By reservation\",\n           \"map_embed\": \"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3872.6151491410083!2d120.9393692!3d13.9219369!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd0b3b619c22cd%3A0x1dce24db9e0c4a30!2sMavic%27s%20Resort%20and%20Events%20Place!5e0!3m2!1sen!2sph!4v1759283766984!5m2!1sen!2sph\"}', NULL, 5, 1, '2025-10-01 02:08:37', '2025-10-01 02:08:37', NULL),
(6, 'social_media', 'Social Media', 'Follow Us', '{\"facebook\": \"https://www.facebook.com/p/Mavics-Resort-and-Events-Place-61550024396909/\"}', NULL, 6, 1, '2025-10-01 02:08:37', '2025-10-01 02:08:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_title` varchar(255) NOT NULL,
  `activity_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`activity_id`, `user_id`, `activity_type`, `activity_title`, `activity_description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 5, 'profile_update', 'Profile Updated', 'You updated your profile information', NULL, NULL, '2025-10-01 19:28:54');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-26 01:22:11'),
(2, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-27 02:26:13'),
(3, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-27 02:26:37'),
(4, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-27 02:56:50'),
(5, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-27 02:57:08'),
(6, 1, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 09:26:16'),
(7, 1, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-29 14:34:23'),
(8, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-08 12:09:43'),
(9, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-08 13:39:37'),
(10, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-08 13:39:51'),
(11, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-09 00:53:45'),
(12, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-09 00:54:17'),
(13, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-09 00:54:23'),
(14, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-09 00:54:35'),
(15, 2, 'booking_status_update', 'bookings', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-09 00:58:32'),
(16, 2, 'booking_delete', 'bookings', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-09 03:31:11'),
(17, 2, 'booking_update', 'bookings', 2, '{\"id\":\"2\",\"customer_id\":\"2\",\"venue_id\":\"2\",\"package_id\":null,\"booking_date\":\"2025-03-20\",\"start_time\":\"16:00:00\",\"end_time\":\"21:00:00\",\"event_type\":\"Birthday Party\",\"guest_count\":\"80\",\"total_amount\":\"15000.00\",\"down_payment\":\"3750.00\",\"balance\":\"11250.00\",\"payment_status\":\"unpaid\",\"status\":\"pending\",\"special_requests\":null,\"created_at\":\"2025-08-26 09:17:58\",\"updated_at\":\"2025-08-29 04:27:39\"}', '{\"venue_id\":2,\"package_id\":null,\"booking_date\":\"2025-03-20\",\"start_time\":\"16:00:00\",\"end_time\":\"21:00:00\",\"event_type\":\"Corporate Event\",\"guest_count\":80,\"total_amount\":15000,\"down_payment\":3750,\"balance\":11250,\"payment_status\":\"partial\",\"status\":\"pending\",\"special_requests\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-09 04:33:27'),
(18, 2, 'booking_create', 'bookings', 5, NULL, '{\"customer_id\":4,\"venue_id\":3,\"package_id\":null,\"booking_date\":\"2025-09-16\",\"start_time\":\"15:39\",\"end_time\":\"18:39\",\"event_type\":\"Birthday Party\",\"guest_count\":20,\"total_amount\":4500,\"down_payment\":1202,\"balance\":3298,\"payment_status\":\"partial\",\"special_requests\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-09 04:39:50'),
(19, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-10 00:55:28'),
(20, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-10 00:55:28'),
(21, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-10 04:43:38'),
(22, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-11 04:47:01'),
(23, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 01:13:32'),
(24, 2, 'booking_delete', 'bookings', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 04:18:03'),
(25, 2, 'booking_delete', 'bookings', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 04:18:07'),
(26, 2, 'booking_delete', 'bookings', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 04:18:11'),
(27, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 11:18:17'),
(28, 2, 'booking_create', 'bookings', 6, NULL, '{\"customer_id\":1,\"venue_id\":3,\"package_id\":null,\"booking_date\":\"2025-09-13\",\"start_time\":\"07:14\",\"end_time\":\"12:14\",\"event_type\":\"Corporate Event\",\"guest_count\":50,\"total_amount\":7500,\"down_payment\":0,\"balance\":7500,\"payment_status\":\"unpaid\",\"special_requests\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 22:14:58'),
(29, 2, 'booking_create', 'bookings', 7, NULL, '{\"customer_id\":2,\"venue_id\":3,\"package_id\":null,\"booking_date\":\"2025-09-23\",\"start_time\":\"06:17\",\"end_time\":\"11:17\",\"event_type\":\"Corporate Event\",\"guest_count\":50,\"total_amount\":7500,\"down_payment\":0,\"balance\":7500,\"payment_status\":\"unpaid\",\"special_requests\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 22:17:42'),
(30, 2, 'manual_payment_confirmation', 'payments', 0, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 22:17:58'),
(31, 2, 'booking_create', 'bookings', 11, NULL, '{\"customer_id\":3,\"venue_id\":3,\"package_id\":null,\"booking_date\":\"2025-09-25\",\"start_time\":\"06:50\",\"end_time\":\"00:50\",\"event_type\":\"Birthday Party\",\"guest_count\":50,\"total_amount\":27000,\"down_payment\":0,\"balance\":27000,\"payment_status\":\"unpaid\",\"special_requests\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 22:50:52'),
(32, 2, 'booking_status_update', 'bookings', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 22:51:07'),
(33, 2, 'booking_delete', 'bookings', 10, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 22:52:15'),
(34, 2, 'booking_delete', 'bookings', 9, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 22:52:17'),
(35, 2, 'booking_delete', 'bookings', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 22:52:20'),
(36, 2, 'payment_status_update', 'payments', 1, '{\"payment_status\":\"pending\"}', '{\"payment_status\":\"completed\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 22:56:48'),
(37, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 23:19:14'),
(38, 2, 'reports_view', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 23:25:03'),
(39, 2, 'reports_view', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 23:27:09'),
(40, 2, 'reports_view', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 23:30:09'),
(41, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 23:51:28'),
(42, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-12 23:51:28'),
(43, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-16 01:35:14'),
(44, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 03:38:10'),
(45, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 03:38:10'),
(46, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 03:38:33'),
(47, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-21 05:48:56'),
(48, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-21 05:49:23'),
(49, 2, 'password_change', 'admin_users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-21 09:57:16'),
(50, 2, 'profile_photo_update', 'admin_users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-21 09:58:55'),
(51, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-21 10:14:28'),
(52, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-21 10:14:28'),
(53, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-23 01:11:10'),
(54, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-24 02:18:34'),
(55, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-24 02:18:45'),
(56, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-25 00:30:14'),
(57, 2, 'booking_create', 'bookings', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-25 02:15:30'),
(58, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-26 07:21:23'),
(59, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-26 07:21:47'),
(60, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 02:54:54'),
(61, 2, 'booking_status_update', 'bookings', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 02:55:27'),
(62, 2, 'booking_delete', 'bookings', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 03:04:37'),
(63, 2, 'booking_delete', 'bookings', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 03:04:49'),
(64, 2, 'booking_delete', 'bookings', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 03:04:51'),
(65, 2, 'booking_delete', 'bookings', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 03:04:53'),
(66, 2, 'booking_delete', 'bookings', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 03:04:58'),
(67, 2, 'booking_status_update', 'bookings', 14, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 03:05:14'),
(68, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 07:31:40'),
(69, 2, 'booking_delete', 'bookings', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 11:45:06'),
(70, 2, 'booking_delete', 'bookings', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 11:45:10'),
(71, 2, 'user_delete', 'customers', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 12:16:53'),
(72, 2, 'user_delete', 'customers', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 12:22:08'),
(73, 2, 'user_delete', 'customers', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 12:26:55'),
(74, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 01:31:21'),
(75, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 02:08:29'),
(76, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 03:26:18'),
(77, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 09:39:18'),
(78, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 09:44:20'),
(79, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 19:53:31'),
(80, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 20:01:36'),
(81, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 20:01:36'),
(82, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 20:01:46'),
(83, 2, 'payment_settings_update', 'payment_settings', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 20:18:16'),
(84, 2, 'payment_settings_update', 'payment_settings', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 20:24:07'),
(85, 2, 'qr_code_upload', 'payment_settings', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 20:40:37'),
(86, 2, 'payment_settings_update', 'payment_settings', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 20:40:45'),
(87, 2, 'qr_code_upload', 'payment_settings', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 20:42:51'),
(88, 2, 'payment_settings_update', 'payment_settings', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 20:42:57'),
(89, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 21:15:12'),
(90, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 21:15:12'),
(91, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 21:19:14'),
(92, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 21:19:23'),
(93, 2, 'admin_logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 21:19:23'),
(94, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 21:37:06'),
(95, 2, 'admin_login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-04 02:01:34');

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`id`, `admin_id`, `session_token`, `ip_address`, `user_agent`, `expires_at`, `created_at`) VALUES
(3, 2, '48e6e426f35b7b3762b236feb3a30f50d28bab9c8fc28bcc558ae62c0f650151', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 02:57:08', '2025-08-27 02:57:08'),
(10, 2, '93e8212be9ee7111a108fefc25276a0f0a72e382c679c7c90eac4b4616acf643', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-13 01:13:32', '2025-09-12 01:13:32'),
(11, 2, '2d6e9f38f236fa15d58b6da57483d89fc7d127eba19b02a6c9841676ed73edfc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-13 05:18:17', '2025-09-12 11:18:17'),
(17, 2, 'd991b5436304826c851a425284a5a26c9d2cc5d7199385fd27a7ed3e52af2218', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-24 20:18:45', '2025-09-24 02:18:45'),
(19, 2, '88c0944c6e722e2bcfe698083532835cc8b0aabf6601cbac87426a351b25428c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-27 01:21:47', '2025-09-26 07:21:47'),
(20, 2, 'c6109404f3d22be40c7072c8d37ea3a06b2b082f586e755cbf6ada51d4dabb99', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-30 20:54:54', '2025-09-30 02:54:54'),
(21, 2, '2b1bbed0de5031d8c045ba605bd900c9b79943c135c9fe83590dab8b592d91a7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 01:31:40', '2025-09-30 07:31:40'),
(22, 2, '78a6ca9ae0d10743fe27775038c001a30fb93da4cd88e6116855096db3614fe1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 19:31:21', '2025-10-01 01:31:21'),
(23, 2, 'd30febffbd966f5fd76b34735cede3fdfb4c093de282f9742f90c271231e5f10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 20:08:29', '2025-10-01 02:08:29'),
(24, 2, '4d404409ca7276d7579ef30a3405400b345c6d0d4b97c13cd5d9394fc1681b39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 21:26:18', '2025-10-01 03:26:18'),
(25, 2, '6b28f2b2447b45b3a515c1f53b02e09946988c9ff3c2a30b6c2ebab78c1a6338', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-02 03:39:18', '2025-10-01 09:39:18'),
(26, 2, 'cf161a0d317129832ecd73a89f9b94cd20f399868b7920b3b5b1a1b386795630', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-02 03:44:20', '2025-10-01 09:44:20'),
(30, 2, 'e54f0a103304841b669b66b1b79129807f7e7f26a578489b4c6903ec49919a5e', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-02 15:37:06', '2025-10-01 21:37:06'),
(31, 2, 'd7ee91bf62592b4e8a788fce66c2e9b9136270de0f6660f9a9911ae596442c2e', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-04 20:01:34', '2025-10-04 02:01:34');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','superadmin') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `phone`, `profile_photo`, `password`, `full_name`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@mavicsresort.com', NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin', 'active', '2025-08-28 09:26:16', '2025-08-26 01:17:58', '2025-09-21 09:59:23'),
(2, 'superadmin', 'superadmin@mavicsresort.com', '09270565953', 'uploads/profiles/admin_2_1758448735.jpg', '$2y$10$/NrJsF0HjaC78xV.B8yks.7S88YzdqigF8ryWYJd7SHFyQ08o3K9W', 'Superman', 'superadmin', 'active', '2025-10-04 02:01:34', '2025-08-26 01:17:58', '2025-10-04 02:01:34');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `venue_id` int(11) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `guest_count` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `down_payment` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('unpaid','partial','paid','refunded') DEFAULT 'unpaid',
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `venue_id`, `package_id`, `booking_date`, `start_time`, `end_time`, `event_type`, `guest_count`, `total_amount`, `down_payment`, `balance`, `payment_status`, `status`, `special_requests`, `created_at`, `updated_at`) VALUES
(12, 5, 3, NULL, '2025-09-27', '10:00:00', '15:00:00', 'Social Gathering', 40, 3000.00, 1000.00, 2000.00, 'partial', 'confirmed', '', '2025-09-25 02:15:30', '2025-09-30 02:55:27'),
(14, 1, 3, NULL, '2025-10-02', '11:03:00', '23:03:00', 'Birthday Party', 15, 7200.00, 0.00, 7200.00, 'unpaid', 'confirmed', '', '2025-09-30 03:04:04', '2025-09-30 03:05:14');

--
-- Triggers `bookings`
--
DELIMITER $$
CREATE TRIGGER `check_venue_availability` BEFORE INSERT ON `bookings` FOR EACH ROW BEGIN
    DECLARE booking_count INT DEFAULT 0;
    DECLARE block_count INT DEFAULT 0;
    
    -- Check for existing bookings on the same date
    SELECT COUNT(*) INTO booking_count
    FROM bookings 
    WHERE venue_id = NEW.venue_id 
      AND booking_date = NEW.booking_date 
      AND status IN ('pending', 'confirmed');
    
    -- Check for venue blocks on the same date
    SELECT COUNT(*) INTO block_count
    FROM venue_blocks 
    WHERE venue_id = NEW.venue_id 
      AND block_date = NEW.booking_date;
    
    IF booking_count > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Venue is already booked for this date';
    END IF;
    
    IF block_count > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Venue is not available for this date';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_booking_balance` BEFORE UPDATE ON `bookings` FOR EACH ROW BEGIN
    IF NEW.down_payment != OLD.down_payment OR NEW.total_amount != OLD.total_amount THEN
        SET NEW.balance = NEW.total_amount - COALESCE(NEW.down_payment, 0);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `booking_availability`
-- (See below for the actual view)
--
CREATE TABLE `booking_availability` (
`venue_id` int(11)
,`venue_name` varchar(100)
,`booked_date` date
,`booking_id` int(11)
,`booking_status` enum('pending','confirmed','cancelled','completed')
,`block_id` int(11)
,`block_reason` varchar(200)
);

-- --------------------------------------------------------

--
-- Table structure for table `booking_notes`
--

CREATE TABLE `booking_notes` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `note_type` enum('general','payment','status_change','cancellation','complaint','followup') DEFAULT 'general',
  `is_internal` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_rules`
--

CREATE TABLE `booking_rules` (
  `id` int(11) NOT NULL,
  `rule_name` varchar(100) NOT NULL,
  `rule_value` text NOT NULL,
  `rule_type` enum('boolean','number','text','json') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_rules`
--

INSERT INTO `booking_rules` (`id`, `rule_name`, `rule_value`, `rule_type`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'max_advance_booking_days', '365', 'number', 'Maximum days in advance a booking can be made', 1, '2025-08-28 20:27:39', '2025-08-28 20:27:39'),
(2, 'min_advance_booking_days', '7', 'number', 'Minimum days in advance a booking can be made', 1, '2025-08-28 20:27:39', '2025-08-28 20:27:39'),
(3, 'allow_same_day_booking', '0', 'boolean', 'Allow bookings on the same day', 1, '2025-08-28 20:27:39', '2025-08-28 20:27:39'),
(4, 'require_down_payment', '1', 'boolean', 'Require down payment to confirm booking', 1, '2025-08-28 20:27:39', '2025-08-28 20:27:39'),
(5, 'auto_cancel_unpaid_hours', '48', 'number', 'Auto-cancel unpaid bookings after X hours', 1, '2025-08-28 20:27:39', '2025-08-28 20:27:39'),
(6, 'exclusive_venue_booking', '1', 'boolean', 'Only one booking per venue per day', 1, '2025-08-28 20:27:39', '2025-08-28 20:27:39'),
(7, 'allow_overlapping_times', '0', 'boolean', 'Allow overlapping booking times (if exclusive booking is off)', 1, '2025-08-28 20:27:39', '2025-08-28 20:27:39');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied','archived') DEFAULT 'unread',
  `read_at` datetime DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `read_at`, `admin_reply`, `replied_by`, `replied_at`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(5, 'John Sagala Encarnacion', 'demo@email.com', '63927056595', 'General Inquiry', 'hello', 'replied', '2025-10-04 10:14:56', 'hi', 2, '2025-10-04 10:15:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-01 09:46:34', '2025-10-04 02:15:18');

-- --------------------------------------------------------

--
-- Table structure for table `contact_page_settings`
--

CREATE TABLE `contact_page_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `setting_type` enum('text','textarea','email','phone','url','boolean') DEFAULT 'text',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_page_settings`
--

INSERT INTO `contact_page_settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `setting_type`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'page_title', 'Get in Touch', 'page_content', 'text', 1, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(2, 'page_subtitle', 'We would love to hear from you. Send us a message and we will respond as soon as possible.', 'page_content', 'textarea', 2, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(3, 'enable_contact_form', '1', 'general', 'boolean', 3, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(4, 'contact_address', 'Purok 5 Sitio Labac Calangay 4207\nSan Nicolas, Batangas, Philippines', 'contact_info', 'textarea', 4, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(5, 'contact_phone_1', '+63 961 306 7957', 'contact_info', 'phone', 5, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(6, 'contact_phone_2', '+63 917 503 3066', 'contact_info', 'phone', 6, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(7, 'contact_email', 'info@mavicsresort.com', 'contact_info', 'email', 7, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(8, 'business_hours', 'Monday - Sunday\n8:00 AM - 10:00 PM', 'contact_info', 'textarea', 8, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(9, 'facebook_url', 'https://www.facebook.com/p/Mavics-Resort-and-Events-Place-61550024396909/', 'social_media', 'url', 9, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(10, 'instagram_url', '', 'social_media', 'url', 10, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(11, 'map_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3872.6151491410083!2d120.9393692!3d13.9219369!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd0b3b619c22cd%3A0x1dce24db9e0c4a30!2sMavics%20Resort%20and%20Events%20Place!5e0!3m2!1sen!2sph!4v1759283766984!5m2!1sen!2sph', 'map', 'url', 11, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(12, 'auto_reply_enabled', '1', 'email', 'boolean', 12, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28'),
(13, 'auto_reply_message', 'Thank you for contacting Mavics Resort. We have received your message and will get back to you within 24 hours.', 'email', 'textarea', 13, 1, '2025-10-01 05:28:28', '2025-10-01 05:28:28');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `login_attempts` tinyint(3) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `email`, `phone`, `address`, `date_of_birth`, `emergency_contact_name`, `emergency_contact_phone`, `password_hash`, `last_login`, `email_verified`, `email_verification_token`, `password_reset_token`, `password_reset_expires`, `status`, `created_at`, `updated_at`, `login_attempts`, `locked_until`, `two_factor_enabled`, `two_factor_secret`) VALUES
(1, 'John', 'Santos', 'john.santos@email.com', '09123456789', '123 Main St, Manila', NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 0, NULL, NULL, NULL, 'active', '2025-08-26 01:17:58', '2025-09-25 00:59:00', 0, NULL, 0, NULL),
(5, 'Demo', 'User', 'demo@email.com', '09270565951', 'Seira, Upalo, house no. 534', NULL, 'John Sagala Encarnacion', '09270565953', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-10-01 20:03:38', 1, NULL, NULL, NULL, 'active', '2025-09-25 00:59:00', '2025-10-01 20:03:38', 0, NULL, 0, NULL);

--
-- Triggers `customers`
--
DELIMITER $$
CREATE TRIGGER `update_customer_last_login` AFTER UPDATE ON `customers` FOR EACH ROW BEGIN
    IF NEW.last_login != OLD.last_login AND NEW.last_login IS NOT NULL THEN
        INSERT INTO `customer_login_history` 
        (`customer_id`, `ip_address`, `user_agent`, `login_time`) 
        VALUES 
        (NEW.id, 
         COALESCE(@login_ip, '127.0.0.1'), 
         COALESCE(@login_user_agent, 'Unknown'), 
         NEW.last_login);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `customer_login_history`
--

CREATE TABLE `customer_login_history` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 1,
  `logout_time` timestamp NULL DEFAULT NULL,
  `session_duration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_login_history`
--

INSERT INTO `customer_login_history` (`id`, `customer_id`, `ip_address`, `user_agent`, `login_time`, `success`, `logout_time`, `session_duration`) VALUES
(1, 5, '127.0.0.1', 'Unknown', '2025-09-30 02:51:39', 1, NULL, NULL),
(2, 5, '127.0.0.1', 'Unknown', '2025-09-30 12:19:09', 1, NULL, NULL),
(3, 5, '127.0.0.1', 'Unknown', '2025-10-01 01:35:15', 1, NULL, NULL),
(4, 5, '127.0.0.1', 'Unknown', '2025-10-01 01:39:05', 1, NULL, NULL),
(5, 5, '127.0.0.1', 'Unknown', '2025-10-01 01:46:26', 1, NULL, NULL),
(6, 5, '127.0.0.1', 'Unknown', '2025-10-01 01:46:36', 1, NULL, NULL),
(7, 5, '127.0.0.1', 'Unknown', '2025-10-01 05:47:00', 1, NULL, NULL),
(8, 5, '127.0.0.1', 'Unknown', '2025-10-01 09:37:14', 1, NULL, NULL),
(9, 5, '127.0.0.1', 'Unknown', '2025-10-01 09:43:49', 1, NULL, NULL),
(10, 5, '127.0.0.1', 'Unknown', '2025-10-01 10:53:57', 1, NULL, NULL),
(11, 5, '127.0.0.1', 'Unknown', '2025-10-01 10:55:34', 1, NULL, NULL),
(12, 5, '127.0.0.1', 'Unknown', '2025-10-01 11:06:47', 1, NULL, NULL),
(13, 5, '127.0.0.1', 'Unknown', '2025-10-01 19:27:03', 1, NULL, NULL),
(14, 5, '127.0.0.1', 'Unknown', '2025-10-01 20:03:38', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_payment_methods`
--

CREATE TABLE `customer_payment_methods` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_type` enum('credit_card','debit_card','bank_account','digital_wallet') NOT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `last_four_digits` varchar(4) DEFAULT NULL,
  `cardholder_name` varchar(100) DEFAULT NULL,
  `expiry_month` tinyint(2) DEFAULT NULL,
  `expiry_year` smallint(4) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `template_type` enum('booking_confirmation','payment_reminder','booking_cancelled','booking_completed','welcome') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `name`, `subject`, `body`, `template_type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'booking_confirmation', 'Booking Confirmation - Mavic\'s Resort', 'Dear {{customer_name}},\n\nThank you for booking with Mavic\'s Resort!\n\nBooking Details:\n- Event: {{event_type}}\n- Date: {{booking_date}}\n- Time: {{start_time}} - {{end_time}}\n- Venue: {{venue_name}}\n- Guests: {{guest_count}}\n- Total Amount: ₱{{total_amount}}\n\nYour booking is currently {{status}}.\n\nBest regards,\nMavic\'s Resort Team', 'booking_confirmation', 1, '2025-08-28 20:27:39', '2025-08-28 20:27:39'),
(2, 'payment_reminder', 'Payment Reminder - Mavic\'s Resort', 'Dear {{customer_name}},\n\nThis is a friendly reminder about your upcoming payment for booking #{{booking_id}}.\n\nEvent Details:\n- Event: {{event_type}}\n- Date: {{booking_date}}\n- Outstanding Balance: ₱{{balance}}\n\nPlease settle your payment at least 7 days before your event.\n\nThank you,\nMavic\'s Resort Team', 'payment_reminder', 1, '2025-08-28 20:27:39', '2025-08-28 20:27:39');

-- --------------------------------------------------------

--
-- Table structure for table `footer_settings`
--

CREATE TABLE `footer_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_group` varchar(50) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `footer_settings`
--

INSERT INTO `footer_settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'contact_address', 'Purok 5 Sitio Labac Calangay 4207 San Nicolas, Philippines', 'contact', 1, 1, '2025-10-01 03:39:14', '2025-10-01 03:39:32', 2),
(2, 'contact_phone_1', '+63 961 306 7957', 'contact', 2, 1, '2025-10-01 03:39:14', '2025-10-01 03:39:32', 2),
(3, 'contact_phone_2', '+63 917 503 3066', 'contact', 3, 1, '2025-10-01 03:39:14', '2025-10-01 03:39:32', 2),
(4, 'contact_email', 'info@mavicsresort.com', 'contact', 4, 1, '2025-10-01 03:39:14', '2025-10-01 03:39:32', 2),
(5, 'contact_hours', 'Mon-Sun: 8:00 AM - 9:00 PM', 'contact', 5, 1, '2025-10-01 03:39:14', '2025-10-01 03:39:32', 2),
(6, 'about_title', 'Mavic\'s Resort', 'about', 1, 1, '2025-10-01 03:39:14', '2025-10-01 03:39:32', 2),
(7, 'about_description', 'Your premier destination for unforgettable events. We provide exceptional venues and personalized service to make your special occasions truly memorable.', 'about', 2, 1, '2025-10-01 03:39:14', '2025-10-01 03:39:32', 2),
(8, 'social_facebook', 'https://www.facebook.com/p/Mavics-Resort-and-Events-Place-61550024396909/', 'social', 1, 1, '2025-10-01 03:39:14', '2025-10-01 03:39:32', 2),
(9, 'social_instagram', '', 'social', 2, 1, '2025-10-01 03:39:14', '2025-10-01 03:39:32', 2),
(10, 'social_twitter', '', 'social', 3, 1, '2025-10-01 03:39:14', '2025-10-01 03:39:32', 2),
(11, 'copyright_text', 'Mavic\'s Resort. All rights reserved.', 'copyright', 1, 1, '2025-10-01 03:39:14', '2025-10-01 03:39:32', 2);

-- --------------------------------------------------------

--
-- Table structure for table `gallery_images`
--

CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery_images`
--

INSERT INTO `gallery_images` (`id`, `title`, `description`, `image_path`, `category`, `tags`, `alt_text`, `is_featured`, `uploaded_by`, `created_at`, `updated_at`) VALUES
(4, 'default-venue', '', 'gallery_1758772186_0.jpg', 'general', '', 'default-venue', 0, 2, '2025-09-25 03:49:46', '2025-09-25 03:49:46');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','closed') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `email`, `success`, `attempt_time`) VALUES
(7, '::1', 'demo@email.com', 1, '2025-10-01 01:35:15'),
(8, '::1', 'demo@email.com', 1, '2025-10-01 01:39:05'),
(9, '::1', 'demo@email.com', 1, '2025-10-01 01:46:26'),
(10, '::1', 'demo@email.com', 1, '2025-10-01 01:46:36'),
(11, '::1', 'demo@email.com', 1, '2025-10-01 05:46:59'),
(12, '::1', 'demo@email.com', 1, '2025-10-01 09:37:14'),
(13, '::1', 'demo@email.com', 1, '2025-10-01 09:43:49'),
(14, '::1', 'demo@email.com', 1, '2025-10-01 10:53:57'),
(15, '::1', 'demo@email.com', 1, '2025-10-01 10:55:34'),
(16, '::1', 'demo@email.com', 1, '2025-10-01 11:06:47'),
(17, '::1', 'demo@email.com', 1, '2025-10-01 19:27:03'),
(18, '::1', 'demo@email.com', 1, '2025-10-01 20:03:38');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `venue_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_hours` int(11) DEFAULT 8,
  `max_guests` int(11) DEFAULT NULL,
  `inclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inclusions`)),
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price_type` enum('fixed','per_person','per_hour') DEFAULT 'fixed',
  `min_guests` int(11) DEFAULT NULL,
  `optional_addons` text DEFAULT NULL,
  `available_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_days`)),
  `advance_notice_days` int(11) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `venue_id`, `name`, `description`, `price`, `duration_hours`, `max_guests`, `inclusions`, `status`, `created_at`, `updated_at`, `price_type`, `min_guests`, `optional_addons`, `available_days`, `advance_notice_days`, `images`) VALUES
(2, 3, 'd', '55555e', 500.00, 8, NULL, '[\"catering\"]', 'active', '2025-09-12 14:05:40', '2025-09-12 14:05:40', 'fixed', NULL, '', '[\"monday\"]', NULL, '[\"package_1757685940_0.jpg\"]');

-- --------------------------------------------------------

--
-- Table structure for table `package_images`
--

CREATE TABLE `package_images` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','credit_card','gcash','paymaya') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `amount`, `payment_method`, `payment_status`, `reference_number`, `payment_date`, `created_at`, `updated_at`) VALUES
(5, 12, 1000.00, 'cash', 'pending', 'PAY-20250925041530-3026', '2025-09-25 02:15:30', '2025-09-25 02:15:30', '2025-09-25 02:15:30');

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','textarea','boolean','json') DEFAULT 'text',
  `setting_group` varchar(50) DEFAULT 'general',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `setting_group`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'cash_enabled', '1', 'boolean', 'payment_methods', 1, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(2, 'bank_transfer_enabled', '1', 'boolean', 'payment_methods', 2, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(3, 'gcash_enabled', '1', 'boolean', 'payment_methods', 3, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(4, 'credit_card_enabled', '0', 'boolean', 'payment_methods', 4, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(5, 'paymaya_enabled', '0', 'boolean', 'payment_methods', 5, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(6, 'bank_name', 'BDO Unibank', 'text', 'bank_details', 1, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(7, 'bank_account_name', 'Mavic\'s Resort and Events Place', 'text', 'bank_details', 2, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(8, 'bank_account_number', '0123-4567-8901', 'text', 'bank_details', 3, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(9, 'bank_branch', 'Tanza, Cavite', 'text', 'bank_details', 4, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(10, 'gcash_number', '+63 961 306 7957', 'text', 'gcash_details', 1, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(11, 'gcash_account_name', 'Mavic\'s Resort', 'text', 'gcash_details', 2, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(12, 'gcash_qr_code', 'uploads/payment/gcash_qr_1759351371.jpg', 'text', 'gcash_details', 3, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2),
(13, 'payment_instructions', 'Please use your booking ID as reference when making a payment. Send the payment confirmation to our email or WhatsApp.', 'textarea', 'general', 1, 1, '2025-10-01 20:00:21', '2025-10-01 20:42:57', 2);

-- --------------------------------------------------------

--
-- Table structure for table `payment_terms`
--

CREATE TABLE `payment_terms` (
  `id` int(11) NOT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `min_down_payment_percent` decimal(5,2) DEFAULT 10.00,
  `max_down_payment_percent` decimal(5,2) DEFAULT 50.00,
  `default_down_payment_percent` decimal(5,2) DEFAULT 25.00,
  `full_payment_days_before` int(11) DEFAULT 7,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_terms`
--

INSERT INTO `payment_terms` (`id`, `venue_id`, `event_type`, `min_down_payment_percent`, `max_down_payment_percent`, `default_down_payment_percent`, `full_payment_days_before`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Wedding Reception', 30.00, 50.00, 40.00, 7, '2025-08-28 20:27:39', '2025-08-28 20:27:39'),
(2, NULL, 'Birthday Party', 10.00, 30.00, 20.00, 7, '2025-08-28 20:27:39', '2025-08-28 20:27:39'),
(3, NULL, 'Corporate Event', 25.00, 50.00, 30.00, 7, '2025-08-28 20:27:39', '2025-08-28 20:27:39'),
(4, NULL, 'Default', 10.00, 50.00, 25.00, 7, '2025-08-28 20:27:39', '2025-08-28 20:27:39');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Mavics Resort', 'string', 'Website name', NULL, '2025-08-26 01:17:58', '2025-08-26 01:17:58'),
(2, 'site_email', 'info@mavicsresort.com', 'string', 'Main contact email', NULL, '2025-08-26 01:17:58', '2025-08-26 01:17:58'),
(3, 'site_phone', '+63 912 345 6789', 'string', 'Main contact phone', NULL, '2025-08-26 01:17:58', '2025-08-26 01:17:58'),
(4, 'booking_advance_days', '30', 'number', 'Maximum days in advance for bookings', NULL, '2025-08-26 01:17:58', '2025-08-26 01:17:58'),
(5, 'default_currency', 'PHP', 'string', 'Default currency symbol', NULL, '2025-08-26 01:17:58', '2025-08-26 01:17:58'),
(6, 'maintenance_mode', '0', 'boolean', 'Site maintenance mode', NULL, '2025-08-26 01:17:58', '2025-08-26 01:17:58');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 0,
  `booking_reminders` tinyint(1) DEFAULT 1,
  `promotional_emails` tinyint(1) DEFAULT 1,
  `payment_reminders` tinyint(1) DEFAULT 1,
  `booking_confirmations` tinyint(1) DEFAULT 1,
  `language_preference` varchar(10) DEFAULT 'en',
  `timezone` varchar(50) DEFAULT 'Asia/Manila',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `customer_id`, `email_notifications`, `sms_notifications`, `booking_reminders`, `promotional_emails`, `payment_reminders`, `booking_confirmations`, `language_preference`, `timezone`, `created_at`, `updated_at`) VALUES
(2, 5, 1, 0, 1, 1, 1, 1, 'en', 'Asia/Manila', '2025-09-25 01:41:37', '2025-09-25 01:41:37'),
(3, 1, 1, 0, 1, 1, 1, 1, 'en', 'Asia/Manila', '2025-09-25 01:41:37', '2025-09-25 01:41:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences_new`
--

CREATE TABLE `user_preferences_new` (
  `preference_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 1,
  `newsletter` tinyint(1) DEFAULT 0,
  `booking_reminders` tinyint(1) DEFAULT 1,
  `promotional_offers` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_preferences_new`
--

INSERT INTO `user_preferences_new` (`preference_id`, `user_id`, `email_notifications`, `sms_notifications`, `newsletter`, `booking_reminders`, `promotional_offers`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 0, 1, 1, 1, '2025-09-25 01:41:37', '2025-09-25 01:41:37'),
(2, 1, 1, 0, 1, 1, 1, '2025-09-25 01:41:37', '2025-09-25 01:41:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences_profile`
--

CREATE TABLE `user_preferences_profile` (
  `preference_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'References customers.id',
  `email_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 1,
  `newsletter` tinyint(1) DEFAULT 0,
  `booking_reminders` tinyint(1) DEFAULT 1,
  `promotional_offers` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Customer notification preferences for profile page';

--
-- Dumping data for table `user_preferences_profile`
--

INSERT INTO `user_preferences_profile` (`preference_id`, `user_id`, `email_notifications`, `sms_notifications`, `newsletter`, `booking_reminders`, `promotional_offers`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 0, 1, 1, 1, '2025-09-25 01:41:37', '2025-09-25 01:41:37'),
(2, 1, 1, 0, 1, 1, 1, '2025-09-25 01:41:37', '2025-09-25 01:41:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `capacity` int(11) NOT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `status` enum('available','maintenance','unavailable') DEFAULT 'available',
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location_area` varchar(100) DEFAULT NULL,
  `floor_plan_available` tinyint(1) DEFAULT 0,
  `operating_hours_start` time DEFAULT NULL,
  `operating_hours_end` time DEFAULT NULL,
  `special_features` text DEFAULT NULL,
  `minimum_booking_hours` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`id`, `name`, `description`, `capacity`, `price_per_hour`, `status`, `amenities`, `images`, `created_at`, `updated_at`, `location_area`, `floor_plan_available`, `operating_hours_start`, `operating_hours_end`, `special_features`, `minimum_booking_hours`) VALUES
(3, 'Conference Room', 'Modern conference room for business meetings', 50, 600.00, 'available', '[\"Projector\",\"Whiteboard\",\"Air Conditioning\"]', '[\"venue_1758760558_0.jpg\"]', '2025-08-26 01:17:58', '2025-09-25 00:35:58', '', 0, '00:00:00', '00:00:00', '', 1),
(4, 'Poolside Terrace', 'ffff', 5, 500.00, 'available', '[\"stage\"]', '[\"venue_1758760259_0.jpg\"]', '2025-09-12 14:06:21', '2025-09-25 00:30:59', '', 0, '00:00:00', '00:00:00', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `venue_blockout_dates`
--

CREATE TABLE `venue_blockout_dates` (
  `id` int(11) NOT NULL,
  `venue_id` int(11) NOT NULL,
  `blockout_date` date NOT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `venue_blocks`
--

CREATE TABLE `venue_blocks` (
  `id` int(11) NOT NULL,
  `venue_id` int(11) NOT NULL,
  `block_date` date NOT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `blocked_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `venue_images`
--

CREATE TABLE `venue_images` (
  `id` int(11) NOT NULL,
  `venue_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `booking_availability`
--
DROP TABLE IF EXISTS `booking_availability`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `booking_availability`  AS SELECT `v`.`id` AS `venue_id`, `v`.`name` AS `venue_name`, cast(`b`.`booking_date` as date) AS `booked_date`, `b`.`id` AS `booking_id`, `b`.`status` AS `booking_status`, `vb`.`id` AS `block_id`, `vb`.`reason` AS `block_reason` FROM ((`venues` `v` left join `bookings` `b` on(`v`.`id` = `b`.`venue_id` and `b`.`status` in ('pending','confirmed') and `b`.`booking_date` >= curdate())) left join `venue_blocks` `vb` on(`v`.`id` = `vb`.`venue_id` and `vb`.`block_date` >= curdate())) WHERE `v`.`status` = 'available' ORDER BY `v`.`name` ASC, cast(`b`.`booking_date` as date) ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_page_content`
--
ALTER TABLE `about_page_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_name` (`section_name`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_activity_type` (`activity_type`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_admin_id` (`admin_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_venue_date` (`venue_id`,`booking_date`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `venue_id` (`venue_id`),
  ADD KEY `idx_booking_date` (`booking_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `bookings_package_fk` (`package_id`),
  ADD KEY `idx_booking_date_status` (`booking_date`,`status`),
  ADD KEY `idx_venue_date` (`venue_id`,`booking_date`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_customer_bookings` (`customer_id`,`created_at`);

--
-- Indexes for table `booking_notes`
--
ALTER TABLE `booking_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_notes_booking_fk` (`booking_id`),
  ADD KEY `booking_notes_admin_fk` (`admin_id`),
  ADD KEY `idx_booking_notes_date` (`created_at`),
  ADD KEY `idx_note_type` (`note_type`);

--
-- Indexes for table `booking_rules`
--
ALTER TABLE `booking_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rule_name` (`rule_name`),
  ADD KEY `idx_active_rules` (`is_active`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `contact_page_settings`
--
ALTER TABLE `contact_page_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_group` (`setting_group`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email_status` (`email`,`status`),
  ADD KEY `idx_email_verification` (`email_verification_token`),
  ADD KEY `idx_password_reset` (`password_reset_token`);

--
-- Indexes for table `customer_login_history`
--
ALTER TABLE `customer_login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_login_history` (`customer_id`,`login_time`),
  ADD KEY `idx_ip_address` (`ip_address`);

--
-- Indexes for table `customer_payment_methods`
--
ALTER TABLE `customer_payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_payment_methods` (`customer_id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_template_name` (`name`),
  ADD KEY `idx_template_type` (`template_type`),
  ADD KEY `idx_active_templates` (`is_active`);

--
-- Indexes for table `footer_settings`
--
ALTER TABLE `footer_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`),
  ADD KEY `idx_email_time` (`email`,`attempt_time`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venue_id` (`venue_id`);

--
-- Indexes for table `package_images`
--
ALTER TABLE `package_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD KEY `idx_customer_token` (`customer_id`,`token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `idx_customer_payment_history` (`booking_id`,`payment_date`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `payment_terms`
--
ALTER TABLE `payment_terms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_terms_venue_fk` (`venue_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_customer_preferences` (`customer_id`);

--
-- Indexes for table `user_preferences_new`
--
ALTER TABLE `user_preferences_new`
  ADD PRIMARY KEY (`preference_id`),
  ADD UNIQUE KEY `unique_user` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `user_preferences_profile`
--
ALTER TABLE `user_preferences_profile`
  ADD PRIMARY KEY (`preference_id`),
  ADD UNIQUE KEY `unique_user` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `remember_token` (`remember_token`),
  ADD KEY `idx_token` (`remember_token`),
  ADD KEY `idx_user_expires` (`user_id`,`expires_at`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `venue_blockout_dates`
--
ALTER TABLE `venue_blockout_dates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_venue_blockout` (`venue_id`,`blockout_date`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `venue_blocks`
--
ALTER TABLE `venue_blocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_venue_block` (`venue_id`,`block_date`),
  ADD KEY `idx_venue_block_date` (`venue_id`,`block_date`),
  ADD KEY `venue_blocks_ibfk_2` (`blocked_by`),
  ADD KEY `idx_block_date` (`block_date`);

--
-- Indexes for table `venue_images`
--
ALTER TABLE `venue_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venue_id` (`venue_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_page_content`
--
ALTER TABLE `about_page_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `booking_notes`
--
ALTER TABLE `booking_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_rules`
--
ALTER TABLE `booking_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_page_settings`
--
ALTER TABLE `contact_page_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customer_login_history`
--
ALTER TABLE `customer_login_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `customer_payment_methods`
--
ALTER TABLE `customer_payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `footer_settings`
--
ALTER TABLE `footer_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `gallery_images`
--
ALTER TABLE `gallery_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `package_images`
--
ALTER TABLE `package_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payment_terms`
--
ALTER TABLE `payment_terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_preferences_new`
--
ALTER TABLE `user_preferences_new`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_preferences_profile`
--
ALTER TABLE `user_preferences_profile`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `venue_blockout_dates`
--
ALTER TABLE `venue_blockout_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `venue_blocks`
--
ALTER TABLE `venue_blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `venue_images`
--
ALTER TABLE `venue_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `about_page_content`
--
ALTER TABLE `about_page_content`
  ADD CONSTRAINT `about_page_content_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_activity_log_customer` FOREIGN KEY (`user_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_package_fk` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_notes`
--
ALTER TABLE `booking_notes`
  ADD CONSTRAINT `booking_notes_admin_fk` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_notes_booking_fk` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_login_history`
--
ALTER TABLE `customer_login_history`
  ADD CONSTRAINT `customer_login_history_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_payment_methods`
--
ALTER TABLE `customer_payment_methods`
  ADD CONSTRAINT `customer_payment_methods_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `footer_settings`
--
ALTER TABLE `footer_settings`
  ADD CONSTRAINT `footer_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD CONSTRAINT `gallery_images_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `admin_users` (`id`);

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `packages_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_images`
--
ALTER TABLE `package_images`
  ADD CONSTRAINT `package_images_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD CONSTRAINT `password_reset_requests_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD CONSTRAINT `payment_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_terms`
--
ALTER TABLE `payment_terms`
  ADD CONSTRAINT `payment_terms_venue_fk` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences_new`
--
ALTER TABLE `user_preferences_new`
  ADD CONSTRAINT `fk_preferences_customer` FOREIGN KEY (`user_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences_profile`
--
ALTER TABLE `user_preferences_profile`
  ADD CONSTRAINT `fk_preferences_profile_customer` FOREIGN KEY (`user_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `venue_blockout_dates`
--
ALTER TABLE `venue_blockout_dates`
  ADD CONSTRAINT `venue_blockout_dates_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `venue_blockout_dates_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `venue_blocks`
--
ALTER TABLE `venue_blocks`
  ADD CONSTRAINT `venue_blocks_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `venue_blocks_ibfk_2` FOREIGN KEY (`blocked_by`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `venue_images`
--
ALTER TABLE `venue_images`
  ADD CONSTRAINT `venue_images_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
