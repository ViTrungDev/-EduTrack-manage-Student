<?php
include '../config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $UserID = $_POST['UserID'] ?? '';
    $Username = $_POST['Username'] ?? '';
    $FullName = $_POST['FullName'] ?? '';  
    $Password = $_POST['Password'] ?? '';
    $Email = $_POST['Email'] ?? null; // Email không bắt buộc
    $Role = $_POST['Role'] ?? 'student'; // mặc định student
    $StudentCode = $_POST['StudentCode'] ?? '';

    // Kiểm tra dữ liệu bắt buộc
    if (!$UserID || !$Username || !$Password || !$StudentCode || !$FullName) {  // thêm $FullName
        die('Dữ liệu gửi lên không đầy đủ.');
    }

    try {
        if ($conn instanceof PDO) {
            // Kiểm tra trùng username hoặc studentcode (optional)
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE Username = :username OR StudentCode = :studentcode");
            $checkStmt->execute([':username' => $Username, ':studentcode' => $StudentCode]);
            if ($checkStmt->fetchColumn() > 0) {
                die('Tên đăng nhập hoặc mã sinh viên đã tồn tại.');
            }

            $sql = "INSERT INTO users (UserID, Username, FullName, Password, Email, Role, StudentCode) 
                    VALUES (:userid, :username, :fullname, :password, :email, :role, :studentcode)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':userid' => $UserID,
                ':username' => $Username,
                ':fullname' => $FullName,
                ':password' => $Password,  // Không mã hóa mật khẩu theo yêu cầu
                ':email' => $Email,
                ':role' => $Role,
                ':studentcode' => $StudentCode
            ]);

            echo "Thêm sinh viên thành công!";
        } else {
            // Trường hợp mysqli
            $escapedUsername = $conn->real_escape_string($Username);
            $escapedStudentCode = $conn->real_escape_string($StudentCode);
            $checkQuery = "SELECT COUNT(*) AS cnt FROM users WHERE Username = '$escapedUsername' OR StudentCode = '$escapedStudentCode'";
            $res = $conn->query($checkQuery);
            $row = $res->fetch_assoc();
            if ($row['cnt'] > 0) {
                die('Tên đăng nhập hoặc mã sinh viên đã tồn tại.');
            } 

            $escapedUserID = $conn->real_escape_string($UserID);
            $escapedFullName = $conn->real_escape_string($FullName);
            $escapedPassword = $conn->real_escape_string($Password);
            $escapedEmail = $Email ? $conn->real_escape_string($Email) : null;
            $escapedRole = $conn->real_escape_string($Role);

            $sql = "INSERT INTO users (UserID, Username, FullName, Password, Email, Role, StudentCode) VALUES 
                ('$escapedUserID', '$escapedUsername', '$escapedFullName', '$escapedPassword', " . ($escapedEmail ? "'$escapedEmail'" : "NULL") . ", '$escapedRole', '$escapedStudentCode')";
            if ($conn->query($sql) === TRUE) {
                header("Location: ../page/listStudent.php?add_student=success");
            } else {
                echo "Lỗi: " . $conn->error;
            }
        }
    } catch (Exception $e) {
        die("Lỗi khi thêm sinh viên: " . $e->getMessage());
    }
} else {
    die("Phương thức gửi dữ liệu không hợp lệ.");
}
?>