<?php
include '../includes/header.php';
include '../Handlers/Handler_dashboard.php';
?>

<link rel="stylesheet" href="../assets/css/css_dashboard.css">
<link rel="stylesheet" href="../assets/css/global.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

<main class="container">
  <?php
    $name = $_SESSION['user']['name'] ?? 'Khách';
    $role = $_SESSION['user']['role'] ?? 'guest';
  ?>
  <div class="header-title">
    <h2>Trang chủ</h2>
    <p>
      <?php
        if ($role === 'teacher') {
          echo "Xin chào giáo viên <strong>$name</strong>! Bạn có thể tạo và quản lý lớp học.";
        } elseif ($role === 'admin') {
          echo "Xin chào quản trị viên <strong>$name</strong>! Bạn có toàn quyền quản lý hệ thống.";
        } else {
          echo "Xin chào <strong>$name</strong>!";
        }
      ?>
    </p>
  </div>

  <div class="content-percentage">
    <div class="percentage-item">
      <p class="percentage-title">Tổng sinh viên</p>
      <div class="percentage-value">
        <p class="value-total"><?= $total_students ?></p>
        <p class="group material-symbols-outlined">group</p>
      </div>
      <div class="<?= $classYear ?> icon-down">
        <span class="material-symbols-outlined"><?= $iconYear ?></span>
        <span class="desc-value"><?= $prefixYear ?><?= abs($growthYear) ?>% so với năm trước</span>
      </div>
    </div>

    <div class="percentage-item">
      <p class="percentage-title">Sinh viên mới</p>
      <div class="percentage-value">
        <p class="value-total"><?= $thisMonthCount ?></p>
        <p class="group material-symbols-outlined">person_add</p>
      </div>
      <div class="<?= $classMonth ?> icon-down">
        <span class="material-symbols-outlined"><?= $iconMonth ?></span>
        <span class="desc-value"><?= $prefixMonth ?><?= abs($growthMonth) ?>% so với tháng trước</span>
      </div>
    </div>

    <div class="percentage-item">
      <p class="percentage-title">GPA trung bình</p>
      <div class="percentage-value">
        <p class="value-total"><?= $currentStats['gpa'] ?></p>
        <p class="group material-symbols-outlined">school</p>
      </div>
      <div class="text-muted icon-down">
        <span class="material-symbols-outlined">
          <?= $gpaGrowth >= 0 ? 'trending_up' : 'trending_down' ?>
        </span>
        <span class="desc-value" style="color: <?= $gpaGrowth >= 0 ? 'green' : 'red' ?>;">
          <?= ($gpaGrowth >= 0 ? '+' : '') . round($gpaGrowth, 2) ?>% so với năm trước
        </span>
      </div>
    </div>

    <div class="percentage-item">
      <p class="percentage-title">Tỷ lệ tốt nghiệp</p>
      <div class="percentage-value">
        <p class="value-total"><?= $currentStats['rate'] ?>%</p>
        <p class="group material-symbols-outlined">check_circle</p>
      </div>
      <div class="text-muted icon-down">
        <span class="material-symbols-outlined">
          <?= $rateGrowth >= 0 ? 'trending_up' : 'trending_down' ?>
        </span>
        <span class="desc-value" style="color: <?= $rateGrowth >= 0 ? 'green' : 'red' ?>;">
          <?= ($rateGrowth >= 0 ? '+' : '') . round($rateGrowth, 2) ?>% so với năm trước
        </span>
      </div>
    </div>
  </div>

  <div class="content-main">
    <div class="history-student">
      <div class="history-header">
        <h3>Sinh viên mới</h3>
        <a href="../page/history.php" class="btn btn-primary">Xem chi tiết</a>
      </div>
      <div class="history-list">
        <?php if (!empty($recentStudents)): ?>
        <?php foreach ($recentStudents as $student): ?>
        <?php
                    $firstLetter = '';
                    if (!empty($student['FullName'])) {
                        $firstLetter = mb_substr(trim($student['FullName']), 0, 1, 'UTF-8');
                    }
                ?>
        <div class="history-item">
          <div class="history-avatar">
            <div class="avatar-circle"><?= htmlspecialchars($firstLetter) ?></div>
          </div>
          <div class="history-details">
            <div class="history-info">
              <p class="history-name"><?= htmlspecialchars($student['FullName']) ?></p>
              <p class="history-code">Mã SV: <?= htmlspecialchars($student['StudentCode']) ?></p>
            </div>
            <p class="history-time"><?= date("d/m/Y H:i", strtotime($student['CreatedAt'])) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p>Không có sinh viên mới nào.</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="dashboard">
      <h3>Thống kê theo ngành</h3>

      <?php
      // ======= LẤY DỮ LIỆU THEO NGÀNH VÀ GỘP "KHÁC" =======
      $sqlPrograms = "
        SELECT p.ProgramName, COUNT(u.UserID) AS TotalStudents
        FROM ProgramInfo p
        LEFT JOIN Users u ON u.ProgramID = p.ProgramID AND u.Role = 'student'
        GROUP BY p.ProgramName
        ORDER BY p.ProgramID
      ";
      $stmtP = $conn->prepare($sqlPrograms);
      $stmtP->execute();
      $programRows = $stmtP->fetchAll(PDO::FETCH_ASSOC);

      // 4 ngành chính cần hiển thị riêng
      $mainPrograms = [
        'Công Nghệ Thông Tin' => 0,
        'Kế Toán' => 0,
        'Ngôn ngữ Anh' => 0,
        'Quản trị kinh doanh' => 0,
        'Khác' => 0
      ];

      foreach ($programRows as $r) {
        $pname = $r['ProgramName'];
        $count = (int)$r['TotalStudents'];
        if (array_key_exists($pname, $mainPrograms) && $pname !== 'Khác') {
          $mainPrograms[$pname] += $count;
        } else {
          $mainPrograms['Khác'] += $count;
        }
      }

      $chartLabels = array_keys($mainPrograms);
      $chartData = array_values($mainPrograms);
      ?>

      <!-- Canvas cho Chart.js -->
      <canvas id="programChart" height="180"></canvas>
      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
      <script>
      const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
      const data = <?= json_encode($chartData) ?>;
      const ctx = document.getElementById('programChart').getContext('2d');

      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Số sinh viên',
            data: data,
            backgroundColor: [
              'rgba(54, 162, 235, 0.6)',
              'rgba(255, 99, 132, 0.6)',
              'rgba(255, 206, 86, 0.6)',
              'rgba(75, 192, 192, 0.6)',
              'rgba(153, 102, 255, 0.6)',
            ],
            borderColor: [
              'rgba(54, 162, 235, 1)',
              'rgba(255, 99, 132, 1)',
              'rgba(255, 206, 86, 1)',
              'rgba(75, 192, 192, 1)',
              'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1,
            barPercentage: 0.6, // Thu nhỏ bề rộng từng cột
            categoryPercentage: 0.6 // Thu nhỏ khung ngoài
          }]
        },
        options: {
          indexAxis: 'y', // Cột ngang
          responsive: true,
          scales: {
            x: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
            }
          },
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              enabled: true
            }
          }
        }
      });
      </script>

    </div>
  </div>
  <div class="manage-footer">
    <div class="manage-item manage-student">
      <a href="../page/student.php" class="manage-link">
        <div class="manage_group">
          <p class="group material-symbols-outlined">person</p>
          <div class="manage_student-desc">
            <h5 class="manage-title">Quản lý sinh viên</h5>
            <p class="manage-desc">Xem và chỉnh sửa thông tin sinh viên</p>
          </div>
        </div>
      </a>
    </div>
    <div class="manage-item manage-teacher">
      <a href="../page/teacher.php" class="manage-link">
        <div class="manage_group">
          <p class="group material-symbols-outlined">group</p>
          <div class="manage_teacher-desc">
            <h5 class="manage-title">Quản lý giáo viên</h5>
            <p class="manage-desc">Xem và chỉnh sửa thông tin giáo viên</p>
          </div>
        </div>
      </a>
    </div>
    <div class="manage-item manage-program">
      <a href="../page/program.php" class="manage-link">
        <div class="manage_group">
          <p class="group material-symbols-outlined">book_2</p>
          <div class="manage_program-desc">
            <h5 class="manage-title">Quản lý môn học </h5>
            <p class="manage-desc">Xem và chỉnh sửa thông tin chương trình</p>
          </div>
        </div>
      </a>
    </div>
  </div>
</main>
</body>

</html>