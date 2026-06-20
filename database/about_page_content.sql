-- Table for About Page Content Management
CREATE TABLE IF NOT EXISTS about_page_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_name VARCHAR(100) NOT NULL UNIQUE,
    section_title VARCHAR(255) NOT NULL,
    section_content TEXT,
    section_data JSON,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default content for About page
INSERT INTO about_page_content (section_name, section_title, section_content, section_data, display_order) VALUES
('hero', 'Hero Section', 'About Mavic\'s Resort', 
 '{"title": "About Mavic\'s Resort", "subtitle": "Your Premier Events Place for Unforgettable Celebrations"}', 1),

('intro', 'Introduction', 'Welcome to Mavic\'s Resort and Events Place', 
 '{"lead": "Nestled in a serene and picturesque setting, Mavic\'s Resort and Events Place is your perfect destination for creating unforgettable memories. We specialize in hosting a wide variety of events, from intimate gatherings to grand celebrations.", 
   "description": "Our resort offers a beautiful blend of natural beauty and modern amenities, providing the ideal backdrop for your special occasions. Whether you\'re planning a wedding, birthday party, corporate event, or family reunion, our dedicated team ensures every detail is perfect."}', 2),

('statistics', 'Statistics', 'Our Achievements', 
 '{"stats": [
   {"number": "500+", "label": "Happy Clients"},
   {"number": "1000+", "label": "Events Hosted"},
   {"number": "5+", "label": "Years of Service"}
 ]}', 3),

('story', 'Our Story', 'Creating Memorable Experiences Since Day One', 
 '{"title": "A Dream Brought to Life",
   "paragraphs": [
     "Mavic\'s Resort and Events Place was born from a passion to create a special venue where people can celebrate life\'s most precious moments. What started as a vision has grown into one of the region\'s most sought-after event venues.",
     "Our founders believed in creating not just a venue, but an experience—a place where every celebration feels magical, every guest feels welcome, and every memory lasts a lifetime. Today, we continue to uphold these values while constantly improving our facilities and services.",
     "Through the years, we\'ve had the privilege of hosting countless weddings, birthdays, corporate functions, and family gatherings. Each event strengthens our commitment to excellence and reminds us why we do what we do."
   ]}', 4),

('contact_info', 'Contact Information', 'Get in Touch', 
 '{"address": "Mavic\'s Resort and Events Place, Tanza, Cavite, Philippines",
   "phone": "Contact us for inquiries",
   "hours": "Available for events daily - By reservation",
   "map_embed": "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3872.6151491410083!2d120.9393692!3d13.9219369!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd0b3b619c22cd%3A0x1dce24db9e0c4a30!2sMavic%27s%20Resort%20and%20Events%20Place!5e0!3m2!1sen!2sph!4v1759283766984!5m2!1sen!2sph"}', 5),

('social_media', 'Social Media', 'Follow Us', 
 '{"facebook": "https://www.facebook.com/p/Mavics-Resort-and-Events-Place-61550024396909/"}', 6);
