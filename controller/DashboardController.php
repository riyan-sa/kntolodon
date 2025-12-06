<?php

class DashboardController
{
    private RuanganModel $ruanganModel;
    private BookingModel $bookingModel;
    private FeedbackModel $feedbackModel;
    private BookingListModel $bookingListModel;

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
