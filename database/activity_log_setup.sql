-- Activity Log Table for tracking user activities
-- Run this SQL to create the activity_log table

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
  CONSTRAINT `fk_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default preferences for existing users
INSERT IGNORE INTO user_preferences (user_id, email_notifications, sms_notifications, newsletter, booking_reminders, promotional_offers)
SELECT user_id, 1, 1, 0, 1, 0 FROM users WHERE role = 'customer';

-- Sample activity log entries (optional - for testing)
-- INSERT INTO activity_log (user_id, activity_type, activity_title, activity_description, created_at)
-- SELECT user_id, 'login', 'Account Login', 'You logged into your account', NOW() 
-- FROM users WHERE role = 'customer' LIMIT 1;