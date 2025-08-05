<?php
// asset_add.php - Add New Asset
require_once 'config/config.php';
if (!hasRole('manager')) {
    header('Location: assets.php');
    exit;
}
$pdo = getDBConnection();

$errors = [];
$success = '';
$asset_tag = $category_id = $vendor_id = $location_id = $status = $serial_number = $lan_mac = $wlan_mac = '';

// Fetch categories, vendors, locations for dropdowns
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$vendors = $pdo->query('SELECT id, name FROM vendors ORDER BY name')->fetchAll();
$locations = $pdo->query('SELECT id, name FROM locations ORDER BY name')->fetchAll();
$employees = $pdo->query('SELECT id, employee_id, name FROM employees ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_tag = trim($_POST['asset_tag'] ?? '');
    // Asset name removed; only asset_tag is used
    $category_id = $_POST['category_id'] ?? '';
    $vendor_id = $_POST['vendor_id'] ?? '';
    $location_id = $_POST['location_id'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $assigned_to_employee_id = isset($_POST['assigned_to_employee_id']) && $_POST['assigned_to_employee_id'] !== '' ? $_POST['assigned_to_employee_id'] : null;
    $serial_number = trim($_POST['serial_number'] ?? '');
    $lan_mac = trim($_POST['lan_mac'] ?? '');
    $wlan_mac = trim($_POST['wlan_mac'] ?? '');

    if ($asset_tag === '') $errors[] = 'Asset tag is required.';
    if (!$category_id) $errors[] = 'Category is required.';
    if (!$vendor_id) $errors[] = 'Vendor is required.';
    if (!$location_id) $errors[] = 'Location is required.';

    if ($serial_number === '') $errors[] = 'Serial Number is required.';
    // MAC address validation regex
    $mac_regex = '/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/';
    if ($lan_mac !== '' && !preg_match($mac_regex, $lan_mac)) {
        $errors[] = 'LAN MAC Address must be in format 00:11:22:33:44:55.';
    }
    if ($wlan_mac !== '' && !preg_match($mac_regex, $wlan_mac)) {
        $errors[] = 'WLAN MAC Address must be in format 00:11:22:33:44:55.';
    }
    // Check for duplicate serial number
    if ($serial_number !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE serial_number = ?');
        $stmt->execute([$serial_number]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Warning: Serial Number already exists. Please use a unique Serial Number.';
        }
    }

    if (!$errors) {
        // Set status automatically: 'active' (In Use) if assigned, 'inactive' (Available) if not
        $auto_status = !is_null($assigned_to_employee_id) ? 'active' : 'inactive';
        try {
            $stmt = $pdo->prepare('INSERT INTO assets (asset_tag, category_id, vendor_id, location_id, status, serial_number, lan_mac, wlan_mac, assigned_to_employee_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$asset_tag, $category_id, $vendor_id, $location_id, $auto_status, $serial_number, $lan_mac, $wlan_mac, $assigned_to_employee_id]);
            $new_asset_id = $pdo->lastInsertId();
            // Log asset creation
            $log_stmt = $pdo->prepare('INSERT INTO asset_history (asset_id, field_changed, old_value, new_value, action, changed_by) VALUES (?, ?, ?, ?, ?, ?)');
            $log_stmt->execute([$new_asset_id, 'ALL', '', json_encode(['asset_tag'=>$asset_tag,'category_id'=>$category_id,'vendor_id'=>$vendor_id,'location_id'=>$location_id,'status'=>$auto_status,'serial_number'=>$serial_number,'lan_mac'=>$lan_mac,'wlan_mac'=>$wlan_mac,'assigned_to_employee_id'=>$assigned_to_employee_id]), 'create', $_SESSION['username'] ?? 'system']);
            // Insert into asset_assignments history only if assigned
            if (!is_null($assigned_to_employee_id)) {
                $assign_stmt = $pdo->prepare('INSERT INTO asset_assignments (asset_id, employee_id, assigned_by, assigned_date, notes) VALUES (?, ?, ?, ?, ?)');
                $assign_stmt->execute([
                    $new_asset_id,
                    $assigned_to_employee_id,
                    $_SESSION['username'] ?? 'system',
                    date('Y-m-d'),
                    'Initial assignment'
                ]);
                // Log assignment
                $log_stmt->execute([$new_asset_id, 'assigned_to_employee_id', '', $assigned_to_employee_id, 'assign', $_SESSION['username'] ?? 'system']);
            }
            header('Location: assets.php?added=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'asset_tag') !== false) {
                $errors[] = 'Warning: Asset Tag already exists. Please use a unique Asset Tag.';
            } else {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
include 'includes/header.php';
?>
<div class="container mt-4">
    <h2>Add New Asset</h2>
    <?php foreach ($errors as $error): ?>
        <?php if ($error === 'Warning: Asset Tag already exists. Please use a unique Asset Tag.'): ?>
            <div class="alert alert-danger"><strong style="color: #b30000; font-weight: bold;">Warning: Asset Tag already exists. Please use a unique Asset Tag.</strong></div>
        <?php elseif ($error === 'Warning: Serial Number already exists. Please use a unique Serial Number.'): ?>
            <div class="alert alert-danger"><strong style="color: #b30000; font-weight: bold;">Warning: Serial Number already exists. Please use a unique Serial Number.</strong></div>
        <?php else: ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
    <?php endforeach; ?>
    <form method="post">
        <div class="form-group">
            <label>Assign to Employee</label>
            <select name="assigned_to_employee_id" class="form-control">
                <option value="">-- Unassigned --</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?php echo $emp['id']; ?>" <?php if (isset($assigned_to_employee_id) && $assigned_to_employee_id == $emp['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Asset Tag</label>
            <input type="text" name="asset_tag" class="form-control" value="<?php echo htmlspecialchars($asset_tag); ?>" required>
        </div>
        <!-- Asset Name field removed -->
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
                <option value="In Use" <?php if ($status === 'In Use') echo 'selected'; ?>>In Use</option>
                <option value="Available" <?php if ($status === 'Available') echo 'selected'; ?>>Available</option>
                <option value="In Repair" <?php if ($status === 'In Repair') echo 'selected'; ?>>In Repair</option>
                <option value="Retired" <?php if ($status === 'Retired') echo 'selected'; ?>>Retired</option>
            </select>
        </div>

        <div class="form-group">
            <label>Serial Number</label>
            <input type="text" name="serial_number" class="form-control" value="<?php echo htmlspecialchars($serial_number); ?>">
        </div>
        <div class="form-group">
            <label>LAN MAC Address</label>
            <input type="text" name="lan_mac" class="form-control" value="<?php echo htmlspecialchars($lan_mac); ?>" placeholder="e.g. 00:11:22:33:44:55">
        </div>
        <div class="form-group">
            <label>WLAN MAC Address</label>
            <input type="text" name="wlan_mac" class="form-control" value="<?php echo htmlspecialchars($wlan_mac); ?>" placeholder="e.g. 66:77:88:99:AA:BB">
        </div>
        <!-- Notes or Remarks field removed -->
        <button type="submit" class="btn btn-success">Add Asset</button>
        <a href="assets.php" class="btn btn-secondary ml-2">Cancel</a>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
