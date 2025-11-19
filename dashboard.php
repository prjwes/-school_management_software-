<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user = getCurrentUser();
$role = $user['role'];

// Get dashboard statistics
$conn = getDBConnection();

$stats = [];

if ($role === 'Student') {
    $student = getStudentByUserId($user['id']);
    
    // Get student's exam results
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM exam_results WHERE student_id = ?");
    $stmt->bind_param("i", $student['id']);
    $stmt->execute();
    $stats['exams'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get fee percentage
    $stats['fee_percentage'] = calculateFeePercentage($student['id']);
    
    // Get clubs joined
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM club_members WHERE student_id = ?");
    $stmt->bind_param("i", $student['id']);
    $stmt->execute();
    $stats['clubs'] = $stmt->get_result()->fetch_assoc()['count'];
    
    $stmt->close();
} else {
    // Admin/Staff statistics
    $stats['total_students'] = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'Active'")->fetch_assoc()['count'];
    $stats['total_exams'] = $conn->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'];
    $stats['total_clubs'] = $conn->query("SELECT COUNT(*) as count FROM clubs")->fetch_assoc()['count'];
    $stats['total_notes'] = $conn->query("SELECT COUNT(*) as count FROM notes")->fetch_assoc()['count'];
}

$search_query = sanitize($_GET['search'] ?? '');
$search_results = [];

if (!empty($search_query)) {
    // Search students
    $search_term = "%{$search_query}%";
    $stmt = $conn->prepare("SELECT 'Student' as type, s.id, s.admission_number, u.full_name, s.grade FROM students s JOIN users u ON s.user_id = u.id WHERE s.admission_number LIKE ? OR u.full_name LIKE ? LIMIT 5");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $search_results['students'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Search exams
    $stmt = $conn->prepare("SELECT 'Exam' as type, e.id, e.exam_name, e.grade, e.exam_date FROM exams e WHERE e.exam_name LIKE ? OR e.exam_type LIKE ? LIMIT 5");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $search_results['exams'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Search users
    $stmt = $conn->prepare("SELECT 'User' as type, u.id, u.full_name, u.role, u.email FROM users u WHERE u.full_name LIKE ? OR u.email LIKE ? LIMIT 5");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $search_results['users'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Search fee records
    $stmt = $conn->prepare("SELECT 'Fee' as type, fp.id, u.full_name as student_name, ft.fee_name, fp.amount_paid FROM fee_payments fp JOIN students s ON fp.student_id = s.id JOIN users u ON s.user_id = u.id JOIN fee_types ft ON fp.fee_type_id = ft.id WHERE u.full_name LIKE ? OR ft.fee_name LIKE ? LIMIT 5");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $search_results['fees'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_news'])) {
    $title = sanitize($_POST['news_title'] ?? '');
    $content = sanitize($_POST['news_content']);
    $media_type = sanitize($_POST['media_type']);
    $media_url = sanitize($_POST['media_url'] ?? '');
    
    // Handle image/video upload
    if ($media_type === 'image' || $media_type === 'video') {
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === 0) {
            $media_url = uploadFile($_FILES['media_file'], 'news');
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO news_posts (user_id, title, content, media_type, media_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user['id'], $title, $content, $media_type, $media_url);
    $stmt->execute();
    $stmt->close();
    
    header('Location: dashboard.php');
    exit();
}

if (isset($_GET['delete_news'])) {
    $news_id = intval($_GET['delete_news']);
    
    // Check if user is admin or post owner
    $stmt = $conn->prepare("SELECT user_id FROM news_posts WHERE id = ?");
    $stmt->bind_param("i", $news_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    
    if ($post && ($role === 'Admin' || $post['user_id'] == $user['id'])) {
        $stmt = $conn->prepare("DELETE FROM news_posts WHERE id = ?");
        $stmt->bind_param("i", $news_id);
        $stmt->execute();
    }
    
    $stmt->close();
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_comment'])) {
    $news_id = intval($_POST['news_id']);
    $comment = sanitize($_POST['comment']);
    
    $stmt = $conn->prepare("INSERT INTO news_comments (news_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $news_id, $user['id'], $comment);
    $stmt->execute();
    $stmt->close();
    
    header('Location: dashboard.php');
    exit();
}

$news_posts = [];
$stmt = $conn->query("SELECT n.*, u.full_name, u.profile_image 
                      FROM news_posts n 
                      JOIN users u ON n.user_id = u.id 
                      ORDER BY n.created_at DESC 
                      LIMIT 20");
$news_posts = $stmt->fetch_all(MYSQLI_ASSOC);

// Get comments for each post
foreach ($news_posts as &$post) {
    $stmt = $conn->prepare("SELECT c.*, u.full_name, u.profile_image 
                           FROM news_comments c 
                           JOIN users u ON c.user_id = u.id 
                           WHERE c.news_id = ? 
                           ORDER BY c.created_at ASC");
    $stmt->bind_param("i", $post['id']);
    $stmt->execute();
    $post['comments'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - School Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
                <div class="search-container" style="margin-top: 12px;">
                    <input type="text" id="dashboardSearch" name="search" placeholder="Search news, students, exams..." style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 4px; width: 300px; max-width: 100%;">
                </div>
            </div>

            <div class="stats-grid">
                <?php if ($role === 'Student'): ?>
                    <div class="stat-card">
                        <div class="stat-icon">📝</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['exams']; ?></h3>
                            <p>Exams Taken</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['fee_percentage']; ?>%</h3>
                            <p>Fees Paid</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🎭</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['clubs']; ?></h3>
                            <p>Clubs Joined</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_students']; ?></h3>
                            <p>Active Students</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📝</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_exams']; ?></h3>
                            <p>Total Exams</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🎭</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_clubs']; ?></h3>
                            <p>Active Clubs</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📚</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_notes']; ?></h3>
                            <p>Study Materials</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Added News Section -->
            <div class="dashboard-content">
                <div class="news-section">
                    <div class="section-header">
                        <h2>News & Updates</h2>
                        <button class="btn btn-primary" onclick="toggleNewsForm()">Post News</button>
                    </div>

                    <!-- News Post Form -->
                    <div id="newsForm" class="news-form" style="display: none;">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="news_title">Title (Optional)</label>
                                <input type="text" id="news_title" name="news_title" placeholder="Enter title...">
                            </div>
                            
                            <div class="form-group">
                                <label for="news_content">Content</label>
                                <textarea id="news_content" name="news_content" rows="3" required placeholder="What's on your mind?"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="media_type">Media Type</label>
                                <div class="media-type-icons" style="display: flex; gap: 12px; margin-top: 8px;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="radio" name="media_type" value="text" checked onchange="toggleMediaInput()"> 📝 Text
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="radio" name="media_type" value="image" onchange="toggleMediaInput()"> 🖼️ Image
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="radio" name="media_type" value="video" onchange="toggleMediaInput()"> 🎥 Video
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="radio" name="media_type" value="link" onchange="toggleMediaInput()"> 🔗 Link
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group" id="mediaFileInput" style="display: none;">
                                <label for="media_file">Upload File</label>
                                <input type="file" id="media_file" name="media_file" accept="image/*,video/*">
                            </div>
                            
                            <div class="form-group" id="mediaUrlInput" style="display: none;">
                                <label for="media_url">URL</label>
                                <input type="url" id="media_url" name="media_url" placeholder="https://...">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="post_news" class="btn btn-primary">Post</button>
                                <button type="button" class="btn btn-secondary" onclick="toggleNewsForm()">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <!-- News Feed -->
                    <div class="news-feed">
                        <?php foreach ($news_posts as $post): ?>
                            <div class="news-card">
                                <div class="news-header">
                                    <div class="news-author">
                                        <?php 
                                        $author_image = $post['profile_image'] ?? 'default-avatar.png';
                                        $author_image_path = 'uploads/profiles/' . htmlspecialchars($author_image);
                                        if ($author_image === 'default-avatar.png' || empty($author_image)) {
                                            $author_image_path = 'assets/images/default-avatar.png';
                                        }
                                        ?>
                                        <img src="<?php echo $author_image_path; ?>" 
                                             alt="Profile" 
                                             class="author-avatar"
                                             style="display: block;"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <span style="display: none; width: 40px; height: 40px; border-radius: 50%; background-color: var(--primary-color); color: white; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">
                                            <?php echo strtoupper(substr($post['full_name'], 0, 1)); ?>
                                        </span>
                                        <div>
                                            <h4><?php echo htmlspecialchars($post['full_name']); ?></h4>
                                            <span class="news-date"><?php echo formatDate($post['created_at']); ?></span>
                                        </div>
                                    </div>
                                    <?php if ($role === 'Admin' || $post['user_id'] == $user['id']): ?>
                                        <a href="?delete_news=<?php echo $post['id']; ?>" class="btn-delete" onclick="return confirm('Delete this post?')">Delete</a>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($post['title']): ?>
                                    <h3 class="news-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                <?php endif; ?>
                                
                                <p class="news-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                
                                <?php if ($post['media_type'] === 'image' && $post['media_url']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post image" class="news-media">
                                <?php elseif ($post['media_type'] === 'video' && $post['media_url']): ?>
                                    <video controls class="news-media">
                                        <source src="uploads/<?php echo htmlspecialchars($post['media_url']); ?>">
                                    </video>
                                <?php elseif ($post['media_type'] === 'link' && $post['media_url']): ?>
                                    <a href="<?php echo htmlspecialchars($post['media_url']); ?>" target="_blank" class="news-link"><?php echo htmlspecialchars($post['media_url']); ?></a>
                                <?php endif; ?>
                                
                                <!-- Comments Section -->
                                <div class="comments-section">
                                    <h5>Comments (<?php echo count($post['comments']); ?>)</h5>
                                    
                                    <?php foreach ($post['comments'] as $comment): ?>
                                        <div class="comment">
                                            <?php 
                                            $comment_image = $comment['profile_image'] ?? 'default-avatar.png';
                                            $comment_image_path = 'uploads/profiles/' . htmlspecialchars($comment_image);
                                            if ($comment_image === 'default-avatar.png' || empty($comment_image)) {
                                                $comment_image_path = 'assets/images/default-avatar.png';
                                            }
                                            ?>
                                            <img src="<?php echo $comment_image_path; ?>" 
                                                 alt="Profile" 
                                                 class="comment-avatar"
                                                 style="display: block;"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <span style="display: none; width: 32px; height: 32px; border-radius: 50%; background-color: var(--primary-color); color: white; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; font-size: 14px;">
                                                <?php echo strtoupper(substr($comment['full_name'], 0, 1)); ?>
                                            </span>
                                            <div class="comment-content">
                                                <strong><?php echo htmlspecialchars($comment['full_name']); ?></strong>
                                                <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                                <span class="comment-date"><?php echo formatDate($comment['created_at']); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Comment Form -->
                                    <form method="POST" class="comment-form">
                                        <input type="hidden" name="news_id" value="<?php echo $post['id']; ?>">
                                        <textarea name="comment" placeholder="Write a comment..." required></textarea>
                                        <button type="submit" name="post_comment" class="btn btn-sm btn-primary">Comment</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($news_posts)): ?>
                            <p class="no-data">No news posts yet. Be the first to post!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Search Results Section -->
            <?php if (!empty($search_query) && !empty($search_results)): ?>
                <div class="table-container" style="margin-bottom: 24px;">
                    <div class="table-header">
                        <h3>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h3>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $has_results = false;
                                
                                if (!empty($search_results['students'])) {
                                    $has_results = true;
                                    foreach ($search_results['students'] as $student) {
                                        echo '<tr><td>Student</td><td>' . htmlspecialchars($student['full_name']) . ' (' . htmlspecialchars($student['admission_number']) . ') - Grade ' . htmlspecialchars($student['grade']) . '</td><td><a href="student_details.php?id=' . $student['id'] . '" class="btn btn-sm">View</a></td></tr>';
                                    }
                                }
                                
                                if (!empty($search_results['exams'])) {
                                    $has_results = true;
                                    foreach ($search_results['exams'] as $exam) {
                                        echo '<tr><td>Exam</td><td>' . htmlspecialchars($exam['exam_name']) . ' - Grade ' . htmlspecialchars($exam['grade']) . '</td><td><a href="exam_results.php?id=' . $exam['id'] . '" class="btn btn-sm">View Results</a></td></tr>';
                                    }
                                }
                                
                                if (!empty($search_results['users'])) {
                                    $has_results = true;
                                    foreach ($search_results['users'] as $u) {
                                        echo '<tr><td>User</td><td>' . htmlspecialchars($u['full_name']) . ' (' . htmlspecialchars($u['role']) . ')</td><td><a href="settings.php" class="btn btn-sm">View</a></td></tr>';
                                    }
                                }
                                
                                if (!empty($search_results['fees'])) {
                                    $has_results = true;
                                    foreach ($search_results['fees'] as $fee) {
                                        echo '<tr><td>Fee Payment</td><td>' . htmlspecialchars($fee['student_name']) . ' - ' . htmlspecialchars($fee['fee_name']) . '</td><td><a href="fees.php" class="btn btn-sm">View</a></td></tr>';
                                    }
                                }
                                
                                if (!$has_results) {
                                    echo '<tr><td colspan="3" style="text-align: center;">No results found</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function toggleNewsForm() {
            const form = document.getElementById('newsForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        function toggleMediaInput() {
            const mediaType = document.querySelector('input[name="media_type"]:checked').value;
            const fileInput = document.getElementById('mediaFileInput');
            const urlInput = document.getElementById('mediaUrlInput');
            
            fileInput.style.display = 'none';
            urlInput.style.display = 'none';
            
            if (mediaType === 'image' || mediaType === 'video') {
                fileInput.style.display = 'block';
            } else if (mediaType === 'link') {
                urlInput.style.display = 'block';
            }
        }
    </script>
</body>
</html>
