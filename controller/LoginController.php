<?php
/**
 * ============================================================================
 * LOGINCONTROLLER.PHP - Authentication & Password Reset Controller
 * ============================================================================
 * 
 * Controller untuk menangani autentikasi user, logout, dan reset password.
 * Menggunakan email-based login dengan captcha validation dan OTP untuk reset password.
 * 
 * FUNGSI UTAMA:
 * 1. LOGIN - Email + password authentication dengan captcha
 * 2. LOGOUT - Session destruction
 * 3. RESET PASSWORD - OTP-based password reset via email
 *    a. verify_email() - Validate email dan kirim OTP
 *    b. verify_otp() - Validate OTP code
 *    c. reset_password() - Update password setelah OTP verified
 * 
 * ROUTES:
 * - ?page=login&action=index - Display login form
 * - ?page=login&action=auth - Process login (POST)
 * - ?page=login&action=logout - Logout user
 * - ?page=login&action=verify_email - Send OTP (AJAX POST)
 * - ?page=login&action=verify_otp - Verify OTP (AJAX POST)
 * - ?page=login&action=reset_password - Reset password (POST)
 * 
 * LOGIN FLOW:
 * 1. User enters email (NOT username) + password + captcha
 * 2. Validate captcha (case-insensitive, stored in $_SESSION['code'])
 * 3. Validate email format dan PNJ domain
 * 4. Authenticate via AkunModel::loginByEmail()
 * 5. Check account status (must be 'Aktif')
 * 6. Set $_SESSION['user'] dengan full user data
 * 7. Redirect based on role:
 *    - Admin/Super Admin → admin dashboard
 *    - User → user dashboard
 * 
 * RESET PASSWORD FLOW:
 * 1. User enters email → verify_email()
 * 2. Validate email exists dan status Aktif
 * 3. Generate 6-digit OTP (random, valid 5 minutes)
 * 4. Store in $_SESSION: reset_otp, reset_email, reset_otp_expired
 * 5. Send OTP via email (SMTP)
 * 6. User enters OTP → verify_otp()
 * 7. Validate OTP match dan not expired
 * 8. Set $_SESSION['otp_verified'] = true
 * 9. User enters new password → reset_password()
 * 10. Update password via AkunModel::updatePassword()
 * 11. Clear reset session variables
 * 
 * SECURITY FEATURES:
 * - CAPTCHA: Case-insensitive validation (strcasecmp)
 * - EMAIL DOMAIN: Strict PNJ validation (@stu.pnj.ac.id or @*.pnj.ac.id)
 * - OTP: 6-digit random, 5-minute expiration
 * - PASSWORD: Minimum 8 characters (enforced)
 * - SESSION: Proper session management dengan status check
 * 
 * EMAIL VALIDATION PATTERNS:
 * - Mahasiswa: nama.x@stu.pnj.ac.id
 * - Dosen/Admin: nama@jurusan.pnj.ac.id or nama@pnj.ac.id
 * - Regex: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]*\.?pnj\.ac\.id$/
 * 
 * OTP EMAIL FORMAT:
 * - Subject: "Kode OTP Reset Password - BookEZ"
 * - Body: Contains 6-digit OTP + 5-minute expiration notice
 * - From: MAIL_FROM_ADDRESS (bookez.web@gmail.com)
 * - Headers: UTF-8 encoding
 * 
 * AJAX RESPONSES:
 * All AJAX methods return JSON:
 * - {success: bool, message: string}
 * - Content-Type: application/json
 * - Output buffer cleared before JSON output
 * 
 * USER NOTIFICATIONS:
 * - JavaScript alert() untuk synchronous feedback
 * - window.location.href untuk navigation after alert
 * - addslashes() untuk escape dynamic messages
 * 
 * USAGE PATTERNS:
 * - view/login.php: Login form
 * - assets/js/reset-password.js: AJAX OTP workflow
 * - config/Email.php: SMTP configuration
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

/**
 * Class LoginController - Authentication & Password Reset
 * 
 * @property AkunModel $model Model untuk account operations
 */
class LoginController
{
    /**
     * AkunModel instance untuk database operations
     * @var AkunModel
     */
    private AkunModel $model;

