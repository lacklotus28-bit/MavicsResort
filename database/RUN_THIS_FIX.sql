-- ========================================
-- MAVICS RESORT - PROFILE PAGE FIX
-- Run this entire script in phpMyAdmin
-- ========================================

-- Activity Log Table
-- References: customers.id (NOT users!)
CREATE TABLE IF NOT EXISTS `activity_log` (
  `activity_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL COMMENT 'References customers.id',
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
  CONSTRAINT `fk_activity_log_customer` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `customers` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Tracks customer activities (login, profile updates, bookings, etc.)';

-- User Preferences Table (New Version)
-- References: customers.id (NOT users!)
CREATE TABLE IF NOT EXISTS `user_preferences_profile` (
  `preference_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL COMMENT 'References customers.id',
  `email_notifications` TINYINT(1) DEFAULT 1,
  `sms_notifications` TINYINT(1) DEFAULT 1,
  `newsletter` TINYINT(1) DEFAULT 0,
  `booking_reminders` TINYINT(1) DEFAULT 1,
  `promotional_offers` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `unique_user` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_preferences_profile_customer` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `customers` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Customer notification preferences for profile page';

-- Migrate data from existing user_preferences table (if it exists)
INSERT IGNORE INTO user_preferences_profile 
  (user_id, email_notifications, sms_notifications, newsletter, booking_reminders, promotional_offers, created_at, updated_at)
SELECT 
    customer_id, 
    email_notifications, 
    sms_notifications, 
    promotional_emails, 
    booking_reminders, 
    promotional_emails,
    created_at,
    updated_at
FROM user_preferences
WHERE customer_id IN (SELECT id FROM customers);

-- Insert default preferences for customers who don't have any yet
INSERT IGNORE INTO user_preferences_profile 
  (user_id, email_notifications, sms_notifications, newsletter, booking_reminders, promotional_offers)
SELECT id, 1, 1, 0, 1, 0 
FROM customers 
WHERE id NOT IN (SELECT user_id FROM user_preferences_profile);

-- Sample activity entries (optional - for testing)
-- Uncomment the lines below if you want some test data
/*
INSERT INTO activity_log (user_id, activity_type, activity_title, activity_description)
SELECT id, 'login', 'Account Login', 'You logged into your account'
FROM customers 
LIMIT 5;
*/

-- Verify tables were created
SELECT 'SUCCESS: Tables created!' AS Status;
SELECT COUNT(*) AS total_customers FROM customers;
SELECT COUNT(*) AS preferences_created FROM user_preferences_profile;
SELECT COUNT(*) AS activity_logs FROM activity_log;

-- Display table structures
SHOW CREATE TABLE activity_log;
SHOW CREATE TABLE user_preferences_profile;