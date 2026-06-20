<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

echo "<h1>Profile Test - Generated at: " . date('Y-m-d H:i:s') . "</h1>";
echo "<p style='color: red; font-weight: bold;'>This is a TEST file to verify the issue</p>";

// Method 1: SELECT *
echo "<h2>Method 1: SELECT *</h2>";
$stmt1 = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt1->execute([$userId]);
$user1 = $stmt1->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($user1);
echo "</pre>";
echo "<p>last_login value: " . ($user1['last_login'] ?? 'NOT SET') . "</p>";

// Method 2: Explicit columns
echo "<h2>Method 2: Explicit SELECT with last_login</h2>";
$stmt2 = $conn->prepare("SELECT id, first_name, last_name, email, last_login FROM customers WHERE id = ?");
$stmt2->execute([$userId]);
$user2 = $stmt2->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($user2);
echo "</pre>";
echo "<p>last_login value: " . ($user2['last_login'] ?? 'NOT SET') . "</p>";

// Method 3: Check what columns exist
echo "<h2>Method 3: Show columns in customers table</h2>";
$stmt3 = $conn->query("SHOW COLUMNS FROM customers");
$columns = $stmt3->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($columns);
echo "</pre>";

// Method 4: Direct query to see actual database value
echo "<h2>Method 4: Raw SQL check for user ID $userId</h2>";
$stmt4 = $conn->query("SELECT id, email, last_login FROM customers WHERE id = $userId");
$user4 = $stmt4->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($user4);
echo "</pre>";

echo "<hr>";
echo "<h2>Conclusion:</h2>";
if (isset($user1['last_login']) && $user1['last_login']) {
    echo "<p style='color: green; font-size: 20px;'>✓ last_login IS working! Value: " . $user1['last_login'] . "</p>";
    echo "<p>The issue must be with browser cache or a different profile.php file being loaded.</p>";
} else {
    echo "<p style='color: red; font-size: 20px;'>✗ last_login is NULL or not being fetched</p>";
    echo "<p>The database query is not working correctly.</p>";
}
?>
