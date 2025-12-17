<?php

class RegisterController
{
    private AkunModel $model;

    public function __construct()
    {
        $this->model = new AkunModel();
    }

    public function index()
    {
        $this->renderRegistView();
    }

    private function renderRegistView()
    {
        require __DIR__ . '/../view/register.php';
    }

    public function submit(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Ambil data dari POST
        $username = isset($_POST['Username']) ? trim((string)$_POST['Username']) : '';
        $nomorInduk = isset($_POST['NomorInduk']) ? trim((string)$_POST['NomorInduk']) : '';
        $email = isset($_POST['Email']) ? trim((string)$_POST['Email']) : '';
        $password = isset($_POST['Password']) ? (string)$_POST['Password'] : '';
        $passwordUlang = isset($_POST['PasswordUlang']) ? (string)$_POST['PasswordUlang'] : '';
        $jurusan = isset($_POST['Jurusan']) ? trim((string)$_POST['Jurusan']) : null;
        $prodi = isset($_POST['Prodi']) ? trim((string)$_POST['Prodi']) : null;
        $captcha = isset($_POST['captcha']) ? trim((string)$_POST['captcha']) : '';
        $captchaSession = isset($_SESSION['code']) ? (string)$_SESSION['code'] : '';

        // Array untuk menampung error
        $errors = [];

        // Validasi captcha
        if ($captcha === '' || strcasecmp($captcha, $captchaSession) !== 0) {
            $errors[] = 'Captcha tidak sesuai atau kosong.';
        }

        // Validasi input wajib
        if ($username === '' || $nomorInduk === '' || $email === '' || $password === '' || $passwordUlang === '') {
            $errors[] = 'Username, Nomor Induk, Email, dan Password wajib diisi.';
        }

        // Validasi email format
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }

        // Validasi domain email PNJ
        if (!empty($email)) {
            $isValidDomain = false;
            
            // Untuk mahasiswa: harus @stu.pnj.ac.id (terima format umum local-part@stu.pnj.ac.id)
            if (preg_match('/^[a-zA-Z0-9._%+-]+@stu\.pnj\.ac\.id$/', $email, $matches)) {
                $isValidDomain = true;
            }
            // Untuk dosen/tenaga pendidik: harus @*.pnj.ac.id atau @pnj.ac.id (bukan @stu.pnj.ac.id)
            elseif ((preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.pnj\.ac\.id$/', $email) || 
                     preg_match('/^[a-zA-Z0-9._%+-]+@pnj\.ac\.id$/', $email)) && 
                    !preg_match('/@stu\.pnj\.ac\.id$/', $email)) {
                $isValidDomain = true;
            }
            
            if (!$isValidDomain) {
                $errors[] = 'Email harus menggunakan domain PNJ. Mahasiswa: @stu.pnj.ac.id, Dosen/Admin: @*.pnj.ac.id atau @pnj.ac.id';
            }
        }

        // Validasi password minimal 8 karakter
        if (strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }

        // Validasi password match
        if ($password !== $passwordUlang) {
            $errors[] = 'Konfirmasi password tidak cocok.';
        }

        // Validasi nomor induk sudah ada
        if ($this->model->isNomorIndukExists($nomorInduk)) {
            $errors[] = 'Nomor Induk sudah terdaftar.';
        }

        // Validasi email sudah ada
        if ($this->model->isEmailExists($email)) {
            $errors[] = 'Email sudah terdaftar.';
        }

        // Validasi username sudah ada
        if ($this->model->isUsernameExists($username)) {
            $errors[] = 'Username sudah digunakan.';
        }

        // Handle file upload screenshot KubacaPNJ untuk validasi mahasiswa
        $validasiMahasiswa = null;
        if (isset($_FILES['SSKUBACAPNJ']) && $_FILES['SSKUBACAPNJ']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['SSKUBACAPNJ'];
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

                // Generate nama file unik
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = 'validasi_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                $uploadPath = $uploadDir . $fileName;

                // Upload file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $validasiMahasiswa = 'assets/uploads/images/' . $fileName;
                } else {
                    $errors[] = 'Gagal mengupload file.';
                }
            }
        }

        // Jika ada error, tampilkan dan kembali ke form
        if (!empty($errors)) {
            $errorMsg = implode(' ', $errors);
            echo "<script>alert('" . addslashes($errorMsg) . "'); window.location.href='index.php?page=register';</script>";
            exit;
        }

        // Konversi jurusan dan prodi kosong menjadi null
        if ($jurusan === '') $jurusan = null;
        if ($prodi === '') $prodi = null;

        // Simpan ke database dengan status "Tidak Aktif"
        try {
            $success = $this->model->register(
                $nomorInduk,
                $username,
                $password,
                $email,
                'User',
                $jurusan,
                $prodi,
                $validasiMahasiswa
            );

            if ($success) {
                // Clear captcha code
                unset($_SESSION['code']);

                echo "<script>alert('Registrasi berhasil! Akun Anda menunggu aktivasi oleh admin. Silakan login setelah akun diaktifkan.'); window.location.href='index.php?page=login';</script>";
                exit;
            } else {
                echo "<script>alert('Gagal menyimpan data. Silakan coba lagi.'); window.location.href='index.php?page=register';</script>";
                exit;
            }
        } catch (Throwable $e) {
            echo "<script>alert('Terjadi kesalahan: " . addslashes($e->getMessage()) . "'); window.location.href='index.php?page=register';</script>";
            exit;
        }
    }
}
