<?php
// API endpoint for venue date blocking - Mavic's Resort
// api/venue-blocks.php

require_once '../config/database.php';
require_once '../classes/auth.php';
require_once '../includes/admin-helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$auth = new Auth();

// Check authentication
if (!$auth->isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$admin = $auth->getCurrentAdmin();

try {
    $conn = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequest($conn);
            break;
        case 'POST':
            handlePostRequest($conn, $admin);
            break;
        case 'DELETE':
            handleDeleteRequest($conn, $admin);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Venue Blocks API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function handleGetRequest($conn) {
    $venueId = $_GET['venue_id'] ?? null;
    $blockId = $_GET['id'] ?? null;
    
    if ($blockId) {
        getSingleBlock($conn, $blockId);
    } elseif ($venueId) {
        getBlocksByVenue($conn, $venueId);
    } else {
        getAllBlocks($conn);
    }
}

function getAllBlocks($conn) {
    try {
        $stmt = $conn->query("
            SELECT vb.*, v.name as venue_name, au.full_name as blocked_by_name
            FROM venue_blocks vb
            LEFT JOIN venues v ON vb.venue_id = v.id
            LEFT JOIN admin_users au ON vb.blocked_by = au.id
            WHERE vb.block_date >= CURRENT_DATE()
            ORDER BY vb.block_date ASC, v.name ASC
        ");
        
        $blocks = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $blocks
        ]);
        
    } catch (PDOException $e) {
        error_log("Get venue blocks error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to retrieve venue blocks'
        ]);
    }
}

function getBlocksByVenue($conn, $venueId) {
    try {
        $stmt = $conn->prepare("
            SELECT vb.*, v.name as venue_name, au.full_name as blocked_by_name
            FROM venue_blocks vb
            LEFT JOIN venues v ON vb.venue_id = v.id
            LEFT JOIN admin_users au ON vb.blocked_by = au.id
            WHERE vb.venue_id = :venue_id AND vb.block_date >= CURRENT_DATE()
            ORDER BY vb.block_date ASC
        ");
        
        $stmt->bindParam(':venue_id', $venueId);
        $stmt->execute();
        
        $blocks = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $blocks
        ]);
        
    } catch (PDOException $e) {
        error_log("Get venue blocks by venue error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to retrieve venue blocks'
        ]);
    }
}

function getSingleBlock($conn, $id) {
    try {
        $stmt = $conn->prepare("
            SELECT vb.*, v.name as venue_name, au.full_name as blocked_by_name
            FROM venue_blocks vb
            LEFT JOIN venues v ON vb.venue_id = v.id
            LEFT JOIN admin_users au ON vb.blocked_by = au.id
            WHERE vb.id = :id
        ");
        
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $block = $stmt->fetch();
        
        if ($block) {
            echo json_encode([
                'success' => true,
                'data' => $block
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Venue block not found'
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Get single venue block error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to retrieve venue block'
        ]);
    }
}

function handlePostRequest($conn, $admin) {
    $venueId = $_POST['venue_id'] ?? null;
    $blockDate = $_POST['block_date'] ?? null;
    $reason = $_POST['reason'] ?? null;
    
    if (!$venueId || !$blockDate || !$reason) {
        echo json_encode([
            'success' => false,
            'message' => 'Venue ID, block date, and reason are required'
        ]);
        return;
    }
    
    try {
        // Verify venue exists
        $stmt = $conn->prepare("SELECT id, name FROM venues WHERE id = :venue_id");
        $stmt->bindParam(':venue_id', $venueId);
        $stmt->execute();
        $venue = $stmt->fetch();
        
        if (!$venue) {
            echo json_encode([
                'success' => false,
                'message' => 'Venue not found'
            ]);
            return;
        }
        
        // Check if date is in the future
        if (strtotime($blockDate) < strtotime('today')) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot block dates in the past'
            ]);
            return;
        }
        
        // Check if date is already blocked
        $stmt = $conn->prepare("
            SELECT id FROM venue_blocks 
            WHERE venue_id = :venue_id AND block_date = :block_date
        ");
        $stmt->bindParam(':venue_id', $venueId);
        $stmt->bindParam(':block_date', $blockDate);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'This date is already blocked for this venue'
            ]);
            return;
        }
        
        // Check if there's an existing booking on this date
        $stmt = $conn->prepare("
            SELECT id, event_type FROM bookings 
            WHERE venue_id = :venue_id AND booking_date = :block_date 
            AND status IN ('pending', 'confirmed')
        ");
        $stmt->bindParam(':venue_id', $venueId);
        $stmt->bindParam(':block_date', $blockDate);
        $stmt->execute();
        $existingBooking = $stmt->fetch();
        
        if ($existingBooking) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot block date - venue has an existing booking (' . $existingBooking['event_type'] . ')'
            ]);
            return;
        }
        
        // Create the block
        $stmt = $conn->prepare("
            INSERT INTO venue_blocks (venue_id, block_date, reason, blocked_by)
            VALUES (:venue_id, :block_date, :reason, :blocked_by)
        ");
        
        $stmt->execute([
            'venue_id' => $venueId,
            'block_date' => $blockDate,
            'reason' => $reason,
            'blocked_by' => $admin['id']
        ]);
        
        $blockId = $conn->lastInsertId();
        
        // Log activity
        logActivity($conn, $admin['id'], 'venue_block_create', 'venue_blocks', $blockId, null, [
            'venue_id' => $venueId,
            'venue_name' => $venue['name'],
            'block_date' => $blockDate,
            'reason' => $reason
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Date blocked successfully for ' . $venue['name'],
            'data' => ['id' => $blockId]
        ]);
        
    } catch (PDOException $e) {
        error_log("Create venue block error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to block date'
        ]);
    }
}

function handleDeleteRequest($conn, $admin) {
    $input = json_decode(file_get_contents('php://input'), true);
    $blockId = $input['id'] ?? null;
    
    if (!$blockId) {
        echo json_encode(['success' => false, 'message' => 'Block ID required']);
        return;
    }
    
    try {
        // Get block data for logging
        $stmt = $conn->prepare("
            SELECT vb.*, v.name as venue_name 
            FROM venue_blocks vb
            LEFT JOIN venues v ON vb.venue_id = v.id
            WHERE vb.id = :id
        ");
        $stmt->bindParam(':id', $blockId);
        $stmt->execute();
        $blockData = $stmt->fetch();
        
        if (!$blockData) {
            echo json_encode(['success' => false, 'message' => 'Venue block not found']);
            return;
        }
        
        // Delete the block
        $stmt = $conn->prepare("DELETE FROM venue_blocks WHERE id = :id");
        $stmt->bindParam(':id', $blockId);
        $stmt->execute();
        
        // Log activity
        logActivity($conn, $admin['id'], 'venue_block_delete', 'venue_blocks', $blockId, $blockData, null);
        
        echo json_encode([
            'success' => true,
            'message' => 'Venue block removed successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Delete venue block error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to remove venue block'
        ]);
    }
}

function logActivity($conn, $adminId, $action, $tableName, $recordId, $oldValues, $newValues) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (admin_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
            VALUES (:admin_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            'admin_id' => $adminId,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (PDOException $e) {
        error_log("Log activity error: " . $e->getMessage());
    }
}
?>