@extends('layout.main')
@section('title','Lead Summary & Pipeline')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><i class="fas fa-funnel-dollar text-warning"></i> Lead Summary & Pipeline</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/">Home</a></li>
          <li class="breadcrumb-item"><a href="#">Marketing</a></li>
          <li class="breadcrumb-item active">Lead Summary</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
<div class="container-fluid">

  {{-- ── FILTER BAR ─────────────────────────────────────────────────────────── --}}
  <div class="card card-outline card-primary mb-3">
    <div class="card-body p-2">
      <form method="GET" action="{{ route('marketing.lead-summary') }}" class="form-inline flex-wrap">
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Dari</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start }}">
        </div>
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Sampai</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end }}">
        </div>
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Sales</label>
          <select name="id_sale" class="form-control form-control-sm">
            <option value="">Semua Sales</option>
            @foreach($allSales as $sale)
              <option value="{{ $sale->id }}" {{ $filterSale == $sale->id ? 'selected' : '' }}>{{ $sale->name }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm mb-1 mr-1"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('marketing.lead-summary') }}" class="btn btn-secondary btn-sm mb-1"><i class="fas fa-undo"></i> Reset</a>
        <span class="ml-2 text-muted small align-self-center">{{ \Carbon\Carbon::parse($start)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($end)->format('d M Y') }}</span>
      </form>
    </div>
  </div>

  {{-- ── KPI CARDS ───────────────────────────────────────────────────────────── --}}
  <div class="row">
    <div class="col-6 col-md-3">
      <div class="small-box bg-gradient-info">
        <div class="inner">
          <h3>{{ $totalLeads }}</h3>
          <p>Total Lead Masuk</p>
        </div>
        <div class="icon"><i class="fas fa-users"></i></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="small-box bg-gradient-warning">
        <div class="inner">
          <h3>{{ $totalInprogress }} <sup style="font-size:.9rem">{{ $pctInprogress }}%</sup></h3>
          <p>In Progress</p>
        </div>
        <div class="icon"><i class="fas fa-spinner"></i></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="small-box bg-gradient-success">
        <div class="inner">
          <h3>{{ $totalConverted }} <sup style="font-size:.9rem">{{ $pctConverted }}%</sup></h3>
          <p>Sukses / Aktif</p>
        </div>
        <div class="icon"><i class="fas fa-check-circle"></i></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="small-box bg-gradient-danger">
        <div class="inner">
          <h3>{{ $totalLost }} <sup style="font-size:.9rem">{{ $pctLost }}%</sup></h3>
          <p>Gagal / Lost</p>
        </div>
        <div class="icon"><i class="fas fa-times-circle"></i></div>
      </div>
    </div>
  </div>

  {{-- ── CHART + SALES PERFORMANCE ──────────────────────────────────────────── --}}
  <div class="row">
    {{-- Donut chart --}}
    <div class="col-md-4">
      <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Pipeline Breakdown</h3></div>
        <div class="card-body d-flex justify-content-center align-items-center" style="min-height:260px;">
          <div style="max-width:220px; width:100%;">
            <canvas id="leadPipelineChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    {{-- Sales performance --}}
    <div class="col-md-8">
      <div class="card card-outline card-warning">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-user-tie"></i> Performa Sales / Marketing</h3>
        </div>
        <div class="card-body p-0">
          @if($salesPerf->isEmpty())
            <div class="p-3 text-muted text-center">Belum ada data sales pada periode ini.</div>
          @else
          <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Nama Sales</th>
                <th class="text-center">Total</th>
                <th class="text-center text-warning">In Progress</th>
                <th class="text-center text-success">Sukses</th>
                <th class="text-center text-danger">Gagal</th>
                <th class="text-center">Conv. Rate</th>
                <th>Breakdown</th>
              </tr>
            </thead>
            <tbody>
              @foreach($salesPerf as $i => $sp)
              <tr>
                <td>{{ $i+1 }}</td>
                <td>
                  <strong>{{ $sp['name'] }}</strong>
                  @if($sp['conv_rate'] >= 50)
                    &nbsp;<i class="fas fa-star text-warning" title="Conv. rate ≥50%"></i>
                  @endif
                </td>
                <td class="text-center"><span class="badge badge-info">{{ $sp['total'] }}</span></td>
                <td class="text-center"><span class="badge badge-warning">{{ $sp['inprogress'] }}</span></td>
                <td class="text-center"><span class="badge badge-success">{{ $sp['converted'] }}</span></td>
                <td class="text-center"><span class="badge badge-danger">{{ $sp['lost'] }}</span></td>
                <td class="text-center">
                  <span class="badge badge-{{ $sp['conv_rate'] >= 60 ? 'success' : ($sp['conv_rate'] >= 30 ? 'primary' : 'secondary') }}">
                    {{ $sp['conv_rate'] }}%
                  </span>
                </td>
                <td style="min-width:100px;">
                  @php
                    $pInp = $sp['total'] > 0 ? round($sp['inprogress']/$sp['total']*100) : 0;
                    $pCon = $sp['total'] > 0 ? round($sp['converted']/$sp['total']*100) : 0;
                    $pLst = $sp['total'] > 0 ? round($sp['lost']/$sp['total']*100) : 0;
                  @endphp
                  <div class="progress" style="height:10px; border-radius:4px;" title="In Progress: {{$pInp}}% | Sukses: {{$pCon}}% | Gagal: {{$pLst}}%">
                    <div class="progress-bar bg-warning" style="width:{{ $pInp }}%"></div>
                    <div class="progress-bar bg-success" style="width:{{ $pCon }}%"></div>
                    <div class="progress-bar bg-danger" style="width:{{ $pLst }}%"></div>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
          <div class="px-2 py-1">
            <small class="text-muted">
              <span class="badge badge-warning">&nbsp;</span> In Progress &nbsp;
              <span class="badge badge-success">&nbsp;</span> Sukses &nbsp;
              <span class="badge badge-danger">&nbsp;</span> Gagal
            </small>
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- ── IN-PROGRESS LEADS ───────────────────────────────────────────────────── --}}
  <div class="card card-outline card-warning">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-spinner text-warning"></i> Lead In Progress
        <span class="badge badge-warning ml-1">{{ $inprogressLeads->count() }}</span>
      </h3>
      <div class="card-tools">
        <input type="text" id="searchInprogress" class="form-control form-control-sm" placeholder="Cari nama / sales..." style="width:200px;">
      </div>
    </div>
    <div class="card-body p-0">
      @if($inprogressLeads->isEmpty())
        <div class="p-3 text-center text-muted">Tidak ada lead in-progress pada periode ini.</div>
      @else
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" id="tableInprogress">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Nama Lead</th>
              <th>Sales</th>
              <th>Lead Source</th>
              <th class="text-center">Step Sekarang</th>
              <th style="min-width:140px;">Progress Workflow</th>
              <th>Update Terakhir</th>
              <th>Tgl Daftar</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @php $hasStaleRow = false; @endphp
            @foreach($inprogressLeads as $i => $lead)
            @php
              $lastUpdate     = $lead->leadUpdates->sortByDesc('created_at')->first();
              $daysOld        = \Carbon\Carbon::parse($lead->created_at)->diffInDays(now());
              // Waktu aktivitas terakhir: dari lead_update ATAU customer updated_at
              $lastUpdateAt   = $lastUpdate ? \Carbon\Carbon::parse($lastUpdate->created_at) : null;
              $customerUpAt   = \Carbon\Carbon::parse($lead->updated_at);
              $lastActivityAt = $lastUpdateAt && $lastUpdateAt->gt($customerUpAt) ? $lastUpdateAt : $customerUpAt;
              $isStale = $lastActivityAt->diffInDays(now()) > 14;
              if ($isStale) $hasStaleRow = true;
            @endphp
            <tr class="{{ $isStale ? 'table-danger' : '' }}"
                data-name="{{ strtolower($lead->name) }}"
                data-sales="{{ strtolower($lead->sale_name?->name ?? '') }}">
              <td>{{ $i+1 }}</td>
              <td>
                <a href="/customer/{{ $lead->id }}" class="font-weight-bold text-primary">{{ $lead->name }}</a>
                <br><small class="text-muted">{{ $lead->phone }}</small>
              </td>
              <td>
                @if($lead->sale_name)
                  <span class="badge badge-info">{{ $lead->sale_name->name }}</span>
                @else
                  <span class="text-muted small">Unassigned</span>
                @endif
              </td>
              <td>
                @if($lead->lead_source)
                  <span class="badge badge-secondary">{{ $lead->lead_source }}</span>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
              <td class="text-center">
                <span class="badge badge-{{ $lead->workflow_pct >= 100 ? 'success' : 'primary' }}"
                      style="white-space:normal; max-width:110px; display:inline-block;">
                  {{ $lead->workflow_current }}
                </span>
                <br><small class="text-muted">{{ $lead->workflow_passed }}/{{ $lead->workflow_total }}</small>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="progress flex-fill mr-1" style="height:8px;">
                    <div class="progress-bar {{ $lead->workflow_pct >= 80 ? 'bg-success' : ($lead->workflow_pct >= 40 ? 'bg-primary' : 'bg-warning') }}"
                         style="width:{{ max($lead->workflow_pct, 3) }}%"></div>
                  </div>
                  <small style="min-width:30px; text-align:right;">{{ $lead->workflow_pct }}%</small>
                </div>
              </td>
              <td>
                @if($lastUpdate)
                  <span class="badge badge-light border">
                    {{ \Carbon\Carbon::parse($lastUpdate->created_at)->format('d M Y') }}
                  </span>
                  <br>
                  <small class="text-muted" style="font-size:0.68rem; max-width:160px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    {{ $lastUpdate->new_value ?? $lastUpdate->notes ?? '-' }}
                  </small>
                @else
                  <span class="text-muted small">Belum ada</span>
                @endif
                @if($isStale)
                  <br><span class="badge badge-danger py-0" title="Tidak ada follow-up &gt;14 hari">
                    <i class="fas fa-exclamation-triangle"></i> Follow-up!
                  </span>
                @endif
              </td>
              <td>
                <small>{{ \Carbon\Carbon::parse($lead->created_at)->format('d M Y') }}</small>
                <br><small class="text-muted">{{ $daysOld }} hr lalu</small>
              </td>
              <td>
                <a href="/customer/{{ $lead->id }}" class="btn btn-xs btn-outline-primary" title="Detail">
                  <i class="fas fa-eye"></i>
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="px-2 py-1">
        @if($hasStaleRow ?? false)
        <small class="text-muted">
          <span class="badge badge-danger py-0">Follow-up!</span> = Tidak ada update &gt; 14 hari
        </small>
        @endif
      </div>
      @endif
    </div>
  </div>

  {{-- ── LOST LEADS ──────────────────────────────────────────────────────────── --}}
  @if($lostLeads->isNotEmpty())
  <div class="card card-outline card-danger collapsed-card">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-times-circle text-danger"></i> Lead Gagal / Lost
        <span class="badge badge-danger ml-1">{{ $lostLeads->count() }}</span>
      </h3>
      <div class="card-tools">
        <button type="button" class="btn btn-tool" data-card-widget="collapse">
          <i class="fas fa-plus"></i>
        </button>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Nama Lead</th>
              <th>Sales</th>
              <th>Alasan Gagal</th>
              <th>Catatan</th>
              <th>Tanggal Gagal</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($lostLeads as $i => $lead)
            <tr>
              <td>{{ $i+1 }}</td>
              <td>
                <a href="/customer/{{ $lead->id }}" class="font-weight-bold text-danger">{{ $lead->name }}</a>
                <br><small class="text-muted">{{ $lead->phone }}</small>
              </td>
              <td>
                @if($lead->sale_name)
                  <span class="badge badge-info">{{ $lead->sale_name->name }}</span>
                @else <span class="text-muted small">-</span> @endif
              </td>
              <td><span class="badge badge-danger">{{ $lead->lost_reason ?? '-' }}</span></td>
              <td><small class="text-muted">{{ $lead->lost_notes ?? '-' }}</small></td>
              <td><small>{{ $lead->lost_at ? \Carbon\Carbon::parse($lead->lost_at)->format('d M Y') : '-' }}</small></td>
              <td>
                <a href="/customer/{{ $lead->id }}" class="btn btn-xs btn-outline-danger"><i class="fas fa-eye"></i></a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

