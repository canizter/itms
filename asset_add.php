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
        $auto_status = 'inactive';
        try {
            $stmt = $pdo->prepare('INSERT INTO assets (asset_tag, category_id, vendor_id, location_id, status, serial_number, lan_mac, wlan_mac) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$asset_tag, $category_id, $vendor_id, $location_id, $auto_status, $serial_number, $lan_mac, $wlan_mac]);
            $new_asset_id = $pdo->lastInsertId();
            // Log asset creation
            $log_stmt = $pdo->prepare('INSERT INTO asset_history (asset_id, field_changed, old_value, new_value, action, changed_by) VALUES (?, ?, ?, ?, ?, ?)');
            $log_stmt->execute([$new_asset_id, 'ALL', '', json_encode(['asset_tag'=>$asset_tag,'category_id'=>$category_id,'vendor_id'=>$vendor_id,'location_id'=>$location_id,'status'=>$auto_status,'serial_number'=>$serial_number,'lan_mac'=>$lan_mac,'wlan_mac'=>$wlan_mac]), 'create', $_SESSION['username'] ?? 'system']);
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

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
  <h2 class="text-2xl font-bold tracking-tight text-gray-900 mb-6">Add New Asset</h2>
  <?php foreach ($errors as $error): ?>
    <?php if ($error === 'Warning: Asset Tag already exists. Please use a unique Asset Tag.'): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
        <!-- Heroicon: exclamation -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 17a5 5 0 100-10 5 5 0 000 10z" /></svg>
        Warning: Asset Tag already exists. Please use a unique Asset Tag.
      </div>
    <?php elseif ($error === 'Warning: Serial Number already exists. Please use a unique Serial Number.'): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
        <!-- Heroicon: exclamation -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 17a5 5 0 100-10 5 5 0 000 10z" /></svg>
        Warning: Serial Number already exists. Please use a unique Serial Number.
      </div>
    <?php else: ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
  <?php endforeach; ?>
  <form method="post" class="bg-white shadow rounded-lg p-6 space-y-5">
    <!-- Assign to Employee removed as requested -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Asset Tag</label>
      <input type="text" name="asset_tag" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($asset_tag); ?>" required>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
      <select name="category_id" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        <option value="">Select Category</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo $cat['id']; ?>" <?php if ($cat['id'] == $category_id) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
      <select name="vendor_id" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        <option value="">Select Vendor</option>
        <?php foreach ($vendors as $ven): ?>
          <option value="<?php echo $ven['id']; ?>" <?php if ($ven['id'] == $vendor_id) echo 'selected'; ?>><?php echo htmlspecialchars($ven['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
      <select name="location_id" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        <option value="">Select Location</option>
        <?php foreach ($locations as $loc): ?>
          <option value="<?php echo $loc['id']; ?>" <?php if ($loc['id'] == $location_id) echo 'selected'; ?>><?php echo htmlspecialchars($loc['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
      <select name="status" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="In Use" <?php if ($status === 'In Use') echo 'selected'; ?>>In Use</option>
        <option value="Available" <?php if ($status === 'Available') echo 'selected'; ?>>Available</option>
        <option value="In Repair" <?php if ($status === 'In Repair') echo 'selected'; ?>>In Repair</option>
        <option value="Retired" <?php if ($status === 'Retired') echo 'selected'; ?>>Retired</option>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
      <input type="text" name="serial_number" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($serial_number); ?>">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">LAN MAC Address</label>
      <input type="text" name="lan_mac" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($lan_mac); ?>" placeholder="e.g. 00:11:22:33:44:55">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">WLAN MAC Address</label>
      <input type="text" name="wlan_mac" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($wlan_mac); ?>" placeholder="e.g. 66:77:88:99:AA:BB">
    </div>
    <div class="flex gap-2 pt-2">
      <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-semibold transition">
        <!-- Heroicon: plus -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Add Asset
      </button>
      <a href="assets.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 font-semibold transition">Cancel</a>
    </div>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
