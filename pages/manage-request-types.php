<?php
if (!isset($user['id']) || !in_array($user['role'], ['SYSTEM', 'ADMIN'])) {
    header('Location: ./');
    exit();
}

global $pdo;
require_once './controllers/ManageRequestTypesController.php';

$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 50;

$controller = new ManageRequestTypesController($pdo);
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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการปัญหาการใช้งาน | HelpDesk</title>
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
                    <h1 class="text-3xl font-bold text-gray-900">จัดการปัญหาการใช้งาน</h1>
                    <p class="text-sm text-gray-500 mt-1">หมวดหมู่ระดับบนสุด</p>
                </div>
                <button onclick="openModal('add')" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    เพิ่มปัญหาการใช้งาน
                </button>
            </div>

            <div class="bg-white md:rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-medium">
                            <tr>
                                <th class="px-6 py-4">รหัส (Code)</th>
                                <th class="px-6 py-4">ชื่อปัญหาการใช้งาน</th>
                                <th class="px-6 py-4 text-center">ประเภทย่อย</th>
                                <th class="px-6 py-4 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-indigo-600"><?php echo htmlspecialchars($item['code'] ?? '-'); ?></td>
                                    <td class="px-6 py-4 font-medium text-slate-800"><?php echo htmlspecialchars($item['name_th']); ?></td>
                                    
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($item['category_count'] > 0): ?>
                                            <button onclick="viewLinkedCategories(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name_th'], ENT_QUOTES); ?>')" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors border border-blue-100 group">
                                                <?php echo $item['category_count']; ?> ประเภท
                                                <svg class="w-3.5 h-3.5 ml-1 opacity-50 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                                0 ประเภท
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button onclick='openEditModal(<?php echo json_encode($item); ?>)' class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors" title="แก้ไข">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        
                                        <?php if ($item['category_count'] > 0): ?>
                                            <button disabled class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 cursor-not-allowed" title="ไม่สามารถลบได้เนื่องจากมีประเภทย่อยผูกอยู่">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <form action="" method="POST" class="inline-block" onsubmit="return confirm('ยืนยันการลบปัญหาการใช้งานนี้?');">
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
                                <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">ไม่พบข้อมูล</td></tr>
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
                    <h3 class="text-lg font-bold text-slate-900" id="modalTitle">เพิ่มข้อมูล</h3>
                </div>
                <form id="itemForm" action="" method="POST" class="p-6 space-y-4">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="itemId" value="">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">รหัส (Code)</label>
                        <input type="text" name="code" id="code" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อปัญหาการใช้งาน <span class="text-red-500">*</span></label>
                        <input type="text" name="name_th" id="name_th" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="pt-4 flex gap-3 justify-end">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">ยกเลิก</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="viewCategoriesModal" class="fixed inset-0 z-[100] hidden opacity-0 transition-opacity duration-300 ease-out">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeViewCategoriesModal()"></div>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div id="viewCategoriesModalContent" class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform scale-95 opacity-0 transition-all duration-300 ease-out sm:my-8 max-w-2xl w-full flex flex-col max-h-[80vh]">
                <div class="bg-white px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-900">
                        ประเภทย่อยของ: <span id="viewCategoriesTitle" class="text-indigo-600 font-medium"></span>
                    </h3>
                    <button type="button" onclick="closeViewCategoriesModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-grow bg-slate-50/50">
                    <div id="categoriesLoading" class="text-center py-8 text-slate-500 hidden">
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
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">รหัส (Code)</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">ชื่อประเภทย่อย</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500">แก้ไข</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTableBody" class="divide-y divide-slate-100 text-sm">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="editCategoryModal" class="fixed inset-0 z-[110] hidden">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeEditCategoryModal()"></div>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-md w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-900">แก้ไขประเภทย่อย</h3>
                </div>
                <form id="editCategoryForm" action="" method="POST" class="p-6 space-y-4">
                    <input type="hidden" name="action" value="edit_category">
                    <input type="hidden" name="cat_id" id="edit_cat_id" value="">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">รหัส (Code)</label>
                        <input type="text" name="cat_code" id="edit_cat_code" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อประเภทย่อย <span class="text-red-500">*</span></label>
                        <input type="text" name="cat_name" id="edit_cat_name" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="pt-4 flex gap-3 justify-end">
                        <button type="button" onclick="closeEditCategoryModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">ยกเลิก</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">บันทึกแก้ไข</button>
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

        // Modal การจัดการ Request Type
        const modal = document.getElementById('itemModal');
        const form = document.getElementById('itemForm');

        function openModal(mode) {
            form.reset();
            document.getElementById('modalTitle').textContent = mode === 'add' ? 'เพิ่มปัญหาการใช้งาน' : 'แก้ไขปัญหาการใช้งาน';
            document.getElementById('formAction').value = mode;
            modal.classList.remove('hidden');
        }

        function openEditModal(data) {
            openModal('edit');
            document.getElementById('itemId').value = data.id;
            document.getElementById('code').value = data.code || '';
            document.getElementById('name_th').value = data.name_th;
        }

        function closeModal() { modal.classList.add('hidden'); }

        // Modal ดูหมวดหมู่ย่อย
        function viewLinkedCategories(requestTypeId, nameTh) {
            document.getElementById('viewCategoriesTitle').textContent = nameTh;
            document.getElementById('categoriesTableBody').innerHTML = '';
            document.getElementById('categoriesLoading').classList.remove('hidden');

            const viewModal = document.getElementById('viewCategoriesModal');
            const modalContent = document.getElementById('viewCategoriesModalContent');

            viewModal.classList.remove('hidden');
            setTimeout(() => {
                viewModal.classList.remove('opacity-0');
                viewModal.classList.add('opacity-100');
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);

            fetch(`/api/admin/request_types/get_categories.php?id=${requestTypeId}`)
                .then(r => r.json())
                .then(d => {
                    document.getElementById('categoriesLoading').classList.add('hidden');
                    if (d.ok && d.data.length > 0) {
                        let html = '';
                        d.data.forEach(cat => {
                            html += `
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 text-indigo-600 font-medium">${escapeHtml(cat.code || '-')}</td>
                                <td class="px-4 py-3 text-slate-800 font-medium">${escapeHtml(cat.name_th)}</td>
                                <td class="px-4 py-3 text-right">
                                    <button onclick='openQuickEditCategory(${JSON.stringify(cat)})' class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors" title="แก้ไข">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                </td>
                            </tr>`;
                        });
                        document.getElementById('categoriesTableBody').innerHTML = html;
                    } else {
                        document.getElementById('categoriesTableBody').innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">ไม่พบข้อมูลประเภทย่อย</td></tr>';
                    }
                })
                .catch(e => {
                    document.getElementById('categoriesLoading').classList.add('hidden');
                    document.getElementById('categoriesTableBody').innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-red-500">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
                });
        }

        function closeViewCategoriesModal() {
            const viewModal = document.getElementById('viewCategoriesModal');
            const modalContent = document.getElementById('viewCategoriesModalContent');
            viewModal.classList.remove('opacity-100');
            viewModal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => { viewModal.classList.add('hidden'); }, 300);
        }

        // Modal แก้ไข Category ย่อยโดยตรง
        function openQuickEditCategory(cat) {
            closeViewCategoriesModal(); // ปิด Modal อันแรกไปก่อน
            
            document.getElementById('edit_cat_id').value = cat.id;
            document.getElementById('edit_cat_code').value = cat.code || '';
            document.getElementById('edit_cat_name').value = cat.name_th;
            
            document.getElementById('editCategoryModal').classList.remove('hidden');
        }

        function closeEditCategoryModal() {
            document.getElementById('editCategoryModal').classList.add('hidden');
        }

        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>
</html>