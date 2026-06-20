<?php
// Debug version of send-reply.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

// Start session
session_start();

// Set JSON header
header('Content-Type: application/json');

try {
    // Check session
    if (!isset($_SESSION['admin_id'])) {
        throw new Exception('Not logged in. Session admin_id not set.');
    }
    
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    }
    
    // Get database connection
    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();
    
    // Get POST data
    $messageId = $_POST['message_id'] ?? 0;
    $replyTo = $_POST['reply_to'] ?? '';
    $replySubject = $_POST['reply_subject'] ?? '';
    $replyMessage = $_POST['reply_message'] ?? '';
    $markAsReplied = isset($_POST['mark_as_replied']);
    
    // Validation
    if (!$messageId) {
        throw new Exception('Message ID is required');
    }
    
    if (empty($replyTo)) {
        throw new Exception('Reply-to email is required');
    }
    
    if (empty($replySubject)) {
        throw new Exception('Subject is required');
    }
    
    if (empty($replyMessage)) {
        throw new Exception('Reply message is required');
    }
    
    if (!filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address: ' . $replyTo);
    }
    
    // Check if message exists
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch();
    
    if (!$message) {
        throw new Exception('Message not found with ID: ' . $messageId);
    }
    
    // Check if required columns exist
    $stmt = $conn->query("DESCRIBE contact_messages");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('admin_reply', $columns)) {
        throw new Exception('Database error: admin_reply column does not exist');
    }
    
    if (!in_array('replied_by', $columns)) {
        throw new Exception('Database error: replied_by column does not exist');
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Update message
    if ($markAsReplied) {
        if (!in_array('replied_at', $columns)) {
            throw new Exception('Database error: replied_at column does not exist');
        }
        
        $stmt = $conn->prepare("
            UPDATE contact_messages 
            SET status = 'replied', 
                replied_at = NOW(), 
                admin_reply = ?, 
                replied_by = ? 
            WHERE id = ?
        ");
        $result = $stmt->execute([$replyMessage, $_SESSION['admin_id'], $messageId]);
    } else {
        $stmt = $conn->prepare("
            UPDATE contact_messages 
            SET admin_reply = ?, 
                replied_by = ? 
            WHERE id = ?
        ");
        $result = $stmt->execute([$replyMessage, $_SESSION['admin_id'], $messageId]);
    }
    
    if (!$result) {
        throw new Exception('Failed to execute update query');
    }
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('No rows were updated. Message ID may not exist.');
    }
    
    // Commit transaction
    $conn->commit();
    
    // Success
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Reply saved successfully!',
        'debug' => [
            'message_id' => $messageId,
            'admin_id' => $_SESSION['admin_id'],
            'rows_affected' => $stmt->rowCount(),
            'mark_as_replied' => $markAsReplied
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback if needed
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log error
    error_log("Send Reply Error: " . $e->getMessage());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    // Return error
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

ob_end_flush();
?>
