@extends('layout.main')
@section('title','Rekap Absensi')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <h1><i class="fas fa-chart-bar mr-2 text-primary"></i>Rekap Absensi Karyawan</h1>
  </div>
</section>

<section class="content"><div class="container-fluid">

  {{-- Filter --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="form-inline flex-wrap gap-2">
        <div class="form-group mr-2 mb-2">
          <label class="mr-1">Bulan:</label>
          <input type="month" name="month" class="form-control" value="{{ $month }}">
        </div>
        <div class="form-group mr-2 mb-2">
          <label class="mr-1">Karyawan:</label>
          <select name="user_id" class="form-control">
            <option value="">-- Semua --</option>
            @foreach($employees as $emp)
              <option value="{{ $emp->id }}" {{ $userId == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="btn btn-primary mb-2 mr-2"><i class="fas fa-search mr-1"></i>Tampilkan</button>
        <a href="{{ route('attendance.report.export', ['month' => $month, 'user_id' => $userId]) }}" class="btn btn-success mb-2">
          <i class="fas fa-file-excel mr-1"></i>Export
        </a>
      </form>
    </div>
  </div>

  {{-- Summary cards --}}
  <div class="row mb-3">
    @php
      $totalPresent  = $summary->sum('attendance');
      $totalLate     = $summary->sum('late');
      $totalCuti     = $summary->sum('cuti');
      $totalSakit    = $summary->sum('sakit');
      $totalLibur    = $summary->sum('libur');
      $totalNoInfo   = $summary->sum('tanpa_keterangan');
    @endphp
    <div class="col-md-2">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
        <div class="info-box-content"><span class="info-box-text">Absensi</span><span class="info-box-number">{{ $totalPresent }}</span></div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
        <div class="info-box-content"><span class="info-box-text">Terlambat</span><span class="info-box-number">{{ $totalLate }}</span></div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-info"><i class="fas fa-umbrella-beach"></i></span>
        <div class="info-box-content"><span class="info-box-text">Cuti</span><span class="info-box-number">{{ $totalCuti }}</span></div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-secondary"><i class="fas fa-notes-medical"></i></span>
        <div class="info-box-content"><span class="info-box-text">Sakit</span><span class="info-box-number">{{ $totalSakit }}</span></div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-dark"><i class="fas fa-calendar-day"></i></span>
        <div class="info-box-content"><span class="info-box-text">Libur</span><span class="info-box-number">{{ $totalLibur }}</span></div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
        <div class="info-box-content"><span class="info-box-text">Tanpa Info</span><span class="info-box-number">{{ $totalNoInfo }}</span></div>
      </div>
    </div>
  </div>

  {{-- Rekap per karyawan --}}
  <div class="card shadow-sm mb-3">
    <div class="card-header">
      <h5 class="card-title mb-0"><i class="fas fa-users mr-1 text-primary"></i>Rekap Per Karyawan</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Karyawan</th>
              <th>Absensi</th>
              <th>Terlambat</th>
              <th>Cuti</th>
              <th>Sakit</th>
              <th>Libur</th>
              <th>Tidak Absen / Tanpa Pemberitahuan</th>
              <th>Total Jam Kerja</th>
            </tr>
          </thead>
          <tbody>
            @forelse($summary as $empId => $stat)
              @php $emp = $employees->firstWhere('id', $empId); @endphp
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $emp->name ?? 'Karyawan #' . $empId }}</td>
                <td><span class="badge badge-success">{{ $stat['attendance'] ?? 0 }}</span></td>
                <td><span class="badge badge-warning">{{ $stat['late'] ?? 0 }}</span></td>
                <td><span class="badge badge-info">{{ $stat['cuti'] ?? 0 }}</span></td>
                <td><span class="badge badge-secondary">{{ $stat['sakit'] ?? 0 }}</span></td>
                <td><span class="badge badge-dark">{{ $stat['libur'] ?? 0 }}</span></td>
                <td><span class="badge badge-danger">{{ $stat['tanpa_keterangan'] ?? 0 }}</span></td>
                <td>
                  @php $minutes = (int) ($stat['total_work_minutes'] ?? 0); @endphp
                  {{ intdiv($minutes, 60) }}j {{ $minutes % 60 }}m
                </td>
              </tr>
            @empty
              <tr><td colspan="9" class="text-center text-muted py-3">Belum ada data untuk periode ini.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Kalender karyawan --}}
  <div class="card shadow-sm mb-3">
    <div class="card-header">
      <h5 class="card-title mb-0"><i class="fas fa-calendar-alt mr-1 text-info"></i>Kalender Absensi {{ $calendarUser ? '- ' . $calendarUser->name : '' }}</h5>
    </div>
    <div class="card-body">
      @if(!$calendarUser)
        <div class="text-muted">Pilih karyawan untuk menampilkan kalender.</div>
      @else
        @php
          $firstDow = \Carbon\Carbon::parse($month.'-01')->dayOfWeek;
          $col = 0;
          $statusStyle = [
            'attendance' => ['bg' => '#d4edda', 'badge' => 'badge-success', 'text' => 'H'],
            'sakit' => ['bg' => '#e2e3f9', 'badge' => 'badge-secondary', 'text' => 'S'],
            'cuti' => ['bg' => '#d1ecf1', 'badge' => 'badge-info', 'text' => 'C'],
            'izin' => ['bg' => '#d1ecf1', 'badge' => 'badge-info', 'text' => 'I'],
            'libur' => ['bg' => '#e2e3e5', 'badge' => 'badge-dark', 'text' => 'L'],
            'tanpa_keterangan' => ['bg' => '#f8d7da', 'badge' => 'badge-danger', 'text' => 'A'],
          ];
        @endphp
        <div class="table-responsive">
          <table class="table table-bordered table-sm text-center mb-2" style="min-width:560px;">
            <thead class="thead-light">
              <tr>
                <th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                @for($i = 0; $i < $firstDow; $i++)
                  <td class="bg-light"></td>
                  @php $col++; @endphp
                @endfor
                @foreach($calendarDays as $day)
                  @if($col % 7 === 0 && !$loop->first)
                    </tr><tr>
                  @endif
                  @php
                    $style = $statusStyle[$day['status']] ?? $statusStyle['tanpa_keterangan'];
                    $title = $day['label'];
                    if (!empty($day['clock_in']) || !empty($day['clock_out'])) {
                      $title .= ' | In: ' . ($day['clock_in'] ?: '-') . ' | Out: ' . ($day['clock_out'] ?: '-');
                    }
                  @endphp
                  <td style="background:{{ $style['bg'] }};vertical-align:top;padding:4px 2px;" title="{{ $title }}">
                    <div class="font-weight-bold">{{ $day['day'] }}</div>
                    <span class="badge {{ $style['badge'] }}" style="font-size:.65rem;">{{ $style['text'] }}</span>
                    @if(!empty($day['clock_in']))
                      <div class="text-muted" style="font-size:.65rem">{{ substr($day['clock_in'], 0, 5) }}</div>
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
        <div class="d-flex flex-wrap" style="gap:.35rem;">
          <span class="badge badge-success">H = Absensi</span>
          <span class="badge badge-secondary">S = Sakit</span>
          <span class="badge badge-info">C/I = Cuti/Izin</span>
          <span class="badge badge-dark">L = Libur</span>
          <span class="badge badge-danger">A = Tanpa keterangan</span>
        </div>
      @endif
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="bg-primary text-white">
            <tr>
              <th>#</th><th>Karyawan</th><th>Tanggal</th><th>Shift</th>
              <th>Clock In</th><th>Clock Out</th><th>Jam Kerja</th>
              <th>Terlambat</th><th>Lokasi</th><th>Status</th><th>Foto</th>
            </tr>
          </thead>
          <tbody>
            @forelse($records as $i => $rec)
            <tr>
              <td>{{ $i+1 }}</td>
              <td>
                <strong>{{ $rec->user->name ?? '-' }}</strong>
                @if($rec->user->employee_id)<br><small class="text-muted">{{ $rec->user->employee_id }}</small>@endif
              </td>
              <td>{{ $rec->date?->format('D, d M Y') }}</td>
              <td><small>{{ optional($rec->shift)->name ?? '-' }}</small></td>
              <td>{{ $rec->clock_in ?? '-' }}</td>
              <td>{{ $rec->clock_out ?? '-' }}</td>
              <td>
                @if($rec->work_minutes)
                  {{ intdiv($rec->work_minutes,60) }}j {{ $rec->work_minutes%60 }}m
                @else -
                @endif
              </td>
              <td>
                @if($rec->late_minutes > 0)
                  <span class="text-danger">{{ $rec->late_minutes }} mnt</span>
                @else
                  <span class="text-success">-</span>
                @endif
              </td>
              <td><small>{{ optional($rec->locationIn)->name ?? '-' }}</small></td>
              <td>{!! $rec->statusBadge() !!}</td>
              <td>
                @if($rec->photo_in)
                  @php $urlIn = \Storage::disk('public')->url($rec->photo_in); @endphp
                  <img src="{{ $urlIn }}" onclick="showPhotoModal('{{ $urlIn }}','Foto – {{ addslashes($rec->user->name ?? '') }}')" style="width:36px;height:36px;object-fit:cover;border-radius:4px;cursor:pointer;">
                @else -
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="11" class="text-center py-4 text-muted">Tidak ada data absensi</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div></section>

{{-- Lightbox Modal --}}
<div class="modal fade" id="photoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="photoModalLabel"></h6>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-2 text-center">
        <img id="photoModalImg" src="" style="max-width:100%;border-radius:8px;">
      </div>
    </div>
  </div>
</div>

@endsection

@push('summernote-script')
<script>
function showPhotoModal(url, caption) {
    document.getElementById('photoModalImg').src   = url;
    document.getElementById('photoModalLabel').textContent = caption;
    $('#photoModal').modal('show');
}
</script>
@endpush
