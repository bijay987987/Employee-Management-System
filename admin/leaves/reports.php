<?php
require_once '../../config.php';
requireAdmin();

// Handle report filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build report query
$sql = "SELECT l.*, 
        u.first_name, u.last_name, u.email, u.position, u.department_id,
        d.name as department_name
        FROM leaves l 
        JOIN users u ON l.employee_id = u.id 
        LEFT JOIN departments d ON u.department_id = d.id 
        WHERE l.applied_at BETWEEN ? AND ?";

$params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
$types = "ss";

if ($department_id) {
    $sql .= " AND u.department_id = ?";
    $params[] = $department_id;
    $types .= "i";
}

if ($status && $status != 'all') {
    $sql .= " AND l.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY l.applied_at DESC";

// Execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get summary statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    AVG(DATEDIFF(end_date, start_date) + 1) as avg_days
    FROM leaves 
    WHERE applied_at BETWEEN ? AND ?";

if ($department_id) {
    $stats_sql .= " AND employee_id IN (SELECT id FROM users WHERE department_id = ?)";
}

$stats_stmt = $conn->prepare($stats_sql);
if ($department_id) {
    $stats_stmt->bind_param("ssi", $start_date, $end_date, $department_id);
} else {
    $stats_stmt->bind_param("ss", $start_date, $end_date);
}
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Get departments for filter
$depts_sql = "SELECT id, name FROM departments WHERE status = 'active' ORDER BY name";
$depts_result = $conn->query($depts_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Reports - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/tables.css">
    <style>
        .report-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .summary-item {
            text-align: center;
            padding: 15px;
        }
        .summary-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .summary-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .print-only {
            display: none;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Leave Reports</h1>
            <div class="breadcrumb">Admin / Leaves / Reports</div>
        </div>
        
        <div class="report-summary">
            <div class="row">
                <div class="col-md-3">
                    <div class="summary-item">
                        <div class="summary-number"><?php echo $stats['total'] ?? 0; ?></div>
                        <div class="summary-label">Total Requests</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-item">
                        <div class="summary-number"><?php echo $stats['pending'] ?? 0; ?></div>
                        <div class="summary-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-item">
                        <div class="summary-number"><?php echo $stats['approved'] ?? 0; ?></div>
                        <div class="summary-label">Approved</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-item">
                        <div class="summary-number"><?php echo round($stats['avg_days'] ?? 0, 1); ?></div>
                        <div class="summary-label">Avg. Days</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card no-print">
            <div class="card-header">
                <h2 class="card-title">Report Filters</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select class="form-control" name="department_id">
                            <option value="">All Departments</option>
                            <?php while ($dept = $depts_result->fetch_assoc()): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo $department_id == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status">
                            <option value="all" <?php echo $status == 'all' || $status == '' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Pending" <?php echo $status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo $status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo $status == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                        <button type="button" class="btn btn-success" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <a href="reports.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title">Leave Report Details</h2>
                <div class="no-print">
                    <span class="badge badge-info">
                        <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="print-only text-center mb-4">
                    <h2>Leave Management Report</h2>
                    <h4><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></h4>
                    <p>Generated on: <?php echo date('M d, Y h:i A'); ?></p>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Applied On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): 
                                $counter = 1;
                                while ($leave = $result->fetch_assoc()): 
                                    $days = (new DateTime($leave['start_date']))->diff(new DateTime($leave['end_date']))->days + 1;
                                    $status_class = '';
                                    switch ($leave['status']) {
                                        case 'Approved': $status_class = 'badge-success'; break;
                                        case 'Rejected': $status_class = 'badge-danger'; break;
                                        default: $status_class = 'badge-warning';
                                    }
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($leave['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></td>
                                <td><?php echo $days; ?></td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $leave['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($leave['applied_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No leave records found for the selected period.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4 no-print">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Summary</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td>Total Requests:</td>
                                        <td><strong><?php echo $stats['total'] ?? 0; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Pending:</td>
                                        <td><strong><?php echo $stats['pending'] ?? 0; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Approved:</td>
                                        <td><strong><?php echo $stats['approved'] ?? 0; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Rejected:</td>
                                        <td><strong><?php echo $stats['rejected'] ?? 0; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Average Days:</td>
                                        <td><strong><?php echo round($stats['avg_days'] ?? 0, 1); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Export Options</h5>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" onclick="exportToCSV()">
                                        <i class="fas fa-file-csv"></i> Export to CSV
                                    </button>
                                    <button class="btn btn-outline-success" onclick="window.print()">
                                        <i class="fas fa-print"></i> Print Report
                                    </button>
                                    <a href="reports.php?start_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-t'); ?>" 
                                       class="btn btn-outline-info">
                                        <i class="fas fa-calendar-alt"></i> This Month
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script>
    function exportToCSV() {
        // Simple CSV export
        let csv = [];
        let rows = document.querySelectorAll("table tr");
        
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            
            for (let j = 0; j < cols.length; j++) {
                // Remove badge HTML
                let text = cols[j].innerText.replace(/\n/g, " ").trim();
                row.push('"' + text + '"');
            }
            
            csv.push(row.join(","));        
        }
        
        let csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
        let encodedUri = encodeURI(csvContent);
        let link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "leave_report_<?php echo date('Y-m-d'); ?>.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    </script>
</body>
</html>