document.addEventListener(
"DOMContentLoaded",
async ()=>{

try{

const raw=
await apiRequest(
"/data/booking"
);

const data=
raw?.response?.data?.items
||[];

renderBooking(
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
"stat-total-booking"
).innerText=
data.length;

document.getElementById(
"stat-pendapatan"
).innerText=
"Rp "+
data.reduce(
(a,b)=>
a+
(Number(
b.harga
)||0),
0
).toLocaleString(
"id-ID"
);

document.getElementById(
"stat-lunas"
).innerText=
data.filter(
x=>
x.status==="Lunas"
).length;

document.getElementById(
"stat-belum"
).innerText=
data.filter(
x=>
x.status!=="Lunas"
).length;

}

function renderBooking(
data
){

const tbody=
document.getElementById(
"bookingTableBody"
);

if(!tbody)return;

tbody.innerHTML="";

if(!data.length){

tbody.innerHTML=`
<tr>
<td colspan="50"
style="padding:30px;text-align:center">
Belum ada data booking
</td>
</tr>
`;

return;

}

data.forEach(
(item,index)=>{

tbody.innerHTML+=`
<tr>

<td>${index+1}</td>

<td>${item.nama||"-"}</td>

<td>${item.jumlah_tamu||0}</td>

<td>${item.checkin||"-"}</td>

<td>${item.checkout||"-"}</td>

<td>${item.kontak||"-"}</td>

<td>${item.tipe||"-"}</td>

<td>${item.unit||"-"}</td>

<td>Rp ${(item.harga||0).toLocaleString("id-ID")}</td>

<td>${item.status||"-"}</td>

<td>${item.bukti||"-"}</td>

<td>${item.invoice||"-"}</td>

<td>${item.additional||"-"}</td>

<td>-</td>

</tr>
`;

}
);

}