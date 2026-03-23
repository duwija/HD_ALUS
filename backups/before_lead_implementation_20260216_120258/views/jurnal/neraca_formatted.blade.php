@extends('layout.main')

@section('title', 'Laporan Neraca')

@section('content')
<style>
/* Modern Card Header */
.card-header-custom {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  margin-bottom: 25px;
  text-align: center;
}

.report-header h3 {
  margin: 0;
  color: #2c3e50;
}

.report-header small {
  color: #5a6c7d;
}

/* Section Headers */
.section-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 12px 15px;
  border-radius: 6px;
  margin-bottom: 15px;
  font-size: 18px;
  font-weight: 700;
  display: flex;
  align-items: center;
}

.section-header i {
  margin-right: 10px;
}

/* Subsection Headers */
.subsection-header {
  background: #4a90e2;
  color: white;
  padding: 8px 12px;
  border-radius: 4px;
  margin-top: 15px;
  margin-bottom: 10px;
  font-size: 15px;
  font-weight: 600;
}

/* Table Styling */
.neraca-table {
  font-size: 14px;
  margin-bottom: 10px;
}

.neraca-table td {
  padding: 8px 10px;
  border: none;
  border-bottom: 1px solid #f0f0f0;
}

.neraca-table tr:hover {
  background-color: #f8f9fa;
}

.neraca-table .font-weight-bold {
  background: #e9ecef;
  border-top: 2px solid #dee2e6;
  border-bottom: 2px solid #dee2e6;
}

/* Total Row */
.total-row {
  background: #343a40 !important;
  color: white !important;
  font-weight: 700;
  font-size: 15px;
}

.total-row td {
  padding: 12px 10px !important;
  border: none !important;
}

/* Amount Styling */
.text-right {
  font-family: 'Courier New', monospace;
}

/* Balance Cards */
.balance-cards {
  display: flex;
  gap: 15px;
  margin-top: 20px;
  margin-bottom: 20px;
}

.balance-card {
  flex: 1;
  padding: 20px;
  border-radius: 8px;
  color: white;
  text-align: center;
}

