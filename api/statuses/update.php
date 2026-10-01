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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

$logId            = (int)($data['log_id']            ?? 0);
$toStatusId       = (int)($data['to_status_id']      ?? 0);
$symptom          = trim((string)($data['symptom']           ?? ''));
$cause            = trim((string)($data['cause']             ?? ''));
$statusChangedRaw = trim((string)($data['status_changed_at'] ?? ''));
$solverByRaw      = trim((string)($data['solver_by']         ?? ''));
$solverByOtherRemark = trim((string)($data['solver_by_other_remark'] ?? ''));

$errors = [];
if ($logId <= 0)             $errors[] = 'log_id is required';
if ($toStatusId <= 0)        $errors[] = 'to_status_id is required';
if ($statusChangedRaw === '') $errors[] = 'status_changed_at is required';

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $errors]);
    exit;
}

$changedAt = str_replace('T', ' ', $statusChangedRaw);
if (strlen($changedAt) === 16) {
    $changedAt .= ':00';
}

if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $changedAt)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'รูปแบบวันที่ไม่ถูกต้อง']);
    exit;
}

// จัดการ solver
$solverId = null;
if ($solverByRaw === 'other') {
    // เก็บ remark ไว้
} elseif (is_numeric($solverByRaw) && (int)$solverByRaw > 0) {
    $solverId            = (int)$solverByRaw;
    $solverByOtherRemark = '';
} else {
    $solverByOtherRemark = '';
}

try {
    $pdo->beginTransaction();

    // ดึง ticket_id จาก log
    $stmtFind = $pdo->prepare('SELECT ticket_id FROM ticket_status_logs WHERE id = :id');
    $stmtFind->execute([':id' => $logId]);
    $ticketId = $stmtFind->fetchColumn();

    if (!$ticketId) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Status log not found']);
        exit;
    }

    $pdo->prepare("
        UPDATE ticket_status_logs
        SET
            to_status              = :to_status,
            symptom                = :symptom,
            cause                  = :cause,
            solver_by              = :solver_by,
            solver_by_other_remark = :solver_by_other_remark,
            changed_at             = :changed_at
        WHERE id = :log_id
        LIMIT 1
    ")->execute([
        ':to_status'              => $toStatusId,
        ':symptom'                => $symptom  ?: null,
        ':cause'                  => $cause    ?: null,
        ':solver_by'              => $solverId,
        ':solver_by_other_remark' => $solverByOtherRemark ?: null,
        ':changed_at'             => $changedAt,
        ':log_id'                 => $logId,
    ]);

    syncTicketTimestamps((int)$ticketId, $pdo);

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['ok' => true, 'message' => 'Status log updated']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('status/update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่']);
}