<?php
if (!isset($user)) {
    $user = getCurrentUser();
}

if (!$user) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}
?>
<header class="header">
    <div class="header-container">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <h1 class="header-title">🎓 EBUSHIBO JS</h1>
        </div>
        
        <div class="header-right">
            <button class="theme-toggle" id="themeToggle" title="Toggle theme">
                <span class="theme-icon">🌙</span>
            </button>
            
            <div class="user-menu">
                <button class="user-menu-toggle" id="userMenuToggle">
                    <?php 
                    $profile_image = $user['profile_image'] ?? 'default-avatar.png';
                    $full_name = $user['full_name'] ?? 'User';
                    $role = $user['role'] ?? 'Guest';
                    
                    $image_path = BASE_URL . '/uploads/profiles/' . htmlspecialchars($profile_image);
                    // Check if custom profile image exists, otherwise use default
                    if ($profile_image === 'default-avatar.png' || empty($profile_image)) {
                        $image_path = BASE_URL . '/assets/images/default-avatar.png';
                    }
                    ?>
                    <img src="<?php echo $image_path; ?>" 
                         alt="Profile" 
                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; object-position: center; display: block; flex-shrink: 0;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <span style="display: none; width: 40px; height: 40px; border-radius: 50%; background-color: var(--primary-color); color: white; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">
                        <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                    </span>
                    <span><?php echo htmlspecialchars($full_name); ?></span>
                </button>
                
                <div class="user-dropdown" id="userDropdown">
                    <div class="dropdown-header">
                        <p class="user-name"><?php echo htmlspecialchars($full_name); ?></p>
                        <p class="user-role"><?php echo htmlspecialchars($role); ?></p>
                    </div>
                    <a href="settings.php" class="dropdown-item">⚙️ Settings</a>
                    <a href="logout.php" class="dropdown-item">🚪 Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>
