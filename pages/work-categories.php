<?php

if (!isset($user['id']) || $user['role'] !== 'SYSTEM') {
    header('Location: ./');
    exit();
}

global $pdo;
require_once __DIR__ . '/../controllers/WorkCategoriesController.php';

$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 100;

$manageController = new WorkCategoriesController($pdo);
$response = $manageController->handleRequest();

if ($response) {
    $_SESSION['toast_message'] = $response['message'];
    $_SESSION['toast_status'] = $response['status'];
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

$flash_message = null;
$flash_status = null;
if (isset($_SESSION['toast_message'])) {
    $flash_message = $_SESSION['toast_message'];
    $flash_status = $_SESSION['toast_status'];
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_status']);
}

$result = $manageController->getAllCategories($currentPage, $limit);
$categories = $result['categories'];
$totalPages = $result['total_pages'];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหมวดหมู่ภาระงาน | HelpDesk</title>
    <?php include './lib/style.php'; ?>
    <style>
        .hot-toast {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(-150%) scale(0.9);
            opacity: 0;
            background: white;
            color: #374151;
            padding: 12px 16px;
            border-radius: 9999px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.35s cubic-bezier(0.21, 1.02, 0.73, 1);
            z-index: 99999;
            pointer-events: none;
        }

        .hot-toast.show {
            transform: translateX(-50%) translateY(0) scale(1);
            opacity: 1;
        }
    </style>
</head>

<body class="bg-slate-50 font-sans antialiased text-gray-800">
    <?php include './components/navbar.php'; ?>
    <div id="toast-container"></div>

    <div class="min-h-screen pb-12">
        <div class="max-w-7xl mx-auto md:px-4 sm:px-6 lg:px-8 py-8">

            <div class="md:px-0 px-4 flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">จัดการหมวดหมู่ภาระงาน</h1>
                    <p class="text-sm text-gray-500 mt-1">เพิ่ม แก้ไข และลบหมวดหมู่ของงานในระบบ</p>
                </div>
                <button onclick="openModal('add')" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    เพิ่มหมวดหมู่ใหม่
                </button>
            </div>

            <div class="bg-white md:rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-medium">
                            <tr>
                                <th class="px-6 py-4">ชื่อหมวดหมู่</th>
                                <th class="px-6 py-4 text-center">จำนวนงานที่ใช้</th>
                                <th class="px-6 py-4 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($categories as $cat): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        <?php echo htmlspecialchars($cat['name_th']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($cat['task_count'] > 0): ?>
                                            <button onclick="viewLinkedTasks(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name_th'], ENT_QUOTES); ?>')" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors border border-blue-100 group">
                                                <?php echo $cat['task_count']; ?> งาน
                                                <svg class="w-3.5 h-3.5 ml-1 opacity-50 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                                0 งาน
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button onclick='openEditModal(<?php echo json_encode($cat); ?>)' class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors" title="แก้ไข">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>

                                        <?php if ($cat['task_count'] > 0): ?>
                                            <button disabled class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 cursor-not-allowed" title="ไม่สามารถลบได้เนื่องจากมีงานผูกอยู่">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <form action="" method="POST" class="inline-block" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบหมวดหมู่ <?php echo htmlspecialchars($cat['name_th']); ?>?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="cat_id" value="<?php echo $cat['id']; ?>">
                                                <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors" title="ลบ">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">ไม่พบข้อมูลหมวดหมู่</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                </div>
                <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 bg-white border-t border-slate-100 flex items-center justify-between">
                        <div class="hidden sm:block">
                            <p class="text-sm text-slate-500">
                                แสดงหน้า <span class="font-semibold text-slate-900"><?php echo $currentPage; ?></span> จากทั้งหมด <span class="font-semibold text-slate-900"><?php echo $totalPages; ?></span> หน้า
                            </p>
                        </div>

                        <nav class="flex items-center gap-1" aria-label="Pagination">
                            <a href="?page=work-categories&p=<?php echo max(1, $currentPage - 1); ?>"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-indigo-600 transition-all <?php echo ($currentPage <= 1) ? 'opacity-40 pointer-events-none' : ''; ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </a>

                            <div class="flex items-center gap-1 mx-1">
                                <?php
                                $window = 2;
                                $start = max(1, $currentPage - $window);
                                $end = min($totalPages, $currentPage + $window);

                                if ($start > 1): ?>
                                    <a href="?page=work-categories&p=1" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">1</a>
                                    <?php if ($start > 2): ?><span class="text-slate-300">...</span><?php endif; ?>
                                <?php endif;

                                for ($i = $start; $i <= $end; $i++): ?>
                                    <a href="?page=work-categories&p=<?php echo $i; ?>"
                                        class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold transition-all <?php echo ($i == $currentPage) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-indigo-50 hover:text-indigo-600'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor;

                                if ($end < $totalPages): ?>
                                    <?php if ($end < $totalPages - 1): ?><span class="text-slate-300">...</span><?php endif; ?>
                                    <a href="?page=work-categories&p=<?php echo $totalPages; ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"><?php echo $totalPages; ?></a>
                                <?php endif; ?>
                            </div>

                            <a href="?page=work-categories&p=<?php echo min($totalPages, $currentPage + 1); ?>"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-indigo-600 transition-all <?php echo ($currentPage >= $totalPages) ? 'opacity-40 pointer-events-none' : ''; ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="categoryModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-900" id="modalTitle">เพิ่มหมวดหมู่ใหม่</h3>
                    <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form id="categoryForm" action="" method="POST" class="p-6 space-y-4">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="cat_id" id="catId" value="">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อหมวดหมู่ <span class="text-red-500">*</span></label>
                        <input type="text" name="name_th" id="name_th" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-colors">
                    </div>
                    <div class="pt-4 flex gap-3 justify-end">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">ยกเลิก</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="viewTasksModal" class="fixed inset-0 z-[100] hidden opacity-0 transition-opacity duration-300 ease-out">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeViewTasksModal()"></div>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div id="viewTasksModalContent" class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform scale-95 opacity-0 transition-all duration-300 ease-out sm:my-8 max-w-2xl w-full flex flex-col max-h-[80vh]">

                <div class="bg-white px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-900">
                        งานในหมวดหมู่: <span id="viewTasksTitle" class="text-indigo-600 font-medium"></span>
                    </h3>
                    <button type="button" onclick="closeViewTasksModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-grow bg-slate-50/50">
                    <div id="tasksLoading" class="text-center py-8 text-slate-500 hidden">
                        <svg class="animate-spin h-8 w-8 text-indigo-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        กำลังโหลดข้อมูล...
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 whitespace-nowrap">วันที่ / เวลา</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 whitespace-nowrap">ผู้บันทึก</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">รายละเอียดงาน</th>
                                </tr>
                            </thead>
                            <tbody id="tasksTableBody" class="divide-y divide-slate-100 text-sm">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ระบบ Toast Notification
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

        // โหลดข้อความแจ้งเตือนเมื่อมีการ Redirect จาก PHP
        <?php if ($flash_message): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showToast("<?php echo addslashes($flash_message); ?>", "<?php echo $flash_status; ?>");
            });
        <?php endif; ?>

        // Modal จัดการหมวดหมู่ (เพิ่ม/แก้ไข)
        const modal = document.getElementById('categoryModal');
        const form = document.getElementById('categoryForm');

        function openModal(mode) {
            form.reset();
            const isAdd = mode === 'add';
            document.getElementById('modalTitle').textContent = isAdd ? 'เพิ่มหมวดหมู่ใหม่' : 'แก้ไขหมวดหมู่';
            document.getElementById('formAction').value = mode;
            modal.classList.remove('hidden');
        }

        function openEditModal(data) {
            openModal('edit');
            document.getElementById('catId').value = data.id;
            document.getElementById('name_th').value = data.name_th;
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        // ฟังก์ชันแปลงวันที่เป็นภาษาไทย (พ.ศ.)
        function formatThaiDate(dateString) {
            if (!dateString) return '';
            const months = [
                'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
                'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
            ];
            const date = new Date(dateString);
            const d = date.getDate();
            const m = months[date.getMonth()];
            const y = date.getFullYear() + 543;
            return `${d} ${m} ${y}`;
        }

        // Modal ดูงานที่ผูกกับหมวดหมู่ (พร้อมแอนิเมชันเปิด)
        function viewLinkedTasks(categoryId, categoryName) {
            document.getElementById('viewTasksTitle').textContent = categoryName;
            document.getElementById('tasksTableBody').innerHTML = '';
            document.getElementById('tasksLoading').classList.remove('hidden');

            const viewModal = document.getElementById('viewTasksModal');
            const modalContent = document.getElementById('viewTasksModalContent');

            // แสดง Modal โดยลบ hidden ออกก่อน
            viewModal.classList.remove('hidden');

            // สั่งให้เบราว์เซอร์รับรู้การแสดงผลก่อนเริ่มแอนิเมชัน
            setTimeout(() => {
                viewModal.classList.remove('opacity-0');
                viewModal.classList.add('opacity-100');
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);

            fetch(`/api/work_categories/get_logs.php?id=${categoryId}`)
                .then(r => r.json())
                .then(d => {
                    document.getElementById('tasksLoading').classList.add('hidden');
                    if (d.ok && d.data.length > 0) {
                        let html = '';
                        d.data.forEach(task => {
                            // เรียกใช้ formatThaiDate เพื่อแปลงวันที่ตรงนี้
                            const thaiDate = formatThaiDate(task.work_date);

                            html += `
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap align-top">
                                    <div class="font-medium text-slate-800">${thaiDate}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        ${task.start_time.substring(0,5)} - ${task.end_time.substring(0,5)} น.
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-700 align-top">
                                    ${escapeHtml(task.username)}
                                </td>
                                <td class="px-4 py-3 text-slate-600 align-top">
                                    ${escapeHtml(task.activity_detail)}
                                </td>
                            </tr>
                        `;
                        });
                        document.getElementById('tasksTableBody').innerHTML = html;
                    } else {
                        document.getElementById('tasksTableBody').innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">ไม่พบข้อมูลงานที่เชื่อมโยง</td></tr>';
                    }
                })
                .catch(e => {
                    document.getElementById('tasksLoading').classList.add('hidden');
                    document.getElementById('tasksTableBody').innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-red-500">เกิดข้อผิดพลาดในการดึงข้อมูล</td></tr>';
                });
        }

        // ปิด Modal พร้อมแอนิเมชันสมูทๆ
        function closeViewTasksModal() {
            const viewModal = document.getElementById('viewTasksModal');
            const modalContent = document.getElementById('viewTasksModalContent');

            // เริ่มเล่นแอนิเมชันหดตัวและจางหาย
            viewModal.classList.remove('opacity-100');
            viewModal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');

            // รอแอนิเมชันจบ (300ms ให้ตรงกับ duration-300) ค่อยซ่อน DOM ทิ้ง
            setTimeout(() => {
                viewModal.classList.add('hidden');
            }, 300);
        }

        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>

</html>