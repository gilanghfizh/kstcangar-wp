<?php
/*
Template Name: Login
Template Post Type: page
*/

if (is_user_logged_in()) {
    wp_redirect(get_permalink(get_page_by_path('title-dashboard')));
    exit;
}

// Handle form login submit
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kst_login_nonce'])) {
    if (wp_verify_nonce($_POST['kst_login_nonce'], 'kst_login')) {
        $username   = sanitize_text_field($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';
        $rememberme = isset($_POST['rememberme']);

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            $login_error = 'Username atau password salah. Silakan coba lagi.';
        } else {
            wp_set_auth_cookie($user->ID, $rememberme);
            wp_redirect(get_permalink(get_page_by_path('title-dashboard')));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login – KST Cangar</title>
    <?php wp_head(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body.login-page {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #e8f5f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* Hide all WP default elements */
        body.login-page header,
        body.login-page footer,
        body.login-page .site-header,
        body.login-page .site-footer,
        body.login-page #wpadminbar { display: none !important; }

        .login-wrapper {
            display: flex;
            gap: 24px;
            align-items: center;
            width: 100%;
            max-width: 900px;
        }

        /* ── LEFT CARD ── */
        .login-left {
            flex: 1;
            background: white;
            border-radius: 24px;
            padding: 36px 32px;
            box-shadow: 0 4px 32px rgba(0,0,0,.08);
        }

        .brand-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .brand-logo-img {
            width: 42px; height: 42px;
            border-radius: 50%;
            object-fit: cover;
        }

        .brand-logo-circle {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: #059669;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; color: white; font-size: 16px;
            flex-shrink: 0;
        }

        .brand-text-name {
            font-size: 17px;
            font-weight: 800;
            color: #059669;
            line-height: 1.2;
        }

        .brand-text-sub {
            font-size: 12px;
            color: #059669;
            opacity: .8;
        }

        .left-title {
            font-size: 26px;
            font-weight: 800;
            color: #059669;
            margin-bottom: 12px;
            line-height: 1.2;
        }

        .left-desc {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .left-image {
            width: 100%;
            border-radius: 16px;
            object-fit: cover;
            height: 200px;
        }

        .left-image-placeholder {
            width: 100%;
            height: 200px;
            border-radius: 16px;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 50%, #6ee7b7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .left-image-placeholder svg { opacity: .4; }

        /* ── RIGHT CARD ── */
        .login-right {
            flex: 1;
            background: white;
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: 0 4px 32px rgba(0,0,0,.08);
            display: flex;
            flex-direction: column;
        }

        .login-title {
            font-size: 24px;
            font-weight: 800;
            color: #059669;
            margin-bottom: 6px;
        }

        .login-sub {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 28px;
        }

        /* Error alert */
        .login-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        /* Fields */
        .login-field {
            margin-bottom: 18px;
        }

        .login-field label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            color: #9ca3af;
            pointer-events: none;
            display: flex;
        }

        .login-field input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            color: #111827;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: white;
        }

        .login-field input:focus {
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5,150,105,.12);
        }

        .btn-eye {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            display: flex;
            padding: 0;
            transition: color .15s;
        }
        .btn-eye:hover { color: #059669; }

        /* Remember + Forgot */
        .login-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #374151;
            cursor: pointer;
        }

        .remember-label input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: #059669;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 14px;
            font-weight: 600;
            color: #059669;
            text-decoration: none;
        }
        .forgot-link:hover { text-decoration: underline; }

        /* Submit button */
        .btn-masuk {
            width: 100%;
            background: #059669;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background .18s;
            letter-spacing: .2px;
        }
        .btn-masuk:hover { background: #047857; }
        .btn-masuk:active { background: #065f46; }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 12px;
            color: #9ca3af;
        }

        /* Responsive */
        @media (max-width: 700px) {
            .login-wrapper { flex-direction: column; max-width: 420px; }
            .login-left { order: 2; }
            .login-right { order: 1; }
        }
    </style>
</head>
<body class="login-page">

<div class="login-wrapper">

    <!-- LEFT: Brand + Info -->
    <div class="login-left">
        <div class="brand-row">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.png"
                 alt="KST Cangar"
                 class="brand-logo-img"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="brand-logo-circle" style="display:none">K</div>
            <div>
                <div class="brand-text-name">KST Cangar</div>
                <div class="brand-text-sub">STP Universitas Brawijaya</div>
            </div>
        </div>

        <h1 class="left-title">Sistem Informasi<br>Terintegrasi</h1>
        <p class="left-desc">
            Platform analytics komprehensif untuk monitoring riset
            hortikultura dan wisata edukasi berbasis teknologi di kawasan Cangar.
        </p>

        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/cangar.png"
             alt="Cangar"
             class="left-image"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <div class="left-image-placeholder" style="display:none">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
    </div>

    <!-- RIGHT: Login Form -->
    <div class="login-right">
        <h2 class="login-title">Login Admin Dashboard</h2>
        <p class="login-sub">Masukkan kredensial Anda untuk mengakses sistem</p>

        <?php if ($login_error): ?>
        <div class="login-error">
            <?php echo esc_html($login_error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php wp_nonce_field('kst_login', 'kst_login_nonce'); ?>

            <!-- Username -->
            <div class="login-field">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input type="text"
                           id="username"
                           name="username"
                           placeholder="Masukkan username"
                           value="<?php echo esc_attr($_POST['username'] ?? ''); ?>"
                           required
                           autocomplete="username">
                </div>
            </div>

            <!-- Password -->
            <div class="login-field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="Masukkan password"
                           required
                           autocomplete="current-password">
                    <button type="button" class="btn-eye" onclick="togglePassword()" id="eyeBtn" title="Tampilkan password">
                        <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <!-- Remember + Forgot -->
            <div class="login-meta">
                <label class="remember-label">
                    <input type="checkbox" name="rememberme" value="1">
                    Ingat saya
                </label>
                <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-link">Lupa password?</a>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-masuk">Masuk</button>
        </form>

        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> KST Cangar – STP Universitas Brawijaya
        </div>
    </div>

</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}
</script>

<?php wp_footer(); ?>
</body>
</html>
