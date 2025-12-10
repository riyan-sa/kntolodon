<?php
/**
 * ============================================================================
 * DASHBOARDCONTROLLER.PHP - User Dashboard Controller
 * ============================================================================
 * 
 * Controller untuk user dashboard dengan room browsing, active booking display, dan feedback.
 * Terintegrasi dengan auto-update status system untuk real-time booking states.
 * 
 * FUNGSI UTAMA:
 * 1. INDEX - Main dashboard dengan room list dan active booking status
 * 2. SELESAI BOOKING - Mark active booking as complete (ketua only)
 * 3. FEEDBACK - Display feedback form after booking completion
 * 4. SUBMIT FEEDBACK - Process feedback submission
 * 
 * ROUTES:
 * - ?page=dashboard&action=index - Main dashboard (default)
 * - ?page=dashboard&action=selesai_booking - Mark booking complete (POST)
 * - ?page=dashboard&action=feedback - Feedback form (after selesai_booking)
 * - ?page=dashboard&action=submit_feedback - Submit feedback (POST)
 * 
 * DASHBOARD BEHAVIOR:
 * - Admin/Super Admin: Redirect to admin dashboard (?page=admin)
 * - User: Show room list + active booking card
 * - Auto-update booking statuses on load (HANGUS → SELESAI → room status)
 * - Display suspend alert if user has active suspension
 * 
 * AUTO-UPDATE SEQUENCE (CRITICAL ORDER):
 * 1. autoUpdateHangusStatus() - Check bookings tanpa check-in >10min late → HANGUS
 * 2. autoUpdateSelesaiStatus() - Check bookings with check-in past waktu_selesai → SELESAI
 * 3. autoUpdateRoomStatus() - Update room availability based on active bookings
 * 
 * ORDER IMPORTANT: HANGUS must run before SELESAI to prevent status conflicts
 * 
 * ACTIVE BOOKING DISPLAY:
 * - Show if user has booking with status AKTIF (as ketua OR anggota)
 * - Display: kode_booking, ruangan, tanggal, waktu, check-in status
 * - Show "Selesaikan Booking" button if:
 *   - User is ketua
 *   - Booking date is today (hari H)
 *   - Current time >= waktu_mulai
 *   - Current time <= waktu_selesai
 * 
 * ROOM LIST:
 * - Filter: Only 'Ruang Umum' (exclude 'Ruang Rapat' for admins)
 * - Dynamic availability: getAllWithDynamicAvailability()
 *   - 'Tersedia' if no active booking at current time
 *   - 'Sedang Digunakan' if has active booking now
 * - Each room shows: foto, nama, kapasitas, fasilitas, status
 * 
 * SELESAI BOOKING WORKFLOW:
 * 1. POST validation: Must be POST request
 * 2. Session check: User must be logged in
 * 3. Booking validation: Must exist
 * 4. Ownership check: User must be ketua
 * 5. TIME CONSTRAINTS:
 *    - Can only complete on booking date (hari H)
 *    - Current time >= waktu_mulai
 *    - Current time <= waktu_selesai
 *    - Cannot complete before waktu_mulai (show minutes remaining)
 *    - Cannot complete if past waktu_selesai (show error)
 * 6. Update status to SELESAI (id=2)
 * 7. Store booking ID in session: $_SESSION['feedback_booking_id']
 * 8. Redirect to feedback page
 * 
 * FEEDBACK WORKFLOW:
 * 1. Check session for feedback_booking_id
 * 2. Validate booking exists and status is SELESAI
 * 3. Check if feedback already submitted (hasFeedback)
 * 4. Display feedback form with rating options (1 or 5)
 * 5. User submits rating + kritik_saran (text feedback)
 * 6. Validate:
 *    - User must be ketua
 *    - Rating must be 1 or 5
 *    - No duplicate feedback
 * 7. Save to feedback table
 * 8. Clear feedback_booking_id from session
 * 9. Redirect to dashboard with success message
 * 
 * FEEDBACK FORM:
 * - Rating: 1 (Sedih/Poor) or 5 (Senyum/Excellent)
 * - Kritik & Saran: Combined text field (required)
 * - Only visible to ketua after selesai_booking
 * 
 * SUSPEND NOTIFICATION:
 * - Display alert if user has active pelanggaran_suspensi
 * - Message includes: suspend end date and reason
 * - Check via BookingModel::checkSuspendStatus()
 * 
 * TIMEZONE:
 * - Set to Asia/Jakarta (WIB) in constructor
 * - All datetime comparisons use WIB timezone
 * 
 * BUSINESS RULES:
 * - SELESAI BOOKING: Only on hari H, between waktu_mulai and waktu_selesai
 * - FEEDBACK: One-time per booking, only by ketua
 * - ADMIN REDIRECT: Admins cannot access user dashboard
 * - REAL-TIME STATUS: Auto-update runs on every page load
 * 
 * SECURITY FEATURES:
 * - Session validation untuk semua actions
 * - Ownership check: Only ketua can complete booking
 * - Time validation: Strict time window enforcement
 * - Duplicate feedback prevention
 * - SQL injection prevention: Prepared statements in models
 * 
 * USAGE PATTERNS:
 * - view/dashboard.php: Main dashboard layout
 * - view/feedback.php: Feedback form
 * - assets/js/dashboard.js: Room modal interactions
 * - assets/js/feedback.js: Rating selection
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

/**
 * Class DashboardController - User Dashboard Handler
 * 
 * @property RuanganModel $ruanganModel Room data and availability
 * @property BookingModel $bookingModel Booking operations
 * @property FeedbackModel $feedbackModel Feedback operations
 * @property BookingListModel $bookingListModel Auto-update methods
 */
