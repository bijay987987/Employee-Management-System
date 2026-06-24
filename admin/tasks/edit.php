<?php
include '../../config.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: manage.php");
    exit();
}

$task_id = sanitize($_GET['id']);
$error = '';
$success = '';

// Get task details
$sql = "SELECT * FROM tasks WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage.php");
    exit();
}

$task = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $assigned_to = sanitize($_POST['assigned_to']);
    $department_id = sanitize($_POST['department_id']);
    $priority = sanitize($_POST['priority']);
    $status = sanitize($_POST['status']);
    $due_date = sanitize($_POST['due_date']);
    
    // If task is being marked as completed, set completed date
    $completed_date = $task['completed_date'];
    if ($status == 'completed' && $task['status'] != 'completed') {
        $completed_date = date('Y-m-d');
    } elseif ($status != 'completed') {
        $completed_date = null;
    }
    
    $sql = "UPDATE tasks SET title = ?, description = ?, assigned_to = ?, department_id = ?, 
            priority = ?, status = ?, due_date = ?, completed_date = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiissssi", $title, $description, $assigned_to, $department_id, 
                     $priority, $status, $due_date, $completed_date, $task_id);
    
    if ($stmt->execute()) {
        $success = "Task updated successfully!";
        // Refresh task data
        $sql = "SELECT * FROM tasks WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $task = $result->fetch_assoc();
    } else {
        $error = "Error updating task: " . $conn->error;
    }
}

// Get departments for dropdown
$dept_sql = "SELECT * FROM departments WHERE status = 'active'";
$dept_result = $conn->query($dept_sql);

// Get employees for dropdown
$employees_sql = "SELECT id, first_name, last_name, position FROM users WHERE role = 'employee' AND status = 'active'";
$employees_result = $conn->query($employees_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - Admin</title>
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
            <h1 class="page-title">Edit Task</h1>
            <div class="breadcrumb">Admin / Tasks / Edit</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Edit Task Details</h2>
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
                        <label for="title" class="form-label">Task Title *</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?php echo htmlspecialchars($task['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description *</label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="5" required><?php echo htmlspecialchars($task['description']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="assigned_to" class="form-label">Assigned To</label>
                            <select id="assigned_to" name="assigned_to" class="form-control">
                                <option value="">Select Employee</option>
                                <?php while ($employee = $employees_result->fetch_assoc()): ?>
                                    <option value="<?php echo $employee['id']; ?>" 
                                        <?php echo ($task['assigned_to'] == $employee['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' - ' . $employee['position']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="department_id" class="form-label">Department *</label>
                            <select id="department_id" name="department_id" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php while ($dept = $dept_result->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo ($task['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="priority" class="form-label">Priority *</label>
                            <select id="priority" name="priority" class="form-control" required>
                                <option value="low" <?php echo $task['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo $task['priority'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo $task['priority'] == 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?php echo $task['priority'] == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Status *</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $task['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $task['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="due_date" class="form-label">Due Date *</label>
                        <input type="date" id="due_date" name="due_date" class="form-control" 
                               value="<?php echo $task['due_date']; ?>" required>
                    </div>
                    
                    <?php if ($task['completed_date']): ?>
                        <div class="form-group">
                            <label class="form-label">Completed Date</label>
                            <p class="form-control-static"><?php echo date('F d, Y', strtotime($task['completed_date'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Task</button>
                        <a href="view.php?id=<?php echo $task['id']; ?>" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Task Activity</h2>
            </div>
            <div class="card-body">
                <div class="activity-timeline">
                    <div class="activity-item">
                        <div class="activity-date"><?php echo date('M d, Y \a\t h:i A', strtotime($task['created_at'])); ?></div>
                        <div class="activity-content">Task created</div>
                    </div>
                    
                    <?php if ($task['updated_at'] != $task['created_at']): ?>
                        <div class="activity-item">
                            <div class="activity-date"><?php echo date('M d, Y \a\t h:i A', strtotime($task['updated_at'])); ?></div>
                            <div class="activity-content">Task last updated</div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($task['completed_date']): ?>
                        <div class="activity-item">
                            <div class="activity-date"><?php echo date('M d, Y', strtotime($task['completed_date'])); ?></div>
                            <div class="activity-content">Task completed</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/forms.js"></script>
</body>
</html>