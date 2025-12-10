<?php 
/**
 * ============================================================================
 * INDEX.PHP - Application Entry Point & Router
 * ============================================================================
 * 
 * File ini adalah entry point utama untuk seluruh aplikasi BookEZ.
 * Menangani routing, autoloading, session management, dan orchestration controller.
 * 
 * FUNGSI UTAMA:
 * 1. Session Management - Inisialisasi session global untuk autentikasi
 * 2. Class Autoloading - SPL autoloader untuk load controller dan model
 * 3. URL Routing - Parse parameter 'page' dan 'action' untuk routing
 * 4. Controller Dispatch - Instantiate dan execute controller method
 * 5. Error Handling - 404 handling untuk route yang tidak ditemukan
 * 
 * ROUTING PATTERN:
 * - URL Format: /index.php?page={controller}&action={method}
 * - Default: page=home (render startpage.php)
 * - Example: ?page=booking&action=buat_booking → BookingController::buat_booking()
 * 
 * CRITICAL NOTES:
 * - NO OUTPUT sebelum session_start() untuk mencegah "headers already sent" error
 * - Setiap case di switch MUST memiliki 'break;' untuk mencegah fallthrough
 * - GuestController deprecated (ada di autoloader tapi tidak digunakan)
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

// CRITICAL: No output before this point!
// Start global session for auth state
// Cek status session dan start hanya jika belum aktif untuk menghindari error
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ============================================================================
 * AUTOLOADER CONFIGURATION
 * ============================================================================
 * 
 * Array mapping untuk class name → file path.
 * Digunakan oleh spl_autoload_register untuk automatic class loading.
 * 
 * STRUCTURE:
 * - Config: Database connection dan email settings
 * - Controllers: Handle HTTP requests dan business logic
 * - Models: Data access layer untuk database operations
 * 
 * LEGACY NOTES:
 * - LoginModel, RegisterModel, GuestModel: deprecated (consolidated ke AkunModel)
 * - GuestController: tidak digunakan lagi
 * 
 * MAINTENANCE:
 * - Tambahkan entry baru saat membuat controller/model baru
 * - Format: 'ClassName' => __DIR__ . '/path/to/File.php'
 */
