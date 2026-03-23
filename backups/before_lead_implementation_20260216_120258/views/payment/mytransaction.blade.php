@extends('layout.main')
@section('title','My Transaction')

@section('content')
<section class="content-header">
  <div class="card card-outline">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">
        <i class="fas fa-file-invoice-dollar mr-2"></i>
        {{ Auth::user()->name }}'s Transactions
      </h3>
      <div class="position-absolute" style="top:10px; right:20px; z-index:10;">
        <a href="{{ route('payment.export.excel', ['date_from' => $date_from->format('Y-m-d'), 'date_end' => $date_end->format('Y-m-d')]) }}" 
         class="btn btn-success btn-sm shadow-sm">
         <i class="fas fa-file-excel"></i> Excel
       </a>
       <a href="{{ route('payment.export.pdf', ['date_from' => $date_from->format('Y-m-d'), 'date_end' => $date_end->format('Y-m-d')]) }}" 
         class="btn btn-danger btn-sm shadow-sm">
         <i class="fas fa-file-pdf"></i> PDF
       </a>
     </div>

   </div>

   <div class="card-body">  {{-- Line Chart --}}
    

    {{-- Filter Form + Chart --}}
    <div class="row mb-4 align-items-start">
      {{-- Kolom kiri: Filter Form --}}
      <div class="col-md-6">
        <form method="post" action="{{ url('payment/mytransaction') }}">
          @csrf
          <div class="row align-items-end">
            <div class="col-md-5">
              <label>From:</label>
              <input type="date" name="date_from" class="form-control"
              value="{{ $date_from->format('Y-m-d') }}">
            </div>

            <div class="col-md-5">
              <label>To:</label>
              <input type="date" name="date_end" class="form-control"
              value="{{ $date_end->format('Y-m-d') }}">
            </div>

            <div class="col-md-2">
              <button type="submit" class="btn btn-warning mt-4 w-100">Show</button>
            </div>
          </div>
        </form>
      </div>

      {{-- Kolom kanan: Chart --}}
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <canvas id="paymentLineChart" height="100"></canvas>
          </div>
        </div>
      </div>
    </div>


    <hr>

    {{-- Table --}}
    <table id="example3" class="table table-bordered table-striped mt-4 table-responsive">
      <thead class="bg-light">
        <tr>
          <th>#</th>
          <th>Receive Payment</th>
          <th>Receive By</th>
          <th>Invoice No</th>
          <th>CID / Customer</th>
          <th>Kas / Payment Point</th>
          <th>Note</th>
          <th>Merchant Fee</th>
          <th>Amount</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @php $total_amount = 0; $total_merchant_fee = 0; @endphp
        @foreach($suminvoices as $index => $inv)
        @php
        $total_amount += $inv->recieve_payment ?? 0;
        $total_merchant_fee += $inv->merchant_fee ?? 0;
        $status = match($inv->payment_status) {
          1 => ['PAID', 'badge-success'],
          2 => ['CANCEL', 'badge-secondary'],
          0 => ['UNPAID', 'badge-danger'],
          default => ['UNKNOWN', 'badge-warning'],
        };
        @endphp
        <tr>
          <td>{{ $index + 1 }}</td>
          <td>{{ $inv->payment_date }}</td>
          <td>{{ $inv->user->name ?? '-' }}</td>
          <td>{{ $inv->number }}</td>
          <td>{{ $inv->customer->customer_id ?? '-' }} | {{ $inv->customer->name ?? '-' }}</td>
          <td>{{ $inv->kasbank->name ?? $inv->payment_point ?? '-' }}</td>
          <td>{{ $inv->note ?? '-' }}</td>
          <td class="text-right">{{ number_format($inv->merchant_fee ?? 0, 0, ',', '.') }}</td>
          <td class="text-right"><strong>{{ number_format($inv->recieve_payment ?? 0, 0, ',', '.') }}</strong></td>
          <td class="text-center"><span class="badge {{ $status[1] }}">{{ $status[0] }}</span></td>
        </tr>
        @endforeach

        <tr class="bg-light">
          <td colspan="7" class="text-right"><strong>Total :</strong></td>
          <td class="text-right"><strong>Rp {{ number_format($total_merchant_fee, 0, ',', '.') }}</strong></td>
          <td class="text-right"><strong>Rp {{ number_format($total_amount, 0, ',', '.') }}</strong></td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
</section>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('paymentLineChart').getContext('2d');
  const paymentLineChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: {!! json_encode($chartData->keys()) !!},
      datasets: [{
        label: 'Volume Pembayaran (Rp)',
        data: {!! json_encode($chartData->values()) !!},
        fill: true,
        tension: 0.3,
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
        pointRadius: 4,
        pointHoverRadius: 6,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        title: { display: true, text: 'Tren Volume Pembayaran Harian' }
      },
      scales: {
        y: { beginAtZero: true, title: { display: true, text: 'Jumlah (Rp)' } },
        x: { title: { display: true, text: 'Tanggal' } }
      }
    }
  });
</script>
@endsection
