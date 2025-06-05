-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 03, 2025 at 07:10 PM
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
-- Database: `myparttimedb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `name`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$c0cIvO9l.TrPmNsQyplb4.n6QPwiSBN1vYqf7F6F/7E.yoaQ85/m6', 'Administrator', 'admin@example.com', '2025-03-23 22:57:27'),
(2, 'newadmin', '$2y$10$aBcDeFgHiJkLmNoPqRsTuVwXyZaBcDeFgHiJkLmNoPqRsTuVwX', 'ผู้ดูแลระบบใหม่', 'newadmin@example.com', '2025-03-24 01:09:11');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `apply_date` datetime NOT NULL,
  `status` varchar(20) NOT NULL,
  `cover_letter` text NOT NULL,
  `additional_info` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  `expected_salary` decimal(10,2) DEFAULT NULL,
  `available_start_date` date DEFAULT NULL,
  `available_hours` varchar(255) DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `required_documents` text DEFAULT NULL,
  `submitted_documents` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `post_id`, `student_id`, `apply_date`, `status`, `cover_letter`, `additional_info`, `message`, `expected_salary`, `available_start_date`, `available_hours`, `resume_path`, `created_at`, `updated_at`, `required_documents`, `submitted_documents`) VALUES
(1, 4, 3, '2025-04-01 09:19:58', 'accepted', '', NULL, '1', 1.00, '2025-04-04', '1', NULL, '2025-04-01 07:19:58', '2025-04-30 00:31:29', NULL, NULL),
(2, 5, 3, '2025-04-03 07:55:08', 'rejected', '', NULL, 'sd', 1.00, '2025-04-04', 'fd', NULL, '2025-04-03 05:55:08', '2025-04-04 08:15:56', NULL, NULL),
(3, 6, 3, '2025-04-03 09:04:46', 'available', '', NULL, 'ด', 1.00, '2025-04-04', 'ด', NULL, '2025-04-03 07:04:46', '2025-04-28 19:01:28', NULL, NULL),
(4, 4, 4, '2025-04-04 10:08:01', 'available', '', NULL, 'sdf', 100.00, '2025-04-05', 'sdf', NULL, '2025-04-04 08:08:01', '2025-04-28 19:01:28', NULL, NULL),
(8, 8, 3, '2025-04-28 19:51:52', 'available', '', NULL, 'cvb', 4.00, '2025-04-29', 'vc', NULL, '2025-04-28 17:51:52', '2025-04-28 18:00:43', NULL, NULL),
(9, 5, 4, '2025-04-28 21:16:22', 'rejected', '', NULL, 'เ้', 2.00, '2025-04-29', '้่', NULL, '2025-04-28 19:16:22', '2025-04-28 19:39:19', NULL, NULL),
(10, 6, 4, '2025-04-29 21:00:46', 'interview', '', NULL, 'sd', 56.00, '2025-04-30', 'sd', NULL, '2025-04-29 19:00:46', '2025-04-29 19:36:57', NULL, NULL),
(11, 8, 4, '2025-04-30 05:59:38', 'interview', '', NULL, 'sad', 10.00, '2025-04-02', 'sda', NULL, '2025-04-30 03:59:38', '2025-04-30 04:25:39', NULL, NULL),
(12, 11, 4, '2025-04-30 06:13:24', 'reviewing', '', NULL, 'ไม่มี', 11000.00, '2025-05-01', 'ได้ตามที่แจ้งไว้', NULL, '2025-04-30 04:13:24', '2025-04-30 04:13:45', NULL, NULL),
(13, 12, 4, '2025-04-30 06:25:09', 'request_documents', '', '', 'ไม่มี', 10000.00, '2025-05-01', 'อาทิยต์', '/uploads/resumes/resume_13_1746081738.pdf', '2025-04-30 04:25:09', '2025-05-02 17:45:00', 's', 'myparttime/uploads/documents/6814ff63333ce_applications_report_2025-04-30 (3).xlsx,myparttime/uploads/documents/6814ffb9869d1_applications_report_2025-04-30 (3).xlsx,myparttime/uploads/documents/6814ffea0945a_applications_report_2025-04-30 (3).xlsx,uploads/documents/6815002dc1467_applications_report_2025-04-30 (3).xlsx,uploads/documents/6815002dc1708_รายงานข้อมูลบริษัท-2025-04-30.pdf,uploads/documents/6815002dc18ce_รายงานข้อมูลบริษัท-2025-04-30.xls,uploads/documents/6815005c14b9e_applications_report_2025-04-30 (3).xlsx');

-- --------------------------------------------------------

--
-- Table structure for table `application_status_history`
--

CREATE TABLE `application_status_history` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `changed_by_type` varchar(20) DEFAULT NULL,
  `changed_by_name` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `application_status_history`
--

INSERT INTO `application_status_history` (`id`, `application_id`, `old_status`, `new_status`, `status`, `comment`, `created_at`, `created_by`, `changed_by_type`, `changed_by_name`, `notes`) VALUES
(1, 1, NULL, NULL, 'reviewing', '', '2025-04-03 12:35:38', 1, NULL, NULL, NULL),
(2, 1, NULL, NULL, 'reviewing', 'hhh', '2025-04-03 12:48:23', 1, NULL, NULL, NULL),
(3, 1, NULL, NULL, 'reviewing', '', '2025-04-03 12:48:41', 1, NULL, NULL, NULL),
(4, 2, NULL, NULL, 'reviewing', '', '2025-04-03 13:09:26', 1, NULL, NULL, NULL),
(5, 2, NULL, NULL, 'reviewing', '', '2025-04-03 13:37:46', 1, NULL, NULL, NULL),
(6, 3, 'pending', 'accepted', 'accepted', '', '2025-04-03 17:53:59', 1, 'company', NULL, NULL),
(7, 1, 'reviewing', 'interview', 'interview', 'นัดสัมภาษณ์วันที่ 2025-04-04', '2025-04-04 10:03:19', 1, 'company', NULL, NULL),
(8, 1, 'reviewing', 'interview', 'interview', 'นัดสัมภาษณ์วันที่ 2025-04-04', '2025-04-04 10:04:27', 1, 'company', NULL, NULL),
(9, 1, 'reviewing', 'interview', 'interview', 'นัดสัมภาษณ์วันที่ 2025-04-05', '2025-04-04 14:55:00', 1, 'company', NULL, NULL),
(10, 4, 'pending', 'reviewing', 'reviewing', '', '2025-04-04 15:12:52', 1, 'company', NULL, NULL),
(11, 4, 'reviewing', 'interview', 'interview', '', '2025-04-04 15:13:44', 1, 'company', NULL, NULL),
(12, 2, 'reviewing', 'rejected', 'rejected', '', '2025-04-04 15:15:56', 1, 'company', NULL, NULL),
(13, 4, 'reviewing', 'interview', 'interview', 'นัดสัมภาษณ์วันที่ 2025-04-05', '2025-04-04 15:16:34', 1, 'company', NULL, NULL),
(14, 4, 'reviewing', 'interview', 'interview', 'นัดสัมภาษณ์วันที่ 2025-04-04', '2025-04-04 15:24:03', 1, 'company', NULL, NULL),
(15, 1, 'reviewing', 'interview', 'interview', 'นัดสัมภาษณ์วันที่ 2025-04-05', '2025-04-05 16:45:47', 1, 'company', NULL, NULL),
(16, 1, 'reviewing', 'interview', 'interview', 'ปดิกด', '2025-04-05 20:06:10', 1, 'company', NULL, NULL),
(17, 1, 'accepted', 'accepted', 'accepted', 'asfas', '2025-04-06 13:33:18', 1, 'company', NULL, NULL),
(18, 1, 'accepted', 'rejected', 'rejected', '', '2025-04-06 15:26:48', 1, 'company', NULL, NULL),
(19, 1, 'rejected', 'accepted', 'accepted', '', '2025-04-06 15:28:20', 1, 'company', NULL, NULL),
(20, 1, 'accepted', 'rejected', 'rejected', '', '2025-04-06 15:28:30', 1, 'company', NULL, NULL),
(21, 4, 'reviewing', 'accepted', 'accepted', '', '2025-04-06 15:28:49', 1, 'company', NULL, NULL),
(22, 4, 'accepted', 'rejected', 'rejected', '', '2025-04-06 15:29:05', 1, 'company', NULL, NULL),
(23, 4, 'rejected', 'accepted', 'accepted', '', '2025-04-06 15:29:30', 1, 'company', NULL, NULL),
(24, 1, 'rejected', 'accepted', 'accepted', '', '2025-04-18 15:04:02', 1, 'company', NULL, NULL),
(26, 8, 'pending', 'accepted', 'accepted', '', '2025-04-29 00:52:45', 1, 'company', NULL, NULL),
(27, 1, 'completed', 'accepted', 'accepted', '', '2025-04-29 00:52:58', 1, 'company', NULL, NULL),
(28, 1, 'available', 'accepted', 'accepted', '', '2025-04-29 02:19:00', 1, 'company', NULL, NULL),
(29, 9, 'pending', 'reviewing', 'reviewing', '', '2025-04-29 02:34:29', 1, 'company', NULL, NULL),
(30, 9, 'reviewing', 'rejected', 'rejected', '', '2025-04-29 02:39:19', 1, 'company', NULL, NULL),
(31, 1, 'available', 'rejected', 'rejected', '', '2025-04-29 21:07:58', 1, 'company', NULL, NULL),
(32, 1, 'rejected', 'accepted', 'accepted', '', '2025-04-29 21:09:28', 1, 'company', NULL, NULL),
(33, 1, NULL, NULL, 'available', 'การจ้างงานสิ้นสุดโดยบริษัท', '2025-04-30 01:51:48', 1, 'company', NULL, NULL),
(46, 10, 'pending', 'reviewing', 'reviewing', '', '2025-04-30 02:36:49', 1, 'company', NULL, NULL),
(47, 10, 'reviewing', 'interview', 'interview', '', '2025-04-30 02:36:57', 1, 'company', NULL, NULL),
(48, 1, 'available', 'accepted', 'accepted', 'ax', '2025-04-30 02:38:48', 1, 'company', NULL, NULL),
(49, 1, 'available', 'accepted', 'accepted', '', '2025-04-30 07:31:29', 1, 'company', NULL, NULL),
(50, 12, 'pending', 'reviewing', 'reviewing', '', '2025-04-30 11:13:45', 1, 'company', NULL, NULL),
(51, 11, 'pending', 'interview', 'interview', '', '2025-04-30 11:25:39', 1, 'company', NULL, NULL),
(52, 10, 'reviewing', 'interview', 'interview', 'นัดสัมภาษณ์วันที่ 2025-04-01', '2025-04-30 11:26:17', 1, 'company', NULL, NULL),
(53, 13, 'pending', 'request_documents', 'request_documents', '', '2025-05-03 00:07:48', 1, 'company', NULL, NULL),
(54, 13, 'request_documents', 'reviewing', 'reviewing', '', '2025-05-03 00:35:56', 1, 'company', NULL, NULL),
(55, 13, 'reviewing', 'request_documents', 'request_documents', '', '2025-05-03 00:37:04', 1, 'company', NULL, NULL),
(56, 13, 'request_documents', 'reviewing', 'reviewing', '', '2025-05-03 00:37:45', 1, 'company', NULL, NULL),
(57, 13, 'reviewing', 'request_documents', 'request_documents', '', '2025-05-03 00:39:11', 1, 'company', NULL, NULL),
(58, 13, 'request_documents', 'reviewing', 'reviewing', '', '2025-05-03 00:41:03', 1, 'company', NULL, NULL),
(59, 13, 'reviewing', 'reviewing', 'reviewing', '', '2025-05-03 00:43:57', 1, 'company', NULL, NULL),
(60, 13, 'reviewing', 'request_documents', 'request_documents', '', '2025-05-03 00:45:00', 1, 'company', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `company_id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `tax_id` varchar(13) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `business_type` varchar(50) NOT NULL,
  `company_type` varchar(50) NOT NULL,
  `business_sector` varchar(100) DEFAULT NULL,
  `company_desc` text NOT NULL,
  `address` text NOT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_person` varchar(50) NOT NULL,
  `contact_email` text NOT NULL,
  `contact_phone` varchar(15) NOT NULL,
  `pdpa_consent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `logo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='is_verified: 0=รอพิจารณา, 1=พร้อมทำงาน, 2=ไม่ผ่านการพิจารณา';

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`company_id`, `company_name`, `tax_id`, `username`, `password`, `business_type`, `company_type`, `business_sector`, `company_desc`, `address`, `province`, `postal_code`, `website`, `contact_person`, `contact_email`, `contact_phone`, `pdpa_consent`, `created_at`, `registration_date`, `updated_at`, `is_verified`, `logo_path`) VALUES
(1, 'บริษัท ตัวอย่าง จำกัด', '1234567890123', 'example_company', '$2y$10$c0cIvO9l.TrPmNsQyplb4.n6QPwiSBN1vYqf7F6F/7E.yoaQ85/m6', 'ธุรกิจการผลิต', 'ห้างหุ้นส่วนจำกัด', 'การเงิน', 'บริษัทให้บริการด้านเทคโนโลยีสารสนเทศและการพัฒนาซอฟต์แวร์', '123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย', 'กรุงเทพมหานคร', '10110', 'https://www.google.co.th/', 'คุณ สมชาย ใจดี', 'a@gmail.com', '0952154430', 1, '2025-03-31 08:06:27', '2025-03-31 08:06:27', '2025-04-19 20:25:36', 1, '/uploads/company/company_1_1744892511.jpg'),
(2, 'asfdsc1', '1234567891234', 'safvas', '$2y$10$JaGm/Jl.X0ff9J.06UqnMuHK65a0iuVbHoDHoK3ESPJURUR6uxFoW', 'ธุรกิจบริการ', 'บริษัทจำกัด', 'อสังหาริมทรัพย์และการก่อสร้าง', 'ฟหด', 'asf', 'ตาก', '45345', 'https://www.google.co.th/', 'asgsdh', 'bankan75@gmail.com', '0123456789', 1, '2025-04-02 08:27:00', '2025-04-02 08:27:00', '2025-05-01 12:09:18', 2, NULL),
(3, 'บรืษัท ดีดี จำกัด', '1111111111111', 'sodeee', '$2y$10$YX1WBFNJSxYb464sakP6qOq52/4Sf/8rh6FvRG4fB8CRa9yck0Dkm', 'ธุรกิจบริการ', 'ห้างหุ้นส่วนจำกัด', 'เทคโนโลยีสารสนเทศ', 'ยังไม่มี', '248 ซอยเพชรเกษม 110 ถนนเพชรเกษม\r\nแขวงหนองค้างพลู เขตหนองแขม\r\nกรุงเทพมหานคร 10160', 'กรุงเทพมหานคร', '10160', 'https://www.google.com/search?q=%E0%B9%80%E0%B8%84%E0%B8%A3%E0%B8%B7%E0%B9%88%E0%B8%AD%E0%B8%87%E0%B8%84%E0%B8%B4%E0%B8%94%E0%B9%80%E0%B8%A5%E0%B8%82&oq=%E0%B9%80%E0%B8%84%E0%B8%A3%E0%B8%B7%E0%B9%88%E0%B8%AD%E0%B8%87%E0%B8%84%E0%B8%B4%E0%B8%94%E0%B9%80%E0', 'สมชาย', 'b@gmail.com', '028090823', 1, '2025-04-30 05:44:23', '2025-04-30 05:44:23', NULL, 0, NULL),
(4, 'ฟห', '1234567891299', 'asdasd', '$2y$10$Al0GtLbSAgwXjdoWCg9Y9OscBdp7RhPiZvbQMeabEkJ9x2ZwmWCSW', 'ธุรกิจบริการ', 'บริษัทมหาชนจำกัด', 'การผลิตและอุตสาหกรรม', 'saf', 'asd', 'เชียงราย', '12345', 'https://www.google.com/search?q=%E0%B9%81%E0%B8%9B%E0%B8%A5%E0%B9%84%E0%B8%97%E0%B8%A2&sca_esv=28365adf0b6f92db&sxsrf=AHTn8zqlp_Grfe12AkMaMriun4Qem-phEQ%3A1745982050503&ei=YpIRaMXBHt2F4-EPiPvmMQ&ved=0ahUKEwjF-p3_4f6MAxXdwjgGHYi9OQYQ4dUDCBA&uact=5&oq=%E0%B', 'asd', 'f@gmail.com', '1234567890', 1, '2025-04-30 06:02:10', '2025-04-30 06:02:10', NULL, 0, NULL),
(5, 'ทำดี จำกัด', '1234562345698', 'ssssss', '$2y$10$ub1EVOW.4e.TcU3ESUcuQeOi08p/Y2p2oRO.Hh2aPRQtGfuQxvol.', 'ธุรกิจการค้า', 'บริษัทมหาชนจำกัด', 'การเงินและการธนาคาร', 'ไม่มี', 'ไม่มี', 'กรุงเทพมหานคร', '10160', 'https://www.google.com/search?q=%E0%B9%81%E0%B8%9B%E0%B8%A5%E0%B9%84%E0%B8%97%E0%B8%A2&sca_esv=28365adf0b6f92db&sxsrf=AHTn8zqlp_Grfe12AkMaMriun4Qem-phEQ%3A1745982050503&ei=YpIRaMXBHt2F4-EPiPvmMQ&ved=0ahUKEwjF-p3_4f6MAxXdwjgGHYi9OQYQ4dUDCBA&uact=5&oq=%E0%B', 'สมจิตร', 'c@gmail.com', '0956321456', 1, '2025-04-30 06:05:20', '2025-04-30 06:05:20', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employments`
--

