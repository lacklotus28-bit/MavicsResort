<?php
/**
 * Mark Message as Read API
 * Customer acknowledges they have seen the admin reply
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to perform this action'
    ]);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get message ID
$messageId = $_POST['message_id'] ?? 0;

if (!$messageId) {
    echo json_encode([
        'success' => false,
        'message' => 'Message ID is required'
    ]);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Get customer email
    $customerEmail = $_SESSION['email'] ?? '';
    
    // Verify message belongs to this customer
    $stmt = $conn->prepare("
        SELECT id, status, email 
        FROM contact_messages 
        WHERE id = ? AND email = ?
    ");
    $stmt->execute([$messageId, $customerEmail]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        echo json_encode([
            'success' => false,
            'message' => 'Message not found or access denied'
        ]);
        exit;
    }
    
    // Check if message has been replied to
    if ($message['status'] !== 'replied') {
        echo json_encode([
            'success' => false,
            'message' => 'This message has not been replied to yet'
        ]);
        exit;
    }
    
    // Update message status to 'read' (customer has acknowledged the reply)
    $updateStmt = $conn->prepare("
        UPDATE contact_messages 
        SET status = 'read',
            read_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$messageId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Message marked as read successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Error marking message as read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating the message'
    ]);
}