$mapmodeldancontroller = [
    // Config Classes - Database dan Email configuration
    'Koneksi' => __DIR__ . '/config/Koneksi.php',
    
    // Controllers - HTTP request handlers
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

/**
 * ============================================================================
 * SPL AUTOLOADER REGISTRATION
 * ============================================================================
 * 
 * Register fungsi autoloader yang akan dipanggil otomatis saat class digunakan.
 * Menggunakan closure (anonymous function) dengan access ke $mapmodeldancontroller.
 * 
 * FLOW:
 * 1. PHP mencoba instantiate class (e.g., new BookingController())
 * 2. Autoloader check apakah class ada di mapping array
 * 3. Jika ada, require_once file yang sesuai
 * 4. Class menjadi available untuk digunakan
 * 
 * @param string $class Nama class yang akan di-autoload
 */
spl_autoload_register(static function (string $class) use ($mapmodeldancontroller): void {
    // Jika class ada di mapping, require file-nya
    if (isset($mapmodeldancontroller[$class])) {
        require_once $mapmodeldancontroller[$class];
    }
});

/**
 * ============================================================================
 * URL ROUTING & PARAMETER PARSING
 * ============================================================================
 * 
 * Parse URL query parameters untuk menentukan controller dan action.
 * 
 * PARAMETERS:
 * - page: Nama controller yang akan di-load (default: 'home')
 * - action: Method yang akan dipanggil (default: 'index')
 * 
 * EXAMPLES:
 * - ?page=booking&action=buat_booking → BookingController::buat_booking()
 * - ?page=admin&action=kelola_ruangan → AdminController::kelola_ruangan()
 * - ?page=login (no action) → LoginController::index()
 * - / (no params) → render startpage.php
 */

// Ambil parameter halaman dari URL, default 'home'
$halaman = $_GET['page'] ?? 'home';
// Ambil parameter aksi dari URL, default 'index'
$aksi = $_GET['action'] ?? 'index';

// Inisialisasi variabel controller (akan di-set oleh switch statement)
$controller = null;

/**
 * ============================================================================
 * CONTROLLER SELECTION & INSTANTIATION
 * ============================================================================
 * 
 * Switch statement untuk mapping page parameter ke controller instance.
 * 
 * AVAILABLE ROUTES:
 * - login: Authentication (login, logout, reset password)
 * - register: User registration dan account creation
 * - booking: User booking management (create, view, cancel, reschedule)
 * - dashboard: User dashboard (role-based redirect)
 * - profile: User profile dan settings
 * - admin: Admin area (kelola ruangan, laporan, member list, etc.)
 * - home: Landing page (default, no controller)
 * 
 * CRITICAL NOTES:
 * - SETIAP case MUST memiliki 'break;' statement!
 * - Missing break menyebabkan fallthrough ke default (home page)
 * - AdminController memiliki role check di constructor
 * - DashboardController redirect admin ke admin dashboard
 * 
 * @var LoginController|RegisterController|BookingController|DashboardController|ProfileController|AdminController|null $controller
 */
switch ($halaman) {
    case 'login':
        // Handle authentication: login form, auth, logout, reset password
        $controller = new LoginController();
        break;
    case 'register':
        // Handle user registration dengan validasi email PNJ
        $controller = new RegisterController();
        break;
    case 'booking':
        // Handle user booking CRUD operations
        $controller = new BookingController();
        break;
    case 'dashboard':
        // User dashboard dengan role-based redirect
        $controller = new DashboardController();
        break;
    case 'profile':
        // User profile management dan settings
        $controller = new ProfileController();
        break;
    case 'admin':
        // Admin area - protected by role check di constructor
        $controller = new AdminController();
        break;
    default:
        // Fallback: render home page
        break;
}

/**
 * ============================================================================
 * HOME PAGE HANDLER
 * ============================================================================
 * 
 * Jika controller null (default case dari switch), render landing page.
 * Landing page tidak memerlukan controller - direct view rendering.
 * 
 * FLOW:
 * - User access root URL (/) atau ?page=home
 * - Switch statement tidak match case manapun
 * - $controller tetap null
 * - Render view/startpage.php langsung
 * - return untuk stop execution
 */
if ($controller === null) {
    // Inisialisasi variabel atau model disini jika perlu
    // Contoh: $modelPeminjaman = new PeminjamanModel();
    
    // Logic untuk halaman home disini

    // Tampilkan halaman home (landing page)
    require __DIR__ . '/view/startpage.php';
    return;
}

/**
 * ============================================================================
 * ACTION METHOD VALIDATION & ERROR HANDLING
 * ============================================================================
 * 
 * Verify bahwa action method exists di controller sebelum execution.
 * Jika method tidak ada, return 404 error page.
 * 
 * EXAMPLE:
 * - Valid: BookingController::buat_booking() exists → execute
 * - Invalid: BookingController::nonexistent() → 404 error
 * 
 * ERROR HANDLING:
 * - Set HTTP response code 404
 * - Render view/error.php
 * - return untuk stop execution
 */
if (!method_exists($controller, $aksi)) {
    http_response_code(404);
    require __DIR__ . '/view/error.php';
    return;
}

/**
 * ============================================================================
 * CONTROLLER ACTION EXECUTION
 * ============================================================================
 * 
 * Execute action method pada controller instance.
 * Menggunakan variable method invocation syntax: $controller->{$aksi}()
 * 
 * FLOW:
 * 1. Controller sudah di-instantiate dari switch statement
 * 2. Action method sudah divalidasi exists
 * 3. Call method secara dynamic
 * 4. Method handle request dan render response (view atau redirect)
 * 
 * EXAMPLES:
 * - BookingController::index() → render booking list view
 * - LoginController::auth() → process login dan redirect
 * - AdminController::kelola_ruangan() → render admin room management view
 */
$controller->{$aksi}();
?>