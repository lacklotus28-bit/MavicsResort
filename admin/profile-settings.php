<?php
// Enhanced Profile Settings - Mavic's Resort (FIXED VERSION)
// Add error handling and proper JSON output

// Start output buffering to catch any unwanted output
ob_start();

// Set error reporting to prevent notices/warnings from appearing
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once 'config/database.php';
require_once 'classes/auth.php';
require_once 'includes/admin-helpers.php';

$auth = new Auth();

// Check authentication
if (!$auth->isAdminLoggedIn()) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    header("Location: admin-login.php");
    exit;
}

$admin = $auth->getCurrentAdmin();
$page_title = 'Profile Settings';

// Initialize variables
$response_data = [];
$conn = null;

try {
    $conn = getDBConnection();
} catch (Exception $e) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    die('Database connection failed');
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    try {
        // Clean any output buffer content
        ob_clean();
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_profile':
                $response_data = handleProfileUpdate($conn, $admin, $_POST);
                break;
                
            case 'change_password':
                $response_data = handlePasswordChange($auth, $admin, $_POST);
                break;
                
            case 'upload_photo':
                $response_data = handlePhotoUpload($conn, $admin, $_FILES);
                break;
                
            case 'remove_photo':
                $response_data = handlePhotoRemoval($conn, $admin);
                break;
                
            case 'create_admin':
                if ($admin['role'] === 'superadmin') {
                    $response_data = handleAdminCreation($conn, $admin, $_POST);
                } else {
                    throw new Exception('Insufficient permissions');
                }
                break;
                
            case 'update_admin_status':
                if ($admin['role'] === 'superadmin') {
                    $response_data = handleAdminStatusUpdate($conn, $admin, $_POST);
                } else {
                    throw new Exception('Insufficient permissions');
                }
                break;
                
            case 'delete_admin':
                if ($admin['role'] === 'superadmin') {
                    $response_data = handleAdminDeletion($conn, $admin, $_POST);
                } else {
                    throw new Exception('Insufficient permissions');
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
        $response_data['success'] = true;
        
    } catch (Exception $e) {
        error_log("Profile Settings Error: " . $e->getMessage());
        $response_data = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
    
    // Ensure clean JSON output
    header('Content-Type: application/json');
    echo json_encode($response_data);
    exit;
}

// Get current admin details from database for regular page load
try {
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$admin['id']]);
    $admin_details = $stmt->fetch();
    
    // Get all admins if superadmin
    $all_admins = [];
    if ($admin['role'] === 'superadmin') {
        $stmt = $conn->query("SELECT id, username, email, full_name, role, status, created_at, last_login FROM admin_users ORDER BY created_at DESC");
        $all_admins = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log("Profile settings database error: " . $e->getMessage());
    $admin_details = $admin; // Fallback to session data
    $all_admins = [];
}

// Handler functions with improved error handling
function handleProfileUpdate($conn, $admin, $data) {
    $full_name = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    
    // Validation
    if (empty($full_name)) {
        throw new Exception('Full name is required');
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address');
    }
    
    try {
        // Check if email is already taken by another admin
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $admin['id']]);
        if ($stmt->fetch()) {
            throw new Exception('Email address is already in use');
        }
        
        // Check if phone column exists (to avoid SQL errors)
        $stmt = $conn->prepare("SHOW COLUMNS FROM admin_users LIKE 'phone'");
        $stmt->execute();
        $phoneColumnExists = $stmt->fetch();
        
        // Update profile based on whether phone column exists
        if ($phoneColumnExists) {
            $stmt = $conn->prepare("
                UPDATE admin_users 
                SET full_name = ?, email = ?, phone = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $email, $phone, $admin['id']]);
        } else {
            $stmt = $conn->prepare("
                UPDATE admin_users 
                SET full_name = ?, email = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $email, $admin['id']]);
        }
        
        // Update session
        $_SESSION['admin_name'] = $full_name;
        $_SESSION['admin_email'] = $email;
        
        // Log activity if function exists
        if (function_exists('logActivity')) {
            logActivity($conn, $admin['id'], 'profile_update', 'admin_users', $admin['id'], 
                       ['full_name' => $admin['full_name'] ?? '', 'email' => $admin['email'] ?? ''], 
                       ['full_name' => $full_name, 'email' => $email]);
        }
        
        return [
            'message' => 'Profile updated successfully',
            'data' => [
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Profile update database error: " . $e->getMessage());
        throw new Exception('Database error occurred while updating profile');
    }
}

