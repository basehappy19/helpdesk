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
            r.category_other_remark, 
            r.symptom_other_remark,
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
            
            // จัดการ Format การแสดงผลหมวดหมู่
            $catName = $row['category_name'] ?? '';
            if (!empty($row['category_other_remark'])) {
                $catName = $catName ? $catName . ' (' . $row['category_other_remark'] . ')' : $row['category_other_remark'];
            }

            // จัดการ Format การแสดงผลอาการ
            $symName = $row['symptom_name'] ?? '';
            if (!empty($row['symptom_other_remark'])) {
                $symName = $symName ? $symName . ' (' . $row['symptom_other_remark'] . ')' : $row['symptom_other_remark'];
            }

            $byId[$id] = [
                'id'                 => $id,
                'code'               => $row['code'],
                'created_at'         => $row['created_at'],
                'department'         => $row['department'],
                'reporter_name'      => $row['reporter_name'],
                'request_type_name'  => $row['request_type_name'],
                'display_category'   => $catName ?: '-', // ฟิลด์ที่จัด Format แล้ว
                'display_symptom'    => $symName ?: '-', // ฟิลด์ที่จัด Format แล้ว
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

/**
 * ดึงข้อมูลตั๋ว (Tickets) พร้อมตัวกรอง
 */
function getAllReports(int $limit = 50, int $offset = 0, array $filters = []): array {
    global $pdo;
    
    // 1. สร้างเงื่อนไข WHERE แบบไดนามิก
    $whereConditions = ["1=1"];
    $params = [];

    if (!empty($filters['search'])) {
        $whereConditions[] = "(r.code LIKE :search_code OR r.reporter_name LIKE :search_name)";
        $params[':search_code'] = '%' . $filters['search'] . '%';
        $params[':search_name'] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['rt'])) {
        $whereConditions[] = "r.request_type_id = :rt";
        $params[':rt'] = $filters['rt'];
    }
    if (!empty($filters['cat'])) {
        $whereConditions[] = "r.category_id = :cat";
        $params[':cat'] = $filters['cat'];
    }
    if (!empty($filters['sym'])) {
        $whereConditions[] = "r.symptom_id = :sym";
        $params[':sym'] = $filters['sym'];
    }
    if (!empty($filters['status'])) {
        $whereConditions[] = "(
            SELECT ts.code 
            FROM ticket_status_logs tsl 
            LEFT JOIN ticket_statuses ts ON tsl.to_status = ts.id 
            WHERE tsl.ticket_id = r.id 
            ORDER BY tsl.changed_at DESC, tsl.id DESC 
            LIMIT 1
        ) = :status";
        $params[':status'] = $filters['status'];
    }

    $whereSql = implode(" AND ", $whereConditions);

    // 2. ดึงข้อมูล Tickets หลัก (เพิ่มฟิลด์ remark)
    $sqlTickets = "
        SELECT
            r.id,
            r.code,
            r.created_at,
            r.department,
            r.reporter_name,
            r.category_other_remark, 
            r.symptom_other_remark,
            rt.name_th      AS request_type_name,
            ct.name_th      AS category_name,
            st.name_th      AS symptom_name
        FROM tickets AS r
            LEFT JOIN request_types      AS rt ON r.request_type_id = rt.id
            LEFT JOIN issue_categories   AS ct ON r.category_id     = ct.id
            LEFT JOIN issue_symptoms     AS st ON r.symptom_id      = st.id
        WHERE {$whereSql}
        ORDER BY r.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sqlTickets);
    
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tickets)) {
        return [];
    }

    $reports = [];
    $ticketIds = [];
    foreach ($tickets as $ticket) {
        $id = (int)$ticket['id'];
        $ticketIds[] = $id;
        
        // จัดการ Format การแสดงผลหมวดหมู่
        $catName = $ticket['category_name'] ?? '';
        if (!empty($ticket['category_other_remark'])) {
            $catName = $catName ? $catName . ' (' . $ticket['category_other_remark'] . ')' : $ticket['category_other_remark'];
        }
        $ticket['display_category'] = $catName ?: '-';

        // จัดการ Format การแสดงผลอาการ
        $symName = $ticket['symptom_name'] ?? '';
        if (!empty($ticket['symptom_other_remark'])) {
            $symName = $symName ? $symName . ' (' . $ticket['symptom_other_remark'] . ')' : $ticket['symptom_other_remark'];
        }
        $ticket['display_symptom'] = $symName ?: '-';

        $ticket['id'] = $id;
        $ticket['ticket_status_logs'] = [];
        $reports[$id] = $ticket;
    }

    // 3. ดึงประวัติสถานะ (Logs) เฉพาะตั๋วที่ได้
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

    foreach ($logs as $log) {
        $tId = (int)$log['ticket_id'];
        if (isset($reports[$tId]) && count($reports[$tId]['ticket_status_logs']) < 3) {
            $reports[$tId]['ticket_status_logs'][] = [
                'id'                 => (int)$log['status_log_id'],
                'status_changed_at'  => $log['status_changed_at'],
                'from_status_name'   => $log['from_status_name'],
                'to_status_name'     => $log['to_status_name'],
                'status_from_style'  => $log['status_from_style'],
                'to_status_style'    => $log['status_to_style'], 
            ];
        }
    }

    return array_values($reports);
}

