<?php
/**
 * Pagination Component
 * Reusable pagination UI component untuk semua halaman
 * 
 * Required variables:
 * - $currentPage (int): Halaman saat ini
 * - $totalPages (int): Total halaman
 * - $baseUrl (string): Base URL untuk pagination links
 * 
 * Optional variables:
 * - $queryParams (array): Additional query parameters untuk maintain filter state
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
