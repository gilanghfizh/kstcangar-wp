document.addEventListener(
"DOMContentLoaded",
async ()=>{

try{

const raw=
await apiRequest(
"/data/stok"
);

const data=
raw?.response?.data?.items
||[];

renderStok(
data
);

updateCards(
data
);

}catch(err){

console.error(
err
);

}

});

function updateCards(
data
){

document.getElementById(
"total-awal"
).innerText=
data.reduce(
(a,b)=>
a+
(Number(
b.stok_awal
)||0),
0
);

document.getElementById(
"total-masuk"
).innerText=
data.reduce(
(a,b)=>
a+
(Number(
b.total_masuk
)||0),
0
);

document.getElementById(
"total-keluar"
).innerText=
data.reduce(
(a,b)=>
a+
(Number(
b.total_keluar
)||0),
0
);

document.getElementById(
"total-retur"
).innerText=
data.reduce(
(a,b)=>
a+
(Number(
b.retur
)||0),
0
);

}

function renderStok(
data
){

const tbody=
document.getElementById(
"soTableBody"
);

if(!tbody)return;

tbody.innerHTML="";

if(!data.length){

tbody.innerHTML=`
<tr>
<td colspan="50"
style="padding:30px;text-align:center">
Belum ada data stok
</td>
</tr>
`;

return;

}

data.forEach(
(item)=>{

tbody.innerHTML+=`
<tr>

<td>${item.nama_barang||"-"}</td>

<td>${item.stok_awal||0}</td>

<td colspan="7">-</td>

<td>${item.total_masuk||0}</td>

<td colspan="7">-</td>

<td>${item.total_keluar||0}</td>

<td>${item.retur||0}</td>

<td>${item.ket_retur||"-"}</td>

<td>${item.satuan||"-"}</td>

<td>${item.stok_akhir||0}</td>

<td>${item.stok_fisik||0}</td>

<td>0</td>

<td>-</td>

<td>${item.stok_fisik||0}</td>

<td>-</td>

</tr>
`;

}
);

}