<?php

/**
 * ScheduleModel - Model untuk CRUD jadwal booking dan reschedule
 * 
 * Relasi:
 * - schedule -> booking (N:1) via id_booking
 */
class ScheduleModel
{
    private PDO $pdo;

    public function __construct()
    {
        $koneksi = new Koneksi();
        $this->pdo = $koneksi->getPdo();
    }

    // ==================== CREATE ====================
    
    /**
     * Buat jadwal baru untuk booking
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO schedule (id_booking, tanggal_schedule, waktu_mulai, waktu_selesai, alasan_reschedule, status_schedule)
                VALUES (:id_booking, :tanggal_schedule, :waktu_mulai, :waktu_selesai, :alasan_reschedule, :status_schedule)";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':id_booking' => $data['id_booking'],
            ':tanggal_schedule' => $data['tanggal_schedule'],
            ':waktu_mulai' => $data['waktu_mulai'],
            ':waktu_selesai' => $data['waktu_selesai'],
            ':alasan_reschedule' => $data['alasan_reschedule'] ?? null,
            ':status_schedule' => $data['status_schedule'] ?? 'AKTIF'
        ]);

        return $result ? (int) $this->pdo->lastInsertId() : false;
    }

    /**
     * Buat jadwal awal saat booking dibuat
     */
    public function createInitialSchedule(int $idBooking, string $tanggal, string $waktuMulai, string $waktuSelesai): int|false
    {
        return $this->create([
            'id_booking' => $idBooking,
            'tanggal_schedule' => $tanggal,
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'alasan_reschedule' => null,
            'status_schedule' => 'AKTIF'
        ]);
    }

    // ==================== READ ====================
    
