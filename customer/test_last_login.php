<?php
// Test script to verify which profile page you're viewing
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Please log in first");
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Last Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-box { border: 2px solid #333; padding: 20px; margin: 10px 0; background: #f0f0f0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Last Login Display Test</h1>
    
    <div class="test-box">
        <h2>User Data</h2>
        <p><strong>ID:</strong> <?php echo $user['id']; ?></p>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    </div>
    
    <div class="test-box">
        <h2>Last Login (Raw Value)</h2>
        <p><strong>Value from database:</strong> <?php echo $user['last_login'] ?? 'NULL'; ?></p>
    </div>
    
    <div class="test-box">
        <h2>Last Login (Formatted - Exact code from profile.php line 259)</h2>
        <p class="<?php echo (isset($user['last_login']) && $user['last_login']) ? 'success' : 'error'; ?>">
            <?php echo (isset($user['last_login']) && $user['last_login']) ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
        </p>
    </div>
    
    <div class="test-box">
        <h2>Condition Test</h2>
        <p><strong>isset($user['last_login']):</strong> <?php echo isset($user['last_login']) ? 'TRUE' : 'FALSE'; ?></p>
        <p><strong>$user['last_login'] value:</strong> <?php echo $user['last_login'] ? 'TRUE (has value)' : 'FALSE (empty/null)'; ?></p>
        <p><strong>Combined condition:</strong> <?php echo (isset($user['last_login']) && $user['last_login']) ? 'TRUE (should show date)' : 'FALSE (shows Never)'; ?></p>
    </div>
    
    <div class="test-box">
        <h2>Instructions</h2>
        <p>If you see the formatted date above (in green), but the profile page still shows "Never", then:</p>
        <ol>
            <li><strong>Clear your browser cache (Ctrl+Shift+Del)</strong></li>
            <li><strong>Hard refresh the profile page (Ctrl+F5)</strong></li>
            <li><strong>Or open profile page in incognito/private mode</strong></li>
        </ol>
        <p><a href="profile.php" target="_blank">Click here to open your profile page in a new tab</a></p>
    </div>
</body>
</html>
