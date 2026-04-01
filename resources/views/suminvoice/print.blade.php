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
        margin: 50;
    }
    body {
        margin: 1;
        padding: 1;
    }
    header, footer, .no-print {
        display: none !important;
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
  background-color: transparent !important;
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

</head>
<body>

@if(session('error'))
<div style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;padding:12px 16px;margin:8px;border-radius:6px;font-size:14px;">
    <strong>&#9888; Gagal:</strong> {{ session('error') }}
</div>
@endif
@if(session('success'))
<div style="background:#d4edda;color:#155724;border:1px solid #c3e6cb;padding:12px 16px;margin:8px;border-radius:6px;font-size:14px;">
    <strong>&#10003;</strong> {{ session('success') }}
</div>
@endif

    <div class="container" id="invoice_pr">
     <div  >
        <button style="float:right;" class="btn no-print" onclick="window.print()">Print</button>
        @php
        if(!empty($suminvoice_number->file))
        {
          $url=url('upload/tax').'/'.$suminvoice_number->file;

          echo' <button class="btn"><a  style="float:right; text-decoration: none; color: #FFFF" href='.$url.'> Faktur pajak </a></button>';
          
      }
      @endphp


      
      
  </div> 
  
  <table id="invoice-header" style="border: none">
    <tr style="border: none">
      <td align="left" colspan="3">
        <img width="150px" src="{{ tenant_img('logoinv.png', '/dashboard/dist/img/logoinv.png') }}">
    </td>
    <td align="left">



        <p><strong><u>TAGIHAN</u></strong>
        </p>
        <p>
          <strong><i>INVOICE</i></strong>
      </p>
  </td>


  <td align="right" width="70px">
    {!! QrCode::size(60)->generate(url('suminvoice/'.$suminvoice_number->tempcode.'/viewinvoice')); !!}
</td>



</tr>
<tr>
    <td align="left" >
       <strong> {{$companyName}}</strong>
       
    </td>
</tr>

</table> 



<div>
    <div class="container t">
        <div class="row">
            <div class="box ">
                <h4 >Kepada:</h4>
                <p><strong>{{ $customer->customer_id }}<br> {{ $customer->name }}</strong><br>
                    {{ $customer->address }}<br>
                    {{ $customer->phone }}<br>
                    {{ $customer->npwp }}
                </p>
            </div>
            <div class="box">

                <div class="table-row">
                    <div class="table-cell right-align">Invoice No :<strong> #{{$suminvoice_number->number }}</strong></div>

                </div>
                <div class="table-row">
                    <div class="table-cell right-align">Inv date: {{ $suminvoice_number->date }}</div>

                </div>
                <div class="table-row">
                   <?php
                   if ($suminvoice_number->payment_status == 1) {
                    // Ambil label & keterangan dari setting payment gateway (bisa dikustomisasi admin)
                    $pgSlug    = $suminvoice_number->payment_gateway ?? null;
                    $pgRecord  = $pgSlug ? \App\PaymentGateway::findForCurrentTenant($pgSlug) : null;
                    $pgLabel   = ($pgRecord->settings['invoice_label'] ?? '') ?: null;
                    $pgNote    = ($pgRecord->settings['invoice_note']  ?? '') ?: null;

                    // Prioritas: invoice_label dari setting → kasbank name → nama gateway uppercase
                    $kasbankName = $pgLabel
                        ?? ($suminvoice_number->kasbank->name ?? null)
                        ?? ($pgSlug ? strtoupper($pgSlug) : 'Online');

                    $displayLine = $kasbankName;
                    if ($pgNote) {
                        $displayLine .= '<br><span style="font-size:10px;color:#555;">' . htmlspecialchars($pgNote) . '</span>';
                    }

                    echo '<div class="table-cell right-align"> Payment date: '. $suminvoice_number->payment_date.'</br>'.$displayLine.' </div>';
                } else {
                    echo '<div class="table-cell right-align">Due date:  '.$suminvoice_number->due_date.' </div>';
                }
                ?>

            </div>
            <div class="table-row">
                <?php
                if ($suminvoice_number->payment_status == 1) {
                    echo '<div class="table-cell right-align"> <a style="font-size: 20px; color: green;">PAID</a></div>';

                    echo ' <div class="table-cell right-align"><a style="font-size: 11px; color: green;">(SUDAH TERBAYAR)</a></div>';
                }elseif ($suminvoice_number->payment_status == 2) {
                    echo '<div class="table-cell right-align"> <a style="font-size: 20px; color: grey;">CANCEL</a></div>';

                    echo ' <div class="table-cell right-align"><a style="font-size: 11px; color: grey;">(DIBATALKAN)</a></div>';
                }

                else {
                    echo '<div class="table-cell right-align"><a style="font-size: 20px; color: red;">UNPAID</a></div>';

                    echo ' <div class="table-cell right-align"><a style="font-size: 11px; color: red;">(BELUM TERBAYAR)</a></div>';
                }
                ?>
            </div>

        </div>
    </div>
    <p style="padding: 0px">
    </p>
</div>
<div class="tbl-wrapper">
<table class="tbl">


    <tbody>
        <tr bgcolor="#CBCAC7"  >
            <th style="border: 1px solid #333">#</th>
            <th style="border: 1px solid #333">Description</th>
            <th style="border: 1px solid #333">Period</th>
            <th style="border: 1px solid #333">Type</th>
            <th style="border: 1px solid #333">Price</th>
            <th style="border: 1px solid #333">Qty</th>
            <th style="border: 1px solid #333">Sub Total</th>
        </tr>
        @php 
        $subtotal=0; 
        if ($suminvoice_number->tax == null){
            $taxfee =0;
        }
        else
        {
            $taxfee = $suminvoice_number->tax/100;
        }



        @endphp


        @foreach( $invoice as $invoice)
        @php 
        $totalwutax = ($invoice->qty * $invoice->amount);
        $totaltax = $totalwutax * $taxfee;
        $pph = $totalwutax * $suminvoice_number->pph/100;
        $taxitem = $invoice->amount * $taxfee;
        $subtotal = $subtotal + ($totalwutax + $totaltax) - $pph;

        $periodLabel = '-';
        if (!empty($invoice->periode) && strlen($invoice->periode) >= 6) {
            $strmonth = substr($invoice->periode, -6, 2);
            $stryear = substr($invoice->periode, -4, 4);

            if (is_numeric($strmonth) && (int) $strmonth >= 1 && (int) $strmonth <= 12) {
                $periodLabel = date("F", mktime(0, 0, 0, (int) $strmonth, 10)).' '.$stryear;
            }
        }

        $monthlyLabel = ((int) $invoice->monthly_fee === 1) ? 'Monthly' : 'General';
        $description = $invoice->description;
        if ((int) $invoice->monthly_fee === 1 && $periodLabel !== '-') {
            $description .= ' - '.$periodLabel;
        }





      @endphp
      <tr style="border: 1px solid #333" >
          <th style="border: 1px solid #333" scope="row">{{ $loop->iteration }}</th>
          {{--   <td>{{ $invoice->created_at }}</td> --}}
          <td style="border: 1px solid #333">{{ $description }}</td>
          <td style="border: 1px solid #333">{{ $periodLabel }}</td>
          <td align="center" style="border: 1px solid #333">{{ $monthlyLabel }}</td>
          <td style="border: 1px solid #333">{{ number_format($invoice->amount + $taxitem, 0, ',', '.') }}</td>
          <input type="hidden" name="invoice_item[]" value={{ $invoice->id }}>

          <td align="center" style="border: 1px solid #333">{{ $invoice->qty }}</td>
          @php
          $isubtotal =$invoice->qty * $invoice->amount;
          $tax = $isubtotal * $taxfee;

          $itotal = $isubtotal + $tax;
          @endphp
          <td style="border: 1px solid #333" align="right">{{ number_format($itotal, 0, ',', '.')  }}</td>



      </tr>

      @endforeach
      <tr>
          {{-- <td colspan="2" style="border: 0px solid #333" ></td> <td colspan="2" style="border: 1px solid #333"> <strong> Subtotal</strong></td> --}}

          {{-- <td style="border: 1px solid #333">
              <strong>Rp. {{ number_format($subtotal, 0, ',', '.') }} </strong> </td></tr> --}}
              {{--  @php 


              if ($suminvoice_number->tax == null){
                $taxfee =0;
            }
            else
            {
                $taxfee = $suminvoice_number->tax;
            }

            $tax = $subtotal * $taxfee/100;
            $pph = $subtotal * $suminvoice_number->pph/100;

            $total = $subtotal + $tax - $pph;

            @endphp --}}
            @if ( $pph != 0)

            <tr>
                <td colspan="6" style="border: 1px solid #333" >Pph 23</td>
                <td style="border: 1px solid #333" align="right"><strong id="total">Rp. -{{ number_format($pph, 0, ',', '.') }} </strong></td>
                <tr>
                    @else
                    @endif
                    <td colspan="6" style="border: 1px solid #333" >Total Tagihan Bulan ini / Current Charges <br> <p >Harga yang tertera pada invoice sudah termasuk PPN</p></td>
                    <td style="border: 1px solid #333" align="right"><strong id="total">Rp. {{ number_format($subtotal, 0, ',', '.') }} </strong></td>
                    {{--  <td colspan="2" style="border: 1px solid #333"> <strong> Tax Ppn ({{$taxfee}}%)</strong> --}}
                        {{-- <input type="text" name="subtotal" id="subtotal" value={{$subtotal}} >--}}
                        {{-- <input type="hidden" name="tax" id="tax" value={{$taxfee}} ></td>  --}}
                        {{--   <input type="text" name="tempcode" id="tempcode" value={{ $tempcode }}> --}}
                        {{--   <td colspan="2" style="border: 1px solid #333">
                            <strong> Rp.

                                {{ number_format($tax, 0, ',', '.') }}

                            </strong> </td> --}}</tr>
                            <tr>
                                <td>
                                </td>
                            </tr>
                            <tr>


            <!--  <td colspan="4" style="border: 1px solid #333"> <strong> Akumulasi Tagihan yang Harus Dibayar / Amount Due {{$current_inv_status}}</strong></td>
            <td colspan="2" style="border: 1px solid #333" align="right">
                <strong id="total">Rp. {{ number_format($suminvoice_amountdue, 0, ',', '.') }} </strong> </td> -->
            </tr>
        </tbody>
        <tfoot>
            {{-- <tr>
                <th colspan="3">Total</th>
                <td>Rp {{ number_format($invoice->total_price) }}</td>
            </tr> --}}


        </tfoot>
    </table>
</div><!-- /.tbl-wrapper -->

    <span class="pt-4">{!! $invNote !!}</span>

</div>





@if ( $suminvoice_number->payment_status == 1)

<div>
    <p>{{$signature}} , {{$suminvoice_number->payment_date}} </p>
    <p>Terima Kasih,</p>

    <br>
    <p>{{ $suminvoice_number->user->name ?? $suminvoice_number->updated_by }}</p>


</div>

@elseif ( $suminvoice_number->payment_status == 2)

<div>
    <p>T{{$signature}} </p>
    <p>Terima Kasih,</p>



</div>



@else

@if ( $current_inv_status == 1)
<tr>
    <td colspan="2" align="center"></br>
        <p><a style='font-size: 14px; color: #c40205; text-decoration: none;'>
        Anda masih memiliki tagihan yang belum terbayar (UNPAID) pada periode sebelumnya, silahkan melakukan pelunasan pembayaran Tagihan tersebut terlebih dahulu. Untuk info lebih lanjut silahkan menghubungi team Payment kami </a>
    </p>
</td>
<a href="{{ url('/invoice/cst/' . $encryptedurl) }}"><button class="btn">Lihat Data Tagihan</button></a>

@else





<br>






@if (!empty($result['data']['status']) AND ($result['data']['status']=="UNPAID") ) 


<div class="container font12" >

    <h5 align="center"><strong>Metode Pembayaran {{$result['data']['payment_name']}}</strong> </h5>

    <p align="center">Batas akhir pembayaran</p>
    <p align="center"><strong>{{date("d F Y, H:i:s", $result['data']['expired_time'])}}WITA</strong></p>

    <div class="payment-info">
        <div align="center">
            <img src="https://billing.alus.co.id/img/bank/{{ $result['data']['payment_method'] }}.webp" alt="Payment">
        </div>
        <div align="center">
            Jumlah Bayar <br>
            <h4><strong>Rp. {{number_format($result['data']['amount'], 0, ',', '.')}}</strong></h4>
        </div>
        <div align="center">
            Kode Bayar / Nomor VA <br>
            <h4> <strong>{{$result['data']['pay_code']}}</strong></h4>
        </div>
    </div>
    <div align="center">
        <br>
        <a style="text-decoration: inherit;"  href="{{$result['data']['checkout_url']}}"><div class="btn1">Lihat Detail / Cara bayar</div> </a>
    </div>
</div>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Kode Bayar disalin ke clipboard');
        }, function(err) {
            alert('Gagal menyalin teks: ', err);
        });
    }
