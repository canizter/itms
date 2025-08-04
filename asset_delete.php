<?php
// asset_delete.php - Delete Asset
require_once 'config/config.php';
if (!hasRole('admin')) {
    header('Location: assets.php');
    exit;
}
$pdo = getDBConnection();
$asset_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$asset_id) {
    header('Location: assets.php');
    exit;
}
// Fetch asset for confirmation
$stmt = $pdo->prepare('SELECT name FROM assets WHERE id = ?');
$stmt->execute([$asset_id]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    header('Location: assets.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        $stmt = $pdo->prepare('DELETE FROM assets WHERE id = ?');
        $stmt->execute([$asset_id]);
        header('Location: assets.php?deleted=1');
        exit;
    } else {
        header('Location: assets.php');
        exit;
    }
}
include 'includes/header.php';
?>
<div class="container mt-4">
    <h2>Delete Asset</h2>
    <div class="alert alert-warning">
        <strong>Are you sure you want to delete this asset?</strong><br>
        Asset: <strong><?php echo htmlspecialchars($asset['name']); ?></strong>
    </div>
    <form method="post">
        <button type="submit" name="confirm" value="yes" class="btn btn-danger">Yes, Delete</button>
        <a href="assets.php" class="btn btn-secondary ml-2">Cancel</a>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
