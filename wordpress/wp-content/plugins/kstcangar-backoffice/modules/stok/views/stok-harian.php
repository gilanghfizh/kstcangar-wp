<?php defined('ABSPATH') || exit; ?>
<!-- stok-harian.php: Pergerakan stok per hari -->

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
    <form method="GET" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <input type="hidden" name="page" value="kstcangar-stok">
        <input type="hidden" name="tab" value="harian">
        <label style="font-weight:600;">Tanggal:</label>
        <input type="date" name="filter_date" value="<?= esc_attr($date) ?>" class="regular-text">
        <label style="font-weight:600;">Barang:</label>
        <select name="filter_item" class="regular-text">
            <option value="">Semua Barang</option>
            <?php foreach ($items as $item): ?>
            <option value="<?= (int)$item->item_id ?>" <?= $filter_item == $item->item_id ? 'selected' : '' ?>>
                <?= esc_html($item->name) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="button">Tampilkan</button>
        <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'harian'], admin_url('admin.php'))) ?>"
           class="button">Reset</a>
    </form>
    <?php if (KSTCangar_Roles::can('kstcangar_stok')): ?>
    <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'opname','action'=>'form'], admin_url('admin.php'))) ?>"
       class="button button-primary">+ Input Stok</a>
    <?php endif; ?>
</div>

<!-- Ringkasan hari ini -->
<?php if ($summary): ?>
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px;">
    <div style="background:#d4edda; padding:14px 16px; border-radius:4px; border-left:4px solid #5cb85c;">
        <div style="font-size:24px; font-weight:bold; color:#5cb85c;">+<?= (int)$summary->total_in ?></div>
        <div style="color:#666; font-size:13px;">Total Masuk Hari Ini</div>
    </div>
    <div style="background:#f8d7da; padding:14px 16px; border-radius:4px; border-left:4px solid #d9534f;">
        <div style="font-size:24px; font-weight:bold; color:#d9534f;">-<?= (int)$summary->total_out ?></div>
        <div style="color:#666; font-size:13px;">Total Keluar Hari Ini</div>
    </div>
    <div style="background:#fff3cd; padding:14px 16px; border-radius:4px; border-left:4px solid #f0ad4e;">
        <div style="font-size:24px; font-weight:bold; color:#f0ad4e;"><?= (int)$summary->total_return ?></div>
        <div style="color:#666; font-size:13px;">Total Retur Hari Ini</div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($movements)): ?>
    <div style="text-align:center; padding:40px; background:#fff; border:1px solid #ddd; border-radius:4px;">
        <p style="color:#666;">Belum ada pergerakan stok untuk tanggal
            <strong><?= KSTCangar_Helpers::format_date($date) ?></strong>.
        </p>
        <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'opname','action'=>'form'], admin_url('admin.php'))) ?>"
           class="button button-primary">+ Input Sekarang</a>
    </div>
<?php else: ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th style="text-align:center;">Jenis</th>
            <th style="text-align:center;">Jumlah</th>
            <th>Keterangan</th>
            <th>Dicatat Oleh</th>
            <th>Waktu Input</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($movements as $row):
            $type_config = match($row->type) {
                'IN'     => ['label' => '📥 Masuk',  'color' => '#5cb85c'],
                'OUT'    => ['label' => '📤 Keluar', 'color' => '#d9534f'],
                'RETURN' => ['label' => '🔄 Retur',  'color' => '#f0ad4e'],
                default  => ['label' => $row->type,  'color' => '#999'],
            };
        ?>
        <tr>
            <td><?= KSTCangar_Helpers::format_date($row->date) ?></td>
            <td><strong><?= esc_html($row->item_name) ?></strong></td>
            <td><?= esc_html($row->unit) ?></td>
            <td style="text-align:center;">
                <span style="color:<?= $type_config['color'] ?>; font-weight:bold;">
                    <?= $type_config['label'] ?>
                </span>
            </td>
            <td style="text-align:center; font-weight:bold; font-size:16px;
                       color:<?= $type_config['color'] ?>;">
                <?= $row->type === 'OUT' ? '-' : '+' ?><?= (int)$row->quantity ?>
            </td>
            <td><small><?= esc_html($row->description ?: '—') ?></small></td>
            <td><?= esc_html($row->created_by_name ?: '—') ?></td>
            <td><small><?= esc_html($row->created_at) ?></small></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
