<?php
include '../../config.php';
requireEmployee();

$user_id = $_SESSION['user_id'];

// Handle filters
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_year = $_GET['year'] ?? date('Y');

// Build query with filters
$sql = "SELECT * FROM attendance 
        WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?
        ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $filter_year, date('m', strtotime($filter_month)));
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary
$summary_sql = "SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
    SUM(total_hours) as total_hours
    FROM attendance 
    WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?";
$stmt = $conn->prepare($summary_sql);
$stmt->bind_param("iss", $user_id, $filter_year, date('m', strtotime($filter_month)));
$stmt->execute();
$summary_result = $stmt->get_result();
$summary = $summary_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History - Employee</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/employee.css">
    <link rel="stylesheet" href="../../assets/css/tables.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Attendance History</h1>
            <div class="breadcrumb">Employee / Attendance / History</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">My Attendance Records</h2>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" action="" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="month" class="form-label">Month</label>
                            <input type="month" id="month" name="month" class="form-control" 
                                   value="<?php echo $filter_month; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="year" class="form-label">Year</label>
                            <select id="year" name="year" class="form-control">
                                <?php
                                $current_year = date('Y');
                                for ($year = $current_year; $year >= $current_year - 5; $year--):
                                ?>
                                    <option value="<?php echo $year; ?>" 
                                        <?php echo $filter_year == $year ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="history.php" class="btn">Reset</a>
                        </div>
                    </div>
                </form>
                
                <!-- Attendance Table -->
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total Hours</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo date('l', strtotime($row['date'])); ?></td>
                                    <td><?php echo $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : 'N/A'; ?></td>
                                    <td><?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : 'N/A'; ?></td>
                                    <td><?php echo $row['total_hours'] ? number_format($row['total_hours'], 2) : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No attendance records found for the selected period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Attendance Summary -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Monthly Summary - <?php echo date('F Y', strtotime($filter_month)); ?></h2>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-item" style="background: #3498db;">
                        <div class="stat-number"><?php echo $summary['total_days']; ?></div>
                        <div class="stat-label">Total Days</div>
                    </div>
                    
                    <div class="stat-item" style="background: #27ae60;">
                        <div class="stat-number"><?php echo $summary['present_days']; ?></div>
                        <div class="stat-label">Present Days</div>
                    </div>
                    
                    <div class="stat-item" style="background: #f39c12;">
                        <div class="stat-number"><?php echo $summary['late_days']; ?></div>
                        <div class="stat-label">Late Days</div>
                    </div>
                    
                    <div class="stat-item" style="background: #e74c3c;">
                        <div class="stat-number"><?php echo $summary['absent_days']; ?></div>
                        <div class="stat-label">Absent Days</div>
                    </div>
                    
                    <div class="stat-item" style="background: #9b59b6;">
                        <div class="stat-number"><?php echo number_format($summary['total_hours'] ?? 0, 1); ?></div>
                        <div class="stat-label">Total Hours</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>