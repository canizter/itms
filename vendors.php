
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
    // Check if vendor is assigned to any asset
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE vendor_id=?');
    $stmt->execute([$_GET['delete']]);
    $in_use = $stmt->fetchColumn();
    if ($in_use > 0) {
        $delete_error = 'Cannot delete: This vendor is assigned to one or more assets.';
    } else {
        $stmt = $pdo->prepare('DELETE FROM vendors WHERE id=?');
        $stmt->execute([$_GET['delete']]);
        $success = 'Vendor deleted.';
    }
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Vendors</h2>
        <button class="btn btn-primary" data-toggle="modal" data-target="#addVendorModal">+ Add Vendor</button>
    </div>
    <?php if ($success): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?=htmlspecialchars($e)?></div><?php endforeach; ?>

    <!-- Add Vendor Modal -->
    <div class="modal fade" id="addVendorModal" tabindex="-1" role="dialog" aria-labelledby="addVendorModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="post">
            <input type="hidden" name="id" value="<?=htmlspecialchars($id)?>">
            <div class="modal-header">
              <h5 class="modal-title" id="addVendorModalLabel">Add Vendor</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label for="vendor_name">Vendor Name</label>
                <input type="text" class="form-control" id="vendor_name" name="name" value="<?=htmlspecialchars($name)?>" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Add Bootstrap JS and CSS if not already included -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($delete_error)): ?>
    <!-- Error Modal -->
    <div class="modal fade" id="deleteErrorModal" tabindex="-1" role="dialog" aria-labelledby="deleteErrorModalLabel" aria-hidden="true" style="display:block;">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="deleteErrorModalLabel">Delete Error</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#deleteErrorModal').modal('hide');">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <?php echo htmlspecialchars($delete_error); ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#deleteErrorModal').modal('hide');">Close</button>
          </div>
        </div>
      </div>
    </div>
    <script>$(function() { $('#deleteErrorModal').modal('show'); });</script>
    <?php endif; ?>
    <table class="table table-bordered">
        <thead><tr><th>Name</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
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
