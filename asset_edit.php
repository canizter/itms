<?php
// asset_edit.php - Edit Asset
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
$stmt = $pdo->prepare('SELECT * FROM assets WHERE id = ?');
$stmt->execute([$asset_id]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    header('Location: assets.php');
    exit;
}
// Initialize all variables used in the form
// Model support
$errors = [];
$asset_tag = $asset['asset_tag'];
$category_id = $asset['category_id'];
$vendor_id = $asset['vendor_id'];
$model_id = $asset['model_id'] ?? '';
$location_id = $asset['location_id'] ?? '';
$status = $asset['status'] ?? 'active';
$serial_number = $asset['serial_number'] ?? '';
$lan_mac = $asset['lan_mac'] ?? '';
$wlan_mac = $asset['wlan_mac'] ?? '';
$assigned_to_employee_id = $asset['assigned_to_employee_id'] ?? '';
$asset_note = $asset['notes'] ?? '';
// Fetch select options if not already present
if (!isset($categories)) {
    $categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
}
if (!isset($vendors)) {
    $vendors = $pdo->query('SELECT id, name FROM vendors ORDER BY name')->fetchAll();
}
if (!isset($employees)) {
    $employees = $pdo->query('SELECT id, employee_id, name FROM employees ORDER BY name')->fetchAll();
}
if (!isset($locations)) {
    $locations = $pdo->query('SELECT id, name FROM locations ORDER BY name')->fetchAll();
}

