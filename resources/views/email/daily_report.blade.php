@php
  $s = $report['summary'];
  $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Laporan Harian</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f5f7; margin: 0; padding: 20px; color: #222;">
  <div style="max-width: 640px; margin: auto; background: #fff; border-radius: 8px; padding: 24px;">
    <h2 style="margin: 0 0 4px;">Laporan Harian {{ $tenantName }}</h2>
    <p style="margin: 0 0 20px; color: #666;">{{ $report['date']->translatedFormat('l, d F Y') }}</p>

    <h3 style="margin: 0 0 8px; font-size: 16px; border-bottom: 2px solid #e5e7eb; padding-bottom: 4px;">Transaksi</h3>
    <table width="100%" cellpadding="6" style="border-collapse: collapse; margin-bottom: 16px; font-size: 14px;">
      <tr><td>Pembayaran diterima</td><td align="right"><strong>{{ number_format($s['payments_count'], 0, ',', '.') }}</strong></td><td align="right"><strong>{{ $rp($s['payments_total']) }}</strong></td></tr>
      <tr style="background:#fafafa;"><td>Invoice diterbitkan</td><td align="right">{{ number_format($s['issued_count'], 0, ',', '.') }}</td><td align="right">{{ $rp($s['issued_total']) }}</td></tr>
      <tr><td>Invoice dibatalkan</td><td align="right">{{ number_format($s['cancelled_count'], 0, ',', '.') }}</td><td align="right">{{ $rp($s['cancelled_total']) }}</td></tr>
      <tr style="background:#fafafa;"><td>Tagihan belum lunas <small style="color:#888;">(saat ini)</small></td><td align="right">{{ number_format($s['unpaid_count'], 0, ',', '.') }}</td><td align="right">{{ $rp($s['unpaid_total']) }}</td></tr>
    </table>

    @if ($report['payments_by_account']->isNotEmpty())
      <h4 style="margin: 0 0 6px; font-size: 14px;">Pembayaran per akun</h4>
      <table width="100%" cellpadding="5" style="border-collapse: collapse; margin-bottom: 12px; font-size: 13px;">
        @foreach ($report['payments_by_account'] as $a)
          <tr style="border-bottom: 1px solid #eee;"><td>{{ $a['name'] }}</td><td align="right">{{ $a['count'] }}x</td><td align="right">{{ $rp($a['total']) }}</td></tr>
        @endforeach
      </table>
      <h4 style="margin: 0 0 6px; font-size: 14px;">Pembayaran per metode</h4>
      <table width="100%" cellpadding="5" style="border-collapse: collapse; margin-bottom: 16px; font-size: 13px;">
        @foreach ($report['payments_by_method'] as $a)
          <tr style="border-bottom: 1px solid #eee;"><td>{{ $a['name'] }}</td><td align="right">{{ $a['count'] }}x</td><td align="right">{{ $rp($a['total']) }}</td></tr>
        @endforeach
      </table>
    @endif

    <h3 style="margin: 8px 0 8px; font-size: 16px; border-bottom: 2px solid #e5e7eb; padding-bottom: 4px;">Pelanggan</h3>
    <table width="100%" cellpadding="6" style="border-collapse: collapse; margin-bottom: 16px; font-size: 14px;">
      <tr><td>Langganan baru</td><td align="right"><strong>{{ $s['new_subs'] }}</strong></td></tr>
      <tr style="background:#fafafa;"><td>Terminate</td><td align="right"><strong>{{ $s['terminated'] }}</strong></td></tr>
      <tr><td>Diisolir / blokir</td><td align="right"><strong>{{ $s['isolated'] }}</strong></td></tr>
      <tr style="background:#fafafa;"><td>Calon pelanggan (potensial) baru</td><td align="right"><strong>{{ $s['new_leads'] }}</strong></td></tr>
      <tr><td colspan="2" style="padding-top: 10px; color: #666; font-size: 13px;">
        Saat ini: {{ number_format($s['active_now'], 0, ',', '.') }} aktif ·
        {{ number_format($s['blocked_now'], 0, ',', '.') }} blokir ·
        {{ number_format($s['potential_now'], 0, ',', '.') }} potensial ·
        {{ number_format($s['inactive_now'], 0, ',', '.') }} tidak aktif
      </td></tr>
    </table>

    <p style="font-size: 13px; color: #555; margin: 0;">
      Daftar lengkap (pembayaran, invoice terbit/batal, pelanggan baru/terminate/isolir) ada di lampiran Excel.
    </p>
    <p style="font-size: 12px; color: #999; margin: 16px 0 0;">
      Email otomatis dari sistem billing. Penerima dapat diubah di menu Admin &rsaquo; Settings.
    </p>
  </div>
</body>
</html>
