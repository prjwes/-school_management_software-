<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $role = sanitize($_POST['role']);
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $user_id = registerUser($username, $email, $password, $full_name, $role);
        
        if ($user_id) {
            // If student role, create student record
            if ($role === 'Student') {
                $conn = getDBConnection();
                $student_id = 'STU' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
                $grade = '7'; // Default grade
                $admission_date = date('Y-m-d');
                $admission_year = date('Y');
                
                $stmt = $conn->prepare("INSERT INTO students (user_id, student_id, grade, admission_date, admission_year) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isssi", $user_id, $student_id, $grade, $admission_date, $admission_year);
                $stmt->execute();
                $stmt->close();
                $conn->close();
            }
            
            $success = 'Account created successfully! Please login.';
        } else {
            $error = 'Username or email already exists';
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - EBUSHIBO J.S PORTAL</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>EBUSHIBO J.S</h1>
                    <h2>Create Account</h2>
                    <p>Sign up to get started</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                     Added role selection dropdown 
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="Student">Student</option>
                            <option value="Teacher">Teacher</option>
                            <option value="DoS_Exams_Teacher">DoS Exams Teacher</option>
                            <option value="Social_Affairs_Teacher">Social Affairs Teacher</option>
                            <option value="Finance_Teacher">Finance Teacher</option>
                            <option value="DHOI">DHOI (Deputy Head)</option>
                            <option value="HOI">HOI (Head of Institution)</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/theme.js"></script>
</body>
</html>
