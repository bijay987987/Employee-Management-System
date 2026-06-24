<?php
require_once '../../config.php';
requireAdmin(); // Or whatever your admin check function is

// Handle filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build query
$sql = "SELECT l.*, u.first_name, u.last_name, u.email 
        FROM leaves l 
        JOIN users u ON l.employee_id = u.id 
        WHERE 1=1";

$params = [];
$types = "";

// Add status filter
if ($filter_status && $filter_status != 'all') {
    $sql .= " AND l.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// Add search filter
if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR l.leave_type LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql .= " ORDER BY l.applied_at DESC";

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM leaves";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leave Requests - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <!-- SAME CSS FILES AS DEPARTMENT PAGES -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/tables.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background-color: #f39c12; color: white; }
        .status-approved { background-color: #27ae60; color: white; }
        .status-rejected { background-color: #e74c3c; color: white; }
        .leave-duration {
            font-size: 12px;
            color: #7f8c8d;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .stat-card {
            border-left: 4px solid;
        }
        .stat-card.total { border-left-color: #3498db; }
        .stat-card.pending { border-left-color: #f39c12; }
        .stat-card.approved { border-left-color: #27ae60; }
        .stat-card.rejected { border-left-color: #e74c3c; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Manage Leave Requests</h1>
            <div class="breadcrumb">Admin / Leaves / Manage</div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="dashboard-stats">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i>📊</i>
                </div>
                <div class="stat-info">
                    <h3>Total Requests</h3>
                    <span class="stat-number"><?php echo $stats['total'] ?? 0; ?></span>
                </div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i>⏳</i>
                </div>
                <div class="stat-info">
                    <h3>Pending</h3>
                    <span class="stat-number"><?php echo $stats['pending'] ?? 0; ?></span>
                </div>
            </div>
            
            <div class="stat-card approved">
                <div class="stat-icon">
                    <i>✅</i>
                </div>
                <div class="stat-info">
                    <h3>Approved</h3>
                    <span class="stat-number"><?php echo $stats['approved'] ?? 0; ?></span>
                </div>
            </div>
            
            <div class="stat-card rejected">
                <div class="stat-icon">
                    <i>❌</i>
                </div>
                <div class="stat-info">
                    <h3>Rejected</h3>
                    <span class="stat-number"><?php echo $stats['rejected'] ?? 0; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Filter & Search</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="all" <?php echo $filter_status == 'all' || $filter_status == '' ? 'selected' : ''; ?>>All Status</option>
                                <option value="Pending" <?php echo $filter_status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo $filter_status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo $filter_status == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by employee name or leave type..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="manage.php" class="btn btn-secondary">Reset Filters</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Main Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title">All Leave Requests</h2>
                <div>
                    <a href="reports.php" class="btn btn-primary">View Reports</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Applied On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): 
                                $counter = 1;
                                while ($leave = $result->fetch_assoc()): 
                                    // Calculate days between dates
                                    $start = new DateTime($leave['start_date']);
                                    $end = new DateTime($leave['end_date']);
                                    $interval = $start->diff($end);
                                    $days = $interval->days + 1; // Include both start and end dates
                                    
                                    // Status badge class
                                    $status_class = '';
                                    switch ($leave['status']) {
                                        case 'Pending': $status_class = 'status-pending'; break;
                                        case 'Approved': $status_class = 'status-approved'; break;
                                        case 'Rejected': $status_class = 'status-rejected'; break;
                                    }
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></div>
                                    <small class="text-muted"><?php echo $leave['email']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                <td>
                                    <div><?php echo date('M d, Y', strtotime($leave['start_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($leave['end_date'])); ?></div>
                                    <small class="leave-duration">(<?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?>)</small>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($leave['applied_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $leave['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- Review Button -->
                                        <a href="review.php?id=<?php echo $leave['id']; ?>" 
                                           class="btn btn-sm btn-info" 
                                           title="Review Details">
                                            <i>👁️</i> Review
                                        </a>
                                        
                                        <?php if ($leave['status'] == 'Pending'): ?>
                                        <!-- Quick Approve Button -->
                                        <a href="approve.php?action=approve&id=<?php echo $leave['id']; ?>" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Are you sure you want to APPROVE this leave request?')"
                                           title="Quick Approve">
                                            <i>✓</i> Approve
                                        </a>
                                        
                                        <!-- Quick Reject Button -->
                                        <a href="approve.php?action=reject&id=<?php echo $leave['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to REJECT this leave request?')"
                                           title="Quick Reject">
                                            <i>✗</i> Reject
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i style="font-size: 48px; opacity: 0.5;">📭</i>
                                        <h4 class="mt-3">No leave requests found</h4>
                                        <p><?php echo $filter_status || $search ? 'Try changing your filters' : 'No employees have applied for leave yet.'; ?></p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    
    <!-- SAME JS FILES AS DEPARTMENT PAGES -->
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/forms.js"></script>
</body>
</html>