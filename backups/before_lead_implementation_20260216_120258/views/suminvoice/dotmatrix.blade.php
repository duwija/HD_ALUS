<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invoice</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @media print {
            @page {
                margin: 5; /* Menghilangkan margin halaman cetak */
            }
            
            body {
                margin: 1;
                padding: 1;
            }

            header, footer, .no-print {
                display: none !important;
            }
        }
        body{
            font-family:'Arial';
            color:#000;
            
            
            margin:1;

        }
        .container{
           color:#000;
           width:180px;
           font-size: 5px;
           background-color:#fff;
       }
       table{
           /* border:1px solid #333;*/
           border-collapse:collapse;
           /* margin:0 auto;*/
           width:95%;
       }
       td, tr, th{
          font-size: 11px;
      }
  </style>
  {{-- <style>
    body{
        font-family:"Arial Black";
        color:#333;
        text-align:left;
        font-size:10px;
        margin:2;
    }
    .container{
        margin:0 auto;
        margin-top:15px;
        padding:40px;
        width:700px;
        height:auto;
        background-color:#fff;
    }
    caption{
        font-size:28px;
        margin-bottom:10px;
    }
    table{
       /* border:1px solid #333;*/
       border-collapse:collapse;
       /* margin:0 auto;*/
       width:100%;
   }
   td, tr, th{
    padding:5px;
/*            border:1px solid #333;*/
/*width:185px;*/
}
th{
    background-color: #f0f0f0;
}
h4, p{
    margin:0px;
}
</style> --}}
<script>
  window.onload(window.print());
</script>
</head>
<body style="font-size: 5px">

    <div class="container" >

      <table style="border: none">
        <tr style="border: none">


          <td align="center">

            {{-- <img width="50px" src="/dashboard/dist/img/logo.jpg">
            <p> --}}
                <strong style="font-size: 11px">{{$companyName}} <br>

                </strong>
            </td>
        </tr>


    </table> 



    <div {{-- style=" background-image: url('/dashboard/dist/img/unpaid.png');background-repeat:no-repeat;background-position: center; background-size: 300px; " --}}>
     <table style="border: none; font-weight:200px  ">

      <tr style="border: none">
       <td colspan="2" align="center">
        <p> <strong>INVOICE</strong>  </p>
    </td>
</tr>
<tr>
  <td>




      Date : {{ $suminvoice_number->date }}<br>
      No. Invoice : #{{ $suminvoice_number->number }}
      <br>
      <a>Bill To: </a>
      {{ $customer->customer_id }}<br> 
      {{ $customer->name}} <br>
      {{-- {{ $customer->phone }} <br> --}}
      {{ $customer->address }}<br>
      {{-- {{ $customer->npwp }} --}}


  </td>
</tr>
<tr>
    <td align="center"> <?php 
    if ( $suminvoice_number->payment_status == 1)
    {
        echo ' <strong><a  style="font-size: 12; color: #000">PAID </a> </stong><br><a  style="font-size: 10; color: #000">Sudah Terbayar </a><br></td>';
    }
    else
    {
     echo ' <strong><a style="font-size: 12; color: #000">UNPAID </a></strong><br><a style="font-size: 10; color: #000">Belum Terbayar </a><br>';
 }

 ?>               

 <td>
 </tr>  

</table>   
<table >


    <tbody>
        <tr >
            <th style="border: 1px solid #333">No</th>
            <th style="border: 1px solid #333">Description</th>
            {{--  <th style="border: 1px solid #333">price</th> --}}
            <th style="border: 1px solid #333">Qty</th>
            <th style="border: 1px solid #333">Total</th>
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

          <td style="border: 1px solid #333">{{ $description }}</td>

          <!-- <td style="border: 1px solid #333">{{ number_format($invoice->amount + $taxitem, 0, ',', '.') }}</td> -->

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
            @else
            @endif
            <td colspan="3" style="border: 1px solid #333" >Total Tagihan</td>
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
<table style="border: 1px">
    <tr style="border: none">
      <td align="left" colspan="0">

      </td>
      <br>
      <td align="center">


         @php
         if ($suminvoice_number->payment_date == null )
         {
          $date = $suminvoice_number->date;
      }
      else
      {
          $date = $suminvoice_number->payment_date;
      }
      

      @endphp

      <p> Tabanan, {{ $date }}<br><br>

        {!! QrCode::size(80)->generate(url('/suminvoice/'.$suminvoice_number->tempcode.'/viewinvoice')); !!}<br><br>
        
    </p>
</td>
</tr>


</table> 
</div>







</body>
</html>
