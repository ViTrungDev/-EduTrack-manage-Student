<?php
include "../includes/header.php";
include "../config/db.php";

if (empty($_GET['subject_id'])) {
    die("Thiếu SubjectID");
}
$subject_id = $_GET['subject_id'];

// Lấy thông tin môn học
$stmtSub = $conn->prepare("SELECT SubjectName FROM Subjects WHERE SubjectID = :id");
$stmtSub->execute([':id' => $subject_id]);
$subject = $stmtSub->fetch(PDO::FETCH_ASSOC);
if (!$subject) {
    die("Môn học không tồn tại");
}

// Lấy danh sách sinh viên (loại bỏ trùng bằng GROUP BY StudentID)
$sql = "
SELECT 
    MIN(e.EnrollmentID) AS EnrollmentID, -- lấy 1 EnrollmentID duy nhất
    u.UserID AS StudentID,
    u.FullName,
    g.Attendance,
    g.Midterm,
    g.Final
FROM Enrollments e
JOIN Users u ON e.StudentID = u.UserID AND u.Role = 'student'
LEFT JOIN Grades g ON e.EnrollmentID = g.EnrollmentID
WHERE e.SubjectID = :subject_id
GROUP BY u.UserID, u.FullName, g.Attendance, g.Midterm, g.Final
ORDER BY u.FullName
";
$stmt = $conn->prepare($sql);
$stmt->execute([':subject_id' => $subject_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý lưu điểm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $insertStmt = $conn->prepare("
        INSERT INTO Grades (EnrollmentID, Attendance, Midterm, Final)
        VALUES (:eid, :att, :mid, :fin)
    ");
    $updateStmt = $conn->prepare("
        UPDATE Grades 
        SET Attendance = :att, Midterm = :mid, Final = :fin
        WHERE EnrollmentID = :eid
    ");

    foreach ($_POST['grades'] as $enrollmentID => $data) {
        $attendance = $data['attendance'] !== '' ? $data['attendance'] : null;
        $midterm    = $data['midterm'] !== '' ? $data['midterm'] : null;
        $final      = $data['final'] !== '' ? $data['final'] : null;

        // Kiểm tra đã tồn tại điểm chưa
        $check = $conn->prepare("SELECT GradeID FROM Grades WHERE EnrollmentID = :eid LIMIT 1");
        $check->execute([':eid' => $enrollmentID]);
        
        if ($check->fetch()) {
            $updateStmt->execute([
                ':att' => $attendance,
                ':mid' => $midterm,
                ':fin' => $final,
                ':eid' => $enrollmentID
            ]);
        } else {
            $insertStmt->execute([
                ':eid' => $enrollmentID,
                ':att' => $attendance,
                ':mid' => $midterm,
                ':fin' => $final
            ]);
        }
    }
    header("Location: ../page/pageProgram.php?subject_id=" . urlencode($subject_id));
    exit;
}
?>