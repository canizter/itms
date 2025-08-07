<?php
// consumables.php - Manage IT consumables (e.g., toner, batteries, cables)
require_once 'config/config.php';
require_once 'includes/header.php';

$success = '';
$errors = [];

if (!hasRole('manager')) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
// Create transaction table if not exists (for demo, production should use migrations)
$pdo->exec("CREATE TABLE IF NOT EXISTS consumable_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consumable_id INT NOT NULL,
    action ENUM('receive','issue') NOT NULL,
    quantity INT NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consumable_id) REFERENCES consumables(id) ON DELETE CASCADE
)");
$type_stmt = $pdo->query('SELECT * FROM consumable_types ORDER BY type');
$type_options = $type_stmt ? $type_stmt->fetchAll() : [];


// Handle add
// Handle receive
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receive_consumable']) && isset($_POST['receive_consumable_id'])) {
    $receive_id = intval($_POST['receive_consumable_id']);
    $receive_qty = intval($_POST['receive_quantity'] ?? 0);
    if ($receive_qty <= 0) {
        $errors[] = 'Received quantity must be greater than 0.';
    } else {
        // Check if consumable exists
        $stmt = $pdo->prepare('SELECT quantity FROM consumables WHERE id = ?');
        $stmt->execute([$receive_id]);
        $row = $stmt->fetch();
        if ($row) {
            $new_qty = $row['quantity'] + $receive_qty;
            $pdo->prepare('UPDATE consumables SET quantity = ? WHERE id = ?')->execute([$new_qty, $receive_id]);
            // Log transaction
            $pdo->prepare('INSERT INTO consumable_transactions (consumable_id, action, quantity) VALUES (?, ?, ?)')->execute([$receive_id, 'receive', $receive_qty]);
            $success = 'Consumable received.';
        } else {
            $errors[] = 'Consumable not found.';
        }
    }
}

// Handle issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_consumable']) && isset($_POST['issue_consumable_id'])) {
    $issue_id = intval($_POST['issue_consumable_id']);
    $issue_qty = intval($_POST['issue_quantity'] ?? 0);
    $issue_note = trim($_POST['issue_note'] ?? '');
    if ($issue_qty <= 0) {
        $errors[] = 'Issued quantity must be greater than 0.';
    } else {
        // Check if consumable exists and has enough quantity
        $stmt = $pdo->prepare('SELECT quantity FROM consumables WHERE id = ?');
        $stmt->execute([$issue_id]);
        $row = $stmt->fetch();
        if ($row) {
            if ($row['quantity'] < $issue_qty) {
                $errors[] = 'Not enough stock to issue the requested quantity.';
            } else {
                $new_qty = $row['quantity'] - $issue_qty;
                $pdo->prepare('UPDATE consumables SET quantity = ? WHERE id = ?')->execute([$new_qty, $issue_id]);
                // Log transaction
                $pdo->prepare('INSERT INTO consumable_transactions (consumable_id, action, quantity, note) VALUES (?, ?, ?, ?)')->execute([$issue_id, 'issue', $issue_qty, $issue_note]);
                $success = 'Consumable issued.';
            }
        } else {
            $errors[] = 'Consumable not found.';
        }
    }
}
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
    if ($consumable_type === '' || !in_array($consumable_type, $type_ids)) {
        $errors[] = 'Please select a valid consumable type.';
    }
    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    // Prevent duplicate by name and type, but only if name is not blank
    if ($name !== '') {
        $dup_stmt = $pdo->prepare('SELECT COUNT(*) FROM consumables WHERE name = ? AND consumable_type = ?');
        $dup_stmt->execute([$name, $consumable_type]);
        if ($dup_stmt->fetchColumn() > 0) {
            $errors[] = 'This consumable name for the selected type already exists.';
        }
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
        $success = 'Consumable added.';
    }
}

// Fetch all consumables
// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_consumable']) && isset($_POST['delete_consumable_id'])) {
    $delete_id = intval($_POST['delete_consumable_id']);
    // Double-check existence before deleting
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM consumables WHERE id = ?');
    $stmt->execute([$delete_id]);
    if ($stmt->fetchColumn() > 0) {
        $pdo->prepare('DELETE FROM consumables WHERE id = ?')->execute([$delete_id]);
        $success = 'Consumable deleted.';
    } else {
        $errors[] = 'Consumable not found.';
    }
}

