<?php
session_start();
include '../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// Lấy ID ticket
$ticketId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($ticketId <= 0) {
    die("Ticket ID không hợp lệ.");
}

// Lấy thông tin ticket
try {
    if ($conn instanceof PDO) {
        $sql = "
            SELECT 
                t.*,
                creator.FullName AS CreatedByName,
                assignee.FullName AS AssignedToName
            FROM Ticket t
            JOIN Users creator ON t.CreatedBy = creator.UserID
            LEFT JOIN Users assignee ON t.AssignedTo = assignee.UserID
            WHERE t.TicketID = :ticketId
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':ticketId' => $ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $ticketIdEsc = (int)$ticketId;
        $sql = "
            SELECT 
                t.*,
                creator.FullName AS CreatedByName,
                assignee.FullName AS AssignedToName
            FROM Ticket t
            JOIN Users creator ON t.CreatedBy = creator.UserID
            LEFT JOIN Users assignee ON t.AssignedTo = assignee.UserID
            WHERE t.TicketID = $ticketIdEsc
        ";
        $result = $conn->query($sql);
        $ticket = $result->fetch_assoc();
    }
} catch (Exception $e) {
    die("Lỗi truy vấn ticket: " . $e->getMessage());
}

if (!$ticket) {
    die("Không tìm thấy ticket.");
}

// Kiểm tra quyền
if (
    strtolower($userRole) !== 'admin' &&
    strtolower($userRole) !== 'teacher' &&
    $ticket['CreatedBy'] != $userId
) {
    die("Bạn không có quyền xem ticket này.");
}

// Xác định trạng thái hợp lệ tiếp theo
$allowedTransitions = [
    'Open' => ['In Progress', 'Resolved', 'Closed'],
    'In Progress' => ['Resolved', 'Closed'],
    'Resolved' => ['Closed'],
    'Closed' => []
];
$currentStatus = $ticket['Status'];
$nextStatuses = $allowedTransitions[$currentStatus] ?? [];

