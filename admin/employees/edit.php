<?php
include '../../config.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: manage.php");
    exit();
}

$employee_id = sanitize($_GET['id']);
$error = '';
$success = '';

// Get employee details
$sql = "SELECT * FROM users WHERE id = ? AND role = 'employee'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage.php");
    exit();
}

$employee = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $department_id = sanitize($_POST['department_id']);
    $position = sanitize($_POST['position']);
    $phone = sanitize($_POST['phone']);
    $status = sanitize($_POST['status']);
    
    // Check if email already exists (excluding current employee)
    $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("si", $email, $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Email already exists!";
    } else {
        $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, department_id = ?, position = ?, phone = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssisssi", $first_name, $last_name, $email, $department_id, $position, $phone, $status, $employee_id);
        
        if ($stmt->execute()) {
            $success = "Employee updated successfully!";
            // Refresh employee data
            $sql = "SELECT * FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $employee = $result->fetch_assoc();
        } else {
            $error = "Error updating employee: " . $conn->error;
        }
    }
}

// Get departments for dropdown
$dept_sql = "SELECT * FROM departments WHERE status = 'active'";
$dept_result = $conn->query($dept_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - Admin</title>
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
            <h1 class="page-title">Edit Employee</h1>
            <div class="breadcrumb">Admin / Employees / Edit</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Edit Employee Information</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="department_id" class="form-label">Department</label>
                            <select id="department_id" name="department_id" class="form-control">
                                <option value="">Select Department</option>
                                <?php while ($dept = $dept_result->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo ($employee['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" id="position" name="position" class="form-control" 
                                   value="<?php echo htmlspecialchars($employee['position'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="active" <?php echo $employee['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $employee['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Employee</button>
                        <a href="manage.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Employee Statistics</h2>
            </div>
            <div class="card-body">
                <?php
                // Get employee task statistics
                $tasks_sql = "SELECT COUNT(*) as total_tasks FROM tasks WHERE assigned_to = ?";
                $stmt = $conn->prepare($tasks_sql);
                $stmt->bind_param("i", $employee_id);
                $stmt->execute();
                $tasks_result = $stmt->get_result();
                $total_tasks = $tasks_result->fetch_assoc()['total_tasks'];
                
                $completed_sql = "SELECT COUNT(*) as completed_tasks FROM tasks WHERE assigned_to = ? AND status = 'completed'";
                $stmt = $conn->prepare($completed_sql);
                $stmt->bind_param("i", $employee_id);
                $stmt->execute();
                $completed_result = $stmt->get_result();
                $completed_tasks = $completed_result->fetch_assoc()['completed_tasks'];
                ?>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_tasks; ?></div>
                        <div class="stat-label">Total Tasks</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $completed_tasks; ?></div>
                        <div class="stat-label">Completed Tasks</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_tasks - $completed_tasks; ?></div>
                        <div class="stat-label">Pending Tasks</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/forms.js"></script>
</body>
</html>