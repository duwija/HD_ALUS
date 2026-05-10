@extends('layout.main')
@section('title','Detail Tiket')
@inject('statuscustomer', 'App\Statuscustomer')
@inject('plan', 'App\Plan')
@inject('sale', 'App\Sale')
@inject('distpoint', 'App\Distpoint')
@inject('user', 'App\User')

@section('content')
<style>
@media (max-width:575.98px) {
  /* Prevent full-page horizontal overflow */
  .content > .container-fluid { overflow-x:hidden; }
  /* Hide breadcrumb on small screens to save space */
  .content-header .breadcrumb { display:none !important; }
  /* Ticket title smaller on xs */
  .content-header h1 { font-size:1.25rem; }
  /* Action bar buttons smaller on xs */
  .tkt-action-bar .btn { font-size:.8rem; padding:.25rem .5rem; }
  /* Info table labels: left-align on xs instead of right-align */
  .tkt-info-lbl { text-align:left !important; }
  /* Allow long badges to wrap their text */
  .tkt-badge-wrap { white-space:normal !important; word-break:break-word !important; }
  /* Update log header: wrap when both sides are long */
  .tkt-update-hdr { flex-wrap:wrap !important; row-gap:4px; }
  /* Shrink coordinate badge font on xs */
  .tkt-coord-badge { font-size:.7rem; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:inline-block; vertical-align:middle; }
}
/* Constrain images inside update/description card bodies (all screen sizes) */
.tkt-desc-body img {
  max-width: 100% !important;
  height: auto !important;
  display: block;
  border-radius: 4px;
}

/* Ticket show dark mode refinement */
body.dark-mode .content .card[style*="border:1px solid #dee2e6"] {
  border-color: #3a445a !important;
  background: #1f2735 !important;
}

body.dark-mode .content .card-header[style*="background:#f4f6f8"] {
  background: #273246 !important;
  border-color: #3a445a !important;
  color: #e5e7eb !important;
}

body.dark-mode .content .card-body[style*="background:#fff"] {
  background: #1f2735 !important;
  color: #e5e7eb !important;
}

body.dark-mode .content .alert.alert-light {
  background: #253146 !important;
  border-color: #3a445a !important;
  color: #e5e7eb !important;
}

body.dark-mode .content .table thead.thead-light th {
  background: #2b364b !important;
  color: #dbe2ef !important;
  border-color: #46526b !important;
}

body.dark-mode .content .table td,
body.dark-mode .content .table th {
  border-color: #3a445a !important;
}

body.dark-mode .content .progress {
  background-color: #334155 !important;
}

body.dark-mode .content .badge.badge-light {
  background-color: #334155 !important;
  color: #e2e8f0 !important;
}

body.dark-mode .content .tkt-desc-body a:not(.badge) {
  color: #8ecbff !important;
}

body.dark-mode .content div[style*="background:#dee2e6"][style*="height:3px"] {
  background: #425068 !important;
}

body.dark-mode .content div[style*="background:#e9ecef"] {
  background: #475569 !important;
  color: #e2e8f0 !important;
}

body.dark-mode #modal-ticketupdate .modal-content,
body.dark-mode #modal-ticketedit .modal-content,
body.dark-mode #modal-workflow .modal-content,
body.dark-mode #modal-notify .modal-content,
body.dark-mode #modal-pause-ticket .modal-content {
  background: #1f2735;
  color: #e5e7eb;
}

