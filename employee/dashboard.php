<?php
include '../config.php';
requireEmployee();

$user_id = $_SESSION['user_id'];

// Get employee stats
$sql_tasks = "SELECT COUNT(*) as total_tasks FROM tasks WHERE assigned_to = ?";
$stmt = $conn->prepare($sql_tasks);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_tasks = $stmt->get_result();
$total_tasks = $result_tasks->fetch_assoc()['total_tasks'];

$sql_pending = "SELECT COUNT(*) as pending_tasks FROM tasks WHERE assigned_to = ? AND status = 'pending'";
$stmt = $conn->prepare($sql_pending);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_pending = $stmt->get_result();
$pending_tasks = $result_pending->fetch_assoc()['pending_tasks'];

$sql_completed = "SELECT COUNT(*) as completed_tasks FROM tasks WHERE assigned_to = ? AND status = 'completed'";
$stmt = $conn->prepare($sql_completed);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_completed = $stmt->get_result();
$completed_tasks = $result_completed->fetch_assoc()['completed_tasks'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Employee Management</title>
    <link rel="icon" href="../images/favicon.ico">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/employee.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Employee Dashboard</h1>
            <div class="breadcrumb">Home / Dashboard</div>
        </div>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: #3498db;">
                    <i>📋</i>
                </div>
                <div class="stat-info">
                    <h3>Total Tasks</h3>
                    <span class="stat-number"><?php echo $total_tasks; ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #e74c3c;">
                    <i>⏳</i>
                </div>
                <div class="stat-info">
                    <h3>Pending Tasks</h3>
                    <span class="stat-number"><?php echo $pending_tasks; ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #27ae60;">
                    <i>✅</i>
                </div>
                <div class="stat-info">
                    <h3>Completed Tasks</h3>
                    <span class="stat-number"><?php echo $completed_tasks; ?></span>
                </div>
            </div>
        </div>
        <!-- Add this to your employee dashboard stats section -->
<div class="stat-card">
    <div class="stat-icon" style="background: #9b59b6;">
        <i>📅</i>
    </div>
    <div class="stat-info">
        <h3>My Leaves</h3>
        
        <?php 
        if (function_exists('getEmployeeLeaveStats')) {
    $leave_stats = getEmployeeLeaveStats($user_id);
} else {
    // Default values if function doesn't exist
    $leave_stats = array('total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0);
}
?>
    
        <span class="stat-number"><?php echo $leave_stats['total']; ?></span>
        <small><?php echo $leave_stats['pending']; ?> pending</small>
    </div>
</div>
        
        <div class="dashboard-content">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">My Recent Tasks</h2>
                    <a href="tasks/my_tasks.php" class="btn btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC LIMIT 5";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $priority_class = '';
                                    switch ($row['priority']) {
                                        case 'high': $priority_class = 'priority-high'; break;
                                        case 'medium': $priority_class = 'priority-medium'; break;
                                        case 'low': $priority_class = 'priority-low'; break;
                                    }
                                    
                                    $status_class = '';
                                    switch ($row['status']) {
                                        case 'completed': $status_class = 'status-completed'; break;
                                        case 'in_progress': $status_class = 'status-in-progress'; break;
                                        case 'pending': $status_class = 'status-pending'; break;
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><span class="badge <?php echo $priority_class; ?>"><?php echo ucfirst($row['priority']); ?></span></td>
                                        <td><span class="badge <?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                        <td>
                                            <a href="tasks/update.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Update</a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center">No tasks assigned</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>