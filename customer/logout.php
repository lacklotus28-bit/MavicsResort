<?php
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Start a new session for flash messages
session_start();

// Set a logout success message
$_SESSION['logout_success'] = 'You have been successfully logged out.';

// Redirect to home page
header('Location: index.php');
exit();
?>