<?php

function getRecentReports(int $limit = 3): array {
    global $pdo;
    $limit = (int)$limit;
    
    $sql = "
        SELECT
            r.id,
            r.code,
            r.created_at,
            r.department,
            r.reporter_name,
            rt.name_th      AS request_type_name,
            ct.name_th      AS category_name,
            st.name_th      AS symptom_name,

            sl.id           AS status_log_id,
            sl.changed_at   AS status_changed_at,
            s_from.name_th  AS from_status_name,
            s_to.name_th    AS to_status_name,
            s_from.style    AS status_from_style,
            s_to.style      AS status_to_style
        FROM tickets AS r
            LEFT JOIN request_types      AS rt     ON r.request_type_id = rt.id
            LEFT JOIN issue_categories   AS ct     ON r.category_id     = ct.id
            LEFT JOIN issue_symptoms     AS st     ON r.symptom_id      = st.id
            LEFT JOIN ticket_status_logs AS sl     ON sl.ticket_id      = r.id
            LEFT JOIN ticket_statuses    AS s_from ON s_from.id         = sl.from_status
            LEFT JOIN ticket_statuses    AS s_to   ON s_to.id           = sl.to_status
        ORDER BY r.created_at DESC, sl.changed_at DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $byId = [];
    foreach ($rows as $row) {
        $id = (int)$row['id'];

        // สร้างโครงสร้าง Ticket หากยังไม่มีใน Array
        if (!isset($byId[$id])) {
            $byId[$id] = [
                'id'                 => $id,
                'code'               => $row['code'],
                'created_at'         => $row['created_at'],
                'department'         => $row['department'],
                'reporter_name'      => $row['reporter_name'],
                'request_type_name'  => $row['request_type_name'],
                'category_name'      => $row['category_name'],
                'symptom_name'       => $row['symptom_name'],
                'ticket_status_logs' => [],
            ];
        }

        // หากมีประวัติสถานะ ให้เพิ่มเข้าไปใน Ticket นั้นๆ
        if (!is_null($row['status_log_id'])) {
            // จำกัดแค่ 3 log ล่าสุดต่อ 1 ticket
            if (count($byId[$id]['ticket_status_logs']) < 3) {
                $byId[$id]['ticket_status_logs'][] = [
                    'id'                 => (int)$row['status_log_id'],
                    'status_changed_at'  => $row['status_changed_at'],
                    'from_status_name'   => $row['from_status_name'],
                    'to_status_name'     => $row['to_status_name'],
                    'from_status_style'  => $row['status_from_style'],
                    'to_status_style'    => $row['status_to_style'], 
                ];
            }
        }
    }

    return array_values($byId);
}

function getTotalReportsCount(): int {
    global $pdo;
    $sql = "SELECT COUNT(id) FROM tickets";
    $stmt = $pdo->query($sql);
    return (int)$stmt->fetchColumn();
}

