@php
  // Perhitungan sama dengan payment/print: biaya admin sekali per invoice, PPh dijumlahkan dari semua item.
  $taxRate = $suminvoice_number->tax ? $suminvoice_number->tax / 100 : 0;
  $pphRate = $suminvoice_number->pph ? $suminvoice_number->pph / 100 : 0;
  $adminFee = (float) ($admin_fee ?? 0);

  $rows = [];
  $itemsTotal = 0;
  $pphTotal = 0;
  foreach ($invoice as $item) {
      $base = $item->qty * $item->amount;
      $tax = $base * $taxRate;
      $pphTotal += $base * $pphRate;
      $itemsTotal += $base + $tax;

      $description = $item->description;
      if ($item->monthly_fee == 1) {
          $month = (int) substr($item->periode, -6, 2);
          $year = substr($item->periode, -4, 4);
          $description .= ' - ' . date('F', mktime(0, 0, 0, max(1, $month), 10)) . ' ' . $year;
      }

      $rows[] = [
          'description' => $description,
          'qty' => $item->qty,
          'price' => $item->amount + ($item->amount * $taxRate),
          'total' => $base + $tax,
      ];
  }
  $grandTotal = $itemsTotal - $pphTotal + $adminFee;

  $status = (int) $suminvoice_number->payment_status;
  $statusLabel = [1 => ['LUNAS', 'Sudah Terbayar'], 2 => ['DIBATALKAN', 'Invoice Dibatalkan']][$status] ?? ['BELUM TERBAYAR', 'Belum Terbayar'];

  $address = tenant_config('company_address1', env('COMPANY_ADDRESS1'));
  $n = fn ($v) => number_format((float) $v, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Struk #{{ $suminvoice_number->number }}</title>
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
    .no-print button { font: 13px sans-serif; padding: 6px 14px; cursor: pointer; }
    @media print {
      body { background: #fff; }
      .receipt { margin: 0; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>

  <div class="no-print"><button onclick="window.print()">Print Struk</button></div>

  <div class="receipt">
    <div class="center">
      <div class="company">{{ $companyName }}</div>
      @if($address)<div class="small">{{ $address }}</div>@endif
    </div>

    <div class="sep double"></div>
    <div class="center bold">INVOICE</div>
    <div class="center status">{{ $statusLabel[0] }}</div>

    <table class="kv">
      <tr><td>No</td><td class="right">#{{ $suminvoice_number->number }}</td></tr>
      <tr><td>Tanggal</td><td class="right">{{ $suminvoice_number->date }}</td></tr>
      @if($status === 1)
        <tr><td>Dibayar</td><td class="right">{{ \Carbon\Carbon::parse($suminvoice_number->payment_date)->format('Y-m-d H:i') }}</td></tr>
      @elseif($status === 0 && $suminvoice_number->due_date)
        <tr><td>Jatuh tempo</td><td class="right">{{ $suminvoice_number->due_date }}</td></tr>
      @endif
    </table>

    <div class="sep"></div>
    <div class="bold">{{ $customer->name }}</div>
    <div>{{ $customer->customer_id }}</div>
    @if($customer->address)<div class="small">{{ $customer->address }}</div>@endif

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
      @if($adminFee > 0)
        <tr><td>Biaya Admin</td><td class="right">{{ $n($adminFee) }}</td></tr>
      @endif
      <tr class="total"><td>TOTAL</td><td class="right">Rp {{ $n($grandTotal) }}</td></tr>
    </table>
    <div class="small">Harga sudah termasuk PPN</div>

    @if($status === 1)
      <div class="sep"></div>
      <table class="kv">
        <tr><td>Diterima di</td><td class="right">{{ optional($suminvoice_number->kasbank)->name ?? '-' }}</td></tr>
        <tr><td>Kasir</td><td class="right">{{ optional($suminvoice_number->user)->name ?? $suminvoice_number->updated_by }}</td></tr>
      </table>
    @endif

    @if(!empty($invNote))
      <div class="sep"></div>
      <div class="small">{!! nl2br(e($invNote)) !!}</div>
    @endif

    <div class="sep double"></div>
    <div class="center">
      <div class="qr">{!! QrCode::size(100)->margin(0)->generate(url($appUrl . '/suminvoice/' . $suminvoice_number->tempcode . '/viewinvoice')) !!}</div>
      <div class="small">Scan untuk cek invoice</div>
      <div style="margin-top:4px">{!! nl2br(e($signature)) !!}</div>
      <div class="bold">TERIMA KASIH</div>
      <div class="small">Dicetak {{ now()->format('d-m-Y H:i') }}</div>
    </div>
  </div>

  <script>
    window.addEventListener('load', function () { window.print(); });
  </script>
</body>
</html>
