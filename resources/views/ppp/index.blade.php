@extends('layout.main')
@section('title','PPP CHAP Secrets Manager')
@section('content')

<section class="content-header p-0 m-0 p-md-3 m-md-3">

  {{-- Flash messages --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
  @endif

  {{-- ─── Status file ─────────────────────────────────────────── --}}
  <div class="alert {{ $isFileWritable ? 'alert-success' : 'alert-warning' }} mb-3">
    <i class="fas {{ $isFileWritable ? 'fa-unlock' : 'fa-lock' }}"></i>
    File <code>/etc/ppp/chap-secrets</code>:
    @if($isFileWritable)
      <strong>Dapat ditulis</strong> oleh web server.
    @else
      <strong>Read-only</strong>. Konfigurasi sudoers diperlukan — lihat petunjuk di bawah.
    @endif
    <form method="POST" action="{{ route('ppp.sync') }}" class="d-inline float-right">
      @csrf
      <button type="submit" class="btn btn-sm btn-primary"
              onclick="return confirm('Sync semua PPP user dari database ke /etc/ppp/chap-secrets?')">
        <i class="fas fa-sync-alt"></i> Sync DB → File
      </button>
    </form>
  </div>

  {{-- ─── Tambah entri manual ─────────────────────────────────── --}}
  <div class="card card-info card-outline mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-plus-circle"></i> Tambah Entri Manual ke chap-secrets</h3>
      <div class="card-tools">
        <button type="button" class="btn btn-tool" data-card-widget="collapse">
          <i class="fas fa-minus"></i>
        </button>
      </div>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('ppp.store') }}" class="form-inline">
        @csrf
        <div class="form-group mr-2">
          <label class="mr-1">Username</label>
          <input type="text" name="username" class="form-control form-control-sm @error('username') is-invalid @enderror"
                 placeholder="pppuser1" required pattern="[a-zA-Z0-9._@-]+" maxlength="100">
          @error('username')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>
        <div class="form-group mr-2">
          <label class="mr-1">Password</label>
          <input type="text" name="password" class="form-control form-control-sm @error('password') is-invalid @enderror"
                 placeholder="password123" required maxlength="100">
          @error('password')
            <span class="invalid-feedback">{{ $message }}</span>
          @enderror
        </div>
        <button type="submit" class="btn btn-sm btn-info">
          <i class="fas fa-save"></i> Simpan ke File
        </button>
      </form>
      <small class="text-muted">Format: <code>username * password *</code> (server & IP menggunakan wildcard)</small>
    </div>
  </div>

  {{-- ─── Tabel Customer dari Database ───────────────────────── --}}
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-database"></i>
        PPP Users dari Database Customer ({{ $customers->count() }} user)
      </h3>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm table-hover table-striped" id="tbl-db">
        <thead class="thead-dark">
          <tr>
            <th>#</th>
            <th>Customer ID</th>
            <th>Nama</th>
            <th>PPP Username</th>
            <th>PPP Password</th>
            <th>Status</th>
            <th>di chap-secrets</th>
          </tr>
        </thead>
        <tbody>
          @forelse($customers as $i => $c)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>
              <a href="{{ url('customer/' . $c->id) }}" target="_blank">
                {{ $c->customer_id }}
              </a>
            </td>
            <td>{{ $c->name }}</td>
            <td><code>{{ $c->pppoe }}</code></td>
            <td>
              <span class="text-muted" id="pw-{{ $c->id }}">••••••</span>
              <button type="button" class="btn btn-xs btn-link p-0 ml-1 toggle-pw" data-id="{{ $c->id }}"
                      data-pw="{{ $c->password ?? '' }}">
                <i class="fas fa-eye"></i>
              </button>
            </td>
            <td>
              @if($c->id_status == 1)
                <span class="badge badge-secondary">Lead</span>
              @elseif($c->id_status == 2)
                <span class="badge badge-success">Aktif</span>
              @elseif($c->id_status == 3)
                <span class="badge badge-warning">Isolir</span>
              @else
                <span class="badge badge-light">{{ $c->id_status }}</span>
              @endif
            </td>
            <td>
              {{-- Cek status sync (dibandingkan dengan file) akan dilakukan via JS --}}
              <span class="badge badge-secondary sync-status" data-pppoe="{{ $c->pppoe }}">–</span>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center text-muted">Belum ada customer dengan data PPP.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ─── Entri di file tapi tidak ada di DB ─────────────────── --}}
  @if(!empty($fileOnly))
  <div class="card card-warning card-outline mt-3">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-file-alt"></i>
        Entri di chap-secrets yang Tidak Ada di Database ({{ count($fileOnly) }} entri)
      </h3>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm table-hover table-striped" id="tbl-file">
        <thead class="thead-dark">
          <tr>
            <th>#</th>
            <th>Username</th>
            <th>Server</th>
            <th>Password</th>
            <th>IP</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($fileOnly as $i => $entry)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td><code>{{ $entry['username'] }}</code></td>
            <td>{{ $entry['server'] }}</td>
            <td>
              <span class="text-muted" id="fpw-{{ $i }}">••••••</span>
              <button type="button" class="btn btn-xs btn-link p-0 ml-1 toggle-fpw" data-i="{{ $i }}"
                      data-pw="{{ $entry['password'] }}">
                <i class="fas fa-eye"></i>
              </button>
            </td>
            <td>{{ $entry['ip'] }}</td>
            <td>
              <form method="POST" action="{{ route('ppp.destroy') }}" class="d-inline">
                @csrf
                @method('DELETE')
                <input type="hidden" name="username" value="{{ $entry['username'] }}">
                <button type="submit" class="btn btn-xs btn-danger"
                        onclick="return confirm('Hapus \'{{ $entry['username'] }}\' dari chap-secrets?')">
                  <i class="fas fa-trash"></i> Hapus
                </button>
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- ─── Petunjuk sudoers ────────────────────────────────────── --}}
  @if(!$isFileWritable)
  <div class="card card-secondary card-outline mt-3">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-info-circle"></i> Konfigurasi Sudoers</h3>
      <div class="card-tools">
        <button type="button" class="btn btn-tool" data-card-widget="collapse">
          <i class="fas fa-minus"></i>
        </button>
      </div>
    </div>
    <div class="card-body">
      <p>Agar web server bisa menulis ke <code>/etc/ppp/chap-secrets</code>, jalankan perintah berikut sebagai root:</p>
      <pre class="bg-dark text-white p-2">sudo visudo -f /etc/sudoers.d/ppp-chap</pre>
      <p>Tambahkan baris berikut:</p>
      <pre class="bg-dark text-white p-2">www-data ALL=(root) NOPASSWD: /bin/cp /tmp/chap_* /etc/ppp/chap-secrets
