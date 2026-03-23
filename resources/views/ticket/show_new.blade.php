@extends('layout.main')
@section('title','Detail Tiket')
@inject('statuscustomer', 'App\Statuscustomer')
@inject('plan', 'App\Plan')
@inject('sale', 'App\Sale')
@inject('distpoint', 'App\Distpoint')
@inject('user', 'App\User')

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">#{{ $ticket->id }} — {{ $ticket->tittle }}</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/ticket">Tiket</a></li>
          <li class="breadcrumb-item active">Detail</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
<div class="container-fluid">

  {{-- Parent / Child banner --}}
  @if($ticket->isChild() && $ticket->parent)
  <div class="alert alert-info py-2 mb-3">
    <i class="fas fa-level-up-alt mr-1"></i>
    Sub-tiket dari <a href="/ticket/{{ $ticket->parent->id }}" class="alert-link font-weight-bold">#{{ $ticket->parent->id }} — {{ $ticket->parent->tittle }}</a>
  </div>
  @endif
  @if($ticket->isParent() && $ticket->children->count() > 0)
  <div class="alert alert-success py-2 mb-3">
    <i class="fas fa-sitemap mr-1"></i>
    Tiket ini memiliki <strong>{{ $ticket->children->count() }}</strong> sub-tiket
    <span class="badge badge-light ml-1">{{ $ticket->getChildrenProgress() }}% selesai</span>
  </div>
  @endif

  {{-- ═══ Action Bar ═══ --}}
  <div class="mb-3 d-flex flex-wrap" style="gap:6px">
    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modal-ticketedit">
      <i class="fas fa-edit mr-1"></i> Edit Tiket
    </button>
    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modal-notify">
      <i class="fas fa-bell mr-1"></i> Kirim Notifikasi
    </button>
    @if($ticket->ticket_type !== 'child')
    <button type="button" class="btn btn-sm btn-info" onclick="window.location.href='/ticket/{{ $ticket->id }}/create-child'">
      <i class="fas fa-plus mr-1"></i> Sub-Tiket
    </button>
    @endif
    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#modal-ticketupdate">
      <i class="fas fa-comment-alt mr-1"></i> Update
    </button>
    <a href="/ticket" class="btn btn-sm btn-secondary">
      <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>
  </div>

  {{-- ═══ Info Card ═══ --}}
  <div class="card mb-3" style="border:1px solid #dee2e6">
    <div class="card-header d-flex align-items-center justify-content-between" style="background:#f4f6f8;border-bottom:1px solid #dee2e6">
      <span class="font-weight-bold"><i class="fas fa-ticket-alt mr-2 text-primary"></i>Informasi Tiket</span>
      @php
        $statusColors = ['Open'=>'danger','Inprogress'=>'primary','Pending'=>'warning','Solve'=>'info','Close'=>'secondary'];
        $statusIcons  = ['Open'=>'fa-exclamation-circle','Inprogress'=>'fa-spinner','Pending'=>'fa-clock','Solve'=>'fa-check','Close'=>'fa-check-circle'];
        $sc = $statusColors[$ticket->status] ?? 'primary';
        $si = $statusIcons[$ticket->status]  ?? 'fa-circle';
      @endphp
      <span class="badge badge-{{ $sc }} px-3 py-1" style="font-size:.85rem">
        <i class="fas {{ $si }} mr-1"></i>{{ $ticket->status }}
      </span>
    </div>
    <div class="card-body" style="background:#fff">
      <div class="row">

        {{-- Kolom Kiri: Data Pelanggan --}}
        <div class="col-md-6 mb-2">
          <table class="table table-sm table-borderless mb-0">
            <tbody>
              <tr>
                <td class="text-muted" style="width:38%;white-space:nowrap">Pelanggan</td>
                <td>
                  <a class="badge badge-primary px-3 py-1" href="/customer/{{ $ticket->customer->id }}">
                    <i class="fas fa-external-link-alt mr-1"></i>{{ $ticket->customer->customer_id }} | {{ $ticket->customer->name }}
                  </a>
                </td>
              </tr>
              <tr>
                <td class="text-muted">Jadwal</td>
                <td class="font-weight-bold">{{ $ticket->date }} {{ $ticket->time }}</td>
              </tr>
              <tr>
                <td class="text-muted">Billing Start</td>
                <td>{{ $ticket->customer->billing_start }}</td>
              </tr>
              <tr>
                <td class="text-muted">Dilaporkan</td>
                <td>{{ $ticket->called_by }}</td>
              </tr>
              <tr>
                <td class="text-muted">Telepon</td>
                <td>
                  @if($ticket->phone)
                  <a href="https://wa.me/{{ '62'.substr(trim($ticket->phone), 1) }}" target="_blank" class="badge badge-success px-2 py-1">
                    <i class="fab fa-whatsapp mr-1"></i>{{ $ticket->phone }}
                  </a>
                  @else <span class="text-muted">—</span> @endif
                </td>
              </tr>
              <tr>
                <td class="text-muted">Alamat</td>
                <td>
                  <a href="https://www.google.com/maps/place/{{ $ticket->customer->coordinate }}" target="_blank" class="text-info">
                    <i class="fas fa-map-marker-alt mr-1"></i>{{ $ticket->customer->address }}
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        {{-- Kolom Kanan: Detail Tiket --}}
        <div class="col-md-6 mb-2">
          <table class="table table-sm table-borderless mb-0">
            <tbody>
              <tr>
                <td class="text-muted" style="width:38%;white-space:nowrap">Kategori</td>
                <td class="font-weight-bold">{{ $ticket->categorie->name }}</td>
              </tr>
              <tr>
                <td class="text-muted">Tags</td>
                <td>
                  @foreach ($tags as $id => $name)
                  <span class="badge badge-info mr-1">{{ $name }}</span>
                  @endforeach
                </td>
              </tr>
              <tr>
                <td class="text-muted">Assign to</td>
                <td>
                  <strong>{{ $ticket->user->name }}</strong>
                  @if($ticket->member)
                  <small class="text-muted ml-1">({{ $ticket->member }})</small>
                  @endif
                </td>
              </tr>
              <tr>
                <td class="text-muted">Sales</td>
                <td>
                  @php $salesObj = $sale->sale($ticket->customer->id_sale); @endphp
                  {{ $salesObj->name ?? '—' }}
                  @if($salesObj->phone ?? null)
                  <a href="https://wa.me/{{ '62'.substr(trim($salesObj->phone), 1) }}" class="badge badge-success ml-1" target="_blank">
                    <i class="fab fa-whatsapp"></i>
                  </a>
                  @endif
                </td>
              </tr>
              <tr>
                <td class="text-muted">Dist Point</td>
                <td>
                  @php
                  $dp = $ticket->customer?->id_distpoint ? $distpoint->distpoint($ticket->customer->id_distpoint) : null;
                  @endphp
                  @if($dp)
                  <a class="badge badge-primary px-2 py-1" href="/distpoint/{{ $ticket->customer->id_distpoint }}">
                    <i class="fas fa-external-link-alt mr-1"></i>{{ $dp->name }}
                  </a>
                  @else <span class="text-muted">—</span> @endif
                </td>
              </tr>
              <tr>
                <td class="text-muted">Dibuat</td>
                <td><small class="text-muted">{{ $ticket->created_at }} &mdash; {{ $ticket->create_by }}</small></td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>

  {{-- ═══ Workflow ═══ --}}
  <div class="card mb-3" style="border:1px solid #dee2e6">
    <div class="card-header d-flex align-items-center justify-content-between" style="background:#f4f6f8;border-bottom:1px solid #dee2e6">
      <span class="font-weight-bold"><i class="fas fa-stream mr-2 text-info"></i>Workflow Progress</span>
      @if(isset($workflowSteps) && $workflowSteps->count() > 0)
      <button type="button" class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#modal-workflow">
        <i class="fas fa-cog mr-1"></i> Kelola Step
      </button>
      @endif
    </div>
    <div class="card-body py-3" style="background:#fff">
      @if(isset($workflowSteps) && $workflowSteps->count() > 0)
        @php
          $totalSteps     = $workflowSteps->count();
          $currentStepId  = $ticket->current_step_id ?? null;
          $currentIndex   = $workflowSteps->search(fn($s) => $s->id == $currentStepId);
          $progressPercent = $currentIndex !== false && $totalSteps > 1
                            ? ($currentIndex / ($totalSteps - 1)) * 100
                            : 0;
          $currentStep     = $workflowSteps->firstWhere('id', $currentStepId);
          $isFinishStep    = $currentStep && in_array(strtolower($currentStep->name), ['finish', 'close']);
          if ($isFinishStep) $progressPercent = 100;
        @endphp

        <div class="mb-3 d-flex align-items-center flex-wrap" style="gap:6px">
          @if($currentStep)
            <span class="badge badge-primary px-3 py-1" style="font-size:.8rem">
              <i class="fas fa-map-marker-alt mr-1"></i>{{ ucfirst($currentStep->name) }}
            </span>
            <small class="text-muted">{{ $currentIndex + 1 }}/{{ $totalSteps }} step &bull; {{ round($progressPercent) }}% selesai</small>
          @else
            <span class="badge badge-secondary">Belum dimulai</span>
          @endif
        </div>

        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
          <div style="min-width:{{ $workflowSteps->count() * 90 }}px;padding:36px 0 8px;position:relative;">
            <div style="position:absolute;top:18px;left:5%;width:90%;height:3px;background:#dee2e6;z-index:1;"></div>
            <div style="position:absolute;top:18px;left:5%;height:3px;background:#007bff;z-index:2;width:{{ $progressPercent * 0.9 }}%;transition:width .4s;"></div>
            <div class="d-flex" style="position:relative;z-index:3;">
              @foreach($workflowSteps as $index => $step)
                @php
                  if ($isFinishStep) { $cls = 'done'; }
                  else {
                    $cls = ($currentStepId == $step->id) ? 'active'
                         : ($currentIndex !== false && $index < $currentIndex ? 'done' : 'pending');
                  }
                  $bg   = $cls === 'done' ? '#28a745' : ($cls === 'active' ? '#007bff' : '#e9ecef');
                  $fg   = $cls === 'pending' ? '#6c757d' : '#fff';
                  $size = $cls === 'active' ? '36px' : '28px';
                  $lw   = $cls === 'active' ? '700' : '400';
                  $lc   = $cls === 'done' ? '#28a745' : ($cls === 'active' ? '#0056b3' : '#6c757d');
                @endphp
                <div class="text-center" style="flex:1;min-width:80px;padding:0 4px;">
                  <div style="width:{{ $size }};height:{{ $size }};border-radius:50%;background:{{ $bg }};color:{{ $fg }};
                              display:flex;align-items:center;justify-content:center;margin:0 auto 6px;font-size:12px;
                              box-shadow:{{ $cls==='active' ? '0 0 0 4px rgba(0,123,255,.2)' : 'none' }};">
                    @if($cls === 'done') <i class="fas fa-check"></i>
                    @elseif($cls === 'active') <i class="fas fa-circle" style="font-size:9px;"></i>
                    @else <span style="font-size:11px;font-weight:600;">{{ $index + 1 }}</span>
                    @endif
                  </div>
                  <span style="font-size:.72rem;font-weight:{{ $lw }};color:{{ $lc }};word-break:break-word;line-height:1.2;display:block;">
                    {{ ucfirst($step->name) }}
                  </span>
                  @if($cls !== 'pending' && $step->updated_at)
                  <small style="font-size:.62rem;color:#aaa;display:block;margin-top:2px;">
                    {{ $step->updated_at->format('d/m H:i') }}
                  </small>
                  @endif
                </div>
              @endforeach
            </div>
          </div>
        </div>

      @else
        @if(!in_array($ticket->status ?? '', ['Solve','Close']))
        <div class="text-center py-3">
          <button id="btn-start-workflow" class="btn btn-success">
            <i class="fas fa-play mr-1"></i> Mulai Workflow
          </button>
        </div>
        @else
        <p class="text-muted mb-0 text-center"><i class="fas fa-check-circle text-success mr-1"></i>Tiket sudah selesai.</p>
        @endif
      @endif
    </div>
  </div>

  {{-- ═══ Sub-Tiket ═══ --}}
  @if($ticket->isParent() && $ticket->children->count() > 0)
  <div class="card mb-3" style="border:1px solid #dee2e6">
    <div class="card-header" style="background:#f4f6f8;border-bottom:1px solid #dee2e6">
      <span class="font-weight-bold">
        <i class="fas fa-list-ul mr-2 text-success"></i>Sub-Tiket
        <span class="badge badge-success ml-1">{{ $ticket->children->count() }}</span>
        <span class="badge badge-light ml-1">{{ $ticket->getChildrenProgress() }}% selesai</span>
      </span>
    </div>
    <div class="card-body p-0" style="background:#fff">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>#ID</th><th>Judul</th><th>Status</th><th>Progress</th>
              <th>Assign</th><th>Jadwal</th><th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($ticket->children as $child)
            @php
              $sc2 = $statusColors[$child->status] ?? 'primary';
              $si2 = $statusIcons[$child->status]  ?? 'fa-circle';
              $ts2 = $child->steps()->count();
              $cs2 = $child->current_step_id;
              $pct = 0;
              if ($ts2 > 0 && $cs2) {
                $cstep = $child->steps()->where('id', $cs2)->first();
                if ($cstep) {
                  $pct = round(($cstep->position / $ts2) * 100);
                  if (in_array(strtolower($cstep->name), ['finish','close'])) $pct = 100;
                }
              } elseif (in_array($child->status, ['Close','Solve'])) { $pct = 100; }
              $pc = $pct >= 75 ? 'bg-success' : ($pct >= 50 ? 'bg-info' : ($pct >= 25 ? 'bg-warning' : 'bg-danger'));
            @endphp
            <tr>
              <td><strong>#{{ $child->id }}</strong></td>
              <td>{{ $child->tittle }}</td>
              <td><span class="badge badge-{{ $sc2 }}"><i class="fas {{ $si2 }} mr-1"></i>{{ $child->status }}</span></td>
              <td style="min-width:110px">
                <div class="progress" style="height:14px;border-radius:7px">
                  <div class="progress-bar {{ $pc }}" style="width:{{ $pct }}%"><small>{{ $pct }}%</small></div>
                </div>
                @if($ts2 > 0)
                <small class="text-muted">{{ $child->steps()->where('id', $cs2)->first()->name ?? 'N/A' }}</small>
                @endif
              </td>
              <td><small>{{ $child->user->name }}</small></td>
              <td><small>{{ $child->date }}<br>{{ $child->time }}</small></td>
              <td><a href="/ticket/{{ $child->id }}" class="btn btn-xs btn-primary"><i class="fas fa-eye"></i></a></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  {{-- ═══ Deskripsi ═══ --}}
  <div class="card mb-3" style="border:1px solid #dee2e6">
    <div class="card-header d-flex justify-content-between align-items-center" style="background:#f4f6f8;border-bottom:1px solid #dee2e6">
      <span class="font-weight-bold"><i class="fas fa-file-alt mr-2 text-warning"></i>Deskripsi Tiket</span>
      <small class="text-muted"><i class="far fa-clock mr-1"></i>{{ $ticket->created_at }}</small>
    </div>
    <div class="card-body" style="background:#fff">
      {!! $ticket->description !!}
    </div>
  </div>

  {{-- ═══ Update Log ═══ --}}
  @foreach($ticket->ticketdetail as $detail)
  <div class="card mb-2" style="border:1px solid #dee2e6">
    <div class="card-header d-flex justify-content-between align-items-center" style="background:#f4f6f8;border-bottom:1px solid #dee2e6">
      <span class="font-weight-bold"><i class="fas fa-user-edit mr-2 text-success"></i>Update — {{ $detail->updated_by }}</span>
      <small class="text-muted"><i class="far fa-clock mr-1"></i>{{ $detail->created_at }}</small>
    </div>
    <div class="card-body" style="background:#fff">
      {!! $detail->description !!}
    </div>
  </div>
  @endforeach

  <div class="mb-5"></div>

