<?php
include '../../config.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: manage.php");
    exit();
}

$task_id = sanitize($_GET['id']);

// Get task details
$sql = "SELECT t.*, 
        u.first_name, u.last_name, u.email, u.position,
        d.name as department_name,
        ab.first_name as assigned_by_first, ab.last_name as assigned_by_last
        FROM tasks t 
        LEFT JOIN users u ON t.assigned_to = u.id 
        LEFT JOIN departments d ON t.department_id = d.id 
        LEFT JOIN users ab ON t.assigned_by = ab.id 
        WHERE t.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage.php");
    exit();
}

$task = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Task - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">View Task</h1>
            <div class="breadcrumb">Admin / Tasks / View</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Task Details</h2>
                <div>
                    <a href="edit.php?id=<?php echo $task['id']; ?>" class="btn btn-warning">Edit Task</a>
                    <a href="manage.php" class="btn">Back to List</a>
                </div>
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
                        <div class="detail-label">Department:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($task['department_name']); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Assigned To:</div>
                        <div class="detail-value">
                            <?php if ($task['assigned_to']): ?>
                                <?php echo htmlspecialchars($task['first_name'] . ' ' . $task['last_name']); ?>
                                (<?php echo htmlspecialchars($task['position']); ?>)
                                <br>
                                <small><?php echo htmlspecialchars($task['email']); ?></small>
                            <?php else: ?>
                                <span class="text-muted">Not assigned</span>
                            <?php endif; ?>
                        </div>
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
                        <div class="detail-label">Status:</div>
                        <div class="detail-value">
                            <span class="badge status-<?php echo $task['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
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
                    
                    <div class="detail-row">
                        <div class="detail-label">Completed Date:</div>
                        <div class="detail-value">
                            <?php echo $task['completed_date'] ? date('F d, Y', strtotime($task['completed_date'])) : 'Not completed'; ?>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Assigned By:</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($task['assigned_by_first'] . ' ' . $task['assigned_by_last']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Created Date:</div>
                        <div class="detail-value">
                            <?php echo date('F d, Y \a\t h:i A', strtotime($task['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Last Updated:</div>
                        <div class="detail-value">
                            <?php echo date('F d, Y \a\t h:i A', strtotime($task['updated_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>