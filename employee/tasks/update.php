<?php
include '../../config.php';
requireEmployee();

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: my_tasks.php");
    exit();
}

$task_id = sanitize($_GET['id']);
$error = '';
$success = '';

// Get task details and verify ownership
$sql = "SELECT * FROM tasks WHERE id = ? AND assigned_to = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: my_tasks.php");
    exit();
}

$task = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = sanitize($_POST['status']);
    $progress_notes = sanitize($_POST['progress_notes']);
    
    // If task is being marked as completed, set completed date
    $completed_date = $task['completed_date'];
    if ($status == 'completed' && $task['status'] != 'completed') {
        $completed_date = date('Y-m-d');
    }
    
    $sql = "UPDATE tasks SET status = ?, completed_date = ? WHERE id = ? AND assigned_to = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $status, $completed_date, $task_id, $user_id);
    
    if ($stmt->execute()) {
        // Log progress notes if provided
        if (!empty($progress_notes)) {
            $log_sql = "INSERT INTO task_progress (task_id, user_id, notes, created_at) VALUES (?, ?, ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("iis", $task_id, $user_id, $progress_notes);
            $log_stmt->execute();
        }
        
        $success = "Task status updated successfully!";
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

// Get task progress history
$progress_sql = "SELECT tp.*, u.first_name, u.last_name 
                 FROM task_progress tp 
                 JOIN users u ON tp.user_id = u.id 
                 WHERE tp.task_id = ? 
                 ORDER BY tp.created_at DESC";
$progress_stmt = $conn->prepare($progress_sql);
$progress_stmt->bind_param("i", $task_id);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Task - Employee</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/employee.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Update Task</h1>
            <div class="breadcrumb">Employee / Tasks / Update</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Task Details</h2>
            </div>
            <div class="card-body">
                <div class="task-details">
                    <div class="detail-row">
                        <div class="detail-label">Task Title:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($task['title']); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Description:</div>
                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($task['description'])); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Priority:</div>
                        <div class="detail-value">
                            <span class="badge priority-<?php echo $task['priority']; ?>">
                                <?php echo ucfirst($task['priority']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Due Date:</div>
                        <div class="detail-value">
                            <?php echo date('F d, Y', strtotime($task['due_date'])); ?>
                            <?php
                            $due_date = new DateTime($task['due_date']);
                            $today = new DateTime();
                            if ($due_date < $today && $task['status'] != 'completed') {
                                echo '<span class="badge" style="background: var(--danger-color); color: white; margin-left: 10px;">Overdue</span>';
                            } elseif ($due_date->diff($today)->days <= 2 && $task['status'] != 'completed') {
                                echo '<span class="badge" style="background: var(--warning-color); color: white; margin-left: 10px;">Due Soon</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Update Task Status</h2>
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
                        <label for="status" class="form-label">Status *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $task['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="progress_notes" class="form-label">Progress Notes</label>
                        <textarea id="progress_notes" name="progress_notes" class="form-control" 
                                  rows="4" placeholder="Add any updates or comments about this task..."></textarea>
                        <small class="form-text">Optional: Add notes about your progress or any issues encountered.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Task</button>
                        <a href="my_tasks.php" class="btn">Back to Tasks</a>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($progress_result->num_rows > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Progress History</h2>
                </div>
                <div class="card-body">
                    <div class="progress-timeline">
                        <?php while ($progress = $progress_result->fetch_assoc()): ?>
                            <div class="progress-item">
                                <div class="progress-header">
                                    <strong><?php echo htmlspecialchars($progress['first_name'] . ' ' . $progress['last_name']); ?></strong>
                                    <span class="progress-date"><?php echo date('M d, Y \a\t h:i A', strtotime($progress['created_at'])); ?></span>
                                </div>
                                <div class="progress-content">
                                    <?php echo nl2br(htmlspecialchars($progress['notes'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/forms.js"></script>
</body>
</html>