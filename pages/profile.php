<?php

global $pdo;
require_once __DIR__ . '/../controllers/ProfileController.php';

if (!isset($user['id'])) {
    header('Location: ./?page=login');
    exit();
}

$profileController = new ProfileController($pdo, $user['id']);

$response = $profileController->handleRequest();

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

$current_user = $profileController->getUserData();

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ผู้ใช้ | HelpDesk</title>
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
            /* gray-700 */
            padding: 12px 16px;
            border-radius: 9999px;
            /* Pill shape */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.35s cubic-bezier(0.21, 1.02, 0.73, 1);
            z-index: 9999;
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <h1 class="text-3xl font-bold text-gray-900 mb-8">การตั้งค่าบัญชี</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-semibold text-slate-800 mb-4 border-b pb-2">ข้อมูลส่วนตัว</h2>

                    <form id="profileForm" action="" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อผู้ใช้งาน (Username)</label>
                            <input type="text" value="<?php echo htmlspecialchars($current_user['username']); ?>" disabled
                                class="w-full px-4 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-500 cursor-not-allowed">
                        </div>
                        <div>
                            <label for="display_th" class="block text-sm font-medium text-slate-700 mb-1">ชื่อ-นามสกุล (ภาษาไทย)</label>
                            <input type="text" name="display_th" id="display_th" required
                                value="<?php echo htmlspecialchars($current_user['display_th']); ?>"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label for="phone_ext" class="block text-sm font-medium text-slate-700 mb-1">เบอร์ติดต่อภายใน (Ext.)</label>
                            <input type="text" name="phone_ext" id="phone_ext" required
                                value="<?php echo htmlspecialchars($current_user['phone_ext']); ?>"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                        <div class="pt-4">
                            <button type="submit" name="update_profile"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition-colors">
                                บันทึกข้อมูล
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-semibold text-slate-800 mb-4 border-b pb-2">เปลี่ยนรหัสผ่าน</h2>

                    <form id="passwordForm" action="" method="POST" class="space-y-4">
                        <div>
                            <label for="old_password" class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่านปัจจุบัน</label>
                            <input type="password" name="old_password" id="old_password" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-slate-700 mb-1">รหัสผ่านใหม่</label>
                            <input type="password" name="new_password" id="new_password" required minlength="6"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-1">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                        <div class="pt-4">
                            <button type="submit" name="update_password"
                                class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition-colors">
                                อัปเดตรหัสผ่าน
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        // 1. ฟังก์ชันสร้างและแสดง Toast คล้าย React Hot Toast
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'hot-toast';

            // เลือก Icon ตามประเภท
            let iconHtml = '';
            if (type === 'success') {
                iconHtml = `<svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>`;
            } else {
                iconHtml = `<svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>`;
            }

            toast.innerHTML = `${iconHtml} <span>${message}</span>`;
            container.appendChild(toast);

            // แสดงแอนิเมชัน
            requestAnimationFrame(() => {
                toast.classList.add('show');
            });

            // ลบ Toast หลังจาก 3 วินาที
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400); // รอให้แอนิเมชันจบก่อนลบ DOM
            }, 3000);
        }

        // 2. Client-Side Validation ก่อนส่งฟอร์มเปลี่ยนรหัสผ่าน
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault(); // ยกเลิกการส่งฟอร์มไปที่ PHP
                showToast('รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน', 'error');
            }
        });

        // 3. รับค่าจาก Session (Flash Message) มาแสดงผลใน Toast
        <?php if ($flash_message): ?>
            document.addEventListener('DOMContentLoaded', () => {
                const phpMessage = "<?php echo addslashes($flash_message); ?>";
                const phpStatus = "<?php echo $flash_status; ?>";
                showToast(phpMessage, phpStatus);
            });
        <?php endif; ?>
    </script>
</body>

</html>