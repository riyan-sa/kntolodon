<?php

/**
 * FeedbackModel - Model untuk CRUD feedback booking
 * 
 * Relasi:
 * - feedback -> booking (N:1) via id_booking
 */
class FeedbackModel
{
    private PDO $pdo;

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
