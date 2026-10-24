<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script type="text/javascript"> function toggleContent(contentId) {
        var content = document.getElementById(contentId);
        if (content.style.display === "none" || content.style.display === "") {
            content.style.display = "block";
        } else {
            content.style.display = "none";
        }
    }
</script>
<title>Invoice</title>
<style>
   @media print {
    @page {
        size: A4;
        margin: 12mm 15mm;
    }
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    header, footer, .no-print, .modal, .containers {
        display: none !important;
    }
    .container {
        max-width: 100% !important;
    }
    #invoice-header, .row, .tbl-wrapper, .tbl tr {
        page-break-inside: avoid;
    }
}

/* ── Mobile / App responsive ─────────────────────────────── */

/* Tablet */
@media (max-width: 900px) {
    .container { max-width: 100% !important; }
}

/* Mobile */
@media (max-width: 768px) {
    html, body {
        font-size: 13px !important;
        overflow-x: hidden;
        margin: 0 !important;
        padding: 0 !important;
    }
    .container {
        padding: 10px 12px !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        box-sizing: border-box;
    }

    /* ── Invoice header table ──────────────────────────── */
    /* Turn header into a stacked flex layout */
    table#invoice-header {
        display: flex !important;
        flex-direction: column !important;
        border: none !important;
        margin: 0 0 8px 0 !important;
    }
    table#invoice-header tr {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        border: none !important;
    }
    table#invoice-header td {
        display: block !important;
        border: none !important;
    }
    /* Logo: left, smaller */
    table#invoice-header td:nth-child(1) {
        flex: 1 1 auto !important;
    }
    table#invoice-header td:nth-child(1) img {
        width: 110px !important;
    }
    /* TAGIHAN text: grouped to the right near QR */
    table#invoice-header td:nth-child(2) {
        flex: 0 0 auto !important;
        text-align: right !important;
        padding-right: 6px !important;
    }
    /* QR: right, smaller */
    table#invoice-header td:nth-child(3) {
        flex: 0 0 auto !important;
    }
    table#invoice-header td:nth-child(3) svg,
    table#invoice-header td:nth-child(3) img {
        width: 50px !important;
        height: 50px !important;
    }
    /* Company name row */
    table#invoice-header tr:nth-child(2) td {
        width: 100% !important;
        font-size: 11px !important;
        padding: 2px 0 !important;
    }

    /* ── Kepada / Invoice info boxes ──────────────────── */
    .row {
        flex-direction: column !important;
        border-radius: 6px !important;
    }
    .row .box, .row .box1 {
        width: 100% !important;
        margin: 0 !important;
        padding: 12px 14px !important;
        box-sizing: border-box !important;
        border-bottom: 1px solid #eee;
    }
    .row .box:last-child, .row .box1:last-child {
        border-bottom: none !important;
    }
    .row .box p, .row .box1 p,
    .row .box .table-row, .row .box1 .table-row {
        line-height: 1.8 !important;
        font-size: 12px !important;
        margin-bottom: 2px !important;
    }
    .row .box h4, .row .box1 h4 {
        font-size: 13px !important;
        margin-bottom: 6px !important;
        color: #555;
    }
    .box2 {
        width: 100% !important;
        margin: 2px 0 !important;
        box-sizing: border-box !important;
    }

    /* ── Invoice items table ─────────────────────────── */
    .tbl-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 6px;
    }
    .tbl {
        font-size: 11px !important;
        min-width: 400px;
        margin: 0 !important;
    }
    .tbl th, .tbl td {
        white-space: nowrap;
        padding: 5px 6px !important;
        font-size: 11px !important;
    }

    /* ── Payment gateway cards ───────────────────────── */
    .payment-grid {
        gap: 8px !important;
        padding: 6px !important;
        margin-top: 10px !important;
    }
    .payment-card {
        width: calc(33% - 10px) !important;
        min-width: 90px !important;
        min-height: 110px !important;
        padding: 12px 8px !important;
        border-radius: 10px !important;
    }
    .payment-card i { font-size: 24px !important; margin-bottom: 6px !important; }
    .payment-label  { font-size: 11px !important; line-height: 1.3 !important; }
    .payment-subtitle { font-size: 9px !important; }

    /* ── VA / Payment info block ─────────────────────── */
    .payment-info { padding: 0 6px !important; }
    .payment-info h4 { font-size: 16px !important; }
    .payment-info img { max-width: 120px !important; }
    .btn1 { font-size: 13px !important; padding: 8px 16px !important; }

    /* ── Tripay VA logos ─────────────────────────────── */
    .row1 { flex-wrap: wrap !important; }
    .btn2 {
        width: calc(33.33% - 8px) !important;
        box-sizing: border-box !important;
        padding: 4px !important;
    }
    .btn2 img { width: 100% !important; height: auto !important; }

    /* ── Caption / status badge ──────────────────────── */
    caption { font-size: 16px !important; }
    a[style*="font-size: 20px"] { font-size: 16px !important; }
    a[style*="font-size: 14px"] { font-size: 12px !important; }

    /* ── Utility ─────────────────────────────────────── */
    .no-print { display: none !important; }
    p[align="right"] {
        text-align: left !important;
        font-size: 11px !important;
    }
    h4, p { font-size: 12px !important; }
    h5 { font-size: 14px !important; }
    strong { font-size: inherit !important; }

    /* ── Modals: full-width on mobile ────────────────── */
    .modal-dialog {
        width: 95% !important;
        margin: 10px auto !important;
    }
    .modal-header h4 { font-size: 15px !important; }
    .modal-body table { font-size: 11px !important; }

    /* ── Signature / footer ───────────────────────────── */
    .container > hr { margin: 12px 0 !important; }
}
</style>
<style>
    .payment-grid {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 14px;
        margin-top: 24px;
        padding: 10px 20px 20px;
    }

    .payment-card {
        flex: 0 0 auto;
        width: 150px;
        background: #ffffff;
        border: 1.5px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        text-align: center;
        padding: 20px 12px 16px;
        cursor: pointer;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        word-break: break-word;
        overflow-wrap: break-word;
        box-sizing: border-box;
    }

    .payment-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.10);
        border-color: #a5b4fc;
    }

    /* Warna ikon per gateway via data-provider */
    .payment-card[data-provider="xendit"] i   { color: #f43f5e; }
    .payment-card[data-provider="winpay"] i   { color: #0ea5e9; }
    .payment-card[data-provider="tripay"] i   { color: #22c55e; }
    .payment-card[data-provider="bumdes"] i   { color: #f59e0b; }
    .payment-card[data-provider="duitku"] i   { color: #8b5cf6; }
    .payment-card[data-provider="xendit"]:hover { border-color: #fda4af; background: #fff1f2; }
    .payment-card[data-provider="winpay"]:hover { border-color: #7dd3fc; background: #f0f9ff; }
    .payment-card[data-provider="tripay"]:hover { border-color: #86efac; background: #f0fdf4; }
    .payment-card[data-provider="bumdes"]:hover { border-color: #fcd34d; background: #fffbeb; }
    .payment-card[data-provider="duitku"]:hover { border-color: #c4b5fd; background: #faf5ff; }

    .payment-card i {
        font-size: 36px;
        margin-bottom: 10px;
        display: block;
        transition: transform 0.25s;
    }
    .payment-card:hover i { transform: scale(1.12); }

    .payment-label {
        font-size: 13px;
        font-weight: 700;
        color: #1f2937;
        line-height: 1.35;
        margin-bottom: 4px;
        word-break: break-word;
    }

    .payment-subtitle {
        font-size: 10px;
        color: #9ca3af;
        margin-top: 4px;
        font-weight: 400;
        line-height: 1.3;
    }

    .payment-fee {
        font-size: 10px;
        color: #ef4444;
        margin-top: 4px;
        font-weight: 600;
        background: #fef2f2;
        border-radius: 4px;
        padding: 2px 6px;
        display: inline-block;
    }
</style>
<style>

    .virtual-account {
          /*  border: 1px solid #ddd;
            border-radius: 5px;
            padding: 6px;
            margin: 2px;
            text-align: center;
            margin-bottom: 20px;*/
        }
        .virtual-account img {
            max-width: 100%;
            height: auto;
        }
        .btn2 {
          border: 1px solid #ddd;
          border-radius: 5px;
          padding: 6px;
          margin: 2px;
          text-align: center;
          margin-bottom: 20px;
          transition: transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
          background-color: transparent;
       }
        .btn2:hover {
           opacity: 0.85;
           transform: scale(1.05);
           box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
           background-color: transparent;
       }

       body{

        color:#333;
        text-align:left;
        font-size:10px;
        margin:2;
    }
/*.toggle-btn {
    width: 100%;
    background-color: #007bff;
    color: white;
    padding: 1px;
    font-size: 16px;
    text-align: left;
    border: none;
    cursor: pointer;
    outline: none;
    border-radius: 5px 5px 0 0;
}*/

.content {
    display: none;
    padding: 1px;
/*    background-color: #f1f1f1;*/
}
.btn {

  box-sizing: border-box;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  background-color: RoyalBlue; 
  border: 1px solid #FFFF;
  border-radius: 0.3em;
  color: #FFFF;
  cursor: pointer;
  display: flex;
  align-self: center;
  /* font-size: 1rem;*/
  font-weight: 200;
  line-height: 1;
  margin: 5px;
  padding: 0.5em 0.5em;
  text-decoration: none;
  text-align: center;
 /* text-transform: uppercase;
  font-family: 'Montserrat', sans-serif;*/
  
}

/* Darker background on mouse-over */
.btn:hover {
  background-color: RoyalBlue;
}
.container {
    margin: 0 auto;
    width: 100%;
    max-width: 900px;
    
    background-color: #fff;
}
.left-align {
    text-align: left;
}

.right-align {
    text-align: right;
}

.center-align {
    text-align: center;
}

.table-container {
 font-size: 14px;


}
.table-cell {
    flex: 1;
    
}
.font12 {

    font-size: 12px;
    text-decoration: inherit;
    

}
.box {
    flex: 1;
    width: 40%;
    margin: 8px;
    
    font-size: 10px;
    background-color: #fff;

}
.box1 {
    flex: 1;
    width: 40%;
    margin: 6px;
    font-size: 12px;
    background-color: #fff;

}
.box2 {
    flex: 1;
    /*width: 40%;*/
    margin: 3px;
    padding: 1px;
    align-content: center;
    font-size: 12px;
    /*background-color: #fff;*/
    /*display: flex;  Menggunakan flexbox */
           /* border: 1px solid #ddd;
            border-radius: 5px;
*/
}


.row {
    display: flex; /* Menggunakan flexbox */
    border: 1px solid #ddd;
    border-radius: 5px;
}
.row1 {
    display: flex; /* Menggunakan flexbox */
    flex: 1;
    
}
.btn2 {
  border: 1px solid #ddd;
  border-radius: 5px;
  padding: 6px;
  margin: 2px;
  text-align: center;
  margin-bottom: 20px;
  transition: transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
  background-color: transparent;
}
.btn3
{
    all: ;
}

.btn2:focus {
  outline: revert;
  background-color: transparent;
}


.tbl{

    border-radius: 1px;
    font-size: 10px;
}
.center-content {
 /* display: flex;*/
 justify-content: center; /* Horizontal center */
 /*align-items: center;    /* Vertical center */*/
 height: 100vh;         /* Optional: Full viewport height */
}
button {
  all: unset;
  cursor: pointer;
}

button:hover {
  background-color: transparent;
}
caption {
    font-size: 28px;
    margin-bottom: 10px;
}
.btn1 {
  display: inline-block;
  p/*adding: 3px 6px;*/
  font-size: 12px;
  padding: 10px;
  /*font-weight: bold;*/
  text-transform: uppercase;
  color: #fff;
  background-color: #4CAF50;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s ease;
  text-decoration: inherit; /* no underline */

}

.btn1:hover {
  background-color: #45a049;
}

table {

    width: 100%;
    border-radius: 5px;
    overflow: hidden; /* Memastikan radius berlaku pada border */
}

td,
tr,
th {
    padding: 5px;
}

th {
    background-color: #f0f0f0;
}

h4,
p {
    margin: 0px;
}
hr.pelangi{
    height: 1px; /*for me 1px fits perfect.*/
    outline: none;
    border: none;
/*    background: linear-gradient(90deg, rgb(255,0,0) 0%, rgb(255,184,0) 12%, rgb(194,255,0) 26%, rgb(0,255,188) 42%, rgb(0,151,255) 57%, rgb(21,0,255) 72%, rgb(201,0,250) 87%, rgb(255,0,138) 100%);*/
background: #a8323a;
}
table {
    width: 100%;

    margin: 20px 0;
    font-size: 16px;
    text-align: left;
}


table th, table td {
    padding: 8px;
}






.button1 {
    background-color: #007BFF;
    color: white;
    border: none;
    padding: 4px 6px;
    cursor: pointer;
    border-radius: 4px;
    font-size: 11px;
}

a {
    text-decoration: none;
}

a button {
    display: inline-block;
}

</style>
</style>

<style>
  /* ── Desain dokumen invoice (sama dengan print kasir); prefix inv- agar tidak bentrok dengan Bootstrap 3 ── */
  .inv-toolbar { max-width: 210mm; margin: 14px auto 0; display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap; padding: 0 8px; }
  .inv-btn { display: inline-block; border: 0; border-radius: 6px; padding: 8px 14px; font-size: 13px; cursor: pointer; color: #fff !important; background: #0d47a1; text-decoration: none !important; line-height: 1.3; }
  .inv-btn-green { background: #28a745; } .inv-btn-grey { background: #4b5563; }
  .inv-btn-teal { background: #ecfeff; color: #0f766e !important; border: 1px solid #99f6e4; }
  .inv-btn-orange { background: #fff7ed; color: #9a3412 !important; border: 1px solid #fdba74; }
  .inv-btn-light { background: #eaf2ff; color: #2f5fa8 !important; border: 1px solid #cfe0ff; }
  .inv-page { max-width: 210mm; margin: 12px auto 24px; background: #fff; padding: 14mm; box-shadow: 0 2px 12px rgba(0,0,0,.08); color: #1f2937; font: 13px/1.45 "Segoe UI", Roboto, Arial, sans-serif; }
  .inv-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding-bottom: 12px; border-bottom: 3px solid #0d47a1; }
  .inv-brand img { max-height: 56px; max-width: 160px; display: block; margin-bottom: 6px; }
  .inv-name { font-size: 18px; font-weight: 700; color: #0d47a1; }
  .inv-addr { font-size: 11px; color: #6b7280; max-width: 320px; }
  .inv-doc { display: flex; gap: 12px; align-items: flex-start; text-align: right; }
  .inv-title { font-size: 26px; font-weight: 800; letter-spacing: 2px; color: #0d47a1; line-height: 1; }
  .inv-subtitle { font-size: 11px; letter-spacing: 3px; color: #6b7280; }
  .inv-qr svg { width: 74px; height: 74px; display: block; }
  .inv-parties { display: flex; gap: 14px; margin: 16px 0; }
  .inv-card { flex: 1; background: #f3f6fb; border-radius: 8px; padding: 10px 12px; }
  .inv-label { font-size: 10px; font-weight: 700; letter-spacing: 1.5px; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; }
  .inv-big { font-size: 15px; font-weight: 700; }
  /* CSS lama halaman ini (Bootstrap 3 + aturan td/th global) jangan ikut mengubah ukuran teks invoice */
  .inv-page table { margin: 0; font-size: 13px; }
  .inv-page td, .inv-page th { font-size: inherit; line-height: 1.45; }
  .inv-page p { margin: 0; }
  .inv-card table td { padding: 1px 8px 1px 0; vertical-align: top; border: 0; font-size: 13px; }
  .inv-card table td:first-child { color: #6b7280; white-space: nowrap; }
  .inv-muted { font-size: 11px; color: #6b7280; }
  table.inv-items { width: 100%; border-collapse: collapse; }
  table.inv-items th { background: #0d47a1; color: #fff; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; padding: 8px 10px; text-align: left; border: 0; }
  table.inv-items td { padding: 8px 10px; border: 0; border-bottom: 1px solid #e5e7eb; }
  table.inv-items tbody tr:nth-child(even) td { background: #fafbfc; }
  .inv-num { text-align: right !important; white-space: nowrap; } .inv-center { text-align: center !important; }
  .inv-summary { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 10px; }
  .inv-summary table { min-width: 300px; border-collapse: collapse; }
  .inv-summary td { padding: 5px 10px; border: 0; }
  .inv-total td { border-top: 2px solid #1f2937 !important; font-size: 16px; font-weight: 800; padding-top: 8px; }
  .inv-stamp { align-self: center; margin-left: 24px; transform: rotate(-10deg); border: 3px solid currentColor; border-radius: 8px; padding: 4px 16px; text-align: center; opacity: .85; }
  .inv-stamp b { display: block; font-size: 20px; letter-spacing: 2px; white-space: nowrap; }
  .inv-stamp small { font-size: 10px; letter-spacing: 2px; }
  .inv-stamp-1 { color: #2e7d32; } .inv-stamp-2 { color: #6b7280; } .inv-stamp-0 { color: #c62828; }
  .inv-warning { margin-top: 16px; border-left: 4px solid #c62828; background: #fdecea; color: #8e1b1b; padding: 10px 12px; border-radius: 4px; }
  .inv-sign { margin-top: 22px; display: flex; justify-content: flex-end; }
  .inv-sign > div { text-align: center; min-width: 200px; }
  .inv-who { margin-top: 46px; font-weight: 700; border-top: 1px solid #1f2937; padding-top: 4px; }
  .inv-actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
  .inv-note { margin-top: 18px; padding: 10px 12px; background: #f9fafb; border-left: 4px solid #0d47a1; font-size: 12px; }
  .inv-foot { margin-top: 16px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #6b7280; text-align: center; }
  @media (max-width: 640px) {
    .inv-page { padding: 16px; }
    .inv-head, .inv-parties, .inv-summary { flex-direction: column; }
    .inv-doc { text-align: left; }
    .inv-summary table { min-width: 0; width: 100%; }
    .inv-stamp { margin: 0 0 10px 6px; }
  }
  @page { size: A4 portrait; margin: 10mm; }
  @media print {
    body { background: #fff !important; }
    .inv-page { margin: 0; padding: 0; box-shadow: none; max-width: none; }
    .inv-page * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

    /* Bootstrap 3 memaksa "* { color:#000 !important; background:transparent !important }" saat print.
       Selector di bawah lebih spesifik sehingga warna & latar desain invoice tetap tercetak. */
    .inv-page .inv-name, .inv-page .inv-title { color: #0d47a1 !important; }
    .inv-page .inv-addr, .inv-page .inv-subtitle, .inv-page .inv-label, .inv-page .inv-muted,
    .inv-page .inv-card table td:first-child, .inv-page .inv-foot { color: #6b7280 !important; }
    .inv-page .inv-card { background: #f3f6fb !important; }
    .inv-page table.inv-items th { background: #0d47a1 !important; color: #fff !important; }
    .inv-page table.inv-items tbody tr:nth-child(even) td { background: #fafbfc !important; }
    .inv-page .inv-head { border-bottom: 3px solid #0d47a1 !important; }
    .inv-page .inv-total td { border-top: 2px solid #1f2937 !important; }
    .inv-page .inv-stamp-1 { color: #2e7d32 !important; }
    .inv-page .inv-stamp-2 { color: #6b7280 !important; }
    .inv-page .inv-stamp-0 { color: #c62828 !important; }
    .inv-page .inv-stamp *, .inv-page .inv-warning * { color: inherit !important; }
    .inv-page .inv-warning { background: #fdecea !important; color: #8e1b1b !important; border-left: 4px solid #c62828 !important; }
    .inv-page .inv-note { background: #f9fafb !important; border-left: 4px solid #0d47a1 !important; }
  }
</style>
</head>
<body>

@if(session('error'))
<div class="no-print" style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;padding:12px 16px;margin:8px;border-radius:6px;font-size:14px;">
    <strong>&#9888; Gagal:</strong> {{ session('error') }}
</div>
@endif
@if(session('success'))
<div class="no-print" style="background:#d4edda;color:#155724;border:1px solid #c3e6cb;padding:12px 16px;margin:8px;border-radius:6px;font-size:14px;">
    <strong>&#10003;</strong> {{ session('success') }}
</div>
@endif

@php
  // ── Perhitungan (sama dengan versi sebelumnya; PPh dijumlahkan dari semua item) ──
  $taxRate = $suminvoice_number->tax ? $suminvoice_number->tax / 100 : 0;
  $pphRate = $suminvoice_number->pph ? $suminvoice_number->pph / 100 : 0;
  $invRows = [];
  $itemsTotal = 0;
  $pphTotal = 0;
  foreach ($invoice as $item) {
      $base = $item->qty * $item->amount;
      $tax = $base * $taxRate;
      $pphTotal += $base * $pphRate;
      $itemsTotal += $base + $tax;

      $description = $item->description;
      if ((int) $item->monthly_fee === 1 && !empty($item->periode) && strlen($item->periode) >= 6) {
          $m = substr($item->periode, -6, 2);
          if (is_numeric($m) && (int) $m >= 1 && (int) $m <= 12) {
              $description .= ' - ' . date('F', mktime(0, 0, 0, (int) $m, 10)) . ' ' . substr($item->periode, -4, 4);
          }
      }
      $invRows[] = ['description' => $description, 'price' => $item->amount + $item->amount * $taxRate, 'qty' => $item->qty, 'total' => $base + $tax];
  }
  $invTotal = $itemsTotal - $pphTotal;
  $subtotal = $invTotal; // masih dipakai modal pembayaran (Tripay dll.) di bagian bawah halaman
  $merchantFee = ($suminvoice_number->payment_status == 1 && $suminvoice_number->merchant_fee > 0) ? (float) $suminvoice_number->merchant_fee : 0;

  $invStatus = (int) $suminvoice_number->payment_status;
  $invStamp = [1 => ['LUNAS', 'PAID', '#2e7d32'], 2 => ['DIBATALKAN', 'CANCELLED', '#6b7280']][$invStatus] ?? ['BELUM TERBAYAR', 'UNPAID', '#c62828'];

  // Label metode bayar dari setting payment gateway (bisa dikustomisasi admin) → kasbank → nama gateway
  $paidVia = null; $paidNote = null;
  if ($invStatus === 1) {
      $pgSlug   = $suminvoice_number->payment_gateway ?? null;
      $pgRecord = $pgSlug ? \App\PaymentGateway::findForCurrentTenant($pgSlug) : null;
      $paidNote = ($pgRecord->settings['invoice_note'] ?? '') ?: null;
      $paidVia  = (($pgRecord->settings['invoice_label'] ?? '') ?: null)
          ?? ($suminvoice_number->kasbank->name ?? null)
          ?? ($pgSlug ? strtoupper($pgSlug) : 'Online');
  }

  $invRp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
  $dataTagihanUrl = url('/invoice/cst/' . $encryptedurl);
@endphp

<div class="inv-toolbar no-print">
  @if(!empty($suminvoice_number->file))
    <a class="inv-btn inv-btn-grey" href="{{ url('upload/tax/' . $suminvoice_number->file) }}">Faktur Pajak</a>
  @endif
  <a class="inv-btn inv-btn-green" href="{{ url('/suminvoice/' . $suminvoice_number->tempcode . '/pdf') }}"><i class="fas fa-file-pdf"></i>&nbsp; Download PDF</a>
  <button type="button" class="inv-btn" onclick="window.print()"><i class="fas fa-print"></i>&nbsp; Print</button>
</div>

<div class="inv-page" id="invoice_pr">
  <div class="inv-head">
    <div class="inv-brand">
      <img src="{{ tenant_img('logoinv.png', '/dashboard/dist/img/logoinv.png') }}" alt="{{ $companyName }}">
      <div class="inv-name">{{ $companyName }}</div>
      @if($companyAddress1)<div class="inv-addr">{{ $companyAddress1 }}</div>@endif
    </div>
    <div class="inv-doc">
      <div>
        <div class="inv-title">INVOICE</div>
        <div class="inv-subtitle">TAGIHAN</div>
      </div>
      <div class="inv-qr">{!! QrCode::size(74)->margin(0)->generate(url('suminvoice/' . $suminvoice_number->tempcode . '/viewinvoice')) !!}</div>
    </div>
  </div>

  <div class="inv-parties">
    <div class="inv-card">
      <div class="inv-label">Ditagihkan kepada</div>
      <div class="inv-big">{{ $customer->name }}</div>
      <table>
        <tr><td>ID Pelanggan</td><td><b>{{ $customer->customer_id }}</b></td></tr>
        @if($customer->address)<tr><td>Alamat</td><td>{{ $customer->address }}</td></tr>@endif
        @if($customer->phone)<tr><td>Telepon</td><td>{{ $customer->phone }}</td></tr>@endif
        @if(trim((string) $customer->npwp) !== '' && $customer->npwp !== '0')<tr><td>NPWP</td><td>{{ strtoupper($customer->npwp) }}</td></tr>@endif
      </table>
    </div>
    <div class="inv-card">
      <div class="inv-label">Pembayaran</div>
      <table>
        <tr><td>No. Invoice</td><td><b>#{{ $suminvoice_number->number }}</b></td></tr>
        <tr><td>Tanggal</td><td>{{ $suminvoice_number->date }}</td></tr>
        @if($invStatus === 1)
          <tr><td>Tanggal bayar</td><td>{{ $suminvoice_number->payment_date }}</td></tr>
          <tr><td>Dibayar via</td><td>{{ $paidVia }}@if($paidNote)<br><span class="inv-muted">{{ $paidNote }}</span>@endif</td></tr>
        @elseif($invStatus === 2)
          <tr><td>Status</td><td><b>Dibatalkan</b></td></tr>
        @else
          <tr><td>Jatuh tempo</td><td><b>{{ $suminvoice_number->due_date ?: '-' }}</b></td></tr>
        @endif
      </table>
    </div>
  </div>

  <table class="inv-items">
    <thead>
      <tr>
        <th style="width:36px">#</th>
        <th>Deskripsi</th>
        <th class="inv-num" style="width:110px">Harga</th>
        <th class="inv-center" style="width:50px">Qty</th>
        <th class="inv-num" style="width:120px">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invRows as $i => $row)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $row['description'] }}</td>
          <td class="inv-num">{{ number_format($row['price'], 0, ',', '.') }}</td>
          <td class="inv-center">{{ $row['qty'] }}</td>
          <td class="inv-num">{{ number_format($row['total'], 0, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="inv-summary">
    <div class="inv-stamp inv-stamp-{{ $invStatus }}"><b>{{ $invStamp[0] }}</b><small>{{ $invStamp[1] }}</small></div>
    <table>
      <tr><td>Subtotal</td><td class="inv-num">{{ $invRp($itemsTotal) }}</td></tr>
      @if($pphTotal > 0)
        <tr><td>PPh 23</td><td class="inv-num">- {{ $invRp($pphTotal) }}</td></tr>
      @endif
      <tr class="inv-total"><td>Total Tagihan</td><td class="inv-num">{{ $invRp($invTotal) }}</td></tr>
      @if($merchantFee > 0)
        <tr><td>Biaya Admin</td><td class="inv-num">{{ $invRp($merchantFee) }}</td></tr>
        <tr class="inv-total"><td>Total Dibayar</td><td class="inv-num">{{ $invRp($invTotal + $merchantFee) }}</td></tr>
      @endif
    </table>
  </div>

  @if($invStatus === 0 && $current_inv_status == 1)
    <div class="inv-warning">
      Anda masih memiliki tagihan yang belum terbayar (UNPAID) pada periode sebelumnya, silahkan melakukan pelunasan pembayaran
      tagihan tersebut terlebih dahulu. Untuk info lebih lanjut silahkan menghubungi team Payment kami.
      <div class="no-print" style="margin-top:8px"><a class="inv-btn" href="{{ $dataTagihanUrl }}">Lihat Data Tagihan</a></div>
    </div>
  @endif

  @if($invStatus === 1 || $invStatus === 2)
    <div class="inv-sign">
      <div>
        {{ $signature }}@if($invStatus === 1), {{ $suminvoice_number->payment_date }}@endif
        <div>Terima kasih,</div>
        @if($invStatus === 1)
          <div class="inv-who">{{ $suminvoice_number->user->name ?? $suminvoice_number->updated_by }}</div>
        @endif
      </div>
    </div>
  @endif

  @if($invStatus === 0 && $current_inv_status != 1 && !empty($suminvoice_number->payment_id))
    @php
      $activePaymentUrl = null;
      if (strpos((string) $suminvoice_number->payment_id, '|') !== false) {
          $parts = explode('|', (string) $suminvoice_number->payment_id, 2);
          $activePaymentUrl = $parts[1] ?? null;
      }
    @endphp
    <div class="inv-actions no-print">
      @if(!empty($activePaymentUrl))
        <a href="{{ $activePaymentUrl }}" target="_blank" rel="noopener noreferrer" class="inv-btn inv-btn-teal">
          <i class="fas fa-external-link-alt"></i>&nbsp; Lanjutkan Pembayaran Aktif
        </a>
      @endif
      <form method="POST" action="{{ url('/payment/reset') }}" style="margin:0;" onsubmit="return confirm('Reset pembayaran ini dan ganti metode bayar?');">
        @csrf
        <input type="hidden" name="id" value="{{ $suminvoice_number->id }}">
        <button type="submit" class="inv-btn inv-btn-orange"><i class="fas fa-random"></i>&nbsp; Ganti Metode Pembayaran</button>
      </form>
    </div>
  @endif

  @if(!($invStatus === 0 && $current_inv_status == 1))
    <div class="inv-actions no-print">
      <a href="{{ $dataTagihanUrl }}" class="inv-btn inv-btn-light"><i class="fas fa-file-invoice-dollar"></i>&nbsp; Data Tagihan</a>
    </div>
  @endif

  @if(!empty($invNote))
    <div class="inv-note">{!! nl2br(e($invNote)) !!}</div>
  @endif

  @if($companyAddress2)
    <div class="inv-foot">{{ $companyAddress2 }}</div>
  @endif
</div>


<div class="containers no-print">

    <!-- Modal Bumdes / Payment Point -->
    <div class="modal fade" id="myModal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Pembayaran Melalui Payment Point</h4>
                </div>
                <div class="modal-body">
                    <p><strong><a style='font-size: 11px; color: #000; text-decoration: none;'>
                    Pembayaran dapat dilakukan secara langsung dengan datang ke Payment point terdekat</a></strong></p>
                    <table border="0" width="100%" style='font-size: 11px;'>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Maps</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($merchants as $merchant)
                            <tr>
                                <td>{{ $merchant->name }}</td>
                                <td>{{ $merchant->address }}</td>
                                <td>
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $merchant->coordinate }}" target="_blank">
                                        <button class="button1">Maps</button>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Winpay Gateway -->
    <div class="modal fade" id="winpayModal" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" align="center">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Winpay Payment Gateway</h4>
                    <p>Bank Virtual Account & Retail Outlet</p>
                    <p style="color: #e74c3c;">* Biaya transaksi Rp 2.500</p>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="row1">
                            @if (isset($resultwinpay['data']) && $resultwinpay['data']['status'] === 'UNPAID')
                            <div style="text-align: center; padding: 20px;">
                                <h5>Pembayaran Aktif</h5>
                                <a href="{{ $resultwinpay['data']['redirect_url'] }}" class="btn1">Lihat Detail Pembayaran</a>
                            </div>
                            @else
                            <form action="{{ url('/create-winpay-va') }}" method="POST">
                                @csrf
                                <input type="hidden" name="id" value="{{ $suminvoice_number->id }}">
                                <div style="text-align: center; padding: 20px;">
                                    <p><strong>Metode Pembayaran Tersedia:</strong></p>
                                    <p>• Multi-Bank Virtual Account</p>
                                    <p>• Alfamart & Indomaret</p>
                                    <br>
                                    <button type="submit" class="btn1" style="font-size: 16px; padding: 12px 30px;">
                                        Lanjutkan Pembayaran
                                    </button>
                                </div>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tripay Gateway -->
    <div class="modal fade" id="tripayModal" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" align="center">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Tripay Payment Gateway</h4>
                    <p>Bank Virtual Account, E-Wallet, QRIS & Retail</p>
                    <p style="color: #e74c3c;">* Biaya transaksi bervariasi per metode</p>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <h5 align="center" style="margin-bottom: 20px;"><strong>Bank Virtual Account</strong></h5>
                        <div class="row1">
                            
                            {{-- BCA VA --}}
                            <form class="btn2" role="form" method="post" action="/tripay/create">
                                @csrf
                                <input type="hidden" name="name" value="{{ $customer->name}}">
                                <input type="hidden" name="customer_id" value="{{ $customer->customer_id}}">
                                <input type="hidden" name="email" value="{{ $customer->email}}">
                                <input type="hidden" name="phone" value="{{ $customer->phone}}">
                                <input type="hidden" name="method" value="BCAVA">
                                <input type="hidden" name="number" value="{{ $suminvoice_number->number }}">
                                <input type="hidden" name="tempcode" value="{{ $suminvoice_number->tempcode }}">
                                <input type="hidden" name="description" value="{{ $suminvoice_number->date }}">
                                <input type="hidden" name="amount" value="{{ $subtotal }}">
                                <button><div class="virtual-account">
                                    <h6>BCA Virtual Account</h6>
                                    <img style="width:200px" src="{{ url('img/bank/BCAVA.webp') }}" alt="BCA">
                                </div></button>
                            </form>

                            {{-- BRI VA --}}
                            <form class="btn2" role="form" method="post" action="/tripay/create">
                                @csrf
                                <input type="hidden" name="name" value="{{ $customer->name}}">
                                <input type="hidden" name="customer_id" value="{{ $customer->customer_id}}">
                                <input type="hidden" name="email" value="{{ $customer->email}}">
                                <input type="hidden" name="phone" value="{{ $customer->phone}}">
                                <input type="hidden" name="method" value="BRIVA">
                                <input type="hidden" name="number" value="{{ $suminvoice_number->number }}">
                                <input type="hidden" name="tempcode" value="{{ $suminvoice_number->tempcode }}">
                                <input type="hidden" name="description" value="{{ $suminvoice_number->date }}">
                                <input type="hidden" name="amount" value="{{ $subtotal }}">
                                <button><div class="virtual-account">
                                    <h6>BRI Virtual Account</h6>
                                    <img style="width:200px" src="{{ url('img/bank/BRIVA.webp') }}" alt="BRI">
                                </div></button>
                            </form>

                            {{-- BNI VA --}}
                            <form class="btn2" role="form" method="post" action="/tripay/create">
                                @csrf
                                <input type="hidden" name="name" value="{{ $customer->name}}">
                                <input type="hidden" name="customer_id" value="{{ $customer->customer_id}}">
                                <input type="hidden" name="email" value="{{ $customer->email}}">
                                <input type="hidden" name="phone" value="{{ $customer->phone}}">
                                <input type="hidden" name="method" value="BNIVA">
                                <input type="hidden" name="number" value="{{ $suminvoice_number->number }}">
                                <input type="hidden" name="tempcode" value="{{ $suminvoice_number->tempcode }}">
                                <input type="hidden" name="description" value="{{ $suminvoice_number->date }}">
                                <input type="hidden" name="amount" value="{{ $subtotal }}">
                                <button><div class="virtual-account">
                                    <h6>BNI Virtual Account</h6>
                                    <img style="width:200px" src="{{ url('img/bank/BNIVA.webp') }}" alt="BNI">
                                </div></button>
                            </form>

                            {{-- Mandiri VA --}}
                            <form class="btn2" role="form" method="post" action="/tripay/create">
                                @csrf
                                <input type="hidden" name="name" value="{{ $customer->name}}">
                                <input type="hidden" name="customer_id" value="{{ $customer->customer_id}}">
                                <input type="hidden" name="email" value="{{ $customer->email}}">
                                <input type="hidden" name="phone" value="{{ $customer->phone}}">
                                <input type="hidden" name="method" value="MANDIRIVA">
                                <input type="hidden" name="number" value="{{ $suminvoice_number->number }}">
                                <input type="hidden" name="tempcode" value="{{ $suminvoice_number->tempcode }}">
                                <input type="hidden" name="description" value="{{ $suminvoice_number->date }}">
                                <input type="hidden" name="amount" value="{{ $subtotal }}">
                                <button><div class="virtual-account">
                                    <h6>Mandiri Virtual Account</h6>
                                    <img style="width:200px" src="{{ url('img/bank/MANDIRIVA.webp') }}" alt="Mandiri">
                                </div></button>
                            </form>

                        </div>

                        <h5 align="center" style="margin: 20px 0;"><strong>QRIS & Retail Outlet</strong></h5>
                        <div class="row1">

                            {{-- QRIS --}}
                            <form class="btn2" role="form" method="post" action="/tripay/create">
                                @csrf
                                <input type="hidden" name="name" value="{{ $customer->name}}">
                                <input type="hidden" name="customer_id" value="{{ $customer->customer_id}}">
                                <input type="hidden" name="email" value="{{ $customer->email}}">
                                <input type="hidden" name="phone" value="{{ $customer->phone}}">
                                <input type="hidden" name="method" value="QRIS">
                                <input type="hidden" name="number" value="{{ $suminvoice_number->number }}">
                                <input type="hidden" name="tempcode" value="{{ $suminvoice_number->tempcode }}">
                                <input type="hidden" name="description" value="{{ $suminvoice_number->date }}">
                                <input type="hidden" name="amount" value="{{ $subtotal }}">
                                <button><div class="virtual-account">
                                    <h6>QRIS</h6>
                                    <img style="width:200px" src="{{ url('img/bank/QRIS.webp') }}" alt="QRIS">
                                </div></button>
                            </form>

                            {{-- Alfamart --}}
                            <form class="btn2" role="form" method="post" action="/tripay/create">
                                @csrf
                                <input type="hidden" name="name" value="{{ $customer->name}}">
                                <input type="hidden" name="customer_id" value="{{ $customer->customer_id}}">
                                <input type="hidden" name="email" value="{{ $customer->email}}">
                                <input type="hidden" name="phone" value="{{ $customer->phone}}">
                                <input type="hidden" name="method" value="ALFAMART">
                                <input type="hidden" name="number" value="{{ $suminvoice_number->number }}">
                                <input type="hidden" name="tempcode" value="{{ $suminvoice_number->tempcode }}">
                                <input type="hidden" name="description" value="{{ $suminvoice_number->date }}">
                                <input type="hidden" name="amount" value="{{ $subtotal }}">
                                <button><div class="virtual-account">
                                    <h6>Alfamart</h6>
                                    <img style="width:200px" src="{{ url('img/bank/ALFAMART.webp') }}" alt="Alfamart">
                                </div></button>
                            </form>

                            {{-- Indomaret --}}
                            <form class="btn2" role="form" method="post" action="/tripay/create">
                                @csrf
                                <input type="hidden" name="name" value="{{ $customer->name}}">
                                <input type="hidden" name="customer_id" value="{{ $customer->customer_id}}">
                                <input type="hidden" name="email" value="{{ $customer->email}}">
                                <input type="hidden" name="phone" value="{{ $customer->phone}}">
                                <input type="hidden" name="method" value="INDOMARET">
                                <input type="hidden" name="number" value="{{ $suminvoice_number->number }}">
                                <input type="hidden" name="tempcode" value="{{ $suminvoice_number->tempcode }}">
                                <input type="hidden" name="description" value="{{ $suminvoice_number->date }}">
                                <input type="hidden" name="amount" value="{{ $subtotal }}">
                                <button><div class="virtual-account">
                                    <h6>Indomaret</h6>
                                    <img style="width:200px" src="{{ url('img/bank/INDOMARET.webp') }}" alt="Indomaret">
                                </div></button>
                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@php
$duitkuGw = $gateways->where('provider','duitku')->first();
$duitkuMethods = $duitkuGw ? ($duitkuGw->settings['active_methods'] ?? ['I1']) : [];
$duitkuMethodLabels = [
    'I1' => ['name'=>'BNI Virtual Account',     'group'=>'Virtual Account', 'icon'=>'fas fa-building'],
    'BC' => ['name'=>'BCA Virtual Account',     'group'=>'Virtual Account', 'icon'=>'fas fa-building'],
    'M2' => ['name'=>'Mandiri Virtual Account', 'group'=>'Virtual Account', 'icon'=>'fas fa-building'],
    'BR' => ['name'=>'BRI Virtual Account',     'group'=>'Virtual Account', 'icon'=>'fas fa-building'],
    'BT' => ['name'=>'Permata Virtual Account', 'group'=>'Virtual Account', 'icon'=>'fas fa-building'],
    'B1' => ['name'=>'CIMB Niaga VA',           'group'=>'Virtual Account', 'icon'=>'fas fa-building'],
    'BV' => ['name'=>'BSI Virtual Account',     'group'=>'Virtual Account', 'icon'=>'fas fa-building'],
    'SP' => ['name'=>'QRIS (Shopee Pay)',        'group'=>'QRIS & E-Wallet', 'icon'=>'fas fa-qrcode'],
    'NQ' => ['name'=>'QRIS (Nobu)',             'group'=>'QRIS & E-Wallet', 'icon'=>'fas fa-qrcode'],
    'DA' => ['name'=>'DANA',                    'group'=>'QRIS & E-Wallet', 'icon'=>'fas fa-wallet'],
    'OV' => ['name'=>'OVO',                     'group'=>'QRIS & E-Wallet', 'icon'=>'fas fa-wallet'],
    'SA' => ['name'=>'ShopeePay Apps',          'group'=>'QRIS & E-Wallet', 'icon'=>'fas fa-wallet'],
    'FT' => ['name'=>'Alfamart / Pegadaian',    'group'=>'Ritel',           'icon'=>'fas fa-store'],
    'IR' => ['name'=>'Indomaret',               'group'=>'Ritel',           'icon'=>'fas fa-store'],
];
$duitkuGroups = [];
foreach ($duitkuMethods as $code) {
    if (isset($duitkuMethodLabels[$code])) {
        $group = $duitkuMethodLabels[$code]['group'];
        $duitkuGroups[$group][$code] = $duitkuMethodLabels[$code];
    }
}
@endphp

{{-- duitkuModal dihapus: pilih metode sudah ada di halaman checkout Duitku --}}

</div>

<script>
// Samakan tinggi semua payment card dengan card tertinggi
function equalizePaymentCards() {
    document.querySelectorAll('.payment-grid').forEach(function(grid) {
        var cards = grid.querySelectorAll('.payment-card');
        var maxH = 0;
        cards.forEach(function(c) { c.style.height = ''; }); // reset dulu
        cards.forEach(function(c) { maxH = Math.max(maxH, c.offsetHeight); });
        cards.forEach(function(c) { c.style.height = maxH + 'px'; });
    });
}
document.addEventListener('DOMContentLoaded', equalizePaymentCards);
window.addEventListener('resize', equalizePaymentCards);
</script>
</body>
</html>
