<?php
$page_title = 'Assets'; // ITMS v1.2
require_once 'config/config.php';
require_once 'includes/header.php';

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Search and filter parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$vendor_filter = $_GET['vendor'] ?? '';
$location_filter = $_GET['location'] ?? '';

// Sorting parameters
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
// Remove 'name' from allowed_sorts
$allowed_sorts = ['asset_tag', 'category_name', 'vendor_name', 'location_name', 'status', 'created_at'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'created_at';
}
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

try {
    $pdo = getDBConnection();
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(a.asset_tag LIKE ? OR a.serial_number LIKE ?)";
        $search_param = "%{$search}%";
        $params = array_merge($params, [$search_param, $search_param]);
    }
    
    if (!empty($category_filter)) {
        $where_conditions[] = "a.category_id = ?";
        $params[] = $category_filter;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "a.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($vendor_filter)) {
        $where_conditions[] = "a.vendor_id = ?";
        $params[] = $vendor_filter;
    }
    if (!empty($location_filter)) {
        $where_conditions[] = "a.location_id = ?";
        $params[] = $location_filter;
    }
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

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
        <a href="asset_add.php" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-semibold transition">
          <!-- Heroicon: plus -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Add New Asset
        </a>
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
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LAN MAC Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WLAN MAC Address</th>
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
                  <a href="asset_details.php?id=<?php echo $asset['id']; ?>">
                    <?php echo htmlspecialchars($asset['asset_tag']); ?>
                  </a>
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
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['lan_mac'] ?? ''); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['wlan_mac'] ?? ''); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['location_name'] ?? 'N/A'); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['assigned_employee_id'] ?? ''); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($asset['assigned_employee_name'] ?? ''); ?></td>
                <td class="px-6 py-4 whitespace-nowrap flex gap-2">
                  <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 text-xs font-medium transition" title="View">
                    <!-- Heroicon: eye -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-9 0a9 9 0 0118 0a9 9 0 01-18 0z" /></svg>
                    View
                  </a>
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
    <?php endif; ?>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
