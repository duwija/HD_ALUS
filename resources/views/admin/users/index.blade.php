@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-users-cog"></i> Admin User Management</h3>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Admin User
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="adminUsersTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">Name</th>
                                    <th width="25%">Email</th>
                                    <th width="15%">Status</th>
                                    <th width="15%">Created At</th>
                                    <th width="10%">Last Login</th>
                                    <th width="10%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($admins as $admin)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $admin->name }}</strong>
                                            @if($admin->id === auth('admin')->id())
                                                <span class="badge badge-info ml-2">You</span>
                                            @endif
                                        </td>
                                        <td>{{ $admin->email }}</td>
                                        <td>
                                            @if($admin->is_active)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle"></i> Active
                                                </span>
                                            @else
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-times-circle"></i> Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ $admin->created_at->format('d M Y H:i') }}</small>
                                        </td>
                                        <td>
                                            @if($admin->last_login_at)
                                                <small>{{ $admin->last_login_at->diffForHumans() }}</small>
                                            @else
                                                <small class="text-muted">Never</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.users.edit', $admin->id) }}" 
                                                   class="btn btn-sm btn-warning"
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                @if($admin->id !== auth('admin')->id())
                                                    <form action="{{ route('admin.users.toggle-status', $admin->id) }}" 
                                                          method="POST" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('Yakin ingin mengubah status admin ini?')">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-sm {{ $admin->is_active ? 'btn-secondary' : 'btn-success' }}"
                                                                title="{{ $admin->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                            <i class="fas fa-{{ $admin->is_active ? 'ban' : 'check' }}"></i>
                                                        </button>
                                                    </form>

                                                    <form action="{{ route('admin.users.destroy', $admin->id) }}" 
                                                          method="POST" 
                                                          class="d-inline delete-form"
                                                          data-admin-name="{{ $admin->name }}"
                                                          data-admin-email="{{ $admin->email }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-sm btn-danger btn-delete" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-users-slash fa-3x mb-3"></i>
                                            <p>Belum ada admin user.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> <strong>Info:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Admin user dapat mengelola semua tenant dan konfigurasi sistem</li>
                            <li>Anda tidak dapat menghapus atau menonaktifkan akun Anda sendiri</li>
                            <li>Pastikan password yang kuat untuk keamanan sistem</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    $('#adminUsersTable').DataTable({
        "order": [[4, "desc"]],
        "pageLength": 25,
        "language": {
            "search": "Cari:",
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Tidak ada data",
            "infoFiltered": "(difilter dari _MAX_ total data)",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        }
    });

    // Handle delete button clicks with SweetAlert2
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.delete-form');
            const adminName = form.dataset.adminName;
            const adminEmail = form.dataset.adminEmail;
            
            Swal.fire({
                title: 'Konfirmasi Hapus Admin User',
                html: `Yakin hapus admin user:<br><strong>${adminName}</strong><br><small class="text-muted">${adminEmail}</small><br><br><span style="color: #dc3545;">Aksi ini tidak dapat dibatalkan!</span>`,
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
});
</script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@endsection
