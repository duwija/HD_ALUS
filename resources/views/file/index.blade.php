@extends('layout.main')
@section('title','Backup File')
@section('content')
@php $kb = fn($b) => number_format($b / 1024, 1, ',', '.') . ' KB'; @endphp
<section class="content-header">

  <div class="alert alert-light border small mb-3">
    <i class="fas fa-lock mr-1"></i> Backup konfigurasi router disimpan terenkripsi di storage server (tidak bisa diakses langsung dari internet)
    selama <strong>{{ $retentionDays }} hari</strong>. Unduh file yang ingin disimpan lebih lama.
  </div>

  {{-- Backup konfigurasi MikroTik --}}
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-network-wired mr-1"></i> Backup Konfigurasi MikroTik</h3>
    </div>
    <div class="card-body">
      <table class="table table-bordered table-striped table-sm">
        <thead>
          <tr>
            <th>#</th>
            <th>Router</th>
            <th>Tanggal</th>
            <th>RouterOS</th>
            <th class="text-right">Ukuran</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        @forelse($routerBackups as $b)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td><a href="{{ url('distrouter/' . $b->distrouter_id) }}#mk-backup">{{ $b->router_name }}</a></td>
            <td>{{ $b->created_at->format('Y-m-d H:i:s') }}</td>
            <td>{{ $b->ros_version ?: '-' }}</td>
            <td class="text-right">{{ $b->size ? $kb($b->size) : '-' }}</td>
            <td>
              <a class="btn btn-primary btn-sm" href="{{ url('distrouter/' . $b->distrouter_id . '/config-backup/' . $b->id) }}"><i class="fas fa-download"></i> Download .rsc</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted">Belum ada backup konfigurasi router.</td></tr>
        @endforelse
        </tbody>
      </table>
      <small class="text-muted">Dibuat otomatis tiap hari 02:30 (hanya bila konfigurasi berubah). Backup terakhir tiap router selalu disimpan walau lebih dari {{ $retentionDays }} hari.</small>
    </div>
  </div>

</section>

@endsection
