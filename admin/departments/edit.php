<?php
include '../../config.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: manage.php");
    exit();
}

$dept_id = sanitize($_GET['id']);
$error = '';
$success = '';

// Get department details
$sql = "SELECT * FROM departments WHERE id = ? AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dept_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage.php");
    exit();
}

$department = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $manager_id = sanitize($_POST['manager_id']);
    
    // Check if department name already exists (excluding current department)
    $check_sql = "SELECT id FROM departments WHERE name = ? AND id != ? AND status = 'active'";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("si", $name, $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Department name already exists!";
    } else {
        $sql = "UPDATE departments SET name = ?, description = ?, manager_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $name, $description, $manager_id, $dept_id);
        
        if ($stmt->execute()) {
            $success = "Department updated successfully!";
            // Refresh department data
            $sql = "SELECT * FROM departments WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $dept_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $department = $result->fetch_assoc();
        } else {
            $error = "Error updating department: " . $conn->error;
        }
    }
}

// Get managers for dropdown
$managers_sql = "SELECT id, first_name, last_name, position FROM users WHERE role = 'employee' AND status = 'active'";
$managers_result = $conn->query($managers_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Department - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Edit Department</h1>
            <div class="breadcrumb">Admin / Departments / Edit</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Edit Department Information</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name" class="form-label">Department Name *</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($department['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="4"><?php echo htmlspecialchars($department['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="manager_id" class="form-label">Department Manager</label>
                        <select id="manager_id" name="manager_id" class="form-control">
                            <option value="">Select Manager</option>
                            <?php while ($manager = $managers_result->fetch_assoc()): ?>
                                <option value="<?php echo $manager['id']; ?>" 
                                    <?php echo ($department['manager_id'] == $manager['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name'] . ' - ' . $manager['position']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Department</button>
                        <a href="manage.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Department Employees</h2>
            </div>
            <div class="card-body">
                <?php
                $employees_sql = "SELECT id, first_name, last_name, position, email 
                                 FROM users 
                                 WHERE department_id = ? AND status = 'active' 
                                 ORDER BY first_name, last_name";
                $stmt = $conn->prepare($employees_sql);
                $stmt->bind_param("i", $dept_id);
                $stmt->execute();
                $employees_result = $stmt->get_result();
                ?>
                
                <?php if ($employees_result->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($employee = $employees_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <td>
                                        <a href="../employees/edit.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No employees in this department.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/forms.js"></script>
</body>
</html>