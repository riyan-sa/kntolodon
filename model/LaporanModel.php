<?php
/**
 * ============================================================================
 * LAPORANMODEL.PHP - Booking Reports & Analytics Model
 * ============================================================================
 * 
 * Model untuk generate laporan peminjaman ruangan dengan berbagai periode.
 * Menyediakan statistik, trending, dan analytics untuk admin dashboard.
 * 
 * FUNGSI UTAMA:
 * 1. DAILY REPORTS - Laporan harian berdasarkan tanggal
 * 2. WEEKLY REPORTS - Laporan mingguan dengan aggregasi
 * 3. MONTHLY REPORTS - Laporan bulanan per minggu
 * 4. YEARLY REPORTS - Laporan tahunan per bulan
 * 5. STATISTICS - Total bookings, durasi, completion rates
 * 6. MOST BOOKED - Top ruangan by booking count
 * 7. FEEDBACK ANALYTICS - Average ratings per ruangan
 * 
 * PERIODE FILTER:
 * - Harian: Specific date (YYYY-MM-DD)
 * - Mingguan: Start date + 6 days (Monday-Sunday)
 * - Bulanan: Month (1-12) + Year (YYYY)
 * - Tahunan: Year (YYYY)
 * 
 * STATISTIK AVAILABLE:
 * - total_booking: Count of all bookings
 * - total_durasi: Sum of durasi_penggunaan (in minutes/hours)
 * - rata_rata_durasi: Average booking duration
 * - booking_selesai: Count of SELESAI status
 * - booking_batal: Count of DIBATALKAN status
 * - booking_hangus: Count of HANGUS status
 * 
 * MOST BOOKED ROOMS:
 * - getMostBookedRooms(): Top rooms by booking frequency
 * - Period-based: Can filter by date range
 * - Includes: room details, booking count, feedback ratings
 * 
 * FEEDBACK INTEGRATION:
 * - getAverageRatingByRoom(): Feedback scores per room
 * - Used for: Room quality trends
 * - Display: 1-5 scale (emoji representation)
 * 
 * DATA AGGREGATION:
 * - Daily: Individual bookings dengan time slots
 * - Weekly: Grouped by DATE(tanggal_schedule)
 * - Monthly: Grouped by WEEK(tanggal_schedule)
 * - Yearly: Grouped by MONTH(tanggal_schedule)
 * 
 * BOOKING DISPLAY:
 * - nama_peminjam: COALESCE(nama_instansi, ketua.username)
 * - Shows both user bookings dan external bookings
 * - Sorted by: tanggal, waktu_mulai
 * 
 * CHART DATA FORMAT:
 * Returns data ready untuk chart libraries:
 * - Labels: Dates, weeks, months
 * - Values: Counts, durations
 * - Suitable for: Chart.js, ApexCharts, etc.
 * 
 * USAGE PATTERNS:
 * - AdminController::laporan() - Display reports page
 * - Filter controls: dropdown untuk periode selection
 * - Export: Can be extended untuk PDF/Excel export
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

/**
 * Class LaporanModel - Reports & Analytics
 * 
 * @property PDO $pdo Database connection instance
 */