function getAllReports(int $limit = 50, int $offset = 0): array {
    global $pdo;
    
    // ------------------------------------------------------------------
    // ขั้นตอนที่ 1: ดึงข้อมูล Tickets หลัก (รองรับ Limit และ Offset สำหรับแบ่งหน้า)
    // ------------------------------------------------------------------
    $sqlTickets = "
        SELECT
            r.id,
            r.code,
            r.created_at,
            r.department,
            r.reporter_name,
            rt.name_th      AS request_type_name,
            ct.name_th      AS category_name,
            st.name_th      AS symptom_name
        FROM tickets AS r
            LEFT JOIN request_types      AS rt ON r.request_type_id = rt.id
            LEFT JOIN issue_categories   AS ct ON r.category_id     = ct.id
            LEFT JOIN issue_symptoms     AS st ON r.symptom_id      = st.id
        ORDER BY r.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sqlTickets);
    // Bind ค่าแบบ Integer ป้องกัน Error และ SQL Injection
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tickets)) {
        return []; // ถ้าไม่มีข้อมูลเลย ให้คืนค่า Array ว่าง
    }

    // สร้าง Array เตรียมเก็บข้อมูลโดยใช้ ID เป็น Key เพื่อให้ค้นหา/ประกอบร่างได้ง่าย
    $reports = [];
    $ticketIds = [];
    foreach ($tickets as $ticket) {
        $id = (int)$ticket['id'];
        $ticketIds[] = $id;
        
        $ticket['id'] = $id;
        $ticket['ticket_status_logs'] = []; // เตรียมช่องว่างสำหรับใส่ Logs
        $reports[$id] = $ticket;
    }

    // ------------------------------------------------------------------
    // ขั้นตอนที่ 2: ดึงประวัติสถานะ (Logs) เฉพาะตั๋วที่ดึงมาได้ในขั้นที่ 1
    // ------------------------------------------------------------------
    // สร้างเครื่องหมาย ? ตามจำนวน Ticket IDs เช่น (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
    
    $sqlLogs = "
        SELECT
            sl.ticket_id,
            sl.id           AS status_log_id,
            sl.changed_at   AS status_changed_at,
            s_from.name_th  AS from_status_name,
            s_to.name_th    AS to_status_name,
            s_from.style    AS status_from_style,
            s_to.style      AS status_to_style
        FROM ticket_status_logs AS sl
            LEFT JOIN ticket_statuses AS s_from ON s_from.id = sl.from_status
            LEFT JOIN ticket_statuses AS s_to   ON s_to.id   = sl.to_status
        WHERE sl.ticket_id IN ($placeholders)
        ORDER BY sl.changed_at DESC
    ";

    $stmtLogs = $pdo->prepare($sqlLogs);
    $stmtLogs->execute($ticketIds);
    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------------
    // ขั้นตอนที่ 3: ประกอบร่าง Logs เข้ากับ Tickets
    // ------------------------------------------------------------------
    foreach ($logs as $log) {
        $tId = (int)$log['ticket_id'];
        
        // ถ้าตั๋วใบนี้มี log นีั และ log ใน array ยังไม่เกิน 3 อัน
        if (isset($reports[$tId]) && count($reports[$tId]['ticket_status_logs']) < 3) {
            $reports[$tId]['ticket_status_logs'][] = [
                'id'                 => (int)$log['status_log_id'],
                'status_changed_at'  => $log['status_changed_at'],
                'from_status_name'   => $log['from_status_name'],
                'to_status_name'     => $log['to_status_name'],
                'from_status_style'  => $log['status_from_style'],
                'to_status_style'    => $log['status_to_style'], 
            ];
        }
    }

    return array_values($reports);
}

function getReportDetails(int $id): ?array {
    global $pdo;

    $sql = "
        SELECT
            r.id,
            r.code,
            r.created_at,
            r.department,
            r.reporter_name,
            rt.name_th      AS request_type_name,
            ct.name_th      AS category_name,
            st.name_th      AS symptom_name,

            sl.id           AS status_log_id,
            sl.symptom      AS status_symptom,
            sl.cause        AS status_cause,
            sl.solver_by    AS status_solver_by,
            sl.sla          AS status_sla,
            sl.changed_at   AS status_changed_at,
            s_from.name_th  AS from_status_name,
            s_to.name_th    AS to_status_name,
            s_from.style    AS status_from_style,
            s_to.style      AS status_to_style
        FROM tickets AS r
            LEFT JOIN request_types      AS rt     ON r.request_type_id = rt.id
            LEFT JOIN issue_categories   AS ct     ON r.category_id     = ct.id
            LEFT JOIN issue_symptoms     AS st     ON r.symptom_id      = st.id
            LEFT JOIN ticket_status_logs AS sl     ON sl.ticket_id      = r.id
            LEFT JOIN ticket_statuses    AS s_from ON s_from.id         = sl.from_status
            LEFT JOIN ticket_statuses    AS s_to   ON s_to.id           = sl.to_status
        WHERE r.id = :id
        ORDER BY sl.changed_at DESC, sl.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ถ้าไม่พบข้อมูล ให้คืนค่า null กลับไป
    if (!$rows) {
        return null;
    }

    $first = $rows[0];

    // โครงสร้างข้อมูลหลักของ Ticket
    $work = [
        'id'                 => (int)$first['id'],
        'code'               => $first['code'],
        'created_at'         => $first['created_at'],
        'department'         => $first['department'],
        'reporter_name'      => $first['reporter_name'],
        'request_type_name'  => $first['request_type_name'],
        'category_name'      => $first['category_name'],
        'symptom_name'       => $first['symptom_name'],
        'ticket_status_logs' => [],
    ];

    // วนลูปเพื่อนำ Status Logs ทั้งหมดใส่เข้าไปใน Array (เรียงจากใหม่ไปเก่าตาม SQL)
    foreach ($rows as $row) {
        if (!is_null($row['status_log_id'])) {
            $work['ticket_status_logs'][] = [
                'id'                 => (int)$row['status_log_id'],
                'status_changed_at'  => $row['status_changed_at'],
                'from_status_name'   => $row['from_status_name'],
                'to_status_name'     => $row['to_status_name'],
                'status_from_style'  => $row['status_from_style'],
                'status_to_style'    => $row['status_to_style'],
                'symptom'            => $row['status_symptom'],
                'cause'              => $row['status_cause'],
                'solver_by'          => $row['status_solver_by'],
                'sla'                => $row['status_sla'],
            ];
        }
    }

    return $work;
}