<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$adminId = $_SESSION['admin_id'];

try {
    $conn = getDBConnection();
    $conn->beginTransaction();
    
    // Get all existing settings
    $stmt = $conn->prepare("SELECT setting_key FROM payment_settings");
    $stmt->execute();
    $existingSettings = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $updatedCount = 0;
    
    // Update each setting from POST data
    foreach ($_POST as $key => $value) {
        if (in_array($key, $existingSettings)) {
            $stmt = $conn->prepare("
                UPDATE payment_settings 
                SET setting_value = ?, updated_by = ?, updated_at = NOW()
                WHERE setting_key = ?
            ");
            $stmt->execute([$value, $adminId, $key]);
            $updatedCount++;
        }
    }
    
    $conn->commit();
    
    // Log activity
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (admin_id, action, table_name, ip_address, user_agent)
        VALUES (?, 'payment_settings_update', 'payment_settings', ?, ?)
    ");
    $stmt->execute([
        $adminId,
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully updated {$updatedCount} settings",
        'updated_count' => $updatedCount
    ]);
    
} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Error updating payment settings: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update settings: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("General error in update-payment-settings: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
