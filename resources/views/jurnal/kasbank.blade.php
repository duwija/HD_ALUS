@extends('layout.main')
@section('title','KAS & BANK')
@section('content')

<div class="container-fluid">
  <!-- Card Header -->
  <div class="card shadow-sm mb-4">
    <div class="card-header-custom">
      <div class="d-flex justify-content-between align-items-center">
        <h3><i class="fas fa-wallet mr-2"></i>KAS & BANK</h3>
        <div class="dropdown">
          <button class="btn btn-light dropdown-toggle" type="button" id="transactionDropdown" data-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-plus-circle mr-1"></i> Transaksi Baru
          </button>
          <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="transactionDropdown">
            <li><a class="dropdown-item" href="/jurnal/kasmasuk"><i class="fas fa-hand-holding-usd text-success mr-2"></i> Kas Masuk</a></li>
            <li><a class="dropdown-item" href="/jurnal/kaskeluar"><i class="fas fa-money-bill-wave text-danger mr-2"></i> Kas Keluar</a></li>
            <li><a class="dropdown-item" href="/jurnal/transferkas"><i class="fas fa-exchange-alt text-primary mr-2"></i> Transfer Kas</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="card-body">
      <div class="filter-section">
        <form method="GET" action="{{ url()->current() }}" class="form-row align-items-end">
          <div class="col-md-4 mb-3">
            <label for="date_from_display"><i class="far fa-calendar-alt mr-1"></i> Tanggal Awal</label>
            <input type="text" id="date_from_display" class="form-control" autocomplete="off" readonly
              value="{{ \Carbon\Carbon::parse(request('date_from', \Carbon\Carbon::today()->toDateString()))->format('d/m/Y') }}">
            <input type="hidden" name="date_from" id="date_from_hidden"
              value="{{ request('date_from', \Carbon\Carbon::today()->toDateString()) }}">
          </div>
          <div class="col-md-4 mb-3">
            <label for="date_to_display"><i class="far fa-calendar-alt mr-1"></i> Tanggal Akhir</label>
            <input type="text" id="date_to_display" class="form-control" autocomplete="off" readonly
              value="{{ \Carbon\Carbon::parse(request('date_to', \Carbon\Carbon::today()->toDateString()))->format('d/m/Y') }}">
            <input type="hidden" name="date_to" id="date_to_hidden"
              value="{{ request('date_to', \Carbon\Carbon::today()->toDateString()) }}">
          </div>
          <div class="col-md-4 mb-3">
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fas fa-search mr-1"></i> Tampilkan Data
            </button>
          </div>
        </form>
      </div>

      <!-- Period Info -->
      <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle mr-2"></i>
        <strong>Periode:</strong> {{ \Carbon\Carbon::parse($date_from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($date_to)->translatedFormat('d F Y') }}
      </div>

      <!-- Summary Cards -->
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="summary-card debit-card card border-0 shadow-sm">
            <div class="card-body">
              <h6 class="stat-label mb-2">
                <i class="fas fa-arrow-down mr-1"></i> Total Debit
              </h6>
              <h3 class="stat-value">
                Rp {{ number_format($transactionsByAccount->sum('total_debit'), 0, ',', '.') }}
              </h3>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="summary-card kredit-card card border-0 shadow-sm">
            <div class="card-body">
              <h6 class="stat-label mb-2">
                <i class="fas fa-arrow-up mr-1"></i> Total Kredit
              </h6>
              <h3 class="stat-value">
                Rp {{ number_format($transactionsByAccount->sum('total_kredit'), 0, ',', '.') }}
              </h3>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="summary-card saldo-card card border-0 shadow-sm">
            <div class="card-body">
              <h6 class="stat-label mb-2">
                <i class="fas fa-wallet mr-1"></i> Saldo
              </h6>
              <h3 class="stat-value">
                Rp {{ number_format($saldo, 0, ',', '.') }}
              </h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h5 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>Komposisi Kas & Bank</h5>
            </div>
            <div class="card-body">
              <div id="kasPieApex" style="height:350px"></div>
            </div>
          </div>
        </div>
        <div class="col-md-8">
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h5 class="mb-0"><i class="fas fa-chart-bar mr-2"></i>Grafik Per Akun</h5>
            </div>
            <div class="card-body">
              <div id="kasBankApex" style="height:350px"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Section -->
      <div class="card shadow-sm">
        <div class="card-header bg-light">
          <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Detail Transaksi Per Akun</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="example1" class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th>ID Akun</th>
                  <th>Nama Akun</th>
                  <th class="text-right">Total Debit</th>
                  <th class="text-right">Total Kredit</th>
                  <th class="text-right">Saldo</th>
                </tr>
              </thead>
              <tbody>
                @forelse($transactionsByAccount as $transaction)
                <tr>
                  <td><strong>{{ $transaction->id_akun }}</strong></td>
                  <td>{{ $transaction->akun_name }}</td>
                  <td class="text-right">
                    <span style="font-family: 'Courier New', monospace;">
                      Rp {{ number_format($transaction->total_debit, 0, ',', '.') }}
                    </span>
                  </td>
                  <td class="text-right">
                    <span style="font-family: 'Courier New', monospace;">
                      Rp {{ number_format($transaction->total_kredit, 0, ',', '.') }}
                    </span>
                  </td>
                  <td class="text-right">
                    <strong style="font-family: 'Courier New', monospace; color: {{ $transaction->saldo >= 0 ? '#28a745' : '#dc3545' }};">
                      Rp {{ number_format($transaction->saldo, 0, ',', '.') }}
                    </strong>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="5" class="text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                    Tidak ada data transaksi untuk periode ini
                  </td>
                </tr>
                @endforelse
              </tbody>
              @if($transactionsByAccount->count() > 0)
              <tfoot style="background-color: #343a40; color: white; font-weight: bold;">
                <tr>
                  <td colspan="2" class="text-right">TOTAL</td>
                  <td class="text-right" style="font-family: 'Courier New', monospace;">
                    Rp {{ number_format($transactionsByAccount->sum('total_debit'), 0, ',', '.') }}
                  </td>
                  <td class="text-right" style="font-family: 'Courier New', monospace;">
                    Rp {{ number_format($transactionsByAccount->sum('total_kredit'), 0, ',', '.') }}
                  </td>
                  <td class="text-right" style="font-family: 'Courier New', monospace;">
                    Rp {{ number_format($saldo, 0, ',', '.') }}
                  </td>
                </tr>
              </tfoot>
              @endif
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
$(document).ready(function() {
  var dpOpts = {
    format: 'dd/mm/yyyy',
    todayHighlight: true,
    autoclose: true,
  };
  $('#date_from_display').datepicker(dpOpts).on('changeDate', function(e) {
    var d = e.date;
    var yyyy = d.getFullYear();
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    var dd = String(d.getDate()).padStart(2, '0');
    $('#date_from_hidden').val(yyyy + '-' + mm + '-' + dd);
  });
  $('#date_to_display').datepicker(dpOpts).on('changeDate', function(e) {
    var d = e.date;
    var yyyy = d.getFullYear();
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    var dd = String(d.getDate()).padStart(2, '0');
    $('#date_to_hidden').val(yyyy + '-' + mm + '-' + dd);
  });
});
</script>

<script>
  // Bar Chart untuk Kas Bank per Akun
  var options = {
    chart: {
      type: 'bar',
      height: 350,
      stacked: false,
      toolbar: { show: true }
    },
    plotOptions: {
      bar: { 
        horizontal: false,
        columnWidth: '55%',
        endingShape: 'rounded'
      }
    },
    dataLabels: { enabled: false },
    series: [
      {
        name: 'Total Debit',
        data: {!! json_encode($transactionsByAccount->pluck('total_debit')) !!}
      },
      {
        name: 'Total Kredit',
        data: {!! json_encode($transactionsByAccount->pluck('total_kredit')) !!}
      }
    ],
    xaxis: {
      categories: {!! json_encode($transactionsByAccount->pluck('akun_name')) !!},
      labels: {
        rotate: -45,
        style: {
          fontSize: '11px'
        }
      }
    },
    yaxis: {
      labels: {
        formatter: function(value) {
          return 'Rp ' + value.toLocaleString('id-ID', {minimumFractionDigits: 0});
        }
      }
    },
    tooltip: {
      y: {
        formatter: function(value) {
          return 'Rp ' + value.toLocaleString('id-ID', {minimumFractionDigits: 0});
        }
      }
    },
    legend: {
      position: 'top',
      horizontalAlign: 'center'
    },
    colors: ['#28a745', '#dc3545']
  };

  var chart = new ApexCharts(document.querySelector("#kasBankApex"), options);
  chart.render();

  // Pie Chart untuk Komposisi Debit vs Kredit
  var optionsPie = {
    chart: {
      type: 'donut',
      height: 350,
      toolbar: { show: false }
    },
    labels: ['Total Debit', 'Total Kredit'],
    series: [{{ $transactionsByAccount->sum('total_debit') }}, {{ $transactionsByAccount->sum('total_kredit') }}],
    colors: ['#28a745', '#dc3545'],
    tooltip: {
      y: {
        formatter: function(val){ 
          return 'Rp ' + val.toLocaleString('id-ID', {minimumFractionDigits:0}) 
        }
      }
    },
    legend: { 
      position: 'bottom',
      horizontalAlign: 'center'
    },
    dataLabels: {
      enabled: true,
      formatter: function (val, opts) {
        return val.toFixed(1) + '%';
      }
    },
    plotOptions: {
      pie: {
        donut: {
          size: '65%',
          labels: {
            show: true,
            total: {
              show: true,
              label: 'Total Kas & Bank',
              fontSize: '14px',
              formatter: function (w) {
                let total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                return 'Rp ' + total.toLocaleString('id-ID', {minimumFractionDigits:0});
              }
            }
          }
        }
      }
    }
  };
  
  var pieChart = new ApexCharts(document.querySelector("#kasPieApex"), optionsPie);
  pieChart.render();
</script>

@endsection
