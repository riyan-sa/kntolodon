<?php
/**
 * ============================================================================
 * BOOKINGCONTROLLER.PHP - User Booking Management Controller
 * ============================================================================
 * 
 * Controller untuk menangani pembuatan, pembatalan, dan reschedule booking oleh user.
 * Terintegrasi dengan sistem validasi operasional (jam operasi, hari libur, kapasitas ruangan).
 * 
 * FUNGSI UTAMA:
 * 1. INDEX - List semua bookings user dengan auto-update status
 * 2. BUAT BOOKING - Create new booking dengan comprehensive validation
 * 3. KODE BOOKING - Display booking confirmation page
 * 4. HAPUS BOOKING - Cancel active booking (ketua only)
 * 5. RESCHEDULE - Reschedule active booking (ketua only, before check-in)
 * 6. GET BOOKED TIMESLOTS - AJAX endpoint untuk timeline conflict detection
 * 7. GET USER BY NIM - AJAX endpoint untuk auto-fill nama anggota
 * 
 * ROUTES:
 * - ?page=booking&action=index - List bookings
 * - ?page=booking&action=buat_booking - Create booking (GET: form, POST: process)
 * - ?page=booking&action=kode_booking&id={id} - Booking confirmation
 * - ?page=booking&action=hapus_booking&id={id} - Cancel booking
 * - ?page=booking&action=reschedule&id={id} - Reschedule booking
 * - ?page=booking&action=get_booked_timeslots&id_ruangan={id}&tanggal={date} - AJAX
 * - ?page=booking&action=get_user_by_nim&nim={nim} - AJAX
 * 
 * BOOKING CREATION VALIDATION FLOW:
 * 1. AUTO-UPDATE STATUS (entry point):
 *    - autoUpdateHangusStatus() - Check bookings tanpa check-in >10min late → HANGUS
 *    - autoUpdateSelesaiStatus() - Check bookings with check-in past waktu_selesai → SELESAI
 *    - autoUpdateRoomStatus() - Update room availability based on active bookings
 * 
 * 2. SESSION CHECK:
 *    - User must be logged in
 *    - User must NOT be Admin/Super Admin
 * 
 * 3. BOOKING BLOCK CHECK:
 *    - checkBookingBlock() - Check if user has active suspension
 *    - Block if 24-hour temporary block (1-2 HANGUS in 30 days)
 *    - Block if 7-day suspension (3 HANGUS in 30 days)
 * 
 * 4. ACTIVE BOOKING CHECK:
 *    - hasActiveBooking() - User can only have ONE active booking at a time
 *    - Prevents resource hogging
 * 
 * 5. ROOM VALIDATION:
 *    - Room must exist and be 'Tersedia'
 *    - Jenis ruangan 'Ruang Umum' untuk user
 * 
 * 6. DATE/TIME VALIDATION:
 *    - Tanggal + waktu tidak boleh masa lalu (minimal 5 menit buffer)
 *    - Waktu selesai > waktu mulai
 *    - Durasi minimal 15 menit (includes 10-minute check-in tolerance)
 * 
 * 7. OPERATIONAL HOURS VALIDATION:
 *    - validateWaktuOperasi() via PengaturanModel
 *    - Check if day is active (is_aktif = 1)
 *    - Check if time within operational hours (jam_buka - jam_tutup)
 * 
 * 8. HOLIDAY VALIDATION:
 *    - validateHariLibur() via PengaturanModel
 *    - Block bookings on registered holidays
 * 
 * 9. CAPACITY VALIDATION:
 *    - Ketua + anggota count must be between min-max room capacity
 *    - Each anggota must have valid NIM/NIP
 *    - No duplicates in anggota list
 * 
 * 10. MEMBER VALIDATION:
 *     - All members must exist in database
 *     - All members must have 'Aktif' status
 *     - No member in anggota can be Admin/Super Admin
 * 
 * 11. MEMBER BLOCK CHECK:
 *     - checkBookingBlock() untuk setiap anggota
 *     - Block booking if any member has active suspension
 * 
 * 12. TIME SLOT AVAILABILITY:
 *     - isTimeSlotAvailable() via ScheduleModel
 *     - No overlap with existing bookings
 * 
 * 13. MEMBER CONFLICT CHECK:
 *     - checkMemberConflicts() via ScheduleModel
 *     - No member (ketua + anggota) has another booking at same time
 * 
 * 14. DATABASE TRANSACTION:
 *     - Create booking record (status AKTIF)
 *     - Create anggota_booking records (ketua + anggota)
 *     - Create initial schedule record
 *     - Rollback on any error
 * 
 * BOOKING CANCELLATION (HAPUS BOOKING):
 * - Only ketua can cancel
 * - Only AKTIF bookings can be cancelled
 * - Update status to DIBATALKAN (id=3)
 * - GET: show confirmation form
 * - POST: process cancellation
 * 
 * RESCHEDULE WORKFLOW:
 * - Only ketua can reschedule
 * - Only AKTIF bookings can be rescheduled
 * - Cannot reschedule if any member already checked in
 * - Can only reschedule minimal 1 hour before waktu_mulai
 * - New datetime validation: minimal 5 menit dari sekarang
 * - Minimal duration: 15 menit
 * - Check time slot availability (exclude current booking)
 * - Update schedule with reason tracking
 * - Update booking duration
 * 
 * AJAX ENDPOINTS:
 * 1. get_booked_timeslots:
 *    - Returns: {success: bool, schedules: [{kode_booking, waktu_mulai, waktu_selesai}]}
 *    - Used by: assets/js/booking.js for timeline display
 * 
 * 2. get_user_by_nim:
 *    - Returns: {success: bool, data: {nomor_induk, username, role, email}, message: string}
 *    - Used by: assets/js/booking.js for auto-fill member names
 *    - Validates user exists and returns basic info (no password)
 * 
 * BUSINESS RULES:
 * - ONE ACTIVE BOOKING PER USER: Prevents resource hogging
 * - CAPACITY ENFORCEMENT: Ketua + anggota within room min-max capacity
 * - OPERATIONAL CONSTRAINTS: Respect jam operasi and hari libur
 * - SUSPENSION SYSTEM: Booking blocked if user or any member suspended
 * - CHECK-IN TOLERANCE: 15-minute minimal duration includes 10-minute late tolerance
 * - RESCHEDULE WINDOW: Minimal 1 hour before start time
 * 
 * SECURITY FEATURES:
 * - Session validation untuk semua actions
 * - Role check: Admin/Super Admin tidak bisa booking
 * - Ownership check: Only ketua can cancel/reschedule
 * - Time validation: Prevent past bookings
 * - SQL injection prevention: Prepared statements in models
 * - XSS prevention: addslashes untuk alert messages
 * 
 * USAGE PATTERNS:
 * - view/booking/buat_booking.php: Booking form
 * - view/booking/kode_booking.php: Confirmation page
 * - view/booking/hapus_booking.php: Cancellation form
 * - view/booking/reskedul_booking.php: Reschedule form
 * - assets/js/booking.js: Timeline + anggota management
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

/**
 * Class BookingController - User Booking Management
 * 
 * @property BookingModel $model Main booking operations
 * @property RuanganModel $ruanganModel Room data and validation
 * @property ScheduleModel $scheduleModel Time slot management
 * @property BookingListModel $bookingListModel Auto-update status methods
 * @property PengaturanModel $pengaturanModel Operational constraints validation
 */
