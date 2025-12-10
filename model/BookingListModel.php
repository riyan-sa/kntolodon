<?php

require_once __DIR__ . '/../config/Email.php';

/**
 * ============================================================================
 * BOOKINGLISTMODEL.PHP - Admin Booking List & Auto-Update Model
 * ============================================================================
 * 
 * Model untuk admin booking list dengan advanced filtering dan auto-update.
 * Menampilkan SEMUA booking (Ruang Umum + Ruang Rapat) untuk admin management.
 * Implements automated status updates dan violation tracking.
 * 
 * FUNGSI UTAMA:
 * 1. READ - Fetch all bookings dengan comprehensive details
 * 2. FILTER - Advanced filtering (room, user, date, status)
 * 3. AUTO-UPDATE SELESAI - Auto-set SELESAI status after waktu_selesai
 * 4. AUTO-UPDATE HANGUS - Auto-set HANGUS status if no check-in
 * 5. CHECK-IN MANAGEMENT - Bulk check-in operations
 * 6. VIOLATION TRACKING - Track HANGUS violations dan suspensions
 * 7. EMAIL NOTIFICATIONS - Send suspension alerts
 * 
 * CRITICAL AUTO-UPDATE METHODS:
 * Must be called at controller entry points untuk maintain system integrity:
 * - autoUpdateSelesaiStatus(): AKTIF → SELESAI (after waktu_selesai + check-in exists)
 * - autoUpdateHangusStatus(): AKTIF → HANGUS (no check-in after 10 min late)
 * - Called in: AdminController::booking_list(), BookingController::index()
 * 
 * SELESAI LOGIC:
 * Booking becomes SELESAI if:
 * - Status = AKTIF
 * - Current time > waktu_selesai
 * - At least 1 member checked-in OR booking is external (nama_instansi exists)
 * 
 * HANGUS LOGIC:
 * Booking becomes HANGUS if:
 * - Status = AKTIF
 * - Current time > waktu_mulai + 10 minutes
 * - ALL members NOT checked-in (100% requirement)
 * - NOT external booking (external bookings cannot be HANGUS)
 * 
 * VIOLATION SYSTEM:
 * - First HANGUS in 30 days → 24-hour booking block
 * - Third HANGUS in 30 days → 7-day suspension + email notification
 * - Stored in: pelanggaran_suspensi table
 * - Email sent to: user's registered email (from akun table)
 * 
 * EMAIL NOTIFICATION:
 * - Trigger: 3rd HANGUS violation (7-day suspension)
 * - Sender: bookez.web@gmail.com (via SMTP)
 * - Template: "Anda telah diskors selama 7 hari karena 3 kali HANGUS dalam 30 hari"
 * - Config: config/Email.php constants
 * 
 * CHECK-IN TRACKING:
 * - Per anggota: anggota_booking.is_checked_in, waktu_check_in
 * - Bulk check-in: checkInBulk() for group check-ins
 * - Validation: Cannot check-in after waktu_selesai
 * 
 * BOOKING LIST DISPLAY:
 * - Shows: Ruang Umum (user bookings) + Ruang Rapat (external bookings)
 * - Name display: COALESCE(nama_instansi, ketua.username)
 * - Sorting: tanggal DESC, waktu_mulai DESC, id_booking DESC
 * 
 * FILTER SUPPORT:
 * - Room: Filter by id_ruangan or 'all'
 * - User: Search ketua username atau nama_instansi
 * - Date: Filter by tanggal_schedule
 * - Status: Filter by status name (AKTIF/SELESAI/DIBATALKAN/HANGUS) or 'all'
 * 
 * USAGE PATTERNS:
 * - AdminController::booking_list() - Admin booking management
 * - Called at entry points untuk automated status updates
 * - Background cron job can also trigger auto-updates
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

/**
 * Class BookingListModel - Booking List dengan Auto-Update
 * 
 * @property PDO $pdo Database connection instance
 */
class BookingListModel
{
    /**
     * PDO instance untuk database operations
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructor - Initialize PDO connection
     */
    public function __construct()
    {
        $koneksi = new Koneksi();
        $this->pdo = $koneksi->getPdo();
    }

