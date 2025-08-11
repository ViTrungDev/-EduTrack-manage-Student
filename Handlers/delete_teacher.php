<?php
include "../config/db.php";

if (!isset($_GET['id'])) {
    die("Thiếu thông tin cần thiết để xóa giáo viên.");
}

$teacherID = $_GET['id'];
// Có thể thêm tham số forceDelete để bắt buộc xóa
$forceDelete = isset($_GET['forceDelete']) && $_GET['forceDelete'] == '1';

try {
    $conn->beginTransaction();

    if (!$forceDelete) {
        // Kiểm tra xem giáo viên này có làm cố vấn lớp nào không
        $stmtCheckClass = $conn->prepare("SELECT COUNT(*) FROM class WHERE AdvisorID = :teacherID");
        $stmtCheckClass->execute([':teacherID' => $teacherID]);
        $count = $stmtCheckClass->fetchColumn();

        if ($count > 0) {
            throw new Exception("Giáo viên này đang là cố vấn lớp, vui lòng chọn giáo viên thay thế hoặc xóa bằng cách bật forceDelete.");
        }
    }

    // Xóa giáo viên (AdvisorID sẽ tự set NULL do ON DELETE SET NULL)
    $stmtDelete = $conn->prepare("DELETE FROM Users WHERE UserID = :teacherID AND Role = 'teacher'");
    $stmtDelete->execute([':teacherID' => $teacherID]);

    $conn->commit();

    header("Location: ../page/manage_teachers.php?msg=Xóa giáo viên thành công");
    exit;

} catch (Exception $e) {
    $conn->rollBack();
    die("Lỗi khi xóa giáo viên: " . htmlspecialchars($e->getMessage()));
}