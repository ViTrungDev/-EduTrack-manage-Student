<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$schedule = [];
$student_info = [];

try {
    // Lấy ClassID của sinh viên
    $stmt = $conn->prepare("
        SELECT ClassID FROM Users WHERE UserID = ?
    ");
    $stmt->execute([$user_id]);
    $class_id = $stmt->fetchColumn();

    if (!$class_id) {
        throw new Exception("Sinh viên chưa được xếp lớp");
    }

    // Lấy thông tin lớp
    $stmt = $conn->prepare("
        SELECT ClassName FROM Class WHERE ClassID = ?
    ");
    $stmt->execute([$class_id]);
    $class_name = $stmt->fetchColumn();

    // Lấy lịch học
    $stmt = $conn->prepare("
        SELECT 
            sch.DayOfWeek,
            TIME_FORMAT(sch.StartTime, '%H:%i') as StartTime,
            TIME_FORMAT(sch.EndTime, '%H:%i') as EndTime,
            sch.Room,
            sub.SubjectName,
            sub.Credit,
            u.FullName as TeacherName
        FROM Schedule sch
        JOIN Subjects sub ON sch.SubjectID = sub.SubjectID
        JOIN Users u ON sch.TeacherID = u.UserID
        WHERE sch.ClassID = ?
        ORDER BY 
            FIELD(sch.DayOfWeek, 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'),
            sch.StartTime
    ");
    $stmt->execute([$class_id]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi khi truy vấn lịch học: " . $e->getMessage());
}

// Nhóm lịch học theo ngày
$schedule_by_day = [];
$day_names = [
    'Mon' => 'Thứ Hai',
    'Tue' => 'Thứ Ba',
    'Wed' => 'Thứ Tư', 
    'Thu' => 'Thứ Năm',
    'Fri' => 'Thứ Sáu',
    'Sat' => 'Thứ Bảy',
    'Sun' => 'Chủ Nhật'
];

foreach ($schedule as $item) {
    $schedule_by_day[$item['DayOfWeek']][] = $item;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch học sinh viên</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .schedule-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 24px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .class-info {
            background-color: #e8f4fc;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 16px;
            color: #2980b9;
        }
        
        .print-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }
        
        .print-btn:hover {
            background-color: #2980b9;
        }
        
        .no-schedule {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
            font-size: 18px;
        }
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .schedule-table th {
            background-color: #3498db;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        
        .schedule-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .schedule-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .schedule-table tr:hover {
            background-color: #e8f4fc;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                background-color: white;
            }
            
            .schedule-container {
                box-shadow: none;
                padding: 0;
            }
            
            .schedule-table {
                page-break-inside: avoid;
            }
        }
        
        @media (max-width: 768px) {
            .schedule-table {
                display: block;
                overflow-x: auto;
            }
            
            .schedule-container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="schedule-container">
        <div class="no-print">
            <h2>Lịch học</h2>
            <div class="class-info">
                Lớp: <strong><?= htmlspecialchars($class_name) ?></strong>
            </div>
            <button class="print-btn" onclick="window.print()">
                <span class="material-symbols-outlined">print</span> In lịch học
            </button>
        </div>

        <?php if (empty($schedule)): ?>
            <div class="no-schedule">
                <p>Hiện chưa có lịch học nào được đăng ký cho lớp của bạn</p>
            </div>
        <?php else: ?>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Thứ</th>
                        <th>Thời gian</th>
                        <th>Môn học</th>
                        <th>Giảng viên</th>
                        <th>Phòng</th>
                        <th>TC</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($day_names as $day_key => $day_name): ?>
                        <?php if (!empty($schedule_by_day[$day_key])): ?>
                            <?php foreach ($schedule_by_day[$day_key] as $item): ?>
                                <tr>
                                    <td><?= $day_name ?></td>
                                    <td><?= $item['StartTime'] ?>-<?= $item['EndTime'] ?></td>
                                    <td><?= htmlspecialchars($item['SubjectName']) ?></td>
                                    <td><?= htmlspecialchars($item['TeacherName']) ?></td>
                                    <td><?= htmlspecialchars($item['Room']) ?></td>
                                    <td><?= $item['Credit'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>