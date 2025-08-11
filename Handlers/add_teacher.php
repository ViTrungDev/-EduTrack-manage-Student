<?php
include "../includes/header.php";
include "../config/db.php";

$error = '';
$success = '';

// Hàm tạo mã UserID dạng TC + 6 ký tự chữ số
function generateTeacherCode($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = 'TC';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}

// Sinh mã UserID không trùng trong db
function generateUniqueTeacherCode($conn) {
    do {
        $code = generateTeacherCode();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE UserID = :code");
        $stmt->execute([':code' => $code]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);
    return $code;
}

// Lấy danh sách ngành từ programinfo
$stmtPrograms = $conn->query("SELECT ProgramID, ProgramName FROM programinfo ORDER BY ProgramName ASC");
$programs = $stmtPrograms->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['fullName'] ?? '');
    $programID = $_POST['program'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate
    if ($username === '' || $fullName === '' || $programID === '' || $email === '' || $password === '') {
        $error = "Vui lòng nhập đầy đủ tên, họ tên, ngành, email và mật khẩu.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $error = "Tên chỉ chứa chữ, số, dấu gạch dưới, dài 3-50 ký tự.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } else {
        // Kiểm tra programID có tồn tại trong danh sách lấy từ DB không
        $validProgramIDs = array_column($programs, 'ProgramID');
        if (!in_array($programID, $validProgramIDs)) {
            $error = "Ngành chọn không hợp lệ.";
        } else {
            try {
                // Kiểm tra trùng email
                $stmtCheckEmail = $conn->prepare("SELECT COUNT(*) FROM users WHERE Email = :email");
                $stmtCheckEmail->execute([':email' => $email]);
                if ($stmtCheckEmail->fetchColumn() > 0) {
                    $error = "Email này đã được sử dụng.";
                } else {
                    // Tạo mã UserID
                    $userID = generateUniqueTeacherCode($conn);

                    // Hash mật khẩu
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    // Thêm giáo viên vào DB
                    $stmtInsert = $conn->prepare("INSERT INTO users (UserID, Username, FullName, ProgramID, Email, Password, Role) VALUES (:userID, :username, :fullName, :programID, :email, :password, 'teacher')");
                    $stmtInsert->execute([
                        ':userID' => $userID,
                        ':username' => $username,
                        ':fullName' => $fullName,
                        ':programID' => $programID,
                        ':email' => $email,
                        ':password' => $passwordHash
                    ]);

                    $success = "Thêm giáo viên mới thành công. Mã giáo viên: " . htmlspecialchars($userID);
                    // Xóa dữ liệu cũ trong form
                    $username = $fullName = $email = $password = $programID = '';
                }
            } catch (PDOException $e) {
                $error = "Lỗi khi thêm giáo viên: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Thêm giáo viên mới</title>
  <style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f9fafb;
    color: #333;
    margin: 0;
    padding: 0;
  }

  .container {
    max-width: 600px;
    margin: 3rem auto;
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 0 10px rgb(0 0 0 / 0.1);
  }

  h1 {
    margin-bottom: 1rem;
    color: #111827;
    text-align: center;
  }

  .error-message {
    background-color: #fee2e2;
    color: #b91c1c;
    padding: 0.75rem 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
  }

  .success-message {
    background-color: #dcfce7;
    color: #15803d;
    padding: 0.75rem 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
  }

  form {
    display: flex;
    flex-direction: column;
  }

  .form-group {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
  }

  label {
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: #374151;
  }

  input[type="text"],
  input[type="email"],
  input[type="password"],
  select {
    padding: 0.5rem 0.75rem;
    font-size: 1rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    transition: border-color 0.2s ease;
  }

  input[type="text"]:focus,
  input[type="email"]:focus,
  input[type="password"]:focus,
  select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgb(59 130 246 / 0.3);
  }

  button.btn-primary {
    background-color: #2563eb;
    color: white;
    border: none;
    padding: 0.7rem 1.4rem;
    font-size: 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    align-self: flex-start;
  }

  button.btn-primary:hover {
    background-color: #1d4ed8;
  }
  </style>
</head>

<body>
  <main class="container" role="main" aria-labelledby="pageTitle">
    <h1 id="pageTitle">Thêm giáo viên mới</h1>

    <?php if ($error): ?>
    <div class="error-message" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="success-message" role="alert"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate aria-label="Form thêm giáo viên mới">
      <div class="form-group">
        <label for="username">Tên</label>
        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username ?? '') ?>" />
      </div>

      <div class="form-group">
        <label for="fullName">Họ và tên</label>
        <input type="text" id="fullName" name="fullName" required value="<?= htmlspecialchars($fullName ?? '') ?>" />
      </div>

      <div class="form-group">
        <label for="program">Ngành</label>
        <select id="program" name="program" required>
          <option value="">-- Chọn ngành --</option>
          <?php foreach ($programs as $prg): ?>
          <option value="<?= htmlspecialchars($prg['ProgramID']) ?>"
            <?= (isset($programID) && $programID == $prg['ProgramID']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($prg['ProgramName']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>" />
      </div>

      <div class="form-group">
        <label for="password">Mật khẩu</label>
        <input type="password" id="password" name="password" required autocomplete="new-password" />
      </div>

      <button type="submit" class="btn-primary">Thêm giáo viên</button>
    </form>
  </main>
</body>

</html>