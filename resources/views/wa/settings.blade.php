@extends('layout.main')
@section('title', 'Pengaturan WA Gateway')

@section('content')
<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1><i class="fab fa-whatsapp mr-2"></i>Pengaturan WA Gateway</h1>
    <a href="{{ url('wa/dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Dashboard</a>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">

      {{-- ===================== Kecepatan blast ===================== --}}
      <div class="col-lg-6">
        <div class="card card-outline card-success">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-tachometer-alt mr-1"></i> Kecepatan Blast Notifikasi</h3>
          </div>
          <div class="card-body">
            <p class="mb-3">
              Jeda antar pesan saat blast (pengingat tagihan, invoice bulanan, pengingat sebelum isolir).
              Semakin lambat semakin kecil risiko nomor WhatsApp diblokir, terutama untuk WA Gateway.
            </p>

            <form method="POST" action="{{ url('wa/settings/speed') }}" id="speedForm">
              @csrf
              <div class="mb-3">
                @foreach ($presets as $key => $p)
                  <div class="custom-control custom-radio">
                    <input type="radio" class="custom-control-input" id="preset_{{ $key }}" name="preset" value="{{ $key }}"
                           data-min="{{ $p['NOTIF_DELAY_MIN'] }}" data-max="{{ $p['NOTIF_DELAY_MAX'] }}"
                           data-every="{{ $p['NOTIF_LONG_PAUSE_EVERY'] }}" data-extra="{{ $p['NOTIF_LONG_PAUSE_EXTRA'] }}"
                           {{ $activePreset === $key ? 'checked' : '' }}>
                    <label class="custom-control-label" for="preset_{{ $key }}">
                      <strong>{{ $p['label'] }}</strong>
                      <small class="text-muted">— jeda {{ $p['NOTIF_DELAY_MIN'] }}–{{ $p['NOTIF_DELAY_MAX'] }} detik, istirahat panjang tiap {{ number_format($p['NOTIF_LONG_PAUSE_EVERY'], 0, ',', '.') }} pesan</small>
                    </label>
                  </div>
                @endforeach
                <div class="custom-control custom-radio">
                  <input type="radio" class="custom-control-input" id="preset_custom" name="preset" value="custom" {{ $activePreset === 'custom' ? 'checked' : '' }}>
                  <label class="custom-control-label" for="preset_custom"><strong>Custom</strong></label>
                </div>
              </div>

              <div class="form-row" id="customFields">
                <div class="form-group col-6">
                  <label class="small mb-1">Jeda minimum (detik)</label>
                  <input type="number" name="NOTIF_DELAY_MIN" class="form-control form-control-sm speed-input" min="1" max="600" value="{{ old('NOTIF_DELAY_MIN', $speed['NOTIF_DELAY_MIN']) }}">
                </div>
                <div class="form-group col-6">
                  <label class="small mb-1">Jeda maksimum (detik)</label>
                  <input type="number" name="NOTIF_DELAY_MAX" class="form-control form-control-sm speed-input" min="1" max="600" value="{{ old('NOTIF_DELAY_MAX', $speed['NOTIF_DELAY_MAX']) }}">
                </div>
                <div class="form-group col-6">
                  <label class="small mb-1">Istirahat panjang tiap (pesan)</label>
                  <input type="number" name="NOTIF_LONG_PAUSE_EVERY" class="form-control form-control-sm speed-input" min="10" max="100000" value="{{ old('NOTIF_LONG_PAUSE_EVERY', $speed['NOTIF_LONG_PAUSE_EVERY']) }}">
                </div>
                <div class="form-group col-6">
                  <label class="small mb-1">Lama istirahat panjang (± detik)</label>
                  <input type="number" name="NOTIF_LONG_PAUSE_EXTRA" class="form-control form-control-sm speed-input" min="1" max="100000" value="{{ old('NOTIF_LONG_PAUSE_EXTRA', $speed['NOTIF_LONG_PAUSE_EXTRA']) }}">
                </div>
              </div>

              <p class="mb-3"><i class="far fa-clock mr-1"></i> Perkiraan durasi: <strong id="speedEstimate">-</strong> per 1.000 pesan</p>

              <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Simpan Kecepatan</button>
            </form>
          </div>
        </div>
      </div>

      {{-- ===================== Suppress nomor gagal ===================== --}}
      <div class="col-lg-6">
        <div class="card card-outline card-warning">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-ban mr-1"></i> Penanganan Nomor Gagal</h3>
          </div>
          <div class="card-body">
            <p class="mb-2">
              Nomor yang gagal dikirimi berturut-turut dihentikan sementara (<em>suppress</em>) agar tidak terus dicoba
              dan membebani nomor pengirim. Hanya berlaku untuk pengiriman lewat <strong>WA Gateway</strong>.
            </p>
            <p class="mb-3 text-muted"><small>Gagal karena gangguan gateway (gateway mati, sesi belum login/scan QR) tidak dihitung ke nomor pelanggan.</small></p>

            <form method="POST" action="{{ url('wa/settings/suppress') }}">
              @csrf
              <div class="custom-control custom-switch mb-3">
                <input type="hidden" name="WA_SUPPRESS_ENABLED" value="0">
                <input type="checkbox" class="custom-control-input" id="WA_SUPPRESS_ENABLED" name="WA_SUPPRESS_ENABLED" value="1" {{ old('WA_SUPPRESS_ENABLED', $suppressEnabled) ? 'checked' : '' }}>
                <label class="custom-control-label" for="WA_SUPPRESS_ENABLED">Aktifkan suppress otomatis</label>
              </div>

              <table class="table table-sm mb-3">
                <thead><tr><th>Tahap</th><th>Gagal berturut-turut</th><th>Dihentikan selama</th></tr></thead>
                <tbody>
                  @foreach ([1 => 'WA_SUPPRESS_FAIL_THRESHOLD', 2 => 'WA_SUPPRESS_STEP2_THRESHOLD', 3 => 'WA_SUPPRESS_STEP3_THRESHOLD'] as $step => $thKey)
                    <tr>
                      <td class="align-middle">{{ $step }}</td>
                      <td><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">&ge;</span></div>
                        <input type="number" name="{{ $thKey }}" class="form-control" min="2" value="{{ old($thKey, $suppress[$thKey]) }}"><div class="input-group-append"><span class="input-group-text">kali</span></div></div></td>
                      <td><div class="input-group input-group-sm">
                        <input type="number" name="WA_SUPPRESS_DAYS_STEP{{ $step }}" class="form-control" min="1" max="365" value="{{ old('WA_SUPPRESS_DAYS_STEP' . $step, $suppress['WA_SUPPRESS_DAYS_STEP' . $step]) }}"><div class="input-group-append"><span class="input-group-text">hari</span></div></div></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>

              <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Simpan Pengaturan</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    {{-- ===================== Daftar nomor tersuppress ===================== --}}
    <div class="card card-outline card-secondary">
      <div class="card-header d-flex align-items-center">
        <h3 class="card-title mr-auto"><i class="fas fa-list mr-1"></i> Nomor yang sedang dihentikan ({{ $suppressed->count() }})</h3>
        @if ($gatewayErrorCount > 0)
          <form method="POST" action="{{ url('wa/settings/release') }}" class="ml-2"
                onsubmit="return confirm('Lepas {{ $gatewayErrorCount }} nomor yang dihentikan karena gangguan gateway?');">
            @csrf
            <input type="hidden" name="scope" value="gateway">
            <button class="btn btn-sm btn-outline-primary"><i class="fas fa-unlock mr-1"></i> Lepas semua akibat gangguan gateway ({{ $gatewayErrorCount }})</button>
          </form>
        @endif
      </div>
      <div class="card-body p-0">
        @if ($suppressed->isEmpty())
          <p class="p-3 mb-0 text-muted">Tidak ada nomor yang sedang dihentikan.</p>
        @else
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead>
                <tr><th>Nomor</th><th>Pelanggan</th><th>Gagal</th><th>Dihentikan sampai</th><th>Error terakhir</th><th></th></tr>
              </thead>
              <tbody>
                @foreach ($suppressed as $row)
                  <tr>
                    <td class="text-nowrap">{{ $row->number }}</td>
                    <td>{{ $row->customer ? $row->customer->customer_id . ' | ' . $row->customer->name : '-' }}</td>
                    <td>{{ $row->consecutive_failures }}x</td>
                    <td class="text-nowrap">{{ \Carbon\Carbon::parse($row->suppress_until)->format('d/m/Y H:i') }}</td>
                    <td>
                      @if ($row->gateway_error)<span class="badge badge-info">gangguan gateway</span>@endif
                      <small class="text-muted">{{ \Illuminate\Support\Str::limit($row->last_error, 80) }}</small>
                    </td>
                    <td class="text-right">
                      <form method="POST" action="{{ url('wa/settings/release') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="scope" value="number">
                        <input type="hidden" name="number" value="{{ $row->number }}">
                        <button class="btn btn-xs btn-outline-success" title="Lepas"><i class="fas fa-unlock"></i> Lepas</button>
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
</section>

