<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_role'];

function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo isActive('dashboard.php'); ?>">
            <span class="nav-icon">🏠</span>
            <span class="nav-text">Dashboard</span>
        </a>
         
        <?php if (canManageStudents($role)): ?>
            <a href="students.php" class="nav-item <?php echo isActive('students.php'); ?>">
                <span class="nav-icon">👥</span>
                <span class="nav-text">Students</span>
            </a>
        <?php endif; ?>
        
        <?php if (canManageExams($role)): ?>
            <a href="exams.php" class="nav-item <?php echo isActive('exams.php'); ?>">
                <span class="nav-icon">📝</span>
                <span class="nav-text">Exams</span>
            </a>
        <?php endif; ?>
        
        <?php if (canManagePayments($role)): ?>
            <a href="fees.php" class="nav-item <?php echo isActive('fees.php'); ?>">
                <span class="nav-icon">💰</span>
                <span class="nav-text">Fees</span>
            </a>
        <?php elseif ($role === 'Student'): ?>
            <a href="fees.php" class="nav-item <?php echo isActive('fees.php'); ?>">
                <span class="nav-icon">💰</span>
                <span class="nav-text">My Fees</span>
            </a>
        <?php endif; ?>
        
        <a href="clubs.php" class="nav-item <?php echo isActive('clubs.php'); ?>">
            <span class="nav-icon">🎭</span>
            <span class="nav-text">Clubs</span>
        </a>
        
        <a href="notes.php" class="nav-item <?php echo isActive('notes.php'); ?>">
            <span class="nav-icon">📚</span>
            <span class="nav-text">Notes</span>
        </a>
        
        <?php if (canGenerateReportCards($role)): ?>
            <a href="reports.php" class="nav-item <?php echo isActive('reports.php'); ?>">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Reports</span>
            </a>
        <?php endif; ?>
        
        <?php if ($role === 'Student'): ?>
            <a href="student_portal.php" class="nav-item <?php echo isActive('student_portal.php'); ?>">
                <span class="nav-icon">👤</span>
                <span class="nav-text">My Portal</span>
            </a>
        <?php endif; ?>
        
        <a href="settings.php" class="nav-item <?php echo isActive('settings.php'); ?>">
            <span class="nav-icon">⚙️</span>
            <span class="nav-text">Settings</span>
        </a>
    </nav>
</aside>
