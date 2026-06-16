<<<<<<< HEAD
<?php defined('ABSPATH') || exit; ?>
<!-- data-barang.php: Lihat semua barang + stok terkini -->

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <h3 style="margin:0;">Data Barang & Stok Terkini</h3>
    <?php if (KSTCangar_Roles::can('kstcangar_stok')): ?>
    <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'items','action'=>'form'], admin_url('admin.php'))) ?>"
       class="button button-primary">+ Tambah Barang</a>
    <?php endif; ?>
</div>

<p style="color:#666; margin-bottom:16px; font-size:13px;">
    Stok sistem dihitung dari: <strong>Total Masuk + Retur − Total Keluar</strong> sejak barang pertama kali dicatat.
</p>

<?php if (empty($data_barang)): ?>
    <div style="text-align:center; padding:40px; background:#fff; border:1px solid #ddd; border-radius:4px;">
        <p style="color:#666;">Belum ada barang terdaftar.</p>
        <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'items','action'=>'form'], admin_url('admin.php'))) ?>"
           class="button button-primary">+ Tambah Barang</a>
    </div>
<?php else: ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th style="text-align:center;">Total Masuk</th>
            <th style="text-align:center;">Total Keluar</th>
            <th style="text-align:center;">Total Retur</th>
            <th style="text-align:center;">Stok Sistem</th>
            <th style="text-align:center;">Stok Fisik Terakhir</th>
            <th style="text-align:center;">Selisih Terakhir</th>
            <th>Status Opname</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data_barang as $row):
            $stok_sistem = (int)$row->total_in + (int)$row->total_return - (int)$row->total_out;
        ?>
        <tr>
            <td><strong><?= esc_html($row->name) ?></strong></td>
            <td><?= esc_html($row->unit) ?></td>
            <td style="text-align:center; color:#5cb85c;">+<?= (int)$row->total_in ?></td>
            <td style="text-align:center; color:#d9534f;">-<?= (int)$row->total_out ?></td>
            <td style="text-align:center; color:#f0ad4e;"><?= (int)$row->total_return ?></td>
            <td style="text-align:center;">
                <strong style="font-size:16px;"><?= $stok_sistem ?></strong>
                <small style="color:#666;"> <?= esc_html($row->unit) ?></small>
            </td>
            <td style="text-align:center;">
                <?= $row->last_physical_stock !== null
                    ? (int)$row->last_physical_stock
                    : '<span style="color:#999;">Belum opname</span>' ?>
            </td>
            <td style="text-align:center;">
                <?php if ($row->last_difference !== null): ?>
                    <span style="color:<?= (int)$row->last_difference != 0 ? '#d9534f' : '#5cb85c' ?>; font-weight:bold;">
                        <?= (int)$row->last_difference ?>
                    </span>
                <?php else: ?>
                    <span style="color:#999;">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($row->last_opname_status): ?>
                    <?= KSTCangar_Helpers::status_badge($row->last_opname_status) ?>
                    <br><small style="color:#666;"><?= esc_html($row->last_opname_week) ?></small>
                <?php else: ?>
                    <span style="color:#999; font-size:12px;">Belum ada opname</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'harian','filter_item'=>$row->item_id], admin_url('admin.php'))) ?>"
                   class="button button-small">Histori Harian</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
=======
<?php defined('ABSPATH') || exit; ?>
<!-- data-barang.php: Lihat semua barang + stok terkini -->

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <h3 style="margin:0;">Data Barang & Stok Terkini</h3>
    <?php if (KSTCangar_Roles::can('kstcangar_stok')): ?>
    <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'items','action'=>'form'], admin_url('admin.php'))) ?>"
       class="button button-primary">+ Tambah Barang</a>
    <?php endif; ?>
</div>

<p style="color:#666; margin-bottom:16px; font-size:13px;">
    Stok sistem dihitung dari: <strong>Total Masuk + Retur − Total Keluar</strong> sejak barang pertama kali dicatat.
</p>

<?php if (empty($data_barang)): ?>
    <div style="text-align:center; padding:40px; background:#fff; border:1px solid #ddd; border-radius:4px;">
        <p style="color:#666;">Belum ada barang terdaftar.</p>
        <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'items','action'=>'form'], admin_url('admin.php'))) ?>"
           class="button button-primary">+ Tambah Barang</a>
    </div>
<?php else: ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th style="text-align:center;">Total Masuk</th>
            <th style="text-align:center;">Total Keluar</th>
            <th style="text-align:center;">Total Retur</th>
            <th style="text-align:center;">Stok Sistem</th>
            <th style="text-align:center;">Stok Fisik Terakhir</th>
            <th style="text-align:center;">Selisih Terakhir</th>
            <th>Status Opname</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data_barang as $row):
            $stok_sistem = (int)$row->total_in + (int)$row->total_return - (int)$row->total_out;
        ?>
        <tr>
            <td><strong><?= esc_html($row->name) ?></strong></td>
            <td><?= esc_html($row->unit) ?></td>
            <td style="text-align:center; color:#5cb85c;">+<?= (int)$row->total_in ?></td>
            <td style="text-align:center; color:#d9534f;">-<?= (int)$row->total_out ?></td>
            <td style="text-align:center; color:#f0ad4e;"><?= (int)$row->total_return ?></td>
            <td style="text-align:center;">
                <strong style="font-size:16px;"><?= $stok_sistem ?></strong>
                <small style="color:#666;"> <?= esc_html($row->unit) ?></small>
            </td>
            <td style="text-align:center;">
                <?= $row->last_physical_stock !== null
                    ? (int)$row->last_physical_stock
                    : '<span style="color:#999;">Belum opname</span>' ?>
            </td>
            <td style="text-align:center;">
                <?php if ($row->last_difference !== null): ?>
                    <span style="color:<?= (int)$row->last_difference != 0 ? '#d9534f' : '#5cb85c' ?>; font-weight:bold;">
                        <?= (int)$row->last_difference ?>
                    </span>
                <?php else: ?>
                    <span style="color:#999;">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($row->last_opname_status): ?>
                    <?= KSTCangar_Helpers::status_badge($row->last_opname_status) ?>
                    <br><small style="color:#666;"><?= esc_html($row->last_opname_week) ?></small>
                <?php else: ?>
                    <span style="color:#999; font-size:12px;">Belum ada opname</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'harian','filter_item'=>$row->item_id], admin_url('admin.php'))) ?>"
                   class="button button-small">Histori Harian</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
>>>>>>> 24cb09f2bf57e19341c8f899ae0ab9888ccfd33e
