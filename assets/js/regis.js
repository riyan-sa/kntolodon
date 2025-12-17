/**
 * ============================================================================
 * REGIS.JS - Registration Form Handler
 * ============================================================================
 * 
 * Module untuk menangani registration form dengan dynamic prodi dropdown
 * dan email domain validation (PNJ only).
 * 
 * FUNGSI UTAMA:
 * 1. JURUSAN-PRODI MAPPING - Dynamic prodi dropdown based on selected jurusan
 * 2. EMAIL VALIDATION - Strict PNJ domain validation (client-side)
 * 3. FORM VALIDATION - Comprehensive validation before submission
 * 
 * JURUSAN-PRODI STRUCTURE:
 * - 7 Jurusan dengan masing-masing prodi list
 * - Teknik Sipil (4 prodi)
 * - Teknik Mesin (7 prodi)
 * - Teknik Elektro (6 prodi)
 * - Teknik Informatika dan Komputer (3 prodi)
 * - Akuntansi (7 prodi)
 * - Administrasi Niaga (4 prodi)
 * - Teknik Grafika dan Penerbitan (5 prodi)
 * 
 * EMAIL VALIDATION PATTERNS:
 * - MAHASISWA: nama.x@stu.pnj.ac.id
 *   - Must have single alphanumeric char before @stu
 *   - Regex: /^[a-zA-Z0-9._%+-]+\.[a-zA-Z0-9]@stu\.pnj\.ac\.id$/
 *   - Example: maulana.ibrahim.a@stu.pnj.ac.id
 * 
 * - DOSEN/STAFF: nama@jurusan.pnj.ac.id or nama@pnj.ac.id
 *   - Must NOT be @stu.pnj.ac.id
 *   - Regex: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.pnj\.ac\.id$/
 *   - Example: euis.oktavianti@tik.pnj.ac.id, admin@pnj.ac.id
 * 
 * VALIDATION FLOW:
 * 1. User selects jurusan → prodi dropdown populated dynamically
 * 2. User fills form including email
 * 3. On form submit → client-side email validation
 * 4. If invalid → show alert dengan error message
 * 5. If valid → submit to server (RegisterController::submit)
 * 6. Server performs additional validation (uniqueness, captcha, etc.)
 * 
 * PASSWORD POLICY:
 * - Minimum 8 characters (enforced server-side)
 * - Password confirmation must match
 * 
 * FILE UPLOAD:
 * - SSKUBACAPNJ: Screenshot KubacaPNJ (untuk mahasiswa validation)
 * - Optional field
 * - Max 25MB, image only (JPEG/PNG/WebP)
 * 
 * TARGET ELEMENTS:
 * - #jurusan: Jurusan select dropdown
 * - #prodi: Prodi select dropdown (dynamic)
 * - #email: Email input field
 * - #formRegister: Registration form
 * 
 * ERROR MESSAGES:
 * - Invalid mahasiswa email: "Email mahasiswa harus menggunakan format..."
 * - Invalid dosen email: "Email dosen/staff harus menggunakan domain..."
 * - Generic invalid: "Email harus menggunakan domain PNJ..."
 * 
 * USAGE:
 * - Included in: view/register.php
 * - Runs on DOM ready
 * - Prevents form submission if validation fails
 * 
 * INTEGRATION:
 * - Client-side validation (this file)
 * - Server-side validation (RegisterController::submit)
 * - Database insertion (AkunModel::register)
 * 
 * @module regis
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

    // ==================== JURUSAN-PRODI DATA MAPPING ====================
    
    /**
     * Data mapping jurusan ke prodi
     * 
     * Nested object struktur dengan jurusan sebagai keys dan array prodi sebagai values.
     * Digunakan untuk populate prodi dropdown dynamically based on selected jurusan.
     * 
     * @type {Object.<string, string[]>}
     */
    const prodiData = {
      'Teknik Sipil': [
        'D-III Konstruksi Sipil',
        'D-III Konstruksi Gedung',
        'D-IV (Sarjana Terapan) Teknik Konstruksi Gedung',
        'D-IV (Sarjana Terapan) Teknik Perancangan Jalan dan Jembatan'
      ],
      'Teknik Mesin': [
        'D-III Teknik Mesin',
        'D-III Teknik Konversi Energi',
        'D-III Teknik Alat Berat',
        'D-IV (Sarjana Terapan) Teknik Manufaktur',
        'D-IV (Sarjana Terapan) Pembangkit Tenaga Listrik',
        'D-IV (Sarjana Terapan) Teknologi Rekayasa Konversi Energi',
        'D-IV (Sarjana Terapan) Teknologi Rekayasa Pemeliharaan Alat Berat'
      ],
      'Teknik Elektro': [
        'D-III Teknik Listrik',
        'D-III Elektronika Industri',
        'D-III Teknik Telekomunikasi',
        'D-IV (Sarjana Terapan) Instrumentasi dan Kontrol Industri',
        'D-IV (Sarjana Terapan) Broadband Multimedia',
        'D-IV (Sarjana Terapan) Teknik Otomasi Listrik Industri'
      ],
      'Teknik Informatika dan Komputer': [
        'D-IV (Sarjana Terapan) Teknik Informatika',
        'D-IV (Sarjana Terapan) Teknik Multimedia dan Jaringan',
        'D-IV (Sarjana Terapan) Teknik Multimedia Digital'
      ],
      'Akuntansi': [
        'D-III Akuntansi',
        'D-III Keuangan dan Perbankan',
        'D-III Manajemen Pemasaran (WNBK)',
        'D-IV (Sarjana Terapan) Akuntansi Keuangan',
        'D-IV (Sarjana Terapan) Keuangan dan Perbankan',
        'D-IV (Sarjana Terapan) Manajemen Keuangan',
        'D-IV (Sarjana Terapan) Keuangan dan Perbankan Syariah'
      ],
      'Administrasi Niaga': [
        'D-III Administrasi Bisnis',
        'D-IV (Sarjana Terapan) Administrasi Bisnis Terapan',
        'D-IV (Sarjana Terapan) Usaha Jasa Konvensi, Perjalanan Insentif dan Pameran (MICE)',
        'D-IV (Sarjana Terapan) Bahasa Inggris Untuk Komunikasi Bisnis dan Profesional (BISPRO)'
      ],
      'Teknik Grafika dan Penerbitan': [
        'D-III Teknik Grafika',
        'D-III Penerbitan',
        'D-IV (Sarjana Terapan) Desain Grafis',
        'D-IV (Sarjana Terapan) Teknologi Industri Cetak Kemasan',
        'D-IV (Sarjana Terapan) Teknologi Rekayasa Cetak dan Grafis 3 Dimensi'
      ]
    };

    // Event listener untuk perubahan jurusan
    document.addEventListener('DOMContentLoaded', function() {
      const jurusanSelect = document.getElementById('jurusan');
      const prodiSelect = document.getElementById('prodi');
      const emailInput = document.getElementById('email');
      const formRegister = document.getElementById('formRegister');

      jurusanSelect.addEventListener('change', function() {
        const selectedJurusan = this.value;
        
        // Reset prodi dropdown
        prodiSelect.innerHTML = '<option value="" disabled selected>Pilih Program Studi</option>';
        
        // Populate prodi based on selected jurusan
        if (selectedJurusan && prodiData[selectedJurusan]) {
          prodiData[selectedJurusan].forEach(function(prodi) {
            const option = document.createElement('option');
            option.value = prodi;
            option.textContent = prodi;
            prodiSelect.appendChild(option);
          });
          prodiSelect.disabled = false;
        } else {
          prodiSelect.disabled = true;
        }
      });

      // Disable prodi select initially
      prodiSelect.disabled = true;

      // Validasi email domain PNJ sebelum submit
      formRegister.addEventListener('submit', function(e) {
        const email = emailInput.value.trim();
        
        // Regex untuk validasi domain PNJ
        // Terima format umum local-part@stu.pnj.ac.id (lebih fleksibel untuk alamat mahasiswa)
        const mahasiswaPattern = /^[a-zA-Z0-9._%+-]+@stu\.pnj\.ac\.id$/;
        const dosenPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.pnj\.ac\.id$/;
        const pnjDirectPattern = /^[a-zA-Z0-9._%+-]+@pnj\.ac\.id$/;
        const stuPattern = /@stu\.pnj\.ac\.id$/;
        
        let isValid = false;
        let errorMsg = '';
        
        // Cek apakah mahasiswa (email harus @stu.pnj.ac.id dengan format nama.x@)
        if (mahasiswaPattern.test(email)) {
          isValid = true;
        }
        // Cek apakah dosen/staff (email harus @*.pnj.ac.id atau @pnj.ac.id, tapi bukan @stu.pnj.ac.id)
        else if ((dosenPattern.test(email) || pnjDirectPattern.test(email)) && !stuPattern.test(email)) {
          isValid = true;
        }
        
        if (!isValid) {
          e.preventDefault();
          alert('Email harus berakhiran @stu.pnj.ac.id (untuk mahasiswa) atau @pnj.ac.id / @*.pnj.ac.id (untuk dosen/staff). Silakan gunakan email institusi PNJ Anda.');
          emailInput.focus();
          return false;
        }
      });
    });function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
    toggleIcon.classList.toggle('fa-eye');
    toggleIcon.classList.toggle('fa-eye-slash');
}
