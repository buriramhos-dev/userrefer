<?php
// ข้อมูลการเชื่อมต่อ
// ใช้ตัวแปร Railway MySQL หรือ default สำหรับ local
$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'referback';
$port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';

$tableName = "patient_records"; // ชื่อตารางหลัก (ใช้ร่วมกับโค้ดของคุณ)

try {
    // สร้าง PDO
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // ให้ error เป็น exception
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // คืนค่ามาแบบ array ชื่อคอลัมน์
            PDO::ATTR_EMULATE_PREPARES => false           // ป้องกัน SQL Injection
        ]
    );

} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit;
}
