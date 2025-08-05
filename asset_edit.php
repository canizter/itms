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
// Initialize all variables used in the form
$errors = [];
$asset_tag = $asset['asset_tag'];
$category_id = $asset['category_id'];
$vendor_id = $asset['vendor_id'];
$location_id = $asset['location_id'] ?? '';
$status = $asset['status'] ?? 'active';
$serial_number = $asset['serial_number'] ?? '';
$lan_mac = $asset['lan_mac'] ?? '';
$wlan_mac = $asset['wlan_mac'] ?? '';
$assigned_to_employee_id = $asset['assigned_to_employee_id'] ?? '';
// Fetch select options if not already present
if (!isset($categories)) {
    $categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
}
if (!isset($vendors)) {
    $vendors = $pdo->query('SELECT id, name FROM vendors ORDER BY name')->fetchAll();
}
if (!isset($employees)) {
    $employees = $pdo->query('SELECT id, employee_id, name FROM employees ORDER BY name')->fetchAll();
}
if (!isset($locations)) {
    $locations = $pdo->query('SELECT id, name FROM locations ORDER BY name')->fetchAll();
}
?>
<?php include 'includes/header.php'; ?>
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
  <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Asset</h2>
  <?php foreach ($errors as $error): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
      <!-- Heroicon: exclamation -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 17a5 5 0 100-10 5 5 0 000 10z" /></svg>
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endforeach; ?>
  <form method="post" class="space-y-5 bg-white shadow rounded-lg p-8">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Employee</label>
      <?php if (!empty($asset['assigned_to_employee_id'])): ?>
        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded mb-2 text-sm">This asset is currently assigned. Please return it before assigning to another employee.</div>
        <select name="assigned_to_employee_id" class="block w-full border border-gray-300 rounded px-3 py-2 bg-gray-100" disabled>
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
        <select name="assigned_to_employee_id" class="block w-full border border-gray-300 rounded px-3 py-2">
          <option value="">-- Unassigned --</option>
          <?php foreach ($employees as $emp): ?>
            <option value="<?php echo $emp['id']; ?>" <?php if ($assigned_to_employee_id == $emp['id']) echo 'selected'; ?>><?php echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['name']); ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Asset Tag</label>
      <input type="text" name="asset_tag" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($asset_tag); ?>" required>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
      <select name="category_id" class="block w-full border border-gray-300 rounded px-3 py-2" required>
        <option value="">Select Category</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo $cat['id']; ?>" <?php if ($cat['id'] == $category_id) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
      <select name="vendor_id" class="block w-full border border-gray-300 rounded px-3 py-2" required>
        <option value="">Select Vendor</option>
        <?php foreach ($vendors as $ven): ?>
          <option value="<?php echo $ven['id']; ?>" <?php if ($ven['id'] == $vendor_id) echo 'selected'; ?>><?php echo htmlspecialchars($ven['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
      <select name="location_id" class="block w-full border border-gray-300 rounded px-3 py-2" required>
        <option value="">Select Location</option>
        <?php foreach ($locations as $loc): ?>
          <option value="<?php echo $loc['id']; ?>" <?php if ($loc['id'] == $location_id) echo 'selected'; ?>><?php echo htmlspecialchars($loc['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
      <select name="status" class="block w-full border border-gray-300 rounded px-3 py-2">
        <option value="active" <?php if ($status === 'active') echo 'selected'; ?>>In Use</option>
        <option value="inactive" <?php if ($status === 'inactive') echo 'selected'; ?>>Available</option>
        <option value="maintenance" <?php if ($status === 'maintenance') echo 'selected'; ?>>In Repair</option>
        <option value="disposed" <?php if ($status === 'disposed') echo 'selected'; ?>>Retired</option>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
      <input type="text" name="serial_number" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($serial_number); ?>" required>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">LAN MAC Address</label>
      <input type="text" name="lan_mac" class="block w-full border border-gray-300 rounded px-3 py-2" value="<?php echo htmlspecialchars($lan_mac); ?>" placeholder="00:00:00:00:00:00">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">WLAN MAC Address</label>
      <input type="text" name="wlan_mac" class="block w-full border border-gray-300 rounded px-3 py-2" value="<?php echo htmlspecialchars($wlan_mac); ?>" placeholder="00:00:00:00:00:00">
    </div>
    <div class="flex flex-wrap gap-2 mt-6">
      <button type="submit" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700 font-semibold transition">Update Asset</button>
      <a href="assets.php" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold transition">Cancel</a>
      <a href="asset_assignments.php?asset_id=<?php echo $asset_id; ?>" class="px-4 py-2 rounded bg-blue-100 text-blue-800 hover:bg-blue-200 font-semibold transition">View Assignment History</a>
      <?php if (!empty($asset['assigned_to_employee_id'])): ?>
        <a href="asset_return.php?id=<?php echo $asset_id; ?>" class="px-4 py-2 rounded bg-red-100 text-red-800 hover:bg-red-200 font-semibold transition" onclick="return confirm('Mark this asset as returned?');">Return</a>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
