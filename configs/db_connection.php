<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$_host     = $_ENV['DB_HOST']     ?? '';
$_port     = $_ENV['DB_PORT']     ?? '3306';
$_dbname   = $_ENV['DB_NAME']     ?? '';
$_dbuser   = $_ENV['DB_USER']     ?? '';
$_dbpass   = $_ENV['DB_PASSWORD'] ?? '';

$dsn = "mysql:host={$_host};port={$_port};dbname={$_dbname};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $_dbuser, $_dbpass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
    ]);
} catch (PDOException $e) {
    error_log('DB Connection failed: ' . $e->getMessage());
    http_response_code(500);
    // ถ้าเป็น API request ตอบ JSON ไม่งั้นตอบ HTML
    if (
        (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
        (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
    ) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้']);
    } else {
        echo '<h1>ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h1><p>กรุณาลองใหม่ภายหลัง</p>';
    }
    exit;
}

// cleanup ตัวแปรชั่วคราว
unset($_host, $_port, $_dbname, $_dbuser, $_dbpass, $dsn);
