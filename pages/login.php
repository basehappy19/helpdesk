<?php
require_once __DIR__ . "../../functions/users.php";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $data = [
        "username" => $_POST['username'] ?? "",
        "password" => $_POST['password'] ?? ""
    ];

    if ($user = Auth($data)) {
        $_SESSION['user'] = $user;
        header("Location: ./?page=home");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <?php include './lib/style.php'; ?>
</head>

<body>
    <?php
    include './components/navbar.php';
    ?>
    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-[80vh] py-8 px-4 flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg">
            <div class="p-8 pb-0">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">เข้าสู่ระบบ</h1>
                    <p class="text-gray-600">ระบบ Helpdesk</p>
                </div>

                <!-- Form -->
                <form method="POST" action="">
                    <!-- Email -->
                    <div class="mb-6">
                        <label for="username" class="block text-gray-700 font-semibold mb-2">ชื่อผู้ใช้</label>
                        <input type="text" id="username" name="username" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="user">
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <label for="password" class="block text-gray-700 font-semibold mb-2">รหัสผ่าน</label>
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="••••••••">
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-lg transition duration-200">
                        เข้าสู่ระบบ
                    </button>
                </form>
            </div>

            <div class="flex justify-end px-8 py-4">
                <a href="./?page=home" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    ย้อนกลับ
                </a>
            </div>
        </div>
    </div>
</body>

</html>