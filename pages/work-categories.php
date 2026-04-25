<?php
global $pdo;

if (!isset($user) || $user['role'] !== 'SYSTEM') {
    echo "<div class='p-8 text-center text-red-500 font-bold'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>";
    exit;
}

$sql = "
    SELECT 
        c.id, 
        c.name_th, 
        c.created_at,
        COUNT(w.id) as task_count
    FROM work_log_categories c
    LEFT JOIN daily_work_logs w ON c.id = w.category_id
    GROUP BY c.id
    ORDER BY c.id DESC
";
$stmt = $pdo->query($sql);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหมวดหมู่ภาระงาน | HelpDesk</title>
    <?php include './lib/style.php'; ?>
    <style>
        /* สไตล์สำหรับ Toast Alert */
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

<body>
    <?php include './components/navbar.php'; ?>

    <div id="toast-container"></div>

    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen py-8 md:px-4">
        <div class="max-w-5xl mx-auto">

            <div class="mb-6">
                <a href="./?page=manage-users" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition-colors mb-4 font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    ย้อนกลับเมนูจัดการระบบ
                </a>
            </div>

            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 md:rounded-2xl shadow-xl p-8 mb-6 text-white">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold">หมวดหมู่ภาระงาน (อยู่ระหว่างพัฒนาระบบ)</h1>
        
                    </div>
                    <div class="flex items-center gap-4">
                        <button onclick="openCategoryModal()" class="px-4 py-2.5 bg-white/20 hover:bg-white/30 border border-white/40 rounded-xl text-white text-sm font-medium transition-all flex items-center gap-2 backdrop-blur-sm shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            เพิ่มหมวดหมู่ใหม่
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white md:rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        รายการหมวดหมู่ทั้งหมด
                    </h2>
                </div>

                <div class="p-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ชื่อหมวดหมู่</th>
                                    <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">จำนวนงานที่ใช้</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                            <p>ไม่มีข้อมูลหมวดหมู่</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr class="hover:bg-indigo-50/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                #<?= htmlspecialchars($cat['id']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($cat['name_th']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?php if ($cat['task_count'] > 0): ?>
                                                    <button onclick="viewLinkedTasks(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name_th'], ENT_QUOTES) ?>')" class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors group" title="คลิกเพื่อดูรายละเอียด">
                                                        <?= $cat['task_count'] ?> งาน
                                                        <svg class="w-4 h-4 ml-1.5 opacity-50 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-gray-100 text-gray-500">
                                                        0 งาน
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button onclick="openCategoryModal(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name_th'], ENT_QUOTES) ?>')" class="p-1.5 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-200 transition-colors mx-1" title="แก้ไข">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>

                                                <?php if ($cat['task_count'] > 0): ?>
                                                    <button disabled class="p-1.5 bg-gray-100 text-gray-400 rounded-lg cursor-not-allowed mx-1" title="ไม่สามารถลบได้เนื่องจากมีงานผูกอยู่">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="confirmDelete(<?= $cat['id'] ?>)" class="p-1.5 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors mx-1" title="ลบ">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                <?php endif; ?>
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

    <div id="categoryModal" class="fixed inset-0 backdrop-blur-sm bg-slate-900/60 hidden items-center justify-center z-[10000] p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md transform transition-all relative w-full">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl flex justify-between items-center">
                <h3 id="modalTitle" class="text-lg font-bold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    เพิ่มหมวดหมู่ใหม่
                </h3>
                <button type="button" onclick="closeCategoryModal()" class="text-white/80 hover:text-white focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="categoryForm" class="p-6 space-y-4">
                <input type="hidden" id="cat_id" name="id">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">ชื่อหมวดหมู่</label>
                    <input type="text" id="cat_name_th" name="name_th" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm" />
                </div>
                <div class="flex items-center gap-3 pt-4">
                    <button type="button" onclick="closeCategoryModal()" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors">ยกเลิก</button>
                    <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-medium hover:bg-indigo-700 transition-all shadow-sm">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteCatModal" class="fixed inset-0 backdrop-blur-sm bg-slate-900/60 hidden items-center justify-center z-[10000] p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm transform transition-all text-center p-6 relative w-full">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">ยืนยันการลบ?</h3>
            <p class="text-gray-500 mb-6 text-sm">คุณแน่ใจหรือไม่ว่าต้องการลบหมวดหมู่นี้ การกระทำนี้ไม่สามารถย้อนกลับได้</p>
            <input type="hidden" id="delete_cat_id">
            <div class="flex items-center gap-3">
                <button onclick="closeDeleteCatModal()" class="flex-1 bg-gray-100 text-gray-700 py-2.5 rounded-lg font-medium hover:bg-gray-200 transition-colors">ยกเลิก</button>
                <button onclick="deleteCategory()" class="flex-1 bg-red-600 text-white py-2.5 rounded-lg font-medium hover:bg-red-700 transition-all shadow-sm">ยืนยันลบ</button>
            </div>
        </div>
    </div>

    <div id="viewTasksModal" class="fixed inset-0 backdrop-blur-sm bg-slate-900/60 hidden items-center justify-center z-[10000] p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl transform transition-all relative w-full max-h-[90vh] flex flex-col">
            <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4 rounded-t-2xl flex justify-between items-center">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    งานในหมวดหมู่: <span id="viewTasksTitle" class="ml-1 text-blue-100"></span>
                </h3>
                <button type="button" onclick="closeViewTasksModal()" class="text-white/80 hover:text-white focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-grow bg-gray-50/50 rounded-b-2xl">
                <div id="tasksLoading" class="text-center py-8 text-gray-500 hidden">
                    <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    กำลังโหลดข้อมูล...
                </div>
                
                <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">วันที่ / เวลา</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ผู้บันทึก</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">รายละเอียดงาน</th>
                            </tr>
                        </thead>
                        <tbody id="tasksTableBody" class="divide-y divide-gray-200">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ระบบ Toast
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if(!container) return;
            
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

        document.addEventListener('DOMContentLoaded', () => {
            const msg = sessionStorage.getItem('toast_msg');
            const type = sessionStorage.getItem('toast_type');
            if (msg) {
                showToast(msg, type);
                sessionStorage.removeItem('toast_msg');
                sessionStorage.removeItem('toast_type');
            }
        });

        // เปิด/ปิด Modal หมวดหมู่
        function openCategoryModal(id = '', name = '') {
            document.getElementById('cat_id').value = id;
            document.getElementById('cat_name_th').value = name;
            
            const titleHtml = id 
                ? `<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> แก้ไขหมวดหมู่`
                : `<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg> เพิ่มหมวดหมู่ใหม่`;
            
            document.getElementById('modalTitle').innerHTML = titleHtml;
            
            const modal = document.getElementById('categoryModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeCategoryModal() {
            const modal = document.getElementById('categoryModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('categoryForm').reset();
        }

        // ส่งข้อมูลเพิ่ม/แก้ไข
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('cat_id').value;
            const name_th = document.getElementById('cat_name_th').value;

            fetch('/api/work_categories/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name_th })
            })
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    sessionStorage.setItem('toast_msg', id ? 'แก้ไขหมวดหมู่สำเร็จ' : 'เพิ่มหมวดหมู่สำเร็จ');
                    sessionStorage.setItem('toast_type', 'success');
                    location.reload();
                } else {
                    showToast(d.message || 'เกิดข้อผิดพลาด', 'error');
                }
            })
            .catch(e => showToast('Error: ' + e.message, 'error'));
        });

        // ยืนยันการลบ
        function confirmDelete(id) {
            document.getElementById('delete_cat_id').value = id;
            const modal = document.getElementById('deleteCatModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteCatModal() {
            const modal = document.getElementById('deleteCatModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function deleteCategory() {
            const id = document.getElementById('delete_cat_id').value;
            fetch('/api/work_categories/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            })
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    sessionStorage.setItem('toast_msg', 'ลบหมวดหมู่สำเร็จ');
                    sessionStorage.setItem('toast_type', 'success');
                    location.reload();
                } else {
                    showToast(d.message || 'เกิดข้อผิดพลาด', 'error');
                    closeDeleteCatModal();
                }
            })
            .catch(e => {
                showToast('Error: ' + e.message, 'error');
                closeDeleteCatModal();
            });
        }

        // ดึงและแสดงรายการงานที่ผูกไว้
        function viewLinkedTasks(categoryId, categoryName) {
            document.getElementById('viewTasksTitle').textContent = categoryName;
            document.getElementById('tasksTableBody').innerHTML = '';
            document.getElementById('tasksLoading').classList.remove('hidden');
            
            const modal = document.getElementById('viewTasksModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            fetch(`/api/work_categories/get_logs.php?id=${categoryId}`)
            .then(r => r.json())
            .then(d => {
                document.getElementById('tasksLoading').classList.add('hidden');
                if(d.ok && d.data.length > 0) {
                    let html = '';
                    d.data.forEach(task => {
                        html += `
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">${task.work_date}</div>
                                    <div class="text-xs text-gray-500 flex items-center mt-1">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        ${task.start_time.substring(0,5)} - ${task.end_time.substring(0,5)} น.
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-800">${escapeHtml(task.username)}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    ${escapeHtml(task.activity_detail)}
                                </td>
                            </tr>
                        `;
                    });
                    document.getElementById('tasksTableBody').innerHTML = html;
                } else {
                    document.getElementById('tasksTableBody').innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">ไม่พบข้อมูลงานที่เชื่อมโยง</td></tr>';
                }
            })
            .catch(e => {
                document.getElementById('tasksLoading').classList.add('hidden');
                document.getElementById('tasksTableBody').innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-red-500">เกิดข้อผิดพลาดในการดึงข้อมูล</td></tr>';
            });
        }

        function closeViewTasksModal() {
            const modal = document.getElementById('viewTasksModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // ป้องกัน XSS
        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>
</html>