@extends('layout.main')
@section('title',' Ticket')
@inject('statuscustomer', 'App\Statuscustomer')
@inject('plan', 'App\Plan')
@inject('sale', 'App\Sale')
@inject('distpoint', 'App\Distpoint')
@inject('user', 'App\User')



@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">

      <h3 class="card-title font-weight-bold"> Title : {{$ticket->tittle}}    </h3>
      <span class="float-right"><i class="far fa-ticket"></i><small> Id #{{$ticket->id}} | Create by : {{$ticket->create_by}} </small> </span> 

    </div>
    
    <div class="card-body row">



      <table class="table table-borderless col-md-6 table-sm">

        <tbody>

          <tr class="col-md-6">
           <tr>
             <th style="width: 30%" class="text-right">Schedule :</th>
             <td><strong>{{$ticket->date}} | {{$ticket->time}} </strong></td>

           </tr>
           <tr>
             <th style="width: 30%" class="text-right">CID | Name :</th>
             <td><strong>{{$ticket->customer->customer_id}} | {{$ticket->customer->name}}</td>

             </tr>
             <tr>
              <th style="width: 30%" class="text-right">Start Billing :</th>
              <td><strong><a class="">{{$ticket->customer->billing_start}} </a></strong></td>

            </tr>
            <tr>
              <th style="width: 30%" class="text-right">Report by :</th>
              <td><strong><a class=""> {{$ticket->called_by}} </a></strong></td>

            </tr>

            <tr>
              <th style="width: 30%" class="text-right">Phone :</th>
              <td>
                <a class="badge badge-primary" href="https://wa.me/{{'62'.substr(trim($ticket->phone), 1)}}"> {{$ticket->phone}}</a></td>

              </tr>
              <tr>
                <th style="width: 30%" class="text-right">Address :</th>
                <td><a href="https://www.google.com/maps/place/{{ $ticket->customer->coordinate }}" target="_blank" >{{$ticket->customer->address}}</a>  </td>

              </tr>


            </tr>



          </tbody>
        </table>
        <table class="table table-borderless col-md-6 table-sm">

          <tbody>

            <?php
            if ($ticket->status == "Open")

            {
              $color='bg-danger'; 
              $btn_c='bg-danger'; }


              elseif ($ticket->status == "Close")
                {$color='bg-secondary'; 
            }
            elseif ($ticket->status == "Pending")
              {  $color='bg-warning'; 
          }
          elseif ($ticket->status == "Solve")
            {  $color='bg-info'; 
        }
        else
         {  $color='bg-primary'; 
     }

     ?>

     <tr class="col-md-6">
      <tr>
        <th style="width: 30%" class="text-right">Status :</th>
        <td><span class="badge {{$color}} "><strong >{{$ticket->status}}</strong></span> 
        </td>

      </tr>
      <tr>
        <th style="width: 30%" class="text-right">PPPOE :</th>
        <td><strong >{{$ticket->customer->pppoe}} | {{$ticket->customer->password}}</strong> 
        </td>

      </tr>
      <tr>
        <th style="width: 30%" class="text-right">Categori :</th>
        <td><strong >{{$ticket->categorie->name}}</strong> 
        </td>

      </tr>
      <tr>
       <th style="width: 30%" class="text-right">Assign to :</th>
       <td><strong>{{$ticket->user->name}} </strong> ( <a style="color:blue">{{$ticket->member}}</a> )

       </td>

     </tr>
     <tr>
       <th style="width: 30%" class="text-right">Sales :</th>
       <td><strong>{{ $sale->sale($ticket->customer->id_sale)->name}} | <a href="https://wa.me/{{'62'.substr(trim($sale->sale($ticket->customer->id_sale)->phone), 1)}}">{{ $sale->sale($ticket->customer->id_sale)->phone}}</a></strong>

       </td>

     </tr>
     <tr>
      <th style="width: 30%" class="text-right">Dist Point :</th>
      <td colspan="2">{{ $distpoint->distpoint($ticket->customer->id_distpoint)->name}}</td>

    </tr>


  </tr>



