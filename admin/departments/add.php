<?php
include '../../config.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $manager_id = sanitize($_POST['manager_id']);
    
    // Check if department already exists
    $check_sql = "SELECT id FROM departments WHERE name = ? AND status = 'active'";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Department already exists!";
    } else {
        $sql = "INSERT INTO departments (name, description, manager_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $description, $manager_id);
        
        if ($stmt->execute()) {
            $success = "Department added successfully!";
            $_POST = array(); // Clear form
        } else {
            $error = "Error adding department: " . $conn->error;
        }
    }
}

// Get managers for dropdown
$managers_sql = "SELECT id, first_name, last_name, position FROM users WHERE role = 'employee' AND status = 'active'";
$managers_result = $conn->query($managers_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Department - Admin</title>
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
            <h1 class="page-title">Add Department</h1>
            <div class="breadcrumb">Admin / Departments / Add</div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Department Information</h2>
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
                        <label for="name" class="form-label">Department Name *</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo $_POST['name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="4"><?php echo $_POST['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="manager_id" class="form-label">Department Manager</label>
                        <select id="manager_id" name="manager_id" class="form-control">
                            <option value="">Select Manager</option>
                            <?php while ($manager = $managers_result->fetch_assoc()): ?>
                                <option value="<?php echo $manager['id']; ?>" 
                                    <?php echo (($_POST['manager_id'] ?? '') == $manager['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name'] . ' - ' . $manager['position']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add Department</button>
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