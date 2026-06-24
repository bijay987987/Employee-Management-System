<?php
include '../../config.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $department_id = sanitize($_POST['department_id']);
    $position = sanitize($_POST['position']);
    $phone = sanitize($_POST['phone']);
    
    // Check if username or email already exists
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Username or email already exists!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, first_name, last_name, role, department_id, position, phone) 
                VALUES (?, ?, ?, ?, ?, 'employee', ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssiss", $username, $email, $hashed_password, $first_name, $last_name, $department_id, $position, $phone);
        
        if ($stmt->execute()) {
            $success = "Employee added successfully!";
            $_POST = array(); // Clear form
        } else {
            $error = "Error adding employee: " . $conn->error;
        }
    }
}

// Get departments for dropdown
$dept_sql = "SELECT * FROM departments WHERE status = 'active'";
$dept_result = $conn->query($dept_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Add New Employee</h1>
            <div class="breadcrumb">Admin / Employees / Add</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Employee Information</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                   value="<?php echo $_POST['first_name'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                   value="<?php echo $_POST['last_name'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?php echo $_POST['username'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="department_id" class="form-label">Department</label>
                            <select id="department_id" name="department_id" class="form-control">
                                <option value="">Select Department</option>
                                <?php while ($dept = $dept_result->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo (($_POST['department_id'] ?? '') == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" id="position" name="position" class="form-control" 
                                   value="<?php echo $_POST['position'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo $_POST['phone'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                        <a href="manage.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/forms.js"></script>
</body>
</html>