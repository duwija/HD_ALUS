@extends('layout.main')
@section('title',' Customer Detail')
@section('maps')
@inject('distrouter', 'App\Distrouter')

@endsection


<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>


<script type="text/javascript">
  google.charts.load('current', {packages:["orgchart"]});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {
    var data = new google.visualization.DataTable();

    data.addColumn('string', 'Name');
    data.addColumn('string', 'Manager');
    data.addColumn('string', 'ToolTip');
      //  data.setRowProperty(3, 'style', 'border: 1px solid green');

        // For each orgchart box, provide the name, manager, and tooltip to show.
    data.addRows([



      @foreach ($customer->device as $topology)
      [{'v':'{{$topology->id}}', 'f':'{{$topology->name}}<div style="color:blue;">{{$topology->ip}} <br>{{$topology->type}}</div>'},
       '{{$topology->parrent}}', 'owner: {{$topology->owner}} | Position :{{$topology->position}} | Note :{{$topology->note}}'],

      
      @endforeach
      ]);

    @foreach ($customer->device as $topology)


    data.setRowProperty({{ $loop->iteration-1 }}, 'style', ' border: 0px; ');
    @endforeach


        // Create the chart.
    var chart = new google.visualization.OrgChart(document.getElementById('chart_div_topology'));
        // Draw the chart, setting the allowHtml option to true for the tooltips.
    
    chart.draw(data, {'allowHtml':true});
  }
</script>

<script type="text/javascript">
  function copy_text() {
   navigator.clipboard.writeText('{{$customer->customer_id}}');


 }
 
