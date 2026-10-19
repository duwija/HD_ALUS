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

    <div class="row">
      <div class="col-lg-6">
        <div class="card card-outline card-teal">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i> Invoice Otomatis</h3>
          </div>
          <div class="card-body">
            <p class="mb-2">
              Cadangan kalau lupa membuat invoice lewat menu <em>Jobs</em>. Pada tanggal yang dipilih, jam <strong>06:00</strong>,
              sistem membuat invoice bulanan untuk semua pelanggan <strong>Aktif</strong> dan <strong>Blokir</strong>
              (sama seperti tombol Buat Invoice di menu Jobs, tanpa filter merchant). Tanggal invoice selalu tanggal 1 bulan tagihan.
            </p>
            <p class="mb-3 text-muted"><small>
              Aman walau invoice sudah dibuat manual: pelanggan yang sudah punya invoice bulanan untuk periode itu dilewati.
              Notifikasi tagihan ikut terkirim ke pelanggan seperti biasa.
            </small></p>

            <form method="POST" action="{{ url('settings/auto-isolir/auto-invoice') }}">
              @csrf
              <div class="form-row">
                <div class="form-group col-sm-6">
                  <label for="auto_invoice_day">Tanggal eksekusi</label>
                  <select name="auto_invoice_day" id="auto_invoice_day" class="form-control">
                    <option value="" {{ $autoInvoiceDay === null ? 'selected' : '' }}>Mati</option>
                    @for ($d = 1; $d <= 28; $d++)
                      <option value="{{ $d }}" {{ $autoInvoiceDay === $d ? 'selected' : '' }}>Tanggal {{ $d }}</option>
                    @endfor
                    <option value="last" {{ $autoInvoiceDay === 'last' ? 'selected' : '' }}>Akhir bulan</option>
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
              <button type="submit" class="btn btn-info">
                <i class="fas fa-save mr-1"></i> Simpan Invoice Otomatis
              </button>
              <span class="ml-2">
                Status:
                @if ($autoInvoiceDay === null)
                  <span class="badge badge-secondary">Mati</span>
                @else
                  <span class="badge badge-info">
                    {{ \App\Http\Controllers\AutoIsolirSettingController::autoInvoiceDayLabel($autoInvoiceDay) }},
                    {{ $autoInvoicePeriod === 'next' ? 'bulan berikutnya' : 'bulan berjalan' }}
                  </span>
                @endif
              </span>
              @if ($autoInvoiceLast)
                <p class="mt-2 mb-0 text-muted"><small>Terakhir dijalankan untuk periode
                  <strong>{{ \Carbon\Carbon::createFromFormat('Y-m-d', $autoInvoiceLast . '-01')->translatedFormat('F Y') }}</strong>.</small></p>
              @endif
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
</section>

<script>
  document.getElementById('autoIsolirForm').addEventListener('submit', function (e) {
    var msg = {{ $enabled ? 'true' : 'false' }}
      ? 'Nonaktifkan auto isolir? Pelanggan yang menunggak tidak akan diisolir otomatis sampai diaktifkan kembali.'
      : 'Aktifkan auto isolir? Mulai jam 01:00 berikutnya pelanggan yang memenuhi kriteria akan diisolir otomatis.';
    if (!confirm(msg)) e.preventDefault();
  });
</script>
@endsection
