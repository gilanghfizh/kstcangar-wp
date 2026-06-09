
let bookingData = [];

document.addEventListener("DOMContentLoaded", async () => {
  if (!localStorage.getItem("kst_access_token")) return;
  showBookingLoading();
  await loadBooking();
});

function showBookingLoading() {
  const tbody = document.getElementById("bookingTableBody");
  if (tbody) {
    tbody.innerHTML = `
<tr>
  <td colspan="14" style="padding:30px;text-align:center;color:#9ca3af;">
    Memuat data booking...
  </td>
</tr>`;
  }
}

async function loadBooking() {
  try {
    const res   = await apiRequest("/data/booking");
    const items = res?.data?.items || [];

    bookingData = items.map((item) => {
      const cols = colMap(item.colValues);
      return {
        rowId:       item.rowId ?? null,
        nama:        cols[0] ?? "-",
        kontak:      cols[1] ?? "-",
        tipe:        cols[2] ?? "-",
        checkin:     cols[3] ?? "-",
        checkout:    "-",
        jumlah_tamu: Number(cols[4] ?? 0),
        status:      cols[5] ?? "-",
        unit:        "-",
        harga:       0,
        bukti:       "-",
        invoice:     "-",
        additional:  "-",
      };
    });

    renderBooking(bookingData);
    updateCards(bookingData);
  } catch (err) {
    console.error("Gagal memuat data booking:", err);
    renderBooking([]);
    updateCards([]);
    const tbody = document.getElementById("bookingTableBody");
    if (tbody) {
      tbody.innerHTML = `
<tr>
  <td colspan="14" style="padding:30px;text-align:center;color:#ef4444;">
    Gagal memuat data booking: ${err.message || 'Terjadi kesalahan'}
  </td>
</tr>`;
    }
  }
}

function updateCards(data) {
  const el = (id) => document.getElementById(id);

  if (el("stat-total-booking")) el("stat-total-booking").innerText = data.length;

  if (el("stat-pendapatan")) {
    const total = data.reduce((a, b) => a + (Number(b.harga) || 0), 0);
    el("stat-pendapatan").innerText = "Rp " + total.toLocaleString("id-ID");
  }

  if (el("stat-lunas")) {
    el("stat-lunas").innerText = data.filter(
      (x) => ["confirmed", "lunas"].includes(String(x.status).toLowerCase())
    ).length;
  }

  if (el("stat-belum")) {
    el("stat-belum").innerText = data.filter(
      (x) => !["confirmed", "lunas"].includes(String(x.status).toLowerCase())
    ).length;
  }
}

function renderBooking(data) {
  const tbody = document.getElementById("bookingTableBody");
  if (!tbody) return;

  tbody.innerHTML = "";

  if (!data.length) {
    tbody.innerHTML = `
<tr>
  <td colspan="14" style="padding:30px;text-align:center;color:#9ca3af;">
    Belum ada data booking
  </td>
</tr>`;
    return;
  }

  data.forEach((item, index) => {
    const sl = String(item.status).toLowerCase();
    let badgeClass = "belum", badgeLabel = item.status;
    if (sl === "confirmed") { badgeClass = "lunas"; badgeLabel = "Lunas"; }
    else if (sl === "pending") { badgeClass = "dp"; badgeLabel = "Pending"; }
    else if (sl === "cancelled") { badgeClass = "belum"; badgeLabel = "Batal"; }

    tbody.innerHTML += `
<tr>
  <td>${index + 1}</td>
  <td>${item.nama}</td>
  <td>${item.jumlah_tamu}</td>
  <td>${item.checkin}</td>
  <td>${item.checkout}</td>
  <td>${item.kontak}</td>
  <td>${item.tipe}</td>
  <td>${item.unit}</td>
  <td>Rp ${Number(item.harga).toLocaleString("id-ID")}</td>
  <td><span class="badge ${badgeClass}">${badgeLabel}</span></td>
  <td>${item.bukti}</td>
  <td>${item.invoice}</td>
  <td>${item.additional}</td>
  <td>
    <button class="btn-aksi edit"  onclick="editBooking(${index})" title="Edit">✏️</button>
    <button class="btn-aksi hapus" onclick="hapusBooking(${index})" title="Hapus">🗑️</button>
  </td>
</tr>`;
  });
}


