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
        <a href="/attendance/report?month={{ $month }}&export=1" class="btn btn-success mb-2">
          <i class="fas fa-file-excel mr-1"></i>Export
        </a>
      </form>
    </div>
  </div>

  {{-- Summary cards --}}
  <div class="row mb-3">
    @php
      $totalPresent  = $summary->sum('present');
      $totalLate     = $summary->sum('late');
      $totalAbsent   = $summary->sum('absent');
      $totalLeave    = $summary->sum('leave');
    @endphp
    <div class="col-md-3">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
        <div class="info-box-content"><span class="info-box-text">Hadir</span><span class="info-box-number">{{ $totalPresent }}</span></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
        <div class="info-box-content"><span class="info-box-text">Terlambat</span><span class="info-box-number">{{ $totalLate }}</span></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
        <div class="info-box-content"><span class="info-box-text">Absen</span><span class="info-box-number">{{ $totalAbsent }}</span></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-info"><i class="fas fa-file-alt"></i></span>
        <div class="info-box-content"><span class="info-box-text">Izin/Sakit</span><span class="info-box-number">{{ $totalLeave }}</span></div>
      </div>
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
