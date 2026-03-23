@extends('layout.main')
@section('title','Pengajuan Lembur')
@section('content')
<section class="content-header">
  <div class="container-fluid d-flex align-items-center justify-content-between">
    <h1><i class="fas fa-business-time mr-2 text-primary"></i>Pengajuan Lembur</h1>
    <div>
      <a href="{{ url('leave') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-beach-access mr-1"></i>Izin / Cuti
        @php $pendingLv = \App\LeaveRequest::where('status','pending')->count() @endphp
        @if($pendingLv > 0) <span class="badge badge-danger">{{ $pendingLv }}</span> @endif
      </a>
    </div>
  </div>
</section>

<section class="content"><div class="container-fluid">

  {{-- Filter --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body py-2">
      <form method="GET" class="form-inline flex-wrap">
        <div class="form-group mr-2 mb-2">
          <label class="mr-1 font-weight-bold">Status:</label>
          <select name="status" class="form-control form-control-sm">
            <option value="">Semua</option>
            <option value="pending"  {{ request('status')=='pending'  ? 'selected':'' }}>Menunggu</option>
            <option value="approved" {{ request('status')=='approved' ? 'selected':'' }}>Disetujui</option>
            <option value="rejected" {{ request('status')=='rejected' ? 'selected':'' }}>Ditolak</option>
          </select>
        </div>
        <div class="form-group mr-2 mb-2">
          <label class="mr-1 font-weight-bold">Karyawan:</label>
          <select name="user_id" class="form-control form-control-sm">
            <option value="">Semua</option>
            @foreach($employees as $emp)
            <option value="{{ $emp->id }}" {{ request('user_id')==$emp->id ? 'selected':'' }}>{{ $emp->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group mr-2 mb-2">
          <label class="mr-1 font-weight-bold">Bulan:</label>
          <input type="month" name="month" class="form-control form-control-sm" value="{{ request('month') }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm mb-2 mr-1"><i class="fas fa-search mr-1"></i>Filter</button>
        <a href="{{ url('overtime') }}" class="btn btn-secondary btn-sm mb-2">Reset</a>
      </form>
    </div>
  </div>

  {{-- Summary --}}
  <div class="row mb-3">
    @php
      $pendingC = \App\OvertimeRequest::where('status','pending')->count();
      $approvedC= \App\OvertimeRequest::where('status','approved')->count();
      $rejectedC= \App\OvertimeRequest::where('status','rejected')->count();
    @endphp
    <div class="col-6 col-md-3">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
        <div class="info-box-content"><span class="info-box-text">Menunggu</span><span class="info-box-number">{{ $pendingC }}</span></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
        <div class="info-box-content"><span class="info-box-text">Disetujui</span><span class="info-box-number">{{ $approvedC }}</span></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
        <div class="info-box-content"><span class="info-box-text">Ditolak</span><span class="info-box-number">{{ $rejectedC }}</span></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="info-box shadow-sm"><span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
        <div class="info-box-content"><span class="info-box-text">Total (filter)</span><span class="info-box-number">{{ $overtimes->total() }}</span></div>
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex align-items-center">
      <i class="fas fa-list mr-2"></i><strong>Daftar Pengajuan Lembur</strong>
      @if($pending > 0)
        <span class="badge badge-warning ml-2">{{ $pending }} Menunggu Approval</span>
      @endif
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="bg-light">
            <tr>
              <th>#</th>
              <th>Karyawan</th>
              <th>Tanggal</th>
              <th>Jam Mulai</th>
              <th>Jam Selesai</th>
              <th>Durasi</th>
              <th>Alasan</th>
              <th>Status</th>
              <th>Diproses oleh</th>
              <th>Catatan</th>
              <th>Dibuat</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($overtimes as $i => $ot)
            <tr class="{{ $ot->status === 'pending' ? 'table-warning' : '' }}">
              <td>{{ $overtimes->firstItem() + $i }}</td>
              <td><strong>{{ optional($ot->user)->name }}</strong></td>
              <td class="text-nowrap">{{ \Carbon\Carbon::parse($ot->date)->format('d/m/Y') }}</td>
              <td>{{ $ot->start_time }}</td>
              <td>{{ $ot->end_time }}</td>
              <td class="text-center"><span class="badge badge-info">{{ $ot->duration_hours }} jam</span></td>
              <td style="max-width:200px">{{ Str::limit($ot->reason, 60) }}</td>
              <td class="text-center">
                @if($ot->status === 'pending')
                  <span class="badge badge-warning">Menunggu</span>
                @elseif($ot->status === 'approved')
                  <span class="badge badge-success">Disetujui</span>
                @else
                  <span class="badge badge-danger">Ditolak</span>
                @endif
              </td>
              <td>{{ optional($ot->approver)->name ?? '-' }}</td>
              <td style="max-width:150px"><small>{{ $ot->approval_notes ?? '-' }}</small></td>
              <td class="text-nowrap"><small>{{ $ot->created_at->format('d/m/Y H:i') }}</small></td>
              <td class="text-center text-nowrap">
                @if($ot->status === 'pending')
                  {{-- Tombol Setujui --}}
                  <form action="{{ url('overtime/'.$ot->id.'/approve') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="action" value="approved">
                    <button type="submit" class="btn btn-xs btn-success"
                      onclick="return confirm('Setujui pengajuan lembur dari {{ optional($ot->user)->name }}?')">
                      <i class="fas fa-check"></i> Setujui
                    </button>
                  </form>
                  {{-- Tombol Tolak --}}
                  <button type="button" class="btn btn-xs btn-danger"
                    data-toggle="modal" data-target="#rejectOtModal{{ $ot->id }}">
                    <i class="fas fa-times"></i> Tolak
                  </button>

                  {{-- Modal Tolak --}}
                  <div class="modal fade" id="rejectOtModal{{ $ot->id }}" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                      <div class="modal-content">
                        <div class="modal-header bg-danger text-white py-2">
                          <h6 class="modal-title">Tolak Pengajuan Lembur</h6>
                          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>
                        <form action="{{ url('overtime/'.$ot->id.'/approve') }}" method="POST">
                          @csrf
                          <input type="hidden" name="action" value="rejected">
                          <div class="modal-body">
                            <p class="mb-2 text-sm">Lembur tanggal <strong>{{ \Carbon\Carbon::parse($ot->date)->format('d/m/Y') }}</strong>
                              dari <strong>{{ optional($ot->user)->name }}</strong></p>
                            <div class="form-group mb-0">
                              <label class="font-weight-bold">Alasan Penolakan:</label>
                              <textarea name="notes" class="form-control" rows="3" placeholder="Isi alasan penolakan..."></textarea>
                            </div>
                          </div>
                          <div class="modal-footer py-2">
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-sm btn-danger">Tolak</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="12" class="text-center py-4 text-muted">Tidak ada data pengajuan lembur.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($overtimes->hasPages())
    <div class="card-footer">
      {{ $overtimes->links() }}
    </div>
    @endif
  </div>

</div></section>
@endsection
