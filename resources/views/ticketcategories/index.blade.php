@extends('layout.main')

@section('content')
<style>
  .tc-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
  }
  .tc-card-header {
    background: var(--bg-surface-2);
    border-bottom: 1px solid var(--border);
    padding: 14px 20px;
    display: flex; align-items: center; justify-content: space-between;
  }
  .tc-card-header h5 {
    margin: 0; font-size: 15px; font-weight: 700;
    color: var(--text-primary);
    display: flex; align-items: center; gap: 8px;
  }
  .tc-card-header h5 i { color: var(--brand); }
  .tc-card-body { padding: 16px; }
  .tc-table th {
    font-size: 11px !important; text-transform: uppercase; letter-spacing: 0.5px;
    font-weight: 700; color: var(--text-secondary) !important;
    background: var(--bg-surface-2) !important; border-color: var(--border) !important;
  }
  .tc-table td {
    vertical-align: middle; font-size: 13px;
    border-color: var(--border) !important;
    color: var(--text-primary) !important;
    background: var(--bg-surface) !important;
  }
  .tc-table tbody tr:hover td { background: var(--brand-light) !important; }
  .wf-step {
    display: inline-flex; align-items: center; gap: 4px;
    background: var(--brand-light); color: var(--brand);
    border: 1px solid rgba(163,48,28,.2);
    border-radius: 20px; padding: 2px 9px;
    font-size: 11px; font-weight: 600; margin: 2px;
  }
  .wf-step i { font-size: 9px; opacity: .6; }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="tc-card">
                <div class="tc-card-header">
                  <h5><i class="fas fa-tags"></i>Kategori Tiket</h5>
                  <a href="{{route('ticketcategories.create')}}" class="btn btn-sm btn-primary" style="border-radius:8px;">
                    <i class="fas fa-plus mr-1"></i>Tambah Kategori
                  </a>
                </div>

                <div class="tc-card-body">
                    <!-- @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif -->

                    <div class="table-responsive">
                        <table class="table table-hover tc-table mb-0" id="example1">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="25%">Nama Kategori</th>
                                    <th width="50%">Workflow</th>
                                    <th width="20%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $key => $category)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $category->name }}</td>
                                        <td>
                                            @if($category->workflow)
                                                <div class="d-flex flex-wrap">
                                                    @foreach($category->workflow as $i => $step)
                                                        <span class="wf-step"><i class="fas fa-circle"></i>{{ $i+1 }}. {{ $step }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex" style="gap:6px">
                                                <a href="{{ route('ticketcategories.edit', $category->id) }}" 
                                                   class="btn btn-sm btn-warning" style="border-radius:8px">
                                                    <i class="fas fa-edit mr-1"></i>Edit
                                                </a>
                                                <form action="{{ route('ticketcategories.destroy', $category->id) }}" 
                                                      method="POST" 
                                                      class="delete-form"
                                                      style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-name="{{ $category->name }}" style="border-radius:8px">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center" style="padding:32px;color:var(--text-muted)">
                                          <i class="fas fa-tags" style="font-size:24px;opacity:.3;display:block;margin-bottom:8px"></i>
                                          Belum ada kategori tiket
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
<script>
$(document).ready(function() {
    // SweetAlert untuk konfirmasi delete
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var form = button.closest('.delete-form');
        var categoryName = button.data('name');
        
        Swal.fire({
            title: 'Konfirmasi Hapus',
            html: 'Apakah Anda yakin ingin menghapus kategori<br><strong>"' + categoryName + '"</strong>?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // SweetAlert untuk success message
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '{{ session('success') }}',
            timer: 3000,
            showConfirmButton: false
        });
    @endif
});
</script>
@endsection
