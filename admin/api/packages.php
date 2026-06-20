<?php
// API endpoint for package management - Mavic's Resort
// api/packages.php

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
        case 'PUT':
            handlePutRequest($conn, $admin);
            break;
        case 'DELETE':
            handleDeleteRequest($conn, $admin);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Packages API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function handleGetRequest($conn) {
    $venueId = $_GET['venue_id'] ?? null;
    $packageId = $_GET['id'] ?? null;
    
    if ($packageId) {
        getSinglePackage($conn, $packageId);
    } elseif ($venueId) {
        getPackagesByVenue($conn, $venueId);
    } else {
        getAllPackages($conn);
    }
}

function getAllPackages($conn) {
    try {
        $stmt = $conn->query("
            SELECT p.*, v.name as venue_name,
                   COUNT(b.id) as booking_count
            FROM packages p
            LEFT JOIN venues v ON p.venue_id = v.id
            LEFT JOIN bookings b ON p.id = b.package_id
            GROUP BY p.id
            ORDER BY v.name, p.name
        ");
        
        $packages = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $packages
        ]);
        
    } catch (PDOException $e) {
        error_log("Get packages error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to retrieve packages'
        ]);
    }
}

function getPackagesByVenue($conn, $venueId) {
    try {
        $stmt = $conn->prepare("
            SELECT p.*, v.name as venue_name,
                   COUNT(b.id) as booking_count
            FROM packages p
            LEFT JOIN venues v ON p.venue_id = v.id
            LEFT JOIN bookings b ON p.id = b.package_id
            WHERE p.venue_id = :venue_id
            GROUP BY p.id
            ORDER BY p.name
        ");
        
        $stmt->bindParam(':venue_id', $venueId);
        $stmt->execute();
        
        $packages = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $packages
        ]);
        
    } catch (PDOException $e) {
        error_log("Get venue packages error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to retrieve venue packages'
        ]);
    }
}

function getSinglePackage($conn, $id) {
    try {
        $stmt = $conn->prepare("
            SELECT p.*, v.name as venue_name
            FROM packages p
            LEFT JOIN venues v ON p.venue_id = v.id
            WHERE p.id = :id
        ");
        
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $package = $stmt->fetch();
        
        if ($package) {
            echo json_encode([
                'success' => true,
                'data' => $package
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Package not found'
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Get single package error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to retrieve package'
        ]);
    }
}

function handlePostRequest($conn, $admin) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if it's a duplicate request
    if (isset($input['action']) && $input['action'] === 'duplicate') {
        duplicatePackage($conn, $input['id'] ?? null, $admin);
        return;
    }
    
    // Handle regular create request
    createPackage($conn, $input, $admin);
}

function createPackage($conn, $data, $admin) {
    try {
        // Validate required fields
        $required = ['venue_id', 'name', 'price', 'duration_hours'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                echo json_encode([
                    'success' => false,
                    'message' => "Field '{$field}' is required"
                ]);
                return;
            }
        }
        
        // Verify venue exists
        $stmt = $conn->prepare("SELECT id FROM venues WHERE id = :venue_id");
        $stmt->bindParam(':venue_id', $data['venue_id']);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Venue not found'
            ]);
            return;
        }
        
        // Parse inclusions
        $inclusions = [];
        if (!empty($data['inclusions'])) {
            if (is_array($data['inclusions'])) {
                $inclusions = $data['inclusions'];
            } else {
                $inclusions = json_decode($data['inclusions'], true) ?: [];
            }
        }
        
        $stmt = $conn->prepare("
            INSERT INTO packages (venue_id, name, description, price, duration_hours, max_guests, inclusions, status)
            VALUES (:venue_id, :name, :description, :price, :duration_hours, :max_guests, :inclusions, :status)
        ");
        
        $stmt->execute([
            'venue_id' => intval($data['venue_id']),
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => floatval($data['price']),
            'duration_hours' => intval($data['duration_hours']),
            'max_guests' => !empty($data['max_guests']) ? intval($data['max_guests']) : null,
            'inclusions' => json_encode($inclusions),
            'status' => $data['status'] ?? 'active'
        ]);
        
        $packageId = $conn->lastInsertId();
        
        // Log activity
        logActivity($conn, $admin['id'], 'package_create', 'packages', $packageId, null, $data);
        
        echo json_encode([
            'success' => true,
            'message' => 'Package created successfully',
            'data' => ['id' => $packageId]
        ]);
        
    } catch (PDOException $e) {
        error_log("Create package error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create package'
        ]);
    }
}

function handlePutRequest($conn, $admin) {
    $input = json_decode(file_get_contents('php://input'), true);
    updatePackage($conn, $input, $admin);
}

