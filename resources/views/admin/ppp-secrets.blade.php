@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">

    {{-- Flash messages --}}
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

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-key"></i> PPP CHAP Secrets</h3>
        <small class="text-muted"><code>/etc/ppp/chap-secrets</code></small>
    </div>

    {{-- Status file --}}
    <div class="alert {{ $isFileWritable ? 'alert-success' : 'alert-warning' }} py-2 mb-4">
        <i class="fas {{ $isFileWritable ? 'fa-unlock' : 'fa-lock' }}"></i>
        File <code>/etc/ppp/chap-secrets</code>:
        @if(!$fileExists)
            <strong class="text-danger">Belum ada.</strong> Tambahkan entri pertama untuk membuatnya.
        @elseif($isFileWritable)
            <strong>Dapat ditulis</strong> oleh web server.
        @else
            <strong>Read-only.</strong> Lihat petunjuk konfigurasi sudoers di bawah.
        @endif
    </div>

    <div class="row">

        {{-- Form tambah entri --}}
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-plus-circle"></i> Tambah Entri PPP Secret
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.ppp-secrets.store') }}" class="form-row align-items-end">
                        @csrf
                        <div class="form-group col-md-3 mb-2">
                            <label class="font-weight-bold">Username</label>
                            <small class="text-muted d-block">Boleh: huruf, angka, . _ @ -</small>
                            <input type="text" name="username"
                                   class="form-control @error('username') is-invalid @enderror"
                                   placeholder="contoh: pelanggan01"
                                   value="{{ old('username') }}"
                                   required pattern="[a-zA-Z0-9._@-]+" maxlength="100" autocomplete="off">
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-3 mb-2">
                            <label class="font-weight-bold">Password</label>
                            <small class="text-muted d-block">&nbsp;</small>
                            <input type="text" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="contoh: rahasia123"
                                   value="{{ old('password') }}"
                                   required maxlength="100" autocomplete="off">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-2 mb-2">
                            <label class="font-weight-bold">IP Address</label>
                            <small class="text-muted d-block">Kosongkan = wildcard <code>*</code></small>
                            <input type="text" name="ip" class="form-control"
                                   placeholder="* atau 192.168.1.10"
                                   value="{{ old('ip') }}" maxlength="50">
                        </div>
                        <div class="form-group col-md-2 mb-2 align-self-end">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Simpan ke File
                            </button>
                        </div>
                    </form>
                    <small class="text-muted">
                        Format yang ditulis: <code>username &nbsp; * &nbsp; password &nbsp; IP</code>
                    </small>
                </div>
            </div>
        </div>

        {{-- Tabel isi file --}}
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-list"></i>
                    Daftar Entri di chap-secrets
                    <span class="badge badge-light ml-1">{{ count($entries) }}</span>
                </div>
                <div class="card-body p-0">
                    @if(empty($entries))
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        File kosong atau belum ada. Tambahkan entri di atas.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-bordered mb-0" id="tbl-chap">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="40">#</th>
                                    <th>Username</th>
                                    <th>Server</th>
                                    <th>Password</th>
                                    <th>IP Address</th>
                                    <th width="80" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $i => $entry)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><code>{{ $entry['username'] }}</code></td>
                                    <td>{{ $entry['server'] }}</td>
                                    <td>
                                        <span class="text-muted" id="pw-{{ $i }}">••••••</span>
                                        <button type="button" class="btn btn-link btn-sm p-0 ml-1 toggle-pw"
                                                data-i="{{ $i }}" data-pw="{{ $entry['password'] }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                    <td>{{ $entry['ip'] }}</td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('admin.ppp-secrets.destroy') }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="username" value="{{ $entry['username'] }}">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Hapus user \'{{ $entry['username'] }}\' dari chap-secrets?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Petunjuk sudoers --}}
        @if(!$isFileWritable)
        <div class="col-12 mt-4">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-exclamation-triangle"></i> Konfigurasi Sudoers Diperlukan
                </div>
                <div class="card-body">
                    <p>Jalankan sebagai <strong>root</strong>:</p>
                    <pre class="bg-dark text-white p-3 rounded">sudo visudo -f /etc/sudoers.d/ppp-chap</pre>
                    <p>Tambahkan:</p>
                    <pre class="bg-dark text-white p-3 rounded">apache ALL=(root) NOPASSWD: /bin/cp /tmp/chap_* /etc/ppp/chap-secrets
apache ALL=(root) NOPASSWD: /bin/chmod 640 /etc/ppp/chap-secrets</pre>
                    <p>Set permission:</p>
                    <pre class="bg-dark text-white p-3 rounded">sudo chown root:apache /etc/ppp/chap-secrets
sudo chmod 640 /etc/ppp/chap-secrets</pre>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    $('.toggle-pw').on('click', function () {
        var i   = $(this).data('i');
        var pw  = $(this).data('pw') || '(kosong)';
        var el  = $('#pw-' + i);
        var ico = $(this).find('i');
        if (el.text() === '\u00b7\u00b7\u00b7\u00b7\u00b7\u00b7') {
            el.text(pw); ico.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            el.text('\u00b7\u00b7\u00b7\u00b7\u00b7\u00b7'); ico.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    if ($('#tbl-chap').length && $('#tbl-chap tbody tr').length > 5) {
        $('#tbl-chap').DataTable({
            pageLength: 25,
            order: [[1, 'asc']],
            columnDefs: [{ orderable: false, targets: [3, 5] }]
        });
    }
});
</script>
@endsection