    // ==================== READ ====================
    
    /**
     * Ambil semua booking dengan info lengkap
     */
    public function getAll(): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai,
                COALESCE(b.nama_instansi, ketua.username) as nama_peminjam
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                LEFT JOIN (
                    SELECT ab.id_booking, a.username
                    FROM anggota_booking ab
                    JOIN akun a ON ab.nomor_induk = a.nomor_induk
                    WHERE ab.is_ketua = 1
                ) ketua ON b.id_booking = ketua.id_booking
                ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC, b.id_booking DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil booking berdasarkan ID dengan detail lengkap
     */
    public function getById(int $id): array|false
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai,
                COALESCE(b.nama_instansi, ketua.username) as nama_peminjam,
                ketua.nomor_induk as nomor_induk_ketua
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                LEFT JOIN (
                    SELECT ab.id_booking, a.username, ab.nomor_induk
                    FROM anggota_booking ab
                    JOIN akun a ON ab.nomor_induk = a.nomor_induk
                    WHERE ab.is_ketua = 1
                ) ketua ON b.id_booking = ketua.id_booking
                WHERE b.id_booking = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ==================== FILTER / SEARCH ====================
    
    /**
     * Filter booking list dengan berbagai kriteria
     * Digunakan untuk halaman booking_list.php admin
     * Support filter kombinasi: ruang, nama ketua, tanggal, status
     * 
     * Note: Workflow baru (No PENDING):
     * - Booking langsung AKTIF
     * - Schedule semua status AKTIF (no approval)
     * - Auto-check HANGUS dilakukan di background
     */
    public function filter(array $filters = []): array
    {
        $sql = "SELECT b.id_booking, b.kode_booking, b.durasi_penggunaan,
                b.nama_instansi, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai,
                COALESCE(b.nama_instansi, ketua.username) as nama_peminjam
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                LEFT JOIN (
                    SELECT ab.id_booking, a.username
                    FROM anggota_booking ab
                    JOIN akun a ON ab.nomor_induk = a.nomor_induk
                    WHERE ab.is_ketua = 1
                ) ketua ON b.id_booking = ketua.id_booking
                WHERE 1=1";
        $params = [];

        // Filter by ruangan (support both id and 'all')
        if (!empty($filters['ruang']) && $filters['ruang'] !== 'all') {
            $sql .= " AND b.id_ruangan = :id_ruangan";
            $params[':id_ruangan'] = $filters['ruang'];
        }

        // Filter by nama ketua (search di username atau nama_instansi)
        if (!empty($filters['nama'])) {
            $sql .= " AND (ketua.username LIKE :nama OR b.nama_instansi LIKE :nama)";
            $params[':nama'] = '%' . $filters['nama'] . '%';
        }

        // Filter by tanggal
        if (!empty($filters['tanggal'])) {
            $sql .= " AND s.tanggal_schedule = :tanggal";
            $params[':tanggal'] = $filters['tanggal'];
        }

        // Filter by status (support both lowercase status names and 'all')
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND LOWER(sb.nama_status) = LOWER(:status)";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC, b.id_booking DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Auto-update booking status menjadi SELESAI
     * Jika booking AKTIF & sudah melewati waktu_selesai & ADA YANG CHECK-IN
     * 
     * CRITICAL LOGIC:
     * - HANYA ubah ke SELESAI jika minimal ada 1 anggota yang check-in
     * - Jika TIDAK ADA yang check-in → Biarkan HANGUS (dihandle oleh autoUpdateHangusStatus)
     * - Booking eksternal (punya nama_instansi) otomatis SELESAI tanpa cek check-in
     * 
     * Called by cron job atau saat admin/user load page
     */
    public function autoUpdateSelesaiStatus(): int
    {
        // Ambil booking yang akan jadi SELESAI
        // Kondisi: status AKTIF DAN sudah melewati waktu_selesai DAN (ada yang check-in ATAU booking eksternal)
        $sql = "SELECT DISTINCT b.id_booking
                FROM booking b
                JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE b.id_status_booking = 1
                AND CONCAT(s.tanggal_schedule, ' ', s.waktu_selesai) < NOW()
                AND (
                    b.nama_instansi IS NOT NULL
                    OR b.id_booking IN (
                        SELECT ab.id_booking 
                        FROM anggota_booking ab 
                        WHERE ab.is_checked_in = 1
                    )
                )";
        $stmt = $this->pdo->query($sql);
        $selesaiBookings = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($selesaiBookings)) {
            return 0;
        }
        
        // Update status jadi SELESAI (id_status_booking = 2)
        $placeholders = implode(',', array_fill(0, count($selesaiBookings), '?'));
        $updateSql = "UPDATE booking SET id_status_booking = 2 WHERE id_booking IN ($placeholders)";
        $updateStmt = $this->pdo->prepare($updateSql);
        $updateStmt->execute($selesaiBookings);
        
        return count($selesaiBookings);
    }
    
