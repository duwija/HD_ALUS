@extends('layout.main')
@section('title','Daftar Shift')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1><i class="fas fa-clock mr-2 text-primary"></i>Manajemen Shift</h1></div>
      <div class="col-sm-6 text-right">
        <a href="/attendance/shifts/create" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Tambah Shift</a>
      </div>
    </div>
  </div>
</section>

<section class="content"><div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="bg-primary text-white">
          <tr>
            <th>#</th><th>Nama Shift</th><th>Jam Masuk</th><th>Jam Keluar</th>
            <th>Toleransi Terlambat</th><th>Status</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($shifts as $i => $shift)
          <tr>
            <td>{{ $i+1 }}</td>
            <td><span class="badge" style="background:{{ $shift->color }};color:#fff;font-size:13px">{{ $shift->name }}</span></td>
            <td>{{ $shift->start_time }}</td>
            <td>{{ $shift->end_time }}</td>
            <td>{{ $shift->late_tolerance }} menit</td>
            <td>
              @if($shift->is_active)
                <span class="badge badge-success">Aktif</span>
              @else
                <span class="badge badge-secondary">Nonaktif</span>
              @endif
            </td>
            <td>
              <a href="/attendance/shifts/{{ $shift->id }}/edit" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
              <form method="POST" action="/attendance/shifts/{{ $shift->id }}" class="d-inline"
                    onsubmit="return confirm('Hapus shift ini?')">
                @csrf @method('DELETE')
                <button class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada shift</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div></section>
@endsection
