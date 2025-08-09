<?php
// Hàm tạo StudentCode (giữ nguyên)
function generateStudentCode() {
    $prefix = "SV";
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomPart = '';
    for ($i = 0; $i < 6; $i++) {
        $randomPart .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $prefix . $randomPart;
}

// Hàm tạo UserID theo yêu cầu: VSID + 6 ký tự ngẫu nhiên
function generateUserID() {
    $prefix = "VSID";
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomPart = '';
    for ($i = 0; $i < 6; $i++) {
        $randomPart .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $prefix . $randomPart;
}

// Tạo mã student code và user id khi load form
$studentCode = generateStudentCode();
$userID = generateUserID();

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Thêm sinh viên</title>
  <style>
  /* CSS giữ nguyên */
  .form-container {
    max-width: 570px;
    margin: 50px auto;
    background: white;
    padding: 30px 35px;
    border-radius: 10px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  }

  .form-container h2 {
    margin-bottom: 25px;
    color: #333;
    text-align: center;
  }

  label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #555;
  }

  input[type="text"],
  input[type="password"],
  input[type="email"] {
    width: 100%;
    padding: 10px 12px;
    margin-bottom: 20px;
    border: 1.8px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
    transition: border-color 0.3s ease;
  }

  input[type="text"]:focus,
  input[type="password"]:focus,
  input[type="email"]:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 8px rgba(59, 130, 246, 0.3);
  }

  input[readonly] {
    background-color: #eee;
    cursor: not-allowed;
  }

  button {
    width: 100%;
    background-color: #3b82f6;
    color: white;
    border: none;
    padding: 14px 0;
    font-size: 17px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    transition: background-color 0.3s ease;
  }

  button:hover {
    background-color: #1e40af;
  }

  @media (max-width: 480px) {
    .form-container {
      margin: 20px 10px;
      padding: 20px;
    }
  }
  </style>
</head>

<body>

  <div class="form-container">
    <h2>Thêm sinh viên mới</h2>
    <form action="../Handlers/add_student.php" method="POST" autocomplete="off">

      <!-- Gửi UserID ẩn -->
      <input type="hidden" name="UserID" value="<?= htmlspecialchars($userID) ?>" />

      <label for="username">Tên đăng nhập (Username):</label>
      <input type="text" id="username" name="Username" required placeholder="Nhập tên đăng nhập" />

      <!-- THÊM Ô NHẬP HỌ TÊN FULLNAME -->
      <label for="fullname">Họ tên (FullName):</label>
      <input type="text" id="fullname" name="FullName" required placeholder="Nhập họ tên sinh viên" />

      <label for="password">Mật khẩu (Password):</label>
      <input type="password" id="password" name="Password" required placeholder="Nhập mật khẩu" />

      <label for="email">Email:</label>
      <input type="email" id="email" name="Email" placeholder="Nhập email (không bắt buộc)" />

      <label for="studentcode">Mã sinh viên (StudentCode):</label>
      <input type="text" id="studentcode" name="StudentCode" value="<?= htmlspecialchars($studentCode) ?>" readonly />

      <!-- Role mặc định student, ẩn không cho sửa -->
      <input type="hidden" name="Role" value="student" />

      <button type="submit">Thêm sinh viên</button>
    </form>
  </div>

</body>

</html>