</tbody>
</table>
<div class="card-body row">
  <button type="button" class="btn btn-sm btn-primary m-1" data-toggle="modal" data-target="#modal-ticketedit">      Edit Ticket</button>
  <form role="form" method="post" action="/whatsapp/wa_ticket">

    @csrf




    <input type='hidden' name='device' value="{{ env('WAPISENDER_TICKET') }}" class="form-control">
    <input type='hidden' name='id_ticket' value="{{ $ticket->id }}" class="form-control">


    <input type='hidden' name='phone' value="{{$ticket->user->phone}}" class="form-control">

    @php
    $message  = "Hai ".$ticket->user->name.", ";
    $message .= "\n ";
    $message .= "\nMember: ".$ticket->member;
    $message .= "\n ";
    $message .= "\nYou have new ticket with detail below :";
    $message .= "\nCustomer Name : *".$ticket->customer->name."*";

    $message .= "\nPhone: ".$ticket->customer->phone;
    $message .= "\nAddress : ".$ticket->customer->address;
    $message .= "\n";

    $message .= "\nTitle  : *".$ticket->tittle."*";
    // $message .= "\nDescription  : ".$ticket->tittle; $ticket->description;
    $message .= "\n";
    $message .= "\nOpen your ticket on this url : http://".env('DOMAIN_NAME')."/ticket/".$ticket->id;
    if (!empty($ticket->customer->coordinate))
    {
      $message .= "\nMaps: https://www.google.com/maps/place/".str_replace(' ', '',$ticket->customer->coordinate );
    }
    $message .= "\n";
    $message .= "\n*Hd System Alusnet*";

    @endphp


    <input type='hidden' name='message' value="{{$message}}" class="form-control">

    <!-- /.card-body -->


    <button type="submit" class="btn btn-success btn-sm m-1"><i class="fab fa-whatsapp">  </i> Notif </button>



  </form>




</div>

{{-- <table class="table table-borderless col-md-6 table-sm">

  <tbody>

    <tr class="col-md-6">
      <tr>
        <th style="width: 25%" class="text-right">Customer ID (CID) :</th>
        <td>{{$ticket->customer->customer_id}}</td>

      </tr>
      <tr>
       <th style="width: 25%" class="text-right">Customer Name :</th>
       <td>{{$ticket->customer->name}}</td>

     </tr>
     <tr>
      <th style="width: 25%" class="text-right">Contact Name : </th>
      <td colspan="2">{{$ticket->customer->contact_name}}</td>
      
    </tr>
    <tr>
      <th style="width: 25%" class="text-right">Phone : </th>
      <td colspan="2">{{$ticket->customer->phone}}</td>
      
    </tr>
    <tr>
      <th style="width: 25%" class="text-right">Address : </th>
      <td colspan="2">{{$ticket->customer->address}}</td>
      
    </tr>
  </tr>


</tbody>
</table>

<table class="table table-borderless col-md-6 table-sm">

  <tbody>

    <tr class="col-md-6">
      <tr>
        <th style="width: 25%; " class="text-right">Status :</th>
        <th style="color: {{ $statuscustomer->status($ticket->customer->id_status)->color}}">{{ $statuscustomer->status($ticket->customer->id_status)->name}}




        </th>

      </tr>
      <tr>
       <th style="width: 25%" class="text-right">Plan :</th>
       <td>{{ $plan->plan($ticket->customer->id_plan)->name}}</td>

     </tr>
     <tr>
      <th style="width: 25%" class="text-right">Distribution Point :</th>
      <td colspan="2">{{ $distpoint->distpoint($ticket->customer->id_distpoint)->name}}</td>
      
    </tr>
    <tr>
      <th style="width: 25%" class="text-right">Note :</th>
      <td colspan="2">{{$ticket->customer->note}}</td>
      
    </tr>
  </tr>


</tbody>
</table> --}}
{{-- <div class="col-md-12">
  <hr>
</div>
--}}
















<!-- /.card-body -->



</div>
<!-- /.card -->

<!-- Form Element sizes -->







