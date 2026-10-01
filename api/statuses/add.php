<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configs/db_connection.php';
require_once __DIR__ . '/sync_ticket_time.php';

// --- Auth: ต้องล็อคอินและมีสิทธิ์ ---
$sessionUser = $_SESSION['user'] ?? null;
if (!$sessionUser) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

$changedBy = (int)$sessionUser['id'];

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

// --- Parse & validate input ---
$ticketId         = (int)($data['work_id']          ?? 0);
$toStatusId       = (int)($data['to_status_id']     ?? 0);
$symptom          = trim((string)($data['symptom']           ?? ''));
$cause            = trim((string)($data['cause']             ?? ''));
$statusChangedRaw = trim((string)($data['status_changed_at'] ?? ''));
$solverByRaw      = trim((string)($data['solver_by']         ?? ''));
$solverByOtherRemark = trim((string)($data['solver_by_other_remark'] ?? ''));

$errors = [];
if ($ticketId <= 0)          $errors[] = 'work_id is required';
if ($toStatusId <= 0)        $errors[] = 'to_status_id is required';
if ($statusChangedRaw === '') $errors[] = 'status_changed_at is required';

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $errors]);
    exit;
}

// แปลง datetime-local → MySQL format
$changedAt = str_replace('T', ' ', $statusChangedRaw);
if (strlen($changedAt) === 16) {
    $changedAt .= ':00';
}

// Validate ว่า datetime format ถูกต้อง
if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $changedAt)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'รูปแบบวันที่ไม่ถูกต้อง']);
    exit;
}

// จัดการ solver
$solverId = null;
if ($solverByRaw === 'other') {
    // เก็บ $solverByOtherRemark ไว้ตามเดิม
} elseif (is_numeric($solverByRaw) && (int)$solverByRaw > 0) {
    $solverId            = (int)$solverByRaw;
    $solverByOtherRemark = '';
} else {
    $solverByOtherRemark = '';
}

try {
    $pdo->beginTransaction();

    // ดึงสถานะก่อนหน้า
    $stmtLast = $pdo->prepare(
        'SELECT to_status FROM ticket_status_logs WHERE ticket_id = :tid ORDER BY changed_at DESC, id DESC LIMIT 1'
    );
    $stmtLast->execute([':tid' => $ticketId]);
    $fromStatus = $stmtLast->fetchColumn();
    $fromStatus = ($fromStatus !== false) ? (int)$fromStatus : null;

    // Insert log
    $pdo->prepare("
        INSERT INTO ticket_status_logs
            (ticket_id, from_status, to_status, symptom, cause, solver_by, solver_by_other_remark, changed_by, changed_at)
        VALUES
            (:ticket_id, :from_status, :to_status, :symptom, :cause, :solver_by, :solver_by_other_remark, :changed_by, :changed_at)
    ")->execute([
        ':ticket_id'              => $ticketId,
        ':from_status'            => $fromStatus,
        ':to_status'              => $toStatusId,
        ':symptom'                => $symptom  ?: null,
        ':cause'                  => $cause    ?: null,
        ':solver_by'              => $solverId,
        ':solver_by_other_remark' => $solverByOtherRemark ?: null,
        ':changed_by'             => $changedBy,
        ':changed_at'             => $changedAt,
    ]);

    syncTicketTimestamps($ticketId, $pdo);

    $pdo->commit();

    http_response_code(201);
    echo json_encode(['ok' => true, 'message' => 'Status log created']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('status/add error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่']);
}