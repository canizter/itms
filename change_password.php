<?php
// change_password.php - User password change page
require_once 'config/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$pdo = getDBConnection();
$username = $_SESSION['username'] ?? '';
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if ($new_password !== $confirm_password) {
        $errors[] = 'New password and confirmation do not match.';
    }
    if (strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    $stmt = $pdo->prepare('SELECT password FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($current_password, $user['password'])) {
        $errors[] = 'Current password is incorrect.';
    }
    if (!$errors) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE username = ?');
        $stmt->execute([$hashed, $username]);
        $success = 'Password changed successfully!';
    }
}
include 'includes/header.php';
?>
<div class="container mt-4" style="max-width: 500px;">
    <h2>Change Password</h2>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endforeach; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Change Password</button>
        <a href="profile.php" class="btn btn-secondary ml-2">Cancel</a>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
