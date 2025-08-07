<?php
// asset_details.php - View Asset Details
require_once 'config/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$pdo = getDBConnection();
$asset_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$asset_id) {
    header('Location: assets.php');
    exit;
}
// Fetch asset with assigned employee info
$stmt = $pdo->prepare('SELECT a.*, c.name as category_name, v.name as vendor_name, l.name as location_name, e.employee_id as assigned_employee_id, e.name as assigned_employee_name FROM assets a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN vendors v ON a.vendor_id = v.id
    LEFT JOIN locations l ON a.location_id = l.id
    LEFT JOIN employees e ON a.assigned_to_employee_id = e.id
    WHERE a.id = ?');
$stmt->execute([$asset_id]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    header('Location: assets.php');
    exit;
}
include 'includes/header.php';
?>
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Asset Details</h2>
    <div class="flex gap-2">
      <a href="assets.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 font-semibold transition">&larr; Back to Assets</a>
      <?php if (hasRole('manager')): ?>
        <a href="asset_edit.php?id=<?php echo $asset_id; ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200 font-semibold transition">Edit</a>
        <?php if (!empty($asset['assigned_to_employee_id'])): ?>
          <a href="asset_return.php?id=<?php echo $asset_id; ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 text-red-800 rounded hover:bg-red-200 font-semibold transition" onclick="return confirm('Mark this asset as returned?');">Return</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b bg-blue-600 rounded-t-lg">
          <h4 class="text-lg font-semibold text-white">Asset: <?php echo htmlspecialchars($asset['asset_tag']); ?></h4>
        </div>
        <div class="px-6 py-4">
          <dl class="divide-y divide-gray-100">
            <div class="flex justify-between py-2">
              <dt class="font-medium text-gray-700">Asset Tag:</dt>
              <dd class="text-gray-900"><?php echo htmlspecialchars($asset['asset_tag']); ?></dd>
            </div>
            <div class="flex justify-between py-2">
              <dt class="font-medium text-gray-700">Category:</dt>
              <dd class="text-gray-900"><?php echo htmlspecialchars($asset['category_name']); ?></dd>
            </div>
            <div class="flex justify-between py-2">
              <dt class="font-medium text-gray-700">Vendor:</dt>
              <dd class="text-gray-900"><?php echo htmlspecialchars($asset['vendor_name'] ?? ''); ?></dd>
            </div>
            <div class="flex justify-between py-2">
              <dt class="font-medium text-gray-700">Location:</dt>
              <dd class="text-gray-900"><?php echo htmlspecialchars($asset['location_name']); ?></dd>
            </div>
            <div class="flex justify-between py-2">
              <dt class="font-medium text-gray-700">Status:</dt>
              <dd>
                <?php 
                  $status = strtolower($asset['status']);
                  $displayStatus = '';
                  $badge_class = 'bg-gray-100 text-gray-800';
                  if ($status === 'active') { $badge_class = 'bg-green-100 text-green-800'; $displayStatus = 'In Use'; }
                  elseif ($status === 'inactive') { $badge_class = 'bg-blue-100 text-blue-800'; $displayStatus = 'Available'; }
                  elseif ($status === 'maintenance') { $badge_class = 'bg-yellow-100 text-yellow-800'; $displayStatus = 'In Repair'; }
                  elseif ($status === 'disposed') { $badge_class = 'bg-red-100 text-red-800'; $displayStatus = 'Retired'; }
                  else { $displayStatus = ucfirst($asset['status']); }
                ?>
                <span class="inline-block px-2 py-1 rounded text-xs font-semibold <?php echo $badge_class; ?>">
                  <?php echo htmlspecialchars($displayStatus); ?>
                </span>
              </dd>
            </div>
            <div class="flex justify-between py-2">
              <dt class="font-medium text-gray-700">Serial Number:</dt>
              <dd class="text-gray-900"><?php echo htmlspecialchars($asset['serial_number']); ?></dd>
            </div>
            <div class="flex justify-between py-2">
              <dt class="font-medium text-gray-700">LAN MAC Address:</dt>
              <dd class="text-gray-900"><?php echo htmlspecialchars($asset['lan_mac']); ?></dd>
            </div>
            <div class="flex justify-between py-2">
              <dt class="font-medium text-gray-700">WLAN MAC Address:</dt>
              <dd class="text-gray-900"><?php echo htmlspecialchars($asset['wlan_mac']); ?></dd>
            </div>
            <div class="flex justify-between py-2">
              <dt class="font-medium text-gray-700">Notes / Remarks:</dt>
              <dd class="text-gray-900"><?php echo htmlspecialchars($asset['notes']); ?></dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
    <div>
      <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b bg-blue-100 rounded-t-lg">
          <h5 class="text-base font-semibold text-blue-800">Assignment</h5>
        </div>
        <div class="px-6 py-4">
          <?php if (!empty($asset['assigned_to_employee_id']) && (!empty($asset['assigned_employee_id']) || !empty($asset['assigned_employee_name']))): ?>
            <div class="mb-2">
              <span class="font-medium text-gray-700">Assigned To:</span><br>
              <span class="text-lg font-semibold text-gray-900">
                <?php
                  $emp_id = $asset['assigned_employee_id'] ?? '';
                  $emp_name = $asset['assigned_employee_name'] ?? '';
                  echo htmlspecialchars(trim($emp_id . ' - ' . $emp_name, ' -'));
                ?>
              </span>
            </div>
            <div class="mb-3">
              <a href="asset_assignments.php?asset_id=<?php echo $asset_id; ?>" class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 text-xs font-medium transition">View Assignment History</a>
            </div>
          <?php else: ?>
            <div class="mb-2 text-gray-500">Currently <strong>Unassigned</strong></div>
            <a href="asset_assignments.php?asset_id=<?php echo $asset_id; ?>" class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 text-xs font-medium transition">View Assignment History</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Serial Number:</div>
                        <div class="col-7"><?php echo htmlspecialchars($asset['serial_number']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">LAN MAC Address:</div>
                        <div class="col-7"><?php echo htmlspecialchars($asset['lan_mac']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">WLAN MAC Address:</div>
                        <div class="col-7"><?php echo htmlspecialchars($asset['wlan_mac']); ?></div>
                    </div>
                    <!-- Notes / Remarks field removed -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Assignment</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($asset['assigned_to_employee_id']) && (!empty($asset['assigned_employee_id']) || !empty($asset['assigned_employee_name']))): ?>
                        <div class="mb-2">
                            <span class="font-weight-bold">Assigned To:</span><br>
                            <span class="h5">
                                <?php
                                    $emp_id = $asset['assigned_employee_id'] ?? '';
                                    $emp_name = $asset['assigned_employee_name'] ?? '';
                                    echo htmlspecialchars(trim($emp_id . ' - ' . $emp_name, ' -'));
                                ?>
                            </span>
                        </div>
                        <div class="mb-3">
                            <a href="asset_assignments.php?asset_id=<?php echo $asset_id; ?>" class="btn btn-outline-primary btn-sm">View Assignment History</a>
                        </div>
                    <?php else: ?>
                        <div class="mb-2 text-muted">Currently <strong>Unassigned</strong></div>
                        <a href="asset_assignments.php?asset_id=<?php echo $asset_id; ?>" class="btn btn-outline-primary btn-sm">View Assignment History</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
