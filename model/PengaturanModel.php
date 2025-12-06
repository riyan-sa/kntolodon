<?php

require_once __DIR__ . '/../config/Koneksi.php';

class PengaturanModel
{
    private PDO $pdo;

    public function __construct()
    {
        $koneksi = new Koneksi();
        $this->pdo = $koneksi->getPdo();
    }

    // ==================== WAKTU OPERASI ====================

    /**
     * Get all waktu operasi (7 hari)
     * @return array Array of waktu operasi records
     */
    public function getAllWaktuOperasi(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT wo.*, a.username as updated_by_name
            FROM waktu_operasi wo
            LEFT JOIN akun a ON wo.updated_by = a.nomor_induk
            ORDER BY FIELD(wo.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get waktu operasi by hari
     * @param string $hari Nama hari (Senin-Minggu)
     * @return array|false Waktu operasi record or false if not found
     */
    public function getWaktuOperasiByHari(string $hari): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM waktu_operasi WHERE hari = ?
        ");
        $stmt->execute([$hari]);
        return $stmt->fetch();
    }

    /**
     * Update waktu operasi untuk hari tertentu
     * @param string $hari Nama hari
     * @param string $jamBuka Format HH:MM:SS
     * @param string $jamTutup Format HH:MM:SS
     * @param int $isAktif 1=aktif, 0=nonaktif
     * @param string $updatedBy Nomor induk admin
     * @return bool Success status
     */
    public function updateWaktuOperasi(string $hari, string $jamBuka, string $jamTutup, int $isAktif, string $updatedBy): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE waktu_operasi 
                SET jam_buka = ?, jam_tutup = ?, is_aktif = ?, updated_by = ?
                WHERE hari = ?
            ");
            return $stmt->execute([$jamBuka, $jamTutup, $isAktif, $updatedBy, $hari]);
        } catch (Exception $e) {
            error_log("Error updating waktu operasi: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if booking time is within operational hours
     * @param string $hari Nama hari (Senin-Minggu)
     * @param string $waktuMulai Format HH:MM or HH:MM:SS
     * @param string $waktuSelesai Format HH:MM or HH:MM:SS
     * @return array ['allowed' => bool, 'message' => string]
     */
    public function validateWaktuOperasi(string $hari, string $waktuMulai, string $waktuSelesai): array
    {
        $waktuOp = $this->getWaktuOperasiByHari($hari);
        
        if (!$waktuOp) {
            return ['allowed' => false, 'message' => 'Data waktu operasi tidak ditemukan'];
        }

        if ($waktuOp['is_aktif'] == 0) {
            return ['allowed' => false, 'message' => "Perpustakaan tutup pada hari {$hari}"];
        }

        // Normalize time format to HH:MM:SS for comparison
        $waktuMulai = strlen($waktuMulai) == 5 ? $waktuMulai . ':00' : $waktuMulai;
        $waktuSelesai = strlen($waktuSelesai) == 5 ? $waktuSelesai . ':00' : $waktuSelesai;

        // CRITICAL FIX: Check BOTH start and end time are within operational hours
        if ($waktuMulai < $waktuOp['jam_buka']) {
            return [
                'allowed' => false,
                'message' => "Waktu mulai ({$waktuMulai}) sebelum jam buka ({$waktuOp['jam_buka']}). Perpustakaan buka pukul {$waktuOp['jam_buka']} - {$waktuOp['jam_tutup']}"
            ];
        }

        if ($waktuSelesai > $waktuOp['jam_tutup']) {
            return [
                'allowed' => false, 
                'message' => "Waktu selesai ({$waktuSelesai}) melebihi jam tutup ({$waktuOp['jam_tutup']}). Perpustakaan tutup pukul {$waktuOp['jam_tutup']}"
            ];
        }

        return ['allowed' => true, 'message' => 'OK'];
    }

    // ==================== HARI LIBUR ====================

    /**
     * Get all hari libur (ordered by tanggal descending)
     * @return array Array of hari libur records
     */
    public function getAllHariLibur(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT hl.*, a.username as created_by_name
            FROM hari_libur hl
            LEFT JOIN akun a ON hl.created_by = a.nomor_induk
            ORDER BY hl.tanggal DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get upcoming hari libur (future dates only)
     * @return array Array of upcoming hari libur
     */
    public function getUpcomingHariLibur(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT hl.*, a.username as created_by_name
            FROM hari_libur hl
            LEFT JOIN akun a ON hl.created_by = a.nomor_induk
            WHERE hl.tanggal >= CURDATE()
            ORDER BY hl.tanggal ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get hari libur by ID
     * @param int $id ID hari libur
     * @return array|false Hari libur record or false
     */
    public function getHariLiburById(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM hari_libur WHERE id_hari_libur = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Check if date is hari libur
     * @param string $tanggal Format YYYY-MM-DD
     * @return bool True if tanggal is hari libur
     */
    public function isHariLibur(string $tanggal): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM hari_libur WHERE tanggal = ?");
        $stmt->execute([$tanggal]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get hari libur info by date
     * @param string $tanggal Format YYYY-MM-DD
     * @return array|false Hari libur record or false
     */
    public function getHariLiburByDate(string $tanggal): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM hari_libur WHERE tanggal = ?");
        $stmt->execute([$tanggal]);
        return $stmt->fetch();
    }

    /**
     * Create new hari libur
     * @param string $tanggal Format YYYY-MM-DD
     * @param string $keterangan Alasan libur
     * @param string $createdBy Nomor induk Super Admin
     * @return int|false ID hari libur baru or false on failure
     */
    public function createHariLibur(string $tanggal, string $keterangan, string $createdBy): int|false
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO hari_libur (tanggal, keterangan, created_by) 
                VALUES (?, ?, ?)
            ");
            
            if ($stmt->execute([$tanggal, $keterangan, $createdBy])) {
                return (int) $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            // Handle duplicate entry
            if ($e->getCode() == 23000) {
                error_log("Duplicate hari libur: {$tanggal}");
            }
            error_log("Error creating hari libur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update hari libur
     * @param int $id ID hari libur
     * @param string $tanggal Format YYYY-MM-DD
     * @param string $keterangan Alasan libur
     * @return bool Success status
     */
    public function updateHariLibur(int $id, string $tanggal, string $keterangan): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE hari_libur 
                SET tanggal = ?, keterangan = ?
                WHERE id_hari_libur = ?
            ");
            return $stmt->execute([$tanggal, $keterangan, $id]);
        } catch (Exception $e) {
            error_log("Error updating hari libur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete hari libur
     * @param int $id ID hari libur
     * @return bool Success status
     */
    public function deleteHariLibur(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM hari_libur WHERE id_hari_libur = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error deleting hari libur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate booking date against hari libur
     * @param string $tanggal Format YYYY-MM-DD
     * @return array ['allowed' => bool, 'message' => string]
     */
    public function validateHariLibur(string $tanggal): array
    {
        $hariLibur = $this->getHariLiburByDate($tanggal);
        
        if ($hariLibur) {
            return [
                'allowed' => false, 
                'message' => "Tidak bisa booking pada tanggal ini. Alasan: {$hariLibur['keterangan']}"
            ];
        }

        return ['allowed' => true, 'message' => 'OK'];
    }
}
