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
    $stmt = $conn->prepare("
        SELECT u.*, c.ClassName, p.ProgramName, p.TotalRequiredCredits, f.FacultyName
        FROM Users u
        LEFT JOIN Class c ON u.ClassID = c.ClassID
        LEFT JOIN ProgramInfo p ON u.ProgramID = p.ProgramID
        LEFT JOIN Faculty f ON c.FacultyID = f.FacultyID
        WHERE u.UserID = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Không tìm thấy thông tin sinh viên");
    }

    // Tính GPA
    $stmt = $conn->prepare("
        SELECT AVG(g.TotalGPA) as avg_gpa
        FROM Grades g
        JOIN Enrollments e ON g.EnrollmentID = e.EnrollmentID
        WHERE e.StudentID = ?
    ");
    $stmt->execute([$user_id]);
    $gpa_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $gpa = $gpa_result['avg_gpa'] ? round($gpa_result['avg_gpa'], 2) : 0;

    // Tín chỉ đã hoàn thành
    $stmt = $conn->prepare("
        SELECT SUM(s.Credit) as total_credits
        FROM Enrollments e
        JOIN Subjects s ON e.SubjectID = s.SubjectID
        WHERE e.StudentID = ? AND e.GPA IS NOT NULL
    ");
    $stmt->execute([$user_id]);
    $credits_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $completed_credits = $credits_result['total_credits'] ?? 0;

    // Số tín chỉ cần và số kỳ còn lại
    $total_required = $student['TotalRequiredCredits'] ?? 120;
    $remaining_credits = max(0, $total_required - $completed_credits);
    $remaining_semesters = ceil($remaining_credits / 15);

} catch (PDOException $e) {
    die("Lỗi khi truy vấn thông tin sinh viên: " . $e->getMessage());
}

// Lấy ký tự đầu làm avatar
$initial = strtoupper(mb_substr($student['FullName'], 0, 1, "UTF-8"));

?>
<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Trang Sinh Viên - Quản lý sinh viên</title>
  <link rel="stylesheet" href="../assets/css/global.css">
  <link rel="stylesheet" href="../assets/css/css_header.css">
  <link rel="stylesheet" href="../assets/css/css_student.css">
  <link rel="stylesheet" href="../assets/css/css_dashboard.css">
  <style>
  .student-header {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 18px;
    margin-bottom: 20px;
  }

  .avatar-circle {
    width: 50px;
    height: 50px;
    background-color: #4CAF50;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 20px;
    border-radius: 50%;
    user-select: none;
  }
  </style>
</head>

<body>


  <div class="dashboard-container">
    <div class="student-header">
      <div class="avatar-circle"><?= $initial ?></div>
      <div>Chào mừng sinh viên <strong><?= htmlspecialchars($student['FullName']) ?></strong>!</div>
    </div>

    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-value"><?= $gpa ?></div>
        <div class="stat-label">GPA hiện tại</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $completed_credits ?></div>
        <div class="stat-label">Tín chỉ đã hoàn thành</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $remaining_semesters ?></div>
        <div class="stat-label">Học kỳ còn lại</div>
      </div>
    </div>

    <div class="functions-container">
      <div class="function-card" onclick="location.href='student_schedule.php'">
        <span class="material-symbols-outlined">calendar_today</span>
        <div class="function-title">Lịch học</div>
        <div class="function-desc">Xem lịch học và thời khóa biểu</div>
      </div>

      <div class="function-card" onclick="location.href='student_grades.php'">
        <span class="material-symbols-outlined">bar_chart</span>
        <div class="function-title">Kết quả học tập</div>
        <div class="function-desc">Xem điểm số và kết quả học tập</div>
      </div>

      <div class="function-card" onclick="location.href='student_profile.php'">
        <span class="material-symbols-outlined">account_circle</span>
        <div class="function-title">Thông tin cá nhân</div>
        <div class="function-desc">Cập nhật thông tin cá nhân</div>
      </div>
    </div>
  </div>

</body>

</html>