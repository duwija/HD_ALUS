@extends('layout.main')
@section('title','Invoice')
@section('content')
@inject('encrypt', 'App\Invoice')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title"> <strong>INVOICE # {{$suminvoice_number->number }} </strong>  </h3>

      @php
      $status = $suminvoice_number->payment_status;

      

      if ( $status ==0)
      {
        $inv_status ='UNPAID';
        $color ='red';
      }
      elseif ( $status ==1)
      {
       $inv_status ='PAID';
       $color ='blue';
     }
     elseif ( $status ==2)
     {
      $inv_status ='CANCEL';
      $color ='grey';
    }
    else
    {
     $inv_status ='UNKNOW';
     $color ='yellow';
   }

   @endphp
   @php

   if ($customer->status_name == 'Active')
   $btn_sts = "btn-success";
   elseif ($customer->status_name == 'Inactive')
   $btn_sts = "btn-secondary";
   elseif ($customer->status_name == 'Block')
   $btn_sts = "btn-danger";
   elseif ($customer->status_name == 'Company_Properti')
   $btn_sts = "btn-primary";
   else
   $btn_sts = "btn-warning";

   @endphp

 </div>

 <div class="card-body row m-md-n2">



  <table class="table table-borderless col-md-6 table-sm">

    <tbody>
      @if (Auth::user()->privilege == 'admin' OR Auth::user()->privilege =='accounting' OR Auth::user()->privilege =='noc' )
      <tr class="col-md-6">

       <tr>
         <th style="width: 25%" class="text-right">CID / Name :</th>
         <td><a href="/customer/{{ $customer->id}}"><strong>{{$customer->customer_id}} 

           | {{$customer->name}} </strong></td>

         </tr>
         <tr>
          <th style="width: 25%" class="text-right">Phone :</th>
          <td><a href="https://wa.me/{{$customer->phone}}">   {{$customer->phone}}  </a></td>
          
        </tr>
        <tr>
          <th style="width: 25%" class="text-right">Address :</th>
          <td><a href="https://www.google.com/maps/place/{{ $customer->coordinate }}" target="_blank" >{{$customer->address}}</a>  </td>
          
        </tr>
        <tr>
          <th style="width: 25%" class="text-right">Billing Start :</th>
          <td><a >   {{$customer->billing_start}}  </a></td>
          
        </tr>
        <tr>
          <th style="width: 25%" class="text-right">Merchant :</th>
          <td> @if(!empty($customer->merchant_name) && !empty($customer->merchant_name->name))
            <a href="/merchant/{{$customer->merchant_name->id}}" class="bg-info badge">{{ $customer->merchant_name->name }}</a>
            @else
            <span>No Merchant</span> <!-- You can change this to whatever default text you want -->
          @endif</td>
          
        </tr>
        
        
      </tr>
      @else

      <tr class="col-md-6">

       <tr>
         <th style="width: 25%" class="text-right">CID / Name :</th>
         <td><strong>{{$customer->customer_id}} 

           | {{$customer->name}} </strong></td>

         </tr>
         <tr>
          <th style="width: 25%" class="text-right">Phone :</th>
          <td>{{$customer->phone}}  </a></td>
          
        </tr>
        <tr>
          <th style="width: 25%" class="text-right">Address :</th>
          <td>{{$customer->address}}</a>  </td>
          
        </tr>
        <tr>
          <th style="width: 25%" class="text-right">Billing Start :</th>
          <td><a >   {{$customer->billing_start}}  </a></td>
          
        </tr>
        <tr>
          <th style="width: 25%" class="text-right">Merchant :</th>
          <td> @if(!empty($customer->merchant_name) && !empty($customer->merchant_name->name))
           {{ $customer->merchant_name->name }}</a>
           @else
           <span>No Merchant</span> <!-- You can change this to whatever default text you want -->
         @endif</td>

       </tr>


     </tr>
     @endif
   </tbody>
 </table>
 <table class="table table-borderless col-md-6 table-sm">

  <tbody>

    <tr class="col-md-6">
      <tr>
        <th style="width: 25%" class="text-right">Status :</th>
        <td><div class=" {{$btn_sts}} badge btn-sm p-2 mr-1 " >


          {{$customer->status_name}}</div> 
        </td>

      </tr>
      <tr>
       <th style="width: 25%" class="text-right">Plan :</th>
       <td>{{$customer->plan_name}}  </td>

     </tr>

     <tr>
       <th style="width: 25%" class="text-right">NPWP :</th>
       <td>{{strtoupper($customer->npwp)}}  </td>
     </tr>

     @if (Auth::user()->privilege == 'admin' OR Auth::user()->privilege =='accounting' OR Auth::user()->privilege =='noc' )
     <tr class="col-md-6">

       <tr>
         <th style="width: 25%" class="text-right">Faktur :</th>
         <td><a href="/upload/tax/{{$suminvoice_number->file}}" target="_blank">{{$suminvoice_number->file}}</a> 
          <br>
          <button type="button" class=" btn  bg-gradient-primary btn-sm" data-toggle="modal" data-target="#modal-faktur">Upload or replace Faktur</button>
        </td>
      </tr>
      @endif

    </tbody>
  </table>





