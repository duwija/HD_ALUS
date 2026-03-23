<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice #{{ $suminvoice_number->number ?? '' }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    @media print {
      @page { margin: 2mm; size: 58mm auto; }
      body { margin: 0; padding: 0; }
      .no-print { display: none !important; }
      html, body { width: 58mm; }
    }

    body {
      font-family: 'Courier New', Courier, monospace;
      font-size: 8px;
      color: #000;
      background: #fff;
      width: 218px;
      margin: 0 auto;
      padding: 4px 3px;
    }

    .receipt {
      width: 100%;
    }

    .center   { text-align: center; }
    .right    { text-align: right; }
    .left     { text-align: left; }
    .bold     { font-weight: bold; }
    .big      { font-size: 10px; font-weight: bold; }
    .small    { font-size: 7px; }

    .divider-eq { border: none; border-top: 1px solid #000; margin: 4px 0; }
    .divider-dash {
      text-align: center;
      font-size: 9px;
      letter-spacing: 0;
      margin: 3px 0;
      overflow: hidden;
      white-space: nowrap;
    }

    .header-block { margin-bottom: 4px; }
    .header-block p { line-height: 1.4; margin: 0; }

    table.items {
      width: 100%;
      border-collapse: collapse;
      font-size: 8px;
    }
    table.items th {
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      padding: 2px 1px;
      font-weight: bold;
    }
    table.items td {
      padding: 2px 1px;
      vertical-align: top;
    }
    table.items .desc { width: 55%; }
    table.items .qty  { width: 10%; text-align: center; }
    table.items .amt  { width: 35%; text-align: right; }
    table.items .total-row td {
      border-top: 1px solid #000;
      font-weight: bold;
      padding-top: 3px;
    }

    .status-paid   { font-size: 11px; font-weight: bold; letter-spacing: 2px; }
    .status-unpaid { font-size: 11px; font-weight: bold; letter-spacing: 2px; }

    .footer-block { margin-top: 6px; }
    .footer-block p { line-height: 1.5; margin: 0; }

    .qr-block { margin: 6px 0; }
    .qr-block img, .qr-block svg { display: block; margin: 0 auto; }

    .inv-note {
      font-size: 8px;
      line-height: 1.4;
      margin-top: 4px;
      border-top: 1px dashed #000;
      padding-top: 4px;
    }
  </style>
  <script>
    window.onload = function() {
      window.print();
    };
  </script>
</head>
<body>
<div class="receipt">

  {{-- ===== HEADER ===== --}}
  <div class="header-block center">
    <p class="big">{{ strtoupper($companyName) }}</p>
    @if ($companyLegal)
    <p>{{ $companyLegal }}</p>
    @endif
    @if ($address1)
    <p class="small">{{ trim($address1) }}</p>
    @endif
    @if ($address2)
    <p class="small">{{ trim($address2) }}</p>
    @endif
  </div>

  <hr class="divider-eq">

  {{-- ===== TITLE ===== --}}
  <p class="center bold" style="font-size:9px;margin:2px 0;letter-spacing:2px">INVOICE</p>

  <hr class="divider-eq">

  {{-- ===== INVOICE INFO ===== --}}
  @php
    $invDate     = $suminvoice_number->date ?? '-';
    $invNumber   = $suminvoice_number->number ?? '-';
    $isPaid      = ($suminvoice_number->payment_status == 1);
    $payDate     = $suminvoice_number->payment_date ?? $invDate;
  @endphp

  <table style="width:100%;font-size:10px;border-collapse:collapse">
    <tr>
      <td style="width:38%">Tanggal</td>
      <td style="width:4%">:</td>
      <td>{{ $invDate }}</td>
    </tr>
    <tr>
      <td>No. Invoice</td>
      <td>:</td>
      <td>#{{ $invNumber }}</td>
    </tr>
  </table>

  <div class="divider-dash">- - - - - - - - - - - - - - - - - - - - -</div>

  {{-- ===== CUSTOMER ===== --}}
  <table style="width:100%;font-size:10px;border-collapse:collapse">
    <tr>
      <td style="width:38%">Bill To</td>
      <td style="width:4%">:</td>
      <td class="bold">{{ $customer->customer_id ?? '' }}</td>
    </tr>
    <tr>
      <td></td>
      <td></td>
      <td class="bold">{{ $customer->name ?? '-' }}</td>
    </tr>
    @if (!empty($customer->address))
    <tr>
      <td></td>
      <td></td>
      <td class="small">{{ $customer->address }}</td>
    </tr>
    @endif
  </table>

  <div class="divider-dash">- - - - - - - - - - - - - - - - - - - - -</div>

  {{-- ===== PAYMENT STATUS ===== --}}
  <p class="center {{ $isPaid ? 'status-paid' : 'status-unpaid' }}">
    {{ $isPaid ? '** LUNAS **' : '** BELUM LUNAS **' }}
  </p>

  <hr class="divider-eq">

  {{-- ===== ITEMS ===== --}}
  @php
    $subtotal = 0;
    $taxfee   = (float) ($suminvoice_number->tax ?? 0) / 100;
    $pph      = 0;
  @endphp

  <table class="items">
    <thead>
      <tr>
        <th class="desc">Deskripsi</th>
        <th class="qty">Qty</th>
        <th class="amt">Jumlah</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice as $inv)
      @php
        $isubtotal = $inv->qty * $inv->amount;
        $itax      = $isubtotal * $taxfee;
        $itotal    = $isubtotal + $itax;
        $ippah     = $isubtotal * (float)($suminvoice_number->pph ?? 0) / 100;
        $subtotal += $itotal - $ippah;
        $pph      += $ippah;

        // Build description
        if ($inv->monthly_fee == 1) {
          $mon   = date("M", mktime(0,0,0, (int)substr($inv->periode,-6,2), 1));
          $yr    = substr($inv->periode,-4,4);
          $desc  = $inv->description.' '.$mon.' '.$yr;
        } else {
          $desc  = $inv->description;
        }
      @endphp
      <tr>
        <td class="desc">{{ $desc }}</td>
        <td class="qty">{{ $inv->qty }}</td>
        <td class="amt">{{ number_format($itotal, 0, ',', '.') }}</td>
      </tr>
      @endforeach

      @if ($pph > 0)
      <tr>
        <td class="desc" colspan="2">PPh 23</td>
        <td class="amt">-{{ number_format($pph, 0, ',', '.') }}</td>
      </tr>
      @endif
    </tbody>
    <tfoot>
      <tr class="total-row">
        <td colspan="2" class="right bold">TOTAL</td>
        <td class="amt bold">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
      </tr>
    </tfoot>
  </table>

  <hr class="divider-eq">

  {{-- ===== INV NOTE ===== --}}
  @if ($invNote)
  <div class="inv-note center">
    {!! strip_tags($invNote, '<br><strong><b>') !!}
  </div>
  @endif

  {{-- ===== FOOTER ===== --}}
  <div class="footer-block center" style="margin-top:8px">
    <p>Tabanan, {{ $isPaid ? $payDate : $invDate }}</p>
    <div class="qr-block">
      {!! QrCode::size(50)->generate(url('/suminvoice/'.$suminvoice_number->tempcode.'/viewinvoice')) !!}
    </div>
    <p class="small" style="margin-top:3px">Scan untuk verifikasi invoice</p>
    <p class="bold" style="margin-top:6px">{{ $signature }}</p>
  </div>

  <hr class="divider-eq" style="margin-top:6px">
  <p class="center small" style="margin-top:2px">
    Terima kasih &mdash; {{ $companyName }}
  </p>

</div>

{{-- Print button (hidden when printing) --}}
<div class="no-print" style="text-align:center;margin-top:12px">
  <button onclick="window.print()" style="padding:6px 20px;font-size:12px;cursor:pointer">
    &#128424; Cetak
  </button>
</div>

</body>
</html>
