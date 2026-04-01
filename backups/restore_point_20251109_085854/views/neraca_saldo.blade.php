@extends('layout.main')

@section('title', 'Laporan Neraca Saldo')

@section('content')
<style>
/* Modern Card Header */
.card-header-custom {
  background: #4a90e2;
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

.card-title-custom i {
  margin-right: 10px;
  font-size: 28px;
}

.card-subtitle {
  opacity: 0.9;
  font-size: 14px;
  margin-top: 5px;
}

/* Filter Section */
.filter-label {
  font-weight: 600;
  color: #495057;
  margin-bottom: 8px;
}

.filter-label i {
  margin-right: 5px;
  color: #667eea;
}

/* Button Styling */
.btn-primary {
  background: #4a90e2;
  border: none;
  transition: all 0.3s;
}

.btn-primary:hover {
  background: #357abd;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(74, 144, 226, 0.3);
}

.btn-success {
  transition: all 0.3s;
}

.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

.btn-danger {
  transition: all 0.3s;
}

.btn-danger:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}

/* Report Header */
.report-header {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  text-align: center;
}

.report-header h3 {
  margin: 0;
  color: #2c3e50;
}

.report-header small {
  color: #5a6c7d;
}

/* Table Styling */
.neraca-table {
  font-size: 14px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.neraca-table thead th {
  background: #4a90e2;
  color: white;
  font-weight: 600;
  border: none;
  padding: 12px;
  text-align: center;
  vertical-align: middle;
}

.neraca-table tbody td {
  padding: 10px;
  vertical-align: middle;
}

/* Group Header Row */
.neraca-table .table-active th {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 700;
  font-size: 15px;
  padding: 12px;
}

/* Subtotal Row */
.neraca-table .font-weight-bold {
  background: #f8f9fa;
  border-top: 2px solid #dee2e6;
  border-bottom: 2px solid #dee2e6;
}

/* Grand Total Row */
.neraca-table tfoot tr {
  background: #343a40 !important;
  color: white !important;
  font-weight: 700;
  border-top: 3px solid #dee2e6;
}

.neraca-table tfoot td {
  padding: 15px 10px;
  font-size: 15px;
}

/* Amount Colors */
.text-right {
  font-family: 'Courier New', monospace;
}

/* Responsive */
@media (max-width: 768px) {
  .card-title-custom {
    font-size: 18px;
  }
  
  .neraca-table {
    font-size: 12px;
  }
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
