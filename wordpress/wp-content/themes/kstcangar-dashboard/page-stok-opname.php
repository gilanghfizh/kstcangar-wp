<?php


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
    <title>Stok Opname – KST Cangar</title>
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
            <li class="active">
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('stok-opname'))); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    Stok Opname
                </a>
            </li>
            <li>
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
                <h1 class="page-title">Stok Opname</h1>
                <p class="subtitle">Kelola data stok barang mingguan</p>
            </div>
            <button class="btn-tambah" id="btnTambah" onclick="showForm('add')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Data Stok
            </button>
        </div>

        <!-- STAT CARDS -->
        <div class="so-stats-grid">
            <div class="so-stat-card">
                <div class="so-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
                <span>Total Stok Awal</span>
                <strong id="total-awal">0</strong>
            </div>
            <div class="so-stat-card">
                <div class="so-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                </div>
                <span>Total Barang Masuk</span>
                <strong id="total-masuk">0</strong>
            </div>
            <div class="so-stat-card">
                <div class="so-icon orange">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>
                </div>
                <span>Total Barang Keluar</span>
                <strong id="total-keluar">0</strong>
            </div>
            <div class="so-stat-card">
                <div class="so-icon red">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <span>Total Retur</span>
                <strong id="total-retur">0</strong>
            </div>
        </div>

        <!-- INLINE FORM (hidden by default) -->
        <div class="so-form-box" id="soFormBox" style="display:none;">
            <h3 id="formTitle">Form Input Stok Opname</h3>
            <input type="hidden" id="editIndex" value="">

            <div class="so-form-grid-2">
                <div class="so-field">
                    <label>Nama Barang <span class="req">*</span></label>
                    <input type="text" id="f-nama" placeholder="Contoh: Kentang Granola">
                </div>
                <div class="so-field">
                    <label>Satuan <span class="req">*</span></label>
                    <input type="text" id="f-satuan" placeholder="Contoh: Kg, Pcs, Box">
                </div>
            </div>

            <div class="so-form-grid-2">
                <div class="so-field">
                    <label>Stok Awal <span class="req">*</span></label>
                    <input type="number" id="f-stok-awal" value="0" min="0">
                </div>
                <div class="so-field">
                    <label>Tanggal Barang Masuk</label>
                    <input type="date" id="f-tgl-masuk">
                </div>
            </div>

            <!-- Barang Masuk per hari -->
            <div class="so-field">
                <label>Barang Masuk (Per Hari)</label>
                <div class="so-hari-grid">
                    <div class="so-hari-col"><span>S</span><input type="number" id="f-masuk-s1" value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>S</span><input type="number" id="f-masuk-s2" value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>R</span><input type="number" id="f-masuk-r"  value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>K</span><input type="number" id="f-masuk-k"  value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>J</span><input type="number" id="f-masuk-j"  value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>S</span><input type="number" id="f-masuk-s3" value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>M</span><input type="number" id="f-masuk-m"  value="0" min="0" oninput="hitungTotal()"></div>
                </div>
            </div>

            <div class="so-form-grid-2">
                <div class="so-field">
                    <label>Total Barang Masuk</label>
                    <input type="number" id="f-total-masuk" value="0" readonly class="readonly-field">
                </div>
                <div class="so-field">
                    <label>Tanggal Barang Keluar</label>
                    <input type="date" id="f-tgl-keluar">
                </div>
            </div>

            <!-- Barang Keluar per hari -->
            <div class="so-field">
                <label>Barang Keluar (Per Hari)</label>
                <div class="so-hari-grid">
                    <div class="so-hari-col"><span>S</span><input type="number" id="f-keluar-s1" value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>S</span><input type="number" id="f-keluar-s2" value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>R</span><input type="number" id="f-keluar-r"  value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>K</span><input type="number" id="f-keluar-k"  value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>J</span><input type="number" id="f-keluar-j"  value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>S</span><input type="number" id="f-keluar-s3" value="0" min="0" oninput="hitungTotal()"></div>
                    <div class="so-hari-col"><span>M</span><input type="number" id="f-keluar-m"  value="0" min="0" oninput="hitungTotal()"></div>
                </div>
            </div>

            <div class="so-form-grid-3">
                <div class="so-field">
                    <label>Total Barang Keluar</label>
                    <input type="number" id="f-total-keluar" value="0" readonly class="readonly-field">
                </div>
                <div class="so-field">
                    <label>Retur</label>
                    <input type="number" id="f-retur" value="0" min="0">
                </div>
                <div class="so-field">
                    <label>Keterangan Retur</label>
                    <input type="text" id="f-ket-retur" placeholder="Alasan retur">
                </div>
            </div>

            <div class="so-form-grid-2">
                <div class="so-field">
                    <label>Stok Fisik <span class="req">*</span></label>
                    <input type="number" id="f-stok-fisik" value="0" min="0" oninput="hitungTotal()">
                </div>
                <div class="so-field">
                    <label>Keterangan Selisih</label>
                    <input type="text" id="f-ket-selisih" placeholder="Alasan selisih">
                </div>
            </div>

            <div class="so-form-actions">
                <button class="btn-batal" onclick="hideForm()">Batal</button>
                <button class="btn-simpan" onclick="simpanData()">Simpan Data</button>
            </div>
        </div>

        <!-- SEARCH & FILTER -->
        <div class="so-search-row">
            <div class="so-search-wrap">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="searchInput" placeholder="Cari Nama Barang" oninput="filterData()">
            </div>
            <button class="btn-filter">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            </button>
        </div>

        <!-- MONTH & YEAR FILTER -->
        <div class="so-filter-row">
            <div class="so-month-tabs" id="monthTabs">
                <?php
                $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                $currentMonth = date('n');
                foreach ($bulan as $i => $b):
                    $active = ($i + 1 == $currentMonth) ? 'active' : '';
                ?>
                <button class="month-tab <?php echo $active; ?>" data-month="<?php echo $i+1; ?>" onclick="setMonth(this)"><?php echo $b; ?></button>
                <?php endforeach; ?>
            </div>
            <div class="so-year-select">
                <select id="yearSelect" onchange="filterData()">
                    <?php for ($y = 2024; $y <= 2027; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php selected($y, date('Y')); ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>

        <!-- TABLE -->
        <div class="so-table-wrap">
            <table class="so-table" id="soTable">
                <thead>
                    <tr>
                        <th rowspan="2">Nama Barang</th>
                        <th rowspan="2">Stok Awal</th>
                        <th colspan="7" class="group-header">Barang Masuk</th>
                        <th rowspan="2">Total Masuk</th>
                        <th colspan="7" class="group-header">Barang Keluar</th>
                        <th rowspan="2">Total Keluar</th>
                        <th rowspan="2">Retur</th>
                        <th rowspan="2">Ket Retur</th>
                        <th rowspan="2">Satuan</th>
                        <th rowspan="2">Stok Akhir</th>
                        <th rowspan="2">Stok Fisik</th>
                        <th rowspan="2">Selisih</th>
                        <th rowspan="2">Ket Selisih</th>
                        <th rowspan="2">Total</th>
                        <th rowspan="2">Aksi</th>
                    </tr>
                    <tr class="sub-header">
                        <th>S</th><th>S</th><th>R</th><th>K</th><th>J</th><th>S</th><th>M</th>
                        <th>S</th><th>S</th><th>R</th><th>K</th><th>J</th><th>S</th><th>M</th>
                    </tr>
                </thead>
                <tbody id="soTableBody">
                    <!-- Data diisi via JS -->
                </tbody>
            </table>
        </div>

    </main>
</div>


<?php wp_footer(); ?>
</body>
</html>
