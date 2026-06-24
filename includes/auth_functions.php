<?php
/**
 * Authentication Helper Functions
 * For Forgot Password Module
 */

/**
 * Generate a random OTP
 * @param int $length OTP length (default: 6)
 * @return string Generated OTP
 */
function generateOTP($length = 6) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

/**
 * Validate OTP against database
 * @param string $email User email
 * @param string $otp OTP to validate
 * @param string $token Associated token
 * @return bool True if valid, false otherwise
 */
function validateOTP($email, $otp, $token) {
    global $conn;
    
    $sql = "SELECT * FROM password_resets 
            WHERE email = ? AND otp = ? AND token = ? 
            AND used = 0 AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $otp, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Mark OTP as used
 * @param string $token Token to mark as used
 * @return bool Success status
 */
function markOTPUsed($token) {
    global $conn;
    
    $sql = "UPDATE password_resets SET used = 1 WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    return $stmt->execute();
}

/**
 * Check if password reset token is valid
 * @param string $token Token to check
 * @return bool True if valid, false otherwise
 */
function isValidPasswordResetToken($token) {
    global $conn;
    
    $sql = "SELECT * FROM password_resets 
            WHERE token = ? AND used = 1 
            AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Development mode: Log to browser console
 * @param string $action Action description
 * @param array $data Data to log
 */
function logToConsole($action, $data) {
    echo "<script>";
    echo "console.log('%c=== SQL MAIL SIMULATION ===', 'background: #3498db; color: white; padding: 5px; border-radius: 3px;');";
    echo "console.log('Action: $action');";
    foreach ($data as $key => $value) {
        echo "console.log('$key: $value');";
    }
    echo "console.log('%c===========================', 'background: #3498db; color: white; padding: 5px; border-radius: 3px;');";
    echo "</script>";
    
    // Also log to server error log
    error_log("SQL MAIL: $action - " . json_encode($data));
}

/**
 * Clean up expired OTPs from database
 */
function cleanupExpiredOTPs() {
    global $conn;
    
    $sql = "DELETE FROM password_resets WHERE expires_at < NOW() OR created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
}

/**
 * Check if email exists and is active
 * @param string $email Email to check
 * @return array|false User data if exists, false otherwise
 */
function getUserByEmail($email) {
    global $conn;
    
    $sql = "SELECT id, email, first_name, last_name, role, status 
            FROM users WHERE email = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Update user password
 * @param string $email User email
 * @param string $password New password (plain text)
 * @return bool Success status
 */
function updateUserPassword($email, $password) {
    global $conn;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $hashed_password, $email);
    return $stmt->execute();
}
?>