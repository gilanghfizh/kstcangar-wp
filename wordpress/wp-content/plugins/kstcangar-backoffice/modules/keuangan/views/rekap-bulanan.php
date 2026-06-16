<?php defined('ABSPATH') || exit; ?>
<!-- rekap-bulanan.php -->

<div style="display:flex; gap:12px; align-items:flex-end; margin-bottom:20px;">
    <form method="GET" style="display:flex; gap:8px; align-items:center;">
        <input type="hidden" name="page" value="kstcangar-keuangan">
        <input type="hidden" name="tab" value="bulanan">
        <label style="font-weight:600;">Bulan:</label>
        <input type="month" name="filter_month" value="<?= esc_attr($month) ?>" class="regular-text">
        <button type="submit" class="button">Tampilkan</button>
    </form>
</div>

<!-- Summary -->
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px;">
    <div style="background:#d4edda; padding:16px; border-radius:4px; border-left:4px solid #5cb85c;">
        <div style="font-size:13px; color:#666;">Total Pemasukan Bulan Ini</div>
        <div style="font-size:24px; font-weight:bold; color:#5cb85c;"><?= KSTCangar_Helpers::rupiah($total_income) ?></div>
    </div>
    <div style="background:#f8d7da; padding:16px; border-radius:4px; border-left:4px solid #d9534f;">
        <div style="font-size:13px; color:#666;">Total Pengeluaran Bulan Ini</div>
        <div style="font-size:24px; font-weight:bold; color:#d9534f;"><?= KSTCangar_Helpers::rupiah($total_expense) ?></div>
    </div>
    <div style="background:#d1ecf1; padding:16px; border-radius:4px; border-left:4px solid #17a2b8;">
        <div style="font-size:13px; color:#666;">Saldo Bersih Bulan Ini</div>
        <div style="font-size:24px; font-weight:bold; color:<?= $net >= 0 ? '#17a2b8' : '#d9534f' ?>;">
            <?= ($net < 0 ? '-' : '') . KSTCangar_Helpers::rupiah(abs($net)) ?>
        </div>
    </div>
</div>

<!-- Per hari dalam bulan -->
<?php if (!empty($rekap)): ?>
<h3>Rincian per Hari</h3>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Pemasukan</th>
            <th>Pengeluaran</th>
            <th>Saldo Hari</th>
            <th>Jumlah Transaksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $running_net = 0;
        foreach ($rekap as $row):
            $net_hari    = (float)$row->total_income - (float)$row->total_expense;
            $running_net += $net_hari;
        ?>
        <tr>
            <td><?= KSTCangar_Helpers::format_date($row->date) ?></td>
            <td style="color:#5cb85c;"><?= KSTCangar_Helpers::rupiah((float)$row->total_income) ?></td>
            <td style="color:#d9534f;"><?= KSTCangar_Helpers::rupiah((float)$row->total_expense) ?></td>
            <td style="font-weight:bold; color:<?= $net_hari >= 0 ? '#17a2b8' : '#d9534f' ?>;">
                <?= ($net_hari < 0 ? '-' : '+') . KSTCangar_Helpers::rupiah(abs($net_hari)) ?>
            </td>
            <td><?= (int)$row->jumlah_transaksi ?>x</td>
        </tr>
        <?php endforeach; ?>
        <!-- Baris total -->
        <tr style="background:#f8f9fa; font-weight:bold;">
            <td><strong>TOTAL</strong></td>
            <td style="color:#5cb85c;"><strong><?= KSTCangar_Helpers::rupiah($total_income) ?></strong></td>
            <td style="color:#d9534f;"><strong><?= KSTCangar_Helpers::rupiah($total_expense) ?></strong></td>
            <td style="color:<?= $net >= 0 ? '#17a2b8' : '#d9534f' ?>;"><strong><?= ($net < 0 ? '-' : '') . KSTCangar_Helpers::rupiah(abs($net)) ?></strong></td>
            <td></td>
        </tr>
    </tbody>
</table>
<?php else: ?>
    <p style="color:#666; text-align:center; padding:24px;">Tidak ada data untuk bulan ini.</p>
<?php endif; ?>

<!-- Per kategori -->
<?php if (!empty($per_kategori)): ?>
<h3 style="margin-top:24px;">Breakdown per Kategori</h3>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; max-width:700px;">
    <!-- Pemasukan -->
    <div>
        <h4 style="color:#5cb85c;">▲ Pemasukan</h4>
        <table class="wp-list-table widefat striped">
            <thead><tr><th>Kategori</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($per_kategori as $row): ?>
                    <?php if ($row->type === 'INCOME'): ?>
                    <tr>
                        <td><?= esc_html($row->category) ?></td>
                        <td><strong><?= KSTCangar_Helpers::rupiah((float)$row->total) ?></strong></td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Pengeluaran -->
    <div>
        <h4 style="color:#d9534f;">▼ Pengeluaran</h4>
        <table class="wp-list-table widefat striped">
            <thead><tr><th>Kategori</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($per_kategori as $row): ?>
                    <?php if ($row->type === 'EXPENSE'): ?>
                    <tr>
                        <td><?= esc_html($row->category) ?></td>
                        <td><strong><?= KSTCangar_Helpers::rupiah((float)$row->total) ?></strong></td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
