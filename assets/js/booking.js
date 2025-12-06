function showModalTime() {
    document.getElementById('modal-overlay').hidden = !document.getElementById('modal-overlay').hidden;
}

function copyCode() {
    // Ambil teks kode
    const codeText = document.getElementById("bookingCode").innerText;
    const icon = document.getElementById("copyIcon");

    // Salin ke clipboard
    navigator.clipboard.writeText(codeText).then(() => {
        // Ubah ikon menjadi checklist sementara
        icon.classList.remove("fa-copy");
        icon.classList.remove("fa-regular");
        icon.classList.add("fa-check");
        icon.classList.add("fa-solid");
        icon.classList.add("text-green-500");

        // Kembalikan ikon setelah 2 detik
        setTimeout(() => {
            icon.classList.add("fa-copy");
            icon.classList.add("fa-regular");
            icon.classList.remove("fa-check");
            icon.classList.remove("fa-solid");
            icon.classList.remove("text-green-500");
        }, 2000);

        // Opsional: Alert kecil
        // alert("Kode berhasil disalin!"); 
    });
}

// Event listeners untuk kode booking page dan booking form
document.addEventListener('DOMContentLoaded', function() {
    // Copy code button
    const copyCodeElement = document.querySelector('[data-action="copy-code"]');
    if (copyCodeElement) {
        copyCodeElement.addEventListener('click', copyCode);
    }
    
    // Back to dashboard buttons
    document.querySelectorAll('[data-action="back-to-dashboard"]').forEach(btn => {
        btn.addEventListener('click', function() {
            window.location.href = '/?page=dashboard';
        });
    });
    
    // Lanjutkan button (open modal)
    const btnLanjutkan = document.getElementById('btn-lanjutkan');
    if (btnLanjutkan) {
        btnLanjutkan.addEventListener('click', showModalTime);
    }
    
    // Close modal button
    const btnCloseModal = document.getElementById('btn-close-modal-time');
    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', showModalTime);
    }
    
    // Hide timeline button
    const btnHideTimeline = document.getElementById('btn-hide-timeline');
    if (btnHideTimeline) {
        btnHideTimeline.addEventListener('click', function() {
            document.getElementById('scheduleTimeline').classList.add('hidden');
        });
    }
});

// ==================== MANAJEMEN ANGGOTA BOOKING ====================

/**
 * Counter untuk tracking jumlah anggota
 * Dimulai dari 1 karena ketua (user yang login) tidak dihitung sebagai anggota
 */
let anggotaCounter = 0;

/**
 * Fungsi untuk membuat card anggota baru
 * @param {number} index - Nomor urut anggota
 * @returns {HTMLElement} - Element card anggota
 */
function createAnggotaCard(index) {
    const card = document.createElement('div');
    card.className = 'anggota-card bg-white border border-gray-200 rounded-lg p-5 shadow-sm h-auto flex flex-col justify-center relative';
    card.setAttribute('data-anggota-index', index);
    
    card.innerHTML = `
        <button type="button" class="btn-hapus-anggota absolute top-2 right-2 text-gray-400 hover:text-red-500 transition p-1" onclick="hapusAnggota(${index})" title="Hapus Anggota">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">NIM / Nomor Induk :</label>
            <input type="text" 
                   name="anggota[${index}][nomor_induk]" 
                   class="clean-input text-gray-800 nim-input" 
                   placeholder="Masukkan NIM..."
                   autocomplete="off"
                   data-index="${index}">
            <p class="text-xs text-gray-400 mt-1 loading-text-${index} hidden">Mencari...</p>
            <p class="text-xs text-red-500 mt-1 error-text-${index} hidden">User tidak ditemukan</p>
        </div>
        <div class="mt-3">
            <label class="block text-sm font-medium text-gray-600 mb-1">Nama Anggota :</label>
            <input type="text" 
                   name="anggota[${index}][nama]" 
                   class="clean-input text-gray-800 nama-input" 
                   placeholder="Otomatis terisi dari NIM..."
                   readonly
                   autocomplete="off">
        </div>
    `;
    
    return card;
}

/**
 * Fungsi untuk menambah anggota baru (with auto-fill support)
 */
function tambahAnggota() {
    anggotaCounter++;
    const container = document.getElementById('anggota-container');
    const card = createAnggotaCard(anggotaCounter);
    
    // Tambahkan animasi masuk
    card.style.opacity = '0';
    card.style.transform = 'translateY(-10px)';
    container.appendChild(card);
    
    // Setup event listener untuk auto-fill
    setupNimInputListener(card, anggotaCounter);
    
    // Trigger animasi
    requestAnimationFrame(() => {
        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    });
    
    // Update tampilan tombol jika perlu
    updateTambahButton();
}

