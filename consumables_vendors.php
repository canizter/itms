<?php
// consumables_vendors.php - Manage consumables vendors
require_once 'config/config.php';
require_once 'includes/header.php';

// This file is now obsolete. Vendor/model logic has been removed from the system.
?>
<?php
// This file is now obsolete. Vendor/model logic has been removed from the system.
?>
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
$errors = [];
$success = '';

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vendor'])) {
    $vendor = trim($_POST['vendor'] ?? '');
    if ($vendor === '') $errors[] = 'Vendor name is required.';
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO consumable_vendors (vendor) VALUES (?)');
        $stmt->execute([$vendor]);
        $success = 'Vendor added.';
    }
}

$vendors = $pdo->query('SELECT * FROM consumable_vendors ORDER BY vendor')->fetchAll();
$edit_vendor = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($vendors as $v) {
        if ($v['id'] == $edit_id) {
            $edit_vendor = $v;
            break;
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $pdo->prepare('DELETE FROM consumable_vendors WHERE id = ?')->execute([$del_id]);
    header('Location: consumables_vendors.php');
    exit;
}

// Handle edit
$edit_vendor = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare('SELECT * FROM consumable_vendors WHERE id = ?');
    $stmt->execute([$edit_id]);
    $edit_vendor = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vendor'])) {
    $vendor = trim($_POST['vendor'] ?? '');
    $id = intval($_POST['id'] ?? 0);
    if ($vendor === '') $errors[] = 'Vendor name is required.';
    // Prevent duplicate vendor name (excluding self)
    if (!$errors) {
        $dup_stmt = $pdo->prepare('SELECT COUNT(*) FROM consumable_vendors WHERE vendor = ? AND id != ?');
        $dup_stmt->execute([$vendor, $id]);
        if ($dup_stmt->fetchColumn() > 0) {
            $errors[] = 'This vendor already exists.';
        }
    }
    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE consumable_vendors SET vendor = ? WHERE id = ?');
        $stmt->execute([$vendor, $id]);
        $success = 'Vendor updated.';
        header('Location: consumables_vendors.php');
        exit;
    }
}
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
  <?php if ($success): ?>
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0a9 9 0 0118 0z" /></svg>
      <?php echo htmlspecialchars($success); ?>
    </div>
  <?php endif; ?>
  <?php foreach ($errors as $error): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 17a5 5 0 100-10 5 5 0 000 10z" /></svg>
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endforeach; ?>

  <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Consumable Vendors</h2>
    <button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition" onclick="document.getElementById('addVendorModal').classList.remove('hidden')">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
      Add Vendor
    </button>
  </div>

  <!-- Add/Edit Vendor Modal -->
  <div id="addVendorModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-auto">
      <form method="post">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h5 class="text-lg font-semibold flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            <?php echo $edit_vendor ? 'Edit Vendor' : 'Add Vendor'; ?>
          </h5>
          <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('addVendorModal').classList.add('hidden')">&times;</button>
        </div>
        <div class="px-6 py-4">
          <div class="mb-4">
            <label for="vendor_name" class="block text-sm font-medium text-gray-700 mb-1">Vendor Name</label>
            <input type="text" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" id="vendor_name" name="<?php echo $edit_vendor ? 'edit_vendor_name' : 'vendor'; ?>" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['vendor']) : ''; ?>" required>
            <?php if ($edit_vendor): ?><input type="hidden" name="id" value="<?php echo $edit_vendor['id']; ?>"><?php endif; ?>
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
          <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('addVendorModal').classList.add('hidden')">Cancel</button>
          <button type="submit" name="<?php echo $edit_vendor ? 'update_vendor' : 'add_vendor'; ?>" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold">Save</button>
        </div>
      </form>
    </div>
  </div>

  <div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($vendors as $v): ?>
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($v['vendor']); ?></td>
            <td class="px-6 py-4 whitespace-nowrap flex gap-2">
              <a href="consumables_vendors.php?edit=<?php echo $v['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 text-xs font-medium transition">Edit</a>
              <a href="consumables_vendors.php?delete=<?php echo $v['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200 text-xs font-medium transition" onclick="return confirm('Delete this vendor?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
  // Show modal if editing (edit in URL)
  <?php if ($edit_vendor): ?>
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('addVendorModal').classList.remove('hidden');
      document.getElementById('vendor_name').value = <?php echo json_encode($edit_vendor['vendor']); ?>;
    });
  <?php endif; ?>
</script>
<?php require_once 'includes/footer.php'; ?>
