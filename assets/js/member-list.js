// SVG Icons as constants
const SVG_ICONS = {
    user: `<svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
    </svg>`,
    mail: `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
    </svg>`,
    school: `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
    </svg>`,
    calendar: `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>`
};

// State
let currentTab = 'user';
let currentItem = null;
let currentItemType = 'user';

// Event Delegation Setup
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    document.querySelectorAll('[data-member-tab]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const tabName = this.getAttribute('data-tab-name');
            switchTab(tabName);
            return false;
        });
    });
    
    // Auto-submit form on dropdown change (Prodi & Status)
    const filterKelas = document.getElementById('filter-kelas');
    const filterStatus = document.getElementById('filter-status');
    const filterForm = document.querySelector('form[action="index.php"]');
    
    if (filterKelas && filterForm) {
        filterKelas.addEventListener('change', function() {
            filterForm.submit();
        });
    }
    
    if (filterStatus && filterForm) {
        filterStatus.addEventListener('change', function() {
            filterForm.submit();
        });
    }
    
    // Add modal button
    const btnAdd = document.querySelector('[data-modal-action="add"]');
    if (btnAdd) {
        btnAdd.addEventListener('click', openAddModal);
    }
    
    // View/Edit member actions via event delegation on parent
    document.addEventListener('click', function(e) {
        const viewTarget = e.target.closest('[data-member-view]');
        if (viewTarget) {
            const memberData = viewTarget.getAttribute('data-member-data');
            const memberType = viewTarget.getAttribute('data-member-type');
            try {
                const data = JSON.parse(memberData);
                openModal(data, memberType);
            } catch(err) {
                console.error('Error parsing member data:', err);
            }
        }
    });
});

// --- Core Functions ---

function switchTab(tab) {
    currentTab = tab;

    const btnUser = document.getElementById('btn-tab-user');
    const btnAdmin = document.getElementById('btn-tab-admin');
    const filterKelas = document.getElementById('filter-kelas-container');
    const listUser = document.getElementById('list-container-user');
    const listAdmin = document.getElementById('list-container-admin');
    const fabContainer = document.getElementById('fab-container');
    const paginationAdmin = document.getElementById('pagination-admin');
    const tabInput = document.getElementById('current-tab-input');
    const searchNama = document.getElementById('search-nama');
    const searchNomorInduk = document.getElementById('search-nomor-induk');
    const labelNomorInduk = document.getElementById('label-nomor-induk');
    const filterStatus = document.getElementById('filter-status');

    if (tab === 'user') {
        if (btnUser) btnUser.className = "px-12 py-2 rounded-full font-semibold shadow-sm transition-all tab-active cursor-default";
        if (btnAdmin) btnAdmin.className = "px-12 py-2 rounded-full font-semibold shadow-sm transition-all tab-inactive hover:bg-gray-100";

        if (filterKelas) filterKelas.style.display = "flex";
        if (labelNomorInduk) labelNomorInduk.textContent = "Nomor Induk :";

        if (listUser) listUser.classList.remove('hidden');
        if (listAdmin) listAdmin.classList.add('hidden');
        
        // Hide admin pagination, show user pagination (pagination container not hidden, part of list)
        if (paginationAdmin) {
            paginationAdmin.classList.add('hidden');
            paginationAdmin.classList.remove('flex');
        }
        
        if (fabContainer) fabContainer.classList.add('hidden'); // Hide Add Button on User Tab
        
        // Update form field names for user tab
        if (searchNama) searchNama.setAttribute('name', 'nama');
        if (searchNomorInduk) searchNomorInduk.setAttribute('name', 'nomor_induk');
        if (filterStatus) filterStatus.setAttribute('name', 'status');
        if (tabInput) tabInput.value = 'user';
    } else {
        if (btnUser) btnUser.className = "px-12 py-2 rounded-full font-semibold shadow-sm transition-all tab-inactive hover:bg-gray-100";
        if (btnAdmin) btnAdmin.className = "px-12 py-2 rounded-full font-semibold shadow-sm transition-all tab-active cursor-default";

        if (filterKelas) filterKelas.style.display = "none";
        if (labelNomorInduk) labelNomorInduk.textContent = "NIP :";

        if (listUser) listUser.classList.add('hidden');
        if (listAdmin) listAdmin.classList.remove('hidden');
        
        // Show admin pagination if exists
        if (paginationAdmin) {
            paginationAdmin.classList.remove('hidden');
            paginationAdmin.classList.add('flex');
        }
        
        if (fabContainer) fabContainer.classList.remove('hidden'); // Show Add Button on Admin Tab (if Super Admin)
        
        // Update form field names for admin tab
        if (searchNama) searchNama.setAttribute('name', 'nama_admin');
        if (searchNomorInduk) searchNomorInduk.setAttribute('name', 'nomor_induk_admin');
        if (filterStatus) filterStatus.setAttribute('name', 'status_admin');
        if (tabInput) tabInput.value = 'admin';
    }
}

