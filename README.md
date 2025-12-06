# PBL Perpustakaan — Local Development Guide

Complete guide to run and contribute to this PHP + Tailwind CSS library management system.

## Prerequisites

- XAMPP (Apache + PHP + MySQL) or equivalent LAMP/WAMP stack
- Node.js + npm (for Tailwind CSS build)
- Modern web browser

## Quick Start

1. **Clone or place** the project folder under XAMPP's `htdocs` directory:
   ```
   C:\xampp\htdocs\
   ```
   
2. **Start services** from XAMPP control panel:
   - Apache (web server)
   - MySQL (database)

3. **Configure database**:
   - The project includes `config/Koneksi.php` with default local credentials:
     - Host: `127.0.0.1`
     - Database: `PBL-Perpustakaan`
     - User: `root`
     - Password: `admin`
   - If using different credentials, update `config/Koneksi.php` (do not commit sensitive data)
   - Import SQL schema from `sql/` directory if needed

4. **Install Node dependencies and build Tailwind CSS**:
   ```pwsh
   npm install
   npm run build:css
   ```

5. **Open the app** in your browser:
   - [http://localhost/](http://localhost/)
   - Or if in subfolder: [http://localhost/PBL%20Perpustakaan/](http://localhost/PBL%20Perpustakaan/)

## Tailwind CSS Development

### Build Commands

Run from project root:

```pwsh
npm run build:css    # Build once (minified for production)
npm run watch:css    # Watch mode during development
```

### File Structure

- **Source:** `assets/css/input.css` (Tailwind directives)
- **Generated:** `assets/css/main.css` (compiled, auto-included by all pages)
- The shared head component `view/components/head.php` auto-includes `main.css` via an `$asset()` helper for stable URLs

## Project Structure

### Directory Layout

```
PBL-Perpustakaan/
├── index.php                 # Entry point & router
├── config/
│   ├── Koneksi.php          # Database PDO connection
│   └── koneksi.example.php  # Example config template
├── controller/              # Business logic controllers
│   ├── LoginController.php
│   ├── RegisterController.php
│   ├── BookingController.php
│   ├── DashboardController.php
│   ├── ProfileController.php
│   ├── AdminController.php
│   └── AkunController.php
├── model/                   # Data access layer
│   ├── AkunModel.php
│   └── BookingModel.php
├── view/                    # Presentation layer
│   ├── startpage.php       # Landing page
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── booking/            # Booking feature views
│   ├── profile/            # Profile feature views
│   └── components/         # Reusable components
│       ├── head.php        # Common <head> with asset helper
│       ├── captcha.php     # CAPTCHA image generator
│       └── Footer.php
├── assets/
│   ├── css/                # Stylesheets
│   ├── js/                 # Client-side scripts
│   └── image/              # Static images
└── sql/                    # Database schema & migrations
```

### Key Conventions

- **Views** are in `view/` and include the shared head component:

  ```php
  <?php require __DIR__ . '/components/head.php'; ?>
  <title>Page Title</title>
  </head>
  ```

- **Keep business logic out of views** — use controllers for logic and models for database access

- **Database connection** is available via `$pdo` by including `config/Koneksi.php`:

  ```php
  require __DIR__ . '/../config/Koneksi.php';
  // Now use $pdo for queries
  ```

- **Assets** use the `$asset()` helper defined in `view/components/head.php`:

  ```php
  <img src="<?= $asset('assets/image/logo.png') ?>">
  <script src="<?= $asset('assets/js/main.js') ?>" defer></script>
  ```

- **JavaScript MUST be external** — NO inline `<script>` blocks in view files:

  ```php
  <!-- At end of view before </body> -->
  <script>
      // Expose asset base path to external scripts
      window.ASSET_BASE_PATH = '<?= $basePath ?>';
  </script>
  <script src="<?= $asset('assets/js/page-name.js') ?>" defer></script>
  ```

## Routing & Architecture

### URL Routing

Entry point is `index.php`, which routes using `page` and `action` query parameters:

- **Default:** `?page=home` renders `view/startpage.php` directly
- **Example:** `?page=login&action=index` calls `LoginController::index()`
- **Pattern:** `/index.php?page={feature}&action={method}`

### Available Routes

Current wired pages:

- `login` → `LoginController`
- `register` → `RegisterController`
- `guest` → `GuestController`
- `booking` → `BookingController`
- `dashboard` → `DashboardController`
- `profile` → `ProfileController`

### Adding New Features

**Step 1:** Create controller and model files:

```php
// controller/FeatureController.php
<?php
class FeatureController {
    public function index() {
        require __DIR__ . '/../view/feature/index.php';
    }
}
```

**Step 2:** Update the autoload map in `index.php`:

```php
$mapmodeldancontroller = [
    // ... existing entries ...
    'FeatureController' => __DIR__ . '/controller/FeatureController.php',
    'FeatureModel' => __DIR__ . '/model/FeatureModel.php',
];
```

**Step 3:** Add routing case in `index.php`:

```php
switch ($halaman) {
    // ... existing cases ...
    case 'feature':
        $controller = new FeatureController();
        break;
}
```

**Step 4:** Access at `/index.php?page=feature&action=index`

## Key Features

### Session Management

- Global session started in `index.php` via `session_start()`
- User data stored in `$_SESSION['user']`
- **User notifications:** JavaScript `alert()` used for feedback messages (no more session flash messages)
- Auth guards can be added to controllers or views (check `$_SESSION['user']`)

### CAPTCHA System

**Server-side:** `view/components/captcha.php` generates a GD image and stores the code in `$_SESSION['code']`

**Client-side:** `assets/js/captcha.js` handles refresh with cache-busting

**View implementation:**

```php
<img src="<?= $asset('view/components/captcha.php') ?>" id="captchaImage" alt="CAPTCHA">
<input type="text" name="captcha" required>
```

**Validation in controller:**

```php
if (strtolower($_POST['captcha']) !== strtolower($_SESSION['code'])) {
    echo "<script>alert('CAPTCHA incorrect'); window.location.href='index.php?page=login';</script>";
    exit;
}
```

### Asset Helper

The `$asset()` function in `view/components/head.php` generates absolute URLs from project root:

```php
$asset('assets/css/main.css')              // → /assets/css/main.css
$asset('view/components/captcha.php')      // → /view/components/captcha.php
```

## Development Notes

### Important Conventions

- **No framework** — plain PHP with manual routing and autoloading
- **Session-first** — all pages have access to `$_SESSION` (started globally)
- **Views are passive** — business logic belongs in controllers
- **Database via PDO** — `$pdo` available after including `config/Koneksi.php`
- **Tailwind must be rebuilt** — run `npm run build:css` after editing `assets/css/input.css`
- **External JavaScript only** — all JavaScript must be in `assets/js/` files, never inline in views

### JavaScript Organization

All JavaScript code must be in external files under `assets/js/`:

- **Page-specific scripts:** `assets/js/{page-name}.js` (e.g., `dashboard.js`, `booking.js`, `profile.js`)
- **Feature-specific scripts:** `assets/js/{feature}.js` (e.g., `captcha.js`, `auth.js`)
- **Global utilities:** `assets/js/main.js` (only for truly global functionality)

**Current JavaScript modules:**

- `auth.js` — logout functionality
- `captcha.js` — CAPTCHA refresh with cache-busting
- `booking.js` — booking form interactions
- `dashboard.js` — booking finish flow and room description modals
- `profile.js` — profile page functionality
- `skrip_sebelum_dashboard.js` — pre-dashboard initialization
- `main.js` — reserved for global utilities

### Security Considerations

- `config/Koneksi.php` contains credentials — avoid committing sensitive data
- Use prepared statements with PDO for all user input
- Validate and sanitize all `$_POST` / `$_GET` data
- Always use `exit;` after `header('Location: ...')` redirects
- CAPTCHA protects form submissions

### Common Tasks

**Start development server:**

```pwsh
# Via XAMPP Control Panel:
# 1. Start Apache
# 2. Start MySQL
# 3. Open http://localhost/
```

**Watch Tailwind CSS changes:**

```pwsh
npm run watch:css
```

**Check autoload mapping:**

```pwsh
# View current mappings in index.php
Get-Content index.php | Select-String "mapmodeldancontroller" -Context 0,15
```

## Contributing

1. Follow existing code structure and naming conventions
2. Update `index.php` autoload map when adding controllers/models
3. Keep views simple — move logic to controllers
4. **Never write inline JavaScript** — always create external files in `assets/js/`
5. Test CAPTCHA implementation on forms
6. Ensure Tailwind CSS is rebuilt before committing UI changes
7. Do not commit `config/Koneksi.php` with real credentials

## Troubleshooting

**404 errors:** Check that controller class name matches the autoload map and switch-case in `index.php`

**Missing styles:** Run `npm run build:css` to regenerate `assets/css/main.css`

**Database connection fails:** Verify credentials in `config/Koneksi.php` and ensure MySQL is running

**CAPTCHA not showing:** Check that GD extension is enabled in PHP (`php.ini` → `extension=gd`)

**Session issues:** Ensure `session_start()` is called before any output (handled in `index.php`)

## License

Internal project for educational purposes.
