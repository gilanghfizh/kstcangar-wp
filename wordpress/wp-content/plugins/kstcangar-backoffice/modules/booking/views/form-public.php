<?php defined('ABSPATH') || exit; ?>
<!-- form-public.php: Form booking untuk pengunjung publik -->

<div style="max-width:600px; margin:0 auto; font-family:sans-serif;">

    <?php if ($success): ?>
    <div style="background:#d4edda; color:#155724; padding:16px; border-radius:8px; margin-bottom:24px; border:1px solid #c3e6cb;">
        <strong>✅ Booking berhasil dikirim!</strong><br>
        Booking Anda sedang dalam proses konfirmasi. Tim kami akan menghubungi Anda segera.
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div style="background:#f8d7da; color:#721c24; padding:16px; border-radius:8px; margin-bottom:24px; border:1px solid #f5c6cb;">
        <strong>❌ Gagal mengirim booking.</strong> Pastikan semua field wajib sudah diisi dengan benar.
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['booking']) && $_GET['booking'] === 'full'): ?>
    <div style="background:#fff3cd; color:#856404; padding:16px; border-radius:8px; margin-bottom:24px; border:1px solid #ffc107;">
        <strong>⚠️ Kapasitas penuh!</strong> Layanan yang Anda pilih sudah penuh untuk tanggal tersebut.
        Silakan pilih tanggal atau jenis layanan yang lain.
    </div>
    <?php endif; ?>

    <h2 style="margin-top:0;">Form Booking KST Cangar</h2>
    <p style="color:#666;">Isi formulir di bawah untuk melakukan pemesanan. Tim kami akan menghubungi Anda untuk konfirmasi.</p>

    <form method="POST" action="<?= admin_url('admin-post.php') ?>">
        <input type="hidden" name="action" value="kstcangar_public_booking">
        <?php wp_nonce_field('kstcangar_public_booking', 'kstcangar_nonce'); ?>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Nama Lengkap *</label>
            <input type="text" name="customer_name" required
                   style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px; box-sizing:border-box;"
                   placeholder="Masukkan nama lengkap Anda">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">No. HP / WhatsApp *</label>
            <input type="text" name="customer_phone" required
                   style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px; box-sizing:border-box;"
                   placeholder="08xxxxxxxxxx">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Jenis Layanan *</label>
            <select name="service_type" required
                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px;">
                <option value="">-- Pilih Layanan --</option>
                <option value="glamping">🏕️ Glamping (maks. 10 orang/hari)</option>
                <option value="cafe">☕ Café Eduwisata (maks. 50 orang/hari)</option>
                <option value="camping">⛺ Camping Ground (maks. 20 orang/hari)</option>
            </select>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Tanggal Kunjungan *</label>
            <input type="date" name="date" required min="<?= date('Y-m-d') ?>"
                   style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px; box-sizing:border-box;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Jumlah Orang *</label>
            <input type="number" name="quantity" value="1" min="1" max="50" required
                   style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px; box-sizing:border-box;">
        </div>

        <div style="margin-bottom:24px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Catatan / Permintaan Khusus</label>
            <textarea name="notes" rows="4"
                      style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px; box-sizing:border-box;"
                      placeholder="Permintaan khusus, kebutuhan tertentu, dll..."></textarea>
        </div>

        <button type="submit"
                style="width:100%; padding:14px; background:#2d6a4f; color:#fff; border:none;
                       border-radius:4px; font-size:16px; font-weight:bold; cursor:pointer;">
            Kirim Booking
        </button>

        <p style="font-size:12px; color:#999; text-align:center; margin-top:12px;">
            Booking akan berstatus <em>pending</em> hingga dikonfirmasi oleh tim kami.
        </p>
    </form>
</div>
