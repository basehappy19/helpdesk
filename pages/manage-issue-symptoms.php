<?php
if (!isset($user['id']) || !in_array($user['role'], ['SYSTEM', 'ADMIN'])) {
    header('Location: ./');
    exit();
}

global $pdo;
require_once './controllers/ManageIssueSymptomsController.php';

$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 50;
$filterCategoryId = isset($_GET['filter_cat']) ? (int)$_GET['filter_cat'] : 0; // รับค่า Filter

$controller = new ManageIssueSymptomsController($pdo);
$response = $controller->handleRequest();

if ($response) {
    $_SESSION['toast_message'] = $response['message'];
    $_SESSION['toast_status'] = $response['status'];
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

$flash_message = $_SESSION['toast_message'] ?? null;
$flash_status = $_SESSION['toast_status'] ?? null;
unset($_SESSION['toast_message'], $_SESSION['toast_status']);

$result = $controller->getAll($currentPage, $limit, $filterCategoryId);
$items = $result['data'];
$totalPages = $result['total_pages'];
$categories = $controller->getCategories(); 
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการอาการปัญหา | HelpDesk</title>
    <?php include './lib/style.php'; ?>
    <style>
        .hot-toast {
            position: fixed; top: 24px; left: 50%;
            transform: translateX(-50%) translateY(-150%) scale(0.9);
            opacity: 0; background: white; color: #374151; padding: 12px 16px;
            border-radius: 9999px; box-shadow: 0 4px 12px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
            display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500;
            transition: all 0.35s cubic-bezier(0.21, 1.02, 0.73, 1); z-index: 99999; pointer-events: none;
        }
        .hot-toast.show { transform: translateX(-50%) translateY(0) scale(1); opacity: 1; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-gray-800">
    <?php include './components/navbar.php'; ?>
    <div id="toast-container"></div>

    <div class="min-h-screen pb-12">
        <div class="max-w-7xl mx-auto md:px-4 sm:px-6 lg:px-8 py-8">
            <div class="md:px-0 px-4 flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">จัดการอาการ</h1>
                    <p class="text-sm text-gray-500 mt-1">กำหนดอาการปัญหา และ ตั้งค่าเวลา SLA ในการแก้ไข (นาที)</p>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <form action="" method="GET" class="w-full sm:w-auto">
                        <input type="hidden" name="page" value="manage-issue-symptoms">
                        <select name="filter_cat" onchange="this.form.submit()" class="w-full sm:w-64 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            <option value="0">ดูทั้งหมดทุกประเภท</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $filterCategoryId == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name_th']) ?> (<?= htmlspecialchars($cat['rt_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    
                    <button onclick="openModal('add')" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        เพิ่มอาการ
                    </button>
                </div>
            </div>

            <div class="bg-white md:rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-medium">
                            <tr>
                                <th class="px-6 py-4">ประเภท</th>
                                <th class="px-6 py-4">รหัส (Code)</th>
                                <th class="px-6 py-4">ชื่ออาการ</th>
                                <th class="px-6 py-4 text-center">SLA (นาที)</th>
                                <th class="px-6 py-4 text-center">ถูกแจ้งปัญหา</th>
                                <th class="px-6 py-4 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 text-slate-600 text-xs">
                                        <div class="font-semibold text-slate-800"><?= htmlspecialchars($item['cat_name']) ?></div>
                                        <div class="text-slate-400 mt-0.5">ปัญหาการใช้งาน: <?= htmlspecialchars($item['rt_name']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-indigo-600"><?php echo htmlspecialchars($item['code'] ?? '-'); ?></td>
                                    <td class="px-6 py-4 font-medium text-slate-800"><?php echo htmlspecialchars($item['name_th']); ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-xs font-bold">
                                            <?php echo htmlspecialchars($item['sla_minutes']); ?> นาที
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-slate-500">
                                        <?= $item['ticket_count'] > 0 ? "<span class='text-blue-600 font-semibold'>{$item['ticket_count']}</span>" : "0" ?>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button onclick='openEditModal(<?php echo json_encode($item); ?>)' class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors" title="แก้ไข">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        
                                        <?php if ($item['ticket_count'] > 0): ?>
                                            <button disabled class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 cursor-not-allowed" title="ไม่สามารถลบได้ เนื่องจากถูกนำไปใช้แจ้งปัญหาแล้ว">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        <?php else: ?>
                                            <form action="" method="POST" class="inline-block" onsubmit="return confirm('ยืนยันการลบอาการนี้?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors" title="ลบ">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">ไม่พบข้อมูลอาการปัญหา</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="itemModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-900" id="modalTitle">เพิ่มอาการ</h3>
                </div>
                <form id="itemForm" action="" method="POST" class="p-6 space-y-4">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="itemId" value="">
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ประเภท <span class="text-red-500">*</span></label>
                        <select name="category_id" id="category_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <option value="">-- เลือกประเภท --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name_th']) ?> (<?= htmlspecialchars($cat['rt_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">ชื่ออาการ <span class="text-red-500">*</span></label>
                            <input type="text" name="name_th" id="name_th" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">รหัส (Code)</label>
                            <input type="text" name="code" id="code" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">SLA (นาที) <span class="text-red-500">*</span></label>
                            <input type="number" name="sla_minutes" id="sla_minutes" value="15" min="1" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3 justify-end">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">ยกเลิก</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">บันทึกข้อมูล</button>
                    </div>
                </form>
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
                `<svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>`;

            toast.innerHTML = `${iconHtml} <span>${message}</span>`;
            container.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }

        <?php if ($flash_message): ?>
            document.addEventListener('DOMContentLoaded', () => { showToast("<?php echo addslashes($flash_message); ?>", "<?php echo $flash_status; ?>"); });
        <?php endif; ?>

        const modal = document.getElementById('itemModal');
        const form = document.getElementById('itemForm');

        function openModal(mode) {
            form.reset();
            document.getElementById('modalTitle').textContent = mode === 'add' ? 'เพิ่มอาการ' : 'แก้ไขอาการ';
            document.getElementById('formAction').value = mode;
            if (mode === 'add') {
                document.getElementById('sla_minutes').value = 15;
                // ถ้ามีการ Filter อยู่ ให้เลือกหมวดหมู่นั้นเป็นค่าเริ่มต้น
                const currentFilter = "<?= $filterCategoryId ?>";
                if(currentFilter != "0") {
                    document.getElementById('category_id').value = currentFilter;
                }
            }
            modal.classList.remove('hidden');
        }

        function openEditModal(data) {
            openModal('edit');
            document.getElementById('itemId').value = data.id;
            document.getElementById('category_id').value = data.category_id;
            document.getElementById('code').value = data.code || '';
            document.getElementById('name_th').value = data.name_th;
            document.getElementById('sla_minutes').value = data.sla_minutes;
        }

        function closeModal() { modal.classList.add('hidden'); }
    </script>
</body>
</html>