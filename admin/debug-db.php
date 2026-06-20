<?php
// Database Debug Tool - Mavic's Resort
// Place this file as 'debug-db.php' in your root directory and run it first

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Mavic's Resort Database Debug Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: red; background: #fff0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: blue; background: #f0f0f8; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: orange; background: #fff8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

// Step 1: Check if config files exist
echo "<h2>1. File Structure Check</h2>";
$files_to_check = [
    'config/database.php',
    'classes/auth.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✓ Found: $file</div>";
    } else {
        echo "<div class='error'>✗ Missing: $file</div>";
    }
}

// Step 2: Test database connection
echo "<h2>2. Database Connection Test</h2>";
try {
    // Include database config
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        
        echo "<div class='info'>Database Configuration:</div>";
        echo "<pre>";
        echo "Host: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
        echo "Database: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
        echo "User: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
        echo "Password: " . (defined('DB_PASS') ? (DB_PASS ? '[SET]' : '[EMPTY]') : 'NOT DEFINED') . "\n";
        echo "</pre>";
        
        // Test connection
        $conn = getDBConnection();
        echo "<div class='success'>✓ Database connection successful</div>";
        
        // Test query
        $stmt = $conn->query("SELECT 1 as test");
        $result = $stmt->fetch();
        if ($result && $result['test'] == 1) {
            echo "<div class='success'>✓ Database query test successful</div>";
        }
        
    } else {
        echo "<div class='error'>✗ Cannot test database - config/database.php not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
}

// Step 3: Check required tables
echo "<h2>3. Database Tables Check</h2>";
try {
    if (isset($conn)) {
        $required_tables = [
            'admin_users',
            'admin_sessions',
            'activity_logs'
        ];
        
        foreach ($required_tables as $table) {
            try {
                $stmt = $conn->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo "<div class='success'>✓ Table exists: $table</div>";
                    
                    // Show table structure
                    $stmt = $conn->query("DESCRIBE $table");
                    $columns = $stmt->fetchAll();
                    
                    echo "<details><summary>Show $table structure</summary>";
                    echo "<table>";
                    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                    foreach ($columns as $col) {
                        echo "<tr>";
                        echo "<td>" . $col['Field'] . "</td>";
                        echo "<td>" . $col['Type'] . "</td>";
                        echo "<td>" . $col['Null'] . "</td>";
                        echo "<td>" . $col['Key'] . "</td>";
                        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                        echo "<td>" . $col['Extra'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table></details>";
                    
                } else {
                    echo "<div class='error'>✗ Table missing: $table</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>✗ Error checking table $table: " . $e->getMessage() . "</div>";
            }
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking tables: " . $e->getMessage() . "</div>";
}

// Step 4: Check admin users
echo "<h2>4. Admin Users Check</h2>";
try {
    if (isset($conn)) {
        $stmt = $conn->query("SELECT id, username, email, full_name, role, status, created_at, last_login FROM admin_users");
        $admins = $stmt->fetchAll();
        
        if (count($admins) > 0) {
            echo "<div class='success'>✓ Found " . count($admins) . " admin user(s)</div>";
            
            echo "<table>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Status</th><th>Created</th><th>Last Login</th></tr>";
            foreach ($admins as $admin) {
                echo "<tr>";
                echo "<td>" . $admin['id'] . "</td>";
                echo "<td>" . $admin['username'] . "</td>";
                echo "<td>" . $admin['email'] . "</td>";
                echo "<td>" . $admin['full_name'] . "</td>";
                echo "<td>" . $admin['role'] . "</td>";
                echo "<td>" . $admin['status'] . "</td>";
                echo "<td>" . $admin['created_at'] . "</td>";
                echo "<td>" . ($admin['last_login'] ?? 'Never') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠ No admin users found</div>";
            echo "<div class='info'>Creating default admin users...</div>";
            
            // Create default admin users
            try {
                $stmt = $conn->prepare("
                    INSERT INTO admin_users (username, email, password, full_name, role, status, created_at)
                    VALUES 
                    ('admin', 'admin@mavicsresort.com', :password, 'Admin User', 'admin', 'active', NOW()),
                    ('superadmin', 'superadmin@mavicsresort.com', :password, 'Super Admin', 'superadmin', 'active', NOW())
                ");
                
                $password = password_hash('password', PASSWORD_DEFAULT);
                $stmt->bindParam(':password', $password);
                $stmt->execute();
                
                echo "<div class='success'>✓ Default admin users created successfully</div>";
                echo "<div class='info'>
                    <strong>Default Login Credentials:</strong><br>
                    Username: admin, Password: password<br>
                    Username: superadmin, Password: password
                </div>";
                
            } catch (Exception $e) {
                echo "<div class='error'>✗ Error creating admin users: " . $e->getMessage() . "</div>";
            }
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking admin users: " . $e->getMessage() . "</div>";
}

// Step 5: Test login functionality
echo "<h2>5. Login Function Test</h2>";
try {
    if (file_exists('classes/auth.php')) {
        require_once 'classes/auth.php';
        
        $auth = new Auth();
        
        echo "<div class='info'>Testing login with superadmin/password...</div>";
        
        // Test login
        $result = $auth->adminLogin('superadmin', 'password');
        
        echo "<div class='info'>Login result:</div>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        
        if ($result['success']) {
            echo "<div class='success'>✓ Login test successful</div>";
        } else {
            echo "<div class='error'>✗ Login test failed: " . $result['message'] . "</div>";
        }
        
    } else {
        echo "<div class='error'>✗ Cannot test login - classes/auth.php not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Login test error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>Full error trace:</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Step 6: Session test
echo "<h2>6. Session Test</h2>";
try {
    session_start();
    echo "<div class='success'>✓ PHP sessions working</div>";
    
    if (function_exists('startSecureSession')) {
        startSecureSession();
        echo "<div class='success'>✓ Custom secure session function working</div>";
    } else {
        echo "<div class='warning'>⚠ startSecureSession function not found</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Session test error: " . $e->getMessage() . "</div>";
}

// Step 7: Check PHP error log
echo "<h2>7. Recent PHP Errors</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "<div class='info'>Error log location: $error_log</div>";
    $errors = file_get_contents($error_log);
    $recent_errors = array_slice(explode("\n", $errors), -20);
    echo "<pre>" . implode("\n", $recent_errors) . "</pre>";
} else {
    echo "<div class='info'>PHP error log not found or not configured</div>";
}

echo "<h2>Debug Complete</h2>";
echo "<div class='info'>If all tests pass, your login should work. If not, the errors above will help identify the issue.</div>";
?>