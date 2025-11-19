<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user = getCurrentUser();
$club_id = intval($_GET['id'] ?? 0);

if (!$club_id) {
    header('Location: clubs.php');
    exit();
}

$conn = getDBConnection();

// Get club details
$stmt = $conn->prepare("SELECT c.*, u.full_name as created_by_name FROM clubs c JOIN users u ON c.created_by = u.id WHERE c.id = ?");
$stmt->bind_param("i", $club_id);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$club) {
    header('Location: clubs.php');
    exit();
}

// Handle adding member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $student_id = intval($_POST['student_id']);
    $member_role = sanitize($_POST['member_role']);
    $joined_date = date('Y-m-d');
    
    $stmt = $conn->prepare("INSERT INTO club_members (club_id, student_id, joined_date, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE role = ?");
    $stmt->bind_param("iisss", $club_id, $student_id, $joined_date, $member_role, $member_role);
    $stmt->execute();
    $stmt->close();
    
    header('Location: club_details.php?id=' . $club_id . '&success=1');
    exit();
}

// Handle removing member
if (isset($_GET['remove_member'])) {
    $member_id = intval($_GET['remove_member']);
    
    $stmt = $conn->prepare("DELETE FROM club_members WHERE id = ? AND club_id = ?");
    $stmt->bind_param("ii", $member_id, $club_id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: club_details.php?id=' . $club_id . '&success=1');
    exit();
}

// Get club members
$stmt = $conn->prepare("SELECT cm.*, s.student_id, u.full_name FROM club_members cm JOIN students s ON cm.student_id = s.id JOIN users u ON s.user_id = u.id WHERE cm.club_id = ? ORDER BY cm.role DESC, u.full_name ASC");
$stmt->bind_param("i", $club_id);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get club posts
$stmt = $conn->prepare("SELECT cp.*, u.full_name, u.profile_image FROM club_posts cp JOIN users u ON cp.user_id = u.id WHERE cp.club_id = ? ORDER BY cp.created_at DESC");
$stmt->bind_param("i", $club_id);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all students not in club
$stmt = $conn->prepare("SELECT s.id, s.student_id, u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.id NOT IN (SELECT student_id FROM club_members WHERE club_id = ?) AND s.status = 'Active' ORDER BY u.full_name");
$stmt->bind_param("i", $club_id);
$stmt->execute();
$available_students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Details - EBUSHIBO J.S PORTAL</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><?php echo htmlspecialchars($club['club_name']); ?></h1>
                <a href="clubs.php" class="btn btn-secondary">Back to Clubs</a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Operation completed successfully!</div>
            <?php endif; ?>

             Club Information 
            <div class="table-container" style="margin-bottom: 24px;">
                <div class="table-header">
                    <h3>Club Details</h3>
                </div>
                <div style="padding: 24px;">
                    <?php if ($club['club_image']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($club['club_image']); ?>" alt="Club" style="width: 100%; max-width: 400px; height: 250px; object-fit: cover; border-radius: 8px; margin-bottom: 16px;">
                    <?php endif; ?>
                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($club['description'])); ?></p>
                    <p><strong>Created By:</strong> <?php echo htmlspecialchars($club['created_by_name']); ?></p>
                    <p><strong>Created Date:</strong> <?php echo formatDate($club['created_at']); ?></p>
                </div>
            </div>

             Add Member Form 
            <div class="table-container" style="margin-bottom: 24px;">
                <div class="table-header">
                    <h3>Add Member</h3>
                </div>
                <form method="POST" style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label for="student_id">Student</label>
                            <select id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($available_students as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']) . ' - ' . htmlspecialchars($s['student_id']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="member_role">Role</label>
                            <select id="member_role" name="member_role" required>
                                <option value="Member">Member</option>
                                <option value="Leader">Leader</option>
                                <option value="Vice-Leader">Vice-Leader</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" name="add_member" class="btn btn-primary" style="width: 100%;">Add Member</button>
                        </div>
                    </div>
                </form>
            </div>

             Club Members 
            <div class="table-container" style="margin-bottom: 24px;">
                <div class="table-header">
                    <h3>Members (<?php echo count($members); ?>)</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                    <td><span class="badge"><?php echo htmlspecialchars($member['role']); ?></span></td>
                                    <td><?php echo formatDate($member['joined_date']); ?></td>
                                    <td>
                                        <a href="?id=<?php echo $club_id; ?>&remove_member=<?php echo $member['id']; ?>" class="btn btn-sm" onclick="return confirm('Remove this member?')">Remove</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($members)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No members yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

             Club Posts 
            <div class="table-container">
                <div class="table-header">
                    <h3>Posts (<?php echo count($posts); ?>)</h3>
                </div>
                <div style="padding: 24px;">
                    <?php foreach ($posts as $post): ?>
                        <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 16px;">
                            <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                                <?php 
                                $author_image = $post['profile_image'] ?? 'default-avatar.png';
                                $author_image_path = 'uploads/profiles/' . htmlspecialchars($author_image);
                                if ($author_image === 'default-avatar.png' || empty($author_image)) {
                                    $author_image_path = 'assets/images/default-avatar.png';
                                }
                                ?>
                                <img src="<?php echo $author_image_path; ?>" 
                                     alt="Profile" 
                                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <span style="display: none; width: 40px; height: 40px; border-radius: 50%; background-color: var(--primary-color); color: white; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($post['full_name'], 0, 1)); ?>
                                </span>
                                <div>
                                    <h4><?php echo htmlspecialchars($post['full_name']); ?></h4>
                                    <span style="color: var(--text-secondary); font-size: 12px;"><?php echo formatDate($post['created_at']); ?></span>
                                </div>
                            </div>
                            
                            <h5><?php echo htmlspecialchars($post['title']); ?></h5>
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            
                            <?php if ($post['image']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" style="width: 100%; max-width: 400px; border-radius: 8px; margin-top: 12px;">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($posts)): ?>
                        <p class="no-data">No posts yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
