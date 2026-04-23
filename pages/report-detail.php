<?php
global $pdo;

// เรียกใช้ Controller
require_once __DIR__ . '/../controllers/ReportDetailController.php';
require_once __DIR__ . '/../functions/status.php';

$ticketCode = isset($_GET['code']) ? $_GET['code'] : 0;

// เริ่มต้นการทำงานของ Controller
$controller = new ReportDetailController($pdo, $user ?? null, $ticketCode);

// เช็ค Error ถ้า Code ไม่ถูกต้องให้เด้งกลับ
if ($controller->error === "INVALID_CODE") {
    header('Location: ./?page=reports');
    exit;
}

// ดึงตัวแปรจาก Controller มาใช้งานใน View
$reportDetails = $controller->reportDetails;
$statuses      = $controller->statuses;
$canEditStatus = $controller->canEditStatus;

if ($reportDetails === null) {
?>
    <!DOCTYPE html>
    <html lang="th">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ไม่พบข้อมูล | HelpDesk</title>
        <?php include './lib/style.php'; ?>
    </head>

    <body class="flex flex-col min-h-screen">
        <?php include './components/navbar.php'; ?>

        <div class="flex-grow bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 flex items-center justify-center py-12 px-4">
            <div class="max-w-md w-full bg-white rounded-3xl shadow-xl p-8 text-center border border-white/60 transform hover:-translate-y-1 transition-all duration-300">

                <div class="w-24 h-24 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner relative">
                    <svg class="w-12 h-12 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="absolute -top-1 -right-1 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm">
                        <span class="text-xl">🔍</span>
                    </div>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-3">ไม่พบรายการแจ้งปัญหานี้</h1>
                <p class="text-gray-500 mb-8 text-sm leading-relaxed">
                    รหัสปัญหา <b class="text-indigo-600 font-semibold">#<?= htmlspecialchars($ticketCode) ?></b> ที่คุณพยายามเข้าถึงอาจไม่ถูกต้อง หรือรายการนี้อาจถูกลบออกจากระบบไปแล้ว
                </p>

                <div class="space-y-3">
                    <a href="./?page=reports" class="inline-flex items-center justify-center w-full px-6 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium rounded-xl hover:shadow-lg hover:shadow-indigo-200 hover:scale-[1.02] transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        กลับสู่หน้ารายการแจ้งปัญหา
                    </a>
                </div>

            </div>
        </div>
    </body>

    </html>
<?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดปัญหา #<?= htmlspecialchars($reportDetails['code'] ?? '') ?> | HelpDesk</title>
    <?php include './lib/style.php'; ?>
    <style>
        /* สไตล์สำหรับ Toast Alert */
        .hot-toast {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(-150%) scale(0.9);
            opacity: 0;
            background: white;
            color: #374151;
            padding: 12px 16px;
            border-radius: 9999px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.35s cubic-bezier(0.21, 1.02, 0.73, 1);
            z-index: 99999;
            pointer-events: none;
        }

        .hot-toast.show {
            transform: translateX(-50%) translateY(0) scale(1);
            opacity: 1;
        }
    </style>
</head>

<body>
    <?php include './components/navbar.php'; ?>

    <div id="toast-container"></div>

    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen py-8 md:px-4">
        <div class="max-w-5xl mx-auto">

            <div class="mb-6">
                <a href="./?page=reports" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition-colors mb-4 font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    ย้อนกลับ
                </a>
            </div>

            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 md:rounded-2xl shadow-xl p-8 mb-6 text-white">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <p class="text-indigo-100 text-sm mb-1">รหัสปัญหา</p>
                        <h1 class="text-3xl md:text-4xl font-bold"><?= htmlspecialchars($reportDetails['code'] ?? '') ?></h1>
                    </div>
                    <div class="md:text-right">
                        <p class="text-indigo-100 text-sm mb-1">หมายเลขอ้างอิง</p>
                        <p class="text-2xl font-semibold">#<?= htmlspecialchars($reportDetails['id'] ?? '') ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 space-y-6">

                    <div class="bg-white md:rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                รายละเอียดการแจ้งปัญหา
                            </h2>
                        </div>

                        <div class="p-6 space-y-5">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 md:rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div class="flex-grow">
                                    <p class="text-sm font-medium text-gray-500 mb-1">แผนก</p>
                                    <p class="text-lg text-gray-800 font-medium"><?= htmlspecialchars($reportDetails['department'] ?? '-') ?></p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="flex-grow">
                                    <p class="text-sm font-medium text-gray-500 mb-1">ผู้แจ้ง</p>
                                    <p class="text-lg text-gray-800 font-medium"><?= htmlspecialchars($reportDetails['reporter_name'] ?? '-') ?></p>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 mb-2">ประเภทคำขอ</p>
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-blue-100 text-blue-800">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                            </svg>
                                            <?= htmlspecialchars($reportDetails['request_type_name'] ?? '-') ?>
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 mb-2">หมวดหมู่</p>
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-purple-100 text-purple-800">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                            <?= htmlspecialchars($reportDetails['display_category'] ?? '-') ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mt-5">
                                <p class="text-sm font-medium text-red-700 mb-1.5 flex items-center">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    อาการ/ปัญหา
                                </p>
                                <p class="text-base text-red-900 font-medium"><?= htmlspecialchars($reportDetails['display_symptom'] ?? '-') ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white md:rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                    <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    ประวัติการเปลี่ยนสถานะ
                                </h2>
                                <?php if ($canEditStatus): ?>
                                    <button onclick="openAddStatusModal()" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:shadow-lg hover:scale-105 transition-all duration-200">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        เพิ่มสถานะ
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-6">
                            <?php if (!empty($reportDetails['ticket_status_logs'])): ?>
                                <div class="relative">
                                    <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gradient-to-b from-indigo-200 to-purple-200"></div>

                                    <div class="space-y-6">
                                        <?php foreach ($reportDetails['ticket_status_logs'] as $index => $log): ?>
                                            <div class="relative flex items-start group">
                                                <div class="absolute left-0 w-10 h-10 bg-white border-4 border-indigo-500 rounded-full flex items-center justify-center z-10">
                                                    <span class="text-xs font-bold text-indigo-600"><?= count($reportDetails['ticket_status_logs']) - $index ?></span>
                                                </div>

                                                <div class="ml-16 flex-grow">
                                                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 hover:shadow-md transition-all relative">

                                                        <?php if ($canEditStatus): ?>
                                                            <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity flex items-center space-x-1">
                                                                <button onclick="openEditStatusModal(<?= htmlspecialchars(json_encode($log)) ?>)" class="p-1.5 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors" title="แก้ไข">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                    </svg>
                                                                </button>
                                                                <button onclick="confirmDeleteStatus(<?= htmlspecialchars($log['id']) ?>)" class="p-1.5 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors" title="ลบ">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>

                                                        <p class="text-xs text-gray-500 mb-2 flex items-center">
                                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            <?= htmlspecialchars(formatDateThaiBuddhist($log['status_changed_at'])) ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500 mb-2 flex items-center">
                                                            อาการ: <?= htmlspecialchars($log['symptom'] ?? "-") ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500 mb-2 flex items-center">
                                                            สาเหตุ: <?= htmlspecialchars($log['cause'] ?? "-") ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500 mb-2 flex items-center">
                                                            ผู้แก้ไขปัญหา: <?= htmlspecialchars($log['solver_by'] ?? "-") ?>
                                                        </p>

                                                        <div class="flex items-center flex-wrap gap-2 mt-3">
                                                            <?php if ($log['from_status_name']): ?>
                                                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium <?= htmlspecialchars($log['status_from_style'] ?? 'bg-gray-100 text-gray-800') ?>">
                                                                    <?= htmlspecialchars($log['from_status_name']) ?>
                                                                </span>
                                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                                </svg>
                                                            <?php else: ?>
                                                                <span class="text-sm text-gray-500 italic">เริ่มต้น</span>
                                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                                </svg>
                                                            <?php endif; ?>

                                                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium <?= htmlspecialchars($log['status_to_style'] ?? 'bg-gray-100 text-gray-800') ?>">
                                                                <?= htmlspecialchars($log['to_status_name']) ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-center text-gray-500 py-8">ไม่มีประวัติการเปลี่ยนสถานะ</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1 space-y-6">

                    <div class="bg-white md:rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-6 py-4">
                            <h3 class="text-white font-semibold flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                เวลาดำเนินการ
                            </h3>
                        </div>
                        <div class="p-6 space-y-5">

                            <div class="relative">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">วันที่แจ้งเรื่อง</p>
                                <p class="text-sm font-medium text-gray-800">
                                    <?= formatDateThaiBuddhist($reportDetails['created_at']) ?>
                                </p>
                            </div>

                            <div class="relative pt-4 border-t border-gray-100">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">วันที่รับเรื่อง</p>
                                <?php if (!empty($reportDetails['accepted_at'])): ?>
                                    <p class="text-sm font-medium text-indigo-700">
                                        <?= formatDateThaiBuddhist($reportDetails['accepted_at']) ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-sm font-medium text-yellow-500 flex items-center">
                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        กำลังรอรับเรื่อง...
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="relative pt-4 border-t border-gray-100">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">กำหนดเสร็จตาม SLA</p>
                                <?php if (!empty($reportDetails['sla_due_at'])): ?>
                                    <p class="text-sm font-medium text-orange-600">
                                        <?= formatDateThaiBuddhist($reportDetails['sla_due_at']) ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-sm font-medium text-gray-400">
                                        - (รอกดรับเรื่องเพื่อคำนวณ) -
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="relative pt-4 border-t border-gray-100">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">วันที่แก้ไขเสร็จ</p>
                                <?php if (!empty($reportDetails['resolved_at'])): ?>
                                    <p class="text-sm font-medium text-emerald-600">
                                        <?= formatDateThaiBuddhist($reportDetails['resolved_at']) ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-sm font-medium text-gray-400">
                                        - ยังไม่เสร็จสิ้น -
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($reportDetails['accepted_at']) && !empty($reportDetails['resolved_at'])):
                                $diffSeconds = strtotime($reportDetails['resolved_at']) - strtotime($reportDetails['accepted_at']);
                                $mins = floor($diffSeconds / 60);
                                $hours = floor($mins / 60);
                                $mins = $mins % 60;
                                $timeStr = $hours > 0 ? "{$hours} ชั่วโมง {$mins} นาที" : "{$mins} นาที";

                                // เช็คว่า ผ่าน หรือ ตก SLA
                                $isPassSLA = false;
                                if (!empty($reportDetails['sla_due_at'])) {
                                    $isPassSLA = strtotime($reportDetails['resolved_at']) <= strtotime($reportDetails['sla_due_at']);
                                }
                            ?>
                                <div class="mt-2 pt-4 border-t-2 border-dashed border-gray-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">ใช้เวลาแก้ไขรวม</p>
                                        <?php if (!empty($reportDetails['sla_due_at'])): ?>
                                            <?php if ($isPassSLA): ?>
                                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full">✅ ผ่าน SLA</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded-full">❌ เกิน SLA</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-lg font-bold <?= $isPassSLA ? 'text-emerald-600' : 'text-red-600' ?> flex items-center">
                                        <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <?= $timeStr ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <div class="bg-white md:rounded-2xl shadow-lg border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            ข้อมูลสรุป
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-600">จำนวนการเปลี่ยนสถานะ</span>
                                <span class="font-semibold text-indigo-600"><?= count($reportDetails['ticket_status_logs'] ?? []) ?> ครั้ง</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-gray-600">สถานะล่าสุด</span>
                                <?php
                                $latestStatus = $reportDetails['ticket_status_logs'][0] ?? null;
                                if ($latestStatus):
                                ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium <?= htmlspecialchars($latestStatus['status_to_style'] ?? 'bg-gray-100 text-gray-800') ?>">
                                        <?= htmlspecialchars($latestStatus['to_status_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php if ($canEditStatus): ?>

        <div id="addStatusModal" class="fixed inset-0 backdrop-blur-sm bg-slate-900/60 hidden items-center justify-center z-[10000] p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md transform transition-all relative w-full">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        เพิ่มสถานะใหม่
                    </h3>
                    <button type="button" onclick="closeAddStatusModal()" class="text-white/80 hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg></button>
                </div>
                <form id="addStatusForm" class="p-6 space-y-4">
                    <input type="hidden" name="work_id" value="<?= htmlspecialchars($reportDetails['id'] ?? '') ?>">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">สถานะใหม่</label>
                        <select name="to_status_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">-- เลือกสถานะ --</option>
                            <?php foreach ($statuses as $status) : ?>
                                <option value="<?php echo $status['id'] ?>"><?php echo htmlspecialchars($status['name_th']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="symptom_input_add" class="block text-sm font-semibold text-gray-700 mb-1">อาการ</label>
                        <input id="symptom_input_add" name="symptom" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm" />
                    </div>
                    <div>
                        <label for="cause_input_add" class="block text-sm font-semibold text-gray-700 mb-1">สาเหตุ</label>
                        <input id="cause_input_add" name="cause" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm" />
                    </div>
                    <div>
                        <label for="solver_by_input_add" class="block text-sm font-semibold text-gray-700 mb-1">ผู้แก้ไขปัญหา</label>
                        <input id="solver_by_input_add" name="solver_by" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">วันที่เปลี่ยนสถานะ</label>
                        <input type="datetime-local" name="status_changed_at" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="flex items-center gap-3 pt-4">
                        <button type="button" onclick="closeAddStatusModal()" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors">ยกเลิก</button>
                        <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-medium hover:bg-indigo-700 transition-all shadow-sm">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="editStatusModal" class="fixed inset-0 backdrop-blur-sm bg-slate-900/60 hidden items-center justify-center z-[10000] p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md transform transition-all relative w-full">
                <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4 rounded-t-2xl flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        แก้ไขสถานะ
                    </h3>
                    <button type="button" onclick="closeEditStatusModal()" class="text-white/80 hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg></button>
                </div>
                <form id="editStatusForm" class="p-6 space-y-4">
                    <input type="hidden" name="log_id" id="edit_log_id">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">สถานะปลายทาง</label>
                        <select name="to_status_id" id="edit_to_status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">-- เลือกสถานะ --</option>
                            <?php foreach ($statuses as $status) : ?>
                                <option value="<?php echo $status['id'] ?>"><?php echo htmlspecialchars($status['name_th']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="symptom_input" class="block text-sm font-semibold text-gray-700 mb-1">อาการ</label>
                        <input id="symptom_input" name="symptom" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" />
                    </div>
                    <div>
                        <label for="cause_input" class="block text-sm font-semibold text-gray-700 mb-1">สาเหตุ</label>
                        <input id="cause_input" name="cause" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" />
                    </div>
                    <div>
                        <label for="solver_by_input" class="block text-sm font-semibold text-gray-700 mb-1">ผู้แก้ไขปัญหา</label>
                        <input id="solver_by_input" name="solver_by" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">วันที่เปลี่ยนสถานะ</label>
                        <input id="status_changed_at" type="datetime-local" name="status_changed_at" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div class="flex items-center gap-3 pt-4">
                        <button type="button" onclick="closeEditStatusModal()" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors">ยกเลิก</button>
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-medium hover:bg-blue-700 transition-all shadow-sm">บันทึกแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="deleteStatusModal" class="fixed inset-0 backdrop-blur-sm bg-slate-900/60 hidden items-center justify-center z-[10000] p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-sm transform transition-all text-center p-6 relative w-full">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">ยืนยันการลบ?</h3>
                <p class="text-gray-500 mb-6 text-sm">คุณแน่ใจหรือไม่ว่าต้องการลบประวัติการเปลี่ยนสถานะนี้ การกระทำนี้ไม่สามารถย้อนกลับได้</p>
                <input type="hidden" id="delete_log_id">
                <div class="flex items-center gap-3">
                    <button onclick="closeDeleteStatusModal()" class="flex-1 bg-gray-100 text-gray-700 py-2.5 rounded-lg font-medium hover:bg-gray-200 transition-colors">ยกเลิก</button>
                    <button onclick="deleteStatus()" class="flex-1 bg-red-600 text-white py-2.5 rounded-lg font-medium hover:bg-red-700 transition-all shadow-sm">ยืนยันลบ</button>
                </div>
            </div>
        </div>

        <script>
            function showToast(message, type = 'success') {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = 'hot-toast';

                let iconHtml = type === 'success' ?
                    `<svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>` :
                    `<svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>`;

                toast.innerHTML = `${iconHtml} <span>${message}</span>`;
                container.appendChild(toast);

                requestAnimationFrame(() => toast.classList.add('show'));
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 400);
                }, 3000);
            }

            document.addEventListener('DOMContentLoaded', () => {
                const msg = sessionStorage.getItem('toast_msg');
                const type = sessionStorage.getItem('toast_type');
                if (msg) {
                    showToast(msg, type);
                    sessionStorage.removeItem('toast_msg');
                    sessionStorage.removeItem('toast_type');
                }
            });

            function reloadWithToast(message, type) {
                sessionStorage.setItem('toast_msg', message);
                sessionStorage.setItem('toast_type', type);
                location.reload();
            }

            const logCount = <?= count($reportDetails['ticket_status_logs'] ?? []) ?>;
            const initialSymptom = <?= json_encode($reportDetails['display_symptom'] ?? '') ?>;
            const latestLogSymptom = <?= json_encode($reportDetails['ticket_status_logs'][0]['symptom'] ?? '') ?>;
            
            const defaultSymptom = latestLogSymptom ? latestLogSymptom : initialSymptom;

            function openAddStatusModal() {
                document.getElementById('addStatusModal').classList.remove('hidden');
                document.getElementById('addStatusModal').classList.add('flex');
                
                // เซ็ตเวลาปัจจุบัน
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                document.querySelector('#addStatusForm input[name="status_changed_at"]').value = now.toISOString().slice(0, 16);

                if (logCount === 0 || logCount === 1) {
                    const statusSelect = document.querySelector('#addStatusForm select[name="to_status_id"]');
                    const symptomInput = document.getElementById('symptom_input_add');
                    
                    if (statusSelect && statusSelect.value === "") {
                        statusSelect.value = "2"; 
                    }
                    
                    if (symptomInput && symptomInput.value === "") {
                        symptomInput.value = defaultSymptom; 
                    }
                }
            }

            function closeAddStatusModal() {
                const modal = document.getElementById('addStatusModal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.getElementById('addStatusForm').reset();
            }

            // Edit Status Modal
            function openEditStatusModal(log) {
                document.getElementById('editStatusModal').classList.remove('hidden');
                document.getElementById('editStatusModal').classList.add('flex');
                
                document.getElementById('edit_log_id').value = log.id;
                document.getElementById('symptom_input').value = log.symptom ?? "";
                document.getElementById('cause_input').value = log.cause ?? "";
                document.getElementById('solver_by_input').value = log.solver_by ?? "";

                const select = document.getElementById('edit_to_status');
                if (log.to_status_id) {
                    select.value = log.to_status_id;
                } else {
                    for (let option of select.options) {
                        if (option.text.trim() === (log.to_status_name || "").trim()) {
                            option.selected = true;
                            break;
                        }
                    }
                }

                if (log.status_changed_at) {
                    const formattedDate = log.status_changed_at.replace(' ', 'T').substring(0, 16);
                    document.getElementById('status_changed_at').value = formattedDate;
                }
            }

            function closeEditStatusModal() {
                const modal = document.getElementById('editStatusModal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.getElementById('editStatusForm').reset();
            }

            // Delete Status Modal
            function confirmDeleteStatus(logId) {
                document.getElementById('delete_log_id').value = logId;
                document.getElementById('deleteStatusModal').classList.remove('hidden');
                document.getElementById('deleteStatusModal').classList.add('flex');
            }

            function closeDeleteStatusModal() {
                const modal = document.getElementById('deleteStatusModal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // API Calls
            function deleteStatus() {
                const logId = document.getElementById('delete_log_id').value;
                fetch('/api/statuses/delete.php', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: logId
                        })
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.ok) reloadWithToast('ลบสถานะเรียบร้อยแล้ว', 'success');
                        else showToast('Error: ' + (d.message || 'ไม่สามารถลบข้อมูลได้'), 'error');
                    })
                    .catch(e => showToast('Error: ' + e.message, 'error'))
                    .finally(() => closeDeleteStatusModal());
            }

            document.getElementById('addStatusForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(this).entries());
                fetch('/api/statuses/add.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.ok) reloadWithToast('เพิ่มสถานะสำเร็จ', 'success');
                        else showToast('Error: ' + (d.message || 'ไม่สามารถเพิ่มข้อมูลได้'), 'error');
                    })
                    .catch(e => showToast('Error: ' + e.message, 'error'));
            });

            document.getElementById('editStatusForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(this).entries());
                fetch('/api/statuses/update.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.ok) reloadWithToast('อัปเดตสถานะสำเร็จ', 'success');
                        else showToast('Error: ' + (d.message || 'ไม่สามารถแก้ไขข้อมูลได้'), 'error');
                    })
                    .catch(e => showToast('Error: ' + e.message, 'error'));
            });
        </script>
    <?php endif; ?>

</body>

</html>