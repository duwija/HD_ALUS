<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width,initial-scale=1" name="viewport">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>

    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        margin: 50; /* Menghilangkan margin halaman cetak */
    }
    
    body {
        margin: 1;
        padding: 1;
    }

    header, footer, .no-print {
        display: none !important;
    }
}
</style>
<style>
    .payment-grid {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 24px;
        margin-top: 20px;
    }

    .payment-card {
        width: 160px;
        background-color: #f9f9f9;
        border-radius: 14px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
        text-align: center;
        padding: 24px 12px;
        cursor: pointer;
        transition: transform 0.25s, box-shadow 0.25s;
    }

    .payment-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
    }

    .payment-card i {
        font-size: 32px;
        color: #007bff;
        margin-bottom: 12px;
    }

    .payment-label {
        font-size: 14px;
        font-weight: 600;
        color: #333;
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
        .btn2:hover {
         opacity: 0.7;
         transform: scale(1.1); /* Memperbesar gambar 10% saat di-hover */
         box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); /* Menambahkan bayangan saat di-hover */
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
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.btn3
{
    all: ;
}

.btn2:focus {
  outline: revert;
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

button:hover {
    background-color: #0056b3;
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
  
  <table style="border: none">
    <tr style="border: none">
      <td align="left" colspan="3">
        <img width="120px" src="/dashboard/dist/img/logoinv.png">
    </br><strong>
    {{ $companyName }}</strong>
</td>
<td align="right">



    <p><strong><u>TAGIHAN</u></strong>
    </p>
    <p>
      <strong><i>INVOICE</i></strong>
  </p>
</td>


<td align="right" width="70px">
    {!! QrCode::size(60)->generate(url('suminvoice/'.$suminvoice_number->tempcode.'/viewinvoice')); !!}
</td>


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
                    echo '<div class="table-cell right-align"> Payment date: '. $suminvoice_number->payment_date.'</br>'.$suminvoice_number->kasbank->name .' </div>';
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
<table class="tbl">


    <tbody>
        <tr bgcolor="#CBCAC7"  >
            <th style="border: 1px solid #333">#</th>
            <th style="border: 1px solid #333">Description</th>
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
        $subtotal = $subtotal + ($totalwutax + $totaltax) - $pph + $admin_fee;
        $strmonth = substr( $invoice->periode, -6, 2);
        $stryear = substr( $invoice->periode, -4, 4);


        $month_num = $strmonth;


        $month_name = date("F", mktime(0, 0, 0, $month_num, 10)); 
        if ( $invoice->monthly_fee == 1 )
        {
          $description = $invoice->description.' - '.$month_name.' '.$stryear;
      }
      else
      {
          $description = $invoice->description;
      }





      @endphp
      <tr style="border: 1px solid #333" >
          <th style="border: 1px solid #333" scope="row">{{ $loop->iteration }}</th>
          {{--   <td>{{ $invoice->created_at }}</td> --}}
          <td style="border: 1px solid #333">{{ $description }}</td>

          <td style="border: 1px solid #333">{{ number_format($invoice->amount + $taxitem, 0, ',', '.') }}</td>
          {{--  <td>{{ $invoice->periode }}</td> --}}
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
                <td colspan="4" style="border: 1px solid #333" >Pph 23</td>
                <td style="border: 1px solid #333" align="right"><strong id="total">Rp. -{{ number_format($pph, 0, ',', '.') }} </strong></td>
                <tr>
                    @elseif (!is_null($admin_fee))
                    <tr>
                        <td colspan="4"style="border: 1px solid #333">Biaya Admin</td>
                        <td  style="border: 1px solid #333" align="right">
                            {{ number_format($admin_fee, 0, ',', '.') }}

                        </td>
                    </tr>

                    @endif
                    <td colspan="4" style="border: 1px solid #333" >Total Tagihan Bulan ini / Current Charges <br> <p >Setiap harga yang tertera pada invoice sudah termasuk PPN</p></td>
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
</div>





@if ( $suminvoice_number->payment_status == 1)

<div>
    <p>{!! nl2br(e($signature)) !!} , {{$suminvoice_number->payment_date}} </p>
    <p>Terima Kasih,</p>

    <br>
    <p>{{ $suminvoice_number->user->name ?? $suminvoice_number->updated_by }}</p>


</div>

@elseif ( $suminvoice_number->payment_status == 2)

<div>
    <p>{!! nl2br(e($signature)) !!}  </p>
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





@endif

@endif




<hr>

@if(!empty($invNote))
<div style="margin: 15px 0; padding: 10px; background-color: #f9f9f9; border-left: 4px solid #007bff;">
    <p style="margin: 0; font-size: 12px; line-height: 1.6;">
        {!! nl2br(e($invNote)) !!}
    </p>
</div>
@endif

<p align="right">

   {!! nl2br(e($companyAddress1)) !!}
   <br>
   {!! nl2br(e($companyAddress2)) !!}





</p>

@if ($suminvoice_number->payment_status == 0 && $current_inv_status == 0)
<!-- Payment Method Buttons -->
<div class="container no-print" style="margin-top: 30px;">
    <h4 style="text-align: center; margin-bottom: 20px;">Pilih Metode Pembayaran</h4>
    <div class="payment-grid">
        
        @php
            $paymentBumdesEnabled = tenant_config('payment_bumdes_enabled', 1);
            $paymentWinpayEnabled = tenant_config('payment_winpay_enabled', 1);
            $paymentTripayEnabled = tenant_config('payment_tripay_enabled', 1);
        @endphp

        @if($paymentBumdesEnabled)
        <!-- Payment Point / Bumdes -->
        <div class="payment-card" data-toggle="modal" data-target="#myModal">
            <i class="fas fa-store"></i>
            <div class="payment-label">Payment Point</div>
            <small style="color: #666;">Bayar di Loket</small>
        </div>
        @endif

        @if($paymentWinpayEnabled)
        <!-- Winpay -->
        <form method="POST" action="{{ url('/create-winpay-va') }}" style="margin: 0;">
            @csrf
            <input type="hidden" name="id" value="{{ $suminvoice_number->id }}">
            <button type="submit" style="all: unset; cursor: pointer;">
                <div class="payment-card">
                    <i class="fas fa-building-columns"></i>
                    <div class="payment-label">Winpay</div>
                    <small style="color: #666;">Multi Bank VA</small>
                </div>
            </button>
        </form>
        @endif

        @if($paymentTripayEnabled)
        <!-- Tripay -->
        <div class="payment-card" data-toggle="modal" data-target="#online">
            <i class="fas fa-credit-card"></i>
            <div class="payment-label">Tripay</div>
            <small style="color: #666;">VA, E-Wallet, QRIS</small>
        </div>
        @endif

    </div>
</div>
@endif


<div class="containers">

@if($suminvoice_number->payment_status == 0 && $current_inv_status == 0 && tenant_config('payment_tripay_enabled', 1))
   <!-- Trigger the modal with a button -->

   <div class="modal fade" id="online" role="dialog">
       <div class="modal-dialog">

           <!-- Modal content-->
           <div class="modal-content">
               <div class="modal-header" align="center">
                   <button type="button" class="close" data-dismiss="modal">&times;</button>
                   <h4 class="modal-title">Pembayaran  Transfer Bank / Outlet Ritel / E-Wallet</h4>
                   <p>* Pembayaran melalui Transfer Bank / Outlet Ritel / E-Wallet akan dikenakan biaya Transaksi </p>

               </div>
               <div class="modal-body">

                   <div class="container">
                       <div class="row1"></h4></div>
                       <div class="row1">



<!-- 
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
                               </form> -->

                        <!--        <form class="btn2" role="form" method="post" action="/tripay/create">
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
                               </form> -->


<!-- 
                               <form class="btn2" role="form" method="post" action="/tripay/create">
                                   @csrf

                                   <input type="hidden" name="name" value="{{ $customer->name}}">
                                   <input type="hidden" name="customer_id" value="{{ $customer->customer_id}}">
                                   <input type="hidden" name="email" value="{{ $customer->email}}">
                                   <input type="hidden" name="phone" value="{{ $customer->phone}}">
                                   <input type="hidden" name="method" value="PERMATAVA">
                                   <input type="hidden" name="number" value="{{ $suminvoice_number->number }}">
                                   <input type="hidden" name="tempcode" value="{{ $suminvoice_number->tempcode }}">
                                   <input type="hidden" name="description" value="{{ $suminvoice_number->date }}">
                                   <input type="hidden" name="amount" value="{{ $subtotal }}">





                                   <button><div class="virtual-account">
                                       <h6>PERMATA VA</h6>
                                       <img style="width:200px" src="{{ url('img/bank/PERMATAVA.webp') }}" alt="PERMATA">
                                   </div></button>


                               </form> -->
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
                        </div>


                        <div class="row1">


                     <!--           <form class="btn2" role="form" method="post" action="/tripay/create">
                                   @csrf

                                   <input type="hidden" name="name" value="{{ $customer->name}}">
                                   <input type="hidden" name="customer_id" value="{{ $customer->customer_id}}">
                                   <input type="hidden" name="email" value="{{ $customer->email}}">
                                   <input type="hidden" name="phone" value="{{ $customer->phone}}">
                                   <input type="hidden" name="method" value="CIMBVA">
                                   <input type="hidden" name="number" value="{{ $suminvoice_number->number }}">
                                   <input type="hidden" name="tempcode" value="{{ $suminvoice_number->tempcode }}">
                                   <input type="hidden" name="description" value="{{ $suminvoice_number->date }}">
                                   <input type="hidden" name="amount" value="{{ $subtotal }}">



                                   <button><div class="virtual-account">
                                       <h6>CIMB Niaga VA</h6>
                                       <img style="width:200px" src="{{ url('img/bank/CIMBVA.webp') }}" alt="CIMB Niaga">
                                   </div></button>



                               </form> -->





                     <!--           <form class="btn2" role="form" method="post" action="/tripay/create">
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



                                <button>
                                    <div class="virtual-account">
                                        <h6>Mandiri Virtual Account</h6>
                                        <img style="width:200px" src="{{ url('img/bank/MANDIRIVA.webp') }}" alt="Mandiri">
                                    </div></button>



                                </form> -->



                                <form class="btn2" role="form" method="post" action="/tripay/create">
                                    @csrf

                                    <input type="hidden" name="name" value="{{ $customer->name}}">
                                    <input type="hidden" name="customer_id" value="{{ $customer->customer_id}}">
                                    <input type="hidden" name="email" value="{{ $customer->email}}">
                                    <input type="hidden" name="phone" value="{{ $customer->phone}}">
                                    <input type="hidden" name="method" value="DANA">
                                    <input type="hidden" name="number" value="{{ $suminvoice_number->number }}">
                                    <input type="hidden" name="tempcode" value="{{ $suminvoice_number->tempcode }}">
                                    <input type="hidden" name="description" value="{{ $suminvoice_number->date }}">
                                    <input type="hidden" name="amount" value="{{ $subtotal }}">



                                    <button><div class="virtual-account">
                                        <h6>Dana</h6>
                                        <img style="width:200px" src="{{ url('img/bank/DANA.webp') }}" alt="Dana">
                                    </div></button>


                                </form>

                                <form class="btn2" role="form" method="post" action="/tripay/create">
                                    @csrf

                                    <input type="hidden" name="name" value="{{ $customer->name}}">
                                    <input type="hidden" name="customer_id" value="{{ $customer->customer_id}}">
                                    <input type="hidden" name="email" value="{{ $customer->email}}">
                                    <input type="hidden" name="phone" value="{{ $customer->phone}}">
                                    <input type="hidden" name="method" value="OVO">
                                    <input type="hidden" name="number" value="{{ $suminvoice_number->number }}">
                                    <input type="hidden" name="tempcode" value="{{ $suminvoice_number->tempcode }}">
                                    <input type="hidden" name="description" value="{{ $suminvoice_number->date }}">
                                    <input type="hidden" name="amount" value="{{ $subtotal }}">



                                    <button><div class="virtual-account">
                                        <h6>Ovo</h6>
                                        <img style="width:200px" src="{{ url('img/bank/OVO.webp') }}" alt="Ovo">
                                    </div></button>


                                </form>





                            </div>
                            <div class="row1">



                           <!--      <form class="btn2" role="form" method="post" action="/tripay/create">
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



                                    <button> <div class="virtual-account">
                                        <h6>Alfamart</h6>
                                        <img style="width:200px" src="{{ url('img/bank/ALFAMART.webp') }}" alt="Alfamart">
                                    </div></button>



                                </form>
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



                                </form>  -->
                  <!--               <form class="btn2" role="form" method="post" action="/tripay/create">
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



                                </form> -->


                                <!-- <button><div class="virtual-account" style="width:200px"> -->
                                    <!-- </div> -->


                                </div>



                                <!-- Add more rows and columns as needed -->
                            </div>



                        </div>
                    </div>
                </div>
            </div>
@endif

@if($suminvoice_number->payment_status == 0 && $current_inv_status == 0 && tenant_config('payment_bumdes_enabled', 1))
            <!-- Modal -->
            <div class="modal fade" id="myModal" role="dialog">
              <div class="modal-dialog">

                  <!-- Modal content-->
                  <div class="modal-content">
                      <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal">&times;</button>
                          <h4 class="modal-title">Pembayan Melalui Payment Point</h4>
                      </div>
                      <div class="modal-body">
                          <p><strong><a style='font-size: 11px; color: #000; text-decoration: none;'>
                          Pembayaran dapat dilakukan secara langsung dengan datang ke  Payment point terdekat</a></strong>
                      </p>
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
@endif




</div>

</body>
</html>