</script>
@section('content')
<section class="content-header p-0 m-0 p-md-3 m-md-3">


  <div class="card  card-outline ">
    <div class="card-header bg-primary  ">
      <h3 class="card-title font-weight-bold "> Show Detail Customer </h3>
    </div>
    
    <div class="card-body ">




      <div class="row">

        <table class="table table-borderless col-md-6 table-sm">

          <tbody>


            <tr>
              <th style="width: 30%" class="text-right">Customer ID :</th>
              @php

              if ($customer->status_name->name == 'Active')
              $btn_sts = "btn-success";
              elseif ($customer->status_name->name == 'Inactive')
              $btn_sts = "btn-secondary";
              elseif ($customer->status_name->name == 'Block')
              $btn_sts = "btn-danger";
              elseif ($customer->status_name->name == 'Company_Properti')
              $btn_sts = "btn-primary";
              else
              $btn_sts = "btn-warning";

              @endphp
              <input type="hidden" name="cid_copy" id="cid_copy" value="{{$customer->customer_id}}">
              <td><div class=" {{$btn_sts}} badge btn-sm p-2 mr-1 " >{{$customer->customer_id}}


                <strong> | {{$customer->status_name->name}}</strong></div><i class="fa border-secondary  fa-copy btn btn-sm " title="Copy Customer Id" onclick="copy_text()"></i></td>
              </tr>
              <tr>
                <th style="width: 25%; " class="text-right">User PPPOE :</th>
                @if ($countpppoe > 1)
                <td>
                  <a class="badge badge-danger">
                    {{ $customer->pppoe }} | pppoe-conflict
                  </a>
                </td>
                @else
                <td>{{ $customer->pppoe }}</td>
                @endif
              </tr> 
              <tr>
                <th style="width: 25%; " class="text-right">Password :</th>
                <td>{{$customer->password}}</td>
              </tr>
              <tr>
                <th style="width: 31%" class="text-right">Customer Name :</th>
                <td>{{$customer->name}}</td>

              </tr>

              <tr>
                <th style="width: 31%" class="text-right">Contact Name : </th>
                <td colspan="">{{$customer->contact_name}}</td>

              </tr>
              <tr>
                <th style="width: 30%" class="text-right">Merchant : </th>
                <td colspan="">
                  @if(!empty($customer->merchant_name) && !empty($customer->merchant_name->name))
                  <a href="/merchant/{{$customer->merchant_name->id}}" class="bg-info badge">{{ $customer->merchant_name->name }}</a>
                  @else
                  <span>No Merchant</span> <!-- You can change this to whatever default text you want -->
                  @endif
                </td>

              </tr>
              <tr>
                <th style="width: 30%" class="text-right">Date of Birth : </th>
                <td colspan="">{{$customer->date_of_birth}}</td>

              </tr>
              <tr>
                <th style="width: 30%" class="text-right">Phone : </th>
                <td colspan=""><a href="https://wa.me/{{$customer->phone}}"> {{$customer->phone}}</a></td>

              </tr>
              <tr>
                <th style="width: 30%" class="text-right">Address : </th>
                <td colspan="">{{$customer->address}}</td>

              </tr>
              <tr>
                <th style="width: 25%" class="text-right">Sales :</th>

                @php

                if ($customer->id_sale == 0)
                $sale_name = "none";

                else
                $sale_name = $customer->sale_name->name;

                @endphp

                <td colspan="2">{{$sale_name}}</td>

              </tr>
              <tr>
                <th style="width: 25%" class="text-right">Note :</th>
                <td colspan="2">{{$customer->note}}</td>

              </tr>
            </tr>



          </tbody>
        </table>

        <table class="table table-borderless col-md-6 table-sm">

          <tbody>

            <tr class="col-md-6">

              <th style="width: 30%" class="text-right">Notif by :</th>
              <td>
                @php
                $icon = '';
                $label = '';

                switch ($customer->notification) {
                  case 0:
                  $icon = '<i class="fas fa-ban text-muted"></i>';
                  $label = 'None';
                  break;
                  case 1:
                  $icon = '<i class="fab fa-whatsapp text-success"></i>';
                  $label = 'WhatsApp';
                  break;
                  case 2:
                  $icon = '<i class="fas fa-envelope text-primary"></i>';
                  $label = 'Email';
                  break;
                  default:
                  $icon = '<i class="fas fa-ban text-muted"></i>';
                  $label = 'None';
                  break;
                }
                @endphp
                {!! $icon !!} {{ $label }}
              </td>


              <tr>
               <th style="width: 30%" class="text-right">Id Card :</th>
               <td>{{$customer->id_card}}</td>

             </tr>

             <tr>
               <th style="width: 25%" class="text-right">Plan :</th>
               <td>{{$customer->plan_name->name}} ( Rp. {{number_format($customer->plan_name->price, 0, ',', '.')}} )</td>

             </tr>
             <tr>
               <th style="width: 25%" class="text-right">Billing Start :</th>
               <td><a class="bg-info badge"> {{$customer->billing_start}} </a>  <a class="bg-info badge"> Isolir Date : {{$customer->isolir_date}} </a> </td>



             </tr>
             <tr>

              <th style="width: 25%" class="text-right">On Router Status :</th>

              <td>
               <div id="mikrotik-status"><div class="d-flex align-items-center">
                <i class="fas fa-spinner fa-spin text-primary mr-2"></i> 
                <span>Loading router status...</span>
              </div></div>


            </td>

          </tr>
          <tr>
           <th style="width: 25%" class="text-right">Distribution Router :</th>
           <td colspan="2">
            @if ( empty($customer->distrouter->name))

            {{'-'}}
            @else

            <a href="/distrouter/{{ $customer->distrouter->id}}"  class="btn btn-primary btn-sm " target="_blank">{{ $customer->distrouter->name }} | {{ $customer->distrouter->ip }}</a>



            @endif





          </td>

        </tr>
        <tr>

          <th style="width: 25%" class="text-right">OLT | ODP :</th>
          <td colspan="2">
           @if ( empty($customer->distpoint_name->name))

           {{'-'}}
           @else
           <a class="btn btn-sm bg-primary" href="/olt/{{ $customer->olt_name->id ?? '-' }}" >
             {{ $customer->olt_name->name ?? '-' }}
           </a>
           @endif

           @if ( empty($customer->distpoint_name->name))

           {{'-'}}
           @else
           <a class="btn btn-sm bg-primary" href="/distpoint/{{ $customer->distpoint_name->id }}" >
            {{ $customer->distpoint_name->name ?? 'none' }}
          </a>
          @endif


        </td>

      </tr>




      <tr>
        <th style="width: 25%" class="text-right">Ticket :</th>
        <td colspan="2"><a href="/ticket/{{ $customer->id }}/create" title="device" class="btn mt-1 btn-success btn-sm  mr-2"> <i class="fas fa-ticket-alt"></i> Create Ticket </a><a href="/ticket/view/{{ $customer->id }}" title="device" class="btn btn-primary btn-sm mt-1 "> <i class="fas fa-ticket-alt"></i> View Ticket </a></td>

      </tr>
      <tr>
        <th style="width: 25%" class="text-right">Invoice :</th>
        <td colspan="2"><a href="/invoice/{{ $customer->id }}/create" title="device" class="btn btn-success btn-sm  mr-1 mt-1"> <i class="fas fa-plus-circle"></i> Create Invoice </a><a href="/invoice/{{ $customer->id }}" title="device" class="btn mt-1 btn-primary btn-sm  mr-1 mt-1"> <i class="fas fa-file-invoice"></i> View Invoice </a><a href="/customer/{{ $customer->id }}/jurnal" title="device" class="btn mt-1 btn-primary btn-sm mr-1 mt-1"> <i class="fas fa-book"></i> jurnal </a></td>

      </tr>
      <tr>
        <th style="width: 25%" class="text-right">Monitor Tools :</th>
        <input type="hidden" name="ip"  id="ip" value="{{ $customer->distrouter ? $customer->distrouter->ip : '' }}">
        <input type="hidden" name="user"  id="user" value="{{ $customer->distrouter ? $customer->distrouter->user : '' }}">
        <input type="hidden" name="password"  id="password" value="{{ $customer->distrouter ? $customer->distrouter->password : '' }}">
        <input type="hidden" name="port"  id="port" value="{{ $customer->distrouter ? $customer->distrouter->port : '' }}">
        <input type="hidden" name="interface"  id="interface" value="<pppoe-{{$customer->pppoe}}>">
          <td colspan="2">
            {{-- Default tombol dalam keadaan disabled --}}
            <button type="button" id="btn-traffic"
            class="btn mb-1 btn-secondary btn-sm pb-1" 
            data-toggle="modal" data-target="#modal-monitor"
            disabled>
            <i class="fas fa-chart-line"></i> Traffic
          </button>

          <button type="button" id="createTunnelBtn"
          class="btn mb-1 btn-secondary btn-sm pb-1"
          title="Create Tunnel" disabled>
          <i class="fas fa-plug"></i> web
        </button>

        @if (!empty($customer->id_onu))
        <button type="button" name="btn_onu_detail" id="btn_onu_detail"
        class="btn mb-1 bg-info btn-sm pb-1" data-toggle="modal" data-target="#modal_onu_detail">
        <i class="fas fa-sun"></i> {{ $customer->id_onu }}
      </button>

      <button type="button" name="btn_onu_reboot" id="btn_onu_reboot"
      class="btn mb-1 bg-warning btn-sm pb-1" data-toggle="modal" data-target="#modal_reboot">
      <i class="fas fa-sync-alt"></i> reboot
    </button>
    @endif
  </td>


