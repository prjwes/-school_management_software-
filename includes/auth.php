<?php
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user has specific role
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['user_role'], $roles);
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

// Require specific role
function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit();
    }
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $conn = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $user;
}

// Login user
function loginUser($username, $password) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT id, username, email, password, full_name, role, profile_image FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['profile_image'] = $user['profile_image'];
            
            $stmt->close();
            $conn->close();
            return true;
        }
    }
    
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT u.id, u.username, u.email, u.password, u.full_name, u.role, u.profile_image, s.grade, s.admission_year 
                            FROM users u 
                            JOIN students s ON u.id = s.user_id 
                            WHERE u.full_name = ? AND u.role = 'Student'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if password matches default format: student.{grade}.{year}
        $default_password = "student." . $user['grade'] . "." . $user['admission_year'];
        
        if ($password === $default_password || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['profile_image'] = $user['profile_image'];
            
            $stmt->close();
            $conn->close();
            return true;
        }
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

// Register user
function registerUser($username, $email, $password, $full_name, $role = 'Student') {
    $conn = getDBConnection();
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return false;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);
    
    $success = $stmt->execute();
    $user_id = $conn->insert_id;
    
    $stmt->close();
    $conn->close();
    
    return $success ? $user_id : false;
}

// Logout user
function logoutUser() {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

function canManageStudents($role) {
    return in_array($role, ['Admin', 'HOI', 'DHOI']);
}

function canManagePayments($role) {
    return in_array($role, ['Admin', 'HOI', 'DHOI', 'Finance_Teacher']);
}

function canManageExams($role) {
    return $role !== 'Student';
}

function canGenerateReportCards($role) {
    return in_array($role, ['Admin', 'HOI', 'DHOI', 'DoS_Exams_Teacher']);
}

function canViewOnly($role) {
    return $role === 'Student';
}
?>
