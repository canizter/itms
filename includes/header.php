<?php
// Make sure user is logged in
requireLogin();
checkSessionTimeout();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <meta name="description" content="IT Management System">
    <meta name="author" content="ITMS">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1><?php echo APP_NAME; ?></h1>
            </div>
            <div class="user-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                <span class="text-muted">|</span>
                <span class="text-muted"><?php echo ucfirst($_SESSION['role']); ?></span>
                <span class="text-muted">|</span>
                <a href="logout.php" class="btn btn-secondary btn-sm" 
                   data-confirm="Are you sure you want to logout?">Logout</a>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="profile.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
                        Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="assets.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'assets.php') ? 'active' : ''; ?>">
                        Assets
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'categories.php') ? 'active' : ''; ?>">
                        Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a href="vendors.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'vendors.php') ? 'active' : ''; ?>">
                        Vendors
                    </a>
                </li>
                <li class="nav-item">
                    <a href="locations.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'locations.php') ? 'active' : ''; ?>">
                        Locations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="employees.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'employees.php') ? 'active' : ''; ?>">
                        Employees
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
                        Reports
                    </a>
                </li>
                <?php if (hasRole('admin')): ?>
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>">
                        Users
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container"><?php
// Function to show success/error messages
function showMessage($type, $message) {
    if (!empty($message)) {
        echo "<div class='alert alert-{$type}'>" . htmlspecialchars($message) . "</div>";
    }
}

// Check for flash messages in session
if (isset($_SESSION['success_message'])) {
    showMessage('success', $_SESSION['success_message']);
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    showMessage('error', $_SESSION['error_message']);
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['warning_message'])) {
    showMessage('warning', $_SESSION['warning_message']);
    unset($_SESSION['warning_message']);
}

if (isset($_SESSION['info_message'])) {
    showMessage('info', $_SESSION['info_message']);
    unset($_SESSION['info_message']);
}
?>
