@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-user-cog"></i> Users: {{ $tenant->app_name }}
                    </h3>
                    <div>
                        <button class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-target="#addUserModal">
                            <i class="fas fa-plus"></i> Tambah User
                        </button>
                        <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
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
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="usersTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="4%">#</th>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th>Email</th>
                                    <th>Privilege</th>
                                    <th>Telepon</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th class="text-center" width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $user->name }}</strong></td>
                                    <td>{{ $user->full_name ?: '-' }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @php
                                            $badgeColors = [
                                                'admin'   => 'danger',
                                                'user'    => 'primary',
                                                'vendor'  => 'warning',
                                                'merchant'=> 'info',
                                                'payment' => 'success',
                                            ];
                                            $bc = $badgeColors[$user->privilege] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $bc }}">{{ ucfirst($user->privilege) }}</span>
                                    </td>
                                    <td>{{ $user->phone ?: '-' }}</td>
                                    <td>
                                        @if($user->is_active_employee)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td><small>{{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('d M Y') : '-' }}</small></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning btn-edit-user"
                                            data-name="{{ $user->name }}"
                                            data-fullname="{{ $user->full_name }}"
                                            data-email="{{ $user->email }}"
                                            data-privilege="{{ $user->privilege }}"
                                            data-phone="{{ $user->phone }}"
                                            data-active="{{ $user->is_active_employee }}"
                                            data-action="{{ route('admin.tenants.users.update', [$tenant->id, $user->id]) }}"
                                            title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info btn-reset-password"
                                            data-name="{{ $user->name }}"
                                            data-action="{{ route('admin.tenants.users.reset-password', [$tenant->id, $user->id]) }}"
                                            title="Reset Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <form action="{{ route('admin.tenants.users.destroy', [$tenant->id, $user->id]) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                                        Belum ada user di tenant ini.
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

{{-- Modal: Tambah User --}}
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.tenants.users.store', $tenant->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus mr-1"></i> Tambah User</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                               placeholder="Harus unik, tanpa spasi" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}">
                    </div>
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Privilege <span class="text-danger">*</span></label>
                        <select name="privilege" class="form-control" required>
                            <option value="user"    {{ old('privilege') === 'user'     ? 'selected' : '' }}>User (Staff)</option>
                            <option value="admin"   {{ old('privilege') === 'admin'    ? 'selected' : '' }}>Admin</option>
                            <option value="vendor"  {{ old('privilege') === 'vendor'   ? 'selected' : '' }}>Vendor/Teknisi</option>
                            <option value="merchant"{{ old('privilege') === 'merchant' ? 'selected' : '' }}>Merchant</option>
                            <option value="payment" {{ old('privilege') === 'payment'  ? 'selected' : '' }}>Payment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Telepon</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                    </div>
                    <div class="form-group">
                        <label>Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Edit User --}}
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editUserForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit mr-1"></i> Edit User: <span id="editUserName"></span></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="full_name" id="editFullName" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Privilege <span class="text-danger">*</span></label>
                        <select name="privilege" id="editPrivilege" class="form-control" required>
                            <option value="user">User (Staff)</option>
                            <option value="admin">Admin</option>
                            <option value="vendor">Vendor/Teknisi</option>
                            <option value="merchant">Merchant</option>
                            <option value="payment">Payment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Telepon</label>
                        <input type="text" name="phone" id="editPhone" class="form-control">
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="editActive" name="is_active_employee" value="1">
                            <label class="custom-control-label" for="editActive">User Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Reset Password --}}
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="resetPasswordForm" action="" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key mr-1"></i> Reset Password: <span id="resetUserName"></span></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-key mr-1"></i> Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        order: [[1, 'asc']],
        pageLength: 25,
    });

    // Edit user — set data first, then open modal
    $(document).on('click', '.btn-edit-user', function() {
        var btn = $(this);
        $('#editUserName').text(btn.data('name'));
        $('#editFullName').val(btn.data('fullname') || '');
        $('#editEmail').val(btn.data('email') || '');
        $('#editPrivilege').val(btn.data('privilege') || 'user');
        $('#editPhone').val(btn.data('phone') || '');
        $('#editActive').prop('checked', btn.data('active') == 1);
        $('#editUserForm').attr('action', btn.data('action'));
        $('#editUserModal').modal('show');
    });

    // Reset password — clear fields, set action, then open modal
    $(document).on('click', '.btn-reset-password', function() {
        var btn = $(this);
        $('#resetUserName').text(btn.data('name'));
        $('#resetPasswordForm').attr('action', btn.data('action'));
        $('#resetPasswordForm input[type=password]').val('');
        $('#resetPasswordModal').modal('show');
    });

    // Reopen add modal if there are validation errors (old input exists)
    @if($errors->any() && old('name'))
        $('#addUserModal').modal('show');
    @endif
});
</script>
@endsection
