<?php

/**
 * MemberModel - Model untuk CRUD dan filter member (User dan Admin)
 * 
 * User: Mahasiswa, Dosen, Tenaga Pendidikan
 * Admin: Admin, Super Admin
 * 
 * Relasi:
 * - akun -> anggota_booking (1:N) via nomor_induk
 * - akun -> pelanggaran_suspensi (1:N) via nomor_induk
 */
class MemberModel
{
    private PDO $pdo;

    public function __construct()
    {
        $koneksi = new Koneksi();
        $this->pdo = $koneksi->getPdo();
    }

    // ==================== CREATE ====================
    
    /**
     * Tambah user baru (Mahasiswa/Dosen/Tenaga Pendidikan)
     */
    public function createUser(array $data): bool
    {
        $sql = "INSERT INTO akun (nomor_induk, username, password, email, status, role)
                VALUES (:nomor_induk, :username, :password, :email, :status, :role)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nomor_induk' => $data['nomor_induk'],
            ':username' => $data['username'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':email' => $data['email'],
            ':status' => $data['status'] ?? 'Aktif',
            ':role' => $data['role'] // 'Mahasiswa', 'Dosen', 'Tenaga Pendidikan'
        ]);
    }

    /**
     * Tambah admin baru (Admin/Super Admin)
     */
    public function createAdmin(array $data): bool
    {
        $sql = "INSERT INTO akun (nomor_induk, username, password, email, status, role)
                VALUES (:nomor_induk, :username, :password, :email, :status, :role)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nomor_induk' => $data['nomor_induk'], // NIP untuk admin
            ':username' => $data['username'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':email' => $data['email'],
            ':status' => $data['status'] ?? 'Aktif',
            ':role' => $data['role'] // 'Admin' atau 'Super Admin'
        ]);
    }

    // ==================== READ ====================
    
    /**
     * Ambil semua user (bukan admin)
     */
    public function getAllUsers(): array
    {
        $sql = "SELECT * FROM akun WHERE role IN ('Mahasiswa', 'Dosen', 'Tenaga Pendidikan') ORDER BY username ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil semua admin
     */
    public function getAllAdmins(): array
    {
        $sql = "SELECT * FROM akun WHERE role IN ('Admin', 'Super Admin') ORDER BY username ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil member berdasarkan nomor induk
     */
    public function getByNomorInduk(string $nomorInduk): array|false
    {
        $sql = "SELECT * FROM akun WHERE nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetch();
    }

    /**
     * Ambil member berdasarkan email
     */
    public function getByEmail(string $email): array|false
    {
        $sql = "SELECT * FROM akun WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Cek apakah nomor induk sudah terdaftar
     */
    public function isNomorIndukExists(string $nomorInduk): bool
    {
        $sql = "SELECT COUNT(*) FROM akun WHERE nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Cek apakah email sudah terdaftar
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

    // ==================== UPDATE ====================
    
    /**
     * Update data member
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
     * Update status member
     */
    public function updateStatus(string $nomorInduk, string $status): bool
    {
        $sql = "UPDATE akun SET status = :status WHERE nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':nomor_induk' => $nomorInduk, ':status' => $status]);
    }

    /**
     * Activate member
     */
    public function activate(string $nomorInduk): bool
    {
        return $this->updateStatus($nomorInduk, 'Aktif');
    }

    /**
     * Deactivate member
     */
    public function deactivate(string $nomorInduk): bool
    {
        return $this->updateStatus($nomorInduk, 'Tidak Aktif');
    }

    // ==================== DELETE ====================
    
    /**
     * Hapus member (soft delete dengan deactivate)
     */
    public function softDelete(string $nomorInduk): bool
    {
        return $this->deactivate($nomorInduk);
    }

    /**
     * Hapus member permanen
     * CATATAN: Akan gagal jika ada relasi ke tabel lain (FK constraint)
     */
    public function delete(string $nomorInduk): bool
    {
        $sql = "DELETE FROM akun WHERE nomor_induk = :nomor_induk";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':nomor_induk' => $nomorInduk]);
    }

    // ==================== FILTER / SEARCH ====================
    
    /**
     * Filter user dengan berbagai kriteria
     */
    public function filterUsers(array $filters = []): array
    {
        $sql = "SELECT * FROM akun WHERE role IN ('Mahasiswa', 'Dosen', 'Tenaga Pendidikan')";
        $params = [];

        // Filter by nama/username
        if (!empty($filters['nama'])) {
            $sql .= " AND username LIKE :nama";
            $params[':nama'] = '%' . $filters['nama'] . '%';
        }

        // Filter by NIM/nomor induk
        if (!empty($filters['nim']) || !empty($filters['nomor_induk'])) {
            $nomorInduk = $filters['nim'] ?? $filters['nomor_induk'];
            $sql .= " AND nomor_induk LIKE :nomor_induk";
            $params[':nomor_induk'] = '%' . $nomorInduk . '%';
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

        // Filter by email
        if (!empty($filters['email'])) {
            $sql .= " AND email LIKE :email";
            $params[':email'] = '%' . $filters['email'] . '%';
        }

        $sql .= " ORDER BY username ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Filter admin dengan berbagai kriteria
     */
    public function filterAdmins(array $filters = []): array
    {
        $sql = "SELECT * FROM akun WHERE role IN ('Admin', 'Super Admin')";
        $params = [];

        // Filter by nama/username
        if (!empty($filters['nama'])) {
            $sql .= " AND username LIKE :nama";
            $params[':nama'] = '%' . $filters['nama'] . '%';
        }

        // Filter by NIP/nomor induk
        if (!empty($filters['nip']) || !empty($filters['nomor_induk'])) {
            $nomorInduk = $filters['nip'] ?? $filters['nomor_induk'];
            $sql .= " AND nomor_induk LIKE :nomor_induk";
            $params[':nomor_induk'] = '%' . $nomorInduk . '%';
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        // Filter by role (Admin atau Super Admin)
        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
            $params[':role'] = $filters['role'];
        }

        $sql .= " ORDER BY username ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Search member by keyword
     */
    public function search(string $keyword, bool $isAdmin = false): array
    {
        if ($isAdmin) {
            $roles = "('Admin', 'Super Admin')";
        } else {
            $roles = "('Mahasiswa', 'Dosen', 'Tenaga Pendidikan')";
        }
        
        $sql = "SELECT * FROM akun WHERE role IN {$roles} 
                AND (username LIKE :keyword OR nomor_induk LIKE :keyword OR email LIKE :keyword)
                ORDER BY username ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':keyword' => '%' . $keyword . '%']);
        return $stmt->fetchAll();
    }

    // ==================== STATISTICS ====================
    
    /**
     * Hitung total user
     */
    public function countUsers(): int
    {
        $sql = "SELECT COUNT(*) FROM akun WHERE role IN ('Mahasiswa', 'Dosen', 'Tenaga Pendidikan')";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Hitung total admin
     */
    public function countAdmins(): int
    {
        $sql = "SELECT COUNT(*) FROM akun WHERE role IN ('Admin', 'Super Admin')";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Hitung user aktif
     */
    public function countActiveUsers(): int
    {
        $sql = "SELECT COUNT(*) FROM akun WHERE role IN ('Mahasiswa', 'Dosen', 'Tenaga Pendidikan') AND status = 'Aktif'";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Hitung berdasarkan role
     */
    public function countByRole(string $role): int
    {
        $sql = "SELECT COUNT(*) FROM akun WHERE role = :role";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':role' => $role]);
        return (int) $stmt->fetchColumn();
    }

    // ==================== PELANGGARAN / SUSPENSI ====================
    
    /**
     * Ambil riwayat pelanggaran user
     */
    public function getPelanggaran(string $nomorInduk): array
    {
        $sql = "SELECT * FROM pelanggaran_suspensi WHERE nomor_induk = :nomor_induk ORDER BY tanggal_mulai DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetchAll();
    }

    /**
     * Cek apakah user sedang di-suspend
     */
    public function isSuspended(string $nomorInduk): bool
    {
        $sql = "SELECT COUNT(*) FROM pelanggaran_suspensi 
                WHERE nomor_induk = :nomor_induk 
                AND CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Ambil suspensi aktif
     */
    public function getActiveSuspension(string $nomorInduk): array|false
    {
        $sql = "SELECT * FROM pelanggaran_suspensi 
                WHERE nomor_induk = :nomor_induk 
                AND CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai
                ORDER BY tanggal_selesai DESC
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nomor_induk' => $nomorInduk]);
        return $stmt->fetch();
    }

    /**
     * Tambah pelanggaran/suspensi
     */
    public function addPelanggaran(array $data): int|false
    {
        $sql = "INSERT INTO pelanggaran_suspensi (nomor_induk, jenis_pelanggaran, alasan_suspensi, tanggal_mulai, tanggal_selesai)
                VALUES (:nomor_induk, :jenis_pelanggaran, :alasan_suspensi, :tanggal_mulai, :tanggal_selesai)";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':nomor_induk' => $data['nomor_induk'],
            ':jenis_pelanggaran' => $data['jenis_pelanggaran'],
            ':alasan_suspensi' => $data['alasan_suspensi'] ?? null,
            ':tanggal_mulai' => $data['tanggal_mulai'],
            ':tanggal_selesai' => $data['tanggal_selesai']
        ]);
        
        return $result ? (int) $this->pdo->lastInsertId() : false;
    }
}