<div class="container-fluid ">
  <div class="row">
    <div class="col-12">

    </div>

    
    <div class="card shadow-sm mt-0 pt-0 col-12">
      @if(isset($workflowSteps) && $workflowSteps->count() > 0)

      @php
      $totalSteps = $workflowSteps->count();
      $currentStepId = $ticket->current_step_id ?? null;
      $currentIndex = $workflowSteps->search(fn($step) => $step->id == $currentStepId);
      $progressPercent = $currentIndex !== false && $totalSteps > 1 
      ? ($currentIndex / ($totalSteps - 1)) * 100 
      : 0;

      $currentStep = $workflowSteps->firstWhere('id', $currentStepId);
      $isFinishStep = $currentStep && strtolower($currentStep->name) === 'finish';
      if ($isFinishStep) {
        $progressPercent = 100;
      }
      @endphp

      <!-- Wrapper Workflow -->
      <div class="workflow-wrapper position-relative">


        <!-- Garis Dasar & Progress -->
        <div class="base-line position-absolute w-100"></div>
        <div class="progress-line position-absolute" style="width: {{ $progressPercent }}%;"></div>

        <!-- Step Item -->
        <div class="d-flex justify-content-start">
          @foreach($workflowSteps as $index => $step)
          @php
          if ($isFinishStep) {
            $class = 'done';
          } else {
            $class = ($currentStepId == $step->id) ? 'active' :
            ($currentIndex !== false && $index < $currentIndex ? 'done' : 'pending');
          }
          @endphp

          <div class="text-center flex-fill">
            <div class="step-dot {{ $class }}">
              <i class="fas {{ $class === 'done' ? 'fa-check' : 'fa-circle-notch' }}"></i>
            </div>
            <span class="step-label small">{{ ucfirst($step->name) }}</span>
          </div>
          @endforeach
        </div>
      </div>

      <!-- Tombol Edit Workflow -->
      <div class="card-body row">
        <button type="button" class="btn btn-primary btn-sm m-1" data-toggle="modal" data-target="#modal-workflow">
          <i class="fas fa-stream"></i> Edit Workflow
        </button>
      </div>

      @else
      @if(!in_array($ticket->status ?? '', ['Solve','Close']))
      <div class="card-body row">
        <button id="btn-start-workflow" class="btn btn-success btn-sm">
          <i class="fas fa-play"></i> Start Workflow
        </button>
      </div>
      @endif
      @endif
    </div>

    <!-- Modal Workflow -->
    <div class="modal fade" id="modal-workflow" tabindex="-1" role="dialog" aria-labelledby="modalWorkflowLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">

          <!-- Header -->
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="modalWorkflowLabel"><i class="fas fa-tasks"></i> Workflow Steps</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <!-- Body -->
          <div class="modal-body">
            <!-- Form tambah step -->
            <form id="addStepForm" class="form-inline mb-3">
              @csrf
              <input type="hidden" id="ticketId" value="{{ $ticket->id }}">
              <input type="text" id="stepName" class="form-control mr-2" placeholder="Nama step baru" required>
              <button class="btn btn-success" type="submit"><i class="fas fa-plus"></i></button>
            </form>

            <!-- List step drag & drop -->
            <ul id="workflow-steps" class="list-group">
              @foreach($workflowSteps as $step)
              @php
              $isCurrent = $ticket->current_step_id == $step->id;
              @endphp
              <li class="list-group-item d-flex justify-content-between align-items-center {{ $isCurrent ? 'bg-primary text-white' : '' }}" 
              style="cursor: grab;" data-step="{{ $step->id }}">
              <!-- Hapus di kiri -->
              <button type="button" class="btn btn-outline-danger btn-sm btn-delete-step mr-2" data-step="{{ $step->id }}">
                <i class="fas fa-trash"></i>
              </button>

              <span class="flex-fill text-center">{{ $step->name }}</span>

              <!-- Pilih di kanan -->
              <button type="button" class="btn btn-outline-primary btn-sm btn-choose-step" data-step="{{ $step->id }}" {{ $isCurrent ? 'disabled' : '' }}>
                Pilih
              </button>
            </li>
            @endforeach
          </ul>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>



  <script>
    document.querySelectorAll('.scroll-arrow').forEach(btn => {
      btn.addEventListener('click', function() {
        const wrapper = this.closest('.workflow-wrapper');
        wrapper.scrollBy({
          left: this.classList.contains('right') ? 150 : -150,
          behavior: 'smooth'
        });
      });
    });
    document.addEventListener("DOMContentLoaded", function () {
      let ticketId = "{{ $ticket->id }}";

  // START workflow jika kosong
      let startBtn = document.getElementById("btn-start-workflow");
      if (startBtn) {
        startBtn.addEventListener("click", function () {
          Swal.fire({
            title: 'Mulai Workflow?',
            text: "Status tiket akan berubah jadi Inprogress dan langkah default akan dibuat.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, mulai',
            cancelButtonText: 'Batal'
          }).then((result) => {
            if (result.isConfirmed) {
              fetch(`/ticket/${ticketId}/workflow/start`, {
                method: "POST",
                headers: {
                  "X-CSRF-TOKEN": "{{ csrf_token() }}",
                  "Content-Type": "application/json"
                }
              })
              .then(res => res.json())
              .then(data => {
                if (data.success) {
                  Swal.fire('Berhasil!', 'Workflow dimulai & status tiket jadi Inprogress.', 'success')
                  .then(() => location.reload());
                }
              });
            }
          });
        });
      }
    });
  </script>

  <script>


    document.addEventListener("DOMContentLoaded", function () {
      let ticketId = "{{ $ticket->id }}";
      let el = document.getElementById("workflow-steps");

    // ✅ Aktifkan drag & drop (tidak mengganggu tombol klik)
      if (el) {
        Sortable.create(el, {
          animation: 150,
            handle: ".list-group-item", // seluruh item masih bisa drag
            filter: ".btn-choose-step, .btn-delete-step", // tombol tetap bisa diklik
            preventOnFilter: true,

            onEnd: function () {
              let order = [];
              document.querySelectorAll('#workflow-steps li').forEach((item, index) => {
                order.push({ id: item.getAttribute("data-step"), position: index + 1 });
              });

              fetch(`/ticket/${ticketId}/workflow/reorder`, {
                method: "POST",
                headers: {
                  "X-CSRF-TOKEN": "{{ csrf_token() }}",
                  "Content-Type": "application/json"
                },
                body: JSON.stringify({ order: order })
              })
              .then(res => res.json())
              .then(data => {
                if (data.success) {
                  Swal.fire({
                    title: 'Urutan Disimpan!',
                    text: 'Urutan langkah telah berhasil diperbarui.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                  }).then(() => {
                    location.reload();
                  });
                }
              });
            }

          });
      }

    // ✅ Handler pilih step (click + touchstart untuk mobile)
      document.querySelectorAll('.btn-choose-step').forEach(btn => {
        ['click', 'touchstart'].forEach(evt => {
          btn.addEventListener(evt, function (e) {
            e.preventDefault();
                e.stopPropagation(); // Hindari konflik drag

                let stepId = this.getAttribute("data-step");

                Swal.fire({
                  title: 'Pilih Step ini?',
                  icon: 'question',
                  showCancelButton: true,
                  confirmButtonText: 'Ya, pilih',
                  cancelButtonText: 'Batal'
                }).then((result) => {
                  if (result.isConfirmed) {
                    Swal.fire({
                      title: 'Memproses...',
                      allowOutsideClick: false,
                      didOpen: () => Swal.showLoading()
                    });

                    fetch(`/ticket/${ticketId}/workflow/move`, {
                      method: "POST",
                      headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Content-Type": "application/json"
                      },
                      body: JSON.stringify({ step_id: stepId })
                    })
                    .then(res => res.json())
                    .then(data => {
                      if (data.success) {
                        Swal.fire('Berhasil!', 'Step berhasil dipilih.', 'success')
                        .then(() => location.reload());
                      }
                    });
                  }
                });
              });
        });
      });

    // ✅ Hapus step (sweetalert)
      document.querySelectorAll('.btn-delete-step').forEach(btn => {
        ['click', 'touchstart'].forEach(evt => {
          btn.addEventListener(evt, function (e) {
            e.preventDefault();
            e.stopPropagation();

            let stepId = this.getAttribute("data-step");

            Swal.fire({
              title: 'Yakin hapus step ini?',
              text: "Data step akan hilang permanen!",
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Ya, hapus',
              cancelButtonText: 'Batal'
            }).then((result) => {
              if (result.isConfirmed) {
                Swal.fire({
                  title: 'Menghapus...',
                  allowOutsideClick: false,
                  didOpen: () => Swal.showLoading()
                });

                fetch(`/ticket/${ticketId}/workflow/delete`, {
                  method: "POST",
                  headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Content-Type": "application/json"
                  },
                  body: JSON.stringify({ step_id: stepId })
                })
                .then(res => res.json())
                .then(data => {
                  if (data.success) {
                    Swal.fire('Dihapus!', 'Step berhasil dihapus.', 'success')
                    .then(() => location.reload());
                  }
                });
              }
            });
          });
        });
      });

    // ✅ Tambahkan step baru
      let addForm = document.getElementById("addStepForm");
      if (addForm) {
        addForm.addEventListener("submit", function(e){
          e.preventDefault();
          let name = document.getElementById("stepName").value;

          Swal.fire({
            title: 'Menambah Step...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
          });

          fetch(`/ticket/${ticketId}/workflow/add`, {
            method: "POST",
            headers: {
              "X-CSRF-TOKEN": "{{ csrf_token() }}",
              "Content-Type": "application/json"
            },
            body: JSON.stringify({ name: name })
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              Swal.fire('Berhasil!', 'Step baru ditambahkan.', 'success')
              .then(() => location.reload());
            }
          });
        });
      }
    });
  </script>
















  <div class="container-fluid ">
    <div class="row">
      <div class="col-12 border-dark tiketview">
        <div class="callout callout-warning" >
          <div class="col-12">
           <strong> Tiket Description </strong>
           <span class="float-right"><i class="far fa-clock"></i><small>{{$ticket->created_at}}</small> </span> 
         </div>
         <hr>
         {!! $ticket->description !!}
       </div>
     </div>
   </div>
 </div>



 @foreach( $ticket->ticketdetail as $ticketdetail)


 <div class="container-fluid ">
  <div class="row">
    <div class="col-12 border-dark tiketview">
      <div class="callout callout-success" >
        <div class="col-12">
         <strong>Update by:{{$ticketdetail->updated_by}} </strong>
         <span class="float-right"><i class="far fa-clock"></i><small> {{$ticketdetail->created_at}}</small></span> 
       </div>
       <hr>
       {!! $ticketdetail->description !!}
     </div>
   </div>
 </div>