function showBookingForm(mode, idx) {
  const box = document.getElementById("bookingFormBox");
  if (!box) return;
  box.style.display = "block";

  const title = document.getElementById("bookingFormTitle");
  if (title) title.textContent = mode === "edit" ? "Form Edit Booking" : "Form Input Booking";

  const editIdx = document.getElementById("bEditIndex");
  if (editIdx) editIdx.value = (mode === "edit" && idx !== undefined) ? idx : "";

  if (mode === "edit" && idx !== undefined && bookingData[idx]) {
    const item = bookingData[idx];
    setVal("b-nama",       item.nama);
    setVal("b-wa",         item.kontak);
    setVal("b-tamu",       item.jumlah_tamu);
    setVal("b-tipe",       item.tipe);
    setVal("b-unit",       item.unit);
    setVal("b-checkin",    item.checkin);
    setVal("b-checkout",   item.checkout);
    setVal("b-harga",      item.harga);
    setVal("b-status",     item.status);
    setVal("b-invoice",    item.invoice);
    setVal("b-additional", item.additional);

    const tipeLabel = document.getElementById("tipeLabel");
    if (tipeLabel) tipeLabel.innerText = item.tipe || "Pilih Tipe";
    const unitLabel = document.getElementById("unitLabel");
    if (unitLabel) unitLabel.innerText = item.unit || "Pilih Unit";
    if (item.tipe && typeof buildUnitOptions === "function") buildUnitOptions(item.tipe);
  } else {
    resetBookingForm();
  }

  box.scrollIntoView({ behavior: "smooth" });
}

function hideBookingForm() {
  const box = document.getElementById("bookingFormBox");
  if (box) box.style.display = "none";
  resetBookingForm();
}

function resetBookingForm() {
  ["b-nama","b-wa","b-alamat","b-receipt","b-invoice","b-additional","b-bukti-display"].forEach(
    (id) => setVal(id, "")
  );
  ["b-tamu","b-harga"].forEach((id) => setVal(id, 0));
  setVal("b-tipe", ""); setVal("b-unit", "");
  setVal("b-checkin", ""); setVal("b-checkout", "");
  setVal("b-status", "Belum Lunas"); setVal("b-metode", "Transfer Bank");

  const tipeLabel = document.getElementById("tipeLabel");
  if (tipeLabel) tipeLabel.innerText = "Pilih Tipe";
  const unitLabel = document.getElementById("unitLabel");
  if (unitLabel) unitLabel.innerText = "Pilih Unit";
  const editIdx = document.getElementById("bEditIndex");
  if (editIdx) editIdx.value = "";
}

async function simpanBooking() {
  const nama    = getVal("b-nama");
  const wa      = getVal("b-wa");
  const tamu    = getVal("b-tamu");
  const tipe    = getVal("b-tipe");
  const unit    = getVal("b-unit");
  const checkin = getVal("b-checkin");
  const checkout= getVal("b-checkout");
  const alamat  = getVal("b-alamat");
  const harga   = getVal("b-harga");
  const status  = getVal("b-status");
  const metode  = getVal("b-metode");
  const receipt = getVal("b-receipt");
  const invoice = getVal("b-invoice");
  const additional = getVal("b-additional");

  if (!nama || !wa || !tipe || !checkin || !alamat) {
    alert("Harap isi semua field yang wajib (*)");
    return;
  }

  const payload = {
    nama_customer: nama, no_hp: wa,
    jumlah_tamu: Number(tamu) || 1,
    layanan: tipe, no_unit: unit,
    tanggal_checkin: checkin, tanggal_checkout: checkout,
    alamat, harga: Number(harga) || 0,
    status_bayar: status, metode_bayar: metode,
    no_receipt: receipt, no_invoice: invoice,
    additional_needs: additional,
  };

  const editIdxVal = getVal("bEditIndex");
  const isEdit     = editIdxVal !== "" && bookingData[editIdxVal];

  try {
    if (isEdit) {
      const rowId = bookingData[editIdxVal].rowId;
      await apiRequest("/data/booking/" + rowId, "PUT", payload);
      alert("Booking berhasil diperbarui.");
    } else {
      await apiRequest("/data/booking", "POST", payload);
      alert("Booking berhasil disimpan.");
    }
    hideBookingForm();
    await loadBooking();
  } catch (err) {
    alert("Gagal menyimpan booking: " + err.message);
  }
}

function editBooking(idx) { showBookingForm("edit", idx); }

async function hapusBooking(idx) {
  const item = bookingData[idx];
  if (!item) return;
  if (!confirm('Hapus booking "' + item.nama + '"?')) return;

  try {
    if (item.rowId) await apiRequest("/data/booking/" + item.rowId, "DELETE");
    alert("Booking berhasil dihapus.");
    await loadBooking();
  } catch (err) {
    alert("Gagal menghapus: " + err.message);
  }
}

function colMap(colValues) {
  const map = {};
  if (Array.isArray(colValues)) colValues.forEach((c) => { map[c.colIdx] = c.value; });
  return map;
}
function getVal(id) { const el = document.getElementById(id); return el ? el.value : ""; }
function setVal(id, val) { const el = document.getElementById(id); if (el) el.value = val ?? ""; }
