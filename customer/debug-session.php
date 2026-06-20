<?php
session_start();

echo "<h1>Session Debug</h1>";
echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Session Status:</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "User ID isset: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "<br>";
echo "User ID value: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";

if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    echo "<h2>User Data from Database:</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
}

echo "<h2>Test Links:</h2>";
echo "<a href='login.php'>Go to Login</a><br>";
echo "<a href='index.php'>Go to Home</a><br>";
echo "<a href='dashboard.php'>Go to Dashboard</a><br>";
?>
