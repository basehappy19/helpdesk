<?php

function getstatuses()
{
    global $pdo;
    try {
        $sql = 'SELECT * FROM ticket_statuses ORDER BY sort_order DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $statuses;
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

function getStatusStatistics()
{
    global $pdo;

    try {
        $sqlStatuses = "
            SELECT 
                s.id,
                s.code,
                s.name_th,
                s.sort_order,
                s.style,
                COALESCE(c.total_reports, 0) AS total_reports
            FROM ticket_statuses s
            LEFT JOIN (
                SELECT 
                    latest.to_status,
                    COUNT(*) AS total_reports
                FROM (
                    SELECT
                        l.ticket_id,
                        l.to_status,
                        ROW_NUMBER() OVER (
                            PARTITION BY l.ticket_id
                            ORDER BY l.changed_at DESC, l.id DESC
                        ) AS rn
                    FROM ticket_status_logs l
                ) AS latest
                WHERE latest.rn = 1
                GROUP BY latest.to_status
            ) AS c ON c.to_status = s.id
            ORDER BY s.sort_order ASC
        ";

        $stmt = $pdo->prepare($sqlStatuses);
        $stmt->execute();
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlTotal = "SELECT COUNT(*) AS total_reports_all FROM tickets";
        $stmt2 = $pdo->prepare($sqlTotal);
        $stmt2->execute();
        $total = $stmt2->fetch(PDO::FETCH_ASSOC);

        return [
            "statuses" => $statuses,
            "total_reports_all" => (int)$total["total_reports_all"]
        ];

    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}



function latest_status(array $ticket): ?array
{
    if (empty($ticket['ticket_status_logs']) || !is_array($ticket['ticket_status_logs'])) {
        return null;
    }

    $latest = $ticket['ticket_status_logs'][0] ?? null;
    if (!$latest) return null;

    return [
        'name'  => $latest['to_status_name'] ?? '-',
        'style' => $latest['to_status_style'] ?? '',
    ];
}
