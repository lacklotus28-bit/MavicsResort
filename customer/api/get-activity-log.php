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

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Query to get user activity log
    $query = "SELECT 
                activity_type as type,
                activity_title as title,
                activity_description as description,
                created_at,
                CASE 
                    WHEN TIMESTAMPDIFF(SECOND, created_at, NOW()) < 60 THEN CONCAT(TIMESTAMPDIFF(SECOND, created_at, NOW()), ' seconds ago')
                    WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), ' minutes ago')
                    WHEN TIMESTAMPDIFF(HOUR, created_at, NOW()) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), ' hours ago')
                    WHEN TIMESTAMPDIFF(DAY, created_at, NOW()) < 30 THEN CONCAT(TIMESTAMPDIFF(DAY, created_at, NOW()), ' days ago')
                    ELSE DATE_FORMAT(created_at, '%M %d, %Y')
                END as time_ago
              FROM activity_log 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC 
              LIMIT 20";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);
    
} catch (PDOException $e) {
    // If activity_log table doesn't exist, return empty array
    // This allows the page to work even without the activity log feature
    error_log("Activity log error: " . $e->getMessage());
    
    echo json_encode([
        'success' => true,
        'activities' => []
    ]);
}
?>