<?php
// consumables_models.php - Manage consumables models
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
$vendor_stmt = $pdo->query('SELECT * FROM consumable_vendors ORDER BY vendor');
$vendor_options = $vendor_stmt ? $vendor_stmt->fetchAll() : [];
$errors = [];
$success = '';

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_model'])) {
    $model = trim($_POST['model'] ?? '');
    $vendor_id = trim($_POST['vendor_id'] ?? '');
    $vendor_ids = array_column($vendor_options, 'id');
    if ($model === '') $errors[] = 'Model name is required.';
    if ($vendor_id === '' || !in_array($vendor_id, $vendor_ids)) $errors[] = 'Please select a valid vendor.';
    // Prevent duplicate model name for the same vendor
    if (!$errors) {
        $dup_stmt = $pdo->prepare('SELECT COUNT(*) FROM consumable_models WHERE model = ? AND vendor_id = ?');
        $dup_stmt->execute([$model, $vendor_id]);
        if ($dup_stmt->fetchColumn() > 0) {
            $errors[] = 'This model already exists for the selected vendor.';
        }
    }
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO consumable_models (model, vendor_id) VALUES (?, ?)');
        $stmt->execute([$model, $vendor_id]);
        $success = 'Model added.';
    }
}

$models = $pdo->query('SELECT * FROM consumable_models ORDER BY model')->fetchAll();
$edit_model = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($models as $m) {
        if ($m['id'] == $edit_id) {
            $edit_model = $m;
            break;
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $pdo->prepare('DELETE FROM consumable_models WHERE id = ?')->execute([$del_id]);
    header('Location: consumables_models.php');
    exit;
}

// Handle edit
$edit_model = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare('SELECT * FROM consumable_models WHERE id = ?');
    $stmt->execute([$edit_id]);
    $edit_model = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_model'])) {
    $model = trim($_POST['model'] ?? '');
    $vendor_id = trim($_POST['vendor_id'] ?? '');
    $id = intval($_POST['id'] ?? 0);
    $vendor_ids = array_column($vendor_options, 'id');
    if ($model === '') $errors[] = 'Model name is required.';
    if ($vendor_id === '' || !in_array($vendor_id, $vendor_ids)) $errors[] = 'Please select a valid vendor.';
    // Prevent duplicate model name for the same vendor (excluding self)
    if (!$errors) {
        $dup_stmt = $pdo->prepare('SELECT COUNT(*) FROM consumable_models WHERE model = ? AND vendor_id = ? AND id != ?');
        $dup_stmt->execute([$model, $vendor_id, $id]);
        if ($dup_stmt->fetchColumn() > 0) {
            $errors[] = 'This model already exists for the selected vendor.';
        }
    }
    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE consumable_models SET model = ?, vendor_id = ? WHERE id = ?');
        $stmt->execute([$model, $vendor_id, $id]);
        $success = 'Model updated.';
        header('Location: consumables_models.php');
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
    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Consumable Models</h2>
    <button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition" onclick="document.getElementById('addModelModal').classList.remove('hidden')">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
      Add Model
    </button>
  </div>

  <!-- Add/Edit Model Modal -->
  <div id="addModelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-auto">
      <form method="post">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h5 class="text-lg font-semibold flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            <?php echo $edit_model ? 'Edit Model' : 'Add Model'; ?>
          </h5>
          <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('addModelModal').classList.add('hidden')">&times;</button>
        </div>
        <div class="px-6 py-4">
          <div class="mb-4">
            <label for="model_name" class="block text-sm font-medium text-gray-700 mb-1">Model Name</label>
            <input type="text" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" id="model_name" name="<?php echo $edit_model ? 'model' : 'model'; ?>" value="<?php echo $edit_model ? htmlspecialchars($edit_model['model']) : ''; ?>" required>
            <?php if ($edit_model): ?><input type="hidden" name="id" value="<?php echo $edit_model['id']; ?>"><?php endif; ?>
          </div>
          <div class="mb-4">
            <label for="model_vendor" class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
            <select id="model_vendor" name="vendor_id" class="block w-full border border-gray-300 rounded px-3 py-2" required>
              <option value="">Select vendor...</option>
              <?php foreach ($vendor_options as $vendor): ?>
                <option value="<?php echo $vendor['id']; ?>" <?php if ($edit_model && $edit_model['vendor_id'] == $vendor['id']) echo 'selected'; ?>><?php echo htmlspecialchars($vendor['vendor']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
          <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('addModelModal').classList.add('hidden')">Cancel</button>
          <button type="submit" name="<?php echo $edit_model ? 'update_model' : 'add_model'; ?>" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold">Save</button>
        </div>
      </form>
    </div>
  </div>

  <div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($models as $m): ?>
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($m['model']); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-900">
              <?php 
                $vendor = null;
                foreach ($vendor_options as $v) {
                  if ($v['id'] == $m['vendor_id']) {
                    $vendor = $v['vendor'];
                    break;
                  }
                }
                echo $vendor ? htmlspecialchars($vendor) : '';
              ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap flex gap-2">
              <a href="consumables_models.php?edit=<?php echo $m['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 text-xs font-medium transition">Edit</a>
              <a href="consumables_models.php?delete=<?php echo $m['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200 text-xs font-medium transition" onclick="return confirm('Delete this model?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
  // Show modal if editing (edit in URL)
  <?php if ($edit_model): ?>
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('addModelModal').classList.remove('hidden');
      document.getElementById('model_name').value = <?php echo json_encode($edit_model['model']); ?>;
      document.getElementById('model_vendor').value = <?php echo json_encode($edit_model['vendor_id']); ?>;
    });
  <?php endif; ?>
</script>
<?php require_once 'includes/footer.php'; ?>