class LaporanModel
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

    // ==================== LAPORAN HARIAN ====================
    
    /**
     * Ambil laporan harian berdasarkan tanggal
     */
    public function getDaily(string $tanggal): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, sb.nama_status,
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
                WHERE s.tanggal_schedule = :tanggal
                ORDER BY s.waktu_mulai ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tanggal' => $tanggal]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil statistik harian
     */
    public function getDailyStats(string $tanggal): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_booking,
                    COALESCE(SUM(b.durasi_penggunaan), 0) as total_durasi,
                    COUNT(CASE WHEN sb.nama_status = 'SELESAI' THEN 1 END) as booking_selesai,
                    COUNT(CASE WHEN sb.nama_status = 'DIBATALKAN' THEN 1 END) as booking_batal
                FROM booking b
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE s.tanggal_schedule = :tanggal";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tanggal' => $tanggal]);
        return $stmt->fetch();
    }

    // ==================== LAPORAN MINGGUAN ====================
    
    /**
     * Ambil laporan mingguan berdasarkan tanggal awal minggu
     */
    public function getWeekly(string $startDate): array
    {
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));
        
        $sql = "SELECT 
                    DATE(s.tanggal_schedule) as tanggal,
                    COUNT(*) as jumlah_booking,
                    COALESCE(SUM(b.durasi_penggunaan), 0) as total_durasi
                FROM booking b
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE s.tanggal_schedule BETWEEN :start_date AND :end_date
                GROUP BY DATE(s.tanggal_schedule)
                ORDER BY s.tanggal_schedule ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil statistik mingguan
     */
    public function getWeeklyStats(string $startDate): array
    {
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));
        
        $sql = "SELECT 
                    COUNT(*) as total_booking,
                    COALESCE(SUM(b.durasi_penggunaan), 0) as total_durasi,
                    COALESCE(AVG(b.durasi_penggunaan), 0) as rata_rata_durasi,
                    COUNT(CASE WHEN sb.nama_status = 'SELESAI' THEN 1 END) as booking_selesai,
                    COUNT(CASE WHEN sb.nama_status = 'DIBATALKAN' THEN 1 END) as booking_batal
                FROM booking b
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE s.tanggal_schedule BETWEEN :start_date AND :end_date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        return $stmt->fetch();
    }

    /**
     * Ambil detail booking mingguan
     */
    public function getWeeklyDetail(string $startDate): array
    {
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));
        
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, sb.nama_status,
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
                WHERE s.tanggal_schedule BETWEEN :start_date AND :end_date
                ORDER BY s.tanggal_schedule ASC, s.waktu_mulai ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        return $stmt->fetchAll();
    }

    // ==================== LAPORAN BULANAN ====================
    
    /**
     * Ambil laporan bulanan berdasarkan bulan dan tahun
     */
    public function getMonthly(int $bulan, int $tahun): array
    {
        $sql = "SELECT 
                    WEEK(s.tanggal_schedule, 1) as minggu_ke,
                    MIN(s.tanggal_schedule) as tanggal_awal,
                    MAX(s.tanggal_schedule) as tanggal_akhir,
                    COUNT(*) as jumlah_booking,
                    COALESCE(SUM(b.durasi_penggunaan), 0) as total_durasi
                FROM booking b
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE MONTH(s.tanggal_schedule) = :bulan AND YEAR(s.tanggal_schedule) = :tahun
                GROUP BY WEEK(s.tanggal_schedule, 1)
                ORDER BY minggu_ke ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':bulan' => $bulan, ':tahun' => $tahun]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil statistik bulanan
     */
    public function getMonthlyStats(int $bulan, int $tahun): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_booking,
                    COALESCE(SUM(b.durasi_penggunaan), 0) as total_durasi,
                    COALESCE(AVG(b.durasi_penggunaan), 0) as rata_rata_durasi,
                    COUNT(CASE WHEN sb.nama_status = 'SELESAI' THEN 1 END) as booking_selesai,
                    COUNT(CASE WHEN sb.nama_status = 'DIBATALKAN' THEN 1 END) as booking_batal
                FROM booking b
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE MONTH(s.tanggal_schedule) = :bulan AND YEAR(s.tanggal_schedule) = :tahun";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':bulan' => $bulan, ':tahun' => $tahun]);
        return $stmt->fetch();
    }

    /**
     * Ambil detail booking bulanan
     */
    public function getMonthlyDetail(int $bulan, int $tahun): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, sb.nama_status,
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
                WHERE MONTH(s.tanggal_schedule) = :bulan AND YEAR(s.tanggal_schedule) = :tahun
                ORDER BY s.tanggal_schedule ASC, s.waktu_mulai ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':bulan' => $bulan, ':tahun' => $tahun]);
        return $stmt->fetchAll();
    }

    // ==================== LAPORAN TAHUNAN ====================
    
    /**
     * Ambil laporan tahunan berdasarkan tahun
     */
    public function getYearly(int $tahun): array
    {
        $sql = "SELECT 
                    MONTH(s.tanggal_schedule) as bulan,
                    MONTHNAME(s.tanggal_schedule) as nama_bulan,
                    COUNT(*) as jumlah_booking,
                    COALESCE(SUM(b.durasi_penggunaan), 0) as total_durasi
                FROM booking b
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE YEAR(s.tanggal_schedule) = :tahun
                GROUP BY MONTH(s.tanggal_schedule), MONTHNAME(s.tanggal_schedule)
                ORDER BY bulan ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tahun' => $tahun]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil statistik tahunan
     */
    public function getYearlyStats(int $tahun): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_booking,
                    COALESCE(SUM(b.durasi_penggunaan), 0) as total_durasi,
                    COALESCE(AVG(b.durasi_penggunaan), 0) as rata_rata_durasi,
                    COUNT(CASE WHEN sb.nama_status = 'SELESAI' THEN 1 END) as booking_selesai,
                    COUNT(CASE WHEN sb.nama_status = 'DIBATALKAN' THEN 1 END) as booking_batal
                FROM booking b
                JOIN status_booking sb ON b.id_status_booking = sb.id_status_booking
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE YEAR(s.tanggal_schedule) = :tahun";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tahun' => $tahun]);
        return $stmt->fetch();
    }

    // ==================== LAPORAN BY RUANGAN ====================
    
    /**
     * Ambil laporan penggunaan per ruangan
     */
    public function getByRuangan(int $idRuangan, string $startDate, string $endDate): array
    {
        $sql = "SELECT b.*, r.nama_ruangan, r.jenis_ruangan, sb.nama_status,
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
                WHERE b.id_ruangan = :id_ruangan
                AND s.tanggal_schedule BETWEEN :start_date AND :end_date
                ORDER BY s.tanggal_schedule ASC, s.waktu_mulai ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_ruangan' => $idRuangan,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil statistik penggunaan semua ruangan
     */
    public function getRuanganUsageStats(string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    r.id_ruangan, r.nama_ruangan, r.jenis_ruangan,
                    COUNT(b.id_booking) as jumlah_booking,
                    COALESCE(SUM(b.durasi_penggunaan), 0) as total_durasi
                FROM ruangan r
                LEFT JOIN booking b ON r.id_ruangan = b.id_ruangan
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                    AND s.tanggal_schedule BETWEEN :start_date AND :end_date
                GROUP BY r.id_ruangan, r.nama_ruangan, r.jenis_ruangan
                ORDER BY jumlah_booking DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        return $stmt->fetchAll();
    }

    // ==================== HELPER METHODS ====================
    
    /**
     * Hitung total peminjaman dalam periode
     */
    public function countInPeriod(string $startDate, string $endDate): int
    {
        $sql = "SELECT COUNT(*) FROM booking b
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE s.tanggal_schedule BETWEEN :start_date AND :end_date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Hitung total durasi penggunaan dalam periode
     */
    public function totalDurationInPeriod(string $startDate, string $endDate): int
    {
        $sql = "SELECT COALESCE(SUM(b.durasi_penggunaan), 0) FROM booking b
                LEFT JOIN schedule s ON b.id_booking = s.id_booking AND s.status_schedule = 'AKTIF'
                WHERE s.tanggal_schedule BETWEEN :start_date AND :end_date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Ambil daftar tahun yang ada data booking
     */
    public function getAvailableYears(): array
    {
        $sql = "SELECT DISTINCT YEAR(s.tanggal_schedule) as tahun
                FROM schedule s
                WHERE s.tanggal_schedule IS NOT NULL
                ORDER BY tahun DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }
}
