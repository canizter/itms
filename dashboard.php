<?php
$page_title = 'Dashboard';
require_once 'config/config.php';
require_once 'includes/header.php';

try {
    $pdo = getDBConnection();
    
    // Get dashboard statistics
    $stats = [];
    // Total assets (not disposed)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE status != 'disposed'");
    $stats['total_assets'] = $stmt->fetch()['total'];
    // In Use assets (active)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE status = 'active'");
    $stats['in_use_assets'] = $stmt->fetch()['total'];
    // Available assets (inactive)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE status = 'inactive'");
    $stats['available_assets'] = $stmt->fetch()['total'];
    // In Repair assets (maintenance)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE status = 'maintenance'");
    $stats['in_repair_assets'] = $stmt->fetch()['total'];
    // Assets by category
    $stmt = $pdo->query("
        SELECT c.name, COUNT(a.id) as count, c.id
        FROM categories c 
        LEFT JOIN assets a ON c.id = a.category_id AND a.status != 'disposed'
        GROUP BY c.id, c.name 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $categories_data = $stmt->fetchAll();
    // Recent assets (last 10)
    $stmt = $pdo->query("
        SELECT a.*, c.name as category_name, v.name as vendor_name, l.name as location_name
        FROM assets a 
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN vendors v ON a.vendor_id = v.id  
        LEFT JOIN locations l ON a.location_id = l.id
        WHERE a.status != 'disposed'
        ORDER BY a.created_at DESC 
        LIMIT 10
    ");
    $stmt = $pdo->query("
        SELECT a.*, c.name as category_name, v.name as vendor_name, l.name as location_name, m.name as model_name, a.serial_number
        FROM assets a 
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN vendors v ON a.vendor_id = v.id  
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN models m ON a.model_id = m.id
        WHERE a.status != 'disposed'
        ORDER BY a.created_at DESC 
        LIMIT 10
    ");
    $recent_assets = $stmt->fetchAll();
    // Consumables by type
    $consumables_by_type = $pdo->query("
        SELECT t.type, SUM(c.quantity) as total_quantity
        FROM consumable_types t
        LEFT JOIN consumables c ON t.id = c.consumable_type
        GROUP BY t.id, t.type
        ORDER BY t.type ASC
    ")->fetchAll();
    // (Warranty expiry section removed: column no longer exists)
    $expiring_warranties = [];
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading dashboard data: ' . $e->getMessage();
    $stats = ['total_assets' => 0, 'active_assets' => 0, 'maintenance_assets' => 0, 'total_categories' => 0, 'total_vendors' => 0];
    $categories_data = [];
    $recent_assets = [];
    $expiring_warranties = [];
}
?>


<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-1">Dashboard</h1>
    <p class="text-gray-500 text-base">IT Management System Overview</p>
  </div>
  <!-- Dashboard Statistics -->

  <!-- Statistics Section -->
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-10">
    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
      <div class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['total_assets']); ?></div>
      <div class="text-gray-500 mt-1">Total Assets</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
      <div class="text-2xl font-bold text-yellow-600"><?php echo number_format(isset($stats['in_use_assets']) && $stats['in_use_assets'] !== null ? $stats['in_use_assets'] : 0); ?></div>
      <div class="text-gray-500 mt-1">In Use</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
      <div class="text-2xl font-bold text-green-600"><?php echo number_format($stats['available_assets']); ?></div>
      <div class="text-gray-500 mt-1">Available</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
      <div class="text-2xl font-bold text-red-600"><?php echo number_format(isset($stats['in_repair_assets']) && $stats['in_repair_assets'] !== null ? $stats['in_repair_assets'] : 0); ?></div>
      <div class="text-gray-500 mt-1">In Repair</div>
    </div>
  </div>

  <!-- Recent Assets Section -->
  <div class="bg-white rounded-lg shadow mb-10">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="text-lg font-semibold text-gray-900">Recent Assets</h3>
      <a href="assets.php" class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium transition">View All</a>
    </div>
    <div class="p-6">
      <?php if (empty($recent_assets)): ?>
        <p class="text-gray-400 text-center">No assets found.</p>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($recent_assets as $asset): ?>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="text-blue-600 hover:underline">
                      <?php echo htmlspecialchars($asset['asset_tag']); ?>
                    </a>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></td>
                  <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($asset['vendor_name'] ?? 'N/A'); ?></td>
                  <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($asset['model_name'] ?? 'N/A'); ?></td>
                  <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($asset['serial_number'] ?? 'N/A'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Consumables by Type and Assets by Category Section -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
    <!-- Consumables by Type -->
    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
      <div class="text-lg font-semibold text-gray-900 mb-2">Consumables by Type</div>
      <?php if (!empty($consumables_by_type)): ?>
        <div class="w-full">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Quantity</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($consumables_by_type as $row): ?>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($row['type']); ?></td>
                  <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-bold"><?php echo number_format($row['total_quantity'] ?? 0); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-gray-400 text-center">No consumables data found.</div>
      <?php endif; ?>
    </div>
    <!-- Assets by Category -->
    <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
      <div class="text-lg font-semibold text-gray-900 mb-2">Assets by Category</div>
      <?php if (empty($categories_data)): ?>
        <div class="text-gray-400 text-center">No data available.</div>
      <?php else: ?>
        <div class="space-y-3 w-full">
          <?php foreach ($categories_data as $category): ?>
            <div class="flex justify-between items-center py-2 border-b last:border-b-0">
              <span class="text-gray-700"><?php echo htmlspecialchars($category['name']); ?></span>
              <span class="inline-block px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold"><?php echo $category['count']; ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>


<!-- Warranty Expiry Alerts -->
<?php if (!empty($expiring_warranties)): ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
  <div class="bg-white rounded-lg shadow mb-8">
    <div class="px-6 py-4 border-b">
      <h3 class="text-lg font-semibold text-yellow-700 flex items-center gap-2">
        <span>⚠️</span> Warranty Expiring Soon (Next 30 Days)
      </h3>
    </div>
    <div class="p-6">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-yellow-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Tag</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warranty Expiry</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Left</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($expiring_warranties as $asset): ?>
              <?php 
                $expiry_date = new DateTime($asset['warranty_expiry']);
                $today = new DateTime();
                $days_left = $today->diff($expiry_date)->days;
              ?>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                  <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="text-blue-600 hover:underline">
                    <?php echo htmlspecialchars($asset['asset_tag']); ?>
                  </a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($asset['name']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($asset['vendor_name'] ?? 'N/A'); ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?php echo formatDate($asset['warranty_expiry']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?php echo $days_left <= 7 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                    <?php echo $days_left; ?> days
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Quick Actions removed -->

<?php require_once 'includes/footer.php'; ?>
