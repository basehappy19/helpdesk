<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/conn.php';
require_once __DIR__ . '/sync_ticket_time.php'; // 🟢 เรียกใช้ฟังก์ชัน Sync

// ป้องกันการเข้าถึงผิดวิธี
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// รับค่าจาก Payload
$ticketId         = isset($data['work_id']) ? (int)$data['work_id'] : 0; 
$toStatusId       = isset($data['to_status_id']) ? (int)$data['to_status_id'] : 0;
$symptom          = trim($data['symptom'] ?? '');
$cause            = trim($data['cause'] ?? '');
$statusChangedRaw = trim($data['status_changed_at'] ?? '');

// ค่าที่รับเพิ่มมาใหม่ สำหรับผู้แก้ไขปัญหา
$solverByRaw            = trim($data['solver_by'] ?? '');
$solverByOtherRemark    = trim($data['solver_by_other_remark'] ?? '');

// จัดการค่า Solver
$solverId = null;
if ($solverByRaw === 'other') {
    $solverId = null; 
    // เก็บค่า $solverByOtherRemark ตามที่พิมพ์ส่งมา
} elseif (is_numeric($solverByRaw) && $solverByRaw > 0) {
    $solverId = (int)$solverByRaw;
    $solverByOtherRemark = null; // ถ้าเลือก user ในระบบ ให้ล้างค่าช่อง remark ทิ้ง
} else {
    // กรณีไม่ได้เลือกหรือส่งค่าว่างมา
    $solverId = null;
    $solverByOtherRemark = null;
}

$errors = [];
if ($ticketId <= 0)   $errors[] = 'work_id is required';
if ($toStatusId <= 0) $errors[] = 'to_status_id is required';
if ($statusChangedRaw === '') $errors[] = 'status_changed_at is required';

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $errors]);
    exit;
}

// แปลงฟอร์แมตเวลา
$changedAt = str_replace('T', ' ', $statusChangedRaw);
if (strlen($changedAt) === 16) {
    $changedAt .= ':00';
}

try {
    $pdo->beginTransaction();

    // 1. ดึงสถานะก่อนหน้า (from_status)
    $sqlLast = "SELECT to_status FROM ticket_status_logs WHERE ticket_id = :ticket_id ORDER BY changed_at DESC, id DESC LIMIT 1";
    $stmtLast = $pdo->prepare($sqlLast);
    $stmtLast->execute([':ticket_id' => $ticketId]);
    $fromStatus = $stmtLast->fetchColumn();
    $fromStatus = ($fromStatus !== false) ? (int)$fromStatus : null;

    // 2. บันทึกประวัติสถานะลง ticket_status_logs
    $sqlInsert = "
        INSERT INTO ticket_status_logs 
            (ticket_id, from_status, to_status, symptom, cause, solver_by, solver_by_other_remark, changed_by, changed_at)
        VALUES 
            (:ticket_id, :from_status, :to_status, :symptom, :cause, :solver_by, :solver_by_other_remark, :changed_by, :changed_at)
    ";
    $stmtLog = $pdo->prepare($sqlInsert);
    $stmtLog->execute([
        ':ticket_id'              => $ticketId,
        ':from_status'            => $fromStatus,
        ':to_status'              => $toStatusId,
        ':symptom'                => $symptom,
        ':cause'                  => $cause,
        ':solver_by'              => $solverId,
        ':solver_by_other_remark' => $solverByOtherRemark,
        ':changed_by'             => null, // ถ้าระบบรองรับ Auth ให้ดึง session user_id มาใส่ตรงนี้
        ':changed_at'             => $changedAt,
    ]);

    // 3. 🟢 เรียกใช้งาน Sync 🟢
    syncTicketTimestamps($ticketId, $pdo);

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'ok'      => true,
        'message' => 'Status log created and ticket timestamps updated',
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => 'DB error',
        'error'   => $e->getMessage(),
    ]);
}
?>