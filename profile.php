<?php
// profile.php - User Profile Page
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
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE username = ?');
        $stmt->execute([$full_name, $email, $username]);
        $_SESSION['full_name'] = $full_name;
        $success = 'Profile updated successfully!';
    }
}
$stmt = $pdo->prepare('SELECT username, email, full_name, role FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo '<div class="alert alert-danger">User not found.</div>';
    exit;
}
include 'includes/header.php';
?>
<div class="max-w-2xl mx-auto mt-8">
    <h2 class="text-2xl font-extrabold tracking-tight text-blue-900 drop-shadow-sm mb-4">My Profile</h2>
    <div class="bg-gradient-to-br from-white to-blue-50 shadow-xl rounded-2xl border border-blue-100 p-8">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="post">
                <dl class="row">
                    <dt class="col-sm-4">Username</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($user['username']); ?></dd>
                    <dt class="col-sm-4">Full Name</dt>
                    <dd class="col-sm-8"><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required></dd>
                    <dt class="col-sm-4">Role</dt>
                    <dd class="col-sm-8"><?php echo ucfirst($user['role']); ?></dd>
                </dl>
                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="change_password.php" class="btn btn-warning ml-2">Change Password</a>
            </form>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
