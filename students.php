<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
requireRole(['Admin', 'HOI', 'DHOI']);

$user = getCurrentUser();
$conn = getDBConnection();

// Handle student addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email'] ?? '');
    $grade = sanitize($_POST['grade']);
    $stream = sanitize($_POST['stream'] ?? '');
    $parent_name = sanitize($_POST['parent_name'] ?? '');
    $parent_phone = sanitize($_POST['parent_phone'] ?? '');
    $parent_email = sanitize($_POST['parent_email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $dob = sanitize($_POST['date_of_birth']);
    $admission_number = sanitize($_POST['admission_number'] ?? '');
    
    // Auto-generate admission number if not provided
    if (empty($admission_number)) {
        $stmt = $conn->prepare("SELECT MAX(CAST(admission_number AS UNSIGNED)) as max_num FROM students WHERE admission_number REGEXP '^[0-9]+$'");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $next_num = ($row['max_num'] ?? 0) + 1;
        $admission_number = str_pad($next_num, 3, '0', STR_PAD_LEFT);
        $stmt->close();
    }
    
    // Create user account
    $username = strtolower(str_replace(' ', '', $full_name)) . rand(100, 999);
    $admission_year = date('Y');
    $default_password = "student." . $grade . "." . $admission_year;
    
    $user_id = registerUser($username, $email ?: $username . '@school.local', $default_password, $full_name, 'Student');
    
    if ($user_id) {
        $student_id = 'STU' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
        $admission_date = date('Y-m-d');
        
        $stmt = $conn->prepare("INSERT INTO students (user_id, student_id, admission_number, grade, stream, admission_date, admission_year, parent_name, parent_phone, parent_email, address, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssissss", $user_id, $student_id, $admission_number, $grade, $stream, $admission_date, $admission_year, $parent_name, $parent_phone, $parent_email, $address, $dob);
        $stmt->execute();
        $stmt->close();
        
        header('Location: students.php?success=1');
        exit();
    }
}

// Handle student promotion
if (isset($_GET['promote'])) {
    $student_id = intval($_GET['promote']);
    
    // Get current student grade
    $stmt = $conn->prepare("SELECT grade FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if ($student) {
        $new_grade = $student['grade'] == '7' ? '8' : ($student['grade'] == '8' ? '9' : null);
        $new_status = $student['grade'] == '9' ? 'Graduated' : 'Promoted';
        
        if ($new_grade) {
            $stmt = $conn->prepare("UPDATE students SET grade = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_grade, $new_status, $student_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE students SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $student_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header('Location: students.php?success=1');
    exit();
}

// Get all students
$grade_filter = isset($_GET['grade']) ? sanitize($_GET['grade']) : null;
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'Active';

$students = getStudents($grade_filter, $status_filter);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - EBUSHIBO J.S PORTAL</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Student Management</h1>
                <button class="btn btn-primary" onclick="toggleAddForm()">Add Student</button>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Student added successfully!</div>
            <?php endif; ?>

             Add Student Form 
            <div id="addStudentForm" class="table-container" style="display: none; margin-bottom: 24px;">
                <div class="table-header">
                    <h3>Add New Student</h3>
                </div>
                <form method="POST" style="padding: 24px;">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="grade">Grade *</label>
                        <select id="grade" name="grade" required>
                            <option value="">Select Grade</option>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="stream">Stream</label>
                        <input type="text" id="stream" name="stream" placeholder="A, B, C, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label for="admission_number">Admission Number (Auto-generated if empty)</label>
                        <input type="text" id="admission_number" name="admission_number" placeholder="Leave empty for auto-generation">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="parent_name">Parent/Guardian Name</label>
                        <input type="text" id="parent_name" name="parent_name">
                    </div>
                    
                    <div class="form-group">
                        <label for="parent_phone">Parent Phone</label>
                        <input type="tel" id="parent_phone" name="parent_phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="parent_email">Parent Email</label>
                        <input type="email" id="parent_email" name="parent_email">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleAddForm()">Cancel</button>
                    </div>
                </form>
            </div>

             Filters 
            <div class="table-container" style="margin-bottom: 24px;">
                <form method="GET" style="padding: 16px; display: flex; gap: 16px; align-items: end;">
                    <div class="form-group" style="margin: 0;">
                        <label for="grade">Filter by Grade</label>
                        <select id="grade" name="grade" onchange="this.form.submit()">
                            <option value="">All Grades</option>
                            <option value="7" <?php echo $grade_filter === '7' ? 'selected' : ''; ?>>Grade 7</option>
                            <option value="8" <?php echo $grade_filter === '8' ? 'selected' : ''; ?>>Grade 8</option>
                            <option value="9" <?php echo $grade_filter === '9' ? 'selected' : ''; ?>>Grade 9</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label for="status">Filter by Status</label>
                        <select id="status" name="status" onchange="this.form.submit()">
                            <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Promoted" <?php echo $status_filter === 'Promoted' ? 'selected' : ''; ?>>Promoted</option>
                            <option value="Graduated" <?php echo $status_filter === 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                        </select>
                    </div>
                </form>
            </div>

             Students Table 
            <div class="table-container">
                <div class="table-header">
                    <h3>Students List (<?php echo count($students); ?>)</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Admission No.</th>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Grade</th>
                                <th>Stream</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Admission Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['admission_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td>Grade <?php echo htmlspecialchars($student['grade']); ?></td>
                                    <td><?php echo htmlspecialchars($student['stream'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><span class="badge"><?php echo htmlspecialchars($student['status']); ?></span></td>
                                    <td><?php echo formatDate($student['admission_date']); ?></td>
                                    <td>
                                        <a href="student_details.php?id=<?php echo $student['id']; ?>" class="btn btn-sm">View</a>
                                        <?php if ($student['status'] === 'Active'): ?>
                                            <a href="?promote=<?php echo $student['id']; ?>" class="btn btn-sm" style="background-color: #28a745;" onclick="return confirm('Promote this student to the next grade?')">Promote</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center;">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function toggleAddForm() {
            const form = document.getElementById('addStudentForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
