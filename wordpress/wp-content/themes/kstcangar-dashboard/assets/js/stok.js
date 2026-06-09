let stokData = [];
let activeMonth = new Date().getMonth() + 1;
let activeYear = new Date().getFullYear();

document.addEventListener("DOMContentLoaded", async () => {
  if (!localStorage.getItem("kst_access_token")) return;
  showStokLoading();
  await loadStok();
});

function showStokLoading() {
  const tbody = document.getElementById("soTableBody");
  if (tbody) {
    tbody.innerHTML = `
<tr>
  <td colspan="50" style="text-align:center;padding:24px;color:#9ca3af;">
    <span>Memuat data stok...</span>
  </td>
</tr>`;
  }
}

async function loadStok() {
  try {
    const res = await apiRequest("/data/stok");
    const items = res?.data?.items || [];

    stokData = items.map((item) => {
      const cols = colMap(item.colValues);

      const created = item.createdAt ? new Date(item.createdAt) : new Date();
      const bulan = isNaN(created) ? activeMonth : created.getMonth() + 1;
      const tahun = isNaN(created) ? activeYear : created.getFullYear();

      return {
        rowId: item.rowId ?? null,
        nama: cols[0] ?? "-",
        satuan: cols[1] ?? "",
        stokAwal: Number(cols[2] ?? 0),
        totalMasuk: Number(cols[3] ?? 0),
        totalKeluar: Number(cols[4] ?? 0),
        retur: Number(cols[5] ?? 0),
        stokSistem: Number(cols[6] ?? 0),
        stokFisik: Number(cols[7] ?? 0),
        selisih: Number(cols[8] ?? 0),
        status: cols[9] ?? "-",
        ketRetur: "",
        ketSelisih: "",
        masuk: [0, 0, 0, 0, 0, 0, 0],
        keluar: [0, 0, 0, 0, 0, 0, 0],
        bulan,
        tahun,
      };
    });

    renderTable();
  } catch (err) {
    console.error("Gagal memuat data stok:", err);
    stokData = [];
    renderTable();
    const tbody = document.getElementById("soTableBody");
    if (tbody) {
      tbody.innerHTML = `
<tr>
  <td colspan="50" style="text-align:center;padding:24px;color:#ef4444;">
    Gagal memuat data stok: ${err.message || 'Terjadi kesalahan'}
  </td>
</tr>`;
    }
  }
}

function getFiltered() {
  const q = (document.getElementById("searchInput")?.value || "").toLowerCase();
  return stokData.filter(
    (r) =>
      r.bulan === activeMonth &&
      r.tahun === activeYear &&
      r.nama.toLowerCase().includes(q),
  );
}

function renderTable() {
  const tbody = document.getElementById("soTableBody");
  if (!tbody) return;

  const rows = getFiltered();
  tbody.innerHTML = "";

  if (!rows.length) {
    tbody.innerHTML = `
<tr>
  <td colspan="50" style="text-align:center;padding:24px;color:#9ca3af;">
    Belum ada data stok untuk periode ini
  </td>
</tr>`;
    updateStats([]);
    return;
  }

  rows.forEach((r, idx) => {
    const stokAkhir = r.stokAwal + r.totalMasuk - r.totalKeluar - r.retur;
    const selisih = r.stokFisik - stokAkhir;

    tbody.innerHTML += `
<tr>
  <td>${r.nama}</td>
  <td>${r.stokAwal}</td>
  ${r.masuk.map((v) => `<td>${v || "-"}</td>`).join("")}
  <td class="bold-green">${r.totalMasuk}</td>
  ${r.keluar.map((v) => `<td>${v || "-"}</td>`).join("")}
  <td class="bold-orange">${r.totalKeluar}</td>
  <td>${r.retur}</td>
  <td>${r.ketRetur || "-"}</td>
  <td>${r.satuan}</td>
  <td>${stokAkhir}</td>
  <td>${r.stokFisik}</td>
  <td>${selisih}</td>
  <td>${r.ketSelisih || "-"}</td>
  <td>${r.stokFisik}</td>
  <td>
    <button class="btn-aksi edit"  onclick="editStok(${idx})" title="Edit">✏️</button>
    <button class="btn-aksi hapus" onclick="hapusStok(${idx})" title="Hapus">🗑️</button>
  </td>
</tr>`;
  });

  updateStats(rows);
}

function updateStats(rows) {
  const el = (id) => document.getElementById(id);
  if (el("total-awal"))
    el("total-awal").textContent = rows.reduce((s, r) => s + r.stokAwal, 0);
  if (el("total-masuk"))
    el("total-masuk").textContent = rows.reduce((s, r) => s + r.totalMasuk, 0);
  if (el("total-keluar"))
    el("total-keluar").textContent = rows.reduce(
      (s, r) => s + r.totalKeluar,
      0,
    );
  if (el("total-retur"))
    el("total-retur").textContent = rows.reduce((s, r) => s + r.retur, 0);
}

