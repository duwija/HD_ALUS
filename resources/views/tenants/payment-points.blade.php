@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">

        {{-- ── FORM TAMBAH ──────────────────────────────────────── --}}
        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle"></i> Tambah Lokasi Pembayaran</h3>
                </div>
                <form method="POST" action="{{ route('admin.tenants.payment-points.store', $tenant->id) }}">
                    @csrf
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle"></i> {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            </div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger">
                                @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Nama Lokasi <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="mis: Kantor Pusat, BUMDes Desa X" required maxlength="30" value="{{ old('name') }}">
                        </div>
                        <div class="form-group">
                            <label>Nama Kontak</label>
                            <input type="text" name="contact_name" class="form-control" placeholder="Nama PIC pembayaran" value="{{ old('contact_name') }}">
                        </div>
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx" value="{{ old('phone') }}">
                        </div>
                        <div class="form-group">
                            <label>Alamat <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Alamat lengkap lokasi pembayaran" required>{{ old('address') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Koordinat GPS</label>
                            <input type="text" name="coordinate" class="form-control" placeholder="-8.12345,115.12345" value="{{ old('coordinate') }}">
                            <small class="text-muted">Copy dari Google Maps → klik kanan → koordinat</small>
                        </div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <input type="text" name="description" class="form-control" placeholder="Jam operasional, dll" value="{{ old('description') }}">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Simpan Lokasi
                        </button>
                    </div>
                </form>
            </div>

            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Info</h3>
                </div>
                <div class="card-body small">
                    <ul class="pl-3 mb-0">
                        <li>Lokasi yang ditambahkan akan muncul di modal <strong>Bumdes / Payment Point</strong> pada halaman invoice customer.</li>
                        <li>Tombol <strong>Maps</strong> aktif jika koordinat GPS diisi.</li>
                        <li>Hapus = soft delete, data tidak benar-benar dihapus dari DB.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- ── DAFTAR LOKASI ──────────────────────────────────────── --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-store"></i> Daftar Lokasi Pembayaran
                        <small class="text-muted">/ {{ $tenant->app_name }}</small>
                    </h3>
                    <a href="{{ route('admin.tenants.payment-gateway', $tenant->id) }}" class="btn btn-default btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali ke Payment Gateway
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($points->isEmpty())
                        <div class="alert alert-warning m-3">
                            <i class="fas fa-exclamation-triangle"></i> Belum ada lokasi pembayaran. Tambahkan menggunakan form di kiri.
                        </div>
                    @else
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Nama</th>
                                <th>Kontak / Telepon</th>
                                <th>Alamat</th>
                                <th class="text-center">Maps</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($points as $point)
                            <tr>
                                <td>
                                    <strong>{{ $point->name }}</strong>
                                    @if($point->description)
                                    <br><small class="text-muted">{{ $point->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $point->contact_name }}
                                    @if($point->phone)
                                    <br><small>{{ $point->phone }}</small>
                                    @endif
                                </td>
                                <td><small>{{ $point->address }}</small></td>
                                <td class="text-center">
                                    @if($point->coordinate)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $point->coordinate }}" target="_blank" class="btn btn-xs btn-info">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </a>
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <button type="button" class="btn btn-xs btn-warning"
                                        onclick="editPoint({{ $point->id }}, {{ json_encode($point->name) }}, {{ json_encode($point->contact_name) }}, {{ json_encode($point->phone) }}, {{ json_encode($point->address) }}, {{ json_encode($point->coordinate ?? '') }}, {{ json_encode($point->description ?? '') }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.tenants.payment-points.destroy', [$tenant->id, $point->id]) }}" style="display:inline;"
                                          onsubmit="return confirm('Hapus lokasi {{ $point->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Modal Edit --}}
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-edit"></i> Edit Lokasi Pembayaran</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" id="editForm">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Lokasi <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required maxlength="30">
                    </div>
                    <div class="form-group">
                        <label>Nama Kontak</label>
                        <input type="text" name="contact_name" id="edit_contact_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Alamat <span class="text-danger">*</span></label>
                        <textarea name="address" id="edit_address" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Koordinat GPS</label>
                        <input type="text" name="coordinate" id="edit_coordinate" class="form-control" placeholder="-8.12345,115.12345">
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <input type="text" name="description" id="edit_description" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editPoint(id, name, contact, phone, address, coordinate, description) {
    var base = '{{ route("admin.tenants.payment-points.update", [$tenant->id, "__ID__"]) }}';
    document.getElementById('editForm').action = base.replace('__ID__', id);
    document.getElementById('edit_name').value         = name;
    document.getElementById('edit_contact_name').value = contact || '';
    document.getElementById('edit_phone').value        = phone || '';
    document.getElementById('edit_address').value      = address;
    document.getElementById('edit_coordinate').value   = coordinate || '';
    document.getElementById('edit_description').value  = description || '';
    $('#editModal').modal('show');
}
</script>
@endsection