</div>




@endforeach
<div class="card-body row">
  @if(isset($workflowSteps) && $workflowSteps->count() > 0)
  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-ticketupdate">
    <i class="fas fa-edit"></i> Update Ticket
  </button>
  @else
  <button type="button" class="btn btn-secondary" disabled title="Workflow belum tersedia. Ubah status ke 'Inprogress' untuk membuat workflow otomatis.">
    <i class="fas fa-lock"></i> Update Ticket (Workflow Required)
  </button>
  <small class="text-muted ml-3">
    <i class="fas fa-info-circle"></i> Workflow belum tersedia. Ubah status ke <strong>Inprogress</strong> untuk mengaktifkan.
  </small>
  @endif
</div>







<div class="modal fade" id="modal-ticketupdate">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
            <!-- <div class="modal-header">
             <h5 class="modal-title">drap Marker to Right Posision</h5> 
              
              
           </div>-->
           <div {{-- class="modal-body" --}}>
             <div {{-- class="content-header" --}}>

              <div class="card card-primary card-outline">
                <div class="card-header">
                  <h3 class="card-title font-weight-bold"> Ticket Update </h3>
                </div>

                <form role="form" method="post" action="/ticketdetail">
                  @csrf
                  <input type="hidden" name="id_ticket" value="{{$ticket->id}}">
                  <input type="hidden" name="updated_by" value=" {{ Auth::user()->name }}">
                  <div class="form-group col-md-12">

                   <label for="nama">Description</label>

                   <!-- tools box -->

                   <!-- /. tools -->

                   <!-- /.card-header -->


                   <textarea name="description" class="textarea" ></textarea>



                 </div>

                 <div class="card-footer col-md-12">
                  <button type="submit" class="btn btn-primary">Submit</button>
                  <button type="button" class="btn btn-default float-right " data-dismiss="modal">cancel</button>

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



  <div class="modal fade" id="modal-ticketedit">
    <div class="modal-dialog modal-lg">
      <div class="modal-content ">
            <!-- <div class="modal-header">
             <h5 class="modal-title">drap Marker to Right Posision</h5> 
              
              
           </div>-->
           <div class="">
             <div class="">

              <div class="card card-primary card-outline pl-5 pr-5">
                <div class="card-header m-auto">
                  <h3 class="card-title font-weight-bold"> Edit Ticket </h3>

                </div>


                <form role="form" method="post" action="/ticket/{{$ticket->id}}/vendoreditticket">
                  @method('patch')
                  @csrf
                  <input type="hidden" name="id_ticket" value="{{$ticket->id}}">
                  <div class="form-group ">
                    <label for="tittle">Title</label>
                    <div class="input-group ">
                      <input readonly type="text" class="form-control" name="tittle" id="tittle"  placeholder="Ticket tittle" value="{{$ticket->tittle}}">


                    </div>




                    <label for="status">  Status </label>
                    <div class="input-group border-primary ">
                      @php
                      $status=['Open', 'Inprogress','Pending','Solve','Close'];
                      @endphp
                      <select name="status" id="status" class="form-control">


                       @foreach ($status as $status)
                       @if ($ticket->status == $status){
                        <option value="{{ $status }}" selected="">{{ $status }}</option>

                      }
                      @else
                      {
                        <option value="{{ $status }}">{{ $status }}</option>
                      }
                      @endif

                      @endforeach



                    </select>
                  </div>



                  <label for="status">  Category: </label>
                  <div class="input-group ">
                    <select disabled name="category" id="category" class="form-control">

                      @foreach ($category as $id => $name)
                      @if ($ticket->id_categori == $id){
                        <option value="{{ $id }}" selected="">{{ $name }}</option>

                      }
                      @else
                      {
                        <option value="{{ $id }}">{{ $name }}</option>
                      }
                      @endif

                      @endforeach
                    </select>
                  </div>





                  <label for="status">  Assign to: </label>
                  <div class="input-group ">
                   <select name="assign_to" id="assign_to" class="form-control">

                    @foreach ($users as $id => $name)
                    @if ($ticket->assign_to == $id){
                      <option value="{{ $id }}" selected="">{{ $name }}</option>

                    }
                    @else
                    {
                      <option value="{{ $id }}">{{ $name }}</option>
                    }
                    @endif

                    @endforeach
                  </select>
                </div>
                <label for="status">  Member : </label>
                <div class="input-group ">

                  <select style="width:100% " name="member[]" class="select2" multiple="multiple" data-placeholder="Select a member" >

                   <option value="{{$ticket->member }}" selected="">{{ $ticket->member }}</option> 
                   {{--  <option value="1">none</option> --}}
                   @foreach ($users as $id => $name)
                   <option value="{{ $name }}">{{ $name }}</option>
                   @endforeach
                 </select>
               </div>

               <div class="input-group ">
                <div class="form-group col-md-6">
                  <label>Schedule Date:</label>
                  <div class="input-group date" id="reservationdate" data-target-input="nearest">
                    <input type="text" name="date" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{$ticket->date}}" />
                    <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>

                <div class="form-group col-md-6">

                 <label>Schedule Time:</label>
                 <div class="input-group bootstrap-timepicker ">
                  <input id="time_updates" name='time' type="time" class="form-control input-small" value="{{$ticket->time}}">
                  <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
                </div>
              </div>

            </div>
            <div class="card-footer bg-light col-md-12">
              <button type="submit" class="btn btn-primary">Submit</button>
              {{-- <a href="{{url('customer')}}" class="btn btn-default float-right">Cancel</a> --}}
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






