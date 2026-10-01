<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/DailyWorkLogModel.php';
require_once __DIR__ . '/../models/WorkCategoryModel.php';

class DailyWorkLogController
{
    private DailyWorkLogModel $logModel;
    private WorkCategoryModel $catModel;
    private ?array            $user;

    private const USER_PALETTE = [
        '#ef4444', '#3b82f6', '#10b981', '#f59e0b',
        '#8b5cf6', '#ec4899', '#06b6d4', '#f97316',
        '#6366f1', '#84cc16', '#d946ef', '#64748b',
    ];

    public function __construct(PDO $pdo, ?array $user)
    {
        $this->logModel = new DailyWorkLogModel($pdo);
        $this->catModel = new WorkCategoryModel($pdo);
        $this->user     = $user;
    }

    private function getUserId(): int
    {
        return (int)($this->user['id'] ?? 0);
    }

    private function getUserRole(): string
    {
        return $this->user['role'] ?? 'MEMBER';
    }

    private function isAdmin(): bool
    {
        return in_array($this->getUserRole(), ['SYSTEM', 'ADMIN'], true);
    }

    public function handlePostRequests(): mixed
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        $userId  = $this->getUserId();
        $isAdmin = $this->isAdmin();

        // 1. AJAX: เพิ่มหมวดหมู่ใหม่
        if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'add_category') {
            header('Content-Type: application/json; charset=utf-8');

            if (!$isAdmin) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เพิ่มหมวดหมู่']);
                exit;
            }

            $name = trim($_POST['category_name'] ?? '');
            if ($name === '') {
                echo json_encode(['status' => 'error', 'message' => 'ชื่อหมวดหมู่ห้ามว่าง']);
                exit;
            }

            try {
                $newId = $this->catModel->create($name);
                echo json_encode(['status' => 'success', 'id' => $newId, 'name_th' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8')]);
            } catch (Exception $e) {
                error_log('add_category error: ' . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด']);
            }
            exit;
        }

        // 2. บันทึกข้อมูลตาราง
        if (isset($_POST['save_log_table'])) {
            $workDate   = $_POST['work_date'] ?? date('Y-m-d');
            $logsUpdate = $_POST['logs_update'] ?? [];
            $logsNew    = $_POST['logs_new']    ?? [];

            $cats           = $this->catModel->getAll();
            $allowedCatIds  = array_flip(array_column($cats, 'id'));

            $ok = $this->logModel->saveTableLogs($userId, $workDate, $logsUpdate, $logsNew, $allowedCatIds);
            return $ok
                ? ['type' => 'success', 'msg' => '✅ บันทึกข้อมูลสำเร็จ']
                : ['type' => 'error',   'msg' => '❌ เกิดข้อผิดพลาดในการบันทึก'];
        }

        // 3. ปฏิทิน: Edit / Delete
        if (isset($_POST['calendar_action'])) {
            $action = $_POST['calendar_action'];
            $logId  = (int)($_POST['log_id'] ?? 0);

            if ($action === 'delete') {
                $this->logModel->deleteCalendarLog($logId, $userId, $isAdmin);
                header('Location: ?page=daily-works&view=calendar&msg=deleted');
                exit;
            }

            if ($action === 'edit') {
                $this->logModel->updateCalendarLog(
                    $logId,
                    $userId,
                    $isAdmin,
                    $_POST['work_date']       ?? date('Y-m-d'),
                    $_POST['start_time']      ?? '00:00',
                    $_POST['end_time']        ?? '01:00',
                    trim($_POST['activity_detail'] ?? ''),
                    ($_POST['category_id'] ?? '') !== '' ? (int)$_POST['category_id'] : null
                );
                header('Location: ?page=daily-works&view=calendar&msg=updated');
                exit;
            }
        }

        // 4. ปฏิทิน: Add
        if (isset($_POST['save_log_calendar'])) {
            $this->logModel->addCalendarLog(
                $userId,
                $_POST['work_date']       ?? date('Y-m-d'),
                $_POST['start_time']      ?? '00:00',
                $_POST['end_time']        ?? '01:00',
                trim($_POST['activity_detail'] ?? ''),
                ($_POST['category_id'] ?? '') !== '' ? (int)$_POST['category_id'] : null
            );
            header('Location: ?page=daily-works&view=calendar&msg=saved');
            exit;
        }

        return null;
    }

    public function getPageData(array $getParams): array
    {
        $userId    = $this->getUserId();
        $userRole  = $this->getUserRole();
        $isLoggedIn = $userId > 0;
        $isAdmin   = $this->isAdmin();

        // คำนวณวันที่
        $y = isset($getParams['year'])  ? (int)$getParams['year']  : (int)date('Y');
        $m = isset($getParams['month']) ? (int)$getParams['month'] : (int)date('m');
        $d = isset($getParams['day'])   ? (int)$getParams['day']   : (int)date('d');

        if (!checkdate($m, $d, $y)) {
            [$y, $m, $d] = [(int)date('Y'), (int)date('m'), (int)date('d')];
        }

        $selectedDate = sprintf('%04d-%02d-%02d', $y, $m, $d);
        $categories   = $this->catModel->getAll();

        // ตารางงาน
        $rawTableLogs = $isLoggedIn
            ? $this->logModel->getTableLogs($selectedDate, $userId, $isAdmin)
            : [];

        $existingLogs = [];
        foreach ($rawTableLogs as $row) {
            if (empty($row['start_time'])) {
                continue;
            }
            [$hour, $min] = array_map('intval', explode(':', $row['start_time']));
            if ($hour <= 0) {
                continue;
            }
            $key = ($min === 30) ? "{$hour}_30" : (string)$hour;
            $existingLogs[$key][] = $row;
        }

        // ปฏิทิน
        $rawCalLogs     = $isLoggedIn ? $this->logModel->getCalendarLogs($userId, $isAdmin) : [];
        $calendarEvents = [];

        foreach ($rawCalLogs as $log) {
            $startT = $log['start_time'] ?? null;
            $endT   = $log['end_time']   ?? null;

            if (!$startT) {
                continue;
            }

            $uid     = (int)$log['user_id'];
            $creator = $log['display_th'] ?: "User #{$uid}";
            $color   = self::USER_PALETTE[$uid % count(self::USER_PALETTE)];

            $calendarEvents[] = [
                'id'              => (int)$log['id'],
                'title'           => $log['activity_detail'] . " [{$creator}]",
                'start'           => $log['work_date'] . 'T' . $startT,
                'end'             => $log['work_date'] . 'T' . ($endT ?? $startT),
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'textColor'       => '#ffffff',
                'extendedProps'   => [
                    'user_id'       => $uid,
                    'creator'       => $creator,
                    'detail'        => $log['activity_detail'],
                    'category_id'   => $log['category_id'],
                    'category_name' => $log['category_name'] ?? 'ไม่ระบุ',
                    'date_raw'      => $log['work_date'],
                    'start_raw'     => substr($startT, 0, 5),
                    'end_raw'       => $endT ? substr($endT, 0, 5) : '',
                ],
            ];
        }

        return [
            'y'             => $y,
            'm'             => $m,
            'd'             => $d,
            'selectedDate'  => $selectedDate,
            'isLoggedIn'    => $isLoggedIn,
            'isSystem'      => $isAdmin,
            'canManageOwn'  => $isAdmin,
            'canEdit'       => $isAdmin && ($selectedDate === date('Y-m-d')),
            'categories'    => $categories,
            'existingLogs'  => $existingLogs,
            'calendarEvents'=> $calendarEvents,
            'defaultView'   => $getParams['view'] ?? ($isLoggedIn ? 'table' : 'calendar'),
        ];
    }
}