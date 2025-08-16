<?php
$host = 'localhost';
$dbname = 'EduTrack';
$username = 'root';
$password = '123456789';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Không thể kết nối CSDL. Vui lòng thử lại sau.");
}
?>