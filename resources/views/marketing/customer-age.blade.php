@extends('layout.main')
@section('title','Customer Report')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><i class="fas fa-hourglass-half text-info"></i> Customer Report</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/">Home</a></li>
          <li class="breadcrumb-item"><a href="#">Marketing</a></li>
          <li class="breadcrumb-item active">Customer Report</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
<div class="container-fluid">

  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'age' ? 'active' : '' }}" href="{{ route('marketing.customer-age') }}">
        <i class="fas fa-hourglass-half"></i> Umur Customer
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'movement' ? 'active' : '' }}" href="{{ route('marketing.customer-age', ['tab' => 'movement']) }}">
        <i class="fas fa-chart-line"></i> Pergerakan Customer
      </a>
    </li>
  </ul>

  @if ($tab === 'movement')
    @include('marketing.partials.customer-movement')
  @else

  {{-- ── FILTER ─────────────────────────────────────────────────────────────── --}}
  <div class="card card-outline card-primary">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-filter"></i> Filter</h3>
      <div class="card-tools">
        <small class="text-muted">Umur customer = billing start s/d hari ini, atau s/d tanggal dihapus</small>
      </div>
    </div>
    <form method="GET" action="{{ route('marketing.customer-age') }}" id="filterForm">
    <div class="card-body pb-0">
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Billing Start</label>
          <div class="input-group">
            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            <div class="input-group-prepend input-group-append"><span class="input-group-text">s/d</span></div>
            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
          </div>
          <div class="mt-1">
            @foreach (['30' => '30 hari terakhir', '60' => '60 hari', '90' => '90 hari', 'month' => 'Bulan ini', 'year' => 'Tahun ini'] as $key => $label)
              <button type="button" class="btn btn-xs btn-outline-secondary date-quick" data-range="{{ $key }}">{{ $label }}</button>
            @endforeach
          </div>
          @if (($filters['age_min'] ?? '') !== '' || ($filters['age_max'] ?? '') !== '')
            {{-- Umur dari klik angka di tabel ringkasan --}}
            <input type="hidden" name="age_min" value="{{ $filters['age_min'] ?? '' }}">
            <input type="hidden" name="age_max" value="{{ $filters['age_max'] ?? '' }}">
            <div class="mt-1">
              <span class="badge badge-info">
                Umur {{ ($filters['age_max'] ?? '') === '' ? '≥ ' . $filters['age_min'] : ($filters['age_min'] ?? 0) . ' - ' . $filters['age_max'] }} hari
              </span>
              <a href="{{ route('marketing.customer-age', \Illuminate\Support\Arr::except($filters, ['age_min', 'age_max'])) }}" class="small text-danger ml-1">hapus</a>
            </div>
          @endif
        </div>
        <div class="form-group col-md-2">
          <label>Cakupan</label>
          <select name="scope" class="form-control">
            <option value="all" {{ ($filters['scope'] ?? 'all') == 'all' ? 'selected' : '' }}>Semua</option>
            <option value="active" {{ ($filters['scope'] ?? '') == 'active' ? 'selected' : '' }}>Belum dihapus</option>
            <option value="deleted" {{ ($filters['scope'] ?? '') == 'deleted' ? 'selected' : '' }}>Sudah dihapus</option>
          </select>
        </div>
        <div class="form-group col-md-2">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="">Semua</option>
            @foreach ($statuses as $id => $name)
              <option value="{{ $id }}" {{ (string)($filters['status'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
            @foreach ($deletedCategories as $key => $label)
              <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label>Invoice Belum Paid</label>
          <select name="unpaid" class="form-control">
            @foreach (['' => 'Semua', '0' => '0 (tidak ada)', '1' => '1 invoice', '2' => '2 invoice', '3plus' => '3 invoice atau lebih'] as $val => $label)
              <option value="{{ $val }}" {{ (string)($filters['unpaid'] ?? '') === (string)$val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>Plan</label>
          <select name="id_plan" class="form-control select2" data-placeholder="Semua plan">
            <option value="">Semua</option>
            @foreach ($plans as $id => $name)
              <option value="{{ $id }}" {{ (string)($filters['id_plan'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>Merchant</label>
          <select name="id_merchant" class="form-control">
            <option value="">Semua</option>
            @foreach ($merchants as $id => $name)
              <option value="{{ $id }}" {{ (string)($filters['id_merchant'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>Sales</label>
          <select name="id_sale" class="form-control select2" data-placeholder="Semua sales">
            <option value="">Semua</option>
            @foreach ($sales as $id => $name)
              <option value="{{ $id }}" {{ (string)($filters['id_sale'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
        </div>
        @if ($tags->isNotEmpty())
        <div class="form-group col-md-3">
          <label>Tag</label>
          <select name="id_tag[]" class="form-control select2" multiple data-placeholder="Semua tag">
            @foreach ($tags as $id => $name)
              <option value="{{ $id }}" {{ in_array((string)$id, array_map('strval', (array)($filters['id_tag'] ?? [])), true) ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
        </div>
        @endif
        <div class="form-group col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-search"></i> Tampilkan</button>
          <a href="{{ route('marketing.customer-age') }}" class="btn btn-default mr-2">Reset</a>
          <a href="{{ route('marketing.customer-age.export', $filters) }}" class="btn btn-success"><i class="fas fa-file-excel"></i> Excel</a>
        </div>
      </div>
    </div>
    </form>
  </div>

  {{-- ── PIE CHART (mengikuti semua filter termasuk umur) ───────────────────── --}}
  @php
    $fmtDate = function ($d) { return \Illuminate\Support\Carbon::parse($d)->format('d-m-Y'); };
    $dateFromF = $filters['date_from'] ?? '';
    $dateToF = $filters['date_to'] ?? '';
    if ($dateFromF === '' && $dateToF === '') {
        $periodLabel = 'semua billing start';
    } elseif ($dateToF === '') {
        $periodLabel = 'billing start sejak ' . $fmtDate($dateFromF);
    } elseif ($dateFromF === '') {
        $periodLabel = 'billing start s/d ' . $fmtDate($dateToF);
    } else {
        $periodLabel = 'billing start ' . $fmtDate($dateFromF) . ' s/d ' . $fmtDate($dateToF);
    }
    if (($filters['age_min'] ?? '') !== '' || ($filters['age_max'] ?? '') !== '') {
        $periodLabel .= ', umur ' . (($filters['age_max'] ?? '') === '' ? '≥ ' . $filters['age_min'] : ($filters['age_min'] ?? 0) . ' - ' . $filters['age_max']) . ' hari';
    }
  @endphp
  <div class="card card-outline card-success">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-chart-pie"></i> Komposisi Customer &mdash; {{ $periodLabel }}</h3>
      <div class="card-tools"><small class="text-muted">Mengikuti semua filter di atas</small></div>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h6 class="text-center font-weight-bold mb-0">Status Customer <small class="text-muted">(tanpa Potensial)</small></h6>
          <p class="text-center text-muted small mb-2" id="pieStatusTotal"></p>
          <div style="height: 300px; position: relative;"><canvas id="pieStatus"></canvas></div>
        </div>
        <div class="col-md-6">
          <h6 class="text-center font-weight-bold mb-0">Customer Dihapus</h6>
          <p class="text-center text-muted small mb-2" id="pieDeletedTotal"></p>
          <div style="height: 300px; position: relative;"><canvas id="pieDeleted"></canvas></div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── RINGKASAN UMUR x STATUS ────────────────────────────────────────────── --}}
  <div class="card card-outline card-info">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-table"></i> Ringkasan Umur &times; Status</h3>
      <div class="card-tools"><small class="text-muted">Mengikuti filter di atas. Klik angka untuk melihat daftarnya.</small></div>
    </div>
    <div class="card-body p-0 table-responsive">
      @php
        $colTotals = array_fill_keys(array_keys($summary['columns']), 0);
        $grandTotal = 0;
        $bucketRanges = [];
        foreach (\App\Http\Controllers\CustomerAgeReportController::AGE_BUCKETS as $i => $b) {
            $bucketRanges['b'.$i] = [$b[1], $b[2]];
        }
      @endphp
      <table class="table table-sm table-bordered table-hover text-center mb-0 export-table" data-export-title="Ringkasan Umur x Status - {{ $periodLabel }}">
        <thead class="thead-light">
          <tr>
            <th class="text-left">Umur</th>
            @foreach ($summary['columns'] as $key => $label)
              <th class="{{ str_starts_with($key, 'del_') ? 'table-danger' : '' }}">{{ $label }}</th>
            @endforeach
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($summary['buckets'] as $bucketKey => $bucketLabel)
            @php $rowTotal = array_sum($summary['matrix'][$bucketKey]); $grandTotal += $rowTotal; @endphp
            <tr>
              <th class="text-left">{{ $bucketLabel }}</th>
              @foreach ($summary['columns'] as $colKey => $colLabel)
                @php $val = $summary['matrix'][$bucketKey][$colKey]; $colTotals[$colKey] += $val; @endphp
                <td>
                  @if ($val > 0 && isset($bucketRanges[$bucketKey]))
                    <a href="{{ route('marketing.customer-age', array_merge($filters, [
                        'age_min' => $bucketRanges[$bucketKey][0],
                        'age_max' => $bucketRanges[$bucketKey][1],
                        'status'  => str_starts_with($colKey, 'st_') ? substr($colKey, 3) : $colKey,
                      ])) }}#customerTable">{{ number_format($val) }}</a>
                  @else
                    <span class="{{ $val ? '' : 'text-muted' }}">{{ number_format($val) }}</span>
                  @endif
                </td>
              @endforeach
              <th>{{ number_format($rowTotal) }}</th>
            </tr>
          @endforeach
        </tbody>
        <tfoot class="thead-light">
          <tr>
            <th class="text-left">Total</th>
            @foreach ($colTotals as $val)
              <th>{{ number_format($val) }}</th>
            @endforeach
            <th>{{ number_format($grandTotal) }}</th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  {{-- ── DAFTAR CUSTOMER ───────────────────────────────────────────────────── --}}
  <div class="card card-outline card-secondary" id="customerTable">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-users"></i> Daftar Customer</h3>
      <div class="card-tools">
        <a href="{{ route('marketing.customer-age.export', $filters) }}" class="btn btn-sm btn-success">
          <i class="fas fa-file-excel"></i> Export Excel <small>(semua hasil filter)</small>
        </a>
      </div>
    </div>
    <div class="card-body table-responsive">
      <table id="ageTable" class="table table-bordered table-striped table-sm" style="width:100%">
        <thead>
          <tr>
            <th>#</th>
            <th>Customer ID</th>
            <th>Nama</th>
            <th>Plan</th>
            <th>Merchant</th>
            <th>Sales</th>
            <th>Status</th>
            <th>Billing Start</th>
            <th>Umur</th>
            <th>Tgl Dihapus</th>
            <th>Alasan Hapus</th>
            <th>Inv. Belum Paid</th>
            <th>Total Tunggakan</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  @endif

</div>
</section>
@endsection

@section('footer-scripts')
@if ($tab === 'movement')
  @include('marketing.partials.customer-movement-js')
@else
<script>
$(function () {
  $('.select2').select2({ width: '100%', allowClear: true });

  // ── Pie chart status & customer dihapus ─────────────────────────────────
  (function () {
    var pie = @json($pie);
    // Palet tervalidasi untuk semua pasangan irisan; abu-abu = "tanpa keterangan"
    var statusColors = ['#2a78d6', '#1baf7a', '#eda100', '#4a3aa7'];
    var deletedColors = ['#e34948', '#008300', '#8a8985'];

    function drawPie(canvasId, totalId, values, colors) {
      var labels = Object.keys(values);
      var data = labels.map(function (k) { return values[k]; });
      var total = data.reduce(function (a, v) { return a + v; }, 0);
      $('#' + totalId).text('Total: ' + total.toLocaleString('id-ID') + ' customer');
      var canvas = document.getElementById(canvasId);
      if (!total) {
        $(canvas).parent().html('<div class="d-flex h-100 align-items-center justify-content-center text-muted">Tidak ada data untuk filter ini</div>');
        return;
      }
      function pct(v) { return (v * 100 / total).toFixed(1).replace('.', ',') + '%'; }
      new Chart(canvas.getContext('2d'), {
        type: 'pie',
        data: {
          labels: labels,
          datasets: [{
            data: data,
            backgroundColor: labels.map(function (l, i) { return colors[i] || '#8a8985'; }),
            borderColor: '#ffffff',
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          legend: {
            position: 'right',
            labels: {
              boxWidth: 12,
              fontColor: '#212529',
              // Nama + jumlah + persen di legend: identitas tidak hanya dari warna
              generateLabels: function (chart) {
                return labels.map(function (l, i) {
                  return {
                    text: l + ': ' + data[i].toLocaleString('id-ID') + ' (' + pct(data[i]) + ')',
                    fillStyle: chart.data.datasets[0].backgroundColor[i],
                    strokeStyle: '#ffffff',
                    lineWidth: 1,
                    hidden: false,
                    index: i
                  };
                });
              }
            },
            onClick: function () {}
          },
          tooltips: {
            callbacks: {
              label: function (item) {
                return labels[item.index] + ': ' + data[item.index].toLocaleString('id-ID') + ' (' + pct(data[item.index]) + ')';
              }
            }
          }
        }
      });
    }

    drawPie('pieStatus', 'pieStatusTotal', pie.status, statusColors);
    drawPie('pieDeleted', 'pieDeletedTotal', pie.deleted, deletedColors);
  })();

  $('.date-quick').on('click', function () {
    function ymd(d) {
      return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    var range = String($(this).data('range'));
    var today = new Date();
    var from;
    if (range === 'month') {
      from = new Date(today.getFullYear(), today.getMonth(), 1);
    } else if (range === 'year') {
      from = new Date(today.getFullYear(), 0, 1);
    } else {
      from = new Date(today);
      from.setDate(from.getDate() - parseInt(range, 10));
    }
    $('#date_from').val(ymd(from));
    $('#date_to').val(ymd(today));
  });

  @include('marketing.partials.export-table-js')

  $('#ageTable').DataTable({
    processing: true,
    serverSide: true,
    pageLength: 25,
    order: [[7, 'desc']],
    ajax: {
      url: '{{ route('marketing.customer-age.data') }}',
      data: function (d) {
        return $.extend(d, @json($filters));
      }
    },
    columns: [
      { data: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'customer_id', name: 'customers.customer_id' },
      { data: 'name', name: 'customers.name' },
      { data: 'plan', orderable: false, searchable: false },
      { data: 'merchant', orderable: false, searchable: false },
      { data: 'sale', orderable: false, searchable: false },
      { data: 'status', orderable: false, searchable: false },
      { data: 'billing_start', name: 'customers.billing_start', searchable: false },
      { data: 'age_days', name: 'age_days', searchable: false },
      { data: 'deleted_at', name: 'customers.deleted_at', searchable: false },
      { data: 'deletion_reason', orderable: false, searchable: false },
      { data: 'unpaid_count', name: 'unpaid_count', searchable: false },
      { data: 'unpaid_total', name: 'unpaid_total', searchable: false }
    ]
  });
});
</script>
@endif
@endsection