</tr>

<tr>
  <th>
  </th>
  <td>
   <input type="hidden" name="id_olt"  id="id_olt" value="{{$customer->id_olt}}">
   <input type="hidden" name="id_onu"  id="id_onu" value="{{$customer->id_onu}}">
   <a id="ont_status"></a>
   <a id="ont_detail"></a>

 </td>
</tr>
</tbody>
</table>
<div class="card-footer col-md-12 mt-5 mb-5">
 <a href="/customer/{{ $customer->id }}/edit" title="edit" class="btn btn-primary btn-sm "> <i class="fa fa-edit">  </i> Edit </a>
 <a href="/customer/log/{{ $customer->id }}" title="log" class="btn btn-info btn-sm "> <i class="fa fa-history">  </i> log </a>
 <button type="button" class="{{-- float-right  --}}btn bg-success btn-sm" data-toggle="modal" data-target="#modal-wa"> <i class="fab fa-whatsapp">  </i> WA</button>

 @if ($customer->status_name->name == 'Inactive')
 <form  action="/customer/{{ $customer->id }}" method="POST" class="d-inline item-delete " >
  @method('delete')
  @csrf

  <button title="Delete" type="submit"  class="btn btn-danger btn-sm float-right"> <i class="fa fa-times"> </i> Delete </button>
</form>
@else
<button title="Delete" type="submit" disabled=""  class="btn btn-danger btn-sm float-right"> <i class="fa fa-times"> </i> Delete </button>
@endif

</div>

