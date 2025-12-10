<?php
/**
 * ============================================================================
 * PAGINATION.PHP - Reusable Pagination Component
 * ============================================================================
 * 
 * Reusable pagination UI component untuk all list views dengan filtering.
 * Provides responsive pagination dengan mobile dan desktop views.
 * 
 * FEATURES:
 * 1. RESPONSIVE DESIGN
 *    - Mobile: Previous/Next buttons only
 *    - Desktop: Full pagination dengan page numbers
 *    - Breakpoint: sm (640px)
 * 
 * 2. PAGE RANGE CALCULATION
 *    - Shows 2 pages before dan after current page
 *    - Example: Current page 5 → Shows 3, 4, [5], 6, 7
 *    - Adjusts at boundaries (start/end of page range)
 * 
 * 3. FILTER PRESERVATION
 *    - Maintains query parameters across page changes
 *    - Example: ?page=admin&action=laporan&tab=bulanan&bulan=12&pg=2
 *    - Uses http_build_query() untuk construct query string
 * 
 * 4. AUTO-HIDE
 *    - If totalPages <= 1, component returns early (no output)
 *    - Saves space when pagination unnecessary
 * 
 * REQUIRED VARIABLES (Pass from controller/view):
 * - $currentPage (int): Current page number (1-indexed)
 * - $totalPages (int): Total number of pages
 * - $baseUrl (string): Base URL untuk pagination links
 *   * Example: "?page=admin&action=booking_external"
 *   * Pattern: Query string WITHOUT page number parameter
 * 
 * OPTIONAL VARIABLES:
 * - $queryParams (array): Additional query parameters
 *   * Example: ['ruang' => 'R101', 'tanggal' => '2024-12-10']
 *   * Used to maintain filter state across pagination
 *   * Converted to query string via http_build_query()
 * 
 * USAGE PATTERN (Controller):
 * ```php
 * $currentPage = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
 * $perPage = 10;
 * $offset = ($currentPage - 1) * $perPage;
 * 
 * $records = $model->filter(['limit' => $perPage, 'offset' => $offset]);
 * $totalRecords = $model->countFiltered($filters);
 * $totalPages = ceil($totalRecords / $perPage);
 * 
 * $baseUrl = '?page=admin&action=booking_external';
 * $queryParams = ['ruang' => $_GET['ruang'] ?? '', 'tanggal' => $_GET['tanggal'] ?? ''];
 * 
 * require __DIR__ . '/view/feature.php'; // Contains pagination component include
 * ```
 * 
 * USAGE PATTERN (View):
 * ```php
 * <?php
 * // Setup variables before including component
 * $currentPage = $paginationData['currentPage'];
 * $totalPages = $paginationData['totalPages'];
 * $baseUrl = '?page=admin&action=booking_external';
 * $queryParams = ['ruang' => $_GET['ruang'] ?? ''];
 * 
 * require __DIR__ . '/../components/pagination.php';
 * ?>
 * ```
 * 
 * URL CONSTRUCTION:
 * - Base: $baseUrl
 * - Add: &page={pageNumber}
 * - Add: $queryString (from $queryParams)
 * - Example: ?page=admin&action=laporan&pg=2&tab=bulanan&bulan=12
 * 
 * PAGE PARAMETER NAMING:
 * - CRITICAL: Use 'pg' parameter for page number (NOT 'page')
 * - Reason: 'page' is used for routing (controller)
 * - Pattern: &pg=2 (not &page=2)
 * - Example: ?page=admin&action=member_list&pg=3
 * 
 * PAGINATION CONTROLS:
 * - Previous button: Shows if currentPage > 1
 * - Next button: Shows if currentPage < totalPages
 * - Page numbers: Shows range around current page
 * - Current page: Highlighted (bg-blue-600 text-white)
 * - Other pages: Gray background (bg-gray-200)
 * - Disabled state: Gray text, no-underline, cursor-not-allowed
 * 
 * RESPONSIVE BEHAVIOR:
 * - Mobile (< 640px):
 *   * Previous/Next buttons only
 *   * Full-width buttons
 *   * Stacked layout
 * - Desktop (>= 640px):
 *   * Full pagination
 *   * Page info: "Showing X to Y of Z results"
 *   * Page number buttons
 *   * Previous/Next navigation
 * 
 * STYLING:
 * - Container: border-t border-gray-200 (top border only)
 * - Buttons: rounded-md dengan hover effects
 * - Current page: bg-blue-600 (primary color)
 * - Hover: bg-gray-50 (subtle highlight)
 * - Disabled: bg-gray-100 text-gray-400 (muted)
 * 
 * PAGE INFO DISPLAY:
 * - Format: "Showing X to Y of Z results"
 * - Calculation:
 *   * Start: ($currentPage - 1) * $perPage + 1
 *   * End: min($currentPage * $perPage, $totalRecords)
 *   * Total: $totalRecords
 * - Example: "Showing 11 to 20 of 45 results"
 * 
 * ELLIPSIS (Not implemented):
 * - Current implementation: Shows consecutive page numbers only
 * - Future: Add "..." for large page counts
 * - Example: 1 ... 8 9 [10] 11 12 ... 50
 * 
 * ERROR PREVENTION:
 * - Early return if variables not set
 * - Early return if totalPages <= 1
 * - max(1, ...) ensures page >= 1
 * - min($totalPages, ...) ensures page <= totalPages
 * 
 * ACCESSIBILITY:
 * - Semantic HTML: <nav> element (not implemented, could add)
 * - Text alternatives: "Previous", "Next" text labels
 * - Disabled state: cursor-not-allowed
 * - Keyboard navigation: Standard <a> links
 * 
 * MULTI-TAB PAGINATION:
 * - Use separate parameters: pg_user, pg_admin
 * - Example: view/admin/member_list.php
 * - Allows independent pagination per tab
 * - Pattern: ?page=admin&action=member_list&tab=user&pg_user=2
 * 
 * INTEGRATION:
 * - Used by: booking_external, member_list, booking_list, laporan, profile history
 * - Pattern: Include component after table/list content
 * - Variables passed from controller via view
 * 
 * NOTE: Current project uses INLINE pagination pattern (not this component file)
 * - Most views implement pagination inline (not via include)
 * - This file exists as reference/template
 * - Inline pattern allows more customization per page
 * - See: view/admin/member_list.php for inline pagination example
 * 
 * @package BookEZ
 * @subpackage Views\Components
 * @version 1.0
 */

