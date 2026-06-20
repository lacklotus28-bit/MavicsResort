<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];

// Define allowed preferences
$allowed_preferences = [
    'email_notifications',
    'sms_notifications',
    'newsletter',
    'booking_reminders',
    'promotional_offers'
];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user_preferences_profile table exists
    $table_check = "SHOW TABLES LIKE 'user_preferences_profile'";
    $check_stmt = $db->query($table_check);
    
    if ($check_stmt->rowCount() == 0) {
        // Table doesn't exist, create it
        $create_table = "CREATE TABLE IF NOT EXISTS user_preferences_profile (
            preference_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email_notifications TINYINT(1) DEFAULT 1,
            sms_notifications TINYINT(1) DEFAULT 1,
            newsletter TINYINT(1) DEFAULT 0,
            booking_reminders TINYINT(1) DEFAULT 1,
            promotional_offers TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES customers(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user (user_id)
        )";
        $db->exec($create_table);
    }
    
    // Check if user has preferences record
    $check_query = "SELECT preference_id FROM user_preferences_profile WHERE user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    $exists = $check_stmt->rowCount() > 0;
    
    // Build update/insert values
    $fields = [];
    $values = [];
    foreach ($allowed_preferences as $pref) {
        if (isset($input[$pref])) {
            $fields[] = "$pref = :$pref";
            $values[$pref] = (int)$input[$pref];
        }
    }
    
    if (empty($fields)) {
        echo json_encode([
            'success' => false,
            'message' => 'No valid preferences provided'
        ]);
        exit;
    }
    
    if ($exists) {
        // Update existing preferences
        $query = "UPDATE user_preferences_profile SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE user_id = :user_id";
    } else {
        // Insert new preferences
        $field_names = array_keys($values);
        $query = "INSERT INTO user_preferences_profile (user_id, " . implode(', ', $field_names) . ", created_at, updated_at) 
                  VALUES (:user_id, :" . implode(', :', $field_names) . ", NOW(), NOW())";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    
    foreach ($values as $key => $value) {
        $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Preferences updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update preferences');
    }
    
} catch (PDOException $e) {
    error_log("Preferences update error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>