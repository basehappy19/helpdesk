<?php
require __DIR__ . "../../functions/status.php";
$statuses = getStatuses();
$work = null;

if (isset($_GET['id'])) {
    $workId = $_GET['id'];
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];

    $apiUrl = $scheme . '://' . $host . '/api/works/get_work.php?id=' . urlencode((string)$workId);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response !== false && $httpCode === 200) {
        $data = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE && !empty($data['ok'])) {
            $work = $data['work'] ?? null;
        }
    }
} else {
    die('work ID is required.');
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดงาน #<?= htmlspecialchars($work['code'] ?? '') ?> | HelpDesk</title>
    <?php include './lib/style.php'; ?>
</head>

<body>
    <?php
    include './components/navbar.php';
    ?>
    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen py-8 px-4">
        <div class="max-w-5xl mx-auto">

            <!-- Header Section -->
            <div class="mb-6">
                <a href="./?page=daily-works" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition-colors mb-4">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    ย้อนกลับ
                </a>
            </div>

            <?php if ($work): ?>

                <!-- Ticket Code Card -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-xl p-8 mb-6 text-white">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <p class="text-indigo-100 text-sm mb-1">รหัสงาน</p>
                            <h1 class="text-3xl md:text-4xl font-bold"><?= htmlspecialchars($work['code'] ?? '') ?></h1>
                        </div>
                        <div class="text-right">
                            <p class="text-indigo-100 text-sm mb-1">หมายเลขอ้างอิง</p>
                            <p class="text-2xl font-semibold">#<?= htmlspecialchars($work['id'] ?? '') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left Column - Main Info -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Work Details Card -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                    <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    รายละเอียดการแจ้งงาน
                                </h2>
                            </div>

                            <div class="p-6 space-y-5">

                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div class="flex-grow">
                                        <p class="text-sm font-medium text-gray-500 mb-1">แผนก</p>
                                        <p class="text-lg text-gray-800 font-medium"><?= htmlspecialchars($work['department'] ?? '-') ?></p>
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
                                        <p class="text-lg text-gray-800 font-medium"><?= htmlspecialchars($work['reporter_name'] ?? '-') ?></p>
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
                                                <?= htmlspecialchars($work['request_type_name'] ?? '-') ?>
                                            </span>
                                        </div>

                                        <div>
                                            <p class="text-sm font-medium text-gray-500 mb-2">หมวดหมู่</p>
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-purple-100 text-purple-800">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                                <?= htmlspecialchars($work['category_name'] ?? '-') ?>
                                            </span>
                                        </div>

                                    </div>
                                </div>

                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <p class="text-sm font-medium text-red-700 mb-1.5 flex items-center">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        อาการ/ปัญหา
                                    </p>
                                    <p class="text-base text-red-900 font-medium"><?= htmlspecialchars($work['symptom_name'] ?? '-') ?></p>
                                </div>

                            </div>
                        </div>

                        <!-- Status History Card -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                        <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        ประวัติการเปลี่ยนสถานะ
                                    </h2>
                                    <?php if (isset($user)): ?>
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
                                <?php if (!empty($work['ticket_status_logs'])): ?>
                                    <div class="relative">
                                        <!-- Timeline Line -->
                                        <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gradient-to-b from-indigo-200 to-purple-200"></div>

                                        <div class="space-y-6">
                                            <?php foreach ($work['ticket_status_logs'] as $index => $log): ?>
                                                <div class="relative flex items-start group">
                                                    <!-- Timeline Dot -->
                                                    <div class="absolute left-0 w-10 h-10 bg-white border-4 border-indigo-500 rounded-full flex items-center justify-center z-10">
                                                        <span class="text-xs font-bold text-indigo-600"><?= count($work['ticket_status_logs']) - $index ?></span>
                                                    </div>

                                                    <!-- Content -->
                                                    <div class="ml-16 flex-grow">
                                                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 hover:shadow-md transition-all relative">
                                                            <?php if (isset($user)): ?>
                                                                <!-- Action Buttons (Show on Hover) -->
                                                                <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity flex items-center space-x-1">
                                                                    <button onclick="openEditStatusModal(<?= htmlspecialchars(json_encode($log)) ?>)" class="p-1.5 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors" title="แก้ไข">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                        </svg>
                                                                    </button>
                                                                    <button onclick="confirmDeleteStatus(<?= htmlspecialchars($log['id']) ?>)" class="p-1.5 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors" title="ลบ">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                                                                อาการ:
                                                                <?= htmlspecialchars($log['symptom'] ?? "-") ?>
                                                            </p>
                                                            <p class="text-xs text-gray-500 mb-2 flex items-center">
                                                                สาเหตุ:
                                                                <?= htmlspecialchars($log['cause'] ?? "-") ?>
                                                            </p>
                                                            <p class="text-xs text-gray-500 mb-2 flex items-center">
                                                                ผู้แก้ไขปัญหา:
                                                                <?= htmlspecialchars($log['solver_by'] ?? "-") ?>
                                                            </p>
                                                            <p class="text-xs text-gray-500 mb-2 flex items-center">
                                                                SLA ข้อใด:
                                                                <?= htmlspecialchars($log['sla'] ?? "-") ?>
                                                            </p>

                                                            <div class="flex items-center flex-wrap gap-2">
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

                    <!-- Right Column - Metadata -->
                    <div class="lg:col-span-1 space-y-6">

                        <!-- Created At Card -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-6 py-4">
                                <h3 class="text-white font-semibold flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    วันที่สร้าง
                                </h3>
                            </div>
                            <div class="p-6">
                                <p class="text-2xl font-bold text-gray-800 mb-1">
                                    <?= formatDateThaiBuddhistWithOutTime($work['created_at']) ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    เวลา <?= date('H:i', strtotime($work['created_at'] ?? '')) ?> น.
                                </p>
                            </div>
                        </div>

                        <!-- Quick Info Card -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                ข้อมูลสรุป
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-600">จำนวนการเปลี่ยนสถานะ</span>
                                    <span class="font-semibold text-indigo-600"><?= count($work['ticket_status_logs'] ?? []) ?> ครั้ง</span>
                                </div>
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-sm text-gray-600">สถานะล่าสุด</span>
                                    <?php
                                    $latestStatus = $work['ticket_status_logs'][0] ?? null;
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

            <?php else: ?>
                <!-- Error State -->
                <div class="bg-white rounded-2xl shadow-lg border border-red-200 p-12 text-center">
                    <svg class="w-20 h-20 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">ไม่พบข้อมูลงาน</h2>
                    <p class="text-gray-600 mb-6">ขออภัย ไม่สามารถดึงข้อมูลงานนี้ได้</p>
                    <a href="index.php" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        กลับหน้าหลัก
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php if (isset($user)): ?>
        <!-- Add Status Modal -->
        <div id="addStatusModal" class="fixed inset-0 backdrop-blur-md bg-white/20 hidden items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
                    <h3 class="text-xl font-semibold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        เพิ่มสถานะใหม่
                    </h3>
                </div>
                <form id="addStatusForm" class="p-6 space-y-4">
                    <input type="hidden" name="work_id" value="<?= htmlspecialchars($work['id'] ?? '') ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">สถานะใหม่</label>
                        <select name="to_status_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- เลือกสถานะ --</option>
                            <?php foreach ($statuses as $status) : ?>
                                <option value="<?php echo $status['id'] ?>"><?php echo $status['name_th'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="symptom_input_add" class="block text-sm font-medium text-gray-700 mb-2">อาการ</label>
                        <input id="symptom_input_add" name="symptom" value="" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label for="cause_input_add" class="block text-sm font-medium text-gray-700 mb-2">สาเหตุ</label>
                        <input id="cause_input_add" name="cause" value="" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div>
                        <label for="solver_by_input_add" class="block text-sm font-medium text-gray-700 mb-2">ผู้แก้ไขปัญหา</label>
                        <input id="solver_by_input_add" name="solver_by" value="" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label for="sla_input_add" class="block text-sm font-medium text-gray-700 mb-2">SLA ข้อใด</label>
                        <input id="sla_input_add" name="sla" value="" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">วันที่เปลี่ยนสถานะ</label>
                        <input type="datetime-local" name="status_changed_at" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div class="flex items-center space-x-3 pt-4">
                        <button type="submit" class="flex-1 bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-2.5 rounded-lg font-medium hover:shadow-lg hover:scale-105 transition-all">
                            บันทึก
                        </button>
                        <button type="button" onclick="closeAddStatusModal()" class="flex-1 bg-gray-200 text-gray-700 py-2.5 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Status Modal -->
        <div id="editStatusModal" class="fixed inset-0 backdrop-blur-md bg-white/20 hidden items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
                <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4 rounded-t-2xl">
                    <h3 class="text-xl font-semibold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        แก้ไขสถานะ
                    </h3>
                </div>
                <form id="editStatusForm" class="p-6 space-y-4">
                    <input type="hidden" name="log_id" id="edit_log_id">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">สถานะปลายทาง</label>
                        <select name="to_status_id" id="edit_to_status" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- เลือกสถานะ --</option>
                            <?php foreach ($statuses as $status) : ?>
                                <option value="<?php echo $status['id'] ?>"><?php echo $status['name_th'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="symptom_input" class="block text-sm font-medium text-gray-700 mb-2">อาการ</label>
                        <input id="symptom_input" name="symptom" value="" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label for="cause_input" class="block text-sm font-medium text-gray-700 mb-2">สาเหตุ</label>
                        <input id="cause_input" name="cause" value="" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <div>
                        <label for="solver_by_input" class="block text-sm font-medium text-gray-700 mb-2">ผู้แก้ไขปัญหา</label>
                        <input id="solver_by_input" name="solver_by" value="" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label for="sla_input" class="block text-sm font-medium text-gray-700 mb-2">SLA ข้อใด</label>
                        <input id="sla_input" name="sla" value="" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">วันที่เปลี่ยนสถานะ</label>
                        <input id="status_changed_at" type="datetime-local" name="status_changed_at" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div class="flex items-center space-x-3 pt-4">
                        <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-cyan-600 text-white py-2.5 rounded-lg font-medium hover:shadow-lg hover:scale-105 transition-all">
                            บันทึกการแก้ไข
                        </button>
                        <button type="button" onclick="closeEditStatusModal()" class="flex-1 bg-gray-200 text-gray-700 py-2.5 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteStatusModal" class="fixed inset-0 backdrop-blur-md bg-white/20 hidden items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
                <div class="bg-gradient-to-r from-red-600 to-pink-600 px-6 py-4 rounded-t-2xl">
                    <h3 class="text-xl font-semibold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        ยืนยันการลบ
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-6">คุณแน่ใจหรือไม่ว่าต้องการลบประวัติการเปลี่ยนสถานะนี้?</p>
                    <input type="hidden" id="delete_log_id">
                    <div class="flex items-center space-x-3">
                        <button onclick="deleteStatus()" class="flex-1 bg-gradient-to-r from-red-600 to-pink-600 text-white py-2.5 rounded-lg font-medium hover:shadow-lg hover:scale-105 transition-all">
                            ยืนยันลบ
                        </button>
                        <button onclick="closeDeleteStatusModal()" class="flex-1 bg-gray-200 text-gray-700 py-2.5 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                            ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Add Status Modal Functions
            function openAddStatusModal() {
                document.getElementById('addStatusModal').classList.remove('hidden');
                document.getElementById('addStatusModal').classList.add('flex');
                // Set current datetime
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                document.querySelector('#addStatusForm input[name="status_changed_at"]').value = now.toISOString().slice(0, 16);
            }

            function closeAddStatusModal() {
                document.getElementById('addStatusModal').classList.add('hidden');
                document.getElementById('addStatusModal').classList.remove('flex');
                document.getElementById('addStatusForm').reset();
            }

            function openEditStatusModal(log) {
                document.getElementById('editStatusModal').classList.remove('hidden');
                document.getElementById('editStatusModal').classList.add('flex');

                document.getElementById('edit_log_id').value = log.id;

                document.getElementById('symptom_input').value = log.symptom ?? "";
                document.getElementById('cause_input').value = log.cause ?? "";
                document.getElementById('solver_by_input').value = log.solver_by ?? "";
                document.getElementById('sla_input').value = log.sla ?? "";

                const select = document.getElementById('edit_to_status');
                const targetName = log.to_status_name;

                for (let option of select.options) {
                    if (option.text.trim() === targetName.trim()) {
                        option.selected = true;
                        break;
                    }
                }

                if (log.status_changed_at) {
                    document.getElementById('status_changed_at').value = log.status_changed_at;
                }

            }


            function closeEditStatusModal() {
                document.getElementById('editStatusModal').classList.add('hidden');
                document.getElementById('editStatusModal').classList.remove('flex');
                document.getElementById('editStatusForm').reset();
            }

            // Delete Status Functions
            function confirmDeleteStatus(logId) {
                document.getElementById('delete_log_id').value = logId;
                document.getElementById('deleteStatusModal').classList.remove('hidden');
                document.getElementById('deleteStatusModal').classList.add('flex');
            }

            function closeDeleteStatusModal() {
                document.getElementById('deleteStatusModal').classList.add('hidden');
                document.getElementById('deleteStatusModal').classList.remove('flex');
            }

            function deleteStatus() {
                const logId = document.getElementById('delete_log_id').value;

                // TODO: Send delete request to API
                fetch('/api/statuses/delete.php', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: logId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.ok) {
                            alert('ลบสถานะสำเร็จ');
                            location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถลบได้'));
                        }
                    })
                    .catch(error => {
                        alert('เกิดข้อผิดพลาด: ' + error.message);
                    });

                closeDeleteStatusModal();
            }

            // Add Status Form Submit
            document.getElementById('addStatusForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());

                // TODO: Send add request to API
                fetch('/api/statuses/add.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.ok) {
                            alert('เพิ่มสถานะสำเร็จ');
                            location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถเพิ่มได้'));
                        }
                    })
                    .catch(error => {
                        alert('เกิดข้อผิดพลาด: ' + error.message);
                    });
            });

            // Edit Status Form Submit
            document.getElementById('editStatusForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());

                // TODO: Send update request to API
                fetch('/api/statuses/update.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.ok) {
                            alert('แก้ไขสถานะสำเร็จ');
                            location.reload();
                        } else {
                            console.error(data);

                            alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถแก้ไขได้'));
                        }
                    })
                    .catch(error => {
                        alert('เกิดข้อผิดพลาด: ' + error.message);
                    });
            });

            // Close modals when clicking outside
            document.querySelectorAll('[id$="Modal"]').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.add('hidden');
                        this.classList.remove('flex');
                    }
                });
            });
        </script>
    <?php endif; ?>

</body>

</html>