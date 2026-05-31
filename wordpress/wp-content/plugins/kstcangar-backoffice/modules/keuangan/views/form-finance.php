<?php defined('ABSPATH') || exit; ?>

<a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-keuangan','tab'=>'list'], admin_url('admin.php'))) ?>"
   style="display:inline-block; margin-bottom:16px;">← Kembali</a>

<div style="background:#fff; padding:24px; border:1px solid #ddd; border-radius:4px; max-width:580px;">
    <h2 style="margin-top:0;"><?= $finance ? 'Edit Transaksi #' . (int)$finance->finance_id : 'Input Transaksi Baru' ?></h2>

    <form method="POST" action="<?= admin_url('admin-post.php') ?>" id="finance-form">
        <input type="hidden" name="action" value="kstcangar_save_finance">
        <input type="hidden" name="finance_id" value="<?= $finance ? (int)$finance->finance_id : 0 ?>">
        <?php KSTCangar_Helpers::nonce_field('save_finance'); ?>

        <table class="form-table">
            <tr>
                <th><label for="type">Jenis Transaksi <span style="color:red">*</span></label></th>
                <td>
                    <select name="type" id="type" required class="regular-text" onchange="updateKategori(this.value)">
                        <option value="">-- Pilih Jenis --</option>
                        <option value="INCOME"  <?= ($finance->type ?? '') === 'INCOME'  ? 'selected' : '' ?>>▲ Pemasukan</option>
                        <option value="EXPENSE" <?= ($finance->type ?? '') === 'EXPENSE' ? 'selected' : '' ?>>▼ Pengeluaran</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="category">Kategori <span style="color:red">*</span></label></th>
                <td>
                    <select name="category" id="category" required class="regular-text">
                        <option value="">-- Pilih Kategori --</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="amount">Nominal (Rp) <span style="color:red">*</span></label></th>
                <td>
                    <input type="number" name="amount" id="amount"
                           value="<?= $finance ? (float)$finance->amount : '' ?>"
                           min="1" step="1" required class="regular-text"
                           placeholder="Contoh: 150000">
                    <p class="description">Masukkan nominal dalam Rupiah tanpa titik/koma.</p>
                </td>
            </tr>
            <tr>
                <th><label for="date">Tanggal <span style="color:red">*</span></label></th>
                <td>
                    <input type="date" name="date" id="date"
                           value="<?= esc_attr($finance->date ?? date('Y-m-d')) ?>"
                           required class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="description">Keterangan</label></th>
                <td>
                    <textarea name="description" id="description" rows="4" class="large-text"
                              placeholder="Detail transaksi, sumber pemasukan, keperluan pengeluaran..."><?= esc_textarea($finance->description ?? '') ?></textarea>
                </td>
            </tr>
        </table>

        <button type="submit" class="button button-primary button-large">
            <?= $finance ? '💾 Update Transaksi' : '✅ Simpan Transaksi' ?>
        </button>
    </form>
</div>

<script>
// Kategori dinamis sesuai jenis transaksi
const kategori = {
    INCOME:  ['Booking', 'Café', 'Glamping', 'Camping', 'Lainnya'],
    EXPENSE: ['Operasional', 'Bahan Baku', 'Perawatan', 'Gaji', 'Lainnya']
};

const currentKategori = '<?= esc_js($finance->category ?? '') ?>';
const currentType     = '<?= esc_js($finance->type ?? '') ?>';

function updateKategori(type) {
    const select = document.getElementById('category');
    select.innerHTML = '<option value="">-- Pilih Kategori --</option>';

    if (!kategori[type]) return;

    kategori[type].forEach(k => {
        const opt = document.createElement('option');
        opt.value = k;
        opt.textContent = k;
        if (k === currentKategori) opt.selected = true;
        select.appendChild(opt);
    });
}

// Init saat page load jika edit mode
if (currentType) updateKategori(currentType);

document.getElementById('type').addEventListener('change', e => updateKategori(e.target.value));
</script>
