<?php
date_default_timezone_set('Asia/Bangkok');
global $pdo;

/* ====== SETUP & INIT ====== */
$y = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$m = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$d = isset($_GET['day']) ? (int)$_GET['day'] : (int)date('d');

if (!checkdate($m, $d, $y)) {
    $y = (int)date('Y');
    $m = (int)date('m');
    $d = (int)date('d');
}

$selected_date = sprintf('%04d-%02d-%02d', $y, $m, $d);
$message = '';

$isLoggedIn = isset($user) && isset($user['id']);
$isToday = ($selected_date === date('Y-m-d'));
$canEdit = $isLoggedIn && $isToday;

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
// (Logic Save Table & Calendar เดิม ไม่เปลี่ยนแปลง)
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

                // 1. จัดการรายการเดิม (แก้ไข หรือ ลบ)
                $stmtUpdate = $pdo->prepare("UPDATE daily_work_logs SET activity_detail = :detail, category_id = :catid, updated_at = NOW() WHERE id = :id AND user_id = :uid");
                $stmtDelete = $pdo->prepare("DELETE FROM daily_work_logs WHERE id = :id AND user_id = :uid");

                foreach ($logs_update as $id => $data) {
                    $activity = trim($data['activity'] ?? '');
                    $category_id = $data['category_id'] ?? '';
                    $cat_db = ($category_id !== '' && isset($allowedCatIds[(string)$category_id])) ? (int)$category_id : null;

                    if ($activity === '') {
                        // ถ้าเคลียร์ข้อความ = ลบทิ้ง
                        $stmtDelete->execute([':id' => $id, ':uid' => $user['id']]);
                    } else {
                        // ถ้ามีข้อความ = อัปเดต
                        $stmtUpdate->execute([
                            ':detail' => $activity,
                            ':catid'  => $cat_db,
                            ':id'     => $id,
                            ':uid'    => $user['id']
                        ]);
                    }
                }

                // 2. จัดการรายการใหม่ (Insert)
                // ✅ แก้ไข: ลบ start_hour และ :sh ออกจากคำสั่ง SQL
                $stmtInsert = $pdo->prepare("INSERT INTO daily_work_logs (user_id, work_date, start_time, end_time, activity_detail, category_id) VALUES (:uid, :wdate, :stime, :etime, :detail, :catid)");

                foreach ($logs_new as $timeKey => $data) {
                    $activity = trim($data['activity'] ?? '');
                    if ($activity === '') continue; // ข้ามถ้าไม่ได้กรอก

                    $category_id = $data['category_id'] ?? '';
                    $cat_db = ($category_id !== '' && isset($allowedCatIds[(string)$category_id])) ? (int)$category_id : null;

                    // แปลง Key เวลา
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

                    $stmtInsert->execute([
                        ':uid'    => $user['id'],
                        ':wdate'  => $work_date,
                        ':stime'  => $startTimeStr,
                        ':etime'  => $endTimeStr,
                        ':detail' => $activity,
                        ':catid'  => $cat_db
                        // ✅ แก้ไข: ลบ ':sh' => $hour ออก เพราะ Database จะคำนวณเอง
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
    '#ef4444', // Red (แดง)
    '#3b82f6', // Blue (น้ำเงิน)
    '#10b981', // Emerald (เขียวหยก)
    '#f59e0b', // Amber (เหลืองอมส้ม)
    '#8b5cf6', // Violet (ม่วง)
    '#ec4899', // Pink (ชมพู)
    '#06b6d4', // Cyan (ฟ้าทะเล)
    '#f97316', // Orange (ส้ม)
    '#6366f1', // Indigo (คราม)
    '#84cc16', // Lime (เขียวมะนาว)
    '#d946ef', // Fuchsia (บานเย็น)
    '#64748b', // Slate (เทาอมฟ้า)
];

if (isset($pdo)) {

    // --- 1. TABLE VIEW (ดึงข้อมูลใส่ตาราง) ---
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
            $h = (int)$hh;
            $m = (int)$mm;
        } else {
            $h = (int)($row['start_hour'] ?? 0);
            $m = 0;
        }

        if ($h <= 0) continue;

        // สร้าง Key เดียวที่แม่นยำ
        // ถ้านาทีเป็น 30 ให้ใช้ key '8_30' ถ้านาทีเป็น 0 ให้ใช้ key '8'
        $keyHour = ($m === 30) ? $h . '_30' : (string)$h;

        $existing_logs[$keyHour][] = $row;
    }



    // --- 2. CALENDAR VIEW (ดึงข้อมูลใส่ปฏิทิน) ---
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

            // ดึงค่าสีออกมา
            $assignedColor = $userPalette[$colorIndex];

            $calendar_events[] = [
                'id' => $log['id'],
                'title' => $displayTitle,
                'start' => $log['work_date'] . 'T' . $startT,
                'end' => $log['work_date'] . 'T' . $endT,
                'backgroundColor' => $assignedColor,
                'borderColor' => $assignedColor,
                'textColor' => '#ffffff',

                'extendedProps' => [
                    'creator' => $creatorName,
                    'detail' => $log['activity_detail'],
                    'category' => $log['category_name'] ?? 'ไม่ระบุ',
                    'date_th' => $log['work_date']
                ]
            ];
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'saved') $message = "✅ บันทึกข้อมูลเรียบร้อยแล้ว";
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
        body {
            font-family: 'Prompt', sans-serif;
        }

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

        /* ปรับแต่ง Modal นิดหน่อย */
        .modal-label {
            @apply text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1;
        }

        .modal-value {
            @apply text-sm text-slate-800 font-medium;
        }
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
                            <select name="month" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer font-medium text-slate-700 hover:text-indigo-700"><?php $ms = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                                                                                                                                                                                    foreach ($ms as $i => $n): ?><option value="<?= $i + 1 ?>" <?= $i + 1 == $m ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select><span class="mx-1 text-slate-400">/</span>
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
                            <?php ksort($existing_logs); ?>

                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php
                                $timeSlots = [];
                                // สร้าง Loop เวลา 8-16 และบังคับใส่ช่วง 30 นาทีลงไปทุกชั่วโมง
                                for ($i = 8; $i <= 16; $i++) {
                                    $timeSlots[] = (string)$i;
                                    if ($i < 16) {
                                        $timeSlots[] = $i . '_30';
                                    }
                                }

                                foreach ($timeSlots as $index => $h):
                                    $logsInHour = $existing_logs[$h] ?? [];
                                    $isHalf = str_contains($h, '_30');

                                    // กรองข้อมูล: ถ้าเป็นชั่วโมงเต็ม (เช่น 8) ให้กรองเอาเฉพาะนาที 00
                                    // (ส่วนนาที 30 จะไปอยู่ในรอบ $h ที่เป็น '8_30' เองโดยอัตโนมัติจาก Logic Part 2)
                                    // ดังนั้นตรงนี้ไม่ต้อง filter อะไรเพิ่มแล้วถ้า Part 2 ถูกต้อง

                                    // ข้อมูลตัวแรก (ถ้ามี) เอาไว้ใช้ดูเวลา start/end
                                    $mainLog = $logsInHour[0] ?? [];

                                    // คำนวณเวลาที่จะแสดงผล
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
                                                    <span class="text-lg font-bold text-slate-700 font-mono tracking-tight">
                                                        <?= $showStart ?>
                                                    </span>
                                                </div>
                                                <div class="pl-[1.2rem] border-l-2 border-indigo-100 ml-[0.24rem] py-1 my-1">
                                                    <span class="text-xs font-medium text-slate-400 block px-2">ถึง</span>
                                                </div>
                                                <div class="flex items-center gap-2 opacity-60">
                                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-300 ml-[0.08rem]"></div>
                                                    <span class="text-sm font-semibold text-slate-500 font-mono tracking-tight">
                                                        <?= $showEnd ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 align-top">
                                            <?php if ($isLoggedIn): ?>
                                                <div class="flex flex-col gap-4">
                                                    <?php if (!empty($logsInHour)): ?>
                                                        <?php foreach ($logsInHour as $entry): ?>
                                                            <div class="relative w-full">
                                                                <textarea
                                                                    name="logs_update[<?= $entry['id'] ?>][activity]"
                                                                    rows="2"
                                                                    class="w-full border-0 bg-transparent p-0 text-slate-800 placeholder:text-slate-300 focus:ring-0 focus:border-indigo-500 sm:text-sm resize-none leading-relaxed"><?= htmlspecialchars($entry['activity_detail']) ?></textarea>
                                                                <div class="absolute bottom-0 left-0 right-0 h-px bg-slate-200 group-hover:bg-indigo-200 transition-colors"></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="relative w-full">
                                                            <textarea
                                                                name="logs_new[<?= $h ?>][activity]"
                                                                rows="2"
                                                                placeholder="ระบุรายละเอียดงาน..."
                                                                class="w-full border-0 bg-transparent p-0 text-slate-800 placeholder:text-slate-300 focus:ring-0 focus:border-indigo-500 sm:text-sm resize-none leading-relaxed"></textarea>
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
                                                                    <div class="text-xs text-indigo-600 font-bold mb-1">
                                                                        <?= htmlspecialchars($entry['display_th']) ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <p class="text-sm text-slate-700">
                                                                    <?= htmlspecialchars($entry['activity_detail']) ?>
                                                                </p>
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
                                                                        <option value="<?= $cat['id'] ?>" <?= (($entry['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                                                            <?= $cat['name_th'] ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="relative pt-1">
                                                            <select name="logs_new[<?= $h ?>][category_id]" class="w-full bg-slate-50 border border-slate-200 text-slate-600 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 transition-all hover:bg-white hover:shadow-sm">
                                                                <option value="">-- หมวดหมู่ --</option>
                                                                <?php foreach ($categories as $cat): ?>
                                                                    <option value="<?= $cat['id'] ?>">
                                                                        <?= $cat['name_th'] ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex flex-col gap-3">
                                                    <?php if (!empty($logsInHour)): ?>
                                                        <?php foreach ($logsInHour as $entry): ?>
                                                            <div class="h-[3.5rem] flex items-start pt-3"> <?php if (!empty($entry['category_name'])): ?>
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 border border-indigo-200">
                                                                        <?= htmlspecialchars($entry['category_name']) ?>
                                                                    </span>
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
                            <span class="text-xs text-slate-400 hidden sm:inline">* ระบบจะบันทึกเฉพาะช่วงเวลามาตรฐาน (ชั่วโมงเต็ม) ข้อมูลช่วงเวลาย่อยจะถูกข้าม</span>
                            <button type="submit" class="bg-indigo-600 text-white px-8 py-2.5 rounded-lg hover:bg-indigo-700 shadow-lg shadow-indigo-200 font-medium transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                                <span class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    บันทึกข้อมูล
                                </span>
                            </button>
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
                <div class="bg-indigo-600 px-4 py-4">
                    <h3 class="text-lg font-bold text-white">📅 เพิ่มกิจกรรมใหม่</h3>
                </div>
                <form action="" method="POST" class="p-6">
                    <input type="hidden" name="save_log_calendar" value="1">
                    <input type="hidden" name="work_date" id="m_work_date">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div><label class="text-sm font-medium">เริ่ม</label><input type="time" name="start_time" id="m_start_time" required class="w-full border p-2 rounded"></div>
                        <div><label class="text-sm font-medium">สิ้นสุด</label><input type="time" name="end_time" id="m_end_time" required class="w-full border p-2 rounded"></div>
                    </div>
                    <div class="mb-4"><label class="text-sm font-medium">รายละเอียด</label><textarea name="activity_detail" rows="3" required class="w-full border p-2 rounded"></textarea></div>
                    <div class="mb-4"><label class="text-sm font-medium">หมวดหมู่</label><select name="category_id" class="w-full border p-2 rounded">
                            <option value="">-- เลือก --</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= $cat['name_th'] ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="flex justify-end gap-2 border-t pt-4"><button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-100 rounded">ยกเลิก</button><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">บันทึก</button></div>
                </form>
            </div>
        </div>
    </div>

    <div id="eventDetailModal" class="hidden fixed inset-0 z-[10000] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeDetailModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full relative">

                <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        📌 รายละเอียดกิจกรรม
                    </h3>
                    <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <p class="modal-label">ผู้บันทึกข้อมูล</p>
                        <div class="flex items-center gap-2">
                            <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </span>
                            <span class="modal-value" id="detail_creator">...</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="modal-label">วันที่</p>
                            <p class="modal-value" id="detail_date">...</p>
                        </div>
                        <div>
                            <p class="modal-label">เวลา</p>
                            <p class="modal-value" id="detail_time">...</p>
                        </div>
                    </div>

                    <div>
                        <p class="modal-label">หมวดหมู่</p>
                        <span id="detail_category" class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                            ...
                        </span>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                        <p class="modal-label">รายละเอียดงาน</p>
                        <div class="max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                            <p class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed break-words" id="detail_desc">...</p>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-100">
                    <button type="button" onclick="closeDetailModal()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-slate-800 text-base font-medium text-white hover:bg-slate-900 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        ปิดหน้าต่าง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
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

        // ฟังก์ชันปิด Modal รายละเอียด
        function closeDetailModal() {
            document.getElementById('eventDetailModal').classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const initialView = urlParams.get('view') || (isLoggedIn ? 'table' : 'calendar');
            const calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'th',
                initialView: 'dayGridMonth',

                // ✅ 3. เริ่มต้นสัปดาห์วันจันทร์ (0=อาทิตย์, 1=จันทร์)
                firstDay: 1,

                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                buttonText: {
                    today: 'วันนี้',
                    month: 'เดือน',
                    week: 'สัปดาห์'
                },
                events: calendarEvents,
                selectable: isLoggedIn,
                editable: false,

                dateClick: function(info) {
                    if (isLoggedIn) openModal(info.dateStr);
                },
                select: function(info) {
                    if (isLoggedIn) {
                        const st = info.start.toTimeString().substring(0, 5);
                        const et = info.end ? info.end.toTimeString().substring(0, 5) : st;
                        openModal(info.startStr.split('T')[0], st, et);
                    }
                },

                // ✅ 4. เปลี่ยน Alert เป็น Modal สวยๆ
                eventClick: function(info) {
                    const props = info.event.extendedProps;

                    // 1. ดึง Object วันที่จาก FullCalendar โดยตรง
                    const startDate = info.event.start;
                    const endDate = info.event.end; // ค่านี้อาจเป็น null ได้ถ้าไม่ได้ระบุจบ

                    // 2. แปลงวันที่เป็นไทย
                    const dateStr = startDate.toLocaleDateString('th-TH', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        weekday: 'long'
                    });

                    // 3. ฟังก์ชันจัดรูปแบบเวลา (ดึงจาก Object Date โดยตรง ไม่ต้องพึ่ง PHP string)
                    const formatTime = (dateObj) => {
                        return dateObj.toLocaleTimeString('th-TH', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    };

                    const sTime = formatTime(startDate);
                    // ถ้ามีเวลาจบ ให้แสดงช่วงเวลา ถ้าไม่มีให้แสดงเวลาเริ่มอย่างเดียว
                    const timeDisplay = endDate ?
                        `${sTime} - ${formatTime(endDate)} น.` :
                        `${sTime} น.`;

                    // 4. ใส่ข้อมูลลง Modal
                    document.getElementById('detail_creator').textContent = props.creator;
                    document.getElementById('detail_date').textContent = dateStr;
                    document.getElementById('detail_time').textContent = timeDisplay; // ✅ แสดงเวลาที่คำนวณแล้ว
                    document.getElementById('detail_category').textContent = props.category;
                    document.getElementById('detail_desc').textContent = props.detail;

                    // ปรับสี Badge หมวดหมู่
                    const catEl = document.getElementById('detail_category');
                    if (props.category !== 'ไม่ระบุ') {
                        catEl.className = "inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 border border-indigo-200";
                    } else {
                        catEl.className = "inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200";
                    }

                    document.getElementById('eventDetailModal').classList.remove('hidden');
                }
            });

            switchView(initialView);
        });
    </script>
</body>

</html>