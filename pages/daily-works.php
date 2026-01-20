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
// (ส่วน Logic การบันทึกคงเดิม แต่เพิ่มการเช็ค isLoggedIn ให้รัดกุม)

// 1.1 SAVE FROM TABLE VIEW (แบบรายชั่วโมง)
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
                $count_saved = 0;

                for ($h = 8; $h <= 16; $h++) {
                    $activity = trim($logs[$h]['activity'] ?? '');
                    $category_id = $logs[$h]['category_id'] ?? '';
                    $cat_db = ($category_id !== '' && isset($allowedCatIds[(string)$category_id])) ? (int)$category_id : null;

                    $stmtCheck->execute([':uid' => $user['id'], ':wdate' => $work_date, ':sh' => $h]);
                    $existingRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                    if ($existingRow) {
                        if ($activity === '') {
                            $stmtDelete->execute([':id' => $existingRow['id']]);
                        } else {
                            $stmtUpdate->execute([
                                ':detail' => $activity,
                                ':catid'  => $cat_db,
                                ':id'     => $existingRow['id']
                            ]);
                            $count_saved++;
                        }
                    } else {
                        if ($activity !== '') {
                            $startTimeStr = sprintf("%02d:00:00", $h);
                            $endTimeStr   = sprintf("%02d:00:00", $h + 1);

                            $stmtInsert->execute([
                                ':uid'    => $user['id'],
                                ':wdate'  => $work_date,
                                ':stime'  => $startTimeStr,
                                ':etime'  => $endTimeStr,
                                ':detail' => $activity,
                                ':catid'  => $cat_db
                            ]);
                            $count_saved++;
                        }
                    }
                }

                $pdo->commit();
                $message = "✅ บันทึกข้อมูลสำเร็จ";
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage());
        }
    }
}

// 1.2 SAVE FROM CALENDAR MODAL (แบบระบุเวลา)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_log_calendar'])) {
    if (!$isLoggedIn) die("Access Denied");

    $c_date = $_POST['work_date'];
    $c_start = $_POST['start_time'];
    $c_end = $_POST['end_time'];
    $c_detail = trim($_POST['activity_detail']);
    $c_cat = $_POST['category_id'] ?: null;

    try {
        if (isset($pdo)) {
            $sql = "INSERT INTO daily_work_logs 
                    (user_id, work_date, start_time, end_time, activity_detail, category_id)
                    VALUES (:uid, :wdate, :stime, :etime, :detail, :catid)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':uid' => $user['id'],
                ':wdate' => $c_date,
                ':stime' => $c_start,
                ':etime' => $c_end,
                ':detail' => $c_detail,
                ':catid' => $c_cat
            ]);

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

