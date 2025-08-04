<?php
$page_title = 'Dashboard';
require_once 'config/config.php';
require_once 'includes/header.php';

try {
    $pdo = getDBConnection();
    
    // Get dashboard statistics
    $stats = [];
    
    // Total assets
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE status != 'disposed'");
    $stats['total_assets'] = $stmt->fetch()['total'];
    
    // Active assets
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE status = 'active'");
    $stats['active_assets'] = $stmt->fetch()['total'];
    
    // Assets in maintenance
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE status = 'maintenance'");
    $stats['maintenance_assets'] = $stmt->fetch()['total'];
    
    // Total categories
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
    $stats['total_categories'] = $stmt->fetch()['total'];
    
    // Total vendors
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM vendors");
    $stats['total_vendors'] = $stmt->fetch()['total'];
    
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
    $recent_assets = $stmt->fetchAll();
    
    // Assets with upcoming warranty expiry (next 30 days)
    $stmt = $pdo->query("
        SELECT a.*, c.name as category_name, v.name as vendor_name
        FROM assets a 
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN vendors v ON a.vendor_id = v.id
        WHERE a.warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND a.status = 'active'
        ORDER BY a.warranty_expiry ASC
    ");
    $expiring_warranties = $stmt->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading dashboard data: ' . $e->getMessage();
    $stats = ['total_assets' => 0, 'active_assets' => 0, 'maintenance_assets' => 0, 'total_categories' => 0, 'total_vendors' => 0];
    $categories_data = [];
    $recent_assets = [];
    $expiring_warranties = [];
}
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">IT Inventory Management System Overview</p>
</div>

<!-- Dashboard Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($stats['total_assets']); ?></div>
        <div class="stat-label">Total Assets</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($stats['active_assets']); ?></div>
        <div class="stat-label">Active Assets</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($stats['maintenance_assets']); ?></div>
        <div class="stat-label">In Maintenance</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($stats['total_categories']); ?></div>
        <div class="stat-label">Categories</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($stats['total_vendors']); ?></div>
        <div class="stat-label">Vendors</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <!-- Recent Assets -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Assets</h3>
            <a href="assets.php" class="btn btn-primary btn-sm">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recent_assets)): ?>
                <p class="text-muted text-center">No assets found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Asset Tag</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_assets as $asset): ?>
                                <tr>
                                    <td>
                                        <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="text-primary">
                                            <?php echo htmlspecialchars($asset['asset_tag']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($asset['name']); ?></td>
                                    <td><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $asset['status'] === 'active' ? 'success' : 
                                                ($asset['status'] === 'maintenance' ? 'warning' : 'secondary'); 
                                        ?>">
                                            <?php echo ucfirst($asset['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($asset['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Assets by Category -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Assets by Category</h3>
        </div>
        <div class="card-body">
            <?php if (empty($categories_data)): ?>
                <p class="text-muted text-center">No data available.</p>
            <?php else: ?>
                <div style="space-y: 1rem;">
                    <?php foreach ($categories_data as $category): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #e9ecef;">
                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                            <span class="badge badge-primary"><?php echo $category['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Warranty Expiry Alerts -->
<?php if (!empty($expiring_warranties)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">⚠️ Warranty Expiring Soon (Next 30 Days)</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Asset Tag</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Vendor</th>
                        <th>Warranty Expiry</th>
                        <th>Days Left</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiring_warranties as $asset): ?>
                        <?php 
                            $expiry_date = new DateTime($asset['warranty_expiry']);
                            $today = new DateTime();
                            $days_left = $today->diff($expiry_date)->days;
                        ?>
                        <tr>
                            <td>
                                <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="text-primary">
                                    <?php echo htmlspecialchars($asset['asset_tag']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($asset['name']); ?></td>
                            <td><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($asset['vendor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo formatDate($asset['warranty_expiry']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $days_left <= 7 ? 'danger' : 'warning'; ?>">
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
<?php endif; ?>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <a href="asset_add.php" class="btn btn-success" style="padding: 1rem; text-align: center;">
                📋 Add New Asset
            </a>
            <a href="assets.php?status=maintenance" class="btn btn-warning" style="padding: 1rem; text-align: center;">
                🔧 View Maintenance Items
            </a>
            <a href="reports.php" class="btn btn-primary" style="padding: 1rem; text-align: center;">
                📊 Generate Reports
            </a>
            <a href="software.php" class="btn btn-secondary" style="padding: 1rem; text-align: center;">
                💾 Manage Software
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
