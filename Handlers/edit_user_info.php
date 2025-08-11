<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "../config/db.php";

$userID = $_SESSION['user']['id'] ?? null;
if (!$userID) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['fullname'];
    $email = $_POST['email'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    $sql = "UPDATE users SET FullName = :fullname, Email = :email"
         . ($password ? ", Password = :password" : "")
         . " WHERE UserID = :userID";

    $stmt = $conn->prepare($sql);
    $params = [
        ':fullname' => $fullName,
        ':email' => $email,
        ':userID' => $userID
    ];
    if ($password) {
        $params[':password'] = $password;
    }
    $stmt->execute($params);

    header("Location: ../page/user_info.php");
    exit;
}

// Lấy dữ liệu hiện tại
$stmt = $conn->prepare("SELECT FullName, Email FROM users WHERE UserID = :userID");
$stmt->execute([':userID' => $userID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Sửa thông tin cá nhân</title>
  <style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f9fafb;
    margin: 0;
    padding: 0;
  }

  .container {
    max-width: 500px;
    margin: 3rem auto;
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 0 10px rgb(0 0 0 / 0.1);
  }

  h1 {
    text-align: center;
    margin-bottom: 2rem;
    color: #111827;
  }

  label {
    display: block;
    margin-bottom: 0.3rem;
    font-weight: 600;
    color: #555;
  }

  input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    border: 1px solid #ccc;
    font-size: 1rem;
    background-color: #f9fafb;
  }

  input:focus {
    outline: none;
    border-color: #3b82f6;
    background-color: #fff;
  }

  .btn {
    background-color: #3b82f6;
    color: white;
    padding: 0.6rem 1rem;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    width: 100%;
  }

  .btn:hover {
    background-color: #2563eb;
  }

  .back-link {
    display: inline-block;
    margin-top: 1rem;
    text-decoration: none;
    color: #3b82f6;
  }

  .back-link:hover {
    text-decoration: underline;
  }
  </style>
</head>

<body>
  <main class="container">
    <h1>Sửa thông tin cá nhân</h1>
    <form method="post">
      <label>Họ và tên</label>
      <input type="text" name="fullname" value="<?= htmlspecialchars($user['FullName']) ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" required>

      <label>Mật khẩu mới (để trống nếu không đổi)</label>
      <input type="password" name="password">

      <button type="submit" class="btn">Lưu thay đổi</button>
    </form>
    <a href="../page/user_info.php" class="back-link">← Quay lại</a>
  </main>
</body>

</html>