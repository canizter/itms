<?php
// assets_table_ajax.php
require_once 'config/config.php';
$pdo = getDBConnection();

// Collect filters from POST
$search = trim($_POST['search'] ?? '');
$category_filter = $_POST['category'] ?? '';
$status_filter = $_POST['status'] ?? '';
$vendor_filter = $_POST['vendor'] ?? '';
$location_filter = $_POST['location'] ?? '';
$model_filter = $_POST['model'] ?? '';
$serial_filter = trim($_POST['serial'] ?? '');
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$sort = $_POST['sort'] ?? 'asset_tag';
$order = isset($_POST['order']) && strtoupper($_POST['order']) === 'DESC' ? 'DESC' : 'ASC';

$where_clauses = [];
$params = [];
if ($search !== '') {
    $where_clauses[] = '(a.asset_tag LIKE ? OR a.serial_number LIKE ? OR a.lan_mac LIKE ? OR a.wlan_mac LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($serial_filter !== '') {
    $where_clauses[] = 'a.serial_number LIKE ?';
    $params[] = "%$serial_filter%";
}
if ($model_filter !== '') {
    $where_clauses[] = 'a.model_id = ?';
    $params[] = $model_filter;
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

$sql = "
    SELECT a.id, a.asset_tag, a.serial_number, a.lan_mac, a.wlan_mac, a.category_id, a.vendor_id, a.model_id, a.location_id, a.status, a.assigned_to_employee_id,
           a.notes,
           c.name as category_name, v.name as vendor_name, m.name as model_name, l.name as location_name,
           e.employee_id as assigned_employee_id, e.name as assigned_employee_name
    FROM assets a 
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN vendors v ON a.vendor_id = v.id
    LEFT JOIN models m ON a.model_id = m.id
    LEFT JOIN locations l ON a.location_id = l.id
    LEFT JOIN employees e ON a.assigned_to_employee_id = e.id
    $where_clause
    ORDER BY 
    " . ($sort === 'created_at' ? 'a.created_at' : $sort) . " $order
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assets = $stmt->fetchAll();

// Render only the table body rows
foreach ($assets as $asset) {
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
    echo '<tr>';
    echo '<td class="px-6 py-4 whitespace-nowrap font-semibold text-blue-700 hover:underline">' . htmlspecialchars($asset['asset_tag']) . '</td>';
    echo '<td class="px-6 py-4 whitespace-nowrap"><span class="inline-block px-2 py-1 rounded text-xs font-semibold ' . $badge_class . '">' . $display_status . '</span></td>';
    echo '<td class="px-6 py-4 whitespace-nowrap text-gray-700">' . htmlspecialchars($asset['category_name'] ?? 'N/A') . '</td>';
    echo '<td class="px-6 py-4 whitespace-nowrap text-gray-700">' . htmlspecialchars($asset['vendor_name'] ?? 'N/A') . '</td>';
    echo '<td class="px-6 py-4 whitespace-nowrap text-gray-700">' . htmlspecialchars($asset['model_name'] ?? 'N/A') . '</td>';
    echo '<td class="px-6 py-4 whitespace-nowrap text-gray-700">' . htmlspecialchars($asset['serial_number'] ?? '') . '</td>';
    echo '<td class="px-6 py-4 whitespace-nowrap text-gray-700">' . htmlspecialchars($asset['location_name'] ?? 'N/A') . '</td>';
    echo '<td class="px-6 py-4 whitespace-nowrap text-gray-700">' . htmlspecialchars($asset['assigned_employee_id'] ?? '') . '</td>';
    echo '<td class="px-6 py-4 whitespace-nowrap text-gray-700">' . htmlspecialchars($asset['assigned_employee_name'] ?? '') . '</td>';
    echo '<td class="px-6 py-4 whitespace-nowrap text-gray-700">' . htmlspecialchars($asset['notes'] ?? '') . '</td>';
    // Prepare asset data for JS (as in main page)
    $asset_for_modal = $asset;
    $asset_for_modal['can_edit_delete'] = (hasRole('manager') || hasRole('admin')) ? true : false;
    $asset_json = htmlspecialchars(json_encode($asset_for_modal), ENT_QUOTES, 'UTF-8');
    echo '<td class="px-6 py-4 whitespace-nowrap flex gap-2">';
    echo '<button type="button" onclick="showAssetModal(' . $asset_json . ')" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 text-xs font-medium transition" title="View">';
    echo '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-9 0a9 9 0 0118 0a9 9 0 01-18 0z" /></svg>View';
    echo '</button>';
    echo '</td>';
    echo '</tr>';
}
