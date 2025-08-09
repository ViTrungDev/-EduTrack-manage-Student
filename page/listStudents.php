<?php
include '../Handlers/Handler_pageListStudent.php';
include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>Danh Sách Sinh Viên</title>
  <link rel="stylesheet" href="../assets/css/listStudent.css">
  <link rel="stylesheet" href="../assets/css/global.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>

<body>
  <div class="container">
    <div class="header-row">
      <h1>Danh Sách Sinh Viên</h1>
      <a href="?action=export" class="btn-export">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M12 16L8 12h3V4h2v8h3l-4 4zM5 18h14v2H5z" />
        </svg>
        Export
      </a>
    </div>

    <!-- Bộ lọc -->
    <div class="select-students">
      <form method="GET" class="form-select-students">
        <div class="select-class">
          <?php if ($hasClass): ?>
          <select name="class" onchange="this.form.submit()">
            <option value="">-- Chọn lớp --</option>
            <?php foreach ($classList as $c): ?>
            <option value="<?= $c['ClassID'] ?>"
              <?= (isset($_GET['class']) && $_GET['class'] == $c['ClassID']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['ClassName']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
        </div>
        <div class="select-program">
          <?php if ($hasProgram): ?>
          <select name="program" onchange="this.form.submit()">
            <option value="">-- Chọn ngành --</option>
            <?php foreach ($programList as $p): ?>
            <option value="<?= $p['ProgramID'] ?>"
              <?= (isset($_GET['program']) && $_GET['program'] == $p['ProgramID']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['ProgramName']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
        </div>
        <div class="search-student">
          <input class="input-search-student" type="text" name="search"
            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Tìm sinh viên..." />
          <button class="btn-search-student" type="submit">Tìm kiếm</button>
        </div>
      </form>
    </div>

    <!-- Bảng -->
    <table>
      <thead>
        <tr>
          <th>STT</th>
          <th>Họ và tên</th>
          <th>Mã SV</th>
          <?php if ($hasFaculty): ?><th>Khoa</th><?php endif; ?>
          <?php if ($hasProgram): ?><th>Ngành</th><?php endif; ?>
          <?php if ($hasClass): ?><th>Lớp</th><?php endif; ?>
          <th>Ngày vào học</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)) : ?>
        <?php $stt = ($page - 1) * $limit + 1; foreach ($rows as $row): ?>
        <tr>
          <td><?= $stt ?></td>
          <td><?= htmlspecialchars($row['FullName'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['StudentCode'] ?? '') ?></td>
          <?php if ($hasFaculty): ?><td><?= htmlspecialchars($row['FacultyName'] ?? 'Chưa cập nhật') ?></td>
          <?php endif; ?>
          <?php if ($hasProgram): ?><td><?= htmlspecialchars($row['ProgramName'] ?? 'Chưa cập nhật') ?></td>
          <?php endif; ?>
          <?php if ($hasClass): ?><td><?= htmlspecialchars($row['ClassName'] ?? 'Chưa cập nhật') ?></td><?php endif; ?>
          <td><?= $row['CreatedAt'] ? date("d/m/Y", strtotime($row['CreatedAt'])) : '' ?></td>
          <td class="action-buttons">
            <a href="../page/edit_student.php?code=<?= urlencode($row['StudentCode']) ?>" class="btn-edit">
              <span class="action-buttons-icon material-symbols-outlined" style="color: white;">edit_square</span>
            </a>
            <a href="../Handlers/delete-student.php?code=<?= urlencode($row['StudentCode']) ?>" class="btn-delete"
              onclick="return confirm('Bạn có chắc muốn xóa sinh viên này?');">
              <span class="action-buttons-icon material-symbols-outlined" style="color: white;">delete</span>
            </a>
          </td>
        </tr>
        <?php $stt++; endforeach; ?>
        <?php else: ?>
        <tr>
          <td colspan="<?= 6 + ($hasFaculty ? 1 : 0) + ($hasProgram ? 1 : 0) + ($hasClass ? 1 : 0) ?>">Không có dữ liệu
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Phân trang -->
    <?php if ($totalPages > 1): ?>
    <nav class="pagination" style="margin-top: 20px; text-align:center;">
      <?php
        $queryParams = $_GET;
        unset($queryParams['page']);
        function buildPageLink($pageNum, $text = null) {
            global $queryParams;
            $queryParams['page'] = $pageNum;
            $url = '?' . http_build_query($queryParams);
            return '<a href="' . htmlspecialchars($url) . '">' . ($text ?? $pageNum) . '</a>';
        }
        if ($page > 1) {
            echo buildPageLink($page - 1, '« Trước');
        }
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == $page) {
                echo '<span class="current-page">' . $i . '</span>';
            } else {
                echo buildPageLink($i);
            }
        }
        if ($page < $totalPages) {
            echo buildPageLink($page + 1, 'Tiếp »');
        }
      ?>
    </nav>
    <?php endif; ?>

  </div>
</body>

</html>