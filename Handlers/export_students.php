<?php
// ../Handlers/export_students.php
// exportStudents($rows, $hasFaculty = false, $hasClass = false)

function exportStudents($rows, $hasFaculty = false, $hasClass = false) {
    // Xóa buffer tránh khoảng trắng thừa
    if (ob_get_length()) ob_end_clean();

    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=Students.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // style: không để background-color mặc định (đặt background: none cho th/td)
    echo "<table border='1' style='border-collapse: collapse; mso-background-source:auto; mso-pattern:auto;'>";
    echo "<thead>
            <tr>
                <th style='background: none;'>STT</th>
                <th style='background: none;'>Họ và tên</th>
                <th style='background: none;'>Mã SV</th>";
    if ($hasFaculty) { echo "<th style='background: none;'>Khoa</th>"; }
    echo "<th style='background: none;'>Ngành</th>";
    if ($hasClass) { echo "<th style='background: none;'>Lớp</th>"; }
    echo "      <th style='background: none;'>Ngày vào học</th>
            </tr>
          </thead><tbody>";

    $stt = 1;
    foreach ($rows as $row) {
        $fullname     = htmlspecialchars($row['FullName'] ?? '');
        $studentCode  = htmlspecialchars($row['StudentCode'] ?? '');
        $programName  = htmlspecialchars($row['ProgramName'] ?? 'Chưa cập nhật');
        $facultyName  = htmlspecialchars($row['FacultyName'] ?? 'Chưa cập nhật');
        $className    = htmlspecialchars($row['ClassName'] ?? 'Chưa cập nhật');
        $createdAtRaw = $row['CreatedAt'] ?? null;
        $createdAt    = $createdAtRaw ? date("d/m/Y", strtotime($createdAtRaw)) : '';

        echo "<tr>";
        echo "<td style='background: none; text-align:center;'>{$stt}</td>";
        echo "<td style='background: none;'>{$fullname}</td>";
        echo "<td style='background: none;'>{$studentCode}</td>";
        if ($hasFaculty) { echo "<td style='background: none;'>{$facultyName}</td>"; }
        echo "<td style='background: none;'>{$programName}</td>";
        if ($hasClass) { echo "<td style='background: none;'>{$className}</td>"; }
        echo "<td style='background: none; text-align:center;'>{$createdAt}</td>";
        echo "</tr>";
        $stt++;
    }

    echo "</tbody></table>";
    exit;
}