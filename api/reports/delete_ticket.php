<?php

declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../db/conn.php';
require_once __DIR__ . '/../../functions/users.php';

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['id'] ?? null;
$ticketCode = $input['code'] ?? null;

$user = null;
if (isset($_SESSION['user'])) {
    $user = getUser($_SESSION['user']['id']);
}

if (!$ticketId || !$ticketCode) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

if (!isset($user['role']) || !in_array($user['role'], ['SYSTEM', 'ADMIN'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'คุณไม่มีสิทธิ์ลบรายการนี้']);
    exit;
}

try {
    $stmtImg = $pdo->prepare("SELECT file_path FROM ticket_images WHERE ticket_id = ?");
    $stmtImg->execute([$ticketId]);
    $images = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

    $pdo->beginTransaction();

    $stmtLog = $pdo->prepare("DELETE FROM ticket_status_logs WHERE ticket_id = ?");
    $stmtLog->execute([$ticketId]);

    $stmtDelImg = $pdo->prepare("DELETE FROM ticket_images WHERE ticket_id = ?");
    $stmtDelImg->execute([$ticketId]);

    $stmtTicket = $pdo->prepare("DELETE FROM tickets WHERE id = ? AND code = ?");
    $stmtTicket->execute([$ticketId, $ticketCode]);

    if ($stmtTicket->rowCount() === 0) {
        throw new Exception("ไม่พบรายการที่ต้องการลบ หรือรหัสไม่ถูกต้อง");
    }

    foreach ($images as $img) {
        $urlParts = parse_url($img['file_path']);
        $relativePath = ltrim($urlParts['path'], '/');

        $fullPath = __DIR__ . '/../../' . $relativePath;

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    $dirPath = __DIR__ . '/../../uploads/tickets/' . $ticketCode;
    if (is_dir($dirPath)) {
        // ลบไฟล์ที่เหลือค้างใน folder (ถ้ามี)
        $files = glob($dirPath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
        rmdir($dirPath);
    }

    $pdo->commit();

    echo json_encode(['ok' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Delete Ticket Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
