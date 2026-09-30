@php
  $fmtDate = function ($d) { return \Illuminate\Support\Carbon::parse($d)->format('d-m-Y'); };
  $totals = $movement['totals'];
  $net = $movement['net'];
@endphp

{{-- ── FILTER PERIODE ─────────────────────────────────────────────────────── --}}
<div class="card card-outline card-primary">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-filter"></i> Periode</h3>
    <div class="card-tools">
      <small class="text-muted">Periode &le; 1 bulan ditampilkan harian, lebih dari 1 bulan ditampilkan bulanan</small>
    </div>
  </div>
  <form method="GET" action="{{ route('marketing.customer-age') }}">
  <input type="hidden" name="tab" value="movement">
  <div class="card-body pb-0">
    <div class="form-row">
      <div class="form-group col-md-4">
        <label>Periode</label>
        <div class="input-group">
          <input type="date" name="period_from" id="period_from" class="form-control" value="{{ $periodFrom }}">
          <div class="input-group-prepend input-group-append"><span class="input-group-text">s/d</span></div>
          <input type="date" name="period_to" id="period_to" class="form-control" value="{{ $periodTo }}">
        </div>
        <div class="mt-1">
          @foreach (['month' => 'Bulan ini', '3' => '3 bulan', '6' => '6 bulan', '12' => '12 bulan', 'year' => 'Tahun ini'] as $key => $label)
            <button type="button" class="btn btn-xs btn-outline-secondary period-quick" data-range="{{ $key }}">{{ $label }}</button>
          @endforeach
        </div>
      </div>
      <div class="form-group col-md-2">
        <label>Plan</label>
        <select name="id_plan" class="form-control select2" data-placeholder="Semua plan">
          <option value="">Semua</option>
          @foreach ($plans as $id => $name)
            <option value="{{ $id }}" {{ (string)($filters['id_plan'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group col-md-2">
        <label>Merchant</label>
        <select name="id_merchant" class="form-control">
          <option value="">Semua</option>
          @foreach ($merchants as $id => $name)
            <option value="{{ $id }}" {{ (string)($filters['id_merchant'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
          @endforeach
        </select>
      </div>
      @if ($tags->isNotEmpty())
      <div class="form-group col-md-2">
        <label>Tag</label>
        <select name="id_tag[]" class="form-control select2" multiple data-placeholder="Semua tag">
          @foreach ($tags as $id => $name)
            <option value="{{ $id }}" {{ in_array((string)$id, array_map('strval', (array)($filters['id_tag'] ?? [])), true) ? 'selected' : '' }}>{{ $name }}</option>
          @endforeach
        </select>
      </div>
      @endif
      <div class="form-group col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-search"></i> Tampilkan</button>
        <a href="{{ route('marketing.customer-age', ['tab' => 'movement']) }}" class="btn btn-default">Reset</a>
      </div>
    </div>
  </div>
  </form>
</div>

{{-- ── TOTAL PERIODE ──────────────────────────────────────────────────────── --}}
<div class="row">
  <div class="col-6 col-md">
    <div class="small-box bg-primary">
      <div class="inner"><h3>{{ number_format($totals['new']) }}</h3><p>Customer Baru</p></div>
      <div class="icon"><i class="fas fa-user-plus"></i></div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="small-box bg-danger">
      <div class="inner"><h3>{{ number_format($totals['del_terminate']) }}</h3><p>Berhenti</p></div>
      <div class="icon"><i class="fas fa-user-minus"></i></div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="small-box bg-success">
      <div class="inner"><h3>{{ number_format($totals['del_cancel']) }}</h3><p>Tidak Jadi</p></div>
      <div class="icon"><i class="fas fa-user-times"></i></div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="small-box bg-secondary">
      <div class="inner"><h3>{{ number_format($totals['del_other']) }}</h3><p>Dihapus Tanpa Keterangan</p></div>
      <div class="icon"><i class="fas fa-user-slash"></i></div>
    </div>
  </div>
  <div class="col-12 col-md">
    <div class="small-box bg-light border">
      <div class="inner">
        <h3>{{ $net > 0 ? '+' : '' }}{{ number_format($net) }}</h3>
        <p>Pertumbuhan Bersih <small class="text-muted d-block">Baru &minus; Berhenti &minus; Tanpa Ket.</small></p>
      </div>
      <div class="icon"><i class="fas fa-balance-scale"></i></div>
    </div>
  </div>
</div>

{{-- ── LINE CHART (dua chart: skala customer baru jauh lebih besar dari yang dihapus) ── --}}
<div class="card card-outline card-info">
  <div class="card-header">
    <h3 class="card-title">
      <i class="fas fa-chart-line"></i> Pergerakan Customer {{ $movement['granularity'] }}
      &mdash; {{ $fmtDate($periodFrom) }} s/d {{ $fmtDate($periodTo) }}
    </h3>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-lg-6">
        <h6 class="font-weight-bold text-center">Customer Baru</h6>
        <div style="height: 300px; position: relative;"><canvas id="movementNewChart"></canvas></div>
      </div>
      <div class="col-lg-6">
        <h6 class="font-weight-bold text-center">Customer Dihapus</h6>
        <div style="height: 300px; position: relative;"><canvas id="movementDeletedChart"></canvas></div>
      </div>
    </div>
  </div>
</div>

{{-- ── TABEL PER PERIODE ──────────────────────────────────────────────────── --}}
<div class="card card-outline card-secondary">
  <div class="card-header"><h3 class="card-title"><i class="fas fa-table"></i> Rincian {{ $movement['granularity'] === 'harian' ? 'per Hari' : 'per Bulan' }}</h3></div>
  <div class="card-body p-0 table-responsive" style="max-height: 420px;">
    <table class="table table-sm table-bordered table-hover text-center mb-0 export-table"
           data-export-title="Pergerakan Customer {{ $movement['granularity'] }} - {{ $fmtDate($periodFrom) }} sd {{ $fmtDate($periodTo) }}">
      <thead class="thead-light" style="position: sticky; top: 0;">
        <tr>
          <th class="text-left">{{ $movement['granularity'] === 'harian' ? 'Tanggal' : 'Bulan' }}</th>
          <th>Customer Baru</th>
          <th>Berhenti</th>
          <th>Tidak Jadi</th>
          <th>Tanpa Keterangan</th>
          <th>Pertumbuhan Bersih</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($movement['periods'] as $key => $label)
          @php $rowNet = $movement['series']['new'][$key] - $movement['series']['del_terminate'][$key] - $movement['series']['del_other'][$key]; @endphp
          <tr>
            <th class="text-left">{{ $label }}</th>
            @foreach (['new', 'del_terminate', 'del_cancel', 'del_other'] as $s)
              <td class="{{ $movement['series'][$s][$key] ? '' : 'text-muted' }}">{{ number_format($movement['series'][$s][$key]) }}</td>
            @endforeach
            <td class="{{ $rowNet < 0 ? 'text-danger' : '' }}">{{ $rowNet > 0 ? '+' : '' }}{{ number_format($rowNet) }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot class="thead-light">
        <tr>
          <th class="text-left">Total</th>
          <th>{{ number_format($totals['new']) }}</th>
          <th>{{ number_format($totals['del_terminate']) }}</th>
          <th>{{ number_format($totals['del_cancel']) }}</th>
          <th>{{ number_format($totals['del_other']) }}</th>
          <th class="{{ $net < 0 ? 'text-danger' : '' }}">{{ $net > 0 ? '+' : '' }}{{ number_format($net) }}</th>
        </tr>
      </tfoot>
    </table>
  </div>
  <div class="card-footer small text-muted">
    Customer baru = billing start dalam periode, tidak termasuk Potensial dan yang dihapus "Tidak Jadi".
    Company_Properti tidak dihitung di semua angka tab ini.
    Berhenti / Tidak Jadi / Tanpa Keterangan = tanggal dihapus dalam periode.
  </div>
</div>