</div>




<hr>

<!-- /.card -->
<!-- /.card-header --> <div class="card-body row">




  {{-- ITEM INVOICE --}}
  <div class="col-md-12">
   <div class="table-responsive">
     <form role="form" method="post" action="/suminvoice">
      @csrf
      <table id="example1" class="table table-bordered table-striped">
        {{--   <div class="card card-primary card-outline">
          --}}

          <div class="text-right text-sm  p-1"> 



            Inv date : {{$suminvoice_number->date}} <br>
            Due Date : {{$suminvoice_number->due_date}} <br>
            @if(!empty($suminvoice_number->payment_date))

            Payment Date : {{$suminvoice_number->payment_date}}

            @endif


          </div>
          <div class="d-flex bg-white justify-content-center p-2  "><strong style="color:{{$color}}; font-size: 30px;"> {{$inv_status}} </strong></div>
          <thead >
            <tr>
              <th scope="col">#</th>
              <th scope="col">Created At</th>
              <th scope="col">Description</th>
              <th scope="col">Price @</th>
              {{-- <th scope="col">Periode</th>
              <th scope="col">Payment Status</th> --}}
              <th scope="col">Qty</th>
              <th scope="col">Total</th>

            </tr>
          </thead>
          <tbody>

            <input type="hidden" name="id_customer" value={{$customer->id}}>
            @php $subtotal=0; 
            // $tempcode=$invoice->tempcode;


            @endphp
            @foreach( $invoice as $invoice)
            @php 
            $subtotal = $subtotal + $invoice->amount * $invoice->qty ;

            $strmonth = substr(  $invoice->periode, -6, 2);
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
            <tr>
              <th scope="row">{{ $loop->iteration }}</th>
              <td>{{ $invoice->created_at }}</td>
              <td>{{ $description }}</td>

              <td>{{ number_format($invoice->amount, 0, ',', '.') }}</td>
              {{--  <td>{{ $invoice->periode }}</td> --}}
              <input type="hidden" name="invoice_item[]" value={{ $invoice->id }}>
              {{--  @if($invoice->payment_status == 0)

              <td style="color:white; background-color: blue" >{{ 'Un Invoice' }}</td>
              <td>
                <input type="hidden" name="invoice_item[]" value={{ $invoice->id }}>
              </td>
              @elseif($invoice->payment_status == 3)
              <td style="color:white; background-color: green" >{{ 'Invoiced' }}</td>
              @endif --}}
              <td>{{ $invoice->qty }}</td>
              <td>{{ number_format($invoice->qty * $invoice->amount,  0, ',', '.') }}</td>


            </tr>

            @endforeach
            <tr> <td colspan="5"> <strong> Subtotal</strong></td>
              <td colspan="2">
               <strong> {{ number_format($subtotal, 0, ',', '.') }} </strong> </td></tr>
               @php 


               $taxfee     = $suminvoice_number->tax      ?? 0;
               $pphfee     = $suminvoice_number->pph      ?? 0;

               if ($status == 0 && !is_null(Auth::user()->admin_fee))
               {
                 $admin_fee  = Auth::user()->admin_fee      ?? 0;
               }
               else
               {$admin_fee  = 0;}
               
               

               $tax = round($subtotal * ($taxfee / 100), 0);
               $pph = round($subtotal * ($pphfee / 100), 0);
               $total = (float)$subtotal + (float)$tax - (float)$pph + (float)$admin_fee;
               @endphp

               <tr> <td colspan="5"> <strong> Tax Ppn ({{$taxfee}}%)</strong>

                <input type="hidden" name="tax" id="tax" value={{$taxfee}} ></td> 

                <td colspan="2">
                 <strong> 

                  {{ number_format($tax, 0, ',', '.') }}

                </strong> </td>
              </tr>
              {{-- Hanya tampil jika UNPAID dan ada biaya admin --}}
              @if ($status == 0 && !is_null(Auth::user()->admin_fee))
              <tr>
                <td colspan="5"><strong>Biaya Admin</strong></td>
                <td colspan="2">
                  <strong>{{ number_format(Auth::user()->admin_fee, 0, ',', '.') }}</strong>
                </td>
              </tr>
              @endif


              @if ( $pph != 0)
              <tr> <td colspan="5"> <strong> Pph 23</strong></td>
                <td colspan="2">
                 <strong id="total"> {{ number_format(-$pph, 0, ',', '.') }} </strong> </td></tr>
                 @else
                 @endif
                 <tr> <td colspan="5"> <strong> Total</strong></td>
                  <td colspan="2">
                    <strong id="total"> {{ number_format($total, 0, ',', '.') }}</strong> </td></tr>

                  </tbody>
                </table>
              </div>

            </form>
            <div class=" text-sm  p-2 card ">
              <b>Note:</b> {{$suminvoice_number->note}}
            </div>
            @if ($suminvoice_number->payment_status ==0)


            @if (Auth::user()->privilege == 'admin' OR Auth::user()->privilege =='accounting' )


            <button type="button" class="float-right btn p-2  bg-gradient-primary btn-sm btn-primary mb-2" data-toggle="modal" data-target="#modal-payment">  + Make Payment </button>


