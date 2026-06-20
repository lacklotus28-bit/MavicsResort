<?php
// API to get message details

// Start output buffering to prevent any unwanted output
ob_start();

// Start session
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_id'])) {
    ob_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Set error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, only log them
ini_set('log_errors', 1);

try {
    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
    exit();
}

// Clean any output and set JSON header
ob_clean();
header('Content-Type: application/json');

// Get message ID
$messageId = $_GET['id'] ?? 0;

if (!$messageId) {
    echo json_encode(['success' => false, 'message' => 'Message ID is required']);
    exit();
}

try {
    // Fetch message details - Simple query first
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        echo json_encode(['success' => false, 'message' => 'Message not found']);
        exit();
    }
    
    // Try to get admin name if replied
    $message['replied_by_name'] = null;
    if ($message['replied_by']) {
        try {
            $adminStmt = $conn->prepare("SELECT full_name FROM admin_users WHERE id = ?");
            $adminStmt->execute([$message['replied_by']]);
            $adminData = $adminStmt->fetch(PDO::FETCH_ASSOC);
            if ($adminData) {
                $message['replied_by_name'] = $adminData['full_name'];
            }
        } catch (PDOException $e) {
            // If admin_users table doesn't exist, just continue without admin name
            error_log("Could not fetch admin name: " . $e->getMessage());
        }
    }
    
    // If message is unread, mark it as read
    if ($message['status'] === 'unread') {
        $updateStmt = $conn->prepare("UPDATE contact_messages SET status = 'read', read_at = NOW() WHERE id = ?");
        $updateStmt->execute([$messageId]);
        $message['status'] = 'read';
        $message['read_at'] = date('Y-m-d H:i:s');
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching message: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
