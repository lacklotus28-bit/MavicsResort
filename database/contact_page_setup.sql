-- Contact Page Tables Setup

-- Table for storing contact messages
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread', 'read', 'replied', 'archived') DEFAULT 'unread',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `replied_at` TIMESTAMP NULL DEFAULT NULL,
  `replied_by` INT(11) DEFAULT NULL,
  `admin_reply` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_replied_by` (`replied_by`),
  CONSTRAINT `fk_contact_replied_by` FOREIGN KEY (`replied_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for contact page settings
CREATE TABLE IF NOT EXISTS `contact_page_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `setting_group` VARCHAR(50) DEFAULT 'general',
  `setting_type` ENUM('text', 'textarea', 'email', 'phone', 'url', 'boolean') DEFAULT 'text',
  `display_order` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_setting_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default contact page settings
INSERT IGNORE INTO `contact_page_settings` (`setting_key`, `setting_value`, `setting_group`, `setting_type`, `display_order`) VALUES
('page_title', 'Get in Touch', 'page_content', 'text', 1),
('page_subtitle', 'We\'d love to hear from you. Send us a message and we\'ll respond as soon as possible.', 'page_content', 'textarea', 2),
('enable_contact_form', '1', 'general', 'boolean', 3),
('contact_address', 'Purok 5 Sitio Labac Calangay 4207\nSan Nicolas, Batangas, Philippines', 'contact_info', 'textarea', 4),
('contact_phone_1', '+63 961 306 7957', 'contact_info', 'phone', 5),
('contact_phone_2', '+63 917 503 3066', 'contact_info', 'phone', 6),
('contact_email', 'info@mavicsresort.com', 'contact_info', 'email', 7),
('business_hours', 'Monday - Sunday\n8:00 AM - 10:00 PM', 'contact_info', 'textarea', 8),
('facebook_url', 'https://www.facebook.com/p/Mavics-Resort-and-Events-Place-61550024396909/', 'social_media', 'url', 9),
('instagram_url', '', 'social_media', 'url', 10),
('map_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3872.6151491410083!2d120.9393692!3d13.9219369!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd0b3b619c22cd%3A0x1dce24db9e0c4a30!2sMavic\\'s%20Resort%20and%20Events%20Place!5e0!3m2!1sen!2sph!4v1759283766984!5m2!1sen!2sph', 'map', 'url', 11),
('auto_reply_enabled', '1', 'email', 'boolean', 12),
('auto_reply_message', 'Thank you for contacting Mavic\\'s Resort. We have received your message and will get back to you within 24 hours.', 'email', 'textarea', 13);