// --- Modal Logic ---

const modal = document.getElementById('memberModal');
const modalContent = document.getElementById('modal-content-container');

function openModal(item, type, mode = 'view') {
    currentItem = item;
    currentItemType = type;
    renderModalContent(mode);

    // Show Modal
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.remove('hidden-modal');
        modal.classList.add('visible-modal');
    }, 10);
}

function openAddModal() {
    currentItem = null; // No item selected
    currentItemType = 'admin'; // Set type to admin for add mode
    renderModalContent('add');
    
    // Show Modal
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.remove('hidden-modal');
        modal.classList.add('visible-modal');
    }, 10);
}

function renderModalContent(mode) {
    let html = '';

    if (mode === 'add') {
        // --- ADD MODE (Admin) ---
        html = `
            <form method="POST" action="index.php?page=admin&action=create_admin" class="w-full h-full flex flex-col items-center pt-2" onsubmit="return validateAddForm()">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 mt-3">Tambah Akun Admin</h2>

                <!-- Form Inputs Container -->
                <div class="w-full space-y-4 px-2 mb-8 text-left">
                    
                    <!-- Nama -->
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-[#1D74BD] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input type="text" name="nama" id="new-name" required class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:border-[#1D74BD] focus:ring-1 focus:ring-[#1D74BD] sm:text-sm transition-all" placeholder="Nama Lengkap">
                    </div>

                    <!-- NIP -->
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-[#1D74BD] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                        </div>
                        <input type="text" name="nip" id="new-nip" required class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:border-[#1D74BD] focus:ring-1 focus:ring-[#1D74BD] sm:text-sm transition-all" placeholder="NIP">
                    </div>

                    <!-- Email -->
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-[#1D74BD] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <input type="email" name="email" id="new-email" required class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:border-[#1D74BD] focus:ring-1 focus:ring-[#1D74BD] sm:text-sm transition-all" placeholder="Email (contoh@pnj.ac.id)">
                    </div>

                    <!-- Password -->
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-focus-within:text-[#1D74BD] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" name="password" id="new-password" required minlength="8" class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:border-[#1D74BD] focus:ring-1 focus:ring-[#1D74BD] sm:text-sm transition-all" placeholder="Password (min. 8 karakter)">
                    </div>

                </div>

                <!-- Button -->
                <button type="submit" class="w-full bg-[#1D74BD] hover:bg-sky-700 text-white font-bold py-3 px-4 rounded-lg shadow-md transition-transform transform active:scale-[0.98]">
                    Simpan
                </button>
            </form>
        `;
    }
    else if (mode === 'view') {
        // Safely access item properties only in view mode
        const item = currentItem;
        if (!item) {
            console.error('No item selected for view mode');
            return;
        }
        
        const idLabel = currentItemType === 'user' ? 'Nomor Induk' : 'NIP';
        const idValue = item.nomor_induk;
        const extraValue = currentItemType === 'user' ? (item.prodi || '-') : "Administrator";

        // Foto profil URL
        const fotoProfilUrl = item.foto_profil ? window.ASSET_BASE_PATH + item.foto_profil : null;
        
        html = `
            <!-- Big Icon/Photo -->
            <div class="relative group mb-6 mx-auto">
                <div class="w-32 h-32 rounded-full border-4 border-[#1D74BD] flex items-center justify-center overflow-hidden ${fotoProfilUrl ? 'bg-white' : 'bg-white text-[#1D74BD]'}">
                    ${fotoProfilUrl ? 
                        `<img src="${fotoProfilUrl}" alt="Foto Profil" class="w-full h-full object-cover">` : 
                        SVG_ICONS.user
                    }
                </div>
                <!-- Upload button overlay -->
                <button type="button" onclick="openUploadFotoModal('${item.nomor_induk}')" class="absolute bottom-0 right-0 bg-[#1D74BD] hover:bg-sky-700 text-white p-2 rounded-full shadow-lg transition-all opacity-0 group-hover:opacity-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
            </div>
            
            <h2 class="text-xl font-bold text-gray-800 mb-1">${item.username}</h2>
            <p class="text-gray-500 font-medium mb-1">${idValue}</p>
            <p class="text-gray-500 text-sm mb-4">${extraValue}</p>

            <div class="space-y-2 text-sm text-gray-600 mb-6 w-full">
                <div class="flex items-center justify-center gap-2">
                    ${SVG_ICONS.mail}
                    <span>${item.email}</span>
                </div>
                <div class="flex items-center justify-center gap-2">
                    ${SVG_ICONS.school}
                    <span>Politeknik Negeri Jakarta</span>
                </div>
                <div class="flex items-center justify-center gap-2">
                    ${SVG_ICONS.calendar}
                    <span>Gabung Sejak 2025</span>
                </div>
            </div>

            <div class="mb-6">
                <span class="text-gray-500">Status: </span>
                <span class="font-bold ${item.status === 'Aktif' ? 'text-gray-800' : 'text-red-600'}">${item.status}</span>
            </div>

            <div class="flex gap-4 w-full justify-center">
                ${getButtonsForView(item)}
            </div>
        `;
    } else if (mode === 'edit') {
        const item = currentItem;
        if (!item) {
            console.error('No item selected for edit mode');
            return;
        }
        
        const idLabel = currentItemType === 'user' ? 'Nomor Induk' : 'NIP';
        const idValue = item.nomor_induk;
        const extraValue = currentItemType === 'user' ? (item.prodi || '-') : "Administrator";
        
        // Foto profil URL
        const fotoProfilUrl = item.foto_profil ? window.ASSET_BASE_PATH + '/' + item.foto_profil : null;
        
        // --- EDIT MODE (Admin Only) ---
        html = `
            <form method="POST" action="index.php?page=admin&action=update_admin" class="w-full" onsubmit="return validateEditForm()">
                <input type="hidden" name="nomor_induk" value="${item.nomor_induk}">
                
                <!-- Big Icon/Photo -->
                <div class="relative group mb-6 mx-auto">
                    <div class="w-32 h-32 rounded-full border-4 border-[#1D74BD] flex items-center justify-center overflow-hidden ${fotoProfilUrl ? 'bg-white' : 'bg-white text-[#1D74BD]'}">
                        ${fotoProfilUrl ? 
                            `<img src="${fotoProfilUrl}" alt="Foto Profil" class="w-full h-full object-cover">` : 
                            SVG_ICONS.user
                        }
                    </div>
                    <!-- Upload button overlay -->
                    <button type="button" onclick="openUploadFotoModal('${item.nomor_induk}')" class="absolute bottom-0 right-0 bg-[#1D74BD] hover:bg-sky-700 text-white p-2 rounded-full shadow-lg transition-all opacity-0 group-hover:opacity-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
                
                <!-- Edit Name -->
                <input type="text" name="nama" id="edit-name" value="${item.username}" required class="text-xl font-bold text-gray-800 mb-2 edit-input text-center w-full" placeholder="Nama Admin">
                
                <!-- Static NIP -->
                <p class="text-gray-500 font-medium mb-2 text-center">${idValue}</p>
                
                <!-- Static Extra -->
                <p class="text-gray-500 text-sm mb-4 text-center">${extraValue}</p>

                <div class="space-y-4 text-sm text-gray-600 mb-6 w-full px-4">
                    <div class="flex items-center gap-2">
                        ${SVG_ICONS.mail}
                        <input type="email" name="email" id="edit-email" value="${item.email}" required class="edit-input text-left flex-1" placeholder="Email">
                    </div>
                    <div class="flex items-center justify-center gap-2 opacity-60">
                        ${SVG_ICONS.school}
                        <span>Politeknik Negeri Jakarta</span>
                    </div>
                    <div class="flex items-center justify-center gap-2 opacity-60">
                        ${SVG_ICONS.calendar}
                        <span>Gabung Sejak 2025</span>
                    </div>
                </div>

                <!-- Edit Status -->
                <div class="mb-6 flex items-center justify-center gap-2">
                    <span class="text-gray-500">Status: </span>
                    <select name="status" id="edit-status" class="border border-gray-300 rounded px-2 py-1 text-sm bg-white">
                        <option value="Aktif" ${item.status === 'Aktif' ? 'selected' : ''}>Aktif</option>
                        <option value="Tidak Aktif" ${item.status === 'Tidak Aktif' ? 'selected' : ''}>Tidak Aktif</option>
                    </select>
                </div>

                <div class="flex gap-4 w-full justify-center">
                    <button type="button" onclick="renderModalContent('view')" class="bg-[#D32F2F] hover:bg-red-700 text-white font-medium py-2 px-8 rounded shadow-md transition-colors flex-1">
                        Batal
                    </button>
                    <button type="submit" class="bg-[#689F38] hover:bg-green-700 text-white font-medium py-2 px-8 rounded shadow-md transition-colors flex-1">
                        Simpan
                    </button>
                </div>
            </form>
        `;
    } else if (mode === 'delete_confirm') {
        const item = currentItem;
        if (!item) {
            console.error('No item selected for delete confirm mode');
            return;
        }
        
        const idValue = item.nomor_induk;
        const actionUrl = currentItemType === 'user' ? 'delete_user' : 'delete_admin';
        
        // --- DELETE CONFIRM MODE ---
        html = `
            <form method="POST" action="index.php?page=admin&action=${actionUrl}" class="w-full">
                <input type="hidden" name="nomor_induk" value="${item.nomor_induk}">
                
                <!-- Big Icon -->
                <div class="w-32 h-32 rounded-full border-4 border-[#1D74BD] flex items-center justify-center mb-6 text-[#1D74BD] mx-auto">
                    ${SVG_ICONS.user}
                </div>
                
                <h2 class="text-xl font-bold text-gray-800 mb-1 text-center">${item.username}</h2>
                <p class="text-gray-500 font-medium mb-1 text-center">${idValue}</p>
                
                <div class="mt-4 mb-8 text-center">
                    <p class="text-lg font-bold text-gray-800 underline decoration-red-500 decoration-2 underline-offset-4">Yakin Ingin Hapus?</p>
                </div>

                <div class="flex gap-4 w-full justify-center">
                    <button type="submit" class="bg-[#1976D2] hover:bg-blue-700 text-white font-medium py-2 px-8 rounded shadow-md transition-colors flex-1">
                        Ya
                    </button>
                    <button type="button" onclick="renderModalContent('view')" class="bg-[#D32F2F] hover:bg-red-700 text-white font-medium py-2 px-8 rounded shadow-md transition-colors flex-1">
                        Tidak
                    </button>
                </div>
            </form>
        `;
    }

    modalContent.innerHTML = html;
}

