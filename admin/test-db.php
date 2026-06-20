<?php
header('Content-Type: text/plain');

echo "=== DATABASE CONNECTION TEST ===\n\n";

try {
    require_once 'config/database.php';
    $conn = getDBConnection();
    
    echo "✓ Database connection successful\n\n";
    
    // Check if contact_messages table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'contact_messages'");
    if ($stmt->rowCount() > 0) {
        echo "✓ contact_messages table exists\n";
        
        // Get table structure
        $stmt = $conn->query("DESCRIBE contact_messages");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "\nTable columns:\n";
        foreach ($columns as $col) {
            echo "  - $col\n";
        }
        
        // Get row count
        $stmt = $conn->query("SELECT COUNT(*) as count FROM contact_messages");
        $count = $stmt->fetch()['count'];
        echo "\nTotal messages: $count\n";
        
    } else {
        echo "✗ contact_messages table does NOT exist\n";
        echo "Please create the table first!\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database connection failed\n";
    echo "Error: " . $e->getMessage() . "\n";
}
?>