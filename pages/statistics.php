<?php
global $pdo;

// ==========================================
// 1. DATA PREPARATION (Controller Logic)
// ==========================================

$totalTickets = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$completedTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE resolved_at IS NOT NULL")->fetchColumn();
$pendingTickets = $totalTickets - $completedTickets;

$slaPass = $pdo->query("SELECT COUNT(*) FROM tickets WHERE resolved_at IS NOT NULL AND sla_due_at IS NOT NULL AND resolved_at <= sla_due_at")->fetchColumn();
$slaFail = $pdo->query("SELECT COUNT(*) FROM tickets WHERE resolved_at IS NOT NULL AND sla_due_at IS NOT NULL AND resolved_at > sla_due_at")->fetchColumn();
$slaTotal = $slaPass + $slaFail;
$slaPassRate = $slaTotal > 0 ? round(($slaPass / $slaTotal) * 100, 2) : 0;

$deptStats = $pdo->query("
    SELECT COALESCE(department, 'ไม่ระบุ') as label, COUNT(*) as count 
    FROM tickets 
    GROUP BY department 
    ORDER BY count DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$trendStats = $pdo->query("
    SELECT DATE(created_at) as date_val, COUNT(*) as count
    FROM tickets
    WHERE created_at >= DATE(NOW()) - INTERVAL 6 DAY
    GROUP BY DATE(created_at)
    ORDER BY date_val ASC
")->fetchAll(PDO::FETCH_ASSOC);

$trendData = [];
for ($i = 6; $i >= 0; $i--) {
    $dateStr = date('Y-m-d', strtotime("-$i days"));
    $trendData[$dateStr] = 0;
}
foreach ($trendStats as $row) {
    $trendData[$row['date_val']] = (int)$row['count'];
}

$symptomStats = $pdo->query("
    SELECT 
        IF(t.category_other_remark IS NOT NULL AND t.category_other_remark != '', 
           IF(c.name_th IS NOT NULL, CONCAT(c.name_th, ' (', t.category_other_remark, ')'), t.category_other_remark), 
           COALESCE(c.name_th, 'ไม่ระบุ')
        ) AS display_category,

        IF(t.symptom_other_remark IS NOT NULL AND t.symptom_other_remark != '', 
           IF(s.name_th IS NOT NULL, CONCAT(s.name_th, ' (', t.symptom_other_remark, ')'), t.symptom_other_remark), 
           COALESCE(s.name_th, 'ไม่ระบุ')
        ) AS display_symptom,

        MAX(s.sla_minutes) as sla_minutes,
        COUNT(t.id) as total_tickets,
        COUNT(CASE WHEN t.resolved_at IS NULL THEN 1 END) as pending_tickets,
        COUNT(CASE WHEN t.resolved_at IS NOT NULL THEN 1 END) as resolved_tickets,
        COUNT(CASE WHEN t.resolved_at IS NOT NULL AND t.sla_due_at IS NOT NULL AND t.resolved_at <= t.sla_due_at THEN 1 END) as sla_pass,
        COUNT(CASE WHEN t.resolved_at IS NOT NULL AND t.sla_due_at IS NOT NULL AND t.resolved_at > t.sla_due_at THEN 1 END) as sla_fail
    FROM tickets t
    LEFT JOIN issue_categories c ON t.category_id = c.id
    LEFT JOIN issue_symptoms s ON t.symptom_id = s.id
    GROUP BY display_category, display_symptom
    ORDER BY total_tickets DESC, display_category ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถิติการให้บริการ | HelpDesk</title>
    <?php include './lib/style.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gradient-to-br from-slate-50 via-gray-50 to-slate-100 min-h-screen text-slate-800">

    <?php include './components/navbar.php'; ?>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 flex items-center">
                    <svg class="w-8 h-8 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    สถิติและภาพรวมระบบ (Dashboard)
                </h1>
                <p class="text-slate-500 mt-2">สรุปข้อมูลการให้บริการและประเมินมาตรฐาน SLA</p>
            </div>
            <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-slate-200 text-sm font-medium text-slate-600">
                ข้อมูล ณ วันที่: <span class="text-indigo-600"><?= formatDateThaiBuddhistWithOutTime(date('Y-m-d')) ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-blue-500"></div>
                <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center mr-4 text-blue-600">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">รับแจ้งทั้งหมด</p>
                    <p class="text-3xl font-bold text-slate-800"><?= number_format($totalTickets) ?> <span class="text-base font-normal text-slate-400">รายการ</span></p>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-amber-400"></div>
                <div class="w-14 h-14 rounded-full bg-amber-50 flex items-center justify-center mr-4 text-amber-500">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">กำลังดำเนินการ</p>
                    <p class="text-3xl font-bold text-slate-800"><?= number_format($pendingTickets) ?> <span class="text-base font-normal text-slate-400">รายการ</span></p>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full bg-emerald-500"></div>
                <div class="w-14 h-14 rounded-full bg-emerald-50 flex items-center justify-center mr-4 text-emerald-600">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">แก้ไขเสร็จสิ้น</p>
                    <p class="text-3xl font-bold text-slate-800"><?= number_format($completedTickets) ?> <span class="text-base font-normal text-slate-400">รายการ</span></p>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center relative overflow-hidden">
                <div class="absolute right-0 top-0 w-2 h-full <?= $slaPassRate >= 80 ? 'bg-indigo-500' : 'bg-red-500' ?>"></div>
                <div class="w-14 h-14 rounded-full <?= $slaPassRate >= 80 ? 'bg-indigo-50' : 'bg-red-50' ?> flex items-center justify-center mr-4 <?= $slaPassRate >= 80 ? 'text-indigo-600' : 'text-red-500' ?>">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">ความสำเร็จตาม SLA</p>
                    <p class="text-3xl font-bold <?= $slaPassRate >= 80 ? 'text-indigo-600' : 'text-red-600' ?>"><?= $slaPassRate ?>%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-8">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h3 class="text-lg font-bold text-slate-800 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    รายงานรายละเอียดปัญหาและการประเมิน SLA
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-bold">หมวดหมู่ปัญหา</th>
                            <th class="px-6 py-4 font-bold">อาการ</th>
                            <th class="px-4 py-4 font-bold text-center">รับแจ้ง (รายการ)</th>
                            <th class="px-4 py-4 font-bold text-center">รอดำเนินการ</th>
                            <th class="px-4 py-4 font-bold text-center">กำหนดเสร็จ (นาที)</th>
                            <th class="px-4 py-4 font-bold text-center text-emerald-600">ผ่านเกณฑ์</th>
                            <th class="px-4 py-4 font-bold text-center text-red-500">เกินเวลา</th>
                            <th class="px-6 py-4 font-bold text-center">อัตราสำเร็จ (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($symptomStats)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-slate-400">ยังไม่มีข้อมูลการแจ้งปัญหา</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($symptomStats as $stat):
                                // คำนวณเปอร์เซ็นต์ เฉพาะตั๋วที่ซ่อมเสร็จแล้ว
                                $totalDone = (int)$stat['resolved_tickets'];
                                $passCount = (int)$stat['sla_pass'];
                                $rate = $totalDone > 0 ? round(($passCount / $totalDone) * 100, 2) : 0;

                                // กำหนดสี
                                $rateColor = 'text-slate-500';
                                $rateBadge = 'bg-slate-100 text-slate-600';
                                if ($totalDone > 0) {
                                    if ($rate >= 80) {
                                        $rateColor = 'text-emerald-600 font-bold';
                                        $rateBadge = 'bg-emerald-100 text-emerald-700';
                                    } else {
                                        $rateColor = 'text-red-600 font-bold';
                                        $rateBadge = 'bg-red-100 text-red-700';
                                    }
                                }
                            ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-slate-700">
                                        <?= htmlspecialchars($stat['display_category'] ?? 'ไม่ระบุ') ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <?= htmlspecialchars($stat['display_symptom'] ?? 'ไม่ระบุ') ?>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-center font-bold text-blue-600">
                                        <?= number_format($stat['total_tickets']) ?>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-center font-medium text-amber-500">
                                        <?= number_format($stat['pending_tickets']) ?>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-center text-slate-500">
                                        <?= $stat['sla_minutes'] ? $stat['sla_minutes'] . ' นาที' : '-' ?>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-center font-bold text-emerald-500 bg-emerald-50/30">
                                        <?= number_format($stat['sla_pass']) ?>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-center font-bold text-red-500 bg-red-50/30">
                                        <?= number_format($stat['sla_fail']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-center">
                                        <?php if ($totalDone > 0): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $rateBadge ?>">
                                                <?= $rate ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wider mb-4">ผลการประเมิน SLA</h3>
                <div class="relative h-64 w-full flex justify-center">
                    <canvas id="slaChart"></canvas>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wider mb-4">แนวโน้ม 7 วันย้อนหลัง</h3>
                <div class="relative h-64 w-full">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <div class="lg:col-span-3 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wider mb-4">5 แผนกที่แจ้งปัญหามากที่สุด</h3>
                <div class="relative h-72 w-full">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            Chart.defaults.font.family = "'Sarabun', 'Prompt', sans-serif";
            Chart.defaults.color = '#64748b';

            // 1. SLA Doughnut Chart
            const ctxSla = document.getElementById('slaChart').getContext('2d');
            new Chart(ctxSla, {
                type: 'doughnut',
                data: {
                    labels: ['ผ่านเกณฑ์', 'เกินเวลา'],
                    datasets: [{
                        data: [<?= $slaPass ?>, <?= $slaFail ?>],
                        backgroundColor: ['#10b981', '#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // 3. Trend Line Chart
            <?php
            $trendLabels = array_keys($trendData);
            $trendValues = array_values($trendData);
            $formattedLabels = array_map(function ($d) {
                $months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
                $time = strtotime($d);
                return date('j', $time) . ' ' . $months[date('n', $time)];
            }, $trendLabels);
            ?>
            const ctxTrend = document.getElementById('trendChart').getContext('2d');
            new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: <?= json_encode($formattedLabels) ?>,
                    datasets: [{
                        label: 'จำนวนปัญหา',
                        data: <?= json_encode($trendValues) ?>,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#6366f1',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // 4. Top Departments Bar Chart
            <?php
            $deptLabels = array_column($deptStats, 'label');
            $deptValues = array_column($deptStats, 'count');
            ?>
            const ctxDept = document.getElementById('deptChart').getContext('2d');
            new Chart(ctxDept, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($deptLabels) ?>,
                    datasets: [{
                        label: 'จำนวน (รายการ)',
                        data: <?= json_encode($deptValues) ?>,
                        backgroundColor: '#8b5cf6',
                        borderRadius: 6,
                        barThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>