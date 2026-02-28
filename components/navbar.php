<nav class="bg-white/80 backdrop-blur-lg shadow-sm border-b border-gray-100 sticky top-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">

            <div class="flex items-center gap-8">
                <a href="./" class="flex items-center gap-3 group">
                    <div class="relative">
                        <div class="absolute inset-0 bg-indigo-500 rounded-xl blur-md opacity-20 group-hover:opacity-40 transition-opacity duration-300"></div>
                        <div class="relative bg-white p-2 rounded-xl border border-gray-100 shadow-sm flex items-center justify-center">
                            <img src="./public/logo.png" alt="Logo" class="w-8 h-8 object-contain">
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <h1 class="text-xl font-extrabold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent tracking-tight">
                            HelpDesk
                        </h1>
                        <span class="text-[11px] text-gray-500 font-medium tracking-wide uppercase">IT Support System</span>
                    </div>
                </a>

                <div class="hidden lg:flex items-center gap-1">
                    <a href="./?page=report" class="nav-link px-4 py-2 rounded-full text-sm font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>แจ้งปัญหา/บริการ</span>
                    </a>

                    <a href="./?page=reports" class="nav-link px-4 py-2 rounded-full text-sm font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        <span>รายงานปัญหาทั้งหมด</span>
                    </a>

                    <a href="./?page=daily-works" class="nav-link px-4 py-2 rounded-full text-sm font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>บันทึกงานประจำวัน</span>
                    </a>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <?php if (isset($user)) : ?>
                    <div class="hidden md:flex items-center gap-3 pl-4 border-l border-gray-200">
                        <div class="flex flex-col items-end">
                            <span class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">ยินดีต้อนรับ</span>
                            <span class="text-sm font-bold text-gray-700"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-purple-500 rounded-full flex items-center justify-center shadow-md border-2 border-white">
                            <span class="text-white font-bold text-sm">
                                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                            </span>
                        </div>
                    </div>

                    <a href="./?page=logout" class="hidden md:inline-flex items-center justify-center w-10 h-10 rounded-full bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-colors duration-200 group" title="ออกจากระบบ">
                        <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                <?php else: ?>
                    <a href="./?page=login" class="hidden md:inline-flex items-center gap-2 bg-gray-900 text-white px-6 py-2.5 rounded-full text-sm font-medium hover:bg-indigo-600 transition-colors duration-300 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <span>เข้าสู่ระบบ</span>
                    </a>
                <?php endif; ?>

                <button id="mobile-menu-button" class="lg:hidden w-10 h-10 flex items-center justify-center rounded-full bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-menu" class="lg:hidden hidden border-t border-gray-100 bg-white/95 backdrop-blur-md absolute w-full shadow-lg">
        <div class="px-4 py-4 space-y-1">
            <?php if (isset($user)) : ?>
                <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl mb-4">
                    <div class="w-12 h-12 bg-gradient-to-tr from-indigo-500 to-purple-500 rounded-full flex items-center justify-center shadow-inner border-2 border-white">
                        <span class="text-white font-bold text-base">
                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                        </span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 font-medium">ยินดีต้อนรับ</span>
                        <span class="text-base font-bold text-gray-800"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <a href="./?page=report" class="mobile-nav-link flex items-center gap-3 px-4 py-3.5 rounded-xl text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>แจ้งปัญหา/บริการ</span>
            </a>

            <a href="./?page=reports" class="mobile-nav-link flex items-center gap-3 px-4 py-3.5 rounded-xl text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
                <span>รายงานปัญหาทั้งหมด</span>
            </a>

            <a href="./?page=daily-works" class="mobile-nav-link flex items-center gap-3 px-4 py-3.5 rounded-xl text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span>บันทึกงานประจำวัน</span>
            </a>

            <div class="h-px bg-gray-100 my-2"></div>

            <?php if (isset($user)) : ?>
                <a href="./?page=logout" class="flex items-center gap-3 px-4 py-3.5 rounded-xl text-red-500 hover:bg-red-50 font-medium transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>ออกจากระบบ</span>
                </a>
            <?php else: ?>
                <a href="./?page=login" class="flex items-center justify-center gap-2 bg-gray-900 text-white px-4 py-3.5 rounded-xl font-medium shadow-md hover:bg-indigo-600 transition-colors mt-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>เข้าสู่ระบบ</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    // จัดการแสดงผล Mobile Menu
    const mobileMenuBtn = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileMenu.classList.toggle('hidden');
        });
    }

    // ปิดเมนูเมื่อคลิกพื้นที่อื่นบนหน้าจอ
    document.addEventListener('click', function(event) {
        if (mobileMenu && !mobileMenu.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
            mobileMenu.classList.add('hidden');
        }
    });

    // เพิ่ม Active State ให้กับเมนูที่กำลังเปิดอยู่ (สไตล์แบบใหม่)
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'home';

    // สำหรับ Desktop
    document.querySelectorAll('.nav-link').forEach(link => {
        const linkPage = new URL(link.href).searchParams.get('page');
        if (linkPage === currentPage) {
            link.classList.remove('text-gray-600');
            link.classList.add('text-indigo-700', 'bg-indigo-50', 'ring-1', 'ring-indigo-100');
        }
    });

    // สำหรับ Mobile
    document.querySelectorAll('.mobile-nav-link').forEach(link => {
        const linkPage = new URL(link.href).searchParams.get('page');
        if (linkPage === currentPage) {
            link.classList.remove('text-gray-600');
            link.classList.add('text-indigo-700', 'bg-indigo-50');
        }
    });
</script>