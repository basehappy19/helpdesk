<?php

declare(strict_types=1);

/**
 * Sync ticket timestamps (accepted_at, resolved_at, sla_due_at) จาก status logs
 *
 * เรียกหลังทุก INSERT/UPDATE/DELETE ใน ticket_status_logs
 */
function syncTicketTimestamps(int $ticketId, PDO $pdo): void
{
    // 1. เวลารับเรื่องครั้งแรก = changed_at ของ log แรกสุด
    $acceptedAt = $pdo->prepare(
        'SELECT MIN(changed_at) FROM ticket_status_logs WHERE ticket_id = :id'
    );
    $acceptedAt->execute([':id' => $ticketId]);
    $acceptedAtVal = $acceptedAt->fetchColumn() ?: null;

    // 2. สถานะล่าสุด
    $stmtLast = $pdo->prepare("
        SELECT to_status, changed_at
        FROM ticket_status_logs
        WHERE ticket_id = :id
        ORDER BY changed_at DESC, id DESC
        LIMIT 1
    ");
    $stmtLast->execute([':id' => $ticketId]);
    $lastLog = $stmtLast->fetch();

    // resolved_at = changed_at ของ log สุดท้าย ถ้า to_status = 6 (COMPLETED)
    $resolvedAt = null;
    if ($lastLog && (int)$lastLog['to_status'] === 6) {
        $resolvedAt = $lastLog['changed_at'];
    }

    // 3. คำนวณ SLA Due Date
    $slaDueAt = null;
    if ($acceptedAtVal !== null) {
        $stmtSla = $pdo->prepare("
            SELECT COALESCE(sym.sla_minutes, 15) AS sla_minutes
            FROM tickets t
            LEFT JOIN issue_symptoms sym ON t.symptom_id = sym.id
            WHERE t.id = :id
        ");
        $stmtSla->execute([':id' => $ticketId]);
        $slaMinutes = max(1, (int)$stmtSla->fetchColumn());

        $slaDueAt = date('Y-m-d H:i:s', strtotime("{$acceptedAtVal} +{$slaMinutes} minutes"));
    }

    // 4. อัปเดต tickets
    $pdo->prepare("
        UPDATE tickets
        SET accepted_at = :accepted_at,
            resolved_at = :resolved_at,
            sla_due_at  = :sla_due_at
        WHERE id = :id
    ")->execute([
        ':accepted_at' => $acceptedAtVal,
        ':resolved_at' => $resolvedAt,
        ':sla_due_at'  => $slaDueAt,
        ':id'          => $ticketId,
    ]);
}