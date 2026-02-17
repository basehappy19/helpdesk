<?php
date_default_timezone_set('Asia/Bangkok');
global $pdo;

/* ==========================================
   PART 0: SETUP & INIT
   ========================================== */
$currentY = (int)date('Y');
$currentM = (int)date('m');
$currentD = (int)date('d');

$y = isset($_GET['year']) ? (int)$_GET['year'] : $currentY;
$m = isset($_GET['month']) ? (int)$_GET['month'] : $currentM;
$d = isset($_GET['day']) ? (int)$_GET['day'] : $currentD;

// ตรวจสอบความถูกต้องของวันที่ (ป้องกัน Bug เดือน ก.พ. เด้ง)
if (!checkdate($m, $d, $y)) {
    if (checkdate($m, 1, $y)) {
        // ถ้าเดือนปีถูก แต่วันเกิน -> ปรับเป็นวันสิ้นเดือน
        $d = (int)date('t', strtotime("$y-$m-01"));
    } else {
        // ถ้าผิดปกติจริงๆ -> Reset เป็นวันนี้
        $y = (int)date('Y');
        $m = (int)date('m');
        $d = (int)date('d');
    }
}

$selected_date = sprintf('%04d-%02d-%02d', $y, $m, $d);
$message = '';

$isLoggedIn = isset($user) && isset($user['id']);
$isToday = ($selected_date === date('Y-m-d'));
$canEdit = $isLoggedIn && $isToday; // แก้ไขได้เฉพาะวันนี้และต้อง Login

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


/* ==========================================
   PART 1: HANDLE FORM SUBMISSIONS
   ========================================== */

