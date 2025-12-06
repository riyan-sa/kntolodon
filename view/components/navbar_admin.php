    <!-- Navbar Admin -->
    <nav class="bg-white py-4 px-6 flex flex-col lg:flex-row justify-between items-center shadow-sm sticky top-0 z-50">
        <!-- Logo Section -->
        <a href="?page=admin" class="flex items-center gap-2 mb-4 lg:mb-0">
            <img src="<?= $asset('/assets/image/logo.png') ?>" alt="BookEZ Logo" class="h-8 w-auto object-contain logo-scale">
        </a>

        <!-- Navigation Links -->
        <div class="flex flex-wrap justify-center gap-6 lg:gap-8">
            <?php if ($_SESSION['user']['role'] === 'Super Admin'): ?>
                <a href="?page=admin&action=booking_external" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Booking Eksternal</span>
                </a>
            <?php endif; ?>
            <a href="?page=admin&action=kelola_ruangan" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                </svg>
                <span>Kelola Ruangan</span>
            </a>
            <a href="?page=admin&action=laporan" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Laporan Peminjaman</span>
            </a>
            <a href="?page=admin&action=booking_list" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span>Booking-List</span>
            </a>
            <a href="?page=admin&action=member_list" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>Member-List</span>
            </a>
            <?php if ($_SESSION['user']['role'] === 'Super Admin'): ?>
                <a href="?page=admin&action=pengaturan" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    <span>Pengaturan</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- User Profile -->
        <a href="?page=profile" class="flex items-center gap-3">
            <span class="text-xl font-bold text-gray-800"><?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['username']) : 'Guest' ?></span>
            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 overflow-hidden">
                <?php if (isset($_SESSION['user']['foto_profil']) && !empty($_SESSION['user']['foto_profil'])): ?>
                    <img src="<?= htmlspecialchars($asset($_SESSION['user']['foto_profil']), ENT_QUOTES) ?>" 
                         alt="Foto Profil" 
                         class="w-full h-full object-cover">
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                <?php endif; ?>
            </div>
        </a>
    </nav>