    /**
     * Auto-update booking status menjadi HANGUS
     * Jika booking AKTIF & lewat 10 menit dari waktu_mulai & SEMUA anggota belum check-in (100% requirement)
     * Otomatis tambah pelanggaran untuk SEMUA anggota & suspend jika sudah 3x
     * 
     * NOTE: Booking eksternal (punya nama_instansi) tidak bisa HANGUS karena tidak ada anggota
     * 
     * Called by cron job atau saat admin/user load page
     */
    public function autoUpdateHangusStatus(): int
    {
        // Ambil booking yang akan jadi HANGUS
        // Booking HANGUS jika: SEMUA anggota belum check-in setelah 10 menit dari waktu mulai
        // Exclude booking eksternal (yang punya nama_instansi)
        $sql = "SELECT b.id_booking
                FROM booking b
                JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE b.id_status_booking = 1
                AND b.nama_instansi IS NULL
                AND CONCAT(s.tanggal_schedule, ' ', s.waktu_mulai) < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                AND b.id_booking IN (
                    SELECT ab.id_booking
                    FROM anggota_booking ab
                    GROUP BY ab.id_booking
                    HAVING SUM(ab.is_checked_in) = 0
                )";
        $stmt = $this->pdo->query($sql);
        $hangusBookings = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($hangusBookings)) {
            return 0;
        }
        
        // Update status jadi HANGUS
        $placeholders = implode(',', array_fill(0, count($hangusBookings), '?'));
        $updateSql = "UPDATE booking SET id_status_booking = 4 WHERE id_booking IN ($placeholders)";
        $updateStmt = $this->pdo->prepare($updateSql);
        $updateStmt->execute($hangusBookings);
        
        // Tambah pelanggaran untuk SEMUA anggota di booking yang HANGUS
        foreach ($hangusBookings as $idBooking) {
            $this->addViolationsForHangusBooking($idBooking);
        }
        
