<?php
// asset_edit.php - Edit Asset
require_once 'config/config.php';
if (!hasRole('manager')) {
    header('Location: assets.php');
    exit;
}
$pdo = getDBConnection();
$asset_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$asset_id) {
    header('Location: assets.php');
    exit;
}
$stmt = $pdo->prepare('SELECT * FROM assets WHERE id = ?');
$stmt->execute([$asset_id]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    header('Location: assets.php');
    exit;
}
$errors = [];
$success = '';
$name = $asset['name'];
$asset_tag = $asset['asset_tag'];
$category_id = $asset['category_id'];
$vendor_id = $asset['vendor_id'];
$location_id = $asset['location_id'];
$status = $asset['status'];
$purchase_date = $asset['purchase_date'];
$serial_number = $asset['serial_number'];
$description = $asset['description'];

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$vendors = $pdo->query('SELECT id, name FROM vendors ORDER BY name')->fetchAll();
$locations = $pdo->query('SELECT id, name FROM locations ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_tag = trim($_POST['asset_tag'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $vendor_id = $_POST['vendor_id'] ?? '';
    $location_id = $_POST['location_id'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $purchase_date = $_POST['purchase_date'] ?? '';
    $serial_number = trim($_POST['serial_number'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($asset_tag === '') $errors[] = 'Asset tag is required.';
    if ($name === '') $errors[] = 'Asset name is required.';
    if (!$category_id) $errors[] = 'Category is required.';
    if (!$vendor_id) $errors[] = 'Vendor is required.';
    if (!$location_id) $errors[] = 'Location is required.';

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE assets SET asset_tag=?, name=?, category_id=?, vendor_id=?, location_id=?, status=?, purchase_date=?, serial_number=?, description=? WHERE id=?');
        $stmt->execute([$asset_tag, $name, $category_id, $vendor_id, $location_id, $status, $purchase_date, $serial_number, $description, $asset_id]);
        header('Location: assets.php?updated=1');
        exit;
    }
}
include 'includes/header.php';
?>
<div class="container mt-4">
    <h2>Edit Asset</h2>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endforeach; ?>
    <form method="post">
        <div class="form-group">
            <label>Asset Tag</label>
            <input type="text" name="asset_tag" class="form-control" value="<?php echo htmlspecialchars($asset_tag); ?>" required>
        </div>
        <div class="form-group">
            <label>Asset Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category_id" class="form-control" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php if ($cat['id'] == $category_id) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Vendor</label>
            <select name="vendor_id" class="form-control" required>
                <option value="">Select Vendor</option>
                <?php foreach ($vendors as $ven): ?>
                    <option value="<?php echo $ven['id']; ?>" <?php if ($ven['id'] == $vendor_id) echo 'selected'; ?>><?php echo htmlspecialchars($ven['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Location</label>
            <select name="location_id" class="form-control" required>
                <option value="">Select Location</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?php echo $loc['id']; ?>" <?php if ($loc['id'] == $location_id) echo 'selected'; ?>><?php echo htmlspecialchars($loc['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="active" <?php if ($status === 'active') echo 'selected'; ?>>Active</option>
                <option value="inactive" <?php if ($status === 'inactive') echo 'selected'; ?>>Inactive</option>
                <option value="maintenance" <?php if ($status === 'maintenance') echo 'selected'; ?>>Maintenance</option>
                <option value="disposed" <?php if ($status === 'disposed') echo 'selected'; ?>>Disposed</option>
            </select>
        </div>
        <div class="form-group">
            <label>Purchase Date</label>
            <input type="date" name="purchase_date" class="form-control" value="<?php echo htmlspecialchars($purchase_date); ?>">
        </div>
        <div class="form-group">
            <label>Serial Number</label>
            <input type="text" name="serial_number" class="form-control" value="<?php echo htmlspecialchars($serial_number); ?>">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
        </div>
        <button type="submit" class="btn btn-success">Update Asset</button>
        <a href="assets.php" class="btn btn-secondary ml-2">Cancel</a>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
