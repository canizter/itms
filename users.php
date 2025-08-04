
<?php
$page_title = 'Users';
require_once 'config/config.php';
require_once 'includes/header.php';
// Only allow admin users
if (!hasRole('admin')) {
    echo '<div class="alert alert-error">Access denied. Only admins can manage users.</div>';
    require_once 'includes/footer.php';
    exit;
}

$pdo = getDBConnection();
$errors = [];
$success = '';

// Handle add/edit/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $user_id = $_POST['user_id'] ?? null;
    
    if ($action === 'add' || $action === 'edit') {
        if (!$username || !$email || !$full_name || !$role) {
            $errors[] = 'All fields are required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }
    }
    
    if ($action === 'add' && empty($errors)) {
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hash, $email, $full_name, $role]);
                $success = 'User added successfully!';
            } catch (PDOException $e) {
                $errors[] = 'Error adding user: ' . $e->getMessage();
            }
        }
    }
    if ($action === 'edit' && $user_id && empty($errors)) {
        $update_password = !empty($_POST['password']);
        try {
            if ($update_password) {
                $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, email=?, full_name=?, role=? WHERE id=?");
                $stmt->execute([$username, $hash, $email, $full_name, $role, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?");
                $stmt->execute([$username, $email, $full_name, $role, $user_id]);
            }
            $success = 'User updated successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Error updating user: ' . $e->getMessage();
        }
    }
    if ($action === 'delete' && $user_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
            $stmt->execute([$user_id]);
            $success = 'User deleted successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Error deleting user: ' . $e->getMessage();
        }
    }
}

// Fetch users
$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

// Fetch user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
}
?>
<div class="page-header">
    <h1 class="page-title">Users</h1>
    <p class="page-subtitle">Manage system users and roles here.</p>
</div>
<div class="card">
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div>
        <?php endforeach; ?>
        <h3><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h3>
        <form method="post" action="users.php">
            <input type="hidden" name="action" value="<?php echo $edit_user ? 'edit' : 'add'; ?>">
            <?php if ($edit_user): ?>
                <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>">
                </div>
                <div class="form-col">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>">
                </div>
                <div class="form-col">
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-control" required>
                        <option value="user" <?php if (($edit_user['role'] ?? '') === 'user') echo 'selected'; ?>>User</option>
                        <option value="manager" <?php if (($edit_user['role'] ?? '') === 'manager') echo 'selected'; ?>>Manager</option>
                        <option value="admin" <?php if (($edit_user['role'] ?? '') === 'admin') echo 'selected'; ?>>Admin</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label"><?php echo $edit_user ? 'New Password (leave blank to keep current)' : 'Password *'; ?></label>
                    <input type="password" name="password" class="form-control" <?php echo $edit_user ? '' : 'required'; ?> autocomplete="new-password">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $edit_user ? 'Update User' : 'Add User'; ?></button>
            <?php if ($edit_user): ?>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Users</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo ucfirst($user['role']); ?></td>
                            <td>
                                <a href="users.php?edit=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="post" action="users.php" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?');">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