<!--              <form  action="/suminvoice/{{$suminvoice_number->id }}" method="POST" class="d-inline invoice-cancel" >
              @method('delete')
              @csrf

              <input type="hidden" name="tempcode" value="{{$suminvoice_number->tempcode}}">
              <input type="hidden" name="payment_id" value="{{$suminvoice_number->payment_id}}">
              <input type="hidden"  class="form-control " name="updated_by" id="updated_by"  value="{{ Auth::user()->id }}">

              <button  type="submit"  class="float-left btn p-2  bg-gradient-warning btn-sm btn-warning mr-2  mb-2 "> Cancel </button>
            </form> -->

            <button type="button" class="float-left btn p-2 bg-gradient-warning btn-sm btn-warning mr-2 mb-2" data-toggle="modal" data-target="#cancelModal">
              Cancel
            </button>
            @elseif ( Auth::user()->privilege =='merchant' OR Auth::user()->privilege =='payment')


            @if ( $current_inv_status == 1)
            <button type="button" class="float-right btn p-2  bg-gradient-danger btn-sm btn-danger mb-2" data-toggle="modal" >  Masih Ada tagihan yang belum terbayar di bulan sebelumnya </button>

            @else
            <button type="button" class="float-right btn p-2  bg-gradient-primary btn-sm btn-primary mb-2" data-toggle="modal" data-target="#modal-payment">  + Make Payment </button>
            @endif






            @endif
            @elseif ($suminvoice_number->payment_status ==1)
            @if (Auth::user()->privilege == 'admin' OR Auth::user()->privilege =='accounting' )
            <button type="button" class="float-left btn p-2 bg-gradient-warning btn-sm btn-warning mr-2 mb-2" data-toggle="modal" data-target="#cancelModal">
              Cancel
            </button>
            @endif
            <button disabled="" type="button" class="float-right btn p-2 btn-sm btn-secondary mb-2" data-toggle="modal" data-target="#modal-payment">  + Make Payment </button>
            @else
