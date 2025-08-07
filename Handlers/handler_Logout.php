<?php
session_start();           // Khởi động session
session_unset();           // Xóa tất cả session
session_destroy();         // Hủy session hoàn toàn

header("Location: ../index.php"); // Chuyển hướng về trang login
exit();
  