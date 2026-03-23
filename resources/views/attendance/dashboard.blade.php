@extends('layout.main')
@section('title','Dashboard Absensi')
@section('content')
<section class="content-header py-1">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col">
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-tachometer-alt mr-1 text-primary"></i>Dashboard Absensi</h5>
      </div>
      <div class="col-auto">
        <form method="GET" class="form-inline">
          <label class="mr-1 small font-weight-bold">Bulan:</label>
          <input type="month" name="month" class="form-control form-control-sm mr-1" value="{{ $month }}">
          <button type="submit" class="btn btn-sm btn-primary px-2"><i class="fas fa-sync-alt"></i></button>
        </form>
      </div>
    </div>
  </div>
</section>

<section class="content"><div class="container-fluid">

  {{-- ── Ringkasan Hari Ini ─────────────────────────────────────────────── --}}
  <p class="text-muted mb-1" style="font-size:11px;letter-spacing:.3px"><i class="fas fa-calendar-day mr-1"></i>HARI INI — {{ \Carbon\Carbon::today()->isoFormat('dddd, D MMMM YYYY') }}</p>
  <div class="row mb-3">
    @foreach([
      ['#28a745','#1e7e34','fas fa-sign-in-alt','Clock-in',$clockedIn,'/attendance/daily?date='.today()->format('Y-m-d')],
      ['#17a2b8','#117a8b','fas fa-sign-out-alt','Clock-out',$clockedOut,'/attendance/daily?date='.today()->format('Y-m-d')],
      ['#dc3545','#bd2130','fas fa-user-slash','Belum Absen',$notYet,'#belum-absen'],
      ['#e0a800','#c69500','fas fa-clock','Terlambat',$late,'/attendance/daily?date='.today()->format('Y-m-d')],
    ] as [$bg,$bgDark,$icon,$label,$val,$href])
    <div class="col-6 col-md-3 mb-2">
      <a href="{{ $href }}" style="text-decoration:none">
        <div class="card border-0 shadow stat-card" style="border-radius:10px;background:linear-gradient(135deg,{{ $bg }} 0%,{{ $bgDark }} 100%) !important">
          <div class="card-body py-3 px-3 d-flex align-items-center justify-content-between">
            <div>
              <div style="font-size:10px;text-transform:uppercase;letter-spacing:.7px;color:rgba(255,255,255,.75);font-weight:600">{{ $label }}</div>
              <div style="font-size:30px;font-weight:700;line-height:1.1;color:#fff">{{ $val }}</div>
            </div>
            <i class="{{ $icon }}" style="font-size:32px;opacity:.25;color:#fff"></i>
          </div>
        </div>
      </a>
    </div>
    @endforeach
  </div>

  {{-- ── Statistik Bulan Ini ────────────────────────────────────────────── --}}
  <p class="text-muted mb-1" style="font-size:11px;letter-spacing:.3px"><i class="fas fa-chart-bar mr-1"></i>STATISTIK {{ strtoupper(\Carbon\Carbon::parse($month.'-01')->isoFormat('MMMM YYYY')) }}</p>
  <div class="row mb-3">
    @foreach([
      ['#28a745','fas fa-check-circle','Hadir',$stats['present'],null],
      ['#ffc107','fas fa-clock','Terlambat',$stats['late'],null],
      ['#dc3545','fas fa-times-circle','Absen',$stats['absent'],null],
      ['#17a2b8','fas fa-file-alt','Izin/Cuti',$stats['leave'],null],
      ['#6f42c1','fas fa-business-time','Jam Lembur',number_format($overtimeSummary,1),'jam'],
      ['#fd7e14','fas fa-hourglass-half','Jam Kerja',$stats['work_hours'],'jam'],
    ] as [$color,$icon,$label,$val,$unit])
    <div class="col-6 col-md-2 mb-2">
      <div class="card border-0 shadow-sm stat-card" style="border-radius:10px;border-top:3px solid {{ $color }} !important">
        <div class="card-body py-2 px-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#888;font-weight:600">{{ $label }}</span>
            <i class="{{ $icon }}" style="font-size:14px;color:{{ $color }};opacity:.7"></i>
          </div>
          <div style="font-size:22px;font-weight:700;color:#333;line-height:1">{{ $val }}@if($unit)<small style="font-size:12px;font-weight:500;color:#999"> {{ $unit }}</small>@endif</div>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  {{-- ── Pending Approvals ──────────────────────────────────────────────── --}}
  @if($pendingLeave > 0 || $pendingOvertime > 0)
  <div class="row mb-2">
    @if($pendingLeave > 0)
    <div class="col-md-6">
      <div class="alert alert-warning shadow-sm d-flex align-items-center justify-content-between py-2" style="border-radius:8px;font-size:13px">
        <div><i class="fas fa-exclamation-triangle mr-1"></i><strong>{{ $pendingLeave }} izin/cuti</strong> pending</div>
        <a href="/leave?status=pending" class="btn btn-sm btn-warning ml-2 py-1">Proses</a>
      </div>
    </div>
    @endif
    @if($pendingOvertime > 0)
    <div class="col-md-6">
      <div class="alert alert-info shadow-sm d-flex align-items-center justify-content-between py-2" style="border-radius:8px;font-size:13px">
        <div><i class="fas fa-exclamation-circle mr-1"></i><strong>{{ $pendingOvertime }} lembur</strong> pending</div>
        <a href="/overtime?status=pending" class="btn btn-sm btn-info ml-2 py-1">Proses</a>
      </div>
    </div>
    @endif
  </div>
  @endif

  {{-- ── Chart Tren + Izin ─────────────────────────────────────────────── --}}
  <div class="row mb-3">
    {{-- Tren 14 hari --}}
    <div class="col-md-9 mb-2">
      <div class="card border-0 shadow-sm" style="border-radius:10px">
        <div class="card-header bg-white border-0 py-2 px-3 d-flex align-items-center justify-content-between" style="border-bottom:1px solid #f0f0f0">
          <span class="font-weight-bold" style="font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:#555"><i class="fas fa-chart-bar mr-1 text-primary"></i>Tren Kehadiran 14 Hari Terakhir</span>
          <div class="d-flex" style="gap:8px;font-size:10px">
            <span><span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:rgba(40,167,69,.75);margin-right:3px"></span>Hadir</span>
            <span><span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:rgba(255,193,7,.85);margin-right:3px"></span>Terlambat</span>
            <span><span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:rgba(220,53,69,.7);margin-right:3px"></span>Absen</span>
          </div>
        </div>
        <div class="card-body px-3 pt-2 pb-2">
          <canvas id="trendChart" height="65"></canvas>
        </div>
      </div>
    </div>

    {{-- Izin/Cuti donut --}}
    <div class="col-md-3 mb-2">
      <div class="card border-0 shadow-sm h-100" style="border-radius:10px">
        <div class="card-header bg-white border-0 py-2 px-3" style="border-bottom:1px solid #f0f0f0">
          <span class="font-weight-bold" style="font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:#555"><i class="fas fa-circle-notch mr-1 text-info"></i>Izin / Cuti</span>
        </div>
        <div class="card-body p-2 d-flex flex-column align-items-center justify-content-center">
          <div style="width:110px;height:110px;position:relative">
            <canvas id="leaveChart"></canvas>
            @php $totalLeave = $leaveSummary->sum('total'); @endphp
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none">
              <div style="font-size:20px;font-weight:700;line-height:1;color:#333">{{ $totalLeave }}</div>
              <div style="font-size:9px;color:#999;text-transform:uppercase">total</div>
            </div>
          </div>
          <div class="mt-2 w-100" style="font-size:11px">
            @foreach([['cuti','Cuti','#1565C0'],['sakit','Sakit','#e53935'],['izin_lainnya','Izin Lainnya','#fb8c00']] as [$key,$lbl,$col])
              @php $t = $leaveSummary->where('type',$key)->sum('total'); @endphp
              <div class="d-flex align-items-center mb-1">
                <span style="width:8px;height:8px;border-radius:2px;background:{{ $col }};display:inline-block;margin-right:5px;flex-shrink:0"></span>
                <span class="flex-grow-1" style="color:#666">{{ $lbl }}</span>
                <strong style="color:#333">{{ $t }}</strong>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Pengajuan Terbaru ─────────────────────────────────────────────── --}}
  <div class="row mb-2">
    {{-- Izin/Cuti terbaru --}}
    <div class="col-md-6 mb-2">
      <div class="card shadow-sm" style="border-radius:8px">
        <div class="card-header bg-white border-bottom-0 d-flex align-items-center justify-content-between py-2 px-3">
          <span class="font-weight-bold" style="font-size:13px"><i class="fas fa-file-signature mr-1 text-info"></i>Pengajuan Izin/Cuti Terbaru</span>
          <a href="/leave" class="btn btn-xs btn-outline-info btn-sm py-0">Lihat Semua</a>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-hover mb-0">
            <thead class="bg-light">
              <tr><th>Karyawan</th><th>Tipe</th><th>Tanggal</th><th>Status</th></tr>
            </thead>
            <tbody>
              @forelse($latestLeaves as $lv)
              <tr>
                <td>{{ $lv->user->name ?? '-' }}</td>
                <td>{{ $lv->type_text }}</td>
                <td style="font-size:11px">{{ $lv->start_date?->format('d/m') }}–{{ $lv->end_date?->format('d/m/y') }}</td>
                <td>
                  @if($lv->status === 'approved') <span class="badge badge-success">Disetujui</span>
                  @elseif($lv->status === 'rejected') <span class="badge badge-danger">Ditolak</span>
                  @else <span class="badge badge-warning">Pending</span>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="4" class="text-center text-muted py-3">Belum ada pengajuan</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Lembur terbaru --}}
    <div class="col-md-6 mb-2">
      <div class="card shadow-sm" style="border-radius:8px">
        <div class="card-header bg-white border-bottom-0 d-flex align-items-center justify-content-between py-2 px-3">
          <span class="font-weight-bold" style="font-size:13px"><i class="fas fa-business-time mr-1" style="color:#6f42c1"></i>Pengajuan Lembur Terbaru</span>
          <a href="/overtime" class="btn btn-xs btn-outline-secondary btn-sm py-0">Lihat Semua</a>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-hover mb-0">
            <thead class="bg-light">
              <tr><th>Karyawan</th><th>Tanggal</th><th>Durasi</th><th>Status</th></tr>
            </thead>
            <tbody>
              @forelse($latestOvertimes as $ot)
              <tr>
                <td>{{ $ot->user->name ?? '-' }}</td>
                <td style="font-size:11px">{{ $ot->date?->format('d/m/y') }}</td>
                <td>{{ number_format($ot->duration_hours, 1) }} jam</td>
                <td>
                  @if($ot->status === 'approved') <span class="badge badge-success">Disetujui</span>
                  @elseif($ot->status === 'rejected') <span class="badge badge-danger">Ditolak</span>
                  @else <span class="badge badge-warning">Pending</span>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="4" class="text-center text-muted py-3">Belum ada pengajuan</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Belum Absen Hari Ini + Absen Masuk Hari Ini ───────────────────── --}}
  <div class="row mb-2">
    {{-- Karyawan belum absen --}}
    <div class="col-md-5 mb-2" id="belum-absen">
      <div class="card shadow-sm" style="border-radius:8px">
        <div class="card-header bg-danger text-white border-0 py-2 px-3">
          <span style="font-size:13px;font-weight:600"><i class="fas fa-user-slash mr-1"></i>Belum Absen Hari Ini ({{ $notYet }})</span>
        </div>
        <div class="card-body p-0">
          @if($notCheckedIn->isEmpty())
            <p class="text-center text-muted py-3 mb-0"><i class="fas fa-check-circle text-success mr-1"></i>Semua karyawan sudah absen.</p>
          @else
          <ul class="list-group list-group-flush">
            @foreach($notCheckedIn as $emp)
            <li class="list-group-item py-1 px-3" style="font-size:12px">
              <i class="fas fa-user-circle text-secondary mr-2"></i>{{ $emp->name }}
              @if($emp->employee_id)
                <small class="text-muted ml-1">({{ $emp->employee_id }})</small>
              @endif
            </li>
            @endforeach
            @if($notYet > 8)
            <li class="list-group-item py-2 px-3 text-muted text-center" style="font-size:12px">
              dan {{ $notYet - 8 }} karyawan lainnya...
            </li>
            @endif
          </ul>
          @endif
        </div>
      </div>
    </div>

    {{-- Absen hari ini detail --}}
    <div class="col-md-7 mb-2">
      <div class="card shadow-sm" style="border-radius:8px">
        <div class="card-header bg-success text-white border-0 d-flex align-items-center justify-content-between py-2 px-3">
          <span style="font-size:13px;font-weight:600"><i class="fas fa-clipboard-list mr-1"></i>Absensi Hari Ini ({{ $todayAtt->count() }})</span>
          <a href="/attendance/daily?date={{ today()->format('Y-m-d') }}" class="btn btn-sm btn-light btn-xs py-0">Detail</a>
        </div>
        <div class="card-body p-0" style="max-height:200px;overflow-y:auto">
          <table class="table table-sm table-hover mb-0">
            <thead class="bg-light sticky-top">
              <tr><th>Karyawan</th><th>Shift</th><th>Masuk</th><th>Pulang</th><th>Status</th></tr>
            </thead>
            <tbody>
              @forelse($todayAtt->sortBy('clock_in') as $att)
              <tr>
                <td style="font-size:12px">{{ $att->user->name ?? '-' }}</td>
                <td style="font-size:11px">{{ $att->shift->name ?? '-' }}</td>
                <td style="font-size:12px">{{ $att->clock_in ?? '-' }}</td>
                <td style="font-size:12px">{{ $att->clock_out ?? '-' }}</td>
                <td>{!! $att->statusBadge() !!}</td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data absensi hari ini</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Quick Links ────────────────────────────────────────────────────── --}}
  <div class="row mb-2">
    <div class="col-12">
      <div class="card shadow-sm" style="border-radius:8px">
        <div class="card-body py-2 px-3">
          <div class="d-flex flex-wrap gap-2">
            @foreach([
              ['/attendance/daily','fas fa-calendar-day','Absen Harian'],
              ['/attendance/report','fas fa-chart-bar','Rekap Absensi'],
              ['/attendance/schedule','fas fa-calendar-alt','Jadwal Shift'],
              ['/leave','fas fa-file-signature','Izin / Cuti'],
              ['/overtime','fas fa-business-time','Lembur'],
              ['/attendance/employees','fas fa-users','Data Karyawan'],
            ] as [$url,$icon,$label])
            <a href="{{ $url }}" class="btn btn-sm btn-light border hover-shadow" style="font-size:12px">
              <i class="{{ $icon }} mr-1 text-primary"></i>{{ $label }}
            </a>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