function getButtonsForView(item) {
    if (currentItemType === 'user') {
        if (item.status === 'Aktif') {
            return `
                <button onclick="handleAction('nonaktifkan')" class="bg-[#D32F2F] hover:bg-red-700 text-white font-medium py-2 px-8 rounded shadow-md transition-colors w-full">
                    Nonaktifkan
                </button>
            `;
        } else {
            return `
                <button onclick="handleAction('hapus')" class="bg-[#D32F2F] hover:bg-red-700 text-white font-medium py-2 px-6 rounded shadow-md transition-colors flex-1">
                    Hapus Akun
                </button>
                <button onclick="handleAction('aktifkan')" class="bg-[#689F38] hover:bg-green-700 text-white font-medium py-2 px-6 rounded shadow-md transition-colors flex-1">
                    Aktifkan
                </button>
            `;
        }
    } else {
        // Admin Tab Buttons - Only Super Admin can edit/delete
        if (window.USER_ROLE === 'Super Admin') {
            return `
                <button onclick="renderModalContent('edit')" class="bg-[#1976D2] hover:bg-blue-700 text-white font-medium py-2 px-8 rounded shadow-md transition-colors flex-1">
                    Ubah
                </button>
                <button onclick="renderModalContent('delete_confirm')" class="bg-[#D32F2F] hover:bg-red-700 text-white font-medium py-2 px-8 rounded shadow-md transition-colors flex-1">
                    Hapus
                </button>
            `;
        } else {
            // Admin can only view
            return `
                <p class="text-gray-500 text-sm italic">Hanya Super Admin yang dapat mengubah data admin</p>
            `;
        }
    }
}

