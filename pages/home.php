<?php
require_once __DIR__ . "../../functions/reports.php";
require_once __DIR__ . "../../functions/status.php";
require_once __DIR__ . "../../functions/time.php";

$reports = getRecentReports(5);
$statistics = getStatusStatistics();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก | HelpDesk</title>
    <?php include './lib/style.php'; ?>
</head>

<body class="bg-slate-50 font-sans antialiased text-gray-800">
    <?php include './components/navbar.php'; ?>

    <div class="min-h-screen pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <div class="bg-indigo-600 rounded-3xl shadow-sm overflow-hidden mb-8 relative">
                <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl"></div>

                <div class="relative px-8 py-10 md:py-12 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="text-white">
                        <h1 class="text-3xl md:text-4xl font-bold mb-2 tracking-tight">ระบบ HelpDesk</h1>
                        <p class="text-indigo-100 text-base md:text-lg opacity-90">โรงพยาบาลเมตตาประชารักษ์ (วัดไร่ขิง)</p>
                    </div>
                    <div>
                        <a href="./?page=report" class="inline-flex items-center px-6 py-3.5 bg-white text-indigo-600 rounded-2xl font-semibold shadow-sm hover:bg-indigo-50 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            แจ้งปัญหา / บริการ
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <div class="xl:col-span-2 space-y-8">

                    <?php
                    $statuses = $statistics['statuses'];
                    $lastIndex = count($statuses) - 1;

                    // ปรับสีให้คลีนขึ้น ใช้สีพาสเทลสำหรับพื้นหลังไอคอน และสีเรียบๆ สำหรับ Progress Bar
                    $statusStyles = [
                        'WAITING' => [
                            'iconBg' => 'bg-amber-50',
                            'iconColor' => 'text-amber-500',
                            'barColor' => 'bg-amber-400',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                        ],
                        'RECEIVED' => [
                            'iconBg' => 'bg-sky-50',
                            'iconColor' => 'text-sky-500',
                            'barColor' => 'bg-sky-400',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                        ],
                        'INSPECTING' => [
                            'iconBg' => 'bg-orange-50',
                            'iconColor' => 'text-orange-500',
                            'barColor' => 'bg-orange-400',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>'
                        ],
                        'WAITING_PART' => [
                            'iconBg' => 'bg-purple-50',
                            'iconColor' => 'text-purple-500',
                            'barColor' => 'bg-purple-400',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>'
                        ],
                        'REPAIRING' => [
                            'iconBg' => 'bg-blue-50',
                            'iconColor' => 'text-blue-500',
                            'barColor' => 'bg-blue-500',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>'
                        ],
                        'COMPLETED' => [
                            'iconBg' => 'bg-emerald-50',
                            'iconColor' => 'text-emerald-500',
                            'barColor' => 'bg-emerald-400',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                        ]
                    ];

                    function getStatusStyle($statusCode, $statusStyles)
                    {
                        return $statusStyles[$statusCode] ?? [
                            'iconBg' => 'bg-gray-50',
                            'iconColor' => 'text-gray-500',
                            'barColor' => 'bg-gray-400',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>'
                        ];
                    }
                    ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

                        <div class="bg-white rounded-2xl border border-gray-100/80 p-5 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] hover:border-indigo-100 hover:shadow-md transition-all duration-200">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-indigo-50 rounded-xl p-2.5 text-indigo-500">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">บันทึกทั้งหมด</p>
                                        <p class="text-xs text-gray-400 font-medium">TOTAL_REPORTS</p>
                                    </div>
                                </div>
                                <div class="text-3xl font-bold text-gray-800 tracking-tight">
                                    <?php echo number_format($statistics['total_reports_all']); ?>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 overflow-hidden">
                                <div class="bg-indigo-500 h-1.5 rounded-full w-full"></div>
                            </div>
                        </div>

                        <?php foreach ($statuses as $i => $statistic) :
                            $style = getStatusStyle($statistic['code'], $statusStyles);
                            $total = array_sum(array_column($statuses, 'total_reports'));
                            $percentage = $total > 0 ? round(($statistic['total_reports'] / $total) * 100, 1) : 0;
                        ?>
                            <div class="bg-white rounded-2xl border border-gray-100/80 p-5 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] hover:border-gray-200 hover:shadow-md transition-all duration-200 <?php echo $i === $lastIndex ? 'sm:col-span-2 lg:col-span-1' : ''; ?>">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="<?php echo $style['iconBg']; ?> rounded-xl p-2.5 <?php echo $style['iconColor']; ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <?php echo $style['icon']; ?>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($statistic['name_th']); ?></p>
                                            <p class="text-xs text-gray-400 font-medium"><?php echo htmlspecialchars($statistic['code']); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-3xl font-bold text-gray-800 tracking-tight">
                                        <?php echo number_format($statistic['total_reports']); ?>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 overflow-hidden flex items-center">
                                    <div class="<?php echo $style['barColor']; ?> h-1.5 rounded-full transition-all duration-500" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white rounded-2xl border border-gray-100/80 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] p-6">
                            <h2 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                </svg>
                                เมนูหลัก
                            </h2>
                            <div class="space-y-3">
                                <a href="./?page=report" class="flex items-center p-3 border border-gray-100 rounded-xl hover:border-indigo-100 hover:bg-indigo-50/50 transition-colors group">
                                    <div class="bg-indigo-50 rounded-lg p-2 text-indigo-500 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-sm font-semibold text-gray-800">แจ้งปัญหา / บริการ</h3>
                                    </div>
                                </a>
                                <a href="./?page=reports" class="flex items-center p-3 border border-gray-100 rounded-xl hover:border-blue-100 hover:bg-blue-50/50 transition-colors group">
                                    <div class="bg-blue-50 rounded-lg p-2 text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-sm font-semibold text-gray-800">บันทึกทั้งหมด</h3>
                                    </div>
                                </a>
                                <a href="./?page=statistics" class="flex items-center p-3 border border-gray-100 rounded-xl hover:border-emerald-100 hover:bg-emerald-50/50 transition-colors group">
                                    <div class="bg-emerald-50 rounded-lg p-2 text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-sm font-semibold text-gray-800">รายงานสถิติ</h3>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-gray-100/80 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] p-6 flex flex-col">
                            <div class="flex items-center justify-between mb-5">
                                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    ล่าสุดวันนี้
                                </h2>
                                <a href="./?page=reports" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center">
                                    ดูทั้งหมด
                                    <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>

                            <div class="space-y-3 flex-1">
                                <?php if (empty($reports)): ?>
                                    <div class="h-full flex flex-col items-center justify-center text-gray-400">
                                        <p class="text-sm">ไม่มีรายการแจ้งซ่อมใหม่</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($reports, 0, 3) as $report) : ?>
                                        <div class="group flex items-start gap-3 p-3 rounded-xl hover:bg-slate-50 border border-transparent hover:border-gray-100 transition-colors">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="text-xs font-mono font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded"><?php echo $report['code'] ?></span>
                                                    <?php
                                                    $latest = latest_status($report);
                                                    $statusName  = $latest['name']  ?? '-';
                                                    $colorClass  = $latest['style'] ?? 'bg-gray-100 text-gray-800';
                                                    ?>
                                                    <span class="px-2 py-0.5 <?php echo $colorClass ?> rounded text-[10px] font-medium whitespace-nowrap"><?php echo $statusName ?></span>
                                                </div>
                                                <h3 class="text-sm font-semibold text-gray-800 truncate"><?php echo $report['symptom_name'] ?></h3>
                                                <p class="text-xs text-gray-500 mt-1 truncate"><?php echo $report['reporter_name'] ?> • <?php echo diffLargestThai($report['created_at']) ?></p>
                                            </div>
                                            <a href="./?page=work&id=<?php echo $report['id'] ?>" class="text-gray-300 hover:text-indigo-600 transition-colors p-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <?php if (isset($user)): ?>
                        <div class="bg-white rounded-2xl border border-gray-100/80 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] p-6">
                            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-5">ข้อมูลผู้ใช้</h3>
                            <div class="flex items-center mb-6">
                                <div class="w-14 h-14 bg-gradient-to-tr from-indigo-500 to-blue-500 rounded-2xl shadow-sm flex items-center justify-center text-white text-xl font-bold">
                                    <?php echo mb_substr($user['display_th'], 0, 1, 'UTF-8'); ?>
                                </div>
                                <div class="ml-4">
                                    <p class="text-xs text-gray-500 font-medium mb-0.5">ยินดีต้อนรับ,</p>
                                    <h4 class="font-bold text-gray-800"><?php echo $user['display_th']; ?></h4>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <a href="./?page=profile" class="w-full text-center px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-sm font-semibold hover:bg-white hover:border-indigo-200 hover:text-indigo-600 transition-all">
                                    จัดการโปรไฟล์
                                </a>
                                <a href="./?page=logout" class="w-full text-center px-4 py-2.5 text-red-500 rounded-xl text-sm font-semibold hover:bg-red-50 transition-colors">
                                    ออกจากระบบ
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-2xl border border-gray-100/80 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] p-6 text-center">
                            <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-indigo-500">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-1">เข้าสู่ระบบ</h3>
                            <p class="text-sm text-gray-500 mb-6">กรุณาเข้าสู่ระบบเพื่อใช้งาน</p>
                            <a href="./?page=login" class="block w-full px-4 py-2.5 bg-gray-900 text-white rounded-xl text-sm font-semibold hover:bg-indigo-600 transition-colors shadow-sm">
                                เข้าสู่ระบบ
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-2xl border border-gray-100/80 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start gap-4 mb-5">
                                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800 leading-tight mb-1">โรงพยาบาลเมตตาประชารักษ์</h3>
                                    <p class="text-xs text-gray-500">(วัดไร่ขิง)</p>
                                </div>
                            </div>

                            <div class="bg-slate-50 rounded-xl p-4 mb-5 border border-gray-100">
                                <p class="text-sm font-semibold text-gray-800">ระบบ Helpdesk</p>
                                <p class="text-xs text-gray-500 mt-0.5">Service Management System</p>
                            </div>

                            <div class="space-y-2.5">
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                    </svg>
                                    <span>พัฒนาโดย: <strong class="text-gray-700 font-medium">นายภาคภูมิ ทีดินดำ</strong></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>© <?php echo date("Y") + 543; ?>
                                        <a href="https://metta.go.th" target="_blank" class="text-indigo-600 hover:underline">metta.go.th</a>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="h-1 bg-blue-600"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>

</html>