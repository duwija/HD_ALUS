{{-- resources/views/jurnal/neraca.blade.php --}}
@extends('layout.main')
@section('title', 'Laporan Neraca')

@section('content')
<style>
  .neraca-container {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .neraca-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
  }
  .neraca-title {
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
  }
  .neraca-subtitle {
    font-size: 14px;
    color: #7f8c8d;
  }
  .filter-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
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
    border-radius: 6px;
    overflow: hidden;
  }
  
  .column-header {
    background: linear-gradient(135deg, #f5f5f5 0%, #eeeeee 100%);
    color: #424242;
    padding: 15px;
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    border-bottom: 2px solid #e0e0e0;
  }
  
  .column-header.liability {
    background: linear-gradient(135deg, #f5f5f5 0%, #eeeeee 100%);
    border-bottom: 2px solid #e0e0e0;
  }
  
  .neraca-table {
    width: 100%;
    border-collapse: collapse;
  }
  .neraca-table td {
    padding: 10px 15px;
    border: none;
    font-size: 14px;
  }
  .neraca-section-title {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
    padding: 12px 15px !important;
    border-bottom: 2px solid #dee2e6;
  }
  .neraca-subsection {
    background-color: #fafbfc;
    font-weight: 600;
    color: #495057;
    padding: 10px 15px !important;
    border-bottom: 1px solid #e9ecef;
  }
  .neraca-item {
    padding-left: 35px !important;
    color: #495057;
    border-bottom: 1px solid #f8f9fa;
  }
  .neraca-item:hover {
    background-color: #f1f3f5;
  }
  .neraca-subtotal {
    font-weight: 600;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    padding: 10px 15px !important;
  }
  .neraca-total {
    font-weight: 700;
    font-size: 15px;
    background-color: #e9ecef;
    border-top: 2px solid #495057;
    padding: 12px 15px !important;
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
    background: linear-gradient(135deg, #f5f5f5 0%, #eeeeee 100%);
    color: #424242;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid #e0e0e0;
  }
  .balance-box.liability {
    background: linear-gradient(135deg, #f5f5f5 0%, #eeeeee 100%);
    border: 2px solid #e0e0e0;
  }
  .balance-label {
    font-size: 12px;
    opacity: 0.8;
    margin-bottom: 5px;
    color: #616161;
  }
  .balance-value {
    font-size: 24px;
    font-weight: 700;
    font-family: 'Courier New', monospace;
    color: #212121;
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

<div class="container-fluid">
  <div class="neraca-container">
    
    <!-- Header -->
    <div class="neraca-header">
      <h3 class="font-weight-bold m-0">
          {{ config('app.company', env('COMPANY','Perusahaan')) }}
        </h3>
        <div><strong>Neraca (Balance Sheet)</strong></div>
      <div class="neraca-subtitle">Per {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}</div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <form method="GET" action="{{ url('jurnal/neraca') }}" class="form-inline">
        <div class="form-group mr-2">
          <label class="mr-2" style="font-weight: 500;">Tanggal:</label>
          <input type="date" name="tanggal_akhir" class="form-control form-control-sm" value="{{ $tanggalAkhir }}" style="min-width: 150px;">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fas fa-filter"></i> Tampilkan
        </button>
      </form>
      <div>
        <a href="{{ url('jurnal/neraca/pdf?tanggal_akhir=' . $tanggalAkhir) }}" class="btn btn-sm btn-outline-danger" target="_blank">
          <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a href="{{ url('jurnal/neraca/excel?tanggal_akhir=' . $tanggalAkhir) }}" class="btn btn-sm btn-outline-success">
          <i class="fas fa-file-excel"></i> Excel
        </a>
      </div>
    </div>

    <!-- 2 Column Layout -->
    <div class="neraca-two-column">
      
      <!-- LEFT COLUMN: ASET -->
      <div class="neraca-column">
        <div class="column-header">ASET</div>
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
        <div class="column-header liability">KEWAJIBAN & EKUITAS</div>
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
      <div class="balance-box">
        <div class="balance-label">TOTAL ASET</div>
        <div class="balance-value">Rp {{ number_format($totalAset, 0, ',', '.') }}</div>
      </div>
      <div class="balance-box liability">
        <div class="balance-label">TOTAL KEWAJIBAN & EKUITAS</div>
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
@endsection
