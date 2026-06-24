<?php if (!isset($conn)) include 'config.php'; ?>

<header class="header">
    <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggle">☰</button>
        <div class="logo">Employee Management System</div>
    </div>
    <div class="header-right">
        <?php if (isAdmin()): ?>
            <?php
            // Get pending tasks count for admin
            $pending_sql = "SELECT COUNT(*) as pending_count FROM tasks WHERE status = 'pending'";
            $pending_result = $conn->query($pending_sql);
            $pending_count = $pending_result->fetch_assoc()['pending_count'];
            
            // Get unassigned tasks count
            $unassigned_sql = "SELECT COUNT(*) as unassigned_count FROM tasks WHERE assigned_to IS NULL";
            $unassigned_result = $conn->query($unassigned_sql);
            $unassigned_count = $unassigned_result->fetch_assoc()['unassigned_count'];
            ?>
            
            <div class="notification-badge">
                <span class="badge" title="Pending Tasks: <?php echo $pending_count; ?>, Unassigned: <?php echo $unassigned_count; ?>">
                    <?php echo $pending_count + $unassigned_count; ?>
                </span>
            </div>
        <?php else: ?>
            <?php
            // Get pending tasks count for employee
            $user_id = $_SESSION['user_id'];
            $pending_sql = "SELECT COUNT(*) as pending_count FROM tasks WHERE assigned_to = ? AND status = 'pending'";
            $stmt = $conn->prepare($pending_sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $pending_result = $stmt->get_result();
            $pending_count = $pending_result->fetch_assoc()['pending_count'];
            ?>
            
            <div class="notification-badge">
                <span class="badge" title="Pending Tasks">
                    <?php echo $pending_count; ?>
                </span>
            </div>
        <?php endif; ?>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
            </div>
            <span><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></span>
        </div>
        <a href="/employee-management-system/logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
</header>