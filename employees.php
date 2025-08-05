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
                $skipped++;
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
            $errors[] = 'Database error: ' . $e->getMessage();
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
<div class="container mt-4">
    <!-- Removed duplicate Employee List heading -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Employee List</h2>
        <div>
            <button class="btn btn-primary mr-2" data-toggle="modal" data-target="#addEmployeeModal">+ Add Employee</button>
            <button class="btn btn-success" data-toggle="modal" data-target="#importEmployeeModal">Import</button>
        </div>
    </div>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endforeach; ?>
    <!-- Add Employee Modal and Import Employee Modal are now used instead of inline buttons/forms -->

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="addEmployeeModalLabel">Add Employee</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($edit_id); ?>">
              <div class="form-group">
                  <label>Employee ID</label>
                  <input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($employee_id); ?>" required>
              </div>
              <div class="form-group">
                  <label>Name</label>
                  <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
              </div>
              <div class="form-group">
                  <label>Site</label>
                  <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($department); ?>" placeholder="Enter site">
              </div>
              <div class="form-group">
                  <label>Email</label>
                  <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
              </div>
              <div class="form-group">
                  <label>Position</label>
                  <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($position); ?>">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Import Employee Modal -->
    <div class="modal fade" id="importEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="importEmployeeModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="employees.php" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title" id="importEmployeeModalLabel">Import Employees from CSV</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label for="csv_file">CSV File</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control-file" required>
                <small class="form-text text-muted">CSV columns: Employee ID, Full Name, Department, Position, Email (optional)</small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" name="import_csv" class="btn btn-success">Import</button>
            </div>
          </form>
        </div>
      </div>
    </div>
<!-- Add Bootstrap JS and CSS if not already included -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Search bar (moved below Add Employee) -->
    <form method="get" class="form-inline mb-3" style="display: flex; gap: 0.5rem; align-items: center;">
        <input type="text" name="search" class="form-control" placeholder="Search employees..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search !== ''): ?>
            <a href="employees.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
    <h4>All Employees</h4>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Position</th>
                <th>Site</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $emp): ?>
                <tr>
                    <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                    <td><?php echo htmlspecialchars($emp['name']); ?></td>
                    <td><?php echo htmlspecialchars($emp['position']); ?></td>
                    <td><?php echo htmlspecialchars($emp['department']); ?></td>
                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                    <td>
                        <a href="employees.php?edit=<?php echo $emp['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="employees.php?delete=<?php echo $emp['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this employee?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Employee pagination">
            <ul class="pagination justify-content-center">
                <!-- Previous button -->
                <li class="page-item<?php if ($page <= 1) echo ' disabled'; ?>">
                    <a class="page-link" href="employees.php?page=<?php echo max(1, $page-1); ?><?php if ($search !== '') echo '&search=' . urlencode($search); ?>">Previous</a>
                </li>
                <!-- Current page number -->
                <li class="page-item active">
                    <span class="page-link">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    </span>
                </li>
                <!-- Next button -->
                <li class="page-item<?php if ($page >= $totalPages) echo ' disabled'; ?>">
                    <a class="page-link" href="employees.php?page=<?php echo min($totalPages, $page+1); ?><?php if ($search !== '') echo '&search=' . urlencode($search); ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
