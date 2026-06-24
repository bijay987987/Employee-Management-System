<?php
require_once '../../config.php';
requireAdmin();

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: manage.php");
    exit();
}

$leave_id = intval($_GET['id']);
$action = $_GET['action'] === 'approve' ? 'Approved' : 'Rejected';

// Quick action without remarks
$sql = "UPDATE leaves SET status = ?, action_date = NOW(), action_by = ? WHERE id = ? AND status = 'Pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $action, $_SESSION['user_id'], $leave_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Leave request has been $action successfully!";
    } else {
        $_SESSION['error'] = "Leave request not found or already processed!";
    }
} else {
    $_SESSION['error'] = "Error processing leave request!";
}

header("Location: manage.php");
exit();
?>