<?php defined('ABSPATH') || exit; ?>

<a href="<?= esc_url(add_query_arg(['page'=>'kstcangar-booking','tab'=>'list'], admin_url('admin.php'))) ?>"
   style="display:inline-block; margin-bottom:16px;">← Kembali ke Daftar</a>

<?php if (!empty($_GET['error']) && $_GET['error'] === 'kapasitas_penuh'): ?>
<div class="notice notice-error is-dismissible">
    <p>⚠️ <strong>Kapasitas penuh!</strong> Layanan yang dipilih sudah penuh untuk tanggal tersebut. Silakan pilih tanggal atau layanan lain.</p>
</div>
<?php endif; ?>

<div style="background:#fff; padding:24px; border:1px solid #ddd; border-radius:4px; max-width:640px;">
    <h2 style="margin-top:0;"><?= $booking ? 'Edit Booking #' . (int)$booking->booking_id : 'Tambah Booking Baru' ?></h2>

    <form method="POST" action="<?= admin_url('admin-post.php') ?>" id="booking-form">
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
                <td><input type="number" name="customer_phone" id="customer_phone"
                           value="<?= esc_attr($booking->customer_phone ?? '') ?>"
                           class="regular-text" placeholder="08xxxxxxxxxx"></td>
            </tr>
            <tr>
                <th><label for="service_type">Jenis Layanan <span style="color:red">*</span></label></th>
                <td>
                    <select name="service_type" id="service_type" required class="regular-text">
                        <option value="">-- Pilih Layanan --</option>
                        <?php
                        $services = [
                            'glamping' => '🏕️ Glamping (maks. 10 orang/hari)',
                            'cafe'     => '☕ Café Eduwisata (maks. 50 orang/hari)',
                            'camping'  => '⛺ Camping Ground (maks. 20 orang/hari)',
                        ];
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
                    <!-- Info kapasitas real-time -->
                    <div id="kapasitas-info" style="margin-top:8px; font-size:13px; color:#666;"></div>
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

        <!-- Pesan kapasitas penuh (ditampilkan via JS) -->
        <div id="kapasitas-error"
             style="display:none; background:#f8d7da; color:#721c24; padding:12px 16px;
                    border-radius:4px; margin-bottom:16px; border:1px solid #f5c6cb;">
            ⚠️ <strong>Kapasitas penuh!</strong> Layanan yang dipilih sudah penuh untuk tanggal tersebut.
            Silakan pilih tanggal atau layanan lain.
        </div>

        <button type="submit" id="submit-btn" class="button button-primary button-large">
            <?= $booking ? '💾 Update Booking' : '✅ Simpan Booking' ?>
        </button>
    </form>
</div>

<script>
const ajaxUrl         = '<?= admin_url('admin-ajax.php') ?>';
const nonce           = '<?= wp_create_nonce('kstcangar_cek_kapasitas') ?>';
const currentBookingId = <?= $booking ? (int)$booking->booking_id : 0 ?>;

let debounceTimer = null;

function cekKapasitas() {
    const service   = document.getElementById('service_type').value;
    const date      = document.getElementById('date').value;
    const quantity  = parseInt(document.getElementById('quantity').value) || 1;
    const infoEl    = document.getElementById('kapasitas-info');
    const errorEl   = document.getElementById('kapasitas-error');
    const submitBtn = document.getElementById('submit-btn');

    // Reset jika layanan atau tanggal belum dipilih
    if (!service || !date) {
        infoEl.innerHTML = '';
        errorEl.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        return;
    }

    const url = `${ajaxUrl}?action=kstcangar_cek_kapasitas&service=${service}&date=${date}&exclude_id=${currentBookingId}&_wpnonce=${nonce}`;

    fetch(url)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;

            const { max, booked, sisa } = res.data;
            const melebihi = quantity > sisa;
            const penuh    = sisa <= 0;

            // Tampilkan info kapasitas
            const silaColor = penuh ? '#d9534f' : (sisa < 5 ? '#f0ad4e' : '#5cb85c');
            infoEl.innerHTML = `
                Kapasitas: <strong>${booked}/${max}</strong> terisi |
                Sisa: <strong style="color:${silaColor}">
                    ${penuh ? 'PENUH' : sisa + ' slot'}
                </strong>
            `;

            // Tampilkan error dan disable tombol jika penuh atau melebihi sisa
            if (penuh || melebihi) {
                errorEl.style.display = 'block';
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
            } else {
                errorEl.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
        })
        .catch(() => {
            // Jika gagal fetch, tetap biarkan form bisa disubmit
            // Validasi server-side tetap akan menangkap jika penuh
            infoEl.innerHTML = '';
        });
}

function debounceCheck() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(cekKapasitas, 400);
}

document.getElementById('service_type').addEventListener('change', debounceCheck);
document.getElementById('date').addEventListener('change', debounceCheck);
document.getElementById('quantity').addEventListener('input', debounceCheck);

// Jalankan saat load (mode edit — service & date sudah terisi)
cekKapasitas();
</script>