</script>

@if($gateways->count() > 0)
<div align="center">
    <br>
    <button style="background-color: #007bff;" class="btn1" onclick="toggleContent('content1')">Pilih Metode Pembayaran yang Lain</button>
</div>
<div id="content1" class="content">
    <tr>
        <td colspan="2" align="center">
            <p align="center">
                <strong style="font-size: 14px; color: #000;">PILIH PAYMENT GATEWAY</strong>
            </p>
            <div class="payment-grid">
                @foreach($gateways as $gw)
                    @switch($gw->provider)
                        @case('bumdes')
                            <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="$('#myModal').modal('show')" style="cursor:pointer;">
                                <i class="{{ $gw->icon }}"></i>
                                <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                                <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                                @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                                <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                                @endif
                            </div>
                        @break
                        @case('winpay')
                            <form id="winpayForm" action="{{ url('/create-winpay-va') }}" method="POST" style="display:inline;">
                                @csrf
                                <input type="hidden" name="id" value="{{ $suminvoice_number->id }}">
                                <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="document.getElementById('winpayForm').submit();" style="cursor:pointer;">
                                    <i class="{{ $gw->icon }}"></i>
                                    <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                                    <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                                    @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                                    <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                                    @endif
                                </div>
                            </form>
                        @break
                        @case('tripay')
                            <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="$('#tripayModal').modal('show')" style="cursor:pointer;">
                                <i class="{{ $gw->icon }}"></i>
                                <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                                <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                                @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                                <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                                @endif
                            </div>
                        @break
                        @case('xendit')
                            <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="$('#xenditModal').modal('show')" style="cursor:pointer;">
                                <i class="{{ $gw->icon }}"></i>
                                <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                                <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                                @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                                <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                                @endif
                            </div>
                        @break
                        @case('duitku')
                            <form id="gw_duitku_form" action="{{ url('/create-duitku-va') }}" method="POST" style="display:inline;">
                                @csrf
                                <input type="hidden" name="id" value="{{ $suminvoice_number->id }}">
                                <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="this.style.opacity='0.6';document.getElementById('gw_duitku_form').submit();" style="cursor:pointer;">
                                    <i class="{{ $gw->icon }}"></i>
                                    <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                                    <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                                    @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                                    <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                                    @endif
                                </div>
                            </form>
                        @break
                        @default
                            {{-- Provider baru belum punya case: tampil generic, klik submit form --}}
                            <form id="gw_{{ $gw->provider }}_form" action="{{ url('/create-' . $gw->provider . '-va') }}" method="POST" style="display:inline;">
                                @csrf
                                <input type="hidden" name="id" value="{{ $suminvoice_number->id }}">
                                <input type="hidden" name="provider" value="{{ $gw->provider }}">
                                <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="document.getElementById('gw_{{ $gw->provider }}_form').submit();" style="cursor:pointer;">
                                    <i class="{{ $gw->icon }}"></i>
                                    <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                                    <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                                    @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                                    <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                                    @endif
                                </div>
                            </form>
                    @endswitch
                @endforeach
            </div>
        </td>
    </tr>
