<?php
header('Content-Type: text/plain');

echo "=== FILE STRUCTURE CHECK ===\n\n";

$files = [
    'api/get-message.php',
    'api/send-reply.php',
    'css/custom-modal.css',
    'css/manage-contact.css',
    'js/contact-management.js',
    'modals/view-message-modal.php',
    'modals/reply-message-modal.php',
    'modals/alert-modal.php',
    'config/database.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ $file\n";
    } else {
        echo "✗ $file (MISSING!)\n";
    }
}

echo "\n=== PERMISSIONS ===\n\n";

foreach ($files as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "$file: $perms\n";
    }
}
?>