<div class=" col-md-12 card card-primary card-outline pt-2 ">
  <div class="card-header d-flex align-items-center">
    <h3 class="card-title mb-0">File List</h3>

    <div class="ml-auto">
      <button type="button" class="btn bg-gradient-primary btn-sm mr-2" data-toggle="modal" data-target="#modal-customerfile">
        Upload File
      </button>
      <a href="/subscribe/{{ $customer->id }}" class="btn btn-primary btn-sm">
        Form Berlangganan
      </a>
    </div>
  </div>


  <!-- /.card-header -->
  <div class="card-body">
    <table id="example4" class="table table-bordered table-striped">

      <thead >
        <tr>
          <th scope="col">#</th>
          <th scope="col">Name</th>

          <th scope="col">Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach( $customer->file as $file)
        <tr>
          <th scope="row">{{ $loop->iteration }}</th>
          <td>{{ $file->name }}</td>

          <td >
           <a href="{{url ($file->path) }}"  target="_blank" title="Download" class="btn btn-primary btn-sm "> <i class="" aria-hidden="true"></i> Download </a>
           <form  action="/file/customer/{{ $file->id }}" method="POST" class="d-inline distpoint-delete" >
            @method('delete')
            @csrf

            <button title="Delete" type="submit"  class="btn btn-danger btn-sm"> Delete </button>
          </form>

        </td>


        <!-- /.modal -->



      </tr>




      @endforeach

    </tbody>
  </table>
</div>
</div>

<div class=" col-md-6  card card-primary card-outline p-0 m-0 ">


  <a href="/device/{{ $customer->id }}" title="device" class="btn btn-info btn-sm "> <i class="fas fa-network-wired"></i>Manage Topology </a>
  <div class="overflow-auto" id="chart_div_topology"></div>

</div>

<div  style="height: 400px;" class="col-md-6  card card-primary card-outline p-0 m-0">
  <a href="https://www.google.com/maps/place/{{ $customer->coordinate }}" target="_blank" class="btn btn-info btn-sm"><i  class="fa fa-map"> </i> Show in Google Maps </a>

  <div style="width: 100%; height: 400px;"id="map">Map Not Set !!</div>
</div>

</div>
<!-- /.card-body -->



</div>
<!-- /.card -->

<!-- Form Element sizes -->


</div>

