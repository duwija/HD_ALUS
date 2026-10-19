@php
  // ── Perhitungan (sama dengan versi lama, kecuali biaya admin dihitung sekali per invoice
  //    dan PPh dijumlahkan dari semua item) ─────────────────────────────────────────────
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
          'price' => $item->amount + ($item->amount * $taxRate),
          'qty' => $item->qty,
          'total' => $base + $tax,
      ];
  }
  $grandTotal = $itemsTotal - $pphTotal + $adminFee;

  $status = (int) $suminvoice_number->payment_status;
  $stamp = [1 => ['LUNAS', 'PAID', '#2e7d32'], 2 => ['DIBATALKAN', 'CANCELLED', '#6b7280']][$status] ?? ['BELUM TERBAYAR', 'UNPAID', '#c62828'];

  $rescode = tenant_rescode();
  $logo = ($rescode && is_file(public_path("tenants/{$rescode}/img/logoinv.png")))
      ? "/tenants/{$rescode}/img/logoinv.png"
      : '/dashboard/dist/img/logoinv.png';

  $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Invoice #{{ $suminvoice_number->number }}</title>
  <style>
    :root { --ink: #1f2937; --muted: #6b7280; --line: #e5e7eb; --brand: #0d47a1; --soft: #f3f6fb; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #eef1f5; color: var(--ink); font: 13px/1.45 "Segoe UI", Roboto, Arial, sans-serif; }
    .toolbar { max-width: 210mm; margin: 16px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
    .btn { display: inline-block; border: 0; border-radius: 6px; padding: 8px 14px; font-size: 13px; cursor: pointer; color: #fff; background: var(--brand); text-decoration: none; }
    .btn.secondary { background: #4b5563; }
    .page { max-width: 210mm; margin: 12px auto 24px; background: #fff; padding: 14mm 14mm 12mm; box-shadow: 0 2px 12px rgba(0,0,0,.08); }

    .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding-bottom: 12px; border-bottom: 3px solid var(--brand); }
    .brand img { max-height: 56px; max-width: 160px; display: block; margin-bottom: 6px; }
    .brand .name { font-size: 18px; font-weight: 700; color: var(--brand); }
    .brand .addr { font-size: 11px; color: var(--muted); max-width: 320px; }
    .doc { display: flex; gap: 12px; align-items: flex-start; text-align: right; }
    .doc .title { font-size: 26px; font-weight: 800; letter-spacing: 2px; color: var(--brand); line-height: 1; }
    .doc .subtitle { font-size: 11px; letter-spacing: 3px; color: var(--muted); }
    .doc .qr svg { width: 74px; height: 74px; display: block; }

    .stamp { align-self: center; margin-left: 24px; transform: rotate(-10deg); border: 3px solid currentColor; border-radius: 8px; padding: 4px 16px; text-align: center; opacity: .85; }
    .stamp b { display: block; font-size: 20px; letter-spacing: 2px; }
    .stamp small { font-size: 10px; letter-spacing: 2px; }

    .parties { display: flex; gap: 14px; margin: 16px 0; }
    .card { flex: 1; background: var(--soft); border-radius: 8px; padding: 10px 12px; }
    .card .label { font-size: 10px; font-weight: 700; letter-spacing: 1.5px; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; }
    .card .big { font-size: 15px; font-weight: 700; }
    .card table td { padding: 1px 8px 1px 0; vertical-align: top; }
    .card table td:first-child { color: var(--muted); white-space: nowrap; }

    table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.items th { background: var(--brand); color: #fff; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; padding: 8px 10px; text-align: left; }
    table.items td { padding: 8px 10px; border-bottom: 1px solid var(--line); }
    table.items tbody tr:nth-child(even) td { background: #fafbfc; }
    .num { text-align: right; white-space: nowrap; }
    .center { text-align: center; }

    .summary { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 10px; }
    .summary table { min-width: 300px; border-collapse: collapse; }
    .summary td { padding: 5px 10px; }
    .summary .total td { border-top: 2px solid var(--ink); font-size: 16px; font-weight: 800; padding-top: 8px; }
    .tax-note { font-size: 11px; color: var(--muted); text-align: right; margin-top: 2px; }

    .warning { margin-top: 16px; border-left: 4px solid #c62828; background: #fdecea; color: #8e1b1b; padding: 10px 12px; border-radius: 4px; }
    .sign { margin-top: 22px; display: flex; justify-content: flex-end; }
    .sign div { text-align: center; min-width: 200px; }
    .sign .who { margin-top: 46px; font-weight: 700; border-top: 1px solid var(--ink); padding-top: 4px; }
    .note { margin-top: 18px; padding: 10px 12px; background: #f9fafb; border-left: 4px solid var(--brand); font-size: 12px; }
    .foot { margin-top: 16px; padding-top: 8px; border-top: 1px solid var(--line); font-size: 11px; color: var(--muted); text-align: center; }

    @page { size: A4 portrait; margin: 10mm; }
    @media print {
      body { background: #fff; }
      .toolbar, .no-print { display: none !important; }
      .page { margin: 0; padding: 0; box-shadow: none; max-width: none; }
      * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>

  <div class="toolbar no-print">
    {{-- Halaman ini dibuka di tab baru dari halaman kasir — kalau tidak ada riwayat, kembali ke halaman kasir invoice ini --}}
    <a class="btn secondary" href="{{ url('payment/' . $suminvoice_number->tempcode) }}"
       onclick="if (window.history.length > 1 && document.referrer) { event.preventDefault(); window.history.back(); }">&larr; Kembali</a>
    @if(!empty($suminvoice_number->file))
      <a class="btn secondary" href="{{ url('upload/tax/' . $suminvoice_number->file) }}" target="_blank">Faktur Pajak</a>
    @endif
    <button class="btn" onclick="window.print()">Print</button>
  </div>

  <div class="page">
    <div class="head">
      <div class="brand">
        <img src="{{ $logo }}" alt="{{ $companyName }}">
        <div class="name">{{ $companyName }}</div>
        @if($companyAddress1)<div class="addr">{!! nl2br(e($companyAddress1)) !!}</div>@endif
      </div>
      <div class="doc">
        <div>
          <div class="title">INVOICE</div>
          <div class="subtitle">TAGIHAN</div>
        </div>
        <div class="qr">{!! QrCode::size(74)->margin(0)->generate(url('suminvoice/' . $suminvoice_number->tempcode . '/viewinvoice')) !!}</div>
      </div>
    </div>

    <div class="parties">
      <div class="card">
        <div class="label">Ditagihkan kepada</div>
        <div class="big">{{ $customer->name }}</div>
        <table>
          <tr><td>ID Pelanggan</td><td><b>{{ $customer->customer_id }}</b></td></tr>
          @if($customer->address)<tr><td>Alamat</td><td>{{ $customer->address }}</td></tr>@endif
          @if($customer->phone)<tr><td>Telepon</td><td>{{ $customer->phone }}</td></tr>@endif
          @if(trim((string) $customer->npwp) !== '' && $customer->npwp !== '0')<tr><td>NPWP</td><td>{{ strtoupper($customer->npwp) }}</td></tr>@endif
        </table>
      </div>
      <div class="card">
        <div class="label">Pembayaran</div>
        <table>
          <tr><td>No. Invoice</td><td><b>#{{ $suminvoice_number->number }}</b></td></tr>
          <tr><td>Tanggal</td><td>{{ $suminvoice_number->date }}</td></tr>
          @if($status === 1)
            <tr><td>Tanggal bayar</td><td>{{ $suminvoice_number->payment_date }}</td></tr>
            <tr><td>Diterima di</td><td>{{ optional($suminvoice_number->kasbank)->name ?? '-' }}</td></tr>
            <tr><td>Diterima oleh</td><td>{{ optional($suminvoice_number->user)->name ?? $suminvoice_number->updated_by }}</td></tr>
          @elseif($status === 2)
            <tr><td>Status</td><td><b>Dibatalkan</b></td></tr>
          @else
            <tr><td>Jatuh tempo</td><td><b>{{ $suminvoice_number->due_date ?: '-' }}</b></td></tr>
          @endif
        </table>
      </div>
    </div>

    <table class="items">
      <thead>
        <tr>
          <th style="width:36px">#</th>
          <th>Deskripsi</th>
          <th class="num" style="width:110px">Harga</th>
          <th class="center" style="width:50px">Qty</th>
          <th class="num" style="width:120px">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $i => $row)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row['description'] }}</td>
            <td class="num">{{ number_format($row['price'], 0, ',', '.') }}</td>
            <td class="center">{{ $row['qty'] }}</td>
            <td class="num">{{ number_format($row['total'], 0, ',', '.') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="summary">
      <div class="stamp" style="color: {{ $stamp[2] }}"><b>{{ $stamp[0] }}</b><small>{{ $stamp[1] }}</small></div>
      <table>
        <tr><td>Subtotal</td><td class="num">{{ $rp($itemsTotal) }}</td></tr>
        @if($pphTotal > 0)
          <tr><td>PPh 23</td><td class="num">- {{ $rp($pphTotal) }}</td></tr>
        @endif
        @if($adminFee > 0)
          <tr><td>Biaya Admin</td><td class="num">{{ $rp($adminFee) }}</td></tr>
        @endif
        <tr class="total"><td>Total</td><td class="num">{{ $rp($grandTotal) }}</td></tr>
      </table>
    </div>
    <div class="tax-note">Setiap harga yang tertera pada invoice sudah termasuk PPN.</div>

    @if($status === 0 && $current_inv_status == 1)
      <div class="warning">
        Anda masih memiliki tagihan yang belum terbayar (UNPAID) pada periode sebelumnya. Silakan melunasi tagihan tersebut
        terlebih dahulu. Untuk info lebih lanjut silakan menghubungi tim Payment kami.
      </div>
    @endif

    @if($status !== 0)
      <div class="sign">
        <div>
          {!! nl2br(e($signature)) !!}@if($status === 1), {{ \Carbon\Carbon::parse($suminvoice_number->payment_date)->format('d-m-Y') }}@endif
          <div>Terima kasih,</div>
          @if($status === 1)
            <div class="who">{{ optional($suminvoice_number->user)->name ?? $suminvoice_number->updated_by }}</div>
          @endif
        </div>
      </div>
    @endif

    @if(!empty($invNote))
      <div class="note">{!! nl2br(e($invNote)) !!}</div>
    @endif

    @if($companyAddress2)
      <div class="foot">{!! nl2br(e($companyAddress2)) !!}</div>
    @endif
  </div>

</body>
</html>
