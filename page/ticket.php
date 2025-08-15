<?php
session_start();
include '../config/db.php'; // kết nối $conn

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// Nhận dữ liệu từ form search & filter
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 18;
$offset = ($page - 1) * $limit;

// Xây dựng câu lệnh SQL
$whereClauses = [];
$params = [];

if (strtolower($userRole) === 'teacher') {
    $whereClauses[] = "(t.CreatedBy = :uid OR t.AssignedTo = :uid)";
    $params[':uid'] = $userId;
} elseif (strtolower($userRole) !== 'admin') {
    $whereClauses[] = "t.CreatedBy = :createdBy";
    $params[':createdBy'] = $userId;
}


if ($search !== '') {
    $whereClauses[] = "(t.Title LIKE :search OR t.Description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($statusFilter !== '') {
    $whereClauses[] = "t.Status = :status";
    $params[':status'] = $statusFilter;
}

$whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

try {
    // Lấy tổng số ticket
    $countSQL = "
        SELECT COUNT(*) FROM Ticket t
        JOIN Users creator ON t.CreatedBy = creator.UserID
        LEFT JOIN Users assignee ON t.AssignedTo = assignee.UserID
        $whereSQL
    ";
    $countStmt = $conn->prepare($countSQL);
    $countStmt->execute($params);
    $totalTickets = $countStmt->fetchColumn();

    // Lấy dữ liệu tickets
    $sql = "
        SELECT 
            t.TicketID,
            t.Title,
            t.Description,
            t.Priority,
            t.Status,
            t.CreatedAt,
            creator.FullName AS CreatedByName,
            assignee.FullName AS AssignedToName
        FROM Ticket t
        JOIN Users creator ON t.CreatedBy = creator.UserID
        LEFT JOIN Users assignee ON t.AssignedTo = assignee.UserID
        $whereSQL
        ORDER BY t.CreatedAt DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Lỗi lấy dữ liệu ticket: " . $e->getMessage());
}

// Tính tổng số trang
$totalPages = ceil($totalTickets / $limit);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/ticket.css">
  <title>Ticket Management System</title>
</head>

<body>
  <div class="container">
    <header>
      <a href="../page/dashboard.php" class="logo">TicketTrack</a>
      <a href="../page/create_ticket.php" class="new-ticket-btn">+ New Ticket</a>
    </header>

    <div class="search-filter">
      <form method="GET" style="display: flex; gap: 10px;">
        <input class="search-input" type="text" name="search" placeholder="Search tickets..."
          value="<?= htmlspecialchars($search) ?>">
        <select name="status" class="select-input">
          <option value="">-- All Status --</option>
          <option value="Open" <?= $statusFilter == 'Open' ? 'selected' : '' ?>>Open</option>
          <option value="In Progress" <?= $statusFilter == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
          <option value="Closed" <?= $statusFilter == 'Closed' ? 'selected' : '' ?>>Closed</option>
        </select>
        <button class="filter-btn" type="submit">Filter</button>
      </form>
    </div>

    <div class="tickets-container">
      <?php if (count($tickets) > 0): ?>
      <?php foreach ($tickets as $ticket): ?>
      <a href="ticket_info.php?id=<?= urlencode($ticket['TicketID']) ?>" class="ticket-card">
        <div class="ticket-header">
          <h3 class="ticket-title"><?= htmlspecialchars($ticket['Title']) ?></h3>
          <div class="ticket-id">ID:<?= htmlspecialchars($ticket['TicketID']) ?></div>
          <div class="ticket-status status-<?= strtolower($ticket['Status']) ?>">
            <?= htmlspecialchars($ticket['Status']) ?>
          </div>
        </div>
        <div class="ticket-body">
          <p class="ticket-description"><?= nl2br(htmlspecialchars($ticket['Description'])) ?></p>
          <div class="ticket-meta">
            <span>Created: <?= htmlspecialchars($ticket['CreatedAt']) ?></span>
            <span>Priority: <?= htmlspecialchars($ticket['Priority']) ?></span>
          </div>
        </div>
        <div class="ticket-footer">
          <div class="ticket-assignee">
            <span class="assignee-name">Assigned to:
              <?= htmlspecialchars($ticket['AssignedToName'] ?? 'Unassigned') ?></span>
          </div>
          <div class="ticket-createdby">
            <span class="assignee-name">Created by: <?= htmlspecialchars($ticket['CreatedByName']) ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
      <?php else: ?>
      <p>Không có ticket nào.</p>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination" style="margin-top: 20px; text-align: center;">
      <?php if ($page > 1): ?>
      <a class="pagination-link"
        href="?search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&page=<?= $page - 1 ?>">Prev</a>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a class="pagination-link"
        href="?search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&page=<?= $i ?>"
        style="<?= $i == $page ? 'font-weight:bold;' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <a class="pagination-link"
        href="?search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&page=<?= $page + 1 ?>">Next</a>
      <?php endif; ?>
    </div>
  </div>
</body>

</html>