www-data ALL=(root) NOPASSWD: /bin/chmod 640 /etc/ppp/chap-secrets</pre>
      <p class="mt-2">Kemudian set permission file:</p>
      <pre class="bg-dark text-white p-2">sudo chmod 640 /etc/ppp/chap-secrets
sudo chown root:www-data /etc/ppp/chap-secrets</pre>
    </div>
  </div>
  @endif

</section>

@endsection

@push('scripts')
<script>
$(function () {
  // Toggle tampilkan/sembunyikan password DB
  $('.toggle-pw').on('click', function () {
    var id  = $(this).data('id');
    var pw  = $(this).data('pw') || '(kosong)';
    var el  = $('#pw-' + id);
    var ico = $(this).find('i');
    if (el.text() === '••••••') {
      el.text(pw);
      ico.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      el.text('••••••');
      ico.removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  // Toggle tampilkan/sembunyikan password File
  $('.toggle-fpw').on('click', function () {
    var i   = $(this).data('i');
    var pw  = $(this).data('pw') || '(kosong)';
    var el  = $('#fpw-' + i);
    var ico = $(this).find('i');
    if (el.text() === '••••••') {
      el.text(pw);
      ico.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      el.text('••••••');
      ico.removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  // DataTable untuk tabel DB
  if ($('#tbl-db tbody tr').length > 1) {
    $('#tbl-db').DataTable({
      responsive: true,
      pageLength: 25,
      order: [[3, 'asc']],
    });
  }
});
</script>
@endpush
