<?php
include '../../config.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $department_id = sanitize($_POST['department_id']);
    $priority = sanitize($_POST['priority']);
    $due_date = sanitize($_POST['due_date']);
    
    $sql = "INSERT INTO tasks (title, description, department_id, priority, due_date, assigned_by) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisss", $title, $description, $department_id, $priority, $due_date, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success = "Task created successfully!";
        $_POST = array(); // Clear form
    } else {
        $error = "Error creating task: " . $conn->error;
    }
}

// Get departments for dropdown
$dept_sql = "SELECT * FROM departments WHERE status = 'active'";
$dept_result = $conn->query($dept_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task - Admin</title>
    <link rel="icon" href="../../images/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Create Task</h1>
            <div class="breadcrumb">Admin / Tasks / Create</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Task Details</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title" class="form-label">Task Title *</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?php echo $_POST['title'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description *</label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="5" required><?php echo $_POST['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="department_id" class="form-label">Department *</label>
                            <select id="department_id" name="department_id" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php while ($dept = $dept_result->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo (($_POST['department_id'] ?? '') == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="priority" class="form-label">Priority *</label>
                            <select id="priority" name="priority" class="form-control" required>
                                <option value="low" <?php echo ($_POST['priority'] ?? '') == 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo ($_POST['priority'] ?? '') == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo ($_POST['priority'] ?? '') == 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?php echo ($_POST['priority'] ?? '') == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="due_date" class="form-label">Due Date *</label>
                        <input type="date" id="due_date" name="due_date" class="form-control" 
                               value="<?php echo $_POST['due_date'] ?? ''; ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Create Task</button>
                        <a href="manage.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/forms.js"></script>
</body>
</html>