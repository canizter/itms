<?php
// asset_assignments.php - Asset Assignment History
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
// Get assignment history
$sql = 'SELECT aa.*, e.employee_id, e.name as employee_name FROM asset_assignments aa
        LEFT JOIN employees e ON aa.employee_id = e.id
        WHERE aa.asset_id = ? ORDER BY aa.assigned_date DESC, aa.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute([$asset_id]);
$history = $stmt->fetchAll();
include 'includes/header.php';
?>
<div class="container mt-4">
    <h2>Assignment History for Asset: <?php echo htmlspecialchars($asset['name']); ?></h2>
    <a href="asset_details.php?id=<?php echo $asset_id; ?>" class="btn btn-secondary mb-3">&larr; Back to Asset Details</a>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Assigned By</th>
                        <th>Assigned Date</th>
                        <th>Return Date</th>
                        <th>Notes</th>
                        <th>Recorded</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['assigned_by']); ?></td>
                            <td><?php echo htmlspecialchars($row['assigned_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['return_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['notes']); ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