// Pagination logic (no search)
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$perPage = 10;
$countSql = "SELECT COUNT(*) FROM consumables";
$totalConsumables = $pdo->query($countSql)->fetchColumn();
$totalPages = max(1, ceil($totalConsumables / $perPage));
$offset = ($page - 1) * $perPage;
$sql = "SELECT * FROM consumables ORDER BY name ASC LIMIT $perPage OFFSET $offset";
$consumables = $pdo->query($sql)->fetchAll();
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
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
    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Consumables</h2>
    <div class="flex gap-2">
      <a href="history.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-800 rounded hover:bg-gray-200 font-semibold transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0a9 9 0 0118 0z" /></svg>
        View History
      </a>
      <a href="add_consumable.php" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Add Consumable
      </a>
    </div>
  </div>


  <div class="bg-white shadow rounded-lg overflow-hidden">
    <?php if (empty($consumables)): ?>
      <div class="text-center text-gray-400 py-12">
        <h3 class="text-lg font-semibold mb-2">No consumables found</h3>
        <p class="mb-4">Try adjusting your search criteria or add your first consumable.</p>
        <a href="add_consumable.php" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Add First Consumable
        </a>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min</th> -->
              <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max</th> -->
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($consumables as $c): ?>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                  <?php 
                    $type = null;
                    foreach ($type_options as $t) {
                      if ($t['id'] == $c['consumable_type']) {
                        $type = $t['type'];
                        break;
                      }
                    }
                    echo htmlspecialchars($type ?? $c['consumable_type']);
                  ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($c['name'] ?? ''); ?></td>
                <!-- <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($c['min_quantity']); ?></td> -->
                <!-- <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($c['max_quantity']); ?></td> -->
                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($c['quantity']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <?php 
                    if ($c['quantity'] == 0) {
                      $status = 'Out of stock';
                    } elseif ($c['quantity'] <= $c['min_quantity']) {
                      $status = 'Low Stock';
                    } else {
                      $status = 'Sufficient';
                    }
                    $badge_classes = [
                      'Out of stock' => 'bg-red-100 text-red-800',
                      'Low Stock' => 'bg-yellow-100 text-yellow-800',
                      'Sufficient' => 'bg-green-100 text-green-800',
                    ];
                    $badge_class = $badge_classes[$status] ?? 'bg-gray-100 text-gray-800';
                  ?>
                  <span class="inline-block px-2 py-1 rounded text-xs font-semibold <?php echo $badge_class; ?>">
                    <?php echo $status; ?>
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap flex gap-2">
                  <a href="view_consumable.php?id=<?php echo $c['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 text-xs font-medium transition" title="View">
                    View
                  </a>

                  <button type="button" onclick="showReceiveModal(<?php echo $c['id']; ?>)" class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded hover:bg-green-200 text-xs font-medium transition" title="Receive">
                    Receive
                  </button>
  <!-- Receive Modal -->
  <div id="receiveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-auto">
      <form id="receiveForm" method="post">
        <input type="hidden" name="receive_consumable_id" id="receive_consumable_id">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h5 class="text-lg font-semibold flex items-center gap-2">
            Receive Consumable
          </h5>
          <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('receiveModal').classList.add('hidden')">&times;</button>
        </div>
        <div class="px-6 py-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity to Receive</label>
            <input type="number" name="receive_quantity" id="receive_quantity" class="block w-full border border-gray-300 rounded px-3 py-2" min="1" required>
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
          <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('receiveModal').classList.add('hidden')">Cancel</button>
          <button type="submit" name="receive_consumable" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700 font-semibold">Receive</button>
        </div>
      </form>
    </div>
  </div>
  <script>
  function showReceiveModal(id) {
    document.getElementById('receive_consumable_id').value = id;
    document.getElementById('receive_quantity').value = '';
    document.getElementById('receiveModal').classList.remove('hidden');
  }
  </script>
                  <button type="button" onclick="showIssueModal(<?php echo $c['id']; ?>)" class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-800 rounded hover:bg-purple-200 text-xs font-medium transition" title="Issue">
                    Issue
                  </button>
  <!-- Issue Modal -->
  <div id="issueModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-auto">
      <form id="issueForm" method="post">
        <input type="hidden" name="issue_consumable_id" id="issue_consumable_id">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h5 class="text-lg font-semibold flex items-center gap-2">
            Issue Consumable
          </h5>
          <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('issueModal').classList.add('hidden')">&times;</button>
        </div>
        <div class="px-6 py-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity to Issue</label>
            <input type="number" name="issue_quantity" id="issue_quantity" class="block w-full border border-gray-300 rounded px-3 py-2" min="1" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
            <textarea name="issue_note" id="issue_note" class="block w-full border border-gray-300 rounded px-3 py-2" rows="2" required></textarea>
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
          <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('issueModal').classList.add('hidden')">Cancel</button>
          <button type="submit" name="issue_consumable" class="px-4 py-2 rounded bg-purple-600 text-white hover:bg-purple-700 font-semibold">Issue</button>
        </div>
      </form>
    </div>
  </div>
  <script>
  function showIssueModal(id) {
    document.getElementById('issue_consumable_id').value = id;
    document.getElementById('issue_quantity').value = '';
    document.getElementById('issue_note').value = '';
    document.getElementById('issueModal').classList.remove('hidden');
  }
  </script>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav class="flex justify-center mt-6" aria-label="Consumable pagination">
        <ul class="inline-flex items-center -space-x-px">
          <!-- Previous button -->
          <li>
            <a class="px-3 py-2 ml-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700 <?php if ($page <= 1) echo 'pointer-events-none opacity-50'; ?>" href="consumables.php?page=<?php echo max(1, $page-1); ?>">Previous</a>
          </li>
          <!-- Current page number -->
          <li>
            <span class="px-4 py-2 leading-tight text-gray-700 bg-gray-200 border border-gray-300">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
          </li>
          <!-- Next button -->
          <li>
            <a class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700 <?php if ($page >= $totalPages) echo 'pointer-events-none opacity-50'; ?>" href="consumables.php?page=<?php echo min($totalPages, $page+1); ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
  <!-- Consumable Details Modal -->

</div>
<script>
  // Show modal if needed (future: for edit)
</script>
<?php require_once 'includes/footer.php'; ?>
