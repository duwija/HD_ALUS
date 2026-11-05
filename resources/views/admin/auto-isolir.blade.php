@extends('layout.main')
@section('title', 'Settings')

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <h1><i class="fas fa-sliders-h mr-2"></i>Settings</h1>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
      <li class="nav-item"><a class="nav-link active" id="tab-isolir-link" data-toggle="tab" href="#tab-isolir" role="tab" aria-controls="tab-isolir" aria-selected="true"><i class="fas fa-user-lock mr-1"></i> Isolir</a></li>
      <li class="nav-item"><a class="nav-link" id="tab-invoice-link" data-toggle="tab" href="#tab-invoice" role="tab" aria-controls="tab-invoice" aria-selected="false"><i class="fas fa-file-invoice-dollar mr-1"></i> Invoice</a></li>
      <li class="nav-item"><a class="nav-link" id="tab-notifikasi-link" data-toggle="tab" href="#tab-notifikasi" role="tab" aria-controls="tab-notifikasi" aria-selected="false"><i class="fas fa-bell mr-1"></i> Notifikasi &amp; Laporan</a></li>
      <li class="nav-item"><a class="nav-link" id="tab-pembayaran-link" data-toggle="tab" href="#tab-pembayaran" role="tab" aria-controls="tab-pembayaran" aria-selected="false"><i class="fas fa-wallet mr-1"></i> Pembayaran</a></li>
    </ul>
    <div class="tab-content" id="settingsTabsContent">
      <div class="tab-pane fade show active" id="tab-isolir" role="tabpanel" aria-labelledby="tab-isolir-link">
      <div class="row">
      <div class="col-lg-6">
        <div class="card card-outline {{ $enabled ? 'card-success' : 'card-secondary' }}">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-lock mr-1"></i> Auto Isolir</h3>
          </div>
          <div class="card-body">
            <p class="mb-3">
              Auto isolir saat ini
              @if ($enabled)
                <span class="badge badge-success" style="font-size: 14px;">AKTIF</span>
              @else
                <span class="badge badge-secondary" style="font-size: 14px;">NONAKTIF</span>
              @endif
              @unless ($isSet)
                <br><small class="text-muted">Belum pernah diatur — sistem menganggapnya aktif.</small>
              @endunless
            </p>

            <form method="POST" action="{{ url('settings/auto-isolir') }}" id="autoIsolirForm">
              @csrf
              <input type="hidden" name="auto_isolir" value="{{ $enabled ? 0 : 1 }}">
              @if ($enabled)
                <button type="submit" class="btn btn-outline-danger">
                  <i class="fas fa-power-off mr-1"></i> Nonaktifkan Auto Isolir
                </button>
              @else
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-power-off mr-1"></i> Aktifkan Auto Isolir
                </button>
              @endif
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card card-outline card-info">
          <div class="card-header">
            <h3 class="card-title">Cara kerja auto isolir</h3>
          </div>
          <div class="card-body">
            <ul class="pl-3 mb-3">
              <li>Berjalan otomatis setiap hari jam <strong>01:00</strong>.</li>
              <li>Yang diisolir: pelanggan <strong>aktif</strong> yang <em>tanggal isolir</em>-nya sama dengan tanggal hari itu dan masih punya <strong>invoice belum lunas</strong>.</li>
              <li>Pengaturan ini sama dengan yang terlihat oleh super admin. Setiap perubahan dicatat di log <em>isolir</em> (menu Admin &rsaquo; Logs).</li>
            </ul>
            <p class="mb-0">
              Hari ini tanggal <strong>{{ $today }}</strong>:
              <strong>{{ number_format($eligibleToday, 0, ',', '.') }}</strong> pelanggan memenuhi kriteria isolir.
            </p>
          </div>
        </div>
      </div>
      </div>
      <div class="row">
      <div class="col-lg-6">
        <div class="card card-outline card-danger">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-network-wired mr-1"></i> Metode Isolir MikroTik</h3>
          </div>
          <div class="card-body">
            <p class="mb-3">Cara memblokir PPPoE pelanggan saat status menjadi <strong>Blocked</strong> (auto isolir maupun manual).</p>

            <form method="POST" action="{{ url('settings/auto-isolir/isolir-method') }}">
              @csrf
              <div class="custom-control custom-radio mb-2">
                <input type="radio" class="custom-control-input" id="isolir_method_disable" name="isolir_method" value="disable"
                       {{ old('isolir_method', $isolirMethod) === 'disable' ? 'checked' : '' }}>
                <label class="custom-control-label" for="isolir_method_disable">
                  <strong>Disable secret PPPoE</strong>
                  <small class="text-muted d-block">Pelanggan tidak bisa login sama sekali. Saat aktif kembali, secret di-enable.</small>
                </label>
              </div>
              <div class="custom-control custom-radio mb-2">
                <input type="radio" class="custom-control-input" id="isolir_method_profile" name="isolir_method" value="profile"
                       {{ old('isolir_method', $isolirMethod) === 'profile' ? 'checked' : '' }}>
                <label class="custom-control-label" for="isolir_method_profile">
                  <strong>Ganti profile ke profile isolir</strong>
                  <small class="text-muted d-block">Pelanggan tetap login dengan profile isolir. Saat aktif kembali, profile dikembalikan ke profile paket (nama plan).</small>
                </label>
              </div>

              <div class="form-group mt-3">
                <label for="isolir_profile">Nama profile isolir</label>
                <input type="text" name="isolir_profile" id="isolir_profile" maxlength="50"
                       class="form-control @error('isolir_profile') is-invalid @enderror"
                       value="{{ old('isolir_profile', $isolirProfile) }}" placeholder="ISOLIR">
                @error('isolir_profile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">
                  Jika belum ada di router, dibuat otomatis: pool <code>ISOLIR</code> (172.20.0.0/16), local address 172.20.0.1,
                  address-list <code>isolir</code>, rate-limit 64k/64k. Jangan ganti nama ini selama masih ada pelanggan yang terisolir.
                </small>
              </div>

              <div class="alert alert-warning py-2 small">
                <i class="fas fa-exclamation-triangle"></i>
                Metode profile <strong>membutuhkan aturan firewall</strong> untuk address-list <code>isolir</code> di setiap router.
                Tanpa itu pelanggan isolir tetap bisa internet (hanya dibatasi 64k).
                Pelanggan <strong>Inactive</strong> tetap di-disable apa pun metodenya.
              </div>

              <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan Metode</button>
              <a href="{{ url('settings/auto-isolir/mikrotik-script') }}" class="btn btn-outline-danger ml-1">
                <i class="fas fa-terminal mr-1"></i> Script Firewall &amp; Halaman Isolir
              </a>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card card-outline card-warning">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bell mr-1"></i> Pengingat sebelum isolir</h3>
          </div>
          <div class="card-body">
            <p class="mb-2">
              Kirim pengingat tagihan ke pelanggan yang belum bayar, beberapa hari sebelum <em>tanggal isolir</em>-nya.
              Dikirim setiap hari jam <strong>09:00</strong> sesuai pilihan notifikasi pelanggan (WA / Email / Aplikasi),
              memakai template pengingat tagihan yang sudah ada.
            </p>
            <p class="mb-3 text-muted"><small>Tetap berjalan walaupun auto isolir dinonaktifkan. Kosongkan semua centang untuk mematikan.</small></p>

            <form method="POST" action="{{ url('settings/auto-isolir/reminder') }}">
              @csrf
              <div class="mb-3">
                @foreach ($reminderOptions as $d)
                  <div class="custom-control custom-checkbox custom-control-inline">
                    <input type="checkbox" class="custom-control-input" id="reminder_{{ $d }}"
                           name="reminder_days[]" value="{{ $d }}" {{ in_array($d, $reminderDays) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="reminder_{{ $d }}">H-{{ $d }}</label>
                  </div>
                @endforeach
              </div>
              <button type="submit" class="btn btn-warning">
                <i class="fas fa-save mr-1"></i> Simpan Pengingat
              </button>
              <span class="ml-2">
                Aktif:
                @if (empty($reminderDays))
                  <span class="badge badge-secondary">Mati</span>
                @else
                  @foreach ($reminderDays as $d)
                    <span class="badge badge-warning">H-{{ $d }}</span>
                  @endforeach
                @endif
              </span>
            </form>
          </div>
        </div>
      </div>
      </div>
      </div>
      <div class="tab-pane fade" id="tab-invoice" role="tabpanel" aria-labelledby="tab-invoice-link">
      <div class="row">
      <div class="col-lg-6">
        <div class="card card-outline card-teal">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i> Invoice Otomatis</h3>
          </div>
          <div class="card-body">
            <p class="mb-2">
              Cadangan kalau lupa membuat invoice lewat menu <em>Jobs</em>. Dijalankan setiap hari jam <strong>06:00</strong>
              untuk pelanggan <strong>Aktif</strong> dan <strong>Blokir</strong> (sama seperti tombol Buat Invoice di menu Jobs, tanpa filter merchant).
              Tanggal invoice selalu tanggal 1 bulan tagihan.
            </p>
            <p class="mb-3 text-muted"><small>
              Aman walau invoice sudah dibuat manual: pelanggan yang sudah punya invoice bulanan untuk periode itu dilewati.
              Notifikasi tagihan ikut terkirim ke pelanggan seperti biasa.
            </small></p>

            @php
              $aiChoice = old('auto_invoice_mode', $autoInvoiceMode === 'isolir'
                  ? ($autoInvoiceBeforeIsolir ? 'isolir' : 'off')
                  : ($autoInvoiceDay === null ? 'off' : 'date'));
            @endphp
            <form method="POST" action="{{ url('settings/auto-isolir/auto-invoice') }}" id="autoInvoiceForm">
              @csrf
              <div class="form-group">
                <label for="auto_invoice_mode">Jadwal</label>
                <select name="auto_invoice_mode" id="auto_invoice_mode" class="form-control">
                  <option value="off" {{ $aiChoice === 'off' ? 'selected' : '' }}>Mati</option>
                  <option value="date" {{ $aiChoice === 'date' ? 'selected' : '' }}>Tanggal tetap — semua pelanggan sekaligus</option>
                  <option value="isolir" {{ $aiChoice === 'isolir' ? 'selected' : '' }}>H-x sebelum tanggal isolir — per pelanggan</option>
                </select>
              </div>

              <div class="form-row ai-mode ai-mode-date">
                <div class="form-group col-sm-6">
                  <label for="auto_invoice_day">Tanggal eksekusi</label>
                  <select name="auto_invoice_day" id="auto_invoice_day" class="form-control @error('auto_invoice_day') is-invalid @enderror">
                    @for ($d = 1; $d <= 28; $d++)
                      <option value="{{ $d }}" {{ (string) old('auto_invoice_day', $autoInvoiceDay ?? 1) === (string) $d ? 'selected' : '' }}>Tanggal {{ $d }}</option>
                    @endfor
                    <option value="last" {{ old('auto_invoice_day', $autoInvoiceDay) === 'last' ? 'selected' : '' }}>Akhir bulan</option>
                  </select>
                </div>
                <div class="form-group col-sm-6">
                  <label for="auto_invoice_period">Periode invoice</label>
                  <select name="auto_invoice_period" id="auto_invoice_period" class="form-control">
                    <option value="current" {{ $autoInvoicePeriod === 'current' ? 'selected' : '' }}>Bulan berjalan</option>
                    <option value="next" {{ $autoInvoicePeriod === 'next' ? 'selected' : '' }}>Bulan berikutnya</option>
                  </select>
                </div>
              </div>

              <div class="ai-mode ai-mode-isolir">
                <div class="form-group">
                  <label for="auto_invoice_before_isolir">Terbitkan invoice</label>
                  <select name="auto_invoice_before_isolir" id="auto_invoice_before_isolir" class="form-control @error('auto_invoice_before_isolir') is-invalid @enderror">
                    @for ($d = 1; $d <= 28; $d++)
                      <option value="{{ $d }}" {{ (int) old('auto_invoice_before_isolir', $autoInvoiceBeforeIsolir ?? 7) === $d ? 'selected' : '' }}>H-{{ $d }} sebelum tanggal isolir</option>
                    @endfor
                  </select>
                  @error('auto_invoice_before_isolir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="alert alert-light border small mb-3">
                  Tiap pelanggan dihitung dari <strong>tanggal isolir</strong>-nya sendiri. Contoh H-7: pelanggan isolir tanggal 20 dibuatkan
                  invoice tanggal 13 untuk periode bulan itu (batas bayar tanggal 19); pelanggan isolir tanggal 5 dibuatkan invoice
                  tanggal 26–28 bulan sebelumnya untuk periode bulan isolirnya.
                  Kalau suatu hari terlewat, pelanggan yang isolirnya ≤ H-x dan belum punya invoice ikut dibuatkan keesokan harinya.
                  Pelanggan dengan tanggal isolir 0 / kosong dilewati.
                </div>
              </div>

              <button type="submit" class="btn btn-info">
                <i class="fas fa-save mr-1"></i> Simpan Invoice Otomatis
              </button>
              <span class="ml-2">
                Status:
                @if ($autoInvoiceSummary === 'mati')
                  <span class="badge badge-secondary">Mati</span>
                @else
                  <span class="badge badge-info" style="white-space:normal">{{ $autoInvoiceSummary }}</span>
                @endif
              </span>
              @if ($autoInvoiceLast && $autoInvoiceMode === 'date')
                <p class="mt-2 mb-0 text-muted"><small>Terakhir dijalankan untuk periode
                  <strong>{{ \Carbon\Carbon::createFromFormat('Y-m-d', $autoInvoiceLast . '-01')->translatedFormat('F Y') }}</strong>.</small></p>
              @endif
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card card-outline card-primary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-invoice mr-1"></i> Catatan Invoice</h3>
          </div>
          <div class="card-body">
            <p class="mb-2">
              Teks yang tampil di bagian bawah invoice (cetak, PDF, dot matrix, halaman tagihan pelanggan, dan bukti pembayaran),
              misalnya info rekening pembayaran atau kontak CS. Bisa lebih dari satu baris.
            </p>
            <p class="mb-3 text-muted"><small>Pengaturan ini sama dengan <code>INV_NOTE</code> yang terlihat oleh super admin.</small></p>

            <form method="POST" action="{{ url('settings/auto-isolir/inv-note') }}">
              @csrf
              <div class="form-group">
                <textarea name="inv_note" rows="6" maxlength="{{ $invNoteMax }}" class="form-control @error('inv_note') is-invalid @enderror"
                          placeholder="Contoh: Pembayaran via transfer ke BCA 1234567890 a.n. ...">{{ old('inv_note', $invNote) }}</textarea>
                <small class="text-muted">Maksimal {{ $invNoteMax }} karakter. Kosongkan untuk menghapus catatan.</small>
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Simpan Catatan
              </button>
            </form>
          </div>
        </div>
      </div>
      </div>
      </div>
      <div class="tab-pane fade" id="tab-notifikasi" role="tabpanel" aria-labelledby="tab-notifikasi-link">
      <div class="row">
      <div class="col-lg-6">
        <div class="card card-outline card-success">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-paper-plane mr-1"></i> Notifikasi Pelanggan</h3>
          </div>
          <div class="card-body">
            <p class="mb-3">
              Atur apakah pelanggan otomatis menerima notifikasi (WhatsApp / Email / Mobile App sesuai pilihan <em>Sent Notif</em> di data pelanggan).
            </p>

            <form method="POST" action="{{ url('settings/auto-isolir/notification') }}">
              @csrf
              <div class="form-group mb-3">
                <div class="custom-control custom-switch">
                  <input type="hidden" name="notif_invoice_create" value="0">
                  <input type="checkbox" class="custom-control-input" id="notif_invoice_create"
                         name="notif_invoice_create" value="1" {{ $notifInvoiceCreate ? 'checked' : '' }}>
                  <label class="custom-control-label" for="notif_invoice_create">
                    <strong>Saat invoice dibuat</strong>
                  </label>
                </div>
                <small class="text-muted d-block ml-5">Invoice otomatis (terjadwal / menu Jobs) dan invoice manual.</small>
              </div>

              <div class="form-group mb-3">
                <div class="custom-control custom-switch">
                  <input type="hidden" name="notif_payment" value="0">
                  <input type="checkbox" class="custom-control-input" id="notif_payment"
                         name="notif_payment" value="1" {{ $notifPayment ? 'checked' : '' }}>
                  <label class="custom-control-label" for="notif_payment">
                    <strong>Saat pelunasan</strong>
                  </label>
                </div>
                <small class="text-muted d-block ml-5">Pelunasan manual (kasir/admin) dan pembayaran online (payment gateway).</small>
              </div>

              <p class="text-muted mb-3"><small>
                Notifikasi ke grup Telegram internal dan tombol kirim ulang manual di halaman invoice tidak terpengaruh.
              </small></p>

              <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card card-outline card-purple">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-envelope-open-text mr-1"></i> Laporan Harian</h3>
          </div>
          <div class="card-body">
            <p class="mb-2">
              Setiap hari jam <strong>07:00</strong> sistem mengirim email laporan <strong>hari sebelumnya</strong>:
              pembayaran (per akun &amp; metode), invoice terbit/batal, langganan baru, terminate, isolir, dan calon pelanggan baru.
              Daftar lengkapnya dilampirkan dalam file Excel.
            </p>
            <p class="mb-3 text-muted"><small>Kosongkan daftar email untuk mematikan laporan.</small></p>

            <form method="POST" action="{{ url('settings/auto-isolir/daily-report') }}">
              @csrf
              <div class="form-group">
                <label for="daily_report_emails">Email penerima</label>
                <textarea name="daily_report_emails" id="daily_report_emails" rows="3"
                          class="form-control @error('daily_report_emails') is-invalid @enderror"
                          placeholder="owner@contoh.com, keuangan@contoh.com">{{ old('daily_report_emails', implode(', ', $dailyReportEmails)) }}</textarea>
                @error('daily_report_emails')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Pisahkan dengan koma atau baris baru. Maksimal 10 email.</small>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan Penerima</button>
            </form>

            @if (!empty($dailyReportEmails))
              <form method="POST" action="{{ url('settings/auto-isolir/daily-report/send') }}" class="mt-2"
                    onsubmit="return confirm('Kirim laporan kemarin sekarang ke {{ implode(', ', $dailyReportEmails) }}?');">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fas fa-paper-plane mr-1"></i> Kirim laporan kemarin sekarang</button>
              </form>
            @endif
          </div>
        </div>
      </div>
      </div>
      </div>
      <div class="tab-pane fade" id="tab-pembayaran" role="tabpanel" aria-labelledby="tab-pembayaran-link">
    @if($customPay)
    @php $cps = $customPay->settings_array; @endphp
    <div class="row">
      <div class="col-12">
        <div class="card card-outline {{ $customPay->enabled ? 'card-success' : 'card-secondary' }}">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-pen-to-square mr-1"></i> Metode Bayar Custom</h3>
            <div class="card-tools">
              <span class="badge {{ $customPay->enabled ? 'badge-success' : 'badge-secondary' }}">{{ $customPay->enabled ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
          </div>
          <div class="card-body">
            <p class="mb-2">
              Konten bebas (mis. instruksi transfer manual / QRIS statis) yang tampil saat pelanggan memilih metode
              <strong>Custom</strong> di halaman tagihan. Tidak ada transaksi online yang dibuat.
            </p>
            <p class="mb-3 text-muted"><small>
              Pengaturan ini sama dengan metode <strong>Custom</strong> di konfigurasi Payment Gateway super admin, perubahan dari salah satu sisi langsung terlihat di sisi lain.
            </small></p>

            <form method="POST" action="{{ url('settings/auto-isolir/custom-payment') }}" id="customPaymentForm">
              @csrf
              <div class="form-group">
                <div class="custom-control custom-switch">
                  <input type="hidden" name="custom_enabled" value="0">
                  <input type="checkbox" class="custom-control-input" id="custom_enabled" name="custom_enabled" value="1" {{ $customPay->enabled ? 'checked' : '' }}>
                  <label class="custom-control-label" for="custom_enabled"><strong>Tampilkan di halaman tagihan pelanggan</strong></label>
                </div>
              </div>
              <div class="form-group">
                <label for="custom_title" class="mb-1">Judul Halaman</label>
                <input type="text" id="custom_title" name="custom_title" maxlength="150" class="form-control @error('custom_title') is-invalid @enderror"
                       value="{{ old('custom_title', $cps['custom_title'] ?? 'Instruksi Pembayaran') }}" placeholder="Instruksi Pembayaran">
              </div>
              <div class="form-group">
                <label class="mb-1">Tampilan (HTML)</label>
                <textarea id="customPayHtmlEditor" name="custom_html">{{ old('custom_html', $cps['custom_html'] ?? '') }}</textarea>
                <small class="text-muted d-block mt-1">
                  Tombol <strong>&lt;/&gt;</strong> di toolbar untuk mengedit kode HTML langsung.
                  Placeholder: <code>{customer_id}</code>, <code>{customer_name}</code>, <code>{total}</code> (total tagihan yang dipilih).
                  Demi keamanan, tag <code>&lt;script&gt;</code>/<code>&lt;iframe&gt;</code> dan atribut <code>on…=</code> dibuang saat disimpan.
                </small>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    @endif
      </div>
    </div>
  </div>
</section>

<script>
  // Tab Settings: ingat tab terakhir (setelah simpan, halaman redirect kembali ke tab yang sama).
  // Kalau ada error validasi, buka tab yang berisi field error.
  $(function () {
    var KEY = 'settingsActiveTab';
    var $links = $('#settingsTabs a[data-toggle="tab"]');
    var target = window.location.hash && $links.filter('[href="' + window.location.hash + '"]').length ? window.location.hash : null;
    var $err = $('#settingsTabsContent .is-invalid').first().closest('.tab-pane');
    if ($err.length) target = '#' + $err.attr('id');
    if (!target) { try { target = sessionStorage.getItem(KEY); } catch (e) {} }
    if (target && $links.filter('[href="' + target + '"]').length) $links.filter('[href="' + target + '"]').tab('show');
    $links.on('shown.bs.tab', function (e) {
      var h = $(e.target).attr('href');
      try { sessionStorage.setItem(KEY, h); } catch (err) {}
      if (history.replaceState) history.replaceState(null, '', h);
    });
  });
</script>

<script>
  (function () {
    var sel = document.getElementById('auto_invoice_mode');
    if (!sel) return;
    function sync() {
      document.querySelectorAll('#autoInvoiceForm .ai-mode').forEach(function (el) {
        var on = el.classList.contains('ai-mode-' + sel.value);
        el.style.display = on ? '' : 'none';
        el.querySelectorAll('select').forEach(function (f) { f.disabled = !on; });
      });
    }
    sel.addEventListener('change', sync);
    sync();
  })();
</script>

<script>
  document.getElementById('autoIsolirForm').addEventListener('submit', function (e) {
    var msg = {{ $enabled ? 'true' : 'false' }}
      ? 'Nonaktifkan auto isolir? Pelanggan yang menunggak tidak akan diisolir otomatis sampai diaktifkan kembali.'
      : 'Aktifkan auto isolir? Mulai jam 01:00 berikutnya pelanggan yang memenuhi kriteria akan diisolir otomatis.';
    if (!confirm(msg)) e.preventDefault();
  });
</script>
@endsection

@push('summernote-script')
<script src="{{ url('dashboard/plugins/summernote/summernote-bs4.min.js') }}"></script>
<script>
$(function () {
  var $editor = $('#customPayHtmlEditor');
  if (!$editor.length || !$.fn.summernote) return;
  $editor.summernote({
    height: 280,
    dialogsInBody: true,
    toolbar: [
      ['style', ['style']],
      ['font', ['bold', 'italic', 'underline', 'clear']],
      ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['table', ['table']],
      ['insert', ['link', 'picture', 'hr']],
      ['view', ['codeview', 'fullscreen']]
    ]
  });
  // Perubahan di mode kode baru tersinkron ke textarea setelah keluar dari codeview.
  $('#customPaymentForm').on('submit', function () {
    if ($editor.summernote('codeview.isActivated')) $editor.summernote('codeview.deactivate');
    $editor.val($editor.summernote('code'));
  });
});
</script>
@endpush
