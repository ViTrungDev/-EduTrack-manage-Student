<!-- includes/header.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$name = $_SESSION['user']['name'] ?? 'Khách';
$role = $_SESSION['user']['role'] ?? 'guest';
$email = $_SESSION['user']['email'] ?? 'Chưa đăng nhập';
$initial = strtoupper(mb_substr($name, 0, 1, "UTF-8"));
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Quản lý sinh viên</title>
  <!-- Google Material Symbols -->
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=stat_minus_1" />
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/global.css">
  <link rel="stylesheet" href="../assets/css/css_header.css">
</head>

<body>
  <header class="header">
    <div class="header-left">
      <i class="icon-box fa-solid fa-graduation-cap"></i>
      <h2 class="header-title">Quản lý sinh viên</h2>
    </div>

    <!-- Thanh nav -->
    <nav class="header-nav">
      <ul class="nav-list">
        <li class="nav-item"><a href="../page/dashboard.php">Trang chủ</a></li>
        <li class="nav-item"><a href="../page/listStudents.php">Danh sách sinh viên</a></li>

        <?php if ($role !== 'teacher'): ?>
        <li class="nav-item"><a href="../page/manage_teachers.php">Quản lý giáo viên</a></li>
        <?php endif; ?>

        <li class="nav-item"><a href="../page/add_student.php">Thêm sinh viên</a></li>
      </ul>
    </nav>

    <!-- Thông tin người dùng -->
    <div class="header-right"><a href="../page/ticket.php" class="nav-ticket"><span
          class="material-symbols-outlined">construction</span>
        <p>Ticket</p>
      </a>
      <div class="avatar-circle">
        <span class="initial"><?= htmlspecialchars($initial) ?></span>
      </div>
      <div>
        <div class="username"><?= htmlspecialchars($name) ?></div>
        <div class="role"><?= htmlspecialchars($role) ?></div>
      </div>
      <div class="show-status">
        <span class="show-status-click material-symbols-outlined">stat_minus_1</span>
        <div id="statusIndicator" class="status-indicator">
          <div class="username"><?= htmlspecialchars($name) ?></div>
          <div class="email"><?= htmlspecialchars($email) ?></div>
          <div class="role"><?= htmlspecialchars($role) ?></div>
          <div class="icon-links">
            <div class="icon-user">
              <a class="icon-link" href="../page/user_info.php">
                <span class="material-symbols-outlined">account_circle</span>
                <span class="icon-text">Thông tin người dùng</span>
              </a>
            </div>
            <div class="icon-user">
              <a class="icon-link" href="page/settings.php">
                <span class="material-symbols-outlined">settings</span>
                <span class="icon-text">Cài đặt</span>
              </a>
            </div>
            <div class="icon-logout icon-user">
              <a class="icon-link" href="../Handlers/handler_Logout.php">
                <span class="log-out material-symbols-outlined">logout</span>
                <span class="log-out icon-text">Đăng xuất</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</body>
<script src="../assets/javascript/jsHeader.js"></script>

</html>