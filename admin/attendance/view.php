<?php
include '../../config.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: manage.php");
    exit();
}

$attendance_id = sanitize($_GET['id']);

// Get attendance details
$sql = "SELECT a.*, 
        u.first_name, u.last_name, u.email, u.position, u.phone,
        d.name as department_name
        FROM attendance a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN departments d ON u.department_id = d.id 
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $attendance_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage.php");
    exit();
}

$attendance = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Attendance Details</h1>
            <div class="breadcrumb">Admin / Attendance / View</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Attendance Information</h2>
                <a href="manage.php" class="btn">Back to List</a>
            </div>
            <div class="card-body">
                <div class="attendance-details">
                    <div class="detail-section">
                        <h3>Employee Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Employee Name:</label>
                                <span><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Department:</label>
                                <span><?php echo htmlspecialchars($attendance['department_name'] ?? 'N/A'); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Position:</label>
                                <span><?php echo htmlspecialchars($attendance['position']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($attendance['email']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Phone:</label>
                                <span><?php echo htmlspecialchars($attendance['phone'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3>Attendance Details</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Date:</label>
                                <span><?php echo date('F d, Y', strtotime($attendance['date'])); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Day:</label>
                                <span><?php echo date('l', strtotime($attendance['date'])); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Check-in Time:</label>
                                <span>
                                    <?php if ($attendance['check_in']): ?>
                                        <?php echo date('h:i A', strtotime($attendance['check_in'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not checked in</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Check-out Time:</label>
                                <span>
                                    <?php if ($attendance['check_out']): ?>
                                        <?php echo date('h:i A', strtotime($attendance['check_out'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not checked out</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Total Hours:</label>
                                <span>
                                    <?php if ($attendance['total_hours']): ?>
                                        <?php echo number_format($attendance['total_hours'], 2); ?> hours
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Status:</label>
                                <span>
                                    <span class="badge status-<?php echo $attendance['status']; ?>">
                                        <?php echo ucfirst($attendance['status']); ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($attendance['notes']): ?>
                        <div class="detail-section">
                            <h3>Notes</h3>
                            <div class="notes-content">
                                <?php echo nl2br(htmlspecialchars($attendance['notes'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($attendance['check_in'] && $attendance['check_out']): ?>
                        <div class="detail-section">
                            <h3>Working Hours Breakdown</h3>
                            <div class="hours-breakdown">
                                <?php
                                $check_in = new DateTime($attendance['check_in']);
                                $check_out = new DateTime($attendance['check_out']);
                                $diff = $check_in->diff($check_out);
                                
                                $total_minutes = ($diff->h * 60) + $diff->i;
                                $working_hours = floor($total_minutes / 60);
                                $working_minutes = $total_minutes % 60;
                                ?>
                                
                                <div class="hours-item">
                                    <strong>Total Duration:</strong>
                                    <span><?php echo $working_hours; ?> hours <?php echo $working_minutes; ?> minutes</span>
                                </div>
                                
                                <div class="hours-item">
                                    <strong>Exact Hours:</strong>
                                    <span><?php echo number_format($attendance['total_hours'], 2); ?> hours</span>
                                </div>
                                
                                <div class="hours-item">
                                    <strong>Time Period:</strong>
                                    <span>
                                        <?php echo date('h:i A', strtotime($attendance['check_in'])); ?> 
                                        to 
                                        <?php echo date('h:i A', strtotime($attendance['check_out'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>