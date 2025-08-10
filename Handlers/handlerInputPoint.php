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

// Xử lý khi submit form điểm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['grades'] as $enrollmentID => $data) {
        $attendance = $data['attendance'] !== '' ? $data['attendance'] : null;
        $midterm    = $data['midterm'] !== '' ? $data['midterm'] : null;
        $final      = $data['final'] !== '' ? $data['final'] : null;

        // Kiểm tra đã có điểm chưa
        $check = $conn->prepare("SELECT GradeID FROM Grades WHERE EnrollmentID = :eid");
        $check->execute([':eid' => $enrollmentID]);
        $grade = $check->fetch();

        if ($grade) {
            // Update
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
            // Insert
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
    header("Location: ../page/pageProgram.php?subject_id=" . urlencode($subject_id));
    exit;
}

// Lấy danh sách sinh viên (mỗi MSSV chỉ 1 dòng)
$sql = "
SELECT 
    MIN(e.EnrollmentID) AS EnrollmentID, -- Lấy EnrollmentID nhỏ nhất để lưu điểm
    u.UserID AS StudentID,
    u.FullName,
    MAX(g.Attendance) AS Attendance,
    MAX(g.Midterm) AS Midterm,
    MAX(g.Final) AS Final
FROM Enrollments e
JOIN Users u ON e.StudentID = u.UserID AND u.Role = 'student'
LEFT JOIN Grades g ON e.EnrollmentID = g.EnrollmentID
WHERE e.SubjectID = :subject_id
GROUP BY u.UserID, u.FullName
ORDER BY u.FullName
";
$stmt = $conn->prepare($sql);
$stmt->execute([':subject_id' => $subject_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Danh sách SV - <?= htmlspecialchars($subject['SubjectName']) ?></title>
  <link rel="stylesheet" href="../assets/css/global.css">
  <style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
  }

  .container {
    max-width: 1000px;
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
  </style>
</head>

<body>
  <div class="container">
    <h1>Danh sách sinh viên - <?= htmlspecialchars($subject['SubjectName']) ?></h1>
    <form method="POST">
      <table>
        <thead>
          <tr>
            <th>MSSV</th>
            <th>Họ tên</th>
            <th>Điểm chuyên cần</th>
            <th>Điểm QT</th>
            <th>Điểm thi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $sv): ?>
          <tr>
            <td><?= htmlspecialchars($sv['StudentID']) ?></td>
            <td><?= htmlspecialchars($sv['FullName']) ?></td>
            <td><input type="number" step="0.01" name="grades[<?= $sv['EnrollmentID'] ?>][attendance]"
                value="<?= htmlspecialchars($sv['Attendance'] ?? '') ?>"></td>
            <td><input type="number" step="0.01" name="grades[<?= $sv['EnrollmentID'] ?>][midterm]"
                value="<?= htmlspecialchars($sv['Midterm'] ?? '') ?>"></td>
            <td><input type="number" step="0.01" name="grades[<?= $sv['EnrollmentID'] ?>][final]"
                value="<?= htmlspecialchars($sv['Final'] ?? '') ?>"></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <button type="submit">💾 Lưu điểm</button>
    </form>
  </div>
</body>

</html>