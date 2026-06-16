<?php defined('ABSPATH') || exit; ?>

<?php if (!empty($_GET['success'])): ?>
    <div class="notice notice-success is-dismissible"><p>✅ Data booking berhasil disimpan.</p></div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <div class="notice notice-error is-dismissible"><p>❌ Terjadi kesalahan. Periksa kembali data yang diisi.</p></div>
<?php endif; ?>

<!-- Filter -->
<form method="GET" style="background:#fff; padding:16px; border:1px solid #ddd; border-radius:4px; margin-bottom:16px; display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <input type="hidden" name="page" value="kstcangar-booking">
    <input type="hidden" name="tab" value="list">

    <div>
        <label style="display:block; font-weight:600; margin-bottom:4px;">Status</label>
        <select name="filter_status" class="regular-text">
            <option value="">Semua Status</option>
            <option value="pending"   <?= $filter_status === 'pending'    ? 'selected' : '' ?>>Pending</option>
            <option value="confirmed" <?= $filter_status === 'confirmed'  ? 'selected' : '' ?>>Confirmed</option>
            <option value="cancelled" <?= $filter_status === 'cancelled'  ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </div>

    <div>
        <label style="display:block; font-weight:600; margin-bottom:4px;">Layanan</label>
        <select name="filter_service" class="regular-text">
            <option value="">Semua Layanan</option>
            <option value="glamping" <?= $filter_service === 'glamping' ? 'selected' : '' ?>>Glamping</option>
            <option value="cafe"     <?= $filter_service === 'cafe'     ? 'selected' : '' ?>>Café Eduwisata</option>
            <option value="camping"  <?= $filter_service === 'camping'  ? 'selected' : '' ?>>Camping Ground</option>
        </select>
    </div>

    <div>
        <label style="display:block; font-weight:600; margin-bottom:4px;">Tanggal</label>
        <input type="date" name="filter_date" value="<?= esc_attr($filter_date) ?>" class="regular-text">
    </div>

    <button type="submit" class="button">🔍 Filter</button>
    <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-booking','tab'=>'list'], admin_url('admin.php'))) ?>" class="button">Reset</a>

    <div style="margin-left:auto;">
        <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-booking','tab'=>'form'], admin_url('admin.php'))) ?>"
           class="button button-primary">+ Tambah Booking</a>
    </div>
</form>

<!-- Ringkasan cepat -->
<?php $stats = KSTCangar_Booking::get_stats(); ?>
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px;">
    <div style="background:#fff3cd; padding:14px 16px; border-radius:4px; border-left:4px solid #f0ad4e;">
        <div style="font-size:24px; font-weight:bold;"><?= $stats['pending'] ?></div>
        <div style="color:#666; font-size:13px;">Menunggu Konfirmasi</div>
    </div>
    <div style="background:#d4edda; padding:14px 16px; border-radius:4px; border-left:4px solid #5cb85c;">
        <div style="font-size:24px; font-weight:bold;"><?= $stats['confirmed_month'] ?></div>
        <div style="color:#666; font-size:13px;">Confirmed Bulan Ini</div>
    </div>
    <div style="background:#d1ecf1; padding:14px 16px; border-radius:4px; border-left:4px solid #17a2b8;">
        <div style="font-size:24px; font-weight:bold;"><?= $stats['total_today'] ?></div>
        <div style="color:#666; font-size:13px;">Booking Hari Ini</div>
    </div>
</div>

<?php if (empty($bookings)): ?>
    <div style="text-align:center; padding:40px; background:#fff; border:1px solid #ddd; border-radius:4px;">
        <p style="color:#666;">Belum ada data booking.</p>
        <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-booking','tab'=>'form'], admin_url('admin.php'))) ?>"
           class="button button-primary">+ Tambah Booking</a>
    </div>
<?php else: ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th style="width:50px;">ID</th>
            <th>Nama Customer</th>
            <th>No. HP</th>
            <th>Layanan</th>
            <th>Tanggal</th>
            <th style="text-align:center;">Jumlah</th>
            <th>Status</th>
            <th>Catatan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($bookings as $booking): ?>
        <tr>
            <td>#<?= (int)$booking->booking_id ?></td>
            <td><strong><?= esc_html($booking->customer_name) ?></strong></td>
            <td><?= esc_html($booking->customer_phone ?: '—') ?></td>
            <td><?= KSTCangar_Booking::service_label($booking->service_type) ?></td>
            <td><?= KSTCangar_Helpers::format_date($booking->date) ?></td>
            <td style="text-align:center;"><?= (int)$booking->quantity ?></td>
            <td><?= KSTCangar_Helpers::status_badge($booking->status) ?></td>
            <td><small><?= esc_html($booking->notes ?: '—') ?></small></td>
            <td>
                <!-- Edit -->
                <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-booking','tab'=>'form','booking_id'=>$booking->booking_id], admin_url('admin.php'))) ?>"
                   class="button button-small">Edit</a>

                <!-- Validasi — hanya admin_kst & administrator -->
                <?php if (KSTCangar_Roles::can('kstcangar_validate') && $booking->status === 'pending'): ?>
                <form method="POST" action="<?= admin_url('admin-post.php') ?>" style="display:inline;">
                    <input type="hidden" name="action" value="kstcangar_validate_booking">
                    <input type="hidden" name="booking_id" value="<?= (int)$booking->booking_id ?>">
                    <input type="hidden" name="new_status" value="confirmed">
                    <?php KSTCangar_Helpers::nonce_field('validate_booking'); ?>
                    <button type="submit" class="button button-small" style="color:#5cb85c; border-color:#5cb85c;">✓ Confirm</button>
                </form>
                <form method="POST" action="<?= admin_url('admin-post.php') ?>" style="display:inline;">
                    <input type="hidden" name="action" value="kstcangar_validate_booking">
                    <input type="hidden" name="booking_id" value="<?= (int)$booking->booking_id ?>">
                    <input type="hidden" name="new_status" value="cancelled">
                    <?php KSTCangar_Helpers::nonce_field('validate_booking'); ?>
                    <button type="submit" class="button button-small" style="color:#d9534f; border-color:#d9534f;">✗ Batalkan</button>
                </form>
                <?php endif; ?>

                <!-- Hapus -->
                <form method="POST" action="<?= admin_url('admin-post.php') ?>" style="display:inline;"
                      onsubmit="return confirm('Yakin hapus booking ini?')">
                    <input type="hidden" name="action" value="kstcangar_delete_booking">
                    <input type="hidden" name="booking_id" value="<?= (int)$booking->booking_id ?>">
                    <?php KSTCangar_Helpers::nonce_field('delete_booking'); ?>
                    <button type="submit" class="button button-small button-link-delete">Hapus</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
