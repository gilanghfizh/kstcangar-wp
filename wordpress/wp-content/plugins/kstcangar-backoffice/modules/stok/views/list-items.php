<?php defined('ABSPATH') || exit; ?>
<!-- list-items.php: Daftar Master Barang -->

<?php if (!empty($_GET['success'])): ?>
    <div class="notice notice-success is-dismissible"><p>✅ Data berhasil disimpan.</p></div>
<?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <h3 style="margin:0;">Daftar Master Barang</h3>
    <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'items','action'=>'form'], admin_url('admin.php'))) ?>"
       class="button button-primary">+ Tambah Barang</a>
</div>

<?php if (empty($items)): ?>
    <div style="text-align:center; padding:40px; background:#fff; border:1px solid #ddd; border-radius:4px;">
        <p style="color:#666;">Belum ada barang terdaftar.</p>
        <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'items','action'=>'form'], admin_url('admin.php'))) ?>"
           class="button button-primary">+ Tambah Barang</a>
    </div>
<?php else: ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= (int)$item->item_id ?></td>
            <td><strong><?= esc_html($item->name) ?></strong></td>
            <td><?= esc_html($item->unit) ?></td>
            <td><?= $item->is_active ? '<span style="color:#5cb85c;">Aktif</span>' : '<span style="color:#999;">Nonaktif</span>' ?></td>
            <td>
                <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'items','action'=>'form','item_id'=>$item->item_id], admin_url('admin.php'))) ?>"
                   class="button button-small">Edit</a>

                <?php if ($item->is_active): ?>
                <form method="POST" action="<?= admin_url('admin-post.php') ?>" style="display:inline;"
                      onsubmit="return confirm('Yakin ingin menonaktifkan barang ini?')">
                    <input type="hidden" name="action" value="kstcangar_delete_item">
                    <input type="hidden" name="item_id" value="<?= (int)$item->item_id ?>">
                    <?php KSTCangar_Helpers::nonce_field('delete_item'); ?>
                    <button type="submit" class="button button-small" style="color:#d9534f;">Nonaktifkan</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
