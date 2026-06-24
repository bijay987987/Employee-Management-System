<?php
include '../config.php';
requireAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Employee Management</title>
    <link rel="icon" href="../images/favicon.ico">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Admin Dashboard</h1>
            <div class="breadcrumb">Home / Dashboard</div>
        </div>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: #3498db;">
                    <i>👥</i>
                </div>
                <div class="stat-info">
                    <h3>Total Employees</h3>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'employee' AND status = 'active'";
                    $result = $conn->query($sql);
                    $total_employees = $result->fetch_assoc()['total'];
                    ?>
                    <span class="stat-number"><?php echo $total_employees; ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #27ae60;">
                    <i>📋</i>
                </div>
                <div class="stat-info">
                    <h3>Total Tasks</h3>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM tasks";
                    $result = $conn->query($sql);
                    $total_tasks = $result->fetch_assoc()['total'];
                    ?>
                    <span class="stat-number"><?php echo $total_tasks; ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #e74c3c;">
                    <i>⏰</i>
                </div>
                <div class="stat-info">
                    <h3>Pending Tasks</h3>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM tasks WHERE status = 'pending'";
                    $result = $conn->query($sql);
                    $pending_tasks = $result->fetch_assoc()['total'];
                    ?>
                    <span class="stat-number"><?php echo $pending_tasks; ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #f39c12;">
                    <i>🏢</i>
                </div>
                <div class="stat-info">
                    <h3>Departments</h3>
                    <?php
                    $sql = "SELECT COUNT(*) as total FROM departments WHERE status = 'active'";
                    $result = $conn->query($sql);
                    $total_departments = $result->fetch_assoc()['total'];
                    ?>
                    <span class="stat-number"><?php echo $total_departments; ?></span>
                </div>
            </div>
        </div>
        <!-- Add this to your admin dashboard stats section -->
<div class="stat-card">
    <div class="stat-icon" style="background: #9b59b6;">
        <i>📅</i>
    </div>
    <div class="stat-info">
        <h3>Leave Requests</h3>
        <?php 
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
                FROM leaves";
        $result = $conn->query($sql);
        $leave_stats = $result->fetch_assoc();
        ?>
        <span class="stat-number"><?php echo $leave_stats['total']; ?></span>
        <small><?php echo $leave_stats['pending']; ?> pending</small>
    </div>
</div>
        
        <div class="dashboard-content">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Tasks</h2>
                    <a href="tasks/manage.php" class="btn btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Assigned To</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT t.*, u.first_name, u.last_name 
                                    FROM tasks t 
                                    LEFT JOIN users u ON t.assigned_to = u.id 
                                    ORDER BY t.created_at DESC 
                                    LIMIT 5";
                            $result = $conn->query($sql);
                            
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
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><span class="badge <?php echo $priority_class; ?>"><?php echo ucfirst($row['priority']); ?></span></td>
                                        <td><span class="badge <?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center">No tasks found</td></tr>';
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