/**
 * นับจำนวนตั๋วทั้งหมด (โดยใช้ตัวกรองชุดเดียวกัน)
 */
function getTotalReportsCount(array $filters = []): int {
    global $pdo;

    $whereConditions = ["1=1"];
    $params = [];

    // ค้นหาข้อความ (ค้นจากรหัสตั๋ว หรือ ชื่อผู้แจ้ง)
    if (!empty($filters['search'])) {
        // เปลี่ยนชื่อ parameter ให้ไม่ซ้ำกัน
        $whereConditions[] = "(r.code LIKE :search_code OR r.reporter_name LIKE :search_name)";
        $params[':search_code'] = '%' . $filters['search'] . '%';
        $params[':search_name'] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['rt'])) {
        $whereConditions[] = "r.request_type_id = :rt";
        $params[':rt'] = $filters['rt'];
    }
    if (!empty($filters['cat'])) {
        $whereConditions[] = "r.category_id = :cat";
        $params[':cat'] = $filters['cat'];
    }
    if (!empty($filters['sym'])) {
        $whereConditions[] = "r.symptom_id = :sym";
        $params[':sym'] = $filters['sym'];
    }
    if (!empty($filters['status'])) {
        $whereConditions[] = "(
            SELECT ts.code 
            FROM ticket_status_logs tsl 
            LEFT JOIN ticket_statuses ts ON tsl.to_status = ts.id 
            WHERE tsl.ticket_id = r.id 
            ORDER BY tsl.changed_at DESC, tsl.id DESC 
            LIMIT 1
        ) = :status";
        $params[':status'] = $filters['status'];
    }

    $whereSql = implode(" AND ", $whereConditions);

    $sql = "SELECT COUNT(r.id) FROM tickets AS r WHERE {$whereSql}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return (int)$stmt->fetchColumn();
}

function getReportDetails(string $code): ?array {
    global $pdo;

    $sql = "
        SELECT
            r.id,
            r.code,
            r.created_at,
            r.accepted_at,    
            r.resolved_at,
            r.sla_due_at,   
            r.department,
            r.reporter_name,
            r.category_other_remark, 
            r.symptom_other_remark,
            rt.name_th      AS request_type_name,
            ct.name_th      AS category_name,
            st.name_th      AS symptom_name,

            sl.id           AS status_log_id,
            sl.symptom      AS status_symptom,
            sl.cause        AS status_cause,
            sl.solver_by    AS status_solver_by,
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
        WHERE r.code = :code
        ORDER BY sl.changed_at DESC, sl.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['code' => $code]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        return null;
    }

    $first = $rows[0];

    // จัดการ Format การแสดงผลหมวดหมู่
    $catName = $first['category_name'] ?? '';
    if (!empty($first['category_other_remark'])) {
        $catName = $catName ? $catName . ' (' . $first['category_other_remark'] . ')' : $first['category_other_remark'];
    }

    // จัดการ Format การแสดงผลอาการ
    $symName = $first['symptom_name'] ?? '';
    if (!empty($first['symptom_other_remark'])) {
        $symName = $symName ? $symName . ' (' . $first['symptom_other_remark'] . ')' : $first['symptom_other_remark'];
    }

    $work = [
        'id'                 => (int)$first['id'],
        'code'               => $first['code'],
        'created_at'         => $first['created_at'],
        'accepted_at'        => $first['accepted_at'],   
        'resolved_at'        => $first['resolved_at'],
        'sla_due_at'         => $first['sla_due_at'], 
        'department'         => $first['department'],
        'reporter_name'      => $first['reporter_name'],
        'request_type_name'  => $first['request_type_name'],
        'display_category'   => $catName ?: '-',    // เพิ่มฟิลด์ใหม่สำหรับแสดงผล
        'display_symptom'    => $symName ?: '-',    // เพิ่มฟิลด์ใหม่สำหรับแสดงผล
        'ticket_status_logs' => [],
    ];

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
            ];
        }
    }

    return $work;
}