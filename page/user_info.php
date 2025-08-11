<?php
include "../includes/header.php";
include "../config/db.php";

$userID = $_SESSION['user']['id'] ?? $_SESSION['UserID'] ?? null;

if (!$userID) {
    header("Location: ../index.php");
    exit;
}

try {
    // Lấy thông tin user (có thêm UserID)
    $stmt = $conn->prepare("SELECT UserID, FullName, Role, Email, StudentCode FROM users WHERE UserID = :userID");
    $stmt->execute([':userID' => $userID]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "Người dùng không tồn tại.";
    }
} catch (PDOException $e) {
    $error = "Lỗi truy vấn dữ liệu: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Thông tin cá nhân</title>
  <style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f9fafb;
    color: #333;
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

  .info-row {
    margin-bottom: 1rem;
  }

  .label {
    font-weight: 600;
    color: #555;
    margin-bottom: 0.25rem;
  }

  .value {
    background-color: #f3f4f6;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    font-size: 1rem;
    color: #111;
  }

  .error-message {
    background-color: #fee2e2;
    color: #b91c1c;
    padding: 0.75rem 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    text-align: center;
  }

  .edit-btn {
    background-color: #3b82f6;
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9rem;
  }

  .edit-btn:hover {
    background-color: #2563eb;
  }
  </style>
</head>

<body>
  <main class="container" role="main" aria-labelledby="pageTitle">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <h1 id="pageTitle">Thông tin cá nhân</h1>
      <a href="../Handlers/edit_user_info.php" class="edit-btn">Sửa</a>
    </div>
    <?php if (!empty($error)): ?>
    <div class="error-message" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php else: ?>

    <div class="info-row">
      <div class="label">Họ và tên</div>
      <div class="value"><?= htmlspecialchars($user['FullName']) ?></div>
    </div>

    <div class="info-row">
      <div class="label">Vai trò</div>
      <div class="value"><?= htmlspecialchars(ucfirst($user['Role'])) ?></div>
    </div>

    <div class="info-row">
      <div class="label">Email</div>
      <div class="value"><?= htmlspecialchars($user['Email']) ?></div>
    </div>

    <?php if ($user['Role'] === 'student'): ?>
    <div class="info-row">
      <div class="label">Mã sinh viên</div>
      <div class="value"><?= htmlspecialchars($user['StudentCode'] ?? '') ?></div>
    </div>
    <?php else: ?>
    <div class="info-row">
      <div class="label">ID người dùng</div>
      <div class="value"><?= htmlspecialchars($user['UserID']) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($user['Role'] === 'teacher' || $user['Role'] === 'admin'): ?>
    <div class="info-row">
      <div class="label">Chức vụ</div>
      <div class="value"><?= ($user['Role'] === 'teacher') ? 'Giáo viên' : 'Quản trị viên' ?></div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
  </main>
</body>

</html>