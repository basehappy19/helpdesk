<?php
require_once __DIR__ . "../../functions/reports.php";
require_once __DIR__ . "../../functions/status.php";
require_once __DIR__ . "../../functions/time.php";

// รับค่าจาก URL (Filter Parameters)
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'rt'     => trim($_GET['rt'] ?? ''),
    'cat'    => trim($_GET['cat'] ?? ''),
    'sym'    => trim($_GET['sym'] ?? '')
];

// --- ตั้งค่าระบบ Pagination ---
$limit = 20; // จำนวนรายการต่อหน้า
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $limit;

// ดึงข้อมูลและจำนวนทั้งหมดโดยส่ง $filters เข้าไปด้วย
$reports = getAllReports($limit, $offset, $filters);
$totalReports = getTotalReportsCount($filters);
$totalPages = ceil($totalReports / $limit);

// ฟังก์ชันช่วยเหลือสำหรับสร้าง URL Pagination ให้จำค่า Filter เดิมไว้
function buildPageUrl($pageNum)
{
    // นำ $_GET ปัจจุบันมาแทนที่ค่า p (page)
    $params = array_merge($_GET, ['p' => $pageNum]);
    return '?' . http_build_query($params);
}

$statusList = getStatuses();
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

    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">บันทึกทั้งหมด</h1>
                    <p class="text-gray-500 mt-1">รายการแจ้งปัญหาและขอรับบริการทั้งหมดในระบบ (<?php echo number_format($totalReports); ?> รายการ)</p>
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

            <div class="bg-white rounded-t-2xl shadow-sm border-b border-gray-100 p-4 sm:p-6">
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
                            <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        ไม่พบข้อมูลรายการแจ้งปัญหา
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $report) :
                                    $latest = latest_status($report);
                                    $statusName  = $latest['name']  ?? '-';
                                    $colorClass  = $latest['style'] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                    <tr class="hover:bg-blue-50/50 transition-colors duration-150 group">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-mono font-semibold text-blue-600"><?php echo htmlspecialchars($report['code']); ?></div>
                                            <div class="text-xs text-gray-500 mt-1 flex items-center">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <?php echo diffLargestThai($report['created_at']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($report['category_name']); ?></div>
                                            <div class="text-sm text-gray-500 mt-0.5 line-clamp-1"><?php echo htmlspecialchars($report['symptom_name']); ?></div>
                                            <div class="text-xs text-indigo-500 mt-1 bg-indigo-50 inline-block px-2 py-0.5 rounded"><?php echo htmlspecialchars($report['request_type_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 flex items-center">
                                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center text-xs font-bold text-gray-600 mr-2">
                                                    <?php echo mb_substr($report['reporter_name'], 0, 1, 'UTF-8'); ?>
                                                </div>
                                                <?php echo htmlspecialchars($report['reporter_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-1 ml-8"><?php echo htmlspecialchars($report['department'] ?? '-'); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $colorClass; ?> shadow-sm">
                                                <?php echo $statusName; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a target="_blank" href="./?page=report-detail&id=<?php echo $report['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 hover:text-blue-600 hover:border-blue-300 transition-all shadow-sm group-hover:shadow">
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
                                    <span class="font-medium"><?php echo min($offset + $limit, $totalReports); ?></span> จาก
                                    <span class="font-medium"><?php echo $totalReports; ?></span> รายการ
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <a href="<?php echo buildPageUrl(max(1, $page - 1)); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo $page <= 1 ? 'pointer-events-none opacity-50' : ''; ?>">
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </a>

                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="<?php echo buildPageUrl($i); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i === $page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <a href="<?php echo buildPageUrl(min($totalPages, $page + 1)); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo $page >= $totalPages ? 'pointer-events-none opacity-50' : ''; ?>">
                                        <span class="sr-only">Next</span>
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const filterRt = document.getElementById('filter_rt');
            const filterCat = document.getElementById('filter_cat');
            const filterSym = document.getElementById('filter_sym');

            // อ่านค่าที่เลือกไว้จาก URL (เพื่อให้ Dropdown จำค่าหลังกดค้นหา)
            const urlParams = new URLSearchParams(window.location.search);
            const initialRt = urlParams.get('rt') || '';
            const initialCat = urlParams.get('cat') || '';
            const initialSym = urlParams.get('sym') || '';

            // ฟังก์ชันช่วยเหลือสำหรับหลีกเลี่ยง HTML injection
            function escapeHtml(str) {
                return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            // 1. โหลด Request Types ตอนเริ่ม
            try {
                const res = await fetch('/api/request_types/get.php', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const payload = await res.json();
                const data = payload?.data ?? payload ?? [];

                let opts = ['<option value="">— ทั้งหมด —</option>'];
                data.forEach(item => {
                    const selected = (item.id == initialRt || item.code == initialRt) ? 'selected' : '';
                    opts.push(`<option value="${escapeHtml(item.id)}" data-code="${escapeHtml(item.code)}" ${selected}>${escapeHtml(item.name_th)}</option>`);
                });
                filterRt.innerHTML = opts.join('');

                // ถ้ามีค่าเริ่มต้น ให้โหลด Category ต่อเลย
                if (initialRt) {
                    await loadCategories(initialRt, initialCat);
                }
            } catch (err) {
                console.error('Failed to load request types', err);
            }

            // Event: เมื่อเปลี่ยน Request Type
            filterRt.addEventListener('change', async function() {
                filterCat.innerHTML = '<option value="">— ทั้งหมด —</option>';
                filterCat.disabled = true;
                filterSym.innerHTML = '<option value="">— ทั้งหมด —</option>';
                filterSym.disabled = true;

                if (this.value) {
                    await loadCategories(this.value, '');
                }
            });

            // 2. ฟังก์ชันโหลด Categories
            async function loadCategories(rtId, preselectId) {
                filterCat.innerHTML = '<option value="">กำลังโหลด...</option>';
                try {
                    const res = await fetch(`/api/categories/get_by_request_type.php?request_type_id=${encodeURIComponent(rtId)}`);
                    const payload = await res.json();
                    const data = payload?.data ?? [];

                    let opts = ['<option value="">— ทั้งหมด —</option>'];
                    data.forEach(item => {
                        const selected = (item.id == preselectId || item.code == preselectId) ? 'selected' : '';
                        opts.push(`<option value="${escapeHtml(item.id)}" ${selected}>${escapeHtml(item.name_th)}</option>`);
                    });
                    filterCat.innerHTML = opts.join('');
                    filterCat.disabled = false;

                    // ถ้ามีค่าเริ่มต้น ให้โหลด Symptom ต่อเลย
                    if (preselectId) {
                        await loadSymptoms(preselectId, initialSym);
                    }
                } catch (err) {
                    console.error(err);
                    filterCat.innerHTML = '<option value="">— ทั้งหมด —</option>';
                }
            }

            // Event: เมื่อเปลี่ยน Category
            filterCat.addEventListener('change', async function() {
                filterSym.innerHTML = '<option value="">— ทั้งหมด —</option>';
                filterSym.disabled = true;

                if (this.value) {
                    await loadSymptoms(this.value, '');
                }
            });

            // 3. ฟังก์ชันโหลด Symptoms
            async function loadSymptoms(catId, preselectId) {
                filterSym.innerHTML = '<option value="">กำลังโหลด...</option>';
                try {
                    const res = await fetch(`/api/symptoms/get_by_issue_category.php?issue_category_id=${encodeURIComponent(catId)}`);
                    const payload = await res.json();
                    const data = payload?.data ?? [];

                    let opts = ['<option value="">— ทั้งหมด —</option>'];
                    data.forEach(item => {
                        const selected = (item.id == preselectId || item.code == preselectId) ? 'selected' : '';
                        opts.push(`<option value="${escapeHtml(item.id)}" ${selected}>${escapeHtml(item.name_th)}</option>`);
                    });
                    filterSym.innerHTML = opts.join('');
                    filterSym.disabled = false;
                } catch (err) {
                    console.error(err);
                    filterSym.innerHTML = '<option value="">— ทั้งหมด —</option>';
                }
            }
        });
    </script>
</body>

</html>