<?php
// add_consumable.php - Add a new consumable
require_once 'config/config.php';

$pdo = getDBConnection();
$type_options = $pdo->query('SELECT * FROM consumable_types ORDER BY type')->fetchAll();
$name = '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_consumable'])) {
    $quantity = intval($_POST['quantity'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $consumable_type = trim($_POST['consumable_type'] ?? '');
    $min_quantity = isset($_POST['min_quantity']) ? intval($_POST['min_quantity']) : 0;
    $max_quantity = isset($_POST['max_quantity']) && $_POST['max_quantity'] !== '' ? intval($_POST['max_quantity']) : null;
    if ($quantity < 0) $errors[] = 'Quantity must be 0 or greater.';
    if ($min_quantity < 0) $errors[] = 'Min quantity must be 0 or greater.';
    if ($max_quantity !== null && $max_quantity < $min_quantity) $errors[] = 'Max quantity must be greater than or equal to min quantity.';
    $type_ids = array_column($type_options, 'id');
    // Name validation
    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($consumable_type === '' || !in_array($consumable_type, $type_ids)) {
        $errors[] = 'Please select a valid consumable type.';
    }
    // Prevent duplicate by name and type
    $dup_stmt = $pdo->prepare('SELECT COUNT(*) FROM consumables WHERE name = ? AND consumable_type = ?');
    $dup_stmt->execute([$name, $consumable_type]);
    if ($dup_stmt->fetchColumn() > 0) {
        $errors[] = 'This consumable name for the selected type already exists.';
    }
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO consumables (name, quantity, consumable_type, min_quantity, max_quantity) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $name,
            $quantity,
            $consumable_type,
            $min_quantity,
            $max_quantity
        ]);
        // Redirect before any output
        header('Location: consumables.php?success=1');
        exit;
    }
}

require_once 'includes/header.php';

if (!hasRole('manager')) {
    header('Location: index.php');
    exit;
}
?>
<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 mt-10">
  <h2 class="text-2xl font-bold tracking-tight text-gray-900 mb-6">Add Consumable</h2>
  <?php foreach ($errors as $error): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endforeach; ?>
<form method="post" class="bg-white shadow rounded-lg p-6 space-y-4" id="addConsumableForm" novalidate>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Consumable Type</label>
      <select name="consumable_type" class="block w-full border border-gray-300 rounded px-3 py-2" required data-vform-required>
        <option value="">Select type...</option>
        <?php foreach ($type_options as $type): ?>
          <option value="<?php echo $type['id']; ?>" <?php if (isset($_POST['consumable_type']) && $_POST['consumable_type'] == $type['id']) echo 'selected'; ?>><?php echo htmlspecialchars($type['type']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
      <input type="text" name="name" class="block w-full border border-gray-300 rounded px-3 py-2" value="<?php echo htmlspecialchars($name ?? ($_POST['name'] ?? '')); ?>" required data-vform-required>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
      <input type="number" name="quantity" class="block w-full border border-gray-300 rounded px-3 py-2" min="0" required data-vform-required>
    </div>
    <div class="flex space-x-4">
      <div class="flex-1">
        <label class="block text-sm font-medium text-gray-700 mb-1">Min Quantity</label>
        <input type="number" name="min_quantity" class="block w-full border border-gray-300 rounded px-3 py-2" min="0" required data-vform-required>
      </div>
      <div class="flex-1">
        <label class="block text-sm font-medium text-gray-700 mb-1">Max Quantity</label>
        <input type="number" name="max_quantity" class="block w-full border border-gray-300 rounded px-3 py-2" min="0" required data-vform-required>
      </div>
    </div>
    <div class="flex justify-end gap-2 pt-4 border-t mt-4">
      <a href="consumables.php" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Cancel</a>
      <button type="submit" name="add_consumable" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold">Save</button>
    </div>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/vform@latest/dist/vform.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addConsumableForm');
    form.addEventListener('submit', function(e) {
      let valid = true;
      form.querySelectorAll('[data-vform-required]').forEach(function(input) {
        if (!input.value || (input.type === 'number' && input.value === '')) {
          input.classList.add('border-red-500');
          valid = false;
        } else {
          input.classList.remove('border-red-500');
        }
      });
      if (!valid) {
        e.preventDefault();
        alert('Please fill in all required fields.');
      }
    });
  });
</script>
<?php require_once 'includes/footer.php'; ?>
