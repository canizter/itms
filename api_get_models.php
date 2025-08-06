<?php
// api_get_models.php - returns models for a given vendor_id as JSON
require_once 'config/config.php';
header('Content-Type: application/json');

$pdo = getDBConnection();
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
if ($vendor_id > 0) {
    $stmt = $pdo->prepare('SELECT id, name FROM models WHERE vendor_id = ? ORDER BY name');
    $stmt->execute([$vendor_id]);
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'models' => $models]);
} else {
    echo json_encode(['success' => false, 'models' => []]);
}
