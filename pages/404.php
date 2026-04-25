<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - ไม่พบหน้าที่ต้องการ | HelpDesk</title>
    <?php include './lib/style.php'; ?>
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 1; }
            70% { transform: scale(1.15); opacity: 0; }
            100% { transform: scale(0.95); opacity: 0; }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .float-icon { animation: float 4s ease-in-out infinite; }
        .pulse-ring {
            position: absolute; inset: -8px;
            border-radius: 9999px;
            border: 2px solid #c7d2fe;
            animation: pulse-ring 2.5s ease-out infinite;
        }
        .fade-in-1 { animation: fadeInUp 0.5s ease both 0.1s; opacity: 0; }
        .fade-in-2 { animation: fadeInUp 0.5s ease both 0.25s; opacity: 0; }
        .fade-in-3 { animation: fadeInUp 0.5s ease both 0.4s; opacity: 0; }
        .fade-in-4 { animation: fadeInUp 0.5s ease both 0.55s; opacity: 0; }
    </style>
</head>

<body class="bg-slate-50 font-sans antialiased text-gray-800">
    <?php include './components/navbar.php'; ?>

    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-16">

        <div class="text-center max-w-md w-full">

            <!-- Icon -->
            <div class="flex justify-center mb-8 fade-in-1">
                <div class="relative">
                    <div class="pulse-ring"></div>
                    <div class="w-24 h-24 bg-indigo-100 rounded-3xl flex items-center justify-center float-icon shadow-sm">
                        <svg class="w-12 h-12 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- 404 number -->
            <div class="fade-in-2 mb-2">
                <span class="text-8xl font-bold tracking-tight bg-gradient-to-b from-indigo-400 to-indigo-600 bg-clip-text text-transparent select-none leading-none">
                    404
                </span>
            </div>

            <!-- Title & Description -->
            <div class="fade-in-3 mb-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">ไม่พบหน้าที่คุณต้องการ</h1>
                <p class="text-gray-500 text-sm leading-relaxed">
                    หน้าที่คุณกำลังค้นหาอาจถูกย้าย ลบออก หรือ URL อาจพิมพ์ผิด<br>
                    กรุณาตรวจสอบที่อยู่อีกครั้ง หรือกลับสู่หน้าหลัก
                </p>
            </div>

            <!-- Actions -->
            <div class="fade-in-4 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="./"
                    class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white rounded-2xl font-semibold shadow-sm hover:bg-indigo-700 transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    กลับหน้าหลัก
                </a>
                <a href="./?page=report"
                    class="inline-flex items-center justify-center px-6 py-3 bg-white text-gray-700 border border-gray-200 rounded-2xl font-semibold shadow-sm hover:border-indigo-200 hover:text-indigo-600 hover:bg-indigo-50 transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6">
                        </path>
                    </svg>
                    แจ้งปัญหา / บริการ
                </a>
            </div>

            <!-- Quick links -->
            <div class="fade-in-4 mt-10 pt-8 border-t border-gray-100">
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-4">ลิงก์ที่อาจเป็นประโยชน์</p>
                <div class="flex flex-wrap justify-center gap-2">
                    <a href="./?page=reports"
                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-100 text-gray-600 rounded-xl text-xs font-medium hover:border-indigo-200 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                        บันทึกทั้งหมด
                    </a>
                    <a href="./?page=statistics"
                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-100 text-gray-600 rounded-xl text-xs font-medium hover:border-emerald-200 hover:text-emerald-600 hover:bg-emerald-50 transition-colors">
                        รายงานสถิติ
                    </a>
                    <a href="./?page=login"
                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-100 text-gray-600 rounded-xl text-xs font-medium hover:border-blue-200 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                        เข้าสู่ระบบ
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer note -->
        <p class="mt-12 text-xs text-gray-300">
            ระบบ HelpDesk · โรงพยาบาลเมตตาประชารักษ์ (วัดไร่ขิง)
        </p>
    </div>
</body>

</html>