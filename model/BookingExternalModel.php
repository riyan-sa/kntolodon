<?php

/**
 * BookingExternalModel - Model untuk CRUD booking eksternal oleh Super Admin
 * Hanya untuk Ruang Rapat, bisa dari instansi luar atau civitas akademik
 * Tidak memerlukan anggota_booking
 * 
 * Relasi:
 * - booking -> ruangan (N:1) via id_ruangan (jenis = 'Ruang Rapat')
 * - booking -> status_booking (N:1) via id_status_booking
 * - booking -> schedule (1:N) via id_booking
 */
class BookingExternalModel
{
    private PDO $pdo;

    public function __construct()
    {
        $koneksi = new Koneksi();
        $this->pdo = $koneksi->getPdo();
    }

    // ==================== CREATE ====================
    
    /**
     * Buat booking eksternal (Super Admin only)
     * Hanya untuk Ruang Rapat
     */
    public function create(array $data): int|false
    {
        $this->pdo->beginTransaction();
        
        try {
            // Validasi bentrok waktu (kecuali untuk status HANGUS)
            if (!empty($data['tanggal_schedule']) && !empty($data['waktu_mulai']) && !empty($data['waktu_selesai'])) {
                if ($this->isTimeConflict($data['id_ruangan'], $data['tanggal_schedule'], $data['waktu_mulai'], $data['waktu_selesai'])) {
                    throw new Exception('Waktu booking bentrok dengan booking lain');
                }
            }
            
            // Generate kode booking unik
            $kodeBooking = $this->generateKodeBooking();
            
            $sql = "INSERT INTO booking (surat_lampiran, durasi_penggunaan, kode_booking, 
                    id_ruangan, id_status_booking, nama_instansi)
                    VALUES (:surat_lampiran, :durasi_penggunaan, :kode_booking,
                    :id_ruangan, :id_status_booking, :nama_instansi)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':surat_lampiran' => $data['surat_lampiran'] ?? null,
                ':durasi_penggunaan' => $data['durasi_penggunaan'],
                ':kode_booking' => $kodeBooking,
                ':id_ruangan' => $data['id_ruangan'],
                ':id_status_booking' => $data['id_status_booking'] ?? 1, // Default AKTIF
                ':nama_instansi' => $data['nama_instansi'] ?? null
            ]);
            
            $idBooking = (int) $this->pdo->lastInsertId();
            
            // Buat schedule jika ada data waktu
            if (!empty($data['tanggal_schedule']) && !empty($data['waktu_mulai']) && !empty($data['waktu_selesai'])) {
                $scheduleModel = new ScheduleModel();
                $scheduleModel->createInitialSchedule(
                    $idBooking,
                    $data['tanggal_schedule'],
                    $data['waktu_mulai'],
                    $data['waktu_selesai']
                );
            }
            
            $this->pdo->commit();
            return $idBooking;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Generate kode booking unik 7 karakter
     */
    private function generateKodeBooking(): string
    {
        do {
            $kode = 'EXT' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
            $sql = "SELECT COUNT(*) FROM booking WHERE kode_booking = :kode";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':kode' => $kode]);
        } while ($stmt->fetchColumn() > 0);
        
        return $kode;
    }

    // ==================== READ ====================
    
    /**
     * Ambil semua booking eksternal (Ruang Rapat)
     */
    public function getAll(): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE r.jenis_ruangan = 'Ruang Rapat'
                ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil booking eksternal berdasarkan ID
     */
    public function getById(int $id): array|false
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE b.id_booking = :id AND r.jenis_ruangan = 'Ruang Rapat'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Ambil booking eksternal berdasarkan kode
     */
    public function getByKode(string $kode): array|false
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE b.kode_booking = :kode AND r.jenis_ruangan = 'Ruang Rapat'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':kode' => $kode]);
        return $stmt->fetch();
    }

    /**
     * Ambil booking mendatang (untuk tab Mendatang) dengan pagination
     */
    public function getUpcoming(int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE r.jenis_ruangan = 'Ruang Rapat'
                AND (s.tanggal_schedule >= CURDATE() OR s.tanggal_schedule IS NULL)
                AND sb.nama_status = 'AKTIF'
                ORDER BY s.tanggal_schedule ASC, s.waktu_mulai ASC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Ambil booking histori (untuk tab Histori) dengan pagination
     */
    public function getHistory(int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE r.jenis_ruangan = 'Ruang Rapat'
                AND (s.tanggal_schedule < CURDATE() OR sb.nama_status IN ('SELESAI', 'DIBATALKAN', 'HANGUS'))
                ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ==================== UPDATE ====================
    
    /**
     * Update booking eksternal
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE booking SET 
                surat_lampiran = :surat_lampiran,
                durasi_penggunaan = :durasi_penggunaan,
                id_ruangan = :id_ruangan,
                id_status_booking = :id_status_booking,
                nama_instansi = :nama_instansi
                WHERE id_booking = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':surat_lampiran' => $data['surat_lampiran'] ?? null,
            ':durasi_penggunaan' => $data['durasi_penggunaan'],
            ':id_ruangan' => $data['id_ruangan'],
            ':id_status_booking' => $data['id_status_booking'],
            ':nama_instansi' => $data['nama_instansi'] ?? null
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

    // ==================== DELETE ====================
    
    /**
     * Batalkan booking eksternal
     */
    public function cancel(int $id): bool
    {
        return $this->updateStatus($id, 4); // 4 = DIBATALKAN
    }

    /**
     * Hapus booking eksternal permanen
     */
    public function delete(int $id): bool
    {
        $this->pdo->beginTransaction();
        try {
            // Hapus schedule
            $sql1 = "DELETE FROM schedule WHERE id_booking = :id";
            $stmt1 = $this->pdo->prepare($sql1);
            $stmt1->execute([':id' => $id]);
            
            // Hapus feedback
            $sql2 = "DELETE FROM feedback WHERE id_booking = :id";
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute([':id' => $id]);
            
            // Hapus booking
            $sql3 = "DELETE FROM booking WHERE id_booking = :id";
            $stmt3 = $this->pdo->prepare($sql3);
            $stmt3->execute([':id' => $id]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // ==================== FILTER / SEARCH ====================
    
    /**
     * Filter booking eksternal
     */
    public function filter(array $filters = []): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE r.jenis_ruangan = 'Ruang Rapat'";
        $params = [];

        // Filter by ruangan
        if (!empty($filters['id_ruangan'])) {
            $sql .= " AND b.id_ruangan = :id_ruangan";
            $params[':id_ruangan'] = $filters['id_ruangan'];
        }

        // Filter by nama ruangan (search)
        if (!empty($filters['nama_ruangan'])) {
            $sql .= " AND r.nama_ruangan LIKE :nama_ruangan";
            $params[':nama_ruangan'] = '%' . $filters['nama_ruangan'] . '%';
        }

        // Filter by nama instansi
        if (!empty($filters['nama_instansi'])) {
            $sql .= " AND b.nama_instansi LIKE :nama_instansi";
            $params[':nama_instansi'] = '%' . $filters['nama_instansi'] . '%';
        }

        // Filter by tanggal
        if (!empty($filters['tanggal'])) {
            $sql .= " AND s.tanggal_schedule = :tanggal";
            $params[':tanggal'] = $filters['tanggal'];
        }

        // Filter by status
        if (!empty($filters['id_status'])) {
            $sql .= " AND b.id_status_booking = :id_status";
            $params[':id_status'] = $filters['id_status'];
        }

        // Filter upcoming only
        if (!empty($filters['upcoming']) && $filters['upcoming'] === true) {
            $sql .= " AND (s.tanggal_schedule >= CURDATE() OR s.tanggal_schedule IS NULL) AND sb.nama_status = 'AKTIF'";
        }

        // Filter history only
        if (!empty($filters['history']) && $filters['history'] === true) {
            $sql .= " AND (s.tanggal_schedule < CURDATE() OR sb.nama_status IN ('SELESAI', 'DIBATALKAN', 'HANGUS'))";
        }

        $sql .= " ORDER BY s.tanggal_schedule DESC, s.waktu_mulai DESC";

        // Pagination
        if (isset($filters['limit']) && isset($filters['offset'])) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->pdo->prepare($sql);
        
        // Bind params
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Bind pagination params if exists
        if (isset($filters['limit']) && isset($filters['offset'])) {
            $stmt->bindValue(':limit', (int) $filters['limit'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $filters['offset'], PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Hitung total booking eksternal berdasarkan filter
     */
    public function countFiltered(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT b.id_booking) as total
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE r.jenis_ruangan = 'Ruang Rapat'";
        $params = [];

        // Filter by ruangan
        if (!empty($filters['id_ruangan'])) {
            $sql .= " AND b.id_ruangan = :id_ruangan";
            $params[':id_ruangan'] = $filters['id_ruangan'];
        }

        // Filter by nama ruangan (search)
        if (!empty($filters['nama_ruangan'])) {
            $sql .= " AND r.nama_ruangan LIKE :nama_ruangan";
            $params[':nama_ruangan'] = '%' . $filters['nama_ruangan'] . '%';
        }

        // Filter by nama instansi
        if (!empty($filters['nama_instansi'])) {
            $sql .= " AND b.nama_instansi LIKE :nama_instansi";
            $params[':nama_instansi'] = '%' . $filters['nama_instansi'] . '%';
        }

        // Filter by tanggal
        if (!empty($filters['tanggal'])) {
            $sql .= " AND s.tanggal_schedule = :tanggal";
            $params[':tanggal'] = $filters['tanggal'];
        }

        // Filter by status
        if (!empty($filters['id_status'])) {
            $sql .= " AND b.id_status_booking = :id_status";
            $params[':id_status'] = $filters['id_status'];
        }

        // Filter upcoming only
        if (!empty($filters['upcoming']) && $filters['upcoming'] === true) {
            $sql .= " AND (s.tanggal_schedule >= CURDATE() OR s.tanggal_schedule IS NULL) AND sb.nama_status = 'AKTIF'";
        }

        // Filter history only
        if (!empty($filters['history']) && $filters['history'] === true) {
            $sql .= " AND (s.tanggal_schedule < CURDATE() OR sb.nama_status IN ('SELESAI', 'DIBATALKAN', 'HANGUS'))";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Search booking eksternal
     */
    public function search(string $keyword): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, sb.nama_status,
                s.tanggal_schedule, s.waktu_mulai, s.waktu_selesai
                FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE r.jenis_ruangan = 'Ruang Rapat'
                AND (r.nama_ruangan LIKE :keyword OR b.nama_instansi LIKE :keyword OR b.kode_booking LIKE :keyword)
                ORDER BY s.tanggal_schedule DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':keyword' => '%' . $keyword . '%']);
        return $stmt->fetchAll();
    }

    /**
     * Hitung total booking eksternal
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM booking b
                JOIN ruangan r ON b.id_ruangan = r.id_ruangan
                WHERE r.jenis_ruangan = 'Ruang Rapat'";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    // ==================== VALIDASI ====================
    
    /**
     * Cek apakah waktu booking bentrok dengan booking lain
     * Hanya untuk status AKTIF (tidak termasuk HANGUS)
     */
    public function isTimeConflict(int $idRuangan, string $tanggal, string $waktuMulai, string $waktuSelesai, ?int $excludeBookingId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM booking b
                JOIN schedule s ON b.id_booking = s.id_booking
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                WHERE b.id_ruangan = :id_ruangan
                AND s.tanggal_schedule = :tanggal
                AND s.status_schedule = 'AKTIF'
                AND sb.nama_status = 'AKTIF'
                AND (
                    (:waktu_mulai >= s.waktu_mulai AND :waktu_mulai < s.waktu_selesai)
                    OR (:waktu_selesai > s.waktu_mulai AND :waktu_selesai <= s.waktu_selesai)
                    OR (:waktu_mulai <= s.waktu_mulai AND :waktu_selesai >= s.waktu_selesai)
                )";
        
        $params = [
            ':id_ruangan' => $idRuangan,
            ':tanggal' => $tanggal,
            ':waktu_mulai' => $waktuMulai,
            ':waktu_selesai' => $waktuSelesai
        ];
        
        // Exclude booking yang sedang di-update
        if ($excludeBookingId !== null) {
            $sql .= " AND b.id_booking != :exclude_id";
            $params[':exclude_id'] = $excludeBookingId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Ambil semua ruang rapat yang tersedia
     */
    public function getRuangRapat(): array
    {
        $sql = "SELECT * FROM ruangan 
                WHERE jenis_ruangan = 'Ruang Rapat' 
                AND status_ruangan = 'Tersedia'
                ORDER BY nama_ruangan ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Cek apakah ruangan adalah ruang rapat
     */
    public function isRuangRapat(int $idRuangan): bool
    {
        $sql = "SELECT COUNT(*) FROM ruangan 
                WHERE id_ruangan = :id AND jenis_ruangan = 'Ruang Rapat'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $idRuangan]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
