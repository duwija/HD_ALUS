@extends('layout.main')
@section('title','Absensi Harian')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><i class="fas fa-calendar-day mr-2 text-info"></i>Absensi Harian</h1>
      </div>
    </div>
  </div>
</section>

<section class="content"><div class="container-fluid">

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="form-inline">
        <label class="mr-2">Tanggal:</label>
        <input type="date" name="date" class="form-control mr-2" value="{{ $date }}">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Tampilkan</button>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header">
      <b>{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</b>
      <span class="badge badge-primary ml-2">{{ $records->count() }} karyawan tercatat</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="bg-info text-white">
            <tr>
              <th>#</th><th>Karyawan</th><th>Shift</th>
              <th>Clock In</th><th>Clock Out</th><th>Terlambat</th>
              <th>Lokasi In</th><th>Jarak</th><th>Status</th>
              <th>Foto In</th><th>Foto Out</th>
            </tr>
          </thead>
          <tbody>
            @forelse($records as $i => $rec)
            <tr>
              <td>{{ $i+1 }}</td>
              <td><strong>{{ $rec->user->name ?? '-' }}</strong></td>
              <td><small>{{ optional($rec->shift)->name ?? '-' }}</small></td>
              <td>{{ $rec->clock_in  ? \Carbon\Carbon::parse($rec->clock_in)->format('H:i:s')  : '-' }}</td>
              <td>{{ $rec->clock_out ? \Carbon\Carbon::parse($rec->clock_out)->format('H:i:s') : '-' }}</td>
              <td>
                @if($rec->late_minutes > 0)
                  <span class="badge badge-warning">{{ $rec->late_minutes }} mnt</span>
                @else -
                @endif
              </td>
              <td><small>{{ optional($rec->locationIn)->name ?? '-' }}</small></td>
              <td>{{ $rec->distance_in ? $rec->distance_in.' m' : '-' }}</td>
              <td>{!! $rec->statusBadge() !!}</td>
              <td>
                @if($rec->photo_in)
                  @php $urlIn = \Storage::disk('public')->url($rec->photo_in); @endphp
                  <img src="{{ $urlIn }}" onclick="showPhotoModal('{{ $urlIn }}','Foto In – {{ addslashes($rec->user->name ?? '') }}')" style="width:44px;height:44px;object-fit:cover;border-radius:50%;cursor:pointer;">
                @else <span class="text-muted">-</span>
                @endif
              </td>
              <td>
                @if($rec->photo_out)
                  @php $urlOut = \Storage::disk('public')->url($rec->photo_out); @endphp
                  <img src="{{ $urlOut }}" onclick="showPhotoModal('{{ $urlOut }}','Foto Out – {{ addslashes($rec->user->name ?? '') }}')" style="width:44px;height:44px;object-fit:cover;border-radius:50%;cursor:pointer;">
                @else <span class="text-muted">-</span>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="11" class="text-center py-4 text-muted">Belum ada data absensi untuk tanggal ini</td></tr>
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