</div></section>
@endsection

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Tren Kehadiran 14 Hari ─────────────────────────────────────────────────
const trendData = @json($trend);
new Chart(document.getElementById('trendChart'), {
  type: 'bar',
  data: {
    labels: trendData.map(d => d.date),
    datasets: [
      { label: 'Hadir',     data: trendData.map(d => d.present), backgroundColor: 'rgba(40,167,69,.75)',  borderRadius: 4 },
      { label: 'Terlambat', data: trendData.map(d => d.late),    backgroundColor: 'rgba(255,193,7,.85)',  borderRadius: 4 },
      { label: 'Absen',     data: trendData.map(d => d.absent),  backgroundColor: 'rgba(220,53,69,.7)',   borderRadius: 4 },
    ],
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
      tooltip: { mode: 'index' }
    },
    scales: {
      x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 } } },
      y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } } },
    },
  },
});

// ── Pie Izin/Cuti ─────────────────────────────────────────────────────────
@php
  $pieLabels = ['Cuti','Sakit','Izin Lainnya'];
  $pieData   = [
    $leaveSummary->where('type','cuti')->sum('total'),
    $leaveSummary->where('type','sakit')->sum('total'),
    $leaveSummary->where('type','izin_lainnya')->sum('total'),
  ];
@endphp
new Chart(document.getElementById('leaveChart'), {
  type: 'doughnut',
  data: {
    labels: @json($pieLabels),
    datasets: [{
      data: @json($pieData),
      backgroundColor: ['#1565C0','#e53935','#fb8c00'],
      borderWidth: 1,
      hoverOffset: 4,
    }],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed } },
    },
  },
});
</script>
<style>
.hover-shadow:hover,.stat-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,.12) !important; transform: translateY(-2px); transition: all .18s; }
.stat-card { transition: all .18s; }
</style>
@endsection
