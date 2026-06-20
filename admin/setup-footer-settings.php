<?php
// Setup Footer Settings - One-time initialization script
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config/database.php';
$conn = getDBConnection();

$message = '';
$messageType = '';

try {
    // Check if table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'footer_settings'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create table
        $sql = "CREATE TABLE IF NOT EXISTS footer_settings (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        $message .= "✓ Footer settings table created successfully.<br>";
    }
    
    // Check if data already exists
    $stmt = $conn->query("SELECT COUNT(*) as count FROM footer_settings");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        // Insert default data
        $sql = "INSERT INTO footer_settings (setting_key, setting_value, setting_group, display_order) VALUES
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
        ('copyright_text', 'Mavic\'s Resort. All rights reserved.', 'copyright', 1)";
        
        $conn->exec($sql);
        $message .= "✓ Default footer settings inserted successfully.<br>";
        $messageType = 'success';
    } else {
        $message = "Footer settings already initialized. Found {$count} settings in database.";
        $messageType = 'info';
    }
    
} catch (PDOException $e) {
    $message = "Error: " . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Footer Settings - Mavic's Resort</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 3rem;
            max-width: 600px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header i {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #666;
        }
        
        .message {
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            line-height: 1.8;
        }
        
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .message.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-shoe-prints"></i>
            <h1>Footer Settings Setup</h1>
            <p>Initialize footer contact information system</p>
        </div>
        
        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="manage-footer.php" class="btn btn-primary">
                <i class="fas fa-cog"></i>
                Go to Footer Settings
            </a>
            <a href="admin-dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
