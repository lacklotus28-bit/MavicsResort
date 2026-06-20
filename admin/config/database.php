<?php
// Database Configuration - Mavic's Resort
// config/database.php

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'mavics_resort');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Connection options
$db_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

// Global database connection variable
$pdo = null;

/**
 * Get database connection
 * @return PDO Database connection object
 * @throws PDOException If connection fails
 */
function getDBConnection() {
    global $pdo, $db_options;
    
    // Return existing connection if available
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $db_options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw new PDOException("Database connection failed");
    }
}

/**
 * Start secure session
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configure secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Test database connection
 * @return array Connection test result
 */
function testDatabaseConnection() {
    try {
        $conn = getDBConnection();
        
        // Test query
        $stmt = $conn->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        if ($result && $result['test'] == 1) {
            return [
                'success' => true,
                'message' => 'Database connection successful',
                'server_info' => $conn->getAttribute(PDO::ATTR_SERVER_INFO)
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Database connection test failed'
            ];
        }
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database connection error: ' . $e->getMessage()
        ];
    }
}

/**
 * Execute database migration/setup
 * @return array Migration result
 */
function setupDatabase() {
    try {
        $conn = getDBConnection();
        
        // Check if tables exist
        $stmt = $conn->query("SHOW TABLES LIKE 'venues'");
        if ($stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Database tables not found. Please import the SQL file first.'
            ];
        }
        
        // Check if default admin users exist
        $stmt = $conn->query("SELECT COUNT(*) as count FROM admin_users");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            // Create default admin users
            $stmt = $conn->prepare("
                INSERT INTO admin_users (username, email, password, full_name, role, status)
                VALUES 
                ('admin', 'admin@mavicsresort.com', :password, 'Admin User', 'admin', 'active'),
                ('superadmin', 'superadmin@mavicsresort.com', :password, 'Super Admin', 'superadmin', 'active')
            ");
            
            $password = password_hash('password', PASSWORD_DEFAULT);
            $stmt->bindParam(':password', $password);
            $stmt->execute();
        }
        
        return [
            'success' => true,
            'message' => 'Database setup completed successfully'
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database setup error: ' . $e->getMessage()
        ];
    }
}

/**
 * Get database statistics
 * @return array Database statistics
 */
function getDatabaseStats() {
    try {
        $conn = getDBConnection();
        $stats = [];
        
        // Get table counts
        $tables = ['venues', 'bookings', 'customers', 'packages', 'admin_users'];
        
        foreach ($tables as $table) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM {$table}");
            $result = $stmt->fetch();
            $stats[$table] = $result['count'];
        }
        
        return [
            'success' => true,
            'data' => $stats
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error getting database stats: ' . $e->getMessage()
        ];
    }
}

// Initialize database connection on file inclusion
try {
    getDBConnection();
} catch (PDOException $e) {
    // Log error but don't stop execution
    error_log("Database initialization warning: " . $e->getMessage());
}
?>