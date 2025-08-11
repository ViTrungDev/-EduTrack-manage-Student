<?php 
include "../includes/header.php";
include "../config/db.php";

if (!isset($_GET['id'])) {
    die("Không xác định được giáo viên để chỉnh sửa.");
}

$userID = $_GET['id'];

// Lấy danh sách ngành học để dropdown
$programList = [];
try {
    $stmtProgram = $conn->prepare("SELECT ProgramID, ProgramName FROM ProgramInfo ORDER BY ProgramName ASC");
    $stmtProgram->execute();
    $programList = $stmtProgram->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // log lỗi nếu cần
}

// Khởi tạo biến lưu dữ liệu giáo viên
$teacher = null;
$error = '';
$success = '';

// Lấy thông tin giáo viên hiện tại để điền vào form
try {
    $stmt = $conn->prepare("SELECT UserID, FullName, Email, ProgramID FROM Users WHERE UserID = :userID AND Role = 'teacher'");
    $stmt->execute([':userID' => $userID]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        die("Không tìm thấy giáo viên.");
    }
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $programID = $_POST['programID'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate cơ bản
    if ($fullName === '' || $email === '' || $programID === '') {
        $error = "Vui lòng nhập đầy đủ họ tên, email và ngành học.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } else {
        try {
            // Nếu đổi mật khẩu thì lưu trực tiếp (mật khẩu thô)
            if ($password !== '') {
                $sqlUpdate = "UPDATE Users SET FullName = :fullName, Email = :email, ProgramID = :programID, Password = :password WHERE UserID = :userID";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':fullName' => $fullName,
                    ':email' => $email,
                    ':programID' => $programID,
                    ':password' => $password,
                    ':userID' => $userID
                ]);
            } else {
                $sqlUpdate = "UPDATE Users SET FullName = :fullName, Email = :email, ProgramID = :programID WHERE UserID = :userID";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':fullName' => $fullName,
                    ':email' => $email,
                    ':programID' => $programID,
                    ':userID' => $userID
                ]);
            }

            $success = "Cập nhật thông tin giáo viên thành công.";

            // Cập nhật lại dữ liệu $teacher để hiển thị form sau submit
            $teacher['FullName'] = $fullName;
            $teacher['Email'] = $email;
            $teacher['ProgramID'] = $programID;

        } catch (PDOException $e) {
            $error = "Lỗi khi cập nhật dữ liệu: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Chỉnh sửa Giáo viên</title>
  <link rel="stylesheet" href="../assets/css/css_manage.css" />
  <link rel="stylesheet" href="../assets/css/global.css" />
</head>
<style>
/* global.css (chung) */
body {
  font-family: Arial, sans-serif;
  background-color: #f9fafb;
  color: #333;
  margin: 0;
  padding: 0;
}

.container {
  max-width: 900px;
  margin: 2rem auto;
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 0 10px rgb(0 0 0 / 0.1);
}

h1 {
  margin-bottom: 0.5rem;
  color: #111827;
}

.error-message {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}

.success-message {
  background-color: #dcfce7;
  color: #15803d;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}

/* css_manage.css (quản lý giáo viên) */

form {
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 0.3rem;
  font-weight: 600;
  color: #374151;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
}

.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus,
.form-group input[type="password"]:focus,
.form-group select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgb(59 130 246 / 0.3);
}

.btn-primary {
  background-color: #2563eb;
  color: white;
  border: none;
  padding: 0.6rem 1.2rem;
  font-size: 1rem;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.btn-primary:hover {
  background-color: #1d4ed8;
}

.btn-secondary {
  background-color: #e5e7eb;
  color: #374151;
  border: none;
  padding: 0.6rem 1.2rem;
  font-size: 1rem;
  border-radius: 6px;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  text-align: center;
  line-height: 1.5rem;
}

.btn-secondary:hover {
  background-color: #d1d5db;
}

.teacher-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.teacher-table th,
.teacher-table td {
  padding: 0.75rem 1rem;
  border: 1px solid #e5e7eb;
  text-align: left;
}

.teacher-table th {
  background-color: #f3f4f6;
  font-weight: 600;
  color: #374151;
}

.teacher-table tbody tr:hover {
  background-color: #eff6ff;
}

.actions-column button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0 6px;
}

.actions-column button svg {
  width: 20px;
  height: 20px;
}

.program-label {
  background-color: #e0e7ff;
  color: #3730a3;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: 500;
}
</style>

<body>
  <main class="container" role="main" aria-labelledby="pageTitle">
    <header class="header-top">
      <h1 id="pageTitle">Chỉnh sửa Giáo viên</h1>
    </header>

    <?php if ($error): ?>
    <div class="error-message" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="success-message" role="alert"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" aria-label="Form chỉnh sửa giáo viên" novalidate>
      <div class="form-group">
        <label for="fullName">Họ và tên</label>
        <input type="text" id="fullName" name="fullName" required
          value="<?= htmlspecialchars($teacher['FullName']) ?>" />
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($teacher['Email']) ?>" />
      </div>

      <div class="form-group">
        <label for="programID">Ngành học</label>
        <select id="programID" name="programID" required>
          <option value="">-- Chọn ngành học --</option>
          <?php foreach ($programList as $program): ?>
          <option value="<?= htmlspecialchars($program['ProgramID']) ?>"
            <?= $teacher['ProgramID'] == $program['ProgramID'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($program['ProgramName']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="password">Mật khẩu mới (để trống nếu không đổi)</label>
        <input type="password" id="password" name="password" autocomplete="new-password" />
      </div>

      <button type="submit" class="btn-primary">Lưu thay đổi</button>
      <a href="manage_teachers.php" class="btn-secondary" style="margin-left: 1em;">Hủy</a>
    </form>
  </main>
</body>

</html>