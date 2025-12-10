<?php 

/**
 * Koneksi - Class untuk mengelola koneksi database
 * 
 * Menggunakan Singleton pattern untuk memastikan hanya ada satu koneksi PDO
 */
class Koneksi
{
    private static ?PDO $instance = null;
    private PDO $pdo;

    private string $host = '127.0.0.1';
    private string $dbName = 'PBL-Perpustakaan';
    private string $user = 'root';
    private string $pass = '';

    public function __construct()
    {
        // Gunakan singleton untuk reuse koneksi
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4",
                    $this->user,
                    $this->pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                die('Database connection failed: ' . $e->getMessage());
            }
        }
        $this->pdo = self::$instance;
    }

    /**
     * Ambil instance PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Static method untuk mendapatkan PDO instance langsung
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            new self();
        }
        return self::$instance;
    }
}

// Untuk backward compatibility dengan kode lama yang menggunakan $pdo langsung
$pdo = Koneksi::getConnection();

?>