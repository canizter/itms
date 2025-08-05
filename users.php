
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
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
  <div class="mb-8 text-center">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Users</h1>
    <p class="text-gray-500 text-lg">Manage system users and roles here.</p>
  </div>
  <div class="bg-white shadow rounded-lg p-8 mb-8">
    <?php if ($success): ?>
      <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
        <!-- Heroicon: check-circle -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0a9 9 0 0118 0z" /></svg>
        <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
        <!-- Heroicon: exclamation -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 17a5 5 0 100-10 5 5 0 000 10z" /></svg>
        <?php echo htmlspecialchars($err); ?>
      </div>
    <?php endforeach; ?>
    <h3 class="text-xl font-semibold mb-4"><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h3>
    <form method="post" action="users.php" class="space-y-4">
      <input type="hidden" name="action" value="<?php echo $edit_user ? 'edit' : 'add'; ?>">
      <?php if ($edit_user): ?>
        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
      <?php endif; ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
          <input type="text" name="username" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
          <input type="email" name="email" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
          <input type="text" name="full_name" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
          <select name="role" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <option value="user" <?php if (($edit_user['role'] ?? '') === 'user') echo 'selected'; ?>>User</option>
            <option value="manager" <?php if (($edit_user['role'] ?? '') === 'manager') echo 'selected'; ?>>Manager</option>
            <option value="admin" <?php if (($edit_user['role'] ?? '') === 'admin') echo 'selected'; ?>>Admin</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo $edit_user ? 'New Password (leave blank to keep current)' : 'Password *'; ?></label>
          <input type="password" name="password" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" <?php echo $edit_user ? '' : 'required'; ?> autocomplete="new-password">
        </div>
      </div>
      <div class="flex gap-2 mt-4">
        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold transition"><?php echo $edit_user ? 'Update User' : 'Add User'; ?></button>
        <?php if ($edit_user): ?>
          <a href="users.php" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold transition">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
  <div class="bg-white shadow rounded-lg p-8">
    <h3 class="text-xl font-semibold mb-4">All Users</h3>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php foreach ($users as $user): ?>
            <tr>
              <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo $user['id']; ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($user['username']); ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($user['email']); ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo ucfirst($user['role']); ?></td>
              <td class="px-6 py-4 whitespace-nowrap flex gap-2">
                <a href="users.php?edit=<?php echo $user['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200 text-xs font-medium transition">Edit</a>
                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                  <form method="post" action="users.php" onsubmit="return confirm('Delete this user?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200 text-xs font-medium transition">Delete</button>
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
