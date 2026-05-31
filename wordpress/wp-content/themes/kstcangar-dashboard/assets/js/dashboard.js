document.addEventListener(
"DOMContentLoaded",
async ()=>{

try{

const raw =
await apiRequest(
"/data/summary"
);

const parsed =
JSON.parse(
raw.data.value
);

console.log(parsed);

const booking =
document.getElementById(
"stat-booking"
);

if(booking){

booking.innerText =
parsed.booking?.today ?? 0;

}

const pendapatan =
document.getElementById(
"stat-pendapatan"
);

if(pendapatan){

pendapatan.innerText =
"Rp " +
(
parsed.keuangan?.income_today ?? 0
).toLocaleString(
"id-ID"
);

}

const stok =
document.getElementById(
"stat-item-stok"
);

if(stok){

stok.innerText =
parsed.booking?.pending ?? 0;

}

const stokTotal =
document.getElementById(
"stat-stok"
);

if(stokTotal){

stokTotal.innerText =
parsed.booking?.confirmed_month ?? 0;

}


/*
CHART STOK
*/

const stokCanvas =
document.getElementById(
"chartStok"
);

if(stokCanvas){

new Chart(
stokCanvas,
{
type:"bar",
data:{
labels:[
"Pending"
],
datasets:[{
data:[
parsed.booking?.pending ?? 0
]
}]
},
options:{
responsive:true,
maintainAspectRatio:false
}
}
);

}


/*
CHART TREN
*/

const trenCanvas =
document.getElementById(
"chartTren"
);

if(trenCanvas){

new Chart(
trenCanvas,
{
type:"line",
data:{
labels:[
"Today"
],
datasets:[
{
label:"Booking",
data:[
parsed.booking?.today ?? 0
]
},
{
label:"Income",
data:[
parsed.keuangan?.income_today ?? 0
]
}
]
},
options:{
responsive:true,
maintainAspectRatio:false
}
}
);

}

}catch(err){

console.error(
"dashboard error",
err
);

}

});