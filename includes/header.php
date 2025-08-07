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
    <!-- Bootstrap 5.3 CSS -->
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
      /* Dropdown with delay on hide */
      .nav-item.dropdown .dropdown-menu {
        display: none;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s;
      }
      .nav-item.dropdown.show .dropdown-menu {
        display: block !important;
        opacity: 1;
        pointer-events: auto;
      }
    </style>
    <script>
      // Dropdown delay logic
      document.addEventListener('DOMContentLoaded', function() {
        var dropdowns = document.querySelectorAll('.nav-item.dropdown');
        dropdowns.forEach(function(drop) {
          var timeout;
          drop.addEventListener('mouseenter', function() {
            clearTimeout(timeout);
            drop.classList.add('show');
          });
          drop.addEventListener('mouseleave', function() {
            timeout = setTimeout(function() {
              drop.classList.remove('show');
            }, 250); // 250ms delay
          });
          // Also keep open if submenu hovered
          var menu = drop.querySelector('.dropdown-menu');
          if (menu) {
            menu.addEventListener('mouseenter', function() {
              clearTimeout(timeout);
              drop.classList.add('show');
            });
            menu.addEventListener('mouseleave', function() {
              timeout = setTimeout(function() {
                drop.classList.remove('show');
              }, 250);
            });
          }
        });
      });
    </script>
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
                <!-- Only the new Settings dropdown remains -->
                <div class="inline-block relative nav-item dropdown">
                  <a href="#" class="btn btn-outline-primary btn-sm">Settings</a>
                  <ul class="dropdown-menu absolute bg-white shadow-lg rounded mt-2 z-10 min-w-[150px] right-0">
                    <li><a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">Profile</a></li>
                    <?php if (hasRole('admin')): ?>
                    <li><a href="users.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>">Users</a></li>
                    <?php endif; ?>
                  </ul>
                </div>
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
                    <a href="dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                        Dashboard
                    </a>
                </li>
                <!-- Only the new Assets dropdown remains -->
                <li class="nav-item dropdown relative">
                    <a href="assets.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'assets.php') ? 'active' : ''; ?>">
                        Assets
                    </a>
                    <ul class="dropdown-menu absolute bg-white shadow-lg rounded mt-2 z-10 min-w-[180px]">
                        <li><a href="categories.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 <?php echo (basename($_SERVER['PHP_SELF']) == 'categories.php') ? 'active' : ''; ?>">Categories</a></li>
                        <li><a href="vendors.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 <?php echo (basename($_SERVER['PHP_SELF']) == 'vendors.php') ? 'active' : ''; ?>">Vendors</a></li>
                        <li><a href="models.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 <?php echo (basename($_SERVER['PHP_SELF']) == 'models.php') ? 'active' : ''; ?>">Models</a></li>
                        <li><a href="locations.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 <?php echo (basename($_SERVER['PHP_SELF']) == 'locations.php') ? 'active' : ''; ?>">Locations</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="employees.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'employees.php') ? 'active' : ''; ?>">
                        Employees
                    </a>
                </li>
                <?php if (hasRole('manager')): ?>
                <li class="nav-item dropdown relative">
                    <a href="consumables.php" class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['consumables.php','consumables_types.php']) ? 'active' : ''; ?>">
                        Consumables
                    </a>
                    <ul class="dropdown-menu absolute bg-white shadow-lg rounded mt-2 z-10 min-w-[180px]">
                        <!-- Consumables List link removed -->
                        <li><a href="consumables_types.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 <?php echo (basename($_SERVER['PHP_SELF']) == 'consumables_types.php') ? 'active' : ''; ?>">Types</a></li>
                        <!-- Vendors link removed -->
                        <!-- Models link removed -->
                    </ul>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
                        Reports
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container-fluid px-0"><?php
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
