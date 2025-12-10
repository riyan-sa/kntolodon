<?php
/**
 * ============================================================================
 * ADMIN/KELOLA_RUANGAN.PHP - Room Management Page
 * ============================================================================
 * 
 * CRUD interface untuk manage all rooms (Ruang Umum & Ruang Rapat).
 * Grid layout dengan modals untuk add/edit/delete operations.
 * 
 * ACCESS CONTROL:
 * - Admin & Super Admin only
 * - Blocks regular User access
 * 
 * FEATURES:
 * 1. ROOM GRID DISPLAY
 *    - Grid layout: 1 col (mobile), 2 cols (md), 3 cols (lg)
 *    - Card structure: Photo + Details side-by-side
 *    - Displays: Foto, Nama, Jenis, Kapasitas, Status
 *    - Action buttons: Edit, Delete
 * 
 * 2. ADD ROOM (Modal)
 *    - Button: FAB (Floating Action Button) at bottom-right
 *    - Modal form dengan all room fields
 *    - Photo upload: Optional, max 5MB
 *    - Submit: Creates new room record
 * 
 * 3. EDIT ROOM (Modal)
 *    - Triggered: Click edit icon on room card
 *    - Pre-populated form dengan existing data
 *    - Shows current photo if exists
 *    - Can upload new photo (replaces old)
 *    - Submit: Updates room record
 * 
 * 4. DELETE ROOM (Modal)
 *    - Triggered: Click delete icon on room card
 *    - Confirmation dialog dengan room name
 *    - Warning: "Tindakan ini tidak dapat diurungkan"
 *    - Submit: Deletes room + associated photo
 * 
 * 5. IMAGE PREVIEW
 *    - Live preview before upload
 *    - Uses FileReader API
 *    - Works untuk both Add and Edit modals
 *    - Target containers: #addPreview, #editPreview
 * 
 * DATA FROM CONTROLLER:
 * - $rooms (array): All room records dari RuanganModel::getAll()
 * - Each room contains: id, nama, jenis, kapasitas (min/max), status, deskripsi, tata_tertib, foto
 * 
 * ROOM DATA STRUCTURE:
 * $room = [
 *   'id_ruangan' => int,
 *   'nama_ruangan' => string,
 *   'jenis_ruangan' => string ('Ruang Umum', 'Ruang Rapat'),
 *   'minimal_kapasitas_ruangan' => int,
 *   'maksimal_kapasitas_ruangan' => int,
 *   'status_ruangan' => string ('Tersedia', 'Sedang Digunakan', 'Tidak Tersedia'),
 *   'deskripsi' => string|null,
 *   'tata_tertib' => string|null,
 *   'foto_ruangan' => string|null (path)
 * ];
 * 
 * ROOM CARD STRUCTURE:
 * - Photo section (left): w-full md:w-5/12
 *   - Image or placeholder
 *   - Aspect ratio maintained
 * - Details section (right): w-full md:w-7/12
 *   - Nama ruangan (heading)
 *   - Jenis badge (Umum: blue, Rapat: purple)
 *   - Kapasitas line (min-max orang)
 *   - Status badge (color-coded)
 *   - Action buttons (Edit, Delete)
 * 
 * FAB (Floating Action Button):
 * - Position: fixed bottom-8 right-8
 * - Icon: Plus sign
 * - Color: Blue (bg-blue-600)
 * - Hover: bg-blue-700 + shadow-xl
 * - Z-index: z-50
 * - Triggers: Add modal
 * 
 * MODALS (3 types):
 * 1. ADD MODAL (#addModal)
 *    - Empty form
 *    - All fields editable
 *    - Photo upload optional
 *    - Submit: ?page=admin&action=tambah_ruangan
 * 
 * 2. EDIT MODAL (#editModal)
 *    - Pre-filled form
 *    - Shows current photo
 *    - Hidden id_ruangan field
 *    - Photo upload replaces old
 *    - Submit: ?page=admin&action=update_ruangan
 * 
 * 3. DELETE MODAL (#deleteModal)
 *    - Confirmation only
 *    - Shows room name
 *    - Hidden id_ruangan field
 *    - Submit: ?page=admin&action=delete_ruangan
 * 
 * FORM FIELDS (Add/Edit):
 * - nama_ruangan (text, required): Room name
 * - jenis_ruangan (select, required): Ruang Umum / Ruang Rapat
 * - minimal_kapasitas (number, required): Min capacity (> 0)
 * - maksimal_kapasitas (number, required): Max capacity (> min)
 * - status_ruangan (select, required): Tersedia / Tidak Tersedia / Sedang Digunakan
 * - deskripsi (textarea, optional): Room description
 * - tata_tertib (textarea, optional): Room rules
 * - foto_ruangan (file, optional): Room photo (image only, max 5MB)
 * 
 * FORM VALIDATION:
 * - Client-side (JavaScript):
 *   * Required fields check
 *   * maksimal_kapasitas > minimal_kapasitas
 *   * File type check (image only)
 *   * File size check (max 5MB)
 * - Server-side (Controller):
 *   * All client validations re-checked
 *   * MIME type validation (image/jpeg, image/png, image/webp)
 *   * File size strict limit (25MB server max)
 *   * Unique nama_ruangan check (optional)
 * 
 * PHOTO UPLOAD:
 * - Max size: 5MB (client), 25MB (server)
 * - Allowed: JPEG, PNG, WebP
 * - Storage: assets/uploads/images/
 * - Filename: room_{timestamp}_{random}.{ext}
 * - Old photo: Auto-deleted on update/delete
 * 
 * TARGET ELEMENTS:
 * - #btn-add-room: FAB button
 * - #addModal: Add modal container
 * - #editModal: Edit modal container
 * - #deleteModal: Delete modal container
 * - .btn-edit-room: Edit buttons (data-room-id)
 * - .btn-delete-room: Delete buttons (data-room-id)
 * - #rooms-data: Data container (data-rooms JSON, data-base-path)
 * 
 * JAVASCRIPT:
 * - assets/js/kelola-ruangan.js: Modal management, image preview, form validation
 * - Functions:
 *   * openAddModal(): Show add modal
 *   * openEditModal(roomId): Show edit modal dengan data
 *   * openDeleteModal(roomId): Show delete confirmation
 *   * closeModal(): Hide all modals
 *   * previewImage(event, targetId): Image preview
 * 
 * DATA ATTRIBUTES PATTERN:
 * ```php
 * <div id="rooms-data" 
 *      data-rooms='<?= json_encode($rooms) ?>'
 *      data-base-path="<?= $basePath ?>"
 *      style="display:none;">
 * </div>
 * ```
 * - Passes PHP data to JavaScript
 * - NO inline script blocks pattern
 * 
 * ROUTING:
 * - View: ?page=admin&action=kelola_ruangan
 * - Add: POST to ?page=admin&action=tambah_ruangan
 * - Edit: POST to ?page=admin&action=update_ruangan
 * - Delete: POST to ?page=admin&action=delete_ruangan
 * 
 * STATUS BADGES:
 * - Tersedia: Green (bg-green-100 text-green-800)
 * - Sedang Digunakan: Yellow (bg-yellow-100 text-yellow-800)
 * - Tidak Tersedia: Red (bg-red-100 text-red-800)
 * 
 * JENIS BADGES:
 * - Ruang Umum: Blue (bg-blue-100 text-blue-800)
 * - Ruang Rapat: Purple (bg-purple-100 text-purple-800)
 * 
 * RESPONSIVE DESIGN:
 * - Mobile: Cards stack vertically, photo on top
 * - Tablet (md): 2 column grid, photo left, details right
 * - Desktop (lg): 3 column grid
 * - Modals: Full screen mobile, max-w-md desktop
 * 
 * CSS:
 * - External: assets/css/kelola-ruangan.css
 * - Tailwind utilities
 * - Card hover effects: hover:shadow-md
 * - FAB shadow: shadow-lg hover:shadow-xl
 * 
 * PLACEHOLDER IMAGE:
 * - Used when: foto_ruangan is NULL or empty
 * - Path: assets/image/room.png
 * - Pattern: $fotoRuangan = !empty($room['foto_ruangan']) ? ... : $defaultImage;
 * 
 * SUCCESS FLOW (Add):
 * 1. Click FAB button
 * 2. Modal opens dengan empty form
 * 3. Fill all required fields
 * 4. Upload photo (optional)
 * 5. Submit form
 * 6. Server validates
 * 7. Creates room record
 * 8. Uploads photo if provided
 * 9. Alert success, reload page
 * 10. New room appears in grid
 * 
 * SUCCESS FLOW (Edit):
 * 1. Click edit icon on room card
 * 2. Modal opens with pre-filled data
 * 3. Modify fields as needed
 * 4. Upload new photo (optional, replaces old)
 * 5. Submit form
 * 6. Server validates
 * 7. Updates room record
 * 8. Replaces photo if new uploaded
 * 9. Deletes old photo
 * 10. Alert success, reload page
 * 
 * SUCCESS FLOW (Delete):
 * 1. Click delete icon on room card
 * 2. Confirmation modal appears
 * 3. Click "Hapus" button
 * 4. Server validates
 * 5. Checks: No active bookings (should implement)
 * 6. Deletes room record
 * 7. Deletes associated photo
 * 8. Alert success, reload page
 * 
 * ERROR HANDLING:
 * - Invalid capacity → alert "Kapasitas maksimal harus lebih dari minimal!"
 * - Invalid file type → alert "Hanya gambar yang diperbolehkan!"
 * - File too large → alert "Ukuran file maksimal 5MB!"
 * - Duplicate name → alert "Nama ruangan sudah ada!"
 * - Missing fields → HTML5 validation + server check
 * 
 * SECURITY:
 * - Admin/Super Admin access only
 * - File upload: MIME validation, size limit
 * - Unique filenames: Prevent overwrites
 * - Photo deletion: Removes old file on update/delete
 * - XSS prevention: htmlspecialchars on outputs
 * 
 * INTEGRATION:
 * - Controller: AdminController (kelola_ruangan, tambah_ruangan, update_ruangan, delete_ruangan)
 * - Model: RuanganModel (getAll, create, update, delete, autoUpdateRoomStatus)
 * - Database: ruangan table
 * - Upload: assets/uploads/images/
 * 
 * @package BookEZ
 * @subpackage Views\Admin
 * @version 1.0
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control: Only Admin and Super Admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

require __DIR__ . '/../components/head.php';

// Default placeholder image jika tidak ada foto
$defaultImage = $asset('/assets/image/room.png');
?>

<title>Kelola Ruangan - Admin Dashboard</title>
<link rel="stylesheet" href="<?= $asset('assets/css/kelola-ruangan.css') ?>">
</head>

<body class="bg-slate-50 h-full flex flex-col">

    <?php require __DIR__ . '/../components/navbar_admin.php'; ?>

    <!-- konten utama -->
    <main class="grow py-8 max-w-7xl mx-auto w-full px-4 lg:px-8">

        <!-- Grid Ruangan -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <?php foreach ($rooms as $room): 
                $fotoRuangan = !empty($room['foto_ruangan']) ? $asset($room['foto_ruangan']) : $defaultImage;
                $isAvailable = $room['status_ruangan'] === 'Tersedia';
            ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden flex flex-col md:flex-row min-h-[280px] transition hover:shadow-md">
                    <div class="w-full md:w-5/12 relative h-48 md:h-auto">
                        <img src="<?= $fotoRuangan ?>" alt="<?= htmlspecialchars($room['nama_ruangan']) ?>" class="absolute inset-0 w-full h-full object-cover">
                    </div>

                    <div class="w-full md:w-7/12 p-4 flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 mb-3"><?= htmlspecialchars($room['nama_ruangan']) ?></h3>
                            <div class="text-sm text-slate-500 space-y-2">
                                <p>Jenis: 
                                    <span class="font-medium <?= $room['jenis_ruangan'] === 'Ruang Umum' ? 'text-blue-600' : 'text-purple-600' ?>">
                                        <?= htmlspecialchars($room['jenis_ruangan']) ?>
                                    </span>
                                    <?php if ($room['jenis_ruangan'] === 'Ruang Umum'): ?>
                                        <span class="text-xs text-blue-500">(User)</span>
                                    <?php else: ?>
                                        <span class="text-xs text-purple-500">(Eksternal)</span>
                                    <?php endif; ?>
                                </p>
                                <p>Minimal: <span class="font-medium text-slate-700"><?= $room['minimal_kapasitas_ruangan'] ?></span></p>
                                <p>Maksimal: <span class="font-medium text-slate-700"><?= $room['maksimal_kapasitas_ruangan'] ?></span></p>
                                <p>Status:
                                    <span class="<?= $isAvailable ? 'text-green-600' : 'text-red-600' ?> font-medium">
                                        <?= htmlspecialchars($room['status_ruangan']) ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2">
                            <button data-action="edit" data-room-id="<?= $room['id_ruangan'] ?>" class="btn-edit-room block w-full py-2 bg-white hover:bg-gray-300 rounded text-center text-sky-500 font-semibold text-sm hover:text-sky-600 border border-sky-500 transition-colors">
                                Update
                            </button>
                            <button data-action="delete" data-room-id="<?= $room['id_ruangan'] ?>" class="btn-delete-room w-full bg-red-700 hover:bg-red-800 text-white text-sm font-bold py-2 rounded shadow-sm transition-colors">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

        <!-- Tombol Tambah Ruangan -->
        <div class="mt-10 flex justify-center">
            <button id="btn-add-room" class="relative group w-full max-w-lg h-40 rounded-xl overflow-hidden shadow-lg border-2 border-sky-500 cursor-pointer block">
                <img src="<?= $defaultImage ?>"
                    alt="Tambah Ruangan"
                    class="absolute inset-0 w-full h-full object-cover transition duration-300 group-hover:scale-105">

                <div class="absolute inset-0 bg-black/20"></div>

                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="bg-sky-400/90 text-black px-8 py-3 rounded-lg font-bold text-lg shadow-lg backdrop-blur-sm transition group-hover:bg-sky-400">
                        Tambah Ruangan
                    </span>
                </div>
            </button>
        </div>

    </main>

    <!-- Modal Tambah Ruangan -->
    <div id="addModal" class="fixed inset-0 bg-white/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-slate-800">Tambah Ruangan Baru</h2>
                <button class="btn-close-add-modal text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form method="POST" action="index.php?page=admin&action=tambah_ruangan" enctype="multipart/form-data" class="p-6">
                <div class="space-y-4">
                    <!-- Nama Ruangan -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nama Ruangan *</label>
                        <input type="text" name="nama_ruangan" required 
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                            placeholder="Contoh: Ruang Layar">
                    </div>

                    <!-- Jenis Ruangan -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Jenis Ruangan *</label>
                        <select name="jenis_ruangan" required 
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                            <option value="">Pilih Jenis Ruangan</option>
                            <option value="Ruang Umum">Ruang Umum (Untuk booking user biasa)</option>
                            <option value="Ruang Rapat">Ruang Rapat (Untuk booking eksternal Super Admin)</option>
                        </select>
                        <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-xs text-blue-800">
                                <strong>📌 Penting:</strong><br>
                                • <strong>Ruang Umum:</strong> User biasa (Mahasiswa/Dosen/Tendik) bisa booking via dashboard<br>
                                • <strong>Ruang Rapat:</strong> Hanya Super Admin yang bisa booking untuk instansi eksternal (perlu surat resmi)
                            </p>
                        </div>
                    </div>

                    <!-- Kapasitas -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Kapasitas Minimal *</label>
                            <input type="number" name="minimal_kapasitas" min="1" required 
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                                placeholder="2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Kapasitas Maksimal *</label>
                            <input type="number" name="maksimal_kapasitas" min="1" required 
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                                placeholder="12">
                        </div>
                    </div>

                    <!-- Status Ruangan - Hanya untuk kondisi khusus -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Status Ruangan *</label>
                        <select name="status_ruangan" required 
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                            <option value="Tersedia">Tersedia (Default - Auto-update berdasarkan booking)</option>
                            <option value="Dalam Perbaikan">Dalam Perbaikan (Manual - Ruangan tidak bisa dibooking)</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">
                            Status "Tersedia/Tidak Tersedia" akan otomatis berubah sesuai jadwal booking aktif.
                        </p>
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Deskripsi</label>
                        <textarea name="deskripsi" rows="3" 
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                            placeholder="Contoh: Audio Visual"></textarea>
                    </div>

                    <!-- Tata Tertib -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tata Tertib</label>
                        <textarea name="tata_tertib" rows="3" 
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                            placeholder="Contoh: 1. Jangan membuang sampah di ruangan tersebut."></textarea>
                    </div>

                    <!-- Foto Ruangan -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Foto Ruangan</label>
                        <input type="file" name="foto_ruangan" accept="image/jpeg,image/png,image/webp,image/jpg" 
                            data-preview-target="addPreview" class="input-file-add"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <p class="text-xs text-slate-500 mt-1">Format: JPEG, PNG, WEBP (Max 5MB)</p>
                        <div id="addPreview" class="mt-3 hidden">
                            <img src="" alt="Preview" class="w-full h-48 object-cover rounded-lg">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-3 justify-end">
                    <button type="button" class="btn-close-add-modal px-6 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium">
                        Batal
                    </button>
                    <button type="submit" 
                        class="px-6 py-2 bg-sky-500 hover:bg-sky-600 text-white rounded-lg font-medium">
                        Tambah Ruangan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Ruangan -->
    <div id="editModal" class="fixed inset-0 bg-white/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-slate-800">Edit Ruangan</h2>
                <button class="btn-close-edit-modal text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form method="POST" action="index.php?page=admin&action=update_ruangan" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="id_ruangan" id="edit_id_ruangan">
                
                <div class="space-y-4">
                    <!-- Nama Ruangan -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nama Ruangan *</label>
                        <input type="text" name="nama_ruangan" id="edit_nama_ruangan" required 
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    </div>

                    <!-- Jenis Ruangan -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Jenis Ruangan *</label>
                        <select name="jenis_ruangan" id="edit_jenis_ruangan" required 
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                            <option value="Ruang Umum">Ruang Umum (Untuk booking user biasa)</option>
                            <option value="Ruang Rapat">Ruang Rapat (Untuk booking eksternal Super Admin)</option>
                        </select>
                        <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-xs text-blue-800">
                                <strong>📌 Penting:</strong><br>
                                • <strong>Ruang Umum:</strong> User biasa (Mahasiswa/Dosen/Tendik) bisa booking via dashboard<br>
                                • <strong>Ruang Rapat:</strong> Hanya Super Admin yang bisa booking untuk instansi eksternal (perlu surat resmi)
                            </p>
                        </div>
                    </div>

                    <!-- Kapasitas -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Kapasitas Minimal *</label>
                            <input type="number" name="minimal_kapasitas" id="edit_minimal_kapasitas" min="1" required 
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Kapasitas Maksimal *</label>
                            <input type="number" name="maksimal_kapasitas" id="edit_maksimal_kapasitas" min="1" required 
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Status Ruangan - Hanya untuk kondisi khusus -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Status Ruangan *</label>
                        <select name="status_ruangan" id="edit_status_ruangan" required 
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                            <option value="Tersedia">Tersedia (Default - Auto-update berdasarkan booking)</option>
                            <option value="Tidak Tersedia">Tidak Tersedia (Sedang digunakan - Auto-update)</option>
                            <option value="Dalam Perbaikan">Dalam Perbaikan (Manual - Ruangan tidak bisa dibooking)</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">
                            Status "Tersedia/Tidak Tersedia" akan otomatis berubah sesuai jadwal booking aktif.
                        </p>
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" rows="3" 
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent"></textarea>
                    </div>

                    <!-- Tata Tertib -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tata Tertib</label>
                        <textarea name="tata_tertib" id="edit_tata_tertib" rows="3" 
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent"></textarea>
                    </div>

                    <!-- Foto Ruangan Current -->
                    <div id="edit_current_photo_container" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Foto Saat Ini</label>
                        <img id="edit_current_photo" src="" alt="Foto Ruangan" class="w-full h-48 object-cover rounded-lg mb-2">
                    </div>

                    <!-- Foto Ruangan -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Ganti Foto Ruangan</label>
                        <input type="file" name="foto_ruangan" accept="image/jpeg,image/png,image/webp,image/jpg" 
                            data-preview-target="editPreview" class="input-file-edit"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <p class="text-xs text-slate-500 mt-1">Format: JPEG, PNG, WEBP (Max 5MB). Kosongkan jika tidak ingin mengganti foto.</p>
                        <div id="editPreview" class="mt-3 hidden">
                            <img src="" alt="Preview" class="w-full h-48 object-cover rounded-lg">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-3 justify-end">
                    <button type="button" class="btn-close-edit-modal px-6 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium">
                        Batal
                    </button>
                    <button type="submit" 
                        class="px-6 py-2 bg-sky-500 hover:bg-sky-600 text-white rounded-lg font-medium">
                        Update Ruangan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div id="deleteModal" class="fixed inset-0 bg-white/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-center text-slate-800 mb-2">Hapus Ruangan?</h3>
                <p class="text-center text-slate-600 mb-6">
                    Apakah Anda yakin ingin menghapus ruangan <span id="delete_room_name" class="font-semibold"></span>? 
                    Tindakan ini tidak dapat dibatalkan.
                </p>

                <form method="POST" action="index.php?page=admin&action=delete_ruangan">
                    <input type="hidden" name="id_ruangan" id="delete_id_ruangan">
                    
                    <div class="flex gap-3">
                        <button type="button" class="btn-close-delete-modal flex-1 px-6 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium">
                            Batal
                        </button>
                        <button type="submit" 
                            class="flex-1 px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
                            Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Data will be injected by JS from data attributes -->
    <div id="rooms-data" data-rooms='<?= json_encode($rooms) ?>' data-base-path="<?= $basePath ?>" style="display:none;"></div>
    <script src="<?= $asset('assets/js/kelola-ruangan.js') ?>" defer></script>

</body>

</html>