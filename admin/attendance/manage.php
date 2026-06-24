<?php
include '../../config.php';
requireAdmin();

// Handle filters
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_department = $_GET['department'] ?? '';

// Build query with filters
$sql = "SELECT a.*, 
        u.first_name, u.last_name, u.position,
        d.name as department_name
        FROM attendance a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN departments d ON u.department_id = d.id 
        WHERE a.date = ?";
$params = [$filter_date];
$types = "s";

if (!empty($filter_department)) {
    $sql .= " AND u.department_id = ?";
    $params[] = $filter_department;
    $types .= "i";
}

$sql .= " ORDER BY a.check_in DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get departments for filter
$dept_sql = "SELECT * FROM departments WHERE status = 'active'";
$dept_result = $conn->query($dept_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/tables.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Manage Attendance</h1>
            <div class="breadcrumb">Admin / Attendance / Manage</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Attendance Records</h2>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" action="" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" id="date" name="date" class="form-control" 
                                   value="<?php echo $filter_date; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="department" class="form-label">Department</label>
                            <select id="department" name="department" class="form-control">
                                <option value="">All Departments</option>
                                <?php while ($dept = $dept_result->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo $filter_department == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="manage.php" class="btn">Reset</a>
                        </div>
                    </div>
                </form>
                
                <!-- Attendance Table -->
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total Hours</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                        <br>
                                        <small><?php echo htmlspecialchars($row['position']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : 'N/A'; ?></td>
                                    <td><?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : 'N/A'; ?></td>
                                    <td><?php echo $row['total_hours'] ? number_format($row['total_hours'], 2) : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No attendance records found for the selected date.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Attendance Summary -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Attendance Summary - <?php echo date('F d, Y', strtotime($filter_date)); ?></h2>
            </div>
            <div class="card-body">
                <?php
                $summary_sql = "SELECT 
                    COUNT(*) as total_employees,
                    SUM(CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
                    FROM users u 
                    LEFT JOIN attendance a ON u.id = a.user_id AND a.date = ?
                    WHERE u.role = 'employee' AND u.status = 'active'";
                
                $stmt = $conn->prepare($summary_sql);
                $stmt->bind_param("s", $filter_date);
                $stmt->execute();
                $summary_result = $stmt->get_result();
                $summary = $summary_result->fetch_assoc();
                ?>
                
                <div class="stats-grid">
                    <div class="stat-item" style="background: #3498db;">
                        <div class="stat-number"><?php echo $summary['total_employees']; ?></div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                    
                    <div class="stat-item" style="background: #27ae60;">
                        <div class="stat-number"><?php echo $summary['present_count']; ?></div>
                        <div class="stat-label">Present</div>
                    </div>
                    
                    <div class="stat-item" style="background: #f39c12;">
                        <div class="stat-number"><?php echo $summary['late_count']; ?></div>
                        <div class="stat-label">Late</div>
                    </div>
                    
                    <div class="stat-item" style="background: #e74c3c;">
                        <div class="stat-number"><?php echo $summary['absent_count']; ?></div>
                        <div class="stat-label">Absent</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>