<?php
// API to send reply to message

// Start output buffering to prevent any unwanted output
ob_start();

// Start session
session_start();

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Clean any output and set JSON header FIRST
ob_clean();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$messageId = $_POST['message_id'] ?? 0;
$replyTo = $_POST['reply_to'] ?? '';
$replySubject = $_POST['reply_subject'] ?? '';
$replyMessage = $_POST['reply_message'] ?? '';
$markAsReplied = isset($_POST['mark_as_replied']) ? true : false;

// Validation
if (!$messageId) {
    echo json_encode(['success' => false, 'message' => 'Message ID is required']);
    exit();
}

if (empty($replyTo) || empty($replySubject) || empty($replyMessage)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

if (!filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // First, verify the message exists
    $checkStmt = $conn->prepare("SELECT id FROM contact_messages WHERE id = ?");
    $checkStmt->execute([$messageId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Message not found');
    }
    
    // Update message status
    if ($markAsReplied) {
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
        // Just save the reply without changing status
        $stmt = $conn->prepare("
            UPDATE contact_messages 
            SET admin_reply = ?, 
                replied_by = ? 
            WHERE id = ?
        ");
        $result = $stmt->execute([$replyMessage, $_SESSION['admin_id'], $messageId]);
    }
    
    if (!$result) {
        throw new Exception('Failed to update message');
    }
    
    // TODO: Send email to customer
    // This requires email configuration (SMTP settings)
    // For now, we'll just save the reply to database
    
    /*
    // Email sending code (requires PHPMailer or similar)
    $to = $replyTo;
    $subject = $replySubject;
    $body = $replyMessage;
    $headers = "From: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $emailSent = mail($to, $subject, $body, $headers);
    
    if (!$emailSent) {
        throw new Exception('Failed to send email');
    }
    */
    
    // Commit transaction
    $conn->commit();
    
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Reply saved successfully! Note: Email sending is not configured yet.'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if it was started
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Error sending reply: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while sending the reply: ' . $e->getMessage()
    ]);
}

// Ensure output buffer is flushed
ob_end_flush();
?>
