<?php

declare(strict_types=1);

class TicketStatusModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllStatuses(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM ticket_statuses ORDER BY sort_order ASC, id ASC');
        return $stmt->fetchAll();
    }

    public function getStatusesForDropdown(): array
    {
        $stmt = $this->pdo->query('SELECT id, name_th FROM ticket_statuses ORDER BY sort_order ASC, id ASC');
        return $stmt->fetchAll();
    }

    public function getStatusStatistics(): array
    {
        $sql = "
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

        $statuses         = $this->pdo->query($sql)->fetchAll();
        $totalReportsAll  = (int)$this->pdo->query('SELECT COUNT(*) FROM tickets')->fetchColumn();

        return [
            'statuses'          => $statuses,
            'total_reports_all' => $totalReportsAll,
        ];
    }
}