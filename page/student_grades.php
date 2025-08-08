<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập và role
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SESSION['user']['role'] !== 'student') {
    header('Location: ../page/dashboard.php');
    exit();
}

// Lấy thông tin sinh viên
$user_id = $_SESSION['user']['id'];
try {
    // Lấy thông tin cơ bản
    $stmt = $conn->prepare("
        SELECT u.FullName, c.ClassName, p.ProgramName
        FROM Users u
        LEFT JOIN Class c ON u.ClassID = c.ClassID
        LEFT JOIN ProgramInfo p ON u.ProgramID = p.ProgramID
        WHERE u.UserID = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception("Không tìm thấy thông tin sinh viên");
    }

    // Lấy điểm tổng kết các môn học
    $stmt = $conn->prepare("
        SELECT 
            s.SubjectID,
            s.SubjectName,
            s.Credit,
            e.Semester,
            g.Midterm,
            g.Final,
            g.Attendance,
            g.TotalGPA as GPA,
            CASE 
                WHEN g.TotalGPA >= 8.5 THEN 'A'
                WHEN g.TotalGPA >= 7.0 THEN 'B'
                WHEN g.TotalGPA >= 5.5 THEN 'C'
                WHEN g.TotalGPA >= 4.0 THEN 'D'
                ELSE 'F'
            END as Grade
        FROM Enrollments e
        JOIN Subjects s ON e.SubjectID = s.SubjectID
        JOIN Grades g ON e.EnrollmentID = g.EnrollmentID
        WHERE e.StudentID = ?
        ORDER BY e.Semester DESC, s.SubjectName ASC
    ");
    $stmt->execute([$user_id]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tính GPA trung bình
    $stmt = $conn->prepare("
        SELECT AVG(g.TotalGPA) as avg_gpa
        FROM Grades g
        JOIN Enrollments e ON g.EnrollmentID = e.EnrollmentID
        WHERE e.StudentID = ?
    ");
    $stmt->execute([$user_id]);
    $gpa_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $gpa = $gpa_result['avg_gpa'] ? round($gpa_result['avg_gpa'], 2) : 0;

    // Tính tổng số tín chỉ đã hoàn thành
    $stmt = $conn->prepare("
        SELECT SUM(s.Credit) as total_credits
        FROM Enrollments e
        JOIN Subjects s ON e.SubjectID = s.SubjectID
        WHERE e.StudentID = ? AND e.GPA IS NOT NULL
    ");
    $stmt->execute([$user_id]);
    $credits_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $completed_credits = $credits_result['total_credits'] ?? 0;

} catch (PDOException $e) {
    die("Lỗi khi truy vấn thông tin học tập: " . $e->getMessage());
}

// Lấy initial cho avatar
$initial = strtoupper(mb_substr($student['FullName'], 0, 1, "UTF-8"));
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả học tập - Quản lý sinh viên</title>
    <!-- Google Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=stat_minus_1" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/css_header.css">
    <link rel="stylesheet" href="../assets/css/css_student.css">
    <link rel="stylesheet" href="../assets/css/css_student_grades.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="grades-container">
        <div class="no-print">
            <h2>Kết quả học tập</h2>
            <p>Xem điểm chi tiết các môn học theo từng học kỳ</p>
        </div>

        <div class="summary-card no-print">
            <div class="summary-item">
                <div class="summary-value"><?= $gpa ?></div>
                <div class="summary-label">GPA trung bình</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?= count($grades) ?></div>
                <div class="summary-label">Môn học đã hoàn thành</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?= $completed_credits ?></div>
                <div class="summary-label">Tín chỉ tích lũy</div>
            </div>
            <button class="print-btn no-print" onclick="window.print()">
                <span class="material-symbols-outlined">print</span>
                In kết quả
            </button>
        </div>

        <?php if (empty($grades)): ?>
            <div class="no-grades">
                <p>Bạn chưa có điểm môn học nào được ghi nhận</p>
            </div>
        <?php else: ?>
            <!-- Nhóm điểm theo học kỳ -->
            <?php
            $semesters = [];
            foreach ($grades as $grade) {
                $semesters[$grade['Semester']][] = $grade;
            }
            krsort($semesters); // Sắp xếp học kỳ mới nhất lên đầu
            ?>

            <div class="semester-tabs no-print">
                <?php foreach ($semesters as $semester => $grades): ?>
                    <div class="tab-button" onclick="showSemester('<?= htmlspecialchars($semester) ?>')">
                        <?= htmlspecialchars($semester) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php foreach ($semesters as $semester => $grades): ?>
                <div class="semester-content" id="semester-<?= htmlspecialchars($semester) ?>">
                    <table class="grades-table">
                        <thead>
                            <tr>
                                <th>Mã môn</th>
                                <th>Tên môn học</th>
                                <th>Số tín chỉ</th>
                                <th>Điểm QT</th>
                                <th>Điểm thi</th>
                                <th>Điểm chuyên cần</th>
                                <th>Điểm tổng kết</th>
                                <th>Xếp loại</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $grade): ?>
                                <tr>
                                    <td><?= htmlspecialchars($grade['SubjectID']) ?></td>
                                    <td><?= htmlspecialchars($grade['SubjectName']) ?></td>
                                    <td><?= htmlspecialchars($grade['Credit']) ?></td>
                                    <td><?= $grade['Midterm'] ? htmlspecialchars($grade['Midterm']) : '-' ?></td>
                                    <td><?= $grade['Final'] ? htmlspecialchars($grade['Final']) : '-' ?></td>
                                    <td><?= $grade['Attendance'] ? htmlspecialchars($grade['Attendance']) : '-' ?></td>
                                    <td><?= htmlspecialchars($grade['GPA']) ?></td>
                                    <td class="grade-<?= $grade['Grade'] ?>"><?= htmlspecialchars($grade['Grade']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="../assets/javascript/jsHeader.js"></script>
    <script>
        // Hiển thị học kỳ đầu tiên khi trang được tải
        document.addEventListener('DOMContentLoaded', function() {
            const firstSemester = document.querySelector('.semester-tabs .tab-button');
            if (firstSemester) {
                firstSemester.classList.add('active');
                const semesterId = firstSemester.textContent.trim();
                document.getElementById(`semester-${semesterId}`).classList.add('active');
            }
        });

        function showSemester(semester) {
            // Ẩn tất cả nội dung học kỳ
            document.querySelectorAll('.semester-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Xóa active tất cả tab
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Hiển thị học kỳ được chọn
            document.getElementById(`semester-${semester}`).classList.add('active');
            
            // Active tab được chọn
            document.querySelectorAll('.tab-button').forEach(button => {
                if (button.textContent.trim() === semester) {
                    button.classList.add('active');
                }
            });
        }
    </script>
</body>
</html>