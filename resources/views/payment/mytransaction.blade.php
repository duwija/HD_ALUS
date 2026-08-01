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

    {{-- Filter paling atas --}}
    <div class="card card-outline card-warning mb-3">
      <div class="card-body py-3">
        <form method="post" action="{{ url('payment/mytransaction') }}">
          @csrf
          <div class="row align-items-end">
            <div class="col-md-4">
              <label>From:</label>
              <input type="date" name="date_from" class="form-control" value="{{ $date_from->format('Y-m-d') }}">
            </div>

            <div class="col-md-4">
              <label>To:</label>
              <input type="date" name="date_end" class="form-control" value="{{ $date_end->format('Y-m-d') }}">
            </div>

            <div class="col-md-4">
              <button type="submit" class="btn btn-warning w-100">Show</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    {{-- Summary --}}
        <div class="row mb-3">
          <div class="col-md-3 col-6 mb-2">
            <div class="small-box bg-info mb-0">
              <div class="inner">
                <h3>{{ number_format($transactionCount, 0, ',', '.') }}</h3>
                <p>Total Transaksi</p>
              </div>
              <div class="icon"><i class="fas fa-receipt"></i></div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-2">
            <div class="small-box bg-success mb-0">
              <div class="inner">
                <h3>Rp {{ number_format($totalReceivePayment, 0, ',', '.') }}</h3>
                <p>Total Pembayaran (Periode)</p>
              </div>
              <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-2">
            <div class="small-box bg-warning mb-0">
              <div class="inner">
                <h3>Rp {{ number_format($totalMerchantFee, 0, ',', '.') }}</h3>
                <p>Total Admin Fee (Periode)</p>
              </div>
              <div class="icon"><i class="fas fa-coins"></i></div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-2">
            <div class="small-box bg-primary mb-0">
              <div class="inner">
                <h3>Rp {{ number_format($merchantCashValue, 0, ',', '.') }}</h3>
                <p>Total Saldo Kas Merchant</p>
              </div>
              <div class="icon"><i class="fas fa-wallet"></i></div>
            </div>
          </div>
        </div>

    @if(Auth::user()->privilege === 'merchant')
    {{-- 3 kolom: kas merchant, hutang fee merchant, tren volume harian --}}
    <div class="row mb-4">
      <div class="col-lg-4 col-md-6 mb-3">
        <div class="card card-outline card-primary h-100 mb-0">
          <div class="card-header py-2">
            <strong>Saldo Per Kas Merchant</strong>
          </div>
          <div class="card-body p-0">
            @if($merchantCashBreakdown->isNotEmpty())
            <div class="table-responsive mb-0">
              <table class="table table-sm table-striped table-bordered mb-0">
                <thead>
                  <tr>
                    <th style="width: 40px;">#</th>
                    <th>Kode Akun</th>
                    <th>Nama Akun</th>
                    <th class="text-right">Nominal</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($merchantCashBreakdown as $idx => $cash)
                  <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $cash['akun_code'] }}</td>
                    <td>{{ $cash['akun_name'] }}</td>
                    <td class="text-right">Rp {{ number_format($cash['saldo'], 0, ',', '.') }}</td>
                  </tr>
                  @endforeach
                </tbody>
                <tfoot>
                  <tr class="bg-light">
                    <th colspan="3" class="text-right">Total Nilai Kas</th>
                    <th class="text-right">Rp {{ number_format($merchantCashValue, 0, ',', '.') }}</th>
                  </tr>
                </tfoot>
              </table>
            </div>
            @else
            <div class="p-3 text-muted">Belum ada akun kas merchant yang terhubung.</div>
            @endif
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6 mb-3">
        <div class="card card-outline card-danger h-100 mb-0">
          <div class="card-header py-2">
            <strong>Fee Merchant</strong>
          </div>
          <div class="card-body p-0">
            @if($merchantLiabilityBreakdown->isNotEmpty())
            <div class="table-responsive mb-0">
              <table class="table table-sm table-striped table-bordered mb-0">
                <thead>
                  <tr>
                    <th style="width: 40px;">#</th>
                    <th>Sumber</th>
                    <th>Kode Akun</th>
                    <th class="text-right">Saldo</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($merchantLiabilityBreakdown as $idx => $item)
                  <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item['source'] }}</td>
                    <td>{{ $item['akun_code'] }}</td>
                    <td class="text-right">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
                  </tr>
                  @endforeach
                </tbody>
                <tfoot>
                  <tr class="bg-light">
                    <th colspan="3" class="text-right">Total</th>
                    <th class="text-right">Rp {{ number_format($merchantLiabilityValue, 0, ',', '.') }}</th>
                  </tr>
                </tfoot>
              </table>
            </div>
            @else
            <div class="p-3 text-muted">Belum ada mapping akun hutang fee untuk user/merchant ini.</div>
            @endif
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-12 mb-3">
        <div class="card card-outline card-info h-100 mb-0">
          <div class="card-header py-2">
            <strong>Tren Volume Harian</strong>
          </div>
          <div class="card-body" style="min-height: 300px;">
            <canvas id="paymentLineChart" height="220"></canvas>
          </div>
        </div>
      </div>
    </div>

    @else
    <div class="row mb-4">
      <div class="col-md-6 mb-3">
        <div class="card card-outline card-primary h-100 mb-0">
          <div class="card-header py-2">
            <strong>Saldo Per Kas Merchant</strong>
          </div>
          <div class="card-body p-0">
            @if($merchantCashBreakdown->isNotEmpty())
            <div class="table-responsive mb-0">
              <table class="table table-sm table-striped table-bordered mb-0">
                <thead>
                  <tr>
                    <th style="width: 40px;">#</th>
                    <th>Kode Akun</th>
                    <th>Nama Akun</th>
                    <th class="text-right">Nominal</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($merchantCashBreakdown as $idx => $cash)
                  <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $cash['akun_code'] }}</td>
                    <td>{{ $cash['akun_name'] }}</td>
                    <td class="text-right">Rp {{ number_format($cash['saldo'], 0, ',', '.') }}</td>
                  </tr>
                  @endforeach
                </tbody>
                <tfoot>
                  <tr class="bg-light">
                    <th colspan="3" class="text-right">Total Nilai Kas</th>
                    <th class="text-right">Rp {{ number_format($merchantCashValue, 0, ',', '.') }}</th>
                  </tr>
                </tfoot>
              </table>
            </div>
            @else
            <div class="p-3 text-muted">Belum ada akun kas merchant yang terhubung.</div>
            @endif
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-3">
        <div class="card card-outline card-info h-100 mb-0">
          <div class="card-header py-2">
            <strong>Tren Volume Harian</strong>
          </div>
          <div class="card-body" style="min-height: 300px;">
            <canvas id="paymentLineChart" height="220"></canvas>
          </div>
        </div>
      </div>
    </div>
    @endif

    <hr>

    {{-- Table --}}
    <div class="table-responsive w-100 mt-4">
    <table id="payment-mytransaction-table" class="table table-bordered table-striped w-100">
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
      </tbody>
      <tfoot>
        <tr class="bg-light">
          <td colspan="7" class="text-right"><strong>Total :</strong></td>
          <td class="text-right"><strong>Rp {{ number_format($total_merchant_fee, 0, ',', '.') }}</strong></td>
          <td class="text-right"><strong>Rp {{ number_format($total_amount, 0, ',', '.') }}</strong></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
    </div>
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
      labels: {!! json_encode($chartLabels) !!},
      datasets: [{
        label: 'Volume Pembayaran Harian (Transaksi)',
        data: {!! json_encode($chartVolumes) !!},
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
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        title: { display: true, text: 'Tren Volume Pembayaran Harian' }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 },
          title: { display: true, text: 'Jumlah Transaksi' }
        },
        x: { title: { display: true, text: 'Tanggal' } }
      }
    }
  });

  $(function () {
    if ($.fn.DataTable.isDataTable('#payment-mytransaction-table')) {
      $('#payment-mytransaction-table').DataTable().destroy();
    }

    $('#payment-mytransaction-table').DataTable({
      paging: false,
      lengthChange: false,
      searching: true,
      ordering: true,
      info: false,
      autoWidth: false,
      responsive: false,
      scrollX: true,
      dom: 'Bfrtip',
      buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5']
    });
  });
</script>
@endsection