function handlePasswordChange($auth, $admin, $data) {
    $current_password = $data['current_password'] ?? '';
    $new_password = $data['new_password'] ?? '';
    $confirm_password = $data['confirm_password'] ?? '';
    
    // Validation
    if (empty($current_password)) {
        throw new Exception('Current password is required');
    }
    
    if (empty($new_password)) {
        throw new Exception('New password is required');
    }
    
    if (strlen($new_password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }
    
    if ($new_password !== $confirm_password) {
        throw new Exception('New passwords do not match');
    }
    
    // Debug password validation - log what we're checking
    error_log("Password validation debug:");
    error_log("Password length: " . strlen($new_password));
    error_log("Has lowercase: " . (preg_match('/[a-z]/', $new_password) ? 'YES' : 'NO'));
    error_log("Has uppercase: " . (preg_match('/[A-Z]/', $new_password) ? 'YES' : 'NO'));
    error_log("Has number: " . (preg_match('/[0-9]/', $new_password) ? 'YES' : 'NO'));
    error_log("Has special: " . (preg_match('/[@$!%*?&]/', $new_password) ? 'YES' : 'NO'));
    
    // Individual password strength checks
    $has_lowercase = preg_match('/[a-z]/', $new_password);
    $has_uppercase = preg_match('/[A-Z]/', $new_password);
    $has_number = preg_match('/[0-9]/', $new_password);
    $has_special = preg_match('/[@$!%*?&]/', $new_password);
    
    if (!$has_lowercase) {
        throw new Exception('Password must contain at least one lowercase letter');
    }
    if (!$has_uppercase) {
        throw new Exception('Password must contain at least one uppercase letter');
    }
    if (!$has_number) {
        throw new Exception('Password must contain at least one number');
    }
    if (!$has_special) {
        throw new Exception('Password must contain at least one special character (@$!%*?&)');
    }
    
    try {
        // Change password using Auth class
        $result = $auth->changePassword($admin['id'], $current_password, $new_password);
        
        if (!$result['success']) {
            throw new Exception($result['message']);
        }
        
        return ['message' => $result['message']];
        
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

function handlePhotoUpload($conn, $admin, $files) {
    if (!isset($files['profile_photo']) || $files['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }
    
    $file = $files['profile_photo'];
    $upload_dir = 'uploads/profiles/';
    
    try {
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $file_info = getimagesize($file['tmp_name']);
        if (!$file_info || !in_array($file_info['mime'], $allowed_types)) {
            throw new Exception('Only JPG, PNG, and WEBP images are allowed');
        }
        
        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('File size must be less than 2MB');
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'admin_' . $admin['id'] . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        // Remove old photo if exists
        $stmt = $conn->prepare("SELECT profile_photo FROM admin_users WHERE id = ?");
        $stmt->execute([$admin['id']]);
        $current_admin = $stmt->fetch();
        
        if (!empty($current_admin['profile_photo']) && file_exists($current_admin['profile_photo'])) {
            unlink($current_admin['profile_photo']);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        // Update database
        $stmt = $conn->prepare("UPDATE admin_users SET profile_photo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$filepath, $admin['id']]);
        
        // Update session
        $_SESSION['admin_photo'] = $filepath;
        
        // Log activity if function exists
        if (function_exists('logActivity')) {
            logActivity($conn, $admin['id'], 'profile_photo_update', 'admin_users', $admin['id']);
        }
        
        return [
            'message' => 'Profile photo updated successfully',
            'photo_path' => $filepath
        ];
        
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

function handlePhotoRemoval($conn, $admin) {
    try {
        // Get current photo path
        $stmt = $conn->prepare("SELECT profile_photo FROM admin_users WHERE id = ?");
        $stmt->execute([$admin['id']]);
        $current_admin = $stmt->fetch();
        
        // Remove file if exists
        if (!empty($current_admin['profile_photo']) && file_exists($current_admin['profile_photo'])) {
            unlink($current_admin['profile_photo']);
        }
        
        // Update database
        $stmt = $conn->prepare("UPDATE admin_users SET profile_photo = NULL, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);
        
        // Update session
        if (isset($_SESSION['admin_photo'])) {
            unset($_SESSION['admin_photo']);
        }
        
        // Log activity if function exists
        if (function_exists('logActivity')) {
            logActivity($conn, $admin['id'], 'profile_photo_remove', 'admin_users', $admin['id']);
        }
        
        return ['message' => 'Profile photo removed successfully'];
        
    } catch (Exception $e) {
        throw new Exception('Failed to remove profile photo: ' . $e->getMessage());
    }
}

function handleAdminCreation($conn, $admin, $data) {
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $full_name = trim($data['full_name'] ?? '');
    $role = $data['role'] ?? 'admin';
    $password = $data['password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        throw new Exception('All fields are required');
    }
    
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address');
    }
    
    if (!in_array($role, ['admin', 'superadmin'])) {
        throw new Exception('Invalid role specified');
    }
    
    try {
        // Check if username/email already exists
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            throw new Exception('Username or email already exists');
        }
        
        // Create admin
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO admin_users (username, email, password, full_name, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([$username, $email, $hashed_password, $full_name, $role]);
        
        $new_admin_id = $conn->lastInsertId();
        
        // Log activity if function exists
        if (function_exists('logActivity')) {
            logActivity($conn, $admin['id'], 'admin_create', 'admin_users', $new_admin_id, 
                       null, ['username' => $username, 'email' => $email, 'role' => $role]);
        }
        
        return [
            'message' => 'Administrator created successfully',
            'admin_id' => $new_admin_id,
            'reload_table' => true
        ];
        
    } catch (PDOException $e) {
        error_log("Admin creation error: " . $e->getMessage());
        throw new Exception('Failed to create administrator');
    }
}

function handleAdminStatusUpdate($conn, $admin, $data) {
    $admin_id = intval($data['admin_id'] ?? 0);
    $status = $data['status'] ?? '';
    
    if ($admin_id === $admin['id']) {
        throw new Exception('You cannot change your own status');
    }
    
    if (!in_array($status, ['active', 'inactive'])) {
        throw new Exception('Invalid status specified');
    }
    
    try {
        // Get admin info for logging
        $stmt = $conn->prepare("SELECT username, full_name FROM admin_users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $target_admin = $stmt->fetch();
        
        if (!$target_admin) {
            throw new Exception('Administrator not found');
        }
        
        // Update status
        $stmt = $conn->prepare("UPDATE admin_users SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $admin_id]);
        
        // Log activity if function exists
        if (function_exists('logActivity')) {
            logActivity($conn, $admin['id'], 'admin_status_update', 'admin_users', $admin_id, 
                       null, ['status' => $status]);
        }
        
        return [
            'message' => "Administrator {$target_admin['full_name']} has been " . ($status === 'active' ? 'activated' : 'deactivated'),
            'reload_table' => true
        ];
        
    } catch (PDOException $e) {
        error_log("Admin status update error: " . $e->getMessage());
        throw new Exception('Failed to update administrator status');
    }
}

function handleAdminDeletion($conn, $admin, $data) {
    $admin_id = intval($data['admin_id'] ?? 0);
    
    if ($admin_id === $admin['id']) {
        throw new Exception('You cannot delete your own account');
    }
    
    try {
        // Get admin info for logging
        $stmt = $conn->prepare("SELECT username, full_name, profile_photo FROM admin_users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $target_admin = $stmt->fetch();
        
        if (!$target_admin) {
            throw new Exception('Administrator not found');
        }
        
        // Remove profile photo if exists
        if (!empty($target_admin['profile_photo']) && file_exists($target_admin['profile_photo'])) {
            unlink($target_admin['profile_photo']);
        }
        
        // Delete admin
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->execute([$admin_id]);
        
        // Log activity if function exists
        if (function_exists('logActivity')) {
            logActivity($conn, $admin['id'], 'admin_delete', 'admin_users', $admin_id, 
                       ['username' => $target_admin['username']], null);
        }
        
        return [
            'message' => "Administrator {$target_admin['full_name']} has been deleted",
            'reload_table' => true
        ];
        
    } catch (PDOException $e) {
        error_log("Admin deletion error: " . $e->getMessage());
        throw new Exception('Failed to delete administrator');
    }
}

// Clean output buffer for regular page load
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Mavics Resort</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/topbar.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/profile-settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <?php include 'includes/topbar.php'; ?>

        <!-- Profile Settings Content -->
        <div class="profile-settings-content">
            <div class="settings-container">
                <!-- Settings Navigation -->
                <nav class="settings-nav">
                    <div class="nav-header">
                        <h2>Settings</h2>
                    </div>
                    <ul class="nav-tabs">
                        <li><a href="#profile" class="nav-tab active" data-tab="profile">
                            <i class="fas fa-user"></i>Profile Information
                        </a></li>
                        <li><a href="#security" class="nav-tab" data-tab="security">
                            <i class="fas fa-shield-alt"></i>Account Security
                        </a></li>
                        <li><a href="#photo" class="nav-tab" data-tab="photo">
                            <i class="fas fa-camera"></i>Profile Photo
                        </a></li>
                        <?php if ($admin['role'] === 'superadmin'): ?>
                        <li><a href="#admin-management" class="nav-tab" data-tab="admin-management">
                            <i class="fas fa-users-cog"></i>Admin Management
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <!-- Settings Content -->
                <div class="settings-content">
                    <!-- Profile Information Tab -->
                    <div class="tab-content active" id="profile">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-user"></i> Personal Information</h3>
                                <p>Update your personal details and contact information</p>
                            </div>
                            <div class="card-body">
                                <form id="profileForm" class="profile-form">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="full_name">Full Name *</label>
                                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($admin_details['full_name'] ?? $admin['full_name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="username">Username</label>
                                            <input type="text" id="username" class="form-control" 
                                                   value="<?php echo htmlspecialchars($admin_details['username'] ?? $admin['username'] ?? ''); ?>" disabled>
                                            <small class="form-text">Username cannot be changed</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="email">Email Address *</label>
                                            <input type="email" id="email" name="email" class="form-control" 
                                                   value="<?php echo htmlspecialchars($admin_details['email'] ?? $admin['email'] ?? ''); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="phone">Contact Number</label>
                                            <input type="tel" id="phone" name="phone" class="form-control" 
                                                   value="<?php echo htmlspecialchars($admin_details['phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="role">Role</label>
                                            <input type="text" id="role" class="form-control" 
                                                   value="<?php echo ucfirst($admin_details['role'] ?? $admin['role'] ?? ''); ?>" disabled>
                                        </div>
                                        <div class="form-group">
                                            <label for="created_at">Member Since</label>
                                            <input type="text" id="created_at" class="form-control" 
                                                   value="<?php echo isset($admin_details['created_at']) ? date('M j, Y', strtotime($admin_details['created_at'])) : 'N/A'; ?>" disabled>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Profile
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Account Security Tab -->
                    <div class="tab-content" id="security">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-shield-alt"></i> Change Password</h3>
                                <p>Keep your account secure with a strong password</p>
                            </div>
                            <div class="card-body">
                                <form id="passwordForm" class="password-form">
                                    <div class="form-group">
                                        <label for="current_password">Current Password *</label>
                                        <div class="password-input">
                                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                                            <button type="button" class="password-toggle" data-target="current_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password">New Password *</label>
                                        <div class="password-input">
                                            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                                            <button type="button" class="password-toggle" data-target="new_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength" id="passwordStrength">
                                            <div class="strength-bar">
                                                <div class="strength-fill"></div>
                                            </div>
                                            <span class="strength-text">Enter a password</span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password *</label>
                                        <div class="password-input">
                                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                                            <button type="button" class="password-toggle" data-target="confirm_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-match" id="passwordMatch"></div>
                                    </div>
                                    
                                    <div class="password-requirements">
                                        <h4>Password Requirements:</h4>
                                        <ul>
                                            <li id="req-length">At least 8 characters</li>
                                            <li id="req-upper">At least one uppercase letter</li>
                                            <li id="req-lower">At least one lowercase letter</li>
                                            <li id="req-number">At least one number</li>
                                            <li id="req-special">At least one special character</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-key"></i> Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Photo Tab -->
                    <div class="tab-content" id="photo">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-camera"></i> Profile Photo</h3>
                                <p>Upload or change your profile picture</p>
                            </div>
                            <div class="card-body">
                                <div class="photo-section">
                                    <div class="current-photo">
                                        <div class="photo-preview">
                                            <?php if (!empty($admin_details['profile_photo']) && file_exists($admin_details['profile_photo'])): ?>
                                                <img src="<?php echo htmlspecialchars($admin_details['profile_photo']); ?>" alt="Profile Photo" id="currentPhoto">
                                            <?php else: ?>
                                                <div class="photo-placeholder" id="photoPlaceholder">
                                                    <?php echo strtoupper(substr($admin['full_name'] ?? 'A', 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="photo-info">
                                            <h4><?php echo htmlspecialchars($admin['full_name']); ?></h4>
                                            <p><?php echo ucfirst($admin['role']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="photo-actions">
                                        <form id="photoUploadForm" class="upload-form">
                                            <div class="upload-area" id="uploadArea">
                                                <div class="upload-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <p>Click to upload or drag and drop</p>
                                                <small>JPG, PNG or WEBP (Max 2MB)</small>
                                                <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/webp" hidden>
                                            </div>
                                            
                                            <div class="upload-preview" id="uploadPreview" style="display: none;">
                                                <img src="" alt="Preview" id="previewImage">
                                                <div class="preview-actions">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-upload"></i> Upload Photo
                                                    </button>
                                                    <button type="button" class="btn btn-outline" onclick="ProfileSettings.cancelUpload()">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        
                                        <?php if (!empty($admin_details['profile_photo'])): ?>
                                        <button type="button" id="removePhotoBtn" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Remove Photo
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Management Tab (Superadmin only) -->
                    <?php if ($admin['role'] === 'superadmin'): ?>
                    <div class="tab-content" id="admin-management">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-users-cog"></i> Admin Management</h3>
                                <p>Create and manage administrator accounts</p>
                            </div>
                            <div class="card-body">
                                <!-- Create New Admin Form -->
                                <div class="admin-create-section">
                                    <h4>Create New Administrator</h4>
                                    <form id="adminCreateForm" class="admin-form">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="new_username">Username *</label>
                                                <input type="text" id="new_username" name="username" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="new_email">Email Address *</label>
                                                <input type="email" id="new_email" name="email" class="form-control" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="new_full_name">Full Name *</label>
                                                <input type="text" id="new_full_name" name="full_name" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="new_role">Role *</label>
                                                <select id="new_role" name="role" class="form-control" required>
                                                    <option value="admin">Admin</option>
                                                    <option value="superadmin">Super Admin</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="new_password">Password *</label>
                                            <input type="password" id="new_password" name="password" class="form-control" required minlength="8">
                                            <small class="form-text">Minimum 8 characters</small>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-user-plus"></i> Create Admin
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Existing Admins -->
                                <div class="admin-list-section">
                                    <h4>Existing Administrators</h4>
                                    
                                    <!-- Desktop Table View -->
                                    <div class="table-responsive desktop-table" id="adminTableContainer">
                                        <table class="table admin-table" id="adminTable">
                                            <thead>
                                                <tr>
                                                    <th>Admin</th>
                                                    <th>Email</th>
                                                    <th>Role</th>
                                                    <th>Status</th>
                                                    <th>Last Login</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($all_admins as $admin_user): ?>
                                                <tr data-admin-id="<?php echo $admin_user['id']; ?>">
                                                    <td>
                                                        <div class="admin-info">
                                                            <div class="admin-avatar">
                                                                <?php echo strtoupper(substr($admin_user['full_name'], 0, 1)); ?>
                                                            </div>
                                                            <div class="admin-details">
                                                                <span class="admin-name"><?php echo htmlspecialchars($admin_user['full_name']); ?></span>
                                                                <span class="admin-username">@<?php echo htmlspecialchars($admin_user['username']); ?></span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($admin_user['email']); ?></td>
                                                    <td>
                                                        <span class="role-badge <?php echo $admin_user['role']; ?>">
                                                            <?php echo ucfirst($admin_user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="status <?php echo $admin_user['status']; ?>">
                                                            <?php echo ucfirst($admin_user['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo $admin_user['last_login'] ? date('M j, Y g:i A', strtotime($admin_user['last_login'])) : 'Never'; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($admin_user['id'] != $admin['id']): ?>
                                                        <div class="admin-actions">
                                                            <button type="button" class="btn btn-sm <?php echo $admin_user['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?>" 
                                                                    onclick="ProfileSettings.toggleAdminStatus(<?php echo $admin_user['id']; ?>, '<?php echo $admin_user['status'] === 'active' ? 'inactive' : 'active'; ?>', '<?php echo htmlspecialchars($admin_user['full_name']); ?>')">
                                                                <i class="fas <?php echo $admin_user['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                                <?php echo $admin_user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger" 
                                                                    onclick="ProfileSettings.deleteAdmin(<?php echo $admin_user['id']; ?>, '<?php echo htmlspecialchars($admin_user['full_name']); ?>')">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                        <?php else: ?>
                                                        <span class="text-muted">Current User</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Mobile Card View -->
                                    <div class="mobile-cards" style="display: none;" id="adminCardsContainer">
                                        <?php foreach ($all_admins as $admin_user): ?>
                                        <div class="admin-card" data-admin-id="<?php echo $admin_user['id']; ?>">
                                            <div class="admin-card-header">
                                                <div class="admin-avatar">
                                                    <?php echo strtoupper(substr($admin_user['full_name'], 0, 1)); ?>
                                                </div>
                                                <div class="admin-details">
                                                    <span class="admin-name"><?php echo htmlspecialchars($admin_user['full_name']); ?></span>
                                                    <span class="admin-username">@<?php echo htmlspecialchars($admin_user['username']); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="admin-card-body">
                                                <div class="admin-card-field">
                                                    <span class="admin-card-label">Email:</span>
                                                    <span class="admin-card-value"><?php echo htmlspecialchars($admin_user['email']); ?></span>
                                                </div>
                                                
                                                <div class="admin-card-field">
                                                    <span class="admin-card-label">Role:</span>
                                                    <span class="role-badge <?php echo $admin_user['role']; ?>">
                                                        <?php echo ucfirst($admin_user['role']); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="admin-card-field">
                                                    <span class="admin-card-label">Status:</span>
                                                    <span class="status <?php echo $admin_user['status']; ?>">
                                                        <?php echo ucfirst($admin_user['status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="admin-card-field">
                                                    <span class="admin-card-label">Last Login:</span>
                                                    <span class="admin-card-value">
                                                        <?php echo $admin_user['last_login'] ? date('M j, Y g:i A', strtotime($admin_user['last_login'])) : 'Never'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <?php if ($admin_user['id'] != $admin['id']): ?>
                                            <div class="admin-card-actions">
                                                <button type="button" class="btn btn-sm <?php echo $admin_user['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> w-100" 
                                                        onclick="ProfileSettings.toggleAdminStatus(<?php echo $admin_user['id']; ?>, '<?php echo $admin_user['status'] === 'active' ? 'inactive' : 'active'; ?>', '<?php echo htmlspecialchars($admin_user['full_name']); ?>')">
                                                    <i class="fas <?php echo $admin_user['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                    <?php echo $admin_user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger w-100" style="margin-top: 8px;" 
                                                        onclick="ProfileSettings.deleteAdmin(<?php echo $admin_user['id']; ?>, '<?php echo htmlspecialchars($admin_user['full_name']); ?>')">
                                                    <i class="fas fa-trash"></i> Delete Admin
                                                </button>
                                            </div>
                                            <?php else: ?>
                                            <div class="admin-card-actions">
                                                <span class="text-muted">Current User</span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Response Modal -->
    <div id="responseModal" class="modal" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="responseModalTitle">Response</h5>
                    <button type="button" class="btn-close" onclick="ProfileSettings.closeModal('responseModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="modal-icon" id="responseModalIcon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <p id="responseModalMessage">Message</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="ProfileSettings.closeModal('responseModal')">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" onclick="ProfileSettings.closeModal('confirmModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="modal-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p id="confirmModalMessage">Are you sure?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="ProfileSettings.closeModal('confirmModal')">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmModalAction">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <?php if (file_exists('includes/logout-modal.php')): ?>
        <?php include 'includes/logout-modal.php'; ?>
    <?php endif; ?>

    <script src="js/admin-main.js"></script>
    <script src="js/profile-settings.js"></script>
</body>
</html>