<?php
/**
 * ============================================================================
 * ERROR.PHP - 404 Error Page View
 * ============================================================================
 * 
 * Simple 404 error page yang ditampilkan ketika:
 * - Route tidak ditemukan (controller/action tidak exists)
 * - method_exists() check fails di index.php
 * - Invalid page parameter
 * 
 * FITUR:
 * - Centered error card dengan white background
 * - Large "404" text (red, bold)
 * - Error message: "Halaman tidak ditemukan."
 * - "Kembali ke Awal" button → redirects to home
 * 
 * TRIGGER CONDITIONS (index.php):
 * 1. $halaman tidak match dengan any case in switch statement
 * 2. $controller instantiated tapi $action method tidak exists
 * 3. method_exists($controller, $action) returns false
 * 4. Default fallthrough (missing break; in switch)
 * 
 * ROUTING:
 * - Current page: Rendered directly from index.php
 * - Not a routed page (no ?page=error parameter)
 * - Return link: index.php?page=home (startpage)
 * 
 * LAYOUT STRUCTURE:
 * - Full page: bg-gray-100 background
 * - Centered content: flex items-center justify-center
 * - White card: shadow-lg, rounded-lg, p-8
 * - Max width: max-w-md
 * - Elements:
 *   * "404" heading (4xl font, red)
 *   * Error message (lg font, gray)
 *   * Return button (blue, hover effect)
 * 
 * TARGET ELEMENTS:
 * - None (static page, no JavaScript)
 * 
 * STYLING:
 * - Card: White bg with shadow
 * - Heading: text-4xl font-bold text-red-500
 * - Message: text-lg text-gray-700
 * - Button: bg-blue-600 hover:bg-blue-700 (pill-shaped)
 * 
 * COMPONENTS:
 * - head.php: Standard head component
 * - Footer.php: Site footer
 * 
 * RESPONSIVE DESIGN:
 * - Mobile: Full width with px-4 padding
 * - Desktop: max-w-md centered card
 * - Consistent spacing: mb-4, mb-6 between elements
 * 
 * USER EXPERIENCE:
 * - Clear error indication (404 + red text)
 * - User-friendly message in Indonesian
 * - Single obvious action: Return to home
 * - No technical details exposed
 * 
 * INTEGRATION:
 * - Rendered by: index.php (default case in switch)
 * - No controller/model involved
 * - Simple static HTML view
 * 
 * IMPROVEMENT SUGGESTIONS:
 * - Add error logging untuk debug
 * - Show requested URL untuk context
 * - Add search functionality
 * - Suggest popular pages
 * - Track 404s untuk identify broken links
 * 
 * @package BookEZ
 * @subpackage Views
 * @version 1.0
 */
require __DIR__ . '/components/head.php';
?>
<title>404 - Halaman Tidak Ditemukan</title>
</head>
<body class="bg-gray-100 h-full mx-auto px-4 py-8">
    <main class="flex flex-col items-center justify-center">
        <div class="bg-white shadow-lg rounded-lg p-8 text-center max-w-md">
            <h1 class="text-4xl font-bold text-red-500 mb-4">404</h1>
            <p class="text-lg text-gray-700 mb-6">Halaman tidak ditemukan.</p>
            <a href="index.php?page=home" class="inline-block px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Kembali ke Awal</a>
        </div>
    </main>
    <?php require __DIR__ . '/components/Footer.php'; ?>
</body>
</html>
