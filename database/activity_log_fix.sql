-- First, let's check the users table structure
-- Run this query to see the exact structure:
SHOW CREATE TABLE users;

-- Common causes of errno 150:
-- 1. Column types don't match (INT vs BIGINT, signed vs unsigned)
-- 2. Character sets don't match
-- 3. Referenced table doesn't exist
-- 4. Referenced column doesn't have an index

-- SOLUTION 1: Drop foreign key constraint temporarily
CREATE TABLE IF NOT EXISTS `activity_log` (
  `activity_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `activity_type` VARCHAR(50) NOT NULL,
  `activity_title` VARCHAR(255) NOT NULL,
  `activity_description` TEXT,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`activity_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_activity_type` (`activity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Then add the foreign key separately (if needed):
-- ALTER TABLE `activity_log`
-- ADD CONSTRAINT `fk_activity_log_user` 
-- FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- SOLUTION 2: Match the exact column type from users table
-- If users.user_id is UNSIGNED, use this:
CREATE TABLE IF NOT EXISTS `activity_log` (
  `activity_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `activity_type` VARCHAR(50) NOT NULL,
  `activity_title` VARCHAR(255) NOT NULL,
  `activity_description` TEXT,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`activity_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_activity_type` (`activity_type`),
  CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SOLUTION 3: If users table has different charset
-- Match the charset and collation:
CREATE TABLE IF NOT EXISTS `activity_log` (
  `activity_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `activity_type` VARCHAR(50) NOT NULL,
  `activity_title` VARCHAR(255) NOT NULL,
  `activity_description` TEXT,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`activity_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_activity_type` (`activity_type`),
  CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- User Preferences Table (also updated with potential fixes)
CREATE TABLE IF NOT EXISTS `user_preferences` (
  `preference_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `email_notifications` TINYINT(1) DEFAULT 1,
  `sms_notifications` TINYINT(1) DEFAULT 1,
  `newsletter` TINYINT(1) DEFAULT 0,
  `booking_reminders` TINYINT(1) DEFAULT 1,
  `promotional_offers` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `unique_user` (`user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key to preferences after table creation (safer):
-- ALTER TABLE `user_preferences`
-- ADD CONSTRAINT `fk_preferences_user` 
-- FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;