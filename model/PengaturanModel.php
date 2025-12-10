<?php
/**
 * ============================================================================
 * PENGATURANMODEL.PHP - System Settings Management Model
 * ============================================================================
 * 
 * Model untuk CRUD operations pada pengaturan sistem (Super Admin only).
 * Mengelola waktu operasi perpustakaan dan hari libur.
 * 
 * FUNGSI UTAMA:
 * 1. WAKTU OPERASI - Manage operational hours (7 days)
 * 2. HARI LIBUR - Manage holiday calendar
 * 3. VALIDATION - Validate booking times against operational constraints
 * 4. CRUD - Create, read, update, delete settings
 * 
 * DATABASE TABLES:
 * - waktu_operasi: Operational hours per day (7 records: Senin-Minggu)
 * - hari_libur: Holiday calendar (dynamic dates)
 * 
 * WAKTU OPERASI TABLE:
 * Fields:
 * - hari: Day name (Senin, Selasa, Rabu, Kamis, Jumat, Sabtu, Minggu)
 * - jam_buka: Opening time (TIME: HH:MM:SS)
 * - jam_tutup: Closing time (TIME: HH:MM:SS)
 * - is_aktif: 1=open, 0=closed (can disable booking for specific days)
 * - updated_by: Nomor induk Super Admin who last updated
 * 
 * HARI LIBUR TABLE:
 * Fields:
 * - id_hari_libur: Primary key
 * - tanggal: Holiday date (DATE: YYYY-MM-DD)
 * - keterangan: Holiday description/reason
 * - created_by: Nomor induk Super Admin who created
 * 
 * OPERATIONAL HOURS VALIDATION:
 * validateWaktuOperasi():
 * - Checks if booking time within operational hours
 * - Returns: ['allowed' => bool, 'message' => string]
 * - Logic: waktu_mulai >= jam_buka AND waktu_selesai <= jam_tutup
 * - Also checks: is_aktif == 1 (day is operational)
 * 
 * HOLIDAY VALIDATION:
 * validateHariLibur() / isHariLibur():
 * - Checks if booking date is registered holiday
 * - Returns: bool (true if holiday, false if regular day)
 * - Blocks booking creation on holidays
 * 
 * INTEGRATION POINTS:
 * - BookingController::buat_booking(): Validates before creating booking
 * - AdminController::booking_external(): Validates external bookings
 * - Dashboard: Shows "Perpustakaan tutup" message on closed days
 * 
 * CRITICAL VALIDATION FLOW:
 * 1. User selects tanggal + waktu_mulai + waktu_selesai
 * 2. Get hari from tanggal (e.g., 'Senin')
 * 3. validateWaktuOperasi(hari, waktu_mulai, waktu_selesai)
 * 4. isHariLibur(tanggal)
 * 5. If both pass, allow booking creation
 * 6. If either fails, show error message to user
 * 
 * TIME FORMAT NORMALIZATION:
 * - Input: HH:MM or HH:MM:SS
 * - Stored: HH:MM:SS (normalized)
 * - Comparison: String comparison (e.g., '09:00:00' < '17:00:00')
 * 
 * SUPER ADMIN ONLY:
 * - All write operations require Super Admin role
 * - Checked in AdminController::pengaturan()
 * - Regular admins have read-only access
 * 
 * USAGE PATTERNS:
 * - AdminController::pengaturan() - Settings page with 2 tabs
 * - AdminController::update_waktu_operasi() - Edit operational hours
 * - AdminController::create_hari_libur() - Add new holiday
 * - AdminController::update_hari_libur() - Edit existing holiday
 * - AdminController::delete_hari_libur() - Remove holiday
 * 
 * VIEW INTEGRATION:
 * - view/admin/pengaturan.php: Settings interface
 * - assets/js/pengaturan.js: Tab switching + modal handling
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

require_once __DIR__ . '/../config/Koneksi.php';

/**
 * Class PengaturanModel - System Settings Management
 * 
 * @property PDO $pdo Database connection instance
 */
class PengaturanModel
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
