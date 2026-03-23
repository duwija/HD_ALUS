@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-building"></i> Tenant Management
                    </h3>
                    <div>
                        <a href="{{ route('admin.tenants.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Tenant Baru
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Domain</th>
                                    <th>App Name</th>
                                    <th>Rescode</th>
                                    <th>Database</th>
                                    <th>Features</th>
                                    <th width="100" class="text-center">Status</th>
                                    <th width="200" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tenants as $index => $tenant)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $tenant->domain }}</strong><br>
                                        <small class="text-muted">{{ $tenant->mail_from }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $tenant->app_name }}</strong><br>
                                        <small class="text-muted">{{ $tenant->signature }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-info badge-lg">{{ $tenant->rescode }}</span>
                                    </td>
                                    <td>
                                        <code>{{ $tenant->db_database }}</code><br>
                                        <small class="text-muted">{{ $tenant->db_host }}:{{ $tenant->db_port }}</small>
                                    </td>
                                    <td>
                                        @if($tenant->features['accounting'] ?? false)
                                            <span class="badge badge-success">Accounting</span>
                                        @endif
                                        @if($tenant->features['ticketing'] ?? false)
                                            <span class="badge badge-success">Ticketing</span>
                                        @endif
                                        @if($tenant->features['whatsapp'] ?? false)
                                            <span class="badge badge-success">WhatsApp</span>
                                        @endif
                                        @if($tenant->features['payment_gateway'] ?? false)
                                            <span class="badge badge-success">Payment</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('admin.tenants.toggle', $tenant->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $tenant->is_active ? 'btn-success' : 'btn-secondary' }}" 
                                                    onclick="return confirm('Toggle status tenant?')">
                                                @if($tenant->is_active)
                                                    <i class="fas fa-check-circle"></i> Active
                                                @else
                                                    <i class="fas fa-times-circle"></i> Inactive
                                                @endif
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-success" 
                                                    onclick="backupDatabase({{ $tenant->id }}, '{{ $tenant->app_name }}')" 
                                                    title="Backup Database">
                                                <i class="fas fa-database"></i>
                                            </button>
                                            <a href="{{ route('admin.tenants.show', $tenant->id) }}" 
                                               class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.tenants.edit', $tenant->id) }}" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.tenants.destroy', $tenant->id) }}" 
                                                  method="POST" style="display: inline;"
                                                  class="delete-form"
                                                  data-tenant-name="{{ $tenant->app_name }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-danger btn-delete" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                        Belum ada tenant. <a href="{{ route('admin.tenants.create') }}">Tambah tenant pertama</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <div class="alert alert-info">
                            <strong><i class="fas fa-info-circle"></i> Info:</strong>
                            Total {{ $tenants->count() }} tenant terdaftar.
                            Active: {{ $tenants->where('is_active', true)->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete button clicks
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.delete-form');
            const tenantName = form.dataset.tenantName;
            
            Swal.fire({
                title: 'Konfirmasi Hapus',
                html: `Yakin hapus tenant <strong>${tenantName}</strong>?<br><span style="color: #dc3545;">Data tidak bisa dikembalikan!</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
                cancelButtonText: '<i class="fas fa-times"></i> Batal',
                reverseButtons: true,
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Menghapus...',
                        html: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    form.submit();
                }
            });
        });
    });
    
    // Backup database function
    window.backupDatabase = function(tenantId, tenantName) {
        Swal.fire({
            title: 'Backup Database?',
            html: `Database <strong>${tenantName}</strong> akan di-backup.<br><small class="text-muted">Proses mungkin memakan waktu beberapa detik...</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-database"></i> Ya, Backup!',
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                // Get CSRF token from meta tag or fallback to form token
                let csrfToken = '';
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    csrfToken = metaTag.getAttribute('content');
                } else {
                    // Fallback: get from hidden input
                    const tokenInput = document.querySelector('input[name="_token"]');
                    if (tokenInput) {
                        csrfToken = tokenInput.value;
                    } else {
                        csrfToken = '{{ csrf_token() }}';
                    }
                }
                
                return fetch('/admin/tenants/' + tenantId + '/backup', {
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
                });
            }
        });
    };
});
</script>
@endsection
