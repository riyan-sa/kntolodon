/**
 * kelola-ruangan.js
 * JavaScript untuk halaman Kelola Ruangan - Admin
 * Handle modal toggle, form validation, dan image preview (tanpa AJAX)
 */

// ==================== DATA INITIALIZATION ====================

// Initialize global variables from data attributes
let ROOMS_DATA = [];
let ASSET_BASE_PATH = '';

// Load data when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const dataContainer = document.getElementById('rooms-data');
    if (dataContainer) {
        ROOMS_DATA = JSON.parse(dataContainer.dataset.rooms || '[]');
        ASSET_BASE_PATH = dataContainer.dataset.basePath || '';
        window.ROOMS_DATA = ROOMS_DATA;
        window.ASSET_BASE_PATH = ASSET_BASE_PATH;
    }
    
    initializeEventListeners();
});

// ==================== EVENT LISTENERS ====================

function initializeEventListeners() {
    // Button: Add Room
    const btnAddRoom = document.getElementById('btn-add-room');
    if (btnAddRoom) {
        btnAddRoom.addEventListener('click', openAddModal);
    }
    
    // Buttons: Edit Room (event delegation)
    document.addEventListener('click', function(event) {
        const editBtn = event.target.closest('.btn-edit-room');
        if (editBtn) {
            const roomId = editBtn.dataset.roomId;
            openEditModal(roomId);
        }
        
        const deleteBtn = event.target.closest('.btn-delete-room');
        if (deleteBtn) {
            const roomId = deleteBtn.dataset.roomId;
            openDeleteModal(roomId);
        }
    });
    
    // Buttons: Close modals
    const closeAddBtns = document.querySelectorAll('.btn-close-add-modal');
    closeAddBtns.forEach(btn => btn.addEventListener('click', closeAddModal));
    
    const closeEditBtns = document.querySelectorAll('.btn-close-edit-modal');
    closeEditBtns.forEach(btn => btn.addEventListener('click', closeEditModal));
    
    const closeDeleteBtns = document.querySelectorAll('.btn-close-delete-modal');
    closeDeleteBtns.forEach(btn => btn.addEventListener('click', closeDeleteModal));
    
    // File inputs for preview
    const fileInputAdd = document.querySelector('.input-file-add');
    if (fileInputAdd) {
        fileInputAdd.addEventListener('change', function(event) {
            const previewTarget = event.target.dataset.previewTarget;
            previewImage(event, previewTarget);
        });
    }
    
    const fileInputEdit = document.querySelector('.input-file-edit');
    if (fileInputEdit) {
        fileInputEdit.addEventListener('change', function(event) {
            const previewTarget = event.target.dataset.previewTarget;
            previewImage(event, previewTarget);
        });
    }
    
    // Form validation
    setupFormValidation();
}

// ==================== MODAL MANAGEMENT ====================

/**
 * Open Add Modal
 */