// 1.1 บันทึกข้อมูลจาก "มุมมองตาราง" (Table)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_log_table'])) {
    if (!$canEdit) {
        $message = '❌ ไม่สามารถแก้ไขข้อมูลย้อนหลัง หรือยังไม่ได้เข้าสู่ระบบ';
    } else {
        $work_date = $_POST['work_date'] ?? $selected_date;
        $logs_update = $_POST['logs_update'] ?? [];
        $logs_new    = $_POST['logs_new'] ?? [];

        try {
            if (isset($pdo)) {
                $pdo->beginTransaction();

                // --- A. รายการเดิม (Update / Delete) ---
                $stmtUpdate = $pdo->prepare("UPDATE daily_work_logs SET activity_detail = :detail, category_id = :catid, updated_at = NOW() WHERE id = :id AND user_id = :uid");
                $stmtDelete = $pdo->prepare("DELETE FROM daily_work_logs WHERE id = :id AND user_id = :uid");

                foreach ($logs_update as $id => $data) {
                    $activity = trim($data['activity'] ?? '');
                    $category_id = $data['category_id'] ?? '';
                    $cat_db = ($category_id !== '' && isset($allowedCatIds[(string)$category_id])) ? (int)$category_id : null;

                    if ($activity === '') {
                        $stmtDelete->execute([':id' => $id, ':uid' => $user['id']]);
                    } else {
                        $stmtUpdate->execute([
                            ':detail' => $activity,
                            ':catid'  => $cat_db,
                            ':id'     => $id,
                            ':uid'    => $user['id']
                        ]);
                    }
                }

                // --- B. รายการใหม่ (Insert) ---
                // ตัด start_hour ออก เพื่อให้ Database คำนวณเอง (แก้ Error Generated Column)
                $stmtInsert = $pdo->prepare("INSERT INTO daily_work_logs (user_id, work_date, start_time, end_time, activity_detail, category_id) VALUES (:uid, :wdate, :stime, :etime, :detail, :catid)");

                foreach ($logs_new as $timeKey => $data) {
                    $activity = trim($data['activity'] ?? '');
                    if ($activity === '') continue;

                    $category_id = $data['category_id'] ?? '';
                    $cat_db = ($category_id !== '' && isset($allowedCatIds[(string)$category_id])) ? (int)$category_id : null;

                    // แปลง Key (เช่น '8' หรือ '8_30')
                    $hour = 0; $min = 0;
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

                    $stmtInsert->execute([
                        ':uid'    => $user['id'],
                        ':wdate'  => $work_date,
                        ':stime'  => $startTimeStr,
                        ':etime'  => $endTimeStr,
                        ':detail' => $activity,
                        ':catid'  => $cat_db
                    ]);
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

// 1.2 แก้ไข/ลบ จาก "Modal ปฏิทิน" (Calendar Action)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calendar_action'])) {
    if (!$isLoggedIn) die("Access Denied");

    $action = $_POST['calendar_action']; // 'edit' หรือ 'delete'
    $log_id = $_POST['log_id'];
    
    try {
        if (isset($pdo)) {
            $pdo->beginTransaction();

            if ($action === 'delete') {
                // ลบ: เช็ค user_id ต้องตรงกัน
                $stmt = $pdo->prepare("DELETE FROM daily_work_logs WHERE id = :id AND user_id = :uid");
                $stmt->execute([':id' => $log_id, ':uid' => $user['id']]);
                $msg = "deleted";
            } elseif ($action === 'edit') {
                // แก้ไข: เช็ค user_id ต้องตรงกัน
                $c_date = $_POST['work_date'];
                $c_start = $_POST['start_time'];
                $c_end = $_POST['end_time'];
                $c_detail = trim($_POST['activity_detail']);
                $c_cat = $_POST['category_id'] ?: null;

                $stmt = $pdo->prepare("UPDATE daily_work_logs SET work_date=:wdate, start_time=:stime, end_time=:etime, activity_detail=:detail, category_id=:catid WHERE id=:id AND user_id=:uid");
                $stmt->execute([
                    ':wdate' => $c_date,
                    ':stime' => $c_start,
                    ':etime' => $c_end,
                    ':detail' => $c_detail,
                    ':catid' => $c_cat,
                    ':id' => $log_id,
                    ':uid' => $user['id']
                ]);
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

// 1.3 เพิ่มใหม่ จาก "หน้าปฏิทิน" (Add New via Calendar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_log_calendar'])) {
    if (!$isLoggedIn) die("Access Denied");
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


/* ==========================================
   PART 2: DATA FETCHING
   ========================================== */
$existing_logs = [];
$calendar_events = [];

$userPalette = [
    '#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', 
    '#06b6d4', '#f97316', '#6366f1', '#84cc16', '#d946ef', '#64748b'
];

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

        // จัดกลุ่มตามเวลา (8 หรือ 8_30)
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
            $colorIndex = $userId % count($userPalette);
            $assignedColor = $userPalette[$colorIndex];

            $calendar_events[] = [
                'id' => $log['id'],
                'title' => $displayTitle,
                'start' => $log['work_date'] . 'T' . $startT,
                'end' => $log['work_date'] . 'T' . $endT,
                'backgroundColor' => $assignedColor,
                'borderColor' => $assignedColor,
                'textColor' => '#ffffff',
                
                // ✅ ส่งข้อมูล User ID และเวลาดิบไปให้ JS ใช้เช็คสิทธิ์
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; }
        .fade-enter-active { transition: opacity 0.3s ease-out; }
        .fc { z-index: 1; }
        .fc-event { cursor: pointer; }
        .fc-day-today { background-color: rgba(99, 102, 241, 0.1) !important; }
        .modal-label { @apply text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1; }
        .modal-value { @apply text-sm text-slate-800 font-medium; }
    </style>
</head>

<body class="bg-slate-50 min-h-screen text-slate-800">

    <?php include './components/navbar.php'; ?>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">📝 บันทึกภาระงานประจำวัน</h1>
            <?php if ($message): ?><div class="mt-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded shadow-sm"><?php echo $message; ?></div><?php endif; ?>
            <?php if (!$isLoggedIn): ?><div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-700 rounded shadow-sm">⚠️ กรุณาเข้าสู่ระบบเพื่อเพิ่มหรือแก้ไขข้อมูล</div><?php endif; ?>
        </div>

        <div class="flex justify-center mb-8">
            <div class="bg-white p-1 rounded-xl shadow-sm border border-slate-200 inline-flex">
                <button onclick="switchView('table')" id="btn-view-table" class="px-6 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 flex items-center gap-2 bg-indigo-600 text-white shadow-md">มุมมองตาราง</button>
                <button onclick="switchView('calendar')" id="btn-view-calendar" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-slate-600 hover:text-indigo-600 transition-all duration-200 flex items-center gap-2">มุมมองปฏิทิน</button>
            </div>
        </div>

        <div id="view-table" class="<?= $defaultView === 'table' ? '' : 'hidden' ?> fade-enter-active">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
                
                <div class="px-6 py-5 bg-white border-b border-slate-200 flex flex-wrap gap-4 items-center justify-between sticky top-0 z-10 shadow-sm">
                    <form method="get" class="flex items-center gap-3 flex-wrap">
                        <input type="hidden" name="page" value="daily-works">
                        <input type="hidden" name="view" value="table">
                        <div class="flex items-center bg-slate-50 border border-slate-300 rounded-lg px-4 py-2 shadow-sm hover:border-indigo-400 transition-colors">
                            <span class="text-sm font-bold text-indigo-600 mr-2 uppercase tracking-wide">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                วันที่:
                            </span>
                            <select name="day" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer font-medium text-slate-700 hover:text-indigo-700"><?php for ($i = 1; $i <= 31; $i++): ?><option value="<?= $i ?>" <?= $i == $d ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select><span class="mx-1 text-slate-400">/</span>
                            <select name="month" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer font-medium text-slate-700 hover:text-indigo-700">
                                <?php 
                                $ms = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                                foreach ($ms as $i => $n): 
                                    $val = $i + 1; // ✅ บวก 1 เพื่อให้ค่าตรงกับ date('m')
                                ?>
                                    <option value="<?= $val ?>" <?= ($val == $m) ? 'selected' : '' ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="year" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer font-medium text-slate-700 hover:text-indigo-700"><?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?><option value="<?= $i ?>" <?= $i == $y ? 'selected' : '' ?>><?= $i + 543 ?></option><?php endfor; ?></select>
                        </div>
                    </form>
                </div>

                <form method="post">
                    <input type="hidden" name="work_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                    <input type="hidden" name="save_log_table" value="1">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider font-bold border-b border-slate-200">
                                    <th class="px-6 py-4 w-48 min-w-[150px]">ช่วงเวลา</th>
                                    <th class="px-6 py-4">รายละเอียดภาระงาน</th>
                                    <th class="px-6 py-4 w-64 min-w-[200px]">หมวดหมู่</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php
                                $timeSlots = [];
                                for ($i = 8; $i <= 16; $i++) {
                                    $timeSlots[] = (string)$i;
                                    // ✅ แสดงแถว 30 นาที เฉพาะเมื่อมีข้อมูลเท่านั้น
                                    if (isset($existing_logs[$i . '_30'])) {
                                        $timeSlots[] = $i . '_30';
                                    }
                                }

                                foreach ($timeSlots as $index => $h):
                                    $logsInHour = $existing_logs[$h] ?? [];
                                    $isHalf = str_contains($h, '_30');
                                    $mainLog = $logsInHour[0] ?? [];

                                    // คำนวณเวลาแสดงผล
                                    if (!empty($mainLog['start_time'])) {
                                        $showStart = date('H:i', strtotime($mainLog['start_time']));
                                        $showEnd = !empty($mainLog['end_time']) ? date('H:i', strtotime($mainLog['end_time'])) : sprintf("%02d:00", intval($h) + 1);
                                    } else {
                                        $val = intval($h);
                                        if ($isHalf) {
                                            $showStart = sprintf("%02d:30", $val);
                                            $showEnd = sprintf("%02d:00", $val + 1);
                                        } else {
                                            $showStart = sprintf("%02d:00", $val);
                                            $showEnd = sprintf("%02d:00", $val + 1);
                                        }
                                    }
                                    $rowClass = ($index % 2 == 0) ? 'bg-white' : 'bg-slate-50/60';
                                ?>
                                    <tr class="<?= $rowClass ?> hover:bg-indigo-50/40 transition-colors group">
                                        <td class="px-6 py-5 align-top border-r border-slate-100">
                                            <div class="flex flex-col items-start justify-center h-full pt-1">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-2 h-2 rounded-full <?= !empty($logsInHour) ? 'bg-indigo-500 ring-4 ring-indigo-100' : 'bg-slate-300' ?>"></div>
                                                    <span class="text-lg font-bold text-slate-700 font-mono tracking-tight"><?= $showStart ?></span>
                                                </div>
                                                <div class="pl-[1.2rem] border-l-2 border-indigo-100 ml-[0.24rem] py-1 my-1">
                                                    <span class="text-xs font-medium text-slate-400 block px-2">ถึง</span>
                                                </div>
                                                <div class="flex items-center gap-2 opacity-60">
                                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-300 ml-[0.08rem]"></div>
                                                    <span class="text-sm font-semibold text-slate-500 font-mono tracking-tight"><?= $showEnd ?></span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 align-top">
                                            <?php if ($isLoggedIn): ?>
                                                <div class="flex flex-col gap-4">
                                                    <?php if (!empty($logsInHour)): ?>
                                                        <?php foreach ($logsInHour as $entry): ?>
                                                            <div class="relative w-full">
                                                                <textarea name="logs_update[<?= $entry['id'] ?>][activity]" rows="2" class="w-full border-0 bg-transparent p-0 text-slate-800 placeholder:text-slate-300 focus:ring-0 focus:border-indigo-500 sm:text-sm resize-none leading-relaxed"><?= htmlspecialchars($entry['activity_detail']) ?></textarea>
                                                                <div class="absolute bottom-0 left-0 right-0 h-px bg-slate-200 group-hover:bg-indigo-200 transition-colors"></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="relative w-full">
                                                            <textarea name="logs_new[<?= $h ?>][activity]" rows="2" placeholder="ระบุรายละเอียดงาน..." class="w-full border-0 bg-transparent p-0 text-slate-800 placeholder:text-slate-300 focus:ring-0 focus:border-indigo-500 sm:text-sm resize-none leading-relaxed"></textarea>
                                                            <div class="absolute bottom-0 left-0 right-0 h-px bg-slate-200 group-hover:bg-indigo-200 transition-colors"></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <?php if (!empty($logsInHour)): ?>
                                                    <div class="flex flex-col gap-3">
                                                        <?php foreach ($logsInHour as $entry): ?>
                                                            <div class="bg-white/50 border border-slate-100 p-3 rounded-lg shadow-sm">
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

                                        <td class="px-6 py-4 align-top w-64 min-w-[200px]">
                                            <?php if ($isLoggedIn): ?>
                                                <div class="flex flex-col gap-4">
                                                    <?php if (!empty($logsInHour)): ?>
                                                        <?php foreach ($logsInHour as $entry): ?>
                                                            <div class="relative pt-1 h-[3.5rem] flex items-start">
                                                                <select name="logs_update[<?= $entry['id'] ?>][category_id]" class="w-full bg-slate-50 border border-slate-200 text-slate-600 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 transition-all hover:bg-white hover:shadow-sm">
                                                                    <option value="">-- หมวดหมู่ --</option>
                                                                    <?php foreach ($categories as $cat): ?>
                                                                        <option value="<?= $cat['id'] ?>" <?= (($entry['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= $cat['name_th'] ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="relative pt-1">
                                                            <select name="logs_new[<?= $h ?>][category_id]" class="w-full bg-slate-50 border border-slate-200 text-slate-600 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 transition-all hover:bg-white hover:shadow-sm">
                                                                <option value="">-- หมวดหมู่ --</option>
                                                                <?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= $cat['name_th'] ?></option><?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex flex-col gap-3">
                                                    <?php if (!empty($logsInHour)): ?>
                                                        <?php foreach ($logsInHour as $entry): ?>
                                                            <div class="h-[3.5rem] flex items-start pt-3">
                                                                <?php if (!empty($entry['category_name'])): ?>
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 border border-indigo-200"><?= htmlspecialchars($entry['category_name']) ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-slate-300 text-xs">-</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-slate-300 text-sm">-</span>
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
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between sticky bottom-0 z-10">
                            <span class="text-xs text-slate-400 hidden sm:inline">* ลบข้อความให้ว่างเพื่อลบรายการ</span>
                            <button type="submit" class="bg-indigo-600 text-white px-8 py-2.5 rounded-lg hover:bg-indigo-700 shadow-lg font-medium">บันทึกข้อมูล</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div id="view-calendar" class="<?= $defaultView === 'calendar' ? '' : 'hidden' ?> fade-enter-active">
            <div class="bg-white p-6 rounded-2xl shadow-xl border border-slate-100">
                <div id='calendar'></div>
            </div>
        </div>
    </div>

    <div id="calendarModal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full z-[10000]">
                <div class="bg-indigo-600 px-4 py-4"><h3 class="text-lg font-bold text-white">📅 เพิ่มกิจกรรมใหม่</h3></div>
                <form action="" method="POST" class="p-6">
                    <input type="hidden" name="save_log_calendar" value="1">
                    <input type="hidden" name="work_date" id="m_work_date">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div><label class="text-sm font-medium">เริ่ม</label><input type="time" name="start_time" id="m_start_time" required class="w-full border p-2 rounded"></div>
                        <div><label class="text-sm font-medium">สิ้นสุด</label><input type="time" name="end_time" id="m_end_time" required class="w-full border p-2 rounded"></div>
                    </div>
                    <div class="mb-4"><label class="text-sm font-medium">รายละเอียด</label><textarea name="activity_detail" rows="3" required class="w-full border p-2 rounded"></textarea></div>
                    <div class="mb-4"><label class="text-sm font-medium">หมวดหมู่</label><select name="category_id" class="w-full border p-2 rounded"><option value="">-- เลือก --</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= $cat['name_th'] ?></option><?php endforeach; ?></select></div>
                    <div class="flex justify-end gap-2 border-t pt-4"><button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-100 rounded">ยกเลิก</button><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">บันทึก</button></div>
                </form>
            </div>
        </div>
    </div>

    <div id="eventDetailModal" class="hidden fixed inset-0 z-[10000] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeDetailModal()"></div>
            
            <form action="" method="POST" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full relative">
                <input type="hidden" name="calendar_action" id="detail_action" value="edit">
                <input type="hidden" name="log_id" id="detail_id">
                <input type="hidden" name="work_date" id="detail_date_input">

                <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">📌 รายละเอียดกิจกรรม</h3>
                    <button type="button" onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <p class="modal-label">ผู้บันทึกข้อมูล</p>
                        <div class="flex items-center gap-2">
                            <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-full"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></span>
                            <span class="modal-value" id="detail_creator">...</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="modal-label">เวลาเริ่ม</p><input type="time" name="start_time" id="detail_start" class="w-full border-slate-200 rounded-md text-sm text-slate-700 bg-slate-50" disabled></div>
                        <div><p class="modal-label">เวลาสิ้นสุด</p><input type="time" name="end_time" id="detail_end" class="w-full border-slate-200 rounded-md text-sm text-slate-700 bg-slate-50" disabled></div>
                    </div>
                    <div><p class="modal-label">หมวดหมู่</p><select name="category_id" id="detail_category" class="w-full border-slate-200 rounded-md text-sm text-slate-700 bg-slate-50 disabled:opacity-75" disabled><option value="">-- ไม่ระบุ --</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= $cat['name_th'] ?></option><?php endforeach; ?></select></div>
                    <div><p class="modal-label">รายละเอียดงาน</p><textarea name="activity_detail" id="detail_desc" rows="3" class="w-full border-slate-200 rounded-md text-sm text-slate-700 bg-slate-50 disabled:resize-none" disabled></textarea></div>
                </div>

                <div class="bg-slate-50 px-4 py-3 sm:px-6 flex flex-row-reverse gap-2 border-t border-slate-100">
                    <button type="button" onclick="closeDetailModal()" class="inline-flex justify-center rounded-lg border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 sm:text-sm">ปิด</button>
                    <button type="submit" id="btn_save_edit" class="hidden inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:text-sm">บันทึกแก้ไข</button>
                    <button type="button" id="btn_delete_event" onclick="confirmDelete()" class="hidden inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:text-sm">ลบ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
        const currentUserId = <?= isset($user['id']) ? (int)$user['id'] : 0 ?>;
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
            const inactiveClass = "text-slate-600 hover:text-indigo-600";
            if (btnTable) btnTable.className = `px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition-all ${viewName === 'table'?activeClass:inactiveClass}`;
            if (btnCal) btnCal.className = `px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition-all ${viewName === 'calendar'?activeClass:inactiveClass}`;

            const url = new URL(window.location);
            url.searchParams.set('view', viewName);
            window.history.pushState({}, '', url);
        }

        <?php if ($isLoggedIn): ?>
            function openModal(dateStr, startTime = '09:00', endTime = '10:00') {
                document.getElementById('m_work_date').value = dateStr;
                document.getElementById('m_start_time').value = startTime;
                document.getElementById('m_end_time').value = endTime;
                document.getElementById('calendarModal').classList.remove('hidden');
            }
            function closeModal() {
                document.getElementById('calendarModal').classList.add('hidden');
            }
        <?php endif; ?>

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

            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'th',
                initialView: 'dayGridMonth',
                firstDay: 1,
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
                buttonText: { today: 'วันนี้', month: 'เดือน', week: 'สัปดาห์' },
                events: calendarEvents,
                selectable: isLoggedIn,
                editable: false,
                dateClick: function(info) { if (isLoggedIn) openModal(info.dateStr); },
                select: function(info) {
                    if (isLoggedIn) {
                        const st = info.start.toTimeString().substring(0, 5);
                        const et = info.end ? info.end.toTimeString().substring(0, 5) : st;
                        openModal(info.startStr.split('T')[0], st, et);
                    }
                },
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    const eventId = info.event.id;
                    const isOwner = (parseInt(props.user_id) === currentUserId);

                    document.getElementById('detail_id').value = eventId;
                    document.getElementById('detail_date_input').value = props.date_raw;
                    document.getElementById('detail_creator').textContent = props.creator;
                    document.getElementById('detail_start').value = props.start_raw;
                    document.getElementById('detail_end').value = props.end_raw;
                    document.getElementById('detail_desc').value = props.detail;
                    document.getElementById('detail_category').value = props.category_id || "";

                    // ควบคุมสิทธิ์การแก้ไข
                    const inputs = ['detail_start', 'detail_end', 'detail_desc', 'detail_category'];
                    const saveBtn = document.getElementById('btn_save_edit');
                    const delBtn = document.getElementById('btn_delete_event');

                    if (isOwner && isLoggedIn) {
                        inputs.forEach(id => {
                            const el = document.getElementById(id);
                            el.disabled = false;
                            el.classList.remove('bg-slate-50', 'border-transparent');
                            el.classList.add('bg-white', 'border-slate-300');
                        });
                        saveBtn.classList.remove('hidden');
                        delBtn.classList.remove('hidden');
                    } else {
                        inputs.forEach(id => {
                            const el = document.getElementById(id);
                            el.disabled = true;
                            el.classList.add('bg-slate-50', 'border-transparent');
                            el.classList.remove('bg-white', 'border-slate-300');
                        });
                        saveBtn.classList.add('hidden');
                        delBtn.classList.add('hidden');
                    }
                    document.getElementById('eventDetailModal').classList.remove('hidden');
                }
            });

            switchView(initialView);
        });
    </script>
</body>
</html>