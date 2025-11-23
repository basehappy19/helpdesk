<?php 
require_once __DIR__ . "../../functions/users.php";

if($_SERVER['REQUEST_METHOD'] === "POST"){
    $data = [
        "username" => $_POST['username'] ?? "",
        "password" => $_POST['password'] ?? ""
    ];

    if($user = Auth($data)){
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
    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen py-8 px-4 flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
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
    </div>
</body>

</html>