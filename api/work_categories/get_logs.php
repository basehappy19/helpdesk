<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configs/db_connection.php';

// Auth: ต้องล็อคอิน
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT w.work_date, w.start_time, w.end_time, w.activity_detail, u.username
        FROM daily_work_logs w
        LEFT JOIN users u ON w.user_id = u.id
        WHERE w.category_id = :id
        ORDER BY w.work_date DESC, w.start_time DESC
    ");
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'data' => $data]);
} catch (Exception $e) {
    error_log('get_logs error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Database error']);
}