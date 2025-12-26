<?php
$y = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$m = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$d = isset($_GET['day']) ? (int)$_GET['day'] : (int)date('d');

if (!checkdate($m, $d, $y)) {
    $y = (int)date('Y');
    $m = (int)date('m');
    $d = (int)date('d');
}

$selected_date = sprintf('%04d-%02d-%02d', $y, $m, $d);global $pdo;
$message = '';

/* ====== สิทธิ์ ====== */
$isLoggedIn = isset($user) && isset($user['id']);
$isToday = ($selected_date === date('Y-m-d'));
$canEdit = $isLoggedIn && $isToday;

/* ====== โหลดหมวดหมู่ ====== */
$stmt_cat = $pdo->query("SELECT * FROM work_log_categories ORDER BY id ASC");
$categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
$allowedCatIds = array_flip(array_map(fn($c) => (string)$c['id'], $categories));

/* ====== SAVE SECTION ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_log'])) {

    if (!$canEdit) {
        $message = '❌ ไม่สามารถแก้ไขข้อมูลย้อนหลัง หรือยังไม่ได้เข้าสู่ระบบ';
    } else {
        $work_date = $_POST['work_date'] ?? $selected_date;
        $dt = DateTime::createFromFormat('Y-m-d', $work_date);

        if (!$dt || $dt->format('Y-m-d') !== $work_date) {
            $message = 'รูปแบบวันที่ไม่ถูกต้อง';
        } else {
            $logs = $_POST['logs'] ?? [];

            $sqlUpsert = "
                INSERT INTO daily_work_logs (user_id, work_date, start_hour, activity_detail, category_id)
                VALUES (:user_id, :work_date, :start_hour, :activity_detail, :category_id)
                ON DUPLICATE KEY UPDATE
                    activity_detail = VALUES(activity_detail),
                    category_id = VALUES(category_id),
                    updated_at = CURRENT_TIMESTAMP
            ";
            $stmtUpsert = $pdo->prepare($sqlUpsert);

            $stmtDelete = $pdo->prepare("
                DELETE FROM daily_work_logs
                WHERE user_id = :user_id AND work_date = :work_date AND start_hour = :start_hour
            ");

            try {
                $pdo->beginTransaction();
                $saved = 0;
                $deleted = 0;

                for ($h = 8; $h <= 16; $h++) {
                    $activity = trim($logs[$h]['activity'] ?? '');
                    $category_id = $logs[$h]['category_id'] ?? '';

                    if ($activity === '') {
                        $stmtDelete->execute([
                            ':user_id' => $user['id'],
                            ':work_date' => $work_date,
                            ':start_hour' => $h
                        ]);
                        $deleted += $stmtDelete->rowCount();
                        continue;
                    }

                    $category_id_db = null;
                    if ($category_id !== '' && isset($allowedCatIds[(string)$category_id])) {
                        $category_id_db = (int)$category_id;
                    }

                    $stmtUpsert->execute([
                        ':user_id' => $user['id'],
                        ':work_date' => $work_date,
                        ':start_hour' => $h,
                        ':activity_detail' => $activity,
                        ':category_id' => $category_id_db
                    ]);
                    $saved++;
                }

                $pdo->commit();
                $message = "✅ บันทึกสำเร็จ (เพิ่ม/อัปเดต {$saved} รายการ, ลบ {$deleted} รายการ)";
                $selected_date = $work_date;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = "เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

/* ====== โหลดข้อมูลเดิม ====== */
$stmt_log = $pdo->prepare("SELECT * FROM daily_work_logs WHERE user_id = :uid AND work_date = :wdate");
$stmt_log->execute([':uid' => $user['id'] ?? 0, ':wdate' => $selected_date]);
$existing_logs = [];
foreach ($stmt_log->fetchAll(PDO::FETCH_ASSOC) as $log) {
    $existing_logs[(int)$log['start_hour']] = $log;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>บันทึกภาระงานประจำวัน - Helpdesk</title>
    <?php include './lib/style.php'; ?>
</head>

<body>
    <?php include './components/navbar.php'; ?>

    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8 px-4">
        <div class="max-w-6xl mx-auto">

            <?php if ($message): ?>
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-lg shadow-sm animate-fade-in">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- 🔒 แจ้งเตือนสิทธิ์ -->
            <?php if (!$isLoggedIn): ?>
                <div class="mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded-r-lg shadow-sm">
                    ⚠️ กรุณาเข้าสู่ระบบเพื่อเพิ่มหรือแก้ไขข้อมูล
                </div>
            <?php elseif (!$isToday): ?>
                <div class="mb-4 p-4 bg-gray-50 border-l-4 border-gray-400 rounded-r-lg shadow-sm">
                    ℹ️ ข้อมูลย้อนหลัง (อ่านอย่างเดียว)
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="bg-blue-600 px-6 py-8">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white mb-2">📝 บันทึกภาระงานประจำวัน</h1>
                            <p class="text-blue-100">ระบบจัดการกิจกรรมรายชั่วโมง</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <form method="get" class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                        <input type="hidden" name="page" value="daily-works">
                        <label class="text-gray-700 font-medium flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            เลือกวันที่:
                        </label>

                        <!-- dropdown วัน -->
                        <select name="day" onchange="this.form.submit()"
                            class="px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <?php for ($i = 1; $i <= 31; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php if ($i == $d) echo 'selected'; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>

                        <!-- dropdown เดือน -->
                        <select name="month" onchange="this.form.submit()"
                            class="px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <?php
                            $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                            foreach ($months as $index => $monthName): ?>
                                <option value="<?php echo $index + 1; ?>" <?php if ($index + 1 == $m) echo 'selected'; ?>>
                                    <?php echo $monthName; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- dropdown ปี -->
                        <select name="year" onchange="this.form.submit()"
                            class="px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php if ($i == $y) echo 'selected'; ?>>
                                    <?php echo $i + 543; ?> <!-- แสดงเป็น พ.ศ. -->
                                </option>
                            <?php endfor; ?>
                        </select>

                        <span class="text-gray-600 bg-white px-4 py-2 rounded-lg border border-gray-200">
                            <?php echo formatDateThaiWithMonth($selected_date); ?>
                        </span>
                    </form>
                </div>
            </div>

            <!-- ตารางกิจกรรม -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <form method="post">
                    <input type="hidden" name="work_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-indigo-600 to-blue-600 text-white">
                                    <th class="px-6 py-4 text-left font-semibold w-32">⏰ ชั่วโมงเริ่ม</th>
                                    <th class="px-6 py-4 text-left font-semibold">กิจกรรม (Activity)</th>
                                    <th class="px-6 py-4 text-left font-semibold w-80">หมวดหมู่ (Category)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php for ($h = 8; $h <= 16; $h++):
                                    $log = $existing_logs[$h] ?? [];
                                ?>
                                    <tr class="bg-white hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4"><span class="bg-indigo-500 text-white px-3 py-1.5 rounded-lg font-semibold text-sm shadow-sm"><?php echo sprintf("%02d:00", $h); ?></span></td>
                                        <td class="px-6 py-4">
                                            <input type="text" name="logs[<?php echo $h; ?>][activity]"
                                                value="<?php echo htmlspecialchars($log['activity_detail'] ?? ''); ?>"
                                                class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition-all duration-200 <?php echo !$canEdit ? 'bg-gray-100' : ''; ?>"
                                                placeholder="📋 เช่น ตรวจเช็คห้อง Server, ให้บริการ Helpdesk..." <?php echo !$canEdit ? 'readonly' : ''; ?>>
                                        </td>
                                        <td class="px-6 py-4">
                                            <select name="logs[<?php echo $h; ?>][category_id]"
                                                class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition-all duration-200 <?php echo !$canEdit ? 'bg-gray-100' : ''; ?>"
                                                <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                                <option value="" <?php if (empty($log['category_id'])) echo 'selected'; ?>>-- ไม่ระบุหมวดหมู่ --</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" <?php if (($log['category_id'] ?? '') == $cat['id']) echo 'selected'; ?>>
                                                        <?php echo htmlspecialchars($cat['name_th']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($canEdit): ?>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                            <button type="submit" name="save_log"
                                class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-semibold rounded-lg shadow-lg hover:from-indigo-700 hover:to-blue-700 transform hover:scale-105 transition-all duration-200">
                                💾 บันทึกข้อมูล
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- คำแนะนำ -->
            <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 rounded-r-lg p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <h3 class="text-blue-800 font-semibold mb-1">💡 คำแนะนำการใช้งาน</h3>
                        <p class="text-blue-700 text-sm">บันทึกกิจกรรมรายชั่วโมง ถ้ามีกิจกรรมมากกว่า 1 เรื่อง ให้ใส่เครื่องหมาย comma (,) คั่น</p>
                    </div>
                </div>
            </div>

        </div>
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