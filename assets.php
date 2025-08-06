<!-- Assignment History Modal -->
<div id="assignmentHistoryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-auto max-h-[90vh] flex flex-col">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h5 class="text-lg font-semibold flex items-center gap-2">
        <!-- Heroicon: clock -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        Assignment History - <span id="assignmentHistoryAssetTag" class="ml-2 text-purple-700"></span>
      </h5>
      <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('assignmentHistoryModal').classList.add('hidden')">&times;</button>
    </div>
    <div class="px-6 py-4 overflow-y-auto flex-1">
      <div id="assignmentHistoryContent">
        <!-- Populated by JS -->
      </div>
    </div>
    <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
      <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('assignmentHistoryModal').classList.add('hidden')">Close</button>
    </div>
  </div>
</div>
<?php
$page_title = 'Assets';
require_once 'config/config.php';
require_once 'includes/header.php';
$pdo = getDBConnection();

// Handle add asset POST
$add_errors = [];
$add_success = '';
$asset_tag = $category_id = $vendor_id = $location_id = $status = $serial_number = $lan_mac = $wlan_mac = '';
$assigned_to_employee_id = '';
if (isset($_POST['add_asset'])) {
    $asset_tag = trim($_POST['asset_tag'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $vendor_id = $_POST['vendor_id'] ?? '';
    $location_id = $_POST['location_id'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $assigned_to_employee_id = isset($_POST['assigned_to_employee_id']) && $_POST['assigned_to_employee_id'] !== '' ? $_POST['assigned_to_employee_id'] : null;
    $serial_number = trim($_POST['serial_number'] ?? '');
    $lan_mac = trim($_POST['lan_mac'] ?? '');
    $wlan_mac = trim($_POST['wlan_mac'] ?? '');
    $asset_note = trim($_POST['asset_note'] ?? '');

    if ($asset_tag === '') $add_errors[] = 'Asset tag is required.';
    if (!$category_id) $add_errors[] = 'Category is required.';
    if (!$vendor_id) $add_errors[] = 'Vendor is required.';
    if (!$location_id) $add_errors[] = 'Location is required.';
    if ($serial_number === '') $add_errors[] = 'Serial Number is required.';
    $mac_regex = '/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/';
    if ($lan_mac !== '' && !preg_match($mac_regex, $lan_mac)) {
        $add_errors[] = 'LAN MAC Address must be in format 00:11:22:33:44:55.';
    }
    if ($wlan_mac !== '' && !preg_match($mac_regex, $wlan_mac)) {
        $add_errors[] = 'WLAN MAC Address must be in format 00:11:22:33:44:55.';
    }
    if ($serial_number !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE serial_number = ?');
        $stmt->execute([$serial_number]);
        if ($stmt->fetchColumn() > 0) {
            $add_errors[] = 'Warning: Serial Number already exists. Please use a unique Serial Number.';
        }
    }
    if (!$add_errors) {
        $auto_status = !is_null($assigned_to_employee_id) ? 'active' : 'inactive';
        try {
            $stmt = $pdo->prepare('INSERT INTO assets (asset_tag, category_id, vendor_id, location_id, status, serial_number, lan_mac, wlan_mac, assigned_to_employee_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$asset_tag, $category_id, $vendor_id, $location_id, $auto_status, $serial_number, $lan_mac, $wlan_mac, $assigned_to_employee_id, $asset_note]);
            $new_asset_id = $pdo->lastInsertId();
            $log_stmt = $pdo->prepare('INSERT INTO asset_history (asset_id, field_changed, old_value, new_value, action, changed_by) VALUES (?, ?, ?, ?, ?, ?)');
            $log_stmt->execute([$new_asset_id, 'ALL', '', json_encode(['asset_tag'=>$asset_tag,'category_id'=>$category_id,'vendor_id'=>$vendor_id,'location_id'=>$location_id,'status'=>$auto_status,'serial_number'=>$serial_number,'lan_mac'=>$lan_mac,'wlan_mac'=>$wlan_mac,'assigned_to_employee_id'=>$assigned_to_employee_id,'notes'=>$asset_note]), 'create', $_SESSION['username'] ?? 'system']);
            if (!is_null($assigned_to_employee_id)) {
                $assign_stmt = $pdo->prepare('INSERT INTO asset_assignments (asset_id, employee_id, assigned_by, assigned_date, notes) VALUES (?, ?, ?, ?, ?)');
                $assign_stmt->execute([
                    $new_asset_id,
                    $assigned_to_employee_id,
                    $_SESSION['username'] ?? 'system',
                    date('Y-m-d'),
                    'Initial assignment'
                ]);
                $log_stmt->execute([$new_asset_id, 'assigned_to_employee_id', '', $assigned_to_employee_id, 'assign', $_SESSION['username'] ?? 'system']);
            }
            $add_success = 'Asset added successfully!';
            // Reset form fields
            $asset_tag = $category_id = $vendor_id = $location_id = $status = $serial_number = $lan_mac = $wlan_mac = '';
            $assigned_to_employee_id = '';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'asset_tag') !== false) {
                $add_errors[] = 'Warning: Asset Tag already exists. Please use a unique Asset Tag.';
            } else {
                $add_errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

try {
    // Pagination and sorting
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'asset_tag';
    $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';

    // Search and filter
    $search = trim($_GET['search'] ?? '');
    $category_filter = $_GET['category'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $vendor_filter = $_GET['vendor'] ?? '';
    $location_filter = $_GET['location'] ?? '';

    $where_clauses = [];
    $params = [];
    if ($search !== '') {
        $where_clauses[] = '(a.asset_tag LIKE ? OR a.serial_number LIKE ? OR a.lan_mac LIKE ? OR a.wlan_mac LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($category_filter !== '') {
        $where_clauses[] = 'a.category_id = ?';
        $params[] = $category_filter;
    }
    if ($status_filter !== '') {
        $where_clauses[] = 'a.status = ?';
        $params[] = $status_filter;
    }
    if ($vendor_filter !== '') {
        $where_clauses[] = 'a.vendor_id = ?';
        $params[] = $vendor_filter;
    }
    if ($location_filter !== '') {
        $where_clauses[] = 'a.location_id = ?';
        $params[] = $location_filter;
    }

    $where_clause = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    // Get total count
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM assets a 
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN vendors v ON a.vendor_id = v.id
        LEFT JOIN locations l ON a.location_id = l.id
        {$where_clause}
    ";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    // Get assets with assigned employee info
    $sql = "
        SELECT a.id, a.asset_tag, a.serial_number, a.lan_mac, a.wlan_mac, a.category_id, a.vendor_id, a.location_id, a.status, a.assigned_to_employee_id,
               c.name as category_name, v.name as vendor_name, l.name as location_name,
               e.employee_id as assigned_employee_id, e.name as assigned_employee_name
        FROM assets a 
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN vendors v ON a.vendor_id = v.id
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN employees e ON a.assigned_to_employee_id = e.id
        {$where_clause}
        ORDER BY 
        " . ($sort === 'created_at' ? 'a.created_at' : $sort) . " {$order}
        LIMIT {$limit} OFFSET {$offset}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $assets = $stmt->fetchAll();

    // Get filter options
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
    $vendors = $pdo->query("SELECT id, name FROM vendors ORDER BY name")->fetchAll();
    $locations = $pdo->query("SELECT id, name FROM locations ORDER BY name")->fetchAll();

} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading assets: ' . $e->getMessage();
    $assets = [];
    $categories = [];
    $vendors = [];
    $locations = [];
    $total_records = 0;
    $total_pages = 0;
}
?>


<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
  <div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Assets Management</h1>
    <p class="text-gray-500 text-sm">Manage your IT inventory assets</p>
  </div>

  <!-- Search and Filter Bar -->
  <form method="GET" action="assets.php" id="searchForm" class="bg-white shadow rounded-lg p-6 mb-6">
    <div class="flex flex-wrap gap-4 items-end">
      <div class="flex flex-col min-w-[180px] flex-1">
        <label for="search" class="text-sm font-medium text-gray-700 mb-1">Search</label>
        <input type="text" name="search" id="search" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Search assets..." value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <div class="flex flex-col flex-1 min-w-[150px]">
        <label for="category" class="text-sm font-medium text-gray-700 mb-1">Category</label>
        <select name="category" id="category" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php if ($category_filter == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex flex-col flex-1 min-w-[150px]">
        <label for="status" class="text-sm font-medium text-gray-700 mb-1">Status</label>
        <select name="status" id="status" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Statuses</option>
          <option value="In Use" <?php if ($status_filter == 'In Use') echo 'selected'; ?>>In Use</option>
          <option value="Available" <?php if ($status_filter == 'Available') echo 'selected'; ?>>Available</option>
          <option value="In Repair" <?php if ($status_filter == 'In Repair') echo 'selected'; ?>>In Repair</option>
          <option value="Retired" <?php if ($status_filter == 'Retired') echo 'selected'; ?>>Retired</option>
        </select>
      </div>
      <div class="flex flex-col flex-1 min-w-[150px]">
        <label for="vendor" class="text-sm font-medium text-gray-700 mb-1">Vendor</label>
        <select name="vendor" id="vendor" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Vendors</option>
          <?php foreach ($vendors as $ven): ?>
            <option value="<?php echo $ven['id']; ?>" <?php if ($vendor_filter == $ven['id']) echo 'selected'; ?>><?php echo htmlspecialchars($ven['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex flex-col flex-1 min-w-[150px]">
        <label for="location" class="text-sm font-medium text-gray-700 mb-1">Location</label>
        <select name="location" id="location" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Locations</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?php echo $loc['id']; ?>" <?php if ($location_filter == $loc['id']) echo 'selected'; ?>><?php echo htmlspecialchars($loc['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex flex-row gap-2 items-end">
        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition">
          <!-- Heroicon: search -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z" /></svg>
          Search
        </button>
        <a href="assets.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 font-semibold transition">Clear</a>
      </div>
    </div>
  </form>

  <!-- Action Bar -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-4">
    <h2 class="text-lg font-semibold text-gray-900">Assets (<?php echo number_format($total_records); ?> total)</h2>
    <div class="flex gap-2">
      <?php if (hasRole('manager')): ?>
        <button type="button" onclick="openAddAssetModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-semibold transition">
          <!-- Heroicon: plus -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Add New Asset
        </button>
        <a href="import_assets.php" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 font-semibold transition">
          <!-- Heroicon: arrow-down-tray -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-8m0 8l-4-4m4 4l4-4m-8 8h8a2 2 0 002-2V6a2 2 0 00-2-2h-8a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
          Import
        </a>
      <?php endif; ?>
      <a href="export_assets.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-800 rounded hover:bg-gray-200 font-semibold transition">
        <!-- Heroicon: arrow-up-tray -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m0-8l-4 4m4-4l4 4m-8 8h8a2 2 0 002-2V6a2 2 0 00-2-2h-8a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        Export
      </a>
    </div>
  </div>

  <div class="bg-white shadow rounded-lg overflow-hidden">
    <?php if (empty($assets)): ?>
      <div class="text-center text-gray-400 py-12">
        <h3 class="text-lg font-semibold mb-2">No assets found</h3>
        <p class="mb-4">Try adjusting your search criteria or add your first asset.</p>
        <?php if (hasRole('manager')): ?>
          <a href="asset_add.php" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition">
            <!-- Heroicon: plus -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Add First Asset
          </a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" data-sort="asset_tag" data-order="<?php echo $sort === 'asset_tag' ? $order : 'asc'; ?>">
                Asset Tag
                <?php if ($sort === 'asset_tag'): ?>
                  <span><?php echo $order === 'ASC' ? '↑' : '↓'; ?></span>
                <?php endif; ?>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</th>
              <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LAN MAC Address</th> -->
              <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WLAN MAC Address</th> -->
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Employee ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Employee Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($assets as $asset): ?>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap font-semibold text-blue-700 hover:underline">
                  <button type="button" onclick="showAssetModal(<?php echo htmlspecialchars(json_encode($asset), ENT_QUOTES, 'UTF-8'); ?>)" class="focus:outline-none">
                    <?php echo htmlspecialchars($asset['asset_tag']); ?>
                  </button>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <?php 
                    $status_map = [
                      'active' => 'In Use',
                      'inactive' => 'Available',
                      'maintenance' => 'In Repair',
                      'disposed' => 'Retired',
                      'In Use' => 'In Use',
                      'Available' => 'Available',
                      'In Repair' => 'In Repair',
                      'Retired' => 'Retired',
                    ];
                    $display_status = $status_map[$asset['status']] ?? htmlspecialchars($asset['status']);
                    $badge_classes = [
                      'In Use' => 'bg-green-100 text-green-800',
                      'Available' => 'bg-blue-100 text-blue-800',
                      'In Repair' => 'bg-yellow-100 text-yellow-800',
                      'Retired' => 'bg-red-100 text-red-800',
                    ];
                    $badge_class = $badge_classes[$display_status] ?? 'bg-gray-100 text-gray-800';
                  ?>
                  <span class="inline-block px-2 py-1 rounded text-xs font-semibold <?php echo $badge_class; ?>">
                    <?php echo $display_status; ?>
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['vendor_name'] ?? 'N/A'); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['serial_number'] ?? ''); ?></td>
                <!-- <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['lan_mac'] ?? ''); ?></td> -->
                <!-- <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['wlan_mac'] ?? ''); ?></td> -->
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['location_name'] ?? 'N/A'); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['assigned_employee_id'] ?? ''); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['assigned_employee_name'] ?? ''); ?></td>
                <td class="px-6 py-4 whitespace-nowrap flex gap-2">
                  <button type="button" onclick="showAssetModal(<?php echo htmlspecialchars(json_encode($asset), ENT_QUOTES, 'UTF-8'); ?>)" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 text-xs font-medium transition" title="View">
                    <!-- Heroicon: eye -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-9 0a9 9 0 0118 0a9 9 0 01-18 0z" /></svg>
                    View
                  </button>
                  <button type="button" onclick="showAssignmentHistoryModal(<?php echo $asset['id']; ?>, '<?php echo htmlspecialchars($asset['asset_tag'], ENT_QUOTES, 'UTF-8'); ?>')" class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 text-purple-800 rounded hover:bg-purple-200 text-xs font-medium transition" title="Assignment History">
                    <!-- Heroicon: clock -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Assignment
                  </button>
<!-- Asset Details Modal -->
<div id="assetDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-auto max-h-[90vh] flex flex-col">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h5 class="text-lg font-semibold flex items-center gap-2">
        <!-- Heroicon: eye -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-9 0a9 9 0 0118 0a9 9 0 01-18 0z" /></svg>
        Asset Details
      </h5>
      <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('assetDetailsModal').classList.add('hidden')">&times;</button>
    </div>
    <div class="px-6 py-4 overflow-y-auto flex-1">
      <dl class="divide-y divide-gray-100" id="assetDetailsContent">
        <!-- Populated by JS -->
      </dl>
    </div>
    <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
      <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('assetDetailsModal').classList.add('hidden')">Close</button>
    </div>
  </div>
</div>
<script>
function showAssetModal(asset) {
  const modal = document.getElementById('assetDetailsModal');
  const content = document.getElementById('assetDetailsContent');
  // Build details HTML
  let html = '';
  html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Asset Tag:</dt><dd class="text-gray-900">${asset.asset_tag || ''}</dd></div>`;
  html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Category:</dt><dd class="text-gray-900">${asset.category_name || ''}</dd></div>`;
  html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Vendor:</dt><dd class="text-gray-900">${asset.vendor_name || ''}</dd></div>`;
  html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Location:</dt><dd class="text-gray-900">${asset.location_name || ''}</dd></div>`;
  html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Status:</dt><dd class="text-gray-900">${asset.status || ''}</dd></div>`;
  html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Serial Number:</dt><dd class="text-gray-900">${asset.serial_number || ''}</dd></div>`;
  html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">LAN MAC Address:</dt><dd class="text-gray-900">${asset.lan_mac || ''}</dd></div>`;
  html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">WLAN MAC Address:</dt><dd class="text-gray-900">${asset.wlan_mac || ''}</dd></div>`;
  html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Assigned Employee ID:</dt><dd class="text-gray-900">${asset.assigned_employee_id || ''}</dd></div>`;
  html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Assigned Employee Name:</dt><dd class="text-gray-900">${asset.assigned_employee_name || ''}</dd></div>`;
  html += `<div class="flex justify-between py-2"><dt class="font-medium text-gray-700">Note / Remarks:</dt><dd class="text-gray-900">${asset.notes || ''}</dd></div>`;
  content.innerHTML = html;
  modal.classList.remove('hidden');
}

function showAssignmentHistoryModal(assetId, assetTag) {
  const modal = document.getElementById('assignmentHistoryModal');
  const content = document.getElementById('assignmentHistoryContent');
  const tagSpan = document.getElementById('assignmentHistoryAssetTag');
  tagSpan.textContent = assetTag;
  content.innerHTML = '<div class="text-gray-500 text-sm">Loading...</div>';
  modal.classList.remove('hidden');
  fetch('asset_assignment_history_api.php?asset_id=' + encodeURIComponent(assetId))
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        content.innerHTML = `<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm'>${data.error}</div>`;
        return;
      }
      if (!data.history || data.history.length === 0) {
        content.innerHTML = '<div class="text-gray-500 text-sm">No assignment history found for this asset.</div>';
        return;
      }
      let html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead><tr>' +
        '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee ID</th>' +
        '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee Name</th>' +
        '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Assigned By</th>' +
        '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Assigned Date</th>' +
        '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Return Date</th>' +
        '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>' +
        '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Recorded</th>' +
        '</tr></thead><tbody>';
      for (const row of data.history) {
        html += '<tr>' +
          `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.employee_id || ''}</td>` +
          `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.employee_name || ''}</td>` +
          `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.assigned_by || ''}</td>` +
          `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.assigned_date || ''}</td>` +
          `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.return_date || ''}</td>` +
          `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.notes || ''}</td>` +
          `<td class="px-4 py-2 whitespace-nowrap text-gray-700">${row.created_at || ''}</td>` +
          '</tr>';
      }
      html += '</tbody></table></div>';
      content.innerHTML = html;
    })
    .catch(err => {
      content.innerHTML = `<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm'>Error loading assignment history.</div>`;
    });
}
</script>
                  <?php if (hasRole('manager')): ?>
                    <a href="asset_edit.php?id=<?php echo $asset['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200 text-xs font-medium transition" title="Edit">
                      <!-- Heroicon: pencil -->
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a4 4 0 01-2.828 1.172H7v-2a4 4 0 011.172-2.828z" /></svg>
                      Edit
                    </a>
                    <a href="asset_delete.php?id=<?php echo $asset['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200 text-xs font-medium transition" title="Delete" onclick="return confirm('Are you sure you want to delete this asset?');">
                      <!-- Heroicon: trash -->
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                      Delete
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <!-- Pagination Controls -->
      <?php if ($total_pages > 1): ?>
      <div class="flex justify-center items-center gap-2 py-4">
        <?php if ($page > 1): ?>
          <a href="<?php echo htmlspecialchars(preg_replace('/([&?])page=\d+/', '$1', $_SERVER['REQUEST_URI'])) . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'page=' . ($page-1); ?>" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">&laquo; Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="<?php echo htmlspecialchars(preg_replace('/([&?])page=\d+/', '$1', $_SERVER['REQUEST_URI'])) . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'page=' . $i; ?>" class="px-3 py-1 rounded <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
          <a href="<?php echo htmlspecialchars(preg_replace('/([&?])page=\d+/', '$1', $_SERVER['REQUEST_URI'])) . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'page=' . ($page+1); ?>" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Next &raquo;</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php
  // Fetch employees for the add asset modal
  $employees = $pdo->query('SELECT id, employee_id, name FROM employees ORDER BY name')->fetchAll();
  ?>

  <!-- Add Asset Modal (Tailwind, hidden by default) -->
  <div id="addAssetModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-auto max-h-[90vh] flex flex-col">
      <form method="post" action="assets.php" class="flex-1 flex flex-col overflow-y-auto">
        <input type="hidden" name="add_asset" value="1">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h5 class="text-lg font-semibold flex items-center gap-2">
            <!-- Heroicon: plus-circle -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Add New Asset
          </h5>
          <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('addAssetModal').classList.add('hidden')">&times;</button>
        </div>
        <div class="px-6 py-4 flex-1 overflow-y-auto">
          <?php if (!empty($add_errors)): ?>
            <?php foreach ($add_errors as $error): ?>
              <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
          <?php elseif ($add_success): ?>
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-3 text-sm"><?php echo htmlspecialchars($add_success); ?></div>
          <?php endif; ?>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Employee</label>
            <select name="assigned_to_employee_id" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">-- Unassigned --</option>
              <?php foreach ($employees as $emp): ?>
                <option value="<?php echo $emp['id']; ?>" <?php if (isset($assigned_to_employee_id) && $assigned_to_employee_id == $emp['id']) echo 'selected'; ?>>
                  <?php echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Asset Tag</label>
            <input type="text" name="asset_tag" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($asset_tag); ?>" required>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select name="category_id" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              <option value="">Select Category</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php if ($cat['id'] == $category_id) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
            <select name="vendor_id" id="modal_vendor_id" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              <option value="">Select Vendor</option>
              <?php foreach ($vendors as $ven): ?>
                <option value="<?php echo $ven['id']; ?>" <?php if ($ven['id'] == $vendor_id) echo 'selected'; ?>><?php echo htmlspecialchars($ven['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
            <select name="model_id" id="modal_model_id" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">Select a vendor first</option>
            </select>
          </div>
<script>
// Dynamically load models for selected vendor in Add Asset Modal
document.addEventListener('DOMContentLoaded', function() {
  const vendorSelect = document.getElementById('modal_vendor_id');
  const modelSelect = document.getElementById('modal_model_id');
  function updateModels(vendorId, selectedModelId = '') {
    if (!vendorId) {
      modelSelect.innerHTML = '<option value="">Select a vendor first</option>';
      return;
    }
    fetch('api_get_models.php?vendor_id=' + encodeURIComponent(vendorId))
      .then(r => r.json())
      .then(data => {
        if (data.success && data.models.length > 0) {
          let opts = '<option value="">Select Model</option>';
          data.models.forEach(function(model) {
            opts += `<option value="${model.id}"${model.id == selectedModelId ? ' selected' : ''}>${model.name}</option>`;
          });
          modelSelect.innerHTML = opts;
        } else {
          modelSelect.innerHTML = '<option value="">N/A</option>';
        }
      });
  }
  if (vendorSelect) {
    vendorSelect.addEventListener('change', function() {
      updateModels(this.value);
    });
    // On modal open, if vendor is preselected, load models
    if (vendorSelect.value) {
      updateModels(vendorSelect.value, '');
    }
  }
});
</script>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
            <select name="location_id" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
              <option value="">Select Location</option>
              <?php foreach ($locations as $loc): ?>
                <option value="<?php echo $loc['id']; ?>" <?php if ($loc['id'] == $location_id) echo 'selected'; ?>><?php echo htmlspecialchars($loc['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="In Use" <?php if ($status === 'In Use') echo 'selected'; ?>>In Use</option>
              <option value="Available" <?php if ($status === 'Available') echo 'selected'; ?>>Available</option>
              <option value="In Repair" <?php if ($status === 'In Repair') echo 'selected'; ?>>In Repair</option>
              <option value="Retired" <?php if ($status === 'Retired') echo 'selected'; ?>>Retired</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
            <input type="text" name="serial_number" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($serial_number); ?>">
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">LAN MAC Address</label>
            <input type="text" name="lan_mac" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($lan_mac); ?>" placeholder="e.g. 00:11:22:33:44:55">
          </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">WLAN MAC Address</label>
          <input type="text" name="wlan_mac" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($wlan_mac); ?>" placeholder="e.g. 66:77:88:99:AA:BB">
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Note / Remarks</label>
          <textarea name="asset_note" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2" placeholder="Optional note or remarks about this asset."><?php echo isset($asset_note) ? htmlspecialchars($asset_note) : ''; ?></textarea>
        </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
          <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('addAssetModal').classList.add('hidden')">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700 font-semibold" name="add_asset">Add Asset</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  <?php if (!empty($add_errors)): ?>
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('addAssetModal').classList.remove('hidden');
    });
  <?php endif; ?>
    function openAddAssetModal() {
      document.getElementById('addAssetModal').classList.remove('hidden');
    }
  </script>
</div>

<?php require_once 'includes/footer.php'; ?>
