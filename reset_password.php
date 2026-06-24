<?php
include 'config.php';

// Check if user has valid reset session
if (!isset($_SESSION['password_reset_token']) || !isset($_SESSION['password_reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['password_reset_email'];
$error = '';
$success = '';

// Check if token is still valid in database
$sql = "SELECT * FROM password_resets 
        WHERE email = ? AND used = 1 
        AND expires_at > NOW() 
        ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Token expired or invalid
    unset($_SESSION['password_reset_token']);
    unset($_SESSION['password_reset_email']);
    header("Location: forgot_password.php?error=expired");
    exit();
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user's password
        $sql = "UPDATE users SET password = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Log password reset in development mode
            if (DEBUG_MODE) {
                echo "<script>";
                echo "console.log('%c=== PASSWORD RESET SUCCESS ===', 'background: #27ae60; color: white; padding: 5px; border-radius: 3px;');";
                echo "console.log('Password updated successfully for: " . $email . "');";
                echo "console.log('Time: " . date('Y-m-d H:i:s') . "');";
                echo "console.log('%c==============================', 'background: #27ae60; color: white; padding: 5px; border-radius: 3px;');";
                echo "</script>";
                
                error_log("PASSWORD RESET: Password updated for " . $email);
            }
            
            // Clear all reset sessions and database entries
            unset($_SESSION['password_reset_token']);
            unset($_SESSION['password_reset_email']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_token']);
            unset($_SESSION['otp_sent_time']);
            
            // Mark all reset requests for this email as used
            $sql = "UPDATE password_resets SET used = 1 WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            $success = "Password reset successfully! You can now login with your new password.";
            
            // Redirect to login after 3 seconds
            header("refresh:3;url=index.php");
        } else {
            $error = "Error updating password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Employee Management System</title>
    <link rel="icon" href="images/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <style>
        .reset-password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .password-strength {
            margin-top: 5px;
            height: 5px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        
        .strength-weak { background: #e74c3c; width: 33%; }
        .strength-medium { background: #f39c12; width: 66%; }
        .strength-strong { background: #27ae60; width: 100%; }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message i {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="reset-password-container">
            <div class="logo" style="text-align: center; margin-bottom: 25px;">
                <h1 style="color: #3498db;">🔄 Set New Password</h1>
                <p>Create a new password for your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="background: #fde8e8; color: #e74c3c; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <div style="font-size: 48px; color: #27ae60; margin-bottom: 15px;">✓</div>
                    <h3 style="margin-top: 0; color: #155724;">Password Reset Successful!</h3>
                    <p><?php echo $success; ?></p>
                    <p>Redirecting to login page...</p>
                    <div style="margin-top: 20px;">
                        <a href="index.php" class="btn btn-primary">Login Now</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" action="" id="resetForm">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" required 
                               placeholder="Enter new password" oninput="checkPasswordStrength()">
                        <div class="password-strength">
                            <div class="strength-meter" id="strengthMeter"></div>
                        </div>
                        <div class="password-requirements">
                            • At least 6 characters long<br>
                            • Include letters and numbers
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                               placeholder="Confirm new password" oninput="checkPasswordMatch()">
                        <div id="passwordMatch" style="font-size: 12px; margin-top: 5px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                            <span style="margin-right: 8px;">🔒</span> Reset Password
                        </button>
                    </div>
                </form>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="index.php">← Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const meter = document.getElementById('strengthMeter');
            
            // Reset classes
            meter.className = 'strength-meter';
            
            if (password.length === 0) {
                meter.style.width = '0%';
                return;
            }
            
            let strength = 0;
            
            // Length check
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            
            // Complexity checks
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            // Update meter
            if (strength <= 2) {
                meter.className = 'strength-meter strength-weak';
            } else if (strength <= 4) {
                meter.className = 'strength-meter strength-medium';
            } else {
                meter.className = 'strength-meter strength-strong';
            }
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                matchDiv.style.color = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = '✓ Passwords match';
                matchDiv.style.color = '#27ae60';
            } else {
                matchDiv.innerHTML = '✗ Passwords do not match';
                matchDiv.style.color = '#e74c3c';
            }
        }
        
        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>