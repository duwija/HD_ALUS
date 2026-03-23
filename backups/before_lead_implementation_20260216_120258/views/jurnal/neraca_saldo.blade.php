@extends('layout.main')

@section('title', 'Laporan Neraca Saldo')

@section('content')
<style>
.card-header-custom {
  background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
  color: white;
  padding: 20px;
}
.card-title-custom {
  font-size: 24px;
  font-weight: 700;
  display: flex;
  align-items: center;
  margin: 0;
}
.card-subtitle {
  opacity: 0.9;
  font-size: 14px;
  margin-top: 5px;
}
.filter-label {
  font-weight: 600;
  color: #495057;
}
.table thead th {
  background: #4a90e2;
  color: white;
  font-weight: 600;
  padding: 12px;
}
.table tbody tr.group-header {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  font-weight: 700;
}
.table tbody tr.total-row {
  background: #343a40;
  color: white;
  font-weight: 700;
}
.amount-column {
  text-align: right;
  font-family: 'Courier New', monospace;
  font-weight: 500;
}
</style>

<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header-custom">
      <h3 class="card-title-custom">
        <i class="fas fa-balance-scale"></i>
        NERACA SALDO (TRIAL BALANCE)
      </h3>
      <div class="card-subtitle">
        Periode: {{ \Carbon\Carbon::parse($tanggalAwal)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}
      </div>
    </div>

    <div class="card-body">
      <!-- Filter Section -->
      <form action="{{ url('/jurnal/neracasaldo') }}" method="GET" class="mb-4">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label class="filter-label">
                <i class="fas fa-calendar-alt"></i> Tanggal Awal
              </label>
              <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" value="{{ $tanggalAwal }}">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label class="filter-label">
                <i class="fas fa-calendar-alt"></i> Tanggal Akhir
              </label>
              <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="{{ $tanggalAkhir }}">
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label class="filter-label">&nbsp;</label>
              <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-filter"></i> Filter
              </button>
            </div>
          </div>
          <div class="col-md-4 text-right">
            <div class="form-group">
              <label class="filter-label">&nbsp;</label>
              <div>
                <a href="{{ url('/jurnal/neracasaldo/export/excel') . '?' . http_build_query(request()->all()) }}" class="btn btn-success">
                  <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="{{ url('/jurnal/neracasaldo/export/pdf') . '?' . http_build_query(request()->all()) }}" class="btn btn-danger">
                  <i class="fas fa-file-pdf"></i> PDF
                </a>
              </div>
            </div>
          </div>
        </div>
      </form>

      <hr style="border-top: 2px solid #e9ecef;">

      <!-- Report Header -->
      <div class="report-header">
        <h3 class="font-weight-bold">
          {{ config('app.company', env('COMPANY','PT. ADIYASA ALUS SOLUSI')) }}
        </h3>
        <div><strong>TRIAL BALANCE</strong></div>
        <small>Per {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d F Y') }}</small>
      </div>

      <!-- Table -->
      <!-- Table -->
      <div class="table-responsive">
        <table class="table table-bordered neraca-table">
         <thead>
          <tr>
            <th rowspan="2" class="align-middle" style="width: 10%;">Kode Akun</th>
            <th rowspan="2" class="align-middle" style="width: 20%;">Nama Akun</th>
            <th colspan="2" class="text-center" style="width: 14%;">Saldo Awal</th>
            <th colspan="2" class="text-center" style="width: 14%;">Pergerakan</th>
            <th colspan="2" class="text-center" style="width: 14%;">Saldo Akhir</th>
            <th rowspan="2" class="text-center align-middle" style="width: 14%;">Balance</th>
          </tr>
          <tr>
            <th class="text-center">Debit</th>
            <th class="text-center">Kredit</th>
            <th class="text-center">Debit</th>
            <th class="text-center">Kredit</th>
            <th class="text-center">Debit</th>
            <th class="text-center">Kredit</th>
          </tr>
        </thead>
        <tbody>
          @php $fmt = fn($n) => number_format((float)$n, 2, ',', '.'); @endphp

          @forelse ($grouped as $groupName => $section)
          {{-- Header Grup --}}
          <tr class="table-active">
            <th colspan="9" class="text-left">
              <i class="fas fa-folder-open mr-2"></i>{{ $groupName }}
            </th>
          </tr>

          {{-- Rows per akun --}}
          @foreach ($section['rows'] as $item)
          @php $balance = $item['akhir_debit'] - $item['akhir_kredit']; @endphp
          <tr>
            <td class="text-center">{{ $item['kode'] }}</td>
            <td>{{ $item['nama'] }}</td>
            <td class="text-right">{{ $item['awal_debit'] > 0 ? $fmt($item['awal_debit']) : '-' }}</td>
            <td class="text-right">{{ $item['awal_kredit'] > 0 ? $fmt($item['awal_kredit']) : '-' }}</td>
            <td class="text-right">{{ $item['gerak_debit'] > 0 ? $fmt($item['gerak_debit']) : '-' }}</td>
            <td class="text-right">{{ $item['gerak_kredit'] > 0 ? $fmt($item['gerak_kredit']) : '-' }}</td>
            <td class="text-right">{{ $item['akhir_debit'] > 0 ? $fmt($item['akhir_debit']) : '-' }}</td>
            <td class="text-right">{{ $item['akhir_kredit'] > 0 ? $fmt($item['akhir_kredit']) : '-' }}</td>
            <td class="text-right">
              {!! $balance < 0 ? '(' . $fmt(abs($balance)) . ')' : $fmt($balance) !!}
            </td>
          </tr>
          @endforeach

          {{-- Subtotal per grup --}}
          @php $s = $section['subtotal']; $balSec = $s['akhir_debit'] - $s['akhir_kredit']; @endphp
          <tr class="font-weight-bold">
            <td class="text-right" colspan="2">
              <i class="fas fa-calculator mr-2"></i>Subtotal {{ $groupName }}
            </td>
            <td class="text-right">{{ $fmt($s['awal_debit']) }}</td>
            <td class="text-right">{{ $fmt($s['awal_kredit']) }}</td>
            <td class="text-right">{{ $fmt($s['gerak_debit']) }}</td>
            <td class="text-right">{{ $fmt($s['gerak_kredit']) }}</td>
            <td class="text-right">{{ $fmt($s['akhir_debit']) }}</td>
            <td class="text-right">{{ $fmt($s['akhir_kredit']) }}</td>
            <td class="text-right">
              {!! $balSec < 0 ? '(' . $fmt(abs($balSec)) . ')' : $fmt($balSec) !!}
            </td>
          </tr>
          @empty
          <tr><td colspan="9" class="text-center">Tidak ada data untuk periode ini.</td></tr>
          @endforelse
        </tbody>

        @php $grandBalance = $grand['akhir_debit'] - $grand['akhir_kredit']; @endphp
        <tfoot>
          <tr>
            <td class="text-right" colspan="2">
              <i class="fas fa-check-double mr-2"></i>GRAND TOTAL
            </td>
            <td class="text-right">{{ $fmt($grand['awal_debit']) }}</td>
            <td class="text-right">{{ $fmt($grand['awal_kredit']) }}</td>
            <td class="text-right">{{ $fmt($grand['gerak_debit']) }}</td>
            <td class="text-right">{{ $fmt($grand['gerak_kredit']) }}</td>
            <td class="text-right">{{ $fmt($grand['akhir_debit']) }}</td>
            <td class="text-right">{{ $fmt($grand['akhir_kredit']) }}</td>
            <td class="text-right">
              {!! $grandBalance < 0 ? '(' . $fmt(abs($grandBalance)) . ')' : $fmt($grandBalance) !!}
            </td>
          </tr>
        </tfoot>
      </table>
      </div>
    </div>
  </div>
</section>
@endsection