<script>
  (function () {
    var form = document.getElementById('speedForm');
    var custom = document.getElementById('customFields');
    var inputs = form.querySelectorAll('.speed-input');

    function val(name) { return parseInt(form.querySelector('[name="' + name + '"]').value, 10) || 0; }

    // Sama dengan SendsCustomerNotification::messageDelay(): rata-rata jeda acak + perlambatan tiap 10 pesan
    // (rata-rata 17,5 dtk) + istirahat panjang (rata-rata 1,25 x extra) tiap N pesan.
    function estimate() {
      var min = val('NOTIF_DELAY_MIN'), max = Math.max(min, val('NOTIF_DELAY_MAX'));
      var every = Math.max(1, val('NOTIF_LONG_PAUSE_EVERY')), extra = val('NOTIF_LONG_PAUSE_EXTRA');
      var perMsg = Math.max(min * 0.8, (min + max) / 2) + 1.75;
      var total = 1000 * perMsg + Math.floor(1000 / every) * 1.25 * extra;
      var h = Math.floor(total / 3600), m = Math.round((total % 3600) / 60);
      document.getElementById('speedEstimate').textContent = '± ' + (h ? h + ' jam ' : '') + m + ' menit';
    }

    function applyPreset() {
      var checked = form.querySelector('input[name="preset"]:checked');
      var isCustom = !checked || checked.value === 'custom';
      if (!isCustom) {
        form.querySelector('[name="NOTIF_DELAY_MIN"]').value = checked.dataset.min;
        form.querySelector('[name="NOTIF_DELAY_MAX"]').value = checked.dataset.max;
        form.querySelector('[name="NOTIF_LONG_PAUSE_EVERY"]').value = checked.dataset.every;
        form.querySelector('[name="NOTIF_LONG_PAUSE_EXTRA"]').value = checked.dataset.extra;
      }
      inputs.forEach(function (i) { i.readOnly = !isCustom; });
      custom.style.opacity = isCustom ? 1 : 0.6;
      estimate();
    }

    form.querySelectorAll('input[name="preset"]').forEach(function (r) { r.addEventListener('change', applyPreset); });
    inputs.forEach(function (i) { i.addEventListener('input', estimate); });
    applyPreset();
  })();
</script>
@endsection
