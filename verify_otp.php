<?php

include 'config.php';

// DEBUG: Check what's in session
error_log("=== VERIFY OTP PAGE LOADED ===");
error_log("Session ID: " . session_id());
error_log("All session data: " . print_r($_SESSION, true));

// Check if we have the required session data
if (empty($_SESSION['reset_email'])) {
    error_log("ERROR: No reset_email in session. Redirecting...");
    header("Location: forgot_password.php?error=session_expired");
    exit();
}

$email = $_SESSION['reset_email'];
error_log("Email from session: " . $email);

$error = '';
$success = '';

// Get OTP from database for this email
$sql = "SELECT otp, token, expires_at, used FROM password_resets 
        WHERE email = ? AND used = 0 
        ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$db_otp = '';
$db_token = '';
$db_expires = '';
$db_used = 0;

if ($result->num_rows > 0) {
    $otp_data = $result->fetch_assoc();
    $db_otp = $otp_data['otp'];
    $db_token = $otp_data['token'];
    $db_expires = $otp_data['expires_at'];
    $db_used = $otp_data['used'];
    
    error_log("Database OTP: $db_otp, Token: " . substr($db_token, 0, 20) . "...");
} else {
    error_log("No OTP found in database for $email");
    $error = "No OTP found. Please request a new one.";
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("=== OTP SUBMISSION STARTED ===");
    error_log("POST data: " . print_r($_POST, true));
    
    if (isset($_POST['otp']) && !empty($_POST['otp'])) {
        $user_otp = trim($_POST['otp']);
        error_log("User entered OTP: $user_otp");
        error_log("Expected OTP: $db_otp");
        
        // Verify OTP
        if ($user_otp === $db_otp) {
            error_log("OTP MATCHES!");
            
            // Check if OTP is already used
            if ($db_used == 1) {
                $error = "This OTP has already been used.";
                error_log("OTP already used");
            }
            // Check if OTP is expired
            elseif (strtotime($db_expires) < time()) {
                $error = "OTP has expired.";
                error_log("OTP expired at: $db_expires");
            }
            // All checks passed
            else {
                // Mark OTP as used
                $sql = "UPDATE password_resets SET used = 1 WHERE email = ? AND otp = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $email, $db_otp);
                
                if ($stmt->execute()) {
                    error_log("OTP marked as used successfully");
                    
                    // Generate new reset token
                    $new_token = bin2hex(random_bytes(32));
                    
                    // Save token in database
                    $token_sql = "INSERT INTO password_resets (email, otp, token, expires_at, used) 
                                 VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), 1)";
                    $token_stmt = $conn->prepare($token_sql);
                    $token_stmt->bind_param("sss", $email, $db_otp, $new_token);
                    $token_stmt->execute();
                    
                    // Set new session variables
                    $_SESSION['password_reset_email'] = $email;
                    $_SESSION['password_reset_token'] = $new_token;
                    
                    // Force save session before redirect
                    session_write_close();
                    
                    error_log("Redirecting to reset_password.php");
                    header("Location: reset_password.php");
                    exit();
                } else {
                    $error = "Database error. Please try again.";
                    error_log("Failed to update OTP: " . $conn->error);
                }
            }
        } else {
            $error = "Invalid OTP. Please try again.";
            error_log("OTP MISMATCH");
        }
    } else {
        $error = "Please enter the OTP.";
        error_log("Empty OTP submitted");
    }
}

