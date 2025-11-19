<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
requireRole(['Admin', 'HOI', 'DHOI', 'DoS_Exams_Teacher']);

$user = getCurrentUser();
$conn = getDBConnection();

// Handle form submission for generating reports
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_reports'])) {
    $exam1_id = isset($_POST['exam1_id']) ? intval($_POST['exam1_id']) : 0;
    $exam2_id = isset($_POST['exam2_id']) ? intval($_POST['exam2_id']) : 0;
    $academic_year = isset($_POST['academic_year']) ? sanitize($_POST['academic_year']) : '';
    $term = isset($_POST['term']) ? sanitize($_POST['term']) : '';

    // Basic validation
    if ($exam1_id <= 0 || $exam2_id <= 0 || $academic_year === '' || $term === '') {
        header('Location: reports.php?error=missing_fields');
        exit();
    }

    // Get exam details
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->bind_param("i", $exam1_id);
    $stmt->execute();
    $exam1 = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->bind_param("i", $exam2_id);
    $stmt->execute();
    $exam2 = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exam1 || !$exam2) {
        header('Location: reports.php?error=invalid_exam');
        exit();
    }

    // Get students (students in the grade of exam1)
    $stmt = $conn->prepare("SELECT s.id, s.admission_number, u.full_name, s.grade FROM students s JOIN users u ON s.user_id = u.id WHERE s.grade = ? AND s.status = 'Active' ORDER BY s.admission_number");
    $stmt->bind_param("s", $exam1['grade']);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Ensure upload directory exists
    $uploadDir = __DIR__ . '/uploads/reports';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            // Failed to create directory
            header('Location: reports.php?error=upload_dir');
            exit();
        }
    }

    // Try to use PhpSpreadsheet to create an XLSX with one sheet per student.
    // If the library isn't available, fall back to the CSV export above.
    // If you want PhpSpreadsheet features, install it via Composer on the server:
    // composer require phpoffice/phpspreadsheet

    $baseName = 'report_cards_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $academic_year) . '_term' . $term . '_' . time();
    $xlsxFilename = $baseName . '.xlsx';
    $csvFilename = $baseName . '.csv';
    $xlsxPath = $uploadDir . '/' . $xlsxFilename;
    $csvPath = $uploadDir . '/' . $csvFilename;

    // If composer autoload exists, require it so classes are available
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    if (class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
    // Build XLSX with sheets per student
    $spreadsheetClass = '\\PhpOffice\\PhpSpreadsheet\\Spreadsheet';
    $spreadsheet = new $spreadsheetClass();
        // We'll iterate students and create sheets; use active sheet for first student
        foreach ($students as $index => $student) {
            if ($index === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            // Safe sheet title (max 31 chars, strip invalid chars)
            $title = preg_replace('/[\\\\\\/\?\*\[\]\:]/', '_', $student['full_name'] . ' - ' . $student['admission_number']);
            $title = mb_substr($title, 0, 31);
            $sheet->setTitle($title ?: ('Sheet' . ($index + 1)));

            $row = 1;
            $sheet->setCellValue('A' . $row, 'EBUSHIBO J.S PORTAL - REPORT CARDS');
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $row++;
            $sheet->setCellValue('A' . $row, 'Academic Year: ' . $academic_year . '    Term: ' . $term);
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $row += 2;

            $sheet->setCellValue('A' . $row, 'Name');
            $sheet->setCellValue('B' . $row, $student['full_name']);
            $sheet->setCellValue('D' . $row, 'ADM');
            $sheet->setCellValue('E' . $row, $student['admission_number']);
            $row++;
            $sheet->setCellValue('A' . $row, 'Grade');
            $sheet->setCellValue('B' . $row, $student['grade']);
            $sheet->setCellValue('D' . $row, 'Term');
            $sheet->setCellValue('E' . $row, $term);
            $row += 2;

            // Fetch results for this student
            $stmt = $conn->prepare("SELECT subject, marks_obtained FROM exam_results WHERE student_id = ? AND exam_id = ? ORDER BY subject");
            $stmt->bind_param("ii", $student['id'], $exam1_id);
            $stmt->execute();
            $exam1_results_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $stmt = $conn->prepare("SELECT subject, marks_obtained FROM exam_results WHERE student_id = ? AND exam_id = ? ORDER BY subject");
            $stmt->bind_param("ii", $student['id'], $exam2_id);
            $stmt->execute();
            $exam2_results_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $exam1_results = [];
            foreach ($exam1_results_raw as $r) {
                $exam1_results[$r['subject']] = $r['marks_obtained'];
            }
            $exam2_results = [];
            foreach ($exam2_results_raw as $r) {
                $exam2_results[$r['subject']] = $r['marks_obtained'];
            }

            $subjects = array_unique(array_merge(array_keys($exam1_results), array_keys($exam2_results)));
            sort($subjects);

            // Header row
            $sheet->setCellValue('A' . $row, 'Subject');
            $sheet->setCellValue('B' . $row, $exam1['exam_name']);
            $sheet->setCellValue('C' . $row, 'Rubric');
            $sheet->setCellValue('D' . $row, $exam2['exam_name']);
            $sheet->setCellValue('E' . $row, 'Rubric');
            $row++;

            foreach ($subjects as $subject) {
                $m1 = isset($exam1_results[$subject]) ? $exam1_results[$subject] : '';
                $m2 = isset($exam2_results[$subject]) ? $exam2_results[$subject] : '';

                $sheet->setCellValue('A' . $row, $subject);
                $sheet->setCellValue('B' . $row, $m1);
                $sheet->setCellValue('C' . $row, function_exists('getRubric') ? getRubric($m1) : '');
                $sheet->setCellValue('D' . $row, $m2);
                $sheet->setCellValue('E' . $row, function_exists('getRubric') ? getRubric($m2) : '');
                $row++;
            }

            $row++;
            $sheet->setCellValue('A' . $row, "Facilitator's remarks based on: core competences, achievements, PCI's development and values.");
            $row++;
            $sheet->setCellValue('A' . $row, '1. BELOW EXPECTATIONS {B.E}    2. APPROACH EXPECTATIONS {A.E}');
            $row++;
            $sheet->setCellValue('A' . $row, '3. MEETING EXPECTATIONS {M.E}     4. EXCEEDING EXPECTATIONS {E.E}');
            $row++;
            $sheet->setCellValue('A' . $row, "FACILITATOR'S SIGNATURE………………….…  DATE………………….…");
            $row++;
            $sheet->setCellValue('A' . $row, "HEAD TEACHER'S SIGNATURE………………….");
            $row++;
            $sheet->setCellValue('A' . $row, 'DATE………………….');
            $row++;
            $sheet->setCellValue('A' . $row, "PARENT'S SIGNATURE………………….  DATE………………….");
            $row++;
            $sheet->setCellValue('A' . $row, 'THE TERM STARTED ON: ___/___/____   CLOSING DATE: ___/___/____');
            $row++;
            $sheet->setCellValue('A' . $row, 'NEXT TERM BEGINS ON: __/__/____');

            // Optionally set some column widths for readability
            foreach (['A','B','C','D','E'] as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // Save XLSX file
        try {
            $writerClass = '\\PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx';
            $writer = new $writerClass($spreadsheet);
            $writer->save($xlsxPath);
        } catch (Exception $e) {
            header('Location: reports.php?error=xlsx_save_failed');
            exit();
        }

        // Redirect to download XLSX
        header('Location: reports.php?success=1&file=' . urlencode($xlsxFilename));
        exit();
    }

    // Fallback: create CSV if PhpSpreadsheet is not available
    $handle = fopen($csvPath, 'w');
    if ($handle === false) {
        header('Location: reports.php?error=cannot_write');
        exit();
    }

    // Ensure CSV is recognised as UTF-8 by Excel (write BOM)
    fwrite($handle, "\xEF\xBB\xBF");

    if (!function_exists('csv_encode')) {
        function csv_encode($value)
        {
            if (is_null($value)) {
                return '';
            }
            $v = trim((string)$value);
            $v = str_replace("\xEF\xBB\xBF", '', $v);
            return mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }
    }

    if (!function_exists('fputcsv_utf8')) {
        function fputcsv_utf8($handle, $row)
        {
            $encoded = array_map('csv_encode', $row);
            return fputcsv($handle, $encoded);
        }
    }

    foreach ($students as $student) {
        // Student header
        fputcsv_utf8($handle, ['EBUSHIBO J.S PORTAL - REPORT CARDS', 'Academic Year: ' . $academic_year, 'Term: ' . $term]);
        fputcsv_utf8($handle, []);
        fputcsv_utf8($handle, ['Name', $student['full_name'], '', 'ADM', $student['admission_number']]);
        fputcsv_utf8($handle, ['Grade', $student['grade'], '', 'Term', $term]);
        fputcsv_utf8($handle, []);

        // Fetch results, keyed by subject
        $stmt = $conn->prepare("SELECT subject, marks_obtained FROM exam_results WHERE student_id = ? AND exam_id = ? ORDER BY subject");
        $stmt->bind_param("ii", $student['id'], $exam1_id);
        $stmt->execute();
        $exam1_results_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $conn->prepare("SELECT subject, marks_obtained FROM exam_results WHERE student_id = ? AND exam_id = ? ORDER BY subject");
        $stmt->bind_param("ii", $student['id'], $exam2_id);
        $stmt->execute();
        $exam2_results_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $exam1_results = [];
        foreach ($exam1_results_raw as $r) {
            $exam1_results[$r['subject']] = $r['marks_obtained'];
        }
        $exam2_results = [];
        foreach ($exam2_results_raw as $r) {
            $exam2_results[$r['subject']] = $r['marks_obtained'];
        }

        $subjects = array_unique(array_merge(array_keys($exam1_results), array_keys($exam2_results)));
        sort($subjects);

        // Table header
        fputcsv_utf8($handle, ['Subject', $exam1['exam_name'], 'Rubric', $exam2['exam_name'], 'Rubric']);

        foreach ($subjects as $subject) {
            $m1 = isset($exam1_results[$subject]) ? $exam1_results[$subject] : '';
            $m2 = isset($exam2_results[$subject]) ? $exam2_results[$subject] : '';
            fputcsv_utf8($handle, [
                $subject,
                $m1,
                function_exists('getRubric') ? getRubric($m1) : '',
                $m2,
                function_exists('getRubric') ? getRubric($m2) : ''
            ]);
        }

        fputcsv_utf8($handle, []);
        fputcsv_utf8($handle, ["Facilitator's remarks based on: core competences, achievements, PCI's development and values."]);
        fputcsv_utf8($handle, ["1. BELOW EXPECTATIONS {B.E}    2. APPROACH EXPECTATIONS {A.E}"]);
        fputcsv_utf8($handle, ["3. MEETING EXPECTATIONS {M.E}     4. EXCEEDING EXPECTATIONS {E.E}"]);
        fputcsv_utf8($handle, ["FACILITATOR'S SIGNATURE………………….…  DATE………………….…"]);
        fputcsv_utf8($handle, ["HEAD TEACHER'S SIGNATURE…………………."]);
        fputcsv_utf8($handle, ["DATE…………………."]);
        fputcsv_utf8($handle, ["PARENT'S SIGNATURE………………….  DATE…………………."]);
        fputcsv_utf8($handle, ["THE TERM STARTED ON: ___/___/____   CLOSING DATE: ___/___/____"]);
        fputcsv_utf8($handle, ["NEXT TERM BEGINS ON: __/__/____"]);
        // Add spacer rows between students
        fputcsv_utf8($handle, []);
        fputcsv_utf8($handle, []);
    }

    // Close CSV handle
    fclose($handle);

    // Redirect to download link (CSV filename is always generated when fallback used)
    header('Location: reports.php?success=1&file=' . urlencode($csvFilename));
    exit();
} // end if POST generate_reports

// Handle download
if (isset($_GET['download_file'])) {
    $file = basename($_GET['download_file']);
    $filepath = __DIR__ . '/uploads/reports/' . $file;

    if (file_exists($filepath)) {
        // Choose content type based on file extension
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        if ($ext === 'xlsx') {
            $ctype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } elseif ($ext === 'csv') {
            $ctype = 'text/csv; charset=UTF-8';
        } else {
            $ctype = 'application/octet-stream';
        }

        header('Content-Type: ' . $ctype);
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Transfer-Encoding: binary');
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Length: ' . filesize($filepath));
        // flush output buffers and read file
        if (ob_get_level()) {
            ob_end_clean();
        }
        readfile($filepath);
        exit();
    } else {
        header('Location: reports.php?error=file_not_found');
        exit();
    }
}

$stmt = $conn->prepare("SELECT * FROM exams ORDER BY exam_date DESC");
$stmt->execute();
$exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Cards - EBUSHIBO J.S PORTAL</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Report Card Management</h1>
                <button class="btn btn-primary" onclick="toggleReportForm()">Generate Report Cards</button>
            </div>

            <?php if (isset($_GET['success']) && isset($_GET['file'])): ?>
                <div class="alert alert-success">
                    Report cards generated successfully! 
                    <a href="?download_file=<?php echo urlencode($_GET['file']); ?>" class="btn btn-sm" style="margin-left: 10px;">Download file</a>
                </div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    Error: <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Generate Report Form -->
            <div id="reportForm" class="table-container" style="display: none; margin-bottom: 24px;">
                <div class="table-header">
                    <h3>Generate Report Cards</h3>
                </div>
                <form method="POST" style="padding: 24px;">
                    <div class="form-group">
                        <label for="exam1_id">Select First Exam *</label>
                        <select id="exam1_id" name="exam1_id" required>
                            <option value="">Select Exam</option>
                            <?php foreach ($exams as $exam): ?>
                                <option value="<?php echo $exam['id']; ?>"><?php echo htmlspecialchars($exam['exam_name']) . ' - Grade ' . $exam['grade']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam2_id">Select Second Exam *</label>
                        <select id="exam2_id" name="exam2_id" required>
                            <option value="">Select Exam</option>
                            <?php foreach ($exams as $exam): ?>
                                <option value="<?php echo $exam['id']; ?>"><?php echo htmlspecialchars($exam['exam_name']) . ' - Grade ' . $exam['grade']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="academic_year">Academic Year</label>
                        <input type="text" id="academic_year" name="academic_year" placeholder="2024-2025" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="term">Term</label>
                        <select id="term" name="term" required>
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3">Term 3</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="generate_reports" class="btn btn-primary">Generate</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleReportForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function toggleReportForm() {
            const form = document.getElementById('reportForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>