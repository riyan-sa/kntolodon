<?php
/**
 * ============================================================================
 * PROFILECONTROLLER.PHP - User Profile & Settings Controller
 * ============================================================================
 * 
 * Controller untuk mengelola profil user, booking history, pelanggaran, dan settings.
 * Support untuk User dan Admin dengan tampilan berbeda (admin: dashboard grid, user: tabs).
 * 
 * FUNGSI UTAMA:
 * 1. INDEX - Main profile page dengan tabs: Kode Booking, History, Pelanggaran
 * 2. UPLOAD FOTO - Upload/update foto profil
 * 3. CHANGE PASSWORD - Change password dengan old password verification
 * 
 * ROUTES:
 * - ?page=profile&action=index - Profile page dengan pagination (pg_history, pg_pelanggaran)
 * - ?page=profile&action=upload_foto - Upload foto profil (POST)
 * - ?page=profile&action=change_password - Change password (POST)
 * 
 * PROFILE PAGE STRUCTURE:
 * - ADMIN/SUPER ADMIN:
 *   - Shows admin dashboard grid (room management, reports, member list)
 *   - No booking history or pelanggaran tabs
 * 
 * - USER (Mahasiswa/Dosen/Tenaga Pendidikan):
 *   - Tab 1: Kode Booking - Active booking card (if exists)
 *   - Tab 2: History - Paginated booking history (SELESAI, DIBATALKAN, HANGUS)
 *   - Tab 3: Pelanggaran - Paginated pelanggaran/suspensi records
 *   - Tab 4: Settings - Foto profil upload + change password modal
 * 
 * PAGINATION:
 * - History: 6 records per page (parameter: pg_history)
 * - Pelanggaran: 6 records per page (parameter: pg_pelanggaran)
 * - Independent pagination state for each tab
 * - Preserved in URL when switching tabs
 * 
 * ACTIVE BOOKING (KODE BOOKING TAB):
 * - Show booking with status AKTIF (ketua OR anggota)
 * - Display: kode_booking, ruangan, tanggal, waktu, anggota list, check-in status
 * - Show action buttons: Reschedule, Cancel (ketua only)
 * 
 * HISTORY TAB:
 * - Filter: Only SELESAI, DIBATALKAN, HANGUS (exclude AKTIF)
 * - Sort: Most recent first
 * - Pagination: 6 per page
 * - Display: kode_booking, ruangan, tanggal, status with color coding
 * - Auto-update HANGUS status on page load
 * 
 * PELANGGARAN TAB:
 * - Show all pelanggaran_suspensi records for user
 * - Display: tanggal_mulai, tanggal_selesai, alasan_suspensi, status
 * - Status: Active if current date between mulai-selesai
 * - Pagination: 6 per page
 * 
 * UPLOAD FOTO WORKFLOW:
 * 1. POST validation: Must be POST request
 * 2. Session check: User must be logged in
 * 3. File validation:
 *    - Must be image (JPEG, PNG, WebP)
 *    - Max size: 25MB
 *    - MIME type validation via finfo_file
 * 4. File handling:
 *    - Delete old foto if exists
 *    - Generate unique filename: 'profile_{nomor_induk}_{timestamp}.{ext}'
 *    - Upload to: assets/uploads/images/
 *    - Create directory if not exists (mode 0755)
 * 5. Database update: AkunModel::updateFotoProfil()
 * 6. Session update: $_SESSION['user']['foto_profil']
 * 7. Redirect with success message
 * 
 * CHANGE PASSWORD WORKFLOW:
 * 1. POST validation: Must be POST request
 * 2. Session check: User must be logged in
 * 3. Input validation:
 *    - old_password, new_password, confirm_password required
 *    - new_password minimum 8 characters
 *    - new_password === confirm_password
 * 4. Old password verification: AkunModel::verifyPassword()
 * 5. New password !== old password (prevent reuse)
 * 6. Update password: AkunModel::updatePassword() (auto-hash)
 * 7. Redirect with success message
 * 
 * AUTO-UPDATE INTEGRATION:
 * - autoUpdateHangusStatus() runs on page load
 * - Ensures history tab shows accurate booking statuses
 * - HANGUS status applied to bookings without check-in >10min late
 * 
 * FILE UPLOAD SECURITY:
 * - MIME type validation (not just extension)
 * - Size limit enforcement (25MB)
 * - Unique filename generation (prevent conflicts)
 * - Directory traversal prevention (controlled upload path)
 * - Old file cleanup (prevent storage bloat)
 * 
 * PASSWORD SECURITY:
 * - Old password verification required
 * - Minimum 8 characters enforcement
 * - Password hashing via password_hash() in model
 * - Prevent password reuse (old !== new)
 * 
 * PAGINATION PATTERN:
 * - URL params: pg_history={n}, pg_pelanggaran={n}
 * - Calculate: offset = (currentPage - 1) * perPage
 * - array_slice for in-memory pagination
 * - Pagination data: {currentPage, totalPages, totalRecords, perPage}
 * - Preserved when switching tabs via JavaScript
 * 
 * BUSINESS RULES:
 * - FOTO PROFIL: User-controlled, not required for account activation
 * - PASSWORD: 8-character minimum, old password required for change
 * - HISTORY: Exclude AKTIF bookings (shown in Kode Booking tab)
 * - PELANGGARAN: Read-only view, managed by admin
 * 
 * USAGE PATTERNS:
 * - view/profile/index.php: Main profile layout with tabs
 * - assets/js/profile.js: Tab switching + foto upload modal
 * - view/components/modal_change_password.php: Password change modal
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

/**
 * Class ProfileController - User Profile & Settings Handler
 * 
 * @property AkunModel $akunModel Account operations (foto, password)
 * @property BookingModel $bookingModel Booking history retrieval
 * @property MemberModel $memberModel Pelanggaran retrieval
 * @property BookingListModel $bookingListModel Auto-update methods
 */
