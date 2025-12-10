<?php
/**
 * ============================================================================
 * BOOKINGMODEL.PHP - User Booking Management Model
 * ============================================================================
 * 
 * Model untuk CRUD operations pada booking user (Ruang Umum).
 * Menangani pembuatan booking, manajemen anggota, check-in, reschedule, dan validasi.
 * 
 * FUNGSI UTAMA:
 * 1. CREATE - Buat booking baru dengan ketua dan anggota
 * 2. READ - Fetch bookings dengan berbagai filter (by user, room, date, status)
 * 3. UPDATE - Update booking data, status, check-in anggota
 * 4. DELETE - Batalkan atau hapus booking (soft/hard delete)
 * 5. FILTER - Advanced filtering dengan pagination
 * 6. VALIDATION - Check active booking, booking block, suspend status
 * 
 * DATABASE TABLE: booking
 * PRIMARY KEY: id_booking (auto-increment)
 * FOREIGN KEYS:
 * - id_ruangan → ruangan.id_ruangan (room being booked)
 * - id_status_booking → status_booking.id_status_booking (AKTIF/SELESAI/DIBATALKAN/HANGUS)
 * 
 * RELATED TABLES:
 * - anggota_booking: Many-to-many join (booking ↔ akun)
 * - schedule: Booking time slots (1:N)
 * - feedback: Post-booking feedback (1:1)
 * 
 * RELASI DATABASE:
 * - booking -> ruangan (N:1) via id_ruangan
 * - booking -> status_booking (N:1) via id_status_booking  
 * - booking -> anggota_booking (1:N) via id_booking (booking participants)
 * - booking -> schedule (1:N) via id_booking (time slots, reschedules)
 * - booking -> feedback (1:1) via id_booking (post-completion feedback)
 * 
 * BUSINESS RULES:
 * 1. ONE ACTIVE BOOKING PER USER - User can only have 1 AKTIF booking at a time
 * 2. CAPACITY VALIDATION - Participant count between room min-max capacity
 * 3. ROOM TYPE - Only 'Ruang Umum' for user bookings ('Ruang Rapat' for external)
 * 4. KETUA REQUIRED - Booking must have exactly 1 ketua (logged-in user)
 * 5. CHECK-IN TRACKING - Track check-in per anggota with waktu_check_in
 * 6. VIOLATION SYSTEM - HANGUS status triggers 24h block or 7-day suspension
 * 
 * STATUS LIFECYCLE:
 * - AKTIF (1): Active booking, can check-in
 * - SELESAI (2): Completed (auto-set after waktu_selesai)
 * - DIBATALKAN (3): Cancelled by user
 * - HANGUS (4): No check-in within 10 minutes of waktu_mulai (auto-set)
 * 
 * KODE BOOKING:
 * - Format: 7-character alphanumeric uppercase (e.g., 'A3F9B2C')
 * - Generated via generateKodeBooking() dengan uniqueness check
 * - Used for booking identification dan confirmation
 * 
 * USAGE PATTERNS:
 * - BookingController: create, view, cancel, reschedule user bookings
 * - DashboardController: display active bookings
 * - AdminController: view all bookings, manage check-ins
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

/**
 * Class BookingModel - User Booking Management dengan comprehensive operations
 * 
 * @property PDO $pdo Database connection instance
 */
