<?php
// history.php - Show all consumable transactions
require_once 'config/config.php';
require_once 'includes/header.php';

if (!hasRole('manager')) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();

// Fetch all transactions with consumable name/type

// Pagination logic
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$perPage = 15;
$countSql = "SELECT COUNT(*) FROM consumable_transactions";
$totalTransactions = $pdo->query($countSql)->fetchColumn();
$totalPages = max(1, ceil($totalTransactions / $perPage));
$offset = ($page - 1) * $perPage;
$sql = "
SELECT ct.*, c.name AS consumable_name, c.consumable_type, t.type AS type_name
FROM consumable_transactions ct
JOIN consumables c ON ct.consumable_id = c.id
LEFT JOIN consumable_types t ON c.consumable_type = t.id
ORDER BY ct.created_at DESC, ct.id DESC
LIMIT $perPage OFFSET $offset
";
$transactions = $pdo->query($sql)->fetchAll();

?>
<div class="max-w-4xl mx-auto mt-10 bg-white shadow rounded-lg p-8">
  <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0a9 9 0 0118 0z" /></svg>
    Consumable Transaction History
  </h2>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consumable</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php if (empty($transactions)): ?>
          <tr><td colspan="6" class="text-center text-gray-400 py-8">No transactions found.</td></tr>
        <?php else: ?>
          <?php foreach ($transactions as $tr): ?>
            <tr>
              <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($tr['created_at'] ?? ''); ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($tr['consumable_name'] ?? ''); ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars(($tr['type_name'] ?? $tr['consumable_type']) ?? ''); ?></td>
              <td class="px-6 py-4 whitespace-nowrap">
                <?php if ($tr['action'] === 'receive'): ?>
                  <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">Received</span>
                <?php elseif ($tr['action'] === 'issue'): ?>
                  <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-purple-100 text-purple-800">Issued</span>
                <?php else: ?>
                  <?php echo htmlspecialchars($tr['action']); ?>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($tr['quantity'] ?? ''); ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($tr['note'] ?? ''); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav class="flex justify-center mt-6" aria-label="History pagination">
        <ul class="inline-flex items-center -space-x-px">
          <!-- Previous button -->
          <li>
            <a class="px-3 py-2 ml-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700 <?php if ($page <= 1) echo 'pointer-events-none opacity-50'; ?>" href="history.php?page=<?php echo max(1, $page-1); ?>">Previous</a>
          </li>
          <!-- Current page number -->
          <li>
            <span class="px-4 py-2 leading-tight text-gray-700 bg-gray-200 border border-gray-300">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
          </li>
          <!-- Next button -->
          <li>
            <a class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700 <?php if ($page >= $totalPages) echo 'pointer-events-none opacity-50'; ?>" href="history.php?page=<?php echo min($totalPages, $page+1); ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
    <div class="mt-6">
      <a href="consumables.php" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold">Back to Consumables</a>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
