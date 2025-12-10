<?php
/**
 * ============================================================================
 * NAVBAR_ADMIN.PHP - Admin Navigation Bar Component
 * ============================================================================
 * 
 * Reusable navigation bar untuk admin pages (Admin dan Super Admin).
 * Conditionally shows links based on user role.
 * 
 * FEATURES:
 * 1. ROLE-BASED NAVIGATION
 *    - Admin: 5 links (Kelola Ruangan, Laporan, Booking-List, Member-List, Dashboard)
 *    - Super Admin: 7 links (+ Booking Eksternal + Pengaturan)
 * 
 * 2. NAVIGATION LINKS (All Admins)
 *    a. Logo (left) → ?page=admin (dashboard)
 *    b. Kelola Ruangan → ?page=admin&action=kelola_ruangan
 *    c. Laporan Peminjaman → ?page=admin&action=laporan
 *    d. Booking-List → ?page=admin&action=booking_list
 *    e. Member-List → ?page=admin&action=member_list
 * 
 * 3. SUPER ADMIN EXCLUSIVE LINKS
 *    a. Booking Eksternal → ?page=admin&action=booking_external (first position)
 *    b. Pengaturan → ?page=admin&action=pengaturan (last position)
 *    - Conditional rendering: <?php if ($_SESSION['user']['role'] === 'Super Admin'): ?>
 * 
 * 4. INLINE SVG ICONS
 *    - NO external icon libraries (Font Awesome, Lucide, etc.)
 *    - All icons: Inline SVG dengan Heroicons style
 *    - Size: w-5 h-5 (20x20px)
 *    - Colors: text-black default, group-hover:text-sky-600
 *    - Icons used:
 *      * Booking Eksternal: Settings/gear icon
 *      * Kelola Ruangan: Building/office icon
 *      * Laporan: Document icon
 *      * Booking-List: Clipboard icon
 *      * Member-List: Users icon
 *      * Pengaturan: Sliders icon
 * 
 * 5. PROFILE SECTION (Right)
 *    - Username display: $_SESSION['user']['username']
 *    - Profile photo: foto_profil or placeholder icon
 *    - Links to: ?page=profile
 *    - Photo size: h-10 w-10 (40x40px circle)
 * 
 * LAYOUT STRUCTURE:
 * - Sticky navbar: sticky top-0 z-50
 * - White background: bg-white
 * - Shadow: shadow-sm
 * - Padding: py-4 px-6
 * - Flex layout: justify-between (logo left, links center, profile right)
 * - Responsive: flex-col on mobile, lg:flex-row on desktop
 * 
 * NAVIGATION PATTERN:
 * - Each link: flex items-center gap-2
 * - Icon + Text label
 * - Hover effect: text-slate-700 → hover:text-sky-600
 * - Group hover: Icon color changes dengan text
 * - Transitions: transition-colors
 * 
 * ROLE CHECK:
 * - Reads: $_SESSION['user']['role']
 * - Super Admin check: === 'Super Admin' (strict equality)
 * - Regular Admin: Shows 5 links only
 * - IMPORTANT: Admin sees subset, Super Admin sees all
 * 
 * RESPONSIVE DESIGN:
 * - Mobile: Stacked vertically (flex-col)
 *   - Logo centered at top
 *   - Links wrap (flex-wrap) dengan gap-6
 *   - Profile below links
 * - Desktop (lg): Horizontal row (lg:flex-row)
 *   - Logo left
 *   - Links center (gap-8)
 *   - Profile right
 * 
 * ICON HOVER BEHAVIOR:
 * - Group class on <a> tag
 * - Icon has: group-hover:text-sky-600
 * - Synced color change dengan text label
 * - Smooth transition: transition-colors
 * 
 * PROFILE PHOTO HANDLING:
 * - Check: isset($_SESSION['user']['foto_profil']) && !empty(...)
 * - If exists: <img src="<?= $asset(...) ?>" />
 * - If NOT exists: Placeholder user icon (SVG)
 * - Rounded circle: rounded-full
 * - Overflow hidden: overflow-hidden (crops image to circle)
 * 
 * SESSION DEPENDENCY:
 * - CRITICAL: Requires $_SESSION['user'] to be set
 * - Fields used:
 *   * role: For conditional Super Admin links
 *   * username: Display name
 *   * foto_profil: Profile photo path
 * 
 * USAGE PATTERN:
 * ```php
 * <?php require __DIR__ . '/../components/navbar_admin.php'; ?>
 * ```
 * - Included in: All admin view files
 * - Path varies: '../components/' or './components/' based on view location
 * 
 * LINK DESTINATIONS:
 * - All links use query string routing: ?page=admin&action={action}
 * - Logo link: ?page=admin (default action = index = dashboard)
 * - Profile link: ?page=profile (user profile page)
 * 
 * CSS CLASSES:
 * - Navbar: sticky, top-0, z-50 (always visible on scroll)
 * - Links: font-medium (semi-bold text)
 * - Logo: h-8, w-auto, object-contain (responsive scaling)
 * - Logo animation: logo-scale class (defined in admin-dashboard.css)
 * 
 * ACCESSIBILITY:
 * - All links have text labels (not icon-only)
 * - Semantic HTML: <nav> element
 * - Alt text on images (logo, profile photo)
 * - Logical tab order (left to right)
 * 
 * INTEGRATION:
 * - Used by: All admin pages (dashboard, kelola_ruangan, laporan, etc.)
 * - Assets: $asset() helper from head.php
 * - Session: $_SESSION['user'] from LoginController
 * 
 * MAINTENANCE:
 * - To add new link: Insert <a> block in appropriate position
 * - Icon source: https://heroicons.com/ (copy SVG code)
 * - Remember: Conditionally render for role restrictions
 * 
 * @package BookEZ
 * @subpackage Views\Components
 * @version 1.0
 */
