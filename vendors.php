
<?php
require_once 'config/config.php';

require_once 'includes/header.php';
if (!isAdmin()) { header('Location: index.php'); exit; }
$pdo = getDBConnection();

$errors = [];
$success = '';
$name = '';
$contact = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $errors[] = 'Vendor name is required.';
    }
    if (empty($errors)) {
        if (isset($_POST['id']) && $_POST['id']) {
            $stmt = $pdo->prepare('UPDATE vendors SET name=? WHERE id=?');
            $stmt->execute([$name, $_POST['id']]);
            $success = 'Vendor updated.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO vendors (name) VALUES (?)');
            $stmt->execute([$name]);
            $success = 'Vendor added.';
        }
        $name = '';
        $id = 0;
    }
}
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM vendors WHERE id=?');
    $stmt->execute([$_GET['delete']]);
    $success = 'Vendor deleted.';
}
$rows = $pdo->query('SELECT * FROM vendors ORDER BY id DESC')->fetchAll();
$rows = $pdo->query('SELECT * FROM vendors ORDER BY id DESC')->fetchAll();
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM vendors WHERE id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $name = $row['name'] ?? '';
    }
}
$rows = $pdo->query('SELECT * FROM vendors ORDER BY id DESC')->fetchAll();
?>
<div class="container mt-4">
    <h2>Vendors</h2>
    <?php if ($success): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?=htmlspecialchars($e)?></div><?php endforeach; ?>
    <form method="post" class="mb-3">
        <input type="hidden" name="id" value="<?=htmlspecialchars($id)?>">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($name)?>" required>
        </div>
        <!-- Contact field removed -->
        <button class="btn btn-primary mt-2">Save</button>
        <?php if ($id): ?><a href="vendors.php" class="btn btn-secondary mt-2">Cancel</a><?php endif; ?>
    </form>
    <table class="table table-bordered">
        <thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?=htmlspecialchars($r['id'])?></td>
                <td><?=htmlspecialchars($r['name'])?></td>
                <td>
                    <a href="vendors.php?id=<?=$r['id']?>" class="btn btn-sm btn-info">Edit</a>
                    <a href="vendors.php?delete=<?=$r['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this vendor?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once 'includes/footer.php'; ?>