body.dark-mode #modal-ticketupdate .modal-header,
body.dark-mode #modal-ticketedit .modal-header,
body.dark-mode #modal-workflow .modal-header,
body.dark-mode #modal-notify .modal-header,
body.dark-mode #modal-pause-ticket .modal-header,
body.dark-mode #modal-ticketupdate .modal-footer,
body.dark-mode #modal-ticketedit .modal-footer,
body.dark-mode #modal-workflow .modal-footer,
body.dark-mode #modal-notify .modal-footer,
body.dark-mode #modal-pause-ticket .modal-footer {
  border-color: #3a445a;
}
</style>
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
  <div class="mb-3 d-flex flex-wrap tkt-action-bar" style="gap:6px">
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
    @php
      $workflowStarted = isset($workflowSteps)
                      && $workflowSteps->count() > 0
                      && !empty($ticket->current_step_id);
    @endphp
    @if($workflowStarted)
    <button type="button" id="btn-update-ticket" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#modal-ticketupdate">
      <i class="fas fa-comment-alt mr-1"></i> Update
    </button>
    @else
    <button type="button" id="btn-update-ticket" class="btn btn-sm btn-warning" onclick="
      Swal.fire({
        title: 'Workflow Belum Dimulai',
        html: 'Silakan klik <strong>Mulai Workflow</strong> terlebih dahulu sebelum melakukan update tiket.',
        icon: 'warning',
        confirmButtonText: 'OK'
      });
    ">
      <i class="fas fa-comment-alt mr-1"></i> Update
    </button>
    @endif
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
                <td class="text-muted text-right tkt-info-lbl" style="width:38%;white-space:nowrap">Pelanggan</td>
                <td>
                  <a class="badge badge-primary px-3 py-1 tkt-badge-wrap" href="/customer/{{ $ticket->customer->id }}">
                    <i class="fas fa-external-link-alt mr-1"></i>{{ $ticket->customer->customer_id }} | {{ $ticket->customer->name }}
                  </a>
                </td>
              </tr>
              <tr>
                <td class="text-muted text-right tkt-info-lbl">Jadwal</td>
                <td class="font-weight-bold">{{ $ticket->date }} {{ $ticket->time }}</td>
              </tr>
              <tr>
                <td class="text-muted text-right tkt-info-lbl">Billing Start</td>
                <td>{{ $ticket->customer->billing_start }}</td>
              </tr>
              <tr>
                <td class="text-muted text-right tkt-info-lbl">Dilaporkan</td>
                <td>{{ $ticket->called_by }}</td>
              </tr>
              <tr>
                <td class="text-muted text-right tkt-info-lbl">Telepon</td>
                <td>
                  @if($ticket->phone)
                  <a href="https://wa.me/{{ '62'.substr(trim($ticket->phone), 1) }}" target="_blank" class="badge badge-success px-2 py-1">
                    <i class="fab fa-whatsapp mr-1"></i>{{ $ticket->phone }}
                  </a>
                  @else <span class="text-muted">—</span> @endif
                </td>
              </tr>
              <tr>
                <td class="text-muted text-right tkt-info-lbl">Alamat</td>
                <td>
                  <a href="https://www.google.com/maps/place/{{ $ticket->customer->coordinate }}" target="_blank" class="text-info" style="word-break:break-word">
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
                <td class="text-muted text-right tkt-info-lbl" style="width:38%;white-space:nowrap">Kategori</td>
                <td class="font-weight-bold">{{ $ticket->categorie->name }}</td>
              </tr>
              <tr>
                <td class="text-muted text-right tkt-info-lbl">Tags</td>
                <td>
                  @foreach ($tags as $id => $name)
                  <span class="badge badge-info mr-1">{{ $name }}</span>
                  @endforeach
                </td>
              </tr>
              <tr>
                <td class="text-muted text-right tkt-info-lbl">Assign to</td>
                <td>
                  <strong>{{ $ticket->user->name }}</strong>
                  @if($ticket->member)
                  <small class="text-muted ml-1">({{ $ticket->member }})</small>
                  @endif
                </td>
              </tr>
              <tr>
                <td class="text-muted text-right tkt-info-lbl">Sales</td>
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
                <td class="text-muted text-right tkt-info-lbl">Dist Point</td>
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
                <td class="text-muted text-right tkt-info-lbl">Dibuat</td>
                <td><small class="text-muted">{{ $ticket->created_at }} &mdash; {{ $ticket->create_by }}</small></td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>

  {{-- ═══ Workflow ═══ --}}
  @php
    $wfStepsForMttr = isset($workflowSteps) ? $workflowSteps : collect();
    $mttrStartAt = $wfStepsForMttr->count() > 0
      ? ($wfStepsForMttr->first()->created_at ?? $ticket->created_at)
      : $ticket->created_at;
    $finishStepForMttr = $wfStepsForMttr->firstWhere('name', 'Finish');
    $isTicketFinishedForMttr = ($finishStepForMttr && $ticket->current_step_id == $finishStepForMttr->id)
                             || in_array($ticket->status ?? '', ['Close', 'Solve']);
    $mttrEndAt = null;
    if ($isTicketFinishedForMttr) {
      if ($finishStepForMttr && $finishStepForMttr->updated_at && $finishStepForMttr->updated_at != $finishStepForMttr->created_at) {
        $mttrEndAt = $finishStepForMttr->updated_at;
      } elseif (!empty($ticket->solved_at)) {
        $mttrEndAt = \Carbon\Carbon::parse($ticket->solved_at);
      } else {
        $mttrEndAt = $ticket->updated_at;
      }
    }
    $mttrPauseSegments = collect($ticketPauses ?? [])->map(function($p){
      return [
        'start' => $p->paused_at ? $p->paused_at->toIso8601String() : null,
        'end'   => $p->resumed_at ? $p->resumed_at->toIso8601String() : null,
      ];
    })->values();
  @endphp
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

        <div class="alert alert-light border py-2 px-3 mb-3">
          <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:10px;">
            <div>
              <small class="text-muted d-block">MTTR Sedang Berjalan</small>
              <div id="mttr-live-value" class="font-weight-bold" style="font-size:1.1rem;line-height:1.2">0j 0m</div>
            </div>
            <div class="d-flex align-items-center" style="gap:8px;">
              <label for="mttr-mode" class="mb-0 text-muted" style="font-size:.8rem;">Mode Hitung</label>
              <select id="mttr-mode" class="form-control form-control-sm" style="min-width:240px;">
                <option value="effective" selected>Efektif (tanpa waktu stop)</option>
                <option value="total">Total (termasuk waktu stop)</option>
              </select>
            </div>
          </div>
          <small id="mttr-live-note" class="text-muted d-block mt-1">
            Mulai: {{ \Carbon\Carbon::parse($mttrStartAt)->format('d/m/Y H:i') }}
          </small>
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
          <button id="btn-start-workflow" class="btn btn-success gps-required-btn" disabled title="Menunggu izin GPS...">
            <i class="fas fa-spinner fa-spin mr-1"></i> Mulai Workflow
          </button>
        </div>
        @else
        <p class="text-muted mb-0 text-center"><i class="fas fa-check-circle text-success mr-1"></i>Tiket sudah selesai.</p>
        @endif
      @endif

      {{-- ── Pause / Resume buttons ──────────────────────────────── --}}
      @if(isset($workflowSteps) && $workflowSteps->count() > 0 && !in_array($ticket->status ?? '', ['Solve','Close']))
      <div class="mt-3 pt-3 border-top d-flex align-items-center flex-wrap" style="gap:8px">
        @if($isPaused ?? false)
          {{-- Tampilkan alasan pause aktif --}}
          @php $activePause = ($ticketPauses ?? collect())->firstWhere('resumed_at', null); @endphp
          <span class="badge badge-warning px-3 py-2" style="font-size:.82rem">
            <i class="fas fa-pause-circle mr-1"></i>
            Sedang Berhenti: <em>{{ $activePause->reason ?? '' }}</em>
            <small class="ml-1 text-dark">({{ $activePause ? $activePause->paused_at->diffForHumans() : '' }})</small>
          </span>
          <button type="button" class="btn btn-sm btn-success gps-required-btn" id="btn-resume-ticket" disabled>
            <i class="fas fa-play mr-1"></i> Lanjut Kembali
          </button>
        @else
          <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#modal-pause-ticket">
            <i class="fas fa-pause mr-1"></i> Stop / Berhenti Sementara
          </button>
        @endif
        @if(isset($ticketPauses) && $ticketPauses->count() > 0)
        <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="collapse" data-target="#pause-history">
          <i class="fas fa-history mr-1"></i> Riwayat Berhenti ({{ $ticketPauses->count() }})
        </button>
        @endif
      </div>
      {{-- Pause history collapsible --}}
      @if(isset($ticketPauses) && $ticketPauses->count() > 0)
      <div id="pause-history" class="collapse mt-2">
        <table class="table table-sm table-bordered mb-0 mt-1">
          <thead class="thead-light"><tr>
            <th>#</th><th>Alasan</th><th>Dihentikan oleh</th><th>Waktu Berhenti</th><th>Dilanjutkan</th><th>Durasi</th>
          </tr></thead>
          <tbody>
            @foreach($ticketPauses as $i => $p)
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $p->reason }}</td>
              <td>{{ $p->paused_by }}</td>
              <td><small>{{ $p->paused_at->format('d/m/Y H:i') }}</small></td>
              <td>
                @if($p->resumed_at)
                  <small>{{ $p->resumed_at->format('d/m/Y H:i') }}</small><br>
                  <small class="text-muted">oleh {{ $p->resumed_by }}</small>
                @else
                  <span class="badge badge-warning">Belum dilanjutkan</span>
                @endif
              </td>
              <td>
                @if($p->resumed_at)
                  {{ $p->paused_at->diffInMinutes($p->resumed_at) }} mnt
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
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
    <div class="card-body tkt-desc-body" style="background:#fff">
      {!! $ticket->description !!}
    </div>
  </div>

  {{-- ═══ Update Log ═══ --}}
  @foreach($ticket->ticketdetail as $detail)
  <div class="card mb-2" style="border:1px solid #dee2e6">
    <div class="card-header d-flex justify-content-between align-items-center tkt-update-hdr" style="background:#f4f6f8;border-bottom:1px solid #dee2e6">
      <span class="font-weight-bold"><i class="fas fa-user-edit mr-2 text-success"></i>Update — {{ $detail->updated_by }}</span>
      <div class="text-right">
        @if($detail->coordinate)
        @php $isMobile = ($detail->device_type ?? 'D') === 'M'; @endphp
        <span class="mr-2" style="display:inline-flex;align-items:center;gap:3px">
          <a href="https://www.google.com/maps/place/{{ $detail->coordinate }}" target="_blank"
             class="badge badge-success tkt-coord-badge" title="{{ $detail->coordinate }}">
            <i class="fas fa-map-marker-alt mr-1"></i>{{ $detail->coordinate }}
          </a>
          @if($detail->device_type)
          <span class="badge {{ $isMobile ? 'badge-info' : 'badge-secondary' }}"
                title="{{ $isMobile ? 'Dikirim dari perangkat Mobile' : 'Dikirim dari Desktop' }}">
            <i class="fas {{ $isMobile ? 'fa-mobile-alt' : 'fa-desktop' }} mr-1"></i>{{ $detail->device_type }}
          </span>
          @endif
        </span>
        @endif
        <small class="text-muted"><i class="far fa-clock mr-1"></i>{{ $detail->created_at }}</small>
      </div>
    </div>
    <div class="card-body tkt-desc-body" style="background:#fff">
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
        <input type="hidden" name="coordinate" id="update-coordinate" value="">
        <input type="hidden" name="device_type" id="update-device-type" value="">
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
            <button type="button" class="btn btn-{{ $isCurrent ? 'light' : 'outline-primary' }} btn-sm btn-choose-step {{ $isCurrent ? '' : 'gps-required-btn' }}"
                    data-step="{{ $step->id }}" {{ $isCurrent ? 'disabled' : 'disabled' }} data-current="{{ $isCurrent ? '1' : '0' }}">
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

