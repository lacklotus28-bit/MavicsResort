<?php
// Test send reply endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Send Reply Endpoint ===\n\n";

// Start session
session_start();
echo "1. Session started\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Admin ID set: " . (isset($_SESSION['admin_id']) ? 'YES (' . $_SESSION['admin_id'] . ')' : 'NO') . "\n\n";

// Test database connection
try {
    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();
    echo "2. Database connected successfully\n\n";
} catch (Exception $e) {
    echo "2. Database connection FAILED: " . $e->getMessage() . "\n\n";
    exit;
}

// Check if contact_messages table has required columns
try {
    $stmt = $conn->query("DESCRIBE contact_messages");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "3. Contact messages table columns:\n";
    foreach ($columns as $col) {
        echo "   - $col\n";
    }
    echo "\n";
    
    $requiredColumns = ['admin_reply', 'replied_by', 'replied_at'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (!empty($missingColumns)) {
        echo "   WARNING: Missing columns: " . implode(', ', $missingColumns) . "\n\n";
    } else {
        echo "   ✓ All required columns exist\n\n";
    }
} catch (Exception $e) {
    echo "3. Error checking table: " . $e->getMessage() . "\n\n";
}

// Test getting a message
try {
    $stmt = $conn->query("SELECT * FROM contact_messages ORDER BY id DESC LIMIT 1");
    $message = $stmt->fetch();
    
    if ($message) {
        echo "4. Sample message found:\n";
        echo "   ID: " . $message['id'] . "\n";
        echo "   From: " . $message['name'] . " (" . $message['email'] . ")\n";
        echo "   Status: " . $message['status'] . "\n\n";
    } else {
        echo "4. No messages found in database\n\n";
    }
} catch (Exception $e) {
    echo "4. Error fetching message: " . $e->getMessage() . "\n\n";
}

// Test update query
if (isset($message)) {
    try {
        echo "5. Testing update query...\n";
        $testReply = "This is a test reply";
        $adminId = $_SESSION['admin_id'] ?? 1;
        $messageId = $message['id'];
        
        $stmt = $conn->prepare("
            UPDATE contact_messages 
            SET admin_reply = ?, 
                replied_by = ? 
            WHERE id = ?
        ");
        
        echo "   Executing: UPDATE contact_messages SET admin_reply = ?, replied_by = ? WHERE id = ?\n";
        echo "   Parameters: ['$testReply', $adminId, $messageId]\n";
        
        $result = $stmt->execute([$testReply, $adminId, $messageId]);
        
        echo "   Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
        echo "   Rows affected: " . $stmt->rowCount() . "\n\n";
        
    } catch (Exception $e) {
        echo "5. Update query FAILED: " . $e->getMessage() . "\n\n";
    }
}

echo "=== Test Complete ===\n";
?>