function closeModal() {
    modal.classList.remove('visible-modal');
    modal.classList.add('hidden-modal');
    setTimeout(() => {
        modal.style.display = 'none';
        currentItem = null;
    }, 200);
}

// --- Handle Actions ---

function handleAction(action) {
    const item = currentItem;
    if (!item) return;

    // Create form untuk submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    const inputNomorInduk = document.createElement('input');
    inputNomorInduk.type = 'hidden';
    inputNomorInduk.name = 'nomor_induk';
    inputNomorInduk.value = item.nomor_induk;
    form.appendChild(inputNomorInduk);

    if (action === 'aktifkan') {
        form.action = 'index.php?page=admin&action=update_user_status';
        const inputStatus = document.createElement('input');
        inputStatus.type = 'hidden';
        inputStatus.name = 'status';
        inputStatus.value = 'Aktif';
        form.appendChild(inputStatus);
    } else if (action === 'nonaktifkan') {
        form.action = 'index.php?page=admin&action=update_user_status';
        const inputStatus = document.createElement('input');
        inputStatus.type = 'hidden';
        inputStatus.name = 'status';
        inputStatus.value = 'Tidak Aktif';
        form.appendChild(inputStatus);
    } else if (action === 'hapus') {
        if (!confirm(`Yakin ingin menghapus user ${item.username}?`)) {
            return;
        }
        form.action = 'index.php?page=admin&action=delete_user';
    }

    document.body.appendChild(form);
    form.submit();
}

