-- Table for Contact Messages
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied', 'archived') DEFAULT 'unread',
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    replied_at TIMESTAMP NULL,
    admin_notes TEXT,
    admin_reply TEXT,
    replied_by INT,
    FOREIGN KEY (replied_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for Contact Page Settings
CREATE TABLE IF NOT EXISTS contact_page_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default contact page settings
INSERT INTO contact_page_settings (setting_key, setting_value) VALUES
('page_title', 'Get in Touch'),
('page_subtitle', 'We\'d love to hear from you. Send us a message and we\'ll respond as soon as possible.'),
('contact_email', 'info@mavicsresort.com'),
('contact_phone_1', '+63 961 306 7957'),
('contact_phone_2', '+63 917 503 3066'),
('contact_address', 'Purok 5 Sitio Labac Calangay 4207 San Nicolas, Philippines'),
('business_hours', 'Monday - Sunday: 8:00 AM - 10:00 PM'),
('map_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3872.6151491410083!2d120.9393692!3d13.9219369!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd0b3b619c22cd%3A0x1dce24db9e0c4a30!2sMavic%27s%20Resort%20and%20Events%20Place!5e0!3m2!1sen!2sph!4v1759283766984!5m2!1sen!2sph'),
('facebook_url', 'https://www.facebook.com/p/Mavics-Resort-and-Events-Place-61550024396909/'),
('instagram_url', ''),
('enable_contact_form', '1'),
('auto_reply_enabled', '1'),
('auto_reply_message', 'Thank you for contacting Mavic\'s Resort. We have received your message and will get back to you within 24 hours.');
