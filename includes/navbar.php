<?php if (!isset($conn)) include 'config.php'; ?>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="<?php echo isAdmin() ? 'admin/dashboard.php' : 'employee/dashboard.php'; ?>">
                Employee Management System
            </a>
        </div>
        
        <div class="nav-menu" id="navMenu">
            <div class="nav-links">
                <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📊</span>
                        Dashboard
                    </a>
                    <a href="admin/employees/manage.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'employees/') !== false ? 'active' : ''; ?>">
                        <span class="nav-icon">👥</span>
                        Employees
                    </a>
                    <a href="admin/tasks/manage.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'tasks/') !== false ? 'active' : ''; ?>">
                        <span class="nav-icon">📋</span>
                        Tasks
                    </a>
                    <a href="admin/departments/manage.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'departments/') !== false ? 'active' : ''; ?>">
                        <span class="nav-icon">🏢</span>
                        Departments
                    </a>
                    <a href="admin/attendance/manage.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'attendance/') !== false ? 'active' : ''; ?>">
                        <span class="nav-icon">⏰</span>
                        Attendance
                    </a>
                    <a href="admin/leaves/manage.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'leaves/') !== false ? 'active' : ''; ?>">
                        <span class="nav-icon">📅</span>
                        Leaves
                    </a>
                    <a href="admin/leaves/reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
    <i>📊</i> Leave Reports
</a>

<a href="admin/leaves/manage.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage.php' ? 'active' : ''; ?>">
    <i>📝</i> Manage Leaves
</a>
                <?php else: ?>
                    <a href="employee/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📊</span>
                        Dashboard
                    </a>
                    <a href="employee/tasks/my_tasks.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'tasks/') !== false ? 'active' : ''; ?>">
                        <span class="nav-icon">📋</span>
                        My Tasks
                    </a>
                    <a href="employee/attendance/checkin.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'attendance/') !== false ? 'active' : ''; ?>">
                        <span class="nav-icon">⏰</span>
                        Attendance
                    </a>
                     <a href="employee/leaves/history.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'leaves/') !== false ? 'active' : ''; ?>">
        <span class="nav-icon">📅</span>
        My Leaves
    </a>
    <a href="employee/leaves/status.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'leaves/status.php') !== false ? 'active' : ''; ?>">
    <span class="nav-icon">📊</span>
    Leave Status
</a>

<a href="employee/leaves/history.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'leaves/history.php') !== false ? 'active' : ''; ?>">
    <span class="nav-icon">📋</span>
    Leave History
</a>

<a href="employee/leaves/apply.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'leaves/apply.php') !== false ? 'active' : ''; ?>">
    <span class="nav-icon">➕</span>
    Apply Leave
</a>
                <?php endif; ?>
            </div>
            
            <div class="nav-user">
                <div class="user-dropdown">
                    <button class="user-toggle" id="userToggle">
                        <div class="user-avatar-sm">
                            <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
                        </div>
                        <span class="user-name">
                            <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>
                        </span>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="<?php echo isAdmin() ? 'admin/profile/view.php' : 'employee/profile/view.php'; ?>" class="dropdown-item">
                            <span class="dropdown-icon">👤</span>
                            My Profile
                        </a>
                        <?php if (isAdmin()): ?>
                            <a href="admin/profile/change_password.php" class="dropdown-item">
                                <span class="dropdown-icon">🔒</span>
                                Change Password
                            </a>
                        <?php else: ?>
                            <a href="employee/profile/change_password.php" class="dropdown-item">
                                <span class="dropdown-icon">🔒</span>
                                Change Password
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php" class="dropdown-item logout">
                            <span class="dropdown-icon">🚪</span>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <button class="nav-toggle" id="navToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            navToggle.classList.toggle('active');
        });
    }
    
    // User dropdown toggle
    const userToggle = document.getElementById('userToggle');
    const dropdownMenu = document.getElementById('dropdownMenu');
    
    if (userToggle && dropdownMenu) {
        userToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            dropdownMenu.classList.remove('show');
        });
    }
});
</script>