<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('dashboard.php');
}

$error = '';
$success = '';

// Handle logout message
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success = 'You have been logged out successfully.';
}

// Handle timeout message
if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $error = 'Your session has expired. Please log in again.';
}

// Handle login form submission
if ($_POST) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, username, password, email, full_name, role FROM users WHERE username = ? AND id > 0");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                // Redirect to dashboard
                redirectTo('dashboard.php');
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred during login. Please try again.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title"><?php echo APP_NAME; ?></h1>
                <p class="text-muted">Please sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" data-validate>
                <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">Username *</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           required 
                           autocomplete="username"
                           value="<?php echo htmlspecialchars($username ?? ''); ?>"
                           placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password *</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required 
                           autocomplete="current-password"
                           placeholder="Enter your password">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem;">
                        Sign In
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <!-- Default login text removed -->
            </div>
        </div>
    </div>
    
    <script src="assets/js/app.js"></script>
    <script>
        // Focus on username field
        document.getElementById('username').focus();
    </script>
</body>
</html>
