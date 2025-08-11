<?php
include "../includes/header.php";
include "../config/db.php";

if (empty($_GET['subject_id'])) {
    die("Thiếu SubjectID");
}
$subject_id = $_GET['subject_id'];

// Lấy thông tin môn học
$stmtSub = $conn->prepare("SELECT SubjectName FROM Subjects WHERE SubjectID = :id");
$stmtSub->execute([':id' => $subject_id]);
$subject = $stmtSub->fetch(PDO::FETCH_ASSOC);
if (!$subject) {
    die("Môn học không tồn tại");
}

// Lấy danh sách lớp cho dropdown
$stmtClass = $conn->query("SELECT ClassID, ClassName FROM Class ORDER BY ClassName");
$classes = $stmtClass->fetchAll(PDO::FETCH_ASSOC);

// Lớp được chọn (nếu có)
$selected_class = $_GET['class_id'] ?? "";

// ====== XỬ LÝ EXPORT CSV ======
if (isset($_GET['export'])) {
    $sqlExport = "
        SELECT 
            u.UserID AS MSSV,
            u.FullName AS HoTen,
            c.ClassName AS Lop,
            MAX(g.Attendance) AS DiemChuyenCan,
            MAX(g.Midterm) AS DiemQT,
            MAX(g.Final) AS DiemThi
        FROM Enrollments e
        JOIN Users u ON e.StudentID = u.UserID AND u.Role = 'student'
        LEFT JOIN Grades g ON e.EnrollmentID = g.EnrollmentID
        LEFT JOIN Class c ON u.ClassID = c.ClassID
        WHERE e.SubjectID = :subject_id
    ";
    $paramsExp = [':subject_id' => $subject_id];
    if (!empty($selected_class)) {
        $sqlExport .= " AND c.ClassID = :class_id";
        $paramsExp[':class_id'] = $selected_class;
    }
    $sqlExport .= " GROUP BY u.UserID, u.FullName, c.ClassName ORDER BY c.ClassName, u.FullName";
    $stmtExp = $conn->prepare($sqlExport);
    $stmtExp->execute($paramsExp);
    $rows = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

    // Xóa bộ đệm để tránh lẫn HTML
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Xuất CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="danhsach_' . $subject_id . '.csv"');

    $output = fopen('php://output', 'w');
    // Ghi BOM để Excel đọc UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    // Ghi tiêu đề
    fputcsv($output, ['MSSV', 'Họ tên', 'Lớp', 'Điểm chuyên cần', 'Điểm QT', 'Điểm thi'], ';');
      foreach ($rows as $r) {
    fputcsv($output, [
        $r['MSSV'],
        $r['HoTen'],
        $r['Lop'],
        $r['DiemChuyenCan'],
        $r['DiemQT'],
        $r['DiemThi']
    ], ';');
}
    fclose($output);
    exit;
}
// ====== END EXPORT CSV ======

// Xử lý khi submit form điểm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['grades'] as $enrollmentID => $data) {
        $attendance = $data['attendance'] !== '' ? $data['attendance'] : null;
        $midterm    = $data['midterm'] !== '' ? $data['midterm'] : null;
        $final      = $data['final'] !== '' ? $data['final'] : null;

        $check = $conn->prepare("SELECT GradeID FROM Grades WHERE EnrollmentID = :eid");
        $check->execute([':eid' => $enrollmentID]);
        $grade = $check->fetch();

        if ($grade) {
            $update = $conn->prepare("
                UPDATE Grades 
                SET Attendance = :att, Midterm = :mid, Final = :fin
                WHERE EnrollmentID = :eid
            ");
            $update->execute([
                ':att' => $attendance,
                ':mid' => $midterm,
                ':fin' => $final,
                ':eid' => $enrollmentID
            ]);
        } else {
            $insert = $conn->prepare("
                INSERT INTO Grades (EnrollmentID, Attendance, Midterm, Final)
                VALUES (:eid, :att, :mid, :fin)
            ");
            $insert->execute([
                ':eid' => $enrollmentID,
                ':att' => $attendance,
                ':mid' => $midterm,
                ':fin' => $final
            ]);
        }
    }
    header("Location: handlerInputPoint.php?subject_id=" . urlencode($subject_id) . "&class_id=" . urlencode($selected_class));
    exit;
}

// SQL lấy danh sách sinh viên
$sql = "
SELECT 
    MIN(e.EnrollmentID) AS EnrollmentID,
    u.UserID AS StudentID,
    u.FullName,
    c.ClassName,
    MAX(g.Attendance) AS Attendance,
    MAX(g.Midterm) AS Midterm,
    MAX(g.Final) AS Final
