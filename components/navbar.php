<nav class="bg-gradient-to-r from-white to-gray-50 shadow-lg border-b border-gray-200 sticky top-0 z-50 backdrop-blur-sm bg-opacity-95">
    <div class="w-full container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">

            <!-- Logo & Brand -->
            <div class="flex items-center space-x-8">
                <a href="./" class="flex items-center space-x-3 group">
                    <div class="relative group">
                        <!-- Glow layer -->
                        <div class="absolute
                rounded-xl blur-md opacity-40 group-hover:opacity-60 transition"></div>

                        <!-- Logo container -->
                        <div class="relative
                p-2.5 rounded-xl flex items-center justify-center shadow-md">
                            <img src="./public/logo.png" alt="Logo" class="w-8 h-8 object-contain drop-shadow-sm">
                        </div>
                    </div>

                    <div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            Help Desk System
                        </h1>
                        <p class="text-xs text-gray-500 font-medium">IT Support & Service</p>
                    </div>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center space-x-2">
                    <a href="./?page=report" class="nav-link group relative px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:text-indigo-600 transition-all duration-200">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>แจ้งปัญหา/บริการ</span>
                        </div>
                        <div class="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-indigo-600 to-purple-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-200"></div>
                    </a>

                    <a href="./?page=daily-works" class="nav-link group relative px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:text-indigo-600 transition-all duration-200">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span>บันทึกงานประจำวัน</span>
                        </div>
                        <div class="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-indigo-600 to-purple-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-200"></div>
                    </a>
                </div>
            </div>

            <!-- Right Section -->
            <div class="flex items-center space-x-4">
                <?php if (isset($user)) : ?>
                    <!-- User Profile Section -->
                    <div class="hidden md:flex items-center space-x-3 bg-gray-100 rounded-xl px-4 py-2.5">
                        <div class="w-9 h-9 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">
                                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-500 font-medium">สวัสดี,</span>
                            <span class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                    </div>

                    <!-- Logout Button -->
                    <a href="./?page=logout" class="group relative inline-flex items-center space-x-2 bg-gradient-to-r from-red-500 to-pink-500 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-lg shadow-red-500/30 hover:shadow-xl hover:shadow-red-500/40 hover:scale-105 transition-all duration-200">
                        <svg class="w-4 h-4 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>ออกจากระบบ</span>
                    </a>
                <?php else: ?>
                    <!-- Login Button -->
                    <a href="./?page=login" class="group relative inline-flex items-center space-x-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 hover:scale-105 transition-all duration-200">
                        <svg class="w-4 h-4 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <span>เข้าสู่ระบบ</span>
                    </a>
                <?php endif; ?>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="lg:hidden inline-flex items-center justify-center p-2.5 rounded-xl text-gray-700 hover:text-indigo-600 hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="lg:hidden hidden border-t border-gray-200 bg-white">
        <div class="px-4 py-4 space-y-2">
            <?php if (isset($user)) : ?>
                <!-- Mobile User Info -->
                <div class="flex items-center space-x-3 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl px-4 py-3 mb-3 border border-indigo-100">
                    <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold">
                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                        </span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 font-medium">สวัสดี,</span>
                        <span class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <a href="./?page=report" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-medium">แจ้งปัญหา/บริการ</span>
            </a>

            <a href="./?page=daily-works" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span class="font-medium">บันทึกงานประจำวัน</span>
            </a>

            <?php if (!isset($user)) : ?>
                <a href="./?page=login" class="flex items-center justify-center space-x-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-3 rounded-xl font-medium shadow-lg mt-3">
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
    // Mobile Menu Toggle
    document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenu.classList.toggle('hidden');
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const mobileMenu = document.getElementById('mobile-menu');
        const menuButton = document.getElementById('mobile-menu-button');

        if (mobileMenu && menuButton && !mobileMenu.contains(event.target) && !menuButton.contains(event.target)) {
            mobileMenu.classList.add('hidden');
        }
    });

    // Add active state to current page
    const currentPage = new URLSearchParams(window.location.search).get('page');
    document.querySelectorAll('.nav-link').forEach(link => {
        const linkPage = new URL(link.href).searchParams.get('page');
        if (linkPage === currentPage) {
            link.classList.add('text-indigo-600', 'bg-indigo-50');
        }
    });
</script>