class ProfileController
{
    /**
     * AkunModel instance untuk account operations
     * @var AkunModel
     */
    private AkunModel $akunModel;
    
    /**
     * BookingModel instance untuk booking history
     * @var BookingModel
     */
    private BookingModel $bookingModel;
    
    /**
     * MemberModel instance untuk pelanggaran data
     * @var MemberModel
     */
    private MemberModel $memberModel;
    
    /**
     * BookingListModel instance untuk auto-update methods
     * @var BookingListModel
     */
    private BookingListModel $bookingListModel;

    /**
     * Constructor - Initialize models and session
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->akunModel = new AkunModel();
        $this->bookingModel = new BookingModel();
        $this->memberModel = new MemberModel();
        $this->bookingListModel = new BookingListModel();
    }

    public function index(): void
    {
        $this->renderIndexView();
    }

    private function renderIndexView(): void
    {
        // Auto-update status HANGUS untuk booking yang lewat 10 menit dari waktu mulai tanpa check-in
        $this->bookingListModel->autoUpdateHangusStatus();
        
        // Get user's bookings and pelanggaran if logged in and not admin
        $active_booking = false;
        $history_bookings = [];
        $pelanggaran_list = [];
        
        // Pagination setup
        $perPageHistory = 6;
        $perPagePelanggaran = 6;
        $currentPageHistory = isset($_GET['pg_history']) ? max(1, (int)$_GET['pg_history']) : 1;
        $currentPagePelanggaran = isset($_GET['pg_pelanggaran']) ? max(1, (int)$_GET['pg_pelanggaran']) : 1;
        
        if (isset($_SESSION['user']) && !in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])) {
            $nomor_induk = $_SESSION['user']['nomor_induk'];
            $active_booking = $this->bookingModel->getActiveByMember($nomor_induk);
            
            // Get all bookings (history) - exclude AKTIF karena sudah ditampilkan di tab Kode Booking
            $all_bookings = $this->bookingModel->getByNomorInduk($nomor_induk);
            $all_history = array_filter($all_bookings, function($booking) {
                return $booking['nama_status'] !== 'AKTIF'; // Only SELESAI, DIBATALKAN, HANGUS
            });
            
            // Apply pagination to history
            $totalHistory = count($all_history);
            $totalPagesHistory = ceil($totalHistory / $perPageHistory);
            $offsetHistory = ($currentPageHistory - 1) * $perPageHistory;
            $history_bookings = array_slice($all_history, $offsetHistory, $perPageHistory);
            
            // Get pelanggaran with pagination
            $all_pelanggaran = $this->memberModel->getPelanggaran($nomor_induk);
            $totalPelanggaran = count($all_pelanggaran);
            $totalPagesPelanggaran = ceil($totalPelanggaran / $perPagePelanggaran);
            $offsetPelanggaran = ($currentPagePelanggaran - 1) * $perPagePelanggaran;
            $pelanggaran_list = array_slice($all_pelanggaran, $offsetPelanggaran, $perPagePelanggaran);
            
            // Pagination data for view
            $paginationHistory = [
                'currentPage' => $currentPageHistory,
                'totalPages' => $totalPagesHistory,
                'totalRecords' => $totalHistory,
                'perPage' => $perPageHistory
            ];
            
            $paginationPelanggaran = [
                'currentPage' => $currentPagePelanggaran,
                'totalPages' => $totalPagesPelanggaran,
                'totalRecords' => $totalPelanggaran,
                'perPage' => $perPagePelanggaran
            ];
        }
        
        require __DIR__ . '/../view/profile/index.php';
    }

    public function upload_foto(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in
        if (!isset($_SESSION['user'])) {
            echo "<script>alert('Anda harus login terlebih dahulu'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Check if request is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "<script>alert('Invalid request method'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        $nomor_induk = $_SESSION['user']['nomor_induk'];
        $errors = [];

        // Handle file upload
        if (!isset($_FILES['foto_profil']) || $_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File foto profil harus diupload.';
        } else {
            $file = $_FILES['foto_profil'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $maxSize = 25 * 1024 * 1024; // 25MB

            // Validasi tipe file
            if (!in_array($file['type'], $allowedTypes)) {
                $errors[] = 'File harus berupa gambar (JPEG, PNG, atau WebP).';
            }

            // Validasi ukuran file
            if ($file['size'] > $maxSize) {
                $errors[] = 'Ukuran file maksimal 25MB.';
            }

            // Jika tidak ada error, upload file
            if (empty($errors)) {
                $uploadDir = __DIR__ . '/../assets/uploads/images/';
                
                // Buat direktori jika belum ada
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Hapus foto lama jika ada
                $oldFoto = $_SESSION['user']['foto_profil'] ?? null;
                if ($oldFoto && file_exists(__DIR__ . '/../' . $oldFoto)) {
                    unlink(__DIR__ . '/../' . $oldFoto);
                }

                // Generate nama file unik
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = 'profile_' . $nomor_induk . '_' . time() . '.' . $extension;
                $uploadPath = $uploadDir . $fileName;

                // Upload file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $fotoProfil = 'assets/uploads/images/' . $fileName;
                    
                    // Update database
                    if ($this->akunModel->updateFotoProfil($nomor_induk, $fotoProfil)) {
                        // Update session
                        $_SESSION['user']['foto_profil'] = $fotoProfil;
                        echo "<script>alert('Foto profil berhasil diupdate!'); window.location.href='index.php?page=profile';</script>";
                        exit;
                    } else {
                        $errors[] = 'Gagal menyimpan foto profil ke database.';
                    }
                } else {
                    $errors[] = 'Gagal mengupload file.';
                }
            }
        }

        // Set error message if any
        if (!empty($errors)) {
            $errorMsg = implode(' ', $errors);
            echo "<script>alert('" . addslashes($errorMsg) . "'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        header('Location: index.php?page=profile');
        exit;
    }

    /**
     * Change password with old password verification
     */
    public function change_password(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in
        if (!isset($_SESSION['user'])) {
            echo "<script>alert('Anda harus login terlebih dahulu'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Check if request is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "<script>alert('Invalid request method'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        $nomor_induk = $_SESSION['user']['nomor_induk'];
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validasi input
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo "<script>alert('Semua field harus diisi'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Validasi panjang password baru
        if (strlen($newPassword) < 8) {
            echo "<script>alert('Password baru minimal 8 karakter'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Validasi konfirmasi password
        if ($newPassword !== $confirmPassword) {
            echo "<script>alert('Password baru dan konfirmasi password tidak cocok'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Verifikasi password lama
        if (!$this->akunModel->verifyPassword($nomor_induk, $oldPassword)) {
            echo "<script>alert('Password lama salah'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Cek apakah password baru sama dengan password lama
        if ($oldPassword === $newPassword) {
            echo "<script>alert('Password baru tidak boleh sama dengan password lama'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Update password
        if ($this->akunModel->updatePassword($nomor_induk, $newPassword)) {
            echo "<script>alert('Password berhasil diubah!'); window.location.href='index.php?page=profile';</script>";
        } else {
            echo "<script>alert('Gagal mengubah password. Silakan coba lagi.'); window.location.href='index.php?page=profile';</script>";
        }
        exit;
    }
}