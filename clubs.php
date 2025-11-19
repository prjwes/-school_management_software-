<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

// Handle club creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_club'])) {
    $club_name = sanitize($_POST['club_name']);
    $description = sanitize($_POST['description']);
    
    $club_image = null;
    if (isset($_FILES['club_image']) && $_FILES['club_image']['error'] === 0) {
        $club_image = uploadFile($_FILES['club_image'], 'clubs');
    }
    
    $stmt = $conn->prepare("INSERT INTO clubs (club_name, description, club_image, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $club_name, $description, $club_image, $user['id']);
    $stmt->execute();
    $stmt->close();
    
    header('Location: clubs.php?success=1');
    exit();
}

// Handle adding member to club
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $club_id = intval($_POST['club_id']);
    $student_id = intval($_POST['student_id']);
    $role = sanitize($_POST['member_role']);
    $joined_date = date('Y-m-d');
    
    $stmt = $conn->prepare("INSERT INTO club_members (club_id, student_id, joined_date, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE role = ?");
    $stmt->bind_param("iisss", $club_id, $student_id, $joined_date, $role, $role);
    $stmt->execute();
    $stmt->close();
    
    header('Location: clubs.php?success=1');
    exit();
}

// Get all clubs
$clubs = $conn->query("SELECT c.*, u.full_name as created_by_name, (SELECT COUNT(*) FROM club_members WHERE club_id = c.id) as member_count FROM clubs c JOIN users u ON c.created_by = u.id ORDER BY c.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get all students
$students = getStudents();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clubs - EBUSHIBO J.S PORTAL</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Clubs</h1>
                <button class="btn btn-primary" onclick="toggleClubForm()">Create Club</button>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Operation completed successfully!</div>
            <?php endif; ?>

            <!-- Create Club Form -->
            <div id="clubForm" class="table-container" style="display: none; margin-bottom: 24px;">
                <div class="table-header">
                    <h3>Create New Club</h3>
                </div>
                <form method="POST" enctype="multipart/form-data" style="padding: 24px;">
                    <div class="form-group">
                        <label for="club_name">Club Name</label>
                        <input type="text" id="club_name" name="club_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="club_image">Club Image</label>
                        <input type="file" id="club_image" name="club_image" accept="image/*">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="create_club" class="btn btn-primary">Create Club</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleClubForm()">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Clubs Grid -->
            <div class="features-grid">
                <?php foreach ($clubs as $club): ?>
                    <div class="feature-card">
                        <?php if ($club['club_image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($club['club_image']); ?>" alt="Club" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 16px;">
                        <?php else: ?>
                            <div class="feature-icon">🎭</div>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($club['club_name']); ?></h3>
                        <p><?php echo htmlspecialchars($club['description']); ?></p>
                        <p style="color: var(--text-secondary); font-size: 14px; margin-top: 12px;">
                            <?php echo $club['member_count']; ?> members
                        </p>
                        <a href="club_details.php?id=<?php echo $club['id']; ?>" class="btn btn-primary" style="margin-top: 12px;">View Club</a>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($clubs)): ?>
                    <p class="no-data">No clubs found. Create one to get started!</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function toggleClubForm() {
            const form = document.getElementById('clubForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