CREATE TABLE `employments` (
  `employment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `position` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL,
  `termination_reason` varchar(100) DEFAULT NULL,
  `termination_comment` text DEFAULT NULL,
  `termination_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `employments`
--

INSERT INTO `employments` (`employment_id`, `student_id`, `company_id`, `position`, `start_date`, `salary`, `status`, `termination_reason`, `termination_comment`, `termination_date`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'a', '2025-04-04', 1.00, 'terminated', 'sa', 'sa', '2025-04-29', '2025-04-29 14:47:30', '2025-04-29 18:51:48'),
(2, 3, 1, 'a', '2025-04-04', 1.00, 'terminated', 'สิ้นสุดสัญญา', 'sa', '2025-04-29', '2025-04-29 19:39:10', '2025-04-29 19:50:24'),
(3, 3, 1, 'a', '2025-04-04', 1.00, 'accepted', NULL, NULL, NULL, '2025-04-30 00:31:50', '2025-04-30 00:31:50');

-- --------------------------------------------------------

--
-- Table structure for table `employment_history`
--

CREATE TABLE `employment_history` (
  `history_id` int(11) NOT NULL,
  `employment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `action_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `employment_history`
--

INSERT INTO `employment_history` (`history_id`, `employment_id`, `student_id`, `company_id`, `action`, `reason`, `comment`, `action_date`, `created_at`) VALUES
(1, 1, 3, 1, 'terminated', 'sa', 'sa', '2025-04-29', '2025-04-29 18:51:48'),
(2, 2, 3, 1, 'terminated', 'สิ้นสุดสัญญา', 'sa', '2025-04-29', '2025-04-29 19:50:24');

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `interview_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `interview_date` date NOT NULL,
  `interview_time` time DEFAULT NULL,
  `interview_type` varchar(50) DEFAULT NULL,
  `interview_location` varchar(100) DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `interviews`
--

INSERT INTO `interviews` (`interview_id`, `application_id`, `interview_date`, `interview_time`, `interview_type`, `interview_location`, `interview_notes`, `status`, `updated_at`) VALUES
(6, 1, '2025-04-30', '16:45:00', 'in-person', 'dfg', 'tjtr', 'completed', '2025-05-01 02:44:59'),
(7, 10, '2025-05-01', '11:26:00', 'in-person', 'ไกล้ฉัน', 'ไม่มี', 'scheduled', '2025-05-01 02:45:11');

-- --------------------------------------------------------

--
-- Table structure for table `jobs_posts`
--

CREATE TABLE `jobs_posts` (
  `post_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `job_title` varchar(100) NOT NULL,
  `job_description` varchar(100) DEFAULT NULL,
  `positions` text NOT NULL,
  `min_salary` decimal(10,2) DEFAULT NULL,
  `max_salary` decimal(10,2) NOT NULL,
  `salary_type` decimal(20,0) DEFAULT NULL,
  `work_days` varchar(50) DEFAULT NULL,
  `work_hours` varchar(50) DEFAULT NULL,
  `requirement` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `post_date` date NOT NULL,
  `expire_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `is_active` int(11) NOT NULL,
  `job_category` varchar(50) DEFAULT 'อื่นๆ',
  `update_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `jobs_posts`
--

INSERT INTO `jobs_posts` (`post_id`, `company_id`, `job_title`, `job_description`, `positions`, `min_salary`, `max_salary`, `salary_type`, `work_days`, `work_hours`, `requirement`, `location`, `post_date`, `expire_date`, `status`, `is_active`, `job_category`, `update_date`) VALUES
(4, 1, 'จัดเรียงของ', 'จัดเรียงของ', '1', 10000.00, 30000.00, 2, 'จันทร์-เสาร์', '18:00-00:00', 'แข็งแรง', 'ไกล้ฉัน', '2025-03-31', '2025-05-02', 'เปิดรับสมัคร', 1, 'ค้าปลีก', '2025-04-30 04:16:01'),
(5, 1, 'นั่งเครื่อง', 'เป็น call center คอยตอบลูกค้า', '1', 12000.00, 15000.00, 2, 'พุธ-อาทิยต์', '18:00-00:00', 'มีความใจเย็น', 'แถวๆนี้', '2025-04-03', '2025-04-28', 'เปิดรับสมัคร', 1, 'สำนักงาน', '2025-04-30 04:16:20'),
(6, 1, 'แจกใบปลิว', 'แจกใบปลิวที่สถานที่ ที่กำหนด', '1', 10000.00, 12000.00, 1, 'ทุกวัน', '9:00 - 15:00', 'มีความอดทน', 'ไกล้ๆฉัน', '2025-04-03', '2025-05-01', 'เปิดรับสมัคร', 1, 'อื่นๆ', '2025-04-30 04:17:58'),
(8, 1, 'พนักงานเสริฟอาหาร', 'คอยบริการลูกค้า', '1', 11000.00, 15000.00, 2, 'จันทร์-เสาร์', '18:00-00:00', 'ไม่มี', 'ไกล้ฉัน', '2025-04-20', '2025-05-01', 'เปิดรับสมัคร', 1, 'ร้านอาหาร', '2025-04-30 04:19:08'),
(11, 1, 'ส่งของ', 'ไม่มี', '2', 10000.00, 15000.00, 3, 'อังคาร-อาทิตย์', '09:00 - 17:00', 'มีใบขับขี่', 'ไกล้ฉัน', '2025-04-30', '2025-05-01', 'เปิดรับสมัคร', 1, 'ส่งของ', '2025-04-30 04:11:06'),
(12, 1, 'พนักงานแจกของ', 'ยืนแจกของ\r\nเดินแจกของ', '5', 10000.00, 12000.00, 2, 'เสาร์-อาทิตย์', '08:00 - 18:00', 'แข็งแรง สุภาพ ใจเย็น', 'แถวๆนี้', '2025-04-30', '2025-05-01', 'เปิดรับสมัคร', 1, 'จัดงานอีเวนท์', '2025-04-30 04:23:38');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"b\" ได้เปลี่ยนเป็น \"กำลังพิจารณา\"', 0, '2025-04-29 19:36:49'),
(2, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"b\" ได้เปลี่ยนเป็น \"นัดสัมภาษณ์\"', 0, '2025-04-29 19:36:57'),
(3, 3, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"a\" ได้เปลี่ยนเป็น \"ผ่านการคัดเลือก\"', 0, '2025-04-29 19:38:48'),
(4, 3, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"a\" ได้เปลี่ยนเป็น \"ผ่านการคัดเลือก\"', 0, '2025-04-30 00:31:29'),
(5, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"ส่งของ\" ได้เปลี่ยนเป็น \"กำลังพิจารณา\"', 0, '2025-04-30 04:13:45'),
(6, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"พนักงานเสริฟอาหาร\" ได้เปลี่ยนเป็น \"นัดสัมภาษณ์\"', 0, '2025-04-30 04:25:39'),
(7, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"พนักงานแจกของ\" ได้เปลี่ยนเป็น \"request_documents\"', 0, '2025-05-02 17:07:48'),
(8, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"พนักงานแจกของ\" ได้เปลี่ยนเป็น \"กำลังพิจารณา\"', 0, '2025-05-02 17:35:56'),
(9, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"พนักงานแจกของ\" ได้เปลี่ยนเป็น \"request_documents\"', 0, '2025-05-02 17:37:04'),
(10, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"พนักงานแจกของ\" ได้เปลี่ยนเป็น \"กำลังพิจารณา\"', 0, '2025-05-02 17:37:45'),
(11, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"พนักงานแจกของ\" ได้เปลี่ยนเป็น \"request_documents\"', 0, '2025-05-02 17:39:11'),
(12, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"พนักงานแจกของ\" ได้เปลี่ยนเป็น \"กำลังพิจารณา\"', 0, '2025-05-02 17:41:03'),
(13, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"พนักงานแจกของ\" ได้เปลี่ยนเป็น \"กำลังพิจารณา\"', 0, '2025-05-02 17:43:57'),
(14, 4, 'application_status', 'สถานะใบสมัครของคุณสำหรับตำแหน่ง \"พนักงานแจกของ\" ได้เปลี่ยนเป็น \"request_documents\"', 0, '2025-05-02 17:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` int(11) NOT NULL,
  `user_type` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `verification_type` varchar(10) NOT NULL,
  `verification_value` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `user_type`, `user_id`, `verification_type`, `verification_value`, `otp_code`, `expires_at`, `is_verified`, `created_at`) VALUES
(53, 'company', 2, 'phone', '0952154430', '303618', '2025-04-17 18:15:36', 0, '2025-04-17 16:00:36'),
(71, 'company', 2, 'email', 'bankzapr12345@gmail.com', '465506', '2025-04-17 19:46:41', 0, '2025-04-17 17:31:41'),
(88, 'company', 1, 'email', 'bankan75@gmail.com', '536974', '2025-04-18 08:26:47', 0, '2025-04-18 06:11:47'),
(92, 'company', 1, 'phone', '0123456789', '507379', '2025-04-18 08:35:57', 0, '2025-04-18 06:20:57'),
(101, 'student', 4, 'phone', '0952154430', '381784', '2025-04-19 05:50:05', 0, '2025-04-19 03:35:05'),
(103, 'student', 4, 'email', 'ggameopp7@gmail.com', '678542', '2025-05-03 08:11:30', 1, '2025-05-03 05:56:30');

-- --------------------------------------------------------

--
-- Table structure for table `resignation_requests`
--

CREATE TABLE `resignation_requests` (
  `request_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `resignation_date` date NOT NULL,
  `submit_date` datetime NOT NULL,
  `processed_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resignation_requests`
--

INSERT INTO `resignation_requests` (`request_id`, `application_id`, `student_id`, `company_id`, `reason`, `resignation_date`, `submit_date`, `processed_date`, `status`, `comment`) VALUES
(1, 1, 3, 1, 'sad', '2025-04-30', '2025-04-29 00:53:37', '2025-04-29 00:55:31', 'approved', 'dsf'),
(2, 8, 3, 1, 'df', '2025-04-29', '2025-04-29 01:00:33', '2025-04-29 01:00:43', 'approved', ''),
(3, 4, 4, 1, 'sdc', '2025-04-29', '2025-04-29 01:52:05', '2025-04-29 01:52:20', 'approved', 'axcs'),
(4, 1, 3, 1, 'faa', '2025-04-29', '2025-04-29 21:03:05', '2025-04-29 21:03:20', 'approved', 'as'),
(5, 1, 3, 1, 'ฆ', '2025-04-30', '2025-04-30 01:43:10', '2025-04-30 01:52:08', 'rejected', ''),
(6, 1, 3, 1, 'as', '2025-04-30', '2025-04-30 11:30:35', NULL, 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `student_code` varchar(13) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `title` varchar(10) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `university_name` varchar(100) DEFAULT NULL,
  `faculty_name` varchar(100) DEFAULT NULL,
  `major_name` varchar(100) DEFAULT NULL,
  `education_level` varchar(50) DEFAULT NULL,
  `year` int(5) DEFAULT NULL,
  `gpa` decimal(4,2) DEFAULT NULL,
  `employment_status` varchar(20) DEFAULT 'unemployed',
  `skill` text DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `student_code`, `password`, `title`, `first_name`, `last_name`, `birth_date`, `email`, `phone`, `university_name`, `faculty_name`, `major_name`, `education_level`, `year`, `gpa`, `employment_status`, `skill`, `experience`, `address`, `province`, `postal_code`, `updated_at`, `profile_image`) VALUES
(3, '6501103071016', '$2y$10$c0cIvO9l.TrPmNsQyplb4.n6QPwiSBN1vYqf7F6F/7E.yoaQ85/m6', 'นาย', 'สรายุทธ', 'ศรีชุม', '2025-03-01', 'bankzapr12345@gmail.com', '0123456789', 'มหาวิทยาลัยธนบุรี', 'วิทยาศาสตร์และเทคโนโลยี', 'วิทยาการข้อมูลและเทคโนโลยีสารสนเทศ', 'ปริญญาตรี', 3, 3.00, 'employed', 'ax', 'scd', NULL, NULL, NULL, '2025-04-30 07:31:50', NULL),
(4, '6501103071055', '$2y$10$c0cIvO9l.TrPmNsQyplb4.n6QPwiSBN1vYqf7F6F/7E.yoaQ85/m6', 'นาย', 'โอเล่', 'สีแจ่ม', '2025-03-01', 'ggameopp7@gmail.com', '0123456787', 'มหาวิทยาลัยธนบุรี', 'วิทยาศาสตร์และเทคโนโลยี', 'โลจิสติก', 'ปริญญาตรี', 3, 3.00, 'unemployed', 'sdb', 'dsgb', 'ฟหด', 'กรุงเทพมหานคร', '10111', '2025-05-03 12:56:35', '/uploads/student/student_4_1744957608.jpg'),
(6, '6501103071001', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นาย', 'วิชัย', 'มั่นคง', '2003-05-15', 'wichai.m@example.com', '0891234567', 'มหาวิทยาลัยธนบุรี', 'วิทยาศาสตร์และเทคโนโลยี', 'วิทยาการข้อมูลและเทคโนโลยีสารสนเทศ', 'ปริญญาตรี', 2, 3.45, 'unemployed', 'Python, SQL, Data Analysis, Machine Learning', 'ฝึกงานที่บริษัท Data Analytics Thailand 3 เดือน', NULL, NULL, NULL, NULL, NULL),
(7, '6501103071002', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นางสาว', 'ปิยะดา', 'แสงทอง', '2003-08-21', 'piyada.s@example.com', '0872345678', 'มหาวิทยาลัยธนบุรี', 'วิทยาศาสตร์และเทคโนโลยี', 'วิทยาการข้อมูลและเทคโนโลยีสารสนเทศ', 'ปริญญาตรี', 2, 3.78, 'unemployed', 'R, Data Visualization, Statistics, Big Data', 'โครงการวิจัยด้านการวิเคราะห์ข้อมูล Big Data ร่วมกับอาจารย์', NULL, NULL, NULL, NULL, NULL),
(8, '6501103071003', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นาย', 'ธนกร', 'พงษ์เทพ', '2003-11-10', 'thanakorn.p@example.com', '0863456789', 'มหาวิทยาลัยธนบุรี', 'วิทยาศาสตร์และเทคโนโลยี', 'เทคโนโลยีสารสนเทศ', 'ปริญญาตรี', 3, 3.25, 'unemployed', 'Web Development, JavaScript, PHP, MySQL', 'พัฒนาเว็บไซต์ให้กับร้านค้าในท้องถิ่น', NULL, NULL, NULL, NULL, NULL),
(9, '6501103071004', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นางสาว', 'กมลชนก', 'แก้วมณี', '2003-03-17', 'kamolchanok.k@example.com', '0854567890', 'มหาวิทยาลัยธนบุรี', 'วิทยาศาสตร์และเทคโนโลยี', 'เทคโนโลยีสารสนเทศ', 'ปริญญาตรี', 3, 3.52, 'unemployed', 'Network Administration, Cybersecurity, Linux', 'ฝึกอบรมด้านความปลอดภัยไซเบอร์กับบริษัท Security Solutions', NULL, NULL, NULL, NULL, NULL),
(10, '6501103071005', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นาย', 'วรพล', 'สุขสมบูรณ์', '2003-06-05', 'worapol.s@example.com', '0895678901', 'มหาวิทยาลัยธนบุรี', 'บริหารธุรกิจ', 'การจัดการ', 'ปริญญาตรี', 3, 3.15, 'unemployed', 'Project Management, Business Analysis, Microsoft Office', 'ฝึกงานที่แผนกบริหารโครงการ บริษัท BizSolutions', NULL, NULL, NULL, NULL, NULL),
(11, '6501103071006', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นางสาว', 'ณัฐธิดา', 'ดวงแก้ว', '2003-09-22', 'nattida.d@example.com', '0886789012', 'มหาวิทยาลัยธนบุรี', 'บริหารธุรกิจ', 'การจัดการ', 'ปริญญาตรี', 3, 3.65, 'unemployed', 'Human Resources, Communication, Leadership', 'กิจกรรมสโมสรนักศึกษา ตำแหน่งเลขานุการ', NULL, NULL, NULL, NULL, NULL),
(12, '6501103071007', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นาย', 'ภาณุวัฒน์', 'ศรีวิไล', '2003-07-14', 'panuwat.s@example.com', '0877890123', 'มหาวิทยาลัยธนบุรี', 'บริหารธุรกิจ', 'การตลาด', 'ปริญญาตรี', 3, 3.40, 'unemployed', 'Digital Marketing, Social Media Management, Content Creation', 'ช่วยวางแผนการตลาดให้กับธุรกิจร้านกาแฟท้องถิ่น', NULL, NULL, NULL, NULL, NULL),
(13, '6501103071008', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นางสาว', 'จิราพร', 'ใจดี', '2003-02-28', 'jiraporn.j@example.com', '0868901234', 'มหาวิทยาลัยธนบุรี', 'บริหารธุรกิจ', 'การตลาด', 'ปริญญาตรี', 3, 3.85, 'unemployed', 'Market Research, Brand Management, Public Relations', 'ฝึกงานกับบริษัทโฆษณาชั้นนำในกรุงเทพ', NULL, NULL, NULL, NULL, NULL),
(14, '6501103071009', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นาย', 'ธีรพงศ์', 'สมบัติ', '2003-04-25', 'teerapong.s@example.com', '0859012345', 'มหาวิทยาลัยธนบุรี', 'วิศวกรรมศาสตร์', 'วิศวกรรมคอมพิวเตอร์', 'ปริญญาตรี', 3, 3.70, 'unemployed', 'Software Development, Java, C++, Mobile Apps', 'พัฒนาแอปพลิเคชันมือถือในโครงการประกวดนวัตกรรมนักศึกษา', NULL, NULL, NULL, NULL, NULL),
(15, '6501103071010', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นางสาว', 'อรวรรณ', 'เรืองรัตน์', '2003-10-18', 'orawan.r@example.com', '0840123456', 'มหาวิทยาลัยธนบุรี', 'วิศวกรรมศาสตร์', 'วิศวกรรมคอมพิวเตอร์', 'ปริญญาตรี', 3, 3.95, 'unemployed', 'AI/ML, Computer Vision, Python, TensorFlow', 'วิจัยด้าน Computer Vision ร่วมกับอาจารย์', NULL, NULL, NULL, NULL, NULL),
(16, '6501103071011', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นาย', 'ณัฐพล', 'อินทร์สว่าง', '2003-01-12', 'nattapon.i@example.com', '0831234567', 'มหาวิทยาลัยธนบุรี', 'วิศวกรรมศาสตร์', 'วิศวกรรมไฟฟ้า', 'ปริญญาตรี', 3, 3.30, 'unemployed', 'Circuit Design, Electronics, IoT, Arduino', 'โครงงานพัฒนาระบบ IoT สำหรับการเกษตร', NULL, NULL, NULL, NULL, NULL),
(17, '6501103071012', '$2y$10$FyeSLQlGa10GjF.tqfCEaOcsm4j0RhN9lW0yneFltEJA0mYSFrz7K', 'นางสาว', 'สุนิสา', 'แสงจันทร์', '2003-12-03', 'sunisa.s@example.com', '0822345678', 'มหาวิทยาลัยธนบุรี', 'วิศวกรรมศาสตร์', 'วิศวกรรมไฟฟ้า', 'ปริญญาตรี', 3, 3.60, 'unemployed', 'Power Systems, Renewable Energy, Automation', 'ฝึกงานที่การไฟฟ้าส่วนภูมิภาค ฝ่ายพลังงานทดแทน', NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `Students` (`student_id`),
  ADD KEY `Jobs_Posts` (`post_id`) USING BTREE;

--
-- Indexes for table `application_status_history`
--
ALTER TABLE `application_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`company_id`),
  ADD UNIQUE KEY `company_name` (`company_name`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `tax_id` (`tax_id`),
  ADD UNIQUE KEY `email` (`contact_email`) USING HASH;

--
-- Indexes for table `employments`
--
ALTER TABLE `employments`
  ADD PRIMARY KEY (`employment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `employment_history`
--
ALTER TABLE `employment_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `employment_id` (`employment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`interview_id`),
  ADD KEY `Applications` (`application_id`),
  ADD KEY `interview_date` (`interview_date`);

--
-- Indexes for table `jobs_posts`
--
ALTER TABLE `jobs_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `companies` (`company_id`) USING BTREE;

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resignation_requests`
--
ALTER TABLE `resignation_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `gmail` (`email`),
  ADD UNIQUE KEY `student_code_idx` (`student_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `application_status_history`
--
ALTER TABLE `application_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employments`
--
ALTER TABLE `employments`
  MODIFY `employment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employment_history`
--
ALTER TABLE `employment_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `jobs_posts`
--
ALTER TABLE `jobs_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `resignation_requests`
--
ALTER TABLE `resignation_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `jobs_posts_app` FOREIGN KEY (`post_id`) REFERENCES `jobs_posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `students_app` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `application_status_history`
--
ALTER TABLE `application_status_history`
  ADD CONSTRAINT `status_history_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employments`
--
ALTER TABLE `employments`
  ADD CONSTRAINT `employment_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `employment_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employment_history`
--
ALTER TABLE `employment_history`
  ADD CONSTRAINT `hist_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `hist_employment` FOREIGN KEY (`employment_id`) REFERENCES `employments` (`employment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `hist_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `jobs_posts`
--
ALTER TABLE `jobs_posts`
  ADD CONSTRAINT `companies` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
