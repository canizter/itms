
<?php
require_once 'config/config.php';

require_once 'includes/header.php';
if (!isAdmin()) { header('Location: index.php'); exit; }
$pdo = getDBConnection();

$errors = [];
$success = '';
$name = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $errors[] = 'Category name is required.';
    }
    // Check uniqueness
    if (empty($errors)) {
        if (isset($_POST['id']) && $_POST['id']) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?');
            $stmt->execute([$name, $_POST['id']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Category already exist';
            }
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = ?');
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Category already exist';
            }
        }
    }
    if (empty($errors)) {
        if (isset($_POST['id']) && $_POST['id']) {
            $stmt = $pdo->prepare('UPDATE categories SET name=? WHERE id=?');
            $stmt->execute([$name, $_POST['id']]);
            $success = 'Category updated.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
            $stmt->execute([$name]);
            $success = 'Category added.';
        }
        $name = '';
        $id = 0;
    }
}
if (isset($_GET['delete'])) {
    // Check if category is assigned to any asset
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE category_id=?');
    $stmt->execute([$_GET['delete']]);
    $in_use = $stmt->fetchColumn();
    if ($in_use > 0) {
        $delete_error = 'Cannot delete: This category is assigned to one or more assets.';
    } else {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id=?');
        $stmt->execute([$_GET['delete']]);
        $success = 'Category deleted.';
    }
}
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $name = $row['name'];
}
$rows = $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
?>


<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
  <?php if ($success): ?>
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
      <!-- Heroicon: check-circle -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0a9 9 0 0118 0z" /></svg>
      <?=htmlspecialchars($success)?></div>
  <?php endif; ?>
  <?php foreach ($errors as $e): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
      <!-- Heroicon: exclamation -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 17a5 5 0 100-10 5 5 0 000 10z" /></svg>
      <?=htmlspecialchars($e)?></div>
  <?php endforeach; ?>

  <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Categories</h2>
    <button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition" onclick="document.getElementById('addCategoryModal').classList.remove('hidden')">
      <!-- Heroicon: plus -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
      Add Category
    </button>
  </div>

  <!-- Add Category Modal (Tailwind, hidden by default) -->
  <div id="addCategoryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-auto">
      <form method="post">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h5 class="text-lg font-semibold flex items-center gap-2">
            <!-- Heroicon: plus-circle -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Add Category
          </h5>
          <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('addCategoryModal').classList.add('hidden')">&times;</button>
        </div>
        <div class="px-6 py-4">
          <div class="mb-4">
            <label for="category_name" class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
            <input type="text" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" id="category_name" name="name" required>
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
          <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('addCategoryModal').classList.add('hidden')">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold">Save</button>
        </div>
      </form>
    </div>
  </div>

  <?php if (!empty($delete_error)): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
      <!-- Heroicon: exclamation -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 17a5 5 0 100-10 5 5 0 000 10z" /></svg>
      <?php echo htmlspecialchars($delete_error); ?>
    </div>
  <?php endif; ?>

  <div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?=htmlspecialchars($r['name'])?></td>
            <td class="px-6 py-4 whitespace-nowrap flex gap-2">
              <a href="categories.php?id=<?=$r['id']?>" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 text-xs font-medium transition">Edit</a>
              <a href="categories.php?delete=<?=$r['id']?>" class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200 text-xs font-medium transition" onclick="return confirm('Delete this category?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
  // Show modal if editing (id in URL)
  <?php if ($id): ?>
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('addCategoryModal').classList.remove('hidden');
      document.getElementById('category_name').value = <?php echo json_encode($name); ?>;
    });
  <?php endif; ?>
</script>
<?php require_once 'includes/footer.php'; ?>
