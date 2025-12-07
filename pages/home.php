<?php
require_once __DIR__ . "../../functions/status.php";
require_once __DIR__ . "../../functions/time.php";

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$apiUrl = $scheme . '://' . $host . '/api/reports/get_recent_report.php';

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

<body class="bg-gray-50">
    <?php
    include './components/navbar.php';
    ?>

    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Hero Section -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-12">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="text-white mb-6 md:mb-0">
                            <h1 class="text-4xl font-bold mb-2">ระบบ HelpDesk</h1>
                            <p class="text-blue-100 text-lg">โรงพยาบาลเมตตาประชารักษ์
                                (วัดไร่ขิง)</p>
                        </div>
                        <div>
                            <a href="./?page=report" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                แจ้งปัญหา/บริการ
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <!-- Main Content Area -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Quick Stats -->

                    <?php
                    $statuses = $statistics['statuses'];
                    $lastIndex = count($statuses) - 1;

                    // สี gradient และไอคอนสำหรับแต่ละสถานะตาม code
                    $statusStyles = [
                        'WAITING' => [
                            'gradient' => 'from-gray-400 to-gray-500',
                            'iconBg' => 'bg-gray-100',
                            'iconColor' => 'text-gray-600',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                            'borderColor' => 'border-gray-400'
                        ],
                        'RECEIVED' => [
                            'gradient' => 'from-sky-400 to-sky-500',
                            'iconBg' => 'bg-sky-100',
                            'iconColor' => 'text-sky-600',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                            'borderColor' => 'border-sky-400'
                        ],
                        'INSPECTING' => [
                            'gradient' => 'from-yellow-400 to-yellow-500',
                            'iconBg' => 'bg-yellow-100',
                            'iconColor' => 'text-yellow-600',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>',
                            'borderColor' => 'border-yellow-400'
                        ],
                        'WAITING_PART' => [
                            'gradient' => 'from-gray-700 to-gray-800',
                            'iconBg' => 'bg-gray-200',
                            'iconColor' => 'text-gray-700',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>',
                            'borderColor' => 'border-gray-700'
                        ],
                        'REPAIRING' => [
                            'gradient' => 'from-blue-600 to-blue-700',
                            'iconBg' => 'bg-blue-100',
                            'iconColor' => 'text-blue-600',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>',
                            'borderColor' => 'border-blue-600'
                        ],
                        'COMPLETED' => [
                            'gradient' => 'from-green-500 to-green-600',
                            'iconBg' => 'bg-green-100',
                            'iconColor' => 'text-green-600',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                            'borderColor' => 'border-green-500'
                        ]
                    ];

                    // ฟังก์ชันหา style ตาม code
                    function getStatusStyle($statusCode, $statusStyles)
                    {
                        if (isset($statusStyles[$statusCode])) {
                            return $statusStyles[$statusCode];
                        }

                        // Default style
                        return [
                            'gradient' => 'from-gray-400 to-gray-500',
                            'iconBg' => 'bg-gray-100',
                            'iconColor' => 'text-gray-600',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>',
                            'borderColor' => 'border-gray-400'
                        ];
                    }
                    ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group border-l-4 border-indigo-500">

                            <!-- Gradient Header -->
                            <div class="h-2 bg-gradient-to-r from-indigo-500 to-purple-600"></div>

                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <!-- Left Section -->
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-4">
                                            <!-- Icon -->
                                            <div class="bg-indigo-100 rounded-xl p-3 group-hover:scale-110 transition-transform duration-300">
                                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </div>

                                            <!-- Label -->
                                            <div>
                                                <p class="text-gray-600 text-sm font-medium">
                                                    บันทึกทั้งหมด
                                                </p>
                                                <p class="text-xs text-gray-400 mt-0.5">
                                                    TOTAL_REPORTS
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Count -->
                                        <div class="flex items-end gap-2 mb-4">
                                            <p class="text-4xl font-bold text-gray-800 group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:from-indigo-500 group-hover:to-purple-600 transition-all duration-300">
                                                <?php echo number_format($statistics['total_reports_all']); ?>
                                            </p>
                                            <span class="text-gray-500 text-sm mb-2">รายการ</span>
                                        </div>
                                    </div>

                                    <!-- Right Section - Arrow Icon -->
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <svg class="w-6 h-6 text-gray-400 transform group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Info Bar -->
                                <div class="pt-4 border-t border-gray-100">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                            </svg>
                                            <span>สถิติรวมทุกสถานะ</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                            <span class="text-xs text-gray-500">อัพเดทแล้ว</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hover Effect Bottom Border -->
                            <div class="h-1 bg-gradient-to-r from-indigo-500 to-purple-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                        </div>
                        <?php foreach ($statuses as $i => $statistic) :
                            $style = getStatusStyle($statistic['code'], $statusStyles);
                        ?>
                            <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group border-l-4 <?php echo $style['borderColor']; ?>
            <?php echo $i === $lastIndex ? 'col-span-full' : ''; ?>">

                                <!-- Gradient Header -->
                                <div class="h-2 bg-gradient-to-r <?php echo $style['gradient']; ?>"></div>

                                <div class="p-6">
                                    <div class="flex items-start justify-between">
                                        <!-- Left Section -->
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-4">
                                                <!-- Icon -->
                                                <div class="<?php echo $style['iconBg']; ?> rounded-xl p-3 group-hover:scale-110 transition-transform duration-300">
                                                    <svg class="w-6 h-6 <?php echo $style['iconColor']; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <?php echo $style['icon']; ?>
                                                    </svg>
                                                </div>

                                                <!-- Status Name -->
                                                <div>
                                                    <p class="text-gray-600 text-sm font-medium">
                                                        <?php echo htmlspecialchars($statistic['name_th']); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-400 mt-0.5">
                                                        <?php echo htmlspecialchars($statistic['code']); ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Count -->
                                            <div class="flex items-end gap-2 mb-4">
                                                <p class="text-4xl font-bold text-gray-800 group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:<?php echo $style['gradient']; ?> transition-all duration-300">
                                                    <?php echo number_format($statistic['total_reports']); ?>
                                                </p>
                                                <span class="text-gray-500 text-sm mb-2">รายการ</span>
                                            </div>
                                        </div>

                                        <!-- Right Section - Arrow Icon -->
                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                            <svg class="w-6 h-6 text-gray-400 transform group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="pt-4 border-t border-gray-100">
                                        <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                                            <span>สัดส่วน</span>
                                            <span class="font-semibold">
                                                <?php
                                                $total = array_sum(array_column($statuses, 'total_reports'));
                                                $percentage = $total > 0 ? round(($statistic['total_reports'] / $total) * 100, 1) : 0;
                                                echo $percentage . '%';
                                                ?>
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                            <div class="bg-gradient-to-r <?php echo $style['gradient']; ?> h-2.5 rounded-full transition-all duration-500 group-hover:animate-pulse"
                                                style="width: <?php echo $percentage; ?>%">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hover Effect Bottom Border -->
                                <div class="h-1 bg-gradient-to-r <?php echo $style['gradient']; ?> transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <style>
                        /* Animation for number count effect */
                        @keyframes fadeInUp {
                            from {
                                opacity: 0;
                                transform: translateY(20px);
                            }

                            to {
                                opacity: 1;
                                transform: translateY(0);
                            }
                        }

                        /* Pulse animation for progress bar */
                        @keyframes pulse {

                            0%,
                            100% {
                                opacity: 1;
                            }

                            50% {
                                opacity: 0.8;
                            }
                        }

                        .animate-fadeInUp {
                            animation: fadeInUp 0.6s ease-out;
                        }
                    </style>
                    <!-- Quick Menu -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">เมนูหลัก</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Create Ticket -->
                            <a href="./?page=report" class="flex items-center p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 group">
                                <div class="bg-blue-100 rounded-lg p-3 group-hover:bg-blue-500 transition-colors duration-200">
                                    <svg class="w-6 h-6 text-blue-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-semibold text-gray-800 group-hover:text-blue-600">แจ้งปัญหา / บริการ</h3>
                                    <p class="text-sm text-gray-500">แจ้งปัญหาหรือขอความช่วยเหลือ</p>
                                </div>
                            </a>

                            <!-- View All Tickets -->
                            <a href="./?page=daily-works" class="flex items-center p-4 border-2 border-gray-200 rounded-xl hover:border-indigo-500 hover:bg-indigo-50 transition-all duration-200 group">
                                <div class="bg-indigo-100 rounded-lg p-3 group-hover:bg-indigo-500 transition-colors duration-200">
                                    <svg class="w-6 h-6 text-indigo-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-semibold text-gray-800 group-hover:text-indigo-600">บันทึกทั้งหมด</h3>
                                    <p class="text-sm text-gray-500">ดูรายการบันทึกทั้งหมด</p>
                                </div>
                            </a>

                            <!-- Reports -->
                            <a href="./?page=statistics" class="col-span-full flex items-center p-4 border-2 border-gray-200 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all duration-200 group">
                                <div class="bg-green-100 rounded-lg p-3 group-hover:bg-green-500 transition-colors duration-200">
                                    <svg class="w-6 h-6 text-green-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-semibold text-gray-800 group-hover:text-green-600">รายงาน</h3>
                                    <p class="text-sm text-gray-500">สถิติและรายงานต่างๆ</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Recent Tickets -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-800">บันทึกงานประจำวันล่าสุด</h2>
                            <a href="./?page=daily-works" class="text-blue-600 hover:text-blue-700 font-medium flex items-center">
                                ดูทั้งหมด
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                        <div class="space-y-4">
                            <!-- Ticket Item -->
                            <?php foreach ($reports as $report) : ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:shadow-md transition-all duration-200">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="text-sm font-mono text-gray-600"><?php echo $report['code'] ?></span>
                                                <?php
                                                $latest = latest_status($report);
                                                $statusName  = $latest['name']  ?? '-';
                                                $colorClass  = $latest['style'] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-3 py-1 <?php echo $colorClass ?> rounded-full text-xs font-medium"><?php echo $statusName ?></span>
                                            </div>
                                            <h3 class="font-semibold text-gray-800 mb-2"><?php echo $report['request_type_name'] . ": " . $report['category_name'] . " - " . $report['symptom_name'] ?></h3>
                                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    <?php echo $report['reporter_name'] ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <?php echo diffLargestThai($report['created_at']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <a href="./?page=work&id=<?php echo $report['id'] ?>" class="text-blue-600 hover:text-blue-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <?php if (isset($user)): ?>
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">ข้อมูลผู้ใช้</h3>
                            <div class="flex items-center mb-4">
                                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full w-16 h-16 flex items-center justify-center text-white text-2xl font-bold">
                                    <?php echo mb_substr($user['display_th'], 0, 1, 'UTF-8'); ?>
                                </div>
                                <div class="ml-4">
                                    <h4 class="font-semibold text-gray-800"><?php echo $user['display_th']; ?></h4>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-200 space-y-2">
                                <a href="profile.php" class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                    จัดการโปรไฟล์
                                </a>
                                <a href="logout.php" class="block w-full text-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                    ออกจากระบบ
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Login Card -->
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <div class="text-center">
                                <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800 mb-2">เข้าสู่ระบบ</h3>
                                <p class="text-sm text-gray-600 mb-6">เข้าสู่ระบบเพื่อใช้งานฟีเจอร์เพิ่มเติม</p>
                                <a href="./?page=login" class="block w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg">
                                    เข้าสู่ระบบ
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 rounded-2xl shadow-lg overflow-hidden">
                        <div class="p-6 text-white relative">
                            <!-- Decorative circles -->
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/10 rounded-full -ml-12 -mb-12"></div>

                            <div class="relative z-10">
                                <!-- Hospital Logo/Icon -->
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold leading-tight">โรงพยาบาลเมตตาประชารักษ์</h3>
                                        <p class="text-white/80 text-sm">(วัดไร่ขิง)</p>
                                    </div>
                                </div>

                                <!-- System Info -->
                                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-white/20 rounded-lg p-2">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-white/90 text-sm font-medium">ระบบ Helpdesk</p>
                                            <p class="text-white/60 text-xs">Service Management System</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Divider -->
                                <div class="border-t border-white/20 my-4"></div>

                                <!-- Copyright & Developer Info -->
                                <div class="space-y-3 text-sm">
                                    <!-- Copyright -->
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-white/60 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div>
                                            <span class="text-white/80">© <?php echo date("Y") + 543; ?> Copyright - </span>
                                            <a href="https://metta.go.th" target="_blank" class="text-white font-semibold hover:text-yellow-300 transition-colors duration-200 underline decoration-dotted">
                                                metta.go.th
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Developer -->
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-white/60 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                        </svg>
                                        <div>
                                            <span class="text-white/80">พัฒนาโดย : </span>
                                            <span class="text-white font-semibold">นายภาคภูมิ ทีดินดำ</span>
                                        </div>
                                    </div>

                                    <!-- Version (Optional) -->
                                    <div class="flex items-center gap-2 pt-2">
                                        <span class="px-2 py-1 bg-white/10 rounded-md text-xs text-white/70">
                                            Version 1.0
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom decorative bar -->
                        <div class="h-1.5 bg-gradient-to-r from-yellow-400 via-pink-400 to-purple-400"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>

</html>