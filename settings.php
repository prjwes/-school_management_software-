<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    
    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $profile_image = uploadFile($_FILES['profile_image'], 'profiles');
    }
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, profile_image = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $email, $profile_image, $user['id']);
    
    if ($stmt->execute()) {
        $success = 'Profile updated successfully!';
        $_SESSION['full_name'] = $full_name;
        $_SESSION['profile_image'] = $profile_image;
        $user = getCurrentUser();
    } else {
        $error = 'Failed to update profile';
    }
    
    $stmt->close();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user['id']);
                
                if ($stmt->execute()) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to change password';
                }
                
                $stmt->close();
            } else {
                $error = 'Password must be at least 6 characters';
            }
        } else {
            $error = 'New passwords do not match';
        }
    } else {
        $error = 'Current password is incorrect';
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - EBUSHIBO J.S PORTAL</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Account Settings</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

             Profile Settings 
            <div class="table-container" style="margin-bottom: 24px;">
                <div class="table-header">
                    <h3>Profile Information</h3>
                </div>
                <form method="POST" enctype="multipart/form-data" style="padding: 24px;">
                    <div class="form-group">
                        <label>Current Profile Image</label>
                        <?php 
                            $profile_path = 'uploads/profiles/' . htmlspecialchars($user['profile_image']);
                            $profile_exists = file_exists($profile_path);
                        ?>
                        <?php if ($profile_exists && $user['profile_image'] !== 'default-avatar.png'): ?>
                            <img src="<?php echo $profile_path; ?>" alt="Profile" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100px; height: 100px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-size: 40px; font-weight: bold;">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_image">Change Profile Image</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" <?php echo ($user['role'] === 'Student') ? 'disabled' : 'required'; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['role']); ?>" disabled>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

             Password Change 
            <div class="table-container">
                <div class="table-header">
                    <h3>Change Password</h3>
                </div>
                <form method="POST" style="padding: 24px;">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </main>
    </div>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
