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

$logId            = isset($data['log_id']) ? (int)$data['log_id'] : 0;
$toStatusId       = isset($data['to_status_id']) ? (int)$data['to_status_id'] : 0;
$symptom          = trim($data['symptom'] ?? '');
$cause            = trim($data['cause'] ?? '');
$solver_by            = trim($data['solver_by'] ?? '');
$sla            = trim($data['sla'] ?? '');
$statusChangedRaw = trim($data['status_changed_at'] ?? '');

$errors = [];
if ($logId <= 0)     $errors[] = 'log_id is required';
if ($toStatusId <= 0) $errors[] = 'to_status_id is required';
if ($statusChangedRaw === '') $errors[] = 'status_changed_at is required';

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $errors]);
    exit;
}

// แปลง format datetime-local → MySQL format
$changedAt = str_replace('T', ' ', $statusChangedRaw);
if (strlen($changedAt) === 16) {
    $changedAt .= ':00';
}

try {
    $sql = "
        UPDATE ticket_status_logs
        SET 
            to_status    = :to_status,
            symptom      = :symptom,
            cause        = :cause,
            solver_by    = :solver_by,
            sla          = :sla,
            changed_at   = :changed_at
        WHERE id = :log_id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':to_status'  => $toStatusId,
        ':symptom'    => $symptom,
        ':cause'      => $cause,
        ':solver_by'      => $solver_by,
        ':sla'      => $sla,
        ':changed_at' => $changedAt,
        ':log_id'     => $logId,
    ]);

    http_response_code(200);
    echo json_encode([
        'ok'      => true,
        'message' => 'Status log updated',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => 'DB error',
        'error'   => $e->getMessage(),
    ]);
}