/**
 * Fungsi untuk menghapus anggota
 * @param {number} index - Index anggota yang akan dihapus
 */
function hapusAnggota(index) {
    const card = document.querySelector(`[data-anggota-index="${index}"]`);
    if (card) {
        // Animasi keluar
        card.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            card.remove();
            reindexAnggota();
            updateTambahButton();
        }, 200);
    }
}

/**
 * Fungsi untuk reindex semua anggota setelah penghapusan
 * Agar nomor urut tetap berurutan
 */
function reindexAnggota() {
    const cards = document.querySelectorAll('#anggota-container .anggota-card');
    anggotaCounter = 0;
    
    cards.forEach((card, idx) => {
        const newIndex = idx + 1;
        anggotaCounter = newIndex;
        
        // Update data attribute
        card.setAttribute('data-anggota-index', newIndex);
        
        // Update label
        const label = card.querySelector('label');
        if (label) {
            label.textContent = `Anggota ${newIndex} :`;
        }
        
        // Update input names
        const namaInput = card.querySelector('input[name*="[nama]"]');
        const nimInput = card.querySelector('input[name*="[nomor_induk]"]');
        
        if (namaInput) {
            namaInput.name = `anggota[${newIndex}][nama]`;
        }
        if (nimInput) {
            nimInput.name = `anggota[${newIndex}][nomor_induk]`;
        }
        
        // Update tombol hapus onclick
        const btnHapus = card.querySelector('.btn-hapus-anggota');
        if (btnHapus) {
            btnHapus.setAttribute('onclick', `hapusAnggota(${newIndex})`);
        }
    });
}

/**
 * Update tampilan tombol tambah berdasarkan min/max kapasitas ruangan
 * Maksimal anggota = maksimal_kapasitas_ruangan - 1 (karena ketua dihitung otomatis)
 */
function updateTambahButton() {
    const btn = document.getElementById('btn-tambah-anggota');
    if (!btn) return;
    
    const jumlahAnggota = document.querySelectorAll('#anggota-container .anggota-card').length;
    
    // Ambil max kapasitas dari database (dari span di view)
    const maxKapasitasEl = document.getElementById('max-kapasitas');
    const maxKapasitasRuangan = maxKapasitasEl ? parseInt(maxKapasitasEl.textContent) : 10;
    
    // Maksimal anggota yang bisa ditambah = maxKapasitasRuangan - 1 (ketua)
    const maxAnggotaTambahan = maxKapasitasRuangan - 1;
    
    if (jumlahAnggota >= maxAnggotaTambahan) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.classList.remove('hover:bg-sky-100', 'hover:border-sky-400');
        
        // Update text untuk info user
        const originalText = btn.getAttribute('data-original-text') || 'Tambah Anggota';
        if (!btn.getAttribute('data-original-text')) {
            btn.setAttribute('data-original-text', originalText);
        }
        btn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Kapasitas Penuh (Maks ${maxKapasitasRuangan} Orang)</span>
        `;
    } else {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
        btn.classList.add('hover:bg-sky-100', 'hover:border-sky-400');
        
        // Reset text
        const originalText = btn.getAttribute('data-original-text');
        if (originalText) {
            btn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                ${originalText}
            `;
        }
    }
}

/**
 * Validasi jumlah anggota sebelum submit
 */
function validateBookingForm() {
    const minKapasitasEl = document.getElementById('min-kapasitas');
    const maxKapasitasEl = document.getElementById('max-kapasitas');
    
    if (!minKapasitasEl || !maxKapasitasEl) return true;
    
    const minKapasitas = parseInt(minKapasitasEl.textContent);
    const maxKapasitas = parseInt(maxKapasitasEl.textContent);
    const jumlahAnggota = document.querySelectorAll('#anggota-container .anggota-card').length;
    
    // +1 untuk ketua (user yang login)
    const totalPeserta = jumlahAnggota + 1;
    
    if (totalPeserta < minKapasitas) {
        alert(`Minimal peserta adalah ${minKapasitas} orang (termasuk Anda). Tambahkan ${minKapasitas - totalPeserta} anggota lagi.`);
        return false;
    }
    
    if (totalPeserta > maxKapasitas) {
        alert(`Maksimal peserta adalah ${maxKapasitas} orang (termasuk Anda). Kurangi ${totalPeserta - maxKapasitas} anggota.`);
        return false;
    }
    
    return true;
}

