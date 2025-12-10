<?php
/**
 * ============================================================================
 * AKUNMODEL.PHP - Account Management Model
 * ============================================================================
 * 
 * Model untuk Complete CRUD operations pada tabel akun (user accounts).
 * Menangani authentication, registration, profile management, dan user filtering.
 * 
 * FUNGSI UTAMA:
 * 1. CREATE - Register user baru, create admin account
 * 2. READ - Fetch accounts by various criteria (email, NIM, role, etc.)
 * 3. UPDATE - Update account info, password, status, role, foto profil
 * 4. DELETE - Soft delete (set status) atau hard delete
 * 5. FILTER/SEARCH - Advanced filtering dengan pagination
 * 6. VALIDATION - Check uniqueness (email, username, NIM)
 * 7. AUTHENTICATION - Login by email/username/NIM dengan password verify
 * 8. STATISTICS - Count users by role, status
 * 
 * DATABASE TABLE: akun
 * PRIMARY KEY: nomor_induk (NIM/NIP)
 * 
 * RELASI DATABASE:
 * - akun -> anggota_booking (1:N) via nomor_induk (booking participants)
 * - akun -> pelanggaran_suspensi (1:N) via nomor_induk (violations/suspensions)
 * 
 * ROLE HIERARCHY:
 * - User: Regular users (Mahasiswa/Dosen/Tenaga Pendidikan)
 * - Admin: System administrators (manage rooms, bookings, users)
 * - Super Admin: Full system access (manage admins, system settings)
 * 
 * STATUS VALUES:
 * - Aktif: Account active, can login and use system
 * - Tidak Aktif: Account pending approval atau disabled
 * 
 * EMAIL VALIDATION:
 * - Mahasiswa: nama.x@stu.pnj.ac.id (single char before @stu)
 * - Dosen/Admin: nama@jurusan.pnj.ac.id or nama@pnj.ac.id
 * 
 * PASSWORD SECURITY:
 * - Hashed using password_hash() with PASSWORD_DEFAULT (bcrypt)
 * - Verified using password_verify()
 * - Minimum 8 characters enforced at controller level
 * 
 * USAGE PATTERNS:
 * - LoginController: authentication methods
 * - RegisterController: create new user accounts
 * - AdminController: manage users (activate, edit, delete)
 * - ProfileController: update user profile data
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

/**
 * Class AkunModel - Account Management dengan comprehensive CRUD operations
 * 
 * @property PDO $pdo Database connection instance
 */
class AkunModel
{
    /**
     * PDO instance untuk database operations
     * Initialized via Koneksi singleton
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructor - Initialize PDO connection
     * 
     * Mendapatkan PDO instance dari Koneksi class untuk database access.
     * Koneksi menggunakan Singleton pattern untuk reuse connection.
     */
    public function __construct()
    {
        $koneksi = new Koneksi();
        $this->pdo = $koneksi->getPdo();
    }

    // ==================== CREATE ====================
    
    /**
     * Buat akun baru
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO akun (nomor_induk, username, password, email, status, role, jurusan, prodi, foto_profil, validasi_mahasiswa)
                VALUES (:nomor_induk, :username, :password, :email, :status, :role, :jurusan, :prodi, :foto_profil, :validasi_mahasiswa)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nomor_induk' => $data['nomor_induk'],
            ':username' => $data['username'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':email' => $data['email'],
            ':status' => $data['status'] ?? 'Tidak Aktif',
            ':role' => $data['role'] ?? 'User',
            ':jurusan' => $data['jurusan'] ?? null,
            ':prodi' => $data['prodi'] ?? null,
            ':foto_profil' => $data['foto_profil'] ?? null,
            ':validasi_mahasiswa' => $data['validasi_mahasiswa'] ?? null
        ]);
    }

    /**
     * Register akun baru (untuk user)
     */
    public function register(string $nomorInduk, string $username, string $password, string $email, string $role = 'User', ?string $jurusan = null, ?string $prodi = null, ?string $validasiMahasiswa = null): bool
    {
        return $this->create([
            'nomor_induk' => $nomorInduk,
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'status' => 'Tidak Aktif',
            'role' => $role,
            'jurusan' => $jurusan,
            'prodi' => $prodi,
            'foto_profil' => null,
            'validasi_mahasiswa' => $validasiMahasiswa
        ]);
    }

