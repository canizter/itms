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

<div class="page-header">
    <h1 class="page-title">Assets Management</h1>
    <p class="page-subtitle">Manage your IT inventory assets</p>
</div>

<!-- Search and Filter Bar -->
<div class="search-filter-bar">
    <form method="GET" action="assets.php" id="searchForm">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Assets</h2>
        </div>
        <div style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 1rem;">
            <div style="display: flex; flex-direction: column; min-width: 180px;">
                <label for="search">Search</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Search assets..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div style="display: flex; flex-direction: column;">
                <label for="category">Category</label>
                <select name="category" id="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php if ($category_filter == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; flex-direction: column;">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="In Use" <?php if ($status_filter == 'In Use') echo 'selected'; ?>>In Use</option>
                    <option value="Available" <?php if ($status_filter == 'Available') echo 'selected'; ?>>Available</option>
                    <option value="In Repair" <?php if ($status_filter == 'In Repair') echo 'selected'; ?>>In Repair</option>
                    <option value="Retired" <?php if ($status_filter == 'Retired') echo 'selected'; ?>>Retired</option>
                </select>
            </div>
            <div style="display: flex; flex-direction: column;">
                <label for="vendor">Vendor</label>
                <select name="vendor" id="vendor" class="form-control">
                    <option value="">All Vendors</option>
                    <?php foreach ($vendors as $ven): ?>
                        <option value="<?php echo $ven['id']; ?>" <?php if ($vendor_filter == $ven['id']) echo 'selected'; ?>><?php echo htmlspecialchars($ven['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; flex-direction: column;">
                <label for="location">Location</label>
                <select name="location" id="location" class="form-control">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo $loc['id']; ?>" <?php if ($location_filter == $loc['id']) echo 'selected'; ?>><?php echo htmlspecialchars($loc['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; flex-direction: row; gap: 0.5rem; align-items: flex-end; margin-bottom: 0;">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="assets.php" class="btn btn-secondary">Clear</a>
            </div>
        </div>
    </form>
</div>

<!-- Action Bar -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            Assets (<?php echo number_format($total_records); ?> total)
        </h3>
        <div>
            <?php if (hasRole('manager')): ?>
                <a href="asset_add.php" class="btn btn-success">Add New Asset</a>
                <a href="import_assets.php" class="btn btn-info">Import</a>
            <?php endif; ?>
            <a href="export_assets.php" class="btn btn-secondary">Export</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($assets)): ?>
            <div class="text-center" style="padding: 3rem;">
                <h3 class="text-muted">No assets found</h3>
                <p class="text-muted">Try adjusting your search criteria or add your first asset.</p>
                <?php if (hasRole('manager')): ?>
                    <a href="asset_add.php" class="btn btn-primary">Add First Asset</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover data-table">
                    <thead>
                        <tr>
                            <th data-sort="asset_tag" data-order="<?php echo $sort === 'asset_tag' ? $order : 'asc'; ?>">
                                Asset Tag 
                                <?php if ($sort === 'asset_tag'): ?>
                                    <?php echo $order === 'ASC' ? '↑' : '↓'; ?>
                                <?php endif; ?>
                            </th>
                            <th>Status</th>
                            <th>Category</th>
                            <th>Vendor</th>
                            <th>Serial Number</th>
                            <th>LAN MAC Address</th>
                            <th>WLAN MAC Address</th>
                            <th>Location</th>
                            <th>Assigned Employee ID</th>
                            <th>Assigned Employee Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td>
                                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="text-primary">
                                        <strong><?php echo htmlspecialchars($asset['asset_tag']); ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $asset['status'] === 'In Use' ? 'success' : 
                                            ($asset['status'] === 'In Repair' ? 'warning' : 
                                            ($asset['status'] === 'Retired' ? 'danger' : 'secondary'));
                                    ?>">
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
                                        echo $display_status;
                                    ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($asset['vendor_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($asset['serial_number'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($asset['lan_mac'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($asset['wlan_mac'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($asset['location_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($asset['assigned_employee_id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($asset['assigned_employee_name'] ?? ''); ?></td>
                                <td>
                                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="btn btn-info btn-sm" title="View"><i class="fa fa-eye"></i> View</a>
                                    <?php if (hasRole('manager')): ?>
                                        <a href="asset_edit.php?id=<?php echo $asset['id']; ?>" class="btn btn-warning btn-sm" title="Edit"><i class="fa fa-edit"></i> Edit</a>
                                        <a href="asset_delete.php?id=<?php echo $asset['id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this asset?');"><i class="fa fa-trash"></i> Delete</a>
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
