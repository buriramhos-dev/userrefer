<?php
// ข้อมูลการเชื่อมต่อ
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "referback";   // ตั้งชื่อฐานข้อมูลของคุณ

$tableName = "patient_records"; // ชื่อตารางหลัก (ใช้ร่วมกับโค้ดของคุณ)

try {
    // สร้าง PDO
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
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
