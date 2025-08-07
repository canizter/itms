

<?php
// view_consumable.php - View details for a single consumable
require_once 'config/config.php';

// Handle min/max edit BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_minmax']) && isset($_POST['edit_minmax_id'])) {
    $pdo = getDBConnection();
    $edit_id = intval($_POST['edit_minmax_id']);
    $min_quantity = isset($_POST['edit_min_quantity']) ? intval($_POST['edit_min_quantity']) : 0;
    $max_quantity = isset($_POST['edit_max_quantity']) && $_POST['edit_max_quantity'] !== '' ? intval($_POST['edit_max_quantity']) : null;
    if ($edit_id > 0) {
        $stmt = $pdo->prepare('UPDATE consumables SET min_quantity = ?, max_quantity = ? WHERE id = ?');
        $stmt->execute([$min_quantity, $max_quantity, $edit_id]);
        header('Location: view_consumable.php?id=' . $edit_id);
        exit;
    }
}

require_once 'includes/header.php';

if (!hasRole('manager')) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
$type_stmt = $pdo->query('SELECT * FROM consumable_types ORDER BY type');
$type_options = $type_stmt ? $type_stmt->fetchAll() : [];

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo '<div class="max-w-xl mx-auto mt-10 text-red-600 font-semibold">Invalid consumable ID.</div>';
    require_once 'includes/footer.php';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM consumables WHERE id = ?');
$stmt->execute([$id]);
$consumable = $stmt->fetch();
if (!$consumable) {
    echo '<div class="max-w-xl mx-auto mt-10 text-red-600 font-semibold">Consumable not found.</div>';
    require_once 'includes/footer.php';
    exit;
}

$type = null;
foreach ($type_options as $t) {
    if ($t['id'] == $consumable['consumable_type']) {
        $type = $t['type'];
        break;
    }
}

?>
<div class="max-w-xl mx-auto mt-10 bg-white shadow rounded-lg p-8">
  <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-9 0a9 9 0 0118 0a9 9 0 01-18 0z" /></svg>
    Consumable Details
  </h2>
  <dl class="divide-y divide-gray-100">
    <div class="flex justify-between py-2">
      <dt class="font-medium text-gray-700">Type:</dt>
      <dd class="text-gray-900"><?php echo htmlspecialchars($type ?? $consumable['consumable_type']); ?></dd>
    </div>
    <div class="flex justify-between py-2">
      <dt class="font-medium text-gray-700">Name:</dt>
      <dd class="text-gray-900"><?php echo htmlspecialchars($consumable['name']); ?></dd>
    </div>
    <div class="flex justify-between py-2">
      <dt class="font-medium text-gray-700">Min Quantity:</dt>
      <dd class="text-gray-900"><?php echo htmlspecialchars($consumable['min_quantity']); ?></dd>
    </div>
    <div class="flex justify-between py-2">
      <dt class="font-medium text-gray-700">Max Quantity:</dt>
      <dd class="text-gray-900"><?php echo htmlspecialchars($consumable['max_quantity']); ?></dd>
    </div>
    <div class="flex justify-between py-2">
      <dt class="font-medium text-gray-700">Quantity:</dt>
      <dd class="text-gray-900"><?php echo htmlspecialchars($consumable['quantity']); ?></dd>
    </div>
  </dl>
  <div class="flex gap-2 mt-8">
    <a href="consumables.php" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold">Back</a>
    <button type="button" onclick="showEditMinMaxModal({id: <?php echo $consumable['id']; ?>, min_quantity: <?php echo $consumable['min_quantity']; ?>, max_quantity: <?php echo $consumable['max_quantity'] === null ? 'null' : $consumable['max_quantity']; ?>})" class="px-4 py-2 rounded bg-yellow-100 text-yellow-800 hover:bg-yellow-200 font-semibold">Edit Min/Max</button>
    <form method="post" action="consumables.php" onsubmit="return confirm('Are you sure you want to delete this consumable? This action cannot be undone.');" style="display:inline;">
      <input type="hidden" name="delete_consumable_id" value="<?php echo $consumable['id']; ?>">
      <button type="submit" name="delete_consumable" class="px-4 py-2 rounded bg-red-100 text-red-800 hover:bg-red-200 font-semibold">Delete</button>
    </form>
  </div>
</div>

<!-- Edit Min/Max Modal (copied from consumables.php) -->
<div id="editMinMaxModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-auto">
    <form id="editMinMaxForm" method="post" action="view_consumable.php?id=<?php echo $consumable['id']; ?>">
      <input type="hidden" name="edit_minmax_id" id="edit_minmax_id">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h5 class="text-lg font-semibold flex items-center gap-2">
          Edit Min/Max Quantity
        </h5>
        <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('editMinMaxModal').classList.add('hidden')">&times;</button>
      </div>
      <div class="px-6 py-4 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Min Quantity</label>
          <input type="number" name="edit_min_quantity" id="edit_min_quantity" class="block w-full border border-gray-300 rounded px-3 py-2" min="0" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Max Quantity</label>
          <input type="number" name="edit_max_quantity" id="edit_max_quantity" class="block w-full border border-gray-300 rounded px-3 py-2" min="0">
        </div>
      </div>
      <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
        <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('editMinMaxModal').classList.add('hidden')">Cancel</button>
        <button type="submit" name="save_minmax" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold">Save</button>
      </div>
    </form>
  </div>
</div>
<script>
function showEditMinMaxModal(data) {
  const modal = document.getElementById('editMinMaxModal');
  document.getElementById('edit_minmax_id').value = data.id;
  document.getElementById('edit_min_quantity').value = data.min_quantity;
  document.getElementById('edit_max_quantity').value = data.max_quantity;
  modal.classList.remove('hidden');
}
</script>
<?php require_once 'includes/footer.php'; ?>
