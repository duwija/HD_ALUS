@extends('layout.main')

@section('title', 'Absen & Jadwal Saya')

@section('content')
<style>
  .attendance-action-buttons {
    gap: .75rem !important;
  }

  .attendance-action-btn {
    min-height: 52px;
    padding: .75rem 1.1rem;
    font-size: 1rem;
    font-weight: 700;
    border-radius: .7rem;
    touch-action: manipulation;
  }

  .attendance-action-btn i {
    font-size: 1rem;
  }

  .attendance-camera-box {
    min-height: 240px;
  }

  .attendance-camera-viewport {
    width: 100%;
    height: 240px;
    object-fit: cover;
  }

  .attendance-camera-placeholder {
    min-height: 240px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  @media (max-width: 576px) {
    .attendance-action-buttons {
      display: flex;
      flex-direction: column;
      width: 100%;
      gap: .65rem !important;
    }

    .attendance-action-btn {
      width: 100%;
      min-height: 56px;
      font-size: 1.05rem;
    }

    .attendance-camera-box,
    .attendance-camera-placeholder {
      min-height: 200px;
    }

    .attendance-camera-viewport {
      height: 200px;
    }
  }
</style>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Absen &amp; Jadwal Saya</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
          <li class="breadcrumb-item active">Absen &amp; Jadwal</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
<div class="container-fluid">

@php
  $clockInDisabled = $attendanceLockReason || ($todayAtt && $todayAtt->clock_in);
  $clockOutDisabled = (bool) $attendanceLockReason;
  $clockOutSoftBlocked = null;
  if (!$attendanceLockReason) {
    if (!$todayAtt || !$todayAtt->clock_in) {
      $clockOutSoftBlocked = 'Clock out aktif setelah Anda clock in.';
    } elseif ($todayAtt->clock_out) {
      $clockOutSoftBlocked = 'Clock out sudah dilakukan hari ini.';
    }
  }
  $clockOutHint = $attendanceLockReason
    ? $attendanceLockReason
    : ($clockOutSoftBlocked ?: 'Clock out siap digunakan.');
@endphp

<div class="card shadow-sm border-0 mb-3" style="background:linear-gradient(135deg,#7f2515 0%,#c6462c 100%);color:#fff;overflow:hidden">
  <div class="card-body p-4">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <div class="d-flex flex-wrap align-items-center mb-2" style="gap:.5rem">
          <span class="badge badge-light text-dark">Absensi Harian</span>
          @if($attendanceLockReason)
            <span class="badge badge-warning text-dark">{{ $attendanceLockReason }}</span>
          @else
            <span class="badge badge-success">Radius wajib</span>
            <span class="badge badge-info">Selfie wajib</span>
          @endif
        </div>
        <h3 class="mb-2 font-weight-bold">Clock in dan clock out dengan selfie + GPS</h3>
        <div class="d-flex flex-wrap" style="gap:.75rem">
          <div><i class="fas fa-map-marker-alt mr-1"></i>{{ $activeLocations->count() }} titik absen aktif</div>
          @if($todaySched && $todaySched->shift)
            <div><i class="fas fa-business-time mr-1"></i>{{ $todaySched->shift->name }}</div>
          @endif
          @if($todayAtt)
            <div><i class="fas fa-clock mr-1"></i>Hari ini: {{ $todayAtt->clock_in ? 'sudah clock-in' : 'belum clock-in' }}{{ $todayAtt->clock_out ? ' · sudah clock-out' : '' }}</div>
          @endif
        </div>
      </div>
      <div class="col-lg-5 mt-4 mt-lg-0">
        <form id="attendance-submit-form" method="POST" action="{{ route('my.attendance.clock-in') }}">
          @csrf
          <input type="hidden" name="month" value="{{ $month }}">
          <input type="hidden" name="latitude" id="attendance-latitude">
          <input type="hidden" name="longitude" id="attendance-longitude">
          <input type="hidden" name="gps_accuracy" id="attendance-accuracy">
          <input type="hidden" name="gps_altitude" id="attendance-altitude">
          <input type="hidden" name="gps_speed" id="attendance-speed">
          <input type="hidden" name="device_info" id="attendance-device-info">
          <input type="hidden" name="is_mock" id="attendance-is-mock" value="0">
          <input type="hidden" name="attendance_action" id="attendance-action" value="clock-in">
          <input type="hidden" name="photo_base64" id="attendance-photo-base64">

          <div class="p-3" style="background:rgba(255,255,255,.12);border-radius:14px">
            <div class="row">
              <div class="col-12">
                <label class="small font-weight-bold mb-1">Selfie wajib</label>
                <div class="position-relative text-center attendance-camera-box" style="border:1px dashed rgba(255,255,255,.45);border-radius:12px;overflow:hidden;background:rgba(0,0,0,.15)">
                  <video id="attendance-camera" autoplay playsinline muted class="attendance-camera-viewport" style="display:none"></video>
                  <img id="attendance-photo-preview" alt="Preview selfie" class="attendance-camera-viewport" style="display:none">
                  <div id="attendance-photo-placeholder" class="small attendance-camera-placeholder" style="opacity:.85">
                    <div>
                      <i class="fas fa-camera fa-2x d-block mb-1"></i>
                      Kamera akan dibuka otomatis di depan
                    </div>
                  </div>
                </div>
                <small class="d-block mt-1" style="opacity:.8">Foto diambil langsung dari kamera depan hp saat absensi.</small>
              </div>
            </div>

            <div class="row mt-3 small">
              <div class="col-md-6 mb-2">
                <div class="font-weight-bold">Status lokasi</div>
                <div id="attendance-location-state">Menunggu izin lokasi</div>
              </div>
              <div class="col-md-6 mb-2">
                <div class="font-weight-bold">Akurasi</div>
                <div id="attendance-accuracy-state">-</div>
              </div>
              <div class="col-12">
                <div class="font-weight-bold">Koordinat</div>
                <div id="attendance-coordinate-state">-</div>
              </div>
            </div>

            <div class="d-flex flex-wrap mt-3 attendance-action-buttons" style="gap:.5rem">
              <button type="button" class="btn btn-light btn-sm" id="attendance-retry-location">
                <i class="fas fa-location-arrow mr-1"></i>Minta Lokasi Ulang
              </button>
              <button type="button" class="btn btn-success attendance-action-btn" data-attendance-action="clock-in" data-url="{{ route('my.attendance.clock-in') }}" {{ $clockInDisabled ? 'disabled' : '' }}>
                <i class="fas fa-sign-in-alt mr-1"></i>Clock In
              </button>
              <button
                type="button"
                class="btn attendance-action-btn {{ $clockOutDisabled ? 'btn-light' : 'btn-warning text-dark' }}"
                style="border:1px solid rgba(255,255,255,.65);{{ $clockOutDisabled ? 'color:#6c757d;' : '' }}"
                data-attendance-action="clock-out"
                data-url="{{ route('my.attendance.clock-out') }}"
                {{ $clockOutDisabled ? 'disabled' : '' }}
              >
                <i class="fas fa-sign-out-alt mr-1"></i>Clock Out
              </button>
            </div>
            <div class="small mt-2" style="opacity:.95">
              <i class="fas fa-info-circle mr-1"></i>{{ $clockOutHint }}
            </div>
          </div>

          @if($attendanceLockReason)
            <div class="alert alert-warning mt-3 mb-0 py-2 small">
              {{ $attendanceLockReason }}
            </div>
          @else
            <div class="alert alert-info mt-3 mb-0 py-2 small">
              Jika izin lokasi ditolak, klik tombol minta ulang lalu izinkan akses lokasi pada browser.
            </div>
          @endif
        </form>
      </div>
    </div>
  </div>
</div>

{{-- ── Month filter ──────────────────────────────────────────────── --}}
<div class="row mb-3">
  <div class="col-md-12">
    <form method="GET" action="{{ url('my-attendance') }}" class="d-flex align-items-center flex-wrap" style="gap:.5rem">
      <label class="mb-0 font-weight-bold">Bulan:</label>
      <input type="month" name="month" value="{{ $month }}"
             class="form-control" style="width:160px">
      <button class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i>Tampilkan</button>
      <a href="{{ url('my-attendance') }}" class="btn btn-secondary btn-sm">Bulan Ini</a>
    </form>
  </div>
</div>

{{-- ── Status hari ini ──────────────────────────────────────────── --}}
@php
  $todayLabel  = \Carbon\Carbon::today()->isoFormat('dddd, D MMMM Y');
  $attStatIcons = [
    'present'  => ['bg'=>'bg-success','icon'=>'fa-check-circle','label'=>'Hadir'],
    'late'     => ['bg'=>'bg-warning','icon'=>'fa-clock','label'=>'Terlambat'],
    'absent'   => ['bg'=>'bg-danger', 'icon'=>'fa-times-circle','label'=>'Alpha'],
    'leave'    => ['bg'=>'bg-info',   'icon'=>'fa-umbrella-beach','label'=>'Cuti/Izin'],
    'off'      => ['bg'=>'bg-secondary','icon'=>'fa-power-off','label'=>'Off'],
    'holiday'  => ['bg'=>'bg-info',   'icon'=>'fa-star','label'=>'Libur'],
  ];
@endphp

<div class="row mb-3">
  {{-- Today card --}}
  <div class="col-md-6 col-lg-4">
    <div class="card card-outline card-primary shadow-sm">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-calendar-day mr-1 text-primary"></i>Hari Ini — {{ $todayLabel }}</h5>
      </div>
      <div class="card-body p-3">
        @if($todaySched && $todaySched->shift)
          <div class="mb-2">
            <span class="badge" style="background:{{ $todaySched->shift->color ?? '#6c757d' }};color:#fff;font-size:.8rem">
              {{ $todaySched->shift->name }}
            </span>
            <span class="text-muted small ml-1">
              {{ \Carbon\Carbon::parse($todaySched->shift->start_time)->format('H:i') }}
              – {{ \Carbon\Carbon::parse($todaySched->shift->end_time)->format('H:i') }}
            </span>
          </div>
        @else
          <div class="mb-2 text-muted small"><i class="fas fa-info-circle mr-1"></i>Tidak ada jadwal hari ini</div>
        @endif

        @if($todayAtt)
          @php $si = $attStatIcons[$todayAtt->status] ?? ['bg'=>'bg-secondary','icon'=>'fa-question','label'=>ucfirst($todayAtt->status)]; @endphp
          <div class="d-flex align-items-center mb-2">
            <span class="badge {{ $si['bg'] }} mr-2 py-1 px-2">
              <i class="fas {{ $si['icon'] }} mr-1"></i>{{ $si['label'] }}
            </span>
            @if($todayAtt->late_minutes > 0)
              <span class="text-warning small"><i class="fas fa-exclamation-circle mr-1"></i>Terlambat {{ $todayAtt->late_minutes }} mnt</span>
            @endif
          </div>
          <div class="row text-center">
            <div class="col-6">
              <div class="text-muted small">Masuk</div>
              <div class="font-weight-bold text-success" style="font-size:1.1rem">
                {{ $todayAtt->clock_in ? \Carbon\Carbon::parse($todayAtt->clock_in)->format('H:i') : '--:--' }}
              </div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Keluar</div>
              <div class="font-weight-bold text-danger" style="font-size:1.1rem">
                {{ $todayAtt->clock_out ? \Carbon\Carbon::parse($todayAtt->clock_out)->format('H:i') : '--:--' }}
              </div>
            </div>
          </div>
          @if($todayAtt->work_minutes)
            <div class="text-center mt-2 text-muted small">
              <i class="fas fa-business-time mr-1"></i>Durasi: {{ floor($todayAtt->work_minutes/60) }}j {{ $todayAtt->work_minutes%60 }}m
            </div>
          @endif
        @else
          <div class="text-center text-muted py-2">
            <i class="fas fa-clock fa-2x mb-1 d-block"></i>
            <span class="small">Belum ada data absen hari ini</span>
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Summary cards --}}
  <div class="col-md-6 col-lg-8">
    <div class="row">
      <div class="col-6 col-lg-3 mb-2">
        <div class="small-box bg-success mb-0">
          <div class="inner">
            <h3>{{ $summary['hadir'] }}</h3>
            <p>Hadir</p>
          </div>
          <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-2">
        <div class="small-box bg-warning mb-0">
          <div class="inner">
            <h3>{{ $summary['late'] }}</h3>
            <p>Terlambat</p>
          </div>
          <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-2">
        <div class="small-box bg-danger mb-0">
          <div class="inner">
            <h3>{{ $summary['absent'] }}</h3>
            <p>Alpha</p>
          </div>
          <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
      </div>
      <div class="col-6 col-lg-3 mb-2">
        <div class="small-box bg-info mb-0">
          <div class="inner">
            <h3>{{ $summary['leave'] }}</h3>
            <p>Cuti/Izin</p>
          </div>
          <div class="icon"><i class="fas fa-umbrella-beach"></i></div>
        </div>
      </div>
    </div>
    {{-- Progress bar kehadiran --}}
    <div class="card shadow-sm mb-0">
      <div class="card-body py-2 px-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="font-weight-bold small">Tingkat Kehadiran Bulan Ini</span>
          @php
            $pct = $summary['total'] > 0 ? round(($summary['hadir']/$summary['total'])*100) : 0;
            $barClass = $pct >= 90 ? 'bg-success' : ($pct >= 75 ? 'bg-warning' : 'bg-danger');
          @endphp
          <span class="badge {{ $barClass }}">{{ $pct }}%</span>
        </div>
        <div class="progress" style="height:12px">
          <div class="progress-bar {{ $barClass }}" style="width:{{ $pct }}%"></div>
        </div>
        <div class="text-muted small mt-1 text-right">
          {{ $summary['hadir'] }} hadir dari {{ $summary['total'] }} hari kerja
          @if($avgWorkMin > 0)
            &nbsp;·&nbsp; Rata-rata {{ floor($avgWorkMin/60) }}j {{ $avgWorkMin%60 }}m/hari
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ── Kalender absensi ──────────────────────────────────────────── --}}
<div class="card shadow-sm mb-3">
  <div class="card-header">
    <h5 class="card-title mb-0"><i class="fas fa-calendar-alt mr-1 text-primary"></i>Kalender Absensi — {{ \Carbon\Carbon::parse($month.'-01')->isoFormat('MMMM Y') }}</h5>
  </div>
  <div class="card-body p-2">
    <div class="table-responsive">
      <table class="table table-bordered table-sm text-center mb-0" style="min-width:560px">
        <thead class="thead-light">
          <tr>
            <th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th>
          </tr>
        </thead>
        <tbody>
          @php
            $firstDow = \Carbon\Carbon::parse($month.'-01')->dayOfWeek; // 0=Sun
            $col = 0;
            $opened = false;
          @endphp
          <tr>
          @for($i = 0; $i < $firstDow; $i++)
            <td class="bg-light"></td>
            @php $col++; @endphp
          @endfor
          @foreach($calDays as $cd)
            @php
              $att   = $cd['att'];
              $sched = $cd['sched'];
              $isWe  = in_array($cd['weekday'], [0,6]);
              $bg    = '';
              $badge = '';
              if ($att) {
                switch($att->status) {
                  case 'present': $bg='#d4edda'; $badge='<span class="badge badge-success py-0 px-1" style="font-size:.6rem">Hadir</span>'; break;
                  case 'late':    $bg='#fff3cd'; $badge='<span class="badge badge-warning py-0 px-1" style="font-size:.6rem">Telat</span>'; break;
                  case 'absent':  $bg='#f8d7da'; $badge='<span class="badge badge-danger py-0 px-1" style="font-size:.6rem">Alpha</span>'; break;
                  case 'leave':   $bg='#d1ecf1'; $badge='<span class="badge badge-info py-0 px-1" style="font-size:.6rem">Cuti</span>'; break;
                  case 'off':     $bg='#e2e3e5'; $badge='<span class="badge badge-secondary py-0 px-1" style="font-size:.6rem">Off</span>'; break;
                  case 'holiday': $bg='#d1ecf1'; $badge='<span class="badge badge-info py-0 px-1" style="font-size:.6rem">Libur</span>'; break;
                }
              } elseif ($isWe) {
                $bg = '#f8f9fa';
              }
              $todayBorder = $cd['isToday'] ? 'border:2px solid #007bff!important;' : '';
            @endphp
            @if($col % 7 === 0 && !$loop->first)
              </tr><tr>
            @endif
            <td style="background:{{ $bg }};{{ $todayBorder }}vertical-align:top;padding:4px 2px;min-width:55px">
              <div class="font-weight-bold {{ $cd['isToday'] ? 'text-primary' : ($isWe ? 'text-secondary' : '') }}" style="font-size:.85rem">
                {{ $cd['day'] }}
              </div>
              {!! $badge !!}
              @if($att && $att->clock_in)
                <div class="text-muted" style="font-size:.6rem">{{ \Carbon\Carbon::parse($att->clock_in)->format('H:i') }}</div>
              @endif
              @if($sched && $sched->shift)
                <div style="font-size:.55rem;color:{{ $sched->shift->color ?? '#6c757d' }};font-weight:600">
                  {{ $sched->shift->name }}
                </div>
              @endif
            </td>
            @php $col++; @endphp
          @endforeach
          @while($col % 7 !== 0)
            <td class="bg-light"></td>
            @php $col++; @endphp
          @endwhile
          </tr>
        </tbody>
      </table>
    </div>
    {{-- Legend --}}
    <div class="mt-2 d-flex flex-wrap" style="gap:.4rem;font-size:.75rem">
      <span class="badge badge-success">Hadir</span>
      <span class="badge badge-warning text-dark">Terlambat</span>
      <span class="badge badge-danger">Alpha</span>
      <span class="badge badge-info">Cuti/Izin/Libur</span>
      <span class="badge badge-secondary">Off</span>
    </div>
  </div>
