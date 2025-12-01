<?php
// API endpoint for user.php: ส่งข้อมูลตารางเป็น JSON (ทั้งหมด)
header('Content-Type: application/json; charset=utf-8');

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'referback';
$port = getenv('DB_PORT') ?: '3306';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );

    $allRows = $pdo->query("
        SELECT id, date_in, name, surname, gender, ward, hospital, o2_ett_icd, partner, note,
               time_contact AS contact_time, status
        FROM patient_records
        ORDER BY status ASC, date_in DESC, id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $allRows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
