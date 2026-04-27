<?php

require_once __DIR__ . '/../models/DailyWorkLogModel.php';
require_once __DIR__ . '/../models/WorkCategoryModel.php';

class DailyWorkLogController {
    private $pdo;
    private $user;
    private $logModel;
    private $catModel;
    private $userPalette = ['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#f97316', '#6366f1', '#84cc16', '#d946ef', '#64748b'];

    public function __construct($pdo, $user) {
        $this->pdo = $pdo;
        $this->user = $user;
        $this->logModel = new DailyWorkLogModel($pdo);
        $this->catModel = new WorkCategoryModel($pdo);
    }

    public function handlePostRequests() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;

        $userId = $this->user['id'] ?? 0;
        $userRole = $this->user['role'] ?? 'MEMBER';
        $isSystem = ($userRole === 'SYSTEM' || $userRole === 'ADMIN');

        // 1. AJAX: เพิ่มหมวดหมู่ใหม่
        if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'add_category') {
            header('Content-Type: application/json');
            if (!$isSystem) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เพิ่มหมวดหมู่']); exit;
            }
            $name = trim($_POST['category_name'] ?? '');
            if ($name === '') {
                echo json_encode(['status' => 'error', 'message' => 'ชื่อหมวดหมู่ห้ามว่าง']); exit;
            }
            try {
                $this->catModel->create($name);
                $newId = $this->pdo->lastInsertId(); // ดึง ID ล่าสุด
                echo json_encode(['status' => 'success', 'id' => $newId, 'name_th' => htmlspecialchars($name)]); exit;
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด']); exit;
            }
        }

        // 2. เซฟข้อมูลตาราง
        if (isset($_POST['save_log_table'])) {
            $workDate = $_POST['work_date'];
            $logsUpdate = $_POST['logs_update'] ?? [];
            $logsNew = $_POST['logs_new'] ?? [];
            
            $cats = $this->catModel->getAll();
            $allowedCatIds = array_flip(array_map(fn($c) => (string)$c['id'], $cats));

            if ($this->logModel->saveTableLogs($userId, $workDate, $logsUpdate, $logsNew, $allowedCatIds)) {
                return ['type' => 'success', 'msg' => '✅ บันทึกข้อมูลสำเร็จ'];
            }
            return ['type' => 'error', 'msg' => '❌ เกิดข้อผิดพลาดในการบันทึก'];
        }

        // 3. จัดการปฏิทิน (Edit/Delete)
        if (isset($_POST['calendar_action'])) {
            $action = $_POST['calendar_action'];
            $logId = $_POST['log_id'];
            if ($action === 'delete') {
                $this->logModel->deleteCalendarLog($logId, $userId, $isSystem);
                header("Location: ?page=daily-works&view=calendar&msg=deleted"); exit;
            } elseif ($action === 'edit') {
                $this->logModel->updateCalendarLog($logId, $userId, $isSystem, $_POST['work_date'], $_POST['start_time'], $_POST['end_time'], trim($_POST['activity_detail']), $_POST['category_id'] ?: null);
                header("Location: ?page=daily-works&view=calendar&msg=updated"); exit;
            }
        }

        // 4. จัดการปฏิทิน (Add)
        if (isset($_POST['save_log_calendar'])) {
            $this->logModel->addCalendarLog($userId, $_POST['work_date'], $_POST['start_time'], $_POST['end_time'], trim($_POST['activity_detail']), $_POST['category_id'] ?: null);
            header("Location: ?page=daily-works&view=calendar&msg=saved"); exit;
        }

        return null;
    }

    public function getPageData($getParams) {
        $userId = $this->user['id'] ?? 0;
        $userRole = $this->user['role'] ?? 'MEMBER';
        $isLoggedIn = $userId > 0;
        $isSystem = ($userRole === 'SYSTEM' || $userRole === 'ADMIN');
        
        // คำนวณวันที่
        $y = isset($getParams['year']) ? (int)$getParams['year'] : (int)date('Y');
        $m = isset($getParams['month']) ? (int)$getParams['month'] : (int)date('m');
        $d = isset($getParams['day']) ? (int)$getParams['day'] : (int)date('d');
        if (!checkdate($m, $d, $y)) {
            $y = (int)date('Y'); $m = (int)date('m'); $d = (int)date('d');
        }
        $selectedDate = sprintf('%04d-%02d-%02d', $y, $m, $d);

        $categories = $this->catModel->getAll();

        // ดึงข้อมูล
        $rawTableLogs = $isLoggedIn ? $this->logModel->getTableLogs($selectedDate, $userId, $isSystem) : [];
        $existingLogs = [];
        foreach ($rawTableLogs as $row) {
            if (!empty($row['start_time'])) {
                $hour = (int)explode(':', $row['start_time'])[0];
                $min = (int)explode(':', $row['start_time'])[1];
            } else {
                $hour = (int)($row['start_hour'] ?? 0);
                $min = 0;
            }
            if ($hour <= 0) continue;
            $keyHour = ($min === 30) ? $hour . '_30' : (string)$hour;
            $existingLogs[$keyHour][] = $row;
        }

        $rawCalLogs = $isLoggedIn ? $this->logModel->getCalendarLogs($userId, $isSystem) : [];
        $calendarEvents = [];
        foreach ($rawCalLogs as $log) {
            $startT = $log['start_time'] ?: sprintf("%02d:00:00", $log['start_hour'] ?? 0);
            $endT = $log['end_time'] ?: sprintf("%02d:00:00", ($log['start_hour'] ?? 0) + 1);
            
            if ($startT) {
                $creator = $log['display_th'] ?: 'User #' . $log['user_id'];
                $color = $this->userPalette[(int)$log['user_id'] % count($this->userPalette)];
                $calendarEvents[] = [
                    'id' => $log['id'],
                    'title' => $log['activity_detail'] . " [" . $creator . "]",
                    'start' => $log['work_date'] . 'T' . $startT,
                    'end' => $log['work_date'] . 'T' . $endT,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'user_id' => (int)$log['user_id'],
                        'creator' => $creator,
                        'detail' => $log['activity_detail'],
                        'category_id' => $log['category_id'],
                        'category_name' => $log['category_name'] ?? 'ไม่ระบุ',
                        'date_raw' => $log['work_date'],
                        'start_raw' => substr($startT, 0, 5),
                        'end_raw' => substr($endT, 0, 5)
                    ]
                ];
            }
        }

        return [
            'y' => $y, 'm' => $m, 'd' => $d,
            'selectedDate' => $selectedDate,
            'isLoggedIn' => $isLoggedIn,
            'isSystem' => $isSystem,
            'canManageOwn' => $isSystem || $userRole === 'ADMIN', // ยึดตาม Logic เดิมของคุณเบส
            'canEdit' => ($isSystem || $userRole === 'ADMIN') && ($selectedDate === date('Y-m-d')),
            'categories' => $categories,
            'existingLogs' => $existingLogs,
            'calendarEvents' => $calendarEvents,
            'defaultView' => $getParams['view'] ?? ($isLoggedIn ? 'table' : 'calendar')
        ];
    }
}