</div>
</section>

{{-- ════════════════════ MODALS ════════════════════ --}}

{{-- Update --}}
<div class="modal fade" id="modal-ticketupdate" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-comment-alt mr-2"></i>Update Tiket</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form role="form" method="post" action="/ticketdetail">
        @csrf
        <input type="hidden" name="id_ticket" value="{{ $ticket->id }}">
        <input type="hidden" name="updated_by" value="{{ Auth::user()->name }}">
        <div class="modal-body">
          <label>Deskripsi</label>
          <textarea name="description" class="textarea form-control"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit --}}
<div class="modal fade" id="modal-ticketedit" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Tiket</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form role="form" method="post" action="/ticket/{{ $ticket->id }}/editticket">
        @method('patch')
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label>Judul</label>
            <input type="text" class="form-control" name="tittle" value="{{ $ticket->tittle }}" required>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                  @foreach(['Open','Inprogress','Pending','Solve','Close'] as $s)
                  <option value="{{ $s }}" {{ $ticket->status == $s ? 'selected' : '' }}>{{ $s }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Kategori</label>
                <select name="category" class="form-control">
                  @foreach($category as $id => $name)
                  <option value="{{ $id }}" {{ $ticket->id_categori == $id ? 'selected' : '' }}>{{ $name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Tags</label>
            @php $selectedTags = array_keys($tags); @endphp
            <select style="width:100%" name="tags[]" class="form-control select2" multiple data-placeholder="Pilih tag">
              @foreach($alltags as $id => $name)
              <option value="{{ $id }}" {{ in_array($id, $selectedTags) ? 'selected' : '' }}>{{ $name }}</option>
              @endforeach
            </select>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Assign To</label>
                <select name="assign_to" class="form-control">
                  @foreach($users as $id => $name)
                  <option value="{{ $id }}" {{ $ticket->assign_to == $id ? 'selected' : '' }}>{{ $name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Member</label>
                <select style="width:100%" name="member[]" class="select2 form-control" multiple data-placeholder="Pilih member">
                  <option value="{{ $ticket->member }}" selected>{{ $ticket->member }}</option>
                  @foreach($users as $id => $name)
                  <option value="{{ $name }}">{{ $name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tanggal Jadwal</label>
                <div class="input-group date" id="reservationdate" data-target-input="nearest">
                  <input type="text" name="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{ $ticket->date }}">
                  <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Waktu Jadwal</label>
                <input id="time_updates" name="time" type="time" class="form-control" value="{{ $ticket->time }}">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Workflow --}}
<div class="modal fade" id="modal-workflow" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-tasks mr-2"></i>Kelola Workflow Steps</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="addStepForm" class="form-inline mb-3">
          @csrf
          <input type="hidden" id="ticketId" value="{{ $ticket->id }}">
          <input type="text" id="stepName" class="form-control mr-2 flex-fill" placeholder="Nama step baru" required>
          <button class="btn btn-success" type="submit"><i class="fas fa-plus mr-1"></i> Tambah</button>
        </form>
        <ul id="workflow-steps" class="list-group">
          @foreach($workflowSteps as $step)
          @php $isCurrent = $ticket->current_step_id == $step->id; @endphp
          <li class="list-group-item d-flex align-items-center {{ $isCurrent ? 'bg-primary text-white' : '' }}"
              style="cursor:grab" data-step="{{ $step->id }}">
            <button type="button" class="btn btn-outline-danger btn-sm btn-delete-step mr-2" data-step="{{ $step->id }}">
              <i class="fas fa-trash"></i>
            </button>
            <span class="flex-fill">
              {{ $step->name }}
              @if($step->updated_at)
              <small class="d-block {{ $isCurrent ? 'text-white-50' : 'text-muted' }}" style="font-size:.75rem">
                {{ $step->updated_at->format('Y-m-d H:i:s') }}
              </small>
              @endif
            </span>
            <button type="button" class="btn btn-{{ $isCurrent ? 'light' : 'outline-primary' }} btn-sm btn-choose-step"
                    data-step="{{ $step->id }}" {{ $isCurrent ? 'disabled' : '' }}>
              Pilih
            </button>
          </li>
          @endforeach
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

{{-- Notifikasi --}}
<div class="modal fade" id="modal-notify" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form method="POST" action="{{ url('ticket/notify') }}">
        @csrf
        <input type="hidden" name="id_ticket" value="{{ $ticket->id }}">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-bell mr-2"></i>Kirim Notifikasi</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-light border py-2 mb-3">
            <i class="fas fa-user mr-1 text-primary"></i>
            <strong>{{ $ticket->user->name ?? '—' }}</strong>
            @if($ticket->user->phone)
              &nbsp;<small class="text-muted"><i class="fab fa-whatsapp text-success"></i> {{ $ticket->user->phone }}</small>
            @endif
            @if($ticket->user->email)
              &nbsp;<small class="text-muted"><i class="fas fa-envelope text-info"></i> {{ $ticket->user->email }}</small>
            @endif
            @if($ticket->user->fcm_token)
              &nbsp;<span class="badge badge-success"><i class="fas fa-mobile-alt"></i> App Aktif</span>
            @else
              &nbsp;<span class="badge badge-secondary"><i class="fas fa-mobile-alt"></i> App Offline</span>
            @endif
          </div>
          <div class="form-group mb-2">
            <label class="font-weight-bold mb-1">Kirim via:</label>
            <div class="d-flex flex-wrap" style="gap:12px">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="ch_wa" name="channels[]" value="whatsapp"
                  {{ $ticket->user->phone ? 'checked' : 'disabled' }}>
                <label class="custom-control-label" for="ch_wa">
                  <i class="fab fa-whatsapp text-success"></i> WhatsApp
                  @unless($ticket->user->phone)<small class="text-muted">(kosong)</small>@endunless
                </label>
              </div>
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="ch_email" name="channels[]" value="email"
                  {{ $ticket->user->email ? '' : 'disabled' }}>
                <label class="custom-control-label" for="ch_email">
                  <i class="fas fa-envelope text-info"></i> Email
                  @unless($ticket->user->email)<small class="text-muted">(kosong)</small>@endunless
                </label>
              </div>
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="ch_app" name="channels[]" value="app"
                  {{ $ticket->user->fcm_token ? 'checked' : 'disabled' }}>
                <label class="custom-control-label" for="ch_app">
                  <i class="fas fa-mobile-alt text-primary"></i> App
                  @unless($ticket->user->fcm_token)<small class="text-muted">(offline)</small>@endunless
                </label>
              </div>
            </div>
          </div>
          <div class="form-group mb-0 mt-3">
            <label class="font-weight-bold">Pesan <small class="text-muted font-weight-normal">(opsional)</small></label>
            <textarea name="message" class="form-control form-control-sm" rows="3"
              placeholder="Mis: Mohon segera ditindaklanjuti..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success" onclick="return validateNotifyChannels()">
            <i class="fas fa-paper-plane mr-1"></i> Kirim
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ════════════════════ SCRIPTS ════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function validateNotifyChannels() {
  var checked = document.querySelectorAll('#modal-notify input[name="channels[]"]:checked');
  if (checked.length === 0) { alert('Pilih minimal satu channel notifikasi.'); return false; }
  return true;
}

document.addEventListener("DOMContentLoaded", function () {
  var ticketId = "{{ $ticket->id }}";

  // Start Workflow
  var startBtn = document.getElementById("btn-start-workflow");
  if (startBtn) {
    startBtn.addEventListener("click", function () {
      Swal.fire({ title: 'Mulai Workflow?', text: "Status tiket akan berubah jadi Inprogress.", icon: 'question',
        showCancelButton: true, confirmButtonText: 'Ya, mulai', cancelButtonText: 'Batal' })
      .then(function (result) {
        if (!result.isConfirmed) return;
        Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch('/ticket/' + ticketId + '/workflow/start', {
          method: "POST", headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}", "Content-Type": "application/json" }
        }).then(r => r.json()).then(function (d) {
          if (d.success) Swal.fire('Berhasil!', d.message || 'Workflow dimulai.', 'success').then(() => location.reload());
          else Swal.fire('Error!', d.message || 'Gagal.', 'error');
        }).catch(() => Swal.fire('Error!', 'Terjadi kesalahan.', 'error'));
      });
    });
  }

  // Drag & Drop
  var el = document.getElementById("workflow-steps");
  if (el) {
    Sortable.create(el, { animation: 150, filter: ".btn-choose-step, .btn-delete-step", preventOnFilter: true,
      onEnd: function () {
        var order = [];
        document.querySelectorAll('#workflow-steps li').forEach(function (item, i) {
          order.push({ id: item.getAttribute("data-step"), position: i + 1 });
        });
        fetch('/ticket/' + ticketId + '/workflow/reorder', {
          method: "POST", headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}", "Content-Type": "application/json" },
          body: JSON.stringify({ order: order })
        }).then(r => r.json()).then(function (d) {
          if (d.success) Swal.fire({ title: 'Urutan Disimpan!', icon: 'success', confirmButtonText: 'OK' }).then(() => location.reload());
        });
      }
    });
  }

  // Pilih Step
  document.querySelectorAll('.btn-choose-step').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault(); e.stopPropagation();
      var stepId = this.getAttribute("data-step");
      Swal.fire({ title: 'Pilih Step ini?', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal' })
      .then(function (r) {
        if (!r.isConfirmed) return;
        Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch('/ticket/' + ticketId + '/workflow/move', {
          method: "POST", headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}", "Content-Type": "application/json" },
          body: JSON.stringify({ step_id: stepId })
        }).then(r => r.json()).then(function (d) {
          if (d.success) Swal.fire('Berhasil!', 'Step dipilih.', 'success').then(() => location.reload());
        });
      });
    });
  });

  // Hapus Step
  document.querySelectorAll('.btn-delete-step').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault(); e.stopPropagation();
      var stepId = this.getAttribute("data-step");
      Swal.fire({ title: 'Hapus step ini?', text: 'Data akan hilang permanen!', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' })
      .then(function (r) {
        if (!r.isConfirmed) return;
        Swal.fire({ title: 'Menghapus...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch('/ticket/' + ticketId + '/workflow/delete', {
          method: "POST", headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}", "Content-Type": "application/json" },
          body: JSON.stringify({ step_id: stepId })
        }).then(r => r.json()).then(function (d) {
          if (d.success) Swal.fire('Dihapus!', 'Step berhasil dihapus.', 'success').then(() => location.reload());
        });
      });
    });
  });

  // Tambah Step
  var addForm = document.getElementById("addStepForm");
  if (addForm) {
    addForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var name = document.getElementById("stepName").value;
      Swal.fire({ title: 'Menambah Step...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
      fetch('/ticket/' + ticketId + '/workflow/add', {
        method: "POST", headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}", "Content-Type": "application/json" },
        body: JSON.stringify({ name: name })
      }).then(r => r.json()).then(function (d) {
        if (d.success) Swal.fire('Berhasil!', 'Step ditambahkan.', 'success').then(() => location.reload());
      });
    });
  }
});
</script>

@endsection

@push('summernote-script')
<script src="{{ url('dashboard/plugins/summernote/summernote-bs4.min.js') }}"></script>
@endpush
