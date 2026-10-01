<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../configs/db_connection.php';
require_once __DIR__ . '/../../models/UserModel.php';

header('Content-Type: application/json; charset=utf-8');

// --- Auth: ต้องล็อคอินและมีสิทธิ์ SYSTEM หรือ ADMIN ---
$sessionUser = $_SESSION['user'] ?? null;
if (!$sessionUser) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

$userModel   = new UserModel($pdo);
$currentUser = $userModel->getById((int)$sessionUser['id']);

if (!$currentUser || !in_array($currentUser['role'], ['SYSTEM', 'ADMIN'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'คุณไม่มีสิทธิ์ลบรายการนี้']);
    exit;
}

// --- Validate input ---
$input      = json_decode(file_get_contents('php://input'), true);
$ticketId   = isset($input['id'])   ? (int)$input['id']   : 0;
$ticketCode = isset($input['code']) ? trim((string)$input['code']) : '';

if ($ticketId <= 0 || $ticketCode === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    // ดึงรูปภาพก่อน (นอก transaction เพราะเป็น SELECT)
    $stmtImg = $pdo->prepare('SELECT file_path FROM ticket_images WHERE ticket_id = ?');
    $stmtImg->execute([$ticketId]);
    $images = $stmtImg->fetchAll();

    $pdo->beginTransaction();

    // ลบ log ก่อน (FK)
    $pdo->prepare('DELETE FROM ticket_status_logs WHERE ticket_id = ?')->execute([$ticketId]);

    // ลบรูป (FK)
    $pdo->prepare('DELETE FROM ticket_images WHERE ticket_id = ?')->execute([$ticketId]);

    // ลบ ticket
    $stmtTicket = $pdo->prepare('DELETE FROM tickets WHERE id = ? AND code = ?');
    $stmtTicket->execute([$ticketId, $ticketCode]);

    if ($stmtTicket->rowCount() === 0) {
        throw new RuntimeException('ไม่พบรายการที่ต้องการลบ หรือรหัสไม่ถูกต้อง');
    }

    $pdo->commit();

    // ลบไฟล์จริงหลัง commit (ถ้า rollback ไฟล์ยังอยู่ได้)
    foreach ($images as $img) {
        $urlParts = parse_url($img['file_path']);
        if (!empty($urlParts['path'])) {
            $fullPath = realpath(__DIR__ . '/../../' . ltrim($urlParts['path'], '/'));
            // ป้องกัน path traversal
            $uploadBase = realpath(__DIR__ . '/../../uploads');
            if ($fullPath && $uploadBase && str_starts_with($fullPath, $uploadBase) && is_file($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    // ลบ directory ของ ticket
    $dirPath = realpath(__DIR__ . '/../../uploads/tickets/' . preg_replace('/[^A-Z0-9\-]/i', '', $ticketCode));
    if ($dirPath && is_dir($dirPath)) {
        foreach (glob($dirPath . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    echo json_encode(['ok' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Delete Ticket Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่']);
}
