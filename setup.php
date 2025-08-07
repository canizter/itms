
<?php
// ITMS Setup Script (v1.5.2)
// This script helps initialize the IT Management System (Version 1.5.2)

error_reporting(E_ALL);
ini_set('display_errors', 1);

$setup_complete = false;
$errors = [];
$success_messages = [];

// Check if setup is already complete
if (file_exists('config/setup_complete.flag')) {
    $setup_complete = true;
}

if ($_POST && !$setup_complete) {
    try {
        // Database configuration
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_username = $_POST['db_username'] ?? 'root';
        $db_password = $_POST['db_password'] ?? '';
        $db_name = $_POST['db_name'] ?? 'itms_db';
        
        // Test database connection
        $pdo = new PDO("mysql:host={$db_host}", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db_name}`");
        
        // Read and execute SQL file
        $sql_files = ['database/itms_database_clean.sql', 'database/itms_database.sql'];
        $sql_file = null;
        
        foreach ($sql_files as $file) {
            if (file_exists($file)) {
                $sql_file = $file;
                break;
            }
        }
        
        if ($sql_file) {
            $sql_content = file_get_contents($sql_file);
            // Remove database creation lines since we already created it
            $sql_content = preg_replace('/CREATE DATABASE.*?;/i', '', $sql_content);
            $sql_content = preg_replace('/USE.*?;/i', '', $sql_content);
            
            // Split into individual queries and clean them
            $queries = array_filter(array_map('trim', explode(';', $sql_content)));
            
            $executed_queries = 0;
            $failed_queries = 0;
            
            foreach ($queries as $query) {
                if (!empty($query) && strlen(trim($query)) > 5) {
                    try {
                        $pdo->exec($query);
                        $executed_queries++;
                    } catch (PDOException $e) {
                        // Ignore table already exists errors and duplicate entries
                        if (strpos($e->getMessage(), 'already exists') === false && 
                            strpos($e->getMessage(), 'Duplicate entry') === false) {
                            $failed_queries++;
                            $errors[] = "SQL Error in query: " . substr($query, 0, 100) . "... - " . $e->getMessage();
                        }
                    }
                }
            }
            
            if ($failed_queries === 0) {
                $success_messages[] = "Database tables initialized successfully! ({$executed_queries} queries executed)";
            } else {
                $success_messages[] = "Database setup completed with some warnings. ({$executed_queries} queries executed, {$failed_queries} failed)";
            }
            
            // Create default admin user separately to avoid SQL syntax issues
            try {
                $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['admin', $admin_password, 'admin@company.com', 'System Administrator', 'admin']);
                $success_messages[] = "Default admin user created successfully! (Username: admin, Password: admin123)";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                    $errors[] = "Failed to create admin user: " . $e->getMessage();
                } else {
                    $success_messages[] = "Admin user already exists - skipping creation.";
                }
            }
        } else {
            $errors[] = "SQL file not found. Please ensure the database SQL file exists.";
        }
        
        // Create uploads directory
        $uploads_dir = 'uploads';
        if (!is_dir($uploads_dir)) {
            if (mkdir($uploads_dir, 0755, true)) {
                $success_messages[] = "Uploads directory created successfully!";
            } else {
                $errors[] = "Failed to create uploads directory. Please create it manually.";
            }
        }
        
        // Update config file
        $config_content = file_get_contents('config/config.php');
        $config_content = str_replace("define('DB_HOST', 'localhost');", "define('DB_HOST', '{$db_host}');", $config_content);
        $config_content = str_replace("define('DB_USERNAME', 'root');", "define('DB_USERNAME', '{$db_username}');", $config_content);
        $config_content = str_replace("define('DB_PASSWORD', '');", "define('DB_PASSWORD', '{$db_password}');", $config_content);
        $config_content = str_replace("define('DB_NAME', 'itms_db');", "define('DB_NAME', '{$db_name}');", $config_content);
        
        if (file_put_contents('config/config.php', $config_content)) {
            $success_messages[] = "Configuration file updated successfully!";
        } else {
            $errors[] = "Failed to update configuration file.";
        }
        
        // Create setup complete flag
        if (empty($errors)) {
            file_put_contents('config/setup_complete.flag', date('Y-m-d H:i:s'));
            $setup_complete = true;
            $success_messages[] = "Setup completed successfully! You can now use the system.";
        }
        
    } catch (Exception $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITMS Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .setup-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 600px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            margin-top: 1rem;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .setup-complete {
            text-align: center;
            padding: 2rem;
        }
        .setup-complete h2 {
            color: #28a745;
            margin-bottom: 1rem;
        }
        .next-steps {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .next-steps h3 {
            margin-top: 0;
            color: #495057;
        }
        .next-steps ul {
            color: #6c757d;
            line-height: 1.6;
        }
        .btn-link {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 4px;
            margin: 0.5rem;
        }
        .btn-link:hover {
            background: #218838;
            color: white;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <?php if ($setup_complete): ?>
            <div class="setup-complete">
                <h1>✅ Setup Complete!</h1>
                <h2>IT Management System is ready to use</h2>
                
                <?php foreach ($success_messages as $message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
                
                <div class="next-steps">
                    <h3>Next Steps:</h3>
                    <ul>
                        <li>Access your system: <a href="index.php">Open ITMS</a></li>
                        <li>Login with default credentials: <strong>admin</strong> / <strong>admin123</strong></li>
                        <li>Change the default admin password</li>
                        <li>User roles:<br>
                            <strong>Admin</strong>: Full access<br>
                            <strong>Viewer</strong>: Can view all assets, employees, and assignments, but cannot add, edit, or delete.<br>
                        </li>
                        <li>Add your organization's <a href="categories.php">categories</a>, vendors, and locations</li>
                        <li>Start adding your IT assets (Asset Tag, Name, etc.)</li>
                    </ul>
                </div>

                <!-- Quick actions removed -->
            </div>
        <?php else: ?>
            <h1>🔧 ITMS Setup Wizard</h1>
            <p style="text-align: center; color: #6c757d; margin-bottom: 2rem;">
                Welcome! Let's set up your IT Management System.
            </p>
            
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
            
            <?php foreach ($success_messages as $message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endforeach; ?>
            
            <form method="POST" action="setup.php">
                <h3 style="color: #495057; margin-bottom: 1rem;">Database Configuration</h3>
                
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                    <small style="color: #6c757d;">Usually 'localhost' for XAMPP</small>
                </div>
                
                <div class="form-group">
                    <label for="db_username">Database Username</label>
                    <input type="text" id="db_username" name="db_username" value="root" required>
                    <small style="color: #6c757d;">Default XAMPP username is 'root'</small>
                </div>
                
                <div class="form-group">
                    <label for="db_password">Database Password</label>
                    <input type="password" id="db_password" name="db_password" value="">
                    <small style="color: #6c757d;">Default XAMPP password is empty</small>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" value="itms_db" required>
                    <small style="color: #6c757d;">Will be created if it doesn't exist</small>
                </div>
                
                <button type="submit" class="btn">🚀 Setup ITMS</button>
            </form>
            
            <div class="next-steps" style="margin-top: 2rem;">
                <h3>Requirements Check:</h3>
                <ul>
                    <li>✅ XAMPP Apache: Make sure it's running</li>
                    <li>✅ XAMPP MySQL: Make sure it's running</li>
                    <li>✅ PHP 7.4+: <?php echo PHP_VERSION; ?></li>
                    <li>✅ Files in place: c:\xampp\htdocs\itms\</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
