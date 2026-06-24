<?php
include '../../config.php';
requireEmployee();

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$message = '';
$error = '';

// Check if already checked in today
$check_sql = "SELECT * FROM attendance WHERE user_id = ? AND date = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$attendance_today = $stmt->get_result()->fetch_assoc();

// Handle check-in
if (isset($_POST['check_in']) && !$attendance_today) {
    $check_in_time = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO attendance (user_id, check_in, date, status) VALUES (?, ?, ?, 'present')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $check_in_time, $today);
    
    if ($stmt->execute()) {
        $message = "Checked in successfully at " . date('h:i A', strtotime($check_in_time));
        $attendance_today = ['check_in' => $check_in_time, 'check_out' => null];
    } else {
        $error = "Error checking in!";
    }
}

// Handle check-out
if (isset($_POST['check_out']) && $attendance_today && !$attendance_today['check_out']) {
    $check_out_time = date('Y-m-d H:i:s');
    
    // Calculate total hours
    $check_in = new DateTime($attendance_today['check_in']);
    $check_out = new DateTime($check_out_time);
    $diff = $check_in->diff($check_out);
    $total_hours = $diff->h + ($diff->i / 60);
    
    $sql = "UPDATE attendance SET check_out = ?, total_hours = ? WHERE user_id = ? AND date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdis", $check_out_time, $total_hours, $user_id, $today);
    
    if ($stmt->execute()) {
        $message = "Checked out successfully at " . date('h:i A', strtotime($check_out_time));
        $attendance_today['check_out'] = $check_out_time;
    } else {
        $error = "Error checking out!";
    }
}

// Get attendance history
$history_sql = "SELECT * FROM attendance WHERE user_id = ? ORDER BY date DESC LIMIT 10";
$stmt = $conn->prepare($history_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Employee</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/employee.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Attendance</h1>
            <div class="breadcrumb">Employee / Attendance / Check-in</div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="attendance-container">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Today's Attendance</h2>
                </div>
                <div class="card-body">
                    <div class="attendance-status">
                        <div class="status-item">
                            <span class="status-label">Date:</span>
                            <span class="status-value"><?php echo date('F d, Y'); ?></span>
                        </div>
                        
                        <div class="status-item">
                            <span class="status-label">Check-in:</span>
                            <span class="status-value">
                                <?php echo $attendance_today && $attendance_today['check_in'] ? 
                                    date('h:i A', strtotime($attendance_today['check_in'])) : 'Not checked in'; ?>
                            </span>
                        </div>
                        
                        <div class="status-item">
                            <span class="status-label">Check-out:</span>
                            <span class="status-value">
                                <?php echo $attendance_today && $attendance_today['check_out'] ? 
                                    date('h:i A', strtotime($attendance_today['check_out'])) : 'Not checked out'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="attendance-actions">
                        <?php if (!$attendance_today): ?>
                            <form method="POST" action="">
                                <button type="submit" name="check_in" class="btn btn-success btn-lg">Check In</button>
                            </form>
                        <?php elseif (!$attendance_today['check_out']): ?>
                            <form method="POST" action="">
                                <button type="submit" name="check_out" class="btn btn-danger btn-lg">Check Out</button>
                            </form>
                        <?php else: ?>
                            <p class="text-center">You have completed today's attendance.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Attendance History</h2>
                    <a href="history.php" class="btn btn-primary">View Full History</a>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Total Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($history_result->num_rows > 0): ?>
                                <?php while ($row = $history_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                        <td><?php echo $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : 'N/A'; ?></td>
                                        <td><?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : 'N/A'; ?></td>
                                        <td><?php echo $row['total_hours'] ? number_format($row['total_hours'], 2) : 'N/A'; ?></td>
                                        <td>
                                            <span class="badge status-<?php echo $row['status']; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No attendance records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/attendance.js"></script>
</body>
</html>