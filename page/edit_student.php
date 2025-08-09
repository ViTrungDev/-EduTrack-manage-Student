<?php
include '../config/db.php';

if (!isset($_GET['code'])) {
    die("Không có mã sinh viên");
}

$code = $_GET['code'];

// Lấy dữ liệu sinh viên
if ($conn instanceof PDO) {
    $stmt = $conn->prepare("SELECT UserID, FullName, ProgramID, ClassID FROM Users WHERE StudentCode = :code");
    $stmt->execute([':code' => $code]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $codeEsc = $conn->real_escape_string($code);
    $res = $conn->query("SELECT UserID, FullName, ProgramID, ClassID FROM Users WHERE StudentCode = '$codeEsc'");
    $student = $res->fetch_assoc();
}

if (!$student) {
    die("Không tìm thấy sinh viên");
}

// Lấy danh sách ngành và lớp
$programList = [];
$classList = [];

if ($conn instanceof PDO) {
    $programList = $conn->query("SELECT ProgramID, ProgramName FROM ProgramInfo ORDER BY ProgramName")->fetchAll(PDO::FETCH_ASSOC);
    $classList = $conn->query("SELECT ClassID, ClassName FROM Class ORDER BY ClassName")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $res = $conn->query("SELECT ProgramID, ProgramName FROM ProgramInfo ORDER BY ProgramName");
    while ($row = $res->fetch_assoc()) {
        $programList[] = $row;
    }
    $res = $conn->query("SELECT ClassID, ClassName FROM Class ORDER BY ClassName");
    while ($row = $res->fetch_assoc()) {
        $classList[] = $row;
    }
}
?>

<style>
/* CSS giữ nguyên như trước */
form {
  max-width: 500px;
  min-height: 500px;
  margin: 75px auto;
  padding: 20px 25px;
  background: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

form label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
  color: #333;
}

form input[type="text"],
form select {
  width: 100%;
  padding: 8px 10px;
  margin-bottom: 18px;
  border: 1.5px solid #ccc;
  border-radius: 5px;
  font-size: 15px;
  transition: border-color 0.3s ease;
}

form input[type="text"]:focus,
form select:focus {
  border-color: #4a90e2;
  outline: none;
  box-shadow: 0 0 6px rgba(74, 144, 226, 0.5);
}

form button {
  background-color: #4a90e2;
  color: white;
  border: none;
  padding: 12px 20px;
  font-size: 16px;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: background-color 0.3s ease;
}

form button:hover {
  background-color: #357ABD;
}

@media (max-width: 600px) {
  form {
    margin: 15px;
    padding: 15px 20px;
  }
}
</style>

<form action="../Handlers/update_student.php" method="POST">
  <input type="hidden" name="UserID" value="<?= htmlspecialchars($student['UserID']) ?>" />

  <label>Họ tên:</label>
  <input type="text" name="FullName" value="<?= htmlspecialchars($student['FullName']) ?>" required />

  <label>Ngành:</label>
  <select name="ProgramID" required>
    <?php foreach ($programList as $p): ?>
    <option value="<?= htmlspecialchars($p['ProgramID']) ?>"
      <?= ($student['ProgramID'] == $p['ProgramID']) ? 'selected' : '' ?>>
      <?= htmlspecialchars($p['ProgramName']) ?>
    </option>
    <?php endforeach; ?>
  </select>

  <label>Lớp:</label>
  <select name="ClassID" required>
    <?php foreach ($classList as $c): ?>
    <option value="<?= htmlspecialchars($c['ClassID']) ?>"
      <?= ($student['ClassID'] == $c['ClassID']) ? 'selected' : '' ?>>
      <?= htmlspecialchars($c['ClassName']) ?>
    </option>
    <?php endforeach; ?>
  </select>

  <button type="submit">Lưu thay đổi</button>
</form>