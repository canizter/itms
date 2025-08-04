<?php
// asset_details.php - View Asset Details
require_once 'config/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$pdo = getDBConnection();
$asset_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$asset_id) {
    header('Location: assets.php');
    exit;
}
$stmt = $pdo->prepare('SELECT a.*, c.name as category_name, v.name as vendor_name, l.name as location_name FROM assets a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN vendors v ON a.vendor_id = v.id
    LEFT JOIN locations l ON a.location_id = l.id
    WHERE a.id = ?');
$stmt->execute([$asset_id]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    header('Location: assets.php');
    exit;
}
include 'includes/header.php';
?>
<div class="container mt-4">
    <h2>Asset Details</h2>
    <a href="assets.php" class="btn btn-secondary mb-3">&larr; Back to Assets</a>
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Asset: <?php echo htmlspecialchars($asset['name']); ?></h4>
            <dl class="row">
                <dt class="col-sm-3">Asset Tag</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($asset['asset_tag']); ?></dd>
                <dt class="col-sm-3">Category</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($asset['category_name']); ?></dd>
                <dt class="col-sm-3">Vendor</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($asset['vendor_name']); ?></dd>
                <dt class="col-sm-3">Location</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($asset['location_name']); ?></dd>
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9"><?php echo ucfirst($asset['status']); ?></dd>
                <dt class="col-sm-3">Purchase Date</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($asset['purchase_date']); ?></dd>
                <dt class="col-sm-3">Serial Number</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($asset['serial_number']); ?></dd>
                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($asset['description'])); ?></dd>
            </dl>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