// Validation Functions
function validateAddForm() {
    const password = document.getElementById('new-password').value;
    const email = document.getElementById('new-email').value;
    
    // Validasi email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Format email tidak valid!');
        return false;
    }
    
    // Validasi domain email PNJ untuk admin/dosen (tidak boleh @stu.pnj.ac.id)
    const dosenPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.pnj\.ac\.id$/;
    const pnjDirectPattern = /^[a-zA-Z0-9._%+-]+@pnj\.ac\.id$/;
    const stuPattern = /@stu\.pnj\.ac\.id$/;
    
    if ((!dosenPattern.test(email) && !pnjDirectPattern.test(email)) || stuPattern.test(email)) {
        alert('Email admin harus menggunakan domain PNJ untuk dosen/staff (contoh: namaf.namal@tik.pnj.ac.id atau admin@pnj.ac.id), bukan email mahasiswa!');
        return false;
    }
    
    // Validasi password minimal 8 karakter
    if (password.length < 8) {
        alert('Password minimal 8 karakter!');
        return false;
    }
    
    return true;
}

function validateEditForm() {
    const email = document.getElementById('edit-email').value;
    
    // Validasi email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Format email tidak valid!');
        return false;
    }
    
    // Validasi domain email PNJ
    // Terima format umum local-part@stu.pnj.ac.id untuk mahasiswa
    const mahasiswaPattern = /^[a-zA-Z0-9._%+-]+@stu\.pnj\.ac\.id$/;
    const dosenPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.pnj\.ac\.id$/;
    const pnjDirectPattern = /^[a-zA-Z0-9._%+-]+@pnj\.ac\.id$/;
    const stuPattern = /@stu\.pnj\.ac\.id$/;
    
    let isValid = false;
    
    // Cek apakah mahasiswa (email harus @stu.pnj.ac.id dengan format nama.x@)
    if (mahasiswaPattern.test(email)) {
        isValid = true;
    }
    // Cek apakah dosen/staff (email harus @*.pnj.ac.id atau @pnj.ac.id, tapi bukan @stu.pnj.ac.id)
    else if ((dosenPattern.test(email) || pnjDirectPattern.test(email)) && !stuPattern.test(email)) {
        isValid = true;
    }
    
    if (!isValid) {
        alert('Email harus berakhiran @stu.pnj.ac.id (untuk mahasiswa) atau @pnj.ac.id / @*.pnj.ac.id (untuk dosen/staff). Silakan gunakan email institusi PNJ Anda.');
        return false;
    }
    
    return true;
}

