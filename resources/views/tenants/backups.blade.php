@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-database"></i> Backup Files: {{ $tenant->app_name }}
                    </h3>
                    <div>
                        <button type="button" class="btn btn-success" onclick="backupDatabase({{ $tenant->id }})">
                            <i class="fas fa-plus"></i> Create New Backup
                        </button>
                        <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Lokasi:</strong> <code>public/tenants/{{ $tenant->rescode }}/backup/</code>
                        <span class="float-right">
                            <strong>Total Files:</strong> {{ count($files) }}
                        </span>
                    </div>

                    @if(count($files) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Filename</th>
                                    <th width="120">Size</th>
                                    <th width="180">Date Created</th>
                                    <th width="200" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($files as $index => $file)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <i class="fas fa-file-archive text-primary"></i>
                                        {{ $file['name'] }}
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">{{ $file['size_formatted'] }}</span>
                                    </td>
                                    <td>
                                        <i class="far fa-clock"></i>
                                        {{ $file['modified_formatted'] }}
                                        <small class="text-muted d-block">
                                            ({{ \Carbon\Carbon::createFromTimestamp($file['modified'])->diffForHumans() }})
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.tenants.backups.download', [$tenant->id, $file['name']]) }}" 
                                               class="btn btn-sm btn-primary" 
                                               title="Download">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger btn-delete" 
                                                    data-filename="{{ $file['name'] }}"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h5>Tidak ada backup file</h5>
                        <p class="mb-0">Klik tombol "Create New Backup" untuk membuat backup database.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Backup database function
function backupDatabase(tenantId) {
    Swal.fire({
        title: 'Backup Database?',
        html: 'Database tenant ini akan di-backup.<br><small class="text-muted">Proses mungkin memakan waktu beberapa detik...</small>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-database"></i> Ya, Backup!',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            // Get CSRF token
            let csrfToken = '';
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                csrfToken = metaTag.getAttribute('content');
            } else {
                const tokenInput = document.querySelector('input[name="_token"]');
                if (tokenInput) {
                    csrfToken = tokenInput.value;
                } else {
                    csrfToken = '{{ csrf_token() }}';
                }
            }
            
            return fetch('{{ route('admin.tenants.backup', $tenant->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Backup failed');
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(
                    `Request failed: ${error}`
                );
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Backup Berhasil!',
                html: result.value.message + '<br><small class="text-muted">' + result.value.filename + '</small>',
                confirmButtonColor: '#28a745'
            }).then(() => {
                location.reload();
            });
        }
    });
}

// Delete backup confirmation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const filename = this.dataset.filename;
            
            Swal.fire({
                title: 'Hapus Backup File?',
                html: `File <strong>${filename}</strong> akan dihapus permanen!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/admin/tenants/{{ $tenant->id }}/backups/' + filename;
                    
                    // Add CSRF token
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    form.appendChild(csrfInput);
                    
                    // Add DELETE method
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(methodInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
});
</script>

@endsection
