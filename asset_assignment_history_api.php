<?php
// asset_assignment_history_api.php - returns assignment history for an asset as JSON
require_once 'config/config.php';
header('Content-Type: application/json');
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
$pdo = getDBConnection();
$asset_id = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;
if (!$asset_id) {
    echo json_encode(['error' => 'Missing asset_id']);
    exit;
}
$sql = 'SELECT aa.*, e.employee_id, e.name as employee_name FROM asset_assignments aa
        LEFT JOIN employees e ON aa.employee_id = e.id
        WHERE aa.asset_id = ? ORDER BY aa.assigned_date DESC, aa.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute([$asset_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['history' => $history]);
