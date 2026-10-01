<?php

require_once __DIR__ . "/../models/TicketModel.php";
$ticketModel = new TicketModel($pdo);

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'rt'     => trim($_GET['rt'] ?? ''),
    'cat'    => trim($_GET['cat'] ?? ''),
    'sym'    => trim($_GET['sym'] ?? '')
];

$limit = 20;
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $limit;

$tickets = $ticketModel->getAllTickets($limit, $offset, $filters);
$totalTickets = $ticketModel->getTotalTicketsCount($filters);
$totalPages = ceil($totalTickets / $limit);

function buildPageUrl($pageNum)
{
    $params = array_merge($_GET, ['p' => $pageNum]);
    return '?' . http_build_query($params);
}

$stmtStatus = $pdo->query("SELECT code, name_th FROM ticket_statuses ORDER BY sort_order ASC, id ASC");
$statusList = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกทั้งหมด | HelpDesk</title>
    <?php include './lib/style.php'; ?>
</head>

<body class="bg-gray-50">
    <?php include './components/navbar.php'; ?>
    <div id="toast-container"></div>

    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen pb-12">
        <div class="max-w-7xl mx-auto md:px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex flex-col md:flex-row md:items-center md:px-0 px-4 justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">บันทึกทั้งหมด</h1>
                    <p class="text-gray-500 mt-1">รายการแจ้งปัญหาและขอรับบริการทั้งหมดในระบบ (<?php echo number_format($totalTickets); ?> รายการ)</p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="./?page=report" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium shadow-sm hover:bg-blue-700 hover:shadow-md transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        แจ้งปัญหาใหม่
                    </a>
                </div>
            </div>

            <div class="bg-white md:rounded-t-2xl shadow-sm border-b border-gray-100 p-4 sm:p-6">
                <form id="filterForm" action="./" method="GET" class="flex flex-col gap-4">
                    <input type="hidden" name="page" value="reports">
                    <div class="flex flex-col sm:flex-row gap-4 justify-between">
                        <div class="relative flex-1 max-w-md">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="ค้นหา รหัส, ชื่อผู้แจ้ง..." class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors duration-200">
                        </div>

                        <div class="flex gap-2">
                            <select name="status" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-xl bg-gray-50 hover:bg-white transition-colors duration-200">
                                <option value="">ทุกสถานะ</option>
                                <?php foreach ($statusList as $st): ?>
                                    <option value="<?php echo htmlspecialchars($st['code']); ?>" <?php echo ($_GET['status'] ?? '') === $st['code'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($st['name_th']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="flex flex-row gap-1">
                                <a href="./?page=reports" class="inline-flex items-center cursor-pointer px-4 py-2 bg-gray-800 text-white rounded-xl hover:bg-gray-700 transition-colors duration-200 shadow-sm text-sm whitespace-nowrap">
                                    เคลียร์
                                </a>
                                <button type="submit" class="cursor-pointer px-4 py-2 bg-blue-800 text-white rounded-xl hover:bg-blue-700 transition-colors duration-200 shadow-sm text-sm whitespace-nowrap">
                                    ค้นหา
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-t border-gray-100 pt-4 mt-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ประเภทงาน (Request Type)</label>
                            <select id="filter_rt" name="rt" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:text-sm rounded-xl bg-gray-50">
                                <option value="">— ทั้งหมด —</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">หมวดหมู่ (Category)</label>
                            <select id="filter_cat" name="cat" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:text-sm rounded-xl bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <option value="">— ทั้งหมด —</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">อาการปัญหา (Symptom)</label>
                            <select id="filter_sym" name="sym" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:text-sm rounded-xl bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <option value="">— ทั้งหมด —</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-b-2xl shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">รหัส / วันที่แจ้ง</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">รายละเอียดปัญหา</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ผู้แจ้ง / หน่วยงาน</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">สถานะล่าสุด</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        ไม่พบข้อมูลรายการแจ้งปัญหา
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $ticket) :
                                    // ดึงสถานะล่าสุดจาก Array Log ช่องแรกสุด ถ้าไม่มีให้แสดง 'รอดำเนินการ'
                                    $latestLog   = $ticket['ticket_status_logs'][0] ?? null;
                                    $statusName  = $latestLog ? $latestLog['to_status_name'] : 'รอดำเนินการ';
                                    $colorClass  = $latestLog ? $latestLog['to_status_style'] : 'bg-gray-100 text-gray-800';
                                ?>
                                    <tr class="hover:bg-blue-50/50 transition-colors duration-150 group">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-mono font-semibold text-blue-600"><?php echo htmlspecialchars($ticket['code']); ?></div>
                                            <div class="text-xs text-gray-500 mt-1 flex items-center">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <?php echo DateHelper::diffLargestThai($ticket['created_at']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($ticket['display_category'] ?? '-'); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-0.5 line-clamp-1">
                                                <?php echo htmlspecialchars($ticket['display_symptom'] ?? '-'); ?>
                                            </div>
                                            <div class="text-xs text-indigo-500 mt-1 bg-indigo-50 inline-block px-2 py-0.5 rounded">
                                                <?php echo htmlspecialchars($ticket['request_type_name'] ?? '-'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 flex items-center">
                                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center text-xs font-bold text-gray-600 mr-2">
                                                    <?php echo mb_substr($ticket['reporter_name'], 0, 1, 'UTF-8'); ?>
                                                </div>
                                                <?php echo htmlspecialchars($ticket['reporter_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-1 ml-8"><?php echo htmlspecialchars($ticket['department'] ?? '-'); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $colorClass; ?> shadow-sm">
                                                <?php echo $statusName; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a target="_blank" href="./?page=report-detail&code=<?php echo $ticket['code']; ?>" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 hover:text-blue-600 hover:border-blue-300 transition-all shadow-sm group-hover:shadow">
                                                ดูรายละเอียด
                                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between sm:px-6">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    แสดง <span class="font-medium"><?php echo $offset + 1; ?></span> ถึง
                                    <span class="font-medium"><?php echo min($offset + $limit, $totalTickets); ?></span> จาก
                                    <span class="font-medium"><?php echo $totalTickets; ?></span> รายการ
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>

</html>