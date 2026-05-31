<?php defined('ABSPATH') || exit; ?>
<!-- list.php: Daftar transaksi + ringkasan hari ini -->

<?php if (!empty($_GET['success'])): ?>
    <div class="notice notice-success is-dismissible"><p>✅ Transaksi berhasil disimpan.</p></div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <div class="notice notice-error is-dismissible"><p>❌ Terjadi kesalahan. Periksa kembali data yang diisi.</p></div>
<?php endif; ?>

<!-- Ringkasan hari ini -->
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px;">
    <div style="background:#d4edda; padding:16px; border-radius:4px; border-left:4px solid #5cb85c;">
        <div style="font-size:13px; color:#666; margin-bottom:4px;">Pemasukan Hari Ini</div>
        <div style="font-size:22px; font-weight:bold; color:#5cb85c;"><?= KSTCangar_Helpers::rupiah($summary['income']) ?></div>
    </div>
    <div style="background:#f8d7da; padding:16px; border-radius:4px; border-left:4px solid #d9534f;">
        <div style="font-size:13px; color:#666; margin-bottom:4px;">Pengeluaran Hari Ini</div>
        <div style="font-size:22px; font-weight:bold; color:#d9534f;"><?= KSTCangar_Helpers::rupiah($summary['expense']) ?></div>
    </div>
    <div style="background:<?= $summary['net'] >= 0 ? '#d1ecf1' : '#fff3cd' ?>; padding:16px; border-radius:4px; border-left:4px solid <?= $summary['net'] >= 0 ? '#17a2b8' : '#f0ad4e' ?>;">
        <div style="font-size:13px; color:#666; margin-bottom:4px;">Saldo Hari Ini</div>
        <div style="font-size:22px; font-weight:bold; color:<?= $summary['net'] >= 0 ? '#17a2b8' : '#f0ad4e' ?>;">
            <?= KSTCangar_Helpers::rupiah(abs($summary['net'])) ?>
            <span style="font-size:14px;"><?= $summary['net'] >= 0 ? '▲' : '▼' ?></span>
        </div>
    </div>
</div>

<!-- Filter + Tombol Tambah -->
<form method="GET" style="background:#fff; padding:16px; border:1px solid #ddd; border-radius:4px; margin-bottom:16px; display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <input type="hidden" name="page" value="kstcangar-keuangan">
    <input type="hidden" name="tab" value="list">

    <div>
        <label style="display:block; font-weight:600; margin-bottom:4px;">Jenis</label>
        <select name="filter_type" class="regular-text">
            <option value="">Semua</option>
            <option value="INCOME"  <?= $filter_type === 'INCOME'  ? 'selected' : '' ?>>Pemasukan</option>
            <option value="EXPENSE" <?= $filter_type === 'EXPENSE' ? 'selected' : '' ?>>Pengeluaran</option>
        </select>
    </div>

    <div>
        <label style="display:block; font-weight:600; margin-bottom:4px;">Tanggal</label>
        <input type="date" name="filter_date" value="<?= esc_attr($filter_date) ?>" class="regular-text">
    </div>

    <button type="submit" class="button">🔍 Filter</button>
    <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-keuangan','tab'=>'list'], admin_url('admin.php'))) ?>" class="button">Reset</a>

    <div style="margin-left:auto;">
        <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-keuangan','tab'=>'list','action'=>'form'], admin_url('admin.php'))) ?>"
           class="button button-primary">+ Input Transaksi</a>
    </div>
</form>

<?php if (empty($transaksi)): ?>
    <div style="text-align:center; padding:40px; background:#fff; border:1px solid #ddd; border-radius:4px;">
        <p style="color:#666;">Belum ada transaksi.</p>
        <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-keuangan','tab'=>'list','action'=>'form'], admin_url('admin.php'))) ?>"
           class="button button-primary">+ Input Transaksi</a>
    </div>
<?php else: ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th style="width:50px;">ID</th>
            <th>Tanggal</th>
            <th>Jenis</th>
            <th>Kategori</th>
            <th>Nominal</th>
            <th>Keterangan</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($transaksi as $row): ?>
        <tr>
            <td>#<?= (int)$row->finance_id ?></td>
            <td><?= KSTCangar_Helpers::format_date($row->date) ?></td>
            <td>
                <?php if ($row->type === 'INCOME'): ?>
                    <span style="color:#5cb85c; font-weight:bold;">▲ Pemasukan</span>
                <?php else: ?>
                    <span style="color:#d9534f; font-weight:bold;">▼ Pengeluaran</span>
                <?php endif; ?>
            </td>
            <td><?= esc_html($row->category) ?></td>
            <td style="font-weight:bold; color:<?= $row->type === 'INCOME' ? '#5cb85c' : '#d9534f' ?>;">
                <?= KSTCangar_Helpers::rupiah((float)$row->amount) ?>
            </td>
            <td><small><?= esc_html($row->description ?: '—') ?></small></td>
            <td><?= KSTCangar_Helpers::status_badge($row->status) ?></td>
            <td>
                <?php if ($row->status === 'draft'): ?>
                    <a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-keuangan','tab'=>'list','action'=>'form','finance_id'=>$row->finance_id], admin_url('admin.php'))) ?>"
                       class="button button-small">Edit</a>

                    <?php if (KSTCangar_Roles::can('kstcangar_validate')): ?>
                    <form method="POST" action="<?= admin_url('admin-post.php') ?>" style="display:inline;">
                        <input type="hidden" name="action" value="kstcangar_validate_finance">
                        <input type="hidden" name="finance_id" value="<?= (int)$row->finance_id ?>">
                        <input type="hidden" name="new_status" value="validated">
                        <?php KSTCangar_Helpers::nonce_field('validate_finance'); ?>
                        <button type="submit" class="button button-small" style="color:#5cb85c; border-color:#5cb85c;">✓ Validasi</button>
                    </form>
                    <form method="POST" action="<?= admin_url('admin-post.php') ?>" style="display:inline;">
                        <input type="hidden" name="action" value="kstcangar_validate_finance">
                        <input type="hidden" name="finance_id" value="<?= (int)$row->finance_id ?>">
                        <input type="hidden" name="new_status" value="rejected">
                        <?php KSTCangar_Helpers::nonce_field('validate_finance'); ?>
                        <button type="submit" class="button button-small" style="color:#d9534f; border-color:#d9534f;">✗ Tolak</button>
                    </form>
                    <?php endif; ?>

                    <form method="POST" action="<?= admin_url('admin-post.php') ?>" style="display:inline;"
                          onsubmit="return confirm('Yakin hapus transaksi ini?')">
                        <input type="hidden" name="action" value="kstcangar_delete_finance">
                        <input type="hidden" name="finance_id" value="<?= (int)$row->finance_id ?>">
                        <?php KSTCangar_Helpers::nonce_field('delete_finance'); ?>
                        <button type="submit" class="button button-small button-link-delete">Hapus</button>
                    </form>
                <?php else: ?>
                    <span style="color:#999; font-size:12px;">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
