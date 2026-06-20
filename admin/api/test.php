<?php
// Simple API test - api/test.php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, log them instead

header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'message' => 'API is working',
        'method' => $_SERVER['REQUEST_METHOD'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Test failed: ' . $e->getMessage()
    ]);
}
?>