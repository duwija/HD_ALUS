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
       /* border:1px solid #8f8d8d;*/
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
        color:#8f8d8d;
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
       /* border:1px solid #8f8d8d;*/
       border-collapse:collapse;
       /* margin:0 auto;*/
       width:100%;
   }
   td, tr, th{
    padding:5px;
/*            border:1px solid #8f8d8d;*/
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


            <strong style="font-size: 11px">{{$companyName}}<br>

            </strong>
        </td>
    </tr>


</table> 



<div>
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
            <th style="border: 1px solid #8f8d8d">No</th>
            <th style="border: 1px solid #8f8d8d">Description</th>
            {{--  <th style="border: 1px solid #8f8d8d">price</th> --}}
            <th style="border: 1px solid #8f8d8d">Qty</th>
            <th style="border: 1px solid #8f8d8d">Total</th>
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
      <tr style="border: 1px solid #8f8d8d" >
          <th style="border: 1px solid #8f8d8d" scope="row">{{ $loop->iteration }}</th>

          <td style="border: 1px solid #8f8d8d">{{ $description }}</td>

          <!-- <td style="border: 1px solid #8f8d8d">{{ number_format($invoice->amount + $taxitem, 0, ',', '.') }}</td> -->

          <input type="hidden" name="invoice_item[]" value={{ $invoice->id }}>

          <td align="center" style="border: 1px solid #8f8d8d">{{ $invoice->qty }}</td>
          @php
          $isubtotal =$invoice->qty * $invoice->amount;
          $tax = $isubtotal * $taxfee;

          $itotal = $isubtotal + $tax;
          @endphp
          <td style="border: 1px solid #8f8d8d" align="right">{{ number_format($itotal, 0, ',', '.')  }}</td>


      </tr>

      @endforeach

      @if ( $pph != 0)

      <tr>
       <td colspan="4" style="border: 1px solid #8f8d8d" >Pph 23</td>
       <td style="border: 1px solid #8f8d8d" align="right"><strong id="total">Rp. -{{ number_format($pph, 0, ',', '.') }} </strong></td>

   </tr>

   @elseif (!is_null($admin_fee))
   <tr>
    <td colspan="3"style="border: 1px solid #8f8d8d">Biaya Admin</td>
    <td  style="border: 1px solid #8f8d8d"  align="right">
        {{ number_format($admin_fee, 0, ',', '.') }}

    </td>
</tr>


@endif


<tr>

  <td colspan="3" style="border: 1px solid #8f8d8d" >Total Tagihan</td>
  <td style="border: 1px solid #8f8d8d" align="right"><strong id="total">Rp. {{ number_format($subtotal, 0, ',', '.') }} </strong></td>
</tr>

</tbody>
<tfoot>

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

      @if(!empty($invNote))
      <p style="font-size: 10px; margin: 5px 0;">
        {!! nl2br(e($invNote)) !!}
      </p>
      @endif

      <p> {!! nl2br(e($signature)) !!}, {{ $date }}<br><br>

        {!! QrCode::size(80)->generate(url($appUrl.'/suminvoice/'.$suminvoice_number->tempcode.'/viewinvoice')); !!}<br><br>

    </p>
</td>
</tr>


</table> 
</div>







</body>
</html>
