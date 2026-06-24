<?php
include 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    
    // Check if email exists and user is active
    $sql = "SELECT id, email, first_name, last_name FROM users WHERE email = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Generate OTP (6 digits)
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
        
        // Invalidate any previous reset requests for this email
        $sql = "UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        // Save new reset request
        $sql = "INSERT INTO password_resets (email, otp, token, expires_at) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $email, $otp, $token, $expires_at);
        
        if ($stmt->execute()) {
            // Development mode: Log to console instead of sending email
            if (DEBUG_MODE) {
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
                
                echo "<script>";
                echo "console.log('%c=== SQL MAIL SIMULATION ===', 'background: #3498db; color: white; padding: 5px; border-radius: 3px;');";
                echo "console.log('%cPASSWORD RESET REQUEST', 'color: #e74c3c; font-weight: bold;');";
                echo "console.log('To: " . $user['first_name'] . " " . $user['last_name'] . " <" . $email . ">');";
                echo "console.log('Subject: Password Reset OTP');";
                echo "console.log('---');";
                echo "console.log('Hello " . $user['first_name'] . ",');";
                echo "console.log('You requested a password reset for your Employee Management System account.');";
                echo "console.log('Your OTP is: %c" . $otp . "%c', 'color: #27ae60; font-size: 18px; font-weight: bold;', '');";
                echo "console.log('This OTP is valid for " . OTP_EXPIRY_MINUTES . " minutes.');";
                echo "console.log('---');";
                echo "console.log('Alternatively, use this direct link:');";
                echo "console.log('" . $reset_link . "');";
                echo "console.log('%c===========================', 'background: #3498db; color: white; padding: 5px; border-radius: 3px;');";
                echo "</script>";
                
                // Also log to server error log
                error_log("SQL MAIL: Password reset OTP sent to " . $email . " - OTP: " . $otp);
            }
            
            // Store in session for verification
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_token'] = $token;
            $_SESSION['otp_sent_time'] = time();
            session_write_close();
            
            header("Location: verify_otp.php");
            exit();
        } else {
            $error = "Error generating reset request. Please try again.";
        }
    } else {
        $error = "No active account found with this email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Employee Management System</title>
    <link rel="icon" href="images/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <style>
        .forgot-password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        
    
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background-color: #fde8e8;
            border: 1px solid #fbd5d5;
            color: #e74c3c;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="forgot-password-container">
            <div class="logo" style="text-align: center; margin-bottom: 25px;">
                <h1 style="color: #3498db;">🔒 Reset Password</h1>
                <p>Enter your email to receive OTP</p>
            </div>
            
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>Error:</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Success:</strong> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           placeholder="Enter your registered email address" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                        <span style="margin-right: 8px;">📧</span> Send OTP
                    </button>
                </div>
            </form>
            
            <div class="back-to-login">
                <p>Remember your password? <a href="index.php">Back to Login</a></p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>