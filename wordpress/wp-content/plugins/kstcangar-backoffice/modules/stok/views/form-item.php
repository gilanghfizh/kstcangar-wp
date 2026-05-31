<?php defined('ABSPATH') || exit; ?>
<!-- form-item.php: Tambah / Edit Master Barang -->

<a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'items'], admin_url('admin.php'))) ?>"
   style="display:inline-block; margin-bottom:16px;">← Kembali</a>

<div style="background:#fff; padding:24px; border:1px solid #ddd; border-radius:4px; max-width:500px;">
    <h2 style="margin-top:0;"><?= $item ? 'Edit Barang' : 'Tambah Barang Baru' ?></h2>

    <form method="POST" action="<?= admin_url('admin-post.php') ?>">
        <input type="hidden" name="action" value="kstcangar_save_item">
        <input type="hidden" name="item_id" value="<?= $item ? (int)$item->item_id : 0 ?>">
        <?php KSTCangar_Helpers::nonce_field('save_item'); ?>

        <table class="form-table">
            <tr>
                <th><label for="name">Nama Barang <span style="color:red">*</span></label></th>
                <td><input type="text" name="name" id="name" value="<?= esc_attr($item->name ?? '') ?>" required class="regular-text" placeholder="Contoh: Kopi Sachet"></td>
            </tr>
            <tr>
                <th><label for="unit">Satuan <span style="color:red">*</span></label></th>
                <td>
                    <select name="unit" id="unit" class="regular-text">
                        <?php
                        $units = ['pcs','kg','liter','dus','pack','botol','karton'];
                        foreach ($units as $u):
                            $selected = ($item->unit ?? 'pcs') === $u ? 'selected' : '';
                        ?>
                        <option value="<?= $u ?>" <?= $selected ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <button type="submit" class="button button-primary"><?= $item ? 'Update Barang' : 'Simpan Barang' ?></button>
    </form>
</div>