<div class="modal fade" id="modal-ticketassign">
  <div class="modal-dialog modal-lg">
    <div class="modal-content  ">
            <!-- <div class="modal-header">
             <h5 class="modal-title">drap Marker to Right Posision</h5> 
              
              
           </div>-->
           <div class="modal-body">
             <div class="content-header">

              <div class="card card-primary card-outline p-5">
                <div class="card-header">
                  <h3 class="card-title font-weight-bold"> Update Ticket </h3>
                </div>


                <form role="form" method="post" action="/ticket/{{$ticket->id}}/assign">
                  @method('patch')
                  @csrf
                  <input type="hidden" name="id_ticket" value="{{$ticket->id}}">
                  <label for="assign_to">  Assign To </label>
                  <div class="input-group mb-3">

                    <select name="assign_to" id="assign_to" class="form-control">

                      @foreach ($users as $id => $name)
                      <option value="{{ $id }}">{{ $name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <label for="member">  Assign To </label>
                  <div class="form-group">

                    <div class="form-group col-11">

                      <select style="width:100% " name="member[]" class="select2" multiple="multiple" data-placeholder="Select a member" >
                        <option value="1">none</option>
                        @foreach ($users as $id => $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                      </select>
                    </div>

                  </div>


                  <div class="card-footer col-md-12">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <a href="{{url('customer')}}" class="btn btn-default float-right">Cancel</a>
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
