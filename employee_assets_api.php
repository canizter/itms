<?php
// employee_assets_api.php - returns assets assigned to an employee as JSON
require_once 'config/config.php';
header('Content-Type: application/json');
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
$pdo = getDBConnection();
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
if (!$employee_id) {
    echo json_encode(['error' => 'Missing employee_id']);
    exit;
}
$sql = 'SELECT a.asset_tag, a.serial_number, a.status, c.name as category, v.name as vendor, l.name as location
        FROM assets a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN vendors v ON a.vendor_id = v.id
        LEFT JOIN locations l ON a.location_id = l.id
        WHERE a.assigned_to_employee_id = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$employee_id]);
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['assets' => $assets]);
