<?php
/**
 * Contact Page Setup Script
 * Run this once to create contact page tables and insert default data
 */

require_once '../customer/includes/config.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Contact Page Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 40px 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #8b4513; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; font-size: 1.1em; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; margin: 15px 0; border: 1px solid #dee2e6; }
        .btn { display: inline-block; padding: 12px 24px; background: #8b4513; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; transition: background 0.3s; }
        .btn:hover { background: #6d3410; }
        ul { margin: 15px 0 15px 25px; }
        li { margin: 8px 0; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>📧 Contact Page Setup</h1>";
echo "<p class='subtitle'>Setting up contact page tables and default data...</p>";

try {
    // Check if tables already exist
    $checkContactMessages = $pdo->query("SHOW TABLES LIKE 'contact_messages'")->rowCount();
    $checkContactSettings = $pdo->query("SHOW TABLES LIKE 'contact_page_settings'")->rowCount();
    
    if ($checkContactMessages && $checkContactSettings) {
        echo "<div class='status warning'>";
        echo "<strong>⚠️ Tables Already Exist</strong><br>";
        echo "Both <code>contact_messages</code> and <code>contact_page_settings</code> tables already exist in the database.";
        echo "</div>";
    } else {
        // Create contact_messages table
        if (!$checkContactMessages) {
            echo "<div class='status info'>Creating <code>contact_messages</code> table...</div>";
            
            $pdo->exec("CREATE TABLE `contact_messages` (
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
              KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
            
            echo "<div class='status success'>✅ <code>contact_messages</code> table created successfully!</div>";
        }
        
        // Create contact_page_settings table
        if (!$checkContactSettings) {
            echo "<div class='status info'>Creating <code>contact_page_settings</code> table...</div>";
            
            $pdo->exec("CREATE TABLE `contact_page_settings` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
            
            echo "<div class='status success'>✅ <code>contact_page_settings</code> table created successfully!</div>";
        }
    }
    
    // Insert default settings (only if table is empty)
    $settingsCount = $pdo->query("SELECT COUNT(*) FROM contact_page_settings")->fetchColumn();
    
    if ($settingsCount == 0) {
        echo "<div class='status info'>Inserting default contact page settings...</div>";
        
        $stmt = $pdo->prepare("INSERT INTO contact_page_settings 
            (setting_key, setting_value, setting_group, setting_type, display_order) VALUES 
            (?, ?, ?, ?, ?)");
        
        $defaultSettings = [
            ['page_title', 'Get in Touch', 'page_content', 'text', 1],
            ['page_subtitle', 'We would love to hear from you. Send us a message and we will respond as soon as possible.', 'page_content', 'textarea', 2],
            ['enable_contact_form', '1', 'general', 'boolean', 3],
            ['contact_address', "Purok 5 Sitio Labac Calangay 4207\nSan Nicolas, Batangas, Philippines", 'contact_info', 'textarea', 4],
            ['contact_phone_1', '+63 961 306 7957', 'contact_info', 'phone', 5],
            ['contact_phone_2', '+63 917 503 3066', 'contact_info', 'phone', 6],
            ['contact_email', 'info@mavicsresort.com', 'contact_info', 'email', 7],
            ['business_hours', "Monday - Sunday\n8:00 AM - 10:00 PM", 'contact_info', 'textarea', 8],
            ['facebook_url', 'https://www.facebook.com/p/Mavics-Resort-and-Events-Place-61550024396909/', 'social_media', 'url', 9],
            ['instagram_url', '', 'social_media', 'url', 10],
            ['map_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3872.6151491410083!2d120.9393692!3d13.9219369!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd0b3b619c22cd%3A0x1dce24db9e0c4a30!2sMavics%20Resort%20and%20Events%20Place!5e0!3m2!1sen!2sph!4v1759283766984!5m2!1sen!2sph', 'map', 'url', 11],
            ['auto_reply_enabled', '1', 'email', 'boolean', 12],
            ['auto_reply_message', 'Thank you for contacting Mavics Resort. We have received your message and will get back to you within 24 hours.', 'email', 'textarea', 13]
        ];
        
        $insertedCount = 0;
        foreach ($defaultSettings as $setting) {
            try {
                $stmt->execute($setting);
                $insertedCount++;
            } catch (PDOException $e) {
                // Skip if already exists
                continue;
            }
        }
        
        echo "<div class='status success'>✅ Inserted {$insertedCount} default settings successfully!</div>";
    } else {
        echo "<div class='status warning'>⚠️ Settings already exist ({$settingsCount} records). Skipping default data insertion.</div>";
    }
    
    // Verify tables and data
    $messagesCount = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
    $settingsCount = $pdo->query("SELECT COUNT(*) FROM contact_page_settings")->fetchColumn();
    
    echo "<div class='status success'>";
    echo "<strong>🎉 Setup Complete!</strong><br><br>";
    echo "<strong>Database Status:</strong><ul>";
    echo "<li>✅ <code>contact_messages</code> table: <strong>Ready</strong> ({$messagesCount} messages)</li>";
    echo "<li>✅ <code>contact_page_settings</code> table: <strong>Ready</strong> ({$settingsCount} settings)</li>";
    echo "</ul></div>";
    
    echo "<div class='status info'>";
    echo "<strong>📋 Next Steps:</strong><ul>";
    echo "<li>Visit the <a href='../customer/contact.php' target='_blank'><strong>Contact Page</strong></a> to see it in action</li>";
    echo "<li>Test the contact form by submitting a message</li>";
    echo "<li>Admin can manage contact messages and settings from the admin panel</li>";
    echo "</ul></div>";
    
} catch (PDOException $e) {
    echo "<div class='status error'>";
    echo "<strong>❌ Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<div class='status info'>";
    echo "<strong>💡 Troubleshooting:</strong><ul>";
    echo "<li>Make sure your database connection is working</li>";
    echo "<li>Verify database user has CREATE TABLE permissions</li>";
    echo "<li>Check the database name in config.php</li>";
    echo "</ul></div>";
}

echo "<br><a href='../customer/contact.php' class='btn'>View Contact Page</a>";
echo "<a href='../customer/index.php' class='btn' style='background: #666; margin-left: 10px;'>Back to Homepage</a>";

echo "</div></body></html>";
?>