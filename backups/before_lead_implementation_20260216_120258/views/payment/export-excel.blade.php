<h4>My Transaction Report</h4>
<p>Periode: {{ $date_from->format('d M Y') }} - {{ $date_end->format('d M Y') }}</p>

<table border="1">
  <thead>
    <tr>
      <th>#</th>
      <th>Date</th>
      <th>Invoice</th>
      <th>Customer</th>
      <th>Kas / Payment Point</th>
      <th>Merchant Fee</th>
      <th>Amount</th>
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
      <td>{{ $inv->merchant_fee ?? 0 }}</td>
      <td>{{ $inv->recieve_payment ?? 0 }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
