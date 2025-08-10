<?php 
include "../includes/header.php";
include "../config/db.php";

// Lấy danh sách ngành học để dropdown
$programList = [];
try {
  $stmtProgram = $conn->prepare("SELECT ProgramID, ProgramName FROM ProgramInfo ORDER BY ProgramName ASC");
  $stmtProgram->execute();
  $programList = $stmtProgram->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // log lỗi nếu cần
}

// Lấy giá trị tìm kiếm và ngành học từ GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$programID = isset($_GET['program']) ? $_GET['program'] : 'all';

// Truy vấn lấy danh sách giáo viên, join ProgramInfo lấy tên ngành học
$sql = "
  SELECT u.UserID, u.FullName, u.Email, p.ProgramName
  FROM Users u
  LEFT JOIN ProgramInfo p ON u.ProgramID = p.ProgramID
  WHERE u.Role = 'teacher'
";

$params = [];

// Điều kiện tìm kiếm (chỉ tìm theo UserID)
if ($search !== '') {
  $sql .= " AND u.UserID LIKE :search ";
  $params[':search'] = "%$search%";
}

// Điều kiện lọc ngành học
if ($programID !== 'all') {
  $sql .= " AND u.ProgramID = :programID ";
  $params[':programID'] = $programID;
}

$sql .= " ORDER BY u.FullName ASC";

try {
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $errorMessage = $e->getMessage();
  $teachers = [];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quản lý Giáo viên</title>
  <link rel="stylesheet" href="../assets/css/css_manage.css" />
  <link rel="stylesheet" href="../assets/css/global.css" />
</head>

<body>
  <main class="container" role="main" aria-labelledby="pageTitle">
    <header class="header-top">
      <div>
        <h1 id="pageTitle">Quản lý Giáo viên</h1>
        <p class="subtitle">Danh sách giáo viên và quản lý hồ sơ</p>
      </div>
      <a href="../Handlers/add_teacher.php" id="addTeacherBtn" class="button-link" aria-label="Thêm giáo viên"
        role="button" tabindex="0"
        style="display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none; color: inherit; cursor: pointer;">
        <svg class="icon" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false" width="24"
          height="24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Thêm giáo viên
      </a>

    </header>

    <form id="filterForm" method="GET" aria-label="Bộ lọc tìm kiếm giáo viên">
      <input type="search" id="searchInput" name="search" placeholder="Tìm kiếm giáo viên..."
        aria-label="Tìm kiếm giáo viên" autocomplete="off" value="<?= htmlspecialchars($search) ?>" />
      <select id="programSelect" name="program" aria-label="Chọn ngành học">
        <option value="all" <?= $programID === 'all' ? 'selected' : '' ?>>Tất cả ngành học</option>
        <?php foreach ($programList as $program): ?>
        <option value="<?= htmlspecialchars($program['ProgramID']) ?>"
          <?= $programID == $program['ProgramID'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($program['ProgramName']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <button id="searchBtn" type="submit" aria-label="Tìm kiếm giáo viên">
        <svg class="icon" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false">
          <circle cx="11" cy="11" r="7"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        Tìm kiếm
      </button>
    </form>

    <table class="teacher-table" role="table">
      <thead>
        <tr>
          <th scope="col">STT</th>
          <th scope="col">ID</th>
          <th scope="col">Họ và tên</th>
          <th scope="col">Khoa</th>
          <th scope="col">Email</th>
          <th scope="col">Mật khẩu</th>
          <th scope="col">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($teachers)): 
          $index = 1;
          foreach ($teachers as $row): ?>
        <tr>
          <td class="stt" tabindex="0"><?= $index++ ?></td>
          <td><?= htmlspecialchars($row['UserID']) ?></td>
          <td>
            <div class="teacher-name"><strong><?= htmlspecialchars($row['FullName']) ?></strong></div>
          </td>
          <td><span class="program-label"
              aria-label="Ngành học <?= htmlspecialchars($row['ProgramName'] ?: 'Chưa có') ?>"><?= htmlspecialchars($row['ProgramName'] ?: 'Chưa có') ?></span>
          </td>
          <td class="email-column"><?= htmlspecialchars($row['Email']) ?></td>
          <td class="password-column" aria-hidden="true">••••••••</td>
          <td class="actions-column">
            <a class="action-btn edit-btn" href="../Handlers/edit_teacher.php?id=<?= urlencode($row['UserID']) ?>"
              aria-label="Chỉnh sửa giáo viên <?= htmlspecialchars($row['FullName']) ?>" title="Chỉnh sửa">
              <svg class="icon" stroke="#ca8a04" viewBox="0 0 24 24">
                <path d="M15.232 5.232l3.536 3.536M16.768 4.768a2.5 2.5 0 013.536 3.536L7 21H3v-4L16.768 4.768z" />
              </svg>
            </a>

            <a href="../Handlers/delete_teacher.php?id=<?= htmlspecialchars($row['UserID']) ?>"
              class="action-btn delete-btn" aria-label="Xóa giáo viên <?= htmlspecialchars($row['FullName']) ?>"
              title="Xóa"
              onclick="return confirm('Bạn có chắc muốn xóa giáo viên <?= htmlspecialchars($row['FullName']) ?> không?');"
              style="display: inline-flex; align-items: center; text-decoration: none;">
              <svg class="icon" stroke="#dc2626" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6" />
                <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" />
                <path d="M10 11v6M14 11v6" />
                <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2" />
              </svg>
            </a>
          </td>
        </tr>
        <?php endforeach; 
        else: ?>
        <tr>
          <td colspan="7">Không tìm thấy giáo viên phù hợp.</td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</body>

</html>