FROM Enrollments e
JOIN Users u ON e.StudentID = u.UserID AND u.Role = 'student'
LEFT JOIN Grades g ON e.EnrollmentID = g.EnrollmentID
LEFT JOIN Class c ON u.ClassID = c.ClassID
WHERE e.SubjectID = :subject_id
";
$params = [':subject_id' => $subject_id];
if (!empty($selected_class)) {
    $sql .= " AND c.ClassID = :class_id";
    $params[':class_id'] = $selected_class;
}
$sql .= " GROUP BY u.UserID, u.FullName, c.ClassName ORDER BY c.ClassName, u.FullName";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Danh sách SV - <?= htmlspecialchars($subject['SubjectName']) ?></title>
  <link rel="stylesheet" href="../assets/css/global.css">
</head>
<style>
body {
  font-family: Arial, sans-serif;
  background-color: #f8f9fa;
  margin: 0;
  padding: 0;
}

.container {
  max-width: 1100px;
  margin: 40px auto;
  background: white;
  padding: 20px 30px;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

h1 {
  text-align: center;
  color: #333;
  margin-bottom: 20px;
}

table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

thead th {
  background: #007bff;
  color: white;
  padding: 12px;
  text-align: center;
}

tbody td {
  padding: 10px;
  text-align: center;
  border-bottom: 1px solid #ddd;
}

tbody tr:nth-child(even) {
  background: #f2f6fc;
}

tbody tr:hover {
  background: #e9f3ff;
}

input[type="number"] {
  width: 80px;
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 5px;
  text-align: center;
}

button {
  display: block;
  margin: 20px auto 0;
  padding: 10px 20px;
  background: #007bff;
  border: none;
  border-radius: 5px;
  color: white;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.3s;
}

button:hover {
  background: #0056b3;
}

.filter {
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: space-around;
}

select {
  padding: 15px 30px;
  border: 1px solid #ccc;
  border-radius: 5px;
}
</style>

<body>
  <div class="container">
    <h1>Danh sách sinh viên - <?= htmlspecialchars($subject['SubjectName']) ?></h1>

    <!-- Form chọn lớp -->
    <div class="filter">
      <form method="GET" style="display:inline-block;">
        <input type="hidden" name="subject_id" value="<?= htmlspecialchars($subject_id) ?>">
        <label>Chọn lớp:</label>
        <select name="class_id" onchange="this.form.submit()">
          <option value="">-- Tất cả lớp --</option>
          <?php foreach ($classes as $class): ?>
          <option value="<?= htmlspecialchars($class['ClassID']) ?>"
            <?= ($selected_class == $class['ClassID']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($class['ClassName']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </form>

      <!-- Nút export -->
      <form method="GET" style="display:inline-block; margin-left:10px;">
        <input type="hidden" name="subject_id" value="<?= htmlspecialchars($subject_id) ?>">
        <?php if (!empty($selected_class)): ?>
        <input type="hidden" name="class_id" value="<?= htmlspecialchars($selected_class) ?>">
        <?php endif; ?>
        <input type="hidden" name="export" value="1">
        <button type="submit">📄 Xuất Excel</button>
      </form>
    </div>

    <!-- Bảng điểm -->
    <form method="POST">
      <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <thead>
          <tr>
            <th>MSSV</th>
            <th>Họ tên</th>
            <th>Lớp</th>
            <th>Điểm chuyên cần</th>
            <th>Điểm QT</th>
            <th>Điểm thi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
          <tr>
            <td colspan="6">Không có sinh viên</td>
          </tr>
          <?php else: foreach ($students as $sv): ?>
          <tr>
            <td><?= htmlspecialchars($sv['StudentID']) ?></td>
            <td><?= htmlspecialchars($sv['FullName']) ?></td>
            <td><?= htmlspecialchars($sv['ClassName'] ?? '-') ?></td>
            <td><input type="number" step="0.01" name="grades[<?= $sv['EnrollmentID'] ?>][attendance]"
                value="<?= htmlspecialchars($sv['Attendance'] ?? '') ?>"></td>
            <td><input type="number" step="0.01" name="grades[<?= $sv['EnrollmentID'] ?>][midterm]"
                value="<?= htmlspecialchars($sv['Midterm'] ?? '') ?>"></td>
            <td><input type="number" step="0.01" name="grades[<?= $sv['EnrollmentID'] ?>][final]"
                value="<?= htmlspecialchars($sv['Final'] ?? '') ?>"></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <button type="submit">💾 Lưu điểm</button>
    </form>
  </div>
</body>

</html>