class BookingModel
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

    // ==================== CREATE ====================
    
    /**
     * Buat booking baru untuk user
     * Hanya untuk Ruang Umum
     * 
     * @param array $data Data booking
     * @param string $nomorIndukKetua Nomor induk user yang login (akan jadi ketua)
     * @param array $anggota Array of ['nomor_induk' => string, 'username' => string] untuk anggota lain
     * @return int|false ID booking yang dibuat atau false jika gagal
     */
    public function create(array $data, string $nomorIndukKetua, array $anggota = []): int|false
    {
        $this->pdo->beginTransaction();
        
        try {
            // Generate kode booking unik (7 karakter)
            $kodeBooking = $this->generateKodeBooking();
            
            // Insert booking
            $sql = "INSERT INTO booking (surat_lampiran, durasi_penggunaan, kode_booking, 
                    id_ruangan, id_status_booking)
                    VALUES (:surat_lampiran, :durasi_penggunaan, :kode_booking,
                    :id_ruangan, :id_status_booking)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':surat_lampiran' => $data['surat_lampiran'] ?? null,
                ':durasi_penggunaan' => $data['durasi_penggunaan'],
                ':kode_booking' => $kodeBooking,
                ':id_ruangan' => $data['id_ruangan'],
                ':id_status_booking' => $data['id_status_booking'] ?? 1 // Default AKTIF (workflow baru: langsung aktif)
            ]);
            
            $idBooking = (int) $this->pdo->lastInsertId();
            
            // Insert ketua sebagai anggota pertama
            $this->addAnggota($idBooking, $nomorIndukKetua, true);
            
            // Insert anggota lainnya
            foreach ($anggota as $member) {
                if (!empty($member['nomor_induk'])) {
                    $this->addAnggota($idBooking, $member['nomor_induk'], false);
                }
            }
            
            $this->pdo->commit();
            return $idBooking;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Tambah anggota ke booking
     */
    public function addAnggota(int $idBooking, string $nomorInduk, bool $isKetua = false): int|false
    {
        $sql = "INSERT INTO anggota_booking (id_booking, nomor_induk, is_ketua, is_checked_in)
                VALUES (:id_booking, :nomor_induk, :is_ketua, 0)";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':id_booking' => $idBooking,
            ':nomor_induk' => $nomorInduk,
            ':is_ketua' => $isKetua ? 1 : 0
        ]);
        return $result ? (int) $this->pdo->lastInsertId() : false;
    }

    /**
     * Generate kode booking unik 7 karakter
     */
    private function generateKodeBooking(): string
    {
        do {
            $kode = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7));
            $sql = "SELECT COUNT(*) FROM booking WHERE kode_booking = :kode";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':kode' => $kode]);
        } while ($stmt->fetchColumn() > 0);
        
        return $kode;
    }

    // ==================== READ ====================
    
    /**
     * Ambil semua booking user (Ruang Umum)
     */
    public function getAll(): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE r.jenis_ruangan = 'Ruang Umum'
                ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil booking berdasarkan ID
     */
    public function getById(int $id): array|false
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan,
                r.minimal_kapasitas_ruangan, r.maksimal_kapasitas_ruangan,
                sb.nama_status, s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE b.id_booking = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Ambil booking berdasarkan kode booking
     */
    public function getByKode(string $kode): array|false
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE b.kode_booking = :kode";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':kode' => $kode]);
        return $stmt->fetch();
    }

    /**
     * Ambil booking berdasarkan nomor induk user (sebagai ketua atau anggota)
     */
    public function getByNomorInduk(string $nomorInduk): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai,
                ab.is_ketua
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                JOIN anggota_booking ab ON b.id_booking = ab.id_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE ab.nomor_induk = :nomor_induk
                ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil booking aktif user (hanya 1 booking aktif per ketua)
     * DEPRECATED: Gunakan getActiveByMember() untuk cek partisipasi sebagai ketua atau anggota
     */
    public function getActiveByKetua(string $nomorInduk): array|false
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.minimal_kapasitas_ruangan, r.maksimal_kapasitas_ruangan, r.foto_ruangan,
                sb.nama_status, s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                JOIN anggota_booking ab ON b.id_booking = ab.id_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE ab.nomor_induk = :nomor_induk 
                AND ab.is_ketua = 1
                AND sb.nama_status = 'AKTIF'
                ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetch();
    }

    /**
     * Ambil booking aktif user (baik sebagai ketua maupun anggota)
     * User tidak bisa booking lagi jika sudah terdaftar di booking aktif orang lain
     */
    public function getActiveByMember(string $nomorInduk): array|false
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.minimal_kapasitas_ruangan, r.maksimal_kapasitas_ruangan, r.foto_ruangan,
                sb.nama_status, s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai,
                ab.is_ketua, ab.is_checked_in, ab.waktu_check_in
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                JOIN anggota_booking ab ON b.id_booking = ab.id_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE ab.nomor_induk = :nomor_induk 
                AND sb.nama_status = 'AKTIF'
                ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetch();
    }

    /**
     * Ambil anggota booking
     */
    public function getAnggota(int $idBooking): array
    {
        $sql = "SELECT ab.*, a.username, a.email, a.role
                FROM anggota_booking ab
                JOIN akun a ON ab.nomor_induk = a.nomor_induk
                WHERE ab.id_booking = :id_booking
                ORDER BY ab.is_ketua DESC, a.username ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_booking' => $idBooking]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil ketua booking
     */
    public function getKetua(int $idBooking): array|false
    {
        $sql = "SELECT ab.*, a.username, a.email, a.role
                FROM anggota_booking ab
                JOIN akun a ON ab.nomor_induk = a.nomor_induk
                WHERE ab.id_booking = :id_booking AND ab.is_ketua = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_booking' => $idBooking]);
        return $stmt->fetch();
    }

    // ==================== UPDATE ====================
    
    /**
     * Update booking
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE booking SET 
                surat_lampiran = :surat_lampiran,
                durasi_penggunaan = :durasi_penggunaan,
                id_ruangan = :id_ruangan,
                id_status_booking = :id_status_booking
                WHERE id_booking = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':surat_lampiran' => $data['surat_lampiran'] ?? null,
            ':durasi_penggunaan' => $data['durasi_penggunaan'],
            ':id_ruangan' => $data['id_ruangan'],
            ':id_status_booking' => $data['id_status_booking']
        ]);
    }

    /**
     * Update status booking
     */
    public function updateStatus(int $id, int $idStatus): bool
    {
        $sql = "UPDATE booking SET id_status_booking = :id_status WHERE id_booking = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id, ':id_status' => $idStatus]);
    }

    /**
     * Check-in anggota
     */
    public function checkInAnggota(int $idBooking, string $nomorInduk): bool
    {
        $sql = "UPDATE anggota_booking SET is_checked_in = 1, waktu_check_in = NOW()
                WHERE id_booking = :id_booking AND nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id_booking' => $idBooking, ':nomor_induk' => $nomorInduk]);
    }

    /**
     * Cek apakah booking sudah ada anggota yang check-in
     * Digunakan untuk validasi reschedule - tidak boleh reschedule jika sudah ada check-in
     */
    public function hasAnyCheckedIn(int $idBooking): bool
    {
        $sql = "SELECT COUNT(*) FROM anggota_booking 
                WHERE id_booking = :id_booking AND is_checked_in = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_booking' => $idBooking]);
        return $stmt->fetchColumn() > 0;
    }

    // ==================== DELETE ====================
    
    /**
     * Batalkan booking (soft delete dengan mengubah status)
     */
    public function cancel(int $id): bool
    {
        // Status 4 = DIBATALKAN
        return $this->updateStatus($id, 4);
    }

    /**
     * Hapus booking permanen (hati-hati dengan FK constraint)
     */
    public function delete(int $id): bool
    {
        $this->pdo->beginTransaction();
        try {
            // Hapus anggota booking dulu (CASCADE)
            $sql1 = "DELETE FROM anggota_booking WHERE id_booking = :id";
            $stmt1 = $this->pdo->prepare($sql1);
            $stmt1->execute([':id' => $id]);
            
            // Hapus schedule
            $sql2 = "DELETE FROM schedule WHERE id_booking = :id";
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute([':id' => $id]);
            
            // Hapus feedback
            $sql3 = "DELETE FROM feedback WHERE id_booking = :id";
            $stmt3 = $this->pdo->prepare($sql3);
            $stmt3->execute([':id' => $id]);
            
            // Hapus booking
            $sql4 = "DELETE FROM booking WHERE id_booking = :id";
            $stmt4 = $this->pdo->prepare($sql4);
            $stmt4->execute([':id' => $id]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Hapus anggota dari booking
     */
    public function removeAnggota(int $idBooking, string $nomorInduk): bool
    {
        // Tidak boleh hapus ketua
        $sql = "DELETE FROM anggota_booking WHERE id_booking = :id_booking AND nomor_induk = :nomor_induk AND is_ketua = 0";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id_booking' => $idBooking, ':nomor_induk' => $nomorInduk]);
    }

    // ==================== FILTER / SEARCH ====================
    
    /**
     * Filter booking dengan berbagai kriteria
     */
    public function filter(array $filters = []): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, r.foto_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE r.jenis_ruangan = 'Ruang Umum'";
        $params = [];

        // Filter by ruangan
        if (!empty($filters['id_ruangan'])) {
            $sql .= " AND b.id_ruangan = :id_ruangan";
            $params[':id_ruangan'] = $filters['id_ruangan'];
        }

        // Filter by status
        if (!empty($filters['id_status'])) {
            $sql .= " AND b.id_status_booking = :id_status";
            $params[':id_status'] = $filters['id_status'];
        }

        // Filter by tanggal schedule
        if (!empty($filters['tanggal'])) {
            $sql .= " AND s.tanggal_schedule = :tanggal";
            $params[':tanggal'] = $filters['tanggal'];
        }

        // Filter by date range
        if (!empty($filters['tanggal_mulai']) && !empty($filters['tanggal_selesai'])) {
            $sql .= " AND s.tanggal_schedule BETWEEN :tanggal_mulai AND :tanggal_selesai";
            $params[':tanggal_mulai'] = $filters['tanggal_mulai'];
            $params[':tanggal_selesai'] = $filters['tanggal_selesai'];
        }

        // Filter by kode booking
        if (!empty($filters['kode_booking'])) {
            $sql .= " AND b.kode_booking LIKE :kode_booking";
            $params[':kode_booking'] = '%' . $filters['kode_booking'] . '%';
        }

        $sql .= " ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Cek apakah user sudah punya booking aktif (baik sebagai ketua maupun anggota)
     */
    public function hasActiveBooking(string $nomorInduk): bool
    {
        $sql = "SELECT COUNT(*) FROM booking b
                JOIN anggota_booking ab ON b.id_booking = ab.id_booking
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                WHERE ab.nomor_induk = :nomor_induk 
                AND sb.nama_status = 'AKTIF'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Hitung total booking
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                WHERE r.jenis_ruangan = 'Ruang Umum'";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    // ==================== VALIDATION ====================

    /**
     * Cek apakah user sedang di-block dari booking (24 jam atau suspend 7 hari)
     * @return array|false Data block jika sedang di-block, false jika tidak
     */
    public function checkBookingBlock(string $nomorInduk): array|false
    {
        $sql = "SELECT * FROM pelanggaran_suspensi 
                WHERE nomor_induk = :nomor_induk 
                AND tanggal_selesai >= NOW()
                ORDER BY tanggal_selesai DESC
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetch();
    }
    
    /**
     * Cek apakah user sedang dalam status suspend (hanya suspend 7 hari, bukan block 24 jam)
     * @return array|false Data suspensi jika sedang suspend, false jika tidak
     */
    public function checkSuspendStatus(string $nomorInduk): array|false
    {
        $sql = "SELECT * FROM pelanggaran_suspensi 
                WHERE nomor_induk = :nomor_induk 
                AND DATEDIFF(tanggal_selesai, tanggal_mulai) > 1
                AND tanggal_selesai >= NOW()
                ORDER BY tanggal_selesai DESC
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetch();
    }

    /**
     * Hitung jumlah booking HANGUS dalam 30 hari terakhir
     * @return int Jumlah booking HANGUS
     */
    public function countHangusBookings(string $nomorInduk, int $days = 30): int
    {
        // Status 4 = HANGUS
        $sql = "SELECT COUNT(*) FROM booking b
                JOIN anggota_booking ab ON b.id_booking = ab.id_booking
                JOIN schedule s ON b.id_booking = s.id_booking
                WHERE ab.nomor_induk = :nomor_induk 
                AND ab.is_ketua = 1
                AND b.id_status_booking = 4
                AND s.tanggal_schedule >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                AND s.status_schedule = 'AKTIF'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk, ':days' => $days]);
        return (int) $stmt->fetchColumn();
    }
}