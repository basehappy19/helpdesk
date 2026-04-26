<?php

if (!isset($user['id']) || $user['role'] !== 'SYSTEM') {
    header('Location: ./');
    exit();
}

global $pdo;
require_once __DIR__ . '/../controllers/ManageUsersController.php';

$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 50;

$manageController = new ManageUsersController($pdo);
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

$result = $manageController->getAllUsers($currentPage, $limit);
$all_users = $result['users'];
$totalPages = $result['total_pages'];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ระบบ | HelpDesk</title>
    <?php include './lib/style.php'; ?>
</head>

<body class="bg-slate-50 font-sans antialiased text-gray-800">
    <?php include './components/navbar.php'; ?>
    <div id="toast-container"></div>

    <div class="min-h-screen pb-12">
        <div class="max-w-7xl mx-auto md:px-4 sm:px-6 lg:px-8 py-8">

            <div class="md:px-0 px-4 flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">จัดการผู้ใช้งานระบบ</h1>
                    <p class="text-sm text-gray-500 mt-1">เพิ่ม ลบ แก้ไข และจัดการสิทธิ์ของผู้ใช้ทั้งหมด</p>
                </div>
                <button onclick="openModal('add')" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    เพิ่มผู้ใช้ใหม่
                </button>
            </div>

            <div class="bg-white md:rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-medium">
                            <tr>
                                <th class="px-6 py-4">ชื่อผู้ใช้</th>
                                <th class="px-6 py-4">ชื่อ-นามสกุล</th>
                                <th class="px-6 py-4">เบอร์ภายใน</th>
                                <th class="px-6 py-4">สิทธิ์</th>
                                <th class="px-6 py-4">ผู้แก้ไขปัญหา</th>
                                <th class="px-6 py-4">วันที่สร้าง</th>
                                <th class="px-6 py-4 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($all_users as $u): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        <?php echo htmlspecialchars($u['username']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">
                                        <?php echo htmlspecialchars($u['display_th']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">
                                        <?php echo htmlspecialchars($u['phone_ext']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $roleColors = [
                                            'SYSTEM' => 'bg-purple-100 text-purple-700 border-purple-200',
                                            'ADMIN' => 'bg-red-100 text-red-700 border-red-200',
                                            'SERVICE' => 'bg-blue-100 text-blue-700 border-blue-200',
                                            'MEMBER' => 'bg-emerald-100 text-emerald-700 border-emerald-200'
                                        ];
                                        $colorClass = $roleColors[$u['role']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                                        ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold border <?php echo $colorClass; ?>">
                                            <?php echo $u['role']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($u['solver'] == 1): ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">ใช่</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">ไม่ใช่</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 text-xs">
                                        <?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button onclick='openEditModal(<?php echo json_encode($u); ?>)' class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors" title="แก้ไข">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>

                                        <?php if ($u['id'] != $user['id']): ?>
                                            <form action="" method="POST" class="inline-block" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้ <?php echo htmlspecialchars($u['username']); ?>?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
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

                            <?php if (empty($all_users)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-slate-500">ไม่พบข้อมูลผู้ใช้งาน</td>
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
                            <a href="?page=manage-users&p=<?php echo max(1, $currentPage - 1); ?>"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-indigo-600 transition-all <?php echo ($currentPage <= 1) ? 'opacity-40 pointer-events-none' : ''; ?>"
                                title="ก่อนหน้า">
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
                                    <a href="?page=manage-users&p=1" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">1</a>
                                    <?php if ($start > 2): ?><span class="text-slate-300">...</span><?php endif; ?>
                                <?php endif;

                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <a href="?page=manage-users&p=<?php echo $i; ?>"
                                        class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold transition-all <?php echo ($i == $currentPage) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-indigo-50 hover:text-indigo-600'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor;

                                if ($end < $totalPages): ?>
                                    <?php if ($end < $totalPages - 1): ?><span class="text-slate-300">...</span><?php endif; ?>
                                    <a href="?page=manage-users&p=<?php echo $totalPages; ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"><?php echo $totalPages; ?></a>
                                <?php endif; ?>
                            </div>

                            <a href="?page=manage-users&p=<?php echo min($totalPages, $currentPage + 1); ?>"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-indigo-600 transition-all <?php echo ($currentPage >= $totalPages) ? 'opacity-40 pointer-events-none' : ''; ?>"
                                title="ถัดไป">
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

    <div id="userModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>

        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg w-full">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-900" id="modalTitle">เพิ่มผู้ใช้ใหม่</h3>
                </div>

                <form id="userForm" action="" method="POST" class="p-6 space-y-4">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="user_id" id="userId" value="">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" id="username" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-colors">
                        <p id="usernameHelp" class="text-xs text-slate-500 mt-1 hidden">ไม่สามารถเปลี่ยน Username ได้ในโหมดแก้ไข</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่าน <span id="pwdAsterisk" class="text-red-500">*</span></label>
                        <input type="text" name="password" id="password" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-colors">
                        <p id="pwdHelp" class="text-xs text-slate-500 mt-1 hidden">ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                        <input type="text" name="display_th" id="display_th" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-colors">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">เบอร์ภายใน</label>
                            <input type="text" name="phone_ext" id="phone_ext" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">สิทธิ์<span class="text-red-500">*</span></label>
                            <select name="role" id="role" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                <option value="MEMBER">MEMBER (ทั่วไป)</option>
                                <option value="SERVICE">SERVICE (จนท.ไอที)</option>
                                <option value="ADMIN">ADMIN (แอดมิน)</option>
                                <option value="SYSTEM">SYSTEM (ผู้ดูแลระบบสูงสุด)</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="solver" id="solver" value="1" class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            <span class="ms-3 text-sm font-medium text-slate-700">ตั้งให้เป็นผู้แก้ไขปัญหา (Solver)</span>
                        </label>
                        <p class="text-xs text-slate-500 mt-1">หากเปิดใช้งาน ผู้ใช้นี้จะไปปรากฏในตัวเลือก "ผู้แก้ไขปัญหา" ในหน้ารายละเอียด</p>
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
            document.addEventListener('DOMContentLoaded', () => {
                showToast("<?php echo addslashes($flash_message); ?>", "<?php echo $flash_status; ?>");
            });
        <?php endif; ?>

        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');

        function openModal(mode) {
            form.reset();
            const isAdd = mode === 'add';

            document.getElementById('modalTitle').textContent = isAdd ? 'เพิ่มผู้ใช้ใหม่' : 'แก้ไขข้อมูลผู้ใช้';
            document.getElementById('formAction').value = mode;

            // จัดการช่อง Username
            const usernameInput = document.getElementById('username');
            usernameInput.readOnly = !isAdd;
            usernameInput.className = isAdd ?
                'w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none' :
                'w-full px-4 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-500 cursor-not-allowed';
            document.getElementById('usernameHelp').style.display = isAdd ? 'none' : 'block';

            // จัดการช่อง Password
            const pwdInput = document.getElementById('password');
            pwdInput.required = isAdd;
            document.getElementById('pwdAsterisk').style.display = isAdd ? 'inline' : 'none';
            document.getElementById('pwdHelp').style.display = isAdd ? 'none' : 'block';

            // Reset Toggle Slider ให้ค่าเริ่มต้นเป็นปิดเวลาเปิด Add Modal
            if(isAdd) {
                document.getElementById('solver').checked = false;
            }

            modal.classList.remove('hidden');
        }

        function openEditModal(userData) {
            openModal('edit');
            document.getElementById('userId').value = userData.id;
            document.getElementById('username').value = userData.username;
            document.getElementById('display_th').value = userData.display_th;
            document.getElementById('phone_ext').value = userData.phone_ext;
            document.getElementById('role').value = userData.role;
            
            // เช็คว่า user เป็น solver ไหม ถ้าเป็นให้ติ๊กเปิดไว้
            document.getElementById('solver').checked = (userData.solver == 1);
        }

        function closeModal() {
            modal.classList.add('hidden');
        }
    </script>
</body>

</html>