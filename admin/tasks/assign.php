<?php
include '../../config.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = sanitize($_POST['task_id']);
    $assigned_to = sanitize($_POST['assigned_to']);
    
    $sql = "UPDATE tasks SET assigned_to = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $assigned_to, $task_id);
    
    if ($stmt->execute()) {
        $success = "Task assigned successfully!";
        $_POST = array(); // Clear form
    } else {
        $error = "Error assigning task: " . $conn->error;
    }
}

// Get unassigned tasks
$tasks_sql = "SELECT * FROM tasks WHERE assigned_to IS NULL AND status != 'completed'";
$tasks_result = $conn->query($tasks_sql);

// Get employees for dropdown
$employees_sql = "SELECT id, first_name, last_name, position FROM users WHERE role = 'employee' AND status = 'active'";
$employees_result = $conn->query($employees_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Task - Admin</title>
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
            <h1 class="page-title">Assign Task</h1>
            <div class="breadcrumb">Admin / Tasks / Assign</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Assign Task to Employee</h2>
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
                            <label for="task_id" class="form-label">Select Task *</label>
                            <select id="task_id" name="task_id" class="form-control" required>
                                <option value="">Select Task</option>
                                <?php while ($task = $tasks_result->fetch_assoc()): ?>
                                    <option value="<?php echo $task['id']; ?>" 
                                        <?php echo (($_POST['task_id'] ?? '') == $task['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($task['title']); ?> 
                                        (Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="assigned_to" class="form-label">Assign To Employee *</label>
                            <select id="assigned_to" name="assigned_to" class="form-control" required>
                                <option value="">Select Employee</option>
                                <?php while ($employee = $employees_result->fetch_assoc()): ?>
                                    <option value="<?php echo $employee['id']; ?>" 
                                        <?php echo (($_POST['assigned_to'] ?? '') == $employee['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' - ' . $employee['position']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Assign Task</button>
                        <a href="manage.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Display unassigned tasks -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Unassigned Tasks</h2>
            </div>
            <div class="card-body">
                <?php
                $unassigned_sql = "SELECT t.*, d.name as department_name 
                                  FROM tasks t 
                                  LEFT JOIN departments d ON t.department_id = d.id 
                                  WHERE t.assigned_to IS NULL 
                                  ORDER BY t.created_at DESC";
                $unassigned_result = $conn->query($unassigned_sql);
                ?>
                
                <?php if ($unassigned_result->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($task = $unassigned_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td><?php echo htmlspecialchars($task['department_name']); ?></td>
                                    <td>
                                        <span class="badge priority-<?php echo $task['priority']; ?>">
                                            <?php echo ucfirst($task['priority']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($task['due_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($task['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No unassigned tasks found.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/forms.js"></script>
</body>
</html>