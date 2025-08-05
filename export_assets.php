<?php
// Export assets as CSV
require_once 'config/config.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=assets_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, [
    'Asset Tag',
    'Category',
    'Vendor',
    'Location',
    'Status',
    'Assigned Employee ID',
    'Assigned Employee Name'
]);

try {
    $pdo = getDBConnection();
    $sql = "
        SELECT a.asset_tag, c.name as category_name, v.name as vendor_name, l.name as location_name,
               a.status, e.employee_id as assigned_employee_id, e.name as assigned_employee_name
        FROM assets a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN vendors v ON a.vendor_id = v.id
        LEFT JOIN locations l ON a.location_id = l.id
        LEFT JOIN employees e ON a.assigned_to_employee_id = e.id
        ORDER BY a.asset_tag ASC
    ";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Map status to display value
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
        $row['status'] = $status_map[$row['status']] ?? $row['status'];
        fputcsv($output, [
            $row['asset_tag'],
            $row['category_name'],
<?php
// ITMS v1.2
require_once 'config/config.php';
            $row['vendor_name'],
            $row['location_name'],
            $row['status'],
            $row['assigned_employee_id'],
            $row['assigned_employee_name']
        ]);
    }
} catch (Exception $e) {
    // Output error as a CSV row
    fputcsv($output, ['Error:', $e->getMessage()]);
}
fclose($output);
exit;
