<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifikasi Tiket</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
    .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.12); }
    .header { background: #007bff; color: #fff; padding: 24px 30px; }
    .header h2 { margin: 0; font-size: 20px; }
    .header small { opacity: .85; font-size: 13px; }
    .body { padding: 28px 30px; color: #333; }
    .greeting { font-size: 16px; margin-bottom: 16px; }
    .table { width: 100%; border-collapse: collapse; margin: 16px 0; }
    .table td { padding: 9px 12px; border-bottom: 1px solid #eee; font-size: 14px; }
    .table td:first-child { font-weight: bold; color: #555; width: 38%; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
    .badge-warning { background:#fff3cd; color:#856404; }
    .badge-info    { background:#d1ecf1; color:#0c5460; }
    .badge-success { background:#d4edda; color:#155724; }
    .btn-link { display: inline-block; margin-top: 18px; padding: 11px 26px; background: #007bff; color: #fff !important; text-decoration: none; border-radius: 5px; font-size: 14px; }
    .footer { background: #f8f9fa; text-align: center; padding: 14px 20px; font-size: 12px; color: #888; border-top: 1px solid #e9ecef; }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h2>🎫 Notifikasi Tiket</h2>
    <small>{{ config('app.name') }}</small>
  </div>

  <div class="body">
    <p class="greeting">Halo, <strong>{{ $data['employee_name'] }}</strong>,</p>
    <p>Ada tiket yang perlu mendapat perhatian Anda. Berikut detailnya:</p>

    <table class="table">
      <tr><td>No. Tiket</td><td><strong>#{{ $data['ticket_id'] }}</strong></td></tr>
      <tr><td>Judul</td><td>{{ $data['title'] }}</td></tr>
      <tr><td>Pelanggan</td><td>{{ $data['customer_name'] }}</td></tr>
      <tr><td>No. HP Pelanggan</td><td>{{ $data['customer_phone'] }}</td></tr>
      <tr><td>Alamat</td><td>{{ $data['customer_address'] }}</td></tr>
      <tr><td>Status</td><td><span class="badge badge-warning">{{ $data['status'] }}</span></td></tr>
      @if(!empty($data['custom_message']))
      <tr><td>Pesan</td><td>{{ $data['custom_message'] }}</td></tr>
      @endif
    </table>

    @if(!empty($data['maps_url']))
    <p>📍 <a href="{{ $data['maps_url'] }}">Lihat Lokasi di Google Maps</a></p>
    @endif

    <a href="{{ $data['ticket_url'] }}" class="btn-link">🔗 Buka Tiket</a>
  </div>

  <div class="footer">
    Pesan ini dikirim otomatis oleh sistem {{ config('app.name') }}.<br>
    Mohon tidak membalas email ini.
  </div>
</div>
</body>
</html>
