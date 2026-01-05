<?php

function getWorkLogCategories(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM work_log_categories ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDailyWorkLogsForCalendar(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT id, work_date, start_time, end_time, activity_detail, category_id 
        FROM daily_work_logs 
        WHERE user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDailyWorkLogs(PDO $pdo, string $work_date): array
{
    $stmt = $pdo->prepare("SELECT * FROM daily_work_logs WHERE work_date = :wdate");
    $stmt->execute([':wdate' => $work_date]);
    $logs = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $log) {
        $logs[(int)$log['start_hour']] = $log;
    }
    return $logs;
}

function saveDailyWorkLogs(PDO $pdo, array $logs, array $allowedCatIds, int $userId, string $work_date): string
{
    $sqlUpsert = "
        INSERT INTO daily_work_logs (user_id, work_date, start_hour, activity_detail, category_id)
        VALUES (:user_id, :work_date, :start_hour, :activity_detail, :category_id)
        ON DUPLICATE KEY UPDATE
            activity_detail = VALUES(activity_detail),
            category_id = VALUES(category_id),
            updated_at = CURRENT_TIMESTAMP
    ";

    $stmtUpsert = $pdo->prepare($sqlUpsert);

    $stmtDelete = $pdo->prepare("
        DELETE FROM daily_work_logs
        WHERE user_id = :user_id AND work_date = :work_date AND start_hour = :start_hour
    ");

    try {
        $pdo->beginTransaction();
        $saved = 0;
        $deleted = 0;

        for ($h = 8; $h <= 16; $h++) {
            $activity = trim($logs[$h]['activity'] ?? '');
            $category_id = $logs[$h]['category_id'] ?? '';

            if ($activity === '') {
                $stmtDelete->execute([
                    ':user_id' => $userId,
                    ':work_date' => $work_date,
                    ':start_hour' => $h
                ]);
                $deleted += $stmtDelete->rowCount();
                continue;
            }

            $category_id_db = null;
            if ($category_id !== '' && isset($allowedCatIds[(string)$category_id])) {
                $category_id_db = (int)$category_id;
            }

            $stmtUpsert->execute([
                ':user_id' => $userId,
                ':work_date' => $work_date,
                ':start_hour' => $h,
                ':activity_detail' => $activity,
                ':category_id' => $category_id_db
            ]);
            $saved++;
        }

        $pdo->commit();
        return "✅ บันทึกสำเร็จ (เพิ่ม/อัปเดต {$saved} รายการ, ลบ {$deleted} รายการ)";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return "❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage());
    }
}
