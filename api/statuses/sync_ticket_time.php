<?php
// ไฟล์: api/statuses/sync_ticket_time.php

function syncTicketTimestamps($ticketId, $pdo) {
    // 1. หาเวลาที่รับงานครั้งแรกสุด (เพื่อนำมาเป็นจุดเริ่มต้น SLA)
    $stmtMin = $pdo->prepare("SELECT MIN(changed_at) FROM ticket_status_logs WHERE ticket_id = :id");
    $stmtMin->execute([':id' => $ticketId]);
    $acceptedAt = $stmtMin->fetchColumn();

    // 2. หาสถานะล่าสุด ว่าเสร็จหรือยัง
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

    // ----------------------------------------------------
    // 3. 🟢 คำนวณ SLA Due Date (เวลาที่ควรจะเสร็จ) 🟢
    // ----------------------------------------------------
    $slaDueAt = null;
    
    // จะคำนวณก็ต่อเมื่อ "มีเวลารับเรื่องแล้วเท่านั้น"
    if ($acceptedAt) {
        // ดึง sla_minutes จากตาราง issue_symptoms โดยอ้างอิงจาก symptom_id ของตั๋วใบนี้
        $stmtSla = $pdo->prepare("
            SELECT sym.sla_minutes 
            FROM tickets t
            LEFT JOIN issue_symptoms sym ON t.symptom_id = sym.id
            WHERE t.id = :id
        ");
        $stmtSla->execute([':id' => $ticketId]);
        $slaMinutes = (int)$stmtSla->fetchColumn();

        // สมมติถ้าตั๋วใบไหนไม่ได้เลือกอาการ หรือลืมตั้งค่า SLA ไว้ ให้ใช้ค่า Default = 15 นาที
        if ($slaMinutes <= 0) {
            $slaMinutes = 15;
        }

        // นำเวลารับเรื่อง (accepted_at) มาบวกด้วยจำนวนนาที SLA
        $slaDueAt = date('Y-m-d H:i:s', strtotime($acceptedAt . " + {$slaMinutes} minutes"));
    }

    // 4. อัปเดตข้อมูลทั้งหมดกลับไปที่ตาราง tickets
    $stmtUpdate = $pdo->prepare("
        UPDATE tickets 
        SET accepted_at = :accepted_at, 
            resolved_at = :resolved_at,
            sla_due_at  = :sla_due_at
        WHERE id = :id
    ");
    $stmtUpdate->execute([
        ':accepted_at' => $acceptedAt ?: null,
        ':resolved_at' => $resolvedAt,
        ':sla_due_at'  => $slaDueAt,
        ':id'          => $ticketId
    ]);
}
?>