    /**
     * Ambil semua schedule
     */
    public function getAll(): array
    {
        $sql = "SELECT s.*, b.kode_booking, r.nama_ruangan 
                FROM schedule s
                JOIN booking b ON s.id_booking = b.id_booking
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                ORDER BY s.tanggal_schedule DESC, s.waktu_mulai ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil schedule berdasarkan ID
     */
    public function getById(int $id): array|false
    {
        $sql = "SELECT s.*, b.kode_booking, r.nama_ruangan 
                FROM schedule s
                JOIN booking b ON s.id_booking = b.id_booking
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                WHERE s.id_schedule = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Ambil schedule berdasarkan booking ID
     */
    public function getByBookingId(int $idBooking): array
    {
        $sql = "SELECT * FROM schedule WHERE id_booking = :id_booking ORDER BY tanggal_schedule DESC, waktu_mulai ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_booking' => $idBooking]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil schedule aktif untuk booking
     */
    public function getActiveByBookingId(int $idBooking): array|false
    {
        $sql = "SELECT * FROM schedule WHERE id_booking = :id_booking AND status_schedule = 'AKTIF' ORDER BY tanggal_schedule DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_booking' => $idBooking]);
        return $stmt->fetch();
    }

    /**
     * Ambil semua schedule untuk ruangan tertentu pada tanggal tertentu
     * Untuk menampilkan timeline waktu yang sudah terpakai
     */
    public function getSchedulesByRoomAndDate(int $idRuangan, string $tanggal): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(s.waktu_mulai, '%H:%i') as waktu_mulai,
                    DATE_FORMAT(s.waktu_selesai, '%H:%i') as waktu_selesai,
                    b.id_booking
                FROM schedule s
                JOIN booking b ON s.id_booking = b.id_booking
                WHERE b.id_ruangan = :id_ruangan
                AND s.tanggal_schedule = :tanggal
                AND s.status_schedule = 'AKTIF'
                AND b.id_status_booking = 1
                ORDER BY s.waktu_mulai ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_ruangan' => $idRuangan, ':tanggal' => $tanggal]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil jadwal berdasarkan ruangan dan tanggal
     * Hanya menampilkan booking dengan status AKTIF
     */
    public function getByRuanganAndDate(int $idRuangan, string $tanggal): array
    {
        $sql = "SELECT s.*, b.kode_booking 
                FROM schedule s
                JOIN booking b ON s.id_booking = b.id_booking
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                WHERE b.id_ruangan = :id_ruangan 
                AND s.tanggal_schedule = :tanggal
                AND s.status_schedule = 'AKTIF'
                AND sb.nama_status = 'AKTIF'
                ORDER BY s.waktu_mulai ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_ruangan' => $idRuangan, ':tanggal' => $tanggal]);
        return $stmt->fetchAll();
    }

    // ==================== UPDATE ====================
    
    /**
     * Update schedule (reschedule)
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE schedule SET 
                tanggal_schedule = :tanggal_schedule,
                waktu_mulai = :waktu_mulai,
                waktu_selesai = :waktu_selesai,
                alasan_reschedule = :alasan_reschedule,
                status_schedule = :status_schedule
                WHERE id_schedule = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':tanggal_schedule' => $data['tanggal_schedule'],
            ':waktu_mulai' => $data['waktu_mulai'],
            ':waktu_selesai' => $data['waktu_selesai'],
            ':alasan_reschedule' => $data['alasan_reschedule'] ?? null,
            ':status_schedule' => $data['status_schedule']
        ]);
    }

    /**
     * Reschedule - update waktu dan alasan
     * CRITICAL: Hanya bisa dilakukan minimal 1 jam sebelum waktu_mulai
     */
    public function reschedule(int $idBooking, string $tanggalBaru, string $waktuMulaiBaru, string $waktuSelesaiBaru, string $alasan): bool
    {
        // Validasi: Ambil schedule lama
        $oldSchedule = $this->getActiveByBookingId($idBooking);
        if (!$oldSchedule) {
            return false; // Schedule tidak ditemukan
        }

        // Validasi: Cek apakah reschedule dilakukan minimal 1 jam sebelum waktu mulai
        $waktuMulaiLama = new DateTime($oldSchedule['tanggal_schedule'] . ' ' . $oldSchedule['waktu_mulai']);
        $sekarang = new DateTime();
        $diffInHours = ($waktuMulaiLama->getTimestamp() - $sekarang->getTimestamp()) / 3600;

        if ($diffInHours < 1) {
            return false; // Tidak bisa reschedule, kurang dari 1 jam
        }

        // Nonaktifkan schedule lama
        $sqlOld = "UPDATE schedule SET status_schedule = 'DIRESCHEDULE' WHERE id_booking = :id_booking AND status_schedule = 'AKTIF'";
        $stmtOld = $this->pdo->prepare($sqlOld);
        $stmtOld->execute([':id_booking' => $idBooking]);

        // Buat schedule baru
        return $this->create([
            'id_booking' => $idBooking,
            'tanggal_schedule' => $tanggalBaru,
            'waktu_mulai' => $waktuMulaiBaru,
            'waktu_selesai' => $waktuSelesaiBaru,
            'alasan_reschedule' => $alasan,
            'status_schedule' => 'AKTIF'
        ]) !== false;
    }
    
    /**
     * Cek apakah booking masih bisa di-reschedule (minimal 1 jam sebelum waktu mulai)
     */
    public function canReschedule(int $idBooking): bool
    {
        $schedule = $this->getActiveByBookingId($idBooking);
        if (!$schedule) {
            return false;
        }

        $waktuMulai = new DateTime($schedule['tanggal_schedule'] . ' ' . $schedule['waktu_mulai']);
        $sekarang = new DateTime();
        $diffInHours = ($waktuMulai->getTimestamp() - $sekarang->getTimestamp()) / 3600;

        return $diffInHours >= 1; // Bisa reschedule jika >= 1 jam
    }

    /**
     * Update status schedule
     */
    public function updateStatus(int $id, string $status): bool
    {
        $sql = "UPDATE schedule SET status_schedule = :status WHERE id_schedule = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id, ':status' => $status]);
    }

    // ==================== DELETE ====================
    
    /**
     * Hapus schedule
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM schedule WHERE id_schedule = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Hapus semua schedule untuk booking
     */
    public function deleteByBookingId(int $idBooking): bool
    {
        $sql = "DELETE FROM schedule WHERE id_booking = :id_booking";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id_booking' => $idBooking]);
    }

    // ==================== FILTER / VALIDATION ====================
    
    /**
     * Cek apakah waktu bentrok dengan jadwal lain di ruangan yang sama
     * HANYA booking dengan status AKTIF yang dianggap bentrok
     */
    public function isTimeSlotAvailable(int $idRuangan, string $tanggal, string $waktuMulai, string $waktuSelesai, ?int $excludeBookingId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM schedule s
                JOIN booking b ON s.id_booking = b.id_booking
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                WHERE b.id_ruangan = :id_ruangan
                AND s.tanggal_schedule = :tanggal
                AND s.status_schedule = 'AKTIF'
                AND sb.nama_status = 'AKTIF'
                AND (
                    (s.waktu_mulai < :waktu_selesai AND s.waktu_selesai > :waktu_mulai)
                )";
        
        $params = [
            ':id_ruangan' => $idRuangan,
            ':tanggal' => $tanggal,
            ':waktu_mulai' => $waktuMulai,
            ':waktu_selesai' => $waktuSelesai
        ];

        if ($excludeBookingId !== null) {
            $sql .= " AND b.id_booking != :exclude_id";
            $params[':exclude_id'] = $excludeBookingId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Filter schedule berdasarkan tanggal
     * Hanya menampilkan booking dengan status AKTIF
     */
    public function filterByDateRange(string $startDate, string $endDate): array
    {
        $sql = "SELECT s.*, b.kode_booking, r.nama_ruangan 
                FROM schedule s
                JOIN booking b ON s.id_booking = b.id_booking
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                WHERE s.tanggal_schedule BETWEEN :start_date AND :end_date
                AND s.status_schedule = 'AKTIF'
                AND sb.nama_status = 'AKTIF'
                ORDER BY s.tanggal_schedule ASC, s.waktu_mulai ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil jadwal mendatang
     * Hanya menampilkan booking dengan status AKTIF
     */
    public function getUpcoming(int $limit = 10): array
    {
        $sql = "SELECT s.*, b.kode_booking, r.nama_ruangan 
                FROM schedule s
                JOIN booking b ON s.id_booking = b.id_booking
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                WHERE s.tanggal_schedule >= CURDATE()
                AND s.status_schedule = 'AKTIF'
                AND sb.nama_status = 'AKTIF'
                ORDER BY s.tanggal_schedule ASC, s.waktu_mulai ASC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Cek konflik jadwal untuk beberapa anggota sekaligus
     * Mengecek apakah ada anggota yang sudah memiliki booking aktif di waktu yang sama
     * 
     * @param array $nomorIndukList Array nomor induk yang akan dicek
     * @param string $tanggal Tanggal booking baru
     * @param string $waktuMulai Waktu mulai booking baru
     * @param string $waktuSelesai Waktu selesai booking baru
     * @return array Array anggota yang konflik dengan format ['nomor_induk', 'username', 'kode_booking']
     */
    public function checkMemberConflicts(array $nomorIndukList, string $tanggal, string $waktuMulai, string $waktuSelesai): array
    {
        if (empty($nomorIndukList)) {
            return [];
        }

        // Buat placeholder untuk IN clause
        $placeholders = implode(',', array_fill(0, count($nomorIndukList), '?'));
        
        $sql = "SELECT DISTINCT ab.nomor_induk, a.username, b.kode_booking, s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM anggota_booking ab
                JOIN booking b ON ab.id_booking = b.id_booking
                JOIN schedule s ON b.id_booking = s.id_booking
                JOIN akun a ON ab.nomor_induk = a.nomor_induk
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                WHERE ab.nomor_induk IN ($placeholders)
                AND s.tanggal_schedule = ?
                AND s.status_schedule = 'AKTIF'
                AND sb.nama_status = 'AKTIF'
                AND (
                    (s.waktu_mulai < ? AND s.waktu_selesai > ?)
                )";
        
        $params = array_merge($nomorIndukList, [$tanggal, $waktuSelesai, $waktuMulai]);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count active bookings on a specific date
     * Used to check if there are existing bookings when adding a holiday
     * 
     * @param string $tanggal Format YYYY-MM-DD
     * @return int Number of active bookings on this date
     */
    public function countActiveBookingsByDate(string $tanggal): int
    {
        $sql = "SELECT COUNT(DISTINCT s.id_booking) 
                FROM schedule s
                INNER JOIN booking b ON s.id_booking = b.id_booking
                WHERE s.tanggal_schedule = ?
                AND s.status_schedule = 'AKTIF'
                AND b.id_status_booking = 1"; // 1 = AKTIF status
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$tanggal]);
        return (int) $stmt->fetchColumn();
    }
}
