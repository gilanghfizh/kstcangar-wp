<?php defined('ABSPATH') || exit; ?>
<!-- rekap-harian.php -->

<div style="display:flex; gap:12px; align-items:flex-end; margin-bottom:20px;">
    <form method="GET" style="display:flex; gap:8px; align-items:center;">
        <input type="hidden" name="page" value="kstcangar-keuangan">
        <input type="hidden" name="tab" value="rekap">
        <label style="font-weight:600;">Tanggal:</label>
        <input type="date" name="filter_date" value="<?= esc_attr($date) ?>" class="regular-text">
        <button type="submit" class="button">Tampilkan</button>
    </form>
</div>

<!-- Summary cards -->
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px;">
    <div style="background:#d4edda; padding:16px; border-radius:4px; border-left:4px solid #5cb85c;">
        <div style="font-size:13px; color:#666;">Total Pemasukan</div>
        <div style="font-size:22px; font-weight:bold; color:#5cb85c;"><?= KSTCangar_Helpers::rupiah($summary['income']) ?></div>
    </div>
    <div style="background:#f8d7da; padding:16px; border-radius:4px; border-left:4px solid #d9534f;">
        <div style="font-size:13px; color:#666;">Total Pengeluaran</div>
        <div style="font-size:22px; font-weight:bold; color:#d9534f;"><?= KSTCangar_Helpers::rupiah($summary['expense']) ?></div>
    </div>
    <div style="background:#d1ecf1; padding:16px; border-radius:4px; border-left:4px solid #17a2b8;">
        <div style="font-size:13px; color:#666;">Saldo Bersih</div>
        <div style="font-size:22px; font-weight:bold; color:<?= $summary['net'] >= 0 ? '#17a2b8' : '#d9534f' ?>;">
            <?= ($summary['net'] < 0 ? '-' : '') . KSTCangar_Helpers::rupiah(abs($summary['net'])) ?>
        </div>
    </div>
</div>

<!-- Per kategori -->
<?php if (!empty($per_kategori)): ?>
<h3>Breakdown per Kategori</h3>
<table class="wp-list-table widefat fixed striped" style="max-width:500px;">
    <thead><tr><th>Jenis</th><th>Kategori</th><th>Total</th><th>Transaksi</th></tr></thead>
    <tbody>
        <?php foreach ($per_kategori as $row): ?>
        <tr>
            <td><?= $row->type === 'INCOME' ? '<span style="color:#5cb85c;">▲ Pemasukan</span>' : '<span style="color:#d9534f;">▼ Pengeluaran</span>' ?></td>
            <td><?= esc_html($row->category) ?></td>
            <td style="font-weight:bold;"><?= KSTCangar_Helpers::rupiah((float)$row->total) ?></td>
            <td><?= (int)$row->jumlah ?>x</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- Detail transaksi hari ini -->
<?php if (!empty($transaksi)): ?>
<h3 style="margin-top:24px;">Detail Transaksi — <?= KSTCangar_Helpers::format_date($date) ?></h3>
<table class="wp-list-table widefat fixed striped">
    <thead><tr><th>Jenis</th><th>Kategori</th><th>Nominal</th><th>Keterangan</th><th>Status</th><th>Dibuat Oleh</th></tr></thead>
    <tbody>
        <?php foreach ($transaksi as $row): ?>
        <tr>
            <td><?= $row->type === 'INCOME' ? '<span style="color:#5cb85c;">▲ Pemasukan</span>' : '<span style="color:#d9534f;">▼ Pengeluaran</span>' ?></td>
            <td><?= esc_html($row->category) ?></td>
            <td style="font-weight:bold;"><?= KSTCangar_Helpers::rupiah((float)$row->amount) ?></td>
            <td><small><?= esc_html($row->description ?: '—') ?></small></td>
            <td><?= KSTCangar_Helpers::status_badge($row->status) ?></td>
            <td><?= esc_html($row->created_by_name) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