    // ==================== READ ====================
    
    /**
     * Ambil semua akun
     */
    public function getAll(): array
    {
        $sql = "SELECT nomor_induk, username, email, status, role, foto_profil FROM akun ORDER BY username ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil akun berdasarkan nomor induk
     */
    public function getByNomorInduk(string $nomorInduk): array|false
    {
        $sql = "SELECT * FROM akun WHERE nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetch();
    }

    /**
     * Ambil akun berdasarkan email
     */
    public function getByEmail(string $email): array|false
    {
        $sql = "SELECT * FROM akun WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Ambil akun berdasarkan username
     */
    public function getByUsername(string $username): array|false
    {
        $sql = "SELECT * FROM akun WHERE username = :username";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    /**
     * Ambil akun berdasarkan role
     */
    public function getByRole(string $role): array
    {
        $sql = "SELECT nomor_induk, username, email, status, role, foto_profil FROM akun WHERE role = :role ORDER BY username ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':role' => $role]);
        return $stmt->fetchAll();
    }

    /**
     * Login - verifikasi kredensial
     */
    public function login(string $nomorInduk, string $password): array|false
    {
        $akun = $this->getByNomorInduk($nomorInduk);
        
        if ($akun && password_verify($password, $akun['password'])) {
            // Hapus password dari hasil untuk keamanan
            unset($akun['password']);
            return $akun;
        }
        
        return false;
    }

    /**
     * Login by email
     */
    public function loginByEmail(string $email, string $password): array|false
    {
        $akun = $this->getByEmail($email);
        
        if ($akun && password_verify($password, $akun['password'])) {
            unset($akun['password']);
            return $akun;
        }
        
        return false;
    }

    /**
     * Login by username
     */
    public function loginByUsername(string $username, string $password): array|false
    {
        $akun = $this->getByUsername($username);
        
        if ($akun && password_verify($password, $akun['password'])) {
            unset($akun['password']);
            return $akun;
        }
        
        return false;
    }

    // ==================== UPDATE ====================
    
    /**
     * Update akun
     */
    public function update(string $nomorInduk, array $data): bool
    {
        $sql = "UPDATE akun SET 
                username = :username,
                email = :email,
                status = :status,
                role = :role
                WHERE nomor_induk = :nomor_induk";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nomor_induk' => $nomorInduk,
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':status' => $data['status'],
            ':role' => $data['role']
        ]);
    }

    /**
     * Update password
     */
    public function updatePassword(string $nomorInduk, string $newPassword): bool
    {
        $sql = "UPDATE akun SET password = :password WHERE nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nomor_induk' => $nomorInduk,
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }

    /**
     * Update status
     */
    public function updateStatus(string $nomorInduk, string $status): bool
    {
        $sql = "UPDATE akun SET status = :status WHERE nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':nomor_induk' => $nomorInduk, ':status' => $status]);
    }

    /**
     * Update role
     */
    public function updateRole(string $nomorInduk, string $role): bool
    {
        $sql = "UPDATE akun SET role = :role WHERE nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':nomor_induk' => $nomorInduk, ':role' => $role]);
    }

    /**
     * Update foto profil
     */
    public function updateFotoProfil(string $nomorInduk, ?string $fotoProfil): bool
    {
        $sql = "UPDATE akun SET foto_profil = :foto_profil WHERE nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':nomor_induk' => $nomorInduk, ':foto_profil' => $fotoProfil]);
    }

    // ==================== DELETE ====================
    
    /**
     * Hapus akun (soft delete dengan mengubah status)
     */
    public function softDelete(string $nomorInduk): bool
    {
        return $this->updateStatus($nomorInduk, 'Tidak Aktif');
    }

    /**
     * Hapus akun permanen
     * CATATAN: Akan gagal jika ada relasi FK (anggota_booking, pelanggaran_suspensi)
     */
    public function delete(string $nomorInduk): bool
    {
        $sql = "DELETE FROM akun WHERE nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':nomor_induk' => $nomorInduk]);
    }

    // ==================== FILTER / SEARCH ====================
    
    /**
     * Filter akun dengan berbagai kriteria
     */
    public function filter(array $filters = []): array
    {
        $sql = "SELECT nomor_induk, username, email, status, role, foto_profil FROM akun WHERE 1=1";
        $params = [];

        // Filter by username/nama
        if (!empty($filters['nama']) || !empty($filters['username'])) {
            $nama = $filters['nama'] ?? $filters['username'];
            $sql .= " AND username LIKE :nama";
            $params[':nama'] = '%' . $nama . '%';
        }

        // Filter by nomor induk
        if (!empty($filters['nomor_induk'])) {
            $sql .= " AND nomor_induk LIKE :nomor_induk";
            $params[':nomor_induk'] = '%' . $filters['nomor_induk'] . '%';
        }

        // Filter by email
        if (!empty($filters['email'])) {
            $sql .= " AND email LIKE :email";
            $params[':email'] = '%' . $filters['email'] . '%';
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        // Filter by role
        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
            $params[':role'] = $filters['role'];
        }

        // Filter by multiple roles
        if (!empty($filters['roles']) && is_array($filters['roles'])) {
            $placeholders = [];
            foreach ($filters['roles'] as $index => $role) {
                $key = ':role_' . $index;
                $placeholders[] = $key;
                $params[$key] = $role;
            }
            $sql .= " AND role IN (" . implode(', ', $placeholders) . ")";
        }

        $sql .= " ORDER BY username ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Search akun by keyword
     */
    public function search(string $keyword): array
    {
        $sql = "SELECT nomor_induk, username, email, status, role, foto_profil FROM akun 
                WHERE username LIKE :keyword OR nomor_induk LIKE :keyword OR email LIKE :keyword
                ORDER BY username ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':keyword' => '%' . $keyword . '%']);
        return $stmt->fetchAll();
    }

