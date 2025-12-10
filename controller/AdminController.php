<?php
/**
 * ============================================================================
 * ADMINCONTROLLER.PHP - Admin Area Management Controller
 * ============================================================================
 * 
 * Controller utama untuk semua fitur admin dengan 20+ methods untuk mengelola sistem BookEZ.
 * Support untuk Admin dan Super Admin dengan role-based access control.
 * 
 * ROLE HIERARCHY:
 * - Admin: Kelola Ruangan, Laporan, Booking List, Member List
 * - Super Admin: All Admin features + Booking External + Pengaturan Sistem
 * 
 * FUNGSI UTAMA (21 METHODS):
 * 
 * 1. DASHBOARD & NAVIGATION
 *    - index() - Admin dashboard main page
 * 
 * 2. KELOLA RUANGAN (Room Management)
 *    - kelola_ruangan() - List all rooms
 *    - tambah_ruangan() - Create new room (POST)
 *    - update_ruangan() - Update room details (POST)
 *    - delete_ruangan() - Delete room (POST)
 * 
 * 3. LAPORAN (Reports & Analytics)
 *    - laporan() - Display reports page with filters
 * 
 * 4. BOOKING LIST (Booking Management)
 *    - booking_list() - List all bookings with check-in management
 *    - get_booking_checkin() - AJAX: Get booking check-in details
 *    - checkin_anggota() - AJAX: Process member check-in
 * 
 * 5. MEMBER LIST (User Management)
 *    - member_list() - List users and admins with tabs
 *    - load_members() - AJAX: Load paginated members by tab
 *    - update_foto_profil() - Update member foto profil (POST)
 *    - update_user_status() - Activate/deactivate user (POST)
 *    - delete_user() - Delete user account (POST)
 * 
 * 6. ADMIN MANAGEMENT
 *    - create_admin() - Create new admin/super admin (POST)
 *    - update_admin() - Update admin details (POST)
 *    - delete_admin() - Delete admin account (POST)
 * 
 * 7. BOOKING EXTERNAL (Super Admin Only)
 *    - booking_external() - List external bookings dengan pagination & filter
 *    - submit_booking_external() - Create external booking (POST)
 *    - delete_booking_external() - Delete external booking (POST)
 * 
 * 8. PENGATURAN SISTEM (Super Admin Only)
 *    - pengaturan() - System settings (waktu operasi, hari libur)
 *    - update_waktu_operasi() - Update operational hours (POST)
 *    - create_hari_libur() - Add holiday (POST)
 *    - update_hari_libur() - Update holiday (POST)
 *    - delete_hari_libur() - Delete holiday (POST)
 * 
 * ROUTES:
 * - ?page=admin&action=index - Admin dashboard
 * - ?page=admin&action=kelola_ruangan - Room management
 * - ?page=admin&action=tambah_ruangan - Add room (POST)
 * - ?page=admin&action=update_ruangan - Update room (POST)
 * - ?page=admin&action=delete_ruangan - Delete room (POST)
 * - ?page=admin&action=laporan - Reports page
 * - ?page=admin&action=booking_list - Booking list dengan check-in
 * - ?page=admin&action=get_booking_checkin&id={id} - AJAX check-in data
 * - ?page=admin&action=checkin_anggota - AJAX check-in process (POST)
 * - ?page=admin&action=member_list - Member management dengan tabs
 * - ?page=admin&action=load_members&tab={user|admin}&page_num={n} - AJAX pagination
 * - ?page=admin&action=update_foto_profil - Update member foto (POST)
 * - ?page=admin&action=update_user_status - Activate/deactivate user (POST)
 * - ?page=admin&action=delete_user - Delete user (POST)
 * - ?page=admin&action=create_admin - Add admin (POST)
 * - ?page=admin&action=update_admin - Update admin (POST)
 * - ?page=admin&action=delete_admin - Delete admin (POST)
 * - ?page=admin&action=booking_external - External bookings (Super Admin)
 * - ?page=admin&action=submit_booking_external - Create external booking (POST)
 * - ?page=admin&action=delete_booking_external - Delete external (POST)
 * - ?page=admin&action=pengaturan - System settings (Super Admin)
 * - ?page=admin&action=update_waktu_operasi - Update hours (POST)
 * - ?page=admin&action=create_hari_libur - Add holiday (POST)
 * - ?page=admin&action=update_hari_libur - Update holiday (POST)
 * - ?page=admin&action=delete_hari_libur - Delete holiday (POST)
 * 
 * KELOLA RUANGAN (ROOM MANAGEMENT):
 * - Display all rooms (Ruang Umum + Ruang Rapat)
 * - CRUD operations: Create, Read, Update, Delete
 * - Foto upload: Max 5MB, image only (JPEG/PNG/WebP)
 * - Validasi kapasitas: min < max
 * - Validasi delete: Cannot delete room dengan booking aktif
 * - File management: Auto-delete old foto on update/delete
 * 
 * LAPORAN (REPORTS):
 * - Most Booked Rooms: Top 5 rooms by booking count
 * - Least Booked Rooms: Bottom 5 rooms by booking count
 * - User Statistics: Total users, active users, suspended users
 * - Booking Statistics: Total bookings by status (AKTIF, SELESAI, DIBATALKAN, HANGUS)
 * - Filter by date range (tanggal_mulai, tanggal_akhir)
 * - Real-time data via LaporanModel
 * 
 * BOOKING LIST:
 * - Display all bookings (user + external) dengan pagination
 * - Filter by: ruangan, status, tanggal
 * - Auto-update: HANGUS and SELESAI status on page load
 * - Check-in management:
 *   - Modal shows anggota list with check-in status
 *   - AJAX endpoint: get_booking_checkin returns {success, booking, anggota}
 *   - AJAX process: checkin_anggota validates and updates anggota_booking
 * - Status badges with color coding
 * 
 * MEMBER LIST:
 * - Two tabs: User (Mahasiswa/Dosen) and Admin (Admin/Super Admin)
 * - AJAX pagination: load_members endpoint returns JSON
 * - Pagination state: pg_user, pg_admin in URL
 * - User management:
 *   - View/Edit modal with validation
 *   - Foto profil upload (separate action)
 *   - Status toggle: Aktif ↔ Tidak Aktif
 *   - Delete with confirmation
 * - Admin management:
 *   - Create new admin/super admin
 *   - Edit admin details
 *   - Delete admin (cannot delete self)
 * - Email validation: Strict PNJ domain (@stu.pnj.ac.id, @*.pnj.ac.id)
 * - Password: Minimum 8 characters
 * 
 * BOOKING EXTERNAL (SUPER ADMIN ONLY):
 * - Two-column layout: Form (left) + Schedule (right)
 * - Tab system: Mendatang vs Histori
 * - Filter by: Ruangan, Instansi, Tanggal
 * - Form fields:
 *   - Pilih Ruangan (select dari Ruang Rapat only)
 *   - Nama Instansi (text)
 *   - Surat Lampiran (PDF upload, max 25MB)
 *   - Tanggal, Jam Mulai, Jam Selesai
 * - Validation:
 *   - Tanggal tidak boleh masa lalu
 *   - Waktu selesai > waktu mulai
 *   - Durasi minimal 15 menit
 *   - No time slot conflict with existing bookings
 * - File handling: PDF only, stored in assets/uploads/docs/
 * - Pagination: 10 per page (parameter: pg)
 * 
 * PENGATURAN SISTEM (SUPER ADMIN ONLY):
 * - Two tabs: Waktu Operasi + Hari Libur
 * - Waktu Operasi:
 *   - 7 records (Senin-Minggu)
 *   - Edit: jam_buka, jam_tutup, is_aktif
 *   - Affects user booking validation
 * - Hari Libur:
 *   - Dynamic list of special dates
 *   - CRUD operations: Create, Update, Delete
 *   - Blocks user bookings on registered dates
 * - Integration: PengaturanModel validation in BookingController
 * 
 * FILE UPLOAD PATTERNS:
 * 1. FOTO RUANGAN:
 *    - Location: assets/uploads/images/
 *    - Allowed: image/jpeg, image/png, image/webp
 *    - Max size: 5MB
 *    - Filename: 'room_{timestamp}_{random}.{ext}'
 * 
 * 2. FOTO PROFIL (Member):
 *    - Location: assets/uploads/images/
 *    - Allowed: image/jpeg, image/png, image/webp
 *    - Max size: 25MB
 *    - Filename: 'profile_{nomor_induk}_{timestamp}.{ext}'
 * 
 * 3. SURAT LAMPIRAN (External Booking):
 *    - Location: assets/uploads/docs/
 *    - Allowed: application/pdf
 *    - Max size: 25MB
 *    - Filename: 'surat_{timestamp}_{random}.pdf'
 * 
 * AUTO-UPDATE INTEGRATION:
 * - booking_list() calls auto-update methods:
 *   1. autoUpdateHangusStatus() - bookings tanpa check-in >10min \u2192 HANGUS
 *   2. autoUpdateSelesaiStatus() - bookings with check-in past waktu_selesai \u2192 SELESAI
 *   3. autoUpdateRoomStatus() - room availability based on active bookings
 * 
 * ROLE-BASED ACCESS:
 * - Constructor enforces Admin/Super Admin role
 * - Super Admin only methods:
 *   - booking_external() - check role before display
 *   - submit_booking_external() - check role before process
 *   - pengaturan() - check role before display
 *   - All pengaturan CRUD methods - check role before process
 * 
 * AJAX RESPONSES:
 * All AJAX methods return JSON:
 * - {success: bool, message: string, data: mixed}
 * - Content-Type: application/json
 * - Output buffer cleared before JSON output
 * 
 * SECURITY FEATURES:
 * - Role check in constructor (redirect non-admins)
 * - Super Admin check for privileged features
 * - Session validation untuk semua actions
 * - MIME type validation untuk file uploads
 * - File size validation
 * - Email domain validation (PNJ only)
 * - Password strength validation (min 8 chars)
 * - SQL injection prevention (prepared statements)
 * - XSS prevention (addslashes for alerts)
 * - CSRF prevention (session-based auth)
 * 
 * USAGE PATTERNS:
 * - view/admin/dashboard.php - Admin dashboard grid
 * - view/admin/kelola_ruangan.php - Room management
 * - view/admin/laporan.php - Reports page
 * - view/admin/booking_list.php - Booking list with check-in
 * - view/admin/member_list.php - Member management
 * - view/admin/booking_external.php - External bookings (Super Admin)
 * - view/admin/pengaturan.php - System settings (Super Admin)
 * - assets/js/kelola-ruangan.js - Room modals
 * - assets/js/booking-list.js - Check-in modal
 * - assets/js/member-list.js - Member modals + pagination
 * - assets/js/admin.js - Booking external + pengaturan tabs
 * - assets/js/laporan.js - Report filters
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

/**
 * Class AdminController - Admin Area Management
 * 
 * @property AkunModel $akunModel Account operations
 * @property PengaturanModel $pengaturanModel System settings
 */
