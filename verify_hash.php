<?php
$input = 'admin123';
$stored = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

if (password_verify($input, $stored)) {
    echo "✅ Password works";
} else {
    echo "❌ Password failed";
}
?>