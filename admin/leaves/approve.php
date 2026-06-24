<?php
require_once '../../config.php';
requireAdmin();

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: manage.php");
    exit();
}

$leave_id = intval($_GET['id']);
$action = $_GET['action'] === 'approve' ? 'approve' : 'reject';

// Get leave details with employee info
$sql = "SELECT l.*, u.first_name, u.last_name, u.email, u.position 
        FROM leaves l 
        JOIN users u ON l.employee_id = u.id 
        WHERE l.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Leave request not found!";
    header("Location: manage.php");
    exit();
}

$leave = $result->fetch_assoc();

// Calculate days
$start = new DateTime($leave['start_date']);
$end = new DateTime($leave['end_date']);
$interval = $start->diff($end);
$days = $interval->days + 1;

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $remarks = sanitize($_POST['remarks']);
    $status = ($action == 'approve') ? 'Approved' : 'Rejected';
    
    $update_sql = "UPDATE leaves SET status = ?, admin_remarks = ?, reviewed_at = NOW(), action_by = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssii", $status, $remarks, $_SESSION['user_id'], $leave_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Leave request has been " . ($action == 'approve' ? 'approved' : 'rejected') . " successfully!";
        header("Location: manage.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating leave request!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($action); ?> Leave - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
    <style>
        .leave-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .detail-item {
            margin-bottom: 10px;
            display: flex;
        }
        .detail-label {
            font-weight: 600;
            min-width: 120px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title"><?php echo ucfirst($action); ?> Leave Request</h1>
            <div class="breadcrumb">Admin / Leaves / <?php echo ucfirst($action); ?></div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Review Leave Request</h2>
                <span class="badge badge-<?php echo $action == 'approve' ? 'success' : 'danger'; ?>">
                    <?php echo strtoupper($action); ?> ACTION
                </span>
            </div>
            <div class="card-body">
                <div class="leave-details">
                    <h4 class="mb-3">Leave Request Details</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Employee:</span>
                                <span><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email:</span>
                                <span><?php echo htmlspecialchars($leave['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Position:</span>
                                <span><?php echo htmlspecialchars($leave['position']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Leave Type:</span>
                                <span><?php echo htmlspecialchars($leave['leave_type']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <span class="detail-label">Start Date:</span>
                                <span><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">End Date:</span>
                                <span><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Total Days:</span>
                                <span><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Applied On:</span>
                                <span><?php echo date('M d, Y h:i A', strtotime($leave['applied_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Reason:</span>
                        <span><?php echo nl2br(htmlspecialchars($leave['reason'])); ?></span>
                    </div>
                    
                    <?php if (!empty($leave['document'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">Attachment:</span>
                        <a href="../../uploads/leaves/<?php echo $leave['document']; ?>" target="_blank" class="btn btn-sm btn-info">
                            <i class="fas fa-paperclip"></i> View Document
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="remarks" class="form-label">
                            Remarks <?php echo $action == 'reject' ? '*' : '(Optional)'; ?>
                        </label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="4" 
                                  placeholder="Add your remarks or comments..." 
                                  <?php echo $action == 'reject' ? 'required' : ''; ?>>
                            <?php echo isset($_POST['remarks']) ? htmlspecialchars($_POST['remarks']) : ''; ?>
                        </textarea>
                        <?php if ($action == 'reject'): ?>
                        <small class="text-muted">Please provide a reason for rejection.</small>
                        <?php else: ?>
                        <small class="text-muted">Optional comments for the employee.</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-<?php echo $action == 'approve' ? 'success' : 'danger'; ?> btn-lg">
                            <i class="fas fa-<?php echo $action == 'approve' ? 'check' : 'times'; ?>"></i>
                            <?php echo ucfirst($action); ?> Leave Request
                        </button>
                        <a href="manage.php" class="btn btn-secondary btn-lg">Cancel</a>
                        <a href="review.php?id=<?php echo $leave_id; ?>" class="btn btn-info btn-lg">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="alert alert-info mt-4">
            <h5><i class="fas fa-info-circle"></i> Important Notes:</h5>
            <ul class="mb-0">
                <li>Once <?php echo $action == 'approve' ? 'approved' : 'rejected'; ?>, this action cannot be undone.</li>
                <li>The employee will receive a notification about this decision.</li>
                <li>For rejections, providing a clear reason is required.</li>
                <li>Check employee's leave balance before approval.</li>
            </ul>
        </div>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/forms.js"></script>
</body>
</html>