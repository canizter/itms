<?php
// consumables_types.php - Manage consumable types
require_once 'config/config.php';
require_once 'includes/header.php';

if (!hasRole('manager')) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
$errors = [];
$success = '';

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
    $type = trim($_POST['type'] ?? '');
    if ($type === '') $errors[] = 'Type name is required.';
    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO consumable_types (type) VALUES (?)');
            $stmt->execute([$type]);
            $success = 'Type added.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = 'This type already exists.';
            } else {
                throw $e;
            }
        }
    }
}

$types = $pdo->query('SELECT * FROM consumable_types ORDER BY type ASC')->fetchAll();
$edit_type = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($types as $t) {
        if ($t['id'] == $edit_id) {
            $edit_type = $t;
            break;
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare('DELETE FROM consumable_types WHERE id = ?')->execute([$id]);
    // Redirect before any output
    echo '<script>window.location.href="consumables_types.php";</script>';
    exit;
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_type'])) {
    $id = intval($_POST['edit_id'] ?? 0);
    $type = trim($_POST['edit_type_name'] ?? '');
    if ($type === '') {
        $errors[] = 'Type name is required.';
    } else {
        $stmt = $pdo->prepare('UPDATE consumable_types SET type = ? WHERE id = ?');
        $stmt->execute([$type, $id]);
        $success = 'Type updated.';
        // Redirect before any output
        echo '<script>window.location.href="consumables_types.php";</script>';
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
    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Consumable Types</h2>
    <button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition" onclick="document.getElementById('addTypeModal').classList.remove('hidden')">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
      Add Type
    </button>
  </div>

  <!-- Add/Edit Type Modal -->
  <div id="addTypeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-auto">
      <form method="post">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h5 class="text-lg font-semibold flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            <?php echo $edit_type ? 'Edit Type' : 'Add Type'; ?>
          </h5>
          <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('addTypeModal').classList.add('hidden')">&times;</button>
        </div>
        <div class="px-6 py-4">
          <div class="mb-4">
            <label for="type_name" class="block text-sm font-medium text-gray-700 mb-1">Type Name</label>
            <input type="text" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" id="type_name" name="<?php echo $edit_type ? 'edit_type_name' : 'type'; ?>" value="<?php echo $edit_type ? htmlspecialchars($edit_type['type']) : ''; ?>" required>
            <?php if ($edit_type): ?><input type="hidden" name="edit_id" value="<?php echo $edit_type['id']; ?>"><?php endif; ?>
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
          <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('addTypeModal').classList.add('hidden')">Cancel</button>
          <button type="submit" name="<?php echo $edit_type ? 'edit_type' : 'add_type'; ?>" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold">Save</button>
        </div>
      </form>
    </div>
  </div>

  <div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($types as $t): ?>
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($t['type']); ?></td>
            <td class="px-6 py-4 whitespace-nowrap flex gap-2">
              <a href="consumables_types.php?edit=<?php echo $t['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 text-xs font-medium transition">Edit</a>
              <a href="consumables_types.php?delete=<?php echo $t['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200 text-xs font-medium transition" onclick="return confirm('Delete this type?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
  // Show modal if editing (edit in URL)
  <?php if ($edit_type): ?>
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('addTypeModal').classList.remove('hidden');
      document.getElementById('type_name').value = <?php echo json_encode($edit_type['type']); ?>;
    });
  <?php endif; ?>
</script>

<?php require_once 'includes/footer.php';
