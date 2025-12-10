<?php
/**
 * ============================================================================
 * FEEDBACKMODEL.PHP - Booking Feedback Management Model
 * ============================================================================
 * 
 * Model untuk CRUD operations pada feedback post-booking.
 * Mengumpulkan user satisfaction data untuk room quality improvement.
 * 
 * FUNGSI UTAMA:
 * 1. CREATE - Submit feedback after booking completion
 * 2. READ - Fetch feedback by booking, get all feedback
 * 3. UPDATE - Edit existing feedback (if allowed)
 * 4. DELETE - Remove feedback (admin only)
 * 5. STATISTICS - Average ratings, total count
 * 6. VALIDATION - Check if booking already has feedback
 * 
 * DATABASE TABLE: feedback
 * PRIMARY KEY: id_feedback (auto-increment)
 * FOREIGN KEYS:
 * - id_booking → booking.id_booking (which booking this feedback is for)
 * 
 * RELASI DATABASE:
 * - feedback -> booking (N:1) via id_booking (one booking can have max 1 feedback)
 * 
 * FEEDBACK FIELDS:
 * - skala_kepuasan: INT (1 or 5)
 *   - 1 = Tidak Puas (sad emoji/red)
 *   - 5 = Sangat Puas (happy emoji/green)
 * - kritik_saran: TEXT (required)
 *   - Gabungan kritik dan saran dalam satu field
 *   - User must provide text feedback
 * 
 * FEEDBACK FLOW:
 * 1. Booking status becomes SELESAI (completed)
 * 2. User redirected to feedback page (view/feedback.php)
 * 3. User selects rating (1 or 5) via emoji buttons
 * 4. User writes kritik_saran (required field)
 * 5. Submit creates feedback record
 * 6. One feedback per booking (checked via hasFeedback())
 * 
 * EMOJI REPRESENTATION:
 * - Rating 5: Senyum (Green) - Excellent experience
 * - Rating 3: Netral (Yellow) - Neutral experience (not used)
 * - Rating 1: Sedih (Red) - Poor experience
 * 
 * BUSINESS RULES:
 * - ONE FEEDBACK PER BOOKING - hasFeedback() validates uniqueness
 * - ONLY AFTER SELESAI - Only completed bookings can receive feedback
 * - KRITIK_SARAN REQUIRED - Cannot submit empty text feedback
 * - NO EDIT - Once submitted, feedback is final (can be relaxed)
 * 
 * ANALYTICS INTEGRATION:
 * - getAverageRating(): Overall satisfaction score
 * - Used in: LaporanModel for room quality trends
 * - Display: Admin dashboard shows average ratings
 * 
 * USAGE PATTERNS:
 * - FeedbackController: Handle submission (if exists)
 * - BookingController: Redirect to feedback after completion
 * - LaporanModel: Include feedback data in reports
 * - Admin dashboard: Display recent feedback
 * 
 * VIEW INTEGRATION:
 * - view/feedback.php: Feedback form with emoji selection
 * - assets/js/feedback.js: Handle rating selection UI
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

/**
 * Class FeedbackModel - Feedback Management
 * 
 * @property PDO $pdo Database connection instance
 */
class FeedbackModel
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
     * Buat feedback baru untuk booking
     * 
     * @param array $data Data feedback dengan keys: id_booking, kritik_saran, skala_kepuasan
     * @return int|false ID feedback yang dibuat atau false jika gagal
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO feedback (id_booking, kritik_saran, skala_kepuasan)
                VALUES (:id_booking, :kritik_saran, :skala_kepuasan)";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':id_booking' => $data['id_booking'],
            ':kritik_saran' => $data['kritik_saran'] ?? '',
            ':skala_kepuasan' => $data['skala_kepuasan']
        ]);

        return $result ? (int) $this->pdo->lastInsertId() : false;
    }

    // ==================== READ ====================
    
    /**
     * Ambil semua feedback
     */
    public function getAll(): array
    {
        $sql = "SELECT f.*, b.kode_booking 
                FROM feedback f
                JOIN booking b ON f.id_booking = b.id_booking
                ORDER BY f.id_feedback DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil feedback berdasarkan ID
     */
    public function getById(int $id): array|false
    {
        $sql = "SELECT f.*, b.kode_booking 
                FROM feedback f
                JOIN booking b ON f.id_booking = b.id_booking
                WHERE f.id_feedback = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Ambil feedback berdasarkan booking ID
     */
    public function getByBookingId(int $idBooking): array|false
    {
        $sql = "SELECT * FROM feedback WHERE id_booking = :id_booking LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_booking' => $idBooking]);
        return $stmt->fetch();
    }

    /**
     * Cek apakah booking sudah ada feedback
     */
    public function hasFeedback(int $idBooking): bool
    {
        $sql = "SELECT COUNT(*) FROM feedback WHERE id_booking = :id_booking";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_booking' => $idBooking]);
        return $stmt->fetchColumn() > 0;
    }

    // ==================== UPDATE ====================
    
    /**
     * Update feedback
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE feedback SET 
                kritik_saran = :kritik_saran,
                skala_kepuasan = :skala_kepuasan
                WHERE id_feedback = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':kritik_saran' => $data['kritik_saran'] ?? '',
            ':skala_kepuasan' => $data['skala_kepuasan']
        ]);
    }

    // ==================== DELETE ====================
    
    /**
     * Hapus feedback
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM feedback WHERE id_feedback = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // ==================== STATISTICS ====================
    
    /**
     * Hitung rata-rata kepuasan
     */
    public function getAverageRating(): float
    {
        $sql = "SELECT AVG(skala_kepuasan) as avg_rating FROM feedback";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();
        return (float) ($result['avg_rating'] ?? 0);
    }

    /**
     * Hitung total feedback
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM feedback";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Ambil feedback terbaru
     */
    public function getLatest(int $limit = 10): array
    {
        $sql = "SELECT f.*, b.kode_booking 
                FROM feedback f
                JOIN booking b ON f.id_booking = b.id_booking
                ORDER BY f.id_feedback DESC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
