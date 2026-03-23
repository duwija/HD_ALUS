@extends('layout.main')
@section('title','My Team')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <h1><i class="fas fa-users mr-2 text-primary"></i>My Team</h1>
    <p class="text-muted mb-0">Karyawan yang berada di bawah supervisi Anda</p>
  </div>
</section>

<section class="content"><div class="container-fluid">

  @if($team->isEmpty())
    <div class="alert alert-info">
      <i class="fas fa-info-circle mr-1"></i>
      Belum ada karyawan yang terdaftar di bawah Anda. Minta admin untuk mengatur <strong>Supervisor / Atasan</strong> pada data karyawan.
    </div>
  @else

  {{-- Summary cards --}}
  <div class="row mb-3">
    <div class="col-6 col-md-3">
      <div class="info-box shadow-sm">
        <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
        <div class="info-box-content"><span class="info-box-text">Total Tim</span><span class="info-box-number">{{ $team->count() }}</span></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="info-box shadow-sm">
        <span class="info-box-icon bg-warning"><i class="fas fa-umbrella-beach"></i></span>
        <div class="info-box-content"><span class="info-box-text">Pending Izin/Cuti</span><span class="info-box-number">{{ $team->sum('pending_leaves') }}</span></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="info-box shadow-sm">
        <span class="info-box-icon bg-warning"><i class="fas fa-business-time"></i></span>
        <div class="info-box-content"><span class="info-box-text">Pending Lembur</span><span class="info-box-number">{{ $team->sum('pending_overtimes') }}</span></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="info-box shadow-sm">
        <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
        <div class="info-box-content"><span class="info-box-text">Perlu Approval</span><span class="info-box-number">{{ $team->sum('pending_leaves') + $team->sum('pending_overtimes') }}</span></div>
      </div>
    </div>
  </div>

  {{-- Team list --}}
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <i class="fas fa-list mr-1"></i><strong>Daftar Anggota Tim</strong>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="bg-light">
            <tr>
              <th>#</th>
              <th>Karyawan</th>
              <th>Jabatan</th>
              <th>Tipe</th>
              <th>Telepon</th>
              <th>Join</th>
              <th class="text-center">Pending Izin</th>
              <th class="text-center">Pending Lembur</th>
              <th>Izin Terakhir</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($team as $i => $member)
            <tr class="{{ ($member->pending_leaves + $member->pending_overtimes) > 0 ? 'table-warning' : '' }}">
              <td>{{ $i + 1 }}</td>
              <td>
                <div class="d-flex align-items-center">
                  <img src="/storage/users/{{ $member->photo }}"
                       onerror="this.onerror=null;this.src='/storage/users/default_profile.png';"
                       class="img-circle mr-2" style="width:32px;height:32px;object-fit:cover;" alt="">
                  <div>
                    <strong>{{ $member->name }}</strong><br>
                    <small class="text-muted">{{ $member->email }}</small>
                  </div>
                </div>
              </td>
              <td>{{ $member->job_title ?? '-' }}</td>
              <td><span class="badge badge-secondary">{{ $member->employee_type ?? '-' }}</span></td>
              <td>{{ $member->phone ?? '-' }}</td>
              <td class="text-nowrap">{{ $member->join_date ? \Carbon\Carbon::parse($member->join_date)->format('d/m/Y') : '-' }}</td>
              <td class="text-center">
                @if($member->pending_leaves > 0)
                  <a href="{{ url('leave?user_id='.$member->id.'&status=pending') }}" class="badge badge-warning">{{ $member->pending_leaves }}</a>
                @else
                  <span class="text-muted">0</span>
                @endif
              </td>
              <td class="text-center">
                @if($member->pending_overtimes > 0)
                  <a href="{{ url('overtime?user_id='.$member->id.'&status=pending') }}" class="badge badge-warning">{{ $member->pending_overtimes }}</a>
                @else
                  <span class="text-muted">0</span>
                @endif
              </td>
              <td>
                @if($member->last_leave)
                  <span class="text-nowrap">
                    {{ $member->last_leave->type_text }}
                    {{ \Carbon\Carbon::parse($member->last_leave->start_date)->format('d/m/Y') }}
                    <span class="badge badge-{{ $member->last_leave->status === 'approved' ? 'success' : ($member->last_leave->status === 'rejected' ? 'danger' : 'warning') }}">
                      {{ $member->last_leave->status_badge }}
                    </span>
                  </span>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td class="text-center text-nowrap">
                @if(($member->pending_leaves + $member->pending_overtimes) > 0)
                  @if($member->pending_leaves > 0)
                  <a href="{{ url('leave?user_id='.$member->id.'&status=pending') }}" class="btn btn-xs btn-warning" title="Approve Izin/Cuti Pending">
                    <i class="fas fa-check"></i> Izin
                  </a>
                  @endif
                  @if($member->pending_overtimes > 0)
                  <a href="{{ url('overtime?user_id='.$member->id.'&status=pending') }}" class="btn btn-xs btn-warning" title="Approve Lembur Pending">
                    <i class="fas fa-check"></i> Lembur
                  </a>
                  @endif
                @else
                <a href="{{ url('leave?user_id='.$member->id) }}" class="btn btn-xs btn-outline-primary" title="Lihat Izin/Cuti">
                  <i class="fas fa-umbrella-beach"></i>
                </a>
                <a href="{{ url('overtime?user_id='.$member->id) }}" class="btn btn-xs btn-outline-secondary" title="Lihat Lembur">
                  <i class="fas fa-business-time"></i>
                </a>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @endif

</div></section>
@endsection