<div class="modal fade" id="modal-wa">
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
        <label for="device">FROM</label>


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
      if ($customer->status_name->name == 'Active'){                      {}
      $message = "Yth. ".$customer->name." ";
      $message .= "\nAccount Anda dengan CID ".$customer->customer_id." Saat ini telah *ACTIVE*";
      $message .= "\nSilahkan Menikmati layanan kami dengan aman dan nyaman  ";
      $message .= "\n*".config('app.signature')."*";
    }
    elseif ($customer->status_name->name == 'Inactive')
    {
     $message = "Yth. ".$customer->name." ";
     $message .= "\nAccount Anda dengan CID ".$customer->customer_id." Saat ini dalam masa *INACTIVE*";
     $message .= "\nSilahkan menghubungi bagian Payment untuk informasi lebih lanjut";
     $message .= "\n*".config('app.signature')."*";
   }
   elseif ($customer->status_name->name == 'Block')
   {
    $message = "Yth. ".$customer->name." ";
    $message .= "\nAccount Anda dengan CID ".$customer->customer_id." Saat ini telah *TERISOLIR*";
    $message .= "\nSilahkan menghubungi bagian Payment untuk informasi lebih lanjut";
    $message .= "\n*".config('app.signature')."*";
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

<!-- Modal -->


<div class="modal fade" id="modal_reboot" tabindex="-1" aria-labelledby="modalRebootLabel" aria-hidden="true">
  <div class="modal-dialog ">
    <div class="modal-content shadow-lg rounded">

      <!-- Modal Header -->
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title" id="modalRebootLabel">
          <i class="fas fa-exclamation-triangle"></i> Confirmation
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body text-center">
        <p class="fs-5"><h5>Are you sure</h5> </p>
        <p class="fs-5">reboot this ONU?</p>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer d-flex justify-content-between">
        @php
        $portId = null;
        $value = null;

        if (isset($customer->id_onu) && strpos($customer->id_onu, ':') !== false) {
          list($key, $value) = explode(":", $customer->id_onu, 2);
          $portId = config('zteframeslotportid')[$key] ?? null;
        }
        @endphp

        @if($portId !== null && $value !== null)
        <form onsubmit="confirmSubmit(event, 'Reboot This ONU!')" action="{{ url('/olt/reboot/' . $customer->id_olt . '/' . $portId . '/' . $value) }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-warning px-4" title="Reboot">
            <i class="fas fa-sync-alt"></i> Reboot
          </button>
        </form>
        @endif

        <button type="button" class="btn btn-default float-right " data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>



<div class="modal fade" id="modal_onu_detail" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">ONU Detail - {{ $customer->id_onu }}</h5>
      </div>
      <div class="modal-body modal-dialog-scrollable" id="modal-body-content">
        <div id="onu_detail" name="onu_detail">
          <div class="fa-3x">
            <i class="fas fa-cog fa-spin"></i>
          </div>
          <a>Getting data from OLT.....</a>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default float-right " data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="modal-customerfile">
  <div class="modal-dialog modal-lg">
    <div class="modal-content ">
            <!-- <div class="modal-header">
             <h5 class="modal-title">drap Marker to Right Posision</h5> 
              
              
           </div>-->
           {{-- <div class="modal-body"> --}}
             {{--   <div class="content-header"> --}}

              <div class="card card-primary card-outline p-5">
                <div class="card-header">
                  <h3 class="card-title font-weight-bold"> Upload File </h3>
                </div>


                <!-- Alert message (start) -->
                @if(Session::has('message'))
                <div class="alert {{ Session::get('alert-class') }}">
                  {{ Session::get('message') }}
                </div>
                @endif 
                <!-- Alert message (end) -->

                <form action="/file"  enctype='multipart/form-data' method="post" >
                 {{csrf_field()}}

                 <div class="form-group">
                   <label class="control-label col-md-3 col-sm-3 col-xs-12" for="name">File <span class="required">*</span></label>
                   <div class="col-md-6 col-sm-6 col-xs-12">
                    <input type='hidden' name='id_customer' value="{{ $customer->id }}" class="form-control">

                    <input type='file' name='file' class="form-control">

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

 <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logModalLabel">Customer Log</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered" id="logTable">
          <thead>
            <tr>
              <th>Tanggal & Waktu</th>
              <th>Customer</th>
              <th>Diubah Oleh</th>
              <th>Perubahan</th>
            </tr>
          </thead>
          <tbody>
            <!-- Log entries will be populated here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>



<div class="modal fade" id="modal-monitor">
  <div class="modal-dialog modal-lg">
    <div class="modal-content ">

      <div class="card card-primary card-outline ">
        {{-- <div class="card-header">
          <h3 class="card-title font-weight-bold"> Monitoring </h3>
        </div> --}}


        <div class="row">
          <div class="col-md-12 mt-1">
            <div class="card">
              <div id="graph"></div>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered">
                <tr>
                  <th>Interace</th>
                  <th>TX</th>
                  <th>RX</th>
                </tr>
                <tr>
                  <td><a>pppoe-{{$customer->customer_id}}</a></td>
                  <td><div id="tabletx"></div></td>
                  <td><div id="tablerx"></div></td>
                </tr>
              </table>
            </div>

          </div>
        </div>


      </div>

    </div>
    <!-- /.modal-dialog -->
  </div>
  <!-- /.modal -->

</div>


</section>

@endsection
@section('footer-scripts')

<script>
  // Wait for the document to be fully loaded
  // $(document).ready(function() {
  //   // Get ONT status on page load
  //   getOntStatus();

  //   // Get ONT details on page load
  //         //   getOntDetail();
  // });

  // Function to get ONT status
  function getOntStatus() {
    // tampilkan spinner loading di awal
    // $('#ont_status').html(`
    //   <div class="d-flex align-items-center">
    //   <i class="fas fa-spinner fa-spin text-info mr-2"></i> 
    //   <span>Loading ONT status...</span>
    //   </div>
    //   `);

    // validasi id_onu & id_olt sebelum kirim request
    let idOnu = $('#id_onu').val();
    let idOlt = $('#id_olt').val();

    if (!idOnu || !idOlt) {
      $('#ont_status').html(
        `<span class="badge badge-secondary">No OLT/ONU data available</span>`
        );
        return; // hentikan eksekusi
      }

      $.ajax({
        url: '/olt/ont_status',
        method: 'POST',
        data: {
          id_onu: idOnu,
          id_olt: idOlt
        },
        success: function (data) {
          $('#ont_status').html(data);
        },
        error: function (xhr) {
          console.warn('Error fetching ONT status:', xhr.status, xhr.responseText);
          $('#ont_status').html(
            `<span class="badge badge-danger">
            Failed to fetch ONT status (${xhr.status})
            </span>`
            );
        }
      });
    }

  // Function to get ONT details
    function getOntDetail() {

     $.ajax({
      url: '/olt/onu_detail',
      method: 'POST',
      data: {
        id_onu: $('#id_onu').val(),  // Using jQuery to get the value
        id_olt: $('#id_olt').val()   // Using jQuery to get the value

      },
      success: function(data) {
        $('#onu_detail').html(data);  // Update HTML with the received data
      },
      error: function(xhr, status, error) {
        console.log('Error fetching ONT details: ' + error);  // Error handling
      }
    });
   }

  // Optional: Trigger functions on a specific event (if required)
  // Example:
   $('#btn_onu_detail').on('click', function() {

    getOntDetail();
  });

   var center = @json($center);
   var locations = @json($locations);

    // Ubah koordinat pusat menjadi array [lat, lng]
   var coordinates = center.coordinate.split(',').map(Number);

    // Inisialisasi peta
   var map = L.map('map').setView(coordinates, center.zoom);

   L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

    // Tambahkan marker untuk setiap lokasi
   locations.forEach(location => {
        var coords = location.customer.split(',').map(Number); // Ubah string koordinat jadi [lat, lng]
        L.marker(coords).addTo(map)
        .bindPopup(location.name);
      });
    </script>


    <script>
      let remoteIp = null; // Default kosong
      let IdCustomer = {{ $customer->id }};

// Ambil status router saat halaman load


// Event tombol create tunnel
      document.getElementById('createTunnelBtn').addEventListener('click', function () {
        if (!remoteIp) {
          Swal.fire({
            icon: 'error',
            title: 'Invalid IP Address',
            text: 'Remote IP address is missing.',
          });
          return;
        }

        Swal.fire({
          title: 'Creating Tunnel...',
          text: 'Please wait while the tunnel is being created.',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        fetch('/customer/createtunnel', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ IdCustomer: IdCustomer, remoteIp: remoteIp })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Tunnel Created!',
              text: 'Opening port...',
              timer: 2000,
              showConfirmButton: false
            });
            setTimeout(() => {
              window.open(`http://${data.host}:${data.port}`, '_blank');
            }, 2000);
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Failed to Create Tunnel',
              text: data.message || 'Unknown error.',
            });
          }
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: error.message,
          });
        });
      });




      function loadRouterStatus(customerId) {

       $.get(`/customer/${customerId}/router-status`, function (res) {
        if (!res.success) {
          $("#mikrotik-status").html(`<span class="badge badge-danger">${res.message}</span>`);
          return;
        }
        remoteIp = res.ip;
        let html = `
        <div class="btn ${res.btn_status} btn-sm mr-2">${res.status_user}</div>
        <a href="http://${res.ip}" class="btn ${res.btn_online} btn-sm" target="_blank">
        ${res.online} | ${res.ip} | ${res.uptime}
        </a>
        `;
        if (parseInt(res.ip_count) > 1) {
          html += `<span class="badge badge-danger ml-2">IP Conflict</span>`;
        }
        $("#mikrotik-status").html(html);
        $("#btn-traffic")
        .prop("disabled", false)
        .removeAttr("disabled")
        .removeClass("btn-secondary")
        .addClass(res.btn_status || "btn-warning")
        .attr("title", "Show Traffic");

            // Aktifkan tombol Web Tunnel
        $("#createTunnelBtn")
        .prop("disabled", false)
        .removeAttr("disabled")
        .removeClass("btn-secondary")
        .addClass(res.btn_status || "btn-warning");
      });
     }
     $(document).ready(function () {
      const customerId = {{ $customer->id }};
      loadRouterStatus(customerId);
      getOntStatus();

      setInterval(() => {
        loadRouterStatus(customerId);
        getOntStatus();
      }, 30000); 

    });





  </script>



  @endsection