// Handle POST (update asset)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_tag = trim($_POST['asset_tag'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $vendor_id = (int)($_POST['vendor_id'] ?? 0);
    $model_id = $_POST['model_id'] ?? '';
    $location_id = (int)($_POST['location_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $serial_number = trim($_POST['serial_number'] ?? '');
    $lan_mac = trim($_POST['lan_mac'] ?? '');
    $wlan_mac = trim($_POST['wlan_mac'] ?? '');
    $new_assigned_to_employee_id = $_POST['assigned_to_employee_id'] !== '' ? (int)$_POST['assigned_to_employee_id'] : null;
    $asset_note = trim($_POST['asset_note'] ?? '');
    // Check if models exist for this vendor
    $model_count = 0;
    if ($vendor_id) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM models WHERE vendor_id = ?');
        $stmt->execute([$vendor_id]);
        $model_count = (int)$stmt->fetchColumn();
    }
    if ($model_count > 0 && !$model_id) {
        $errors[] = 'Model is required for the selected vendor.';
    }

    // Validation
    if ($asset_tag === '') {
        $errors[] = 'Asset tag is required.';
    }
    if (!$category_id) {
        $errors[] = 'Category is required.';
    }
    if (!$vendor_id) {
        $errors[] = 'Vendor is required.';
    }
    if (!$location_id) {
        $errors[] = 'Location is required.';
    }
    if ($serial_number === '') {
        $errors[] = 'Serial number is required.';
    }
    // Optionally, add more validation as needed

    // Assignment logic: Only allow assignment if asset is not currently assigned or is being returned
    $current_assigned_to_employee_id = $asset['assigned_to_employee_id'] ?? null;
    $assignment_changed = $new_assigned_to_employee_id != $current_assigned_to_employee_id;

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            // Update asset
            $update_stmt = $pdo->prepare('UPDATE assets SET asset_tag=?, category_id=?, vendor_id=?, model_id=?, location_id=?, status=?, serial_number=?, lan_mac=?, wlan_mac=?, assigned_to_employee_id=?, notes=? WHERE id=?');
            $update_stmt->execute([
                $asset_tag,
                $category_id,
                $vendor_id,
                $model_id ?: null,
                $location_id,
                $status,
                $serial_number,
                $lan_mac,
                $wlan_mac,
                $new_assigned_to_employee_id,
                $asset_note,
                $asset_id
            ]);

            // If assignment changed, insert into assignment history
            if ($assignment_changed) {
                $now = date('Y-m-d');
                $assign_stmt = $pdo->prepare('INSERT INTO asset_assignments (asset_id, employee_id, assigned_by, assigned_date, notes) VALUES (?, ?, ?, ?, ?)');
                if ($new_assigned_to_employee_id) {
                    $assign_stmt->execute([$asset_id, $new_assigned_to_employee_id, $_SESSION['username'] ?? 'system', $now, 'Re-assigned via edit']);
                }
                // Optionally, mark previous assignment as returned (if needed)
                if ($current_assigned_to_employee_id && !$new_assigned_to_employee_id) {
                    $return_stmt = $pdo->prepare('UPDATE asset_assignments SET returned_at=? WHERE asset_id=? AND employee_id=? AND returned_at IS NULL');
                    $return_stmt->execute([$now, $asset_id, $current_assigned_to_employee_id]);
                }
            }

            $pdo->commit();
            header('Location: assets.php?msg=updated');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to update asset: ' . $e->getMessage();
        }
    }
    // If errors, the form will re-render with $errors and posted values
}
?>
<?php include 'includes/header.php'; ?>
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
  <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Asset</h2>
  <?php foreach ($errors as $error): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
      <!-- Heroicon: exclamation -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 17a5 5 0 100-10 5 5 0 000 10z" /></svg>
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endforeach; ?>
  <form method="post" class="space-y-5 bg-white shadow rounded-lg p-8">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Employee</label>
      <?php if (!empty($asset['assigned_to_employee_id'])): ?>
        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded mb-2 text-sm">This asset is currently assigned. Please return it before assigning to another employee.</div>
        <input type="text" class="block w-full border border-gray-300 rounded px-3 py-2 bg-gray-100" value="<?php 
          foreach ($employees as $emp) {
            if ($emp['id'] == $assigned_to_employee_id) {
              echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['name']);
              break;
            }
          }
        ?>" disabled>
        <input type="hidden" name="assigned_to_employee_id" value="<?php echo htmlspecialchars($assigned_to_employee_id); ?>">
      <?php else: ?>
        <input type="text" id="employeeSearch" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type to search employee..." autocomplete="off">
        <input type="hidden" name="assigned_to_employee_id" id="assigned_to_employee_id" value="<?php echo htmlspecialchars($assigned_to_employee_id ?? ''); ?>">
        <div id="employeeSearchResults" class="absolute z-50 bg-white border border-gray-300 rounded shadow mt-1 w-full hidden"></div>
        <script>
          const employees = <?php echo json_encode($employees); ?>;
          const searchInput = document.getElementById('employeeSearch');
          const resultsDiv = document.getElementById('employeeSearchResults');
          const hiddenInput = document.getElementById('assigned_to_employee_id');
          function renderResults(filtered) {
            if (!filtered.length) {
              resultsDiv.innerHTML = '<div class="px-4 py-2 text-gray-500">No results found</div>';
              resultsDiv.classList.remove('hidden');
              return;
            }
            resultsDiv.innerHTML = filtered.map(emp =>
              `<div class='px-4 py-2 hover:bg-blue-100 cursor-pointer' data-id='${emp.id}' data-name='${emp.employee_id} - ${emp.name}'>${emp.employee_id} - ${emp.name}</div>`
            ).join('');
            resultsDiv.classList.remove('hidden');
          }
          searchInput.addEventListener('input', function() {
            const val = this.value.trim().toLowerCase();
            if (!val) {
              resultsDiv.classList.add('hidden');
              hiddenInput.value = '';
              return;
            }
            const filtered = employees.filter(emp =>
              emp.employee_id.toLowerCase().includes(val) || emp.name.toLowerCase().includes(val)
            );
            renderResults(filtered);
          });
        resultsDiv.addEventListener('mousedown', function(e) {
            if (e.target && e.target.dataset.id) {
              searchInput.value = e.target.dataset.name;
              hiddenInput.value = e.target.dataset.id;
              resultsDiv.classList.add('hidden');
              // Auto-set status to 'active' (In Use) when employee is selected
              var statusSelect = document.querySelector('select[name="status"]');
              if (statusSelect) {
                statusSelect.value = 'active';
              }
            }
          });
        document.addEventListener('mousedown', function(e) {
            if (!resultsDiv.contains(e.target) && e.target !== searchInput) {
              resultsDiv.classList.add('hidden');
            }
          });
        // If employee is cleared, set status to 'inactive' (Available)
        searchInput.addEventListener('input', function() {
          if (!this.value.trim()) {
            hiddenInput.value = '';
            var statusSelect = document.querySelector('select[name="status"]');
            if (statusSelect) {
              statusSelect.value = 'inactive';
            }
          }
        });
          // Pre-fill if editing
          <?php if (isset($assigned_to_employee_id) && $assigned_to_employee_id): ?>
          (function() {
            const emp = employees.find(e => e.id == <?php echo json_encode($assigned_to_employee_id); ?>);
            if (emp) {
              searchInput.value = emp.employee_id + ' - ' + emp.name;
              hiddenInput.value = emp.id;
            }
          })();
          <?php endif; ?>
        </script>
      <?php endif; ?>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Asset Tag</label>
      <input type="text" name="asset_tag" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($asset_tag); ?>" required>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
      <select name="category_id" class="block w-full border border-gray-300 rounded px-3 py-2" required>
        <option value="">Select Category</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo $cat['id']; ?>" <?php if ($cat['id'] == $category_id) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
      <select name="vendor_id" id="edit_vendor_id" class="block w-full border border-gray-300 rounded px-3 py-2" required>
        <option value="">Select Vendor</option>
        <?php foreach ($vendors as $ven): ?>
          <option value="<?php echo $ven['id']; ?>" <?php if ($ven['id'] == $vendor_id) echo 'selected'; ?>><?php echo htmlspecialchars($ven['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
      <select name="model_id" id="edit_model_id" class="block w-full border border-gray-300 rounded px-3 py-2">
        <option value="">Select a vendor first</option>
      </select>
    </div>
<script>
// Dynamically load models for selected vendor in Edit Asset page
document.addEventListener('DOMContentLoaded', function() {
  const vendorSelect = document.getElementById('edit_vendor_id');
  const modelSelect = document.getElementById('edit_model_id');
  function updateModels(vendorId, selectedModelId = '') {
    if (!vendorId) {
      modelSelect.innerHTML = '<option value="">Select a vendor first</option>';
      return;
    }
    fetch('api_get_models.php?vendor_id=' + encodeURIComponent(vendorId))
      .then(r => r.json())
      .then(data => {
        if (data.success && data.models.length > 0) {
          let opts = '<option value="">Select Model</option>';
          data.models.forEach(function(model) {
            opts += `<option value="${model.id}"${model.id == selectedModelId ? ' selected' : ''}>${model.name}</option>`;
          });
          modelSelect.innerHTML = opts;
        } else {
          modelSelect.innerHTML = '<option value="">N/A</option>';
        }
      });
  }
  if (vendorSelect) {
    vendorSelect.addEventListener('change', function() {
      updateModels(this.value);
    });
    // On page load, if vendor is preselected, load models and select the current model
    if (vendorSelect.value) {
      updateModels(vendorSelect.value, <?php echo json_encode($model_id); ?>);
    }
  }
});
</script>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
      <select name="location_id" class="block w-full border border-gray-300 rounded px-3 py-2" required>
        <option value="">Select Location</option>
        <?php foreach ($locations as $loc): ?>
          <option value="<?php echo $loc['id']; ?>" <?php if ($loc['id'] == $location_id) echo 'selected'; ?>><?php echo htmlspecialchars($loc['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
      <select name="status" class="block w-full border border-gray-300 rounded px-3 py-2">
        <option value="active" <?php if ($status === 'active') echo 'selected'; ?>>In Use</option>
        <option value="inactive" <?php if ($status === 'inactive') echo 'selected'; ?>>Available</option>
        <option value="maintenance" <?php if ($status === 'maintenance') echo 'selected'; ?>>In Repair</option>
        <option value="disposed" <?php if ($status === 'disposed') echo 'selected'; ?>>Retired</option>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
      <input type="text" name="serial_number" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($serial_number); ?>" required>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">LAN MAC Address</label>
      <input type="text" name="lan_mac" class="block w-full border border-gray-300 rounded px-3 py-2" value="<?php echo htmlspecialchars($lan_mac); ?>" placeholder="00:00:00:00:00:00">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">WLAN MAC Address</label>
      <input type="text" name="wlan_mac" class="block w-full border border-gray-300 rounded px-3 py-2" value="<?php echo htmlspecialchars($wlan_mac); ?>" placeholder="00:00:00:00:00:00">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Note / Remarks</label>
      <textarea name="asset_note" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2" placeholder="Optional note or remarks about this asset."><?php echo htmlspecialchars($asset_note); ?></textarea>
    </div>
    <div class="flex flex-wrap gap-2 mt-6">
      <button type="submit" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700 font-semibold transition">Update Asset</button>
      <a href="assets.php" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold transition">Cancel</a>
      <a href="asset_assignments.php?asset_id=<?php echo $asset_id; ?>" class="px-4 py-2 rounded bg-blue-100 text-blue-800 hover:bg-blue-200 font-semibold transition">View Assignment History</a>
      <?php if (!empty($asset['assigned_to_employee_id'])): ?>
        <a href="asset_return.php?id=<?php echo $asset_id; ?>" class="px-4 py-2 rounded bg-red-100 text-red-800 hover:bg-red-200 font-semibold transition" onclick="return confirm('Mark this asset as returned?');">Return</a>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
