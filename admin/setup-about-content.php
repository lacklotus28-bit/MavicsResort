<?php
// Setup About Content - Initialize Database
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

$conn = getDBConnection();
$messages = [];

try {
    // Create table
    $createTableSQL = "CREATE TABLE IF NOT EXISTS about_page_content (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($createTableSQL);
    $messages[] = "✓ Table 'about_page_content' created successfully";
    
    // Check if data already exists
    $checkStmt = $conn->query("SELECT COUNT(*) FROM about_page_content");
    $count = $checkStmt->fetchColumn();
    
    if ($count == 0) {
        // Insert default data
        $insertSQL = "INSERT INTO about_page_content (section_name, section_title, section_content, section_data, display_order) VALUES
        ('hero', 'Hero Section', 'About Mavics Resort', 
         '{\"title\": \"About Mavics Resort\", \"subtitle\": \"Your Premier Events Place for Unforgettable Celebrations\"}', 1),
        
        ('intro', 'Introduction', 'Welcome to Mavics Resort and Events Place', 
         '{\"lead\": \"Nestled in a serene and picturesque setting, Mavics Resort and Events Place is your perfect destination for creating unforgettable memories. We specialize in hosting a wide variety of events, from intimate gatherings to grand celebrations.\", 
           \"description\": \"Our resort offers a beautiful blend of natural beauty and modern amenities, providing the ideal backdrop for your special occasions. Whether you are planning a wedding, birthday party, corporate event, or family reunion, our dedicated team ensures every detail is perfect.\"}', 2),
        
        ('statistics', 'Statistics', 'Our Achievements', 
         '{\"stats\": [
           {\"number\": \"500+\", \"label\": \"Happy Clients\"},
           {\"number\": \"1000+\", \"label\": \"Events Hosted\"},
           {\"number\": \"5+\", \"label\": \"Years of Service\"}
         ]}', 3),
        
        ('story', 'Our Story', 'Creating Memorable Experiences Since Day One', 
         '{\"title\": \"A Dream Brought to Life\",
           \"paragraphs\": [
             \"Mavics Resort and Events Place was born from a passion to create a special venue where people can celebrate lifes most precious moments. What started as a vision has grown into one of the regions most sought-after event venues.\",
             \"Our founders believed in creating not just a venue, but an experience—a place where every celebration feels magical, every guest feels welcome, and every memory lasts a lifetime. Today, we continue to uphold these values while constantly improving our facilities and services.\",
             \"Through the years, we have had the privilege of hosting countless weddings, birthdays, corporate functions, and family gatherings. Each event strengthens our commitment to excellence and reminds us why we do what we do.\"
           ]}', 4),
        
        ('contact_info', 'Contact Information', 'Get in Touch', 
         '{\"address\": \"Mavics Resort and Events Place, Tanza, Cavite, Philippines\",
           \"phone\": \"Contact us for inquiries\",
           \"hours\": \"Available for events daily - By reservation\",
           \"map_embed\": \"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3872.6151491410083!2d120.9393692!3d13.9219369!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd0b3b619c22cd%3A0x1dce24db9e0c4a30!2sMavic%27s%20Resort%20and%20Events%20Place!5e0!3m2!1sen!2sph!4v1759283766984!5m2!1sen!2sph\"}', 5),
        
        ('social_media', 'Social Media', 'Follow Us', 
         '{\"facebook\": \"https://www.facebook.com/p/Mavics-Resort-and-Events-Place-61550024396909/\"}', 6)";
        
        $conn->exec($insertSQL);
        $messages[] = "✓ Default content inserted successfully";
    } else {
        $messages[] = "ℹ Content already exists ($count sections found)";
    }
    
    $success = true;
    
} catch (PDOException $e) {
    $messages[] = "✗ Error: " . $e->getMessage();
    $success = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup About Content - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #8b4513 0%, #d4a574 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .setup-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #8b4513;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .messages {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .message {
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .message:last-child {
            margin-bottom: 0;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #8b4513;
            color: white;
        }
        
        .btn-primary:hover {
            background: #a0522d;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .status-icon {
            font-size: 3rem;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .status-icon.success {
            color: #28a745;
        }
        
        .status-icon.error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="status-icon <?php echo $success ? 'success' : 'error'; ?>">
            <i class="fas fa-<?php echo $success ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        </div>
        
        <h1><?php echo $success ? 'Setup Complete!' : 'Setup Failed'; ?></h1>
        
        <div class="messages">
            <?php foreach ($messages as $message): ?>
                <?php
                $class = 'info';
                if (strpos($message, '✓') !== false) {
                    $class = 'success';
                } elseif (strpos($message, '✗') !== false) {
                    $class = 'error';
                }
                ?>
                <div class="message <?php echo $class; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="actions">
            <?php if ($success): ?>
                <a href="manage-about.php" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Manage About Content
                </a>
            <?php else: ?>
                <a href="setup-about-content.php" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Try Again
                </a>
            <?php endif; ?>
            
            <a href="admin-dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
