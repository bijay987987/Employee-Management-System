<?php if (!isset($conn)) include 'config.php'; ?>
<nav class="sidebar" id="sidebar">
    <ul class="sidebar-menu">
        <?php if (isAdmin()): ?>
            <li><a href="/employee-management-system/admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                📊 Dashboard
            </a></li>
            <li><a href="/employee-management-system/admin/employees/manage.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'employees/') !== false ? 'active' : ''; ?>">
                👥 Employees
            </a></li>
            <li><a href="/employee-management-system/admin/tasks/manage.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'tasks/') !== false ? 'active' : ''; ?>">
                📋 Tasks
            </a></li>
            <li><a href="/employee-management-system/admin/departments/manage.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'departments/') !== false ? 'active' : ''; ?>">
                🏢 Departments
            </a></li>
            <li><a href="/employee-management-system/admin/attendance/manage.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'attendance/') !== false ? 'active' : ''; ?>">
                ⏰ Attendance
            </a></li>
            <!-- In your admin navbar section -->
<li>
    <a href="/employee-management-system/admin/leaves/manage.php" 
       class="<?php echo strpos($_SERVER['PHP_SELF'], 'leaves/') !== false ? 'active' : ''; ?>">
        📅 Leave Management
    </a>
</li>
            
        <?php else: ?>
            <li><a href="/employee-management-system/employee/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                📊 Dashboard
            </a></li>
            <li><a href="/employee-management-system/employee/tasks/my_tasks.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'tasks/') !== false ? 'active' : ''; ?>">
                📋 My Tasks
            </a></li>
            <li><a href="/employee-management-system/employee/attendance/checkin.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'attendance/') !== false ? 'active' : ''; ?>">
                ⏰ Attendance
            </a></li>
            <li><a href="<?php echo isAdmin() ? '/employee-management-system/admin/profile/view.php' : '/employee-management-system/employee/profile/view.php'; ?>" class="<?php echo strpos($_SERVER['PHP_SELF'], 'profile/') !== false ? 'active' : ''; ?>">
            👤 Profile
        </a></li><!-- Add this to employee sidebar -->
        <!-- In your employee navbar section -->
<li>
    <a href="/employee-management-system/employee/leaves/my_leaves.php" 
       class="<?php echo strpos($_SERVER['PHP_SELF'], 'leaves/') !== false ? 'active' : ''; ?>">
        📅 My Leaves
    </a>
</li>     
        <?php endif; ?>
        
    </ul>
</nav>