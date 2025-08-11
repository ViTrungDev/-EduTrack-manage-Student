<?php
include "../includes/header.php";
include "../config/db.php"; // Kết nối PDO

// ===== Lấy danh sách ngành học =====
$sqlProgram = "SELECT ProgramID, ProgramName FROM ProgramInfo ORDER BY ProgramName";
$stmtProgram = $conn->prepare($sqlProgram);
$stmtProgram->execute();
$programs = $stmtProgram->fetchAll(PDO::FETCH_ASSOC);

// ===== Xử lý lọc ngành =====
$where = "";
$params = [];
if (!empty($_GET['program_id'])) {
    $where = "WHERE s.ProgramID = :program_id";
    $params[':program_id'] = $_GET['program_id'];
}

// ===== Lấy danh sách môn học kèm số SV và SV đã có điểm =====
$sql = "
SELECT 
    s.SubjectID,
    s.SubjectName,
    s.Credit,
    sch.DayOfWeek,
    sch.StartTime,
    sch.EndTime,
    sch.Room,
    COUNT(DISTINCT e.StudentID) AS TotalStudents,
    COUNT(DISTINCT CASE WHEN g.TotalGPA IS NOT NULL THEN e.StudentID END) AS StudentsWithGrades
FROM Subjects s
LEFT JOIN Schedule sch ON s.SubjectID = sch.SubjectID
LEFT JOIN Enrollments e ON s.SubjectID = e.SubjectID
LEFT JOIN Grades g ON e.EnrollmentID = g.EnrollmentID
$where
GROUP BY s.SubjectID, s.SubjectName, s.Credit, sch.DayOfWeek, sch.StartTime, sch.EndTime, sch.Room
ORDER BY s.SubjectName;
";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Map thứ tiếng Anh -> tiếng Việt =====
$days = [
    'Mon' => 'Thứ 2', 'Tue' => 'Thứ 3', 'Wed' => 'Thứ 4',
    'Thu' => 'Thứ 5', 'Fri' => 'Thứ 6', 'Sat' => 'Thứ 7', 'Sun' => 'Chủ nhật'
];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/css_manage_program.css">
  <link rel="stylesheet" href="../assets/css/global.css">
  <title>Quản lý môn học</title>
</head>

<body>
  <div class="container">
    <h1>Quản lý môn học</h1>
    <!-- Bộ lọc ngành -->
    <div class="filter">
      <div class="filter-item">
        <label for="nganhHoc">Chọn ngành học:</label>
        <form method="GET" style="display:inline-block;">
          <select name="program_id" id="nganhHoc" class="select-filter" onchange="this.form.submit()">
            <option value="">Tất cả ngành</option>
            <?php foreach($programs as $p): ?>
            <option value="<?= $p['ProgramID'] ?>"
              <?= (!empty($_GET['program_id']) && $_GET['program_id'] == $p['ProgramID']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['ProgramName']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    </div>

    <!-- Danh sách môn học -->
    <div class="courses">
      <?php if(empty($courses)): ?>
      <p>Không có môn học nào.</p>
      <?php else: ?>
      <?php foreach($courses as $row): ?>
      <div class="course">
        <div class="course-header">
          <h2><?= htmlspecialchars($row['SubjectName']) ?></h2>
          <span class="credits"><?= $row['Credit'] ?> tín chỉ</span>
        </div>
        <span class="course-code"><?= htmlspecialchars($row['SubjectID']) ?></span>
        <p style="color:#ccc">
          <?php
                if (!empty($row['DayOfWeek'])) {
                    echo $days[$row['DayOfWeek']] . ", " 
                        . date('H:i', strtotime($row['StartTime'])) 
                        . "-" . date('H:i', strtotime($row['EndTime'])) 
                        . ", phòng " . htmlspecialchars($row['Room']);
                } else {
                    echo "Chưa xếp lịch";
                }
              ?>
        </p>
        <div class="student-info">
          <p style="color:#ccc">Số sinh viên:</p>
          <p style="font-size: 20px;"><?= $row['TotalStudents'] ?></p>
          <p style="color:#ccc">Đã nhập điểm:</p>
          <p style="font-size: 20px;"><?= $row['StudentsWithGrades'] . '/' . $row['TotalStudents'] ?></p>
        </div>
        <div class="course-actions">
          <a href="../Handlers/handlerInputPoint.php?subject_id=<?= urlencode($row['SubjectID']) ?>" class="btn">Danh
            sách SV</a>
          <a href="../Handlers/handlerInputPoint.php?subject_id=<?= urlencode($row['SubjectID']) ?>"
            class="btn green">Nhập điểm</a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>

</html>