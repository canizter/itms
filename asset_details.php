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
// Fetch asset with assigned employee info
$stmt = $pdo->prepare('SELECT a.*, c.name as category_name, v.name as vendor_name, l.name as location_name, e.employee_id as assigned_employee_id, e.name as assigned_employee_name FROM assets a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN vendors v ON a.vendor_id = v.id
    LEFT JOIN locations l ON a.location_id = l.id
    LEFT JOIN employees e ON a.assigned_to_employee_id = e.id
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Asset Details</h2>
        <a href="assets.php" class="btn btn-secondary">&larr; Back to Assets</a>
    </div>
    <div class="row">
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Asset: <?php echo htmlspecialchars($asset['name']); ?></h4>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Asset Tag:</div>
                        <div class="col-7"><?php echo htmlspecialchars($asset['asset_tag']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Category:</div>
                        <div class="col-7"><?php echo htmlspecialchars($asset['category_name']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Vendor:</div>
                        <div class="col-7"><?php echo htmlspecialchars($asset['vendor_name']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Location:</div>
                        <div class="col-7"><?php echo htmlspecialchars($asset['location_name']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Status:</div>
                        <div class="col-7">
                            <?php 
                                $status = strtolower($asset['status']);
                                $badge = 'secondary';
                                $displayStatus = '';
                                if ($status === 'active') { $badge = 'success'; $displayStatus = 'In Use'; }
                                elseif ($status === 'inactive') { $badge = 'info'; $displayStatus = 'Available'; }
                                elseif ($status === 'maintenance') { $badge = 'warning'; $displayStatus = 'In Repair'; }
                                elseif ($status === 'disposed') { $badge = 'dark'; $displayStatus = 'Retired'; }
                                else { $displayStatus = ucfirst($asset['status']); }
                            ?>
                            <span class="badge badge-<?php echo $badge; ?> text-uppercase"><?php echo htmlspecialchars($displayStatus); ?></span>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Serial Number:</div>
                        <div class="col-7"><?php echo htmlspecialchars($asset['serial_number']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Notes / Remarks:</div>
                        <div class="col-7"><?php echo nl2br(htmlspecialchars($asset['description'])); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Assignment</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($asset['assigned_to_employee_id']) && (!empty($asset['assigned_employee_id']) || !empty($asset['assigned_employee_name']))): ?>
                        <div class="mb-2">
                            <span class="font-weight-bold">Assigned To:</span><br>
                            <span class="h5">
                                <?php
                                    $emp_id = $asset['assigned_employee_id'] ?? '';
                                    $emp_name = $asset['assigned_employee_name'] ?? '';
                                    echo htmlspecialchars(trim($emp_id . ' - ' . $emp_name, ' -'));
                                ?>
                            </span>
                        </div>
                        <div class="mb-3">
                            <a href="asset_assignments.php?asset_id=<?php echo $asset_id; ?>" class="btn btn-outline-primary btn-sm">View Assignment History</a>
                        </div>
                    <?php else: ?>
                        <div class="mb-2 text-muted">Currently <strong>Unassigned</strong></div>
                        <a href="asset_assignments.php?asset_id=<?php echo $asset_id; ?>" class="btn btn-outline-primary btn-sm">View Assignment History</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
