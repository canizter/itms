<?php
// ITMS v1.2
require_once 'config/config.php';
require_once 'includes/header.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if (($handle = fopen($file, 'r')) !== false) {
        $pdo = getDBConnection();
        $header = fgetcsv($handle);
// Only allow: Asset Tag, Category, Vendor, Location, Status, Assigned Employee ID
// Only allow: Asset Tag, Category, Vendor, Location, Status, Serial Number, Note / Remarks, Assigned Employee ID
$expected = ['Asset Tag', 'Category', 'Vendor', 'Location', 'Status', 'Serial Number', 'Note / Remarks', 'Assigned Employee ID'];
if ($header && array_map('strtolower', $header) === array_map('strtolower', $expected)) {
    $rowCount = 0;
    $rowNum = 1;
    $errors = [];
    while (($row = fgetcsv($handle)) !== false) {
        $rowNum++;
        list($asset_tag, $category, $vendor, $location, $status, $serial_number, $description, $employee_id) = $row;
        // Check category exists
        $cat_id = null;
        if ($category) {
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ?');
            $stmt->execute([$category]);
            $cat_id = $stmt->fetchColumn();
            if (!$cat_id) {
                $errors[] = "Row $rowNum: Category '$category' does not exist.";
                continue;
            }
        }
        // Check vendor exists
        $ven_id = null;
        if ($vendor) {
            $stmt = $pdo->prepare('SELECT id FROM vendors WHERE name = ?');
            $stmt->execute([$vendor]);
            $ven_id = $stmt->fetchColumn();
            if (!$ven_id) {
                $errors[] = "Row $rowNum: Vendor '$vendor' does not exist.";
                continue;
            }
        }
        // Check location exists
        $loc_id = null;
        if ($location) {
            $stmt = $pdo->prepare('SELECT id FROM locations WHERE name = ?');
            $stmt->execute([$location]);
            $loc_id = $stmt->fetchColumn();
            if (!$loc_id) {
                $errors[] = "Row $rowNum: Location '$location' does not exist.";
                continue;
            }
        }
        // Find employee by ID only
        $emp_id = null;
        if ($employee_id) {
            $stmt = $pdo->prepare('SELECT id FROM employees WHERE employee_id = ?');
            $stmt->execute([$employee_id]);
            $emp_id = $stmt->fetchColumn();
        }
        // Map status from display value to ENUM value
        $status_enum_map = [
            'in use' => 'active',
            'available' => 'inactive',
            'in repair' => 'maintenance',
            'retired' => 'disposed',
        ];
        $status_key = strtolower(trim($status));
        $status = $status_enum_map[$status_key] ?? 'inactive'; // Default to 'inactive' (Available)
                // Insert or update asset (upsert by asset_tag)
                $stmt = $pdo->prepare('SELECT id FROM assets WHERE asset_tag = ?');
                $stmt->execute([$asset_tag]);
                $asset_id = $stmt->fetchColumn();
        if ($asset_id) {
            // Update all relevant fields except asset_tag
            $pdo->prepare('UPDATE assets SET category_id=?, vendor_id=?, location_id=?, status=?, serial_number=?, description=?, assigned_to_employee_id=? WHERE id=?')
                ->execute([$cat_id, $ven_id, $loc_id, $status, $serial_number, $description, $emp_id, $asset_id]);
        } else {
            $pdo->prepare('INSERT INTO assets (asset_tag, category_id, vendor_id, location_id, status, serial_number, description, assigned_to_employee_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$asset_tag, $cat_id, $ven_id, $loc_id, $status, $serial_number, $description, $emp_id]);
        }
        $rowCount++;
    }
    if (empty($errors)) {
        $success = true;
    } else {
        $error = implode('<br>', $errors);
    }
} else {
    $error = 'Invalid CSV header. Please use the provided template.';
}
        fclose($handle);
    } else {
        $error = 'Failed to open uploaded file.';
    }
}
?>
<div class="container" style="max-width: 600px; margin: 2rem auto;">
    <h2>Import Assets from CSV</h2>
    <?php if ($success): ?>
        <div class="alert alert-success">Assets imported successfully!</div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="csv_file">CSV File</label>
            <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
            <small>Download template: <a href="assets_import_template.csv" download>assets_import_template.csv</a></small><br>
            <strong>Required columns:</strong> Asset Tag, Category, Vendor, Location, Status, Serial Number<br>
            <strong>Optional columns:</strong> Note / Remarks, Assigned Employee ID<br>
        </div>
        <button type="submit" class="btn btn-primary">Import</button>
        <a href="assets.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?>
