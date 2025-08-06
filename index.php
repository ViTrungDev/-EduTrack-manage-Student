<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập hệ thống</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!-- CSS riêng -->
    <link rel="stylesheet" href="assets/css/css_login.css">
</head>
<body>
    <div class="login-container">
        <header class="header">
            <i class="icon-box fa-solid fa-graduation-cap"></i>
            <h2>Đăng nhập hệ thống</h2>
            <p>Quản lý sinh viên trường đại học</p>
        </header>

        <form method="POST" action="Handlers/process_login.php">
            <label for="username">Tên đăng nhập</label>
            <div class="input-login input-mail">
                <span class="icon_login material-symbols-outlined">mail</span>
                <input class="input-field" type="text" name="username" required>
            </div>

            <label for="password">Mật khẩu</label>
            <div class="input-login input-password">
                <span class="icon_login material-symbols-outlined">lock</span>
                <input class="input-field" type="password" name="password" required>
            </div>

            <!-- Chỉ hiển thị lỗi nếu có -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error">
                    <span class="icon material-symbols-outlined">report</span>
                    <p><?= htmlspecialchars($_SESSION['error']) ?></p>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <button type="submit">Đăng nhập</button>
        </form>
    </div>
</body>
</html>
