<?php
/*
 * Template Name: Booking List
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booklist ATP – KST Cangar</title>
    <?php wp_head(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="dashboard-page">

<!-- TOP NAVBAR -->
<nav class="top-navbar">
    <div class="brand">
        <div class="brand-logo">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.png"
                 alt="KST Cangar"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <span class="brand-logo-fallback">K</span>
        </div>
        <div>
            <div class="brand-title">KST Cangar</div>
            <div class="brand-sub">STP Universitas Brawijaya</div>
        </div>
    </div>
    <div class="top-actions">
        <a href="<?php echo home_url('/'); ?>" class="nav-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Kembali ke Beranda
        </a>
        <a href="<?php echo get_edit_profile_url(); ?>" class="nav-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?php echo esc_html($current_user->display_name ?: 'admin'); ?>
        </a>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="nav-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</nav>

<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <ul>
            <li>
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('title-dashboard'))); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('stok-opname'))); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    Stok Opname
                </a>
            </li>
            <li class="active">
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('booklist-atp'))); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Booklist ATP
                </a>
            </li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- PAGE HEADER -->
        <div class="so-page-header">
            <div>
                <h1 class="page-title">Booklist ATP</h1>
                <p class="subtitle">Kelola data reservasi pengunjung</p>
            </div>
            <button class="btn-tambah" onclick="showBookingForm('add')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Booking
            </button>
        </div>

        <!-- STAT CARDS -->
        <div class="so-stats-grid">
            <div class="so-stat-card">
                <div class="so-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <span>Total Booking</span>
                <strong id="stat-total-booking">0</strong>
            </div>
            <div class="so-stat-card">
                <div class="so-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <span>Total Pendapatan</span>
                <strong id="stat-pendapatan">Rp 0</strong>
            </div>
            <div class="so-stat-card">
                <div class="so-icon teal">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <span>Lunas</span>
                <strong id="stat-lunas">0</strong>
            </div>
            <div class="so-stat-card">
                <div class="so-icon orange">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <span>Belum Lunas / DP</span>
                <strong id="stat-belum">0</strong>
            </div>
        </div>

        <!-- INLINE FORM (hidden by default) -->
        <div class="so-form-box" id="bookingFormBox" style="display:none;">
            <div class="booking-form-header">
                <h3 id="bookingFormTitle">Form Input Booking</h3>
                <button class="btn-close-form" onclick="hideBookingForm()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <input type="hidden" id="bEditIndex" value="">

            <div class="so-form-grid-2">
                <div class="so-field">
                    <label>Nama Pengunjung <span class="req">*</span></label>
                    <input type="text" id="b-nama" placeholder="Nama lengkap">
                </div>
                <div class="so-field">
                    <label>Jumlah Tamu <span class="req">*</span></label>
                    <input type="number" id="b-tamu" value="1" min="1">
                </div>
            </div>

            <div class="so-form-grid-2">
                <div class="so-field">
                    <label>No WhatsApp <span class="req">*</span></label>
                    <input type="number" id="b-wa" placeholder="08xxxxxxxxxx">
                </div>
                <div class="so-field">
                    <label>Tipe <span class="req">*</span></label>
                    <div class="custom-select-wrap" id="tipeWrap">
                        <div class="custom-select-trigger" onclick="toggleDropdown('tipeDropdown','tipeWrap')">
                            <span id="tipeLabel">Pilih Tipe</span>
                            <svg class="cs-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="custom-select-options" id="tipeDropdown">
                            <label class="cs-option"><input type="radio" name="tipe" value="Glamping Deluxe" onchange="selectTipe(this)"> Glamping Deluxe</label>
                            <label class="cs-option"><input type="radio" name="tipe" value="Glamping Long"   onchange="selectTipe(this)"> Glamping Long</label>
                            <label class="cs-option"><input type="radio" name="tipe" value="Camping Ground"  onchange="selectTipe(this)"> Camping Ground</label>
                        </div>
                    </div>
                    <input type="hidden" id="b-tipe">
                </div>
            </div>

            <div class="so-form-grid-2">
                <div class="so-field">
                    <label>No Unit Glamping</label>
                    <div class="custom-select-wrap" id="unitWrap">
                        <div class="custom-select-trigger" onclick="toggleDropdown('unitDropdown','unitWrap')">
                            <span id="unitLabel">Pilih Unit</span>
                            <svg class="cs-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="custom-select-options" id="unitDropdown"></div>
                    </div>
                    <input type="hidden" id="b-unit">
                </div>
                <div class="so-field">
                    <label>Tanggal Check In <span class="req">*</span></label>
                    <input type="date" id="b-checkin">
                </div>
            </div>

            <div class="so-form-grid-2">
                <div class="so-field">
                    <label>Tanggal Check Out <span class="req">*</span></label>
                    <input type="date" id="b-checkout">
                </div>
                <div class="so-field"><!-- spacer --></div>
            </div>

            <div class="so-field" style="margin-bottom:16px">
                <label>Alamat <span class="req">*</span></label>
                <input type="text" id="b-alamat" placeholder="Alamat lengkap">
            </div>

            <div class="so-form-grid-2">
                <div class="so-field">
                    <label>Harga <span class="req">*</span></label>
                    <input type="number" id="b-harga" value="0" min="0">
                </div>
                <div class="so-field">
                    <label>Status Pembayaran <span class="req">*</span></label>
                    <select id="b-status" class="so-native-select">
                        <option value="Belum Lunas">Belum Lunas</option>
                        <option value="Lunas">Lunas</option>
                        <option value="DP">DP</option>
                    </select>
                </div>
            </div>

            <div class="so-form-grid-2">
                <div class="so-field">
                    <label>Metode Pembayaran</label>
                    <select id="b-metode" class="so-native-select">
                        <option value="Transfer Bank">Transfer Bank</option>
                        <option value="Cash">Cash</option>
                        <option value="E-Wallet">E-Wallet</option>
                        <option value="QRIS">QRIS</option>
                    </select>
                </div>
                <div class="so-field">
                    <label>Bukti Pembayaran</label>
                    <input type="text" id="b-bukti-display" placeholder="Upload File" readonly
                           onclick="document.getElementById('b-bukti-file').click()" style="cursor:pointer">
                    <input type="file" id="b-bukti-file" accept="image/*,.pdf"
                           style="display:none" onchange="handleFileUpload(this)">
                </div>
            </div>

            <div class="so-form-grid-2">
                <div class="so-field">
                    <label>No. Receipt</label>
                    <input type="text" id="b-receipt" placeholder="RCP-XXX-2026">
                </div>
                <div class="so-field">
                    <label>No. Invoice</label>
                    <input type="text" id="b-invoice" placeholder="INV-XXX-2026">
                </div>
            </div>

            <div class="so-field" style="margin-bottom:16px">
                <label>Additional Needs</label>
                <input type="text" id="b-additional"
                       placeholder="Misal: sewa kompor portable, matras tambahan, tenda dome, dll">
            </div>

            <div class="so-form-actions">
                <button class="btn-batal" onclick="hideBookingForm()">Batal</button>
                <button class="btn-simpan" onclick="simpanBooking()">Simpan Booking</button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="so-table-wrap">
            <table class="so-table" id="bookingTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Jumlah Tamu</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Kontak</th>
                        <th>Tipe</th>
                        <th>No Unit</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th>Bukti Bayar</th>
                        <th>Invoice</th>
                        <th>Additional Needs</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="bookingTableBody"></tbody>
            </table>
        </div>

        <div id="buktiModal" class="modal-bukti">
            <div class="modal-bukti-content">

                <span class="modal-close"
                    onclick="tutupBukti()">
                &times;
                </span>

                <iframe
                id="buktiFrame"
                style="
                    width:100%;
                    height:80vh;
                    border:none;
                ">
                </iframe>

            </div>
            </div>

    </main>
</div>

<script>
const unitOptions = {
  "Glamping Deluxe": ["Deluxe 1","Deluxe 2","Deluxe 3","Deluxe 4","Deluxe 5", "Deluxe 6", "Deluxe 7"],
  "Glamping Long":   ["Long Unit 8","Long Unit 9","Long Unit 10"],
};

function toggleDropdown(dropId, wrapId) {
  document.getElementById(wrapId).classList.toggle("open");
}

function selectTipe(radio) {
  document.getElementById("b-tipe").value       = radio.value;
  document.getElementById("tipeLabel").innerText = radio.value;
  buildUnitOptions(radio.value);
}

function buildUnitOptions(tipe) {
  const drop = document.getElementById("unitDropdown");
  if (!drop) return;
  drop.innerHTML = (unitOptions[tipe] || [])
    .map((item) => `
<label class="cs-option">
  <input type="radio" name="unit" value="${item}" onchange="selectUnit(this)">
  ${item}
</label>`)
    .join("");
}

function selectUnit(radio) {
  document.getElementById("b-unit").value       = radio.value;
  document.getElementById("unitLabel").innerText = radio.value;
}

function handleFileUpload(input) {
  if (input.files[0]) {
    document.getElementById("b-bukti-display").value = input.files[0].name;
  }
}

// Tutup dropdown saat klik di luar
document.addEventListener("click", function (e) {
  if (!e.target.closest(".custom-select-wrap")) {
    document.querySelectorAll(".custom-select-wrap.open")
      .forEach((el) => el.classList.remove("open"));
  }
});
</script>

<?php wp_footer(); ?>
</body>
</html>
