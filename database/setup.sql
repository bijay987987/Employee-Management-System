-- Create database
CREATE DATABASE IF NOT EXISTS employee_management;
USE employee_management;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('admin', 'employee') DEFAULT 'employee',
    department_id INT,
    position VARCHAR(100),
    phone VARCHAR(20),
    hire_date DATE DEFAULT CURRENT_DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Departments table
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    manager_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tasks table
CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INT,
    assigned_by INT,
    department_id INT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    due_date DATE,
    completed_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Attendance table
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    check_in TIMESTAMP NULL,
    check_out TIMESTAMP NULL,
    date DATE NOT NULL,
    total_hours DECIMAL(5,2) DEFAULT 0,
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Task progress table
CREATE TABLE task_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password resets table (NEW)
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(10) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used TINYINT(1) DEFAULT 0,
    INDEX idx_email_token (email, token),
    INDEX idx_expires (expires_at)
);

-- Leaves table
CREATE TABLE IF NOT EXISTS leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    emergency_contact VARCHAR(100) NULL,
    contact_phone VARCHAR(20) NULL,
    document VARCHAR(255) NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    admin_remarks TEXT,
    action_by INT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (action_by) REFERENCES users(id)
);

-- Departments data
INSERT INTO departments (name, description) VALUES
('IT', 'Information Technology Department'),
('HR', 'Human Resources Department'),
('Finance', 'Finance and Accounting Department'),
('Marketing', 'Marketing and Sales Department');

-- Users (bcrypt hashed passwords)
-- All hashes generated using PHP password_hash()
-- Default password for all users: password123
INSERT INTO users (username, email, password, first_name, last_name, role, position) VALUES
(
 'admin',
 'admin@company.com',
 '$2y$10$Q6/dKgUHGMMnRFXBQAYNMOl8i6GwlyKHXWpFsH9.lJefeljowR5bC',
 'System',
 'Administrator',
 'admin',
 'System Administrator'
);

INSERT INTO users (username, email, password, first_name, last_name, role, department_id, position) VALUES
(
 'ram.sharma',
 'ram.sharma@company.com',
 '$2y$10$j8mAWvS0ObPT4pe9ob8Z5uzmGm3fO.k8q5BTYDMrlZsH/YGvUvBm2',
 'Ram',
 'Sharma',
 'employee',
 1,
 'Software Developer'
),
(
 'shyam.adhikari',
 'shyam.adhikari@company.com',
 '$2y$10$XkQVYWzlthVxQ3nqR5T8AuWkfuEoZ/VCYZGJE9ZqFW3DM40cyhxNK',
 'Shyam',
 'Adhikari',
 'employee',
 2,
 'HR Manager'
),
(
 'hari.khadka',
 'hari.khadka@company.com',
 '$2y$10$IwRXo..IuJBZ6RcOz/Imj.971529COtVSG2jdKoBJhHZVCXQy8OyW',
 'Hari',
 'Khadka',
 'employee',
 3,
 'Financial Analyst'
),
(
 'sita.thapa',
 'sita.thapa@company.com',
 '$2y$10$j8XYZ7Cp20/C6yYcJEDLG.ztxZCcVglSajaGDuJ/76bgniSwTIyUC',
 'Sita',
 'Thapa',
 'employee',
 4,
 'Marketing Specialist'
),
(
 'gita.bhandari',
 'gita.bhandari@company.com',
 '$2y$10$WbyfhCO/.aSj0prRyNLVE.1Z.eayWqRzBrYIqwm93PHKO64eXf9q2',
 'Gita',
 'Bhandari',
 'employee',
 1,
 'System Administrator'
);


-- Sample tasks
INSERT INTO tasks (title, description, assigned_to, assigned_by, department_id, priority, status, due_date) VALUES
('Website Redesign', 'Redesign company website with modern UI', 2, 1, 1, 'high', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)),
('Database Optimization', 'Optimize database queries for better performance', 2, 1, 1, 'medium', 'in_progress', DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY)),
('Employee Training Program', 'Develop new employee training materials', 3, 1, 2, 'high', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY)),
('Q4 Financial Report', 'Prepare quarterly financial reports', 4, 1, 3, 'medium', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY)),
('Social Media Campaign', 'Launch new social media marketing campaign', 5, 1, 4, 'low', 'in_progress', DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY));

-- Attendance
INSERT INTO attendance (user_id, check_in, check_out, date, total_hours, status) VALUES
(2, '2024-12-19 09:00:00', '2024-12-19 17:00:00', '2024-12-19', 8.0, 'present'),
(3, '2024-12-18 08:30:00', '2024-12-18 16:00:00', '2024-12-18', 7.5, 'present'),
(4, '2024-12-17 08:00:00', '2024-12-17 16:30:00', '2024-12-17', 8.5, 'present'),
(5, '2024-12-16 09:15:00', '2024-12-16 15:45:00', '2024-12-16', 6.5, 'half_day');

-- Task progress
INSERT INTO task_progress (task_id, user_id, notes) VALUES
(2, 2, 'Started optimizing user queries. Identified several slow-running queries.'),
(5, 5, 'Created campaign design and scheduled first set of posts.');