<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control: Only Admin and Super Admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

require __DIR__ . '/../components/head.php';
?>

<title>Member List - Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $asset('assets/css/member-list.css') ?>">
</head>

<body class="min-h-screen flex flex-col">

    <!-- admin navbar -->
    <?php include __DIR__ . '/../components/navbar_admin.php'; ?>

    <!-- Main Content -->
    <main class="grow py-8 px-6">

        <!-- Container Wrapper -->
        <div class="max-w-7xl mx-auto">
            <!-- Tab Switcher -->
            <div class="flex gap-4 mb-6">
                <button type="button" data-member-tab data-tab-name="user" id="btn-tab-user" class="px-12 py-2 rounded-full font-semibold shadow-sm transition-all tab-active">
                    User
                </button>
                <button type="button" data-member-tab data-tab-name="admin" id="btn-tab-admin" class="px-12 py-2 rounded-full font-semibold shadow-sm transition-all tab-inactive">
                    Admin
                </button>
            </div>

            <!-- Search & Filter Bar (Blue Header) -->
            <form method="GET" action="index.php" class="bg-blue-800 p-4 rounded-t-lg shadow-md">
                <input type="hidden" name="page" value="admin">
                <input type="hidden" name="action" value="member_list">
                <input type="hidden" id="current-tab-input" name="tab" value="user">
                
                <div class="flex flex-col md:flex-row gap-4 items-center justify-between text-white font-medium">

                    <!-- Nama Search -->
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <label class="whitespace-nowrap w-16 text-white">Nama :</label>
                        <div class="relative w-full md:w-64">
                            <input type="text" id="search-nama" name="nama" value="<?= htmlspecialchars($_GET['nama'] ?? '') ?>" class="w-full pl-3 pr-10 py-2 rounded text-gray-800 text-sm focus:outline-none bg-white" placeholder="">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Nomor Induk Search (NIM/NIP) -->
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <label class="whitespace-nowrap text-white" id="label-nomor-induk">Nomor Induk :</label>
                        <div class="relative w-full md:w-48">
                            <input type="text" id="search-nomor-induk" name="nomor_induk" value="<?= htmlspecialchars($_GET['nomor_induk'] ?? '') ?>" class="w-full pl-3 pr-10 py-2 rounded text-gray-800 text-sm focus:outline-none bg-white" placeholder="">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Prodi (Only for User) -->
                    <div class="flex items-center gap-2 w-full md:w-auto" id="filter-kelas-container">
                        <label class="whitespace-nowrap text-white">Prodi :</label>
                        <div class="relative w-full md:w-40">
                            <select id="filter-kelas" name="prodi" class="w-full px-3 py-2 rounded text-gray-800 text-sm focus:outline-none bg-white appearance-none cursor-pointer">
                                <option value="">Semua</option>
                                <?php foreach ($prodiList as $prodi): ?>
                                    <option value="<?= htmlspecialchars($prodi) ?>" <?= (isset($_GET['prodi']) && $_GET['prodi'] === $prodi) ? 'selected' : '' ?>><?= htmlspecialchars($prodi) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <label class="whitespace-nowrap text-white">Status :</label>
                        <div class="relative w-full md:w-40">
                            <select id="filter-status" name="status" class="w-full px-3 py-2 rounded text-gray-800 text-sm focus:outline-none bg-white appearance-none cursor-pointer">
                                <option value="">Semua</option>
                                <option value="Aktif" <?= (isset($_GET['status']) && $_GET['status'] === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="Tidak Aktif" <?= (isset($_GET['status']) && $_GET['status'] === 'Tidak Aktif') ? 'selected' : '' ?>>Tidak Aktif</option>
                            </select>
                        </div>
                    </div>

                </div>
            </form>

            <!-- Data Table -->
            <div class="bg-white shadow-md rounded-b-lg overflow-hidden">
                <div id="table-header" class="grid grid-cols-12 gap-4 bg-slate-200/50 p-3 text-sky-600 font-semibold border-b border-gray-200">
                    <div id="header-profil" class="col-span-1 text-center">Profil</div>
                    <div id="header-nama" class="col-span-3 pl-4">Nama</div>
                    <div id="header-nomor-induk" class="col-span-2">Nomor Induk</div>
                    <div id="header-prodi" class="col-span-2">Prodi</div>
                    <div id="header-validasi" class="col-span-2 text-center">Foto Validasi</div>
                    <div id="header-status" class="col-span-2 text-center">Status</div>
                </div>

                <!-- List Container - User Tab -->
                <div id="list-container-user" class="divide-y divide-gray-100">
                    <?php if (empty($userData)): ?>
                        <div class="p-8 text-center text-gray-500">
                            <p>Tidak ada data user yang ditemukan.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($userData as $user): ?>
                            <div class="user-card grid grid-cols-12 gap-4 p-4 items-center hover:bg-blue-50 transition-colors border-l-4 border-transparent hover:border-sky-600 cursor-pointer"
                                data-nama="<?= htmlspecialchars($user['username']) ?>"
                                data-nomor-induk="<?= htmlspecialchars($user['nomor_induk']) ?>"
                                data-prodi="<?= htmlspecialchars($user['prodi'] ?? '') ?>"
                                data-status="<?= htmlspecialchars($user['status']) ?>"
                                data-member-view
                                data-member-data='<?= json_encode($user, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>'
                                data-member-type="user">
                                <div class="col-span-1 flex justify-center text-sky-600">
                                    <?php if (!empty($user['foto_profil'])): ?>
                                        <img src="<?= $asset($user['foto_profil']) ?>" alt="Foto Profil" class="w-10 h-10 rounded-full object-cover">
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="col-span-3 pl-4 font-semibold text-gray-800 truncate"><?= htmlspecialchars($user['username']) ?></div>
                                <div class="col-span-2 font-medium text-gray-600"><?= htmlspecialchars($user['nomor_induk']) ?></div>
                                <div class="col-span-2 text-gray-600"><?= htmlspecialchars($user['prodi'] ?? '-') ?></div>
                                <div class="col-span-2 flex justify-center" onclick="event.stopPropagation();">
                                    <?php if (!empty($user['validasi_mahasiswa'])): ?>
                                        <a href="<?= $asset($user['validasi_mahasiswa']) ?>" download class="flex flex-row items-center gap-1 px-3 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-md text-sm font-medium transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Download
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">-</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-span-2 text-center font-bold <?= $user['status'] === 'Aktif' ? 'text-gray-800' : 'text-red-500' ?>"><?= htmlspecialchars($user['status']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination for User Tab -->
                <div id="pagination-user">
                <?php if (isset($paginationUser) && $paginationUser['totalPages'] > 1): ?>
                    <?php
                    $baseUrl = 'index.php';
                    $queryParams = [
                        'page' => 'admin',
                        'action' => 'member_list',
                        'tab' => 'user'
                    ];
                    if (!empty($_GET['nama'])) $queryParams['nama'] = $_GET['nama'];
                    if (!empty($_GET['nomor_induk'])) $queryParams['nomor_induk'] = $_GET['nomor_induk'];
                    if (!empty($_GET['prodi'])) $queryParams['prodi'] = $_GET['prodi'];
                    if (!empty($_GET['status'])) $queryParams['status'] = $_GET['status'];
                    
                    $currentPage = $paginationUser['currentPage'];
                    $totalPages = $paginationUser['totalPages'];
                    ?>
                    
                    <div class="bg-white px-6 py-4 border-t border-gray-200">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <!-- Info -->
                            <div class="text-sm text-gray-600">
                                Menampilkan 
                                <span class="font-semibold"><?= (($currentPage - 1) * $paginationUser['perPage']) + 1 ?></span>
                                - 
                                <span class="font-semibold"><?= min($currentPage * $paginationUser['perPage'], $paginationUser['totalRecords']) ?></span>
                                dari 
                                <span class="font-semibold"><?= $paginationUser['totalRecords'] ?></span>
                                user
                            </div>
                            
                            <!-- Pagination Controls -->
                            <nav class="inline-flex rounded-md shadow-sm" aria-label="Pagination">
                                <!-- Previous -->
                                <?php if ($currentPage > 1): 
                                    $prevParams = $queryParams;
                                    $prevParams['pg_user'] = $currentPage - 1;
                                ?>
                                    <a href="<?= $baseUrl ?>?<?= http_build_query($prevParams) ?>" 
                                       class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                                        &laquo; Prev
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-l-md cursor-not-allowed">
                                        &laquo; Prev
                                    </span>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php 
                                $range = 2;
                                $startPage = max(1, $currentPage - $range);
                                $endPage = min($totalPages, $currentPage + $range);
                                
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                    $pageParams = $queryParams;
                                    $pageParams['pg_user'] = $i;
                                ?>
                                    <?php if ($i == $currentPage): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-[#1e73be] border border-[#1e73be]">
                                            <?= $i ?>
                                        </span>
                                    <?php else: ?>
                                        <a href="<?= $baseUrl ?>?<?= http_build_query($pageParams) ?>" 
                                           class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50">
                                            <?= $i ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Next -->
                                <?php if ($currentPage < $totalPages): 
                                    $nextParams = $queryParams;
                                    $nextParams['pg_user'] = $currentPage + 1;
                                ?>
                                    <a href="<?= $baseUrl ?>?<?= http_build_query($nextParams) ?>" 
                                       class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                                        Next &raquo;
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-r-md cursor-not-allowed">
                                        Next &raquo;
                                    </span>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
                </div>

                <!-- List Container - Admin Tab (Hidden by default) -->
                <div id="list-container-admin" class="divide-y divide-gray-100 hidden">
                    <?php if (empty($adminData)): ?>
                        <div class="p-8 text-center text-gray-500">
                            <p>Tidak ada data admin yang ditemukan.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($adminData as $admin): ?>
                            <div class="admin-card grid grid-cols-12 gap-4 p-4 items-center hover:bg-blue-50 cursor-pointer transition-colors border-l-4 border-transparent hover:border-sky-600"
                                data-nama="<?= htmlspecialchars($admin['username']) ?>"
                                data-nomor-induk="<?= htmlspecialchars($admin['nomor_induk']) ?>"
                                data-status="<?= htmlspecialchars($admin['status']) ?>"
                                data-member-view
                                data-member-data='<?= json_encode($admin, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>'
                                data-member-type="admin">
                                <div class="col-span-3 flex justify-center text-sky-600">
                                    <?php if (!empty($admin['foto_profil'])): ?>
                                        <img src="<?= $asset($admin['foto_profil']) ?>" alt="Foto Profil" class="w-10 h-10 rounded-full object-cover">
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="col-span-4 pl-4 font-semibold text-gray-800 truncate"><?= htmlspecialchars($admin['username']) ?></div>
                                <div class="col-span-3 font-medium text-gray-600"><?= htmlspecialchars($admin['nomor_induk']) ?></div>
                                <div class="col-span-2 text-center font-bold <?= $admin['status'] === 'Aktif' ? 'text-gray-800' : 'text-red-500' ?>"><?= htmlspecialchars($admin['status']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination for Admin Tab -->
                <?php if (isset($paginationAdmin) && $paginationAdmin['totalPages'] > 1): ?>
                    <?php
                    $baseUrlAdmin = 'index.php';
                    $queryParamsAdmin = [
                        'page' => 'admin',
                        'action' => 'member_list',
                        'tab' => 'admin'
                    ];
                    if (!empty($_GET['nama_admin'])) $queryParamsAdmin['nama_admin'] = $_GET['nama_admin'];
                    if (!empty($_GET['nomor_induk_admin'])) $queryParamsAdmin['nomor_induk_admin'] = $_GET['nomor_induk_admin'];
                    if (!empty($_GET['status_admin'])) $queryParamsAdmin['status_admin'] = $_GET['status_admin'];
                    
                    $currentPageAdmin = $paginationAdmin['currentPage'];
                    $totalPagesAdmin = $paginationAdmin['totalPages'];
                    ?>
                    
                    <div id="pagination-admin" class="bg-white px-6 py-4 border-t border-gray-200 hidden">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <!-- Info -->
                            <div class="text-sm text-gray-600">
                                Menampilkan 
                                <span class="font-semibold"><?= (($currentPageAdmin - 1) * $paginationAdmin['perPage']) + 1 ?></span>
                                - 
                                <span class="font-semibold"><?= min($currentPageAdmin * $paginationAdmin['perPage'], $paginationAdmin['totalRecords']) ?></span>
                                dari 
                                <span class="font-semibold"><?= $paginationAdmin['totalRecords'] ?></span>
                                admin
                            </div>
                            
                            <!-- Pagination Controls -->
                            <nav class="inline-flex rounded-md shadow-sm" aria-label="Pagination">
                                <!-- Previous -->
                                <?php if ($currentPageAdmin > 1): 
                                    $prevParamsAdmin = $queryParamsAdmin;
                                    $prevParamsAdmin['pg_admin'] = $currentPageAdmin - 1;
                                ?>
                                    <a href="<?= $baseUrlAdmin ?>?<?= http_build_query($prevParamsAdmin) ?>" 
                                       class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                                        &laquo; Prev
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-l-md cursor-not-allowed">
                                        &laquo; Prev
                                    </span>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php 
                                $rangeAdmin = 2;
                                $startPageAdmin = max(1, $currentPageAdmin - $rangeAdmin);
                                $endPageAdmin = min($totalPagesAdmin, $currentPageAdmin + $rangeAdmin);
                                
                                for ($i = $startPageAdmin; $i <= $endPageAdmin; $i++): 
                                    $pageParamsAdmin = $queryParamsAdmin;
                                    $pageParamsAdmin['pg_admin'] = $i;
                                ?>
                                    <?php if ($i == $currentPageAdmin): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-[#1e73be] border border-[#1e73be]">
                                            <?= $i ?>
                                        </span>
                                    <?php else: ?>
                                        <a href="<?= $baseUrlAdmin ?>?<?= http_build_query($pageParamsAdmin) ?>" 
                                           class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50">
                                            <?= $i ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Next -->
                                <?php if ($currentPageAdmin < $totalPagesAdmin): 
                                    $nextParamsAdmin = $queryParamsAdmin;
                                    $nextParamsAdmin['pg_admin'] = $currentPageAdmin + 1;
                                ?>
                                    <a href="<?= $baseUrlAdmin ?>?<?= http_build_query($nextParamsAdmin) ?>" 
                                       class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                                        Next &raquo;
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-r-md cursor-not-allowed">
                                        Next &raquo;
                                    </span>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        <!-- End Container Wrapper -->

    </main>

    <!-- Floating Action Button (Only for Super Admin on Admin Tab) -->
    <?php if ($_SESSION['user']['role'] === 'Super Admin'): ?>
        <div id="fab-container" class="fixed bottom-8 right-8 hidden z-30">
            <button data-modal-action="add" class="btn-add bg-[#1D74BD] hover:bg-sky-700 text-white p-4 rounded-full shadow-lg flex items-center justify-center w-14 h-14 transition-transform transform hover:scale-110">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
                </svg>
            </button>
        </div>
    <?php endif; ?>

    <!-- Hidden data for JavaScript -->
    <!-- Data akan di-inject melalui data attributes -->\n    <div id="member-list-data" data-user-role="<?= $_SESSION['user']['role'] ?>" data-base-path="<?= $basePath ?>" style="display:none;"></div>

    <!-- Modal (Overlay) -->
    <div id="memberModal" class="fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm hidden-modal transition-opacity duration-300" style="display: none;">
        <!-- Modal Card -->
        <div class="bg-white rounded-lg shadow-2xl w-[90%] max-w-md p-8 relative transform scale-100" onclick="event.stopPropagation()">
            <!-- Close Button -->
            <button id="btn-close-modal" class="absolute flex flex-row top-4 left-4 text-gray-400 hover:text-gray-600 z-10">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7M8 12h9" />
                </svg>
                <span class="text-sm font-medium">Back</span>
            </button>

            <!-- Dynamic Content Container -->
            <div id="modal-content-container" class="flex flex-col items-center text-center">
                <!-- Content injected via JS -->
            </div>
        </div>
    </div>

    <script src="<?= $asset('assets/js/member-list.js') ?>" defer></script>
</body>

</html>