{{-- Pause Ticket Modal --}}
<div class="modal fade" id="modal-pause-ticket" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-pause-circle mr-2 text-warning"></i>Stop / Berhenti Sementara</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group mb-0">
          <label class="font-weight-bold">Alasan Berhenti <span class="text-danger">*</span></label>
          <textarea id="pause-reason" class="form-control mt-1" rows="3" maxlength="500"
            placeholder="Mis: Menunggu material, pelanggan tidak ada di rumah, dll..."></textarea>
          <small class="text-muted">Masukkan alasan kenapa pekerjaan dihentikan sementara.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-warning" id="btn-confirm-pause">
          <i class="fas fa-pause mr-1"></i> Hentikan Sementara
        </button>
      </div>
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
          method: "POST", headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}", "Content-Type": "application/json" },
          body: JSON.stringify({ coordinate: window._gpsCoordinate || null, device_type: window._gpsDeviceType || null })
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
          body: JSON.stringify({ step_id: stepId, coordinate: window._gpsCoordinate || null, device_type: window._gpsDeviceType || null })
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

  // ── Pause / Berhenti Sementara ────────────────────────────────────────
  var btnConfirmPause = document.getElementById('btn-confirm-pause');
  if (btnConfirmPause) {
    btnConfirmPause.addEventListener('click', function () {
      var reason = document.getElementById('pause-reason').value.trim();
      if (!reason) {
        Swal.fire('Perhatian', 'Alasan berhenti harus diisi.', 'warning');
        return;
      }
      Swal.fire({ title: 'Hentikan pekerjaan?', text: 'Kamu bisa melanjutkan kembali nanti.', icon: 'question',
        showCancelButton: true, confirmButtonText: 'Ya, hentikan', cancelButtonText: 'Batal' })
      .then(function (r) {
        if (!r.isConfirmed) return;
        Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch('/ticket/' + ticketId + '/workflow/pause', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
          body: JSON.stringify({ reason: reason, coordinate: window._gpsCoordinate || null, device_type: window._gpsDeviceType || null })
        }).then(r => r.json()).then(function (d) {
          if (d.success) {
            Swal.fire('Dihentikan!', 'Pekerjaan dihentikan sementara.', 'success').then(() => location.reload());
          } else {
            Swal.fire('Error!', d.message || 'Gagal menyimpan.', 'error');
          }
        }).catch(() => Swal.fire('Error!', 'Terjadi kesalahan.', 'error'));
      });
    });
  }

  // ── Resume / Lanjut Kembali ────────────────────────────────────────────
  var btnResume = document.getElementById('btn-resume-ticket');
  if (btnResume) {
    btnResume.addEventListener('click', function () {
      Swal.fire({ title: 'Lanjutkan pekerjaan?', icon: 'question',
        showCancelButton: true, confirmButtonText: 'Ya, lanjut', cancelButtonText: 'Batal' })
      .then(function (r) {
        if (!r.isConfirmed) return;
        Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch('/ticket/' + ticketId + '/workflow/resume', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
          body: JSON.stringify({ coordinate: window._gpsCoordinate || null, device_type: window._gpsDeviceType || null })
        }).then(r => r.json()).then(function (d) {
          if (d.success) {
            Swal.fire('Dilanjutkan!', 'Pekerjaan dilanjutkan. Berhenti selama ' + d.duration_minutes + ' menit.', 'success')
              .then(() => location.reload());
          } else {
            Swal.fire('Error!', d.message || 'Gagal.', 'error');
          }
        }).catch(() => Swal.fire('Error!', 'Terjadi kesalahan.', 'error'));
      });
    });
  }

});

