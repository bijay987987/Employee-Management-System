<?php
include '../../config.php';
requireAdmin();

// Handle department deletion
if (isset($_GET['delete_id'])) {
    $delete_id = sanitize($_GET['delete_id']);
    
    // Check if department has employees
    $check_sql = "SELECT COUNT(*) as employee_count FROM users WHERE department_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee_count = $result->fetch_assoc()['employee_count'];
    
    if ($employee_count > 0) {
        $_SESSION['error'] = "Cannot delete department with assigned employees!";
    } else {
        $sql = "UPDATE departments SET status = 'inactive' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        
        $_SESSION['message'] = "Department deactivated successfully!";
    }
    
    header("Location: manage.php");
    exit();
}

// Get all departments
$sql = "SELECT d.*, 
        COUNT(u.id) as employee_count,
        m.first_name as manager_first, m.last_name as manager_last
        FROM departments d 
        LEFT JOIN users u ON d.id = u.department_id AND u.status = 'active'
        LEFT JOIN users m ON d.manager_id = m.id 
        WHERE d.status = 'active'
        GROUP BY d.id 
        ORDER BY d.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/tables.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Manage Departments</h1>
            <div class="breadcrumb">Admin / Departments / Manage</div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Departments List</h2>
                <a href="add.php" class="btn btn-primary">Add New Department</a>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Manager</th>
                            <th>Employees</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description'] ?? 'No description'); ?></td>
                                    <td>
                                        <?php if ($row['manager_first']): ?>
                                            <?php echo htmlspecialchars($row['manager_first'] . ' ' . $row['manager_last']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['employee_count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="manage.php?delete_id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to deactivate this department?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No departments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>