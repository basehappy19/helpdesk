<?php

declare(strict_types=1);

class DailyWorkLogModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * ดึง logs ตามวันที่ (ถ้า isAdmin จะเห็นของทุกคน)
     */
    public function getTableLogs(string $date, int $userId, bool $isAdmin): array
    {
        if ($isAdmin) {
            $stmt = $this->pdo->prepare("
                SELECT d.*, u.display_th, c.name_th AS category_name
                FROM daily_work_logs d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN work_log_categories c ON d.category_id = c.id
                WHERE d.work_date = :date
                ORDER BY d.user_id ASC, d.start_time ASC
            ");
            $stmt->execute(['date' => $date]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT d.*, c.name_th AS category_name
                FROM daily_work_logs d
                LEFT JOIN work_log_categories c ON d.category_id = c.id
                WHERE d.user_id = :uid AND d.work_date = :date
                ORDER BY d.start_time ASC
            ");
            $stmt->execute(['uid' => $userId, 'date' => $date]);
        }

        return $stmt->fetchAll();
    }

    /**
     * ดึง logs ทั้งหมดสำหรับปฏิทิน
     */
    public function getCalendarLogs(int $userId, bool $isAdmin): array
    {
        if ($isAdmin) {
            $stmt = $this->pdo->query("
                SELECT d.*, u.display_th, c.name_th AS category_name
                FROM daily_work_logs d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN work_log_categories c ON d.category_id = c.id
                ORDER BY d.work_date ASC, d.start_time ASC
            ");
        } else {
            $stmt = $this->pdo->prepare("
                SELECT d.*, c.name_th AS category_name
                FROM daily_work_logs d
                LEFT JOIN work_log_categories c ON d.category_id = c.id
                WHERE d.user_id = :uid
                ORDER BY d.work_date ASC, d.start_time ASC
            ");
            $stmt->execute(['uid' => $userId]);
        }

        return $stmt->fetchAll();
    }

    /**
     * บันทึกข้อมูลตารางแบบ Batch (update/insert/delete ใน transaction เดียว)
     *
     * @param array<int, array>   $logsUpdate [id => ['activity', 'category_id']]
     * @param array<string, array> $logsNew   [timeKey => ['activity', 'category_id']]
     * @param array<string, int>   $allowedCatIds  [cat_id => index]
     */
    public function saveTableLogs(
        int    $userId,
        string $workDate,
        array  $logsUpdate,
        array  $logsNew,
        array  $allowedCatIds
    ): bool {
        try {
            $this->pdo->beginTransaction();

            $stmtUpdate = $this->pdo->prepare("
                UPDATE daily_work_logs
                SET activity_detail = :detail, category_id = :catid, updated_at = NOW()
                WHERE id = :id AND user_id = :uid
            ");
            $stmtDelete = $this->pdo->prepare(
                'DELETE FROM daily_work_logs WHERE id = :id AND user_id = :uid'
            );

            foreach ($logsUpdate as $id => $data) {
                $activity = trim($data['activity'] ?? '');
                $catId    = $this->resolveCategory((string)($data['category_id'] ?? ''), $allowedCatIds);

                if ($activity === '') {
                    $stmtDelete->execute([':id' => (int)$id, ':uid' => $userId]);
                } else {
                    $stmtUpdate->execute([
                        ':detail' => $activity,
                        ':catid'  => $catId,
                        ':id'     => (int)$id,
                        ':uid'    => $userId,
                    ]);
                }
            }

            $stmtInsert = $this->pdo->prepare("
                INSERT INTO daily_work_logs (user_id, work_date, start_time, end_time, activity_detail, category_id)
                VALUES (:uid, :wdate, :stime, :etime, :detail, :catid)
            ");

            foreach ($logsNew as $timeKey => $data) {
                $activity = trim($data['activity'] ?? '');
                if ($activity === '') {
                    continue;
                }

                $catId  = $this->resolveCategory((string)($data['category_id'] ?? ''), $allowedCatIds);
                [$hour, $min] = $this->parseTimeKey((string)$timeKey);

                $startTime = sprintf('%02d:%02d:00', $hour, $min);
                $endTime   = date('H:i:s', strtotime("{$startTime} +1 hour"));

                $stmtInsert->execute([
                    ':uid'    => $userId,
                    ':wdate'  => $workDate,
                    ':stime'  => $startTime,
                    ':etime'  => $endTime,
                    ':detail' => $activity,
                    ':catid'  => $catId,
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('saveTableLogs error: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteCalendarLog(int $id, int $userId, bool $isAdmin): bool
    {
        if ($isAdmin) {
            $stmt = $this->pdo->prepare('DELETE FROM daily_work_logs WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        }
        $stmt = $this->pdo->prepare('DELETE FROM daily_work_logs WHERE id = :id AND user_id = :uid');
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    public function updateCalendarLog(
        int     $id,
        int     $userId,
        bool    $isAdmin,
        string  $date,
        string  $start,
        string  $end,
        string  $detail,
        ?int    $categoryId
    ): bool {
        if ($isAdmin) {
            $stmt = $this->pdo->prepare("
                UPDATE daily_work_logs
                SET work_date = :date, start_time = :start, end_time = :end,
                    activity_detail = :detail, category_id = :cat
                WHERE id = :id
            ");
            return $stmt->execute([
                ':date'   => $date,
                ':start'  => $start,
                ':end'    => $end,
                ':detail' => $detail,
                ':cat'    => $categoryId,
                ':id'     => $id,
            ]);
        }

        $stmt = $this->pdo->prepare("
            UPDATE daily_work_logs
            SET work_date = :date, start_time = :start, end_time = :end,
                activity_detail = :detail, category_id = :cat
            WHERE id = :id AND user_id = :uid
        ");
        return $stmt->execute([
            ':date'   => $date,
            ':start'  => $start,
            ':end'    => $end,
            ':detail' => $detail,
            ':cat'    => $categoryId,
            ':id'     => $id,
            ':uid'    => $userId,
        ]);
    }

    public function addCalendarLog(
        int    $userId,
        string $date,
        string $start,
        string $end,
        string $detail,
        ?int   $categoryId
    ): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO daily_work_logs (user_id, work_date, start_time, end_time, activity_detail, category_id)
            VALUES (:uid, :date, :start, :end, :detail, :cat)
        ");
        return $stmt->execute([
            ':uid'    => $userId,
            ':date'   => $date,
            ':start'  => $start,
            ':end'    => $end,
            ':detail' => $detail,
            ':cat'    => $categoryId,
        ]);
    }

    // -----------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------

    /**
     * คืน category_id ที่ validate แล้ว หรือ null ถ้า invalid
     */
    private function resolveCategory(string $raw, array $allowedCatIds): ?int
    {
        if ($raw === '' || !isset($allowedCatIds[$raw])) {
            return null;
        }
        return (int)$raw;
    }

    /**
     * แปลง timeKey (เช่น "9", "9_30") เป็น [hour, minute]
     *
     * @return array{0: int, 1: int}
     */
    private function parseTimeKey(string $key): array
    {
        if (str_ends_with($key, '_30')) {
            $hour = (int)explode('_', $key)[0];
            return [$hour, 30];
        }
        return [(int)$key, 0];
    }
}