<!--             <button type="button" class="float-left btn p-2 bg-gradient-warning btn-sm btn-warning mr-2 mb-2" data-toggle="modal" data-target="#cancelModal">
              Cancel
            </button> -->
            <button disabled="" type="button" class="float-right btn p-2 btn-sm btn-secondary mb-2" data-toggle="modal" data-target="#modal-payment">  + Make Payment </button>


            @endif 





            <a href="{{url('suminvoice').'/' .$invoice->tempcode. '/print'}}" target="_blank" class="btn btn-primary float-left mr-2 ">Print</a>
            <a href="{{url('suminvoice').'/' .$invoice->tempcode. '/dotmatrix'}}" target="_blank" class="btn btn-primary float-left mr-2">Print Thermal</a>

          <!--   @if (Auth::user()->privilege == 'admin' OR Auth::user()->privilege =='accounting' )
           
            <form action="/suminvoice/remainderinv/{{$suminvoice_number->id}}" method="POST" class="d-inline">
              @method('post')
              @csrf
              <button type="submit" class="btn btn-success float-left mr-2"><i class="fab fa-whatsapp">   </i> Sent Remainder</button>
            </form>
            @endif -->

            @if (Auth::user()->privilege == 'admin' || Auth::user()->privilege == 'accounting')
            <button type="button" class="btn btn-success float-left mr-2" onclick="showNotificationOptions('{{ $suminvoice_number->id }}')">
              <i class="fab fa-whatsapp"></i> Send Remainder
            </button>
            @endif
          </div>
        </div>

        <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Cancellation Reason</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <form id="cancelForm" action="/suminvoice/{{$suminvoice_number->id}}" method="POST">
                  @method('delete')
                  @csrf

                  <input type="hidden" name="tempcode" value="{{$suminvoice_number->tempcode}}">
                  <input type="hidden" name="payment_id" value="{{$suminvoice_number->payment_id}}">
                  <input type="hidden" name="updated_by" value="{{ Auth::user()->id }}">
                  <div class="form-group">
                    <label for="cancel_date">Cancellation Date</label>
                    <input type="date" class="form-control" name="cancel_date" id="cancel_date" value="{{ now()->format('Y-m-d') }}" required>
                  </div>
                  
                  {{-- Jika invoice sudah dibayar, tampilkan opsi refund --}}
                  @if ($suminvoice_number->payment_status == 1)
                  <div class="form-group">
                    <label for="refund_account">Refund dari Kas/Bank</label>
                    @if ($bank->isNotEmpty())
                    <select name="refund_account" id="refund_account" class="form-control">
                      @foreach ($bank as $akun)
                      <option value="{{ $akun->akun_code }}">{{ $akun->akun_code }} : {{ $akun->name }}</option>
                      @endforeach
                    </select>
                    @else
                    <input type="text" class="form-control text-danger" value="Akun Kas/Bank tidak ditemukan!" readonly>
                    @endif
                  </div>
                  @endif
                  <div class="form-group">
                    <label for="cancel_reason">Reason for Cancellation</label>
                    <textarea class="form-control" name="cancel_reason" id="cancel_reason" rows="3" required></textarea>
                  </div>

                  <button type="submit" class="btn btn-danger">Confirm Cancel</button>
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="modal-faktur">
          <div class="modal-dialog modal-lg">
            <div class="modal-content ">


              <div class="card card-primary card-outline p-5">
                <div class="card-header">
                  <h3 class="card-title font-weight-bold"> Upload Faktur </h3>
                </div>


                <!-- Alert message (start) -->
                @if(Session::has('message'))
                <div class="alert {{ Session::get('alert-class') }}">
                  {{ Session::get('message') }}
                </div>
                @endif 
                <!-- Alert message (end) -->

                <form action="/suminvoice/{{$suminvoice_number->id}}/faktur"  enctype='multipart/form-data' method="post" >
                  @method('patch')
                  {{csrf_field()}}

                  <div class="form-group">
                   <label class="control-label col-md-3 col-sm-3 col-xs-12" for="name">File <span class="required">*</span></label>
                   <div class="col-md-6 col-sm-6 col-xs-12">

                     <input type='file' name='file' class="form-control">
                     <input type='hidden' name='tempcode' value="{{$suminvoice_number->tempcode}}">

                     @if ($errors->has('file'))
                     <span class="errormsg text-danger">{{ $errors->first('file') }}</span>
                     @endif
                   </div>
                 </div>

                 <div class="form-group">
                   <div class="col-md-6">
                     <input type="submit" name="submit" value='Submit' class='btn btn-success'>
                   </div>
                 </div>

               </form>
             </div>

           {{--  </div> --}}

         {{-- </div> --}}
         <!-- /.modal-content -->
       </div>
       <!-- /.modal-dialog -->
     </div>
     <!-- /.modal -->


   </div>


   <div class="modal fade" id="modal-wa_invoice">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
       <div class="card-header text-center">
        <h3 class="card-title font-weight-bold"> Message </h3>
      </div>
      <form role="form" method="post" action="/customer/wa">

        @csrf
        <div class="card-body">
         {{--    <div class="form-group">
          <label for="nama">FROM</label>
          <input type="text" class="form-control @error('key') is-invalid @enderror " name="key" id="key"  placeholder="Enter Plan key" value="{{env('WAPISENDER_KEY')}}">
          @error('key')
          <div class="error invalid-feedback">{{ $message }}</div>
          @enderror
        </div> --}}
        <div class="form-group">
          <label for="nama">FROM</label>


          <select name="device" id="device" class="form-control">
            <option value="{{env('WAPISENDER_PAYMENT')}}">WA PAYMENT</option>
            <option value="{{env('WAPISENDER_TICKET')}}">WA NOC</option>

          </select>

        </div>
        <div class="form-group">
         <input type='hidden' name='id_customer' value="{{ $customer->id }}" class="form-control">
         <label for="phone">To  </label>
         <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone"  id="phone" placeholder="Phone" value="{{$customer->phone}}">
         @error('phone')
         <div class="error invalid-feedback">{{ $message }}</div>
         @enderror
       </div>

       <div class="form-group">
        <label for="description">Description  </label>
        @php
        if  ($suminvoice_number->payment_status ==0){ 
          $message = "Remainder";
          $message .= "\n";                     
          $message .= "Yth. ".$customer->name." ";
          $message .= "\nTagihan Customer dengan CID *".$customer->customer_id."* sudah kami Terbitkan sebesar *Rp.".$total."*";
          $message .="\nSilahkan melakukan pembayaran sebelum tanggal 20-".date("m-Y", time());
          $message .="\nUntuk info lebih lengkap silahkan klik link berikut";
          $message .="\nhttp://".tenant_config('domain_name', env("DOMAIN_NAME"))."/suminvoice/".$suminvoice_number->tempcode."/print";
          $message .="\n";

          $message .="\nUntuk pembayaran non-tunai, Mohon mengirimkan bukti transfer ke nomor ini karena nomor sebelumnya sudah tidak aktif .";
          $message .="\n";
          $message .="\nAbaikan pesan ini jika sudah melakukan pembayaran";
          $message .="\n";
          $message .="\n* Alusnet *";
        }
        elseif  ($suminvoice_number->payment_status ==1)
        {
         $message = "Yth. ".$customer->name." ";

         $message .="\n";
         $message .="\nTerimakasih, Pembayaran tagihan Customer dengan CID ".$customer->customer_id." sudah kami *TERIMA* ";
         $message .="\nUntuk info lebih lengkap silahkan klik link";
         $message .="\nhttp://".tenant_config('domain_name', env("DOMAIN_NAME"))."/suminvoice/".$suminvoice_number->tempcode."/print";
         $message .="\n";
         $message .="\n* Payment System Alusnet *";


       }

       else
       $message = "";
       @endphp

       <textarea style="height: 110px;" class="form-control" name="message" id="message" placeholder="Message" value={{$message}} >{{$message}} </textarea>
     </div>

   </div>
   <!-- /.card-body -->

   <div class="card-footer">
    <button type="submit" class="btn btn-primary">Submit</button>
    <button type="button" class="btn btn-default float-right " data-dismiss="modal">Cancel</button>

  </div>
