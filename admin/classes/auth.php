<?php
// Enhanced Authentication Class for Debugging - Mavic's Resort
// IMPORTANT: Use this for debugging only, then switch back to the original for production

require_once 'config/database.php';

class Auth {
    private $conn;
    private $debug_mode = true; // Set to false for production
    
    public function __construct() {
        try {
            $this->conn = getDBConnection();
            if ($this->debug_mode) {
                error_log("Auth: Database connection established successfully");
            }
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log("Auth: Database connection failed - " . $e->getMessage());
            }
            throw $e;
        }
    }
    
    // Admin login with enhanced debugging
public function adminLogin($username, $password) {
    try {
        // Fix: Use separate parameter names for each username placeholder
        $query = "SELECT id, username, email, password, full_name, role, status 
                 FROM admin_users 
                 WHERE (username = :username1 OR email = :username2) 
                 AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username1', $username);
        $stmt->bindParam(':username2', $username);
        $stmt->execute();
        
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Update last login
            $this->updateLastLogin($admin['id']);
            
            // Create session
            $this->createAdminSession($admin);
            
            // Log the login
            $this->logActivity($admin['id'], 'admin_login', null, null, null, null);
            
            return [
                'success' => true,
                'admin' => $admin,
                'message' => 'Login successful'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred during login'
        ];
    }
}
    
    // Create admin session
    private function createAdminSession($admin) {
        try {
            startSecureSession();
            
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['login_time'] = time();
            
            // Generate session token
            $token = bin2hex(random_bytes(32));
            $_SESSION['session_token'] = $token;
            
            if ($this->debug_mode) {
                error_log("Auth: Session created for user ID: {$admin['id']}, Token length: " . strlen($token));
            }
            
            // Store session in database
            $this->storeSessionToken($admin['id'], $token);
            
        } catch (Exception $e) {
            error_log("Auth: Session creation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Store session token in database
    private function storeSessionToken($admin_id, $token) {
        try {
            // Check if admin_sessions table exists
            $stmt = $this->conn->query("SHOW TABLES LIKE 'admin_sessions'");
            if ($stmt->rowCount() === 0) {
                if ($this->debug_mode) {
                    error_log("Auth: admin_sessions table does not exist, skipping session storage");
                }
                return; // Skip session storage if table doesn't exist
            }
            
            $query = "INSERT INTO admin_sessions 
                     (admin_id, session_token, ip_address, user_agent, expires_at) 
                     VALUES (:admin_id, :token, :ip_address, :user_agent, :expires_at)";
            
            $stmt = $this->conn->prepare($query);
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            $stmt->bindParam(':expires_at', $expires_at);
            
            $stmt->execute();
            
            if ($this->debug_mode) {
                error_log("Auth: Session token stored successfully for admin ID: $admin_id");
            }
            
        } catch(PDOException $e) {
            error_log("Auth: Session storage error: " . $e->getMessage());
            // Don't throw error - session storage is not critical for basic login
        }
    }
    
    // Update last login timestamp
    private function updateLastLogin($admin_id) {
        try {
            $query = "UPDATE admin_users SET last_login = NOW() WHERE id = :admin_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->execute();
            
            if ($this->debug_mode) {
                error_log("Auth: Last login updated for admin ID: $admin_id");
            }
        } catch(PDOException $e) {
            error_log("Auth: Update last login error: " . $e->getMessage());
        }
    }
    
    // Check if admin is logged in
    public function isAdminLoggedIn() {
        try {
            startSecureSession();
            
            if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
                if ($this->debug_mode) {
                    error_log("Auth: No admin session found");
                }
                return false;
            }
            
            // Check session timeout (24 hours)
            if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 86400) {
                if ($this->debug_mode) {
                    error_log("Auth: Session timeout for admin ID: " . ($_SESSION['admin_id'] ?? 'unknown'));
                }
                $this->adminLogout();
                return false;
            }
            
            // Verify session token if available
            if (isset($_SESSION['session_token'])) {
                return $this->verifySessionToken($_SESSION['admin_id'], $_SESSION['session_token']);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Auth: Session check error: " . $e->getMessage());
            return false;
        }
    }
    
    // Verify session token
    private function verifySessionToken($admin_id, $token) {
        try {
            // Check if admin_sessions table exists
            $stmt = $this->conn->query("SHOW TABLES LIKE 'admin_sessions'");
            if ($stmt->rowCount() === 0) {
                if ($this->debug_mode) {
                    error_log("Auth: admin_sessions table does not exist, skipping token verification");
                }
                return true; // Allow login without token verification if table doesn't exist
            }
            
            $query = "SELECT id FROM admin_sessions 
                     WHERE admin_id = :admin_id 
                     AND session_token = :token 
                     AND expires_at > NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            $valid = $stmt->rowCount() > 0;
            
            if ($this->debug_mode) {
                error_log("Auth: Token verification result for admin ID $admin_id: " . ($valid ? 'valid' : 'invalid'));
            }
            
            return $valid;
            
        } catch(PDOException $e) {
            error_log("Auth: Session verification error: " . $e->getMessage());
            return true; // Allow access on verification error to prevent lockout
        }
    }
    
    // Admin logout
    public function adminLogout() {
        try {
            startSecureSession();
            
            if (isset($_SESSION['admin_id']) && isset($_SESSION['session_token'])) {
                // Log the logout
                $this->logActivity($_SESSION['admin_id'], 'admin_logout', null, null, null, null);
                
                // Remove session from database
                $this->removeSessionToken($_SESSION['admin_id'], $_SESSION['session_token']);
            }
            
            // Clear session
            $_SESSION = [];
            
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            session_destroy();
            
            return ['success' => true, 'message' => 'Logged out successfully'];
            
        } catch (Exception $e) {
            error_log("Auth: Logout error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Logout error'];
        }
    }
    
    // Remove session token from database
    private function removeSessionToken($admin_id, $token) {
        try {
            $query = "DELETE FROM admin_sessions 
                     WHERE admin_id = :admin_id AND session_token = :token";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Auth: Session removal error: " . $e->getMessage());
        }
    }
    
    // Get current admin info
    public function getCurrentAdmin() {
        if (!$this->isAdminLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['admin_id'] ?? null,
            'username' => $_SESSION['admin_username'] ?? null,
            'email' => $_SESSION['admin_email'] ?? null,
            'full_name' => $_SESSION['admin_name'] ?? null,
            'role' => $_SESSION['admin_role'] ?? null
        ];
    }
    
    // Check admin permissions
    public function hasPermission($permission) {
        $admin = $this->getCurrentAdmin();
        if (!$admin) return false;
        
        // Super admin has all permissions
        if ($admin['role'] === 'superadmin') {
            return true;
        }
        
        // Define admin permissions
        $adminPermissions = [
            'view_dashboard',
            'manage_bookings',
            'manage_venues',
            'view_reports',
            'manage_payments'
        ];
        
        // Check if admin has specific permission
        return in_array($permission, $adminPermissions);
    }
    
    // Log admin activities
    private function logActivity($admin_id, $action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
        try {
            // Check if activity_logs table exists
            $stmt = $this->conn->query("SHOW TABLES LIKE 'activity_logs'");
            if ($stmt->rowCount() === 0) {
                if ($this->debug_mode) {
                    error_log("Auth: activity_logs table does not exist, skipping activity logging");
                }
                return;
            }
            
            $query = "INSERT INTO activity_logs 
                     (admin_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                     VALUES (:admin_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)";
            
            $stmt = $this->conn->prepare($query);
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $old_values_json = $old_values ? json_encode($old_values) : null;
            $new_values_json = $new_values ? json_encode($new_values) : null;
            
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':table_name', $table_name);
            $stmt->bindParam(':record_id', $record_id);
            $stmt->bindParam(':old_values', $old_values_json);
            $stmt->bindParam(':new_values', $new_values_json);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            
            $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Auth: Activity log error: " . $e->getMessage());
        }
    }
    
    // Clean expired sessions
    public function cleanExpiredSessions() {
        try {
            $query = "DELETE FROM admin_sessions WHERE expires_at < NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->rowCount();
            
        } catch(PDOException $e) {
            error_log("Auth: Clean expired sessions error: " . $e->getMessage());
            return 0;
        }
    }
    
    // Change admin password
    public function changePassword($admin_id, $current_password, $new_password) {
        try {
            // Verify current password
            $query = "SELECT password FROM admin_users WHERE id = :admin_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->execute();
            
            $admin = $stmt->fetch();
            
            if (!$admin || !password_verify($current_password, $admin['password'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE admin_users SET password = :password, updated_at = NOW() WHERE id = :admin_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->execute();
            
            // Log the change
            $this->logActivity($admin_id, 'password_change', 'admin_users', $admin_id, null, null);
            
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
            
        } catch(PDOException $e) {
            error_log("Auth: Change password error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $this->debug_mode ? $e->getMessage() : 'An error occurred while changing password'
            ];
        }
    }
}
?>