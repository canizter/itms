<?php
// asset_edit.php - Edit Asset
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
$stmt = $pdo->prepare('SELECT * FROM assets WHERE id = ?');
$stmt->execute([$asset_id]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    header('Location: assets.php');
    exit;
}
$errors = [];
$asset_tag = $asset['asset_tag'];
$category_id = $asset['category_id'];
$vendor_id = $asset['vendor_id'];
$location_id = $asset['location_id'];
$serial_number = $asset['serial_number'];
$lan_mac = $asset['lan_mac'] ?? '';
$wlan_mac = $asset['wlan_mac'] ?? '';
$status = $asset['status'];
$assigned_to_employee_id = $asset['assigned_to_employee_id'] ?? null;

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$vendors = $pdo->query('SELECT id, name FROM vendors ORDER BY name')->fetchAll();
$employees = $pdo->query('SELECT id, employee_id, name FROM employees ORDER BY name')->fetchAll();
$locations = $pdo->query('SELECT id, name FROM locations ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_tag = trim($_POST['asset_tag'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $vendor_id = $_POST['vendor_id'] ?? '';
    $location_id = $_POST['location_id'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $serial_number = trim($_POST['serial_number'] ?? '');
    $lan_mac = trim($_POST['lan_mac'] ?? '');
    $wlan_mac = trim($_POST['wlan_mac'] ?? '');
    $assigned_to_employee_id = isset($_POST['assigned_to_employee_id']) && $_POST['assigned_to_employee_id'] !== '' ? $_POST['assigned_to_employee_id'] : null;

    if ($asset_tag === '') $errors[] = 'Asset tag is required.';
    if (!$category_id) $errors[] = 'Category is required.';
    if (!$vendor_id) $errors[] = 'Vendor is required.';
    if (!$location_id) $errors[] = 'Location is required.';
    if ($serial_number === '') $errors[] = 'Serial number is required.';

    // MAC address validation (optional, adjust as needed)
    $mac_regex = '/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/';
    if ($lan_mac && !preg_match($mac_regex, $lan_mac)) {
        $errors[] = 'LAN MAC address format is invalid.';
    }
    if ($wlan_mac && !preg_match($mac_regex, $wlan_mac)) {
        $errors[] = 'WLAN MAC address format is invalid.';
    }

    if (!$errors) {
        // Determine new status: 'active' (In Use) if assigned, 'inactive' (Available) if not
        $auto_status = !is_null($assigned_to_employee_id) ? 'active' : 'inactive';
        $stmt = $pdo->prepare('UPDATE assets SET asset_tag=?, category_id=?, vendor_id=?, location_id=?, status=?, serial_number=?, lan_mac=?, wlan_mac=?, assigned_to_employee_id=? WHERE id=?');
        $stmt->execute([$asset_tag, $category_id, $vendor_id, $location_id, $auto_status, $serial_number, $lan_mac, $wlan_mac, $assigned_to_employee_id, $asset_id]);

        // Record assignment change if changed and not null
        $current = $asset['assigned_to_employee_id'] ?? null;
        if ($assigned_to_employee_id != $current) {
            $assign_stmt = $pdo->prepare('INSERT INTO asset_assignments (asset_id, employee_id, assigned_by, assigned_date, notes) VALUES (?, ?, ?, ?, ?)');
            $assign_stmt->execute([
                $asset_id,
                $assigned_to_employee_id,
                $_SESSION['username'] ?? 'system',
                date('Y-m-d'),
                'Assignment changed via edit'
            ]);
            // Update the asset's current assignment and status
            $update_asset_stmt = $pdo->prepare('UPDATE assets SET assigned_to_employee_id = ?, status = ? WHERE id = ?');
            $update_asset_stmt->execute([$assigned_to_employee_id, $auto_status, $asset_id]);
        }
        header('Location: assets.php?updated=1');
        exit;
    }
}
include 'includes/header.php';
?>
<div class="container mt-4">
    <h2>Edit Asset</h2>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endforeach; ?>
    <form method="post">
        <div class="form-group">
            <label>Assign to Employee</label>
            <?php if (!empty($asset['assigned_to_employee_id'])): ?>
                <div class="alert alert-info">This asset is currently assigned. Please return it before assigning to another employee.</div>
                <select name="assigned_to_employee_id" class="form-control" disabled>
                    <option value="<?php echo $assigned_to_employee_id; ?>" selected>
                        <?php 
                        foreach ($employees as $emp) {
                            if ($emp['id'] == $assigned_to_employee_id) {
                                echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['name']);
                                break;
                            }
                        }
                        ?>
                    </option>
                </select>
            <?php else: ?>
                <select name="assigned_to_employee_id" class="form-control">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php if ($assigned_to_employee_id == $emp['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
        <div class="form-group">
        <div class="form-group">
            <label>Asset Tag</label>
            <input type="text" name="asset_tag" class="form-control" value="<?php echo htmlspecialchars($asset_tag); ?>" required>
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category_id" class="form-control" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php if ($cat['id'] == $category_id) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Vendor</label>
            <select name="vendor_id" class="form-control" required>
                <option value="">Select Vendor</option>
                <?php foreach ($vendors as $ven): ?>
                    <option value="<?php echo $ven['id']; ?>" <?php if ($ven['id'] == $vendor_id) echo 'selected'; ?>><?php echo htmlspecialchars($ven['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Location</label>
            <select name="location_id" class="form-control" required>
                <option value="">Select Location</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?php echo $loc['id']; ?>" <?php if ($loc['id'] == $location_id) echo 'selected'; ?>><?php echo htmlspecialchars($loc['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="active" <?php if ($status === 'active') echo 'selected'; ?>>In Use</option>
                <option value="inactive" <?php if ($status === 'inactive') echo 'selected'; ?>>Available</option>
                <option value="maintenance" <?php if ($status === 'maintenance') echo 'selected'; ?>>In Repair</option>
                <option value="disposed" <?php if ($status === 'disposed') echo 'selected'; ?>>Retired</option>
            </select>
        </div>

        <div class="form-group">
            <label>Serial Number</label>
            <input type="text" name="serial_number" class="form-control" value="<?php echo htmlspecialchars($serial_number); ?>" required>
        </div>
        <div class="form-group">
            <label>LAN MAC Address</label>
            <input type="text" name="lan_mac" class="form-control" value="<?php echo htmlspecialchars($lan_mac); ?>" placeholder="00:00:00:00:00:00">
        </div>
        <div class="form-group">
            <label>WLAN MAC Address</label>
            <input type="text" name="wlan_mac" class="form-control" value="<?php echo htmlspecialchars($wlan_mac); ?>" placeholder="00:00:00:00:00:00">
        </div>
        <button type="submit" class="btn btn-success">Update Asset</button>
        <a href="assets.php" class="btn btn-secondary ml-2">Cancel</a>
        <a href="asset_assignments.php?asset_id=<?php echo $asset_id; ?>" class="btn btn-info ml-2">View Assignment History</a>
        <?php if (!empty($asset['assigned_to_employee_id'])): ?>
            <a href="asset_return.php?id=<?php echo $asset_id; ?>" class="btn btn-danger ml-2" onclick="return confirm('Mark this asset as returned?');">Return</a>
        <?php endif; ?>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
