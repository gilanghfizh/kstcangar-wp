<?php defined('ABSPATH') || exit;
$items = KSTCangar_Stok::get_active_items();
$today = date('Y-m-d');
$week  = KSTCangar_Helpers::get_week();
?>

<a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'opname'], admin_url('admin.php'))) ?>"
   style="display:inline-block; margin-bottom:16px;">← Kembali ke Daftar</a>

<div style="background:#fff; padding:24px; border:1px solid #ddd; border-radius:4px; max-width:700px;">
    <h2 style="margin-top:0;">Input Stok Harian</h2>

    <?php if (empty($items)): ?>
        <div class="notice notice-warning"><p>⚠️ Belum ada barang terdaftar. <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-stok','tab'=>'items','action'=>'form'], admin_url('admin.php'))) ?>">Tambah barang dulu</a>.</p></div>
    <?php else: ?>

    <!-- Form: Input pergerakan stok (IN/OUT/RETURN) -->
    <h3>📋 Input Barang Masuk / Keluar / Retur</h3>
    <form method="POST" action="<?= admin_url('admin-post.php') ?>">
        <input type="hidden" name="action" value="kstcangar_save_movement">
        <?php KSTCangar_Helpers::nonce_field('save_movement'); ?>

        <table class="form-table">
            <tr>
                <th><label for="item_id">Nama Barang <span style="color:red">*</span></label></th>
                <td>
                    <select name="item_id" id="item_id" required class="regular-text">
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($items as $item): ?>
                        <option value="<?= (int)$item->item_id ?>"><?= esc_html($item->name) ?> (<?= esc_html($item->unit) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="date">Tanggal <span style="color:red">*</span></label></th>
                <td><input type="date" name="date" id="date" value="<?= esc_attr($today) ?>" required class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="type">Jenis <span style="color:red">*</span></label></th>
                <td>
                    <select name="type" id="type" required class="regular-text">
                        <option value="IN">📥 Barang Masuk</option>
                        <option value="OUT">📤 Barang Keluar</option>
                        <option value="RETURN">🔄 Retur</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="quantity">Jumlah <span style="color:red">*</span></label></th>
                <td><input type="number" name="quantity" id="quantity" min="1" value="1" required class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="description">Keterangan</label></th>
                <td><textarea name="description" id="description" rows="3" class="large-text" placeholder="Keterangan retur, sumber barang, dll..."></textarea></td>
            </tr>
        </table>
        <button type="submit" class="button button-primary">Simpan Pergerakan Stok</button>
    </form>

    <hr style="margin:32px 0;">

    <!-- Form: Stok Opname Mingguan -->
    <h3>📊 Input Stok Fisik (Opname Mingguan)</h3>
    <p style="color:#666;">Isi stok fisik hasil pengecekan langsung. Sistem akan menghitung selisih secara otomatis.</p>

    <form method="POST" action="<?= admin_url('admin-post.php') ?>">
        <input type="hidden" name="action" value="kstcangar_save_opname">
        <?php KSTCangar_Helpers::nonce_field('save_opname'); ?>

        <table class="form-table">
            <tr>
                <th><label for="opname_item_id">Nama Barang <span style="color:red">*</span></label></th>
                <td>
                    <select name="item_id" id="opname_item_id" required class="regular-text">
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($items as $item): ?>
                        <option value="<?= (int)$item->item_id ?>"><?= esc_html($item->name) ?> (<?= esc_html($item->unit) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label>Minggu</label></th>
                <td>
                    <input type="text" name="week" value="<?= esc_attr($week) ?>" readonly class="regular-text" style="background:#f0f0f0;">
                    <p class="description">Minggu berjalan saat ini: <strong><?= esc_html($week) ?></strong></p>
                </td>
            </tr>
            <tr>
                <th><label for="initial_stock">Stok Awal Minggu <span style="color:red">*</span></label></th>
                <td><input type="number" name="initial_stock" id="initial_stock" min="0" value="0" required class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="physical_stock">Stok Fisik (Hasil Cek) <span style="color:red">*</span></label></th>
                <td>
                    <input type="number" name="physical_stock" id="physical_stock" min="0" value="0" required class="regular-text">
                    <p class="description">Masukkan jumlah stok aktual dari pengecekan fisik di lapangan.</p>
                </td>
            </tr>
            <tr>
                <th><label for="note">Keterangan Selisih</label></th>
                <td>
                    <textarea name="note" id="note" rows="3" class="large-text" placeholder="Wajib diisi jika ada selisih..."></textarea>
                    <p class="description">Selisih dihitung otomatis. Isi keterangan jika ada selisih.</p>
                </td>
            </tr>
        </table>
        <button type="submit" class="button button-primary">Simpan Opname Mingguan</button>
    </form>

    <?php endif; ?>
</div>
