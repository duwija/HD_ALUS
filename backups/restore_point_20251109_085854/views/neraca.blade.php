{{-- resources/views/jurnal/neraca.blade.php --}}
@extends('layout.main')
@section('title', 'Laporan Neraca')

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
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 25px;
  text-align: center;
  border-left: 4px solid #4a90e2;
}

.report-header h3 {
  margin: 0;
  color: #2c3e50;
}

.report-header small {
  color: #5a6c7d;
}
  
/* 2 Column Layout */
.neraca-two-column {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  margin-bottom: 20px;
}

.neraca-column {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.column-header {
  background: #4a90e2;
  color: white;
  padding: 15px;
  font-size: 18px;
  font-weight: 700;
  text-align: center;
  border-bottom: 3px solid #357abd;
  display: flex;
  align-items: center;
  justify-content: center;
}

.column-header i {
  margin-right: 10px;
  font-size: 22px;
}

.neraca-table {
  width: 100%;
  border-collapse: collapse;
}

.neraca-table td {
  padding: 10px 15px;
  border: none;
  font-size: 14px;
  border-bottom: 1px solid #f0f0f0;
}

.neraca-table tr:hover {
  background-color: #f8f9fa;
}

.neraca-section-title {
  background: #4a90e2;
  color: white;
  font-weight: 600;
  padding: 12px 15px !important;
  border-bottom: 2px solid #357abd;
}

.neraca-subsection {
  background-color: #e9ecef;
  font-weight: 600;
  color: #495057;
  padding: 10px 15px !important;
  border-bottom: 1px solid #dee2e6;
}

.neraca-item {
  padding-left: 35px !important;
  color: #495057;
}

.neraca-item:hover {
  background-color: #f1f3f5;
}

.neraca-subtotal {
  font-weight: 600;
  background-color: #f8f9fa;
  border-top: 2px solid #dee2e6;
  border-bottom: 2px solid #dee2e6;
  padding: 10px 15px !important;
}

.neraca-total {
  font-weight: 700;
  font-size: 15px;
  background: #343a40;
  color: white;
  border-top: 3px solid #495057;
  padding: 15px !important;
}

.neraca-amount {
  text-align: right;
  font-family: 'Courier New', monospace;
  white-space: nowrap;
  font-size: 13px;
}

/* Bottom Summary */
.balance-summary {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  margin-top: 20px;
}

.balance-box {
  padding: 20px;
  border-radius: 8px;
  text-align: center;
  color: white;
}

.balance-box.assets {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.balance-box.liability {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.balance-label {
  font-size: 14px;
  opacity: 0.9;
  margin-bottom: 5px;
}

.balance-value {
  font-size: 28px;
  font-weight: 700;
  font-family: 'Courier New', monospace;
}

.balance-alert {
  margin-top: 20px;
  padding: 15px 20px;
  border-radius: 6px;
  font-size: 14px;
  text-align: center;
}

.balance-alert.success {
  background-color: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
}

.balance-alert.warning {
  background-color: #fff3cd;
  border: 1px solid #ffeaa7;
  color: #856404;
}

@media print {
  .filter-section, .balance-alert { display: none; }
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
      <form method="GET" action="{{ url('jurnal/neraca') }}" class="mb-4">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label class="filter-label">
                <i class="fas fa-calendar-alt"></i> Tanggal Akhir
              </label>
              <input type="date" name="tanggal_akhir" class="form-control" value="{{ $tanggalAkhir }}">
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
                <a href="{{ url('jurnal/neraca/excel?tanggal_akhir=' . $tanggalAkhir) }}" class="btn btn-success">
                  <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="{{ url('jurnal/neraca/pdf?tanggal_akhir=' . $tanggalAkhir) }}" class="btn btn-danger" target="_blank">
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
        <div><strong>NERACA (BALANCE SHEET)</strong></div>
        <small>Per {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}</small>
      </div>

      <!-- 2 Column Layout -->
      <div class="neraca-two-column">
        
        <!-- LEFT COLUMN: ASET -->
        <div class="neraca-column">
          <div class="column-header">
            <i class="fas fa-coins"></i> ASET
          </div>
          <table class="neraca-table">
          
          <!-- Aset Lancar -->
          <tr>
            <td colspan="2" class="neraca-subsection">Aset Lancar</td>
          </tr>
          @forelse ($data['aset_lancar'] ?? [] as $item)
          <tr>
            <td class="neraca-item">{{ $item['name'] }}</td>
            <td class="neraca-amount neraca-item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
          </tr>
          @empty
          <tr>
            <td class="neraca-item" colspan="2" style="text-align: center; color: #999; font-style: italic;">Tidak ada data</td>
          </tr>
          @endforelse
          <tr>
            <td class="neraca-subtotal">Total Aset Lancar</td>
            <td class="neraca-amount neraca-subtotal">{{ number_format($totals['aset_lancar'], 0, ',', '.') }}</td>
          </tr>

          <!-- Aset Tetap -->
          <tr>
            <td colspan="2" class="neraca-subsection">Aset Tetap</td>
          </tr>
          @forelse ($data['aset_tetap'] ?? [] as $item)
          <tr>
            <td class="neraca-item">{{ $item['name'] }}</td>
            <td class="neraca-amount neraca-item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
          </tr>
          @empty
          <tr>
            <td class="neraca-item" colspan="2" style="text-align: center; color: #999; font-style: italic;">Tidak ada data</td>
          </tr>
          @endforelse
          <tr>
            <td class="neraca-subtotal">Total Aset Tetap</td>
            <td class="neraca-amount neraca-subtotal">{{ number_format($totals['aset_tetap'], 0, ',', '.') }}</td>
          </tr>

          <!-- Total Aset -->
          <tr>
            <td class="neraca-total">TOTAL ASET</td>
            <td class="neraca-amount neraca-total">{{ number_format($totalAset, 0, ',', '.') }}</td>
          </tr>

        </table>
      </div>

      <!-- RIGHT COLUMN: KEWAJIBAN & EKUITAS -->
      <div class="neraca-column">
        <div class="column-header">
          <i class="fas fa-balance-scale"></i> KEWAJIBAN & EKUITAS
        </div>
        <table class="neraca-table">
          
          <!-- Kewajiban Lancar -->
          <tr>
            <td colspan="2" class="neraca-subsection">Kewajiban Lancar</td>
          </tr>
          @forelse ($data['kewajiban_lancar'] ?? [] as $item)
          <tr>
            <td class="neraca-item">{{ $item['name'] }}</td>
            <td class="neraca-amount neraca-item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
          </tr>
          @empty
          <tr>
            <td class="neraca-item" colspan="2" style="text-align: center; color: #999; font-style: italic;">Tidak ada data</td>
          </tr>
          @endforelse
          <tr>
            <td class="neraca-subtotal">Total Kewajiban Lancar</td>
            <td class="neraca-amount neraca-subtotal">{{ number_format($totals['kewajiban_lancar'], 0, ',', '.') }}</td>
          </tr>

          <!-- Spacer -->
          <tr>
            <td colspan="2" style="height: 10px; background: white;"></td>
          </tr>

          <!-- Ekuitas -->
          <tr>
            <td colspan="2" class="neraca-subsection">Ekuitas</td>
          </tr>
          @forelse ($data['ekuitas'] ?? [] as $item)
          <tr>
            <td class="neraca-item">{{ $item['name'] }}</td>
            <td class="neraca-amount neraca-item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
          </tr>
          @empty
          @endforelse
          <tr>
            <td class="neraca-item">Laba (Rugi) Ditahan</td>
            <td class="neraca-amount neraca-item" style="color: {{ $labaRugi >= 0 ? '#28a745' : '#dc3545' }}; font-weight: 500;">
              {{ number_format($labaRugi, 0, ',', '.') }}
            </td>
          </tr>
          <tr>
            <td class="neraca-subtotal">Total Ekuitas</td>
            <td class="neraca-amount neraca-subtotal">{{ number_format($totalEkuitas, 0, ',', '.') }}</td>
          </tr>

          <!-- Total Kewajiban & Ekuitas -->
          <tr>
            <td class="neraca-total">TOTAL KEWAJIBAN & EKUITAS</td>
            <td class="neraca-amount neraca-total">{{ number_format($totalKewajibanEkuitas, 0, ',', '.') }}</td>
          </tr>

        </table>
      </div>

    </div>

    <!-- Balance Summary Boxes -->
    <div class="balance-summary">
      <div class="balance-box assets">
        <div class="balance-label">
          <i class="fas fa-coins"></i> TOTAL ASET
        </div>
        <div class="balance-value">Rp {{ number_format($totalAset, 0, ',', '.') }}</div>
      </div>
      <div class="balance-box liability">
        <div class="balance-label">
          <i class="fas fa-balance-scale"></i> TOTAL KEWAJIBAN & EKUITAS
        </div>
        <div class="balance-value">Rp {{ number_format($totalKewajibanEkuitas, 0, ',', '.') }}</div>
      </div>
    </div>

    <!-- Balance Check Alert -->
    @php
      $selisih = abs($totalAset - $totalKewajibanEkuitas);
      $isBalanced = $selisih < 0.01;
    @endphp
    
    <div class="balance-alert {{ $isBalanced ? 'success' : 'warning' }}">
      @if($isBalanced)
        <i class="fas fa-check-circle"></i> 
        <strong>Neraca Seimbang</strong> - Total Aset = Total Kewajiban + Ekuitas
      @else
        <i class="fas fa-exclamation-triangle"></i> 
        <strong>Perhatian:</strong> Neraca tidak seimbang. Selisih: Rp {{ number_format($selisih, 0, ',', '.') }}
      @endif
    </div>

  </div>
  </div>
</section>
@endsection
