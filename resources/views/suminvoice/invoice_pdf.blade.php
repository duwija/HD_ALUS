<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice {{ $suminvoice_number->number }}</title>
<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #333;
        margin: 0;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    .header-table td {
        border: none;
        vertical-align: top;
        padding: 0;
    }
    .company-name {
        font-size: 12px;
        font-weight: bold;
        margin-top: 4px;
    }
    .tagihan-title {
        text-align: right;
        font-size: 13px;
    }
    .box-table td {
        border: 1px solid #ddd;
        vertical-align: top;
        padding: 10px;
        font-size: 11px;
        width: 50%;
    }
    .box-table h4 {
        margin: 0 0 6px;
        font-size: 12px;
        color: #555;
    }
    .status-paid   { color: #1a7a2e; font-weight: bold; font-size: 16px; }
    .status-cancel { color: #6c757d; font-weight: bold; font-size: 16px; }
    .status-unpaid { color: #c0392b; font-weight: bold; font-size: 16px; }
    .items-table {
        margin-top: 14px;
    }
    .items-table th, .items-table td {
        border: 1px solid #333;
        padding: 6px;
        font-size: 11px;
    }
    .items-table th {
        background-color: #cbcac7;
    }
    .note {
        margin-top: 10px;
        font-size: 11px;
    }
    .signature {
        margin-top: 24px;
        font-size: 11px;
    }
    .footer-address {
        margin-top: 20px;
        padding-top: 8px;
        border-top: 1px solid #ccc;
        text-align: right;
        font-size: 10px;
        color: #555;
    }
</style>
</head>
<body>

<table class="header-table">
    <tr>
        <td width="60%">
            @if($logoBase64)
            <img src="data:image/png;base64,{{ $logoBase64 }}" width="150">
            @endif
            <div class="company-name">{{ $companyName }}</div>
        </td>
        <td width="25%" class="tagihan-title">
            <strong><u>TAGIHAN</u></strong><br>
            <em>INVOICE</em>
        </td>
        <td width="15%" align="right">
            <img src="data:image/png;base64,{{ $qrcode }}" width="60">
        </td>
    </tr>
</table>

<table class="box-table" style="margin-top:14px;">
    <tr>
        <td>
            <h4>Kepada:</h4>
            <strong>{{ $customer->customer_id }}<br>{{ $customer->name }}</strong><br>
            {{ $customer->address }}<br>
            {{ $customer->phone }}<br>
            {{ $customer->npwp }}
        </td>
        <td align="right">
            Invoice No: <strong>#{{ $suminvoice_number->number }}</strong><br>
            Inv date: {{ $suminvoice_number->date }}<br>
            @if ($suminvoice_number->payment_status == 1)
                @php
                    $pgSlug   = $suminvoice_number->payment_gateway ?? null;
                    $pgRecord = $pgSlug ? \App\PaymentGateway::findForCurrentTenant($pgSlug) : null;
                    $pgLabel  = ($pgRecord->settings['invoice_label'] ?? '') ?: null;
                    $kasbankName = $pgLabel
                        ?? ($suminvoice_number->kasbank->name ?? null)
                        ?? ($pgSlug ? strtoupper($pgSlug) : 'Online');
                @endphp
                Payment date: {{ $suminvoice_number->payment_date }}<br>
                {{ $kasbankName }}<br>
                <span class="status-paid">PAID</span><br>
                <span style="font-size:10px;color:#1a7a2e;">(SUDAH TERBAYAR)</span>
            @elseif ($suminvoice_number->payment_status == 2)
                <span class="status-cancel">CANCEL</span><br>
                <span style="font-size:10px;color:#6c757d;">(DIBATALKAN)</span>
            @else
                Due date: {{ $suminvoice_number->due_date }}<br>
                <span class="status-unpaid">UNPAID</span><br>
                <span style="font-size:10px;color:#c0392b;">(BELUM TERBAYAR)</span>
            @endif
        </td>
    </tr>
</table>

@php
    $subtotal = 0;
    $taxfee = $suminvoice_number->tax == null ? 0 : $suminvoice_number->tax / 100;
    $pphTotal = 0;
@endphp

<table class="items-table">
    <tr>
        <th>#</th>
        <th>Description</th>
        <th>Price</th>
        <th>Qty</th>
        <th>Sub Total</th>
    </tr>
    @foreach ($invoice as $item)
    @php
        $totalwutax = $item->qty * $item->amount;
        $totaltax = $totalwutax * $taxfee;
        $pph = $totalwutax * $suminvoice_number->pph / 100;
        $taxitem = $item->amount * $taxfee;
        $subtotal = $subtotal + ($totalwutax + $totaltax) - $pph;
        $pphTotal += $pph;

        $periodLabel = '-';
        if (!empty($item->periode) && strlen($item->periode) >= 6) {
            $strmonth = substr($item->periode, -6, 2);
            $stryear = substr($item->periode, -4, 4);
            if (is_numeric($strmonth) && (int) $strmonth >= 1 && (int) $strmonth <= 12) {
                $periodLabel = date("F", mktime(0, 0, 0, (int) $strmonth, 10)) . ' ' . $stryear;
            }
        }
        $description = $item->description;
        if ((int) $item->monthly_fee === 1 && $periodLabel !== '-') {
            $description .= ' - ' . $periodLabel;
        }
        $itotal = ($item->qty * $item->amount) + (($item->qty * $item->amount) * $taxfee);
    @endphp
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $description }}</td>
        <td>{{ number_format($item->amount + $taxitem, 0, ',', '.') }}</td>
        <td align="center">{{ $item->qty }}</td>
        <td align="right">{{ number_format($itotal, 0, ',', '.') }}</td>
    </tr>
    @endforeach

    @if ($pphTotal != 0)
    <tr>
        <td colspan="4">Pph 23</td>
        <td align="right"><strong>Rp. -{{ number_format($pphTotal, 0, ',', '.') }}</strong></td>
    </tr>
    @endif
    <tr>
        <td colspan="4">Total Tagihan</td>
        <td align="right"><strong>Rp. {{ number_format($subtotal, 0, ',', '.') }}</strong></td>
    </tr>
    @if ($suminvoice_number->payment_status == 1 && $suminvoice_number->merchant_fee > 0)
    <tr>
        <td colspan="4">Biaya Admin</td>
        <td align="right"><strong>Rp. {{ number_format($suminvoice_number->merchant_fee, 0, ',', '.') }}</strong></td>
    </tr>
    <tr>
        <td colspan="4">Total Dibayar</td>
        <td align="right"><strong>Rp. {{ number_format($subtotal + $suminvoice_number->merchant_fee, 0, ',', '.') }}</strong></td>
    </tr>
    @endif
</table>

@if (!empty($invNote))
<div class="note">{!! nl2br(e($invNote)) !!}</div>
@endif

@if ($suminvoice_number->payment_status == 1)
<div class="signature">
    <p>{{ $signature }}, {{ $suminvoice_number->payment_date }}</p>
    <p>Terima Kasih,</p>
    <br><br>
    <p>{{ $suminvoice_number->user->name ?? $suminvoice_number->updated_by }}</p>
</div>
@elseif ($suminvoice_number->payment_status == 2)
<div class="signature">
    <p>{{ $signature }}</p>
    <p>Terima Kasih,</p>
</div>
@else
<div class="signature">
    @if ($current_inv_status == 1)
    <p style="color:#c40205;">
        Anda masih memiliki tagihan yang belum terbayar (UNPAID) pada periode sebelumnya, silahkan melakukan
        pelunasan pembayaran Tagihan tersebut terlebih dahulu. Untuk info lebih lanjut silahkan menghubungi
        team Payment kami.
    </p>
    @endif
    <p>Lihat &amp; bayar tagihan ini secara online: <a href="{{ url('/invoice/cst/' . $encryptedurl) }}">{{ url('/invoice/cst/' . $encryptedurl) }}</a></p>
</div>
@endif

<div class="footer-address">
    {{ $companyAddress1 }}<br>
    {{ $companyAddress2 }}
</div>

</body>
</html>
