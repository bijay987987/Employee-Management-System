<?php
require_once '../../config.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: manage.php");
    exit();
}

$leave_id = intval($_GET['id']);

// Get leave details with employee info
$sql = "SELECT l.*, 
        u.first_name, u.last_name, u.email, u.position, u.phone,
        d.name as department_name,
        a.first_name as action_first, a.last_name as action_last
        FROM leaves l 
        JOIN users u ON l.employee_id = u.id 
        LEFT JOIN departments d ON u.department_id = d.id 
        LEFT JOIN users a ON l.action_by = a.id 
        WHERE l.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage.php");
    exit();
}

$leave = $result->fetch_assoc();

// Calculate days
$start = new DateTime($leave['start_date']);
$end = new DateTime($leave['end_date']);
$interval = $start->diff($end);
$days = $interval->days + 1;

// Get employee's leave history
$history_sql = "SELECT * FROM leaves WHERE employee_id = ? AND id != ? ORDER BY start_date DESC LIMIT 5";
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("ii", $leave['employee_id'], $leave_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Leave - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .info-card {
            border-left: 4px solid #3498db;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending { background-color: #f39c12; color: white; }
        .status-approved { background-color: #27ae60; color: white; }
        .status-rejected { background-color: #e74c3c; color: white; }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .detail-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .detail-value {
            color: #212529;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Review Leave Request</h1>
            <div class="breadcrumb">Admin / Leaves / Review</div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title">Leave Request Details</h2>
                <span class="status-badge <?php echo 'status-' . strtolower($leave['status']); ?>">
                    <?php echo $leave['status']; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Employee Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Employee ID</div>
                        <div class="detail-value">#EMP<?php echo str_pad($leave['employee_id'], 4, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Department</div>
                        <div class="detail-value"><?php echo htmlspecialchars($leave['department_name'] ?? 'Not Assigned'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Position</div>
                        <div class="detail-value"><?php echo htmlspecialchars($leave['position']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Leave Type</div>
                        <div class="detail-value"><?php echo htmlspecialchars($leave['leave_type']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Duration</div>
                        <div class="detail-value">
                            <?php echo date('M d, Y', strtotime($leave['start_date'])); ?> - 
                            <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                            (<?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?>)
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Applied On</div>
                        <div class="detail-value"><?php echo date('M d, Y h:i A', strtotime($leave['applied_at'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Email</div>
                        <div class="detail-value"><?php echo htmlspecialchars($leave['email']); ?></div>
                    </div>
                </div>
                
                <div class="info-card">
                    <h5 class="mb-3"><i class="fas fa-sticky-note"></i> Reason for Leave</h5>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($leave['reason'])); ?></p>
                </div>
                
                <?php if (!empty($leave['document'])): ?>
                <div class="info-card">
                    <h5 class="mb-3"><i class="fas fa-paperclip"></i> Supporting Document</h5>
                    <a href="../../uploads/leaves/<?php echo $leave['document']; ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-download"></i> Download Document
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($leave['admin_remarks'])): ?>
                <div class="info-card">
                    <h5 class="mb-3"><i class="fas fa-comment-alt"></i> Admin Remarks</h5>
                    <p class="mb-2"><?php echo nl2br(htmlspecialchars($leave['admin_remarks'])); ?></p>
                    <small class="text-muted">
                        Action taken by: <?php echo htmlspecialchars($leave['action_first'] . ' ' . $leave['action_last']); ?> 
                        on <?php echo date('M d, Y h:i A', strtotime($leave['action_date'])); ?>
                    </small>
                </div>
                <?php endif; ?>
                
                <?php if ($leave['status'] == 'Pending'): ?>
                <div class="mt-4 pt-4 border-top">
                    <h5 class="mb-3">Take Action</h5>
                    <div class="d-flex gap-3">
                        <a href="approve.php?action=approve&id=<?php echo $leave_id; ?>" class="btn btn-success btn-lg">
                            <i class="fas fa-check"></i> Approve Leave
                        </a>
                        <a href="approve.php?action=reject&id=<?php echo $leave_id; ?>" class="btn btn-danger btn-lg">
                            <i class="fas fa-times"></i> Reject Leave
                        </a>
                        <a href="manage.php" class="btn btn-secondary btn-lg">Back to List</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2 class="card-title">Employee's Recent Leave History</h2>
            </div>
            <div class="card-body">
                <?php if ($history_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th>Applied On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($history = $history_result->fetch_assoc()): 
                                    $hist_days = (new DateTime($history['start_date']))->diff(new DateTime($history['end_date']))->days + 1;
                                    $hist_status_class = 'status-' . strtolower($history['status']);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($history['leave_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($history['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($history['end_date'])); ?></td>
                                    <td><?php echo $hist_days; ?></td>
                                    <td><span class="status-badge <?php echo $hist_status_class; ?>"><?php echo $history['status']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($history['applied_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No previous leave records found for this employee.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>