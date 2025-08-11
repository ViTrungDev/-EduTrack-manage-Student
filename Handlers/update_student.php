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
$password = $_POST['Password'] ?? '';

if ($fullName === '') {
    die("Tên không được để trống.");
}

if (!$classID) {
    die("Chưa chọn lớp.");
}

if (!$programID) {
    die("Chưa chọn ngành.");
}

// Lấy mật khẩu cũ từ DB nếu cần
if ($conn instanceof PDO) {
    $stmt = $conn->prepare("SELECT Password FROM Users WHERE UserID = :userID");
    $stmt->execute([':userID' => $userID]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        die("Sinh viên không tồn tại.");
    }
    $oldPassword = $row['Password'];

    // Kiểm tra class tồn tại
    $stmt = $conn->prepare("SELECT 1 FROM Class WHERE ClassID = :classID");
    $stmt->execute([':classID' => $classID]);
    if (!$stmt->fetch()) {
        die("Lớp không tồn tại.");
    }

    // Kiểm tra program tồn tại
    $stmt = $conn->prepare("SELECT 1 FROM ProgramInfo WHERE ProgramID = :programID");
    $stmt->execute([':programID' => $programID]);
    if (!$stmt->fetch()) {
        die("Ngành không tồn tại.");
    }

    // Nếu password mới không rỗng thì cập nhật, ngược lại giữ mật khẩu cũ
    if (trim($password) !== '') {
        $passwordToSave = $password;
    } else {
        $passwordToSave = $oldPassword;
    }

    // Câu lệnh cập nhật có mật khẩu
    $sql = "UPDATE Users SET FullName = :fullName, ClassID = :classID, ProgramID = :programID, Password = :password WHERE UserID = :userID";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':fullName' => $fullName,
        ':classID' => $classID,
        ':programID' => $programID,
        ':password' => $passwordToSave,
        ':userID' => $userID
    ]);

} else {
    // Tương tự với mysqli
    $userIDEsc = $conn->real_escape_string($userID);
    $fullNameEsc = $conn->real_escape_string($fullName);
    $classIDEsc = $conn->real_escape_string($classID);
    $programIDEsc = intval($programID);

    // Lấy mật khẩu cũ
    $res = $conn->query("SELECT Password FROM Users WHERE UserID = '$userIDEsc' LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        die("Sinh viên không tồn tại.");
    }
    $row = $res->fetch_assoc();
    $oldPassword = $row['Password'];

    // Kiểm tra lớp tồn tại
    $checkClass = $conn->query("SELECT 1 FROM Class WHERE ClassID = '$classIDEsc' LIMIT 1");
    if (!$checkClass || $checkClass->num_rows === 0) {
        die("Lớp không tồn tại.");
    }
    // Kiểm tra ngành tồn tại
    $checkProgram = $conn->query("SELECT 1 FROM ProgramInfo WHERE ProgramID = $programIDEsc LIMIT 1");
    if (!$checkProgram || $checkProgram->num_rows === 0) {
        die("Ngành không tồn tại.");
    }

    // Cập nhật password nếu có nhập mới
    if (trim($password) !== '') {
        $passwordToSave = $conn->real_escape_string($password);
    } else {
        $passwordToSave = $oldPassword;
    }

    $sql = "UPDATE Users SET FullName='$fullNameEsc', ClassID='$classIDEsc', ProgramID=$programIDEsc, Password='$passwordToSave' WHERE UserID='$userIDEsc'";
    if (!$conn->query($sql)) {
        die("Lỗi cập nhật: " . $conn->error);
    }
}

header("Location: ../page/listStudents.php?msg=update_success");
exit;