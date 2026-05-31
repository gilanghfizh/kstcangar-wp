<?php defined('ABSPATH') || exit; ?>
<!-- rekap-mingguan.php -->

<div style="display:flex; gap:12px; align-items:flex-end; margin-bottom:20px;">
    <form method="GET" style="display:flex; gap:8px; align-items:center;">
        <input type="hidden" name="page" value="kstcangar-keuangan">
        <input type="hidden" name="tab" value="mingguan">
        <label style="font-weight:600;">Minggu:</label>
        <input type="week" name="filter_week" value="<?= esc_attr($week) ?>" class="regular-text">
        <button type="submit" class="button">Tampilkan</button>
    </form>
</div>

<!-- Summary -->
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px;">
    <div style="background:#d4edda; padding:16px; border-radius:4px; border-left:4px solid #5cb85c;">
        <div style="font-size:13px; color:#666;">Total Pemasukan Minggu Ini</div>
        <div style="font-size:22px; font-weight:bold; color:#5cb85c;"><?= KSTCangar_Helpers::rupiah($total_income) ?></div>
    </div>
    <div style="background:#f8d7da; padding:16px; border-radius:4px; border-left:4px solid #d9534f;">
        <div style="font-size:13px; color:#666;">Total Pengeluaran Minggu Ini</div>
        <div style="font-size:22px; font-weight:bold; color:#d9534f;"><?= KSTCangar_Helpers::rupiah($total_expense) ?></div>
    </div>
    <div style="background:#d1ecf1; padding:16px; border-radius:4px; border-left:4px solid #17a2b8;">
        <div style="font-size:13px; color:#666;">Saldo Bersih</div>
        <div style="font-size:22px; font-weight:bold; color:<?= $net >= 0 ? '#17a2b8' : '#d9534f' ?>;">
            <?= ($net < 0 ? '-' : '') . KSTCangar_Helpers::rupiah(abs($net)) ?>
        </div>
    </div>
</div>

<!-- Per hari -->
<?php if (!empty($rekap)): ?>
<h3>Rincian per Hari</h3>
<table class="wp-list-table widefat fixed striped">
    <thead><tr><th>Tanggal</th><th>Pemasukan</th><th>Pengeluaran</th><th>Saldo Hari</th><th>Transaksi</th></tr></thead>
    <tbody>
        <?php foreach ($rekap as $row):
            $net_hari = (float)$row->total_income - (float)$row->total_expense;
        ?>
        <tr>
            <td><?= KSTCangar_Helpers::format_date($row->date) ?></td>
            <td style="color:#5cb85c;"><?= KSTCangar_Helpers::rupiah((float)$row->total_income) ?></td>
            <td style="color:#d9534f;"><?= KSTCangar_Helpers::rupiah((float)$row->total_expense) ?></td>
            <td style="font-weight:bold; color:<?= $net_hari >= 0 ? '#17a2b8' : '#d9534f' ?>;">
                <?= ($net_hari < 0 ? '-' : '') . KSTCangar_Helpers::rupiah(abs($net_hari)) ?>
            </td>
            <td><?= (int)$row->jumlah_transaksi ?>x</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p style="color:#666; text-align:center; padding:24px;">Tidak ada data untuk minggu ini.</p>
<?php endif; ?>

<!-- Per kategori -->
<?php if (!empty($per_kategori)): ?>
<h3 style="margin-top:24px;">Breakdown per Kategori</h3>
<table class="wp-list-table widefat fixed striped" style="max-width:500px;">
    <thead><tr><th>Jenis</th><th>Kategori</th><th>Total</th></tr></thead>
    <tbody>
        <?php foreach ($per_kategori as $row): ?>
        <tr>
            <td><?= $row->type === 'INCOME' ? '<span style="color:#5cb85c;">▲ Pemasukan</span>' : '<span style="color:#d9534f;">▼ Pengeluaran</span>' ?></td>
            <td><?= esc_html($row->category) ?></td>
            <td style="font-weight:bold;"><?= KSTCangar_Helpers::rupiah((float)$row->total) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
