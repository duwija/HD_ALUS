@extends('layout.main')

@section('content')
<style>
  .labarugi-container {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    max-width: 900px;
    margin: 0 auto;
  }
  .labarugi-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
  }
  .labarugi-title {
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
  }
  .labarugi-subtitle {
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
    flex-wrap: wrap;
    gap: 10px;
  }
  
  .labarugi-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
  }
  .labarugi-table td {
    padding: 8px 15px;
    border: none;
    font-size: 13px;
  }
  .labarugi-table td:first-child {
    width: 120px;
  }
  .labarugi-table td:nth-child(2) {
    width: auto;
  }
  .labarugi-table td:last-child {
    width: 150px;
  }
  .section-title {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
    padding: 12px 15px !important;
    border-bottom: 2px solid #dee2e6;
    font-size: 14px;
  }
  .subsection {
    background-color: #fafbfc;
    font-weight: 600;
    color: #495057;
    padding: 10px 15px !important;
    border-bottom: 1px solid #e9ecef;
    font-size: 13px;
  }
  .item {
    padding: 6px 8px !important;
    color: #495057;
    border-bottom: 1px solid #f8f9fa;
  }
  .item:hover {
    background-color: #f1f3f5;
  }
  .item-code {
    color: #6c757d;
    font-family: 'Courier New', monospace;
    font-size: 12px;
  }
  .item-name {
    color: #495057;
  }
  .subtotal {
    font-weight: 600;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    padding: 10px 15px !important;
  }
  .total {
    font-weight: 700;
    font-size: 15px;
    background-color: #e9ecef;
    border-top: 2px solid #495057;
    padding: 12px 15px !important;
  }
  .laba-kotor {
    font-weight: 600;
    background-color: #fff3cd;
    border-top: 1px solid #ffc107;
    border-bottom: 1px solid #ffc107;
    padding: 10px 15px !important;
  }
  .laba-bersih {
    font-weight: 700;
    font-size: 16px;
    padding: 12px 15px !important;
  }
  .laba-bersih.profit {
    background-color: #d4edda;
    border: 2px solid #28a745;
    color: #155724;
  }
  .laba-bersih.loss {
    background-color: #f8d7da;
    border: 2px solid #dc3545;
    color: #721c24;
  }
  .amount {
    text-align: right;
    font-family: 'Courier New', monospace;
    white-space: nowrap;
    font-size: 13px;
  }
  
  .result-box {
    margin-top: 20px;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
  }
  .result-box.profit {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 2px solid #28a745;
  }
  .result-box.loss {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    border: 2px solid #dc3545;
  }
  .result-label {
    font-size: 14px;
    margin-bottom: 5px;
    opacity: 0.8;
  }
  .result-value {
    font-size: 28px;
    font-weight: 700;
    font-family: 'Courier New', monospace;
  }
  .result-box.profit .result-value {
    color: #155724;
  }
  .result-box.loss .result-value {
    color: #721c24;
  }

  @media print {
    .filter-section { display: none; }
  }
</style>