</div>
@endif

@else

@if($gateways->count() > 0)
<tr>
    <td colspan="2" align="center">
        <p align="center">
            <strong style="font-size: 14px; color: #000;">PILIH PAYMENT GATEWAY</strong>
        </p>
        <div class="payment-grid">
            @foreach($gateways as $gw)
                @switch($gw->provider)
                    @case('bumdes')
                        <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="$('#myModal').modal('show')" style="cursor:pointer;">
                            <i class="{{ $gw->icon }}"></i>
                            <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                            <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                            @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                            <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                            @endif
                        </div>
                    @break
                    @case('winpay')
                        <form id="winpayForm2" action="{{ url('/create-winpay-va') }}" method="POST" style="display:inline;">
                            @csrf
                            <input type="hidden" name="id" value="{{ $suminvoice_number->id }}">
                            <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="document.getElementById('winpayForm2').submit();" style="cursor:pointer;">
                                <i class="{{ $gw->icon }}"></i>
                                <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                                <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                                @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                                <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                                @endif
                            </div>
                        </form>
                    @break
                    @case('tripay')
                        <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="$('#tripayModal').modal('show')" style="cursor:pointer;">
                            <i class="{{ $gw->icon }}"></i>
                            <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                            <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                            @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                            <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                            @endif
                        </div>
                    @break
                    @case('xendit')
                        <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="$('#xenditModal').modal('show')" style="cursor:pointer;">
                            <i class="{{ $gw->icon }}"></i>
                            <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                            <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                            @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                            <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                            @endif
                        </div>
                    @break
                    @case('duitku')
                        <form id="gw2_duitku_form" action="{{ url('/create-duitku-va') }}" method="POST" style="display:inline;">
                            @csrf
                            <input type="hidden" name="id" value="{{ $suminvoice_number->id }}">
                            <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="this.style.opacity='0.6';document.getElementById('gw2_duitku_form').submit();" style="cursor:pointer;">
                                <i class="{{ $gw->icon }}"></i>
                                <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                                <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                                @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                                <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                                @endif
                            </div>
                        </form>
                    @break
                    @default
                        <form id="gw2_{{ $gw->provider }}_form" action="{{ url('/create-' . $gw->provider . '-va') }}" method="POST" style="display:inline;">
                            @csrf
                            <input type="hidden" name="id" value="{{ $suminvoice_number->id }}">
                            <input type="hidden" name="provider" value="{{ $gw->provider }}">
                            <div class="payment-card" data-provider="{{ $gw->provider }}" onclick="document.getElementById('gw2_{{ $gw->provider }}_form').submit();" style="cursor:pointer;">
                                <i class="{{ $gw->icon }}"></i>
                                <div class="payment-label">{{ $gw->settings['invoice_label'] ?? $gw->label }}</div>
                                <div class="payment-subtitle">{{ $gw->settings['invoice_note'] ?? $gw->settings['subtitle'] ?? '' }}</div>
                                @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                                <div class="payment-fee">{{ $gw->feeDescription() }}</div>
                                @endif
                            </div>
                        </form>
                @endswitch
            @endforeach
        </div>
    </td>
</tr>
@endif

@endif

<div class="container">





 @endif

 @endif




 <hr>

 <p align="right">

     {{ $companyAddress1 }}
     <br>
     {{ $companyAddress2 }}





 </p>


<div class="containers">

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
