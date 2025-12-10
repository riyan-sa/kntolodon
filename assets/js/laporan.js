/**
 * Laporan.js - Handle tab switching, filters, dan download untuk halaman laporan
 * NO inline scripts - all event handlers use event delegation
 */

// Initialize data from data attributes
let ASSET_BASE_PATH = '';

document.addEventListener('DOMContentLoaded', function() {
    // Get base path from data attribute  
    const dataContainer = document.getElementById('laporan-data');
    if (dataContainer) {
        ASSET_BASE_PATH = dataContainer.dataset.basePath || '';
        window.ASSET_BASE_PATH = ASSET_BASE_PATH;
    }
    
    initLaporanPage();
});

/**
 * Initialize laporan page
 */
function initLaporanPage() {
    // Get current tab from URL or default to 'harian'
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('tab') || 'harian';
    
    // Show appropriate tab content
    showTab(currentTab);
    
    // Setup event listeners untuk filter inputs
    setupFilterListeners();
    
    // Setup event delegation for all tab buttons (with onclick attributes)
    // This allows existing onclick to work OR data attributes
    document.addEventListener('click', function(event) {
        // Tab switching buttons
        if (event.target.closest('[data-laporan-tab]')) {
            const btn = event.target.closest('[data-laporan-tab]');
            const tabName = btn.getAttribute('data-tab-name');
            gantiTabLaporan(tabName);
            event.preventDefault();
        }
        
        // Download buttons
        if (event.target.closest('[data-download-laporan]')) {
            const btn = event.target.closest('[data-download-laporan]');
            const periode = btn.getAttribute('data-periode');
            downloadLaporan(periode);
            event.preventDefault();
        }
    });
}

/**
 * Setup event listeners untuk semua filter inputs
 */
function setupFilterListeners() {
    // Harian - Date picker
    const filterTanggal = document.getElementById('filter-tanggal');
    if (filterTanggal) {
        filterTanggal.addEventListener('change', function() {
            applyFilter('harian', { tanggal: this.value });
        });
    }
    
    // Mingguan - Date picker
    const filterTanggalMingguan = document.getElementById('filter-tanggal-mingguan');
    if (filterTanggalMingguan) {
        filterTanggalMingguan.addEventListener('change', function() {
            applyFilter('mingguan', { tanggal: this.value });
        });
    }
    
    // Bulanan - Month & Year select
    const filterBulan = document.getElementById('filter-bulan');
    const filterTahunBulanan = document.getElementById('filter-tahun-bulanan');
    if (filterBulan && filterTahunBulanan) {
        filterBulan.addEventListener('change', function() {
            const bulanVal = filterBulan.value;
            const tahunVal = filterTahunBulanan.value;
            applyFilter('bulanan', { bulan: bulanVal, tahun: tahunVal });
        });
        filterTahunBulanan.addEventListener('change', function() {
            const bulanVal = filterBulan.value;
            const tahunVal = filterTahunBulanan.value;
            applyFilter('bulanan', { bulan: bulanVal, tahun: tahunVal });
        });
    }
    
    // Tahunan - Year select
    const filterTahunTahunan = document.getElementById('filter-tahun-tahunan');
    if (filterTahunTahunan) {
        filterTahunTahunan.addEventListener('change', function() {
            applyFilter('tahunan', { tahun: this.value });
        });
    }
}

/**
 * Apply filter dan reload page dengan query parameters
 */
function applyFilter(tab, filters) {
    const params = new URLSearchParams();
    params.set('page', 'admin');
    params.set('action', 'laporan');
    params.set('tab', tab);
    
    // Add filter parameters
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            params.set(key, filters[key]);
        }
    });
    
    // Reload page dengan filter baru
    window.location.href = '?' + params.toString();
}

/**
 * Switch tab laporan
 */
function gantiTabLaporan(tab) {
    // Update URL dengan tab baru
    const params = new URLSearchParams(window.location.search);
    params.set('tab', tab);
    
    // Get current filter values before reload
    if (tab === 'harian') {
        const tanggal = document.getElementById('filter-tanggal')?.value;
        if (tanggal) params.set('tanggal', tanggal);
    } else if (tab === 'mingguan') {
        const tanggal = document.getElementById('filter-tanggal-mingguan')?.value;
        if (tanggal) params.set('tanggal', tanggal);
    } else if (tab === 'bulanan') {
        const bulan = document.getElementById('filter-bulan')?.value;
        const tahun = document.getElementById('filter-tahun-bulanan')?.value;
        if (bulan) params.set('bulan', bulan);
        if (tahun) params.set('tahun', tahun);
    } else if (tab === 'tahunan') {
        const tahun = document.getElementById('filter-tahun-tahunan')?.value;
        if (tahun) params.set('tahun', tahun);
    }
    
    window.location.href = '?' + params.toString();
}

/**
 * Show specific tab content
 */
function showTab(tab) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
        content.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'text-sky-600', 'border-sky-600');
        btn.classList.add('text-slate-500', 'border-transparent');
    });
    
    // Show selected tab content
    const targetContent = document.getElementById(`content-${tab}`);
    if (targetContent) {
        targetContent.style.display = 'block';
        targetContent.classList.add('active');
    }
    
    // Activate corresponding tab buttons (there are multiple sets)
    document.querySelectorAll(`#btn-${tab}, #btn-${tab}-2, #btn-${tab}-3, #btn-${tab}-4`).forEach(btn => {
        btn.classList.add('active', 'text-sky-600', 'border-sky-600');
        btn.classList.remove('text-slate-500', 'border-transparent');
    });
}

/**
 * Download laporan (placeholder - akan dikembangkan lebih lanjut)
 */
function downloadLaporan(periode) {
    // Get current filters
    const urlParams = new URLSearchParams(window.location.search);
    const tanggal = urlParams.get('tanggal') || '';
    const bulan = urlParams.get('bulan') || '';
    const tahun = urlParams.get('tahun') || '';
    
    // Build download URL
    const params = new URLSearchParams();
    params.set('page', 'admin');
    params.set('action', 'download_laporan');
    params.set('periode', periode);
    if (tanggal) params.set('tanggal', tanggal);
    if (bulan) params.set('bulan', bulan);
    if (tahun) params.set('tahun', tahun);
    
    // For now, just alert (implementation will be added later)
    alert('Fitur download akan segera tersedia. Format: PDF/Excel untuk periode ' + periode);
    
    // TODO: Implement actual download functionality
    // window.location.href = '?' + params.toString();
}

// Expose functions to global scope
window.gantiTabLaporan = gantiTabLaporan;
window.downloadLaporan = downloadLaporan;
