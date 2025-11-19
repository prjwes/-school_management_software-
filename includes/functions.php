<?php
require_once __DIR__ . '/../config/database.php';

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Upload file
function uploadFile($file, $directory) {
    $target_dir = UPLOAD_PATH . $directory . '/';
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $directory . '/' . $new_filename;
    }
    
    return false;
}

// Get student by user ID
function getStudentByUserId($user_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $student;
}

// Get all students with filters
function getStudents($grade = null, $status = null) {
    $conn = getDBConnection();
    
    $sql = "SELECT s.*, u.full_name, u.email FROM students s 
            JOIN users u ON s.user_id = u.id WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($grade) {
        $sql .= " AND s.grade = ?";
        $params[] = $grade;
        $types .= "s";
    }
    
    if ($status) {
        $sql .= " AND s.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $sql .= " ORDER BY s.grade, u.full_name";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    $conn->close();
    
    return $students;
}

// Calculate fee percentage paid
function calculateFeePercentage($student_id) {
    $conn = getDBConnection();
    
    // Get total fees for student's grade
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM fee_types WHERE is_active = 1 AND (grade = (SELECT grade FROM students WHERE id = ?) OR grade = 'All')");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_fees = $result->fetch_assoc()['total'] ?? 0;
    
    // Get total paid
    $stmt = $conn->prepare("SELECT SUM(amount_paid) as paid FROM fee_payments WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_paid = $result->fetch_assoc()['paid'] ?? 0;
    
    $stmt->close();
    $conn->close();
    
    if ($total_fees == 0) return 0;
    
    return round(($total_paid / $total_fees) * 100, 2);
}

// Export to Excel (CSV format)
function exportToExcel($data, $filename, $headers) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

function getRubric($marks) {
    $marks = floatval($marks);
    if ($marks < 11) return 'BE2';
    if ($marks < 21) return 'BE1';
    if ($marks < 31) return 'AE2';
    if ($marks < 41) return 'AE1';
    if ($marks >= 41 && $marks <= 57) return 'ME2';
    if ($marks < 74) return 'ME1';
    if ($marks < 89) return 'EE2';
    if ($marks >= 90 && $marks <= 99) return 'EE1';
    if ($marks >= 100) return 'EE1';
    return 'N/A';
}
?>
