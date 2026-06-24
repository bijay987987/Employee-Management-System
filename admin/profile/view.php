<?php
include '../../config.php';
requireAdmin();

$user_id = $_SESSION['user_id'];

// Get admin details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">My Profile</h1>
            <div class="breadcrumb">Admin / Profile / View</div>
        </div>
        
        <div class="profile-container">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Admin Information</h2>
                    <a href="change_password.php" class="btn btn-warning">Change Password</a>
                </div>
                <div class="card-body">
                    <div class="profile-details">
                        <div class="profile-avatar">
                            <div class="avatar-large">
                                <?php echo strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)); ?>
                            </div>
                        </div>
                        
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>First Name:</label>
                                <span><?php echo htmlspecialchars($admin['first_name']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Last Name:</label>
                                <span><?php echo htmlspecialchars($admin['last_name']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Username:</label>
                                <span><?php echo htmlspecialchars($admin['username']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($admin['email']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Role:</label>
                                <span class="badge" style="background: var(--primary-color); color: white;">
                                    <?php echo ucfirst($admin['role']); ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Position:</label>
                                <span><?php echo htmlspecialchars($admin['position'] ?? 'System Administrator'); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Account Created:</label>
                                <span><?php echo date('F d, Y', strtotime($admin['created_at'])); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Last Updated:</label>
                                <span><?php echo date('F d, Y \a\t h:i A', strtotime($admin['updated_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">System Statistics</h2>
                </div>
                <div class="card-body">
                    <?php
                    // Get system statistics
                    $employees_sql = "SELECT COUNT(*) as total_employees FROM users WHERE role = 'employee' AND status = 'active'";
                    $employees_result = $conn->query($employees_sql);
                    $total_employees = $employees_result->fetch_assoc()['total_employees'];
                    
                    $tasks_sql = "SELECT COUNT(*) as total_tasks FROM tasks";
                    $tasks_result = $conn->query($tasks_sql);
                    $total_tasks = $tasks_result->fetch_assoc()['total_tasks'];
                    
                    $departments_sql = "SELECT COUNT(*) as total_departments FROM departments WHERE status = 'active'";
                    $departments_result = $conn->query($departments_sql);
                    $total_departments = $departments_result->fetch_assoc()['total_departments'];
                    
                    $pending_tasks_sql = "SELECT COUNT(*) as pending_tasks FROM tasks WHERE status = 'pending'";
                    $pending_tasks_result = $conn->query($pending_tasks_sql);
                    $pending_tasks = $pending_tasks_result->fetch_assoc()['pending_tasks'];
                    ?>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_employees; ?></div>
                            <div class="stat-label">Total Employees</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_tasks; ?></div>
                            <div class="stat-label">Total Tasks</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_departments; ?></div>
                            <div class="stat-label">Departments</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $pending_tasks; ?></div>
                            <div class="stat-label">Pending Tasks</div>
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