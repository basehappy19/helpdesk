<?php
require_once __DIR__ . "../../functions/work_log_functions.php";
global $pdo;

$isLoggedIn = isset($user) && isset($user['id']);
$categories = getWorkLogCategories($pdo);

$events = [];
if ($isLoggedIn) {
    $raw_logs = getDailyWorkLogsForCalendar($pdo, $user['id']);
    foreach ($raw_logs as $log) {
        $events[] = [
            'id' => $log['id'],
            'title' => $log['activity_detail'],
            'start' => $log['work_date'] . 'T' . $log['start_time'],
            'end' => $log['work_date'] . 'T' . $log['end_time'],
            'backgroundColor' => '#6366f1',
            'borderColor' => '#4f46e5',
            'extendedProps' => [
                'category_id' => $log['category_id']
            ]
        ];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_save'])) {
    if (!$isLoggedIn) {
        die("กรุณาเข้าสู่ระบบ");
    }

    $user_id = $_SESSION['user']['id'];
    $work_date = $_POST['work_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $start_hour = (int) explode(':', $start_time)[0];
    $activity_detail = trim($_POST['activity_detail']);
    $category_id = $_POST['category_id'] ?? null;
    $category_id = ($category_id === '' ? null : $category_id);

    try {
        $sql = "INSERT INTO daily_work_logs
(user_id, work_date, start_time, end_time, activity_detail, category_id)
VALUES
(:uid, :wdate, :stime, :etime, :detail, :catid)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid'    => $user_id,
            ':wdate'  => $work_date,
            ':stime'  => $start_time,
            ':etime'  => $end_time,
            ':detail' => $activity_detail,
            ':catid'  => $category_id
        ]);


        header("Location: ./?page=daily-works");
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกงานประจำวัน - Helpdesk</title>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

        * {
            font-family: 'Prompt', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        :root {
            --fc-today-bg-color: rgba(99, 102, 241, 0.08);
            --fc-border-color: #e5e7eb;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e0e7ff 100%);
        }

        /* Modern Calendar Buttons */
        .fc .fc-button-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: none;
            letter-spacing: 0.025em;
        }

        .fc .fc-button-primary:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
            transform: translateY(-2px);
        }

        .fc .fc-button-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }

        .fc .fc-button-primary:disabled {
            opacity: 0.4;
            transform: none;
        }

        .fc .fc-button-active {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.4) !important;
        }

        /* Modern Toolbar */
        .fc .fc-toolbar-title {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.025em;
        }

        /* Modern Day Cells */
        .fc-daygrid-day {
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .fc-daygrid-day::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .fc-daygrid-day:hover::before {
            opacity: 1;
        }

        .fc-daygrid-day:hover {
            transform: scale(1.02);
            z-index: 10;
        }

        .fc-daygrid-day-number {
            padding: 10px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s ease;
        }

        .fc-day-today {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
            border: 2px solid #6366f1 !important;
        }

        .fc-day-today .fc-daygrid-day-number {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 6px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            font-weight: 700;
        }

        /* Modern Events */
        .fc-event {
            border-radius: 8px;
            padding: 4px 8px;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            margin-bottom: 3px;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
            transition: all 0.2s ease;
            color: white;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }

        .fc-event:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35);
        }

        .fc-event-title {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Modern Header */
        .fc .fc-col-header-cell {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            font-weight: 700;
            color: #475569;
            border-color: #e5e7eb;
            padding: 16px 8px;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .fc-daygrid-day-frame {
            min-height: 120px;
        }

        /* Glassmorphism Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        /* Modern Modal Animation */
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-content {
            animation: modalSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Input Focus Effects */
        .modern-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 12px;
            }

            .fc .fc-toolbar-title {
                font-size: 1.35rem;
            }

            .fc-daygrid-day-frame {
                min-height: 80px;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
    </style>
</head>

<body class="min-h-screen text-slate-800">

    <?php include './components/navbar.php'; ?>

    <div class="max-w-7xl mx-auto py-8 px-4">
        <!-- Modern Header -->
        <div class="mb-10 text-center">
            <div class="flex flex-col items-center">
                <div class="inline-flex items-center gap-3 mb-4">
                    <h1 class="text-4xl font-extrabold bg-gradient-to-r text-indigo-600">
                        บันทึกภาระงานประจำวัน
                    </h1>
                </div>

                <?php if (!$isLoggedIn): ?>
                    <div class="inline-flex items-center gap-2 bg-red-50 text-red-600 px-6 py-3 rounded-2xl text-sm font-semibold border-2 border-red-200 shadow-lg">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        กรุณาเข้าสู่ระบบเพื่อบันทึกงาน
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modern Calendar Card -->
        <div class="glass-card p-8 rounded-3xl shadow-2xl">
            <div id='calendar'></div>
        </div>
    </div>

    <!-- Modern Modal -->
    <div id="eventModal" class="hidden fixed inset-0 bg-gradient-to-br from-slate-900/70 via-purple-900/30 to-slate-900/70 backdrop-blur-xl z-50 flex items-center justify-center p-4">
        <div class="modal-content glass-card rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden border border-white/20">
            <!-- Modal Header -->
            <div class="relative bg-indigo-600 px-8 py-6">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative flex justify-between items-center">
                    <div>
                        <h3 class="text-white font-bold text-2xl mb-1">เพิ่มกิจกรรมใหม่</h3>
                        <p class="text-indigo-100 text-sm font-medium" id="modal_date_display"></p>
                    </div>
                    <button onclick="closeModal()" class="text-white/80 hover:text-white text-4xl leading-none transition-all hover:rotate-90 duration-300">
                        ×
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <form action="" method="POST" class="p-8 space-y-6">
                <input type="hidden" name="work_date" id="m_work_date">

                <!-- Time Inputs -->
                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            เวลาเริ่ม
                        </label>
                        <input type="time" name="start_time" id="m_start_time" required
                            class="modern-input w-full border-2 border-slate-200 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-slate-700">
                    </div>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            เวลาสิ้นสุด
                        </label>
                        <input type="time" name="end_time" id="m_end_time" required
                            class="modern-input w-full border-2 border-slate-200 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all font-semibold text-slate-700">
                    </div>
                </div>

                <!-- Activity Detail -->
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        รายละเอียดกิจกรรม
                    </label>
                    <textarea name="activity_detail" rows="4" required placeholder="ระบุรายละเอียดงานที่ทำ..."
                        class="modern-input w-full border-2 border-slate-200 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all resize-none font-medium text-slate-700"></textarea>
                </div>

                <!-- Category -->
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        หมวดหมู่
                    </label>
                    <select name="category_id"
                        class="modern-input w-full border-2 border-slate-200 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none appearance-none bg-white transition-all cursor-pointer font-semibold text-slate-700">
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name_th']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4 pt-6 border-t-2 border-slate-100">
                    <button type="button" onclick="closeModal()"
                        class="flex-1 px-6 py-4 border-2 border-slate-300 text-slate-700 rounded-xl font-bold hover:bg-slate-50 hover:border-slate-400 transition-all hover:-translate-y-0.5 shadow-sm">
                        ยกเลิก
                    </button>
                    <button type="submit" name="btn_save"
                        class="flex-1 px-6 py-4 bg-indigo-600 text-white rounded-xl font-bold hover:bg-purple-700 shadow-xl shadow-indigo-200 transition-all hover:-translate-y-0.5">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            บันทึกข้อมูล
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'th',
                firstDay: 1,
                initialView: 'dayGridMonth',
                height: 'auto',

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

                events: <?= json_encode($events) ?>,

                selectable: isLoggedIn,
                selectMirror: isLoggedIn,
                editable: false,
                eventStartEditable: false,
                eventDurationEditable: false,

                dateClick: function(info) {
                    if (!isLoggedIn) return;
                    if (info.date instanceof Date) {
                        openModalFromDateClick(info);
                    } else {
                        openModalForDate(info.dateStr);
                    }
                },

                select: function(info) {
                    if (!isLoggedIn) return;
                    openModalFromSelection(info);
                },

                eventClick: function(info) {
                    if (!isLoggedIn) return;
                    const start = info.event.start.toLocaleTimeString('th-TH', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    const end = info.event.end ?
                        info.event.end.toLocaleTimeString('th-TH', {
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : '-';
                    alert(`📌 ${info.event.title}\n⏰ ${start} - ${end}`);
                },

                dayCellDidMount: function(arg) {
                    if (!isLoggedIn) {
                        arg.el.style.cursor = 'not-allowed';
                    }
                },

                eventContent: function(arg) {
                    const start = arg.event.start;
                    const end = arg.event.end;

                    const fmt = (d) =>
                        d.toLocaleTimeString('th-TH', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                    const timeText = end ?
                        `${fmt(start)} – ${fmt(end)}` :
                        fmt(start);

                    return {
                        html: `
            <div class="fc-custom-event">
                <div class="fc-custom-time">${timeText}</div>
                <div class="fc-custom-title">${arg.event.title}</div>
            </div>
        `
                    };
                },

            });

            calendar.render();
        });

        function openModalFromSelection(info) {
            const modal = document.getElementById('eventModal');
            const dateDisplay = document.getElementById('modal_date_display');
            const start = info.start;
            const end = info.end ? info.end : new Date(start.getTime() + 60 * 60000);

            const dateStr = start.getFullYear() + '-' +
                String(start.getMonth() + 1).padStart(2, '0') + '-' +
                String(start.getDate()).padStart(2, '0');

            const startTime = String(start.getHours()).padStart(2, '0') + ':' +
                String(start.getMinutes()).padStart(2, '0');
            const endTime = String(end.getHours()).padStart(2, '0') + ':' +
                String(end.getMinutes()).padStart(2, '0');

            const thaiDays = ['วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์'];
            const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
            ];

            const thaiDate = `${thaiDays[start.getDay()]}ที่ ${start.getDate()} ${thaiMonths[start.getMonth()]} ${start.getFullYear() + 543}`;

            document.getElementById('m_work_date').value = dateStr;
            document.getElementById('m_start_time').value = startTime;
            document.getElementById('m_end_time').value = endTime;
            dateDisplay.textContent = thaiDate;

            modal.classList.remove('hidden');
        }

        function openModalForDate(dateStr) {
            const modal = document.getElementById('eventModal');
            const dateDisplay = document.getElementById('modal_date_display');
            const date = new Date(dateStr + 'T00:00:00');

            const thaiDays = ['วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์'];
            const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
            ];

            const thaiDate = `${thaiDays[date.getDay()]}ที่ ${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`;

            document.getElementById('m_work_date').value = dateStr;
            document.getElementById('m_start_time').value = '09:00';
            document.getElementById('m_end_time').value = '17:00';
            dateDisplay.textContent = thaiDate;

            modal.classList.remove('hidden');
        }

        function openModalFromDateClick(info) {
            const modal = document.getElementById('eventModal');
            const dateDisplay = document.getElementById('modal_date_display');
            const start = info.date;
            const end = new Date(start.getTime() + 60 * 60000);

            const dateStr = start.getFullYear() + '-' +
                String(start.getMonth() + 1).padStart(2, '0') + '-' +
                String(start.getDate()).padStart(2, '0');

            const startTime = String(start.getHours()).padStart(2, '0') + ':' +
                String(start.getMinutes()).padStart(2, '0');
            const endTime = String(end.getHours()).padStart(2, '0') + ':' +
                String(end.getMinutes()).padStart(2, '0');

            const thaiDays = ['วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์'];
            const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
            ];

            const thaiDate = `${thaiDays[start.getDay()]}ที่ ${start.getDate()} ${thaiMonths[start.getMonth()]} ${start.getFullYear() + 543}`;

            document.getElementById('m_work_date').value = dateStr;
            document.getElementById('m_start_time').value = startTime;
            document.getElementById('m_end_time').value = endTime;
            dateDisplay.textContent = thaiDate;

            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('eventModal').classList.add('hidden');
        }

        document.getElementById('eventModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>

</html>