.balance-card.assets {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.balance-card.liabilities {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.balance-card-label {
  font-size: 14px;
  opacity: 0.9;
  margin-bottom: 5px;
}

.balance-card-value {
  font-size: 24px;
  font-weight: 700;
}

/* Column Separator */
.col-separator {
  border-right: 2px solid #e9ecef;
  padding-right: 20px;
}
</style>

<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header-custom">
      <h3 class="card-title-custom">
        <i class="fas fa-file-invoice-dollar"></i>
        NERACA (BALANCE SHEET)
      </h3>
      <div class="card-subtitle">Laporan posisi keuangan perusahaan</div>
    </div>

    <div class="card-body">
      <!-- Filter Section -->
      <form action="{{ url('/jurnal/neraca-formatted') }}" method="GET" class="mb-4">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label class="filter-label">
                <i class="fas fa-calendar-alt"></i> Tanggal Akhir
              </label>
              <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="{{ $tanggalAkhir }}" required>
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
          <div class="col-md-7 text-right">
            <div class="form-group">
              <label class="filter-label">&nbsp;</label>
              <div>
                <a href="{{ url('/jurnal/neraca-formatted/export/excel') . '?' . http_build_query(['tanggal_akhir' => $tanggalAkhir]) }}" class="btn btn-success">
                  <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="{{ url('/jurnal/neraca-formatted/export/pdf') . '?' . http_build_query(['tanggal_akhir' => $tanggalAkhir]) }}" class="btn btn-danger">
                  <i class="fas fa-file-pdf"></i> PDF
                </a>
              </div>
            </div>
          </div>
        </div>
      </form>

      <hr style="border-top: 2px solid #e9ecef;">

      @php
      $fmt = fn($n) => number_format((float)$n, 2, ',', '.');
      @endphp

      <!-- Report Header -->
      <div class="report-header">
        <h3 class="font-weight-bold">
          {{ config('app.company', env('COMPANY','PT. ADIYASA ALUS SOLUSI')) }}
        </h3>
        <div><strong>NERACA (BALANCE SHEET)</strong></div>
        <small>Per {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d F Y') }}</small>
      </div>

      <!-- Balance Cards -->
      <div class="balance-cards">
        <div class="balance-card assets">
          <div class="balance-card-label">
            <i class="fas fa-coins"></i> Total Aset
          </div>
          <div class="balance-card-value">
            Rp {{ $fmt($totalAset) }}
          </div>
        </div>
        
        <div class="balance-card liabilities">
          <div class="balance-card-label">
            <i class="fas fa-balance-scale"></i> Total Liabilitas & Modal
          </div>
          <div class="balance-card-value">
            Rp {{ $fmt($totalLiabilitas + $totalEkuitas) }}
          </div>
        </div>
      </div>

      {{-- KIRI: ASET --}}
      <div class="col-md-5">
        <h5 class="mb-2"><strong>Aset</strong></h5>

        @foreach ($aset as $judul => $bagian)
        @if (!empty($bagian['rows']))
        <h6 class="mt-3"><strong>{{ $judul }}</strong></h6>
        <table class="table table-sm">
          <tbody>
            @foreach ($bagian['rows'] as $r)
            <tr>
              <td style="width: 18%">{{ $r['kode'] }}</td>
              <td>{{ $r['nama'] }}</td>
              <td class="text-right" style="width: 20%">
                {!! $r['nilai'] < 0 ? '(' . $fmt(abs($r['nilai'])) . ')' : $fmt($r['nilai']) !!}
              </td>
            </tr>
            @endforeach
            <tr class="font-weight-bold">
              <td></td>
              <td>Total <strong>{{ $judul }}</strong></td>
              <td class="text-right">
                {!! $bagian['subtotal'] < 0 ? '(' . $fmt(abs($bagian['subtotal'])) . ')' : $fmt($bagian['subtotal']) !!}
              </td>
            </tr>
          </tbody>
        </table>
        @endif
        @endforeach

        <table class="table table-sm">
          <tr class="font-weight-bold bg-light">
            <td style="width:18%"></td>
            <td>Total Aset</td>
            <td class="text-right" style="width:20%">
              {!! $totalAset < 0 ? '(' . $fmt(abs($totalAset)) . ')' : $fmt($totalAset) !!}
            </td>
          </tr>
        </table>
      </div>

      {{-- KANAN: LIABILITAS & MODAL --}}
      <div class="col-md-5">
        <h5 class="mb-2"><strong>Liabilitas dan Modal</strong></h5>

        @foreach ($liabilitas as $judul => $bagian)
        @if (!empty($bagian['rows']))
        <h6 class="mt-3"><strong>{{ $judul }}</strong></h6>
        <table class="table table-sm">
          <tbody>
            @foreach ($bagian['rows'] as $r)
            <tr>
              <td style="width: 18%">{{ $r['kode'] }}</td>
              <td>{{ $r['nama'] }}</td>
              <td class="text-right" style="width: 20%">
                {!! $r['nilai'] < 0 ? '(' . $fmt(abs($r['nilai'])) . ')' : $fmt($r['nilai']) !!}
              </td>
            </tr>
            @endforeach
            <tr class="font-weight-bold">
              <td></td>
              <td>Total <strong>{{ $judul }}</strong></td>
              <td class="text-right">
                {!! $bagian['subtotal'] < 0 ? '(' . $fmt(abs($bagian['subtotal'])) . ')' : $fmt($bagian['subtotal']) !!}
              </td>
            </tr>
          </tbody>
        </table>
        @endif
        @endforeach

        @foreach ($ekuitas as $judul => $bagian)
        @if (!empty($bagian['rows']))
        <h6 class="mt-3"><strong>{{ $judul }}</strong></h6>
        <table class="table table-sm">
          <tbody>
            @foreach ($bagian['rows'] as $r)
            <tr>
              <td style="width: 18%">{{ $r['kode'] }}</td>
              <td>{{ $r['nama'] }}</td>
              <td class="text-right" style="width: 20%">
                {!! $r['nilai'] < 0 ? '(' . $fmt(abs($r['nilai'])) . ')' : $fmt($r['nilai']) !!}
              </td>
            </tr>
            @endforeach
            <tr class="font-weight-bold">
              <td></td>
              <td>Total <strong>{{ $judul }}</strong></td>
              <td class="text-right">
                {!! $bagian['subtotal'] < 0 ? '(' . $fmt(abs($bagian['subtotal'])) . ')' : $fmt($bagian['subtotal']) !!}
              </td>
            </tr>
          </tbody>
        </table>
        @endif
        @endforeach

        <table class="table table-sm">
          <tr class="font-weight-bold bg-light">
            <td style="width:18%"></td>
            <td>Total Liabilitas dan Modal</td>
            <td class="text-right" style="width:20%">
              {!! ($totalLiabilitas + $totalEkuitas) < 0
              ? '(' . $fmt(abs($totalLiabilitas + $totalEkuitas)) . ')'
              : $fmt($totalLiabilitas + $totalEkuitas) !!}
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>

</div>
</section>
@endsection
