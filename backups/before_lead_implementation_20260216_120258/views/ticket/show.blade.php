@extends('layout.main')
@section('title',' Ticket')
@inject('statuscustomer', 'App\Statuscustomer')
@inject('plan', 'App\Plan')
@inject('sale', 'App\Sale')
@inject('distpoint', 'App\Distpoint')
@inject('user', 'App\User')

{{-- @section('maps')
{!! $map['js'] !!} --}}
{{-- @endsection --}}

{{-- <script type="text/javascript">
  function copy_name()
  {

    document.getElementById("called_by").value= {!! json_encode($customer->contact_name) !!};
  }
  function copy_called_phone()
  {

    document.getElementById("phone").value= {!! json_encode($customer->phone) !!};
  }
</script> --}}

@section('content')
<section class="content-header">

  <!-- Parent/Child Navigation -->
  @if($ticket->isChild() && $ticket->parent)
  <div class="alert alert-info alert-dismissible shadow-sm">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-level-up-alt"></i> <strong>Child Ticket</strong> - This is a sub-ticket of 
    <a href="/ticket/{{$ticket->parent->id}}" class="alert-link font-weight-bold">
      #{{$ticket->parent->id}} - {{$ticket->parent->tittle}}
    </a>
  </div>
  @endif

  @if($ticket->isParent() && $ticket->children->count() > 0)
  <div class="alert alert-success alert-dismissible shadow-sm">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-sitemap"></i> <strong>Parent Ticket</strong> - Has {{$ticket->children->count()}} sub-ticket(s)
    <span class="badge badge-light ml-2">{{$ticket->getChildrenProgress()}}% Complete</span>
  </div>
  @endif

  <div class="card shadow-lg border-0 rounded-lg">
    <div class="card-header bg-gradient-primary text-white">
      <div class="d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold mb-0">
          <i class="fas fa-ticket-alt mr-2"></i>{{$ticket->tittle}}
        </h3>
        <div class="text-right">
          <small class="badge badge-light">
            <i class="fas fa-hashtag"></i> {{$ticket->id}}
          </small>
          <small class="badge badge-light ml-2">
            <i class="fas fa-user"></i> {{$ticket->create_by}}
          </small>
        </div>
      </div>
    </div>
    
    <div class="card-body">
      <div class="row">
        <!-- Left Column - Customer Info -->
        <div class="col-md-6">
          <div class="card border-left-primary shadow-sm h-100">
            <div class="card-header bg-light">
              <h6 class="font-weight-bold text-primary mb-0">
                <i class="fas fa-user-circle"></i> Customer Information
              </h6>
            </div>
            <div class="card-body">
              <table class="table table-sm table-borderless mb-0">
                <tbody>
                  <tr>
                    <td class="text-muted text-right" style="width: 37%">
                      Schedule :
                    </td>
                    <td class="font-weight-bold">{{$ticket->date}} | {{$ticket->time}}</td>
                  </tr>
                  <tr>
                    <td class="text-muted text-right">
                      CID|Name :
                    </td>
                    <td>
                      <a class="badge badge-primary px-3 py-2" href="/customer/{{$ticket->customer->id}}">
                        <i class="fas fa-external-link-alt"></i> {{$ticket->customer->customer_id}} | {{$ticket->customer->name}}
                      </a>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-muted text-right">
                      Start Bill :
                    </td>
                    <td class="font-weight-bold">{{$ticket->customer->billing_start}}</td>
                  </tr>
                  <tr>
                    <td class="text-muted text-right">
                      Report by :
                    </td>
                    <td class="font-weight-bold">{{$ticket->called_by}}</td>
                  </tr>
                  <tr>
                    <td class="text-muted text-right">
                      Phone :
                    </td>
                    <td>
                      <a class="badge badge-success px-3 py-2" href="https://wa.me/{{'62'.substr(trim($ticket->phone), 1)}}" target="_blank">
                        <i class="fab fa-whatsapp"></i> {{$ticket->phone}}
                      </a>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-muted text-right">
                      Address :
                    </td>
                    <td>
                      <a href="https://www.google.com/maps/place/{{$ticket->customer->coordinate}}" target="_blank" class="text-info">
                        <i class="fas fa-map-marked-alt"></i> {{$ticket->customer->address}}
                      </a>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Right Column - Ticket Info -->
        <div class="col-md-6">
          <div class="card border-left-info shadow-sm h-100">
            <div class="card-header bg-light">
              <h6 class="font-weight-bold text-info mb-0">
                <i class="fas fa-info-circle"></i> Ticket Details
              </h6>
            </div>
            <div class="card-body">
              <?php
              if ($ticket->status == "Open") {
                $color='badge-danger'; 
                $icon='fa-exclamation-circle';
              } elseif ($ticket->status == "Close") {
                $color='badge-secondary';
                $icon='fa-check-circle';
              } elseif ($ticket->status == "Pending") {
                $color='badge-warning';
                $icon='fa-clock';
              } elseif ($ticket->status == "Solve") {
                $color='badge-info';
                $icon='fa-check';
              } else {
                $color='badge-primary';
                $icon='fa-circle';
              }
              ?>
              <table class="table table-sm table-borderless mb-0">
                <tbody>
                  <tr>
                    <td class="text-muted text-right" style="width: 37%">
                      Status :
                    </td>
                    <td>
                      <span class="badge {{$color}} px-3 py-2">
                        <i class="fas {{$icon}}"></i> {{$ticket->status}}
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-muted text-right">
                      Category :
                    </td>
                    <td class="font-weight-bold">{{$ticket->categorie->name}}</td>
                  </tr>
                  <tr>
                    <td class="text-muted text-right">
                      Tags :
                    </td>
                    <td>
                      @foreach ($tags as $id => $name)
                      <span class="badge badge-info mr-1 mb-1">
                        <i class="fas fa-tag"></i> {{ $name }}
                      </span>
                      @endforeach
                    </td>
                  </tr>
                  <tr>
                    <td class="text-muted text-right">
                      Assign to :
                    </td>
                    <td>
                      <span class="font-weight-bold">{{$ticket->user->name}}</span>
                      <span class="badge badge-light ml-2">{{$ticket->member}}</span>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-muted text-right">
                      Sales :
                    </td>
                    <td>
                      <strong>{{ $sale->sale($ticket->customer->id_sale)->name}}</strong>
                      <a href="https://wa.me/{{'62'.substr(trim($sale->sale($ticket->customer->id_sale)->phone), 1)}}" class="badge badge-success ml-2" target="_blank">
                        <i class="fab fa-whatsapp"></i> {{ $sale->sale($ticket->customer->id_sale)->phone}}
                      </a>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-muted text-right">
                      Dist Point :
                    </td>
                    <td>
                      @php
                      $dp = $ticket->customer?->id_distpoint 
                      ? $distpoint->distpoint($ticket->customer->id_distpoint)
                      : null;
                      @endphp
                      @if($dp)
                      <a class="badge badge-primary px-3 py-2" href="/distpoint/{{ $ticket->customer->id_distpoint }}">
                        <i class="fas fa-external-link-alt"></i> {{ $dp->name }}
                      </a>
                      @else
                      <span class="badge badge-secondary">None</span>
                      @endif
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="row mt-4">
        <div class="col-12">
          <div class="card bg-light shadow-sm">
            <div class="card-body">
              <button type="button" class="btn btn-primary shadow-sm mr-2 mb-2" data-toggle="modal" data-target="#modal-ticketedit">
                <i class="fas fa-edit"></i> Edit Ticket
              </button>
              
              <form role="form" method="post" action="/ticket/wa_ticket" class="d-inline">
                @csrf
                <input type='hidden' name='device' value="{{ env('WAPISENDER_TICKET') }}">
                <input type='hidden' name='id_ticket' value="{{ $ticket->id }}">
                <input type='hidden' name='phone' value="{{$ticket->user->phone}}">
                
                @php
                $message  = "Halo, " . $ticket->user->name . "\n\n";
                $message .= "*Ada Tiket buat Kamu nih*\n";
                $message .= "━━━━━━━━━━━━━━━\n";
                $message .= "Judul: " . $ticket->tittle . "\n";
                $message .= "*Member*: " . $ticket->member . "\n";
                $message .= "*Nama Pelanggan*: " . $ticket->customer->name . "\n";
                $message .= "*Nomor HP*: " . $ticket->customer->phone . "\n";
                $message .= "*Alamat*: " . $ticket->customer->address . "\n";
                $message .= "\n 📍 Maps: https://www.google.com/maps/place/".str_replace(' ', '',$ticket->customer->coordinate ). "\n";
                $message .= "━━━━━━━━━━━━━━━\n";
                $message .= "🔗 Silakan cek tiket pada tautan berikut:\n";
                $message .= "👉 https://" . env('DOMAIN_NAME') . "/ticket/" . $ticket->id . "\n\n";
                $message .= "Terima kasih! Jika ada yang perlu dikonfirmasi, silakan hubungi tim terkait.\n\n";
                $message .= "\n~ *" . config("app.signature") . "* ~";
                @endphp
                
                <input type='hidden' name='message' value="{{$message}}">
                <button type="submit" class="btn btn-success shadow-sm mr-2 mb-2">
                  <i class="fab fa-whatsapp"></i> Send Notification
                </button>
              </form>

              @if($ticket->ticket_type !== 'child')
              <button type="button" class="btn btn-info shadow-sm mb-2" onclick="window.location.href='/ticket/{{$ticket->id}}/create-child'">
                <i class="fas fa-plus-circle"></i> Add Sub-Ticket
              </button>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Child Tickets Section -->
      @if($ticket->isParent() && $ticket->children->count() > 0)
      <div class="row mt-4">
        <div class="col-12">
          <div class="card shadow-sm border-left-success">
            <div class="card-header bg-light">
              <h6 class="font-weight-bold text-success mb-0">
                <i class="fas fa-list-ul"></i> Sub-Tickets ({{$ticket->children->count()}})
                <span class="badge badge-success ml-2">{{$ticket->getChildrenProgress()}}% Complete</span>
              </h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th width="60">#ID</th>
                      <th>Title</th>
                      <th>Status</th>
                      <th width="150">Workflow Progress</th>
                      <th>Assigned To</th>
                      <th>Schedule</th>
                      <th>Created</th>
                      <th width="80">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($ticket->children as $child)
                    <?php
                    if ($child->status == "Open") {
                      $color='badge-danger'; $icon='fa-exclamation-circle';
                    } elseif ($child->status == "Close") {
                      $color='badge-secondary'; $icon='fa-check-circle';
                    } elseif ($child->status == "Pending") {
                      $color='badge-warning'; $icon='fa-clock';
                    } elseif ($child->status == "Solve") {
                      $color='badge-info'; $icon='fa-check';
                    } else {
                      $color='badge-primary'; $icon='fa-circle';
                    }
                    
                    // Calculate workflow progress
                    $totalSteps = $child->steps()->count();
                    $currentStepId = $child->current_step_id;
                    $workflowProgress = 0;
                    
                    if ($totalSteps > 0 && $currentStepId) {
                      $currentStep = $child->steps()->where('id', $currentStepId)->first();
                      if ($currentStep) {
                        $currentPosition = $currentStep->position;
                        $workflowProgress = round(($currentPosition / $totalSteps) * 100);
                        
                        // Jika step terakhir bernama "Finish" atau "Close", set 100%
                        if (strtolower($currentStep->name) === 'finish' || strtolower($currentStep->name) === 'close') {
                          $workflowProgress = 100;
                        }
                      }
                    } elseif (in_array($child->status, ['Close', 'Solve'])) {
                      $workflowProgress = 100;
                    }
                    
                    // Progress bar color
                    if ($workflowProgress >= 75) {
                      $progressColor = 'bg-success';
                    } elseif ($workflowProgress >= 50) {
                      $progressColor = 'bg-info';
                    } elseif ($workflowProgress >= 25) {
                      $progressColor = 'bg-warning';
                    } else {
                      $progressColor = 'bg-danger';
                    }
                    ?>
                    <tr>
                      <td class="font-weight-bold">#{{$child->id}}</td>
                      <td>{{$child->tittle}}</td>
                      <td>
                        <span class="badge {{$color}}">
                          <i class="fas {{$icon}}"></i> {{$child->status}}
                        </span>
                      </td>
                      <td>
                        <div class="progress" style="height: 20px;">
                          <div class="progress-bar {{$progressColor}}" role="progressbar" 
                               style="width: {{$workflowProgress}}%;" 
                               aria-valuenow="{{$workflowProgress}}" aria-valuemin="0" aria-valuemax="100">
                            <small class="font-weight-bold">{{$workflowProgress}}%</small>
                          </div>
                        </div>
                        @if($totalSteps > 0)
                        <small class="text-muted d-block mt-1">
                          <i class="fas fa-tasks"></i> {{$child->steps()->where('id', $currentStepId)->first()->name ?? 'N/A'}}
                        </small>
                        @endif
                      </td>
                      <td>{{$child->user->name}}</td>
                      <td><small>{{$child->date}} {{$child->time}}</small></td>
                      <td><small>{{$child->created_at->format('d M Y')}}</small></td>
                      <td>
                        <a href="/ticket/{{$child->id}}" class="btn btn-sm btn-primary">
                          <i class="fas fa-eye"></i>
                        </a>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>

  <!-- Workflow Section -->
  <div class="container-fluid mt-4">
    <div class="row">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header bg-gradient-info text-white">
            <h5 class="mb-0">
              <i class="fas fa-stream"></i> Workflow Progress
            </h5>
          </div>
          <div class="card-body">
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
            <div class="mt-3">
              <button type="button" class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modal-workflow">
                <i class="fas fa-stream"></i> Edit Workflow
              </button>
            </div>

            @else
            @if(!in_array($ticket->status ?? '', ['Solve','Close']))
            <div class="text-center py-4">
              <button id="btn-start-workflow" class="btn btn-success btn-lg shadow-sm">
                <i class="fas fa-play"></i> Start Workflow
              </button>
            </div>
            @endif
            @endif
          </div>
        </div>
      </div>
    </div>
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

  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>


  <script>

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
              Swal.fire({
                title: 'Memproses...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
              });
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
                  Swal.fire('Berhasil!', data.message || 'Workflow dimulai & status tiket jadi Inprogress.', 'success')
                  .then(() => location.reload());
                } else {
                  Swal.fire('Error!', data.message || 'Gagal memulai workflow.', 'error');
                }
              })
              .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'Terjadi kesalahan saat memulai workflow.', 'error');
              });
            }
          });
        });
      }
    });


    
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

  <!-- Ticket Description -->
  <div class="container-fluid mt-4">
    <div class="row">
      <div class="col-12">
        <div class="card shadow-sm border-left-warning">
          <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
              <strong><i class="fas fa-file-alt text-warning"></i> Ticket Description</strong>
              <span class="text-muted"><i class="far fa-clock"></i> {{$ticket->created_at}}</span>
            </div>
          </div>
          <div class="card-body">
            {!! $ticket->description !!}
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Ticket Updates -->
  @foreach( $ticket->ticketdetail as $ticketdetail)
  <div class="container-fluid mt-2">
    <div class="row">
      <div class="col-12">
        <div class="card shadow-sm border-left-success">
          <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
              <strong><i class="fas fa-user-edit text-success"></i> Update by: {{$ticketdetail->updated_by}}</strong>
              <span class="text-muted"><i class="far fa-clock"></i> {{$ticketdetail->created_at}}</span>
            </div>
          </div>
          <div class="card-body">
            {!! $ticketdetail->description !!}
          </div>
        </div>
      </div>
    </div>
  </div>
  @endforeach

  <!-- Update Ticket Button -->
  <div class="container-fluid mt-4 mb-4">
    <div class="row">
      <div class="col-12">
        @if(isset($workflowSteps) && $workflowSteps->count() > 0)
        <button type="button" class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modal-ticketupdate">
          <i class="fas fa-plus-circle"></i> Update Ticket
        </button>
        @else
        <button type="button" class="btn btn-secondary shadow-sm" disabled title="Workflow belum tersedia. Ubah status ke 'Inprogress' untuk membuat workflow otomatis.">
          <i class="fas fa-lock"></i> Update Ticket (Workflow Required)
        </button>
        <!-- <small class="text-muted ml-2">
          <i class="fas fa-info-circle"></i> Ubah status tiket ke <strong>Inprogress</strong> untuk mengaktifkan workflow dan tombol update.
        </small> -->
        @endif
      </div>
    </div>
  </div>

  <!-- Modals Section -->
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


                <form role="form" method="post" action="/ticket/{{$ticket->id}}/editticket">
                  @method('patch')
                  @csrf
                  <input type="hidden" name="id_ticket" value="{{$ticket->id}}">
                  <div class="form-group ">
                    <label for="tittle">Title</label>
                    <div class="input-group ">
                      <input type="text" class="form-control" name="tittle" id="tittle"  placeholder="Ticket tittle" value="{{$ticket->tittle}}">


                    </div>




                    <label for="status">  Status </label>
                    <div class="input-group border-primary ">
                      @php
                      $status=['Open', 'Inprogress','Pending','Solve','Close'];
                      @endphp
                      <select name="status" id="status" class="form-control">
                       {{--  <option selected=""> {{$ticket->status}}</option>
                       <option>Open</option>
                       <option>Inprogress</option>
                       <option>Pandding</option>
                       <option>Close</option> --}}


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
                   <select name="category" id="category" class="form-control">

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


                @php
                // Mengambil ID tag yang dipilih
                $selectedTags = array_keys($tags); // Mengambil ID tag yang dipilih dari array dengan 'name' sebagai value
                @endphp

                <label for="tags">Tags:</label>
                <div class="input-group mb-12">
                  <select style="width:100%" name="tags[]" id="tags" class="form-control select2" multiple="multiple" data-placeholder="Select tags">
                    @foreach ($alltags as $id => $name)
                    <option value="{{ $id }}" {{ in_array($id, $selectedTags) ? 'selected' : '' }}>
                      {{ $name }}
                    </option>
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

</section>
@endsection
