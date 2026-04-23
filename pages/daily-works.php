<?php
date_default_timezone_set('Asia/Bangkok');
global $pdo;

// ==========================================
// 🚀 AJAX: สำหรับเพิ่มหมวดหมู่ใหม่แบบ Inline
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'add_category') {
    header('Content-Type: application/json');

    // เช็คสิทธิ์ก่อนบันทึก (ให้เฉพาะ Admin/System)
    $userRole = $user['role'] ?? 'MEMBER';
    if (!in_array($userRole, ['SYSTEM', 'ADMIN'])) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เพิ่มหมวดหมู่']);
        exit;
    }

    $cat_name = trim($_POST['category_name'] ?? '');
    if ($cat_name === '') {
        echo json_encode(['status' => 'error', 'message' => 'ชื่อหมวดหมู่ห้ามว่าง']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO work_log_categories (name_th) VALUES (:name)");
        $stmt->execute([':name' => $cat_name]);
        $new_id = $pdo->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'id' => $new_id,
            'name_th' => htmlspecialchars($cat_name)
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// ==========================================
// PART 0: SETUP & INIT
// ==========================================
$currentY = (int)date('Y');
$currentM = (int)date('m');
$currentD = (int)date('d');

$y = isset($_GET['year']) ? (int)$_GET['year'] : $currentY;
$m = isset($_GET['month']) ? (int)$_GET['month'] : $currentM;
$d = isset($_GET['day']) ? (int)$_GET['day'] : $currentD;

if (!checkdate($m, $d, $y)) {
    if (checkdate($m, 1, $y)) {
        $d = (int)date('t', strtotime("$y-$m-01"));
    } else {
        $y = (int)date('Y');
        $m = (int)date('m');
        $d = (int)date('d');
    }
}

$selected_date = sprintf('%04d-%02d-%02d', $y, $m, $d);
$message = '';

// --- 🔐 ระบบจัดการสิทธิ์ (Role Permissions) ---
$isLoggedIn = isset($user) && isset($user['id']);
$userRole = $user['role'] ?? 'MEMBER';

$isSystem = ($userRole === 'SYSTEM');
$isAdmin = ($userRole === 'ADMIN');
$canManageOwn = ($isSystem || $isAdmin);

$isToday = ($selected_date === date('Y-m-d'));
$canEdit = $canManageOwn && $isToday;

$defaultView = $isLoggedIn ? 'table' : 'calendar';
if (isset($_GET['view'])) $defaultView = $_GET['view'];

/* ====== LOAD CATEGORIES ====== */
$categories = [];
try {
    if (isset($pdo)) {
        $stmt_cat = $pdo->query("SELECT * FROM work_log_categories ORDER BY id ASC");
        $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
}
$allowedCatIds = array_flip(array_map(fn($c) => (string)$c['id'], $categories));


// ==========================================
// PART 1: HANDLE FORM SUBMISSIONS
// ==========================================
// ... (ส่วนจัดการ Form Submit ทั้ง 1.1, 1.2, 1.3 คงเดิมไม่เปลี่ยนแปลงเพื่อไม่ให้กระทบ Logic ด้านหลัง)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_log_table'])) {
    if (!$canEdit) {
        $message = '❌ ไม่มีสิทธิ์แก้ไขข้อมูล หรือไม่สามารถแก้ไขย้อนหลังได้';
    } else {
        $work_date = $_POST['work_date'] ?? $selected_date;
        $logs_update = $_POST['logs_update'] ?? [];
        $logs_new    = $_POST['logs_new'] ?? [];

        try {
            if (isset($pdo)) {
                $pdo->beginTransaction();

                $stmtUpdate = $pdo->prepare("UPDATE daily_work_logs SET activity_detail = :detail, category_id = :catid, updated_at = NOW() WHERE id = :id AND user_id = :uid");
                $stmtDelete = $pdo->prepare("DELETE FROM daily_work_logs WHERE id = :id AND user_id = :uid");

                foreach ($logs_update as $id => $data) {
                    $activity = trim($data['activity'] ?? '');
                    $category_id = $data['category_id'] ?? '';
                    $cat_db = ($category_id !== '' && isset($allowedCatIds[(string)$category_id])) ? (int)$category_id : null;

                    if ($activity === '') {
                        $stmtDelete->execute([':id' => $id, ':uid' => $user['id']]);
                    } else {
                        $stmtUpdate->execute([':detail' => $activity, ':catid'  => $cat_db, ':id' => $id, ':uid' => $user['id']]);
                    }
                }

                $stmtInsert = $pdo->prepare("INSERT INTO daily_work_logs (user_id, work_date, start_time, end_time, activity_detail, category_id) VALUES (:uid, :wdate, :stime, :etime, :detail, :catid)");

                foreach ($logs_new as $timeKey => $data) {
                    $activity = trim($data['activity'] ?? '');
                    if ($activity === '') continue;

                    $category_id = $data['category_id'] ?? '';
                    $cat_db = ($category_id !== '' && isset($allowedCatIds[(string)$category_id])) ? (int)$category_id : null;

                    $hour = 0;
                    $min = 0;
                    if (strpos($timeKey, '_30') !== false) {
                        $parts = explode('_', $timeKey);
                        $hour = (int)$parts[0];
                        $min = 30;
                    } else {
                        $hour = (int)$timeKey;
                        $min = 0;
                    }

                    $startTimeStr = sprintf("%02d:%02d:00", $hour, $min);
                    $endTimeStr = date('H:i:s', strtotime("$startTimeStr +1 hour"));

                    $stmtInsert->execute([':uid' => $user['id'], ':wdate' => $work_date, ':stime' => $startTimeStr, ':etime' => $endTimeStr, ':detail' => $activity, ':catid' => $cat_db]);
                }
                $pdo->commit();
                $message = "✅ บันทึกข้อมูลสำเร็จ";
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calendar_action'])) {
    if (!$canManageOwn) die("Access Denied");
    $action = $_POST['calendar_action'];
    $log_id = $_POST['log_id'];
    try {
        if (isset($pdo)) {
            $pdo->beginTransaction();
            if ($action === 'delete') {
                if ($isSystem) {
                    $stmt = $pdo->prepare("DELETE FROM daily_work_logs WHERE id = :id");
                    $stmt->execute([':id' => $log_id]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM daily_work_logs WHERE id = :id AND user_id = :uid");
                    $stmt->execute([':id' => $log_id, ':uid' => $user['id']]);
                }
                $msg = "deleted";
            } elseif ($action === 'edit') {
                $c_date = $_POST['work_date'];
                $c_start = $_POST['start_time'];
                $c_end = $_POST['end_time'];
                $c_detail = trim($_POST['activity_detail']);
                $c_cat = $_POST['category_id'] ?: null;
                if ($isSystem) {
                    $stmt = $pdo->prepare("UPDATE daily_work_logs SET work_date=:wdate, start_time=:stime, end_time=:etime, activity_detail=:detail, category_id=:catid WHERE id=:id");
                    $stmt->execute([':wdate' => $c_date, ':stime' => $c_start, ':etime' => $c_end, ':detail' => $c_detail, ':catid' => $c_cat, ':id' => $log_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE daily_work_logs SET work_date=:wdate, start_time=:stime, end_time=:etime, activity_detail=:detail, category_id=:catid WHERE id=:id AND user_id=:uid");
                    $stmt->execute([':wdate' => $c_date, ':stime' => $c_start, ':etime' => $c_end, ':detail' => $c_detail, ':catid' => $c_cat, ':id' => $log_id, ':uid' => $user['id']]);
                }
                $msg = "updated";
            }
            $pdo->commit();
            header("Location: ?page=daily-works&view=calendar&msg=" . $msg);
            exit;
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "Error: " . $e->getMessage();
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_log_calendar'])) {
    if (!$canManageOwn) die("Access Denied");
    $c_date = $_POST['work_date'];
    $c_start = $_POST['start_time'];
    $c_end = $_POST['end_time'];
    $c_detail = trim($_POST['activity_detail']);
    $c_cat = $_POST['category_id'] ?: null;
    try {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("INSERT INTO daily_work_logs (user_id, work_date, start_time, end_time, activity_detail, category_id) VALUES (:uid, :wdate, :stime, :etime, :detail, :catid)");
            $stmt->execute([':uid' => $user['id'], ':wdate' => $c_date, ':stime' => $c_start, ':etime' => $c_end, ':detail' => $c_detail, ':catid' => $c_cat]);
            header("Location: ?page=daily-works&view=calendar&msg=saved");
            exit;
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }
}

// ==========================================
// PART 2: DATA FETCHING
// ==========================================
$existing_logs = [];
$calendar_events = [];
$userPalette = ['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#f97316', '#6366f1', '#84cc16', '#d946ef', '#64748b'];

if (isset($pdo)) {
    // --- 2.1 Fetch Table Data ---
    $sql_table = "";
    $params_table = [':wdate' => $selected_date];

    if ($isLoggedIn) {
        $sql_table = "SELECT * FROM daily_work_logs WHERE user_id = :uid AND work_date = :wdate";
        $params_table[':uid'] = $user['id'];
    } else {
        $sql_table = "
            SELECT d.*, u.display_th, c.name_th AS category_name 
            FROM daily_work_logs d 
            LEFT JOIN users u ON d.user_id = u.id 
            LEFT JOIN work_log_categories c ON d.category_id = c.id
            WHERE d.work_date = :wdate ORDER BY d.user_id ASC";
    }

    $stmt_log = $pdo->prepare($sql_table);
    $stmt_log->execute($params_table);

    while ($row = $stmt_log->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['start_time'])) {
            [$hh, $mm] = explode(':', $row['start_time']);
            $hour = (int)$hh;
            $minute = (int)$mm;
        } else {
            $hour = (int)($row['start_hour'] ?? 0);
            $minute = 0;
        }
        if ($hour <= 0) continue;
        $keyHour = ($minute === 30) ? $hour . '_30' : (string)$hour;
        $existing_logs[$keyHour][] = $row;
    }

    // --- 2.2 Fetch Calendar Data ---
    $stmt_cal = $pdo->query("
        SELECT d.*, u.display_th, c.name_th AS category_name
        FROM daily_work_logs d 
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN work_log_categories c ON d.category_id = c.id
    ");

    foreach ($stmt_cal->fetchAll(PDO::FETCH_ASSOC) as $log) {
        $startT = $log['start_time'];
        $endT = $log['end_time'];
        if (empty($startT) && !empty($log['start_hour'])) {
            $startT = sprintf("%02d:00:00", $log['start_hour']);
            $endT   = sprintf("%02d:00:00", $log['start_hour'] + 1);
        }

        if ($startT) {
            $creatorName = !empty($log['display_th']) ? $log['display_th'] : 'User #' . $log['user_id'];
            $displayTitle = $log['activity_detail'] . " [$creatorName]";
            $userId = intval($log['user_id']);
            $assignedColor = $userPalette[$userId % count($userPalette)];

            $calendar_events[] = [
                'id' => $log['id'],
                'title' => $displayTitle,
                'start' => $log['work_date'] . 'T' . $startT,
                'end' => $log['work_date'] . 'T' . $endT,
                'backgroundColor' => $assignedColor,
                'borderColor' => $assignedColor,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'user_id' => (int)$log['user_id'],
                    'creator' => $creatorName,
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
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'saved') $message = "✅ บันทึกข้อมูลเรียบร้อยแล้ว";
    if ($_GET['msg'] == 'updated') $message = "✅ แก้ไขข้อมูลเรียบร้อยแล้ว";
    if ($_GET['msg'] == 'deleted') $message = "🗑️ ลบข้อมูลเรียบร้อยแล้ว";
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกภาระงานประจำวัน</title>
    <?php include './lib/style.php'; ?>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <style>
        .fade-enter-active {
            transition: opacity 0.3s ease-out;
        }

        .fc {
            z-index: 1;
        }

        .fc-event {
            cursor: pointer;
        }

        .fc-day-today {
            background-color: rgba(99, 102, 241, 0.1) !important;
        }

        .modal-label {
            @apply text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1;
        }

        .modal-value {
            @apply text-sm text-slate-800 font-medium;
        }

        /* ซ่อน Header ของ FullCalendar บางส่วนในมือถือ */
        @media (max-width: 640px) {
            .fc-toolbar-title {
                font-size: 1.1rem !important;
            }

            .fc .fc-toolbar.fc-header-toolbar {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen text-slate-800">

    <?php include './components/navbar.php'; ?>

    <div class="max-w-7xl mx-auto py-6 sm:py-8 md:px-4 sm:px-6 lg:px-8">

        <div class="md:px-0 px-4 mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-2">📝 บันทึกภาระงานประจำวัน</h1>
            <?php if ($message): ?><div class="mt-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded shadow-sm text-sm sm:text-base"><?php echo $message; ?></div><?php endif; ?>

            <?php if (!$isLoggedIn): ?>
                <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-700 rounded shadow-sm text-sm sm:text-base">⚠️ กรุณาเข้าสู่ระบบเพื่อจัดการข้อมูล</div>
            <?php elseif (!$canManageOwn): ?>
                <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 rounded shadow-sm text-sm sm:text-base">ℹ️ สิทธิ์การใช้งานของคุณ: สามารถ <b>ดูข้อมูล</b> ได้เท่านั้น</div>
            <?php endif; ?>
        </div>

        <div class="md:px-0 px-4 flex justify-center mb-6 sm:mb-8 w-full">
            <div class="bg-white p-1 rounded-xl shadow-sm border border-slate-200 flex w-full sm:w-auto">
                <button onclick="switchView('table')" id="btn-view-table" class="flex-1 justify-center sm:flex-none px-4 sm:px-6 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 flex items-center gap-2 bg-indigo-600 text-white shadow-md">มุมมองตาราง</button>
                <button onclick="switchView('calendar')" id="btn-view-calendar" class="flex-1 justify-center sm:flex-none px-4 sm:px-6 py-2.5 rounded-lg text-sm font-semibold text-slate-600 hover:text-indigo-600 transition-all duration-200 flex items-center gap-2">มุมมองปฏิทิน</button>
            </div>
        </div>

        <div id="view-table" class="<?= $defaultView === 'table' ? '' : 'hidden' ?> fade-enter-active">
            <div class="bg-white md:rounded-2xl shadow-lg border border-slate-100 overflow-hidden">

                <div class="px-4 py-4 md:px-6 md:py-5 bg-white border-b border-slate-200 sticky top-0 z-10 shadow-sm">
                    <form method="get" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <input type="hidden" name="page" value="daily-works">
                        <input type="hidden" name="view" value="table">
                        <div class="flex flex-wrap items-center bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 sm:px-4 sm:py-2 shadow-sm hover:border-indigo-400 transition-colors w-full sm:w-auto">
                            <span class="text-xs sm:text-sm font-bold text-indigo-600 mr-2 uppercase tracking-wide">วันที่:</span>
                            <select name="day" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer font-medium text-slate-700 hover:text-indigo-700 text-sm sm:text-base">
                                <?php for ($i = 1; $i <= 31; $i++): ?><option value="<?= $i ?>" <?= $i == $d ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?>
                            </select><span class="mx-1 text-slate-400">/</span>
                            <select name="month" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer font-medium text-slate-700 hover:text-indigo-700 text-sm sm:text-base">
                                <?php
                                $ms = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                                foreach ($ms as $i => $n): $val = $i + 1;
                                ?>
                                    <option value="<?= $val ?>" <?= ($val == $m) ? 'selected' : '' ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="year" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer font-medium text-slate-700 hover:text-indigo-700 text-sm sm:text-base ml-1">
                                <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?><option value="<?= $i ?>" <?= $i == $y ? 'selected' : '' ?>><?= $i + 543 ?></option><?php endfor; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <form method="post">
                    <input type="hidden" name="work_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                    <input type="hidden" name="save_log_table" value="1">

                    <div class="w-full">
                        <table class="w-full text-left border-collapse block md:table">
                            <thead class="hidden md:table-header-group">
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider font-bold border-b border-slate-200">
                                    <th class="px-6 py-4 w-48 min-w-[150px]">ช่วงเวลา</th>
                                    <th class="px-6 py-4">รายละเอียดภาระงาน</th>
                                    <th class="px-6 py-4 w-64 min-w-[200px]">หมวดหมู่</th>
                                </tr>
                            </thead>
                            <tbody class="block md:table-row-group divide-y divide-slate-100 md:divide-y-0 bg-slate-50 md:bg-white gap-2">
                                <?php
                                $timeSlots = [];
                                for ($i = 8; $i <= 16; $i++) {
                                    $timeSlots[] = (string)$i;
                                    if (isset($existing_logs[$i . '_30'])) $timeSlots[] = $i . '_30';
                                }

                                foreach ($timeSlots as $index => $h):
                                    $logsInHour = $existing_logs[$h] ?? [];
                                    $isHalf = str_contains($h, '_30');
                                    $mainLog = $logsInHour[0] ?? [];

                                    if (!empty($mainLog['start_time'])) {
                                        $showStart = date('H:i', strtotime($mainLog['start_time']));
                                        $showEnd = !empty($mainLog['end_time']) ? date('H:i', strtotime($mainLog['end_time'])) : sprintf("%02d:00", intval($h) + 1);
                                    } else {
                                        $val = intval($h);
                                        $showStart = $isHalf ? sprintf("%02d:30", $val) : sprintf("%02d:00", $val);
                                        $showEnd = sprintf("%02d:00", $val + 1);
                                    }
                                    $rowClass = ($index % 2 == 0) ? 'md:bg-white' : 'md:bg-slate-50/60';
                                ?>
                                    <tr class="<?= $rowClass ?> bg-white my-3 md:mx-4 md:my-0 md:rounded-xl shadow-sm md:shadow-none border border-slate-200 md:border-0 md:border-b hover:bg-indigo-50/40 transition-colors group flex flex-col md:table-row">

                                        <td class="px-4 py-3 md:px-6 md:py-5 align-top md:border-r border-slate-100 block md:table-cell w-full md:w-48 bg-slate-50 md:bg-transparent rounded-t-xl md:rounded-none border-b md:border-b-0">
                                            <div class="flex flex-row md:flex-col items-center md:items-start justify-between md:justify-center md:h-full">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-2 h-2 rounded-full <?= !empty($logsInHour) ? 'bg-indigo-500 ring-4 ring-indigo-100' : 'bg-slate-300' ?>"></div>
                                                    <span class="text-base md:text-lg font-bold text-slate-700 font-mono tracking-tight"><?= $showStart ?></span>
                                                </div>
                                                <div class="hidden md:block pl-[1.2rem] border-l-2 border-indigo-100 ml-[0.24rem] py-1 my-1">
                                                    <span class="text-xs font-medium text-slate-400 block px-2">ถึง</span>
                                                </div>
                                                <div class="md:hidden text-xs text-slate-400 mx-2">ถึง</div>
                                                <div class="flex items-center gap-2 opacity-60">
                                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-300 ml-[0.08rem] hidden md:block"></div>
                                                    <span class="text-sm md:text-sm font-semibold text-slate-500 font-mono tracking-tight"><?= $showEnd ?></span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 md:px-6 md:py-4 align-top block md:table-cell w-full">
                                            <?php if ($canEdit): ?>
                                                <div class="flex flex-col gap-3">
                                                    <?php if (!empty($logsInHour)): ?>
                                                        <?php foreach ($logsInHour as $entry): ?>
                                                            <div class="relative w-full">
                                                                <label class="md:hidden text-xs text-indigo-500 font-bold mb-1 block">รายละเอียดงาน:</label>
                                                                <textarea name="logs_update[<?= $entry['id'] ?>][activity]" rows="2" class="w-full border-0 bg-slate-50 md:bg-transparent p-2 md:p-0 rounded-lg md:rounded-none text-slate-800 placeholder:text-slate-300 focus:ring-0 focus:border-indigo-500 text-sm resize-none leading-relaxed"><?= htmlspecialchars($entry['activity_detail']) ?></textarea>
                                                                <div class="hidden md:block absolute bottom-0 left-0 right-0 h-px bg-slate-200 group-hover:bg-indigo-200 transition-colors"></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="relative w-full">
                                                            <label class="md:hidden text-xs text-indigo-500 font-bold mb-1 block">รายละเอียดงาน:</label>
                                                            <textarea name="logs_new[<?= $h ?>][activity]" rows="2" placeholder="ระบุรายละเอียดงาน..." class="w-full border-0 bg-slate-50 md:bg-transparent p-2 md:p-0 rounded-lg md:rounded-none text-slate-800 placeholder:text-slate-400 md:placeholder:text-slate-300 focus:ring-0 focus:border-indigo-500 text-sm resize-none leading-relaxed"></textarea>
                                                            <div class="hidden md:block absolute bottom-0 left-0 right-0 h-px bg-slate-200 group-hover:bg-indigo-200 transition-colors"></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <?php if (!empty($logsInHour)): ?>
                                                    <div class="flex flex-col gap-3">
                                                        <?php foreach ($logsInHour as $entry): ?>
                                                            <div class="bg-white md:bg-white/50 border border-slate-100 p-3 rounded-lg shadow-sm">
                                                                <?php if (!empty($entry['display_th'])): ?>
                                                                    <div class="text-xs text-indigo-600 font-bold mb-1"><?= htmlspecialchars($entry['display_th']) ?></div>
                                                                <?php endif; ?>
                                                                <p class="text-sm text-slate-700"><?= htmlspecialchars($entry['activity_detail']) ?></p>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-slate-300 text-sm italic font-light">- ว่าง -</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-4 pb-4 md:px-6 md:py-4 align-top w-full md:w-64 min-w-[200px] block md:table-cell">
                                            <?php if ($canEdit): ?>
                                                <div class="flex flex-col gap-3">
                                                    <?php if (!empty($logsInHour)): ?>
                                                        <?php foreach ($logsInHour as $entry): ?>
                                                            <div class="relative w-full">
                                                                <select name="logs_update[<?= $entry['id'] ?>][category_id]" class="cursor-pointer w-full bg-white md:bg-slate-50 border border-slate-200 md:border-transparent text-slate-600 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 transition-all hover:bg-white hover:shadow-sm">
                                                                    <option value="">-- เลือกหมวดหมู่ --</option>
                                                                    <?php foreach ($categories as $cat): ?>
                                                                        <option value="<?= $cat['id'] ?>" <?= (($entry['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= $cat['name_th'] ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="relative w-full">
                                                            <select name="logs_new[<?= $h ?>][category_id]" class="cursor-pointer w-full bg-white md:bg-slate-50 border border-slate-200 md:border-transparent text-slate-600 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 transition-all hover:bg-white hover:shadow-sm">
                                                                <option value="">-- เลือกหมวดหมู่ --</option>
                                                                <?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= $cat['name_th'] ?></option><?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex flex-col gap-3">
                                                    <?php if (!empty($logsInHour)): ?>
                                                        <?php foreach ($logsInHour as $entry): ?>
                                                            <div class="flex items-start md:pt-3">
                                                                <?php if (!empty($entry['category_name'])): ?>
                                                                    <span class="inline-flex items-center px-2.5 py-1 md:py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 border border-indigo-200"><?= htmlspecialchars($entry['category_name']) ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-slate-300 text-xs">-</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-slate-300 text-sm hidden md:block">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($canEdit): ?>
                        <div class="px-4 py-4 md:px-6 md:py-4 bg-white md:bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between sticky bottom-0 z-10 gap-3 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] md:shadow-none">
                            <span class="text-xs text-slate-400 text-center sm:text-left w-full sm:w-auto">* ลบข้อความให้ว่างเพื่อลบรายการ</span>
                            <button type="submit" class="w-full sm:w-auto bg-indigo-600 text-white px-8 py-3 sm:py-2.5 rounded-lg hover:bg-indigo-700 shadow-lg font-medium text-base sm:text-sm transition-colors">บันทึกข้อมูล</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div id="view-calendar" class="<?= $defaultView === 'calendar' ? '' : 'hidden' ?> fade-enter-active">
            <div class="bg-white p-4 md:p-6 rounded-2xl shadow-xl border border-slate-100">
                <div id='calendar'></div>
            </div>
        </div>
    </div>


    <div id="calendarModal" class="hidden fixed inset-0 z-[9999] overflow-y-auto backdrop-blur-sm">
        <div class="flex min-h-full items-center justify-center md:p-4 text-center w-full">
            <div class="fixed inset-0 bg-slate-900/60 transition-opacity" onclick="closeModal()"></div>

            <div class="relative transform overflow-visible md:rounded-3xl bg-white text-left shadow-2xl transition-all w-full sm:max-w-md z-10 my-8 border border-white">

                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-5 py-4 sm:px-6 md:rounded-t-3xl flex justify-between items-center shadow-sm">
                    <h3 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        เพิ่มกิจกรรมใหม่
                    </h3>
                    <button type="button" onclick="closeModal()" class="text-indigo-200 hover:text-white transition-colors bg-white/10 hover:bg-white/20 p-1.5 rounded-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form action="" method="POST" class="p-5 sm:p-7 bg-slate-50/50 rounded-b-3xl">
                    <input type="hidden" name="save_log_calendar" value="1">
                    <input type="hidden" name="work_date" id="m_work_date">

                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">เวลาเริ่ม</label>
                                <div class="relative">
                                    <input type="time" name="start_time" id="m_start_time" required class="w-full border border-slate-200 bg-slate-50 p-2.5 pl-3 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white text-base sm:text-sm font-medium text-slate-700 transition-all outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">เวลาสิ้นสุด</label>
                                <div class="relative">
                                    <input type="time" name="end_time" id="m_end_time" required class="w-full border border-slate-200 bg-slate-50 p-2.5 pl-3 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white text-base sm:text-sm font-medium text-slate-700 transition-all outline-none">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative mb-5 bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">หมวดหมู่</label>
                            <button type="button" id="btn_show_add_cat_new" onclick="toggleAddCategoryUINew()" class="inline-flex text-xs text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded-md items-center gap-1 font-semibold transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                เพิ่มใหม่
                            </button>
                        </div>

                        <div id="category_select_wrapper_new">
                            <select name="category_id" id="detail_category_new" class="w-full border border-slate-200 bg-slate-50 rounded-xl text-base sm:text-sm font-medium text-slate-700 p-3 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all outline-none cursor-pointer">
                                <option value="">-- ไม่ระบุหมวดหมู่ --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= $cat['name_th'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="category_add_wrapper_new" style="display: none;" class="items-center gap-2 mt-1 bg-indigo-50/50 p-2 rounded-xl border border-indigo-100">
                            <input type="text" id="new_category_name_new" placeholder="ระบุชื่อหมวดหมู่ที่ต้องการ..." class="w-full border-slate-200 rounded-lg text-base sm:text-sm font-medium text-slate-700 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5 px-3 outline-none">
                            <button type="button" onclick="saveNewCategory('new')" class="bg-indigo-600 text-white p-2.5 rounded-lg hover:bg-indigo-700 shadow-sm transition-colors flex-shrink-0" title="บันทึกหมวดหมู่">
                                <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="toggleAddCategoryUINew()" class="bg-white text-slate-500 p-2.5 border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors flex-shrink-0" title="ยกเลิก">
                                <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-6">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">รายละเอียดภาระงาน</label>
                        <textarea name="activity_detail" rows="3" required placeholder="อธิบายกิจกรรมที่คุณทำ..." class="w-full border border-slate-200 bg-slate-50 p-3 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white text-base sm:text-sm font-medium text-slate-700 transition-all outline-none resize-none"></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal()" class="w-full sm:w-auto px-6 py-3 sm:py-2.5 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm order-last sm:order-first">ยกเลิก</button>
                        <button type="submit" class="w-full sm:w-auto px-8 py-3 sm:py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-[0_4px_12px_rgba(79,70,229,0.3)] hover:shadow-[0_6px_16px_rgba(79,70,229,0.4)] transition-all transform hover:-translate-y-0.5">บันทึกภาระงาน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div id="eventDetailModal" class="hidden fixed inset-0 z-[10000] overflow-y-auto backdrop-blur-sm">
        <div class="flex min-h-full items-center justify-center md:p-4 text-center w-full">
            <div class="fixed inset-0 bg-slate-900/60 transition-opacity" onclick="closeDetailModal()"></div>

            <form action="" method="POST" class="relative transform overflow-visible md:rounded-3xl bg-white text-left shadow-2xl transition-all w-full sm:max-w-md z-10 my-8 border border-white">
                <input type="hidden" name="calendar_action" id="detail_action" value="edit">
                <input type="hidden" name="log_id" id="detail_id">
                <input type="hidden" name="work_date" id="detail_date_input">

                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-5 py-4 sm:px-6 md:rounded-t-3xl flex justify-between items-center shadow-sm">
                    <h3 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        รายละเอียดกิจกรรม
                    </h3>
                    <button type="button" onclick="closeDetailModal()" class="text-indigo-200 hover:text-white transition-colors bg-white/10 hover:bg-white/20 p-1.5 rounded-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-5 sm:p-7 bg-slate-50/50 rounded-b-3xl">
                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-5 flex items-center justify-between">
                        <p class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-0">ผู้บันทึกข้อมูล</p>
                        <div class="flex items-center gap-2">
                            <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </span>
                            <span class="text-sm font-bold text-slate-800" id="detail_creator">...</span>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">เวลาเริ่ม</p>
                                <input type="time" name="start_time" id="detail_start" class="w-full border border-slate-200 bg-slate-50 p-2.5 pl-3 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white text-base sm:text-sm font-medium text-slate-700 transition-all outline-none">
                                <div id="view_start" class="hidden text-base sm:text-sm text-indigo-600 font-bold py-2 px-1"></div>
                            </div>
                            <div>
                                <p class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">เวลาสิ้นสุด</p>
                                <input type="time" name="end_time" id="detail_end" class="w-full border border-slate-200 bg-slate-50 p-2.5 pl-3 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white text-base sm:text-sm font-medium text-slate-700 transition-all outline-none">
                                <div id="view_end" class="hidden text-base sm:text-sm text-indigo-600 font-bold py-2 px-1"></div>
                            </div>
                        </div>
                    </div>

                    <div class="relative mb-5 bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
                        <div class="flex justify-between items-center mb-2">
                            <p class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-0">หมวดหมู่</p>
                            <button type="button" id="btn_show_add_cat_edit" onclick="toggleAddCategoryUIEdit()" style="display: none;" class="inline-flex text-xs text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded-md items-center gap-1 font-semibold transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                เพิ่มใหม่
                            </button>
                        </div>

                        <div id="category_select_wrapper_edit">
                            <select name="category_id" id="detail_category_edit" class="w-full border border-slate-200 bg-slate-50 rounded-xl text-base sm:text-sm font-medium text-slate-700 p-3 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all outline-none cursor-pointer">
                                <option value="">-- ไม่ระบุหมวดหมู่ --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= $cat['name_th'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="category_add_wrapper_edit" style="display: none;" class="items-center gap-2 mt-1 bg-indigo-50/50 p-2 rounded-xl border border-indigo-100">
                            <input type="text" id="new_category_name_edit" placeholder="ระบุชื่อหมวดหมู่ที่ต้องการ..." class="w-full border-slate-200 rounded-lg text-base sm:text-sm font-medium text-slate-700 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5 px-3 outline-none">
                            <button type="button" onclick="saveNewCategory('edit')" class="bg-indigo-600 text-white p-2.5 rounded-lg hover:bg-indigo-700 shadow-sm transition-colors flex-shrink-0" title="บันทึกหมวดหมู่">
                                <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="toggleAddCategoryUIEdit()" class="bg-white text-slate-500 p-2.5 border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors flex-shrink-0" title="ยกเลิก">
                                <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div id="view_category" class="hidden text-base sm:text-sm text-slate-800 font-medium py-1 px-1"></div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-6">
                        <p class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">รายละเอียดงาน</p>
                        <textarea name="activity_detail" id="detail_desc" rows="3" class="w-full border border-slate-200 bg-slate-50 p-3 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white text-base sm:text-sm font-medium text-slate-700 transition-all outline-none resize-none"></textarea>
                        <div id="view_desc" class="hidden text-base sm:text-sm text-slate-700 font-medium py-3 px-4 bg-slate-50/50 rounded-xl min-h-[4rem] whitespace-pre-wrap border border-slate-100"></div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                        <button type="button" id="btn_delete_event" style="display: none;" onclick="confirmDelete()" class="w-full sm:w-auto sm:mr-auto inline-flex justify-center items-center px-6 py-3 sm:py-2.5 bg-red-50 text-red-600 text-center font-bold rounded-xl hover:bg-red-100 hover:text-red-700 transition-colors shadow-sm order-3 sm:order-1">ลบกิจกรรม</button>

<button type="button" onclick="closeDetailModal()" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 sm:py-2.5 bg-white border border-slate-200 text-slate-600 text-center font-bold rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm order-2">ปิด</button>

<button type="submit" id="btn_save_edit" style="display: none;" class="w-full sm:w-auto inline-flex justify-center items-center px-8 py-3 sm:py-2.5 bg-indigo-600 text-white text-center font-bold rounded-xl hover:bg-indigo-700 shadow-[0_4px_12px_rgba(79,70,229,0.3)] hover:shadow-[0_6px_16px_rgba(79,70,229,0.4)] transition-all transform hover:-translate-y-0.5 order-1 sm:order-3">บันทึกแก้ไข</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <script>
        // -------------------------
        // 1. AJAX Add Category Logic
        // -------------------------
        function toggleAddCategoryUINew() {
            const selectWrapper = document.getElementById('category_select_wrapper_new');
            const addWrapper = document.getElementById('category_add_wrapper_new');
            const input = document.getElementById('new_category_name_new');

            if (addWrapper.style.display === 'none') {
                addWrapper.style.display = 'flex';
                selectWrapper.style.display = 'none';
                input.value = '';
                input.focus();
            } else {
                addWrapper.style.display = 'none';
                selectWrapper.style.display = 'block';
            }
        }

        function toggleAddCategoryUIEdit() {
            const selectWrapper = document.getElementById('category_select_wrapper_edit');
            const addWrapper = document.getElementById('category_add_wrapper_edit');
            const input = document.getElementById('new_category_name_edit');

            if (addWrapper.style.display === 'none') {
                addWrapper.style.display = 'flex';
                selectWrapper.style.display = 'none';
                input.value = '';
                input.focus();
            } else {
                addWrapper.style.display = 'none';
                selectWrapper.style.display = 'block';
            }
        }

        async function saveNewCategory(type) {
            const input = document.getElementById(`new_category_name_${type}`);
            const name = input.value.trim();

            if (!name) {
                alert('กรุณาระบุชื่อหมวดหมู่');
                input.focus();
                return;
            }

            try {
                const formData = new FormData();
                formData.append('ajax_action', 'add_category');
                formData.append('category_name', name);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    const categorySelects = document.querySelectorAll('select[name*="category_id"]');
                    categorySelects.forEach(select => {
                        const option = new Option(data.name_th, data.id);
                        select.add(option);
                    });
                    document.getElementById(`detail_category_${type}`).value = data.id;

                    if (type === 'new') toggleAddCategoryUINew();
                    if (type === 'edit') toggleAddCategoryUIEdit();
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาดในการบันทึก');
                }
            } catch (error) {
                console.error(error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
            }
        }

        // -------------------------
        // 2. Calendar Logic & Variables
        // -------------------------
        const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
        const currentUserId = <?= isset($user['id']) ? (int)$user['id'] : 0 ?>;
        const isSystem = <?= $isSystem ? 'true' : 'false' ?>;
        const canManageOwn = <?= $canManageOwn ? 'true' : 'false' ?>;

        const calendarEvents = <?= json_encode($calendar_events) ?>;
        let calendar = null;

        function switchView(viewName) {
            const tableView = document.getElementById('view-table');
            const calView = document.getElementById('view-calendar');
            const btnTable = document.getElementById('btn-view-table');
            const btnCal = document.getElementById('btn-view-calendar');

            if (viewName === 'table') {
                if (tableView) tableView.classList.remove('hidden');
                calView.classList.add('hidden');
            } else {
                if (tableView) tableView.classList.add('hidden');
                calView.classList.remove('hidden');
                if (calendar) calendar.render();
            }

            const activeClass = "bg-indigo-600 text-white shadow-md";
            const inactiveClass = "text-slate-600 hover:text-indigo-600 bg-transparent";

            if (btnTable) {
                btnTable.className = btnTable.className.replace(/bg-indigo-600|text-white|shadow-md|text-slate-600|hover:text-indigo-600|bg-transparent/g, '').trim();
                btnTable.className += ` ${viewName === 'table' ? activeClass : inactiveClass}`;
            }
            if (btnCal) {
                btnCal.className = btnCal.className.replace(/bg-indigo-600|text-white|shadow-md|text-slate-600|hover:text-indigo-600|bg-transparent/g, '').trim();
                btnCal.className += ` ${viewName === 'calendar' ? activeClass : inactiveClass}`;
            }

            const url = new URL(window.location);
            url.searchParams.set('view', viewName);
            window.history.pushState({}, '', url);
        }

        <?php if ($canManageOwn): ?>

            function openModal(dateStr, startTime = '09:00', endTime = '10:00') {
                document.getElementById('m_work_date').value = dateStr;
                document.getElementById('m_start_time').value = startTime;
                document.getElementById('m_end_time').value = endTime;
                document.getElementById('calendarModal').classList.remove('hidden');
            }
        <?php endif; ?>

        function closeModal() {
            document.getElementById('calendarModal').classList.add('hidden');
        }

        function closeDetailModal() {
            document.getElementById('eventDetailModal').classList.add('hidden');
        }

        function confirmDelete() {
            if (confirm('คุณต้องการลบกิจกรรมนี้ใช่หรือไม่?')) {
                document.getElementById('detail_action').value = 'delete';
                document.getElementById('detail_action').form.submit();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const initialView = urlParams.get('view') || (isLoggedIn ? 'table' : 'calendar');
            const calendarEl = document.getElementById('calendar');

            // ตรวจสอบขนาดหน้าจอเพื่อปรับมุมมองปฏิทินในมือถือให้เหมาะสม
            const isMobile = window.innerWidth < 768;

            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'th',
                initialView: isMobile ? 'listWeek' : 'dayGridMonth', // ถ้าจอมือถือให้แสดงแบบรายการ (List)
                firstDay: 1,
                headerToolbar: {
                    left: isMobile ? 'prev,next' : 'prev,next today',
                    center: 'title',
                    right: isMobile ? 'listWeek,timeGridDay' : 'dayGridMonth,timeGridWeek'
                },
                buttonText: {
                    today: 'วันนี้',
                    month: 'เดือน',
                    week: 'สัปดาห์',
                    list: 'รายการ',
                    day: 'วัน'
                },
                events: calendarEvents,
                selectable: canManageOwn,
                editable: false,
                contentHeight: 'auto',
                dateClick: function(info) {
                    if (canManageOwn) openModal(info.dateStr);
                },
                select: function(info) {
                    if (canManageOwn) {
                        const st = info.start.toTimeString().substring(0, 5);
                        const et = info.end ? info.end.toTimeString().substring(0, 5) : st;
                        openModal(info.startStr.split('T')[0], st, et);
                    }
                },
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    const eventId = info.event.id;
                    const isOwner = (parseInt(props.user_id) === currentUserId);

                    const canEditThisEvent = isSystem || (canManageOwn && isOwner);

                    document.getElementById('detail_id').value = eventId;
                    document.getElementById('detail_date_input').value = props.date_raw;
                    document.getElementById('detail_creator').textContent = props.creator;

                    const inputStart = document.getElementById('detail_start');
                    const inputEnd = document.getElementById('detail_end');
                    const inputCat = document.getElementById('detail_category_edit');
                    const inputDesc = document.getElementById('detail_desc');
                    const btnAddCatEdit = document.getElementById('btn_show_add_cat_edit');
                    const selectCatWrapper = document.getElementById('category_select_wrapper_edit');

                    const viewStart = document.getElementById('view_start');
                    const viewEnd = document.getElementById('view_end');
                    const viewCat = document.getElementById('view_category');
                    const viewDesc = document.getElementById('view_desc');

                    const saveBtn = document.getElementById('btn_save_edit');
                    const delBtn = document.getElementById('btn_delete_event');

                    inputStart.value = props.start_raw;
                    inputEnd.value = props.end_raw;
                    inputCat.value = props.category_id || "";
                    inputDesc.value = props.detail;

                    viewStart.textContent = props.start_raw ? (props.start_raw + " น.") : "-";
                    viewEnd.textContent = props.end_raw ? (props.end_raw + " น.") : "-";
                    viewCat.textContent = props.category_name || "-- ไม่ระบุ --";
                    viewDesc.textContent = props.detail;

                    if (canEditThisEvent) {
                        inputStart.classList.remove('hidden');
                        inputEnd.classList.remove('hidden');
                        selectCatWrapper.style.display = 'block';
                        inputDesc.classList.remove('hidden');

                        btnAddCatEdit.style.display = 'inline-flex';

                        viewStart.classList.add('hidden');
                        viewEnd.classList.add('hidden');
                        viewCat.classList.add('hidden');
                        viewDesc.classList.add('hidden');

                        saveBtn.style.display = 'inline-flex';
                        delBtn.style.display = 'inline-flex';
                    } else {
                        inputStart.classList.add('hidden');
                        inputEnd.classList.add('hidden');
                        selectCatWrapper.style.display = 'none';
                        inputDesc.classList.add('hidden');

                        btnAddCatEdit.style.display = 'none';

                        viewStart.classList.remove('hidden');
                        viewEnd.classList.remove('hidden');
                        viewCat.classList.remove('hidden');
                        viewDesc.classList.remove('hidden');

                        saveBtn.style.display = 'none';
                        delBtn.style.display = 'none';
                    }

                    document.getElementById('eventDetailModal').classList.remove('hidden');
                }
            });

            switchView(initialView);
        });
    </script>
</body>

</html>