function updatePackage($conn, $data, $admin) {
    try {
        $packageId = $data['id'] ?? null;
        
        if (!$packageId) {
            echo json_encode(['success' => false, 'message' => 'Package ID required']);
            return;
        }
        
        // Get existing package for logging
        $stmt = $conn->prepare("SELECT * FROM packages WHERE id = :id");
        $stmt->bindParam(':id', $packageId);
        $stmt->execute();
        $oldData = $stmt->fetch();
        
        if (!$oldData) {
            echo json_encode([
                'success' => false,
                'message' => 'Package not found'
            ]);
            return;
        }
        
        // Parse inclusions
        $inclusions = [];
        if (!empty($data['inclusions'])) {
            if (is_array($data['inclusions'])) {
                $inclusions = $data['inclusions'];
            } else {
                $inclusions = json_decode($data['inclusions'], true) ?: [];
            }
        }
        
        $stmt = $conn->prepare("
            UPDATE packages 
            SET name = :name, description = :description, price = :price, 
                duration_hours = :duration_hours, max_guests = :max_guests, 
                inclusions = :inclusions, status = :status, updated_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => floatval($data['price']),
            'duration_hours' => intval($data['duration_hours']),
            'max_guests' => !empty($data['max_guests']) ? intval($data['max_guests']) : null,
            'inclusions' => json_encode($inclusions),
            'status' => $data['status'] ?? 'active',
            'id' => $packageId
        ]);
        
        // Log activity
        logActivity($conn, $admin['id'], 'package_update', 'packages', $packageId, $oldData, $data);
        
        echo json_encode([
            'success' => true,
            'message' => 'Package updated successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Update package error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update package'
        ]);
    }
}

function handleDeleteRequest($conn, $admin) {
    $input = json_decode(file_get_contents('php://input'), true);
    $packageId = $input['id'] ?? null;
    
    if (!$packageId) {
        echo json_encode(['success' => false, 'message' => 'Package ID required']);
        return;
    }
    
    try {
        // Check for existing bookings
        $stmt = $conn->prepare("
            SELECT COUNT(*) as booking_count 
            FROM bookings 
            WHERE package_id = :package_id AND status IN ('pending', 'confirmed')
        ");
        $stmt->bindParam(':package_id', $packageId);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['booking_count'] > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete package with existing bookings'
            ]);
            return;
        }
        
        // Get package data for logging
        $stmt = $conn->prepare("SELECT * FROM packages WHERE id = :id");
        $stmt->bindParam(':id', $packageId);
        $stmt->execute();
        $packageData = $stmt->fetch();
        
        if (!$packageData) {
            echo json_encode(['success' => false, 'message' => 'Package not found']);
            return;
        }
        
        // Delete package
        $stmt = $conn->prepare("DELETE FROM packages WHERE id = :id");
        $stmt->bindParam(':id', $packageId);
        $stmt->execute();
        
        // Log activity
        logActivity($conn, $admin['id'], 'package_delete', 'packages', $packageId, $packageData, null);
        
        echo json_encode([
            'success' => true,
            'message' => 'Package deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Delete package error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete package'
        ]);
    }
}

function duplicatePackage($conn, $packageId, $admin) {
    if (!$packageId) {
        echo json_encode(['success' => false, 'message' => 'Package ID required']);
        return;
    }
    
    try {
        // Get original package
        $stmt = $conn->prepare("SELECT * FROM packages WHERE id = :id");
        $stmt->bindParam(':id', $packageId);
        $stmt->execute();
        $package = $stmt->fetch();
        
        if (!$package) {
            echo json_encode(['success' => false, 'message' => 'Package not found']);
            return;
        }
        
        // Create duplicate
        $stmt = $conn->prepare("
            INSERT INTO packages (venue_id, name, description, price, duration_hours, max_guests, inclusions, status)
            VALUES (:venue_id, :name, :description, :price, :duration_hours, :max_guests, :inclusions, :status)
        ");
        
        $duplicateName = $package['name'] . ' (Copy)';
        
        $stmt->execute([
            'venue_id' => $package['venue_id'],
            'name' => $duplicateName,
            'description' => $package['description'],
            'price' => $package['price'],
            'duration_hours' => $package['duration_hours'],
            'max_guests' => $package['max_guests'],
            'inclusions' => $package['inclusions'],
            'status' => 'active'
        ]);
        
        $newPackageId = $conn->lastInsertId();
        
        // Log activity
        logActivity($conn, $admin['id'], 'package_duplicate', 'packages', $newPackageId, null, ['original_id' => $packageId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Package duplicated successfully',
            'data' => ['id' => $newPackageId]
        ]);
        
    } catch (PDOException $e) {
        error_log("Duplicate package error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to duplicate package'
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