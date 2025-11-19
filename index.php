<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect to dashboard if already logged in
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
    <title>School Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="landing-page">
        <nav class="navbar">
            <div class="container">
                <div class="nav-brand">
                    <h2>🎓 EBUSHIBO J.S PORTAL</h2>
                </div>
                <div class="nav-links">
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="signup.php" class="btn btn-primary">Sign Up</a>
                </div>
            </div>
        </nav>

        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <h1>School Management Software</h1>
                    <p>Complete solution for student management, Notes, Exams, Fees, Clubs, and more</p>
                    <div class="hero-buttons">
                        <a href="signup.php" class="btn btn-primary btn-lg">Get Started</a>
                        <a href="login.php" class="btn btn-outline btn-lg">Login</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="features">
            <div class="container">
                <h2>Key Features</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">👥</div>
                        <h3>Student Management</h3>
                        <p>Track admissions, promotions, and graduations across grades 7-9</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📝</div>
                        <h3>Exam Management</h3>
                        <p>Add, view, edit, and export exam marks with ease</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">💰</div>
                        <h3>Fee Tracking</h3>
                        <p>Monitor payments with percentage calculations and filtering</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🎭</div>
                        <h3>Club Activities</h3>
                        <p>Manage clubs with posts, comments, and member interactions</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📚</div>
                        <h3>Study Materials</h3>
                        <p>Upload and download notes for all subjects and grades</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h3>Report Cards</h3>
                        <p>Generate and manage student report cards</p>
                    </div>
                </div>
            </div>
        </section>

        <footer class="footer">
            <div class="container">
                <p>&copy; 2025 School Management System. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <script src="assets/js/theme.js"></script>
</body>
</html>
