<?php

include 'config.php';

// DEBUG: Log incoming request
error_log("=== RESEND OTP REQUEST ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));

// Try multiple ways to get the email
$email = '';

// 1. First try from POST (form submission from verify_otp.php)
if (isset($_POST['email']) && !empty($_POST['email'])) {
    $email = sanitize($_POST['email']);
    error_log("Email from POST: " . $email);
    $_SESSION['reset_email'] = $email;
}

// 2. Try from session
elseif (isset($_SESSION['reset_email']) && !empty($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
    error_log("Email from SESSION: " . $email);
}

// 3. Try from GET (URL parameter)
elseif (isset($_GET['email']) && !empty($_GET['email'])) {
    $email = sanitize($_GET['email']);
    error_log("Email from GET: " . $email);
    $_SESSION['reset_email'] = $email;
}

// 4. Try to get from database using token in session
elseif (isset($_SESSION['reset_token'])) {
    $sql = "SELECT email FROM password_resets WHERE token = ? AND used = 0 ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['reset_token']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        $_SESSION['reset_email'] = $email;
        error_log("Email from DATABASE using token: " . $email);
    }
}

// If still no email, redirect with error
if (empty($email)) {
    error_log("ERROR: Could not determine email for OTP resend");
    $_SESSION['error'] = "Unable to resend OTP. Please request a new one.";
    header("Location: forgot_password.php");
    exit();
}

error_log("Resending OTP to: " . $email);

// Generate new OTP
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

// Invalidate previous OTPs for this email
$sql = "UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();

// Insert new OTP
$sql = "INSERT INTO password_resets (email, otp, token, expires_at) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $email, $otp, $token, $expires_at);

if ($stmt->execute()) {
    // Development mode logging
    if (DEBUG_MODE) {
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
        
        echo "<script>";
        echo "console.log('%c=== OTP RESENT ===', 'background: #f39c12; color: white; padding: 5px; border-radius: 3px;');";
        echo "console.log('New OTP sent to: " . $email . "');";
        echo "console.log('New OTP: %c" . $otp . "%c', 'color: #27ae60; font-size: 18px; font-weight: bold;', '');";
        echo "console.log('Expires at: " . $expires_at . "');";
        echo "console.log('Token: " . substr($token, 0, 20) . "...');";
        echo "console.log('%c================', 'background: #f39c12; color: white; padding: 5px; border-radius: 3px;');";
        echo "</script>";
        
        error_log("OTP RESENT SUCCESS: New OTP sent to " . $email . " - OTP: " . $otp);
    }
    
    // Update session with new token
    $_SESSION['reset_token'] = $token;
    $_SESSION['otp_sent_time'] = time();
    
    // Force session save
    session_write_close();
    
    error_log("Redirecting back to verify_otp.php with success flag");
    header("Location: verify_otp.php?resent=1");
    exit();
} else {
    // Error handling
    error_log("ERROR: Database insert failed for OTP resend to " . $email);
    $_SESSION['error'] = "Failed to resend OTP. Please try again.";
    header("Location: verify_otp.php?error=resend_failed");
    exit();
}
?>