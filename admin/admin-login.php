<?php
// Admin Login - Mavic's Resort
require_once 'config/database.php';
require_once 'classes/auth.php';

$auth = new Auth();

// Check if already logged in
if ($auth->isAdminLoggedIn()) {
    header("Location: admin-dashboard.php");
    exit;
}


$error_message = '';
$success_message = '';

// Check for logout message
if (isset($_GET['message'])) {
    $success_message = htmlspecialchars($_GET['message']);
}

// Check for session expiry
if (isset($_GET['expired'])) {
    $error_message = 'Your session has expired. Please login again.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username)) {
        $error_message = 'Username is required';
    } elseif (empty($password)) {
        $error_message = 'Password is required';
    } elseif (strlen($username) < 3) {
        $error_message = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters';
    } else {
        // Attempt login
        $result = $auth->adminLogin($username, $password);
        
        if ($result['success']) {
            
            // Return JSON response for AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'redirect' => 'admin-dashboard.php'
                ]);
                exit;
            }
            
            // Regular form submission redirect
            header("Location: admin-dashboard.php");
            exit;
        } else {
            $error_message = $result['message'];
            
            // Return JSON response for AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $error_message
                ]);
                exit;
            }
        }
    }
    
    // Return JSON response for validation errors
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $error_message
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mavics Resort</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/admin-login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <!-- Background elements for resort ambiance -->
        <div class="resort-background">
            <div class="palm-tree palm-left"><i class="fas fa-tree"></i></div>
            <div class="palm-tree palm-right"><i class="fas fa-tree"></i></div>
            <div class="sun"><i class="fas fa-sun"></i></div>
            <div class="waves">
                <div class="wave"></div>
                <div class="wave"></div>
                <div class="wave"></div>
            </div>
        </div>
        
        <div class="login-card">
            <div class="login-header">
                <img src="images/logo.jpg" alt="Mavics Resort Logo" class="logo">
                <h1>Admin Portal</h1>
                <p>Welcome to Mavics Resort Management</p>
            </div>
            
            <!-- Display error/success messages -->
            <?php if (!empty($error_message)): ?>
                <div class="error-message show">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message show">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form class="login-form" id="adminLoginForm" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required 
                               placeholder="Enter your username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required 
                               placeholder="Enter your password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <span class="btn-text">Sign In</span>
                    <i class="fas fa-sign-in-alt btn-icon"></i>
                </button>
            </form>
        </div>
        
    </div>
    
    <script src="js/admin-login.js"></script>
</body>
</html>
