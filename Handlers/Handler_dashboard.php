<?php 
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

// Chỉ cho phép admin/teacher
if ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

// ======= TỔNG SỐ SINH VIÊN =======
$sql = 'SELECT COUNT(*) AS total_students FROM Users WHERE role = "student"';
$stmt = $conn->prepare($sql);
$stmt->execute();
$total_students = $stmt->fetchColumn();

// ======= TỔNG SỐ SINH VIÊN MỚI =======
$query = "SELECT FullName, StudentCode, CreatedAt 
          FROM Users 
          WHERE Role = 'student' 
          ORDER BY CreatedAt DESC 
          LIMIT 4";
$stmt = $conn->prepare($query);
$stmt->execute();
$recentStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======= TĂNG TRƯỞNG SINH VIÊN NĂM =======
$currentYear = date('Y');
$lastYear = $currentYear - 1;

$sqlCurrent = 'SELECT COUNT(*) FROM Users WHERE role = "student" AND YEAR(CreatedAt) = :currentYear';
$stmt = $conn->prepare($sqlCurrent);
$stmt->bindParam(':currentYear', $currentYear, PDO::PARAM_INT);
$stmt->execute();
$currentCount = $stmt->fetchColumn();

$sqlLast = 'SELECT COUNT(*) FROM Users WHERE role = "student" AND YEAR(CreatedAt) = :lastYear';
$stmt = $conn->prepare($sqlLast);
$stmt->bindParam(':lastYear', $lastYear, PDO::PARAM_INT);
$stmt->execute();
$lastCount = $stmt->fetchColumn();

$growthYear = $lastCount == 0 ? ($currentCount > 0 ? 100 : 0) : round((($currentCount - $lastCount) / $lastCount) * 100, 2);

// ======= SINH VIÊN MỚI TRONG THÁNG & SO VỚI THÁNG TRƯỚC =======
$startThisMonth = date('Y-m-01');
$startLastMonth = date('Y-m-01', strtotime('-1 month'));
$endLastMonth = date('Y-m-t', strtotime('-1 month'));

$sqlThisMonth = "SELECT COUNT(*) FROM Users WHERE role = 'student' AND CreatedAt >= :startThisMonth";
$stmt = $conn->prepare($sqlThisMonth);
$stmt->bindParam(':startThisMonth', $startThisMonth);
$stmt->execute();
$thisMonthCount = $stmt->fetchColumn();

$sqlLastMonth = "SELECT COUNT(*) FROM Users WHERE role = 'student' AND CreatedAt >= :startLastMonth AND CreatedAt <= :endLastMonth";
$stmt = $conn->prepare($sqlLastMonth);
$stmt->bindParam(':startLastMonth', $startLastMonth);
$stmt->bindParam(':endLastMonth', $endLastMonth);
$stmt->execute();
$lastMonthCount = $stmt->fetchColumn();

$growthMonth = $lastMonthCount == 0 ? ($thisMonthCount > 0 ? 100 : 0) : round((($thisMonthCount - $lastMonthCount) / $lastMonthCount) * 100, 2);

$iconYear = $growthYear >= 0 ? 'arrow_upward' : 'arrow_downward_alt';
$classYear = $growthYear >= 0 ? 'text-success' : 'text-danger';
$prefixYear = $growthYear >= 0 ? '+' : '-';

$iconMonth = $growthMonth >= 0 ? 'arrow_upward' : 'arrow_downward_alt';
$classMonth = $growthMonth >= 0 ? 'text-success' : 'text-danger';
$prefixMonth = $growthMonth >= 0 ? '+' : '-';

// ======= GPA TRUNG BÌNH & TỶ LỆ TỐT NGHIỆP =======
function getGPAStats($conn, $year) {
    $sql = "SELECT e.StudentID, 
                  ROUND(AVG(
                      COALESCE(g.Midterm,0)*0.3 + 
                      COALESCE(g.Final,0)*0.6 + 
                      COALESCE(g.Attendance,0)*0.1
                  ), 2) AS avg_gpa
            FROM Grades g
            JOIN Enrollments e ON g.EnrollmentID = e.EnrollmentID
            WHERE e.Semester LIKE :semester
            GROUP BY e.StudentID";

    $stmt = $conn->prepare($sql);
    $semester = "%-" . $year;
    $stmt->bindParam(':semester', $semester);
    $stmt->execute();
    $gpas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalGPA = 0;
    $graduates = 0;
    $count = count($gpas);

    foreach ($gpas as $row) {
        $totalGPA += $row['avg_gpa'];
        if ($row['avg_gpa'] >= 2.5) {
            $graduates++;
        }
    }

    $avgGPA = $count > 0 ? round($totalGPA / $count, 2) : 0;
    $gradRate = $count > 0 ? round(($graduates / $count) * 100, 2) : 0;

    return ['gpa' => $avgGPA, 'rate' => $gradRate];
}

$currentStats = getGPAStats($conn, $currentYear);
$lastStats = getGPAStats($conn, $lastYear);

$gpaGrowth = $lastStats['gpa'] > 0 ? round((($currentStats['gpa'] - $lastStats['gpa']) / $lastStats['gpa']) * 100, 2) : 100;
$rateGrowth = $lastStats['rate'] > 0 ? round((($currentStats['rate'] - $lastStats['rate']) / $lastStats['rate']) * 100, 2) : 100;


?>