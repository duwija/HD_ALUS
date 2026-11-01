<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Layanan Terisolir - {{ $companyName }}</title>
  {{-- Sengaja tanpa CDN: halaman ini dibuka dari jaringan isolir yang aksesnya dibatasi. --}}
  <style>
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;
      font-family:-apple-system,"Segoe UI",Roboto,Arial,sans-serif;background:#f3f5f9;color:#1f2937}
    .card{width:100%;max-width:420px;background:#fff;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.12);overflow:hidden}
    .head{background:#dc2626;color:#fff;text-align:center;padding:24px 20px}
    .head img{max-height:48px;max-width:180px;background:#fff;border-radius:8px;padding:4px 8px;margin-bottom:12px}
    .head h1{margin:0;font-size:1.25rem}
    .head p{margin:6px 0 0;font-size:.9rem;opacity:.92}
    .body{padding:22px 20px}
    .info{font-size:.92rem;line-height:1.5;margin:0 0 18px}
    label{display:block;font-size:.85rem;font-weight:600;margin:0 0 6px}
    input{width:100%;padding:11px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:1rem;margin-bottom:14px}
    input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
    .btn{display:block;width:100%;text-align:center;padding:12px;border:0;border-radius:8px;font-size:1rem;font-weight:600;
      cursor:pointer;text-decoration:none}
    .btn-primary{background:#2563eb;color:#fff}
    .btn-wa{background:#16a34a;color:#fff;margin-top:10px}
    .btn-link{background:none;color:#2563eb;margin-top:6px;font-size:.9rem;font-weight:500}
    .err{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:8px;padding:10px 12px;font-size:.88rem;margin-bottom:14px}
    .note{font-size:.8rem;color:#6b7280;margin:18px 0 0;line-height:1.45}
    .foot{text-align:center;font-size:.75rem;color:#9ca3af;padding:0 20px 18px}
  </style>
</head>
<body>
  <div class="card">
    <div class="head">
      <img src="{{ tenant_img('logoinv.png', 'dashboard/dist/img/logoinv.png') }}" alt="{{ $companyName }}" onerror="this.style.display='none'">
      <h1>Layanan Internet Terisolir</h1>
      <p>Mohon maaf, akses internet Anda sedang dibatasi.</p>
    </div>
    <div class="body">
      <p class="info">
        Layanan Anda diisolir karena terdapat <strong>tagihan yang belum dibayar</strong>.
        Silakan cek dan lunasi tagihan Anda. Setelah pembayaran diterima, internet akan aktif kembali secara otomatis.
      </p>

      @if ($errors->any())
        <div class="err">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ url('isolir/cek') }}" autocomplete="off">
        @csrf
        <label for="customer_id">ID Pelanggan</label>
        <input type="text" id="customer_id" name="customer_id" value="{{ old('customer_id') }}" required maxlength="50"
               placeholder="Contoh: 2401001" inputmode="text">

        <label for="phone_last4">4 digit terakhir nomor HP terdaftar</label>
        <input type="password" id="phone_last4" name="phone_last4" required maxlength="4" pattern="[0-9]{4}"
               inputmode="numeric" placeholder="••••">

        <button type="submit" class="btn btn-primary">Lihat &amp; Bayar Tagihan</button>
      </form>

      @if ($waNumber)
        <a class="btn btn-wa" href="https://wa.me/{{ $waNumber }}?text={{ rawurlencode('Halo, layanan internet saya terisolir. Mohon info tagihan saya.') }}">
          Hubungi CS via WhatsApp
        </a>
      @endif
      <a class="btn btn-link" href="{{ url('tagihan/login') }}">Masuk Portal Pelanggan</a>

      <p class="note">
        Sudah membayar tetapi internet belum aktif? Tunggu beberapa menit, lalu matikan dan nyalakan kembali modem/router Anda.
      </p>
    </div>
    <div class="foot">&copy; {{ date('Y') }} {{ $companyName }}</div>
  </div>
</body>
</html>
