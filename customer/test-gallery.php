<?php
// Test Gallery Data - Check if gallery_images table has data
require_once '../admin/config/database.php';

try {
    $conn = getDBConnection();
    
    echo "<h2>Gallery Images Table Check</h2>";
    
    // Check if table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'gallery_images'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo "<p style='color:red;'>❌ gallery_images table does NOT exist!</p>";
        echo "<p>Creating table...</p>";
        
        // Create the table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS gallery_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                image_path VARCHAR(255) NOT NULL,
                category VARCHAR(100),
                tags TEXT,
                alt_text VARCHAR(255),
                is_featured BOOLEAN DEFAULT 0,
                uploaded_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (uploaded_by) REFERENCES admin_users(id)
            )
        ");
        
        echo "<p style='color:green;'>✅ Table created successfully!</p>";
    } else {
        echo "<p style='color:green;'>✅ gallery_images table exists!</p>";
    }
    
    // Check for data
    $stmt = $conn->query("SELECT COUNT(*) as count FROM gallery_images");
    $count = $stmt->fetch()['count'];
    
    echo "<p><strong>Total images in database:</strong> {$count}</p>";
    
    if ($count > 0) {
        echo "<h3>Gallery Images:</h3>";
        $stmt = $conn->query("SELECT * FROM gallery_images ORDER BY created_at DESC");
        $images = $stmt->fetchAll();
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Category</th><th>Image Path</th><th>Featured</th><th>Created</th></tr>";
        
        foreach ($images as $img) {
            $featured = $img['is_featured'] ? '⭐ Yes' : 'No';
            echo "<tr>";
            echo "<td>{$img['id']}</td>";
            echo "<td>{$img['title']}</td>";
            echo "<td>{$img['category']}</td>";
            echo "<td>{$img['image_path']}</td>";
            echo "<td>{$featured}</td>";
            echo "<td>{$img['created_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠️ No gallery images found in database.</p>";
        echo "<p>You can add images through the admin panel: <a href='../admin/manage-gallery.php'>Manage Gallery</a></p>";
        
        // Insert sample data
        echo "<h3>Adding Sample Gallery Images</h3>";
        
        $sample_images = [
            [
                'title' => 'Conference Room',
                'description' => 'Modern conference room for business meetings',
                'image_path' => 'gallery_1758772186_0.jpg',
                'category' => 'venues',
                'tags' => 'conference, meeting, business',
                'alt_text' => 'Conference room interior',
                'is_featured' => 1
            ],
            [
                'title' => 'Poolside Area',
                'description' => 'Beautiful poolside venue perfect for outdoor events',
                'image_path' => 'gallery_1758772186_0.jpg',
                'category' => 'facilities',
                'tags' => 'pool, outdoor, relaxation',
                'alt_text' => 'Poolside area view',
                'is_featured' => 0
            ],
            [
                'title' => 'Resort Grounds',
                'description' => 'Scenic view of our resort grounds',
                'image_path' => 'gallery_1758772186_0.jpg',
                'category' => 'facilities',
                'tags' => 'resort, nature, scenic',
                'alt_text' => 'Resort grounds panorama',
                'is_featured' => 0
            ]
        ];
        
        foreach ($sample_images as $img) {
            $stmt = $conn->prepare("
                INSERT INTO gallery_images (title, description, image_path, category, tags, alt_text, is_featured, uploaded_by)
                VALUES (:title, :description, :image_path, :category, :tags, :alt_text, :is_featured, 2)
            ");
            
            $stmt->execute([
                ':title' => $img['title'],
                ':description' => $img['description'],
                ':image_path' => $img['image_path'],
                ':category' => $img['category'],
                ':tags' => $img['tags'],
                ':alt_text' => $img['alt_text'],
                ':is_featured' => $img['is_featured']
            ]);
        }
        
        echo "<p style='color:green;'>✅ Added 3 sample gallery images!</p>";
        echo "<p><a href='test-gallery.php'>Refresh this page</a> to see the data.</p>";
    }
    
    // Test the API endpoint
    echo "<h3>Testing API Endpoint</h3>";
    $api_url = 'http://' . $_SERVER['HTTP_HOST'] . '/mavics/admin/api/get-gallery.php?limit=10';
    echo "<p>API URL: <a href='{$api_url}' target='_blank'>{$api_url}</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gallery Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        table {
            width: 100%;
            margin: 20px 0;
        }
        th {
            background: #8b4513;
            color: white;
            padding: 10px;
        }
        td {
            padding: 8px;
        }
        tr:nth-child(even) {
            background: #f5f5f5;
        }
        a {
            color: #8b4513;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>🖼️ Gallery Database Test</h1>
    <hr>
</body>
</html>