</form>

</div>
<!-- /.modal-content -->
</div>
<!-- /.modal-dialog -->
</div>




<div class="modal fade" id="modal-payment">
  <div class="modal-dialog modal-lg card card-primary card-outline">
    <div class="modal-content ">
            <!-- <div class="modal-header">
             <h5 class="modal-title">drap Marker to Right Posision</h5> 
              
              
           </div>-->
           <div class="modal-body ">
             <div class="content-header">

              <div class="">
                <div class="card-header">
                  <h4 class=" font-weight-bold text-center"> Make Payment  </h4>
                </div>

                <div class="form-group col-md-10">
                </div>
                <form role="form" method="post" action="/suminvoice/{{$suminvoice_number->id }} ">
                  @method('patch')
                  @csrf
                  <input type="hidden"  name="tempcode" value="{{$suminvoice_number->tempcode}}">
                  <div class="card-body row">
                    <div class="form-group m-0 col-md-12">
                     <label for="description">CID / Name #</label>
                     <a href="/customer/{{ $customer->id}}"><strong>{{$customer->customer_id}} | {{$customer->name}} </strong></a></br>
                     <label for="description">Inv Number #</label> 
                     <input type="hidden" name="customer_id" value="{{$customer->customer_id}}">
                     <input type="hidden" name="payment_id" value="{{$suminvoice_number->payment_id}}">
                     <input type="hidden" name="id_customer" value="{{$customer->id}}"> 
                     <input type="hidden" name="id" value="{{$invoice->id}}"> 
                     <input type="hidden" name="number" value="{{$suminvoice_number->number}} ">
                     <input type="hidden" name="customer_name" value="{{$customer->name}} ">  

                     <a href="/suminvoice/{{$suminvoice_number->tempcode}}">{{$suminvoice_number->number}} </a>
                     <hr>
                   </div>
                   

                   <div class="form-group m-0 col-md-6">
                    <label for="invoice_type">  Amount </label>
                    <div class="input-group mb-3">
                      @if (Auth::user()->privilege == 'admin' || Auth::user()->privilege == 'accounting')
                      <input type="text" class="form-control @error('recieve_payment') is-invalid @enderror" 
                      name="recieve_payment" id="recieve_payment"  
                      placeholder="Rp. XXXX.XXXX" value="{{ $total }}">
                      @else
                      <input type="text" class="form-control @error('recieve_payment') is-invalid @enderror" 
                      name="recieve_payment" id="recieve_payment"  
                      placeholder="Rp. XXXX.XXXX" value="{{ $total }}" readonly>
                      @endif
                      @error('recieve_payment')
                      <div class="error invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>


                  </div>
                  <div class="form-group col-md-6">
                    <label for="Kas/Bank">Kas/Bank</label>
                    @if ($bank->isNotEmpty())
                    <select name="payment_point" id="payment_point" class="form-control">
                      <!-- <option value="">Select a payment point</option> -->
                      @foreach ($bank as $akun)
                      <option value="{{ $akun->akun_code }}">{{$akun->akun_code}} : {{ $akun->name }}</option>
                      @endforeach
                    </select>
                    @else
                    <input style="color: red;" type="text" readonly class="form-control " value="Akun Kas atau Bank tidak Ditemukan !">
                    @endif
                  </div>

                  
                  
                  <div class="form-group m-0 col-md-12 ">
                    <label for="qty">note</label>
                    <input type="text" class="form-control @error('note') is-invalid @enderror " name="note" id="note"  placeholder="note" value="{{old('note')}}">
                    @error('note')
                    <div class="error invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="amount">Recieve By</label>
                    <input type="hidden" readonly class="form-control " name="updated_by" id="updated_by"  value="{{ Auth::user()->id }}">
                    
                    <input type="text" readonly class="form-control"   value="{{ Auth::user()->name }}">
                    
                    
                  </div>
                  
                  <div class="col-md-12">
                    @if ($bank->isNotEmpty())
                    <button id="submit-button" type="submit" class="btn btn-primary">Submit</button>
                    @else
                    <button id="submit-button" type="submit" class="btn btn-primary" disabled>Submit</button>
                    @endif
                    <a href="/suminvoice/{{$suminvoice_number->tempcode}}" class="btn btn-default float-right">Cancel</a>
                  </div>

                </form>

              </div>

            </div>
            
          </div>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
      </div>
      <!-- /.modal -->


    </div>




  </section>
  @endsection

  @section('footer-scripts')


  <script>
    function showNotificationOptions(id) {
      Swal.fire({
        title: 'Kirim Pengingat',
        html:
          '<p class="mb-3">Pilih cara pengiriman notifikasi:</p>' +
          '<button onclick="Swal.close(); sendNotification(\'wa\', ' + id + ')" class="btn btn-success m-1"><i class="fab fa-whatsapp"></i> WhatsApp</button>' +
          '<button onclick="Swal.close(); sendNotification(\'fcm\', ' + id + ')" class="btn btn-primary m-1"><i class="fas fa-mobile-alt"></i> Android App</button>' +
          '<button onclick="Swal.close(); sendNotification(\'email\', ' + id + ')" class="btn btn-info m-1"><i class="fas fa-envelope"></i> Email</button>',
        icon: 'question',
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Batal',
      });
    }

    function sendNotification(type, id) {
      fetch(`/suminvoice/remainderinv/${id}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ type: type })
      })
      .then(response => response.json().then(data => ({ ok: response.ok, data })))
      .then(({ ok, data }) => {
        if (ok) {
          Swal.fire('Terkirim!', data.message, 'success');
        } else {
          Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'warning');
        }
      })
      .catch(error => {
        Swal.fire('Error!', 'Terjadi kesalahan saat mengirim notifikasi.', 'error');
        console.error(error);
      });
    }
  </script>


  @endsection

