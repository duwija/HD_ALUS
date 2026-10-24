@php
  // Desain sama dengan halaman print invoice; layout pakai tabel karena dompdf tidak mendukung flexbox.
  // Perhitungan sama dengan halaman print: total = item + PPN - PPh; biaya admin hanya untuk invoice lunas.
  $taxRate = $suminvoice_number->tax ? $suminvoice_number->tax / 100 : 0;
  $pphRate = $suminvoice_number->pph ? $suminvoice_number->pph / 100 : 0;
  $rows = [];
  $itemsTotal = 0;
  $pphTotal = 0;
  foreach ($invoice as $item) {
      $base = $item->qty * $item->amount;
      $tax = $base * $taxRate;
      $pphTotal += $base * $pphRate;
      $itemsTotal += $base + $tax;

      $description = $item->description;
      if ((int) $item->monthly_fee === 1 && !empty($item->periode) && strlen($item->periode) >= 6) {
          $m = substr($item->periode, -6, 2);
          if (is_numeric($m) && (int) $m >= 1 && (int) $m <= 12) {
              $description .= ' - ' . date('F', mktime(0, 0, 0, (int) $m, 10)) . ' ' . substr($item->periode, -4, 4);
          }
      }
      $rows[] = ['description' => $description, 'price' => $item->amount + $item->amount * $taxRate, 'qty' => $item->qty, 'total' => $base + $tax];
  }
  $total = $itemsTotal - $pphTotal;

  $status = (int) $suminvoice_number->payment_status;
  $merchantFee = ($status === 1 && $suminvoice_number->merchant_fee > 0) ? (float) $suminvoice_number->merchant_fee : 0;
  $stamp = [1 => ['LUNAS', 'PAID', '#2e7d32'], 2 => ['DIBATALKAN', 'CANCELLED', '#6b7280']][$status] ?? ['BELUM TERBAYAR', 'UNPAID', '#c62828'];

  $paidVia = null; $paidNote = null;
  if ($status === 1) {
      $pg = $suminvoice_number->payment_gateway ? \App\PaymentGateway::findForCurrentTenant($suminvoice_number->payment_gateway) : null;
      $paidNote = ($pg->settings['invoice_note'] ?? '') ?: null;
      $paidVia = (($pg->settings['invoice_label'] ?? '') ?: null)
          ?? ($suminvoice_number->kasbank->name ?? null)
          ?? ($suminvoice_number->payment_gateway ? strtoupper($suminvoice_number->payment_gateway) : 'Online');
  }

  $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Invoice #{{ $suminvoice_number->number }}</title>
  <style>
    @page { margin: 34px 38px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1f2937; }
    table { border-collapse: collapse; width: 100%; }
    td, th { vertical-align: top; }
    .brand-name { font-size: 15px; font-weight: bold; color: #0d47a1; margin-top: 4px; }
    .muted { color: #6b7280; }
    .small { font-size: 9px; }
    .title { font-size: 22px; font-weight: bold; letter-spacing: 2px; color: #0d47a1; line-height: 1; }
    .subtitle { font-size: 9px; letter-spacing: 3px; color: #6b7280; }
    .head-line { border-bottom: 3px solid #0d47a1; height: 6px; margin-bottom: 12px; }
    .card { background: #f3f6fb; border-radius: 6px; padding: 9px 11px; }
    .card-label { font-size: 8px; font-weight: bold; letter-spacing: 1.3px; color: #6b7280; text-transform: uppercase; margin-bottom: 3px; }
    .card-big { font-size: 13px; font-weight: bold; margin-bottom: 3px; }
    .kv td { padding: 1px 6px 1px 0; }
    .kv td.k { color: #6b7280; white-space: nowrap; width: 82px; }
    .items { margin-top: 12px; }
    .items th { background: #0d47a1; color: #fff; font-size: 8.5px; text-transform: uppercase; letter-spacing: .5px; padding: 6px 8px; text-align: left; }
    .items td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
    .items tr.even td { background: #fafbfc; }
    .num { text-align: right; white-space: nowrap; }
    .center { text-align: center; }
    .summary td { padding: 4px 8px; }
    .summary tr.total td { border-top: 2px solid #1f2937; font-size: 13px; font-weight: bold; padding-top: 6px; }
    .stamp { border: 2.5px solid; border-radius: 6px; padding: 4px 12px; text-align: center; display: inline-block; }
    .stamp b { display: block; font-size: 15px; letter-spacing: 2px; }
    .stamp span { font-size: 8px; letter-spacing: 2px; }
    .warning { margin-top: 14px; border-left: 4px solid #c62828; background: #fdecea; color: #8e1b1b; padding: 8px 10px; }
    .note { margin-top: 14px; padding: 8px 10px; background: #f9fafb; border-left: 4px solid #0d47a1; font-size: 9.5px; }
    .sign { margin-top: 18px; }
    .sign .who { margin-top: 40px; font-weight: bold; border-top: 1px solid #1f2937; padding-top: 3px; }
    .foot { margin-top: 14px; padding-top: 6px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #6b7280; text-align: center; }
  </style>
</head>
<body>

  {{-- Header --}}
  <table>
    <tr>
      <td style="width:60%">
        @if($logoBase64)<img src="data:image/png;base64,{{ $logoBase64 }}" style="max-height:48px;max-width:140px">@endif
        <div class="brand-name">{{ $companyName }}</div>
        @if($companyAddress1)<div class="muted small">{{ $companyAddress1 }}</div>@endif
      </td>
      <td style="text-align:right">
        <table>
          <tr>
            <td style="text-align:right;padding-right:10px">
              <div class="title">INVOICE</div>
              <div class="subtitle">TAGIHAN</div>
            </td>
            <td style="width:70px"><img src="data:image/png;base64,{{ $qrcode }}" style="width:66px;height:66px"></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  <div class="head-line"></div>

  {{-- Ditagihkan kepada & Pembayaran --}}
  <table>
    <tr>
      <td style="width:50%;padding-right:6px">
        <div class="card">
          <div class="card-label">Ditagihkan kepada</div>
          <div class="card-big">{{ $customer->name }}</div>
          <table class="kv">
            <tr><td class="k">ID Pelanggan</td><td><b>{{ $customer->customer_id }}</b></td></tr>
            @if($customer->address)<tr><td class="k">Alamat</td><td>{{ $customer->address }}</td></tr>@endif
            @if($customer->phone)<tr><td class="k">Telepon</td><td>{{ $customer->phone }}</td></tr>@endif
            @if(trim((string) $customer->npwp) !== '' && $customer->npwp !== '0')<tr><td class="k">NPWP</td><td>{{ strtoupper($customer->npwp) }}</td></tr>@endif
          </table>
        </div>
      </td>
      <td style="width:50%;padding-left:6px">
        <div class="card">
          <div class="card-label">Pembayaran</div>
          <table class="kv">
            <tr><td class="k">No. Invoice</td><td><b>#{{ $suminvoice_number->number }}</b></td></tr>
            <tr><td class="k">Tanggal</td><td>{{ $suminvoice_number->date }}</td></tr>
            @if($status === 1)
              <tr><td class="k">Tanggal bayar</td><td>{{ $suminvoice_number->payment_date }}</td></tr>
              <tr><td class="k">Dibayar via</td><td>{{ $paidVia }}@if($paidNote)<br><span class="muted small">{{ $paidNote }}</span>@endif</td></tr>
            @elseif($status === 2)
              <tr><td class="k">Status</td><td><b>Dibatalkan</b></td></tr>
            @else
              <tr><td class="k">Jatuh tempo</td><td><b>{{ $suminvoice_number->due_date ?: '-' }}</b></td></tr>
            @endif
          </table>
        </div>
      </td>
    </tr>
  </table>

  {{-- Item --}}
  <table class="items">
    <thead>
      <tr>
        <th style="width:24px">#</th>
        <th>Deskripsi</th>
        <th class="num" style="width:85px">Harga</th>
        <th class="center" style="width:34px">Qty</th>
        <th class="num" style="width:95px">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $i => $row)
        <tr class="{{ $i % 2 ? 'even' : '' }}">
          <td>{{ $i + 1 }}</td>
          <td>{{ $row['description'] }}</td>
          <td class="num">{{ number_format($row['price'], 0, ',', '.') }}</td>
          <td class="center">{{ $row['qty'] }}</td>
          <td class="num">{{ number_format($row['total'], 0, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Stempel status + ringkasan --}}
  <table style="margin-top:8px">
    <tr>
      <td style="width:45%;padding-top:10px;padding-left:10px">
        <div class="stamp" style="color:{{ $stamp[2] }};border-color:{{ $stamp[2] }}"><b>{{ $stamp[0] }}</b><span>{{ $stamp[1] }}</span></div>
      </td>
      <td>
        <table class="summary">
          <tr><td>Subtotal</td><td class="num">{{ $rp($itemsTotal) }}</td></tr>
          @if($pphTotal > 0)
            <tr><td>PPh 23</td><td class="num">- {{ $rp($pphTotal) }}</td></tr>
          @endif
          <tr class="total"><td>Total Tagihan</td><td class="num">{{ $rp($total) }}</td></tr>
          @if($merchantFee > 0)
            <tr><td>Biaya Admin</td><td class="num">{{ $rp($merchantFee) }}</td></tr>
            <tr class="total"><td>Total Dibayar</td><td class="num">{{ $rp($total + $merchantFee) }}</td></tr>
          @endif
        </table>
      </td>
    </tr>
  </table>

  @if($status === 0 && $current_inv_status == 1)
    <div class="warning">
      Anda masih memiliki tagihan yang belum terbayar (UNPAID) pada periode sebelumnya, silahkan melakukan pelunasan
      pembayaran tagihan tersebut terlebih dahulu. Untuk info lebih lanjut silahkan menghubungi team Payment kami.
    </div>
  @endif

  @if($status === 1 || $status === 2)
    <table class="sign">
      <tr>
        <td style="width:60%"></td>
        <td style="text-align:center">
          {{ $signature }}@if($status === 1), {{ $suminvoice_number->payment_date }}@endif
          <div>Terima kasih,</div>
          @if($status === 1)
            <div class="who">{{ $suminvoice_number->user->name ?? $suminvoice_number->updated_by }}</div>
          @endif
        </td>
      </tr>
    </table>
  @endif

  @if(!empty($invNote))
    <div class="note">{!! nl2br(e($invNote)) !!}</div>
  @endif

  @if($companyAddress2)
    <div class="foot">{{ $companyAddress2 }}</div>
  @endif

</body>
</html>
