<?php

class DailyWorkLogModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ดึงข้อมูลสำหรับแสดงผลแบบตาราง (แยกสิทธิ์ Admin ให้เห็นทุกคน)
    public function getTableLogs($date, $userId, $isSystem) {
        if ($isSystem) {
            $sql = "SELECT d.*, u.display_th, c.name_th AS category_name 
                    FROM daily_work_logs d 
                    LEFT JOIN users u ON d.user_id = u.id 
                    LEFT JOIN work_log_categories c ON d.category_id = c.id
                    WHERE d.work_date = ? ORDER BY d.user_id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$date]);
        } else {
            $sql = "SELECT * FROM daily_work_logs WHERE user_id = ? AND work_date = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $date]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงข้อมูลสำหรับปฏิทิน
    public function getCalendarLogs($userId, $isSystem) {
        $sql = "SELECT d.*, u.display_th, c.name_th AS category_name
                FROM daily_work_logs d 
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN work_log_categories c ON d.category_id = c.id";
        
        if (!$isSystem) {
            $sql .= " WHERE d.user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->pdo->query($sql);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ระบบเซฟข้อมูลตารางแบบลูป (Batch Save)
    public function saveTableLogs($userId, $workDate, $logsUpdate, $logsNew, $allowedCatIds) {
        try {
            $this->pdo->beginTransaction();

            $stmtUpdate = $this->pdo->prepare("UPDATE daily_work_logs SET activity_detail = :detail, category_id = :catid, updated_at = NOW() WHERE id = :id AND user_id = :uid");
            $stmtDelete = $this->pdo->prepare("DELETE FROM daily_work_logs WHERE id = :id AND user_id = :uid");

            // อัปเดตของเดิม
            foreach ($logsUpdate as $id => $data) {
                $activity = trim($data['activity'] ?? '');
                $catId = ($data['category_id'] !== '' && isset($allowedCatIds[(string)$data['category_id']])) ? (int)$data['category_id'] : null;

                if ($activity === '') {
                    $stmtDelete->execute([':id' => $id, ':uid' => $userId]);
                } else {
                    $stmtUpdate->execute([':detail' => $activity, ':catid' => $catId, ':id' => $id, ':uid' => $userId]);
                }
            }

            // เพิ่มของใหม่
            $stmtInsert = $this->pdo->prepare("INSERT INTO daily_work_logs (user_id, work_date, start_time, end_time, activity_detail, category_id) VALUES (:uid, :wdate, :stime, :etime, :detail, :catid)");
            foreach ($logsNew as $timeKey => $data) {
                $activity = trim($data['activity'] ?? '');
                if ($activity === '') continue;

                $catId = ($data['category_id'] !== '' && isset($allowedCatIds[(string)$data['category_id']])) ? (int)$data['category_id'] : null;

                $hour = 0; $min = 0;
                if (strpos($timeKey, '_30') !== false) {
                    $hour = (int)explode('_', $timeKey)[0];
                    $min = 30;
                } else {
                    $hour = (int)$timeKey;
                }

                $startTimeStr = sprintf("%02d:%02d:00", $hour, $min);
                $endTimeStr = date('H:i:s', strtotime("$startTimeStr +1 hour"));

                $stmtInsert->execute([':uid' => $userId, ':wdate' => $workDate, ':stime' => $startTimeStr, ':etime' => $endTimeStr, ':detail' => $activity, ':catid' => $catId]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // ฟังก์ชันจัดการรายการปฏิทินเดี่ยวๆ
    public function deleteCalendarLog($id, $userId, $isSystem) {
        $sql = $isSystem ? "DELETE FROM daily_work_logs WHERE id = ?" : "DELETE FROM daily_work_logs WHERE id = ? AND user_id = ?";
        $params = $isSystem ? [$id] : [$id, $userId];
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function updateCalendarLog($id, $userId, $isSystem, $date, $start, $end, $detail, $cat) {
        $sql = $isSystem ? 
            "UPDATE daily_work_logs SET work_date=?, start_time=?, end_time=?, activity_detail=?, category_id=? WHERE id=?" :
            "UPDATE daily_work_logs SET work_date=?, start_time=?, end_time=?, activity_detail=?, category_id=? WHERE id=? AND user_id=?";
        
        $params = [$date, $start, $end, $detail, $cat, $id];
        if (!$isSystem) $params[] = $userId;
        
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function addCalendarLog($userId, $date, $start, $end, $detail, $cat) {
        $sql = "INSERT INTO daily_work_logs (user_id, work_date, start_time, end_time, activity_detail, category_id) VALUES (?, ?, ?, ?, ?, ?)";
        return $this->pdo->prepare($sql)->execute([$userId, $date, $start, $end, $detail, $cat]);
    }
}