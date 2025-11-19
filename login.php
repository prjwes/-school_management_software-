<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (loginUser($username, $password)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid credentials';
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
    <title>Login - EBUSHIBO J.S PORTAL</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>EBUSHIBO J.S</h1>
                    <h2>Welcome Back</h2>
                    <p>Login to access your account</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <!-- Updated label to indicate students use full name -->
                        <label for="username">Username/Email (Students: use Full Name)</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
                    <p><a href="forgot-password.php">Forgot password?</a></p>
                </div>

                <div class="demo-credentials">
                    <p><strong>Demo Credentials:</strong></p>
                    <p>Student: Use full name | student.{grade}.{year}</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/theme.js"></script>
</body>
</html>
