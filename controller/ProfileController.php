<?php

class ProfileController
{
    private AkunModel $akunModel;
    private BookingModel $bookingModel;
    private MemberModel $memberModel;
    private BookingListModel $bookingListModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->akunModel = new AkunModel();
        $this->bookingModel = new BookingModel();
        $this->memberModel = new MemberModel();
        $this->bookingListModel = new BookingListModel();
    }

    public function index(): void
    {
        $this->renderIndexView();
    }

    private function renderIndexView(): void
    {
        // Auto-update status HANGUS untuk booking yang lewat 10 menit dari waktu mulai tanpa check-in
        $this->bookingListModel->autoUpdateHangusStatus();
        
        // Get user's bookings and pelanggaran if logged in and not admin
        $active_booking = false;
        $history_bookings = [];
        $pelanggaran_list = [];
        
        // Pagination setup
        $perPageHistory = 6;
        $perPagePelanggaran = 6;
        $currentPageHistory = isset($_GET['pg_history']) ? max(1, (int)$_GET['pg_history']) : 1;
        $currentPagePelanggaran = isset($_GET['pg_pelanggaran']) ? max(1, (int)$_GET['pg_pelanggaran']) : 1;
        
        if (isset($_SESSION['user']) && !in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])) {
            $nomor_induk = $_SESSION['user']['nomor_induk'];
            $active_booking = $this->bookingModel->getActiveByMember($nomor_induk);
            
            // Get all bookings (history) - exclude AKTIF karena sudah ditampilkan di tab Kode Booking
            $all_bookings = $this->bookingModel->getByNomorInduk($nomor_induk);
            $all_history = array_filter($all_bookings, function($booking) {
                return $booking['nama_status'] !== 'AKTIF'; // Only SELESAI, DIBATALKAN, HANGUS
            });
            
            // Apply pagination to history
            $totalHistory = count($all_history);
            $totalPagesHistory = ceil($totalHistory / $perPageHistory);
            $offsetHistory = ($currentPageHistory - 1) * $perPageHistory;
            $history_bookings = array_slice($all_history, $offsetHistory, $perPageHistory);
            
            // Get pelanggaran with pagination
            $all_pelanggaran = $this->memberModel->getPelanggaran($nomor_induk);
            $totalPelanggaran = count($all_pelanggaran);
            $totalPagesPelanggaran = ceil($totalPelanggaran / $perPagePelanggaran);
            $offsetPelanggaran = ($currentPagePelanggaran - 1) * $perPagePelanggaran;
            $pelanggaran_list = array_slice($all_pelanggaran, $offsetPelanggaran, $perPagePelanggaran);
            
            // Pagination data for view
            $paginationHistory = [
                'currentPage' => $currentPageHistory,
                'totalPages' => $totalPagesHistory,
                'totalRecords' => $totalHistory,
                'perPage' => $perPageHistory
            ];
            
            $paginationPelanggaran = [
                'currentPage' => $currentPagePelanggaran,
                'totalPages' => $totalPagesPelanggaran,
                'totalRecords' => $totalPelanggaran,
                'perPage' => $perPagePelanggaran
            ];
        }
        
        require __DIR__ . '/../view/profile/index.php';
    }

    public function upload_foto(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in
        if (!isset($_SESSION['user'])) {
            echo "<script>alert('Anda harus login terlebih dahulu'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Check if request is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "<script>alert('Invalid request method'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        $nomor_induk = $_SESSION['user']['nomor_induk'];
        $errors = [];

        // Handle file upload
        if (!isset($_FILES['foto_profil']) || $_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File foto profil harus diupload.';
        } else {
            $file = $_FILES['foto_profil'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $maxSize = 25 * 1024 * 1024; // 25MB

            // Validasi tipe file
            if (!in_array($file['type'], $allowedTypes)) {
                $errors[] = 'File harus berupa gambar (JPEG, PNG, atau WebP).';
            }

            // Validasi ukuran file
            if ($file['size'] > $maxSize) {
                $errors[] = 'Ukuran file maksimal 25MB.';
            }

            // Jika tidak ada error, upload file
            if (empty($errors)) {
                $uploadDir = __DIR__ . '/../assets/uploads/images/';
                
                // Buat direktori jika belum ada
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Hapus foto lama jika ada
                $oldFoto = $_SESSION['user']['foto_profil'] ?? null;
                if ($oldFoto && file_exists(__DIR__ . '/../' . $oldFoto)) {
                    unlink(__DIR__ . '/../' . $oldFoto);
                }

                // Generate nama file unik
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = 'profile_' . $nomor_induk . '_' . time() . '.' . $extension;
                $uploadPath = $uploadDir . $fileName;

                // Upload file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $fotoProfil = 'assets/uploads/images/' . $fileName;
                    
                    // Update database
                    if ($this->akunModel->updateFotoProfil($nomor_induk, $fotoProfil)) {
                        // Update session
                        $_SESSION['user']['foto_profil'] = $fotoProfil;
                        echo "<script>alert('Foto profil berhasil diupdate!'); window.location.href='index.php?page=profile';</script>";
                        exit;
                    } else {
                        $errors[] = 'Gagal menyimpan foto profil ke database.';
                    }
                } else {
                    $errors[] = 'Gagal mengupload file.';
                }
            }
        }

        // Set error message if any
        if (!empty($errors)) {
            $errorMsg = implode(' ', $errors);
            echo "<script>alert('" . addslashes($errorMsg) . "'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        header('Location: index.php?page=profile');
        exit;
    }

    /**
     * Change password with old password verification
     */
    public function change_password(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in
        if (!isset($_SESSION['user'])) {
            echo "<script>alert('Anda harus login terlebih dahulu'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Check if request is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "<script>alert('Invalid request method'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        $nomor_induk = $_SESSION['user']['nomor_induk'];
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validasi input
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo "<script>alert('Semua field harus diisi'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Validasi panjang password baru
        if (strlen($newPassword) < 8) {
            echo "<script>alert('Password baru minimal 8 karakter'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Validasi konfirmasi password
        if ($newPassword !== $confirmPassword) {
            echo "<script>alert('Password baru dan konfirmasi password tidak cocok'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Verifikasi password lama
        if (!$this->akunModel->verifyPassword($nomor_induk, $oldPassword)) {
            echo "<script>alert('Password lama salah'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Cek apakah password baru sama dengan password lama
        if ($oldPassword === $newPassword) {
            echo "<script>alert('Password baru tidak boleh sama dengan password lama'); window.location.href='index.php?page=profile';</script>";
            exit;
        }

        // Update password
        if ($this->akunModel->updatePassword($nomor_induk, $newPassword)) {
            echo "<script>alert('Password berhasil diubah!'); window.location.href='index.php?page=profile';</script>";
        } else {
            echo "<script>alert('Gagal mengubah password. Silakan coba lagi.'); window.location.href='index.php?page=profile';</script>";
        }
        exit;
    }
}