@extends('layout.main')

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
.filter-section {
  background-color: #f8f9fa;
  padding: 1.5rem;
  border-radius: 0.25rem;
  margin-bottom: 1.5rem;
}
.filter-label {
  font-weight: 600;
  color: #495057;
}
.report-header {
  text-align: center;
  margin-bottom: 30px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}
.report-header h3 {
  margin: 0;
  font-weight: 700;
  color: #343a40;
}
.report-title {
  font-size: 18px;
  font-weight: 700;
  margin: 10px 0;
  color: #4a90e2;
}
.labarugi-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 30px;
}
.labarugi-table tr {
  border-bottom: 1px solid #f0f0f0;
}
.labarugi-table td {
  padding: 10px 15px;
}
.section-title {
  background: #4a90e2;
  color: white;
  font-weight: 700;
  font-size: 16px;
  padding: 12px 15px !important;
  text-align: left;
}
.subsection {
  background: #f8f9fa;
  font-weight: 600;
  color: #495057;
  padding: 10px 30px !important;
  font-style: italic;
}
.item {
  padding: 8px 15px !important;
  color: #495057;
}
.item-code {
  width: 15%;
  font-family: 'Courier New', monospace;
  padding-left: 50px !important;
}
.item-name {
  width: 55%;
}
.amount {
  width: 30%;
  text-align: right;
  font-family: 'Courier New', monospace;
  font-weight: 500;
}
.subtotal {
  background: #e9ecef;
  font-weight: 700;
  padding: 12px 15px !important;
}
.laba-kotor {
  background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
  color: white;
  font-weight: 700;
  font-size: 16px;
  padding: 15px !important;
}
.laba-bersih {
  font-weight: 700;
  font-size: 18px;
  padding: 15px !important;
}
.laba-bersih.profit {
  background: linear-gradient(135deg, #28a745 0%, #218838 100%);
  color: white;
}
.laba-bersih.loss {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
}
.result-box {
  padding: 2rem;
  border-radius: 0.5rem;
  margin-top: 1rem;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.result-box.profit {
  background: linear-gradient(135deg, #28a745 0%, #218838 100%);
  color: white;
}
.result-box.loss {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
}
.result-label {
  font-size: 1rem;
  opacity: 0.9;
  margin-bottom: 10px;
  font-weight: 600;
}
.result-value {
  font-size: 2.5rem;
  font-weight: 700;
  font-family: 'Courier New', monospace;
}
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
</style>

<section class="content">
  <div class="card">
    <div class="card-header card-header-custom">
      <h3 class="card-title card-title-custom">
        <i class="fas fa-chart-line"></i>
        Laporan Laba Rugi
      </h3>
      <p class="card-subtitle mb-0">Income Statement - Profit & Loss Report</p>
    </div>
    
    <div class="card-body">
      <!-- Filter Section -->
      <div class="filter-section">
        <form method="GET" action="{{ url('jurnal/labarugi') }}" class="form-row align-items-end">
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
        <div class="report-title">LAPORAN LABA RUGI</div>
        <small>
          Periode {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d F Y') }} 
          s/d {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}
        </small>
      </div>

      <!-- Export Buttons -->
      <div class="mb-3 text-right">
        <a href="{{ url('jurnal/labarugi/excel') }}?tanggal_awal={{ $tanggalAwal }}&tanggal_akhir={{ $tanggalAkhir }}" 
           class="btn btn-success btn-sm">
          <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <a href="{{ url('jurnal/labarugi/pdf') }}?tanggal_awal={{ $tanggalAwal }}&tanggal_akhir={{ $tanggalAkhir }}" 
           class="btn btn-danger btn-sm" target="_blank">
          <i class="fas fa-file-pdf"></i> Export PDF
        </a>
      </div>

      <!-- Laba Rugi Table -->
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
      <div class="result-label">
        <i class="fas {{ $labaBersih >= 0 ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
        {{ $labaBersih >= 0 ? 'LABA BERSIH PERIODE INI' : 'RUGI BERSIH PERIODE INI' }}
      </div>
      <div class="result-value">
        Rp {{ number_format(abs($labaBersih), 0, ',', '.') }}
      </div>
    </div>

    </div>
  </div>
</section>
@endsection
