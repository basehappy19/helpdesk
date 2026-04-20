<?php

if (!isset($user['id']) || $user['role'] !== 'SYSTEM') {
    header('Location: ./');
    exit();
}

global $pdo;
require_once __DIR__ . '/../controllers/ManageUsersController.php';

$manageController = new ManageUsersController($pdo);
$response = $manageController->handleRequest();

// PRG Pattern - ป้องกันการส่ง Form ซ้ำ
if ($response) {
    $_SESSION['toast_message'] = $response['message'];
    $_SESSION['toast_status'] = $response['status'];
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// โหลดข้อความแจ้งเตือนจาก Session
$flash_message = null;
$flash_status = null;
if (isset($_SESSION['toast_message'])) {
    $flash_message = $_SESSION['toast_message'];
    $flash_status = $_SESSION['toast_status'];
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_status']);
}

$all_users = $manageController->getAllUsers();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ระบบ | HelpDesk</title>
    <?php include './lib/style.php'; ?>
    <style>
        /* สไตล์ Toast เหมือนหน้า Profile */
        .hot-toast { position: fixed; top: 24px; left: 50%; transform: translateX(-50%) translateY(-150%) scale(0.9); opacity: 0; background: white; color: #374151; padding: 12px 16px; border-radius: 9999px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; transition: all 0.35s cubic-bezier(0.21, 1.02, 0.73, 1); z-index: 9999; pointer-events: none; }
        .hot-toast.show { transform: translateX(-50%) translateY(0) scale(1); opacity: 1; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-gray-800">
    <?php include './components/navbar.php'; ?>
    <div id="toast-container"></div>

    <div class="min-h-screen pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">จัดการผู้ใช้งานระบบ</h1>
                    <p class="text-sm text-gray-500 mt-1">เพิ่ม ลบ แก้ไข และจัดการสิทธิ์ของผู้ใช้ทั้งหมด</p>
                </div>
                <button onclick="openModal('add')" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    เพิ่มผู้ใช้ใหม่
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-medium">
                            <tr>
                                <th class="px-6 py-4">ชื่อผู้ใช้</th>
                                <th class="px-6 py-4">ชื่อ-นามสกุล</th>
                                <th class="px-6 py-4">เบอร์ภายใน</th>
                                <th class="px-6 py-4">สิทธิ์</th>
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
                                <td class="px-6 py-4 text-slate-500 text-xs">
                                    <?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button onclick='openEditModal(<?php echo json_encode($u); ?>)' class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors" title="แก้ไข">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    
                                    <?php if ($u['id'] != $user['id']): ?>
                                    <form action="" method="POST" class="inline-block" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้ <?php echo htmlspecialchars($u['username']); ?>?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors" title="ลบ">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($all_users)): ?>
                            <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">ไม่พบข้อมูลผู้ใช้งาน</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

                    <div class="pt-4 flex gap-3 justify-end">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">ยกเลิก</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">บันทึกข้อมูล</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        // ระบบ Toast
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'hot-toast';
            let iconHtml = type === 'success' 
                ? `<svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>`
                : `<svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>`;
            
            toast.innerHTML = `${iconHtml} <span>${message}</span>`;
            container.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 3000);
        }

        // โหลดข้อมูลแจ้งเตือน
        <?php if ($flash_message): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showToast("<?php echo addslashes($flash_message); ?>", "<?php echo $flash_status; ?>");
            });
        <?php endif; ?>

        // การจัดการ Modal
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
            usernameInput.className = isAdd 
                ? 'w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none' 
                : 'w-full px-4 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-500 cursor-not-allowed';
            document.getElementById('usernameHelp').style.display = isAdd ? 'none' : 'block';

            // จัดการช่อง Password
            const pwdInput = document.getElementById('password');
            pwdInput.required = isAdd;
            document.getElementById('pwdAsterisk').style.display = isAdd ? 'inline' : 'none';
            document.getElementById('pwdHelp').style.display = isAdd ? 'none' : 'block';

            modal.classList.remove('hidden');
        }

        function openEditModal(userData) {
            openModal('edit');
            document.getElementById('userId').value = userData.id;
            document.getElementById('username').value = userData.username;
            document.getElementById('display_th').value = userData.display_th;
            document.getElementById('phone_ext').value = userData.phone_ext;
            document.getElementById('role').value = userData.role;
        }

        function closeModal() {
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>