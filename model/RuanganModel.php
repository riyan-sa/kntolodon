<?php

/**
 * RuanganModel - Model untuk CRUD dan filter ruangan
 * 
 * Relasi:
 * - ruangan -> booking (1:N) via id_ruangan
 */
class RuanganModel
{
    private PDO $pdo;

    public function __construct()
    {
        $koneksi = new Koneksi();
        $this->pdo = $koneksi->getPdo();
    }

    // ==================== CREATE ====================
    
    /**
     * Tambah ruangan baru
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO ruangan (nama_ruangan, jenis_ruangan, 
                maksimal_kapasitas_ruangan, minimal_kapasitas_ruangan, deskripsi, tata_tertib, status_ruangan, foto_ruangan)
                VALUES (:nama_ruangan, :jenis_ruangan, 
                :maksimal_kapasitas_ruangan, :minimal_kapasitas_ruangan, :deskripsi, :tata_tertib, :status_ruangan, :foto_ruangan)";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':nama_ruangan' => $data['nama_ruangan'],
            ':jenis_ruangan' => $data['jenis_ruangan'], // 'Ruang Rapat' atau 'Ruang Umum'
            ':maksimal_kapasitas_ruangan' => $data['maksimal_kapasitas_ruangan'],
            ':minimal_kapasitas_ruangan' => $data['minimal_kapasitas_ruangan'],
            ':deskripsi' => $data['deskripsi'] ?? null,
            ':tata_tertib' => $data['tata_tertib'] ?? null,
            ':status_ruangan' => $data['status_ruangan'] ?? 'Tersedia',
            ':foto_ruangan' => $data['foto_ruangan'] ?? null
        ]);

        return $result ? (int) $this->pdo->lastInsertId() : false;
    }

    // ==================== READ ====================
    
    /**
     * Ambil semua ruangan
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM ruangan ORDER BY nama_ruangan ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil ruangan berdasarkan ID
     */
    public function getById(int $id): array|false
    {
        $sql = "SELECT * FROM ruangan WHERE id_ruangan = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Ambil ruangan berdasarkan jenis (untuk memisahkan Ruang Rapat dan Ruang Umum)
     * - 'Ruang Rapat' untuk booking external (Super Admin)
     * - 'Ruang Umum' untuk booking user
     */
    public function getByJenis(string $jenis): array
    {
        $sql = "SELECT * FROM ruangan WHERE jenis_ruangan = :jenis ORDER BY nama_ruangan ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':jenis' => $jenis]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil ruangan yang tersedia
     */
    public function getAvailable(): array
    {
        $sql = "SELECT * FROM ruangan WHERE status_ruangan = 'Tersedia' ORDER BY nama_ruangan ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil ruangan yang tersedia berdasarkan jenis
     */
    public function getAvailableByJenis(string $jenis): array
    {
        $sql = "SELECT * FROM ruangan WHERE status_ruangan = 'Tersedia' AND jenis_ruangan = :jenis ORDER BY nama_ruangan ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':jenis' => $jenis]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil semua ruangan dengan status ketersediaan dinamis berdasarkan booking aktif
     * Status 'Tidak Tersedia' jika:
     * - Ada booking AKTIF di hari H (hari ini)
     * - Dalam rentang 5 menit sebelum waktu mulai sampai waktu selesai
     * Real-time check based on current datetime
     */
    public function getAllWithDynamicAvailability(string $jenis): array
    {
        $sql = "SELECT r.*, 
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM booking b
                        JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                        JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                        WHERE b.id_ruangan = r.id_ruangan 
                        AND sb.nama_status = 'AKTIF'
                        AND s.tanggal_schedule = CURDATE()
                        AND CONCAT(s.tanggal_schedule, ' ', s.waktu_mulai) <= DATE_ADD(NOW(), INTERVAL 5 MINUTE)
                        AND CONCAT(s.tanggal_schedule, ' ', s.waktu_selesai) >= NOW()
                    ) THEN 'Tidak'
                    WHEN r.status_ruangan = 'Tersedia' THEN 'Tersedia'
                    ELSE 'Tidak'
                END AS status_ruangan
                FROM ruangan r
                WHERE r.jenis_ruangan = :jenis
                ORDER BY r.nama_ruangan ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':jenis' => $jenis]);
        return $stmt->fetchAll();
    }

    // ==================== UPDATE ====================
    
    /**
     * Update data ruangan
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE ruangan SET 
                nama_ruangan = :nama_ruangan,
                jenis_ruangan = :jenis_ruangan,
                maksimal_kapasitas_ruangan = :maksimal_kapasitas_ruangan,
                minimal_kapasitas_ruangan = :minimal_kapasitas_ruangan,
                deskripsi = :deskripsi,
                tata_tertib = :tata_tertib,
                status_ruangan = :status_ruangan,
                foto_ruangan = :foto_ruangan
                WHERE id_ruangan = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nama_ruangan' => $data['nama_ruangan'],
            ':jenis_ruangan' => $data['jenis_ruangan'],
            ':maksimal_kapasitas_ruangan' => $data['maksimal_kapasitas_ruangan'],
            ':minimal_kapasitas_ruangan' => $data['minimal_kapasitas_ruangan'],
            ':deskripsi' => $data['deskripsi'] ?? null,
            ':tata_tertib' => $data['tata_tertib'] ?? null,
            ':status_ruangan' => $data['status_ruangan'] ?? 'Tersedia',
            ':foto_ruangan' => $data['foto_ruangan'] ?? null
        ]);
    }

    /**
     * Update status ruangan saja
     */
    public function updateStatus(int $id, string $status): bool
    {
        $sql = "UPDATE ruangan SET status_ruangan = :status WHERE id_ruangan = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id, ':status' => $status]);
    }

    // ==================== DELETE ====================
    
    /**
     * Hapus ruangan (soft delete dengan mengubah status)
     */
    public function softDelete(int $id): bool
    {
        return $this->updateStatus($id, 'Tidak Tersedia');
    }

    /**
     * Hapus ruangan permanen
     * CATATAN: Akan gagal jika ada booking yang menggunakan ruangan ini (FK constraint)
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM ruangan WHERE id_ruangan = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // ==================== FILTER / SEARCH ====================
    
    /**
     * Filter ruangan dengan berbagai kriteria
     */
    public function filter(array $filters = []): array
    {
        $sql = "SELECT * FROM ruangan WHERE 1=1";
        $params = [];

        // Filter by nama
        if (!empty($filters['nama'])) {
            $sql .= " AND nama_ruangan LIKE :nama";
            $params[':nama'] = '%' . $filters['nama'] . '%';
        }

        // Filter by jenis ruangan
        if (!empty($filters['jenis'])) {
            $sql .= " AND jenis_ruangan = :jenis";
            $params[':jenis'] = $filters['jenis'];
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND status_ruangan = :status";
            $params[':status'] = $filters['status'];
        }

        // Filter by kapasitas minimal
        if (!empty($filters['kapasitas_min'])) {
            $sql .= " AND kapasitas_ruangan >= :kapasitas_min";
            $params[':kapasitas_min'] = $filters['kapasitas_min'];
        }

        // Filter by kapasitas maksimal
        if (!empty($filters['kapasitas_max'])) {
            $sql .= " AND kapasitas_ruangan <= :kapasitas_max";
            $params[':kapasitas_max'] = $filters['kapasitas_max'];
        }

        $sql .= " ORDER BY nama_ruangan ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Search ruangan by nama
     */
    public function search(string $keyword): array
    {
        $sql = "SELECT * FROM ruangan WHERE nama_ruangan LIKE :keyword ORDER BY nama_ruangan ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':keyword' => '%' . $keyword . '%']);
        return $stmt->fetchAll();
    }

    /**
     * Cek apakah ruangan sedang dipakai (ada booking aktif)
     */
    public function isInUse(int $id): bool
    {
        $sql = "SELECT COUNT(*) FROM booking b
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                WHERE b.id_ruangan = :id AND sb.nama_status = 'AKTIF'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Hitung total ruangan
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM ruangan";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Hitung ruangan berdasarkan jenis
     */
    public function countByJenis(string $jenis): int
    {
        $sql = "SELECT COUNT(*) FROM ruangan WHERE jenis_ruangan = :jenis";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':jenis' => $jenis]);
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Auto-update status_ruangan di database based on real-time availability
     * Set 'Tidak Tersedia' jika ada booking aktif dalam 5 menit ke depan (hari H)
     * Set 'Tersedia' jika tidak ada booking atau sudah lewat waktu selesai
     */
    public function autoUpdateRoomStatus(): int
    {
        // Update jadi 'Tidak Tersedia' untuk ruangan dengan booking aktif dalam 5 menit
        $sql1 = "UPDATE ruangan r
                SET r.status_ruangan = 'Tidak Tersedia'
                WHERE EXISTS (
                    SELECT 1 FROM booking b
                    JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                    JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                    WHERE b.id_ruangan = r.id_ruangan
                    AND sb.nama_status = 'AKTIF'
                    AND s.tanggal_schedule = CURDATE()
                    AND CONCAT(s.tanggal_schedule, ' ', s.waktu_mulai) <= DATE_ADD(NOW(), INTERVAL 5 MINUTE)
                    AND CONCAT(s.tanggal_schedule, ' ', s.waktu_selesai) >= NOW()
                )
                AND r.status_ruangan = 'Tersedia'";
        $stmt1 = $this->pdo->prepare($sql1);
        $stmt1->execute();
        $updated1 = $stmt1->rowCount();
        
        // Update jadi 'Tersedia' untuk ruangan yang tidak ada booking aktif atau sudah lewat
        $sql2 = "UPDATE ruangan r
                SET r.status_ruangan = 'Tersedia'
                WHERE NOT EXISTS (
                    SELECT 1 FROM booking b
                    JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                    JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                    WHERE b.id_ruangan = r.id_ruangan
                    AND sb.nama_status = 'AKTIF'
                    AND s.tanggal_schedule = CURDATE()
                    AND CONCAT(s.tanggal_schedule, ' ', s.waktu_mulai) <= DATE_ADD(NOW(), INTERVAL 5 MINUTE)
                    AND CONCAT(s.tanggal_schedule, ' ', s.waktu_selesai) >= NOW()
                )
                AND r.status_ruangan = 'Tidak Tersedia'
                AND r.jenis_ruangan = 'Ruang Umum'";
        $stmt2 = $this->pdo->prepare($sql2);
        $stmt2->execute();
        $updated2 = $stmt2->rowCount();
        
        return $updated1 + $updated2;
    }
}
