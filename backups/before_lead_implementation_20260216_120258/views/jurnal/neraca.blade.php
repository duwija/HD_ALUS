{{-- resources/views/jurnal/neraca.blade.php --}}
@extends('layout.main')
@section('title', 'Laporan Neraca')

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
.report-header {
  text-align: center;
  margin-bottom: 30px;
}
.neraca-two-column {
  display: flex;
  gap: 30px;
  margin-top: 20px;
}
.neraca-column {
  flex: 1;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  overflow: hidden;
}
.column-header {
  background: #4a90e2;
  color: white;
  padding: 12px 15px;
  font-weight: 700;
  font-size: 16px;
}
.neraca-table {
  width: 100%;
}
.neraca-table td {
  padding: 10px 15px;
  border-bottom: 1px solid #f0f0f0;
}
.neraca-subsection {
  background: #f8f9fa;
  font-weight: 700;
  color: #495057;
  padding: 12px 15px !important;
  border-bottom: 2px solid #dee2e6 !important;
}
.neraca-item {
  font-size: 14px;
}
.neraca-amount {
  text-align: right;
  font-family: 'Courier New', monospace;
  white-space: nowrap;
}
.neraca-subtotal {
  font-weight: 700;
  background: #e9ecef;
  padding: 12px 15px !important;
}
.neraca-total {
  background: #343a40;
  color: white;
  font-weight: 700;
  font-size: 16px;
  padding: 15px !important;
}
.balance-check {
  text-align: center;
  margin-top: 30px;
  padding: 20px;
  border-radius: 8px;
}
.balance-check.balanced {
  background: linear-gradient(135deg, #28a745 0%, #218838 100%);
  color: white;
}
.balance-check.not-balanced {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
}
.balance-summary {
  display: flex;
  gap: 20px;
  margin-top: 30px;
}
.balance-box {
  flex: 1;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  text-align: center;
}
.balance-box.assets {
  background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
  color: white;
}
.balance-box.liability {
  background: linear-gradient(135deg, #5dade2 0%, #3498db 100%);
  color: white;
}
.balance-box .balance-label {
  font-size: 14px;
  opacity: 0.9;
  margin-bottom: 10px;
  font-weight: 600;
}
.balance-box .balance-value {
  font-size: 24px;
  font-weight: 700;
  font-family: 'Courier New', monospace;
}
.balance-alert {
  text-align: center;
  padding: 15px 20px;
  border-radius: 8px;
  margin-top: 20px;
  font-weight: 600;
}
.balance-alert.success {
  background: linear-gradient(135deg, #28a745 0%, #218838 100%);
  color: white;
}
.balance-alert.warning {
  background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
  color: #212529;
  border: 2px solid #ffc107;
}
.balance-alert i {
  margin-right: 8px;
  font-size: 18px;
}
@media (max-width: 768px) {
  .neraca-two-column {
    flex-direction: column;
  }
  .balance-summary {
    flex-direction: column;
  }
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
