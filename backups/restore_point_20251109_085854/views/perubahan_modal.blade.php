@extends('layout.main')
@section('title', 'Laporan Perubahan Modal')

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
  .filter-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
  }
  
  .filter-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    display: block;
  }
  
  .filter-label i {
    margin-right: 5px;
    color: #4a90e2;
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
    font-size: 16px;
    font-weight: 600;
  }
  
  .report-header .report-title {
    font-size: 20px;
    font-weight: 700;
    color: #2c3e50;
    margin: 10px 0;
  }
  
  .report-header small {
    color: #5a6c7d;
  }

  .modal-container {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    max-width: 800px;
    margin: 0 auto;
  }

  .modal-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
  }

  .modal-table td {
    padding: 12px 15px;
    border: none;
    font-size: 14px;
  }

  .modal-table td:first-child {
    width: 50%;
    color: #495057;
  }

  .modal-table td:last-child {
    width: 50%;
    text-align: right;
    font-family: 'Courier New', monospace;
    font-weight: 500;
  }

  .section-header {
    background: #4a90e2;
    color: white;
    padding: 10px 15px;
    font-weight: 600;
    font-size: 14px;
    border-radius: 4px;
    margin-bottom: 10px;
  }

  .item-row td {
    padding: 8px 15px 8px 30px !important;
    border-bottom: 1px solid #f8f9fa;
  }

  .item-row:hover {
    background: #f8f9fa;
  }

  .subtotal-row {
    background: #e9ecef;
    font-weight: 600;
  }

  .subtotal-row td {
    padding: 10px 15px 10px 30px !important;
    border-top: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
  }

  .total-row {
    background: #343a40;
    color: white;
    font-weight: 700;
    font-size: 16px;
  }

  .total-row td {
    padding: 15px !important;
    border-top: 2px solid #495057;
  }

  .positive {
    color: #28a745;
  }

  .negative {
    color: #dc3545;
  }

  .modal-summary {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    margin-top: 20px;
  }

  .modal-summary.negative {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
  }

  .modal-summary-label {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 5px;
  }

  .modal-summary-value {
    font-size: 28px;
    font-weight: 700;
    font-family: 'Courier New', monospace;
  }

  @media print {
    .filter-section { display: none; }
    .btn { display: none; }
  }
</style>

<section class="content">
  <div class="card">
    <div class="card-header card-header-custom">
      <h3 class="card-title card-title-custom">
        <i class="fas fa-chart-area"></i>
        Laporan Perubahan Modal
      </h3>
      <p class="card-subtitle mb-0">Statement of Changes in Equity</p>
    </div>
    
    <div class="card-body">
      <!-- Filter Section -->
      <div class="filter-section">
        <form method="GET" action="{{ url('jurnal/perubahan-modal') }}" class="form-row align-items-end">
          <div class="col-md-5">
            <label class="filter-label">
              <i class="far fa-calendar-alt"></i> Tanggal Awal
            </label>
            <input type="date" name="tanggal_awal" class="form-control" value="{{ $tanggalAwal }}">
          </div>
          <div class="col-md-5">
            <label class="filter-label">
              <i class="far fa-calendar-check"></i> Tanggal Akhir
            </label>
            <input type="date" name="tanggal_akhir" class="form-control" value="{{ $tanggalAkhir }}">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fas fa-filter"></i> Filter
            </button>
          </div>
        </form>
      </div>

      <!-- Report Header -->
      <div class="report-header">
        <h3>{{ config('app.company', env('COMPANY','Perusahaan')) }}</h3>
        <div class="report-title">LAPORAN PERUBAHAN MODAL</div>
        <small>
          Periode {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d F Y') }} 
          s/d {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}
        </small>
      </div>

      <!-- Export Buttons -->
      <div class="mb-3 text-right">
        <a href="{{ url('jurnal/perubahan-modal/excel') }}?tanggal_awal={{ $tanggalAwal }}&tanggal_akhir={{ $tanggalAkhir }}" 
           class="btn btn-success btn-sm">
          <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <a href="{{ url('jurnal/perubahan-modal/pdf') }}?tanggal_awal={{ $tanggalAwal }}&tanggal_akhir={{ $tanggalAkhir }}" 
           class="btn btn-danger btn-sm" target="_blank">
          <i class="fas fa-file-pdf"></i> Export PDF
        </a>
      </div>

      <!-- Perubahan Modal Table -->
      <div class="modal-container">
        <table class="modal-table">
          
          <!-- Modal Awal -->
          <tr>
            <td colspan="2" class="section-header">
              <i class="fas fa-wallet"></i> Modal Awal Periode
            </td>
          </tr>
          <tr class="item-row">
            <td>Modal per {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d F Y') }}</td>
            <td>{{ number_format($modalAwal, 0, ',', '.') }}</td>
          </tr>

          <!-- Penambahan -->
          <tr>
            <td colspan="2" class="section-header">
              <i class="fas fa-plus-circle"></i> Penambahan Modal
            </td>
          </tr>
          @if($penambahanModal != 0)
          <tr class="item-row">
            <td>Setoran Modal</td>
            <td class="positive">{{ number_format($penambahanModal, 0, ',', '.') }}</td>
          </tr>
          @else
          <tr class="item-row">
            <td colspan="2" style="text-align: center; color: #999; font-style: italic;">Tidak ada penambahan modal</td>
          </tr>
          @endif

          <!-- Laba/Rugi -->
          <tr>
            <td colspan="2" class="section-header">
              <i class="fas fa-chart-line"></i> Laba (Rugi) Periode Berjalan
            </td>
          </tr>
          <tr class="item-row">
            <td>{{ $labaBersih >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}</td>
            <td class="{{ $labaBersih >= 0 ? 'positive' : 'negative' }}">
              {{ $labaBersih >= 0 ? '' : '(' }}{{ number_format(abs($labaBersih), 0, ',', '.') }}{{ $labaBersih >= 0 ? '' : ')' }}
            </td>
          </tr>

          <!-- Pengurangan -->
          <tr>
            <td colspan="2" class="section-header">
              <i class="fas fa-minus-circle"></i> Pengurangan Modal
            </td>
          </tr>
          @if($prive != 0)
          <tr class="item-row">
            <td>Prive / Penarikan</td>
            <td class="negative">({{ number_format($prive, 0, ',', '.') }})</td>
          </tr>
          @else
          <tr class="item-row">
            <td colspan="2" style="text-align: center; color: #999; font-style: italic;">Tidak ada pengambilan prive</td>
          </tr>
          @endif

          <!-- Total -->
          <tr class="total-row">
            <td><i class="fas fa-check-double"></i> Modal Akhir Periode</td>
            <td>{{ number_format($modalAkhir, 0, ',', '.') }}</td>
          </tr>

        </table>

        <!-- Modal Summary Box -->
        <div class="modal-summary {{ ($modalAkhir - $modalAwal) >= 0 ? '' : 'negative' }}">
          <div class="modal-summary-label">
            <i class="fas fa-exchange-alt"></i>
            PERUBAHAN MODAL SELAMA PERIODE
          </div>
          <div class="modal-summary-value">
            {{ ($modalAkhir - $modalAwal) >= 0 ? '+' : '' }} Rp {{ number_format($modalAkhir - $modalAwal, 0, ',', '.') }}
          </div>
        </div>

      </div>

    </div>
  </div>
</section>
@endsection