    /**
     * Constructor - Initialize AkunModel
     */
    public function __construct()
    {
        $this->model = new AkunModel();
    }

    /**
     * Index Action - Display login form
     * 
     * @return void
     */
    public function index()
    {
        $this->renderLoginView();
    }

    private function renderLoginView()
    {
        require __DIR__ . '/../view/login.php';
    }

    public function auth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validasi input
        $email = trim($_POST['Email'] ?? '');
        $password = $_POST['Password'] ?? '';
        $captcha = trim($_POST['captcha'] ?? '');

        // Validasi captcha
        if (empty($captcha) || !isset($_SESSION['code']) || strcasecmp($captcha, $_SESSION['code']) !== 0) {
            echo "<script>alert('Captcha tidak valid'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Validasi input kosong
        if (empty($email) || empty($password)) {
            echo "<script>alert('Email dan password harus diisi'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Validasi format email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('Format email tidak valid'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Validasi domain email PNJ
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]*\.?pnj\.ac\.id$/', $email)) {
            echo "<script>alert('Email harus menggunakan domain PNJ'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Autentikasi dengan database
        $akun = $this->model->loginByEmail($email, $password);

        if ($akun === false) {
            echo "<script>alert('Email atau password salah'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Cek status akun
        if ($akun['status'] === 'Tidak Aktif') {
            echo "<script>alert('Akun Anda belum diaktifkan oleh admin. Silakan hubungi admin untuk aktivasi.'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Set session user
        $_SESSION['user'] = [
            'nomor_induk' => $akun['nomor_induk'],
            'username' => $akun['username'],
            'email' => $akun['email'],
            'role' => $akun['role'],
            'status' => $akun['status'],
            'jurusan' => $akun['jurusan'] ?? null,
            'prodi' => $akun['prodi'] ?? null,
            'foto_profil' => $akun['foto_profil'] ?? null,
            'validasi_mahasiswa' => $akun['validasi_mahasiswa'] ?? null
        ];

        // Clear captcha code after successful login
        unset($_SESSION['code']);

        // Redirect berdasarkan role
        if (in_array($akun['role'], ['Admin', 'Super Admin'])) {
            header('Location: index.php?page=admin&action=index');
        } else {
            header('Location: index.php?page=dashboard');
        }
        exit;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_destroy();
        header('Location: index.php?page=login');
        exit;
    }

    // ==================== RESET PASSWORD FLOW ====================
    
    /**
     * Verify email exists and is active, then send OTP
     */
    public function verify_email()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear any output buffer to prevent HTML output before JSON
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $email = trim($_POST['email'] ?? '');

        // Validasi input
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email tidak valid']);
            exit;
        }

        // Validasi domain email PNJ
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]*\\.?pnj\\.ac\\.id$/', $email)) {
            echo json_encode(['success' => false, 'message' => 'Email harus menggunakan domain PNJ']);
            exit;
        }

        // Cek email di database
        $akun = $this->model->getByEmail($email);

        if (!$akun) {
            echo json_encode(['success' => false, 'message' => 'Email tidak terdaftar']);
            exit;
        }

        // Cek status akun harus aktif
        if ($akun['status'] !== 'Aktif') {
            echo json_encode(['success' => false, 'message' => 'Akun tidak aktif. Silakan hubungi admin.']);
            exit;
        }

        // Generate OTP 6 digit
        $otp = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Simpan OTP dan waktu expired di session (5 menit)
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp_expired'] = time() + (5 * 60); // 5 menit dari sekarang
        
        // Debug log (hapus setelah testing)
        error_log('OTP Generated: ' . $otp . ' for email: ' . $email);

        // Kirim OTP via email
        require_once __DIR__ . '/../config/Email.php';
        
        $to = $email;
        $subject = 'Kode OTP Reset Password - BookEZ';
        $message = "Halo {$akun['username']},\n\n";
        $message .= "Anda telah meminta reset password untuk akun BookEZ Anda.\n\n";
        $message .= "Kode OTP Anda adalah: {$otp}\n\n";
        $message .= "Kode ini berlaku selama 5 menit.\n";
        $message .= "Jika Anda tidak meminta reset password, abaikan email ini.";
        $message .= MAIL_SIGNATURE;

        $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">\r\n";
        $headers .= "Reply-To: " . MAIL_REPLY_TO . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        // Send response immediately to reduce perceived delay
        echo json_encode(['success' => true, 'message' => 'OTP telah dikirim ke email Anda']);
        
        // Flush output to browser
        if (ob_get_length()) ob_end_flush();
        flush();
        
        // Send email in background (this may take a few seconds)
        $mailResult = mail($to, $subject, $message, $headers);
        
        // Log email result
        error_log('Email send result: ' . ($mailResult ? 'success' : 'failed') . ' to: ' . $to);
        exit;
    }

    /**
     * Verify OTP code
     */
    public function verify_otp()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear any output buffer to prevent HTML output before JSON
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $otp = trim($_POST['otp'] ?? '');
        
        // Remove any non-digit characters
        $otp = preg_replace('/[^0-9]/', '', $otp);

        // Validasi input
        if (empty($otp) || strlen($otp) !== 6) {
            echo json_encode(['success' => false, 'message' => 'Kode OTP harus 6 digit angka']);
            exit;
        }

        // Cek apakah OTP tersimpan di session
        if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_otp_expired'])) {
            echo json_encode(['success' => false, 'message' => 'Sesi OTP tidak ditemukan. Silakan kirim ulang OTP.']);
            exit;
        }

        // Cek apakah OTP sudah expired
        if (time() > $_SESSION['reset_otp_expired']) {
            unset($_SESSION['reset_otp'], $_SESSION['reset_otp_expired'], $_SESSION['reset_email']);
            echo json_encode(['success' => false, 'message' => 'Kode OTP sudah kadaluarsa. Silakan kirim ulang OTP.']);
            exit;
        }

        // Debug log (hapus setelah testing)
        error_log('OTP Verify - Input: ' . $otp . ' | Session: ' . $_SESSION['reset_otp'] . ' | Match: ' . ($otp === $_SESSION['reset_otp'] ? 'YES' : 'NO'));

        // Verifikasi OTP (strict comparison)
        if ($otp !== $_SESSION['reset_otp']) {
            echo json_encode(['success' => false, 'message' => 'Kode OTP salah. Pastikan Anda memasukkan 6 digit yang benar.']);
            exit;
        }

        // OTP valid, set flag untuk allow reset password
        $_SESSION['otp_verified'] = true;

        echo json_encode(['success' => true, 'message' => 'Kode OTP valid']);
        exit;
    }

    /**
     * Reset password after OTP verification
     */
    public function reset_password()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "<script>alert('Invalid request method'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        // Cek apakah OTP sudah diverifikasi
        if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
            echo "<script>alert('Verifikasi OTP terlebih dahulu'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $email = $_SESSION['reset_email'] ?? '';

        // Validasi input
        if (empty($newPassword) || empty($confirmPassword)) {
            echo "<script>alert('Password tidak boleh kosong'); window.history.back();</script>";
            exit;
        }

        if (strlen($newPassword) < 8) {
            echo "<script>alert('Password minimal 8 karakter'); window.history.back();</script>";
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            echo "<script>alert('Password dan konfirmasi password tidak cocok'); window.history.back();</script>";
            exit;
        }

        // Update password di database
        $akun = $this->model->getByEmail($email);
        
        if (!$akun) {
            echo "<script>alert('Akun tidak ditemukan'); window.location.href='index.php?page=login';</script>";
            exit;
        }

        if ($this->model->updatePassword($akun['nomor_induk'], $newPassword)) {
            // Clear session reset password
            unset($_SESSION['reset_otp'], $_SESSION['reset_otp_expired'], $_SESSION['reset_email'], $_SESSION['otp_verified']);
            
            echo "<script>alert('Password berhasil direset! Silakan login dengan password baru.'); window.location.href='index.php?page=login';</script>";
        } else {
            echo "<script>alert('Gagal mereset password. Silakan coba lagi.'); window.history.back();</script>";
        }
        exit;
    }
}
