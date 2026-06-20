<?php
session_start();
header('Content-Type: text/plain');

echo "=== SESSION CHECK ===\n\n";

if (isset($_SESSION['admin_id'])) {
    echo "✓ Admin is logged in\n";
    echo "Admin ID: " . $_SESSION['admin_id'] . "\n";
    echo "Username: " . ($_SESSION['admin_username'] ?? 'Not set') . "\n";
} else {
    echo "✗ Admin is NOT logged in\n";
    echo "Please login first at: admin-login.php\n";
}

echo "\nAll session data:\n";
print_r($_SESSION);
?>