?>
    <!-- Navbar Admin -->
    <nav class="bg-white py-4 px-6 flex flex-col lg:flex-row justify-between items-center shadow-sm sticky top-0 z-50">
        <!-- Logo Section -->
        <a href="?page=admin" class="flex items-center gap-2 mb-4 lg:mb-0">
            <img src="<?= $asset('/assets/image/logo.png') ?>" alt="BookEZ Logo" class="h-8 w-auto object-contain logo-scale">
        </a>

        <!-- Navigation Links -->
        <div class="flex flex-wrap justify-center gap-6 lg:gap-8">
            <?php if ($_SESSION['user']['role'] === 'Super Admin'): ?>
                <a href="?page=admin&action=booking_external" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Booking Eksternal</span>
                </a>
            <?php endif; ?>
            <a href="?page=admin&action=kelola_ruangan" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                </svg>
                <span>Kelola Ruangan</span>
            </a>
            <a href="?page=admin&action=laporan" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Laporan Peminjaman</span>
            </a>
            <a href="?page=admin&action=booking_list" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span>Booking-List</span>
            </a>
            <a href="?page=admin&action=member_list" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>Member-List</span>
            </a>
            <?php if ($_SESSION['user']['role'] === 'Super Admin'): ?>
                <a href="?page=admin&action=pengaturan" class="flex items-center gap-2 text-slate-700 hover:text-sky-600 font-medium transition-colors group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black group-hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    <span>Pengaturan</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- User Profile -->
        <a href="?page=profile" class="flex items-center gap-3">
            <span class="text-xl font-bold text-gray-800"><?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['username']) : 'Guest' ?></span>
            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 overflow-hidden">
                <?php if (isset($_SESSION['user']['foto_profil']) && !empty($_SESSION['user']['foto_profil'])): ?>
                    <img src="<?= htmlspecialchars($asset($_SESSION['user']['foto_profil']), ENT_QUOTES) ?>" 
                         alt="Foto Profil" 
                         class="w-full h-full object-cover">
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                <?php endif; ?>
            </div>
        </a>
    </nav>