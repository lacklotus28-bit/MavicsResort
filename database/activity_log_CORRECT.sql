-- CORRECTED SQL FOR MAVICS RESORT DATABASE
-- Your database uses 'customers' table, not 'users' table!

-- Activity Log Table (references customers table)
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
  CONSTRAINT `fk_activity_log_customer` FOREIGN KEY (`user_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- User Preferences Table (references customers table)  
CREATE TABLE IF NOT EXISTS `user_preferences_new` (
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
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_preferences_customer` FOREIGN KEY (`user_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrate data from existing user_preferences to new table
INSERT INTO user_preferences_new (user_id, email_notifications, sms_notifications, newsletter, booking_reminders, promotional_offers, created_at, updated_at)
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

-- Insert default preferences for customers who don't have preferences
INSERT IGNORE INTO user_preferences_new (user_id, email_notifications, sms_notifications, newsletter, booking_reminders, promotional_offers)
SELECT id, 1, 1, 0, 1, 0 
FROM customers 
WHERE id NOT IN (SELECT user_id FROM user_preferences_new);

-- Success message
SELECT 'Tables created successfully! You can now use the profile page.' AS Status,
       'Note: user_id in these tables refers to customers.id' AS Important_Note;