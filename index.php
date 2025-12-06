<?php 
// CRITICAL: No output before this point!
// Start global session for auth state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Daftar mapping autoloader untuk class ke file
$mapmodeldancontroller = [
    'Koneksi' => __DIR__ . '/config/Koneksi.php',
    'LoginController' => __DIR__ . '/controller/LoginController.php',
    // Legacy mappings below are unused; model consolidated in AkunModel
    //'LoginModel' => __DIR__ . '/model/LoginModel.php',
    'RegisterController' => __DIR__.'/controller/RegisterController.php',
    //'RegisterModel' => __DIR__.'/model/RegisterModel.php',
    'GuestController' => __DIR__.'/controller/GuestController.php',
    //'GuestModel' => __DIR__.'/model/GuestModel.php',
    'BookingController' => __DIR__ . '/controller/BookingController.php',
    'BookingModel' => __DIR__ . '/model/BookingModel.php',
    'AkunModel' => __DIR__ . '/model/AkunModel.php',
    'DashboardController' => __DIR__ . '/controller/DashboardController.php',
    'ProfileController' => __DIR__ . '/controller/ProfileController.php',
    'AdminController' => __DIR__ . '/controller/AdminController.php',
    // New models
    'RuanganModel' => __DIR__ . '/model/RuanganModel.php',
    'ScheduleModel' => __DIR__ . '/model/ScheduleModel.php',
    'BookingExternalModel' => __DIR__ . '/model/BookingExternalModel.php',
    'BookingListModel' => __DIR__ . '/model/BookingListModel.php',
    'LaporanModel' => __DIR__ . '/model/LaporanModel.php',
    'MemberModel' => __DIR__ . '/model/MemberModel.php',
    'FeedbackModel' => __DIR__ . '/model/FeedbackModel.php',
    'PengaturanModel' => __DIR__ . '/model/PengaturanModel.php',
];

// Autoload class sesuai mapping di atas
spl_autoload_register(static function (string $class) use ($mapmodeldancontroller): void {
    // Jika class ada di mapping, require file-nya
    if (isset($mapmodeldancontroller[$class])) {
        require_once $mapmodeldancontroller[$class];
    }
});

// Ambil parameter halaman dari URL, default 'home'
$halaman = $_GET['page'] ?? 'home';
// Ambil parameter aksi dari URL, default 'index'
$aksi = $_GET['action'] ?? 'index';

// Inisialisasi variabel controller
$controller = null;

// Pilih controller sesuai halaman yang diminta
switch ($halaman) {
    case 'login':
        $controller = new LoginController();
        break;
    case 'register':
        $controller = new RegisterController();
        break;
    case 'booking':
        $controller = new BookingController();
        break;
    case 'dashboard':
        $controller = new DashboardController();
        break;
    case 'profile':
        $controller = new ProfileController();
        break;
    case 'admin':
        $controller = new AdminController();
        break;
    default:
        break;
}

// Jika controller tidak ditemukan (halaman home)
if ($controller === null) {
    // Inisialisasi variabel atau model disini jika perlu
    // Contoh: $modelPeminjaman = new PeminjamanModel();
    
    // Logic untuk halaman home disini

    // Tampilkan halaman home
    require __DIR__ . '/view/startpage.php';
    return;
}

// Jika method aksi tidak ada di controller, tampilkan 404
if (!method_exists($controller, $aksi)) {
    http_response_code(404);
    require __DIR__ . '/view/error.php';
    return;
}

// Jalankan method aksi pada controller yang dipilih
$controller->{$aksi}();
?>