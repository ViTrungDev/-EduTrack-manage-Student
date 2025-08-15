<?php
session_start();
include '../config/db.php'; 

if (!isset($_SESSION['user']['id'])) {
    die("Bạn cần đăng nhập để tạo ticket.");
}

$errors = [];
$success = false;

// Lấy danh sách AssignedTo (admin + teacher)
$assignedUsers = [];
if ($conn instanceof PDO) {
    $stmt = $conn->query("SELECT UserID, FullName, Role FROM Users WHERE Role IN ('admin', 'teacher')");
    $assignedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $res = $conn->query("SELECT UserID, FullName, Role FROM Users WHERE Role IN ('admin', 'teacher')");
    while ($row = $res->fetch_assoc()) {
        $assignedUsers[] = $row;
    }
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['Title'] ?? '');
    $createdBy = $_SESSION['user']['id']; 
    $assignedTo = $_POST['AssignedTo'] ?? null;
    $priority = $_POST['Priority'] ?? 'Medium';
    $relatedType = $_POST['RelatedType'] ?? 'Other';
    $description = trim($_POST['Description'] ?? '');

    if ($title === '' || $description === '') {
        $errors[] = "Vui lòng nhập đầy đủ tiêu đề và mô tả.";
    }

    if (empty($errors)) {
        try {
            if ($conn instanceof PDO) {
                $sql = "INSERT INTO Ticket (Title, Description, CreatedBy, AssignedTo, Priority, RelatedType) 
                        VALUES (:title, :description, :createdby, :assignedto, :priority, :relatedtype)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':createdby' => $createdBy,
                    ':assignedto' => $assignedTo ?: null,
                    ':priority' => $priority,
                    ':relatedtype' => $relatedType
                ]);
            } else {
                $titleEsc = $conn->real_escape_string($title);
                $descEsc = $conn->real_escape_string($description);
                $createdByEsc = $conn->real_escape_string($createdBy);
                $assignedToEsc = $assignedTo ? "'" . $conn->real_escape_string($assignedTo) . "'" : "NULL";
                $priorityEsc = $conn->real_escape_string($priority);
                $relatedTypeEsc = $conn->real_escape_string($relatedType);

                $sql = "INSERT INTO Ticket (Title, Description, CreatedBy, AssignedTo, Priority, RelatedType) 
                        VALUES ('$titleEsc', '$descEsc', '$createdByEsc', $assignedToEsc, '$priorityEsc', '$relatedTypeEsc')";
                $conn->query($sql);
            }
            $success = true;
        } catch (Exception $e) {
            $errors[] = "Lỗi khi tạo ticket: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>New Ticket</title>
  <link rel="stylesheet" href="../assets/css/create_ticket.css">
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_back" />
</head>

<body>
  <div class="container">
    <h1>New Ticket</h1>

    <?php if ($success): ?>
    <p style="color: green;">Tạo ticket thành công!</p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <ul style="color: red;">
      <?php foreach ($errors as $err): ?>
      <li><?= htmlspecialchars($err) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <form method="POST">
      <label>Title:</label>
      <input type="text" name="Title" required>

      <label>Assigned To:</label>
      <select name="AssignedTo">
        <option value="">-- Chọn người xử lý --</option>
        <?php foreach ($assignedUsers as $user): ?>
        <option value="<?= htmlspecialchars($user['UserID']) ?>">
          <?= htmlspecialchars($user['FullName'] . " ({$user['Role']})") ?>
        </option>
        <?php endforeach; ?>
      </select>

      <label>Priority:</label>
      <select name="Priority">
        <option value="Low">Low</option>
        <option value="Medium" selected>Medium</option>
        <option value="High">High</option>
      </select>

      <label>Related Type:</label>
      <select name="RelatedType">
        <option value="Subject">Subject</option>
        <option value="Class">Class</option>
        <option value="Other" selected>Other</option>
      </select>

      <label>Description:</label>
      <textarea name="Description" required></textarea>

      <button type="submit">Tạo Ticket</button>
      <a href="ticket.php" class="back-link"><span class="material-symbols-outlined">arrow_back</span>Quay lại danh sách
        ticket</a>
    </form>
  </div>
</body>

</html>