        return count($hangusBookings);
    }
    
    /**
     * Auto check-in untuk booking eksternal di hari H
     * Booking eksternal tidak punya anggota, jadi tidak bisa HANGUS
     * Langsung set semua booking eksternal hari ini sebagai "sudah check-in"
     */
    public function autoCheckInExternalBookings(): int
    {
        // Update: Tidak ada action yang perlu dilakukan
        // Booking eksternal tidak punya anggota_booking, jadi tidak ada yang perlu di-check-in
        // Status tetap AKTIF sampai admin/super admin selesaikan manual atau auto-update ke SELESAI
        return 0;
    }
    
    /**
     * Tambah pelanggaran untuk semua anggota booking yang HANGUS
     * Cek apakah sudah 3x dalam 30 hari, jika ya suspend 1 minggu
     */
    private function addViolationsForHangusBooking(int $idBooking): void
    {
        // Ambil semua anggota booking
        $sql = "SELECT ab.nomor_induk, a.username, a.email
                FROM anggota_booking ab
                JOIN akun a ON ab.nomor_induk = a.nomor_induk
                WHERE ab.id_booking = :id_booking";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_booking' => $idBooking]);
        $members = $stmt->fetchAll();
        
        foreach ($members as $member) {
            $nomorInduk = $member['nomor_induk'];
            
            // Hitung pelanggaran yang SUDAH ADA dalam 30 hari terakhir
            $countSql = "SELECT COUNT(*) FROM pelanggaran_suspensi 
                        WHERE nomor_induk = :nomor_induk 
                        AND jenis_pelanggaran = 'Keterlambatan Check-in'
                        AND tanggal_mulai >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute([':nomor_induk' => $nomorInduk]);
            $existingCount = (int) $countStmt->fetchColumn();
            
            // Total pelanggaran setelah ditambah yang baru ini
            $totalCount = $existingCount + 1;
            
            // Tentukan alasan dan durasi block/suspensi
            if ($totalCount >= 3) {
                // Kena suspend 7 hari (pelanggaran ketiga)
                $alasan = "Melakukan 3x No-Show. Akun di-suspend 7 hari.";
                $tanggalMulai = date('Y-m-d');
                $tanggalSelesai = date('Y-m-d', strtotime('+7 days'));
            } else {
                // Block booking 24 jam (pelanggaran ke-1 atau ke-2)
                $alasan = "Tidak hadir ($totalCount/3). Diblokir booking 24 jam.";
                $tanggalMulai = date('Y-m-d H:i:s');
                $tanggalSelesai = date('Y-m-d H:i:s', strtotime('+24 hours'));
            }
            
            // Insert pelanggaran baru
            $insertSql = "INSERT INTO pelanggaran_suspensi (nomor_induk, jenis_pelanggaran, alasan_suspensi, tanggal_mulai, tanggal_selesai)
                        VALUES (:nomor_induk, 'Keterlambatan Check-in', :alasan, :tanggal_mulai, :tanggal_selesai)";
            $insertStmt = $this->pdo->prepare($insertSql);
            $insertStmt->execute([
                ':nomor_induk' => $nomorInduk,
                ':alasan' => $alasan,
                ':tanggal_mulai' => $tanggalMulai,
                ':tanggal_selesai' => $tanggalSelesai
            ]);
            
            // Kirim email notifikasi
            $this->sendViolationEmail($member['email'], $member['username'], $totalCount, $tanggalSelesai);
        }
    }
    
    /**
     * Kirim email notifikasi pelanggaran
     * Email sender diambil dari config/Email.php
     */
    private function sendViolationEmail(string $email, string $username, int $count, string $tanggalSelesai): void
    {
        $subject = "⚠️ Peringatan Pelanggaran BookEZ - Keterlambatan Check-in";
        
        // Build message dengan format yang lebih baik
        if ($count >= 3) {
            $message = "Yth. $username,\n\n";
            $message .= "==========================================\n";
            $message .= "        PEMBERITAHUAN SUSPEND AKUN        \n";
            $message .= "==========================================\n\n";
            $message .= "Akun Anda telah melakukan 3x pelanggaran No-Show (tidak check-in tepat waktu).\n\n";
            $message .= "📅 Status: SUSPEND\n";
            $message .= "📅 Suspend hingga: " . date('d F Y', strtotime($tanggalSelesai)) . "\n\n";
            $message .= "⚠️ Selama masa suspend, Anda TIDAK DAPAT membuat booking baru.\n";
            $message .= "⚠️ Harap hadir tepat waktu pada booking Anda berikutnya.\n";
            $message .= MAIL_SIGNATURE;
        } else {
            $message = "Yth. $username,\n\n";
            $message .= "==========================================\n";
            $message .= "         PERINGATAN PELANGGARAN ($count/3)       \n";
            $message .= "==========================================\n\n";
            $message .= "Anda telah melakukan keterlambatan check-in pada booking Anda.\n\n";
            $message .= "📊 Status Pelanggaran: $count dari 3 pelanggaran\n";
            $message .= "📅 Periode Perhitungan: 30 hari terakhir\n";
            $message .= "🚫 Akun Anda diblokir dari membuat booking selama 24 jam\n";
            $message .= "📅 Dapat booking kembali: " . date('d F Y H:i', strtotime($tanggalSelesai)) . " WIB\n\n";
            $message .= "⚠️ PERINGATAN PENTING:\n";
            $message .= "Jika mencapai 3x pelanggaran dalam 30 hari, akun Anda akan di-suspend selama 7 hari.\n\n";
            $message .= "💡 Tips: Hadir tepat waktu sesuai jadwal booking Anda.\n";
            $message .= MAIL_SIGNATURE;
        }
        
        // Headers menggunakan config
        $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">\r\n";
        $headers .= "Reply-To: " . MAIL_REPLY_TO . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // Kirim email
        $result = @mail($email, $subject, $message, $headers);
        
        // Log jika gagal (opsional untuk debugging)
        if (!$result) {
            error_log("Failed to send violation email to: $email");
        }
    }

    /**
     * Search booking by keyword (nama, kode, ruangan)
     */
    public function search(string $keyword): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai,
                COALESCE(b.nama_instansi, ketua.username) as nama_peminjam
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                LEFT JOIN (
                    SELECT ab.id_booking, a.username
                    FROM anggota_booking ab
                    JOIN akun a ON ab.nomor_induk = a.nomor_induk
                    WHERE ab.is_ketua = 1
                ) ketua ON b.id_booking = ketua.id_booking
                WHERE (
                    b.kode_booking LIKE :keyword 
                    OR r.nama_ruangan LIKE :keyword 
                    OR ketua.username LIKE :keyword
                    OR b.nama_instansi LIKE :keyword
                )
                ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC, b.id_booking DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':keyword' => '%' . $keyword . '%']);
        return $stmt->fetchAll();
    }

    // ==================== ADMIN CHECK-IN ====================
    
    /**
     * Get booking detail dengan list anggota untuk modal check-in
     */
    public function getBookingDetailForCheckIn(int $idBooking): array|false
    {
        try {
            $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan, sb.nama_status,
                    s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai, s.alasan_reschedule,
                    COALESCE(b.nama_instansi, ketua.username) as nama_peminjam
                    FROM booking b
                    JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                    JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                    LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                    LEFT JOIN (
                        SELECT ab.id_booking, a.username
                        FROM anggota_booking ab
                        JOIN akun a ON ab.nomor_induk = a.nomor_induk
                        WHERE ab.is_ketua = 1
                    ) ketua ON b.id_booking = ketua.id_booking
                    WHERE b.id_booking = :id_booking";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_booking' => $idBooking]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                error_log("BookingListModel::getBookingDetailForCheckIn - Booking ID $idBooking not found");
                return false;
            }
            
            // Ambil anggota
            $sqlAnggota = "SELECT ab.*, a.username, a.email, a.role
                        FROM anggota_booking ab
                        JOIN akun a ON ab.nomor_induk = a.nomor_induk
                        WHERE ab.id_booking = :id_booking
                        ORDER BY ab.is_ketua DESC, a.username ASC";
            $stmtAnggota = $this->pdo->prepare($sqlAnggota);
            $stmtAnggota->execute([':id_booking' => $idBooking]);
            $booking['anggota'] = $stmtAnggota->fetchAll();
            
            // Log jika tidak ada anggota (untuk debugging)
            if (empty($booking['anggota']) && empty($booking['nama_instansi'])) {
                error_log("BookingListModel::getBookingDetailForCheckIn - Warning: Booking ID $idBooking has no members and is not external booking");
            }
            
            return $booking;
        } catch (PDOException $e) {
            error_log("BookingListModel::getBookingDetailForCheckIn - Database error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Admin check-in anggota secara manual
     */
    public function adminCheckInAnggota(int $idBooking, string $nomorInduk): bool
    {
        $sql = "UPDATE anggota_booking 
                SET is_checked_in = 1, waktu_check_in = NOW()
                WHERE id_booking = :id_booking AND nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_booking' => $idBooking,
            ':nomor_induk' => $nomorInduk
        ]);
    }
    
    /**
     * Admin check-in semua anggota sekaligus
     */
    public function adminCheckInAll(int $idBooking): bool
    {
        $sql = "UPDATE anggota_booking 
                SET is_checked_in = 1, waktu_check_in = NOW()
                WHERE id_booking = :id_booking AND is_checked_in = 0";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id_booking' => $idBooking]);
    }

    // ==================== STATISTICS ====================
    
    /**
     * Hitung total booking
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM booking";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Hitung booking berdasarkan status
     */
    public function countByStatus(string $status): int
    {
        $sql = "SELECT COUNT(*) FROM booking b
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                WHERE sb.nama_status = :status";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Hitung booking hari ini (only AKTIF)
     */
    public function countToday(): int
    {
        $sql = "SELECT COUNT(DISTINCT b.id_booking) FROM booking b
                JOIN schedule s ON b.id_booking = s.id_booking
                WHERE s.tanggal_schedule = CURDATE() AND s.status_schedule = 'AKTIF' AND b.id_status_booking = 1";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Ambil booking mendatang (only AKTIF status)
     */
    public function getUpcoming(int $limit = 10): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai,
                COALESCE(b.nama_instansi, ketua.username) as nama_peminjam
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                LEFT JOIN (
                    SELECT ab.id_booking, a.username
                    FROM anggota_booking ab
                    JOIN akun a ON ab.nomor_induk = a.nomor_induk
                    WHERE ab.is_ketua = 1
                ) ketua ON b.id_booking = ketua.id_booking
                WHERE s.tanggal_schedule >= CURDATE() AND sb.nama_status = 'AKTIF'
                ORDER BY s.tanggal_schedule ASC, s.waktu_mulai ASC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Ambil daftar ruangan untuk dropdown filter
     */
    public function getRuanganList(): array
    {
        $sql = "SELECT id_ruangan, nama_ruangan, jenis_ruangan FROM ruangan ORDER BY nama_ruangan ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Ambil daftar status untuk dropdown filter
     */
    public function getStatusList(): array
    {
        $sql = "SELECT id_status_booking, nama_status FROM status_booking ORDER BY id_status_booking ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    // ==================== PAGINATION ====================

    /**
     * Filter booking list dengan pagination
     */
    public function filterWithPagination(array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT b.id_booking, b.kode_booking, b.durasi_penggunaan,
                b.nama_instansi, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai,
                COALESCE(b.nama_instansi, ketua.username) as nama_peminjam
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                LEFT JOIN (
                    SELECT ab.id_booking, a.username
                    FROM anggota_booking ab
                    JOIN akun a ON ab.nomor_induk = a.nomor_induk
                    WHERE ab.is_ketua = 1
                ) ketua ON b.id_booking = ketua.id_booking
                WHERE 1=1";
        $params = [];

        // Filter by ruangan
        if (!empty($filters['ruang']) && $filters['ruang'] !== 'all') {
            $sql .= " AND b.id_ruangan = :id_ruangan";
            $params[':id_ruangan'] = $filters['ruang'];
        }

        // Filter by nama ketua
        if (!empty($filters['nama'])) {
            $sql .= " AND (ketua.username LIKE :nama OR b.nama_instansi LIKE :nama)";
            $params[':nama'] = '%' . $filters['nama'] . '%';
        }

        // Filter by tanggal
        if (!empty($filters['tanggal'])) {
            $sql .= " AND s.tanggal_schedule = :tanggal";
            $params[':tanggal'] = $filters['tanggal'];
        }

        // Filter by status
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND LOWER(sb.nama_status) = LOWER(:status)";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC, b.id_booking DESC";
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Hitung total booking berdasarkan filter (untuk pagination)
     */
    public function countFiltered(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT b.id_booking) as total
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                LEFT JOIN (
                    SELECT ab.id_booking, a.username
                    FROM anggota_booking ab
                    JOIN akun a ON ab.nomor_induk = a.nomor_induk
                    WHERE ab.is_ketua = 1
                ) ketua ON b.id_booking = ketua.id_booking
                WHERE 1=1";
        $params = [];

        if (!empty($filters['ruang']) && $filters['ruang'] !== 'all') {
            $sql .= " AND b.id_ruangan = :id_ruangan";
            $params[':id_ruangan'] = $filters['ruang'];
        }

        if (!empty($filters['nama'])) {
            $sql .= " AND (ketua.username LIKE :nama OR b.nama_instansi LIKE :nama)";
            $params[':nama'] = '%' . $filters['nama'] . '%';
        }

        if (!empty($filters['tanggal'])) {
            $sql .= " AND s.tanggal_schedule = :tanggal";
            $params[':tanggal'] = $filters['tanggal'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND LOWER(sb.nama_status) = LOWER(:status)";
            $params[':status'] = $filters['status'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }
}
