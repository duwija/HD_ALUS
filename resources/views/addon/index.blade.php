@extends('layout.main')
@section('title','Add-on Management')
@section('content')

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><i class="fas fa-puzzle-piece mr-2"></i>Add-on Management</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/home">Home</a></li>
          <li class="breadcrumb-item active">Add-on</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    <div class="card card-primary card-outline shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Daftar Add-on</h3>
        <a href="{{ route('addon.create') }}" class="btn btn-primary btn-sm">
          <i class="fas fa-plus mr-1"></i>Tambah Add-on
        </a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Nama Add-on</th>
              <th>Harga</th>
              <th>Deskripsi</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($addons as $addon)
            <tr class="{{ $addon->trashed() ? 'table-secondary text-muted' : '' }}">
              <td>{{ $loop->iteration }}</td>
              <td><strong>{{ $addon->name }}</strong></td>
              <td>Rp {{ number_format($addon->price, 0, ',', '.') }}</td>
              <td>{{ $addon->description ?: '-' }}</td>
              <td>
                @if($addon->trashed())
                  <span class="badge badge-secondary">Dihapus</span>
                @elseif($addon->is_active)
                  <span class="badge badge-success">Aktif</span>
                @else
                  <span class="badge badge-warning">Nonaktif</span>
                @endif
              </td>
              <td class="text-center">
                @if($addon->trashed())
                  <form action="{{ route('addon.restore', $addon->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-xs btn-outline-success" title="Pulihkan">
                      <i class="fas fa-undo"></i> Pulihkan
                    </button>
                  </form>
                @else
                  <a href="{{ route('addon.edit', $addon->id) }}" class="btn btn-xs btn-outline-warning">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <form action="{{ route('addon.destroy', $addon->id) }}" method="POST" class="d-inline form-delete-addon">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-xs btn-outline-danger">
                      <i class="fas fa-trash"></i> Hapus
                    </button>
                  </form>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada add-on.</td></tr>
            @endforelse
          </tbody>
        </table>
        </div>{{-- /.table-responsive --}}
      </div>
    </div>

  </div>
</section>
@endsection

@section('footer-scripts')
<script>
  document.querySelectorAll('.form-delete-addon').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Hapus Add-on?',
        text: 'Add-on yang sudah dipakai pelanggan akan ikut terlepas.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
      }).then(function(result) {
        if (result.isConfirmed) form.submit();
      });
    });
  });
</script>
@endsection
