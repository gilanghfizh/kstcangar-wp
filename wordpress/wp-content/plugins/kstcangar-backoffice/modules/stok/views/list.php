<?php defined('ABSPATH') || exit; ?>

<?php if (!empty($_GET['success'])): ?>
    <div class="notice notice-success is-dismissible"><p>✅ Data berhasil disimpan.</p></div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <div class="notice notice-error is-dismissible"><p>❌ Terjadi kesalahan. Silakan coba lagi.</p></div>
<?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <div>
        <form method="GET" style="display:inline-flex; gap:8px; align-items:center;">
            <input type="hidden" name="page" value="kstcangar-stok">
            <input type="hidden" name="tab" value="opname">
            <label>Filter Tanggal:</label>
            <input type="date" name="filter_date" value="<?= esc_attr($date) ?>" class="regular-text">
            <button type="submit" class="button">Tampilkan</button>
        </form>
        <small style="color:#666;">Minggu: <strong><?= esc_html($week) ?></strong></small>
    </div>
    <div style="display:flex; gap:8px;">
        <?php if (KSTCangar_Roles::can('kstcangar_stok')): ?>
        <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'opname','action'=>'form'], admin_url('admin.php'))) ?>"
           class="button button-primary">+ Input Stok Harian</a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($opnames)): ?>
    <div style="text-align:center; padding:40px; background:#fff; border:1px solid #ddd; border-radius:4px;">
        <p style="color:#666;">Belum ada data stok untuk minggu <strong><?= esc_html($week) ?></strong>.</p>
        <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'opname','action'=>'form'], admin_url('admin.php'))) ?>"
           class="button button-primary">Input Sekarang</a>
    </div>
<?php else: ?>
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
            <th>Status</th>
            <th>Dibuat Oleh</th>
            <?php if (KSTCangar_Roles::can('kstcangar_validate')): ?>
            <th>Aksi</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($opnames as $row): ?>
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
                <?php if ($row->difference != 0 && $row->note): ?>
                    <br><small style="color:#666;"><?= esc_html($row->note) ?></small>
                <?php endif; ?>
            </td>
            <td><?= KSTCangar_Helpers::status_badge($row->status) ?></td>
            <td><?= esc_html($row->created_by_name) ?></td>
            <?php if (KSTCangar_Roles::can('kstcangar_validate') && $row->status === 'draft'): ?>
            <td>
                <form method="POST" action="<?= admin_url('admin-post.php') ?>" style="display:inline;">
                    <input type="hidden" name="action" value="kstcangar_validate_opname">
                    <input type="hidden" name="opname_id" value="<?= (int)$row->opname_id ?>">
                    <input type="hidden" name="validate_action" value="validated">
                    <?php KSTCangar_Helpers::nonce_field('validate_opname'); ?>
                    <button type="submit" class="button button-small" style="color:#5cb85c; border-color:#5cb85c;">✓ Validasi</button>
                </form>
                <form method="POST" action="<?= admin_url('admin-post.php') ?>" style="display:inline;">
                    <input type="hidden" name="action" value="kstcangar_validate_opname">
                    <input type="hidden" name="opname_id" value="<?= (int)$row->opname_id ?>">
                    <input type="hidden" name="validate_action" value="rejected">
                    <?php KSTCangar_Helpers::nonce_field('validate_opname'); ?>
                    <button type="submit" class="button button-small" style="color:#d9534f; border-color:#d9534f;">✗ Tolak</button>
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
