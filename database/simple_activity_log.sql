-- SIMPLE VERSION - NO FOREIGN KEYS - GUARANTEED TO WORK
-- Copy and paste this entire script into phpMyAdmin SQL tab and click "Go"

-- Activity Log Table
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

-- User Preferences Table
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

-- Insert default preferences for existing users
INSERT IGNORE INTO user_preferences (user_id, email_notifications, sms_notifications, newsletter, booking_reminders, promotional_offers)
SELECT user_id, 1, 1, 0, 1, 0 
FROM users 
WHERE role = 'customer';

-- Success message
SELECT 'Tables created successfully! You can now use the profile page.' AS Status;