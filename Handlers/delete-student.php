<?php
include '../config/db.php';  // file kết nối DB

if (!isset($_GET['code']) || empty($_GET['code'])) {
    die('Không có mã sinh viên để xóa.');
}

$studentCode = $_GET['code'];

try {
    if ($conn instanceof PDO) {
        // chuẩn bị câu lệnh xóa với tham số
        $stmt = $conn->prepare("DELETE FROM `Users` WHERE StudentCode = :code");
        $stmt->bindValue(':code', $studentCode, PDO::PARAM_STR);
        $stmt->execute();

        // check xem có bản ghi nào bị xóa không
        if ($stmt->rowCount() > 0) {
            header('Location: ../page/listStudents.php?msg=Xóa thành công');
            exit;
        } else {
            die('Không tìm thấy sinh viên để xóa.');
        }

    } elseif ($conn instanceof mysqli) {
        // escape tránh lỗi SQL Injection
        $studentCodeEsc = $conn->real_escape_string($studentCode);
        $sql = "DELETE FROM `Users` WHERE StudentCode = '$studentCodeEsc'";
        if ($conn->query($sql)) {
            if ($conn->affected_rows > 0) {
                header('Location: ../page/listStudents.php?msg=Xóa thành công');
                exit;
            } else {
                die('Không tìm thấy sinh viên để xóa.');
            }
        } else {
            die('Lỗi SQL: ' . $conn->error);
        }
    } else {
        die('Không có kết nối DB hợp lệ.');
    }
} catch (Exception $e) {
    die('Lỗi khi xóa sinh viên: ' . $e->getMessage());
}
?>