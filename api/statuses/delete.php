<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/conn.php';
require_once __DIR__ . '/sync_ticket_time.php'; // 🟢 เรียกใช้ฟังก์ชัน Sync

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
    exit;
}

$logId = isset($data['id']) ? (int)$data['id'] : 0;

if ($logId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid log id']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. 🟢 หา Ticket ID จาก Log ที่กำลังจะลบ (เพื่อเอาไปใช้อัปเดตตารางตั๋ว)
    $stmtFind = $pdo->prepare("SELECT ticket_id FROM ticket_status_logs WHERE id = :id");
    $stmtFind->execute([':id' => $logId]);
    $ticketId = $stmtFind->fetchColumn();

    if (!$ticketId) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Status log not found']);
        exit;
    }

    // 2. ลบข้อมูล Log ทิ้ง
    $stmt = $pdo->prepare("DELETE FROM ticket_status_logs WHERE id = :id");
    $stmt->execute([':id' => $logId]);

    // 3. 🟢 สั่งประมวลผลเวลาใหม่ 🟢
    syncTicketTimestamps($ticketId, $pdo);

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'Status log deleted and ticket synced',
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'DB error',
        'error' => $e->getMessage(),
    ]);
}
?>