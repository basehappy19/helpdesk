<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configs/db_connection.php';
require_once __DIR__ . '/sync_ticket_time.php';

// --- Auth ---
$sessionUser = $_SESSION['user'] ?? null;
if (!$sessionUser) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
    exit;
}

$logId = (int)($data['id'] ?? 0);

if ($logId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid log id']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtFind = $pdo->prepare('SELECT ticket_id FROM ticket_status_logs WHERE id = :id');
    $stmtFind->execute([':id' => $logId]);
    $ticketId = $stmtFind->fetchColumn();

    if (!$ticketId) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Status log not found']);
        exit;
    }

    $pdo->prepare('DELETE FROM ticket_status_logs WHERE id = :id')->execute([':id' => $logId]);

    syncTicketTimestamps((int)$ticketId, $pdo);

    $pdo->commit();

    echo json_encode(['ok' => true, 'message' => 'Status log deleted']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('status/delete error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่']);
}