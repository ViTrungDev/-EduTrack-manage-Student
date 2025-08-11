<?php
include '../config/db.php';
include '../Handlers/export_students.php';

// ----- HỖ TRỢ: kiểm tra tồn tại table/column trên DB -----
function tableExists($conn, $tableName) {
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t");
        $stmt->execute([':t' => $tableName]);
        return (int)$stmt->fetchColumn() > 0;
    } else { // mysqli
        $t = $conn->real_escape_string($tableName);
        $res = $conn->query("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$t'");
        if ($res) {
            $r = $res->fetch_assoc();
            return (int)$r['c'] > 0;
        }
        return false;
    }
}

function columnExists($conn, $tableName, $columnName) {
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c");
        $stmt->execute([':t' => $tableName, ':c' => $columnName]);
        return (int)$stmt->fetchColumn() > 0;
    } else {
        $t = $conn->real_escape_string($tableName);
        $c = $conn->real_escape_string($columnName);
        $res = $conn->query("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '$t' AND column_name = '$c'");
        if ($res) {
            $r = $res->fetch_assoc();
            return (int)$r['c'] > 0;
        }
        return false;
    }
}

// Kiểm tra cấu trúc DB
$hasProgram = tableExists($conn, 'ProgramInfo');
$hasFaculty = $hasProgram && columnExists($conn, 'ProgramInfo', 'FacultyID') && tableExists($conn, 'Faculty');
$hasClass   = tableExists($conn, 'Class') && columnExists($conn, 'Users', 'ClassID');

// Lấy page hiện tại, mặc định 1
$page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build select list và join
$select = "u.FullName, u.StudentCode, u.CreatedAt";
$joins  = "";

if ($hasProgram) {
    $select .= ", p.ProgramName";
    $joins  .= " LEFT JOIN `ProgramInfo` p ON u.ProgramID = p.ProgramID";
}

if ($hasFaculty) {
    $select .= ", f.FacultyName";
    $joins  .= " LEFT JOIN `Faculty` f ON p.FacultyID = f.FacultyID";
}

if ($hasClass) {
    $select .= ", c.ClassName";
    $joins  .= " LEFT JOIN `Class` c ON u.ClassID = c.ClassID";
}

// Filter từ request
$whereClauses = ["u.Role = 'student'"];
if (!empty($_GET['class'])) {
    $whereClauses[] = "u.ClassID = " . intval($_GET['class']);
}
if (!empty($_GET['program'])) {
    $whereClauses[] = "u.ProgramID = " . intval($_GET['program']);
}
if (!empty($_GET['search'])) {
    $s = trim($_GET['search']);
    if ($conn instanceof PDO) {
        $whereClauses[] = "(u.FullName LIKE :search OR u.StudentCode LIKE :search)";
    } else {
        $escaped = $conn->real_escape_string($s);
        $whereClauses[] = "(u.FullName LIKE '%$escaped%' OR u.StudentCode LIKE '%$escaped%')";
    }
}

$whereSQL = implode(" AND ", $whereClauses);

// Đếm tổng số bản ghi
$countSql = "SELECT COUNT(*) FROM `Users` u $joins WHERE $whereSQL";
$totalRows = 0;
try {
    if ($conn instanceof PDO) {
        $stmtCount = $conn->prepare($countSql);
        if (!empty($_GET['search'])) {
            $stmtCount->bindValue(':search', '%' . $_GET['search'] . '%', PDO::PARAM_STR);
        }
        $stmtCount->execute();
        $totalRows = (int)$stmtCount->fetchColumn();
    } elseif ($conn instanceof mysqli) {
        $resultCount = $conn->query($countSql);
        if ($resultCount === false) {
            throw new Exception("Lỗi SQL: " . $conn->error);
        }
        $rowCount = $resultCount->fetch_row();
        $totalRows = (int)$rowCount[0];
        $resultCount->free();
    }
} catch (Exception $e) {
    die("Lỗi khi đếm dữ liệu: " . $e->getMessage());
}

// Tính tổng số trang
$totalPages = ceil($totalRows / $limit);

// Truy vấn dữ liệu phân trang
$sql = "SELECT $select
        FROM `Users` u
        $joins
        WHERE $whereSQL
        ORDER BY u.CreatedAt ASC
        LIMIT $limit OFFSET $offset
";

$rows = [];
try {
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare($sql);
        if (!empty($_GET['search'])) {
            $stmt->bindValue(':search', '%' . $_GET['search'] . '%', PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($conn instanceof mysqli) {
        $result = $conn->query($sql);
        if ($result === false) {
            throw new Exception("Lỗi SQL: " . $conn->error);
        }
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
        if ($result) $result->free();
    } else {
        throw new Exception("Không tìm thấy kết nối DB hợp lệ.");
    }
} catch (Exception $e) {
    die("Lỗi khi truy vấn dữ liệu: " . $e->getMessage());
}



// Nếu bấm nút export
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    exportStudents($rows, $hasFaculty, $hasClass);
    exit;
}

// Lấy danh sách Class & Program để đổ vào select
$classList = [];
$programList = [];

if ($hasClass) {
    if ($conn instanceof PDO) {
        $classList = $conn->query("SELECT ClassID, ClassName FROM Class ORDER BY ClassName ASC")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $res = $conn->query("SELECT ClassID, ClassName FROM Class ORDER BY ClassName ASC");
        while ($row = $res->fetch_assoc()) {
            $classList[] = $row;
        }
    }
}

if ($hasProgram) {
    if ($conn instanceof PDO) {
        $programList = $conn->query("SELECT ProgramID, ProgramName FROM ProgramInfo ORDER BY ProgramName ASC")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $res = $conn->query("SELECT ProgramID, ProgramName FROM ProgramInfo ORDER BY ProgramName ASC");
        while ($row = $res->fetch_assoc()) {
            $programList[] = $row;
        }
    }
}
?>