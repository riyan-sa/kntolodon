<?php
/**
 * ============================================================================
 * ADMIN/PENGATURAN.PHP - System Settings (Super Admin Only)
 * ============================================================================
 * 
 * System-wide configuration page untuk operational hours & holidays.
 * Two tabs: Waktu Operasi (7 days) & Hari Libur (dynamic list).
 * Settings enforce booking validation rules across entire system.
 * 
 * ACCESS CONTROL:
 * - Super Admin ONLY (strict check: role === 'Super Admin')
 * - Blocks Admin access (redirects to admin dashboard)
 * - Critical: Changes affect all users' booking capabilities
 * 
 * FEATURES:
 * 1. TAB SYSTEM
 *    - TAB 1: Waktu Operasi (Operational Hours per day)
 *    - TAB 2: Hari Libur (Holiday/Closure dates)
 *    - Tab switching via JavaScript
 *    - Independent content containers
 * 
 * 2. WAKTU OPERASI (Operational Hours)
 *    - 7 cards: Senin - Minggu
 *    - Each card shows: Jam Buka, Jam Tutup, Status (Aktif/Nonaktif)
 *    - Edit modal: Update jam_buka, jam_tutup, is_aktif
 *    - Status toggle: Disables booking for entire day when Nonaktif
 *    - Validation: jam_tutup > jam_buka
 * 
 * 3. HARI LIBUR (Holidays)
 *    - Dynamic list of closure dates
 *    - Table: No, Tanggal, Keterangan, Aksi
 *    - Add: FAB button at bottom-right
 *    - Edit: Modify tanggal, keterangan
 *    - Delete: Confirmation modal
 *    - Blocks bookings on registered dates
 * 
 * 4. VALIDATION INTEGRATION
 *    - validateWaktuOperasi(): Called in BookingController before creation
 *    - validateHariLibur(): Called in BookingController before creation
 *    - Returns: ['allowed' => bool, 'message' => string]
 *    - See: temp/IMPLEMENTASI_PENGATURAN_SISTEM.md
 * 
 * DATA FROM CONTROLLER:
 * - $waktuOperasi (array): 7 records (Senin-Minggu) dengan operational hours
 * - $hariLibur (array): All registered holidays sorted by tanggal ASC
 * - $currentTab (string): Active tab ('waktu-operasi' or 'hari-libur')
 * 
 * WAKTU OPERASI STRUCTURE:
 * $waktuOperasi = [
 *   [
 *     'id' => int,
 *     'hari' => string ('Senin', 'Selasa', ..., 'Minggu'),
 *     'jam_buka' => string (HH:MM:SS),
 *     'jam_tutup' => string (HH:MM:SS),
 *     'is_aktif' => int (1 = aktif, 0 = nonaktif)
 *   ],
 *   // 7 records total
 * ];
 * 
 * HARI LIBUR STRUCTURE:
 * $hariLibur = [
 *   [
 *     'id' => int,
 *     'tanggal' => string (YYYY-MM-DD),
 *     'keterangan' => string (reason/description),
 *     'created_by' => string (admin nomor_induk)
 *   ],
 *   // Dynamic count
 * ];
 * 
 * WAKTU OPERASI CARD:
 * - Day name (e.g., "Senin") as heading
 * - Jam Buka & Jam Tutup displayed (formatted HH:MM)
 * - Status badge:
 *   * Aktif: Green (bg-green-100 text-green-800)
 *   * Nonaktif: Red (bg-red-100 text-red-800)
 * - Edit button: Opens modal with pre-filled data
 * - Card layout: 1 col (mobile), 2 cols (md), 3 cols (lg), 7 cols (xl)
 * 
 * HARI LIBUR TABLE:
 * - Columns: No, Tanggal, Keterangan, Aksi
 * - No: Sequential number
 * - Tanggal: Formatted date (d F Y)
 * - Keterangan: Holiday description
 * - Aksi: Edit icon, Delete icon
 * - Empty state: "Belum ada hari libur terdaftar"
 * 
 * MODALS (3 types):
 * 1. EDIT WAKTU OPERASI MODAL
 *    - Fields: hari (read-only), jam_buka, jam_tutup, is_aktif (toggle)
 *    - Validation: jam_tutup > jam_buka
 *    - Submit: POST to ?page=admin&action=update_waktu_operasi
 * 
 * 2. ADD/EDIT HARI LIBUR MODAL
 *    - Fields: tanggal (date, min=today), keterangan (textarea)
 *    - Add mode: Empty form
 *    - Edit mode: Pre-filled data, hidden id field
 *    - Submit: POST to ?page=admin&action=create_hari_libur or update_hari_libur
 * 
 * 3. DELETE HARI LIBUR MODAL
 *    - Confirmation dialog dengan tanggal & keterangan
 *    - Warning: "Tindakan ini tidak dapat diurungkan"
 *    - Submit: POST to ?page=admin&action=delete_hari_libur
 * 
 * FORM VALIDATION (Waktu Operasi):
 * - Client-side (JavaScript):
 *   * jam_buka required
 *   * jam_tutup required
 *   * jam_tutup > jam_buka
 * - Server-side (Controller):
 *   * Same validations re-checked
 *   * Time format validation (HH:MM:SS)
 *   * is_aktif boolean check
 * 
 * FORM VALIDATION (Hari Libur):
 * - Client-side (JavaScript):
 *   * tanggal required, min=today (no past dates)
 *   * keterangan required, max 255 chars
 * - Server-side (Controller):
 *   * Date format validation (YYYY-MM-DD)
 *   * Past date prevention (tanggal >= today)
 *   * Duplicate date check (optional)
 *   * created_by = Super Admin nomor_induk
 * 
 * FAB (Floating Action Button):
 * - Position: fixed bottom-8 right-8
 * - Icon: Plus sign
 * - Color: Blue (bg-blue-600)
 * - Z-index: z-50
 * - Visibility: Only on Hari Libur tab
 * - Action: Opens Add Hari Libur modal
 * 
 * TARGET ELEMENTS:
 * - [data-tab-action]: Tab buttons (data-tab-name)
 * - .tab-content: Tab content containers
 * - #btn-edit-waktu-{hari}: Edit operational hours buttons
 * - #btn-add-libur: FAB for adding holiday
 * - [data-action="edit-libur"]: Edit holiday buttons (data-libur-id)
 * - [data-action="delete-libur"]: Delete holiday buttons (data-libur-id)
 * - #modal-waktu-operasi: Edit operational hours modal
 * - #modal-hari-libur: Add/edit holiday modal
 * - #modal-delete-libur: Delete holiday confirmation modal
 * 
 * JAVASCRIPT:
 * - assets/js/pengaturan.js: Tab switching, modal management
 * - Functions:
 *   * switchTab(tabName): Change active tab
 *   * openWaktuModal(hari, data): Show edit operational hours modal
 *   * openLiburModal(mode, data): Show add/edit holiday modal
 *   * openDeleteLiburModal(id, tanggal, keterangan): Show delete confirmation
 *   * closeModal(): Hide all modals
 *   * validateWaktuForm(): Validate operational hours form
 *   * validateLiburForm(): Validate holiday form
 * 
 * DATA ATTRIBUTES PATTERN:
 * ```php
 * <div id="pengaturan-data"
 *      data-base-path="<?= $basePath ?>"
 *      data-waktu-operasi='<?= json_encode($waktuOperasi) ?>'
 *      data-hari-libur='<?= json_encode($hariLibur) ?>'
 *      style="display:none;">
 * </div>
 * ```
 * 
 * CSS:
 * - External: assets/css/pengaturan.css
 * - Tailwind utilities
 * - Tab styles: tab-active (blue) vs bg-white (inactive)
 * - Card styles: Rounded, shadow, white background
 * - Icons: Inline SVG (clock, calendar, edit, delete)
 * 
 * ROUTING:
 * - View: ?page=admin&action=pengaturan&tab={waktu-operasi|hari-libur}
 * - Update waktu operasi: POST to ?page=admin&action=update_waktu_operasi
 * - Create hari libur: POST to ?page=admin&action=create_hari_libur
 * - Update hari libur: POST to ?page=admin&action=update_hari_libur
 * - Delete hari libur: POST to ?page=admin&action=delete_hari_libur
 * 
 * TAB SWITCHING:
 * - Active tab: tab-active class (bg-blue-600 text-white)
 * - Inactive tab: bg-white text-gray-600 hover:bg-blue-50
 * - JavaScript handles content visibility
 * - URL param: &tab=waktu-operasi or &tab=hari-libur
 * 
 * RESPONSIVE DESIGN:
 * - Mobile: Single column cards, scrollable table
 * - Tablet (md): 2-column grid for operational hours
 * - Desktop (lg): 3-column grid
 * - XL: 7-column grid (one card per day)
 * - Modals: Full screen mobile, max-w-md desktop
 * 
 * BUSINESS RULES:
 * - Waktu Operasi: is_aktif = 0 blocks ALL bookings for that day
 * - Hari Libur: Registered dates block ALL bookings
 * - Validation enforced in: BookingController::buat_booking()
 * - Booking form JS can fetch operational hours to disable unavailable slots
 * - Dashboard shows "Perpustakaan tutup" message on nonaktif days
 * 
 * SUCCESS FLOW (Edit Waktu Operasi):
 * 1. Super Admin clicks edit on Senin card
 * 2. Modal opens with current jam_buka, jam_tutup, is_aktif
 * 3. Updates: jam_tutup from 17:00 to 18:00
 * 4. Validates: jam_tutup > jam_buka
 * 5. Submit to server
 * 6. Server updates waktu_operasi record
 * 7. Alert success, reload page
 * 8. Card shows updated time
 * 9. Booking validation now uses new hours
 * 
 * SUCCESS FLOW (Add Hari Libur):
 * 1. Super Admin clicks FAB on Hari Libur tab
 * 2. Modal opens with empty form
 * 3. Fills: tanggal = 2025-12-25, keterangan = "Natal"
 * 4. Validates: tanggal >= today, keterangan not empty
 * 5. Submit to server
 * 6. Server creates hari_libur record with created_by
 * 7. Alert success, reload page
 * 8. New holiday appears in table
 * 9. Booking validation blocks 2025-12-25
 * 
 * ERROR HANDLING:
 * - jam_tutup <= jam_buka → alert "Jam tutup harus lebih dari jam buka!"
 * - Past date for holiday → alert "Tidak bisa menambah hari libur di masa lampau!"
 * - Duplicate date → alert "Tanggal sudah terdaftar sebagai hari libur!"
 * - Missing fields → HTML5 validation + server check
 * - Delete error → alert "Gagal menghapus hari libur!"
 * 
 * SECURITY:
 * - Super Admin only (strict role check)
 * - Past date prevention for holidays
 * - Time format validation
 * - created_by tracking (audit trail)
 * - XSS prevention: htmlspecialchars on outputs
 * 
 * INTEGRATION:
 * - Controller: AdminController (pengaturan, update_waktu_operasi, create_hari_libur, update_hari_libur, delete_hari_libur)
 * - Model: PengaturanModel (getWaktuOperasi, updateWaktuOperasi, getHariLibur, createHariLibur, updateHariLibur, deleteHariLibur, validateWaktuOperasi, validateHariLibur)
 * - Database: waktu_operasi (7 records), hari_libur (dynamic)
 * - Booking validation: BookingController calls validateWaktuOperasi() and validateHariLibur()
 * - Migration: temp/MIGRATION_PENGATURAN_SISTEM.sql
 * 
 * @package BookEZ
 * @subpackage Views\Admin
 * @version 1.0
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control: Super Admin only
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Super Admin') {
    header('Location: index.php?page=admin&action=index');
    exit;
}

require __DIR__ . '/../components/head.php';
?>

<title>Pengaturan Sistem - Super Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $asset('assets/css/pengaturan.css') ?>">
</head>

<body class="min-h-screen flex flex-col bg-gray-50">

    <!-- Admin Navbar -->
    <?php include __DIR__ . '/../components/navbar_admin.php'; ?>

    <!-- Main Content -->
    <main class="grow py-8 px-6">
        <div class="max-w-7xl mx-auto">
            
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Pengaturan Sistem</h1>
                <p class="text-gray-600 mt-1">Kelola waktu operasi dan hari libur perpustakaan</p>
            </div>

            <!-- Tab Switcher -->
            <div class="flex gap-4 mb-6">
                <button data-tab-action data-tab-name="waktu-operasi" id="btn-tab-waktu" 
                        class="px-8 py-3 rounded-lg font-semibold shadow-sm transition-all tab-active">
                    <svg class="inline w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Waktu Operasi
                </button>
                <button data-tab-action data-tab-name="hari-libur" id="btn-tab-libur" 
                        class="px-8 py-3 rounded-lg font-semibold shadow-sm transition-all tab-inactive">
                    <svg class="inline w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Hari Libur
                </button>
            </div>

            <!-- ========== TAB 1: WAKTU OPERASI ========== -->
            <div id="tab-waktu-operasi" class="tab-content">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-sky-700 text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold">Hari</th>
                                    <th class="px-6 py-4 text-left font-semibold">Jam Buka</th>
                                    <th class="px-6 py-4 text-left font-semibold">Jam Tutup</th>
                                    <th class="px-6 py-4 text-center font-semibold">Status</th>
                                    <th class="px-6 py-4 text-center font-semibold">Terakhir Diubah</th>
                                    <th class="px-6 py-4 text-center font-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($waktuOperasi as $wo): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($wo['hari']) ?></td>
                                    <td class="px-6 py-4 text-gray-700"><?= substr($wo['jam_buka'], 0, 5) ?></td>
                                    <td class="px-6 py-4 text-gray-700"><?= substr($wo['jam_tutup'], 0, 5) ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($wo['is_aktif']): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                Buka
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                                Tutup
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-600">
                                        <?php if ($wo['updated_by_name']): ?>
                                            <?= htmlspecialchars($wo['updated_by_name']) ?><br>
                                            <span class="text-xs text-gray-400"><?= date('d/m/Y H:i', strtotime($wo['updated_at'])) ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-400">Default</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button data-modal-edit-waktu 
                                                data-hari="<?= $wo['hari'] ?>" 
                                                data-jam-buka="<?= substr($wo['jam_buka'], 0, 5) ?>" 
                                                data-jam-tutup="<?= substr($wo['jam_tutup'], 0, 5) ?>" 
                                                data-is-aktif="<?= $wo['is_aktif'] ?>" 
                                                class="inline-flex items-center px-4 py-2 bg-sky-700 text-white rounded-lg hover:bg-yellow-600 transition font-medium">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========== TAB 2: HARI LIBUR ========== -->
            <div id="tab-hari-libur" class="tab-content hidden">
                
                <!-- Add Button -->
                <div class="mb-4 flex justify-end">
                    <button data-modal-add-libur 
                            class="inline-flex items-center px-6 py-3 bg-sky-700 text-white rounded-lg hover:bg-sky-800 transition font-semibold shadow-md">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Tambah Hari Libur
                    </button>
                </div>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <?php if (empty($hariLibur)): ?>
                        <div class="p-12 text-center text-gray-500">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-lg font-medium">Belum ada hari libur terdaftar</p>
                            <p class="text-sm mt-2">Klik tombol "Tambah Hari Libur" untuk menambahkan tanggal libur baru</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-sky-700 text-white">
                                    <tr>
                                        <th class="px-6 py-4 text-left font-semibold">Tanggal</th>
                                        <th class="px-6 py-4 text-left font-semibold">Keterangan</th>
                                        <th class="px-6 py-4 text-center font-semibold">Dibuat Oleh</th>
                                        <th class="px-6 py-4 text-center font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($hariLibur as $hl): ?>
                                        <?php
                                        $isPast = strtotime($hl['tanggal']) < strtotime('today');
                                        $rowClass = $isPast ? 'bg-gray-100' : 'hover:bg-gray-50';
                                        ?>
                                    <tr class="<?= $rowClass ?> transition">
                                        <td class="px-6 py-4 font-semibold <?= $isPast ? 'text-gray-500' : 'text-gray-800' ?>">
                                            <?= date('d F Y', strtotime($hl['tanggal'])) ?>
                                            <?php if ($isPast): ?>
                                                <span class="ml-2 text-xs text-gray-400">(Sudah lewat)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 <?= $isPast ? 'text-gray-500' : 'text-gray-700' ?>">
                                            <?= htmlspecialchars($hl['keterangan']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm <?= $isPast ? 'text-gray-400' : 'text-gray-600' ?>">
                                            <?= htmlspecialchars($hl['created_by_name'] ?? 'Super Admin') ?><br>
                                            <span class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($hl['created_at'])) ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button data-modal-edit-libur 
                                                        data-id="<?= $hl['id_hari_libur'] ?>" 
                                                        data-tanggal="<?= $hl['tanggal'] ?>" 
                                                        data-keterangan="<?= htmlspecialchars($hl['keterangan'], ENT_QUOTES, 'UTF-8') ?>" 
                                                        class="inline-flex items-center px-3 py-2 bg-sky-700 text-white rounded-lg hover:bg-yellow-600 transition text-sm font-medium">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                    Edit
                                                </button>
                                                <button data-modal-delete-libur 
                                                        data-id="<?= $hl['id_hari_libur'] ?>" 
                                                        data-tanggal-formatted="<?= date('d F Y', strtotime($hl['tanggal'])) ?>" 
                                                        data-keterangan="<?= htmlspecialchars($hl['keterangan'], ENT_QUOTES, 'UTF-8') ?>" 
                                                        class="inline-flex items-center px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-sm font-medium">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- ========== MODAL: EDIT WAKTU OPERASI ========== -->
    <div id="modal-edit-waktu" class="modal-overlay hidden">
        <div class="modal-content bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
            <div class="modal-header bg-sky-700 text-white px-6 py-4 rounded-t-lg">
                <h3 class="text-xl font-semibold">Edit Waktu Operasi</h3>
            </div>
            <form method="POST" action="index.php?page=admin&action=update_waktu_operasi">
                <div class="p-6 space-y-4">
                    <input type="hidden" name="hari" id="edit-hari">
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Hari</label>
                        <input type="text" id="edit-hari-display" disabled 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-700 font-medium">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Buka</label>
                            <input type="time" name="jam_buka" id="edit-jam-buka" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jam Tutup</label>
                            <input type="time" name="jam_tutup" id="edit-jam-tutup" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="is_aktif" value="1" id="edit-status-buka" class="w-4 h-4 text-green-600">
                                <span class="ml-2 text-sm font-medium text-gray-700">Buka</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="is_aktif" value="0" id="edit-status-tutup" class="w-4 h-4 text-red-600">
                                <span class="ml-2 text-sm font-medium text-gray-700">Tutup</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex justify-end gap-3 px-6 py-4 bg-gray-50 rounded-b-lg">
                    <button type="button" id="btn-cancel-edit-waktu" 
                            class="w-full px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition font-medium">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== MODAL: TAMBAH HARI LIBUR ========== -->
    <div id="modal-add-libur" class="modal-overlay hidden">
        <div class="modal-content bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
            <div class="modal-header bg-sky-700 text-white px-6 py-4 rounded-t-lg">
                <h3 class="text-xl font-semibold">Tambah Hari Libur</h3>
            </div>
            <form method="POST" action="index.php?page=admin&action=create_hari_libur">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Libur</label>
                        <input type="date" name="tanggal" id="add-tanggal" required
                               min="<?= date('Y-m-d') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Keterangan</label>
                        <textarea name="keterangan" id="add-keterangan" required rows="3"
                                  placeholder="Contoh: Hari Raya Natal, Maintenance Gedung, Event Kampus"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"></textarea>
                    </div>
                </div>
                <div class="modal-footer flex justify-end gap-3 px-6 py-4 bg-gray-50 rounded-b-lg">
                    <button type="button" id="btn-cancel-add-libur" 
                            class="w-full px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 bg-sky-700 text-white rounded-lg hover:bg-sky-800 transition font-medium">
                        Tambah
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== MODAL: EDIT HARI LIBUR ========== -->
    <div id="modal-edit-libur" class="modal-overlay hidden">
        <div class="modal-content bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
            <div class="modal-header bg-sky-700 text-white px-6 py-4 rounded-t-lg">
                <h3 class="text-xl font-semibold">Edit Hari Libur</h3>
            </div>
            <form method="POST" action="index.php?page=admin&action=update_hari_libur">
                <input type="hidden" name="id_hari_libur" id="edit-libur-id">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Libur</label>
                        <input type="date" name="tanggal" id="edit-libur-tanggal" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Keterangan</label>
                        <textarea name="keterangan" id="edit-libur-keterangan" required rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent resize-none"></textarea>
                    </div>
                </div>
                <div class="modal-footer flex justify-end gap-3 px-6 py-4 bg-gray-50 rounded-b-lg">
                    <button type="button" id="btn-cancel-edit-libur" 
                            class="w-full px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 bg-sky-700 text-white rounded-lg hover:bg-yellow-600 transition font-medium">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== MODAL: DELETE HARI LIBUR ========== -->
    <div id="modal-delete-libur" class="modal-overlay hidden">
        <div class="modal-content bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
            <div class="modal-header bg-red-600 text-white px-6 py-4 rounded-t-lg">
                <h3 class="text-xl font-semibold">Hapus Hari Libur</h3>
            </div>
            <form method="POST" action="index.php?page=admin&action=delete_hari_libur">
                <input type="hidden" name="id_hari_libur" id="delete-libur-id">
                <div class="p-6">
                    <p class="text-gray-700 mb-4">Apakah Anda yakin ingin menghapus hari libur ini?</p>
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 mb-1">Tanggal:</p>
                        <p class="font-semibold text-gray-800" id="delete-libur-tanggal"></p>
                        <p class="text-sm text-gray-600 mt-3 mb-1">Keterangan:</p>
                        <p class="font-semibold text-gray-800" id="delete-libur-keterangan"></p>
                    </div>
                </div>
                <div class="modal-footer flex justify-end gap-3 px-6 py-4 bg-gray-50 rounded-b-lg">
                    <button type="button" id="btn-cancel-delete-libur" 
                            class="w-full px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                        Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= $asset('assets/js/pengaturan.js') ?>"></script>
</body>
</html>