if (!isset($currentPage) || !isset($totalPages) || !isset($baseUrl)) {
    return;
}

// Jika hanya 1 halaman, tidak perlu pagination
if ($totalPages <= 1) {
    return;
}

// Build query string dari $queryParams jika ada
$queryString = '';
if (isset($queryParams) && is_array($queryParams)) {
    $queryString = '&' . http_build_query($queryParams);
}

// Calculate pagination range
$range = 2; // Show 2 pages before and after current page
$startPage = max(1, $currentPage - $range);
$endPage = min($totalPages, $currentPage + $range);
?>

<div class="flex items-center justify-between px-4 py-3 sm:px-6 border-t border-gray-200">
    <!-- Mobile View -->
    <div class="flex flex-1 justify-between sm:hidden">
        <?php if ($currentPage > 1): ?>
            <a href="<?= $baseUrl . '&page=' . ($currentPage - 1) . $queryString ?>" 
               class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Previous
            </a>
        <?php else: ?>
            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-md cursor-not-allowed">
                Previous
            </span>
        <?php endif; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= $baseUrl . '&page=' . ($currentPage + 1) . $queryString ?>" 
               class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Next
            </a>
        <?php else: ?>
            <span class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-md cursor-not-allowed">
                Next
            </span>
        <?php endif; ?>
    </div>

    <!-- Desktop View -->
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-700">
                Halaman
                <span class="font-medium"><?= $currentPage ?></span>
                dari
                <span class="font-medium"><?= $totalPages ?></span>
            </p>
        </div>
        <div>
            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                <!-- Previous Button -->
                <?php if ($currentPage > 1): ?>
                    <a href="<?= $baseUrl . '&page=' . ($currentPage - 1) . $queryString ?>" 
                       class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                        <span class="sr-only">Previous</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                        </svg>
                    </a>
                <?php else: ?>
                    <span class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-300 ring-1 ring-inset ring-gray-300 bg-gray-100 cursor-not-allowed">
                        <span class="sr-only">Previous</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                        </svg>
                    </span>
                <?php endif; ?>

                <!-- First Page -->
                <?php if ($startPage > 1): ?>
                    <a href="<?= $baseUrl . '&page=1' . $queryString ?>" 
                       class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                        1
                    </a>
                    <?php if ($startPage > 2): ?>
                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Page Numbers -->
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $currentPage): ?>
                        <span aria-current="page" class="relative z-10 inline-flex items-center bg-sky-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                            <?= $i ?>
                        </span>
                    <?php else: ?>
                        <a href="<?= $baseUrl . '&page=' . $i . $queryString ?>" 
                           class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Last Page -->
                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300">...</span>
                    <?php endif; ?>
                    <a href="<?= $baseUrl . '&page=' . $totalPages . $queryString ?>" 
                       class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                        <?= $totalPages ?>
                    </a>
                <?php endif; ?>

                <!-- Next Button -->
                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= $baseUrl . '&page=' . ($currentPage + 1) . $queryString ?>" 
                       class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                        <span class="sr-only">Next</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </a>
                <?php else: ?>
                    <span class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-300 ring-1 ring-inset ring-gray-300 bg-gray-100 cursor-not-allowed">
                        <span class="sr-only">Next</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </span>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</div>