class DashboardController
{
    /**
     * RuanganModel instance untuk room data
     * @var RuanganModel
     */
    private RuanganModel $ruanganModel;
    
    /**
     * BookingModel instance untuk booking operations
     * @var BookingModel
     */
    private BookingModel $bookingModel;
    
    /**
     * FeedbackModel instance untuk feedback operations
     * @var FeedbackModel
     */
    private FeedbackModel $feedbackModel;
    
    /**
     * BookingListModel instance untuk auto-update methods
     * @var BookingListModel
     */
    private BookingListModel $bookingListModel;

    /**
     * Constructor - Initialize models, session, and timezone
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set timezone to Asia/Jakarta (WIB)
        date_default_timezone_set('Asia/Jakarta');
        
        $this->ruanganModel = new RuanganModel();
        $this->bookingModel = new BookingModel();
        $this->feedbackModel = new FeedbackModel();
        $this->bookingListModel = new BookingListModel();
    }

    public function index(): void
    {
        // Check if user has admin/superadmin role and redirect to admin dashboard
        if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])) {
            header('Location: index.php?page=admin');
            exit;
        }

        $this->renderIndexView();
    }

    private function renderIndexView(): void
    {
        // CRITICAL: Urutan eksekusi auto-update sangat penting!
        // 1. HANGUS dulu - cek booking tanpa check-in yang lewat 10 menit
        // 2. SELESAI kedua - cek booking dengan check-in yang sudah habis waktunya
        $this->bookingListModel->autoUpdateHangusStatus();
        $this->bookingListModel->autoUpdateSelesaiStatus();
        
        // Auto-update status ruangan di database based on real-time availability
        $this->ruanganModel->autoUpdateRoomStatus();
        
        // Cek apakah user ada pelanggaran baru (suspend)
        if (isset($_SESSION['user']['nomor_induk'])) {
            $suspend = $this->bookingModel->checkSuspendStatus($_SESSION['user']['nomor_induk']);
            if ($suspend) {
                $msg = 'Akun Anda sedang disuspend sampai ' . date('d F Y', strtotime($suspend['tanggal_selesai'])) . '. Alasan: ' . $suspend['alasan_suspensi'];
                echo "<script>alert('" . addslashes($msg) . "');</script>";
            }
        }
        
        // Ambil semua ruangan untuk user (Ruang Umum) dengan status dinamis
        $ruangan_list = $this->ruanganModel->getAllWithDynamicAvailability('Ruang Umum');
        
        // Cek apakah user punya booking aktif (baik sebagai ketua maupun anggota)
        $has_active_booking = false;
        $active_booking = null;
        
        if (isset($_SESSION['user']['nomor_induk'])) {
            $active_booking_data = $this->bookingModel->getActiveByMember($_SESSION['user']['nomor_induk']);
            if ($active_booking_data) {
                $has_active_booking = true;
                $active_booking = $active_booking_data;
            }
        }
        
        require __DIR__ . '/../view/dashboard.php';
    }

    /**
     * Selesaikan booking aktif (ubah status jadi SELESAI dan tampilkan halaman feedback)
     */
    public function selesai_booking(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!isset($_SESSION['user'])) {
            echo "<script>alert('Anda harus login terlebih dahulu'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        $id_booking = (int) ($_POST['id_booking'] ?? 0);
        
        if (!$id_booking) {
            echo "<script>alert('ID booking tidak valid'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }

        // Validasi booking milik user
        $booking = $this->bookingModel->getById($id_booking);
        if (!$booking) {
            echo "<script>alert('Booking tidak ditemukan'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }

        $ketua = $this->bookingModel->getKetua($id_booking);
        if (!$ketua || $ketua['nomor_induk'] !== $_SESSION['user']['nomor_induk']) {
            echo "<script>alert('Anda tidak memiliki akses untuk menyelesaikan booking ini'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }

        // Validasi waktu: Hanya bisa selesaikan booking pada hari H setelah waktu mulai dan sebelum waktu selesai
        if (!empty($booking['tanggal_schedule']) && !empty($booking['waktu_mulai']) && !empty($booking['waktu_selesai'])) {
            $now = new DateTime();
            $tanggal_booking = new DateTime($booking['tanggal_schedule']);
            $waktu_mulai = new DateTime($booking['tanggal_schedule'] . ' ' . $booking['waktu_mulai']);
            $waktu_selesai = new DateTime($booking['tanggal_schedule'] . ' ' . $booking['waktu_selesai']);
            
            // Cek apakah sudah hari H
            if ($now->format('Y-m-d') < $tanggal_booking->format('Y-m-d')) {
                echo "<script>alert('Booking hanya bisa diselesaikan pada hari H (tanggal booking)'); window.location.href='index.php?page=dashboard';</script>";
                exit;
            }
            
            // Cek apakah belum melewati hari H
            if ($now->format('Y-m-d') > $tanggal_booking->format('Y-m-d')) {
                echo "<script>alert('Waktu booking sudah lewat. Booking akan otomatis ditandai HANGUS atau SELESAI oleh sistem'); window.location.href='index.php?page=dashboard';</script>";
                exit;
            }
            
            // Cek apakah sudah melewati waktu mulai
            if ($now < $waktu_mulai) {
                $diff_minutes = ($waktu_mulai->getTimestamp() - $now->getTimestamp()) / 60;
                echo "<script>alert('Booking baru bisa diselesaikan setelah waktu mulai (" . date('H:i', strtotime($booking['waktu_mulai'])) . " WIB). Masih " . ceil($diff_minutes) . " menit lagi.'); window.location.href='index.php?page=dashboard';</script>";
                exit;
            }
            
            // Cek apakah belum melewati waktu selesai
            if ($now > $waktu_selesai) {
                echo "<script>alert('Waktu booking sudah berakhir. Silakan hubungi admin jika ada masalah.'); window.location.href='index.php?page=dashboard';</script>";
                exit;
            }
        }

        // Update status jadi SELESAI (id=2)
        if ($this->bookingModel->updateStatus($id_booking, 2)) {
            // Simpan ID booking di session untuk feedback
            $_SESSION['feedback_booking_id'] = $id_booking;
            // Redirect ke halaman feedback
            header('Location: index.php?page=dashboard&action=feedback');
        } else {
            echo "<script>alert('Gagal menyelesaikan booking'); window.location.href='index.php?page=dashboard';</script>";
        }
        exit;
    }

    /**
     * Tampilkan halaman feedback
     */
    public function feedback(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }

        // Cek apakah ada booking ID di session
        if (!isset($_SESSION['feedback_booking_id'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $id_booking = $_SESSION['feedback_booking_id'];
        
        // Validasi booking
        $booking = $this->bookingModel->getById($id_booking);
        if (!$booking || $booking['nama_status'] !== 'SELESAI') {
            unset($_SESSION['feedback_booking_id']);
            header('Location: index.php?page=dashboard');
            exit;
        }

        // Cek apakah sudah ada feedback
        if ($this->feedbackModel->hasFeedback($id_booking)) {
            unset($_SESSION['feedback_booking_id']);
            echo "<script>alert('Feedback sudah pernah diberikan untuk booking ini'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }

        require __DIR__ . '/../view/feedback.php';
    }

    /**
     * Submit feedback
     */
    public function submit_feedback(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!isset($_SESSION['user'])) {
            echo "<script>alert('Anda harus login terlebih dahulu'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Ambil data dari POST
        $id_booking = (int) ($_POST['id_booking'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $feedback_text = trim($_POST['kritik_saran'] ?? '');

        // Validasi
        if (!$id_booking) {
            echo "<script>alert('ID booking tidak valid'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }

        if (!in_array($rating, [1, 5])) {
            echo "<script>alert('Rating tidak valid'); window.location.href='index.php?page=dashboard&action=feedback';</script>";
            exit;
        }

        // Validasi booking
        $booking = $this->bookingModel->getById($id_booking);
        if (!$booking || $booking['nama_status'] !== 'SELESAI') {
            echo "<script>alert('Booking tidak valid untuk feedback'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }

        // Cek apakah user adalah ketua booking
        $ketua = $this->bookingModel->getKetua($id_booking);
        if (!$ketua || $ketua['nomor_induk'] !== $_SESSION['user']['nomor_induk']) {
            echo "<script>alert('Anda tidak memiliki akses untuk memberikan feedback'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }

        // Cek apakah sudah ada feedback
        if ($this->feedbackModel->hasFeedback($id_booking)) {
            echo "<script>alert('Feedback sudah pernah diberikan untuk booking ini'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }

        // Simpan feedback
        $feedback_data = [
            'id_booking' => $id_booking,
            'kritik_saran' => $feedback_text,
            'skala_kepuasan' => $rating
        ];

        if ($this->feedbackModel->create($feedback_data)) {
            // Hapus session feedback_booking_id
            unset($_SESSION['feedback_booking_id']);
            echo "<script>alert('Terima kasih atas feedback Anda!'); window.location.href='index.php?page=dashboard';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan feedback'); window.location.href='index.php?page=dashboard';</script>";
        }
        exit;
    }
}

?>
