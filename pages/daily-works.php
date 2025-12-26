<?php
require_once __DIR__ . "../../functions/work_log_functions.php"; 
global $pdo;

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

$categories = getWorkLogCategories($pdo);
$allowedCatIds = array_flip(array_map(fn($c) => (string)$c['id'], $categories));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_log'])) {
    if (!$isLoggedIn) {
        $message = '❌ กรุณาเข้าสู่ระบบก่อนบันทึกข้อมูล';
    } elseif (!$isToday) {
        $message = '❌ ไม่สามารถบันทึกข้อมูลย้อนหลังหรืออนาคตได้ (อนุญาตเฉพาะวันที่ปัจจุบัน)';
    } else {
        $logs = $_POST['logs'] ?? [];
        $message = saveDailyWorkLogs($pdo, $logs, $allowedCatIds, $user['id'], $selected_date);
    }
}

$userId = $user['id'] ?? 0;
$existing_logs = getDailyWorkLogs($pdo, $selected_date);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>บันทึกภาระงานประจำวัน - Helpdesk</title>
    <?php include './lib/style.php'; ?>
</head>

<body class="bg-gradient-to-br from-indigo-50 to-blue-100 min-h-screen">

    <?php include './components/navbar.php'; ?>

    <div class="max-w-7xl mx-auto py-10 px-4">

        <!-- ✅ ข้อความสถานะ -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg shadow animate-fade-in">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- 🔒 แจ้งสิทธิ์ -->
        <?php if (!$isLoggedIn): ?>
            <div class="mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded-lg shadow-sm">
                ⚠️ กรุณาเข้าสู่ระบบเพื่อเพิ่มหรือแก้ไขข้อมูล
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-6">
                <h1 class="text-3xl font-bold text-white">📝 บันทึกงานประจำวัน</h1>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <form method="get" class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    <input type="hidden" name="page" value="daily-works">

                    <label class="text-gray-700 font-medium flex items-center gap-2">เลือกวันที่:</label>

                    <!-- วัน -->
                    <select name="day" onchange="this.form.submit()" class="cursor-pointer px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == $d ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>

                    <!-- เดือน -->
                    <select name="month" onchange="this.form.submit()" class="cursor-pointer px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <?php
                        $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                        foreach ($months as $index => $monthName): ?>
                            <option value="<?= $index + 1 ?>" <?= $index + 1 == $m ? 'selected' : '' ?>><?= $monthName ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- ปี -->
                    <select name="year" onchange="this.form.submit()" class="cursor-pointer px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == $y ? 'selected' : '' ?>><?= $i + 543 ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
        </div>

        <!-- ตารางกิจกรรม -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">

            <form method="post">
                <input type="hidden" name="work_date" value="<?= htmlspecialchars($selected_date); ?>">

                <div class="bg-white shadow-xl rounded-xl overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gradient-to-r from-indigo-600 to-blue-600 text-white">
                            <tr>
                                <th class="px-6 py-3 text-left">ชั่วโมงเริ่ม</th>
                                <th class="px-6 py-3 text-left">กิจกรรม (Activity)</th>
                                <th class="px-6 py-3 text-left w-64">หมวดหมู่ (Category)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php for ($h = 8; $h <= 16; $h++):
                                $log = $existing_logs[$h] ?? [];
                                $activity = htmlspecialchars($log['activity_detail'] ?? '');
                                $category_name = '';
                                if (!empty($log['category_id'])) {
                                    foreach ($categories as $cat) {
                                        if ($cat['id'] == $log['category_id']) {
                                            $category_name = htmlspecialchars($cat['name_th']);
                                            break;
                                        }
                                    }
                                }
                            ?>
                                <tr class="bg-white hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 font-semibold text-indigo-600">
                                        <?= sprintf('%02d:00', $h); ?>
                                    </td>

                                    <?php if (!$isLoggedIn || !$canEdit): ?>
                                        <td class="px-6 py-4 text-gray-700">
                                            <?= $activity !== '' ? nl2br($activity) : '<span class="text-gray-400 italic">— ไม่มีข้อมูล —</span>'; ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">
                                            <?= $category_name !== '' ? $category_name : '<span class="text-gray-400 italic">— ไม่ระบุหมวดหมู่ —</span>'; ?>
                                        </td>

                                    <?php else: ?>
                                        <td class="px-6 py-4">
                                            <input type="text" name="logs[<?= $h ?>][activity]"
                                                value="<?= $activity ?>"
                                                class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition-all duration-200 <?= !$canEdit ? 'bg-gray-100 cursor-not-allowed' : ''; ?>"
                                                placeholder="เช่น ตรวจเช็คห้อง Server, ให้บริการ Helpdesk..." <?= !$canEdit ? 'readonly' : ''; ?>>
                                        </td>
                                        <td class="px-6 py-4">
                                            <select name="logs[<?= $h ?>][category_id]"
                                                class="cursor-pointer w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition-all duration-200 <?= !$canEdit ? 'bg-gray-100 cursor-not-allowed' : ''; ?>">
                                                <option value="" <?= empty($log['category_id']) ? 'selected' : ''; ?>>-- ไม่ระบุหมวดหมู่ --</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= $cat['id']; ?>" <?= ($log['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($cat['name_th']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($canEdit): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="submit" name="save_log"
                            class="flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-lg hover:from-indigo-700 hover:to-blue-700 transform hover:scale-105 transition-all duration-200">
                            💾 บันทึกข้อมูล
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($isLoggedIn) : ?>
            <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg shadow-sm">
                <h3 class="font-semibold text-blue-800 mb-1">💡 คำแนะนำ</h3>
                <p class="text-blue-700 text-sm">- สามารถบันทึกได้เฉพาะวันที่ปัจจุบัน<br>
                    - ถ้ามีกิจกรรมมากกว่า 1 เรื่อง ให้ใช้เครื่องหมาย “,” คั่น</p>
            </div>
        <?php endif; ?>

    </div>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in .3s ease-out;
        }
    </style>
</body>

</html>