if ($isLoggedIn && isset($pdo)) {
    // Table Data
    $stmt_log = $pdo->prepare("SELECT * FROM daily_work_logs WHERE user_id = :uid AND work_date = :wdate");
    $stmt_log->execute([':uid' => $user['id'], ':wdate' => $selected_date]);
    foreach ($stmt_log->fetchAll(PDO::FETCH_ASSOC) as $log) {
        if (!empty($log['start_hour'])) {
            $existing_logs[(int)$log['start_hour']] = $log;
        } else if (!empty($log['start_time'])) {
            $h = (int)explode(':', $log['start_time'])[0];
            $existing_logs[$h] = $log;
        }
    }

    // Calendar Data
    $stmt_cal = $pdo->prepare("SELECT * FROM daily_work_logs WHERE user_id = :uid");
    $stmt_cal->execute([':uid' => $user['id']]);
    foreach ($stmt_cal->fetchAll(PDO::FETCH_ASSOC) as $log) {
        $startT = $log['start_time'];
        $endT = $log['end_time'];
        if (empty($startT) && !empty($log['start_hour'])) {
            $startT = sprintf("%02d:00:00", $log['start_hour']);
            $endT   = sprintf("%02d:00:00", $log['start_hour'] + 1);
        }
        if ($startT) {
            $calendar_events[] = [
                'id' => $log['id'],
                'title' => $log['activity_detail'],
                'start' => $log['work_date'] . 'T' . $startT,
                'end' => $log['work_date'] . 'T' . $endT,
                'backgroundColor' => '#4f46e5'
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

        /* ✅ FIX 2: ปรับ CSS FullCalendar ให้ไม่บัง Modal และแสดงผลถูกต้อง */
        .fc {
            z-index: 1;
        }

        .fc-event {
            cursor: pointer;
        }

        .fc-day-today {
            background-color: rgba(99, 102, 241, 0.1) !important;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen text-slate-800">

    <?php include './components/navbar.php'; ?>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">📝 บันทึกภาระงานประจำวัน</h1>

            <?php if ($message): ?>
                <div class="mt-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!$isLoggedIn): ?>
                <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-700 rounded shadow-sm">
                    ⚠️ กรุณาเข้าสู่ระบบเพื่อเพิ่มหรือแก้ไขข้อมูล
                </div>
            <?php endif; ?>
        </div>

        <div class="flex justify-center mb-8">
            <div class="bg-white p-1 rounded-xl shadow-sm border border-slate-200 inline-flex">
                <button onclick="switchView('table')" id="btn-view-table" class="px-6 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 flex items-center gap-2 bg-indigo-600 text-white shadow-md">
                    มุมมองตาราง
                </button>
                <button onclick="switchView('calendar')" id="btn-view-calendar" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-slate-600 hover:text-indigo-600 transition-all duration-200 flex items-center gap-2">
                    มุมมองปฏิทิน
                </button>
            </div>
        </div>

        <div id="view-table" class="fade-enter-active">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
                <div class="px-6 py-5 bg-slate-50 border-b border-slate-200 flex flex-wrap gap-4 items-center justify-between">
                    <form method="get" class="flex items-center gap-3 flex-wrap">
                        <input type="hidden" name="page" value="daily-works">
                        <input type="hidden" name="view" value="table">
                        <div class="flex items-center bg-white border border-slate-300 rounded-lg px-3 py-2 shadow-sm">
                            <select name="day" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer">
                                <?php for ($i = 1; $i <= 31; $i++): ?><option value="<?= $i ?>" <?= $i == $d ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?>
                            </select>
                            <span class="mx-1">/</span>
                            <select name="month" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer">
                                <?php $ms = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                                foreach ($ms as $i => $n): ?><option value="<?= $i + 1 ?>" <?= $i + 1 == $m ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
                            </select>
                            <span class="mx-1">/</span>
                            <select name="year" onchange="this.form.submit()" class="bg-transparent outline-none cursor-pointer">
                                <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?><option value="<?= $i ?>" <?= $i == $y ? 'selected' : '' ?>><?= $i + 543 ?></option><?php endfor; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <form method="post">
                    <input type="hidden" name="work_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                    <input type="hidden" name="save_log_table" value="1">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 text-sm border-b">
                                    <th class="px-6 py-4">เวลา</th>
                                    <th class="px-6 py-4">กิจกรรม</th>
                                    <th class="px-6 py-4">หมวดหมู่</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php for ($h = 8; $h <= 16; $h++): $log = $existing_logs[$h] ?? []; ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-3"><span class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded font-bold"><?= sprintf("%02d:00", $h) ?></span></td>
                                        <td class="px-6 py-3"><input type="text" name="logs[<?= $h ?>][activity]" value="<?= htmlspecialchars($log['activity_detail'] ?? '') ?>" class="w-full border p-2 rounded" <?= $canEdit ? '' : 'readonly' ?>></td>
                                        <td class="px-6 py-3">
                                            <select name="logs[<?= $h ?>][category_id]" class="w-full border p-2 rounded" <?= $canEdit ? '' : 'disabled' ?>>
                                                <option value="">--</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= $cat['id'] ?>" <?= (($log['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= $cat['name_th'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($canEdit): ?>
                        <div class="px-6 py-4 border-t flex justify-end">
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">บันทึกข้อมูล</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div id="view-calendar" class="hidden fade-enter-active">
            <div class="bg-white p-6 rounded-2xl shadow-xl border border-slate-100">
                <div id='calendar'></div>
            </div>
        </div>
    </div>

    <div id="calendarModal" class="hidden fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full z-[10000]">
                <div class="bg-indigo-600 px-4 py-4 sm:px-6">
                    <h3 class="text-lg leading-6 font-bold text-white">📅 เพิ่มกิจกรรมใหม่</h3>
                    <p class="text-indigo-200 text-sm mt-1" id="modal_date_display">...</p>
                </div>

                <form action="" method="POST" class="p-6">
                    <input type="hidden" name="save_log_calendar" value="1">
                    <input type="hidden" name="work_date" id="m_work_date">

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-sm font-medium mb-1">เวลาเริ่ม</label><input type="time" name="start_time" id="m_start_time" required class="w-full border p-2 rounded"></div>
                        <div><label class="block text-sm font-medium mb-1">เวลาสิ้นสุด</label><input type="time" name="end_time" id="m_end_time" required class="w-full border p-2 rounded"></div>
                    </div>
                    <div class="mb-4"><label class="block text-sm font-medium mb-1">รายละเอียด</label><textarea name="activity_detail" rows="3" required class="w-full border p-2 rounded"></textarea></div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-1">หมวดหมู่</label>
                        <select name="category_id" class="w-full border p-2 rounded">
                            <option value="">-- เลือก --</option>
                            <?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= $cat['name_th'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-100 rounded">ยกเลิก</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded shadow">บันทึก</button>
                    </div>
                </form>
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
                tableView.classList.remove('hidden');
                calView.classList.add('hidden');
                btnTable.className = "px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 bg-indigo-600 text-white shadow-md";
                btnCal.className = "px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 text-slate-600 hover:text-indigo-600";
            } else {
                tableView.classList.add('hidden');
                calView.classList.remove('hidden');
                btnCal.className = "px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 bg-indigo-600 text-white shadow-md";
                btnTable.className = "px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 text-slate-600 hover:text-indigo-600";
                if (calendar) calendar.render();
            }

            const url = new URL(window.location);
            url.searchParams.set('view', viewName);
            window.history.pushState({}, '', url);
        }

        /* ✅ FIX 4: เพิ่มการตรวจสอบ Login ใน JS */
        function checkAuthAndOpen(callback) {
            if (!isLoggedIn) {
                alert('⚠️ กรุณาเข้าสู่ระบบก่อนเพิ่มกิจกรรม');
                return;
            }
            callback();
        }

        function openModal(dateStr, startTime = '09:00', endTime = '10:00') {
            const modal = document.getElementById('calendarModal');
            document.getElementById('m_work_date').value = dateStr;
            document.getElementById('m_start_time').value = startTime;
            document.getElementById('m_end_time').value = endTime;

            const d = new Date(dateStr);
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                weekday: 'long'
            };
            document.getElementById('modal_date_display').textContent = d.toLocaleDateString('th-TH', options);

            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('calendarModal').classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const initialView = urlParams.get('view') || 'table';

            const calendarEl = document.getElementById('calendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'th',
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'วันนี้',
                    month: 'เดือน',
                    week: 'สัปดาห์',
                    day: 'วัน'
                },
                events: calendarEvents,
                selectable: true, 
                editable: false,

                dateClick: function(info) {
                    checkAuthAndOpen(() => openModal(info.dateStr));
                },

                select: function(info) {
                    checkAuthAndOpen(() => {
                        const st = info.start.toTimeString().substring(0, 5);
                        const et = info.end ? info.end.toTimeString().substring(0, 5) : st;
                        openModal(info.startStr.split('T')[0], st, et);
                    });
                },

                // คลิกที่ Event เดิม
                eventClick: function(info) {
                    alert(`📌 ${info.event.title}\n🕒 ${info.event.start.toLocaleTimeString('th-TH')}`);
                }
            });

            switchView(initialView);
        });
    </script>
</body>

</html>