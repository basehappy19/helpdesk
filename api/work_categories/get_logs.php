<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/conn.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $sql = "SELECT w.work_date, w.start_time, w.end_time, w.activity_detail, u.username 
            FROM daily_work_logs w
            LEFT JOIN users u ON w.user_id = u.id
            WHERE w.category_id = ?
            ORDER BY w.work_date DESC, w.start_time DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Database error']);
}