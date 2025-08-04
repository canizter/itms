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
$employees = $pdo->query('SELECT * FROM employees ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
include 'includes/header.php';
?>
<div class="container mt-4">
    <h2>Employee List</h2>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endforeach; ?>
    <form method="post" class="mb-4">
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
            <label>Department</label>
            <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($department); ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div class="form-group">
            <label>Position</label>
            <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($position); ?>">
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Update' : 'Add'; ?> Employee</button>
        <?php if ($editing): ?>
            <a href="employees.php" class="btn btn-secondary ml-2">Cancel</a>
        <?php endif; ?>
    </form>
    <h4>All Employees</h4>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Email</th>
                <th>Position</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $emp): ?>
                <tr>
                    <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                    <td><?php echo htmlspecialchars($emp['name']); ?></td>
                    <td><?php echo htmlspecialchars($emp['department']); ?></td>
                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                    <td><?php echo htmlspecialchars($emp['position']); ?></td>
                    <td>
                        <a href="employees.php?edit=<?php echo $emp['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="employees.php?delete=<?php echo $emp['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this employee?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
