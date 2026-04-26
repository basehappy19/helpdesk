<?php
if (!isset($user['id']) || !in_array($user['role'], ['SYSTEM', 'ADMIN'])) {
    header('Location: ./');
    exit();
}

global $pdo;
require_once __DIR__ . '/../controllers/ManageIssueSymptomsController.php';

$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 50;

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

$result = $controller->getAll($currentPage, $limit);
$items = $result['data'];
$totalPages = $result['total_pages'];
$categories = $controller->getCategories(); // ดึงหมวดหมู่พร้อมประเภทหลักมาโชว์ใน Dropdown
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการอาการและ SLA (Symptoms) | HelpDesk</title>
    <?php include './lib/style.php'; ?>
</head>
<body class="bg-slate-50 font-sans antialiased text-gray-800">
    <?php include './components/navbar.php'; ?>
    <div id="toast-container"></div>

    <div class="min-h-screen pb-12">
        <div class="max-w-7xl mx-auto md:px-4 sm:px-6 lg:px-8 py-8">
            <div class="md:px-0 px-4 flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">จัดการอาการปัญหา</h1>
                    <p class="text-sm text-gray-500 mt-1">กำหนดอาการย่อยและตั้งค่าเวลา SLA ในการแก้ไข (นาที)</p>
                </div>
                <button onclick="openModal('add')" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    เพิ่มอาการ
                </button>
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
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button onclick='openEditModal(<?php echo json_encode($item); ?>)' class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors" title="แก้ไข">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <form action="" method="POST" class="inline-block" onsubmit="return confirm('ยืนยันการลบข้อมูลนี้?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors" title="ลบ">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
        <?php if ($flash_message): ?>
            document.addEventListener('DOMContentLoaded', () => { showToast("<?php echo addslashes($flash_message); ?>", "<?php echo $flash_status; ?>"); });
        <?php endif; ?>

        const modal = document.getElementById('itemModal');
        const form = document.getElementById('itemForm');

        function openModal(mode) {
            form.reset();
            document.getElementById('modalTitle').textContent = mode === 'add' ? 'เพิ่มอาการปัญหา' : 'แก้ไขอาการปัญหา';
            document.getElementById('formAction').value = mode;
            if (mode === 'add') document.getElementById('sla_minutes').value = 15; // ค่าเริ่มต้น SLA
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