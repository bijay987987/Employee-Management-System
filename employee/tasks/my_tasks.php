<?php
include '../../config.php';
requireEmployee();

$user_id = $_SESSION['user_id'];

// Handle task status update
if (isset($_POST['update_status'])) {
    $task_id = sanitize($_POST['task_id']);
    $status = sanitize($_POST['status']);
    
    $sql = "UPDATE tasks SET status = ? WHERE id = ? AND assigned_to = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $task_id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Task status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating task status!";
    }
    
    header("Location: my_tasks.php");
    exit();
}

// Get user's tasks
$sql = "SELECT t.*, d.name as department_name 
        FROM tasks t 
        LEFT JOIN departments d ON t.department_id = d.id 
        WHERE t.assigned_to = ? 
        ORDER BY 
            CASE 
                WHEN t.status = 'pending' THEN 1
                WHEN t.status = 'in_progress' THEN 2
                WHEN t.status = 'completed' THEN 3
                ELSE 4
            END,
            t.due_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - Employee</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/employee.css">
    <link rel="stylesheet" href="../../assets/css/tables.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">My Tasks</h1>
            <div class="breadcrumb">Employee / Tasks / My Tasks</div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Task List</h2>
            </div>
            <div class="card-body">
                <?php if ($result->num_rows > 0): ?>
                    <div class="tasks-list">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="task-item <?php echo $row['priority'] . '-priority'; ?>">
                                <div class="task-header">
                                    <h3 class="task-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                                    <span class="task-priority badge priority-<?php echo $row['priority']; ?>">
                                        <?php echo ucfirst($row['priority']); ?>
                                    </span>
                                </div>
                                
                                <div class="task-description">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </div>
                                
                                <div class="task-meta">
                                    <span>Department: <?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></span>
                                    <span>Due: <?php echo date('M d, Y', strtotime($row['due_date'])); ?></span>
                                    <span>Status: 
                                        <span class="badge status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                        </span>
                                    </span>
                                </div>
                                
                                <div class="task-actions">
                                    <form method="POST" action="" class="status-form">
                                        <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
                                        <select name="status" class="form-control" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $row['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="in_progress" <?php echo $row['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="completed" <?php echo $row['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                        <button type="submit" name="update_status" style="display: none;">Update</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <p>No tasks assigned to you.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/tasks.js"></script>
</body>
</html>