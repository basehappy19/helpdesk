<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/conn.php';

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
$solver_by        = trim($data['solver_by'] ?? '');
$statusChangedRaw = trim($data['status_changed_at'] ?? '');

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
    // 🟢 เริ่ม Transaction (รับประกันว่า Log และ Ticket จะถูกอัปเดตพร้อมกัน)
    $pdo->beginTransaction();

    // 1. ดึงสถานะก่อนหน้า (from_status)
    $sqlLast = "
        SELECT to_status 
        FROM ticket_status_logs 
        WHERE ticket_id = :ticket_id
        ORDER BY changed_at DESC, id DESC
        LIMIT 1
    ";
    $stmtLast = $pdo->prepare($sqlLast);
    $stmtLast->execute([':ticket_id' => $ticketId]);
    $fromStatus = $stmtLast->fetchColumn();
    $fromStatus = ($fromStatus !== false) ? (int)$fromStatus : null;

    // 2. บันทึกประวัติสถานะลง ticket_status_logs
    $sqlInsert = "
        INSERT INTO ticket_status_logs 
            (ticket_id, from_status, to_status, symptom, cause, solver_by, changed_by, changed_at)
        VALUES 
            (:ticket_id, :from_status, :to_status, :symptom, :cause, :solver_by, :changed_by, :changed_at)
    ";
    $stmtLog = $pdo->prepare($sqlInsert);
    $stmtLog->execute([
        ':ticket_id'   => $ticketId,
        ':from_status' => $fromStatus,
        ':to_status'   => $toStatusId,
        ':symptom'     => $symptom,
        ':cause'       => $cause,
        ':solver_by'   => $solver_by,
        ':changed_by'  => null, // ดึงจาก session User ID แทนถ้าระบบ Auth รองรับ
        ':changed_at'  => $changedAt,
    ]);

    // 3. 🟢 อัปเดตข้อมูล SLA Timestamp ลงในตาราง tickets 🟢
    // - ใช้ COALESCE(accepted_at, :time) เพื่อให้เวลา "รับเรื่อง" บันทึกแค่ครั้งแรกครั้งเดียว
    $sqlUpdateTicket = "UPDATE tickets SET accepted_at = COALESCE(accepted_at, :changed_time)";
    $updateParams = [
        ':ticket_id' => $ticketId,
        ':changed_time' => $changedAt
    ];

    // ถ้าสถานะเปลี่ยนเป็น "เสร็จสิ้น" (ID = 6) ให้ประทับเวลา resolved_at
    if ($toStatusId === 6) {
        $sqlUpdateTicket .= ", resolved_at = :resolved_time";
        $updateParams[':resolved_time'] = $changedAt;
    } else {
        // หากถูกแก้สถานะถอยหลัง (เช่น เปลี่ยนจาก 6 กลับไปเป็น 5) ให้เคลียร์ resolved_at ออก
        $sqlUpdateTicket .= ", resolved_at = NULL";
    }

    $sqlUpdateTicket .= " WHERE id = :ticket_id";
    $stmtUpdate = $pdo->prepare($sqlUpdateTicket);
    $stmtUpdate->execute($updateParams);

    // ยืนยันการทำงาน (Commit)
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