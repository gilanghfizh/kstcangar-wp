<?php defined('ABSPATH') || exit; ?>

<a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-booking','tab'=>'list'], admin_url('admin.php'))) ?>"
   style="display:inline-block; margin-bottom:16px;">← Kembali ke Daftar</a>

<div style="background:#fff; padding:24px; border:1px solid #ddd; border-radius:4px; max-width:640px;">
    <h2 style="margin-top:0;"><?= $booking ? 'Edit Booking #' . (int)$booking->booking_id : 'Tambah Booking Baru' ?></h2>

    <form method="POST" action="<?= admin_url('admin-post.php') ?>">
        <input type="hidden" name="action" value="kstcangar_save_booking">
        <input type="hidden" name="booking_id" value="<?= $booking ? (int)$booking->booking_id : 0 ?>">
        <?php KSTCangar_Helpers::nonce_field('save_booking'); ?>

        <table class="form-table">
            <tr>
                <th><label for="customer_name">Nama Customer <span style="color:red">*</span></label></th>
                <td><input type="text" name="customer_name" id="customer_name"
                           value="<?= esc_attr($booking->customer_name ?? '') ?>"
                           required class="regular-text" placeholder="Nama lengkap"></td>
            </tr>
            <tr>
                <th><label for="customer_phone">No. HP / WhatsApp</label></th>
                <td><input type="text" name="customer_phone" id="customer_phone"
                           value="<?= esc_attr($booking->customer_phone ?? '') ?>"
                           class="regular-text" placeholder="08xxxxxxxxxx"></td>
            </tr>
            <tr>
                <th><label for="service_type">Jenis Layanan <span style="color:red">*</span></label></th>
                <td>
                    <select name="service_type" id="service_type" required class="regular-text">
                        <option value="">-- Pilih Layanan --</option>
                        <?php
                        $services = ['glamping' => '🏕️ Glamping', 'cafe' => '☕ Café Eduwisata', 'camping' => '⛺ Camping Ground'];
                        foreach ($services as $val => $label):
                            $selected = ($booking->service_type ?? '') === $val ? 'selected' : '';
                        ?>
                        <option value="<?= $val ?>" <?= $selected ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="date">Tanggal Kunjungan <span style="color:red">*</span></label></th>
                <td>
                    <input type="date" name="date" id="date"
                           value="<?= esc_attr($booking->date ?? date('Y-m-d')) ?>"
                           min="<?= date('Y-m-d') ?>" required class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="quantity">Jumlah Orang <span style="color:red">*</span></label></th>
                <td>
                    <input type="number" name="quantity" id="quantity"
                           value="<?= (int)($booking->quantity ?? 1) ?>"
                           min="1" max="50" required class="regular-text">
                </td>
            </tr>
            <?php if ($booking || KSTCangar_Roles::can('kstcangar_validate')): ?>
            <tr>
                <th><label for="status">Status</label></th>
                <td>
                    <select name="status" id="status" class="regular-text">
                        <option value="pending"   <?= ($booking->status ?? 'pending') === 'pending'   ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= ($booking->status ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="cancelled" <?= ($booking->status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th><label for="notes">Catatan</label></th>
                <td>
                    <textarea name="notes" id="notes" rows="4" class="large-text"
                              placeholder="Permintaan khusus, catatan tambahan..."><?= esc_textarea($booking->notes ?? '') ?></textarea>
                </td>
            </tr>
        </table>

        <button type="submit" class="button button-primary button-large">
            <?= $booking ? '💾 Update Booking' : '✅ Simpan Booking' ?>
        </button>
    </form>
</div>
