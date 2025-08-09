<?php
$page_title = 'Models';
require_once 'config/config.php';
require_once 'includes/header.php';
$pdo = getDBConnection();
$error_message = '';
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_model = null;

// Handle add model
if (isset($_POST['add_model'])) {
    $model_name = trim($_POST['model_name'] ?? '');
    $vendor_id = $_POST['vendor_id'] ?? '';
    $description = trim($_POST['description'] ?? '');
    if ($model_name && $vendor_id) {
        // Check for duplicate model name per vendor
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM models WHERE name = ? AND vendor_id = ?');
        $stmt->execute([$model_name, $vendor_id]);
        if ($stmt->fetchColumn() > 0) {
            $error_message = 'A model with this name already exists for the selected vendor.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO models (name, vendor_id, description) VALUES (?, ?, ?)');
            $stmt->execute([$model_name, $vendor_id, $description]);
            header('Location: models.php?added=1');
            exit;
        }
    } else {
        $error_message = 'Model name and vendor are required.';
    }
}

// Handle edit model
if (isset($_POST['edit_model']) && isset($_POST['model_id'])) {
    $model_name = trim($_POST['model_name'] ?? '');
    $vendor_id = $_POST['vendor_id'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $model_id = intval($_POST['model_id']);
    if ($model_name && $vendor_id) {
        // Check for duplicate model name per vendor, excluding current model
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM models WHERE name = ? AND vendor_id = ? AND id != ?');
        $stmt->execute([$model_name, $vendor_id, $model_id]);
        if ($stmt->fetchColumn() > 0) {
            $edit_id = $model_id;
            $error_message = 'A model with this name already exists for the selected vendor.';
        } else {
            $stmt = $pdo->prepare('UPDATE models SET name=?, vendor_id=?, description=? WHERE id=?');
            $stmt->execute([$model_name, $vendor_id, $description, $model_id]);
            header('Location: models.php?updated=1');
            exit;
        }
    } else {
        $edit_id = $model_id;
        $error_message = 'Model name and vendor are required.';
    }
}

// Handle delete model
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare('DELETE FROM models WHERE id=?');
        $stmt->execute([$delete_id]);
        header('Location: models.php?deleted=1');
        exit;
    } catch (PDOException $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

// If editing, fetch model data for modal
if ($edit_id) {
    $stmt = $pdo->prepare('SELECT * FROM models WHERE id=?');
    $stmt->execute([$edit_id]);
    $edit_model = $stmt->fetch(PDO::FETCH_ASSOC);
}

try {
    // Fetch all models with their associated vendor
    $sql = "
        SELECT m.id, m.name as model_name, m.description, m.created_at, v.name as vendor_name
        FROM models m
        LEFT JOIN vendors v ON m.vendor_id = v.id
        ORDER BY m.name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $models = $stmt->fetchAll();
    // Fetch vendors for add/edit forms
    $vendors = $pdo->query("SELECT id, name FROM vendors ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error loading models: ' . $e->getMessage();
    $models = [];
    $vendors = [];
}
?>
<?php if (!empty($error_message)): ?>
<div class="max-w-2xl mx-auto mt-6 mb-4 px-4 py-3 bg-red-100 text-red-800 rounded shadow text-center">
  <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<?php if (hasRole('manager')): ?>
<!-- Add/Edit Model Modal (Tailwind version, hidden by default) -->
<div id="addModelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-auto">
    <form method="post" action="models.php<?php if ($edit_id) echo '?edit=' . $edit_id; ?>">
      <?php if ($edit_id): ?>
        <input type="hidden" name="model_id" value="<?php echo $edit_id; ?>">
      <?php endif; ?>
      <div class="flex items-center justify-between px-6 py-4 border-b">
  <h5 class="text-base font-semibold flex items-center gap-2">
          <!-- Heroicon: plus-circle -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          <?php echo $edit_id ? 'Edit Model' : 'Add Model'; ?>
        </h5>
  <button type="button" class="text-gray-400 hover:text-gray-700 text-xl font-bold" onclick="document.getElementById('addModelModal').classList.add('hidden')">&times;</button>
      </div>
      <div class="px-6 py-4">
        <?php if ($error_message && (isset($_POST['add_model']) || isset($_POST['edit_model']))): ?>
          <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <div class="mb-4">
          <label for="model_name" class="block text-sm font-medium text-gray-700 mb-1">Model Name</label>
          <input type="text" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" name="model_name" id="model_name" value="<?php echo $edit_model ? htmlspecialchars($edit_model['name']) : (isset($_POST['model_name']) ? htmlspecialchars($_POST['model_name']) : ''); ?>" required>
        </div>
        <div class="mb-4">
          <label for="vendor_id" class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
          <select class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" name="vendor_id" id="vendor_id" required>
            <option value="">Select Vendor</option>
            <?php foreach ($vendors as $vendor): ?>
              <option value="<?php echo $vendor['id']; ?>" <?php if (($edit_model && $edit_model['vendor_id'] == $vendor['id']) || (isset($_POST['vendor_id']) && $_POST['vendor_id'] == $vendor['id'])) echo 'selected'; ?>><?php echo htmlspecialchars($vendor['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-4">
          <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" name="description" id="description" rows="3"><?php echo $edit_model ? htmlspecialchars($edit_model['description']) : (isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''); ?></textarea>
        </div>
      </div>
      <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
        <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('addModelModal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold" name="<?php echo $edit_id ? 'edit_model' : 'add_model'; ?>"><?php echo $edit_id ? 'Update Model' : 'Add Model'; ?></button>
      </div>

    </form>
  </div>
</div>
<?php endif; ?>

<?php if (($error_message && (isset($_POST['add_model']) || isset($_POST['edit_model']))) || $edit_id): ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('addModelModal').classList.remove('hidden');
  });
</script>
<?php endif; ?>


<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
    <div>
  <h2 class="text-2xl font-extrabold tracking-tight text-blue-900 drop-shadow-sm mb-2">Model Management</h2>
  <div class="text-blue-500 text-sm font-medium mb-2">Manage hardware models and their associated vendors</div>
    </div>
    <?php if (hasRole('manager')): ?>
      <button class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 text-white font-semibold shadow-md hover:from-blue-500 hover:to-blue-700 transition" onclick="document.getElementById('addModelModal').classList.remove('hidden')">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Add Model
      </button>
    <?php endif; ?>
  </div>
  <div class="bg-gradient-to-br from-white to-blue-50 shadow-xl rounded-2xl border border-blue-100 overflow-hidden">
    <?php if (empty($models)): ?>
      <div class="text-center text-gray-400 py-12">No models found.</div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-blue-100 bg-white rounded-2xl overflow-hidden">
          <thead class="bg-blue-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
              <?php if (hasRole('manager')): ?><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th><?php endif; ?>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-blue-100">
            <?php foreach ($models as $model): ?>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900"><?php echo htmlspecialchars($model['model_name']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($model['vendor_name']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($model['description']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 text-sm"><?php echo htmlspecialchars($model['created_at']); ?></td>
                <?php if (hasRole('manager')): ?>
                <td class="px-6 py-4 whitespace-nowrap flex gap-2">
                  <a href="models.php?edit=<?php echo $model['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200 text-xs font-medium transition">
                    <!-- Heroicon: pencil -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a4 4 0 01-2.828 1.172H7v-2a4 4 0 011.172-2.828z" /></svg>
                    Edit
                  </a>
                  <a href="models.php?delete=<?php echo $model['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200 text-xs font-medium transition" onclick="return confirm('Delete this model?');">
                    <!-- Heroicon: trash -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    Delete
                  </a>
                </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
