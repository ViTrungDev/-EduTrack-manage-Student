<?php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Truy vấn user theo Email hoặc StudentCode
    $stmt = $conn->prepare("SELECT * FROM Users WHERE Email = :username OR StudentCode = :username LIMIT 1");
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Nếu là admin/teacher thì bắt buộc phải đăng nhập bằng Email
        if (($user['Role'] === 'admin' || $user['Role'] === 'teacher') && $username !== $user['Email']) {
            $_SESSION['error'] = "Admin hoặc giáo viên chỉ được đăng nhập bằng Email";
            header("Location: ../index.php");
            exit();
        }

        // So sánh mật khẩu (không mã hóa)
        if ($password === $user['Password']) {
            $_SESSION['user'] = [
                'id' => $user['UserID'],
                'name' => $user['FullName'],
                'role' => $user['Role'],
                'email' => $user['Email'],
            ];
            // Điều hướng theo role
            if ($user['Role'] === 'admin' || $user['Role'] === 'teacher') {
                header("Location: ../page/dashboard.php");
            } elseif ($user['Role'] === 'student') {
                header("Location: ../page/student.php");
            } else {
                $_SESSION['error'] = "Vai trò người dùng không hợp lệ.";
                header("Location: ../index.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Tên đăng nhập hoặc mật khẩu không đúng";
            header("Location: ../index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Tài khoản không tồn tại";
        header("Location: ../index.php");
        exit();
    }
}
?>