function showForm(mode, idx) {
  const box = document.getElementById("soFormBox");
  if (!box) return;
  box.style.display = "block";

  const title = document.getElementById("formTitle");
  if (title)
    title.textContent =
      mode === "edit" ? "Form Edit Stok Opname" : "Form Input Stok Opname";

  const editIdx = document.getElementById("editIndex");
  if (editIdx) editIdx.value = mode === "edit" && idx !== undefined ? idx : "";

  if (mode === "edit" && idx !== undefined) {
    const rows = getFiltered();
    const r = rows[idx];
    if (r) {
      setVal("f-nama", r.nama);
      setVal("f-satuan", r.satuan);
      setVal("f-stok-awal", r.stokAwal);
      setVal("f-total-masuk", r.totalMasuk);
      setVal("f-total-keluar", r.totalKeluar);
      setVal("f-retur", r.retur);
      setVal("f-ket-retur", r.ketRetur);
      setVal("f-stok-fisik", r.stokFisik);
      setVal("f-ket-selisih", r.ketSelisih);
    }
  } else {
    resetForm();
  }

  box.scrollIntoView({ behavior: "smooth" });
}

function hideForm() {
  const box = document.getElementById("soFormBox");
  if (box) box.style.display = "none";
  resetForm();
}

function resetForm() {
  document.querySelectorAll("#soFormBox input").forEach((el) => {
    el.value = el.type === "number" ? 0 : "";
  });
  const editIdx = document.getElementById("editIndex");
  if (editIdx) editIdx.value = "";
}

function hitungTotal() {
  const masukIds = [
    "f-masuk-s1",
    "f-masuk-s2",
    "f-masuk-r",
    "f-masuk-k",
    "f-masuk-j",
    "f-masuk-s3",
    "f-masuk-m",
  ];
  const keluarIds = [
    "f-keluar-s1",
    "f-keluar-s2",
    "f-keluar-r",
    "f-keluar-k",
    "f-keluar-j",
    "f-keluar-s3",
    "f-keluar-m",
  ];
  const tm = masukIds.reduce(
    (s, id) => s + (parseInt(document.getElementById(id)?.value) || 0),
    0,
  );
  const tk = keluarIds.reduce(
    (s, id) => s + (parseInt(document.getElementById(id)?.value) || 0),
    0,
  );
  setVal("f-total-masuk", tm);
  setVal("f-total-keluar", tk);
}

async function simpanData() {
  const nama = getVal("f-nama");
  const satuan = getVal("f-satuan");
  if (!nama || !satuan) {
    alert("Harap isi Nama Barang dan Satuan.");
    return;
  }

  const masukIds = [
    "f-masuk-s1",
    "f-masuk-s2",
    "f-masuk-r",
    "f-masuk-k",
    "f-masuk-j",
    "f-masuk-s3",
    "f-masuk-m",
  ];
  const keluarIds = [
    "f-keluar-s1",
    "f-keluar-s2",
    "f-keluar-r",
    "f-keluar-k",
    "f-keluar-j",
    "f-keluar-s3",
    "f-keluar-m",
  ];

  const payload = {
    nama_barang: nama,
    satuan,
    stok_awal: Number(getVal("f-stok-awal")) || 0,
    masuk_harian: masukIds.map((id) => Number(getVal(id)) || 0),
    keluar_harian: keluarIds.map((id) => Number(getVal(id)) || 0),
    total_masuk: Number(getVal("f-total-masuk")) || 0,
    total_keluar: Number(getVal("f-total-keluar")) || 0,
    retur: Number(getVal("f-retur")) || 0,
    ket_retur: getVal("f-ket-retur"),
    stok_fisik: Number(getVal("f-stok-fisik")) || 0,
    ket_selisih: getVal("f-ket-selisih"),
    bulan: activeMonth,
    tahun: activeYear,
  };

  const editIdxVal = getVal("editIndex");
  const isEdit = editIdxVal !== "";

  try {
    if (isEdit) {
      const rows = getFiltered();
      const r = rows[Number(editIdxVal)];
      if (r && r.rowId) {
        await apiRequest("/data/stok/" + r.rowId, "PUT", payload);
        alert("Data stok berhasil diperbarui.");
      }
    } else {
      await apiRequest("/data/stok", "POST", payload);
      alert("Data stok berhasil disimpan.");
    }
    hideForm();
    await loadStok();
  } catch (err) {
    alert("Gagal menyimpan data stok: " + err.message);
  }
}

function editStok(idx) {
  showForm("edit", idx);
}

async function hapusStok(idx) {
  const rows = getFiltered();
  const item = rows[idx];
  if (!item) return;
  if (!confirm('Hapus data stok "' + item.nama + '"?')) return;

  try {
    if (item.rowId) await apiRequest("/data/stok/" + item.rowId, "DELETE");
    alert("Data stok berhasil dihapus.");
    await loadStok();
  } catch (err) {
    alert("Gagal menghapus: " + err.message);
  }
}

function setMonth(el) {
  document
    .querySelectorAll(".month-tab")
    .forEach((b) => b.classList.remove("active"));
  el.classList.add("active");
  activeMonth = parseInt(el.dataset.month);
  renderTable();
}

function filterData() {
  activeYear =
    parseInt(document.getElementById("yearSelect")?.value) || activeYear;
  renderTable();
}

function colMap(colValues) {
  const map = {};
  if (Array.isArray(colValues))
    colValues.forEach((c) => {
      map[c.colIdx] = c.value;
    });
  return map;
}
function getVal(id) {
  const el = document.getElementById(id);
  return el ? el.value : "";
}
function setVal(id, val) {
  const el = document.getElementById(id);
  if (el) el.value = val ?? "";
}
