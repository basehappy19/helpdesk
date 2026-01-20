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
        $logs = $_POST['logs'] ?? [];
        try {
            if (isset($pdo)) {
                $stmtCheck = $pdo->prepare("SELECT id FROM daily_work_logs WHERE user_id = :uid AND work_date = :wdate AND start_hour = :sh");
                $stmtInsert = $pdo->prepare("INSERT INTO daily_work_logs (user_id, work_date, start_time, end_time, activity_detail, category_id) VALUES (:uid, :wdate, :stime, :etime, :detail, :catid)");
                $stmtUpdate = $pdo->prepare("UPDATE daily_work_logs SET activity_detail = :detail, category_id = :catid, updated_at = NOW() WHERE id = :id");
                $stmtDelete = $pdo->prepare("DELETE FROM daily_work_logs WHERE id = :id");
                $pdo->beginTransaction();
                for ($h = 8; $h <= 16; $h++) {
                    $activity = trim($logs[$h]['activity'] ?? '');
                    $category_id = $logs[$h]['category_id'] ?? '';
                    $cat_db = ($category_id !== '' && isset($allowedCatIds[(string)$category_id])) ? (int)$category_id : null;
                    $stmtCheck->execute([':uid' => $user['id'], ':wdate' => $work_date, ':sh' => $h]);
                    $existingRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                    if ($existingRow) {
                        if ($activity === '') $stmtDelete->execute([':id' => $existingRow['id']]);
                        else $stmtUpdate->execute([':detail' => $activity, ':catid' => $cat_db, ':id' => $existingRow['id']]);
                    } else {
                        if ($activity !== '') {
                            $stmtInsert->execute([':uid' => $user['id'], ':wdate' => $work_date, ':stime' => sprintf("%02d:00:00", $h), ':etime' => sprintf("%02d:00:00", $h + 1), ':detail' => $activity, ':catid' => $cat_db]);
                        }
                    }
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
        $h = 0;
        if (!empty($row['start_hour'])) $h = (int)$row['start_hour'];
        else if (!empty($row['start_time'])) $h = (int)explode(':', $row['start_time'])[0];
        if ($h > 0) $existing_logs[$h][] = $row;
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

            // ✅ 2. คำนวณสีตาม User ID (Logic ที่ขอมา)
            // เอา user_id มาแปลงเป็นตัวเลข (int)
            $userId = intval($log['user_id']);

            // หา index ของสี โดยการหารเอาเศษ (Modulo) กับจำนวนสีทั้งหมด
            // ผลลัพธ์จะได้เลขตั้งแต่ 0 ถึง 11 (ตามจำนวนสีใน $userPalette)
            // User 1 -> index 1, User 13 -> index 1 (สีเดิมวนลูป)
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
                <div class="px-6 py-5 bg-slate-50 border-b border-slate-200 flex flex-wrap gap-4 items-center justify-between">
                    <form method="get" class="flex items-center gap-3 flex-wrap">
                        <input type="hidden" name="page" value="daily-works">
                        <input type="hidden" name="view" value="table">
                        <div class="flex items-center bg-white border border-slate-300 rounded-lg px-3 py-2 shadow-sm">
                            <span class="text-sm font-medium text-slate-600 mr-2">วันที่:</span>
                            <select name="day" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer"><?php for ($i = 1; $i <= 31; $i++): ?><option value="<?= $i ?>" <?= $i == $d ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select><span class="mx-1">/</span>
                            <select name="month" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer"><?php $ms = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                                                                                                                                    foreach ($ms as $i => $n): ?><option value="<?= $i + 1 ?>" <?= $i + 1 == $m ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select><span class="mx-1">/</span>
                            <select name="year" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer"><?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?><option value="<?= $i ?>" <?= $i == $y ? 'selected' : '' ?>><?= $i + 543 ?></option><?php endfor; ?></select>
                        </div>
                    </form>

                    <button onclick="exportToPDF()" class="flex items-center gap-2 px-4 py-2 bg-rose-500 text-white rounded-lg hover:bg-rose-600 shadow-md transition-all text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export PDF
                    </button>
                </div>

                <form method="post">
                    <input type="hidden" name="work_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                    <input type="hidden" name="save_log_table" value="1">

                    <div id="printable-area" class="p-4 bg-white">

                        <div id="pdf-header" class="hidden mb-4 text-center">
                            <h2 class="text-xl font-bold text-slate-800">รายงานภาระงานประจำวัน</h2>
                            <p class="text-slate-600">วันที่: <?= date('d/m/Y', strtotime($selected_date)) ?></p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-700 text-sm border-b-2 border-slate-200">
                                        <th class="px-4 py-3 w-24 border">เวลา</th>
                                        <th class="px-4 py-3 border">รายละเอียด</th>
                                        <?php if ($isLoggedIn): ?><th class="px-4 py-3 w-64 border">หมวดหมู่</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php for ($h = 8; $h <= 16; $h++): $logsInHour = $existing_logs[$h] ?? []; ?>
                                        <tr class="border-b">
                                            <td class="px-4 py-3 align-top border bg-slate-50 font-bold text-slate-600 text-center"><?= sprintf("%02d:00", $h) ?></td>

                                            <?php if (!$isLoggedIn): ?>
                                                <td class="px-4 py-3 align-top border" colspan="2">
                                                    <?php if (empty($logsInHour)): ?><span class="text-slate-300 text-sm italic">- ว่าง -</span><?php else: ?>
                                                        <div class="flex flex-col gap-2">
                                                            <?php $i = 1;
                                                                                                                                                foreach ($logsInHour as $entry): ?>
                                                                <div class="text-sm">
                                                                    <div class="font-semibold text-indigo-700 mb-1">
                                                                        #<?= $i ?> <?= htmlspecialchars($entry['display_th']) ?>
                                                                        <?php if (!empty($entry['category_name'])): ?>
                                                                            <span class="ml-2 text-[10px] bg-slate-200 px-1.5 py-0.5 rounded text-slate-600"><?= htmlspecialchars($entry['category_name']) ?></span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="pl-4 text-slate-700 whitespace-pre-wrap break-words"><?= htmlspecialchars($entry['activity_detail']) ?></div>
                                                                </div>
                                                            <?php $i++;
                                                                                                                                                endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php else: ?>
                                                <?php $myLog = $logsInHour[0] ?? []; ?>
                                                <td class="px-4 py-3 align-top border">
                                                    <textarea name="logs[<?= $h ?>][activity]" class="w-full border p-2 rounded focus:ring-2 focus:ring-indigo-500 outline-none text-sm resize-none" rows="2"><?= htmlspecialchars($myLog['activity_detail'] ?? '') ?></textarea>
                                                </td>
                                                <td class="px-4 py-3 align-top border">
                                                    <select name="logs[<?= $h ?>][category_id]" class="w-full border p-2 rounded text-sm">
                                                        <option value="">--</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>" <?= (($myLog['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= $cat['name_th'] ?></option><?php endforeach; ?>
                                                    </select>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if ($canEdit): ?>
                        <div class="px-6 py-4 border-t flex justify-end bg-slate-50">
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 shadow-md transition-all">บันทึกข้อมูล</button>
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

        function exportToPDF() {
            const element = document.getElementById('printable-area');
            const header = document.getElementById('pdf-header');

            header.classList.remove('hidden');

            const opt = {
                margin: [10, 10, 10, 10],
                filename: 'WorkLog_<?= $selected_date ?>.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    useCORS: true
                }, // scale 2 เพื่อให้ภาพชัด
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };

            // 4. สั่งสร้าง PDF
            html2pdf().set(opt).from(element).save().then(() => {
                // 5. ซ่อนหัวกระดาษกลับเหมือนเดิมเมื่อเสร็จ
                header.classList.add('hidden');
            });
        }


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