// --- Filter Functionality (Now using server-side filtering via form submission) ---
// Client-side filtering removed - filters now handled by server

// --- Upload Foto Profil Modal ---
function openUploadFotoModal(nomorInduk) {
    const modal = document.getElementById('memberModal');
    const contentContainer = document.getElementById('modal-content-container');
    
    contentContainer.innerHTML = `
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Upload Foto Profil</h2>
        
        <form method="POST" action="index.php?page=admin&action=update_foto_profil" enctype="multipart/form-data" class="w-full" onsubmit="return validateUploadFoto()">
            <input type="hidden" name="nomor_induk" value="${nomorInduk}">
            
            <!-- Preview Container -->
            <div class="mb-6 flex justify-center">
                <div class="w-40 h-40 rounded-full border-4 border-[#1D74BD] overflow-hidden bg-gray-100 flex items-center justify-center">
                    <img id="foto-preview" src="" alt="Preview" class="w-full h-full object-cover hidden">
                    <svg id="foto-placeholder" xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
            
            <!-- File Input -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Foto</label>
                <input type="file" name="foto_profil" id="foto_profil" accept="image/jpeg,image/png,image/webp" required 
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#1D74BD] file:text-white hover:file:bg-sky-700 cursor-pointer"
                    onchange="previewFotoProfile(event)">
                <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, WebP (Max 25MB)</p>
            </div>
            
            <!-- Buttons -->
            <div class="flex gap-4 w-full">
                <button type="button" onclick="closeModal()" class="bg-gray-400 hover:bg-gray-500 text-white font-medium py-2 px-8 rounded shadow-md transition-colors flex-1">
                    Batal
                </button>
                <button type="submit" class="bg-[#1D74BD] hover:bg-sky-700 text-white font-medium py-2 px-8 rounded shadow-md transition-colors flex-1">
                    Upload
                </button>
            </div>
        </form>
    `;
    
    modal.classList.remove('hidden-modal');
    modal.style.display = 'flex';
}

function previewFotoProfile(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('foto-preview');
    const placeholder = document.getElementById('foto-placeholder');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }
}

function validateUploadFoto() {
    const fileInput = document.getElementById('foto_profil');
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Pilih foto terlebih dahulu!');
        return false;
    }
    
    // Validasi tipe file
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('Hanya file gambar (JPEG, PNG, WebP) yang diperbolehkan!');
        return false;
    }
    
    // Validasi ukuran file (max 25MB)
    if (file.size > 25 * 1024 * 1024) {
        alert('Ukuran file maksimal 25MB!');
        return false;
    }
    
    return true;
}

// Auto-hide flash messages after 5 seconds
setTimeout(() => {
    const flashMessages = document.querySelectorAll('.animate-fade-in');
    flashMessages.forEach(msg => {
        msg.style.opacity = '0';
        msg.style.transition = 'opacity 0.5s';
        setTimeout(() => msg.remove(), 500);
    });
}, 5000);

// Close modal when clicking outside or close button
modal.addEventListener('click', function (e) {
    if (e.target === modal) {
        closeModal();
    }
});

// Close button event listener
const btnCloseModal = document.getElementById('btn-close-modal');
if (btnCloseModal) {
    btnCloseModal.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closeModal();
    });
}

// Initialize view
switchTab('user');
