<?php
require_once '../../config.php';
requireEmployee();

$employee_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get employee details for leave balance
$emp_sql = "SELECT * FROM users WHERE id = ?";
$emp_stmt = $conn->prepare($emp_sql);
$emp_stmt->bind_param("i", $employee_id);
$emp_stmt->execute();
$emp_result = $emp_stmt->get_result();
$employee = $emp_result->fetch_assoc();

// Calculate remaining leaves (example logic)
$current_year = date('Y');
$used_leaves_sql = "SELECT SUM(DATEDIFF(end_date, start_date) + 1) as used_days 
                    FROM leaves 
                    WHERE employee_id = ? 
                    AND YEAR(start_date) = ? 
                    AND status = 'Approved' 
                    AND leave_type = 'Annual'";
$used_stmt = $conn->prepare($used_leaves_sql);
$used_stmt->bind_param("ii", $employee_id, $current_year);
$used_stmt->execute();
$used_result = $used_stmt->get_result();
$used_leaves = $used_result->fetch_assoc()['used_days'] ?? 0;

$total_allowed = 20; // Annual leaves per year
$remaining_leaves = $total_allowed - $used_leaves;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_type = sanitize($_POST['leave_type']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $reason = sanitize($_POST['reason']);
    $emergency_contact = sanitize($_POST['emergency_contact']);
    $contact_phone = sanitize($_POST['contact_phone']);
    
    // Validation
    if (empty($leave_type) || empty($start_date) || empty($end_date) || empty($reason)) {
        $error = "Please fill in all required fields!";
    } elseif (strtotime($start_date) > strtotime($end_date)) {
        $error = "End date cannot be before start date!";
    } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $error = "Start date cannot be in the past!";
    } else {
        // Calculate days
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $days = $start->diff($end)->days + 1;
        
        // Check leave balance for annual leave
        if ($leave_type == 'Annual' && $days > $remaining_leaves) {
            $error = "You only have $remaining_leaves annual leaves remaining. You requested $days days.";
        } else {
            // Handle file upload
            $document = '';
            if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
                $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (in_array($_FILES['document']['type'], $allowed_types) && $_FILES['document']['size'] <= $max_size) {
                    $ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
                    $document = 'leave_' . time() . '_' . $employee_id . '.' . $ext;
                    $upload_dir = '../../uploads/leaves/';
                    
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . $document);
                } else {
                    $error = "Invalid file type or size too large (max 5MB, PDF/JPEG/PNG only)";
                }
            }
            
            if (empty($error)) {
                $sql = "INSERT INTO leaves (employee_id, leave_type, start_date, end_date, reason, 
                        emergency_contact, contact_phone, document, applied_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssssss", $employee_id, $leave_type, $start_date, $end_date, 
                                 $reason, $emergency_contact, $contact_phone, $document);
                
                if ($stmt->execute()) {
                    $success = "Leave application submitted successfully! It will be reviewed by HR.";
                    $_POST = array(); // Clear form
                } else {
                    $error = "Error submitting leave application: " . $conn->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Leave - Employee</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
    <style>
        .leave-balance {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .balance-item {
            text-align: center;
            padding: 10px;
        }
        .balance-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .balance-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .leave-type-info {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .required::after {
            content: " *";
            color: #e74c3c;
        }
        .date-preview {
            background: #e8f4fd;
            border: 1px dashed #3498db;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Apply for Leave</h1>
            <div class="breadcrumb">Leaves / Apply</div>
        </div>
        
        <div class="leave-balance">
            <div class="row">
                <div class="col-md-4">
                    <div class="balance-item">
                        <div class="balance-number"><?php echo $total_allowed; ?></div>
                        <div class="balance-label">Annual Leaves Allowed</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="balance-item">
                        <div class="balance-number"><?php echo $used_leaves; ?></div>
                        <div class="balance-label">Leaves Used This Year</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="balance-item">
                        <div class="balance-number"><?php echo $remaining_leaves; ?></div>
                        <div class="balance-label">Remaining Leaves</div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Leave Application Form</h2>
                <small class="text-muted">Fields marked with * are required</small>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="leave_type" class="form-label required">Leave Type</label>
                                <select id="leave_type" name="leave_type" class="form-control" required>
                                    <option value="">Select Leave Type</option>
                                    <option value="Annual" <?php echo ($_POST['leave_type'] ?? '') == 'Annual' ? 'selected' : ''; ?>>Annual Leave</option>
                                    <option value="Sick" <?php echo ($_POST['leave_type'] ?? '') == 'Sick' ? 'selected' : ''; ?>>Sick Leave</option>
                                    <option value="Casual" <?php echo ($_POST['leave_type'] ?? '') == 'Casual' ? 'selected' : ''; ?>>Casual Leave</option>
                                    <option value="Maternity" <?php echo ($_POST['leave_type'] ?? '') == 'Maternity' ? 'selected' : ''; ?>>Maternity Leave</option>
                                    <option value="Paternity" <?php echo ($_POST['leave_type'] ?? '') == 'Paternity' ? 'selected' : ''; ?>>Paternity Leave</option>
                                    <option value="Bereavement" <?php echo ($_POST['leave_type'] ?? '') == 'Bereavement' ? 'selected' : ''; ?>>Bereavement Leave</option>
                                    <option value="Study" <?php echo ($_POST['leave_type'] ?? '') == 'Study' ? 'selected' : ''; ?>>Study Leave</option>
                                    <option value="Unpaid" <?php echo ($_POST['leave_type'] ?? '') == 'Unpaid' ? 'selected' : ''; ?>>Unpaid Leave</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="start_date" class="form-label required">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" 
                                       value="<?php echo $_POST['start_date'] ?? ''; ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date" class="form-label required">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" 
                                       value="<?php echo $_POST['end_date'] ?? ''; ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="date-preview" id="datePreview">
                                <strong>Leave Duration:</strong> <span id="daysCount">0</span> days
                                <br>
                                <small>From <span id="startPreview"></span> to <span id="endPreview"></span></small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reason" class="form-label required">Reason for Leave</label>
                                <textarea id="reason" name="reason" class="form-control" rows="4" 
                                          placeholder="Please provide details of why you need leave..." required><?php echo $_POST['reason'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="emergency_contact" class="form-label">Emergency Contact Person</label>
                                <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" 
                                       value="<?php echo $_POST['emergency_contact'] ?? ''; ?>" 
                                       placeholder="Name of contact person during your absence">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_phone" class="form-label">Emergency Contact Phone</label>
                                <input type="tel" id="contact_phone" name="contact_phone" class="form-control" 
                                       value="<?php echo $_POST['contact_phone'] ?? ''; ?>" 
                                       placeholder="Phone number for emergency contact">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="document" class="form-label">Supporting Document (Optional)</label>
                                <input type="file" id="document" name="document" class="form-control" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">Upload supporting documents like medical certificate (Max 5MB, PDF/JPEG/PNG)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="leave-type-info" id="leaveTypeInfo">
                        <h5><i class="fas fa-info-circle"></i> Leave Type Information</h5>
                        <p id="leaveDescription">Select a leave type to see details and requirements.</p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i> Submit Leave Application
                        </button>
                        <button type="reset" class="btn btn-secondary">Clear Form</button>
                        <a href="history.php" class="btn btn-outline-info">View Leave History</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2 class="card-title">Important Information</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-exclamation-triangle text-warning"></i> Please Note:</h5>
                        <ul>
                            <li>Submit leave applications at least 3 working days in advance</li>
                            <li>For sick leaves exceeding 3 days, medical certificate is required</li>
                            <li>Annual leaves must be approved at least 1 week in advance</li>
                            <li>Check with your manager before applying for long leaves</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-clock text-info"></i> Processing Time:</h5>
                        <ul>
                            <li>Normal processing: 1-2 working days</li>
                            <li>Urgent requests: Contact HR directly</li>
                            <li>You will receive email notification upon approval/rejection</li>
                            <li>Check status in "My Leaves" section</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/forms.js"></script>
    <script>
    // Leave type descriptions
    const leaveDescriptions = {
        'Annual': 'Paid annual vacation leave. Requires advance notice. Deducted from your annual leave balance.',
        'Sick': 'For medical reasons. Medical certificate required for leaves exceeding 3 days.',
        'Casual': 'For personal emergencies or short-term needs. Limited to 7 days per year.',
        'Maternity': 'For expecting mothers. Requires medical documentation. Up to 90 days.',
        'Paternity': 'For new fathers. Up to 14 days within 6 months of child birth.',
        'Bereavement': 'For family bereavement. Up to 5 days. Documentation may be required.',
        'Study': 'For educational purposes. Requires proof of enrollment and schedule.',
        'Unpaid': 'Leave without pay. Requires manager approval in advance.'
    };
    
    // Update leave type info
    document.getElementById('leave_type').addEventListener('change', function() {
        const selected = this.value;
        const infoDiv = document.getElementById('leaveTypeInfo');
        const descP = document.getElementById('leaveDescription');
        
        if (selected && leaveDescriptions[selected]) {
            descP.textContent = leaveDescriptions[selected];
            infoDiv.style.display = 'block';
        } else {
            infoDiv.style.display = 'none';
        }
    });
    
    // Calculate days between dates
    function calculateDays() {
        const start = document.getElementById('start_date').value;
        const end = document.getElementById('end_date').value;
        const preview = document.getElementById('datePreview');
        
        if (start && end) {
            const startDate = new Date(start);
            const endDate = new Date(end);
            
            // Check if end date is after start date
            if (endDate >= startDate) {
                const diffTime = Math.abs(endDate - startDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                document.getElementById('startPreview').textContent = formatDate(startDate);
                document.getElementById('endPreview').textContent = formatDate(endDate);
                document.getElementById('daysCount').textContent = diffDays;
                preview.style.display = 'block';
                
                // Check annual leave balance
                const leaveType = document.getElementById('leave_type').value;
                if (leaveType === 'Annual' && diffDays > <?php echo $remaining_leaves; ?>) {
                    preview.style.borderColor = '#e74c3c';
                    preview.style.background = '#fde8e8';
                    preview.innerHTML += `<br><small class="text-danger">Warning: You only have <?php echo $remaining_leaves; ?> annual leaves remaining!</small>`;
                } else {
                    preview.style.borderColor = '#3498db';
                    preview.style.background = '#e8f4fd';
                }
            }
        } else {
            preview.style.display = 'none';
        }
    }
    
    function formatDate(date) {
        return date.toLocaleDateString('en-US', { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }
    
    document.getElementById('start_date').addEventListener('change', calculateDays);
    document.getElementById('end_date').addEventListener('change', calculateDays);
    
    // Set min end date based on start date
    document.getElementById('start_date').addEventListener('change', function() {
        document.getElementById('end_date').min = this.value;
        calculateDays();
    });
    
    // Trigger leave type info on page load if already selected
    document.addEventListener('DOMContentLoaded', function() {
        const leaveType = document.getElementById('leave_type');
        if (leaveType.value) {
            leaveType.dispatchEvent(new Event('change'));
        }
    });
    </script>
</body>
</html>