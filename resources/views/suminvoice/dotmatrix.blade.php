@php
  // Perhitungan sama dengan versi sebelumnya: total = item + PPN - PPh; biaya admin hanya untuk invoice lunas.
  $taxRate = (float) ($suminvoice_number->tax ?? 0) / 100;
  $pphRate = (float) ($suminvoice_number->pph ?? 0) / 100;

  $rows = [];
  $itemsTotal = 0;
  $pphTotal = 0;
  foreach ($invoice as $inv) {
      $base = $inv->qty * $inv->amount;
      $tax = $base * $taxRate;
      $pphTotal += $base * $pphRate;
      $itemsTotal += $base + $tax;

      $desc = $inv->description;
      if ($inv->monthly_fee == 1) {
          $desc .= ' - ' . date('F', mktime(0, 0, 0, max(1, (int) substr($inv->periode, -6, 2)), 1)) . ' ' . substr($inv->periode, -4, 4);
      }
      $rows[] = ['description' => $desc, 'qty' => $inv->qty, 'price' => $inv->amount + $inv->amount * $taxRate, 'total' => $base + $tax];
  }
  $total = $itemsTotal - $pphTotal;

  $status = (int) $suminvoice_number->payment_status;
  $merchantFee = ($status === 1 && $suminvoice_number->merchant_fee > 0) ? (float) $suminvoice_number->merchant_fee : 0;
  $statusLabel = [1 => 'LUNAS', 2 => 'DIBATALKAN'][$status] ?? 'BELUM TERBAYAR';

  $paidVia = null;
  if ($status === 1) {
      $pg = $suminvoice_number->payment_gateway ? \App\PaymentGateway::findForCurrentTenant($suminvoice_number->payment_gateway) : null;
      $paidVia = (($pg->settings['invoice_label'] ?? '') ?: null)
          ?? ($suminvoice_number->kasbank->name ?? null)
          ?? ($suminvoice_number->payment_gateway ? strtoupper($suminvoice_number->payment_gateway) : null);
  }

  $n = fn ($v) => number_format((float) $v, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Struk #{{ $suminvoice_number->number ?? '' }}</title>
  <style>
    /* Struk printer thermal 58 mm (area cetak ±48 mm) */
    @page { size: 58mm auto; margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #eee; color: #000; font: 11px/1.35 "Courier New", Courier, monospace; }
    .receipt { width: 58mm; margin: 10px auto; padding: 3mm 4mm 5mm; background: #fff; }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: 700; }
    .company { font-size: 14px; font-weight: 700; text-transform: uppercase; }
    .small { font-size: 9.5px; }
    .sep { border-top: 1px dashed #000; margin: 5px 0; }
    .sep.double { border-top: 3px double #000; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 1px 0; vertical-align: top; }
    .kv td:first-child { white-space: nowrap; padding-right: 4px; }
    .item-name { padding-top: 3px; }
    .status { margin: 4px 0; padding: 3px 0; border: 1px solid #000; font-weight: 700; font-size: 13px; letter-spacing: 1px; }
    .total td { font-size: 13px; font-weight: 700; padding-top: 3px; }
    .qr svg { width: 26mm; height: 26mm; }
    .no-print { text-align: center; margin: 10px; }
    .no-print button { font: 13px sans-serif; padding: 6px 20px; cursor: pointer; }
    @media print {
      body { background: #fff; }
      .receipt { margin: 0; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

  <div class="receipt">
    <div class="center">
      <div class="company">{{ $companyName }}</div>
      @if($companyAddress1)<div class="small">{{ trim($companyAddress1) }}</div>@endif
      @if($companyAddress2)<div class="small">{{ trim($companyAddress2) }}</div>@endif
    </div>

    <div class="sep double"></div>
    <div class="center bold">INVOICE</div>
    <div class="center status">{{ $statusLabel }}</div>

    <table class="kv">
      <tr><td>No</td><td class="right">#{{ $suminvoice_number->number }}</td></tr>
      <tr><td>Tanggal</td><td class="right">{{ $suminvoice_number->date }}</td></tr>
      @if($status === 1 && $suminvoice_number->payment_date)
        <tr><td>Dibayar</td><td class="right">{{ \Carbon\Carbon::parse($suminvoice_number->payment_date)->format('Y-m-d H:i') }}</td></tr>
      @elseif($status === 0 && $suminvoice_number->due_date)
        <tr><td>Jatuh tempo</td><td class="right">{{ $suminvoice_number->due_date }}</td></tr>
      @endif
    </table>

    <div class="sep"></div>
    <div class="bold">{{ $customer->name ?? '-' }}</div>
    <div>{{ $customer->customer_id ?? '' }}</div>
    @if(!empty($customer->address))<div class="small">{{ $customer->address }}</div>@endif

    <div class="sep"></div>
    @foreach($rows as $row)
      <div class="item-name">{{ $row['description'] }}</div>
      <table>
        <tr>
          <td>{{ $row['qty'] }} x {{ $n($row['price']) }}</td>
          <td class="right">{{ $n($row['total']) }}</td>
        </tr>
      </table>
    @endforeach

    <div class="sep"></div>
    <table>
      <tr><td>Subtotal</td><td class="right">{{ $n($itemsTotal) }}</td></tr>
      @if($pphTotal > 0)
        <tr><td>PPh 23</td><td class="right">-{{ $n($pphTotal) }}</td></tr>
      @endif
      @if($merchantFee > 0)
        <tr><td>Total Tagihan</td><td class="right">{{ $n($total) }}</td></tr>
        <tr><td>Biaya Admin</td><td class="right">{{ $n($merchantFee) }}</td></tr>
        <tr class="total"><td>TOTAL DIBAYAR</td><td class="right">Rp {{ $n($total + $merchantFee) }}</td></tr>
      @else
        <tr class="total"><td>TOTAL</td><td class="right">Rp {{ $n($total) }}</td></tr>
      @endif
    </table>
    <div class="small">Harga sudah termasuk PPN</div>

    @if($status === 1)
      <div class="sep"></div>
      <table class="kv">
        @if($paidVia)<tr><td>Dibayar via</td><td class="right">{{ $paidVia }}</td></tr>@endif
        <tr><td>Diterima oleh</td><td class="right">{{ $suminvoice_number->user->name ?? $suminvoice_number->updated_by }}</td></tr>
      </table>
    @endif

    @if($invNote)
      <div class="sep"></div>
      <div class="small">{!! nl2br(e($invNote)) !!}</div>
    @endif

    <div class="sep double"></div>
    <div class="center">
      <div class="qr">{!! QrCode::size(100)->margin(0)->generate(url('/suminvoice/' . $suminvoice_number->tempcode . '/viewinvoice')) !!}</div>
      <div class="small">Scan untuk verifikasi invoice</div>
      @if($signature)<div style="margin-top:4px">{{ $signature }}</div>@endif
      <div class="bold">TERIMA KASIH</div>
      <div class="small">Dicetak {{ now()->format('d-m-Y H:i') }}</div>
    </div>
  </div>

  {{-- Tombol cetak (tidak ikut tercetak) --}}
  <div class="no-print"><button onclick="window.print()">&#128424; Cetak</button></div>

  <script>
    window.addEventListener('load', function () { window.print(); });
  </script>
</body>
</html>
