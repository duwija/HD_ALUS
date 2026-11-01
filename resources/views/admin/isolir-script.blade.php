@extends('layout.main')
@section('title', 'Script Isolir MikroTik')

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <h1><i class="fas fa-terminal mr-2"></i>Script Captive Portal Isolir</h1>
    <a href="{{ url('settings/auto-isolir') }}" class="btn btn-sm btn-default mt-2"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Settings</a>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    @if ($method !== 'profile')
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        Metode isolir saat ini <strong>Disable secret</strong>. Script ini hanya berguna untuk metode
        <strong>Ganti profile</strong> — pelanggan yang di-disable tidak bisa login, jadi tidak akan melihat halaman isolir.
      </div>
    @endif
    @unless ($domainValid)
      <div class="alert alert-danger">Domain tenant (<code>{{ $opt['domain'] ?: 'kosong' }}</code>) tidak valid. Periksa pengaturan domain tenant.</div>
    @endunless

    <div class="row">
      <div class="col-lg-4">
        <div class="card card-outline card-primary">
          <div class="card-header"><h3 class="card-title">Parameter</h3></div>
          <div class="card-body">
            <form method="GET" action="{{ url('settings/auto-isolir/mikrotik-script') }}">
              <div class="form-group">
                <label>Versi RouterOS</label>
                <select name="ros" class="form-control">
                  <option value="7" {{ $opt['ros'] === 7 ? 'selected' : '' }}>v7.x</option>
                  <option value="6" {{ $opt['ros'] === 6 ? 'selected' : '' }}>v6.x (minimal 6.36)</option>
                </select>
              </div>
              <div class="form-group">
                <label>Port web proxy</label>
                <input type="number" name="proxy_port" class="form-control @error('proxy_port') is-invalid @enderror"
                       value="{{ $opt['proxy_port'] }}" min="1025" max="65535">
                @error('proxy_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Jika web proxy router sudah dipakai untuk keperluan lain, isi dengan port yang sedang dipakai.</small>
              </div>
              <div class="form-group">
                <label>Domain tambahan (walled garden)</label>
                <textarea name="extra" rows="5" class="form-control" placeholder="satu domain per baris&#10;contoh: web.whatsapp.com">{{ $extra }}</textarea>
                @if ($invalidHosts)
                  <small class="text-danger d-block">Diabaikan (tidak valid): {{ implode(', ', $invalidHosts) }}</small>
                @endif
                <small class="text-muted">
                  Sudah otomatis disertakan: <code>{{ $opt['domain'] }}</code>, CDN halaman tagihan
                  @if ($gatewayHosts), payment gateway aktif ({{ implode(', ', $gatewayHosts) }})@endif.
                </small>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-sync mr-1"></i> Buat Ulang Script</button>
            </form>
          </div>
        </div>

        <div class="card card-outline card-info">
          <div class="card-header"><h3 class="card-title">Cara kerja</h3></div>
          <div class="card-body small">
            <ol class="pl-3 mb-2">
              <li>Profile <code>{{ $opt['profile'] }}</code> memasukkan IP pelanggan ke address-list <code>isolir</code>.</li>
              <li>Akses HTTP pelanggan isolir diarahkan ke web proxy router, lalu ke
                <a href="{{ url('isolir') }}" target="_blank">{{ $opt['domain'] }}/isolir</a>.</li>
              <li>Hanya DNS dan domain walled garden yang bisa diakses. HTTPS lain diblokir; HP biasanya otomatis memunculkan notifikasi "Masuk ke jaringan".</li>
              <li>Di halaman isolir pelanggan memasukkan ID pelanggan + 4 digit terakhir nomor HP untuk membuka tagihan dan membayar.</li>
            </ol>
            <p class="mb-0 text-muted">Script bisa dijalankan ulang (rule lama berkomentar <code>ISOLIR:</code> dihapus dulu). Jalankan di setiap router PPPoE.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card card-outline card-danger">
          <div class="card-header">
            <h3 class="card-title">Script pemasangan</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-sm btn-danger js-copy" data-target="#isolirScript"><i class="fas fa-copy mr-1"></i> Copy</button>
            </div>
          </div>
          <div class="card-body p-0">
            <textarea id="isolirScript" class="form-control border-0" rows="26" readonly
                      style="font-family: monospace; font-size: 12px; white-space: pre; background:#1e1e1e; color:#d4d4d4;">{{ $script }}</textarea>
          </div>
          <div class="card-footer small text-muted">
            Paste ke <strong>New Terminal</strong> Winbox/WebFig atau SSH. Uji dulu di satu router dengan satu pelanggan uji.
            @if ($opt['ros'] === 7)
              Pada v7, baris proxy memakai <code>action=redirect action-data=</code>.
            @else
              Pada v6, baris proxy memakai <code>action=deny redirect-to=</code>.
            @endif
          </div>
        </div>

        <div class="card card-outline card-secondary collapsed-card">
          <div class="card-header">
            <h3 class="card-title">Script rollback (hapus captive portal)</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
            </div>
          </div>
          <div class="card-body p-0">
            <textarea id="isolirRollback" class="form-control border-0" rows="8" readonly
                      style="font-family: monospace; font-size: 12px; white-space: pre;">{{ $rollback }}</textarea>
          </div>
          <div class="card-footer">
            <button type="button" class="btn btn-sm btn-secondary js-copy" data-target="#isolirRollback"><i class="fas fa-copy mr-1"></i> Copy</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  document.querySelectorAll('.js-copy').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var el = document.querySelector(btn.dataset.target);
      var done = function () { var t = btn.innerHTML; btn.innerHTML = '<i class="fas fa-check mr-1"></i> Tersalin'; setTimeout(function () { btn.innerHTML = t; }, 1500); };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(el.value).then(done);
      } else {
        el.select(); document.execCommand('copy'); done();
      }
    });
  });
</script>
@endsection
