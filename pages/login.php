<?php
require_once __DIR__ . "../../functions/users.php";

// 1. ประกาศตัวแปร $error ไว้ก่อนเพื่อกัน Warning ว่า undefined variable
$error = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $data = [
        "username" => $_POST['username'] ?? "",
        "password" => $_POST['password'] ?? ""
    ];

    if ($user = Auth($data)) {
        // Login สำเร็จ
        $_SESSION['user'] = $user;
        header("Location: ./?page=home");
        exit();
    } else {
        // 2. Login ไม่สำเร็จ: กำหนดข้อความแจ้งเตือน
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Helpdesk</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <?php include './lib/style.php'; ?>
    <style> body { font-family: 'Prompt', sans-serif; } </style>
</head>

<body class="bg-[#F3F4F6] min-h-screen flex items-center justify-center relative overflow-hidden">
    
    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute bottom-[-20%] left-[20%] w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

    <div class="relative w-full max-w-md p-6">
        <div class="bg-white/80 backdrop-blur-lg rounded-3xl shadow-2xl overflow-hidden border border-white/20">
            <div class="p-8">
                <div class="text-center mb-10">
                    <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Helpdesk Login</h1>
                    <p class="text-gray-500 mt-2 text-sm">เข้าสู่ระบบเพื่อเริ่มใช้งาน</p>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="mb-6 p-3 rounded-lg bg-red-50 border border-red-200 text-red-600 text-sm text-center flex items-center justify-center gap-2 animate-pulse">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label class="text-sm font-semibold text-gray-600 block mb-2 pl-1">ชื่อผู้ใช้</label>
                        <input type="text" name="username" required 
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                            class="w-full px-5 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 outline-none transition-all duration-200"
                            placeholder="username">
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-600 block mb-2 pl-1">รหัสผ่าน</label>
                        <input type="password" name="password" required
                            class="w-full px-5 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 outline-none transition-all duration-200"
                            placeholder="••••••••">
                    </div>

                    <button type="submit" class="cursor-pointer w-full py-3.5 px-4 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:shadow-indigo-500/30 hover:scale-[1.02] transition-all duration-200">
                        เข้าสู่ระบบ
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <a href="./?page=home" class="text-sm text-gray-400 hover:text-gray-600 transition-colors inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        กลับหน้าหลัก
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }
    </style>
</body>
</html>