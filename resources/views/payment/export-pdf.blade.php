<h3 style="text-align:center;">My Transaction Report</h3>
<p style="text-align:center;">
  Periode: {{ $date_from->format('d M Y') }} - {{ $date_end->format('d M Y') }}
</p>
<table border="1" cellspacing="0" cellpadding="6" width="100%">
  <thead>
    <tr>
      <th>#</th><th>Date</th><th>Invoice</th><th>Customer</th>
      <th>Kas</th><th>Merchant Fee</th><th>Amount</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data as $i => $inv)
    <tr>
      <td>{{ $i+1 }}</td>
      <td>{{ $inv->payment_date }}</td>
      <td>{{ $inv->number }}</td>
      <td>{{ $inv->customer->name ?? '-' }}</td>
      <td>{{ $inv->kasbank->nama_akun ?? $inv->payment_point ?? '-' }}</td>
      <td align="right">{{ number_format($inv->merchant_fee ?? 0, 0, ',', '.') }}</td>
      <td align="right">{{ number_format($inv->recieve_payment ?? 0, 0, ',', '.') }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
