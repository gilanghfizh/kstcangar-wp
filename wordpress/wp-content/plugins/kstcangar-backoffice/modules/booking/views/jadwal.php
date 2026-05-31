<?php defined('ABSPATH') || exit; ?>
<!-- jadwal.php: Kalender ketersediaan per layanan per bulan -->

<div style="display:flex; gap:12px; align-items:flex-end; margin-bottom:20px;">
    <form method="GET" style="display:flex; gap:8px; align-items:center;">
        <input type="hidden" name="page" value="kstcangar-booking">
        <input type="hidden" name="tab" value="jadwal">
        <label style="font-weight:600;">Bulan:</label>
        <input type="month" name="filter_month" value="<?= esc_attr($month) ?>" class="regular-text">
        <button type="submit" class="button">Tampilkan</button>
    </form>
</div>

<!-- Keterangan kapasitas -->
<div style="background:#fff; padding:16px; border:1px solid #ddd; border-radius:4px; margin-bottom:20px;">
    <strong>Kapasitas per Hari:</strong>
    <span style="margin-left:16px;">🏕️ Glamping: 10 orang</span>
    <span style="margin-left:16px;">☕ Café: 50 orang</span>
    <span style="margin-left:16px;">⛺ Camping: 20 orang</span>
</div>

<?php if (empty($jadwal)): ?>
    <div style="text-align:center; padding:40px; background:#fff; border:1px solid #ddd; border-radius:4px;">
        <p style="color:#666;">Tidak ada booking untuk bulan <strong><?= esc_html($month) ?></strong>.</p>
    </div>
<?php else: ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Layanan</th>
            <th style="text-align:center;">Total Booking</th>
            <th style="text-align:center;">Qty Confirmed</th>
            <th style="text-align:center;">Pending</th>
            <th>Ketersediaan</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $kapasitas = ['glamping' => 10, 'cafe' => 50, 'camping' => 20];
        foreach ($jadwal as $row):
            $max   = $kapasitas[$row->service_type] ?? 10;
            $sisa  = $max - (int)$row->confirmed_qty;
            $pct   = min(100, round(($row->confirmed_qty / $max) * 100));
            $color = $pct >= 100 ? '#d9534f' : ($pct >= 70 ? '#f0ad4e' : '#5cb85c');
        ?>
        <tr>
            <td><strong><?= KSTCangar_Helpers::format_date($row->date) ?></strong></td>
            <td><?= KSTCangar_Booking::service_label($row->service_type) ?></td>
            <td style="text-align:center;"><?= (int)$row->total_booking ?></td>
            <td style="text-align:center;"><?= (int)$row->confirmed_qty ?> / <?= $max ?></td>
            <td style="text-align:center;"><?= (int)$row->pending_count ?></td>
            <td>
                <div style="background:#eee; border-radius:4px; height:12px; width:120px; display:inline-block; vertical-align:middle;">
                    <div style="background:<?= $color ?>; width:<?= $pct ?>%; height:100%; border-radius:4px;"></div>
                </div>
                <span style="margin-left:8px; color:<?= $color ?>; font-weight:bold;">
                    <?= $pct >= 100 ? 'PENUH' : "Sisa $sisa" ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