</div>
</section>
@endsection

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Pipeline donut chart
  (function() {
    const ctx = document.getElementById('leadPipelineChart');
    if (!ctx) return;
    new Chart(ctx.getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: [
          'In Progress ({{ $totalInprogress }})',
          'Sukses ({{ $totalConverted }})',
          'Gagal ({{ $totalLost }})'
        ],
        datasets: [{
          data: [{{ $totalInprogress }}, {{ $totalConverted }}, {{ $totalLost }}],
          backgroundColor: ['#ffc107','#28a745','#dc3545'],
          borderWidth: 2
        }]
      },
      options: {
        cutout: '62%',
        plugins: {
          legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 8 } },
          tooltip: {
            callbacks: {
              label: function(item) {
                const total = {{ $totalLeads }};
                const pct = total > 0 ? ((item.raw / total) * 100).toFixed(1) : 0;
                return ' ' + item.raw + ' lead (' + pct + '%)';
              }
            }
          }
        }
      }
    });
  })();

  // Search filter for in-progress table
  document.getElementById('searchInprogress').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tableInprogress tbody tr').forEach(function(row) {
      const name  = row.getAttribute('data-name') || '';
      const sales = row.getAttribute('data-sales') || '';
      row.style.display = (!q || name.includes(q) || sales.includes(q)) ? '' : 'none';
    });
  });
</script>
@endsection