    // ==================== VALIDATION ====================
    
    /**
     * Cek apakah nomor induk sudah ada
     */
    public function isNomorIndukExists(string $nomorInduk): bool
    {
        $sql = "SELECT COUNT(*) FROM akun WHERE nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Cek apakah email sudah ada
     */
    public function isEmailExists(string $email, ?string $excludeNomorInduk = null): bool
    {
        $sql = "SELECT COUNT(*) FROM akun WHERE email = :email";
        $params = [':email' => $email];
        
        if ($excludeNomorInduk !== null) {
            $sql .= " AND nomor_induk != :exclude";
            $params[':exclude'] = $excludeNomorInduk;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Cek apakah username sudah ada
     */
    public function isUsernameExists(string $username, ?string $excludeNomorInduk = null): bool
    {
        $sql = "SELECT COUNT(*) FROM akun WHERE username = :username";
        $params = [':username' => $username];
        
        if ($excludeNomorInduk !== null) {
            $sql .= " AND nomor_induk != :exclude";
            $params[':exclude'] = $excludeNomorInduk;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verifikasi password
     */
    public function verifyPassword(string $nomorInduk, string $password): bool
    {
        $akun = $this->getByNomorInduk($nomorInduk);
        return $akun && password_verify($password, $akun['password']);
    }

    // ==================== STATISTICS ====================
    
    /**
     * Hitung total akun
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM akun";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Hitung akun aktif
     */
    public function countActive(): int
    {
        $sql = "SELECT COUNT(*) FROM akun WHERE status = 'Aktif'";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Hitung akun berdasarkan role
     */
    public function countByRole(string $role): int
    {
        $sql = "SELECT COUNT(*) FROM akun WHERE role = :role";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':role' => $role]);
        return (int) $stmt->fetchColumn();
    }

    // ==================== MEMBER LIST SPECIFIC ====================
    
    /**
     * Ambil semua user (Mahasiswa, Dosen, Tenaga Pendidikan) dengan prodi
     */
    public function getUsersWithProdi(): array
    {
        $sql = "SELECT nomor_induk, username, email, status, role, prodi, foto_profil, validasi_mahasiswa 
                FROM akun 
                WHERE role IN ('Mahasiswa', 'Dosen', 'Tenaga Pendidikan', 'User')
                ORDER BY username ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil semua admin (Admin, Super Admin)
     */
    public function getAdminsWithDetails(): array
    {
        $sql = "SELECT nomor_induk, username, email, status, role, foto_profil 
                FROM akun 
                WHERE role IN ('Admin', 'Super Admin')
                ORDER BY username ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil semua prodi yang tersedia untuk filter
     */
    public function getAllProdi(): array
    {
        $sql = "SELECT DISTINCT prodi FROM akun 
                WHERE prodi IS NOT NULL AND prodi != '' 
                AND role IN ('Mahasiswa', 'Dosen', 'Tenaga Pendidikan', 'User')
                ORDER BY prodi ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Filter users dengan pagination dan search
     */
    public function filterUsers(array $filters = []): array
    {
        $sql = "SELECT nomor_induk, username, email, status, role, prodi, foto_profil, validasi_mahasiswa 
                FROM akun 
                WHERE role IN ('Mahasiswa', 'Dosen', 'Tenaga Pendidikan', 'User')";
        $params = [];

        // Filter by nama
        if (!empty($filters['nama'])) {
            $sql .= " AND username LIKE :nama";
            $params[':nama'] = '%' . $filters['nama'] . '%';
        }

        // Filter by nomor_induk
        if (!empty($filters['nomor_induk'])) {
            $sql .= " AND nomor_induk LIKE :nomor_induk";
            $params[':nomor_induk'] = '%' . $filters['nomor_induk'] . '%';
        }

        // Filter by prodi
        if (!empty($filters['prodi'])) {
            $sql .= " AND prodi = :prodi";
            $params[':prodi'] = $filters['prodi'];
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY username ASC";

        // Pagination
        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = (int)$filters['limit'];
            $params[':offset'] = (int)($filters['offset'] ?? 0);
        }

        $stmt = $this->pdo->prepare($sql);
        
        // Bind parameters with proper types
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count filtered users (tanpa limit/offset)
     */
    public function countFilteredUsers(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM akun 
                WHERE role IN ('Mahasiswa', 'Dosen', 'Tenaga Pendidikan', 'User')";
        $params = [];

        if (!empty($filters['nama'])) {
            $sql .= " AND username LIKE :nama";
            $params[':nama'] = '%' . $filters['nama'] . '%';
        }

        if (!empty($filters['nomor_induk'])) {
            $sql .= " AND nomor_induk LIKE :nomor_induk";
            $params[':nomor_induk'] = '%' . $filters['nomor_induk'] . '%';
        }

        if (!empty($filters['prodi'])) {
            $sql .= " AND prodi = :prodi";
            $params[':prodi'] = $filters['prodi'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Filter admins dengan pagination dan search
     */
    public function filterAdmins(array $filters = []): array
    {
        $sql = "SELECT nomor_induk, username, email, status, role, foto_profil 
                FROM akun 
                WHERE role IN ('Admin', 'Super Admin')";
        $params = [];

        // Filter by nama
        if (!empty($filters['nama'])) {
            $sql .= " AND username LIKE :nama";
            $params[':nama'] = '%' . $filters['nama'] . '%';
        }

        // Filter by nomor_induk
        if (!empty($filters['nomor_induk'])) {
            $sql .= " AND nomor_induk LIKE :nomor_induk";
            $params[':nomor_induk'] = '%' . $filters['nomor_induk'] . '%';
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY username ASC";

        // Pagination
        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = (int)$filters['limit'];
            $params[':offset'] = (int)($filters['offset'] ?? 0);
        }

        $stmt = $this->pdo->prepare($sql);
        
        // Bind parameters with proper types
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count filtered admins (tanpa limit/offset)
     */
    public function countFilteredAdmins(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM akun 
                WHERE role IN ('Admin', 'Super Admin')";
        $params = [];

        if (!empty($filters['nama'])) {
            $sql .= " AND username LIKE :nama";
            $params[':nama'] = '%' . $filters['nama'] . '%';
        }

        if (!empty($filters['nomor_induk'])) {
            $sql .= " AND nomor_induk LIKE :nomor_induk";
            $params[':nomor_induk'] = '%' . $filters['nomor_induk'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}

