<?php
$page_title = 'Assets';
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
$allowed_sorts = ['asset_tag', 'name', 'category_name', 'vendor_name', 'location_name', 'status', 'created_at'];
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
        $where_conditions[] = "(a.asset_tag LIKE ? OR a.name LIKE ? OR a.description LIKE ? OR a.serial_number LIKE ?)";
        $search_param = "%{$search}%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
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
    
    // Get assets
    $sql = "
        SELECT a.*, c.name as category_name, v.name as vendor_name, l.name as location_name
        FROM assets a 
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN vendors v ON a.vendor_id = v.id
        LEFT JOIN locations l ON a.location_id = l.id
        {$where_clause}
        ORDER BY {$sort} {$order}
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
        <div class="form-row">
            <div class="search-group">
                <label for="search" class="form-label">Search</label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       class="form-control search-input" 
                       placeholder="Search by tag, name, description, or serial number..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-col">
                <label for="category" class="form-label">Category</label>
                <select name="category" id="category" class="form-control filter-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-col">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-control filter-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="disposed" <?php echo $status_filter === 'disposed' ? 'selected' : ''; ?>>Disposed</option>
                </select>
            </div>
            
            <div class="form-col">
                <label for="vendor" class="form-label">Vendor</label>
                <select name="vendor" id="vendor" class="form-control filter-select">
                    <option value="">All Vendors</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?php echo $vendor['id']; ?>" 
                                <?php echo $vendor_filter == $vendor['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vendor['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-col">
                <label for="location" class="form-label">Location</label>
                <select name="location" id="location" class="form-control filter-select">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['id']; ?>" 
                                <?php echo $location_filter == $location['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($location['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: flex; gap: 0.5rem; align-items: end;">
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
            <?php endif; ?>
            <button onclick="exportData('csv')" class="btn btn-secondary">Export CSV</button>
            <button onclick="printPage()" class="btn btn-secondary">Print</button>
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
                            <th data-sort="name" data-order="<?php echo $sort === 'name' ? $order : 'asc'; ?>">
                                Name
                                <?php if ($sort === 'name'): ?>
                                    <?php echo $order === 'ASC' ? '↑' : '↓'; ?>
                                <?php endif; ?>
                            </th>
                            <th>Category</th>
                            <th>Vendor</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Purchase Date</th>
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
                                <td><?php echo htmlspecialchars($asset['name']); ?></td>
                                <td><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($asset['vendor_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($asset['location_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $asset['status'] === 'active' ? 'success' : 
                                            ($asset['status'] === 'maintenance' ? 'warning' : 
                                            ($asset['status'] === 'disposed' ? 'danger' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($asset['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($asset['purchase_date']); ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="asset_details.php?id=<?php echo $asset['id']; ?>" 
                                           class="btn btn-primary btn-sm" 
                                           data-tooltip="View Details">📋</a>
                                        
                                        <?php if (hasRole('manager')): ?>
                                            <a href="asset_edit.php?id=<?php echo $asset['id']; ?>" 
                                               class="btn btn-warning btn-sm" 
                                               data-tooltip="Edit Asset">✏️</a>
                                        <?php endif; ?>
                                        
                                        <?php if (hasRole('admin')): ?>
                                            <a href="asset_delete.php?id=<?php echo $asset['id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               data-tooltip="Delete Asset"
                                               onclick="return confirmDelete('<?php echo htmlspecialchars($asset['name']); ?>')">🗑️</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">« Previous</a></li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next »</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