</div>

{{-- ── Tabel detail absensi ──────────────────────────────────────── --}}
<div class="card shadow-sm mb-3">
  <div class="card-header">
    <h5 class="card-title mb-0"><i class="fas fa-list mr-1 text-info"></i>Detail Absensi Bulan Ini</h5>
  </div>
  <div class="card-body p-0">
    @if($attendances->isEmpty())
      <div class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Tidak ada data absensi bulan ini</div>
    @else
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th>Tanggal</th>
            <th>Shift</th>
            <th>Status</th>
            <th>Masuk</th>
            <th>Keluar</th>
            <th>Durasi</th>
            <th>Telat</th>
            <th>Catatan</th>
          </tr>
        </thead>
        <tbody>
          @foreach($attendances->sortByDesc('date') as $att)
          @php
            $si = $attStatIcons[$att->status] ?? ['bg'=>'bg-secondary','icon'=>'fa-question','label'=>ucfirst($att->status)];
          @endphp
          <tr>
            <td class="font-weight-bold">{{ \Carbon\Carbon::parse($att->date)->isoFormat('ddd, D MMM') }}</td>
            <td>
              @if($att->shift)
                <span class="badge" style="background:{{ $att->shift->color ?? '#6c757d' }};color:#fff">{{ $att->shift->name }}</span>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>
            <td><span class="badge {{ $si['bg'] }}"><i class="fas {{ $si['icon'] }} mr-1"></i>{{ $si['label'] }}</span></td>
            <td class="text-success">{{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '-' }}</td>
            <td class="text-danger">{{ $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '-' }}</td>
            <td>
              @if($att->work_minutes)
                {{ floor($att->work_minutes/60) }}j {{ $att->work_minutes%60 }}m
              @else -
              @endif
            </td>
            <td>
              @if($att->late_minutes > 0)
                <span class="text-warning font-weight-bold">{{ $att->late_minutes }} mnt</span>
              @else
                <span class="text-success">-</span>
              @endif
            </td>
            <td class="text-muted small">{{ $att->note ?: '-' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>
</div>

{{-- ── Jadwal Shift bulan ini ────────────────────────────────────── --}}
<div class="row">
  <div class="col-md-6">
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-business-time mr-1 text-warning"></i>Jadwal Shift Bulan Ini</h5>
      </div>
      <div class="card-body p-0">
        @if($schedules->isEmpty())
          <div class="text-center text-muted py-3 small"><i class="fas fa-inbox mr-1"></i>Tidak ada jadwal bulan ini</div>
        @else
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
              <tr><th>Tanggal</th><th>Shift</th><th>Jenis</th><th>Catatan</th></tr>
            </thead>
            <tbody>
              @foreach($schedules->sortBy('date') as $sc)
              <tr>
                <td>{{ \Carbon\Carbon::parse($sc->date)->isoFormat('ddd, D MMM') }}</td>
                <td>
                  @if($sc->shift)
                    <span class="badge" style="background:{{ $sc->shift->color ?? '#6c757d' }};color:#fff">{{ $sc->shift->name }}</span>
                    <div class="text-muted" style="font-size:.7rem">{{ \Carbon\Carbon::parse($sc->shift->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($sc->shift->end_time)->format('H:i') }}</div>
                  @else <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  @if($sc->day_type === 'off')
                    <span class="badge badge-secondary">Off</span>
                  @elseif($sc->day_type === 'holiday')
                    <span class="badge badge-info">Libur</span>
                  @else
                    <span class="badge badge-light text-dark">{{ $sc->day_type ?: 'Kerja' }}</span>
                  @endif
                </td>
                <td class="text-muted small">{{ $sc->note ?: '-' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-md-6">
    {{-- Cuti & Izin --}}
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-umbrella-beach mr-1 text-info"></i>Cuti &amp; Izin Disetujui</h5>
      </div>
      <div class="card-body p-0">
        @if($leaveMonth->isEmpty())
          <div class="text-center text-muted py-3 small"><i class="fas fa-inbox mr-1"></i>Tidak ada pengajuan bulan ini</div>
        @else
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="thead-light">
              <tr><th>Jenis</th><th>Mulai</th><th>Selesai</th><th>Hari</th></tr>
            </thead>
            <tbody>
              @foreach($leaveMonth as $lv)
              <tr>
                <td>{{ $lv->type }}</td>
                <td>{{ \Carbon\Carbon::parse($lv->start_date)->format('d/m') }}</td>
                <td>{{ \Carbon\Carbon::parse($lv->end_date)->format('d/m') }}</td>
                <td>{{ $lv->days }} hari</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif
      </div>
    </div>

    {{-- Lembur --}}
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-moon mr-1 text-warning"></i>Lembur Disetujui</h5>
      </div>
      <div class="card-body p-0">
        @if($overtimeMonth->isEmpty())
          <div class="text-center text-muted py-3 small"><i class="fas fa-inbox mr-1"></i>Tidak ada lembur bulan ini</div>
        @else
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="thead-light">
              <tr><th>Tanggal</th><th>Mulai</th><th>Selesai</th><th>Durasi</th></tr>
            </thead>
            <tbody>
              @foreach($overtimeMonth as $ot)
              <tr>
                <td>{{ \Carbon\Carbon::parse($ot->date)->format('d/m') }}</td>
                <td>{{ $ot->start_time ?? '-' }}</td>
                <td>{{ $ot->end_time ?? '-' }}</td>
                <td>{{ $ot->duration ?? '-' }} jam</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

</div>
</section>

<script>
(function() {
  const form = document.getElementById('attendance-submit-form');
  if (!form) return;

  const deviceInfo = document.getElementById('attendance-device-info');
  const actionInput = document.getElementById('attendance-action');
  const latitudeInput = document.getElementById('attendance-latitude');
  const longitudeInput = document.getElementById('attendance-longitude');
  const accuracyInput = document.getElementById('attendance-accuracy');
  const altitudeInput = document.getElementById('attendance-altitude');
  const speedInput = document.getElementById('attendance-speed');
  const mockInput = document.getElementById('attendance-is-mock');
  const photoBase64Input = document.getElementById('attendance-photo-base64');
  const locationState = document.getElementById('attendance-location-state');
  const accuracyState = document.getElementById('attendance-accuracy-state');
  const coordinateState = document.getElementById('attendance-coordinate-state');
  const retryButton = document.getElementById('attendance-retry-location');
  const cameraVideo = document.getElementById('attendance-camera');
  const photoPreview = document.getElementById('attendance-photo-preview');
  const photoPlaceholder = document.getElementById('attendance-photo-placeholder');
  const actionButtons = form.querySelectorAll('[data-attendance-action]');

  let lastPosition = null;
  let cameraStream = null;

  if (deviceInfo) {
    deviceInfo.value = navigator.userAgent || '';
  }

  function setLocationFeedback(state, text) {
    if (!locationState) return;
    locationState.textContent = text;
    locationState.className = state === 'ok' ? 'text-success' : (state === 'warn' ? 'text-warning' : 'text-danger');
  }

  function fillPosition(position) {
    lastPosition = position;
    const coords = position.coords;

    if (latitudeInput) latitudeInput.value = coords.latitude.toFixed(6);
    if (longitudeInput) longitudeInput.value = coords.longitude.toFixed(6);
    if (accuracyInput) accuracyInput.value = coords.accuracy.toFixed(2);
    if (altitudeInput) altitudeInput.value = coords.altitude === null ? '' : coords.altitude.toFixed(2);
    if (speedInput) speedInput.value = coords.speed === null ? '' : coords.speed.toFixed(2);

    if (accuracyState) accuracyState.textContent = coords.accuracy.toFixed(1) + ' m';
    if (coordinateState) coordinateState.textContent = coords.latitude.toFixed(6) + ', ' + coords.longitude.toFixed(6);
    setLocationFeedback('ok', 'Lokasi aktif dan siap divalidasi.');
  }

  async function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !cameraVideo) {
      setLocationFeedback('warn', 'Kamera tidak tersedia di browser ini.');
      return;
    }

    try {
      cameraStream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: { ideal: 'user' },
        },
        audio: false,
      });

      cameraVideo.srcObject = cameraStream;
      cameraVideo.style.display = 'block';
      if (photoPlaceholder) photoPlaceholder.style.display = 'none';
      if (photoPreview) photoPreview.style.display = 'none';
      setLocationFeedback('ok', 'Kamera siap. Arahkan wajah ke depan lalu submit absensi.');
    } catch (error) {
      setLocationFeedback('error', 'Tidak bisa membuka kamera. Periksa izin kamera pada browser.');
    }
  }

  function captureCameraFrame() {
    if (!cameraVideo || !cameraVideo.videoWidth || !cameraVideo.videoHeight) {
      return false;
    }

    const canvas = document.createElement('canvas');
    canvas.width = cameraVideo.videoWidth;
    canvas.height = cameraVideo.videoHeight;
    const context = canvas.getContext('2d');
    context.drawImage(cameraVideo, 0, 0, canvas.width, canvas.height);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
    if (photoBase64Input) {
      photoBase64Input.value = dataUrl;
    }
    if (photoPreview) {
      photoPreview.src = dataUrl;
      photoPreview.style.display = 'block';
    }
    if (cameraVideo) {
      cameraVideo.style.display = 'none';
    }
    if (photoPlaceholder) {
      photoPlaceholder.style.display = 'none';
    }

    return true;
  }

  function requestLocation(onSuccess) {
    if (!navigator.geolocation) {
      setLocationFeedback('error', 'Browser tidak mendukung GPS.');
      return;
    }

    setLocationFeedback('warn', 'Meminta lokasi perangkat...');

    const highAccuracyOptions = {
      enableHighAccuracy: true,
      timeout: 12000,
      maximumAge: 0,
    };

    const fallbackOptions = {
      enableHighAccuracy: false,
      timeout: 20000,
      maximumAge: 120000,
    };

    function handleSuccess(position) {
      fillPosition(position);
      if (typeof onSuccess === 'function') {
        onSuccess(position);
      }
    }

    function handleError(error) {
      const messageMap = {
        1: 'Izin lokasi ditolak. Klik minta ulang lalu izinkan akses lokasi.',
        2: 'Lokasi perangkat tidak tersedia. Pastikan GPS aktif.',
        3: 'Permintaan lokasi habis waktu. Coba lagi di area dengan sinyal GPS lebih baik.',
      };

      if (lastPosition) {
        fillPosition(lastPosition);
        setLocationFeedback('warn', 'GPS real-time lambat. Menggunakan lokasi terakhir yang tersedia.');
        if (typeof onSuccess === 'function') {
          onSuccess(lastPosition);
        }
        return;
      }

      setLocationFeedback('error', messageMap[error.code] || 'Gagal membaca lokasi perangkat.');
    }

    navigator.geolocation.getCurrentPosition(handleSuccess, function(error) {
      if (error.code === 3) {
        setLocationFeedback('warn', 'GPS presisi timeout, mencoba mode lokasi standar...');
        navigator.geolocation.getCurrentPosition(handleSuccess, handleError, fallbackOptions);
        return;
      }
      handleError(error);
    }, highAccuracyOptions);
  }

  function submitFor(action, url) {
    actionInput.value = action;
    form.action = url;

    if (!cameraVideo || !cameraStream) {
      setLocationFeedback('error', 'Kamera belum aktif. Izinkan akses kamera lalu coba lagi.');
      startCamera();
      return;
    }

    if (!latitudeInput.value || !longitudeInput.value || !accuracyInput.value) {
      requestLocation(function() {
        if (!captureCameraFrame()) {
          setLocationFeedback('error', 'Gagal mengambil foto dari kamera.');
          return;
        }
        if (form.requestSubmit) form.requestSubmit(); else form.submit();
      });
      return;
    }

    if (!captureCameraFrame()) {
      setLocationFeedback('error', 'Gagal mengambil foto dari kamera.');
      return;
    }

    if (form.requestSubmit) form.requestSubmit(); else form.submit();
  }

  actionButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      submitFor(button.dataset.attendanceAction, button.dataset.url);
    });
  });

  if (retryButton) {
    retryButton.addEventListener('click', function() {
      requestLocation();
    });
  }

  startCamera();
  requestLocation();
})();
</script>
@endsection
