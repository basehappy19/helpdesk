<?php

class TicketStatusModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ดึงสถานะทั้งหมด (ใช้แทน getStatuses เดิม)
    public function getAllStatuses() {
        try {
            $sql = 'SELECT * FROM ticket_statuses ORDER BY sort_order DESC';
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // ดึงสถิติจำนวนงานในแต่ละสถานะ (ใช้แทน getStatusStatistics เดิม)
    public function getStatusStatistics() {
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

            $stmt = $this->pdo->query($sqlStatuses);
            $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // นับจำนวนตั๋วทั้งหมดรวมกัน
            $sqlTotal = "SELECT COUNT(*) AS total_reports_all FROM tickets";
            $stmtTotal = $this->pdo->query($sqlTotal);
            $total = $stmtTotal->fetch(PDO::FETCH_ASSOC);

            return [
                "statuses" => $statuses,
                "total_reports_all" => (int)$total["total_reports_all"]
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}