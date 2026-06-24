<?php
require_once '../../config.php';
requireEmployee();

$employee_id = $_SESSION['user_id'];
$current_year = date('Y');

// Store all database results in arrays first
$summary_data = [];
$upcoming_leaves = [];
$pending_leaves = [];

// Get leave summary by type
$summary_sql = "SELECT 
    leave_type,
    COUNT(*) as count,
    SUM(CASE WHEN status = 'Approved' THEN DATEDIFF(end_date, start_date) + 1 ELSE 0 END) as approved_days,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count
    FROM leaves 
    WHERE employee_id = ? AND YEAR(start_date) = ?
    GROUP BY leave_type";
$summary_stmt = $conn->prepare($summary_sql);
$summary_stmt->bind_param("ii", $employee_id, $current_year);
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();

while ($row = $summary_result->fetch_assoc()) {
    $summary_data[$row['leave_type']] = $row;
}

// Get upcoming approved leaves
$upcoming_sql = "SELECT * FROM leaves 
                WHERE employee_id = ? 
                AND status = 'Approved' 
                AND start_date >= CURDATE() 
                ORDER BY start_date ASC 
                LIMIT 5";
$upcoming_stmt = $conn->prepare($upcoming_sql);
$upcoming_stmt->bind_param("i", $employee_id);
$upcoming_stmt->execute();
$upcoming_result = $upcoming_stmt->get_result();

while ($row = $upcoming_result->fetch_assoc()) {
    $upcoming_leaves[] = $row;
}

// Get pending leaves
$pending_sql = "SELECT * FROM leaves 
               WHERE employee_id = ? 
               AND status = 'Pending' 
               ORDER BY applied_at DESC";
