<?php
/**
 * ============================================================================
 * HEAD.PHP - Standard HTML Head Component
 * ============================================================================
 * 
 * Reusable head component yang WAJIB di-include di SEMUA view files.
 * Provides essential HTML structure, meta tags, asset helper, dan base path.
 * 
 * CRITICAL FEATURES:
 * 1. BASE PATH COMPUTATION
 *    - Auto-detects deployment path (root atau subfolder)
 *    - Works di: http://localhost/ atau http://localhost/PBL%20Perpustakaan/
 *    - Computation: Compare document root vs project root filesystem paths
 *    - Normalizes slashes dan ensures leading slash
 * 
 * 2. ASSET HELPER FUNCTION
 *    - $asset(string $p): Helper function untuk construct asset URLs
 *    - Example: $asset('assets/css/main.css') → /assets/css/main.css
 *    - Example (subfolder): $asset('assets/css/main.css') → /PBL%20Perpustakaan/assets/css/main.css
 *    - Handles leading slashes automatically
 *    - Collapses duplicate slashes dengan preg_replace
 * 
 * 3. STANDARD META TAGS
 *    - charset: UTF-8
 *    - viewport: width=device-width, initial-scale=1.0 (responsive)
 *    - X-UA-Compatible: IE=7 (IE compatibility mode)
 * 
 * 4. GLOBAL ASSETS
 *    - Favicon: favicon.ico (project root)
 *    - Main CSS: assets/css/main.css (Tailwind compiled output)
 *    - Both auto-linked dengan $asset() helper
 * 
 * USAGE PATTERN (REQUIRED IN ALL VIEWS):
 * ```php
 * <?php require __DIR__ . '/components/head.php'; ?>
 * <title>Your Page Title</title>
 * </head>
 * ```
 * 
 * PATH COMPUTATION LOGIC:
 * 1. Get document root: $_SERVER['DOCUMENT_ROOT']
 * 2. Get project root: dirname(__DIR__, 2) (2 levels up from view/components)
 * 3. Normalize paths: Replace backslashes dengan forward slashes
 * 4. Check if project root starts with document root
 * 5. Extract relative path: substr(projectRoot, strlen(docRoot))
 * 6. Ensure leading slash dan collapse duplicates
 * 
 * ASSET HELPER INTERNALS:
 * - Anonymous function (fn syntax): fn(string $p) => ...
 * - Concatenates: $basePath + '/' + ltrim($p, '/')
 * - Normalizes: preg_replace('#/+#', '/', ...) removes duplicate slashes
 * - Example input: '/assets//css/main.css' → '/assets/css/main.css'
 * 
 * EXPOSED VARIABLES:
 * - $basePath (string): Base path untuk project (e.g., '' atau '/PBL%20Perpustakaan')
 * - $asset (Closure): Helper function untuk generate asset URLs
 * 
 * DEPLOYMENT SCENARIOS:
 * 1. XAMPP Root (http://localhost/):
 *    - Document root: C:\xampp\htdocs
 *    - Project root: C:\xampp\htdocs
 *    - $basePath: ''
 *    - $asset('logo.png') → '/logo.png'
 * 
 * 2. XAMPP Subfolder (http://localhost/PBL%20Perpustakaan/):
 *    - Document root: C:\xampp\htdocs
 *    - Project root: C:\xampp\htdocs\PBL Perpustakaan
 *    - $basePath: '/PBL Perpustakaan'
 *    - $asset('logo.png') → '/PBL%20Perpustakaan/logo.png'
 * 
 * HTML STRUCTURE:
 * - <!DOCTYPE html>
 * - <html lang="en">
 * - <head> (opened, NOT closed)
 * - View file MUST add <title> and close </head>
 * 
 * LINKED ASSETS:
 * - favicon.ico: Site icon (appears in browser tab)
 * - assets/css/main.css: Tailwind compiled CSS (auto-included globally)
 * 
 * CSS AUTO-INCLUSION:
 * - main.css linked dengan htmlspecialchars($asset(...), ENT_QUOTES)
 * - XSS protection via ENT_QUOTES
 * - Applies to all pages automatically
 * 
 * ERROR HANDLING:
 * - If document root unavailable: $basePath defaults to ''
 * - If project root unavailable: $basePath defaults to ''
 * - Graceful degradation: Asset URLs will work from root
 * 
 * SECURITY:
 * - htmlspecialchars() applied to all asset URLs (ENT_QUOTES)
 * - Prevents XSS via attribute injection
 * - Safe for user-generated paths
 * 
 * INTEGRATION:
 * - Used by: ALL view files (startpage, login, register, dashboard, etc.)
 * - Required pattern: require __DIR__ . '/components/head.php';
 * - Relative path resolution: __DIR__ ensures correct path from any view
 * 
 * MAINTENANCE:
 * - CRITICAL: Do NOT modify base path logic without testing both scenarios
 * - Test changes in: root deployment AND subfolder deployment
 * - $asset() helper is used extensively across all views
 * 
 * @package BookEZ
 * @subpackage Views\Components
 * @version 1.0
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php

    // Compute a base URL path to the project root so asset links work from any view.
    $docRootFs = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : null;
    $projectRootFs = realpath(dirname(__DIR__, 2)); // project root two levels up from view/header
    $basePath = '';
    if ($docRootFs && $projectRootFs) {
        $docRootNorm = str_replace('\\', '/', $docRootFs);
        $projRootNorm = str_replace('\\', '/', $projectRootFs);
        if (strpos($projRootNorm, $docRootNorm) === 0) {
            $basePath = substr($projRootNorm, strlen($docRootNorm));
        }
    }
    // Ensure leading slash and collapse duplicate slashes
    $basePath = '/' . ltrim($basePath, '/');
    $asset = fn(string $p) => preg_replace('#/+#', '/', $basePath . '/' . ltrim($p, '/'));
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=7">
    <link rel="shortcut icon" href="<?= htmlspecialchars($asset('favicon.ico'), ENT_QUOTES) ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/main.css'), ENT_QUOTES) ?>">