<?php
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Phương thức không hợp lệ.");
}

$userID = $_POST['UserID'] ?? '';
if (!$userID) {
    die("Không xác định được sinh viên để cập nhật.");
}

$fullName = trim($_POST['FullName'] ?? '');
$classID = $_POST['ClassID'] ?? null;
$programID = $_POST['ProgramID'] ?? null;

if ($fullName === '') {
    die("Tên không được để trống.");
}

if (!$classID) {
    var_dump($_POST['ClassID'] ?? null);
    die("Chưa chọn lớp.");
}

if (!$programID) {
    die("Chưa chọn ngành.");
}

// Kiểm tra ClassID tồn tại
if ($conn instanceof PDO) {
    $stmt = $conn->prepare("SELECT 1 FROM Class WHERE ClassID = :classID");
    $stmt->execute([':classID' => $classID]);
    if (!$stmt->fetch()) {
        die("Lớp không tồn tại.");
    }

    // Kiểm tra ProgramID tồn tại
    $stmt = $conn->prepare("SELECT 1 FROM ProgramInfo WHERE ProgramID = :programID");
    $stmt->execute([':programID' => $programID]);
    if (!$stmt->fetch()) {
        die("Ngành không tồn tại.");
    }

    // Cập nhật
    $sql = "UPDATE Users SET FullName = :fullName, ClassID = :classID, ProgramID = :programID WHERE UserID = :userID";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':fullName' => $fullName,
        ':classID' => $classID,
        ':programID' => $programID,
        ':userID' => $userID
    ]);
} else {
    $fullNameEsc = $conn->real_escape_string($fullName);
    $classIDEsc = $conn->real_escape_string($classID);
    $programIDEsc = intval($programID);
    $userIDEsc = $conn->real_escape_string($userID);

    $checkClass = $conn->query("SELECT 1 FROM Class WHERE ClassID = '$classIDEsc' LIMIT 1");
    if (!$checkClass || $checkClass->num_rows === 0) {
        die("Lớp không tồn tại.");
    }
    $checkProgram = $conn->query("SELECT 1 FROM ProgramInfo WHERE ProgramID = $programIDEsc LIMIT 1");
    if (!$checkProgram || $checkProgram->num_rows === 0) {
        die("Ngành không tồn tại.");
    }

    $sql = "UPDATE Users SET FullName='$fullNameEsc', ClassID='$classIDEsc', ProgramID=$programIDEsc WHERE UserID='$userIDEsc'";
    if (!$conn->query($sql)) {
        die("Lỗi cập nhật: " . $conn->error);
    }
}

header("Location: ../page/listStudents.php?msg=update_success");
exit;