<?php defined('ABSPATH') || exit; ?>
<!-- laporan.php: Laporan Mingguan Stok Opname -->

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <form method="GET" style="display:inline-flex; gap:8px; align-items:center;">
        <input type="hidden" name="page" value="kstcangar-stok">
        <input type="hidden" name="tab" value="laporan">
        <label>Filter Minggu:</label>
        <input type="week" name="filter_week" value="<?= esc_attr(str_replace('W', 'W', $week)) ?>" class="regular-text">
        <button type="submit" class="button">Tampilkan</button>
    </form>
</div>

<?php if (empty($laporan)): ?>
    <div style="text-align:center; padding:40px; background:#fff; border:1px solid #ddd; border-radius:4px;">
        <p style="color:#666;">Belum ada data laporan untuk minggu <strong><?= esc_html($week) ?></strong>.</p>
    </div>
<?php else: ?>

<!-- Ringkasan -->
<?php
$total_selisih = array_sum(array_column($laporan, 'difference'));
$validated_count = count(array_filter($laporan, fn($r) => $r->status === 'validated'));
$draft_count = count(array_filter($laporan, fn($r) => $r->status === 'draft'));
?>
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px;">
    <div style="background:#fff; padding:16px; border:1px solid #ddd; border-radius:4px; text-align:center;">
        <div style="font-size:28px; font-weight:bold;"><?= count($laporan) ?></div>
        <div style="color:#666; font-size:13px;">Total Item</div>
    </div>
    <div style="background:#fff; padding:16px; border:1px solid #ddd; border-radius:4px; text-align:center;">
        <div style="font-size:28px; font-weight:bold; color:<?= $total_selisih != 0 ? '#d9534f' : '#5cb85c' ?>;"><?= $total_selisih ?></div>
        <div style="color:#666; font-size:13px;">Total Selisih</div>
    </div>
    <div style="background:#fff; padding:16px; border:1px solid #ddd; border-radius:4px; text-align:center;">
        <div style="font-size:28px; font-weight:bold; color:#5cb85c;"><?= $validated_count ?></div>
        <div style="color:#666; font-size:13px;">Tervalidasi</div>
    </div>
</div>

<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th style="text-align:center;">Stok Awal</th>
            <th style="text-align:center;">Masuk</th>
            <th style="text-align:center;">Keluar</th>
            <th style="text-align:center;">Retur</th>
            <th style="text-align:center;">Stok Sistem</th>
            <th style="text-align:center;">Stok Fisik</th>
            <th style="text-align:center;">Selisih</th>
            <th>Keterangan</th>
            <th>Status</th>
            <th>Divalidasi Oleh</th>
            <?php if (KSTCangar_Roles::can('kstcangar_validate')): ?>
            <th>Aksi</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($laporan as $row): ?>
        <tr>
            <td><strong><?= esc_html($row->item_name) ?></strong></td>
            <td><?= esc_html($row->unit) ?></td>
            <td style="text-align:center;"><?= (int)$row->initial_stock ?></td>
            <td style="text-align:center; color:#5cb85c;">+<?= (int)$row->stock_in ?></td>
            <td style="text-align:center; color:#d9534f;">-<?= (int)$row->stock_out ?></td>
            <td style="text-align:center; color:#f0ad4e;"><?= (int)$row->stock_return ?></td>
            <td style="text-align:center;"><strong><?= (int)$row->system_stock ?></strong></td>
            <td style="text-align:center;"><?= (int)$row->physical_stock ?></td>
            <td style="text-align:center; <?= $row->difference != 0 ? 'color:#d9534f; font-weight:bold;' : 'color:#5cb85c;' ?>">
                <?= (int)$row->difference ?>
            </td>
            <td><?= esc_html($row->note ?: '—') ?></td>
            <td><?= KSTCangar_Helpers::status_badge($row->status) ?></td>
            <td><?= esc_html($row->validated_by_name ?: '—') ?></td>
            <?php if (KSTCangar_Roles::can('kstcangar_validate') && $row->status === 'draft'): ?>
            <td>
                <form method="POST" action="<?= admin_url('admin-post.php') ?>" style="display:inline;">
                    <input type="hidden" name="action" value="kstcangar_validate_opname">
                    <input type="hidden" name="opname_id" value="<?= (int)$row->opname_id ?>">
                    <input type="hidden" name="validate_action" value="validated">
                    <?php KSTCangar_Helpers::nonce_field('validate_opname'); ?>
                    <button type="submit" class="button button-small" style="color:#5cb85c;">✓ Validasi</button>
                </form>
                <form method="POST" action="<?= admin_url('admin-post.php') ?>" style="display:inline;">
                    <input type="hidden" name="action" value="kstcangar_validate_opname">
                    <input type="hidden" name="opname_id" value="<?= (int)$row->opname_id ?>">
                    <input type="hidden" name="validate_action" value="rejected">
                    <?php KSTCangar_Helpers::nonce_field('validate_opname'); ?>
                    <button type="submit" class="button button-small" style="color:#d9534f;">✗ Tolak</button>
                </form>
            </td>
            <?php elseif (KSTCangar_Roles::can('kstcangar_validate')): ?>
            <td>—</td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
