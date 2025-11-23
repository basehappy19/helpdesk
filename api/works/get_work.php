<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid or missing ticket ID'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
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
            sl.symptom   AS status_symptom,
            sl.cause   AS status_cause,
            sl.solver_by   AS status_solver_by,
            sl.sla   AS status_sla,
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
    $stmt->execute(['id' => (int)$_GET['id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        http_response_code(404);
        echo json_encode(['message' => 'Work not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $first = $rows[0];

    $work = [
        'id'                => (int)$first['id'],
        'code'              => $first['code'],
        'created_at'        => $first['created_at'],
        'department'        => $first['department'],
        'reporter_name'     => $first['reporter_name'],
        'request_type_name' => $first['request_type_name'],
        'category_name'     => $first['category_name'],
        'symptom_name'      => $first['symptom_name'],
        'ticket_status_logs' => [],
    ];

    // ดึงทุก status log (เรียงจากใหม่ไปเก่า ตาม ORDER BY ด้านบน)
    foreach ($rows as $row) {
        if (!is_null($row['status_log_id'])) {
            $work['ticket_status_logs'][] = [
                'id'                 => (int)$row['status_log_id'],
                'status_changed_at'  => $row['status_changed_at'],
                'from_status_name'   => $row['from_status_name'],
                'to_status_name'     => $row['to_status_name'],
                'status_from_style'  => $row['status_from_style'],
                'status_to_style'    => $row['status_to_style'],
                'symptom'    => $row['status_symptom'],
                'cause'    => $row['status_cause'],
                'solver_by'    => $row['status_solver_by'],
                'sla'    => $row['status_sla'],
            ];
        }
    }

    echo json_encode(['ok' => true, 'work' => $work], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $th) {
    // แล้วแต่จะ log / handle
    http_response_code(500);
    echo json_encode(['message' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