// Cập nhật trạng thái (nếu có)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = trim($_POST['status']);

    if (!in_array($newStatus, $nextStatuses)) {
        die("Trạng thái không hợp lệ.");
    }

    try {
        if ($conn instanceof PDO) {
            $stmtOld = $conn->prepare("SELECT Status FROM Ticket WHERE TicketID = :ticketId");
            $stmtOld->execute([':ticketId' => $ticketId]);
            $oldStatus = $stmtOld->fetchColumn();

            $stmt = $conn->prepare("UPDATE Ticket SET Status = :status WHERE TicketID = :ticketId");
            $stmt->execute([
                ':status' => $newStatus,
                ':ticketId' => $ticketId
            ]);

            $stmtLog = $conn->prepare("
                INSERT INTO TicketLogs (TicketID, Action, OldValue, NewValue, PerformedBy)
                VALUES (:ticketId, 'Updated Status', :oldValue, :newValue, :userId)
            ");
            $stmtLog->execute([
                ':ticketId' => $ticketId,
                ':oldValue' => $oldStatus,
                ':newValue' => $newStatus,
                ':userId' => $userId
            ]);
        } else {
            $ticketIdEsc = (int)$ticketId;
            $resOld = $conn->query("SELECT Status FROM Ticket WHERE TicketID = $ticketIdEsc");
            $rowOld = $resOld->fetch_assoc();
            $oldStatus = $rowOld['Status'];

            $newStatusEsc = $conn->real_escape_string($newStatus);
            $conn->query("UPDATE Ticket SET Status = '$newStatusEsc' WHERE TicketID = $ticketIdEsc");

            $oldStatusEsc = $conn->real_escape_string($oldStatus);
            $conn->query("
                INSERT INTO TicketLogs (TicketID, Action, OldValue, NewValue, PerformedBy)
                VALUES ($ticketIdEsc, 'Updated Status', '$oldStatusEsc', '$newStatusEsc', '$userId')
            ");
        }

        header("Location: ticket_info.php?id=" . $ticketId . "&updated=1");
        exit;
    } catch (Exception $e) {
        die("Lỗi cập nhật trạng thái: " . $e->getMessage());
    }
}

// Lấy log lịch sử
if ($conn instanceof PDO) {
    $sqlLog = "
        SELECT 
            l.*,
            u.FullName AS UserName
        FROM TicketLogs l
        JOIN Users u ON l.PerformedBy = u.UserID
        WHERE l.TicketID = :ticketId
        ORDER BY l.CreatedAt ASC
    ";
    $stmtLog = $conn->prepare($sqlLog);
    $stmtLog->execute([':ticketId' => $ticketId]);
    $logs = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
} else {
    $ticketIdEsc = (int)$ticketId;
    $sqlLog = "
        SELECT 
            l.*,
            u.FullName AS UserName
        FROM TicketLogs l
        JOIN Users u ON l.PerformedBy = u.UserID
        WHERE l.TicketID = $ticketIdEsc
        ORDER BY l.CreatedAt ASC
    ";
    $resultLog = $conn->query($sqlLog);
    $logs = [];
    while ($row = $resultLog->fetch_assoc()) {
        $logs[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ticket #<?= htmlspecialchars($ticket['TicketID']) ?></title>
  <link rel="stylesheet" href="../assets/css/ticket_info.css">
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=send" />
</head>

<body>
  <div class="container">
    <header>
      <div class="logo">TicketTrack</div>
      <a href="../page/ticket.php" class="back-btn">← Back to Tickets</a>
    </header>

    <div class="ticket-detail">
      <h2>Title: <?= htmlspecialchars($ticket['Title']) ?></h2>
      <div style="display: flex; align-items: center; gap:20px;">
        <label>Status:</label>
        <?php if (!empty($nextStatuses)) : ?>
        <form method="POST">
          <select name="status" class="status-badge <?= strtolower(str_replace(' ', '-', $ticket['Status'])) ?>">
            <?php foreach ($nextStatuses as $status) : ?>
            <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="update-btn">Cập nhật</button>
        </form>
        <?php else: ?>
        <span class="status-badge closed"><?= htmlspecialchars($ticket['Status']) ?></span>
        <?php endif; ?>
      </div>

      <p><strong>Priority:</strong> <?= htmlspecialchars($ticket['Priority']) ?></p>
      <p><strong>Created At:</strong> <?= htmlspecialchars($ticket['CreatedAt']) ?></p>
      <p><strong>Created By:</strong> <?= htmlspecialchars($ticket['CreatedByName']) ?></p>
      <p><strong>Assigned To:</strong> <?= htmlspecialchars($ticket['AssignedToName'] ?? 'Unassigned') ?></p>
      <hr>
      <p class="description"><?= nl2br(htmlspecialchars($ticket['Description'])) ?></p>
    </div>

    <!-- Khung chat -->
    <?php if (
        in_array($ticket['Status'], ['Open', 'In Progress']) &&
        ($_SESSION['user']['id'] === $ticket['CreatedBy'] || $_SESSION['user']['id'] === $ticket['AssignedTo'])
    ): ?>
    <div class="chat-section">
      <h3>💬 Chat về ticket</h3>
      <div class="chat-box" id="chatBox"></div>
      <div class="chat-input">
        <input type="text" id="chatInput" placeholder="Nhập tin nhắn..." style="width:80%;" />
        <button id="sendBtn"><strong>Gửi</strong> <span class="material-symbols-outlined">send</span></button>
      </div>
    </div>

    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script>
    const socket = io("http://localhost:3001");

    const ticketId = <?= json_encode($ticket['TicketID']) ?>;
    const userId = <?= json_encode($_SESSION['user']['id']) ?>;
    const userName = <?= json_encode($_SESSION['user']['name']) ?>;
    const userRole = <?= json_encode($_SESSION['user']['role']) ?>;

    // Join room ticket
    socket.emit("joinTicket", {
      ticketId,
      userId
    });

    // Nhận lịch sử chat
    socket.on("chatHistory", (messages) => {
      messages.forEach(msg => {
        addMessage(msg.FullName || msg.userId, msg.Message || msg.message, msg.CreatedAt || msg.createdAt, msg
          .userId);
      });
    });

    // Nhận tin nhắn mới
    socket.on("newMessage", (msg) => {
      addMessage(msg.FullName || msg.userId, msg.Message || msg.message, msg.CreatedAt || msg.createdAt, msg
        .userId);
    });

    // Gửi tin nhắn
    document.getElementById("sendBtn").addEventListener("click", sendMessage);
    document.getElementById("chatInput").addEventListener("keypress", function(e) {
      if (e.key === "Enter") sendMessage();
    });

    function sendMessage() {
      const input = document.getElementById("chatInput");
      const message = input.value;
      if (message.trim() !== "") {
        socket.emit("sendMessage", {
          ticketId,
          userId,
          message
        });
        input.value = "";
      }
    }

    function addMessage(sender, message, time, senderId) {
      const chatBox = document.getElementById("chatBox");
      const div = document.createElement("div");
      div.className = "chat-message " + (senderId === userId ? "self" : "other");
      div.textContent = `[${time}] ${sender}: ${message}`;
      chatBox.appendChild(div);
      chatBox.scrollTop = chatBox.scrollHeight;
    }
    </script>
    <?php elseif ($ticket['Status'] === 'Closed'): ?>
    <p><em>🔒 Chat đã đóng vì ticket đã được xử lý.</em></p>
    <?php endif; ?>

    <!-- Lịch sử xử lý -->
    <div class="ticket-history">
      <h3>Lịch sử xử lý</h3>
      <ul>
        <li><strong><?= htmlspecialchars($ticket['CreatedByName']) ?></strong> đã tạo ticket với trạng thái
          <strong><?= htmlspecialchars($ticket['Status'] ?: 'Open') ?></strong>
          (<?= htmlspecialchars($ticket['CreatedAt']) ?>)
        </li>
        <?php foreach ($logs as $log): ?>
        <?php if ($log['Action'] === 'Updated Status'): ?>
        <li>
          <strong><?= htmlspecialchars($log['UserName']) ?></strong>
          chuyển trạng thái từ <strong><?= htmlspecialchars($log['OldValue'] ?: 'Open') ?></strong>
          sang <strong><?= htmlspecialchars($log['NewValue']) ?></strong>
          (<?= htmlspecialchars($log['CreatedAt']) ?>)
        </li>
        <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</body>

</html>