$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("i", $employee_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();

while ($row = $pending_result->fetch_assoc()) {
    $pending_leaves[] = $row;
}

// Get leave quota (example values)
$leave_quota = [
    'Annual' => ['total' => 20, 'used' => 0, 'remaining' => 20],
    'Sick' => ['total' => 15, 'used' => 0, 'remaining' => 15],
    'Casual' => ['total' => 7, 'used' => 0, 'remaining' => 7]
];

// Calculate used leaves
foreach ($summary_data as $leave_type => $row) {
    if (isset($leave_quota[$leave_type])) {
        $leave_quota[$leave_type]['used'] = $row['approved_days'] ?? 0;
        $leave_quota[$leave_type]['remaining'] = 
            $leave_quota[$leave_type]['total'] - ($row['approved_days'] ?? 0);
    }
}

// Calculate totals
$total_used = array_sum(array_column($leave_quota, 'used'));
$total_allowed = array_sum(array_column($leave_quota, 'total'));
$pending_count = count($pending_leaves);
$upcoming_count = count($upcoming_leaves);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leaves Dashboard - Employee</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        /* Your existing CSS styles here */
        .leave-quota-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }
        .quota-annual { border-color: #3498db; }
        .quota-sick { border-color: #27ae60; }
        .quota-casual { border-color: #e74c3c; }
        .quota-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .quota-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        .quota-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .quota-progress {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        .quota-progress-bar {
            height: 100%;
            border-radius: 4px;
        }
        .quota-numbers {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
        }
        .upcoming-leave {
            background: #e8f5e9;
            border-left: 4px solid #27ae60;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .pending-leave {
            background: #fff8e1;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .leave-days {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">My Leaves Dashboard</h1>
            <div class="breadcrumb">Leaves / Dashboard</div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo $total_used; ?></span>
                <span class="stat-label">Leaves Used</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $total_allowed - $total_used; ?></span>
                <span class="stat-label">Leaves Remaining</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $pending_count; ?></span>
                <span class="stat-label">Pending Requests</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $upcoming_count; ?></span>
                <span class="stat-label">Upcoming Leaves</span>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Leave Quota <?php echo $current_year; ?></h2>
                    </div>
                    <div class="card-body">
                        <?php foreach ($leave_quota as $type => $quota): 
                            $percentage = $quota['total'] > 0 ? ($quota['used'] / $quota['total']) * 100 : 0;
                            $color_class = '';
                            if ($percentage < 50) $color_class = 'bg-success';
                            elseif ($percentage < 80) $color_class = 'bg-warning';
                            else $color_class = 'bg-danger';
                        ?>
                        <div class="leave-quota-card quota-<?php echo strtolower($type); ?>">
                            <div class="quota-header">
                                <div class="quota-title"><?php echo $type; ?> Leave</div>
                                <span class="quota-badge" style="background: <?php echo $percentage < 80 ? '#27ae60' : '#e74c3c'; ?>; color: white;">
                                    <?php echo $quota['remaining']; ?> days left
                                </span>
                            </div>
                            <div class="quota-progress">
                                <div class="quota-progress-bar <?php echo $color_class; ?>" 
                                     style="width: <?php echo min($percentage, 100); ?>%"></div>
                            </div>
                            <div class="quota-numbers">
                                <span>Used: <?php echo $quota['used']; ?> days</span>
                                <span>Total: <?php echo $quota['total']; ?> days</span>
                                <span>Remaining: <?php echo $quota['remaining']; ?> days</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="card-title">Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <a href="apply.php" class="btn btn-primary btn-lg w-100 mb-3">
                                    Apply for Leave
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="history.php" class="btn btn-info btn-lg w-100 mb-3">
                                    View History
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="status.php" class="btn btn-success btn-lg w-100 mb-3">
                                    View Status
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Upcoming Approved Leaves</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcoming_leaves)): 
                            foreach ($upcoming_leaves as $leave): 
                                if (!empty($leave['start_date']) && !empty($leave['end_date'])) {
                                    $days = (new DateTime($leave['start_date']))->diff(new DateTime($leave['end_date']))->days + 1;
                                    $days_until = (new DateTime())->diff(new DateTime($leave['start_date']))->days;
                                } else {
                                    $days = 0;
                                    $days_until = 0;
                                }
                        ?>
                        <div class="upcoming-leave">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($leave['leave_type'] ?? 'N/A'); ?></h6>
                                    <small class="text-muted">
                                        <?php echo !empty($leave['start_date']) ? date('M d', strtotime($leave['start_date'])) : 'N/A'; ?> - 
                                        <?php echo !empty($leave['end_date']) ? date('M d, Y', strtotime($leave['end_date'])) : 'N/A'; ?>
                                    </small>
                                </div>
                                <span class="badge bg-success"><?php echo $days; ?> days</span>
                            </div>
                            <div class="mt-2">
                                <small>
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?php echo $days_until > 0 ? "In $days_until days" : "Starts today"; ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="text-center text-muted py-4">
                            No upcoming approved leaves
                        </div>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="history.php?filter=upcoming" class="btn btn-outline-primary btn-sm">
                                View All Upcoming Leaves
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="card-title">Pending Leave Requests</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pending_leaves)): 
                            foreach ($pending_leaves as $leave): 
                                if (!empty($leave['start_date']) && !empty($leave['end_date'])) {
                                    $days = (new DateTime($leave['start_date']))->diff(new DateTime($leave['end_date']))->days + 1;
                                } else {
                                    $days = 0;
                                }
                                
                                if (!empty($leave['applied_at'])) {
                                    $applied_days = (new DateTime($leave['applied_at']))->diff(new DateTime())->days;
                                } else {
                                    $applied_days = 0;
                                }
                        ?>
                        <div class="pending-leave">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($leave['leave_type'] ?? 'N/A'); ?></h6>
                                    <small class="text-muted">
                                        Applied <?php echo $applied_days == 0 ? 'today' : "$applied_days days ago"; ?>
                                    </small>
                                </div>
                                <span class="badge bg-warning">Pending</span>
                            </div>
                            <div class="mt-2">
                                <small>
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo !empty($leave['start_date']) ? date('M d', strtotime($leave['start_date'])) : 'N/A'; ?> - 
                                    <?php echo !empty($leave['end_date']) ? date('M d', strtotime($leave['end_date'])) : 'N/A'; ?>
                                    (<?php echo $days; ?> days)
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="text-center text-muted py-4">
                            No pending leave requests
                        </div>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="history.php?filter=pending" class="btn btn-outline-warning btn-sm">
                                View All Pending Requests
                            </a>
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