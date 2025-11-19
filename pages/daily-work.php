<?php

declare(strict_types=1);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = 'localhost';
$apiUrl = $scheme . '://' . $host . '/api/reports/get_reports.php';

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
]);
$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

$reports = [];
if ($response !== false && $httpCode === 200) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && !empty($data['ok'])) {
        $reports = $data['reports'] ?? [];
    }
}

function latest_status(array $ticket): ?array
{
    if (empty($ticket['ticket_status_logs']) || !is_array($ticket['ticket_status_logs'])) {
        return null;
    }

    // log ล่าสุดจะอยู่ index 0 (เพราะ sort DESC แล้ว)
    $latest = $ticket['ticket_status_logs'][0] ?? null;
    if (!$latest) return null;

    return [
        'name'  => $latest['to_status_name'] ?? '-',
        'style' => $latest['to_status_style'] ?? '', // alias ที่มาจาก API
    ];
}


function formatDateThaiBuddhist(string $datetime, ?DateTimeZone $tz = null): string
{
    $tz = $tz ?: new DateTimeZone('Asia/Bangkok');
    $dt = new DateTime($datetime, $tz);
    $year_th = (int)$dt->format('Y') + 543;
    return $dt->format('d/m/') . $year_th . $dt->format(' H:i');
}

function diffLargestThai(string $datetime, ?DateTimeZone $tz = null): string
{
    $tz  = $tz ?: new DateTimeZone('Asia/Bangkok');
    $dt  = new DateTime($datetime, $tz);
    $now = new DateTime('now', $tz);

    $diffSec = max(0, $now->getTimestamp() - $dt->getTimestamp());

    if ($diffSec < 60) {
        return 'เมื่อสักครู่';
    }

    $units = [
        'ปี'     => 12 * 30 * 24 * 60 * 60,  // 12 เดือน
        'เดือน'  => 30 * 24 * 60 * 60,       // 30 วัน
        'วัน'    => 24 * 60 * 60,
        'ชั่วโมง' => 60 * 60,
        'นาที'   => 60,
    ];

    foreach ($units as $label => $secsPerUnit) {
        if ($diffSec >= $secsPerUnit) {
            $value = intdiv($diffSec, $secsPerUnit); // ปัดลง เช่น 61 นาที = 1 ชั่วโมง
            return $value . ' ' . $label . 'ที่ผ่านมา';
        }
    }
    return 'เมื่อสักครู่';
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>งานประจำวัน | HelpDesk</title>
    <?php include './lib/style.php'; ?>
</head>

<body>
    <?php include './components/navbar.php'; ?>
    <div class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen py-8 md:px-4">
        <div class="w-full container mx-auto">

            <div class="bg-white md:rounded-xl shadow-sm border border-gray-100 p-8 mt-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">งานประจำวัน</h3>
                    <span class="text-sm text-gray-500"><?= count($reports) ?> รายการ</span>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">วันที่แจ้ง</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">หน่วยงาน</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">ผู้แจ้ง</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">รายการ</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">อาการปัญหา</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">สถานะ</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($reports)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p class="text-gray-500 font-medium">ไม่พบข้อมูลงานประจำวัน</p>
                                                <p class="text-gray-400 text-sm mt-1">ยังไม่มีรายการแจ้งปัญหาในขณะนี้</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reports as $r): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                if (!empty($r['created_at'])) {
                                                    $tz = new DateTimeZone('Asia/Bangkok');
                                                    $display = formatDateThaiBuddhist($r['created_at'], $tz);
                                                    $relative = diffLargestThai($r['created_at'], $tz);

                                                    echo '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($display) . '</div>';
                                                    echo '<div class="text-xs text-gray-500 mt-1">' . htmlspecialchars($relative) . '</div>';
                                                } else {
                                                    echo '<span class="text-gray-400">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-gray-900"><?= htmlspecialchars($r['department'] ?? '-') ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-gray-900"><?= htmlspecialchars($r['reporter_name'] ?? '-') ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    <?= htmlspecialchars($r['request_type_name'] ?? '-') ?>
                                                    <?php if (!empty($r['category_name'])): ?>
                                                        <span class="mx-1">:</span>
                                                        <span><?= htmlspecialchars($r['category_name']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-gray-900">
                                                    <?= htmlspecialchars($r['symptom_name'] ?? '-') ?>
                                                </span>
                                            </td>
                                            <?php
                                            $latest = latest_status($r);
                                            $statusName  = $latest['name']  ?? '-';
                                            $colorClass  = $latest['style'] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <td class="px-6 py-4 text-center <?php echo htmlspecialchars($colorClass) ?>">
                                                <span class="items-center rounded-full text-sm font-medium">
                                                    <?php echo htmlspecialchars($statusName) ?>
                                            </td>
                                            </span>
                                            <td class="px-6 py-4 text-center">
                                                <a href="/ticket.php?id=<?= urlencode((string)($r['id'] ?? '')) ?>"
                                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all duration-150">
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    ดูรายละเอียด
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>