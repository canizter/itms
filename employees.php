<?php
// employees.php - Employee List Management
require_once 'config/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$errors = [];
$success = '';
$name = $department = $email = $position = $employee_id = '';
$edit_id = null;
$editing = false;

// Handle CSV upload
if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $csvFile = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($csvFile, 'r');
    if ($handle !== false) {
        $rowNum = 0;
        $imported = 0;
        $skipped = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            // Skip header row
            if ($rowNum === 1 && isset($row[0]) && stripos($row[0], 'employee_id') !== false) {
                continue;
            }
            // New order: employee_id, name, position, site, email (email optional)
            $emp_id = trim($row[0] ?? '');
            $emp_name = trim($row[1] ?? '');
            $emp_position = trim($row[2] ?? '');
            $emp_dept = trim($row[3] ?? '');
            $emp_email = isset($row[4]) ? trim($row[4]) : '';
            if ($emp_id === '' || $emp_name === '') {
                $skipped++;
                continue;
            }
            // Email is optional, but if present, must be valid
            if ($emp_email !== '' && !filter_var($emp_email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }
            try {
                // Check if employee_id already exists
                $check = $pdo->prepare('SELECT id FROM employees WHERE employee_id = ?');
                $check->execute([$emp_id]);
                if ($rowExists = $check->fetch(PDO::FETCH_ASSOC)) {
                    // Update existing employee
                    $update = $pdo->prepare('UPDATE employees SET name=?, department=?, email=?, position=? WHERE employee_id=?');
                    $update->execute([$emp_name, $emp_dept, $emp_email, $emp_position, $emp_id]);
                } else {
                    // Insert new employee
                    $insert = $pdo->prepare('INSERT INTO employees (employee_id, name, department, email, position) VALUES (?, ?, ?, ?, ?)');
                    $insert->execute([$emp_id, $emp_name, $emp_dept, $emp_email, $emp_position]);
                }
                $imported++;
            } catch (PDOException $e) {
                // If duplicate employee_id, show user-friendly warning
                if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'employee_id') !== false) {
                    $errors[] = 'Employee already exist';
                } else {
                    $skipped++;
                }
            }
        }
        fclose($handle);
        if ($imported > 0) {
            $success = "Imported $imported employees from CSV.";
        }
        if ($skipped > 0) {
            $errors[] = "$skipped rows were skipped due to errors or missing required fields.";
        }
        if ($imported === 0 && $skipped === 0) {
            $errors[] = "No valid employee data found in CSV.";
        }
    } else {
        $errors[] = "Failed to open uploaded CSV file.";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = trim($_POST['employee_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $edit_id = (isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) && (int)$_POST['edit_id'] > 0) ? (int)$_POST['edit_id'] : null;

    if ($employee_id === '' || $name === '') {
        $errors[] = 'Employee ID and Name are required.';
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (!$errors) {
        try {
            if ($edit_id !== null) {
                // Only update if edit_id is a valid integer and exists
                $stmt = $pdo->prepare('UPDATE employees SET employee_id=?, name=?, department=?, email=?, position=? WHERE id=?');
                $stmt->execute([$employee_id, $name, $department, $email, $position, $edit_id]);
                if ($stmt->rowCount() > 0) {
                    $success = 'Employee updated successfully!';
                } else {
                    $success = 'No employee was updated.';
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO employees (employee_id, name, department, email, position) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$employee_id, $name, $department, $email, $position]);
                if ($stmt->rowCount() > 0) {
                    $success = 'Employee added successfully!';
                } else {
                    $success = 'No employee was added.';
                }
            }
            $employee_id = $name = $department = $email = $position = '';
            $edit_id = null;
        } catch (PDOException $e) {
            // Check for duplicate entry error (employee_id must be unique)
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'employee_id') !== false) {
                $errors[] = 'Employee already exist';
            } else {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
// Edit employee (populate form)
if (isset($_GET['edit'])) {
    $editing = true;
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM employees WHERE id=?');
    $stmt->execute([$edit_id]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
if ($emp) {
    $employee_id = $emp['employee_id'];
    $name = $emp['name'];
    $department = $emp['department'];
    $email = $emp['email'];
    $position = $emp['position'];
}
}
// Delete employee
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $pdo->prepare('DELETE FROM employees WHERE id=?')->execute([$del_id]);
    $success = 'Employee deleted successfully!';
}
// Fetch all employees

// Handle search and pagination
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$perPage = 10;
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE employee_id LIKE ? OR name LIKE ? OR position LIKE ? OR department LIKE ? OR email LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
}
$countSql = "SELECT COUNT(*) FROM employees $where";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalEmployees = $stmt->fetchColumn();
$totalPages = max(1, ceil($totalEmployees / $perPage));
$offset = ($page - 1) * $perPage;
$sql = "SELECT * FROM employees $where ORDER BY employee_id ASC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Employee List</h2>
    <div class="flex gap-2">
      <button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition" onclick="document.getElementById('addEmployeeModal').classList.remove('hidden')">
        <!-- Heroicon: plus -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Add Employee
      </button>
      <button class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-semibold transition" onclick="document.getElementById('importEmployeeModal').classList.remove('hidden')">
        <!-- Heroicon: arrow-down-tray -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-8m0 8l-4-4m4 4l4-4m-8 8h8a2 2 0 002-2V6a2 2 0 00-2-2h-8a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        Import
      </button>
    </div>
  </div>
  <?php if ($success): ?>
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
      <!-- Heroicon: check-circle -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0a9 9 0 0118 0z" /></svg>
      <?php echo htmlspecialchars($success); ?>
    </div>
  <?php endif; ?>
  <?php foreach ($errors as $error): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-3 text-sm font-semibold flex items-center gap-2">
      <!-- Heroicon: exclamation -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 17a5 5 0 100-10 5 5 0 000 10z" /></svg>
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endforeach; ?>

  <!-- Add Employee Modal (Tailwind, hidden by default) -->
  <div id="addEmployeeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-auto">
      <form method="post">
        <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($edit_id); ?>">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h5 class="text-lg font-semibold flex items-center gap-2">
            <!-- Heroicon: plus-circle -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            <?php echo $editing ? 'Edit Employee' : 'Add Employee'; ?>
          </h5>
          <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('addEmployeeModal').classList.add('hidden')">&times;</button>
        </div>
        <div class="px-6 py-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
            <input type="text" name="employee_id" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($employee_id); ?>" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" name="name" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($name); ?>" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
            <input type="text" name="department" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($department); ?>" placeholder="Enter site">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($email); ?>">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
            <input type="text" name="position" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($position); ?>">
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
          <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('addEmployeeModal').classList.add('hidden')">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Import Employee Modal (Tailwind, hidden by default) -->
  <div id="importEmployeeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-auto">
      <form method="POST" action="employees.php" enctype="multipart/form-data">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h5 class="text-lg font-semibold flex items-center gap-2">
            <!-- Heroicon: arrow-down-tray -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-8m0 8l-4-4m4 4l4-4m-8 8h8a2 2 0 002-2V6a2 2 0 00-2-2h-8a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            Import Employees from CSV
          </h5>
          <button type="button" class="text-gray-400 hover:text-gray-700 text-2xl font-bold" onclick="document.getElementById('importEmployeeModal').classList.add('hidden')">&times;</button>
        </div>
        <div class="px-6 py-4">
          <div class="mb-4">
            <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
            <input type="file" name="csv_file" id="csv_file" accept=".csv" class="block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" required>
            <small class="text-gray-500">CSV columns: Employee ID, Full Name, Department, Position, Email (optional)</small>
          </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
          <button type="button" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="document.getElementById('importEmployeeModal').classList.add('hidden')">Cancel</button>
          <button type="submit" name="import_csv" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700 font-semibold">Import</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Search bar -->
  <form method="get" class="flex gap-2 mb-4">
    <input type="text" name="search" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 flex-1" placeholder="Search employees..." value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition">Search</button>
    <?php if ($search !== ''): ?>
      <a href="employees.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 font-semibold transition">Clear</a>
    <?php endif; ?>
  </form>

  <div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee ID</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($employees as $emp): ?>
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($emp['employee_id']); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($emp['name']); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($emp['position']); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($emp['department']); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($emp['email']); ?></td>
            <td class="px-6 py-4 whitespace-nowrap flex gap-2">
              <a href="employees.php?edit=<?php echo $emp['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 text-xs font-medium transition">Edit</a>
              <a href="employees.php?delete=<?php echo $emp['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200 text-xs font-medium transition" onclick="return confirm('Delete this employee?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <nav class="flex justify-center mt-6" aria-label="Employee pagination">
      <ul class="inline-flex items-center -space-x-px">
        <!-- Previous button -->
        <li>
          <a class="px-3 py-2 ml-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700 <?php if ($page <= 1) echo 'pointer-events-none opacity-50'; ?>" href="employees.php?page=<?php echo max(1, $page-1); ?><?php if ($search !== '') echo '&search=' . urlencode($search); ?>">Previous</a>
        </li>
        <!-- Current page number -->
        <li>
          <span class="px-4 py-2 leading-tight text-gray-700 bg-gray-200 border border-gray-300">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        </li>
        <!-- Next button -->
        <li>
          <a class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700 <?php if ($page >= $totalPages) echo 'pointer-events-none opacity-50'; ?>" href="employees.php?page=<?php echo min($totalPages, $page+1); ?><?php if ($search !== '') echo '&search=' . urlencode($search); ?>">Next</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
</div>
<script>
  // Show modal if editing (edit_id in POST or GET)
  <?php if ($editing): ?>
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('addEmployeeModal').classList.remove('hidden');
    });
  <?php endif; ?>
</script>
<?php include 'includes/footer.php'; ?>