function openAddModal() {
    const modal = document.getElementById('addModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Reset form
    const form = modal.querySelector('form');
    form.reset();
    
    // Hide preview
    document.getElementById('addPreview').classList.add('hidden');
}

/**
 * Close Add Modal
 */
function closeAddModal() {
    const modal = document.getElementById('addModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

/**
 * Open Edit Modal
 * @param {number} roomId - ID ruangan yang akan diedit
 */
function openEditModal(roomId) {
    const room = window.ROOMS_DATA.find(r => r.id_ruangan == roomId);
    
    if (!room) {
        alert('Data ruangan tidak ditemukan!');
        return;
    }
    
    // Populate form fields
    document.getElementById('edit_id_ruangan').value = room.id_ruangan;
    document.getElementById('edit_nama_ruangan').value = room.nama_ruangan;
    document.getElementById('edit_jenis_ruangan').value = room.jenis_ruangan;
    document.getElementById('edit_minimal_kapasitas').value = room.minimal_kapasitas_ruangan;
    document.getElementById('edit_maksimal_kapasitas').value = room.maksimal_kapasitas_ruangan;
    document.getElementById('edit_status_ruangan').value = room.status_ruangan;
    document.getElementById('edit_deskripsi').value = room.deskripsi || '';
    document.getElementById('edit_tata_tertib').value = room.tata_tertib || '';
    
    // Show current photo if exists
    const photoContainer = document.getElementById('edit_current_photo_container');
    const photoImg = document.getElementById('edit_current_photo');
    
    if (room.foto_ruangan) {
        photoContainer.classList.remove('hidden');
        photoImg.src = window.ASSET_BASE_PATH  + room.foto_ruangan;
    } else {
        photoContainer.classList.add('hidden');
    }
    
    // Hide new preview
    document.getElementById('editPreview').classList.add('hidden');
    
    // Show modal
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

/**
 * Close Edit Modal
 */
function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

/**
 * Open Delete Modal
 * @param {number} roomId - ID ruangan yang akan dihapus
 */
function openDeleteModal(roomId) {
    const room = window.ROOMS_DATA.find(r => r.id_ruangan == roomId);
    
    if (!room) {
        alert('Data ruangan tidak ditemukan!');
        return;
    }
    
    // Populate delete modal
    document.getElementById('delete_id_ruangan').value = room.id_ruangan;
    document.getElementById('delete_room_name').textContent = room.nama_ruangan;
    
    // Show modal
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

/**
 * Close Delete Modal
 */
function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// ==================== IMAGE PREVIEW ====================

/**
 * Preview uploaded image
 * @param {Event} event - File input change event
 * @param {string} previewId - ID of preview container
 */
function previewImage(event, previewId) {
    const file = event.target.files[0];
    const previewContainer = document.getElementById(previewId);
    const previewImg = previewContainer.querySelector('img');
    
    if (file) {
        // Validasi ukuran file (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file maksimal 5MB!');
            event.target.value = '';
            previewContainer.classList.add('hidden');
            return;
        }
        
        // Validasi tipe file
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            alert('Hanya file gambar (JPEG, PNG, WEBP) yang diperbolehkan!');
            event.target.value = '';
            previewContainer.classList.add('hidden');
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewContainer.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        previewContainer.classList.add('hidden');
    }
}

// ==================== MODAL CLOSE ON OUTSIDE CLICK ====================

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    // Add Modal
    const addModal = document.getElementById('addModal');
    if (event.target === addModal) {
        closeAddModal();
    }
    
    // Edit Modal
    const editModal = document.getElementById('editModal');
    if (event.target === editModal) {
        closeEditModal();
    }
    
    // Delete Modal
    const deleteModal = document.getElementById('deleteModal');
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAddModal();
        closeEditModal();
        closeDeleteModal();
    }
});

// ==================== FORM VALIDATION ====================

function setupFormValidation() {
    // Validate Add Form
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function(event) {
            const minKapasitas = parseInt(document.querySelector('#addModal input[name="minimal_kapasitas"]').value);
            const maxKapasitas = parseInt(document.querySelector('#addModal input[name="maksimal_kapasitas"]').value);
            
            if (minKapasitas >= maxKapasitas) {
                event.preventDefault();
                alert('Kapasitas minimal harus lebih kecil dari kapasitas maksimal!');
                return false;
            }
        });
    }
    
    // Validate Edit Form
    const editForm = document.querySelector('#editModal form');
    if (editForm) {
        editForm.addEventListener('submit', function(event) {
            const minKapasitas = parseInt(document.getElementById('edit_minimal_kapasitas').value);
            const maxKapasitas = parseInt(document.getElementById('edit_maksimal_kapasitas').value);
            
            if (minKapasitas >= maxKapasitas) {
                event.preventDefault();
                alert('Kapasitas minimal harus lebih kecil dari kapasitas maksimal!');
                return false;
            }
        });
    }
}
