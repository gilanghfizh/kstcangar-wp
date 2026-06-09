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
    <title>Dashboard – KST Cangar</title>
    <?php wp_head(); ?>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Google Fonts -->
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
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Kembali ke Beranda
        </a>
        <a href="<?php echo get_edit_profile_url(); ?>" class="nav-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?php echo esc_html($current_user->display_name ?: 'admin'); ?>
        </a>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="nav-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</nav>

<!-- DASHBOARD LAYOUT -->
<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <ul>
            <li class="active">
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('title-dashboard'))); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('stok-opname'))); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Stok Opname
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('booklist-atp'))); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Booklist ATP
                </a>
            </li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <h1 class="page-title">Dashboard Overview</h1>
        <p class="subtitle">Ringkasan Stok Opname &amp; Booklist ATP</p>

        <!-- STAT CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon-box blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
                <div class="stat-badge green-badge">+1 item baru</div>
                <span>Total Item Stok</span>
                <h2 id="stat-item-stok">–</h2>
            </div>
            <div class="stat-card">
                <div class="icon-box green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                </div>
                <div class="stat-badge blue-badge">Total unit</div>
                <span>Stok Tersedia</span>
                <h2 id="stat-stok">–</h2>
            </div>
            <div class="stat-card">
                <div class="icon-box teal">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="stat-badge green-badge">Aktif</div>
                <span>Total Booking</span>
                <h2 id="stat-booking">–</h2>
            </div>
            <div class="stat-card">
                <div class="icon-box orange">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div class="stat-badge orange-badge">+8%</div>
                <span>Pendapatan Booking</span>
                <h2 id="stat-pendapatan">–</h2>
            </div>
        </div>

        <!-- CHART GRID -->
        <div class="chart-grid">
            <div class="panel">
                <h3>Stok Barang Tersedia</h3>
                <div class="chart-wrap">
                    <canvas id="chartStok"></canvas>
                </div>
            </div>
            <div class="panel">
                <h3>Tren Booking &amp; Pendapatan</h3>
                <div class="chart-wrap">
                    <canvas id="chartTren"></canvas>
                </div>
            </div>
        </div>

        <!-- BOTTOM GRID -->
        <div class="bottom-grid">
            <!-- Aktivitas Stok Terkini -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Aktivitas Stok Terkini</h3>
                    <a href="#" class="lihat-detail">Lihat Detail →</a>
                </div>
                <ul class="activity-list">
                    <li>
                        <span class="dot green"></span>
                        <div class="act-info">
                            <strong>Kentang Granola</strong>
                            <small>Stok keluar · 1 hari lalu</small>
                        </div>
                        <span class="act-val plus">+200 Kg</span>
                    </li>
                    <li>
                        <span class="dot yellow"></span>
                        <div class="act-info">
                            <strong>Strawberry Fresh</strong>
                            <small>Stok keluar · 1 hari lalu</small>
                        </div>
                        <span class="act-val minus">-80 Kg</span>
                    </li>
                    <li>
                        <span class="dot green"></span>
                        <div class="act-info">
                            <strong>Sayuran Organik</strong>
                            <small>Stok keluar · 2 hari lalu</small>
                        </div>
                        <span class="act-val plus">+150 Kg</span>
                    </li>
                    <li>
                        <span class="dot red"></span>
                        <div class="act-info">
                            <strong>Pupuk Kompos</strong>
                            <small>Retur · 3 hari lalu</small>
                        </div>
                        <span class="act-val minus">-10 Kg</span>
                    </li>
                </ul>
            </div>

            <!-- Booking Terkini -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Booking Terkini</h3>
                    <a href="#" class="lihat-detail">Lihat Detail →</a>
                </div>
                <ul class="booking-list">
                    <li>
                        <div class="avatar">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div class="booking-info">
                            <strong>Ahmad Rizki</strong>
                            <small>Glamping Deluxe · 1-3 Mei 2026</small>
                        </div>
                        <span class="badge lunas">Lunas</span>
                    </li>
                    <li>
                        <div class="avatar">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div class="booking-info">
                            <strong>Siti Nurhaliza</strong>
                            <small>Camping Ground · 5-6 Mei 2026</small>
                        </div>
                        <span class="badge dp">DP</span>
                    </li>
                    <li>
                        <div class="avatar">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div class="booking-info">
                            <strong>Budi Santoso</strong>
                            <small>Glamping Long · 8-10 Mei 2026</small>
                        </div>
                        <span class="badge belum">Belum Lunas</span>
                    </li>
                </ul>
            </div>
        </div>

    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>
