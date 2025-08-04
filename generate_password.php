<?php
// Generate password hash for admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password hash for '{$password}': {$hash}\n";

// Test the hash
if (password_verify($password, $hash)) {
    echo "✅ Password verification successful!\n";
} else {
    echo "❌ Password verification failed!\n";
}
?>
