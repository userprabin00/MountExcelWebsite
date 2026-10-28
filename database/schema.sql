-- Mount Excel Boarding School Database Schema
-- Production Ready UTF-8 (utf8mb4) Database Setup

CREATE DATABASE IF NOT EXISTS `mount_excel_school` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `mount_excel_school`;

-- --------------------------------------------------------
-- Table: rate_limits
-- IP-based limits for public submissions and admin login
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip` VARCHAR(45) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rate_limits_lookup` (`ip`, `action`, `created_at`),
    INDEX `idx_rate_limits_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: admins
-- Secure administrative users with password hashing and lockout protection
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('super_admin', 'admin', 'staff') NOT NULL DEFAULT 'admin',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `failed_logins` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `lockout_until` DATETIME NULL,
    `last_login` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_username` (`username`),
    INDEX `idx_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: admissions
-- Online student admission applications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `application_no` VARCHAR(30) NOT NULL UNIQUE,
    `full_name` VARCHAR(120) NOT NULL,
    `dob` DATE NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `grade` VARCHAR(20) NOT NULL,
    `parent_name` VARCHAR(120) NULL,
    `parent_contact` VARCHAR(30) NOT NULL,
    `parent_email` VARCHAR(120) NULL,
    `address` TEXT NOT NULL,
    `previous_school` VARCHAR(150) NULL,
    `status` ENUM('pending', 'reviewed', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    `admin_notes` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admissions_app_no` (`application_no`),
    INDEX `idx_admissions_grade` (`grade`),
    INDEX `idx_admissions_status` (`status`),
    INDEX `idx_admissions_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: contact_messages
-- Public inquiries and contact submissions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(120) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `subject` VARCHAR(150) NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('unread', 'read', 'replied', 'archived') NOT NULL DEFAULT 'unread',
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_contact_status` (`status`),
    INDEX `idx_contact_email` (`email`),
    INDEX `idx_contact_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: activity_logs
-- Administrative and security audit trails
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_logs_admin` (`admin_id`),
    INDEX `idx_logs_action` (`action`),
    INDEX `idx_logs_created` (`created_at`),
    CONSTRAINT `fk_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: settings
-- Application key-value configurations and admission settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial admission settings defaults
INSERT INTO `settings` (`setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
('admission_open', 'on', NOW(), NOW()),
('admission_start_date', '2026-12-15', NOW(), NOW()),
('admission_end_date', '2027-04-13', NOW(), NOW()),
('admission_closed_message', 'Admission is currently closed. Applications for the 2084 session open on 15 December 2026. For queries call +977-9851148834.', NOW(), NOW()),
('admission_grade_end_dates', '{\n    \"Play Group\": \"2027-04-13\",\n    \"Nursery\": \"2027-04-13\",\n    \"LKG\": \"2027-04-13\",\n    \"UKG\": \"2027-04-13\",\n    \"Grade 1\": \"2027-04-13\",\n    \"Grade 2\": \"2027-04-13\",\n    \"Grade 3\": \"2027-04-13\",\n    \"Grade 4\": \"2027-04-13\",\n    \"Grade 5\": \"2027-04-30\",\n    \"Grade 6\": \"2027-04-30\",\n    \"Grade 7\": \"2027-04-30\",\n    \"Grade 8\": \"2027-04-30\"\n}', NOW(), NOW())
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- --------------------------------------------------------
-- Table: gallery_items
-- Admin-managed photo gallery entries per album
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gallery_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `album` ENUM('merit','excursion','art','general') NOT NULL DEFAULT 'general',
    `filename` VARCHAR(120) NOT NULL COMMENT 'Stored under /uploads/gallery/',
    `title` VARCHAR(150) NOT NULL DEFAULT '',
    `caption` TEXT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_gallery_album` (`album`),
    INDEX `idx_gallery_visible` (`is_visible`),
    INDEX `idx_gallery_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: staff_members
-- Admin-managed teacher and staff profiles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_members` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `position` VARCHAR(120) NOT NULL,
    `bio` TEXT NULL,
    `photo_filename` VARCHAR(120) NULL COMMENT 'Stored under /uploads/staff/',
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_staff_visible` (`is_visible`),
    INDEX `idx_staff_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
