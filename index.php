<?php
require_once 'config/config.php';

// Redirect to login if not logged in, otherwise to dashboard
if (isLoggedIn()) {
    redirectTo('dashboard.php');
} else {
    redirectTo('login.php');
}
?>
