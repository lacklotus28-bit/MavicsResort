-- Payment Settings Table for Admin Management
CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','textarea','boolean','json') DEFAULT 'text',
  `setting_group` varchar(50) DEFAULT 'general',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `payment_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default payment settings
INSERT INTO `payment_settings` (`setting_key`, `setting_value`, `setting_type`, `setting_group`, `display_order`, `is_active`) VALUES
('cash_enabled', '1', 'boolean', 'payment_methods', 1, 1),
('bank_transfer_enabled', '1', 'boolean', 'payment_methods', 2, 1),
('gcash_enabled', '1', 'boolean', 'payment_methods', 3, 1),
('credit_card_enabled', '0', 'boolean', 'payment_methods', 4, 1),
('paymaya_enabled', '0', 'boolean', 'payment_methods', 5, 1),

('bank_name', 'BDO Unibank', 'text', 'bank_details', 1, 1),
('bank_account_name', 'Mavic\'s Resort and Events Place', 'text', 'bank_details', 2, 1),
('bank_account_number', '0123-4567-8901', 'text', 'bank_details', 3, 1),
('bank_branch', 'Tanza, Cavite', 'text', 'bank_details', 4, 1),

('gcash_number', '+63 961 306 7957', 'text', 'gcash_details', 1, 1),
('gcash_account_name', 'Mavic\'s Resort', 'text', 'gcash_details', 2, 1),
('gcash_qr_code', '', 'text', 'gcash_details', 3, 1),

('payment_instructions', 'Please use your booking ID as reference when making a payment. Send the payment confirmation to our email or WhatsApp.', 'textarea', 'general', 1, 1);
