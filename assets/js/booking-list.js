// booking-list.js - Admin Booking List dengan Modal Check-in

// State
let currentBookingId = null;
let currentBookingData = null;

// Modal elements
const modalCheckin = document.getElementById('modal-checkin');
const btnCloseModal = document.getElementById('btn-close-checkin');
const formCheckinAll = document.getElementById('form-checkin-all');

// Open modal check-in
// Skip modal untuk booking eksternal (tampilkan info saja)
function openCheckinModal(idBooking) {
    currentBookingId = idBooking;
    
    // Fetch booking detail via AJAX
    fetch(`?page=admin&action=get_booking_checkin&id=${idBooking}`)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(result => {
            console.log('AJAX Response:', result);
            
            if (result.success) {
                currentBookingData = result.data;
                
                // Validasi Hari H: Check-in hanya bisa dilakukan pada tanggal booking
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const bookingDate = new Date(result.data.tanggal_schedule);
                bookingDate.setHours(0, 0, 0, 0);
                
                if (today < bookingDate) {
                    const formattedDate = bookingDate.toLocaleDateString('id-ID', { 
                        day: 'numeric', 
                        month: 'long', 
                        year: 'numeric' 
                    });
                    alert(`⚠️ Check-in hanya bisa dilakukan pada Hari H!\n\nTanggal booking: ${formattedDate}\nHari ini: ${today.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}`);
                    return;
                }
                
                // Cek apakah booking eksternal (punya nama_instansi)
                // Note: Check for null and empty string
                if (result.data.nama_instansi && result.data.nama_instansi !== '') {
                    alert(`ℹ️ BOOKING EKSTERNAL\n\nInstansi: ${result.data.nama_instansi}\nRuangan: ${result.data.nama_ruangan}\nTanggal: ${formatDate(result.data.tanggal_schedule)}\nWaktu: ${result.data.waktu_mulai} - ${result.data.waktu_selesai}\n\n✅ Booking eksternal tidak memerlukan check-in karena anggotanya tidak terdaftar di sistem.\n\nGunakan tombol "Selesai" untuk menyelesaikan booking ini.`);
                    return;
                }
                
                // Booking internal: tampilkan modal check-in
                console.log('Rendering modal for booking ID:', result.data.id_booking);
                
                try {
                    renderCheckinModal(result.data);
                    
                    if (modalCheckin) {
                        modalCheckin.style.display = 'flex'; // Force display
                        modalCheckin.classList.remove('hidden');
                        modalCheckin.classList.add('flex');
                        console.log('Modal opened successfully');
                    } else {
                        console.error('Modal element not found!');
                        alert('Error: Modal element tidak ditemukan di DOM');
                    }
                } catch (err) {
                    console.error('Error rendering modal:', err);
                    alert('Error saat render modal: ' + err.message);
                }
            } else {
                console.error('Error message:', result.message);
                alert(result.message || 'Gagal memuat data booking');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Terjadi kesalahan saat memuat data booking: ' + error.message);
        });
}

// Close modal
function closeCheckinModal() {
    modalCheckin.classList.remove('flex');
    modalCheckin.classList.add('hidden');
    currentBookingId = null;
    currentBookingData = null;
}

// Render modal content
function renderCheckinModal(booking) {
    console.log('renderCheckinModal called with:', booking);
    
    const modalContent = document.getElementById('modal-checkin-content');
    
    if (!modalContent) {
        console.error('Modal content element not found!');
        alert('Error: Element modal-checkin-content tidak ditemukan');
        return;
    }
    
    // Validate required data
    if (!booking.anggota || booking.anggota.length === 0) {
        console.warn('Booking has no members');
        modalContent.innerHTML = '<p class="text-center text-red-500">Booking ini tidak memiliki anggota!</p>';
        return;
    }
    
    // Header info
    const statusColor = booking.nama_status === 'AKTIF' ? 'text-green-600' : 
                       booking.nama_status === 'SELESAI' ? 'text-blue-600' : 'text-red-600';
    
    let html = `
        <div class="mb-4 pb-4 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-800 mb-2">${booking.nama_ruangan}</h3>
            <div class="grid grid-cols-2 gap-2 text-sm text-gray-600">
                <div>
                    <p><span class="font-semibold">Kode:</span> ${booking.kode_booking}</p>
                    <p><span class="font-semibold">Peminjam:</span> ${booking.nama_peminjam}</p>
                </div>
                <div>
                    <p><span class="font-semibold">Tanggal:</span> ${formatDate(booking.tanggal_schedule)}</p>
                    <p><span class="font-semibold">Waktu:</span> ${booking.waktu_mulai} - ${booking.waktu_selesai}</p>
                </div>
            </div>
            <p class="mt-2"><span class="font-semibold">Status:</span> <span class="${statusColor} font-bold">${booking.nama_status}</span></p>
            ${booking.alasan_reschedule ? `
                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-xs font-semibold text-yellow-800 mb-1">📋 Alasan Reschedule:</p>
                    <p class="text-sm text-yellow-900">${booking.alasan_reschedule}</p>
                </div>
            ` : ''}
        </div>
        
        <div class="mb-4">
            <div class="flex justify-between items-center mb-3">
                <h4 class="font-semibold text-gray-800">Daftar Anggota</h4>
                ${booking.nama_status === 'AKTIF' ? `
                    <button type="button" onclick="checkinAll()" class="text-xs bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition">
                        Check-in Semua
                    </button>
                ` : ''}
            </div>
            <div class="space-y-2 max-h-64 overflow-y-auto">
    `;
    
    // List anggota
    booking.anggota.forEach(anggota => {
        const isCheckedIn = anggota.is_checked_in == 1;
        const isKetua = anggota.is_ketua == 1;
        const checkInTime = anggota.waktu_check_in ? new Date(anggota.waktu_check_in).toLocaleString('id-ID') : '-';
        
        html += `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border ${isCheckedIn ? 'border-green-200 bg-green-50' : 'border-gray-200'}">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-gray-800">${anggota.username}</p>
                        ${isKetua ? '<span class="text-xs bg-blue-500 text-white px-2 py-0.5 rounded">Ketua</span>' : ''}
                    </div>
                    <p class="text-xs text-gray-500">${anggota.nomor_induk} • ${anggota.role}</p>
                    ${isCheckedIn ? `<p class="text-xs text-green-600 mt-1">✓ Check-in: ${checkInTime}</p>` : ''}
                </div>
                <div>
                    ${isCheckedIn ? `
                        <span class="text-green-600 font-semibold text-sm">✓ Hadir</span>
                    ` : (booking.nama_status === 'AKTIF' ? `
                        <button type="button" onclick="checkinAnggota('${anggota.nomor_induk}')" 
                                class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                            Check-in
                        </button>
                    ` : `
                        <span class="text-gray-400 text-sm">Belum hadir</span>
                    `)}
                </div>
            </div>
        `;
    });
    
    html += `
            </div>
        </div>
    `;
    
    modalContent.innerHTML = html;
}

// Check-in satu anggota
function checkinAnggota(nomorInduk) {
    if (!currentBookingId) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '?page=admin&action=checkin_anggota';
    
    const inputBooking = document.createElement('input');
    inputBooking.type = 'hidden';
    inputBooking.name = 'id_booking';
    inputBooking.value = currentBookingId;
    
    const inputAnggota = document.createElement('input');
    inputAnggota.type = 'hidden';
    inputAnggota.name = 'nomor_induk';
    inputAnggota.value = nomorInduk;
    
    form.appendChild(inputBooking);
    form.appendChild(inputAnggota);
    document.body.appendChild(form);
    form.submit();
}

// Check-in semua anggota
function checkinAll() {
    if (!currentBookingId) return;
    
    if (confirm('Check-in semua anggota sekaligus?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=admin&action=checkin_all';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id_booking';
        input.value = currentBookingId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// Format date helper
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const options = { day: '2-digit', month: 'long', year: 'numeric' };
    return date.toLocaleDateString('id-ID', options);
}

// Event listeners
if (btnCloseModal) {
    btnCloseModal.addEventListener('click', closeCheckinModal);
}

// Close modal when clicking outside
if (modalCheckin) {
    modalCheckin.addEventListener('click', function(e) {
        if (e.target === modalCheckin) {
            closeCheckinModal();
        }
    });
}

// Make row clickable for AKTIF status bookings
document.addEventListener('DOMContentLoaded', function() {
    const bookingRows = document.querySelectorAll('[data-booking-id]');
    bookingRows.forEach(row => {
        const status = row.getAttribute('data-status');
        if (status === 'AKTIF') {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function() {
                const id = this.getAttribute('data-booking-id');
                openCheckinModal(id);
            });
        }
    });
});
