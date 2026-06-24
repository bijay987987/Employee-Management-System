<?php
require_once '../../config.php';
requireEmployee();

$employee_id = $_SESSION['user_id'];

// Get leave history with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM leaves WHERE employee_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $employee_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Get leaves
$sql = "SELECT * FROM leaves WHERE employee_id = ? ORDER BY applied_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $employee_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Get leave statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'Approved' THEN DATEDIFF(end_date, start_date) + 1 ELSE 0 END) as approved_days
    FROM leaves 
    WHERE employee_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $employee_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave History - Employee</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/tables.css">
    <style>
        .history-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-box {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-top: 4px solid;
        }
        .stat-box.total { border-color: #3498db; }
        .stat-box.pending { border-color: #f39c12; }
        .stat-box.approved { border-color: #27ae60; }
        .stat-box.rejected { border-color: #e74c3c; }
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
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending { background-color: #f39c12; color: white; }
        .status-approved { background-color: #27ae60; color: white; }
        .status-rejected { background-color: #e74c3c; color: white; }
        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .page-item {
            margin: 0 5px;
        }
        .page-link {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            color: #007bff;
            text-decoration: none;
            display: block;
        }
        .page-link:hover {
            background: #f8f9fa;
        }
        .page-item.active .page-link {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">My Leave History</h1>
            <div class="breadcrumb">Leaves / History</div>
        </div>
        
        <div class="history-stats">
            <div class="stat-box total">
                <span class="stat-number"><?php echo $stats['total'] ?? 0; ?></span>
                <span class="stat-label">Total Applications</span>
            </div>
            <div class="stat-box pending">
                <span class="stat-number"><?php echo $stats['pending'] ?? 0; ?></span>
                <span class="stat-label">Pending</span>
            </div>
            <div class="stat-box approved">
                <span class="stat-number"><?php echo $stats['approved'] ?? 0; ?></span>
                <span class="stat-label">Approved</span>
            </div>
            <div class="stat-box rejected">
                <span class="stat-number"><?php echo $stats['rejected'] ?? 0; ?></span>
                <span class="stat-label">Rejected</span>
            </div>
            <div class="stat-box approved">
                <span class="stat-number"><?php echo $stats['approved_days'] ?? 0; ?></span>
                <span class="stat-label">Approved Days</span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title">Leave Applications</h2>
                <div>
                    <a href="apply.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Apply for Leave
                    </a>
                    <a href="status.php" class="btn btn-outline-info">
                        <i class="fas fa-chart-bar"></i> View Status
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Applied On</th>
                                <th>Status</th>
                                <th>Admin Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): 
                                $counter = $offset + 1;
                                while ($leave = $result->fetch_assoc()): 
                                    $days = (new DateTime($leave['start_date']))->diff(new DateTime($leave['end_date']))->days + 1;
                                    $status_class = '';
                                    switch ($leave['status']) {
                                        case 'Pending': $status_class = 'status-pending'; break;
                                        case 'Approved': $status_class = 'status-approved'; break;
                                        case 'Rejected': $status_class = 'status-rejected'; break;
                                    }
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></td>
                                <td><?php echo $days; ?></td>
                                <td><?php echo date('M d, Y', strtotime($leave['applied_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $leave['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($leave['admin_remarks'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="showRemarks('<?php echo addslashes($leave['admin_remarks']); ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($leave['status'] == 'Pending'): ?>
                                        <a href="edit.php?id=<?php echo $leave['id']; ?>" class="btn btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="cancel.php?id=<?php echo $leave['id']; ?>" class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to cancel this leave application?')" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="view.php?id=<?php echo $leave['id']; ?>" class="btn btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.5;"></i>
                                        <h4 class="mt-3">No leave applications found</h4>
                                        <p>You haven't applied for any leaves yet.</p>
                                        <a href="apply.php" class="btn btn-primary mt-2">
                                            <i class="fas fa-plus"></i> Apply for Your First Leave
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <div class="text-center text-muted mt-3">
                    Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $total_rows); ?> of <?php echo $total_rows; ?> entries
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script>
    function showRemarks(remarks) {
        Swal.fire({
            title: 'Admin Remarks',
            html: '<div class="text-start p-3" style="max-height: 300px; overflow-y: auto;">' + 
                  '<p>' + remarks.replace(/\n/g, '<br>') + '</p></div>',
            icon: 'info',
            confirmButtonText: 'Close',
            width: '600px'
        });
    }
    
    // Auto-dismiss alerts
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
    </script>
</body>
</html>