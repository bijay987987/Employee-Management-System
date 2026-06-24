<?php
include 'config.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: employee/dashboard.php");
    }
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role'] ?? ''); // Fixed: Added null coalescing operator
    
    if (empty($role)) {
        $error = "Please select your role";
    } else {
        $sql = "SELECT * FROM users WHERE email = ? AND role = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                
                if ($user['role'] == 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: employee/dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "Invalid email or password for selected role!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management System</title>
    <link rel="icon" href="images/favicon.ico">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="logo">
                <h1>Employee Management System</h1>
                <p>Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="role">Select Role</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="admin" <?php echo ($_POST['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="employee" <?php echo ($_POST['role'] ?? '') == 'employee' ? 'selected' : ''; ?>>Employee</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           placeholder="Enter your email address" value="<?php echo $_POST['email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required 
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary">Sign In</button>
                <div class="form-group" style="text-align: center; margin-top: 15px;">
    <a href="forgot_password.php" style="color: #3498db; text-decoration: none;">
        <span style="margin-right: 5px;">🔒</span> Forgot Password?
    </a>
</div>
            </form>
            
        
        </div>
    </div>
    <script src="assets/js/auth.js"></script>
</body>

</html>