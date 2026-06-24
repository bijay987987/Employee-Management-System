<?php
include '../../config.php';
requireEmployee();

$user_id = $_SESSION['user_id'];

// Get user details
$sql = "SELECT u.*, d.name as department_name 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.id 
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Employee</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/employee.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">My Profile</h1>
            <div class="breadcrumb">Employee / Profile / View</div>
        </div>
        
        <div class="profile-container">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Personal Information</h2>
                    <a href="edit.php" class="btn btn-warning">Edit Profile</a>
                </div>
                <div class="card-body">
                    <div class="profile-details">
                        <div class="profile-avatar">
                            <div class="avatar-large">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                        </div>
                        
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>First Name:</label>
                                <span><?php echo htmlspecialchars($user['first_name']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Last Name:</label>
                                <span><?php echo htmlspecialchars($user['last_name']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Username:</label>
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Phone:</label>
                                <span><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Department:</label>
                                <span><?php echo htmlspecialchars($user['department_name'] ?? 'Not assigned'); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Position:</label>
                                <span><?php echo htmlspecialchars($user['position'] ?? 'Not specified'); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Hire Date:</label>
                                <span><?php echo date('F d, Y', strtotime($user['hire_date'])); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Status:</label>
                                <span class="badge status-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Quick Stats</h2>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <?php
                        // Get task stats
                        $tasks_sql = "SELECT COUNT(*) as total_tasks FROM tasks WHERE assigned_to = ?";
                        $stmt = $conn->prepare($tasks_sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $tasks_result = $stmt->get_result();
                        $total_tasks = $tasks_result->fetch_assoc()['total_tasks'];
                        
                        $pending_sql = "SELECT COUNT(*) as pending_tasks FROM tasks WHERE assigned_to = ? AND status = 'pending'";
                        $stmt = $conn->prepare($pending_sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $pending_result = $stmt->get_result();
                        $pending_tasks = $pending_result->fetch_assoc()['pending_tasks'];
                        
                        $completed_sql = "SELECT COUNT(*) as completed_tasks FROM tasks WHERE assigned_to = ? AND status = 'completed'";
                        $stmt = $conn->prepare($completed_sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $completed_result = $stmt->get_result();
                        $completed_tasks = $completed_result->fetch_assoc()['completed_tasks'];
                        ?>
                        
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_tasks; ?></div>
                            <div class="stat-label">Total Tasks</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $pending_tasks; ?></div>
                            <div class="stat-label">Pending Tasks</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $completed_tasks; ?></div>
                            <div class="stat-label">Completed Tasks</div>
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