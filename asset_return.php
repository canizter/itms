<?php
// asset_return.php - Handle asset return action
require_once 'config/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$pdo = getDBConnection();
$asset_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$asset_id) {
    header('Location: assets.php');
    exit;
}
// Get asset info
$stmt = $pdo->prepare('SELECT * FROM assets WHERE id = ?');
$stmt->execute([$asset_id]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$asset || empty($asset['assigned_to_employee_id'])) {
    header('Location: asset_details.php?id=' . $asset_id);
    exit;
}
$employee_id = $asset['assigned_to_employee_id'];
// Mark the asset as returned in asset_assignments (set return_date to today for the latest assignment)

// Mark the asset as returned in asset_assignments (set return_date to today for the latest assignment)
$stmt = $pdo->prepare('UPDATE asset_assignments SET return_date = CURDATE(), notes = CONCAT(notes, " | Returned") WHERE asset_id = ? AND employee_id = ? AND return_date IS NULL ORDER BY assigned_date DESC LIMIT 1');
$stmt->execute([$asset_id, $employee_id]);
// Log the return in asset_history
$log_stmt = $pdo->prepare('INSERT INTO asset_history (asset_id, field_changed, old_value, new_value, action, changed_by) VALUES (?, ?, ?, ?, ?, ?)');
$log_stmt->execute([$asset_id, 'assigned_to_employee_id', $employee_id, '', 'return', $_SESSION['username'] ?? 'system']);
// Unassign the asset and set status to 'Available' (inactive)
$stmt = $pdo->prepare('UPDATE assets SET assigned_to_employee_id = NULL, status = ? WHERE id = ?');
$stmt->execute(['inactive', $asset_id]);
header('Location: asset_details.php?id=' . $asset_id . '&returned=1');
exit;
