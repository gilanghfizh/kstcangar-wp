

document.addEventListener("DOMContentLoaded", async () => {
  if (!localStorage.getItem("kst_access_token")) return;

  showLoadingStates();
  await loadSummary();
  await loadChartStok();
  await loadChartTren();
  await loadAktivitasStok();
  await loadBookingTerkini();
});

function showLoadingStates() {
  const ids = ["stat-item-stok", "stat-stok", "stat-booking", "stat-pendapatan"];
  ids.forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.innerText = "...";
  });
}

async function loadSummary() {
  try {
    const res = await apiRequest("/data/summary");
    // res = { code, createdAt, updatedAt, data: { typeName, value: "<JSON string>" } }
    const parsed = JSON.parse(res.data.value);

    const booking  = parsed.booking  || {};
    const keuangan = parsed.keuangan || {};

    const elItemStok = document.getElementById("stat-item-stok");
    if (elItemStok) elItemStok.innerText = booking.pending ?? 0;

    const elStok = document.getElementById("stat-stok");
    if (elStok) elStok.innerText = (booking.confirmed_month ?? 0).toLocaleString("id-ID");

    const elBooking = document.getElementById("stat-booking");
    if (elBooking) elBooking.innerText = booking.today ?? 0;

    const elPendapatan = document.getElementById("stat-pendapatan");
    if (elPendapatan) elPendapatan.innerText = formatRupiah(keuangan.income_today ?? 0);

  } catch (err) {
    console.warn("Summary gagal:", err);
  }
}

async function loadChartStok() {
  const canvas = document.getElementById("chartStok");
  if (!canvas) return;

  let labels = [];
  let values = [];

  try {
    const res   = await apiRequest("/data/stok");
    const items = res?.data?.items || [];

    items.forEach((item) => {
      const cols = colMap(item.colValues);
      labels.push(cols[0] ?? "-");       // colIdx 0 = Nama Barang
      values.push(Number(cols[7] ?? 0)); // colIdx 7 = Stok Fisik
    });
  } catch (err) {
    console.warn("Data chart stok tidak tersedia:", err);
  }

  new Chart(canvas, {
    type: "bar",
    data: {
      labels: labels.length ? labels : ["Tidak ada data"],
      datasets: [{
        label: "Stok Fisik",
        data: values.length ? values : [0],
        backgroundColor: "rgba(5, 150, 105, 0.7)",
        borderColor: "#059669",
        borderWidth: 1,
        borderRadius: 4,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true } },
    },
  });
}

async function loadChartTren() {
  const canvas = document.getElementById("chartTren");
  if (!canvas) return;

  let trenLabels     = [];
  let trenPendapatan = [];

  try {
    const res = await apiRequest("/data/keuangan/rekap");
    // res = { code, data: { typeName:'timeSeries', value: [{timestamp, value}] } }
    const series = res?.data?.value || [];

    series.forEach((point) => {
      trenLabels.push(String(point.timestamp ?? "").substring(0, 10));
      trenPendapatan.push(Number(point.value ?? 0));
    });
  } catch (err) {
    console.warn("Data chart tren tidak tersedia:", err);
  }

  new Chart(canvas, {
    type: "line",
    data: {
      labels: trenLabels.length ? trenLabels : ["Tidak ada data"],
      datasets: [{
        label: "Net Pendapatan (Rp)",
        data: trenPendapatan.length ? trenPendapatan : [0],
        borderColor: "#059669",
        backgroundColor: "rgba(5, 150, 105, 0.1)",
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 6,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "top" },
        tooltip: {
          callbacks: {
            label: (ctx) => " " + formatRupiah(ctx.parsed.y),
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { callback: (val) => formatRupiahShort(val) },
        },
      },
    },
  });
}

async function loadAktivitasStok() {
  const list = document.querySelector(".activity-list");
  if (!list) return;

  try {
    const res   = await apiRequest("/data/stok");
    const items = res?.data?.items || [];
    if (!items.length) return;

    list.innerHTML = "";
    items.slice(0, 4).forEach((item) => {
      const cols   = colMap(item.colValues);
      const nama   = cols[0] ?? "-";         // colIdx 0 = Nama Barang
      const satuan = cols[1] ?? "";          // colIdx 1 = Satuan
      const masuk  = Number(cols[3] ?? 0);  // colIdx 3 = Masuk
      const keluar = Number(cols[4] ?? 0);  // colIdx 4 = Keluar
      const retur  = Number(cols[5] ?? 0);  // colIdx 5 = Retur

      let dotClass = "green", label = "Stok masuk", valClass = "plus", valText = "+" + masuk + " " + satuan;

      if (retur > 0) {
        dotClass = "red"; label = "Retur"; valClass = "minus"; valText = "-" + retur + " " + satuan;
      } else if (keluar > 0 && masuk === 0) {
        dotClass = "yellow"; label = "Stok keluar"; valClass = "minus"; valText = "-" + keluar + " " + satuan;
      }

      list.innerHTML += `
<li>
  <span class="dot ${dotClass}"></span>
  <div class="act-info"><strong>${nama}</strong><small>${label}</small></div>
  <span class="act-val ${valClass}">${valText}</span>
</li>`;
    });
  } catch (err) {
    console.warn("Aktivitas stok tidak tersedia:", err);
  }
}

async function loadBookingTerkini() {
  const list = document.querySelector(".booking-list");
  if (!list) return;

  try {
    const res   = await apiRequest("/data/booking");
    const items = res?.data?.items || [];
    if (!items.length) return;

    list.innerHTML = "";
    items.slice(0, 3).forEach((item) => {
      const cols    = colMap(item.colValues);
      const nama    = cols[0] ?? "-";  // colIdx 0 = Nama Customer
      const layanan = cols[2] ?? "-";  // colIdx 2 = Jenis Layanan
      const tanggal = cols[3] ?? "-";  // colIdx 3 = Tanggal
      const status  = cols[5] ?? "-";  // colIdx 5 = Status

      const sl = String(status).toLowerCase();
      let badgeClass = "belum", badgeLabel = status;
      if (sl === "confirmed") { badgeClass = "lunas"; badgeLabel = "Lunas"; }
      else if (sl === "pending") { badgeClass = "dp"; badgeLabel = "Pending"; }
      else if (sl === "cancelled") { badgeClass = "belum"; badgeLabel = "Batal"; }

      list.innerHTML += `
<li>
  <div class="avatar">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
    </svg>
  </div>
  <div class="booking-info"><strong>${nama}</strong><small>${layanan} · ${tanggal}</small></div>
  <span class="badge ${badgeClass}">${badgeLabel}</span>
</li>`;
    });
  } catch (err) {
    console.warn("Booking terkini tidak tersedia:", err);
  }
}


function colMap(colValues) {
  const map = {};
  if (Array.isArray(colValues)) colValues.forEach((c) => { map[c.colIdx] = c.value; });
  return map;
}

function formatRupiah(angka) {
  const n = Number(angka) || 0;
  if (n >= 1000000) return "Rp " + (n / 1000000).toFixed(1).replace(".", ",") + " Jt";
  return "Rp " + n.toLocaleString("id-ID");
}

function formatRupiahShort(angka) {
  const n = Number(angka) || 0;
  if (n >= 1000000) return (n / 1000000).toFixed(1) + "Jt";
  if (n >= 1000) return (n / 1000).toFixed(0) + "K";
  return String(n);
}
