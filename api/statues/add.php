<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/conn.php';

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

$ticketId         = isset($data['work_id']) ? (int)$data['work_id'] : 0; 
$toStatusId       = isset($data['to_status_id']) ? (int)$data['to_status_id'] : 0;
$symptom          = trim($data['symptom'] ?? '');
$cause            = trim($data['cause'] ?? '');
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

$changedAt = str_replace('T', ' ', $statusChangedRaw);
if (strlen($changedAt) === 16) {
    $changedAt .= ':00';
}

try {
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
    if ($fromStatus !== false) {
        $fromStatus = (int)$fromStatus;
    } else {
        $fromStatus = null;
    }

    $sqlInsert = "
        INSERT INTO ticket_status_logs 
            (ticket_id, from_status, to_status, symptom, cause, changed_by, changed_at)
        VALUES 
            (:ticket_id, :from_status, :to_status, :symptom, :cause, :changed_by, :changed_at)
    ";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':ticket_id'   => $ticketId,
        ':from_status' => $fromStatus,
        ':to_status'   => $toStatusId,
        ':symptom'     => $symptom,
        ':cause'     => $cause,
        ':changed_by'  => null,   
        ':changed_at'  => $changedAt,
    ]);

    http_response_code(201);
    echo json_encode([
        'ok'      => true,
        'message' => 'Status log created',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => 'DB error',
        'error'   => $e->getMessage(),
    ]);
}