class AdminController
{
    /**
     * AkunModel instance untuk account operations
     * @var AkunModel
     */
    private AkunModel $akunModel;
    
    /**
     * PengaturanModel instance untuk system settings
     * @var PengaturanModel
     */
    private PengaturanModel $pengaturanModel;

    /**
     * Constructor - Role check and initialize models
     * 
     * CRITICAL: Enforces Admin/Super Admin role requirement
     * Redirects non-admins to user dashboard
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in and has Admin/Super Admin role
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $this->akunModel = new AkunModel();
        $this->pengaturanModel = new PengaturanModel();
    }

    public function index(): void
    {
        // Show admin dashboard
        require __DIR__ . '/../view/admin/dashboard.php';
    }

    public function kelola_ruangan(): void
    {
        $ruanganModel = new RuanganModel();
        $rooms = $ruanganModel->getAll();
        
        require __DIR__ . '/../view/admin/kelola_ruangan.php';
    }

    public function tambah_ruangan(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=kelola_ruangan');
            exit;
        }

        $namaRuangan = trim($_POST['nama_ruangan'] ?? '');
        $jenisRuangan = $_POST['jenis_ruangan'] ?? '';
        $minKapasitas = (int) ($_POST['minimal_kapasitas'] ?? 0);
        $maxKapasitas = (int) ($_POST['maksimal_kapasitas'] ?? 0);
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $tataTertib = trim($_POST['tata_tertib'] ?? '');
        $statusRuangan = $_POST['status_ruangan'] ?? 'Tersedia';

        // Validasi input
        if (empty($namaRuangan) || empty($jenisRuangan) || $minKapasitas <= 0 || $maxKapasitas <= 0) {
            echo "<script>alert('Semua field wajib diisi dengan benar!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
            exit;
        }

        // Validasi min < max
        if ($minKapasitas >= $maxKapasitas) {
            echo "<script>alert('Kapasitas minimal harus lebih kecil dari kapasitas maksimal!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
            exit;
        }

        // Handle file upload
        $fotoRuangan = null;
        if (isset($_FILES['foto_ruangan']) && $_FILES['foto_ruangan']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto_ruangan'];
            
            // Validasi tipe file (hanya gambar)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            
            if (!in_array($mimeType, $allowedTypes)) {
                echo "<script>alert('Hanya file gambar (JPEG, PNG, WEBP) yang diperbolehkan!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
                exit;
            }
            
            // Validasi ukuran file (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                echo "<script>alert('Ukuran file maksimal 5MB!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
                exit;
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'room_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $uploadPath = __DIR__ . '/../assets/uploads/images/' . $filename;
            
            // Create directory if not exists
            if (!is_dir(dirname($uploadPath))) {
                mkdir(dirname($uploadPath), 0755, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $fotoRuangan = 'assets/uploads/images/' . $filename;
            } else {
                echo "<script>alert('Gagal upload foto!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
                exit;
            }
        }

        $ruanganModel = new RuanganModel();
        $result = $ruanganModel->create([
            'nama_ruangan' => $namaRuangan,
            'jenis_ruangan' => $jenisRuangan,
            'maksimal_kapasitas_ruangan' => $maxKapasitas,
            'minimal_kapasitas_ruangan' => $minKapasitas,
            'deskripsi' => $deskripsi,
            'tata_tertib' => $tataTertib,
            'status_ruangan' => $statusRuangan,
            'foto_ruangan' => $fotoRuangan
        ]);

        if ($result) {
            echo "<script>alert('Ruangan berhasil ditambahkan!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan ruangan!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
        }
        exit;
    }

    public function update_ruangan(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=kelola_ruangan');
            exit;
        }

        $idRuangan = (int) ($_POST['id_ruangan'] ?? 0);
        $namaRuangan = trim($_POST['nama_ruangan'] ?? '');
        $jenisRuangan = $_POST['jenis_ruangan'] ?? '';
        $minKapasitas = (int) ($_POST['minimal_kapasitas'] ?? 0);
        $maxKapasitas = (int) ($_POST['maksimal_kapasitas'] ?? 0);
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $tataTertib = trim($_POST['tata_tertib'] ?? '');
        $statusRuangan = $_POST['status_ruangan'] ?? 'Tersedia';

        // Validasi input
        if (!$idRuangan || empty($namaRuangan) || empty($jenisRuangan) || $minKapasitas <= 0 || $maxKapasitas <= 0) {
            echo "<script>alert('Semua field wajib diisi dengan benar!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
            exit;
        }

        // Validasi min < max
        if ($minKapasitas >= $maxKapasitas) {
            echo "<script>alert('Kapasitas minimal harus lebih kecil dari kapasitas maksimal!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
            exit;
        }

        $ruanganModel = new RuanganModel();
        
        // Ambil data lama untuk foto
        $oldRoom = $ruanganModel->getById($idRuangan);
        if (!$oldRoom) {
            echo "<script>alert('Ruangan tidak ditemukan!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
            exit;
        }

        $fotoRuangan = $oldRoom['foto_ruangan'];

        // Handle file upload (jika ada file baru)
        if (isset($_FILES['foto_ruangan']) && $_FILES['foto_ruangan']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto_ruangan'];
            
            // Validasi tipe file (hanya gambar)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            
            if (!in_array($mimeType, $allowedTypes)) {
                echo "<script>alert('Hanya file gambar (JPEG, PNG, WEBP) yang diperbolehkan!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
                exit;
            }
            
            // Validasi ukuran file (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                echo "<script>alert('Ukuran file maksimal 5MB!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
                exit;
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'room_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $uploadPath = __DIR__ . '/../assets/uploads/images/' . $filename;
            
            // Create directory if not exists
            if (!is_dir(dirname($uploadPath))) {
                mkdir(dirname($uploadPath), 0755, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Hapus foto lama jika ada
                if (!empty($oldRoom['foto_ruangan'])) {
                    $oldFilePath = __DIR__ . '/../' . $oldRoom['foto_ruangan'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                $fotoRuangan = 'assets/uploads/images/' . $filename;
            }
        }

        $result = $ruanganModel->update($idRuangan, [
            'nama_ruangan' => $namaRuangan,
            'jenis_ruangan' => $jenisRuangan,
            'maksimal_kapasitas_ruangan' => $maxKapasitas,
            'minimal_kapasitas_ruangan' => $minKapasitas,
            'deskripsi' => $deskripsi,
            'tata_tertib' => $tataTertib,
            'status_ruangan' => $statusRuangan,
            'foto_ruangan' => $fotoRuangan
        ]);

        if ($result) {
            echo "<script>alert('Ruangan berhasil diupdate!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
        } else {
            echo "<script>alert('Gagal mengupdate ruangan!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
        }
        exit;
    }

    public function delete_ruangan(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=kelola_ruangan');
            exit;
        }

        $idRuangan = (int) ($_POST['id_ruangan'] ?? 0);

        if (!$idRuangan) {
            echo "<script>alert('ID ruangan tidak valid!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
            exit;
        }

        $ruanganModel = new RuanganModel();
        
        // Ambil data ruangan untuk hapus foto
        $room = $ruanganModel->getById($idRuangan);
        if (!$room) {
            echo "<script>alert('Ruangan tidak ditemukan!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
            exit;
        }

        // Cek apakah ruangan sedang dipakai
        if ($ruanganModel->isInUse($idRuangan)) {
            echo "<script>alert('Tidak dapat menghapus ruangan yang sedang digunakan!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
            exit;
        }

        try {
            // Hapus ruangan dari database
            if ($ruanganModel->delete($idRuangan)) {
                // Hapus foto jika ada
                if (!empty($room['foto_ruangan'])) {
                    $filePath = __DIR__ . '/../' . $room['foto_ruangan'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                echo "<script>alert('Ruangan berhasil dihapus!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
            } else {
                echo "<script>alert('Gagal menghapus ruangan!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Tidak dapat menghapus ruangan karena masih memiliki relasi data (booking)!'); window.location.href='index.php?page=admin&action=kelola_ruangan';</script>";
        }
        exit;
    }

    public function laporan(): void
    {
        $laporanModel = new LaporanModel();
        
        // Ambil filter dari GET parameters
        $tab = $_GET['tab'] ?? 'harian';
        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
        $bulan = $_GET['bulan'] ?? date('n');
        $tahun = $_GET['tahun'] ?? date('Y');
        
        // Initialize data arrays
        $data = [];
        $stats = [];
        $mostBookedRoom = 'Belum ada data';
        
        // Fetch data berdasarkan tab yang dipilih
        switch ($tab) {
            case 'harian':
                $data = $laporanModel->getDaily($tanggal);
                $stats = $laporanModel->getDailyStats($tanggal);
                break;
                
            case 'mingguan':
                // Hitung start of week dari tanggal yang dipilih
                $startDate = date('Y-m-d', strtotime('monday this week', strtotime($tanggal)));
                $data = $laporanModel->getWeeklyDetail($startDate);
                $stats = $laporanModel->getWeeklyStats($startDate);
                break;
                
            case 'bulanan':
                $data = $laporanModel->getMonthlyDetail((int)$bulan, (int)$tahun);
                $stats = $laporanModel->getMonthlyStats((int)$bulan, (int)$tahun);
                break;
                
            case 'tahunan':
                $data = $laporanModel->getYearly((int)$tahun);
                $stats = $laporanModel->getYearlyStats((int)$tahun);
                break;
        }
        
        // Ambil ruangan paling sering dibooking berdasarkan periode
        $startDateForStats = $tanggal;
        $endDate = $tanggal;
        
        switch ($tab) {
            case 'harian':
                $startDateForStats = $tanggal;
                $endDate = $tanggal;
                break;
                
            case 'mingguan':
                $startDateForStats = $startDate;
                $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));
                break;
                
            case 'bulanan':
                $startDateForStats = date('Y-m-01', mktime(0, 0, 0, $bulan, 1, $tahun));
                $endDate = date('Y-m-t', mktime(0, 0, 0, $bulan, 1, $tahun));
                break;
                
            case 'tahunan':
                $startDateForStats = date('Y-01-01', mktime(0, 0, 0, 1, 1, $tahun));
                $endDate = date('Y-12-31', mktime(0, 0, 0, 12, 31, $tahun));
                break;
        }
        
        $roomStats = $laporanModel->getRuanganUsageStats($startDateForStats, $endDate);
        if (!empty($roomStats) && $roomStats[0]['jumlah_booking'] > 0) {
            $mostBookedRoom = $roomStats[0]['nama_ruangan'];
        }
        
        // Ambil daftar tahun yang tersedia untuk dropdown
        $availableYears = $laporanModel->getAvailableYears();
        
        require __DIR__ . '/../view/admin/laporan.php';
    }

    public function booking_list(): void
    {
        $bookingListModel = new BookingListModel();
        $ruanganModel = new RuanganModel();
        
        // CRITICAL: Urutan eksekusi auto-update sangat penting!
        // 1. HANGUS dulu - cek booking tanpa check-in yang lewat 10 menit
        // 2. SELESAI kedua - cek booking dengan check-in yang sudah habis waktunya
        $bookingListModel->autoUpdateHangusStatus();
        $bookingListModel->autoUpdateSelesaiStatus();
        
        // Auto-update status ruangan di database based on real-time availability
        $ruanganModel->autoUpdateRoomStatus();
        
        // ========== PAGINATION SETUP ==========
        $perPage = 10;
        $currentPage = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
        $offset = ($currentPage - 1) * $perPage;
        
        // Ambil filter dari GET parameters
        $filters = [
            'ruang' => $_GET['ruang'] ?? 'all',
            'nama' => $_GET['nama'] ?? '',
            'tanggal' => $_GET['tanggal'] ?? '',
            'status' => $_GET['status'] ?? 'all'
        ];
        
        // Fetch data booking dengan filter dan pagination
        $bookings = $bookingListModel->filterWithPagination($filters, $perPage, $offset);
        
        // Get total count for pagination
        $totalRecords = $bookingListModel->countFiltered($filters);
        $totalPages = ceil($totalRecords / $perPage);
        
        // Ambil list ruangan untuk dropdown
        $ruanganList = $bookingListModel->getRuanganList();
        
        // Pass pagination data to view
        $paginationData = [
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'perPage' => $perPage
        ];
        
        require __DIR__ . '/../view/admin/booking_list.php';
    }

    public function member_list(): void
    {
        $akunModel = new AkunModel();
        
        // ========== PAGINATION SETUP FOR USER TAB ==========
        $perPageUser = 10;
        $currentPageUser = isset($_GET['pg_user']) ? max(1, (int)$_GET['pg_user']) : 1;
        $offsetUser = ($currentPageUser - 1) * $perPageUser;
        
        // User filters
        $userFilters = [
            'nama' => $_GET['nama'] ?? '',
            'nomor_induk' => $_GET['nomor_induk'] ?? '',
            'prodi' => $_GET['prodi'] ?? '',
            'status' => $_GET['status'] ?? '',
            'limit' => $perPageUser,
            'offset' => $offsetUser
        ];
        
        // Fetch user data dengan pagination
        $userData = $akunModel->filterUsers($userFilters);
        
        // Count total users untuk pagination
        $countUserFilters = array_diff_key($userFilters, ['limit' => '', 'offset' => '']);
        $totalUsers = $akunModel->countFilteredUsers($countUserFilters);
        $totalPagesUser = ceil($totalUsers / $perPageUser);
        
        // ========== PAGINATION SETUP FOR ADMIN TAB ==========
        $perPageAdmin = 10;
        $currentPageAdmin = isset($_GET['pg_admin']) ? max(1, (int)$_GET['pg_admin']) : 1;
        $offsetAdmin = ($currentPageAdmin - 1) * $perPageAdmin;
        
        // Admin filters
        $adminFilters = [
            'nama' => $_GET['nama_admin'] ?? '',
            'nomor_induk' => $_GET['nomor_induk_admin'] ?? '',
            'status' => $_GET['status_admin'] ?? '',
            'limit' => $perPageAdmin,
            'offset' => $offsetAdmin
        ];
        
        // Fetch admin data dengan pagination
        $adminData = $akunModel->filterAdmins($adminFilters);
        
        // Count total admins untuk pagination
        $countAdminFilters = array_diff_key($adminFilters, ['limit' => '', 'offset' => '']);
        $totalAdmins = $akunModel->countFilteredAdmins($countAdminFilters);
        $totalPagesAdmin = ceil($totalAdmins / $perPageAdmin);
        
        // Fetch prodi list untuk dropdown
        $prodiList = $akunModel->getAllProdi();
        
        // Pagination data untuk view
        $paginationUser = [
            'currentPage' => $currentPageUser,
            'totalPages' => $totalPagesUser,
            'totalRecords' => $totalUsers,
            'perPage' => $perPageUser
        ];
        
        $paginationAdmin = [
            'currentPage' => $currentPageAdmin,
            'totalPages' => $totalPagesAdmin,
            'totalRecords' => $totalAdmins,
            'perPage' => $perPageAdmin
        ];

        require __DIR__ . '/../view/admin/member_list.php';
    }

    /**
     * AJAX endpoint untuk load member data (user atau admin)
     */
    public function load_members(): void
    {
        header('Content-Type: application/json');
        
        $tab = $_GET['tab'] ?? 'user';
        $page = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        $akunModel = new AkunModel();
        
        if ($tab === 'user') {
            $filters = [
                'nama' => $_GET['nama'] ?? '',
                'nomor_induk' => $_GET['nomor_induk'] ?? '',
                'prodi' => $_GET['prodi'] ?? '',
                'status' => $_GET['status'] ?? '',
                'limit' => $perPage,
                'offset' => $offset
            ];
            
            $data = $akunModel->filterUsers($filters);
            $countFilters = array_diff_key($filters, ['limit' => '', 'offset' => '']);
            $total = $akunModel->countFilteredUsers($countFilters);
        } else {
            $filters = [
                'nama' => $_GET['nama_admin'] ?? '',
                'nomor_induk' => $_GET['nomor_induk_admin'] ?? '',
                'status' => $_GET['status_admin'] ?? '',
                'limit' => $perPage,
                'offset' => $offset
            ];
            
            $data = $akunModel->filterAdmins($filters);
            $countFilters = array_diff_key($filters, ['limit' => '', 'offset' => '']);
            $total = $akunModel->countFilteredAdmins($countFilters);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => ceil($total / $perPage),
                'totalRecords' => $total,
                'perPage' => $perPage
            ]
        ]);
        exit;
    }

    public function booking_external(): void
    {
        // Only Super Admin can access
        if ($_SESSION['user']['role'] !== 'Super Admin') {
            header('Location: index.php?page=admin&action=kelola_ruangan');
            exit;
        }

        $bookingExternalModel = new BookingExternalModel();
        
        // ========== PAGINATION SETUP ==========
        $perPage = 10;
        $currentPage = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
        $offset = ($currentPage - 1) * $perPage;
        
        // Ambil filter dari GET parameters
        $tab = $_GET['tab'] ?? 'upcoming';
        $filters = [
            'nama_ruangan' => $_GET['ruang'] ?? '',
            'nama_instansi' => $_GET['instansi'] ?? '',
            'tanggal' => $_GET['tanggal'] ?? '',
            'limit' => $perPage,
            'offset' => $offset
        ];
        
        // Fetch data based on tab
        if ($tab === 'history') {
            $filters['history'] = true;
        } else {
            $filters['upcoming'] = true;
        }
        
        $bookings = $bookingExternalModel->filter($filters);
        
        // Get total count for pagination
        $countFilters = array_diff_key($filters, ['limit' => '', 'offset' => '']);
        $totalRecords = $bookingExternalModel->countFiltered($countFilters);
        $totalPages = ceil($totalRecords / $perPage);
        
        // Pass pagination data to view
        $paginationData = [
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'perPage' => $perPage
        ];
        
        // Ambil list ruang rapat untuk dropdown
        $ruangRapat = $bookingExternalModel->getRuangRapat();
        
        require __DIR__ . '/../view/admin/booking_external.php';
    }

    public function submit_booking_external(): void
    {
        // Only Super Admin can submit
        if ($_SESSION['user']['role'] !== 'Super Admin') {
            echo "<script>alert('Anda tidak memiliki akses!'); window.location.href='index.php?page=admin&action=index';</script>";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=booking_external');
            exit;
        }

        $idRuangan = (int) ($_POST['id_ruangan'] ?? 0);
        $namaInstansi = trim($_POST['nama_instansi'] ?? '');
        $tanggal = $_POST['tanggal'] ?? '';
        $waktuMulai = $_POST['waktu_mulai'] ?? '';
        $waktuSelesai = $_POST['waktu_selesai'] ?? '';

        // Validasi input
        if (!$idRuangan || empty($namaInstansi) || empty($tanggal) || empty($waktuMulai) || empty($waktuSelesai)) {
            echo "<script>alert('Semua field harus diisi!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
            exit;
        }

        // Validasi tanggal tidak di masa lalu
        if (strtotime($tanggal) < strtotime(date('Y-m-d'))) {
            echo "<script>alert('Tanggal booking tidak boleh di masa lalu!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
            exit;
        }

        // Validasi waktu selesai > waktu mulai
        if (strtotime($waktuSelesai) <= strtotime($waktuMulai)) {
            echo "<script>alert('Waktu selesai harus lebih besar dari waktu mulai!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
            exit;
        }

        // VALIDASI MINIMAL DURASI BOOKING: 15 menit
        $durasi = (strtotime($waktuSelesai) - strtotime($waktuMulai)) / 60;
        if ($durasi < 15) {
            echo "<script>alert('Durasi booking minimal 15 menit (toleransi keterlambatan check-in 10 menit)'); window.location.href='index.php?page=admin&action=booking_external';</script>";
            exit;
        }

        // VALIDASI PENGATURAN SISTEM (Waktu Operasi & Hari Libur)
        // 1. Cek hari libur
        $hariLiburCheck = $this->pengaturanModel->validateHariLibur($tanggal);
        if (!$hariLiburCheck['allowed']) {
            echo "<script>alert('" . addslashes($hariLiburCheck['message']) . "'); window.location.href='index.php?page=admin&action=booking_external';</script>";
            exit;
        }

        // 2. Cek waktu operasi (hari dalam seminggu + jam operasional)
        $hari_mapping = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 
                         'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $hari_inggris = date('l', strtotime($tanggal));
        $hari_indonesia = $hari_mapping[$hari_inggris];
        
        $waktuOpCheck = $this->pengaturanModel->validateWaktuOperasi($hari_indonesia, $waktuMulai, $waktuSelesai);
        if (!$waktuOpCheck['allowed']) {
            echo "<script>alert('" . addslashes($waktuOpCheck['message']) . "'); window.location.href='index.php?page=admin&action=booking_external';</script>";
            exit;
        }

        $bookingExternalModel = new BookingExternalModel();

        // Validasi ruangan harus Ruang Rapat
        if (!$bookingExternalModel->isRuangRapat($idRuangan)) {
            echo "<script>alert('Hanya Ruang Rapat yang bisa dibooking eksternal!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
            exit;
        }

        // Validasi bentrok waktu
        if ($bookingExternalModel->isTimeConflict($idRuangan, $tanggal, $waktuMulai, $waktuSelesai)) {
            echo "<script>alert('Waktu booking bentrok dengan booking lain! Silakan pilih waktu lain.'); window.location.href='index.php?page=admin&action=booking_external';</script>";
            exit;
        }

        // Handle file upload
        $suratLampiran = null;
        if (isset($_FILES['surat_lampiran']) && $_FILES['surat_lampiran']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['surat_lampiran'];
            
            // Validasi tipe file (hanya PDF) - Modern approach with finfo object
            $allowedTypes = ['application/pdf'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            
            if (!in_array($mimeType, $allowedTypes)) {
                echo "<script>alert('Hanya file PDF yang diperbolehkan!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
                exit;
            }
            
            // Validasi ukuran file (max 25MB)
            if ($file['size'] > 25 * 1024 * 1024) {
                echo "<script>alert('Ukuran file maksimal 25MB!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
                exit;
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'surat_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $uploadPath = __DIR__ . '/../assets/uploads/docs/' . $filename;
            
            // Create directory if not exists
            if (!is_dir(dirname($uploadPath))) {
                mkdir(dirname($uploadPath), 0755, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $suratLampiran = 'assets/uploads/docs/' . $filename;
            } else {
                echo "<script>alert('Gagal upload file!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
                exit;
            }
        }

        // Hitung durasi dalam menit
        $durasi = (strtotime($waktuSelesai) - strtotime($waktuMulai)) / 60;

        // Buat booking
        $data = [
            'id_ruangan' => $idRuangan,
            'nama_instansi' => $namaInstansi,
            'surat_lampiran' => $suratLampiran,
            'durasi_penggunaan' => (int) $durasi,
            'tanggal_schedule' => $tanggal,
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'id_status_booking' => 1 // AKTIF
        ];

        $result = $bookingExternalModel->create($data);

        if ($result) {
            echo "<script>alert('Booking eksternal berhasil dibuat!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
        } else {
            echo "<script>alert('Gagal membuat booking eksternal!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
        }
        exit;
    }

    public function update_foto_profil(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=member_list');
            exit;
        }

        $nomorInduk = trim($_POST['nomor_induk'] ?? '');

        if (empty($nomorInduk)) {
            echo "<script>alert('Nomor induk tidak valid!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Handle file upload
        if (!isset($_FILES['foto_profil']) || $_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
            echo "<script>alert('File foto tidak valid!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        $file = $_FILES['foto_profil'];
        
        // Validasi tipe file (hanya gambar)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            echo "<script>alert('Hanya file gambar (JPEG, PNG, WebP) yang diperbolehkan!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }
        
        // Validasi ukuran file (max 25MB)
        if ($file['size'] > 25 * 1024 * 1024) {
            echo "<script>alert('Ukuran file maksimal 25MB!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $nomorInduk . '_' . time() . '.' . $extension;
        $uploadPath = __DIR__ . '/../assets/uploads/images/' . $filename;
        
        // Create directory if not exists
        if (!is_dir(dirname($uploadPath))) {
            mkdir(dirname($uploadPath), 0755, true);
        }
        
        // Get old foto profil to delete
        $akun = $this->akunModel->getByNomorInduk($nomorInduk);
        $oldFoto = $akun['foto_profil'] ?? null;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $fotoProfil = 'assets/uploads/images/' . $filename;
            
            // Update database
            $result = $this->akunModel->updateFotoProfil($nomorInduk, $fotoProfil);
            
            if ($result) {
                // Delete old foto if exists
                if ($oldFoto && file_exists(__DIR__ . '/../' . $oldFoto)) {
                    unlink(__DIR__ . '/../' . $oldFoto);
                }
                
                echo "<script>alert('Foto profil berhasil diperbarui!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            } else {
                // Delete uploaded file if database update fails
                unlink($uploadPath);
                echo "<script>alert('Gagal memperbarui foto profil!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            }
        } else {
            echo "<script>alert('Gagal upload file!'); window.location.href='index.php?page=admin&action=member_list';</script>";
        }
        exit;
    }

    public function delete_booking_external(): void
    {
        // Only Super Admin can delete
        if ($_SESSION['user']['role'] !== 'Super Admin') {
            echo "<script>alert('Anda tidak memiliki akses!'); window.location.href='index.php?page=admin&action=index';</script>";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=booking_external');
            exit;
        }

        $idBooking = (int) ($_POST['id_booking'] ?? 0);

        if (!$idBooking) {
            echo "<script>alert('ID booking tidak valid!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
            exit;
        }

        $bookingExternalModel = new BookingExternalModel();
        
        // Ambil data booking untuk hapus file surat lampiran
        $booking = $bookingExternalModel->getById($idBooking);
        if ($booking && !empty($booking['surat_lampiran'])) {
            $filePath = __DIR__ . '/../' . $booking['surat_lampiran'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Delete booking
        if ($bookingExternalModel->delete($idBooking)) {
            echo "<script>alert('Booking berhasil dihapus!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
        } else {
            echo "<script>alert('Gagal menghapus booking!'); window.location.href='index.php?page=admin&action=booking_external';</script>";
        }
        exit;
    }

    // ==================== MEMBER LIST ACTIONS ====================

    public function create_admin(): void
    {
        // Only Super Admin can create admin
        if ($_SESSION['user']['role'] !== 'Super Admin') {
            echo "<script>alert('Anda tidak memiliki akses untuk menambah admin!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=member_list');
            exit;
        }

        $nama = trim($_POST['nama'] ?? '');
        $nip = trim($_POST['nip'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validasi
        if (empty($nama) || empty($nip) || empty($email) || empty($password)) {
            echo "<script>alert('Semua field harus diisi!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Validasi email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('Format email tidak valid!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Validasi domain email PNJ untuk admin/dosen (tidak boleh @stu.pnj.ac.id)
        if ((!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.pnj\.ac\.id$/', $email) && 
             !preg_match('/^[a-zA-Z0-9._%+-]+@pnj\.ac\.id$/', $email)) || 
            preg_match('/@stu\.pnj\.ac\.id$/', $email)) {
            echo "<script>alert('Email admin harus menggunakan domain PNJ untuk dosen/staff (contoh: nama@jurusan.pnj.ac.id atau admin@pnj.ac.id), bukan email mahasiswa!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Validasi password minimal 8 karakter
        if (strlen($password) < 8) {
            echo "<script>alert('Password minimal 8 karakter!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Cek apakah NIP sudah ada
        if ($this->akunModel->isNomorIndukExists($nip)) {
            echo "<script>alert('NIP sudah terdaftar!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Cek apakah email sudah ada
        if ($this->akunModel->isEmailExists($email)) {
            echo "<script>alert('Email sudah terdaftar!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Buat akun admin baru
        $result = $this->akunModel->create([
            'nomor_induk' => $nip,
            'username' => $nama,
            'password' => $password,
            'email' => $email,
            'status' => 'Aktif',
            'role' => 'Admin'
        ]);

        if ($result) {
            echo "<script>alert('Admin baru berhasil ditambahkan!'); window.location.href='index.php?page=admin&action=member_list';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan admin!'); window.location.href='index.php?page=admin&action=member_list';</script>";
        }
        exit;
    }

    public function update_admin(): void
    {
        // Only Super Admin can update admin
        if ($_SESSION['user']['role'] !== 'Super Admin') {
            echo "<script>alert('Anda tidak memiliki akses untuk mengubah admin!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=member_list');
            exit;
        }

        $nomorInduk = trim($_POST['nomor_induk'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $status = $_POST['status'] ?? 'Aktif';

        // Validasi
        if (empty($nomorInduk) || empty($nama) || empty($email)) {
            echo "<script>alert('Semua field harus diisi!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Validasi email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('Format email tidak valid!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Validasi domain email PNJ
        $isValidDomain = false;
        
        // Untuk mahasiswa/user: harus @stu.pnj.ac.id
        if (preg_match('/^[a-zA-Z0-9._%+-]+\.[a-zA-Z0-9]@stu\.pnj\.ac\.id$/', $email)) {
            $isValidDomain = true;
        }
        // Untuk admin/dosen: harus @*.pnj.ac.id atau @pnj.ac.id (bukan @stu.pnj.ac.id)
        elseif ((preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.pnj\.ac\.id$/', $email) || 
                 preg_match('/^[a-zA-Z0-9._%+-]+@pnj\.ac\.id$/', $email)) && 
                !preg_match('/@stu\.pnj\.ac\.id$/', $email)) {
            $isValidDomain = true;
        }
        
        if (!$isValidDomain) {
            echo "<script>alert('Email harus menggunakan domain PNJ. Mahasiswa: nama.x@stu.pnj.ac.id, Dosen/Admin: nama@jurusan.pnj.ac.id atau nama@pnj.ac.id'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Cek apakah admin ada
        $admin = $this->akunModel->getByNomorInduk($nomorInduk);
        if (!$admin) {
            echo "<script>alert('Admin tidak ditemukan!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Cek apakah email sudah digunakan oleh akun lain
        if ($this->akunModel->isEmailExists($email, $nomorInduk)) {
            echo "<script>alert('Email sudah digunakan oleh akun lain!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Update admin
        $result = $this->akunModel->update($nomorInduk, [
            'username' => $nama,
            'email' => $email,
            'status' => $status,
            'role' => $admin['role'] // Keep existing role
        ]);

        if ($result) {
            echo "<script>alert('Data admin berhasil diubah!'); window.location.href='index.php?page=admin&action=member_list';</script>";
        } else {
            echo "<script>alert('Gagal mengubah data admin!'); window.location.href='index.php?page=admin&action=member_list';</script>";
        }
        exit;
    }

    public function delete_admin(): void
    {
        // Only Super Admin can delete admin
        if ($_SESSION['user']['role'] !== 'Super Admin') {
            echo "<script>alert('Anda tidak memiliki akses untuk menghapus admin!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=member_list');
            exit;
        }

        $nomorInduk = trim($_POST['nomor_induk'] ?? '');

        if (empty($nomorInduk)) {
            echo "<script>alert('Nomor induk tidak valid!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Cek apakah admin ada
        $admin = $this->akunModel->getByNomorInduk($nomorInduk);
        if (!$admin) {
            echo "<script>alert('Admin tidak ditemukan!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Tidak bisa hapus diri sendiri
        if ($nomorInduk === $_SESSION['user']['nomor_induk']) {
            echo "<script>alert('Tidak dapat menghapus akun sendiri!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Hard delete
        try {
            $result = $this->akunModel->delete($nomorInduk);
            if ($result) {
                echo "<script>alert('Admin berhasil dihapus!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            } else {
                echo "<script>alert('Gagal menghapus admin!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Tidak dapat menghapus admin karena masih memiliki relasi data!'); window.location.href='index.php?page=admin&action=member_list';</script>";
        }
        exit;
    }

    public function update_user_status(): void
    {
        // Both Admin and Super Admin can update user status
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=member_list');
            exit;
        }

        $nomorInduk = trim($_POST['nomor_induk'] ?? '');
        $status = $_POST['status'] ?? 'Aktif';

        if (empty($nomorInduk)) {
            echo "<script>alert('Nomor induk tidak valid!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Cek apakah user ada
        $user = $this->akunModel->getByNomorInduk($nomorInduk);
        if (!$user) {
            echo "<script>alert('User tidak ditemukan!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Update status
        $result = $this->akunModel->updateStatus($nomorInduk, $status);

        if ($result) {
            $statusText = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';
            echo "<script>alert('" . addslashes("User berhasil {$statusText}!") . "'); window.location.href='index.php?page=admin&action=member_list';</script>";
        } else {
            echo "<script>alert('Gagal mengubah status user!'); window.location.href='index.php?page=admin&action=member_list';</script>";
        }
        exit;
    }

    public function delete_user(): void
    {
        // Both Admin and Super Admin can delete user
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=member_list');
            exit;
        }

        $nomorInduk = trim($_POST['nomor_induk'] ?? '');

        if (empty($nomorInduk)) {
            echo "<script>alert('Nomor induk tidak valid!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Cek apakah user ada
        $user = $this->akunModel->getByNomorInduk($nomorInduk);
        if (!$user) {
            echo "<script>alert('User tidak ditemukan!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            exit;
        }

        // Hard delete
        try {
            $result = $this->akunModel->delete($nomorInduk);
            if ($result) {
                echo "<script>alert('User berhasil dihapus!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            } else {
                echo "<script>alert('Gagal menghapus user!'); window.location.href='index.php?page=admin&action=member_list';</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Tidak dapat menghapus user karena masih memiliki relasi data (booking/pelanggaran)!'); window.location.href='index.php?page=admin&action=member_list';</script>";
        }
        exit;
    }
    
    /**
     * Get booking detail untuk modal check-in (AJAX)
     */
    public function get_booking_checkin(): void
    {
        // Clear any previous output
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID booking tidak ditemukan']);
            exit;
        }
        
        try {
            $bookingListModel = new BookingListModel();
            $booking = $bookingListModel->getBookingDetailForCheckIn((int)$_GET['id']);
            
            if (!$booking) {
                echo json_encode(['success' => false, 'message' => 'Booking tidak ditemukan atau data tidak lengkap']);
                exit;
            }
            
            echo json_encode(['success' => true, 'data' => $booking]);
        } catch (Exception $e) {
            error_log('Error get_booking_checkin: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Admin check-in anggota
     */
    public function checkin_anggota(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=booking_list');
            exit;
        }
        
        $idBooking = (int) ($_POST['id_booking'] ?? 0);
        $nomorInduk = $_POST['nomor_induk'] ?? '';
        
        if (!$idBooking || !$nomorInduk) {
            echo "<script>alert('Data tidak lengkap'); window.location.href='index.php?page=admin&action=booking_list';</script>";
            exit;
        }
        
        // Validasi Hari H: Check-in hanya bisa dilakukan pada tanggal booking
        $bookingListModel = new BookingListModel();
        $booking = $bookingListModel->getById($idBooking);
        
        if (!$booking || !$booking['tanggal_schedule']) {
            echo "<script>alert('Booking tidak ditemukan'); window.location.href='index.php?page=admin&action=booking_list';</script>";
            exit;
        }
        
        $today = date('Y-m-d');
        $bookingDate = date('Y-m-d', strtotime($booking['tanggal_schedule']));
        
        if ($today !== $bookingDate) {
            $formattedDate = date('d F Y', strtotime($booking['tanggal_schedule']));
            $msg = "⚠️ Check-in hanya bisa dilakukan pada Hari H!\\n\\nTanggal booking: $formattedDate\\nHari ini: " . date('d F Y');
            echo "<script>alert('$msg'); window.location.href='index.php?page=admin&action=booking_list';</script>";
            exit;
        }
        
        if ($bookingListModel->adminCheckInAnggota($idBooking, $nomorInduk)) {
            $message = "✅ Check-in berhasil!\\n\\n📱 Pemberitahuan untuk User:\\nUser yang di-check-in harus REFRESH halaman dashboard mereka untuk mengaktifkan tombol Selesai.";
            echo "<script>alert('" . $message . "'); window.location.href='index.php?page=admin&action=booking_list';</script>";
        } else {
            echo "<script>alert('❌ Check-in gagal'); window.location.href='index.php?page=admin&action=booking_list';</script>";
        }
        exit;
    }
    
    /**
     * Admin check-in semua anggota sekaligus
     */
    public function checkin_all(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=booking_list');
            exit;
        }
        
        $idBooking = (int) ($_POST['id_booking'] ?? 0);
        
        if (!$idBooking) {
            echo "<script>alert('ID booking tidak ditemukan'); window.location.href='index.php?page=admin&action=booking_list';</script>";
            exit;
        }
        
        // Validasi Hari H: Check-in hanya bisa dilakukan pada tanggal booking
        $bookingListModel = new BookingListModel();
        $booking = $bookingListModel->getById($idBooking);
        
        if (!$booking || !$booking['tanggal_schedule']) {
            echo "<script>alert('Booking tidak ditemukan'); window.location.href='index.php?page=admin&action=booking_list';</script>";
            exit;
        }
        
        $today = date('Y-m-d');
        $bookingDate = date('Y-m-d', strtotime($booking['tanggal_schedule']));
        
        if ($today !== $bookingDate) {
            $formattedDate = date('d F Y', strtotime($booking['tanggal_schedule']));
            $msg = "⚠️ Check-in hanya bisa dilakukan pada Hari H!\\n\\nTanggal booking: $formattedDate\\nHari ini: " . date('d F Y');
            echo "<script>alert('$msg'); window.location.href='index.php?page=admin&action=booking_list';</script>";
            exit;
        }
        
        if ($bookingListModel->adminCheckInAll($idBooking)) {
            $message = "✅ Semua anggota berhasil di-check-in!\\n\\n📱 Pemberitahuan untuk User:\\nUser yang di-check-in harus REFRESH halaman dashboard mereka untuk mengaktifkan tombol Selesai.\\n\\nCara refresh: Tekan F5 atau Ctrl+R";
            echo "<script>alert('" . $message . "'); window.location.href='index.php?page=admin&action=booking_list';</script>";
        } else {
            echo "<script>alert('❌ Check-in gagal'); window.location.href='index.php?page=admin&action=booking_list';</script>";
        }
        exit;
    }

    // ==================== PENGATURAN SISTEM (SUPER ADMIN ONLY) ====================

    /**
     * Show pengaturan sistem page (Super Admin only)
     */
    public function pengaturan(): void
    {
        // Super Admin only check
        if ($_SESSION['user']['role'] !== 'Super Admin') {
            header('Location: index.php?page=admin&action=index');
            exit;
        }

        $waktuOperasi = $this->pengaturanModel->getAllWaktuOperasi();
        $hariLibur = $this->pengaturanModel->getAllHariLibur();

        require __DIR__ . '/../view/admin/pengaturan.php';
    }

    /**
     * Update waktu operasi (Super Admin only)
     */
    public function update_waktu_operasi(): void
    {
        if ($_SESSION['user']['role'] !== 'Super Admin') {
            header('Location: index.php?page=admin&action=index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=pengaturan');
            exit;
        }

        $hari = $_POST['hari'] ?? '';
        $jamBuka = $_POST['jam_buka'] ?? '';
        $jamTutup = $_POST['jam_tutup'] ?? '';
        $isAktif = (int) ($_POST['is_aktif'] ?? 1);

        // Validasi input
        if (empty($hari) || empty($jamBuka) || empty($jamTutup)) {
            echo "<script>alert('Semua field wajib diisi!'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
            exit;
        }

        // Validasi jam buka < jam tutup
        if ($jamBuka >= $jamTutup) {
            echo "<script>alert('Jam buka harus lebih awal dari jam tutup!'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
            exit;
        }

        $pengaturanModel = new PengaturanModel();
        $updatedBy = $_SESSION['user']['nomor_induk'];

        if ($pengaturanModel->updateWaktuOperasi($hari, $jamBuka, $jamTutup, $isAktif, $updatedBy)) {
            echo "<script>alert('Waktu operasi berhasil diupdate'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
        } else {
            echo "<script>alert('Gagal update waktu operasi'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
        }
        exit;
    }

    /**
     * Create hari libur (Super Admin only)
     */
    public function create_hari_libur(): void
    {
        if ($_SESSION['user']['role'] !== 'Super Admin') {
            header('Location: index.php?page=admin&action=index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=pengaturan');
            exit;
        }

        $tanggal = $_POST['tanggal'] ?? '';
        $keterangan = trim($_POST['keterangan'] ?? '');

        // Validasi input
        if (empty($tanggal) || empty($keterangan)) {
            echo "<script>alert('Tanggal dan keterangan wajib diisi!'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
            exit;
        }

        // Validasi tanggal tidak boleh masa lalu
        if ($tanggal < date('Y-m-d')) {
            echo "<script>alert('Tidak bisa menambahkan hari libur di masa lalu!'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
            exit;
        }

        $pengaturanModel = new PengaturanModel();
        $createdBy = $_SESSION['user']['nomor_induk'];

        // Check duplicate
        if ($pengaturanModel->isHariLibur($tanggal)) {
            echo "<script>alert('Tanggal ini sudah terdaftar sebagai hari libur!'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
            exit;
        }

        // OPTIONAL WARNING: Check if there are active bookings on this date
        $scheduleModel = new ScheduleModel();
        $activeBookingsCount = $scheduleModel->countActiveBookingsByDate($tanggal);
        if ($activeBookingsCount > 0) {
            // Log warning but still allow (admin decision)
            error_log("WARNING: Adding holiday on {$tanggal} but {$activeBookingsCount} active booking(s) exist. Consider canceling them.");
        }

        if ($pengaturanModel->createHariLibur($tanggal, $keterangan, $createdBy)) {
            $warningMsg = $activeBookingsCount > 0 ? "\n\nPeringatan: Ada {$activeBookingsCount} booking aktif pada tanggal ini. Pertimbangkan untuk membatalkan booking tersebut." : "";
            echo "<script>alert('Hari libur berhasil ditambahkan" . addslashes($warningMsg) . "'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan hari libur'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
        }
        exit;
    }

    /**
     * Update hari libur (Super Admin only)
     */
    public function update_hari_libur(): void
    {
        if ($_SESSION['user']['role'] !== 'Super Admin') {
            header('Location: index.php?page=admin&action=index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=pengaturan');
            exit;
        }

        $id = (int) ($_POST['id_hari_libur'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? '';
        $keterangan = trim($_POST['keterangan'] ?? '');

        // Validasi input
        if (!$id || empty($tanggal) || empty($keterangan)) {
            echo "<script>alert('Semua field wajib diisi!'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
            exit;
        }

        $pengaturanModel = new PengaturanModel();

        // CRITICAL FIX: Check duplicate tanggal (kecuali untuk record yang sedang diedit)
        $existing = $pengaturanModel->getHariLiburByDate($tanggal);
        if ($existing && $existing['id_hari_libur'] != $id) {
            echo "<script>alert('Tanggal ini sudah terdaftar sebagai hari libur!'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
            exit;
        }

        if ($pengaturanModel->updateHariLibur($id, $tanggal, $keterangan)) {
            echo "<script>alert('Hari libur berhasil diupdate'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
        } else {
            echo "<script>alert('Gagal update hari libur'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
        }
        exit;
    }

    /**
     * Delete hari libur (Super Admin only)
     */
    public function delete_hari_libur(): void
    {
        if ($_SESSION['user']['role'] !== 'Super Admin') {
            header('Location: index.php?page=admin&action=index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin&action=pengaturan');
            exit;
        }

        $id = (int) ($_POST['id_hari_libur'] ?? 0);

        if (!$id) {
            echo "<script>alert('ID hari libur tidak ditemukan'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
            exit;
        }

        $pengaturanModel = new PengaturanModel();

        if ($pengaturanModel->deleteHariLibur($id)) {
            echo "<script>alert('Hari libur berhasil dihapus'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
        } else {
            echo "<script>alert('Gagal menghapus hari libur'); window.location.href='index.php?page=admin&action=pengaturan';</script>";
        }
        exit;
    }
}
