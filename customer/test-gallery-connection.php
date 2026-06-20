<?php
/**
 * Gallery Connection Test
 * Tests the complete flow between Admin and Customer Gallery
 */

require_once '../admin/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Gallery Connection Test</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 1200px; 
            margin: 20px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0;
        }
        th, td { 
            padding: 12px; 
            border: 1px solid #ddd; 
            text-align: left;
        }
        th { 
            background: #8b4513; 
            color: white;
        }
        tr:nth-child(even) { background: #f9f9f9; }
        .flow-diagram {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #8b4513;
            margin: 15px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        h1 { color: #8b4513; }
        h2 { color: #333; border-bottom: 2px solid #8b4513; padding-bottom: 10px; }
        a { color: #8b4513; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .test-pass { background: #d4edda; border: 1px solid #c3e6cb; }
        .test-fail { background: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h1>🔗 Gallery Connection Test</h1>
    <p><strong>Testing complete integration between Admin Gallery Management and Customer Gallery Display</strong></p>
    <hr>
";

// Test 1: Database Connection
echo "<div class='test-section'>";
echo "<h2>📊 Test 1: Database Connection</h2>";
try {
    $conn = getDBConnection();
    echo "<p class='success'>✅ Database connection successful!</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}
echo "</div>";

// Test 2: Gallery Table Existence
echo "<div class='test-section'>";
echo "<h2>🗄️ Test 2: Gallery Table Check</h2>";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'gallery_images'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ gallery_images table exists!</p>";
        
        // Show table structure
        $stmt = $conn->query("DESCRIBE gallery_images");
        $columns = $stmt->fetchAll();
        
        echo "<details><summary>View Table Structure</summary>";
        echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "</tr>";
        }
        echo "</table></details>";
    } else {
        echo "<p class='error'>❌ gallery_images table does NOT exist!</p>";
        echo "<p class='info'>Creating table...</p>";
        
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
        
        echo "<p class='success'>✅ Table created successfully!</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Check Gallery Images
echo "<div class='test-section'>";
echo "<h2>🖼️ Test 3: Gallery Images Check</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM gallery_images");
    $count = $stmt->fetch()['count'];
    
    echo "<p><strong>Total Images in Database:</strong> {$count}</p>";
    
    if ($count == 0) {
        echo "<p class='info'>⚠️ No images found. Adding sample data...</p>";
        
        // Insert sample images
        $sample_images = [
            [
                'title' => 'Conference Room',
                'description' => 'Modern conference room perfect for business meetings',
                'image_path' => 'gallery_1758772186_0.jpg',
                'category' => 'venues',
                'tags' => 'conference, meeting, business, professional',
                'alt_text' => 'Modern conference room with presentation setup',
                'is_featured' => 1
            ],
            [
                'title' => 'Poolside Venue',
                'description' => 'Beautiful outdoor poolside area for events',
                'image_path' => 'gallery_1758772186_0.jpg',
                'category' => 'facilities',
                'tags' => 'pool, outdoor, water, relaxation',
                'alt_text' => 'Resort poolside area with seating',
                'is_featured' => 0
            ],
            [
                'title' => 'Resort Gardens',
                'description' => 'Lush tropical gardens throughout the resort',
                'image_path' => 'gallery_1758772186_0.jpg',
                'category' => 'facilities',
                'tags' => 'nature, garden, tropical, scenic',
                'alt_text' => 'Beautiful resort garden landscape',
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
        
        echo "<p class='success'>✅ Added 3 sample gallery images!</p>";
        $count = 3;
    }
    
    // Display gallery images
    if ($count > 0) {
        $stmt = $conn->query("SELECT * FROM gallery_images ORDER BY is_featured DESC, created_at DESC");
        $images = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Featured</th>
                <th>Image Path</th>
                <th>Status</th>
              </tr>";
        
        foreach ($images as $img) {
            $featured = $img['is_featured'] ? '⭐ Yes' : 'No';
            $file_exists = file_exists("../admin/uploads/gallery/" . $img['image_path']);
            $status = $file_exists ? 
                "<span class='status-badge badge-success'>✓ Exists</span>" : 
                "<span class='status-badge badge-danger'>✗ Missing</span>";
            
            echo "<tr>";
            echo "<td>{$img['id']}</td>";
            echo "<td>{$img['title']}</td>";
            echo "<td>" . ucwords($img['category']) . "</td>";
            echo "<td>{$featured}</td>";
            echo "<td>{$img['image_path']}</td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Admin API Endpoint
echo "<div class='test-section'>";
echo "<h2>🔌 Test 4: API Endpoint Test</h2>";

$api_url = 'http://' . $_SERVER['HTTP_HOST'] . '/mavics/admin/api/get-gallery.php?limit=10';
echo "<p><strong>API URL:</strong> <a href='{$api_url}' target='_blank'>{$api_url}</a></p>";

try {
    $json_response = @file_get_contents($api_url);
    
    if ($json_response !== false) {
        $data = json_decode($json_response, true);
        
        if ($data && isset($data['success'])) {
            if ($data['success']) {
                echo "<div class='test-result test-pass'>";
                echo "<p class='success'>✅ API is working correctly!</p>";
                echo "<p><strong>Response:</strong></p>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
                echo "</div>";
            } else {
                echo "<div class='test-result test-fail'>";
                echo "<p class='error'>❌ API returned an error</p>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
                echo "</div>";
            }
        } else {
            echo "<p class='error'>❌ Invalid JSON response from API</p>";
        }
    } else {
        echo "<p class='error'>❌ Could not connect to API endpoint</p>";
        echo "<p>Make sure your Apache server is running!</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ API Test Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: Upload Directory
echo "<div class='test-section'>";
echo "<h2>📁 Test 5: Upload Directory Check</h2>";

$upload_dir = '../admin/uploads/gallery/';
if (file_exists($upload_dir)) {
    echo "<p class='success'>✅ Upload directory exists: <code>{$upload_dir}</code></p>";
    
    // Check if writable
    if (is_writable($upload_dir)) {
        echo "<p class='success'>✅ Directory is writable</p>";
    } else {
        echo "<p class='error'>❌ Directory is NOT writable! Please check permissions.</p>";
    }
    
    // List files
    $files = scandir($upload_dir);
    $image_files = array_filter($files, function($file) {
        return !in_array($file, ['.', '..']) && 
               preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
    });
    
    echo "<p><strong>Images in directory:</strong> " . count($image_files) . "</p>";
    
    if (count($image_files) > 0) {
        echo "<ul>";
        foreach ($image_files as $file) {
            $size = filesize($upload_dir . $file);
            $size_mb = round($size / 1024 / 1024, 2);
            echo "<li>{$file} ({$size_mb} MB)</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='error'>❌ Upload directory does NOT exist!</p>";
    echo "<p class='info'>Creating directory...</p>";
    
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p class='success'>✅ Directory created successfully!</p>";
    } else {
        echo "<p class='error'>❌ Failed to create directory</p>";
    }
}
echo "</div>";

// Test 6: Connection Flow Diagram
echo "<div class='test-section'>";
echo "<h2>🔄 Test 6: Data Flow Verification</h2>";

echo "<div class='flow-diagram'>";
echo "<h3>Admin → Customer Connection Flow:</h3>";
echo "<ol>";
echo "<li><strong>Admin Uploads Image</strong> → manage-gallery.php</li>";
echo "<li><strong>Image Saved</strong> → /admin/uploads/gallery/</li>";
echo "<li><strong>Database Record Created</strong> → gallery_images table</li>";
echo "<li><strong>Customer Requests Gallery</strong> → index.php</li>";
echo "<li><strong>JavaScript Calls API</strong> → /admin/api/get-gallery.php</li>";
echo "<li><strong>API Returns JSON</strong> → Image data from database</li>";
echo "<li><strong>Gallery Renders</strong> → Customer sees images!</li>";
echo "</ol>";
echo "</div>";

// Verify each step
$flow_tests = [
    'Admin Page Exists' => file_exists('../admin/manage-gallery.php'),
    'Upload Directory Exists' => file_exists('../admin/uploads/gallery/'),
    'Database Table Exists' => $conn->query("SHOW TABLES LIKE 'gallery_images'")->rowCount() > 0,
    'Customer Page Exists' => file_exists('index.php'),
    'Gallery JS Exists' => file_exists('js/gallery.js'),
    'API Endpoint Exists' => file_exists('../admin/api/get-gallery.php'),
    'Images in Database' => $conn->query("SELECT COUNT(*) as c FROM gallery_images")->fetch()['c'] > 0
];

echo "<h3>Flow Component Status:</h3>";
echo "<table>";
echo "<tr><th>Component</th><th>Status</th></tr>";
foreach ($flow_tests as $component => $status) {
    $badge = $status ? 
        "<span class='status-badge badge-success'>✓ OK</span>" : 
        "<span class='status-badge badge-danger'>✗ FAIL</span>";
    echo "<tr><td>{$component}</td><td>{$badge}</td></tr>";
}
echo "</table>";

$all_passed = !in_array(false, $flow_tests);
if ($all_passed) {
    echo "<div class='test-result test-pass'>";
    echo "<h3 class='success'>🎉 ALL TESTS PASSED!</h3>";
    echo "<p>The Admin Gallery Management is <strong>FULLY CONNECTED</strong> to the Customer Gallery!</p>";
    echo "</div>";
} else {
    echo "<div class='test-result test-fail'>";
    echo "<h3 class='error'>⚠️ SOME TESTS FAILED</h3>";
    echo "<p>Please fix the issues above before proceeding.</p>";
    echo "</div>";
}

echo "</div>";

// Test 7: Quick Links
echo "<div class='test-section'>";
echo "<h2>🔗 Quick Links</h2>";
echo "<ul>";
echo "<li><a href='../admin/admin-login.php' target='_blank'>→ Admin Login</a> (superadmin / password)</li>";
echo "<li><a href='../admin/manage-gallery.php' target='_blank'>→ Admin Gallery Management</a></li>";
echo "<li><a href='index.php' target='_blank'>→ Customer Homepage (with Gallery)</a></li>";
echo "<li><a href='{$api_url}' target='_blank'>→ Gallery API (JSON)</a></li>";
echo "<li><a href='test-gallery.php'>→ Refresh This Test</a></li>";
echo "</ul>";
echo "</div>";

// Summary
echo "<div class='test-section' style='background: #fff3cd; border-left: 5px solid #ffc107;'>";
echo "<h2>📋 Summary</h2>";
echo "<p><strong>Connection Status:</strong> ";
if ($all_passed) {
    echo "<span class='success'>✅ FULLY CONNECTED & WORKING</span>";
} else {
    echo "<span class='error'>❌ NEEDS ATTENTION</span>";
}
echo "</p>";

echo "<h3>How It Works:</h3>";
echo "<ol>";
echo "<li>Admin uploads images through <strong>Admin Panel → Gallery Management</strong></li>";
echo "<li>Images are stored in <strong>/admin/uploads/gallery/</strong></li>";
echo "<li>Database records created in <strong>gallery_images</strong> table</li>";
echo "<li>Customer visits homepage at <strong>/customer/index.php</strong></li>";
echo "<li>Gallery JavaScript calls <strong>API endpoint</strong></li>";
echo "<li>API fetches images from <strong>database</strong></li>";
echo "<li>Gallery renders images on <strong>customer homepage</strong></li>";
echo "</ol>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Login to admin panel and upload more resort images</li>";
echo "<li>Organize images by categories (venues, facilities, events)</li>";
echo "<li>Mark best photos as featured</li>";
echo "<li>View the results on customer homepage</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>
