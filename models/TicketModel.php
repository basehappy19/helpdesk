<?php

declare(strict_types=1);

class TicketModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // -----------------------------------------------------------
    // Helper: สร้าง display name รวม remark
    // -----------------------------------------------------------
    private function buildDisplayName(?string $base, ?string $remark): string
    {
        $base   = $base   ?? '';
        $remark = $remark ?? '';

        if ($remark === '') {
            return $base !== '' ? $base : '-';
        }
        return $base !== '' ? "{$base} ({$remark})" : $remark;
    }

    // -----------------------------------------------------------
    // Helper: สร้าง WHERE clause + params จาก filters
    // -----------------------------------------------------------
    private function buildWhereClause(array $filters): array
    {
        $conditions = ['1=1'];
        $params     = [];

        if (!empty($filters['search'])) {
            $conditions[]           = '(r.code LIKE :search_code OR r.reporter_name LIKE :search_name)';
            $params[':search_code'] = '%' . $filters['search'] . '%';
            $params[':search_name'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['rt'])) {
            $conditions[]  = 'r.request_type_id = :rt';
            $params[':rt'] = $filters['rt'];
        }
        if (!empty($filters['cat'])) {
            $conditions[]   = 'r.category_id = :cat';
            $params[':cat'] = $filters['cat'];
        }
        if (!empty($filters['sym'])) {
            $conditions[]   = 'r.symptom_id = :sym';
            $params[':sym'] = $filters['sym'];
        }
        if (!empty($filters['status'])) {
            $conditions[]       = '(
                SELECT ts.code
                FROM ticket_status_logs tsl
                LEFT JOIN ticket_statuses ts ON tsl.to_status = ts.id
                WHERE tsl.ticket_id = r.id
                ORDER BY tsl.changed_at DESC, tsl.id DESC
                LIMIT 1
            ) = :status';
            $params[':status'] = $filters['status'];
        }

        return [implode(' AND ', $conditions), $params];
    }

    // -----------------------------------------------------------
    // Helper: โหลด status logs หลาย ticket ด้วย query เดียว
    // -----------------------------------------------------------
    private function loadStatusLogs(array $ticketIds, int $maxPerTicket = 3): array
    {
        if (empty($ticketIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
        $sql = "
            SELECT
                sl.ticket_id,
                sl.id          AS status_log_id,
                sl.changed_at  AS status_changed_at,
                s_from.name_th AS from_status_name,
                s_to.name_th   AS to_status_name,
                s_from.style   AS status_from_style,
                s_to.style     AS status_to_style
            FROM ticket_status_logs AS sl
                LEFT JOIN ticket_statuses AS s_from ON s_from.id = sl.from_status
                LEFT JOIN ticket_statuses AS s_to   ON s_to.id   = sl.to_status
            WHERE sl.ticket_id IN ({$placeholders})
            ORDER BY sl.changed_at DESC, sl.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ticketIds);
        $logs = $stmt->fetchAll();

        $grouped = [];
        foreach ($logs as $log) {
            $tId = (int)$log['ticket_id'];
            if (!isset($grouped[$tId])) {
                $grouped[$tId] = [];
            }
            if (count($grouped[$tId]) < $maxPerTicket) {
                $grouped[$tId][] = [
                    'id'                 => (int)$log['status_log_id'],
                    'status_changed_at'  => $log['status_changed_at'],
                    'from_status_name'   => $log['from_status_name'],
                    'to_status_name'     => $log['to_status_name'],
                    'status_from_style'  => $log['status_from_style'],
                    'to_status_style'    => $log['status_to_style'],
                ];
            }
        }

        return $grouped;
    }

    // -----------------------------------------------------------
    // Helper: โหลด thumbnail ของ ticket list
    // -----------------------------------------------------------
    private function loadThumbnails(array $ticketIds): array
    {
        if (empty($ticketIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
        $stmt         = $this->pdo->prepare(
            "SELECT ticket_id, file_path FROM ticket_images WHERE ticket_id IN ({$placeholders}) ORDER BY id ASC"
        );
        $stmt->execute($ticketIds);

        $thumbs = [];
        foreach ($stmt->fetchAll() as $img) {
            $tid = (int)$img['ticket_id'];
            if (!isset($thumbs[$tid])) {
                $thumbs[$tid] = $img['file_path'];
            }
        }

        return $thumbs;
    }

    // -----------------------------------------------------------
    // Public: ดึง ticket ล่าสุด (สำหรับหน้า Home)
    // -----------------------------------------------------------
    public function getRecentTickets(int $limit = 5): array
    {
        $limit = max(1, $limit);

        $sql = "
            SELECT
                r.id, r.code, r.created_at, r.department, r.reporter_name,
                r.category_other_remark, r.symptom_other_remark,
                rt.name_th AS request_type_name,
                ct.name_th AS category_name,
                st.name_th AS symptom_name
            FROM tickets AS r
                LEFT JOIN request_types    AS rt ON rt.id = r.request_type_id
                LEFT JOIN issue_categories AS ct ON ct.id = r.category_id
                LEFT JOIN issue_symptoms   AS st ON st.id = r.symptom_id
            ORDER BY r.created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return [];
        }

        $ticketIds = array_column($rows, 'id');
        $logsMap   = $this->loadStatusLogs(array_map('intval', $ticketIds));
        $thumbMap  = $this->loadThumbnails(array_map('intval', $ticketIds));

        $results = [];
        foreach ($rows as $row) {
            $id        = (int)$row['id'];
            $results[] = [
                'id'                 => $id,
                'code'               => $row['code'],
                'created_at'         => $row['created_at'],
                'department'         => $row['department'],
                'reporter_name'      => $row['reporter_name'],
                'request_type_name'  => $row['request_type_name'],
                'display_category'   => $this->buildDisplayName($row['category_name'], $row['category_other_remark']),
                'display_symptom'    => $this->buildDisplayName($row['symptom_name'],  $row['symptom_other_remark']),
                'ticket_status_logs' => $logsMap[$id] ?? [],
                'thumbnail'          => $thumbMap[$id] ?? null,
            ];
        }

        return $results;
    }

    // -----------------------------------------------------------
    // Public: ดึง ticket list พร้อม filter + pagination
    // -----------------------------------------------------------
    public function getAllTickets(int $limit = 50, int $offset = 0, array $filters = []): array
    {
        [$whereSql, $params] = $this->buildWhereClause($filters);

        $sql = "
            SELECT
                r.id, r.code, r.created_at, r.department, r.reporter_name,
                r.category_other_remark, r.symptom_other_remark,
                rt.name_th AS request_type_name,
                ct.name_th AS category_name,
                st.name_th AS symptom_name
            FROM tickets AS r
                LEFT JOIN request_types    AS rt ON rt.id = r.request_type_id
                LEFT JOIN issue_categories AS ct ON ct.id = r.category_id
                LEFT JOIN issue_symptoms   AS st ON st.id = r.symptom_id
            WHERE {$whereSql}
            ORDER BY r.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return [];
        }

        $ticketIds = array_map(fn($r) => (int)$r['id'], $rows);
        $logsMap   = $this->loadStatusLogs($ticketIds);

        $results = [];
        foreach ($rows as $row) {
            $id          = (int)$row['id'];
            $row['id']   = $id;
            $row['display_category']   = $this->buildDisplayName($row['category_name'], $row['category_other_remark']);
            $row['display_symptom']    = $this->buildDisplayName($row['symptom_name'],  $row['symptom_other_remark']);
            $row['ticket_status_logs'] = $logsMap[$id] ?? [];
            $results[]   = $row;
        }

        return $results;
    }

    // -----------------------------------------------------------
    // Public: นับ ticket ทั้งหมด (ใช้คู่กับ getAllTickets)
    // -----------------------------------------------------------
    public function getTotalTicketsCount(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildWhereClause($filters);

        $sql  = "SELECT COUNT(r.id) FROM tickets AS r WHERE {$whereSql}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    // -----------------------------------------------------------
    // Public: ดึงรายละเอียด ticket ตาม code
    // -----------------------------------------------------------
    public function getTicketDetails(string $code): ?array
    {
        $sql = "
            SELECT
                r.id, r.code, r.created_at, r.accepted_at, r.resolved_at, r.sla_due_at,
                r.department, r.reporter_name,
                r.category_other_remark, r.symptom_other_remark,
                rt.name_th AS request_type_name,
                ct.name_th AS category_name,
                st.name_th AS symptom_name,
                sl.id                      AS status_log_id,
                sl.symptom                 AS status_symptom,
                sl.cause                   AS status_cause,
                sl.solver_by               AS status_solver_by,
                u.display_th               AS solver_display,
                sl.solver_by_other_remark  AS status_solver_by_other_remark,
                sl.changed_at              AS status_changed_at,
                s_from.name_th             AS from_status_name,
                s_to.name_th               AS to_status_name,
                s_from.style               AS status_from_style,
                s_to.style                 AS status_to_style
            FROM tickets AS r
                LEFT JOIN request_types    AS rt     ON rt.id     = r.request_type_id
                LEFT JOIN issue_categories AS ct     ON ct.id     = r.category_id
                LEFT JOIN issue_symptoms   AS st     ON st.id     = r.symptom_id
                LEFT JOIN ticket_status_logs AS sl   ON sl.ticket_id = r.id
                LEFT JOIN ticket_statuses  AS s_from ON s_from.id = sl.from_status
                LEFT JOIN ticket_statuses  AS s_to   ON s_to.id   = sl.to_status
                LEFT JOIN users            AS u      ON u.id      = sl.solver_by
            WHERE r.code = :code
            ORDER BY sl.changed_at DESC, sl.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['code' => $code]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return null;
        }

        $first  = $rows[0];
        $ticket = [
            'id'                 => (int)$first['id'],
            'code'               => $first['code'],
            'created_at'         => $first['created_at'],
            'accepted_at'        => $first['accepted_at'],
            'resolved_at'        => $first['resolved_at'],
            'sla_due_at'         => $first['sla_due_at'],
            'department'         => $first['department'],
            'reporter_name'      => $first['reporter_name'],
            'request_type_name'  => $first['request_type_name'],
            'display_category'   => $this->buildDisplayName($first['category_name'], $first['category_other_remark']),
            'display_symptom'    => $this->buildDisplayName($first['symptom_name'],  $first['symptom_other_remark']),
            'ticket_status_logs' => [],
            'images'             => [],
        ];

        foreach ($rows as $row) {
            if ($row['status_log_id'] === null) {
                continue;
            }
            $ticket['ticket_status_logs'][] = [
                'id'                     => (int)$row['status_log_id'],
                'status_changed_at'      => $row['status_changed_at'],
                'from_status_name'       => $row['from_status_name'],
                'to_status_name'         => $row['to_status_name'],
                'status_from_style'      => $row['status_from_style'],
                'status_to_style'        => $row['status_to_style'],
                'symptom'                => $row['status_symptom'],
                'cause'                  => $row['status_cause'],
                'solver_by'              => $row['status_solver_by'],
                'solver_display'         => $row['solver_display'],
                'solver_by_other_remark' => $row['status_solver_by_other_remark'],
            ];
        }

        // โหลดรูป
        $stmtImg = $this->pdo->prepare(
            'SELECT id, file_path FROM ticket_images WHERE ticket_id = :tid ORDER BY id ASC'
        );
        $stmtImg->execute(['tid' => $ticket['id']]);
        $ticket['images'] = $stmtImg->fetchAll();

        return $ticket;
    }
}