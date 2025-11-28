<?php
// env_test.php - แสดงค่า environment variables ที่เว็บเห็น และทดสอบการเชื่อมต่อ MySQL (สั้นๆ)
header('Content-Type: text/plain; charset=utf-8');

$keys = [
    'MYSQLHOST','MYSQLUSER','MYSQLPASSWORD','MYSQLDATABASE','MYSQLPORT',
    'DB_HOST','DB_USER','DB_PASS','DB_NAME','DB_PORT',
    'MYSQL_URL','MYSQL_PUBLIC_URL'
];

foreach ($keys as $k) {
    $v = getenv($k);
    echo str_pad($k, 20) . ': ' . ($v === false ? '(not set)' : $v) . PHP_EOL;
}

echo PHP_EOL . "--- PDO test (attempt to connect) ---" . PHP_EOL;

$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'referback';
$port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';

// Try to connect using TCP (force using host and port)
$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

echo "DSN: $dsn" . PHP_EOL;

if ($host === 'localhost') {
    echo "Note: host is 'localhost' — PDO may attempt Unix socket instead of TCP.\n";
}

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "Connection successful." . PHP_EOL;
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . PHP_EOL;
}
