<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Method Not Allowed',
    ]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'Invalid JSON',
    ]);
    exit;
}

$logId = isset($data['id']) ? (int)$data['id'] : 0;

if ($logId <= 0) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Invalid log id',
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM ticket_status_logs WHERE id = :id");
    $stmt->execute([':id' => $logId]);

    if ($stmt->rowCount() === 0) {
        // ไม่เจอแถวให้ลบ
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'message' => 'Status log not found',
        ]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Status log deleted',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'DB error',
        'error' => $e->getMessage(),
    ]);
}