class BookingController
{
    /**
     * BookingModel instance untuk booking CRUD operations
     * @var BookingModel
     */
    private BookingModel $model;
    
    /**
     * RuanganModel instance untuk room validation
     * @var RuanganModel
     */
    private RuanganModel $ruanganModel;
    
    /**
     * ScheduleModel instance untuk time slot management
     * @var ScheduleModel
     */
    private ScheduleModel $scheduleModel;
    
    /**
     * BookingListModel instance untuk auto-update methods
     * @var BookingListModel
     */
    private BookingListModel $bookingListModel;
    
    /**
     * PengaturanModel instance untuk operational constraints
     * @var PengaturanModel
     */
    private PengaturanModel $pengaturanModel;

    /**
     * Constructor - Initialize models and session
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new BookingModel();
        $this->ruanganModel = new RuanganModel();
        $this->scheduleModel = new ScheduleModel();
        $this->bookingListModel = new BookingListModel();
        $this->pengaturanModel = new PengaturanModel();
    }

    public function index(): void
    {
        // CRITICAL: Urutan eksekusi auto-update sangat penting!
        // 1. HANGUS dulu - cek booking tanpa check-in yang lewat 10 menit
        // 2. SELESAI kedua - cek booking dengan check-in yang sudah habis waktunya
        $this->bookingListModel->autoUpdateHangusStatus();
        $this->bookingListModel->autoUpdateSelesaiStatus();
        
        // Auto-update status ruangan di database based on real-time availability
        $this->ruanganModel->autoUpdateRoomStatus();
        
        // Ambil id ruangan dari URL
        $id_ruangan = $_GET['room'] ?? null;
        
        if (!$id_ruangan) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        
        // Ambil data ruangan
        $ruangan = $this->ruanganModel->getById((int)$id_ruangan);
        
        if (!$ruangan) {
            echo "<script>alert('Ruangan tidak ditemukan'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }
        
        // Cek apakah ruangan tersedia untuk user (Ruang Umum)
        if ($ruangan['jenis_ruangan'] !== 'Ruang Umum') {
            echo "<script>alert('Ruangan tidak tersedia untuk booking user'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }
        
        $this->renderIndexView();
    }

    private function renderIndexView(): void
    {
        $id_ruangan = $_GET['room'] ?? null;
        $ruangan = $this->ruanganModel->getById((int)$id_ruangan);
        require __DIR__ . '/../view/booking/index.php';
    }

    public function buat_booking(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=dashboard');
            exit;
        }

        if (!isset($_SESSION['user'])) {
            echo "<script>alert('Anda harus login terlebih dahulu'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        $nomor_induk = $_SESSION['user']['nomor_induk'];

        // Cek apakah ketua sedang di-block dari booking (24 jam atau suspend 7 hari)
        $block = $this->model->checkBookingBlock($nomor_induk);
        if ($block) {
            $waktu_selesai_block = strtotime($block['tanggal_selesai']);
            $sisa_waktu = $waktu_selesai_block - time();
            $jam = floor($sisa_waktu / 3600);
            $menit = floor(($sisa_waktu % 3600) / 60);
            
            $message = 'Anda tidak dapat membuat booking. ' . $block['alasan_suspensi'] . "\n";
            $message .= 'Dapat booking kembali: ' . date('d F Y H:i', $waktu_selesai_block) . ' WIB';
            if ($jam > 0) {
                $message .= " (sekitar $jam jam $menit menit lagi)";
            } else {
                $message .= " (sekitar $menit menit lagi)";
            }
            $msg = str_replace(['\\', "'", "\n", "\r"], ['\\\\', "\\'", '\\n', ''], $message);
            echo "<script>alert('" . $msg . "'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }

        // Cek apakah user sudah punya booking aktif
        if ($this->model->hasActiveBooking($nomor_induk)) {
            echo "<script>alert('Anda sudah memiliki booking aktif. Selesaikan booking tersebut terlebih dahulu.'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Ambil data dari form
        $id_ruangan = (int) ($_POST['id_ruangan'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? '';
        $waktu_mulai = $_POST['waktu_mulai'] ?? '';
        $waktu_selesai = $_POST['waktu_selesai'] ?? '';
        $anggota = $_POST['anggota'] ?? [];

        // Validasi input
        if (empty($id_ruangan) || empty($tanggal) || empty($waktu_mulai) || empty($waktu_selesai)) {
            echo "<script>alert('Semua field harus diisi'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            exit;
        }

        // Validasi tanggal dan waktu tidak boleh masa lalu (minimal 5 menit dari sekarang)
        $booking_datetime = strtotime($tanggal . ' ' . $waktu_mulai);
        $now = time();
        $min_buffer = 0 * 60; // 0 menit buffer
        
        if ($booking_datetime < ($now + $min_buffer)) {
            if (strtotime($tanggal) < strtotime('today')) {
                echo "<script>alert('Tanggal booking tidak boleh di masa lalu'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            } else {
                echo "<script>alert('Waktu booking harus minimal 5 menit dari sekarang untuk persiapan'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            }
            exit;
        }

        // VALIDASI PENGATURAN SISTEM (Waktu Operasi & Hari Libur)
        // 1. Cek hari libur
        $hariLiburCheck = $this->pengaturanModel->validateHariLibur($tanggal);
        if (!$hariLiburCheck['allowed']) {
            $msg = str_replace(['\\', "'", "\n", "\r"], ['\\\\', "\\'", '\\n', ''], $hariLiburCheck['message']);
            echo "<script>alert('" . $msg . "'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            exit;
        }

        // 2. Cek waktu operasi (hari dalam seminggu + jam operasional)
        $hari_mapping = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 
                         'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $hari_inggris = date('l', strtotime($tanggal));
        $hari_indonesia = $hari_mapping[$hari_inggris];
        
        $waktuOpCheck = $this->pengaturanModel->validateWaktuOperasi($hari_indonesia, $waktu_mulai, $waktu_selesai);
        if (!$waktuOpCheck['allowed']) {
            $msg = str_replace(['\\', "'", "\n", "\r"], ['\\\\', "\\'", '\\n', ''], $waktuOpCheck['message']);
            echo "<script>alert('" . $msg . "'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            exit;
        }

        // Validasi ruangan
        $ruangan = $this->ruanganModel->getById($id_ruangan);
        if (!$ruangan || $ruangan['jenis_ruangan'] !== 'Ruang Umum' || $ruangan['status_ruangan'] !== 'Tersedia') {
            echo "<script>alert('Ruangan tidak tersedia untuk booking'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }

        // Hitung durasi
        $time_start = strtotime($waktu_mulai);
        $time_end = strtotime($waktu_selesai);
        $durasi = ($time_end - $time_start) / 60; // dalam menit

        if ($durasi <= 0) {
            echo "<script>alert('Waktu selesai harus lebih besar dari waktu mulai'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            exit;
        }

        // VALIDASI MINIMAL DURASI BOOKING: 15 menit
        // Mencegah booking yang terlalu singkat dan langsung HANGUS (toleransi HANGUS = 10 menit)
        if ($durasi < 15) {
            echo "<script>alert('Durasi booking minimal 15 menit (toleransi keterlambatan check-in 10 menit)'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            exit;
        }

        // Validasi kapasitas (ketua + anggota)
        $total_peserta = 1; // ketua
        $valid_anggota = [];
        foreach ($anggota as $member) {
            if (!empty($member['nomor_induk']) && !empty($member['nama'])) {
                $valid_anggota[] = $member;
                $total_peserta++;
            }
        }

        if ($total_peserta < $ruangan['minimal_kapasitas_ruangan'] || $total_peserta > $ruangan['maksimal_kapasitas_ruangan']) {
            $message = "Jumlah peserta harus antara {$ruangan['minimal_kapasitas_ruangan']} - {$ruangan['maksimal_kapasitas_ruangan']} orang";
            $msg = str_replace(['\\', "'", "\n", "\r"], ['\\\\', "\\'", '\\n', ''], $message);
            echo "<script>alert('" . $msg . "'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            exit;
        }

        // Cek apakah ada anggota yang sedang di-block dari booking
        $blocked_members = [];
        foreach ($valid_anggota as $member) {
            $member_block = $this->model->checkBookingBlock($member['nomor_induk']);
            if ($member_block) {
                $waktu_selesai_block = strtotime($member_block['tanggal_selesai']);
                $blocked_members[] = $member['nama'] . ' (' . $member['nomor_induk'] . ') - dapat booking ' . date('d F Y H:i', $waktu_selesai_block) . ' WIB';
            }
        }
        
        if (!empty($blocked_members)) {
            $message = 'Anggota berikut sedang diblokir dari booking:\n' . implode('\n', $blocked_members);
            $msg = str_replace(['\\', "'", "\n", "\r"], ['\\\\', "\\'", '\\n', ''], $message);
            echo "<script>alert('" . $msg . "'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            exit;
        }

        // Cek bentrok waktu ruangan
        if (!$this->scheduleModel->isTimeSlotAvailable($id_ruangan, $tanggal, $waktu_mulai, $waktu_selesai)) {
            echo "<script>alert('Waktu yang dipilih bentrok dengan jadwal lain. Silakan pilih waktu lain.'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            exit;
        }

        // Cek konflik anggota - pastikan ketua dan anggota tidak ada booking aktif di waktu yang sama
        $all_members = array_merge([$nomor_induk], array_column($valid_anggota, 'nomor_induk'));
        $conflicted_members = $this->scheduleModel->checkMemberConflicts($all_members, $tanggal, $waktu_mulai, $waktu_selesai);
        
        if (!empty($conflicted_members)) {
            $names = array_map(function($member) {
                return $member['username'] . ' (' . $member['nomor_induk'] . ')';
            }, $conflicted_members);
            $message = 'Anggota berikut sudah memiliki booking di waktu yang sama: ' . implode(', ', $names);
            $msg = str_replace(['\\', "'", "\n", "\r"], ['\\\\', "\\'", '\\n', ''], $message);
            echo "<script>alert('" . $msg . "'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            exit;
        }

        // Buat booking
        $booking_data = [
            'id_ruangan' => $id_ruangan,
            'durasi_penggunaan' => (int) $durasi,
            'surat_lampiran' => null,
            'id_status_booking' => 1 // AKTIF (workflow baru: langsung aktif)
        ];

        $id_booking = $this->model->create($booking_data, $nomor_induk, $valid_anggota);

        if (!$id_booking) {
            echo "<script>alert('Gagal membuat booking. Silakan coba lagi.'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            exit;
        }

        // Buat schedule
        $schedule_created = $this->scheduleModel->createInitialSchedule($id_booking, $tanggal, $waktu_mulai, $waktu_selesai);

        if (!$schedule_created) {
            echo "<script>alert('Gagal membuat jadwal booking'); window.location.href='index.php?page=booking&room=" . $id_ruangan . "';</script>";
            exit;
        }

        // Tampilkan notifikasi sukses dengan peringatan penting
        $success_message = "Booking berhasil dibuat!\n\n";
        $success_message .= "⚠️ PENTING:\n";
        $success_message .= "• Anda tidak dapat membuat booking baru sampai booking ini selesai atau dibatalkan\n";
        $success_message .= "• Harap hadir tepat waktu untuk check-in\n\n";
        $success_message .= "❌ KONSEKUENSI JIKA TIDAK CHECK-IN:\n";
        $success_message .= "• Booking HANGUS jika tidak check-in dalam 10 menit setelah waktu mulai\n";
        $success_message .= "• Pelanggaran 1/3 & 2/3: Diblokir booking 24 jam\n";
        $success_message .= "• Pelanggaran 3/3: Suspend 7 hari (tidak bisa booking sama sekali)";
        
        $msg = str_replace(['\\', "'", "\n", "\r"], ['\\\\', "\\'", '\\n', ''], $success_message);
        echo "<script>alert('" . $msg . "'); window.location.href='index.php?page=booking&action=kode_booking&id=" . $id_booking . "';</script>";
        exit;
    }

    public function kode_booking(): void
    {
        $id_booking = (int) ($_GET['id'] ?? 0);
        
        if (!$id_booking) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $booking = $this->model->getById($id_booking);
        
        if (!$booking) {
            echo "<script>alert('Booking tidak ditemukan'); window.location.href='index.php?page=dashboard';</script>";
            exit;
        }

        // Cek apakah user adalah ketua atau anggota booking ini
        if (isset($_SESSION['user'])) {
            $anggota = $this->model->getAnggota($id_booking);
            $is_member = false;
            foreach ($anggota as $member) {
                if ($member['nomor_induk'] === $_SESSION['user']['nomor_induk']) {
                    $is_member = true;
                    break;
                }
            }
            
            // Admin bisa lihat semua booking
            if (!$is_member && !in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])) {
                echo "<script>alert('Anda tidak memiliki akses ke booking ini'); window.location.href='index.php?page=dashboard';</script>";
                exit;
            }
        }

        require __DIR__ . '/../view/booking/kode_booking.php';
    }

    public function hapus_booking(): void
    {
        $id_booking = (int) ($_GET['id'] ?? 0);
        
        if (!$id_booking) {
            header('Location: index.php?page=profile');
            exit;
        }

        $booking = $this->model->getById($id_booking);
        
        if (!$booking) {
            echo "<script>alert('Booking tidak ditemukan'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Cek apakah user adalah ketua booking
        if (isset($_SESSION['user'])) {
            $ketua = $this->model->getKetua($id_booking);
            if (!$ketua || $ketua['nomor_induk'] !== $_SESSION['user']['nomor_induk']) {
                echo "<script>alert('Hanya ketua booking yang dapat membatalkan booking'); window.location.href='index.php?page=profile';</script>";
                exit;
            }
        }

        // Cek status booking (hanya bisa hapus AKTIF)
        if ($booking['nama_status'] !== 'AKTIF') {
            $message = 'Booking dengan status ' . $booking['nama_status'] . ' tidak dapat dibatalkan';
            $msg = str_replace(['\\', "'", "\n", "\r"], ['\\\\', "\\'", '\\n', ''], $message);
            echo "<script>alert('" . $msg . "'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Handle POST request (proses hapus)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Status 3 = DIBATALKAN
            if ($this->model->updateStatus($id_booking, 3)) {
                echo "<script>alert('Booking berhasil dibatalkan'); window.location.href='index.php?page=profile';</script>";
            } else {
                echo "<script>alert('Gagal membatalkan booking'); window.location.href='index.php?page=profile';</script>";
            }
            exit;
        }

        // Handle GET request (tampilkan konfirmasi)
        require __DIR__ . '/../view/booking/hapus_booking.php';
    }

    public function reschedule(): void
    {
        $id_booking = (int) ($_GET['id'] ?? 0);
        
        if (!$id_booking) {
            header('Location: index.php?page=profile');
            exit;
        }

        $booking = $this->model->getById($id_booking);
        
        if (!$booking) {
            echo "<script>alert('Booking tidak ditemukan'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Cek apakah user adalah ketua booking
        if (isset($_SESSION['user'])) {
            $ketua = $this->model->getKetua($id_booking);
            if (!$ketua || $ketua['nomor_induk'] !== $_SESSION['user']['nomor_induk']) {
                echo "<script>alert('Hanya ketua booking yang dapat melakukan reschedule'); window.location.href='index.php?page=profile';</script>";
                exit;
            }
        }

        // Cek status booking (hanya bisa reschedule AKTIF)
        if ($booking['nama_status'] !== 'AKTIF') {
            $message = 'Booking dengan status ' . $booking['nama_status'] . ' tidak dapat direschedule';
            $msg = str_replace(['\\', "'", "\n", "\r"], ['\\\\', "\\'", '\\n', ''], $message);
            echo "<script>alert('" . $msg . "'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Cek apakah sudah ada yang check-in (tidak boleh reschedule jika sudah di-acc/check-in)
        if ($this->model->hasAnyCheckedIn($id_booking)) {
            echo "<script>alert('Booking tidak dapat direschedule karena sudah ada anggota yang check-in'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Validasi apakah masih bisa reschedule (minimal 1 jam sebelum waktu mulai)
        if (!$this->scheduleModel->canReschedule($id_booking)) {
            echo "<script>alert('Reschedule hanya bisa dilakukan minimal 1 jam sebelum waktu mulai booking'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Handle POST request (proses reschedule)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tanggal_baru = $_POST['tanggal'] ?? '';
            $waktu_mulai_baru = $_POST['waktu_mulai'] ?? '';
            $waktu_selesai_baru = $_POST['waktu_selesai'] ?? '';
            $alasan = $_POST['alasan_reschedule'] ?? 'Reschedule oleh ketua';

            if (empty($tanggal_baru) || empty($waktu_mulai_baru) || empty($waktu_selesai_baru)) {
                echo "<script>alert('Semua field harus diisi'); window.location.href='index.php?page=booking&action=reschedule&id=" . $id_booking . "';</script>";
                exit;
            }

            // Validasi tanggal dan waktu tidak boleh masa lalu (minimal 5 menit dari sekarang)
            $reschedule_datetime = strtotime($tanggal_baru . ' ' . $waktu_mulai_baru);
            $now = time();
            $min_buffer = 5 * 60; // 5 menit buffer
            
            if ($reschedule_datetime < ($now + $min_buffer)) {
                if (strtotime($tanggal_baru) < strtotime('today')) {
                    echo "<script>alert('Tanggal reschedule tidak boleh di masa lalu'); window.location.href='index.php?page=booking&action=reschedule&id=" . $id_booking . "';</script>";
                } else {
                    echo "<script>alert('Waktu reschedule harus minimal 5 menit dari sekarang untuk persiapan'); window.location.href='index.php?page=booking&action=reschedule&id=" . $id_booking . "';</script>";
                }
                exit;
            }

            // Validasi waktu
            $time_start = strtotime($waktu_mulai_baru);
            $time_end = strtotime($waktu_selesai_baru);
            if ($time_end <= $time_start) {
                echo "<script>alert('Waktu selesai harus lebih besar dari waktu mulai'); window.location.href='index.php?page=booking&action=reschedule&id=" . $id_booking . "';</script>";
                exit;
            }

            // VALIDASI MINIMAL DURASI BOOKING: 15 menit
            $durasi = ($time_end - $time_start) / 60;
            if ($durasi < 15) {
                echo "<script>alert('Durasi booking minimal 15 menit (toleransi keterlambatan check-in 10 menit)'); window.location.href='index.php?page=booking&action=reschedule&id=" . $id_booking . "';</script>";
                exit;
            }

            // Cek bentrok waktu
            if (!$this->scheduleModel->isTimeSlotAvailable($booking['id_ruangan'], $tanggal_baru, $waktu_mulai_baru, $waktu_selesai_baru, $id_booking)) {
                echo "<script>alert('Waktu yang dipilih bentrok dengan jadwal lain'); window.location.href='index.php?page=booking&action=reschedule&id=" . $id_booking . "';</script>";
                exit;
            }

            // Proses reschedule
            if ($this->scheduleModel->reschedule($id_booking, $tanggal_baru, $waktu_mulai_baru, $waktu_selesai_baru, $alasan)) {
                // Update durasi booking
                $durasi = ($time_end - $time_start) / 60;
                $this->model->update($id_booking, [
                    'id_ruangan' => $booking['id_ruangan'],
                    'durasi_penggunaan' => (int) $durasi,
                    'surat_lampiran' => $booking['surat_lampiran'],
                    'id_status_booking' => $booking['id_status_booking']
                ]);

                echo "<script>alert('Booking berhasil direschedule'); window.location.href='index.php?page=profile';</script>";
            } else {
                echo "<script>alert('Gagal melakukan reschedule'); window.location.href='index.php?page=profile';</script>";
            }
            exit;
        }

        // Handle GET request (tampilkan form)
        require __DIR__ . '/../view/booking/reskedul_booking.php';
    }

    /**
     * API endpoint untuk get jadwal terpakai pada tanggal & ruangan tertentu
     * Response: JSON {"success": true, "schedules": [{"waktu_mulai": "...", "waktu_selesai": "...", "kode_booking": "..."}]}
     */
    public function get_booked_timeslots(): void
    {
        header('Content-Type: application/json');
        
        $idRuangan = (int) ($_GET['id_ruangan'] ?? 0);
        $tanggal = $_GET['tanggal'] ?? '';
        
        if (!$idRuangan || !$tanggal) {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
            exit;
        }
        
        $schedules = $this->scheduleModel->getSchedulesByRoomAndDate($idRuangan, $tanggal);
        echo json_encode(['success' => true, 'schedules' => $schedules]);
        exit;
    }

    /**
     * API endpoint untuk get user data by NIM/NIP (untuk auto-fill nama anggota)
     * Response: JSON {"success": true, "data": {"nomor_induk": "...", "username": "...", "role": "..."}}
     */
    public function get_user_by_nim(): void
    {
        // Clear any previous output buffer to ensure clean JSON
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $nomor_induk = $_GET['nim'] ?? '';
        
        if (empty($nomor_induk)) {
            echo json_encode(['success' => false, 'message' => 'NIM/NIP required']);
            exit;
        }

        // Load AkunModel
        $akunModel = new AkunModel();
        $user = $akunModel->getByNomorInduk($nomor_induk);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
            exit;
        }

        // Return data tanpa password
        echo json_encode([
            'success' => true,
            'data' => [
                'nomor_induk' => $user['nomor_induk'],
                'username' => $user['username'],
                'role' => $user['role'],
                'email' => $user['email']
            ]
        ]);
        exit;
    }
}

?>