/**
 * Fetch user data dari server berdasarkan NIM/NIP
 * @param {string} nim - NIM/NIP yang akan dicari
 * @param {number} index - Index anggota card
 */
async function fetchUserByNim(nim, index) {
    const loadingText = document.querySelector(`.loading-text-${index}`);
    const errorText = document.querySelector(`.error-text-${index}`);
    const namaInput = document.querySelector(`[data-anggota-index="${index}"] .nama-input`);
    
    // Reset state
    if (errorText) errorText.classList.add('hidden');
    if (loadingText) {
        loadingText.classList.remove('hidden');
        loadingText.textContent = 'Mencari...';
    }
    
    try {
        const response = await fetch(`index.php?page=booking&action=get_user_by_nim&nim=${encodeURIComponent(nim)}`);
        
        // Cek HTTP status
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Ambil response text dulu untuk debug
        const responseText = await response.text();
        
        // Parse JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response Text:', responseText);
            throw new Error('Invalid JSON response dari server');
        }
        
        if (loadingText) loadingText.classList.add('hidden');
        
        if (data.success && data.data) {
            // Auto-fill nama
            if (namaInput) {
                namaInput.value = data.data.username;
                namaInput.classList.remove('border-red-300');
            }
        } else {
            // User tidak ditemukan
            if (errorText) {
                errorText.classList.remove('hidden');
                errorText.textContent = data.message || 'User tidak ditemukan';
            }
            if (namaInput) {
                namaInput.value = '';
                namaInput.classList.add('border-red-300');
            }
        }
    } catch (error) {
        if (loadingText) loadingText.classList.add('hidden');
        if (errorText) {
            errorText.classList.remove('hidden');
            errorText.textContent = 'Gagal mengambil data';
        }
        console.error('Error fetching user:', error);
    }
}

/**
 * Debounce function untuk mengurangi API calls
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Debounced version of fetchUserByNim
const debouncedFetchUser = debounce(fetchUserByNim, 500);

/**
 * Setup event listener untuk NIM input (auto-fill nama)
 */
function setupNimInputListener(card, index) {
    const nimInput = card.querySelector('.nim-input');
    
    if (nimInput) {
        nimInput.addEventListener('input', function(e) {
            const nim = e.target.value.trim();
            
            if (nim.length >= 5) { // Minimal 5 karakter untuk mulai search
                debouncedFetchUser(nim, index);
            } else {
                // Reset jika NIM kurang dari 5 karakter
                const namaInput = card.querySelector('.nama-input');
                const errorText = card.querySelector(`.error-text-${index}`);
                const loadingText = card.querySelector(`.loading-text-${index}`);
                
                if (namaInput) namaInput.value = '';
                if (errorText) errorText.classList.add('hidden');
                if (loadingText) loadingText.classList.add('hidden');
            }
        });
    }
}

// ==================== TIMELINE & VALIDASI BENTROK ====================

/**
 * Load jadwal terpakai untuk tanggal dan ruangan tertentu
 */
async function loadBookedTimeslots(idRuangan, tanggal) {
    if (!tanggal) return;
    
    try {
        const response = await fetch(`?page=booking&action=get_booked_timeslots&id_ruangan=${idRuangan}&tanggal=${tanggal}`);
        const data = await response.json();
        
        if (data.success && data.schedules && data.schedules.length > 0) {
            displayTimeline(data.schedules);
        } else {
            hideTimeline();
        }
    } catch (error) {
        console.error('Error loading schedules:', error);
    }
}

/**
 * Tampilkan timeline jadwal terpakai
 */
function displayTimeline(schedules) {
    const timeline = document.getElementById('scheduleTimeline');
    const content = document.getElementById('timelineContent');
    
    if (!timeline || !content) return;
    
    let html = '';
    schedules.forEach(schedule => {
        html += `
            <div class="flex items-center justify-between text-sm bg-white rounded p-2 border border-gray-200">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium text-gray-700">${schedule.waktu_mulai} - ${schedule.waktu_selesai}</span>
                </div>
                <span class="text-xs text-gray-500">#${schedule.id_booking}</span>
            </div>
        `;
    });
    
    content.innerHTML = html;
    timeline.classList.remove('hidden');
}

/**
 * Sembunyikan timeline
 */
function hideTimeline() {
    const timeline = document.getElementById('scheduleTimeline');
    if (timeline) {
        timeline.classList.add('hidden');
    }
}

/**
 * Validasi bentrok waktu dengan jadwal yang sudah ada
 */