<div class="container-fluid">
  <div class="labarugi-container">
    
    <!-- Header -->
    <div class="labarugi-header">
      <h3 style="margin: 0 0 5px 0; font-size: 16px; font-weight: 600; color: #2c3e50;">
        {{ config('app.company', env('COMPANY','Perusahaan')) }}
      </h3>
      <div class="labarugi-title">LAPORAN LABA RUGI</div>
      <div class="labarugi-subtitle">
        Periode {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d F Y') }} 
        s/d {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <form method="GET" action="{{ url('jurnal/labarugi') }}" class="form-inline">
        <div class="form-group mr-2">
          <label class="mr-2" style="font-weight: 500;">Dari:</label>
          <input type="date" name="tanggal_awal" class="form-control form-control-sm" value="{{ $tanggalAwal }}" style="min-width: 150px;">
        </div>
        <div class="form-group mr-2">
          <label class="mr-2" style="font-weight: 500;">Sampai:</label>
          <input type="date" name="tanggal_akhir" class="form-control form-control-sm" value="{{ $tanggalAkhir }}" style="min-width: 150px;">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fas fa-filter"></i> Tampilkan
        </button>
      </form>
      <div>
        <a href="{{ url('jurnal/labarugi/pdf?tanggal_awal=' . $tanggalAwal . '&tanggal_akhir=' . $tanggalAkhir) }}" class="btn btn-sm btn-outline-danger" target="_blank">
          <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a href="{{ url('jurnal/labarugi/excel?tanggal_awal=' . $tanggalAwal . '&tanggal_akhir=' . $tanggalAkhir) }}" class="btn btn-sm btn-outline-success">
          <i class="fas fa-file-excel"></i> Excel
        </a>
      </div>
    </div>

    <!-- Table -->
    <table class="labarugi-table">
      
      <!-- PENDAPATAN UTAMA -->
      <tr>
        <td colspan="3" class="section-title">PENDAPATAN</td>
      </tr>
      <tr>
        <td colspan="3" class="subsection">Pendapatan Usaha</td>
      </tr>
      @forelse ($data['pendapatan'] ?? [] as $item)
      <tr>
        <td class="item item-code">{{ $item['akun_code'] }}</td>
        <td class="item item-name">{{ $item['name'] }}</td>
        <td class="amount item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
      </tr>
      @empty
      <tr>
        <td class="item" colspan="3" style="text-align: center; color: #999; font-style: italic;">Tidak ada data</td>
      </tr>
      @endforelse
      @if(!empty($data['pendapatan']))
      <tr>
        <td colspan="2" class="item" style="padding-left: 50px !important; font-weight: 500;">Subtotal Pendapatan Usaha</td>
        <td class="amount item" style="font-weight: 500;">{{ number_format($totals['pendapatan'], 0, ',', '.') }}</td>
      </tr>
      @endif

      <!-- Pendapatan Lainnya -->
      @if(!empty($data['pendapatan_lainnya']))
      <tr>
        <td colspan="3" class="subsection">Pendapatan Lainnya</td>
      </tr>
      @forelse ($data['pendapatan_lainnya'] ?? [] as $item)
      <tr>
        <td class="item item-code">{{ $item['akun_code'] }}</td>
        <td class="item item-name">{{ $item['name'] }}</td>
        <td class="amount item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
      </tr>
      @empty
      @endforelse
      <tr>
        <td colspan="2" class="item" style="padding-left: 50px !important; font-weight: 500;">Subtotal Pendapatan Lainnya</td>
        <td class="amount item" style="font-weight: 500;">{{ number_format($totals['pendapatan_lainnya'], 0, ',', '.') }}</td>
      </tr>
      @endif

      <!-- Total Pendapatan -->
      <tr>
        <td colspan="2" class="subtotal">TOTAL PENDAPATAN</td>
        <td class="amount subtotal">{{ number_format($totalPendapatanUtama, 0, ',', '.') }}</td>
      </tr>

      <!-- Spacer -->
      <tr>
        <td colspan="3" style="height: 10px;"></td>
      </tr>

      <!-- HARGA POKOK PENJUALAN -->
      <tr>
        <td colspan="3" class="section-title">HARGA POKOK PENJUALAN</td>
      </tr>
      @forelse ($data['hpp'] ?? [] as $item)
      <tr>
        <td class="item item-code">{{ $item['akun_code'] }}</td>
        <td class="item item-name">{{ $item['name'] }}</td>
        <td class="amount item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
      </tr>
      @empty
      <tr>
        <td class="item" colspan="3" style="text-align: center; color: #999; font-style: italic;">Tidak ada data</td>
      </tr>
      @endforelse
      <tr>
        <td colspan="2" class="subtotal">Total Harga Pokok Penjualan</td>
        <td class="amount subtotal">{{ number_format($totals['hpp'], 0, ',', '.') }}</td>
      </tr>

      <!-- LABA KOTOR -->
      <tr>
        <td colspan="2" class="laba-kotor">LABA KOTOR</td>
        <td class="amount laba-kotor">{{ number_format($labaKotor, 0, ',', '.') }}</td>
      </tr>

      <!-- Spacer -->
      <tr>
        <td colspan="3" style="height: 10px;"></td>
      </tr>

      <!-- BEBAN OPERASIONAL -->
      <tr>
        <td colspan="3" class="section-title">BEBAN OPERASIONAL</td>
      </tr>

      <!-- Beban Operasional -->
      <tr>
        <td colspan="3" class="subsection">Beban Usaha</td>
      </tr>
      @forelse ($data['beban_operasional'] ?? [] as $item)
      <tr>
        <td class="item item-code">{{ $item['akun_code'] }}</td>
        <td class="item item-name">{{ $item['name'] }}</td>
        <td class="amount item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
      </tr>
      @empty
      <tr>
        <td class="item" colspan="3" style="text-align: center; color: #999; font-style: italic;">Tidak ada data</td>
      </tr>
      @endforelse
      @if(!empty($data['beban_operasional']))
      <tr>
        <td colspan="2" class="item" style="padding-left: 50px !important; font-weight: 500;">Subtotal Beban Usaha</td>
        <td class="amount item" style="font-weight: 500;">{{ number_format($totals['beban_operasional'], 0, ',', '.') }}</td>
      </tr>
      @endif

      <!-- Beban Lainnya -->
      @if(!empty($data['beban_lainnya']))
      <tr>
        <td colspan="3" class="subsection">Beban Lainnya</td>
      </tr>
      @forelse ($data['beban_lainnya'] ?? [] as $item)
      <tr>
        <td class="item item-code">{{ $item['akun_code'] }}</td>
        <td class="item item-name">{{ $item['name'] }}</td>
        <td class="amount item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
      </tr>
      @empty
      @endforelse
      <tr>
        <td colspan="2" class="item" style="padding-left: 50px !important; font-weight: 500;">Subtotal Beban Lainnya</td>
        <td class="amount item" style="font-weight: 500;">{{ number_format($totals['beban_lainnya'], 0, ',', '.') }}</td>
      </tr>
      @endif

      <!-- Depresiasi -->
      @if(!empty($data['depresiasi']))
      <tr>
        <td colspan="3" class="subsection">Depresiasi & Amortisasi</td>
      </tr>
      @forelse ($data['depresiasi'] ?? [] as $item)
      <tr>
        <td class="item item-code">{{ $item['akun_code'] }}</td>
        <td class="item item-name">{{ $item['name'] }}</td>
        <td class="amount item">{{ number_format($item['saldo'], 0, ',', '.') }}</td>
      </tr>
      @empty
      @endforelse
      <tr>
        <td colspan="2" class="item" style="padding-left: 50px !important; font-weight: 500;">Subtotal Depresiasi & Amortisasi</td>
        <td class="amount item" style="font-weight: 500;">{{ number_format($totals['depresiasi'], 0, ',', '.') }}</td>
      </tr>
      @endif

      <!-- Total Beban -->
      <tr>
        <td colspan="2" class="subtotal">TOTAL BEBAN OPERASIONAL</td>
        <td class="amount subtotal">{{ number_format($totalBeban, 0, ',', '.') }}</td>
      </tr>

      <!-- Spacer -->
      <tr>
        <td colspan="3" style="height: 15px;"></td>
      </tr>

      <!-- LABA BERSIH -->
      <tr>
        <td colspan="2" class="laba-bersih {{ $labaBersih >= 0 ? 'profit' : 'loss' }}">
          {{ $labaBersih >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}
        </td>
        <td class="amount laba-bersih {{ $labaBersih >= 0 ? 'profit' : 'loss' }}">
          {{ number_format(abs($labaBersih), 0, ',', '.') }}
        </td>
      </tr>

    </table>

    <!-- Result Box -->
    <div class="result-box {{ $labaBersih >= 0 ? 'profit' : 'loss' }}">
      <div class="result-label">{{ $labaBersih >= 0 ? 'LABA BERSIH PERIODE INI' : 'RUGI BERSIH PERIODE INI' }}</div>
      <div class="result-value">
        Rp {{ number_format(abs($labaBersih), 0, ',', '.') }}
      </div>
    </div>

  </div>
</div>
@endsection
