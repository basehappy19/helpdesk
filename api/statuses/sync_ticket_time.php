<?php

function syncTicketTimestamps($ticketId, $pdo) {
    // 1. หาเวลาที่รับงานครั้งแรกสุด (Log อันแรกสุดของตั๋วใบนี้)
    $stmtMin = $pdo->prepare("SELECT MIN(changed_at) FROM ticket_status_logs WHERE ticket_id = :id");
    $stmtMin->execute([':id' => $ticketId]);
    $acceptedAt = $stmtMin->fetchColumn();

    // 2. หาสถานะล่าสุด (Log อันสุดท้ายของตั๋วใบนี้)
    $stmtLast = $pdo->prepare("
        SELECT to_status, changed_at 
        FROM ticket_status_logs 
        WHERE ticket_id = :id 
        ORDER BY changed_at DESC, id DESC 
        LIMIT 1
    ");
    $stmtLast->execute([':id' => $ticketId]);
    $lastLog = $stmtLast->fetch(PDO::FETCH_ASSOC);

    $resolvedAt = null;
    // ถ้าสถานะล่าสุดคือ "เสร็จสิ้น" (สมมติ ID = 6) ให้ประทับเวลา
    if ($lastLog && (int)$lastLog['to_status'] === 6) { 
        $resolvedAt = $lastLog['changed_at'];
    }

    // 3. อัปเดตข้อมูลกลับไปที่ตาราง tickets
    $stmtUpdate = $pdo->prepare("
        UPDATE tickets 
        SET accepted_at = :accepted_at, 
            resolved_at = :resolved_at 
        WHERE id = :id
    ");
    $stmtUpdate->execute([
        ':accepted_at' => $acceptedAt ?: null, // ถ้าลบ log ออกหมด จะกลายเป็น null ให้เอง
        ':resolved_at' => $resolvedAt,
        ':id' => $ticketId
    ]);
}
?>