<?php
// ... your existing functions ...

// Function to get employee's leave statistics
function getEmployeeLeaveStats($employee_id) {
    global $conn;
    
    $sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
        FROM leaves 
        WHERE employee_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return array('total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0);
}

// Function to calculate working days (excluding weekends)
function calculateWorkingDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day'); // Include end date
    
    $workingDays = 0;
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    foreach ($period as $date) {
        // Check if it's a weekend (Saturday = 6, Sunday = 0)
        if ($date->format('N') < 6) {
            $workingDays++;
        }
    }
    
    return $workingDays;
}

// Function to check overlapping leave requests
function checkOverlappingLeave($employee_id, $start_date, $end_date, $exclude_id = 0) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM leaves 
            WHERE employee_id = ? 
            AND status IN ('Pending', 'Approved')
            AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?) 
                 OR (? BETWEEN start_date AND end_date) OR (? BETWEEN start_date AND end_date))
            AND id != ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssi", $employee_id, $start_date, $end_date, 
                      $start_date, $end_date, $start_date, $end_date, $exclude_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        return $data['count'] > 0;
    }
    
    return false;
}
?>