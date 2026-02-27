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