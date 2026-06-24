<?php
require_once '../../config.php';
requireEmployee();

$employee_id = $_SESSION['user_id'];

// Get leave status summary
$current_year = date('Y');

// Monthly breakdown
$monthly_sql = "SELECT 
    MONTH(start_date) as month,
    COUNT(*) as count,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM leaves 
    WHERE employee_id = ? AND YEAR(start_date) = ?
    GROUP BY MONTH(start_date)
    ORDER BY month";
$monthly_stmt = $conn->prepare($monthly_sql);
$monthly_stmt->bind_param("ii", $employee_id, $current_year);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();

// Get recent status updates
$recent_sql = "SELECT * FROM leaves 
              WHERE employee_id = ? 
              ORDER BY COALESCE(reviewed_at, applied_at) DESC 
              LIMIT 10";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("i", $employee_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();

// Get approval rate
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    AVG(DATEDIFF(COALESCE(reviewed_at, NOW()), applied_at)) as avg_processing_days
    FROM leaves 
    WHERE employee_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $employee_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

$approval_rate = $stats['total'] > 0 ? ($stats['approved'] / $stats['total']) * 100 : 0;

// Store monthly data in array
$monthly_data = [];
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_data[$row['month']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Status - Employee</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/tables.css">
    <style>
        .status-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .overview-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-top: 4px solid;
        }
        .overview-total { border-color: #3498db; }
        .overview-approved { border-color: #27ae60; }
        .overview-pending { border-color: #f39c12; }
        .overview-rejected { border-color: #e74c3c; }
        .overview-number {
            font-size: 36px;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }
        .overview-label {
            font-size: 16px;
            color: #666;
        }
        .progress-circle {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
            position: relative;
        }
        .circle-bg {
            fill: none;
            stroke: #e0e0e0;
            stroke-width: 8;
        }
        .circle-progress {
            fill: none;
            stroke-width: 8;
            stroke-linecap: round;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
            transition: stroke-dasharray 0.5s ease;
        }
        .circle-text {
            font-size: 20px;
            font-weight: 700;
            fill: #333;
        }
        .monthly-chart {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .chart-bar {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .chart-month {
            width: 80px;
            font-weight: 600;
            color: #333;
        }
        .chart-bars {
            flex: 1;
            display: flex;
            align-items: center;
            height: 30px;
        }
        .chart-approved {
            background: #27ae60;
            height: 100%;
            border-radius: 4px 0 0 4px;
        }
        .chart-pending {
            background: #f39c12;
            height: 100%;
        }
        .chart-rejected {
            background: #e74c3c;
            height: 100%;
            border-radius: 0 4px 4px 0;
        }
        .chart-count {
            width: 50px;
            text-align: right;
            font-weight: 600;
            color: #333;
        }
        .status-timeline {
            position: relative;
            padding-left: 30px;
        }
        .status-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        .timeline-item:last-child {
            border-bottom: none;
        }
        .timeline-dot {
            position: absolute;
            left: -25px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .timeline-approved { background: #27ae60; }
        .timeline-pending { background: #f39c12; }
        .timeline-rejected { background: #e74c3c; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Leave Status & Analytics</h1>
            <div class="breadcrumb">Leaves / Status</div>
        </div>
        
        <div class="status-overview">
            <div class="overview-card overview-total">
                <span class="overview-number"><?php echo $stats['total'] ?? 0; ?></span>
                <span class="overview-label">Total Applications</span>
            </div>
            
            <div class="overview-card overview-approved">
                <span class="overview-number"><?php echo $stats['approved'] ?? 0; ?></span>
                <span class="overview-label">Approved</span>
            </div>
            
            <div class="overview-card overview-pending">
                <span class="overview-number"><?php echo $stats['pending'] ?? 0; ?></span>
                <span class="overview-label">Pending</span>
            </div>
            
            <div class="overview-card overview-rejected">
                <span class="overview-number"><?php echo $stats['rejected'] ?? 0; ?></span>
                <span class="overview-label">Rejected</span>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Approval Statistics</h2>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="progress-circle">
                                    <svg width="100" height="100" viewBox="0 0 100 100">
                                        <circle class="circle-bg" cx="50" cy="50" r="40"></circle>
                                        <circle class="circle-progress" cx="50" cy="50" r="40" 
                                                stroke="#27ae60"
                                                stroke-dasharray="<?php echo $approval_rate * 2.513; ?>, 251.3"></circle>
                                        <text class="circle-text" x="50" y="55" text-anchor="middle">
                                            <?php echo round($approval_rate); ?>%
                                        </text>
                                    </svg>
                                </div>
                                <div class="text-center mt-3">
                                    <h5>Approval Rate</h5>
                                    <p class="text-muted"><?php echo $stats['approved'] ?? 0; ?> out of <?php echo $stats['total'] ?? 0; ?> applications approved</p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mt-4">
                                    <h5>Processing Time</h5>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <i class="fas fa-clock fa-2x text-info"></i>
                                        </div>
                                        <div>
                                            <h3 class="mb-0"><?php echo round($stats['avg_processing_days'] ?? 0, 1); ?> days</h3>
                                            <small class="text-muted">Average processing time</small>
                                        </div>
                                    </div>
                                    
                                    <h5 class="mt-4">Current Year (<?php echo $current_year; ?>)</h5>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <div class="h4 mb-1"><?php echo $stats['approved'] ?? 0; ?></div>
                                                <small class="text-muted">Approved</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <div class="h4 mb-1"><?php echo $stats['pending'] ?? 0; ?></div>
                                                <small class="text-muted">Pending</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <div class="h4 mb-1"><?php echo $stats['rejected'] ?? 0; ?></div>
                                                <small class="text-muted">Rejected</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="card-title">Monthly Breakdown - <?php echo $current_year; ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="monthly-chart">
                            <?php 
                            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                            
                            for ($i = 1; $i <= 12; $i++): 
                                $data = $monthly_data[$i] ?? ['count' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
                                $approved_pct = $data['count'] > 0 ? ($data['approved'] / $data['count']) * 100 : 0;
                                $pending_pct = $data['count'] > 0 ? ($data['pending'] / $data['count']) * 100 : 0;
                                $rejected_pct = $data['count'] > 0 ? ($data['rejected'] / $data['count']) * 100 : 0;
                            ?>
                            <div class="chart-bar">
                                <div class="chart-month"><?php echo $months[$i-1]; ?></div>
                                <div class="chart-bars">
                                    <?php if ($data['approved'] > 0): ?>
                                    <div class="chart-approved" style="width: <?php echo $approved_pct; ?>%"
                                         title="Approved: <?php echo $data['approved']; ?>"></div>
                                    <?php endif; ?>
                                    <?php if ($data['pending'] > 0): ?>
                                    <div class="chart-pending" style="width: <?php echo $pending_pct; ?>%"
                                         title="Pending: <?php echo $data['pending']; ?>"></div>
                                    <?php endif; ?>
                                    <?php if ($data['rejected'] > 0): ?>
                                    <div class="chart-rejected" style="width: <?php echo $rejected_pct; ?>%"
                                         title="Rejected: <?php echo $data['rejected']; ?>"></div>
                                    <?php endif; ?>
                                </div>
                                <div class="chart-count"><?php echo $data['count']; ?></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Recent Status Updates</h2>
                    </div>
                    <div class="card-body">
                        <div class="status-timeline">
                            <?php if ($recent_result->num_rows > 0): 
                                while ($leave = $recent_result->fetch_assoc()): 
                                    if (!empty($leave['start_date']) && !empty($leave['end_date'])) {
                                        $days = (new DateTime($leave['start_date']))->diff(new DateTime($leave['end_date']))->days + 1;
                                    } else {
                                        $days = 0;
                                    }
                                    $dot_class = 'timeline-' . strtolower($leave['status']);
                            ?>
                            <div class="timeline-item">
                                <div class="timeline-dot <?php echo $dot_class; ?>"></div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($leave['leave_type'] ?? 'N/A'); ?></h6>
                                <p class="mb-1">
                                    <small class="text-muted">
                                        <?php echo !empty($leave['start_date']) ? date('M d', strtotime($leave['start_date'])) : 'N/A'; ?> - 
                                        <?php echo !empty($leave['end_date']) ? date('M d', strtotime($leave['end_date'])) : 'N/A'; ?>
                                        (<?php echo $days; ?> days)
                                    </small>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-<?php 
                                        echo $leave['status'] == 'Approved' ? 'success' : 
                                             ($leave['status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                        <?php echo $leave['status']; ?>
                                    </span>
                                    <small class="text-muted">
                                        <?php 
                                        if (!empty($leave['reviewed_at'])) {
                                            echo date('M d', strtotime($leave['reviewed_at']));
                                        } else {
                                            echo date('M d', strtotime($leave['applied_at']));
                                        }
                                        ?>
                                    </small>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <p>No leave applications yet</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="history.php" class="btn btn-outline-primary btn-sm">
                                View Full History
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="card-title">Quick Tips</h2>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6>Improve Approval Chances:</h6>
                            <ul class="mb-0">
                                <li>Apply at least 3 days in advance</li>
                                <li>Provide clear reason for leave</li>
                                <li>Attach supporting documents</li>
                                <li>Check company holiday calendar</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6>Avoid Rejections:</h6>
                            <ul class="mb-0">
                                <li>Don't apply during peak periods</li>
                                <li>Ensure proper handover</li>
                                <li>Follow company leave policy</li>
                                <li>Coordinate with team members</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="card-title">Need Help?</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="apply.php" class="btn btn-primary">
                                How to Apply
                            </a>
                            <a href="policy.php" class="btn btn-outline-info">
                                Leave Policy
                            </a>
                            <button class="btn btn-outline-warning" onclick="contactHR()">
                                Contact HR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script>
    function contactHR() {
        alert('Contact HR Department:\n\nEmail: hr@company.com\nPhone: +1 (555) 123-4567\nOffice Hours: 9:00 AM - 5:00 PM (Mon-Fri)\nLocation: HR Department, 3rd Floor');
    }
    
    // Animate progress circle
    document.addEventListener('DOMContentLoaded', function() {
        const progressCircle = document.querySelector('.circle-progress');
        if (progressCircle) {
            const dashArray = progressCircle.getAttribute('stroke-dasharray').split(',')[0];
            progressCircle.style.strokeDasharray = `0, 251.3`;
            
            setTimeout(() => {
                progressCircle.style.transition = 'stroke-dasharray 1.5s ease';
                progressCircle.style.strokeDasharray = `${dashArray}, 251.3`;
            }, 300);
        }
    });
    </script>
</body>
</html>