// Calculate remaining time
$remaining_time = 0;
if (!empty($db_expires)) {
    $remaining_time = max(0, strtotime($db_expires) - time());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <link rel="icon" href="images/favicon.ico">
    <link rel="stylesheet" href="assets/css/auth.css">
    <style>
        body {
            font-family: Arial;
            background: #f5f6fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            width: 400px;
        }
        input[type="text"] {
            width: 100%;
            padding: 15px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 10px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background: #2980b9;
        }
        .error {
            background: #fde8e8;
            color: #e74c3c;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #fbd5d5;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
        }
        .email-display {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
            font-weight: bold;
            color: #2c3e50;
        }
        .resend-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .resend-btn {
            background: #f39c12;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .resend-btn:hover {
            background: #e67e22;
        }
        .resend-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        .timer {
            text-align: center;
            font-size: 14px;
            color: #e74c3c;
            margin: 10px 0;
            font-weight: bold;
        }
        .back-link {
            text-align: center;
            margin-top: 10px;
        }
        .back-link a {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 style="text-align: center; color: #3498db; margin-top: 0;">Verify OTP</h2>
        
        <div class="email-display">
            Email: <?php echo htmlspecialchars($email); ?>
        </div>
        
        <?php if (isset($_GET['resent']) && $_GET['resent'] == 1): ?>
            <div class="success">
                ✅ New OTP sent! Check console/logs for the code.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($db_otp) && $db_used == 0): ?>
            <div class="timer" id="timer">
                ⏰ Time remaining: <span id="countdown"><?php echo gmdate("i:s", $remaining_time); ?></span>
            </div>
            
            <form method="POST" action="" id="otpForm">
                <input type="text" 
                       name="otp" 
                       id="otpInput"
                       maxlength="6" 
                       pattern="[0-9]{6}" 
                       placeholder="Enter 6-digit OTP" 
                       required 
                       autofocus
                       oninput="this.value = this.value.replace(/\D/g, '').slice(0, 6)">
                
                <button type="submit">Verify OTP</button>
            </form>
            
            <div class="resend-section">
                <form method="POST" action="resend_otp.php" id="resendForm">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <button type="submit" name="resend" class="resend-btn" id="resendBtn" <?php echo ($remaining_time > 60) ? 'disabled' : ''; ?>>
                        <?php echo ($remaining_time > 60) ? 'Resend OTP (Wait)' : 'Resend OTP'; ?>
                    </button>
                </form>
                <div id="resendTimer" class="timer" style="<?php echo ($remaining_time <= 60) ? 'display:none;' : ''; ?>">
                    Can resend in: <span id="resendCountdown"><?php echo gmdate("i:s", max(0, $remaining_time - 60)); ?></span>
                </div>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #e74c3c;">
                No valid OTP found. Please request a new one.
            </p>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="forgot_password.php">← Request New OTP</a>
        </div>
        
    
    </div>
    
    <script>
        // Main timer
        let remainingTime = <?php echo $remaining_time; ?>;
        const countdownElement = document.getElementById('countdown');
        const timerContainer = document.getElementById('timer');
        
        // Resend timer (60 seconds cooldown)
        let resendCooldown = Math.max(0, <?php echo $remaining_time; ?> - 60);
        const resendCountdownElement = document.getElementById('resendCountdown');
        const resendTimerContainer = document.getElementById('resendTimer');
        const resendBtn = document.getElementById('resendBtn');
        
        function updateTimers() {
            // Update main timer
            if (remainingTime > 0) {
                remainingTime--;
                const minutes = Math.floor(remainingTime / 60);
                const seconds = remainingTime % 60;
                countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Change color when less than 1 minute
                if (remainingTime < 60) {
                    countdownElement.style.color = '#e74c3c';
                }
            } else {
                countdownElement.textContent = "00:00";
                countdownElement.style.color = '#e74c3c';
            }
            
            // Update resend cooldown
            if (resendCooldown > 0) {
                resendCooldown--;
                const resendMinutes = Math.floor(resendCooldown / 60);
                const resendSeconds = resendCooldown % 60;
                resendCountdownElement.textContent = `${resendMinutes.toString().padStart(2, '0')}:${resendSeconds.toString().padStart(2, '0')}`;
                
                // Show/hide resend timer
                resendTimerContainer.style.display = 'block';
            } else {
                // Enable resend button
                resendBtn.disabled = false;
                resendBtn.textContent = 'Resend OTP';
                resendTimerContainer.style.display = 'none';
            }
        }
        
        // Start timers if needed
        if (remainingTime > 0 || resendCooldown > 0) {
            setInterval(updateTimers, 1000);
            updateTimers(); // Initial call
        }
        
        // Auto-submit when 6 digits are entered
        document.getElementById('otpInput')?.addEventListener('input', function(e) {
            if (this.value.length === 6) {
                this.form.submit();
            }
        });
        
        // Handle resend form submission
        document.getElementById('resendForm')?.addEventListener('submit', function(e) {
            if (resendBtn.disabled) {
                e.preventDefault();
                alert('Please wait before resending OTP.');
                return false;
            }
            
            // Show loading
            resendBtn.disabled = true;
            resendBtn.innerHTML = 'Sending...';
            
            // Allow the form to submit
            return true;
        });
    </script>
</body>
</html>