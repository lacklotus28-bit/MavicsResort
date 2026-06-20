<?php
session_start();

require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit();
}

try {
    $conn = getDBConnection();
    
    $query = "SELECT 
                c.id,
                c.first_name,
                c.last_name,
                c.email,
                c.phone,
                c.address,
                c.status,
                c.email_verified,
                c.created_at,
                c.last_login,
                COUNT(DISTINCT b.id) as total_bookings
              FROM customers c
              LEFT JOIN bookings b ON c.id = b.customer_id
              WHERE 1=1";
    
    $params = [];
    
    // Check if exporting specific users
    if (isset($_GET['ids'])) {
        $ids = explode(',', $_GET['ids']);
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $query .= " AND c.id IN ($placeholders)";
        $params = $ids;
    } else {
        // Export all with filters
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $query .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $query .= " AND c.status = ?";
            $params[] = $_GET['status'];
        }
    }
    
    $query .= " GROUP BY c.id ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_His') . '.csv"');
    
    // Create file pointer
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'User ID',
        'First Name',
        'Last Name',
        'Email',
        'Phone',
        'Address',
        'Status',
        'Email Verified',
        'Total Bookings',
        'Registration Date',
        'Last Login'
    ]);
    
    // Add data rows
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['first_name'],
            $user['last_name'],
            $user['email'],
            $user['phone'] ?? '',
            $user['address'] ?? '',
            $user['status'],
            $user['email_verified'] ? 'Yes' : 'No',
            $user['total_bookings'],
            date('Y-m-d H:i:s', strtotime($user['created_at'])),
            $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never'
        ]);
    }
    
    fclose($output);
    
    // Log the activity
    $logStmt = $conn->prepare("
        INSERT INTO activity_logs (admin_id, action, ip_address, user_agent)
        VALUES (:admin_id, 'export_users', :ip, :user_agent)
    ");
    
    $logStmt->execute([
        'admin_id' => $_SESSION['admin_id'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);
    
    exit();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error exporting users: ' . $e->getMessage();
    header('Location: ../manage-users.php');
    exit();
}
?>