// ── GPS Coordinate capture ──────────────────────────────────────────────
window._gpsCoordinate = null;
window._gpsDeviceType = null;

function _enableGpsButtons() {
  // Aktifkan tombol Mulai Workflow
  var btnStart = document.getElementById('btn-start-workflow');
  if (btnStart) {
    btnStart.disabled = false;
    btnStart.title = '';
    btnStart.innerHTML = '<i class="fas fa-play mr-1"></i> Mulai Workflow';
  }
  // Aktifkan tombol Pilih Step (hanya yang bukan current step)
  document.querySelectorAll('.btn-choose-step.gps-required-btn').forEach(function(btn) {
    if (btn.getAttribute('data-current') !== '1') {
      btn.disabled = false;
    }
  });
  // Aktifkan tombol Resume jika ada
  var btnResume = document.getElementById('btn-resume-ticket');
  if (btnResume) btnResume.disabled = false;
  // Isi device_type ke hidden input form Update
  var elDt = document.getElementById('update-device-type');
  if (elDt) elDt.value = window._gpsDeviceType || '';
}

function _showGpsDenied() {
  // Update button tidak terpengaruh GPS
  // Workflow buttons tetap terkunci tanpa GPS
}

function _detectDevice() {
  var ua = navigator.userAgent || '';
  var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|Tablet/i.test(ua)
               || (navigator.maxTouchPoints > 1 && /Macintosh/i.test(ua) === false);
  return isMobile ? 'M' : 'D';
}