async function validateTimeConflict(idRuangan, tanggal, waktuMulai, waktuSelesai) {
    if (!tanggal || !waktuMulai || !waktuSelesai) return;
    
    try {
        const response = await fetch(`?page=booking&action=get_booked_timeslots&id_ruangan=${idRuangan}&tanggal=${tanggal}`);
        const data = await response.json();
        
        if (data.success && data.schedules) {
            const conflict = checkTimeOverlap(data.schedules, waktuMulai, waktuSelesai);
            if (conflict) {
                showConflictWarning(`Waktu bentrok dengan booking #${conflict.id_booking} (${conflict.waktu_mulai} - ${conflict.waktu_selesai})`);
            } else {
                hideConflictWarning();
            }
        }
    } catch (error) {
        console.error('Error validating time:', error);
    }
}

/**
 * Cek apakah waktu bentrok dengan jadwal yang ada
 */
function checkTimeOverlap(schedules, waktuMulai, waktuSelesai) {
    for (const schedule of schedules) {
        const start1 = timeToMinutes(schedule.waktu_mulai);
        const end1 = timeToMinutes(schedule.waktu_selesai);
        const start2 = timeToMinutes(waktuMulai);
        const end2 = timeToMinutes(waktuSelesai);
        
        // Cek overlap: (start1 < end2) AND (start2 < end1)
        if (start1 < end2 && start2 < end1) {
            return schedule; // Ada bentrok
        }
    }
    return null; // Tidak ada bentrok
}

/**
 * Convert waktu HH:MM ke menit
 */
function timeToMinutes(time) {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
}

/**
 * Tampilkan warning bentrok
 */
function showConflictWarning(message) {
    const warning = document.getElementById('conflictWarning');
    const messageEl = document.getElementById('conflictMessage');
    
    if (warning && messageEl) {
        messageEl.textContent = message;
        warning.classList.remove('hidden');
    }
}

/**
 * Sembunyikan warning bentrok
 */
function hideConflictWarning() {
    const warning = document.getElementById('conflictWarning');
    if (warning) {
        warning.classList.add('hidden');
    }
}

/**
 * Inisialisasi event listener saat DOM ready
 */
document.addEventListener('DOMContentLoaded', function() {
    const btnTambah = document.getElementById('btn-tambah-anggota');
    const mainForm = document.getElementById('mainForm');
    const tanggalInput = document.getElementById('tanggalInput');
    const waktuMulaiInput = document.getElementById('waktuMulaiInput');
    const waktuSelesaiInput = document.getElementById('waktuSelesaiInput');
    
    // Ambil id_ruangan dari hidden input
    const idRuanganInput = document.querySelector('input[name="id_ruangan"]');
    const idRuangan = idRuanganInput ? idRuanganInput.value : null;
    
    if (btnTambah) {
        btnTambah.addEventListener('click', tambahAnggota);
    }
    
    // Event listener untuk tanggal - load timeline
    if (tanggalInput && idRuangan) {
        tanggalInput.addEventListener('change', function() {
            loadBookedTimeslots(idRuangan, this.value);
            
            // Validasi ulang jika waktu sudah diisi
            const waktuMulai = waktuMulaiInput ? waktuMulaiInput.value : null;
            const waktuSelesai = waktuSelesaiInput ? waktuSelesaiInput.value : null;
            if (waktuMulai && waktuSelesai) {
                validateTimeConflict(idRuangan, this.value, waktuMulai, waktuSelesai);
            }
        });
    }
    
    // Event listener untuk waktu mulai - validasi bentrok
    if (waktuMulaiInput && idRuangan) {
        waktuMulaiInput.addEventListener('change', function() {
            const tanggal = tanggalInput ? tanggalInput.value : null;
            const waktuSelesai = waktuSelesaiInput ? waktuSelesaiInput.value : null;
            if (tanggal && waktuSelesai) {
                validateTimeConflict(idRuangan, tanggal, this.value, waktuSelesai);
            }
        });
    }
    
    // Event listener untuk waktu selesai - validasi bentrok
    if (waktuSelesaiInput && idRuangan) {
        waktuSelesaiInput.addEventListener('change', function() {
            const tanggal = tanggalInput ? tanggalInput.value : null;
            const waktuMulai = waktuMulaiInput ? waktuMulaiInput.value : null;
            if (tanggal && waktuMulai) {
                validateTimeConflict(idRuangan, tanggal, waktuMulai, this.value);
            }
        });
    }
    
    // Validasi form sebelum submit
    if (mainForm) {
        mainForm.addEventListener('submit', function(e) {
            if (!validateBookingForm()) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Update tombol saat halaman dimuat
    updateTambahButton();
});