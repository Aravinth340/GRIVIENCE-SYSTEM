-- Grievance Redressal System Database
-- Chendhuran Polytechnic College

CREATE DATABASE IF NOT EXISTS grievance_system;
USE grievance_system;

-- ====================================
-- TABLE: admins
-- ====================================
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE: departments
-- ====================================
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20) UNIQUE,
    hod_name VARCHAR(100),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE: staff
-- ====================================
CREATE TABLE staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    department_id INT NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    role ENUM('staff', 'hod') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE: categories
-- ====================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE: grievances
-- ====================================
CREATE TABLE grievances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grievance_id VARCHAR(50) UNIQUE NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    register_number VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(15),
    department_id INT NOT NULL,
    category_id INT NOT NULL,
    description TEXT NOT NULL,
    file_path VARCHAR(255),
    file_name VARCHAR(255),
    status ENUM('pending', 'in_review', 'escalated', 'resolved', 'rejected', 'closed') DEFAULT 'pending',
    assigned_to INT,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    rating INT DEFAULT NULL CHECK (rating >= 1 AND rating <= 5),
    resolution_remarks TEXT,
    anonymous BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    resolution_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_register (register_number),
    INDEX idx_status (status),
    INDEX idx_department (department_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE: grievance_comments
-- ====================================
CREATE TABLE grievance_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grievance_id INT NOT NULL,
    comment_by INT NOT NULL,
    comment_type ENUM('staff', 'admin') DEFAULT 'staff',
    comment_text TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grievance_id) REFERENCES grievances(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE: activity_logs
-- ====================================
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('admin', 'staff', 'student') NOT NULL,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    grievance_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grievance_id) REFERENCES grievances(id) ON DELETE SET NULL,
    INDEX idx_user_type (user_type),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE: notifications
-- ====================================
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('admin', 'staff', 'student') NOT NULL,
    user_id INT,
    grievance_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grievance_id) REFERENCES grievances(id) ON DELETE CASCADE,
    INDEX idx_user (user_type, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE: email_templates
-- ====================================
CREATE TABLE email_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE: settings
-- ====================================
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- INSERT: Sample Departments
-- ====================================
INSERT INTO departments (name, code, hod_name, email) VALUES
('Computer Engineering', 'CE', 'Dr. Ram Kumar', 'ce.hod@chendhuran.edu'),
('Automobile Engineering', 'AE', 'Mr. Vikram Singh', 'ae.hod@chendhuran.edu'),
('Civil Engineering', 'CIE', 'Ms. Priya Sharma', 'cie.hod@chendhuran.edu'),
('Electrical and Electronics Engineering', 'EEE', 'Dr. Rajesh Patel', 'eee.hod@chendhuran.edu'),
('Electronics and Communication Engineering', 'ECE', 'Mr. Anil Kumar', 'ece.hod@chendhuran.edu'),
('Mechanical Engineering', 'ME', 'Mr. Suresh Reddy', 'me.hod@chendhuran.edu');

-- ====================================
-- INSERT: Sample Categories
-- ====================================
INSERT INTO categories (category_name, description) VALUES
('Academic', 'Issues related to academics, examinations, and results'),
('Infrastructure', 'Complaints about college infrastructure and facilities'),
('Hostel', 'Hostel and accommodation related issues'),
('Fees', 'Fee payment and refund related grievances'),
('Harassment', 'Bullying, harassment, or discriminatory behavior'),
('Canteen', 'Food quality and canteen related issues'),
('Library', 'Library facilities and resources'),
('Sports', 'Sports facilities and programs'),
('Placement', 'Placement and internship related issues'),
('Others', 'Any other grievance not covered above');

-- ====================================
-- INSERT: Sample Admin (Password: admin123)
-- ====================================
INSERT INTO admins (username, password, email, full_name) VALUES
('admin', '$2y$10$92IXUNpkm1rq4cmsTBUbeevNYWLKKKKKKKKKKKKKa6EO8Tj5aaO2', 'admin@chendhuran.edu', 'Principal Admin');

-- ====================================
-- INSERT: Sample Staff
-- ====================================
INSERT INTO staff (name, email, department_id, password, role) VALUES
('Ramesh Kumar', 'ramesh.ce@chendhuran.edu', 1, '$2y$10$92IXUNpkm1rq4cmsTBUbeevNYWLKKKKKKKKKKKKa6EO8Tj5aaO2', 'hod'),
('Anjali Verma', 'anjali.ce@chendhuran.edu', 1, '$2y$10$92IXUNpkm1rq4cmsTBUbeevNYWLKKKKKKKKKKKKa6EO8Tj5aaO2', 'staff'),
('Vikram Singh', 'vikram.ae@chendhuran.edu', 2, '$2y$10$92IXUNpkm1rq4cmsTBUbeevNYWLKKKKKKKKKKKKa6EO8Tj5aaO2', 'hod'),
('Neha Sharma', 'neha.ae@chendhuran.edu', 2, '$2y$10$92IXUNpkm1rq4cmsTBUbeevNYWLKKKKKKKKKKKKa6EO8Tj5aaO2', 'staff');

-- ====================================
-- INSERT: Email Templates
-- ====================================
INSERT INTO email_templates (template_name, subject, body) VALUES
('grievance_submitted', 'Your Grievance Has Been Submitted Successfully', 'Dear {student_name},\n\nYour grievance has been successfully submitted.\n\nGrievance ID: {grievance_id}\nStatus: Pending Review\n\nYou can track your grievance using your Register Number and Grievance ID.\n\nBest Regards,\nGrievance Redressal System\nChendhuran Polytechnic College'),
('status_updated', 'Your Grievance Status Has Been Updated', 'Dear {student_name},\n\nYour grievance (ID: {grievance_id}) status has been updated.\n\nNew Status: {status}\nRemarks: {remarks}\n\nBest Regards,\nGrievance Redressal System'),
('grievance_resolved', 'Your Grievance Has Been Resolved', 'Dear {student_name},\n\nWe are happy to inform you that your grievance (ID: {grievance_id}) has been resolved.\n\nPlease rate your experience and provide feedback.\n\nBest Regards,\nGrievance Redressal System');

-- ====================================
-- INSERT: Settings
-- ====================================
INSERT INTO settings (setting_key, setting_value) VALUES
('college_name', 'Chendhuran Polytechnic College'),
('college_email', 'grievance@chendhuran.edu'),
('college_phone', '+91-XXXXXX-XXXX'),
('grievance_auto_escalate_days', '7'),
('enable_email_notifications', '1'),
('enable_sms_notifications', '0'),
('max_upload_size_mb', '5'),
('allowed_file_types', 'pdf,jpg,jpeg,png,doc,docx');