function _requestGps() {
  if (!navigator.geolocation) {
    // GPS tidak didukung — workflow buttons tetap terkunci, Update tetap bisa
    return;
  }
  navigator.geolocation.getCurrentPosition(
    function(pos) {
      window._gpsCoordinate = pos.coords.latitude.toFixed(7) + ', ' + pos.coords.longitude.toFixed(7);
      window._gpsDeviceType = _detectDevice();
      var el = document.getElementById('update-coordinate');
      if (el) el.value = window._gpsCoordinate;
      _enableGpsButtons();
    },
    function(err) {
      _showGpsDenied();
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
  );
}

// Jalankan saat halaman load
_requestGps();

// Isi hidden input setiap kali modal Update dibuka
$('#modal-ticketupdate').on('show.bs.modal', function () {
  var el = document.getElementById('update-coordinate');
  if (el && window._gpsCoordinate) el.value = window._gpsCoordinate;
  var dt = document.getElementById('update-device-type');
  if (dt && window._gpsDeviceType) dt.value = window._gpsDeviceType;
});

// MTTR running (show ticket)
(function(){
  var elValue = document.getElementById('mttr-live-value');
  var elMode = document.getElementById('mttr-mode');
  var elNote = document.getElementById('mttr-live-note');
  if (!elValue || !elMode || !elNote) return;

  var mttrStartAt = new Date(@json(\Carbon\Carbon::parse($mttrStartAt)->toIso8601String()));
  var mttrFixedEndRaw = @json($mttrEndAt ? \Carbon\Carbon::parse($mttrEndAt)->toIso8601String() : null);
  var mttrFixedEndAt = mttrFixedEndRaw ? new Date(mttrFixedEndRaw) : null;
  var pauseSegments = @json($mttrPauseSegments);

  function toSeconds(ms) {
    return Math.max(0, Math.floor(ms / 1000));
  }

  function formatDuration(totalSeconds) {
    var days = Math.floor(totalSeconds / 86400);
    var hours = Math.floor((totalSeconds % 86400) / 3600);
    var minutes = Math.floor((totalSeconds % 3600) / 60);
    if (days > 0) return days + ' hari ' + hours + 'j ' + minutes + 'm';
    return hours + 'j ' + minutes + 'm';
  }

  function getPausedSeconds(fromAt, toAt) {
    var fromMs = fromAt.getTime();
    var toMs = toAt.getTime();
    var sum = 0;

    for (var i = 0; i < pauseSegments.length; i++) {
      var seg = pauseSegments[i] || {};
      if (!seg.start) continue;
      var segStart = new Date(seg.start).getTime();
      var segEnd = seg.end ? new Date(seg.end).getTime() : toMs;
      if (segEnd <= fromMs || segStart >= toMs) continue;
      var overlapStart = Math.max(segStart, fromMs);
      var overlapEnd = Math.min(segEnd, toMs);
      if (overlapEnd > overlapStart) {
        sum += toSeconds(overlapEnd - overlapStart);
      }
    }

    return sum;
  }

  function renderMttr() {
    var now = new Date();
    var endAt = mttrFixedEndAt || now;
    var totalSeconds = toSeconds(endAt.getTime() - mttrStartAt.getTime());
    var pausedSeconds = getPausedSeconds(mttrStartAt, endAt);
    var effectiveSeconds = Math.max(0, totalSeconds - pausedSeconds);

    var mode = elMode.value;
    var shownSeconds = mode === 'total' ? totalSeconds : effectiveSeconds;
    elValue.textContent = formatDuration(shownSeconds);

    if (mode === 'total') {
      elNote.textContent = 'Total sejak ' + mttrStartAt.toLocaleString('id-ID') + ' (waktu stop tetap dihitung)';
    } else {
      elNote.textContent = 'Efektif sejak ' + mttrStartAt.toLocaleString('id-ID') + ' (waktu stop tidak dihitung)';
    }
  }

  elMode.addEventListener('change', renderMttr);
  renderMttr();
  if (!mttrFixedEndAt) {
    setInterval(renderMttr, 1000);
  }
})();
</script>

@endsection

@push('summernote-script')
<script src="{{ url('dashboard/plugins/summernote/summernote-bs4.min.js') }}"></script>
@endpush
