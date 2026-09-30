@extends('layout.main')
@section('title','Hasil Konversi Lead')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><i class="fas fa-funnel-dollar text-warning"></i> Lead Summary & Pipeline</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/">Home</a></li>
          <li class="breadcrumb-item"><a href="#">Marketing</a></li>
          <li class="breadcrumb-item"><a href="{{ route('marketing.lead-summary') }}">Lead Summary</a></li>
          <li class="breadcrumb-item active">Hasil Konversi</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
<div class="container-fluid">

  @include('marketing.partials.lead-summary-tabs', ['activeTab' => 'conversion'])

  {{-- ── FILTER BAR ─────────────────────────────────────────────────────────── --}}
  <div class="card card-outline card-primary mb-3">
    <div class="card-body p-2">
      <form method="GET" action="{{ route('marketing.lead-conversion') }}" class="form-inline flex-wrap">
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Berdasarkan</label>
          <select name="basis" class="form-control form-control-sm">
            <option value="created" {{ $basis === 'created' ? 'selected' : '' }}>Tanggal Lead Masuk</option>
            <option value="billing" {{ $basis === 'billing' ? 'selected' : '' }}>Billing Start</option>
          </select>
        </div>
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Dari</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start }}">
        </div>
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Sampai</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end }}">
        </div>
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Sales</label>
          <select name="id_sale" class="form-control form-control-sm select2" style="min-width:220px;">
            <option value="">Semua Sales</option>
            @foreach($allSales as $sale)
              <option value="{{ $sale->id }}" {{ $filterSale == $sale->id ? 'selected' : '' }}>{{ $sale->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Plan</label>
          <select name="id_plan" class="form-control form-control-sm select2" style="min-width:180px;">
            <option value="">Semua Plan</option>
            @foreach($plans as $id => $name)
              <option value="{{ $id }}" {{ (string)($filters['id_plan'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Merchant</label>
          <select name="id_merchant" class="form-control form-control-sm">
            <option value="">Semua Merchant</option>
            @foreach($merchants as $id => $name)
              <option value="{{ $id }}" {{ (string)($filters['id_merchant'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Status</label>
          <select name="status" class="form-control form-control-sm">
            <option value="">Semua Status</option>
            @foreach($statusOptions as $val => $label)
              <option value="{{ $val }}" {{ $filterStatus === (string)$val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Invoice Belum Bayar</label>
          <select name="unpaid" class="form-control form-control-sm">
            @foreach (['' => 'Semua', '0' => '0 (lunas)', '1' => '1 invoice', '2' => '2 invoice', '3plus' => '3 invoice atau lebih'] as $val => $label)
              <option value="{{ $val }}" {{ $filterUnpaid === (string)$val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        @if ($hasTagTables)
        <div class="form-group mr-2 mb-1">
          <label class="mr-1 text-muted small">Tag</label>
          @if ($tags->isNotEmpty())
            <select name="id_tag[]" class="form-control form-control-sm select2" multiple data-placeholder="Semua Tag" style="min-width:180px;">
              @foreach($tags as $id => $name)
                <option value="{{ $id }}" {{ in_array((string)$id, array_map('strval', (array)($filters['id_tag'] ?? [])), true) ? 'selected' : '' }}>{{ $name }}</option>
              @endforeach
            </select>
          @else
            <select class="form-control form-control-sm" disabled>
              <option>Belum ada tag</option>
            </select>
          @endif
        </div>
        @endif
        <button type="submit" class="btn btn-primary btn-sm mb-1 mr-1"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('marketing.lead-conversion') }}" class="btn btn-secondary btn-sm mb-1"><i class="fas fa-undo"></i> Reset</a>
        <span class="ml-2 text-muted small align-self-center">
          {{ $basis === 'billing' ? 'Billing start' : 'Lead masuk' }}
          {{ \Carbon\Carbon::parse($start)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($end)->format('d M Y') }}
        </span>
      </form>
    </div>
  </div>

  {{-- ── KOTAK RINGKASAN ───────────────────────────────────────────────────── --}}
  <div class="row">
    <div class="col-6 col-md">
      <div class="small-box bg-primary">
        <div class="inner"><h3>{{ number_format($total) }}</h3><p>Lead Terkonversi</p></div>
        <div class="icon"><i class="fas fa-handshake"></i></div>
      </div>
    </div>
    <div class="col-6 col-md">
      <div class="small-box bg-success">
        <div class="inner"><h3>{{ number_format($counts['active']) }}</h3><p>Masih Active</p></div>
        <div class="icon"><i class="fas fa-check-circle"></i></div>
      </div>
    </div>
    <div class="col-6 col-md">
      <div class="small-box bg-warning">
        <div class="inner"><h3>{{ number_format($counts['inactive']) }}</h3><p>Inactive / Block</p></div>
        <div class="icon"><i class="fas fa-pause-circle"></i></div>
      </div>
    </div>
    <div class="col-6 col-md">
      <div class="small-box bg-danger">
        <div class="inner"><h3>{{ number_format($counts['stopped']) }}</h3><p>Berhenti</p></div>
        <div class="icon"><i class="fas fa-user-minus"></i></div>
      </div>
    </div>
    <div class="col-6 col-md">
      <div class="small-box bg-light border">
        <div class="inner">
          <h3>{{ $retention === null ? '-' : str_replace('.', ',', $retention) . '%' }}</h3>
          <p>Retensi <small class="text-muted d-block">belum berhenti / terkonversi</small></p>
        </div>
        <div class="icon"><i class="fas fa-shield-alt"></i></div>
      </div>
    </div>
    <div class="col-6 col-md">
      <div class="small-box bg-light border">
        <div class="inner">
          <h3>{{ $avgDays === null ? '-' : str_replace('.', ',', $avgDays) }}</h3>
          <p>Rata-rata Hari <small class="text-muted d-block">lead masuk &rarr; konversi</small></p>
        </div>
        <div class="icon"><i class="fas fa-stopwatch"></i></div>
      </div>
    </div>
  </div>

  @if ($companyPropertyCount > 0)
    <div class="alert alert-light border small py-2">
      <i class="fas fa-info-circle text-info"></i>
      Catatan: ada <strong>{{ $companyPropertyCount }} Company_Properti</strong> terkonversi di periode ini,
      tidak termasuk dalam hitungan di atas.
    </div>
  @endif

  {{-- ── CHART PER PERIODE ─────────────────────────────────────────────────── --}}
  <div class="card card-outline card-info">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-chart-line"></i> Lead Terkonversi {{ $chart['granularity'] }}
        <small class="text-muted">&mdash; menurut {{ $basis === 'billing' ? 'billing start' : 'tanggal lead masuk' }}</small>
      </h3>
      <div class="card-tools">
        <small class="text-muted">Range &le; 1 bulan harian, lebih dari 1 bulan bulanan</small>
      </div>
    </div>
    <div class="card-body">
      <div style="height: 300px; position: relative;"><canvas id="conversionChart"></canvas></div>
      <details class="mt-2">
        <summary class="small text-muted">Lihat angka per {{ $chart['granularity'] === 'harian' ? 'hari' : 'bulan' }}</summary>
        <div class="table-responsive mt-2" style="max-height: 300px;">
          <table class="table table-sm table-bordered text-center mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-left">{{ $chart['granularity'] === 'harian' ? 'Tanggal' : 'Bulan' }}</th>
                <th>Terkonversi</th><th>Inactive / Block</th><th>Berhenti</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($chart['periods'] as $key => $label)
                <tr>
                  <th class="text-left">{{ $label }}</th>
                  @foreach (['total', 'inactive', 'stopped'] as $s)
                    <td class="{{ $chart['series'][$s][$key] ? '' : 'text-muted' }}">{{ $chart['series'][$s][$key] }}</td>
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </details>
    </div>
  </div>

  {{-- ── TABEL ─────────────────────────────────────────────────────────────── --}}
  <div class="card card-outline card-secondary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-list"></i> Daftar Lead Terkonversi</h3></div>
    <div class="card-body table-responsive">
      <table id="conversionTable" class="table table-bordered table-striped table-sm" style="width:100%">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Sales</th>
            <th>Plan</th>
            <th>Merchant</th>
            <th>Tag</th>
            <th>Invoice Belum Bayar</th>
            <th>Lead Masuk</th>
            <th>Billing Start</th>
            <th>Dikonversi</th>
            <th>Lama Konversi</th>
            <th>Status Sekarang</th>
            <th>Tgl Berhenti</th>
            <th>Alasan</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($leads as $lead)
            @php
              $statusName = $lead->status_name->name ?? '-';
              if ($lead->outcome === 'stopped') {
                  $isTerminate = in_array($lead->deletion_type ?? null, \App\Http\Controllers\LeadConversionController::TERMINATE_TYPES, true);
                  $badge = $isTerminate ? 'badge-danger' : 'badge-dark';
                  $label = $isTerminate ? 'Berhenti' : 'Dihapus - Tanpa Keterangan';
              } else {
                  $badge = ['active' => 'badge-success', 'inactive' => 'badge-warning'][$lead->outcome] ?? 'badge-secondary';
                  $label = $statusName;
              }
            @endphp
            <tr>
              <td>
                <a href="{{ url('customer/' . $lead->id) }}">{{ $lead->name }}</a>
                <br><small class="text-muted">{{ $lead->customer_id }}</small>
              </td>
              <td>{{ $lead->sale_name->name ?? '-' }}</td>
              <td>{{ $lead->plan_name->name ?? '-' }}</td>
              <td>{{ $lead->merchant_name->name ?? '-' }}</td>
              <td>
                @if ($lead->relationLoaded('tags'))
                  @forelse ($lead->tags as $tag)
                    <span class="badge badge-light border">{{ $tag->name }}</span>
                  @empty
                    -
                  @endforelse
                @else
                  -
                @endif
              </td>
              <td data-order="{{ $lead->invoice_unpaid }}">
                @if ($lead->invoice_unpaid > 0)
                  <span class="badge badge-danger">{{ $lead->invoice_unpaid }}</span>
                @else
                  <span class="text-muted">0</span>
                @endif
              </td>
              <td data-order="{{ optional($lead->created_at)->format('Y-m-d H:i') }}">{{ optional($lead->created_at)->format('d M Y') ?? '-' }}</td>
              <td data-order="{{ $lead->billing_start }}">{{ $lead->billing_start ? \Carbon\Carbon::parse($lead->billing_start)->format('d M Y') : '-' }}</td>
              <td data-order="{{ $lead->converted_on->format('Y-m-d H:i') }}">{{ $lead->converted_on->format('d M Y') }}</td>
              <td data-order="{{ $lead->days_to_convert }}">{{ $lead->days_to_convert === null ? '-' : $lead->days_to_convert . ' hari' }}</td>
              <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
              <td data-order="{{ optional($lead->deleted_at)->format('Y-m-d H:i') }}">{{ optional($lead->deleted_at)->format('d M Y') ?? '-' }}</td>
              <td><small>{{ $lead->deletion_reason ?? '' }}</small></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-footer small text-muted">
      Hanya lead yang dikonversi lewat tombol Convert (punya tanggal konversi). Lead yang dihapus "Tidak Jadi" dan Company_Properti tidak dihitung.
      Invoice Belum Bayar = jumlah invoice yang belum dibayar.
      Status adalah kondisi saat ini.
    </div>
  </div>

</div>
</section>
@endsection

@section('footer-scripts')
<script>
$(function () {
  $('.select2').select2({ width: 'resolve' });
  // ── Line chart lead terkonversi ─────────────────────────────────────────
  (function () {
    var chart = @json($chart);
    var keys = Object.keys(chart.periods);
    var lines = [
      { key: 'total',    label: 'Terkonversi',      color: '#2a78d6', dash: [],     point: 'circle' },
      { key: 'inactive', label: 'Inactive / Block', color: '#eda100', dash: [6, 4], point: 'triangle' },
      { key: 'stopped',  label: 'Berhenti',         color: '#e34948', dash: [2, 3], point: 'rect' }
    ];
    var maxValue = Math.max.apply(null, lines.map(function (l) {
      return Math.max.apply(null, keys.map(function (k) { return chart.series[l.key][k]; }));
    }));

    new Chart(document.getElementById('conversionChart').getContext('2d'), {
      type: 'line',
      data: {
        labels: keys.map(function (k) { return chart.periods[k]; }),
        datasets: lines.map(function (l) {
          return {
            label: l.label,
            data: keys.map(function (k) { return chart.series[l.key][k]; }),
            borderColor: l.color,
            backgroundColor: l.color,
            borderWidth: 2,
            borderDash: l.dash,
            fill: false,
            lineTension: 0,
            pointStyle: l.point,
            pointRadius: keys.length > 40 ? 2 : 4,
            pointHoverRadius: 6,
            pointHitRadius: 10,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 1
          };
        })
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: { position: 'bottom', labels: { boxWidth: 12, fontColor: '#212529' } },
        tooltips: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: function (item, data) {
              return data.datasets[item.datasetIndex].label + ': ' + Number(item.yLabel).toLocaleString('id-ID');
            }
          }
        },
        hover: { mode: 'index', intersect: false },
        scales: {
          xAxes: [{ gridLines: { display: false }, ticks: { fontColor: '#52514e', autoSkip: true, maxRotation: 0 } }],
          yAxes: [{
            gridLines: { color: 'rgba(0,0,0,0.06)', zeroLineColor: 'rgba(0,0,0,0.2)' },
            ticks: {
              beginAtZero: true,
              fontColor: '#52514e',
              // Jumlah lead selalu bulat; nilai kecil pakai langkah 1
              stepSize: maxValue <= 10 ? 1 : undefined,
              suggestedMax: maxValue === 0 ? 5 : undefined,
              callback: function (v) { return v.toLocaleString('id-ID'); }
            }
          }]
        }
      }
    });
  })();

  // Export: nama/ID dipisah "|", beberapa tag dipisah koma, tag HTML dibuang
  var exportTitle = @json('Hasil Konversi Lead - ' . ($basis === 'billing' ? 'Billing start' : 'Lead masuk') . ' '
      . \Carbon\Carbon::parse($start)->format('d-m-Y') . ' sd ' . \Carbon\Carbon::parse($end)->format('d-m-Y'));
  var exportOptions = {
    format: {
      body: function (data) {
        var html = String(data).replace(/<br\s*\/?>/gi, ' | ').replace(/<\/span>\s*<span/gi, '</span>, <span');
        return $('<div>').html(html).text().replace(/\s+/g, ' ').trim();
      }
    }
  };
  $('#conversionTable').DataTable({
    dom: "<'row'<'col-sm-6'B><'col-sm-6'f>>rt<'row'<'col-sm-5'i><'col-sm-7'p>>",
    pageLength: 25,
    order: [[8, 'desc']],
    buttons: [
      { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', title: exportTitle, exportOptions: exportOptions },
      { extend: 'csvHtml5', text: '<i class="fas fa-file-csv"></i> CSV', title: exportTitle, exportOptions: exportOptions },
      { extend: 'print', text: '<i class="fas fa-print"></i> Print', title: exportTitle, exportOptions: exportOptions }
    ],
    language: { emptyTable: 'Tidak ada lead terkonversi di periode ini', search: 'Cari:' }
  });
});
</script>
@endsection
