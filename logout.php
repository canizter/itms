<?php
require_once 'config/config.php';

// Destroy session and redirect to login
session_destroy();

// Clear any remember me cookies if they exist
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page with logout message
redirectTo('login.php?logout=1');
?>
