-- Table for Footer Settings
CREATE TABLE IF NOT EXISTS footer_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_group VARCHAR(50) NOT NULL,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default footer contact information
INSERT INTO footer_settings (setting_key, setting_value, setting_group, display_order) VALUES
-- Contact Information
('contact_address', 'Purok 5 Sitio Labac Calangay 4207 San Nicolas, Philippines', 'contact', 1),
('contact_phone_1', '+63 961 306 7957', 'contact', 2),
('contact_phone_2', '+63 917 503 3066', 'contact', 3),
('contact_email', 'info@mavicsresort.com', 'contact', 4),
('contact_hours', 'Mon-Sun: 8:00 AM - 10:00 PM', 'contact', 5),

-- About Section
('about_title', 'Mavic\'s Resort', 'about', 1),
('about_description', 'Your premier destination for unforgettable events. We provide exceptional venues and personalized service to make your special occasions truly memorable.', 'about', 2),

-- Social Media
('social_facebook', 'https://www.facebook.com/p/Mavics-Resort-and-Events-Place-61550024396909/', 'social', 1),
('social_instagram', '', 'social', 2),
('social_twitter', '', 'social', 3),

-- Copyright
('copyright_text', 'Mavic\'s Resort. All rights reserved.', 'copyright', 1);
