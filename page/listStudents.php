<?php
include '../includes/header.php';
include '../config/db.php';

// Truy vấn dữ liệu sinh viên + ngành học
$sql = "
    SELECT 
        u.FullName, 
        u.StudentCode, 
        p.ProgramName, 
        u.CreatedAt
    FROM Users u
    LEFT JOIN ProgramInfo p ON u.ProgramID = p.ProgramID
    WHERE u.Role = 'student'
    ORDER BY u.CreatedAt ASC
";

$rows = [];

try {
    if (isset($conn) && $conn instanceof PDO) {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($conn) && (get_class($conn) === 'mysqli' || $conn instanceof mysqli)) {
        $result = $conn->query($sql);
        if ($result === false) {
            throw new Exception("Lỗi SQL: " . $conn->error);
        }
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
        $result->free();
    } else {
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            throw new Exception("Không tìm thấy kết nối DB hợp lệ (\$conn hoặc \$pdo).");
        }
    }
} catch (Exception $e) {
    die("Lỗi khi truy vấn dữ liệu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="../assets/css/listStudent.css">
  <link rel="stylesheet" href="../assets/css/global.css">
  <title>Danh Sách Sinh Viên</title>
</head>

<body>
  <div class="container">
    <!-- Heading and export button -->
    <div class="header-row">
      <h1>Danh Sách Sinh Viên</h1>
      <button class="btn-export" aria-haspopup="true" aria-expanded="false" aria-label="Xuất dữ liệu sinh viên"
        onclick="exportStudents()">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <path d="M12 16L8 12h3V4h2v8h3l-4 4zM5 18h14v2H5z" />
        </svg>
        Export
        <svg style="margin-left: 6px;" width="12" height="12" aria-hidden="true" focusable="false" viewBox="0 0 24 24"
          fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </button>
    </div>

    <table>
      <thead>
        <tr>
          <th>STT</th>
          <th>Họ và tên</th>
          <th>Mã SV</th>
          <th>Ngành</th>
          <th>Ngày vào học</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (count($rows) > 0) {
            $stt = 1;
            foreach ($rows as $row) {
                $fullname = htmlspecialchars($row['FullName'] ?? '');
                $studentCode = htmlspecialchars($row['StudentCode'] ?? '');
                $programName = htmlspecialchars($row['ProgramName'] ?? 'Chưa cập nhật');
                $createdAtRaw = $row['CreatedAt'] ?? null;
                $createdAt = $createdAtRaw ? date("d/m/Y", strtotime($createdAtRaw)) : '';

                echo "<tr>";
                echo "<td>" . $stt++ . "</td>";
                echo "<td><strong>{$fullname}</strong></td>";
                echo "<td>{$studentCode}</td>";
                echo "<td>{$programName}</td>";
                echo "<td>{$createdAt}</td>";
                echo "<td>
                        <a href='edit_student.php?code={$studentCode}' class='btn-edit' title='Sửa sinh viên'>Sửa</a> | 
                        <a href='delete_student.php?code={$studentCode}' class='btn-delete' title='Xóa sinh viên' onclick=\"return confirm('Bạn có chắc muốn xóa sinh viên này?');\">Xóa</a>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>Không có dữ liệu</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>

  <script>
  function exportStudents() {
    // Ví dụ đơn giản: xuất bảng sang CSV
    let rows = document.querySelectorAll('table tr');
    let csv = [];
    rows.forEach(row => {
      let cols = row.querySelectorAll('th, td');
      let rowData = [];
      cols.forEach(col => {
        // Bỏ cột hành động (cuối cùng) khi xuất
        if (col.cellIndex === cols.length - 1) return;
        // Thêm dấu ngoặc kép nếu có dấu phẩy hoặc dấu nháy trong dữ liệu
        let text = col.innerText.replace(/"/g, '""');
        if (text.includes(',') || text.includes('"') || text.includes('\n')) {
          text = `"${text}"`;
        }
        rowData.push(text);
      });
      csv.push(rowData.join(','));
    });
    let csvContent = csv.join('\n');

    // Tạo file CSV và tải về
    let blob = new Blob([csvContent], {
      type: 'text/csv;charset=utf-8;'
    });
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = 'danh_sach_sinh_vien.csv';
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }
  </script>
</body>

</html>