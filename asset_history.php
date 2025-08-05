<?php
// asset_history.php - Asset Change History UI
require_once 'config/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$pdo = getDBConnection();
$asset_id = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;
if (!$asset_id) {
    header('Location: assets.php');
    exit;
}
// Get asset info
$stmt = $pdo->prepare('SELECT * FROM assets WHERE id = ?');
$stmt->execute([$asset_id]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    header('Location: assets.php');
    exit;
}
// Get history
$sql = 'SELECT * FROM asset_history WHERE asset_id = ? ORDER BY changed_at DESC, id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute([$asset_id]);
$history = $stmt->fetchAll();
include 'includes/header.php';
?>
<div class="container mt-4">
    <h2>Change History for Asset: <?php echo htmlspecialchars($asset['name']); ?></h2>
    <a href="asset_details.php?id=<?php echo $asset_id; ?>" class="btn btn-secondary mb-3">&larr; Back to Asset Details</a>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Field Changed</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['changed_at']); ?></td>
                            <td><?php echo htmlspecialchars($row['changed_by']); ?></td>
                            <td><?php echo htmlspecialchars($row['action']); ?></td>
                            <td><?php echo htmlspecialchars($row['field_changed']); ?></td>
                            <td><?php echo htmlspecialchars($row['old_value'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['new_value'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
