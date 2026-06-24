<?php
include '../../config.php';
requireAdmin();

// Handle task deletion
if (isset($_GET['delete_id'])) {
    $delete_id = sanitize($_GET['delete_id']);
    $sql = "DELETE FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    
    $_SESSION['message'] = "Task deleted successfully!";
    header("Location: manage.php");
    exit();
}

// Get all tasks with employee and department info
$sql = "SELECT t.*, 
        u.first_name, u.last_name, 
        d.name as department_name,
        ab.first_name as assigned_by_first, ab.last_name as assigned_by_last
        FROM tasks t 
        LEFT JOIN users u ON t.assigned_to = u.id 
        LEFT JOIN departments d ON t.department_id = d.id 
        LEFT JOIN users ab ON t.assigned_by = ab.id 
        ORDER BY t.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tasks - Admin</title>
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
            <h1 class="page-title">Manage Tasks</h1>
            <div class="breadcrumb">Admin / Tasks / Manage</div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Tasks List</h2>
                <div>
                    <a href="create.php" class="btn btn-primary">Create Task</a>
                    <a href="assign.php" class="btn btn-success">Assign Task</a>
                </div>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Assigned To</th>
                            <th>Department</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Assigned By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge priority-<?php echo $row['priority']; ?>">
                                            <?php echo ucfirst($row['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['assigned_by_first'] . ' ' . $row['assigned_by_last']); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="manage.php?delete_id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this task?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No tasks found</td>
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