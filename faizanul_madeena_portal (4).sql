-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 23, 2025 at 10:38 AM
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
-- Database: `faizanul_madeena_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('Super Admin','Admin','Staff') DEFAULT 'Admin',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`user_id`, `username`, `password`, `full_name`, `role`, `last_login`, `created_at`) VALUES
(2, 'admin', '$2y$10$iT3DrTaf0qXux1VxKQWHue0zOOP67fviDIG1VpIA2Fb.DLI/ATjJy', 'System Admin', 'Super Admin', NULL, '2025-12-13 18:50:57');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `date`, `status`) VALUES
(72, 3, '2025-12-19', 'Present'),
(74, 5, '2025-12-22', 'Present'),
(75, 6, '2025-12-22', 'Present'),
(76, 6, '2025-12-23', 'Absent'),
(77, 5, '2025-12-23', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_private` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `is_private`) VALUES
(1, 'Circulars', 0),
(2, 'Student File', 0),
(3, 'Examinations', 0);

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_reminders`
--

CREATE TABLE `dashboard_reminders` (
  `id` int(11) NOT NULL,
  `reminder_date` date NOT NULL,
  `note_text` text DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` varchar(20) DEFAULT 'Normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dashboard_reminders`
--

INSERT INTO `dashboard_reminders` (`id`, `reminder_date`, `note_text`, `document_path`, `created_at`, `priority`) VALUES
(1, '2025-12-31', 'last day photo ', 'uploads/reminder_1766425074_PXL_20240423_124855972.dng', '2025-12-22 17:37:54', 'Normal'),
(2, '2025-12-22', 'we need collaborate with  new osthaths for work ', '', '2025-12-22 17:52:44', 'High'),
(3, '2025-12-31', 'asdfasfasf', '', '2025-12-22 17:53:20', 'Medium');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `doc_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `file_path` text DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT 'General',
  `file_type` varchar(10) DEFAULT NULL,
  `file_size` varchar(20) DEFAULT NULL,
  `is_starred` tinyint(1) DEFAULT 0,
  `student_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`doc_id`, `title`, `file_path`, `uploaded_by`, `uploaded_at`, `category`, `file_type`, `file_size`, `is_starred`, `student_id`) VALUES
(23, 'Birth Certificate', '694977ce76991_birth_cert.pdf', NULL, '2025-12-22 16:54:38', 'Student File', 'pdf', '929.69 KB', 0, 5),
(24, 'ID Card/NIC', '694977ce793f4_nic_copy.jpg', NULL, '2025-12-22 16:54:38', 'Student File', 'jpg', '155.22 KB', 0, 5),
(25, 'School Leaving Cert', '694977ce7b57a_leaving_cert.png', NULL, '2025-12-22 16:54:38', 'Student File', 'png', '591.52 KB', 0, 5),
(26, 'Birth Certificate', '694980b5d6b5e_birth_cert.jpg', NULL, '2025-12-22 17:32:37', 'Student File', 'jpg', '761.39 KB', 0, 6),
(27, 'ID Card/NIC', '694980b5d743f_nic_copy.jpg', NULL, '2025-12-22 17:32:37', 'Student File', 'jpg', '883.26 KB', 0, 6),
(28, 'School Leaving Cert', '694980b5d7a9e_leaving_cert.jpg', NULL, '2025-12-22 17:32:37', 'Student File', 'jpg', '14.50 MB', 0, 6),
(29, 'Medical Report', '694980b5d821d_medical_report.jpg', NULL, '2025-12-22 17:32:37', 'Student File', 'jpg', '1.81 MB', 0, 6);

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_name`, `description`, `created_at`) VALUES
(1, 'Hifz Class', NULL, '2025-12-15 15:26:41'),
(2, 'Al-Alim', NULL, '2025-12-15 15:26:41'),
(3, 'Al-Alimah', NULL, '2025-12-15 15:26:41'),
(4, 'Qiraat Course', NULL, '2025-12-15 15:26:41');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `job_role` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `admission_no` varchar(20) DEFAULT NULL,
  `class_year` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated') DEFAULT 'Active',
  `admission_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `health_issues` text DEFAULT NULL,
  `photo` text DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `full_name`, `gender`, `dob`, `blood_group`, `admission_no`, `class_year`, `status`, `admission_date`, `created_at`, `phone`, `emergency_phone`, `email`, `address`, `health_issues`, `photo`, `father_name`, `mother_name`) VALUES
(5, 'fazil', 'Male', '2006-11-15', 'A+', '2025/01', 'Al-Alim 1st Year', 'Active', '2025-12-22', '2025-12-22 16:54:38', '0777777777', '0777777777', 'fazil@gmail.com', '57 , sadaam hussain village , meera kerny, eravur', '', '42049208-0dc6-463b-b1a1-690bccc6d12f.jpg', 'saliheen', 'mubeene'),
(6, 'fathimah', 'Female', '2013-02-04', 'AB+', '2025/002', 'Al-Alimah 1st Year', 'Active', '2025-12-22', '2025-12-22 17:32:37', '0757654321', '0757654321', 'fathi@gmail.com', 'afcagaeghaesdcsadcagscx da', 'asfasfasf', 'Screenshot 2025-01-07 011156.png', 'saliheen', 'mubeena'),
(7, 'ahmad sahd', 'Male', '2014-05-21', 'A-', '2025/003', 'Hifz Class 1st Year', 'Active', '2025-12-23', '2025-12-23 09:17:02', '12345678910', '12345678910', 'sahd@gmail.coma', 'asdafs', 'afsczxedczx', '694993ec5e263.dng', 'ajmeer', 'mujahida');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `subject_specialty` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `unique_username` (`username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `dashboard_reminders`
--
ALTER TABLE `dashboard_reminders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`doc_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_name` (`program_name`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `dashboard_reminders`
--
ALTER TABLE `dashboard_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `doc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
