@extends('layout.main')
@section('title', 'Laporan Arus Kas')

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

  .aruskascontainer {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .saldo-box {
    background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .saldo-item {
    text-align: center;
  }
  .saldo-label {
    font-size: 12px;
    opacity: 0.9;
    margin-bottom: 5px;
  }
  .saldo-value {
    font-size: 20px;
    font-weight: 700;
    font-family: 'Courier New', monospace;
  }
  .aktivitas-section {
    margin-bottom: 30px;
  }
  .aktivitas-header {
    background: #4a90e2;
    color: white;
    padding: 12px 15px;
    font-weight: 600;
    border-left: 4px solid #357abd;
    margin-bottom: 10px;
    font-size: 16px;
  }
  .aktivitas-header.operasional {
    background: #3498db;
    border-left-color: #2980b9;
  }
  .aktivitas-header.investasi {
    background: #e74c3c;
    border-left-color: #c0392b;
  }
  .aktivitas-header.pendanaan {
    background: #f39c12;
    border-left-color: #d68910;
  }
  .detail-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
    font-size: 13px;
  }
  .detail-table th {
    background: #f8f9fa;
    padding: 8px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
  }
  .detail-table td {
    padding: 6px 8px;
    border-bottom: 1px solid #f8f9fa;
  }
  .detail-table tr:hover {
    background: #f1f3f5;
  }
  .subtotal-row {
    background: #e9ecef;
    font-weight: 600;
  }
  .total-row {
    background: #dee2e6;
    font-weight: 700;
    font-size: 14px;
  }
  .amount {
    text-align: right;
    font-family: 'Courier New', monospace;
    white-space: nowrap;
  }
  .amount.positive {
    color: #28a745;
  }
  .amount.negative {
    color: #dc3545;
  }
  .metode-indirect-detail {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 15px;
  }
  .metode-indirect-detail table {
    width: 100%;
    font-size: 13px;
  }
  .metode-indirect-detail td {
    padding: 5px 0;
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
        <i class="fas fa-water"></i>
        Laporan Arus Kas
      </h3>
      <p class="card-subtitle mb-0">Cash Flow Statement - Metode {{ $mode == 'direct' ? 'Langsung (Direct)' : 'Tidak Langsung (Indirect)' }}</p>
    </div>
    
    <div class="card-body">
      <!-- Filter Section -->
      <div class="filter-section">
        <form method="GET" action="{{ url('jurnal/arus-kas') }}" class="form-row align-items-end">
          <div class="col-md-3">
            <label class="filter-label">
              <i class="far fa-calendar-alt"></i> Tanggal Awal
            </label>
            <input type="date" name="tanggal_awal" class="form-control" value="{{ $tanggalAwal }}">
          </div>
          <div class="col-md-3">
            <label class="filter-label">
              <i class="far fa-calendar-check"></i> Tanggal Akhir
            </label>
            <input type="date" name="tanggal_akhir" class="form-control" value="{{ $tanggalAkhir }}">
          </div>
          <div class="col-md-3">
            <label class="filter-label">
              <i class="fas fa-exchange-alt"></i> Metode
            </label>
            <select name="mode" class="form-control">
              <option value="direct" {{ $mode=='direct' ? 'selected' : '' }}>Langsung (Direct)</option>
              <option value="indirect" {{ $mode=='indirect' ? 'selected' : '' }}>Tidak Langsung (Indirect)</option>
            </select>
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fas fa-filter"></i> Filter
            </button>
          </div>
        </form>
      </div>

      <!-- Report Header -->
      <div class="report-header">
        <h3>{{ config('app.company', env('COMPANY','Perusahaan')) }}</h3>
        <div class="report-title">LAPORAN ARUS KAS</div>
        <small>
          Periode {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d F Y') }} 
          s/d {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}
        </small>
      </div>

      <!-- Export Buttons -->
      <div class="mb-3 text-right">
        <a href="{{ url('jurnal/arus-kas/excel?tanggal_awal=' . $tanggalAwal . '&tanggal_akhir=' . $tanggalAkhir . '&mode=' . $mode) }}" 
           class="btn btn-success btn-sm">
          <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <a href="{{ url('jurnal/arus-kas/pdf?tanggal_awal=' . $tanggalAwal . '&tanggal_akhir=' . $tanggalAkhir . '&mode=' . $mode) }}" 
           class="btn btn-danger btn-sm" target="_blank">
          <i class="fas fa-file-pdf"></i> Export PDF
        </a>
      </div>

    <!-- Saldo Awal & Akhir -->
    <div class="saldo-box">
      <div class="saldo-item">
        <div class="saldo-label">Saldo Awal Kas</div>
        <div class="saldo-value">{{ number_format($saldoAwal, 0, ',', '.') }}</div>
      </div>
      <div class="saldo-item">
        <i class="fas fa-arrow-right" style="font-size: 24px; opacity: 0.7;"></i>
      </div>
      <div class="saldo-item">
        <div class="saldo-label">Saldo Akhir Kas</div>
        <div class="saldo-value">{{ number_format($saldoAkhir, 0, ',', '.') }}</div>
      </div>
    </div>

    @if($mode == 'direct')
      <!-- METODE LANGSUNG -->
      
      <!-- Aktivitas Operasional -->
      <div class="aktivitas-section">
        <div class="aktivitas-header operasional">
          <i class="fas fa-cog mr-2"></i>ARUS KAS DARI AKTIVITAS OPERASIONAL
        </div>
        @if(count($detailOperasional) > 0)
        <table class="detail-table">
          <thead>
            <tr>
              <th width="100">Tanggal</th>
              <th width="120">No. Ref</th>
              <th>Keterangan</th>
              <th width="150">Akun Lawan</th>
              <th width="120" class="amount">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            @foreach($detailOperasional as $item)
            <tr>
              <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
              <td>
                <span class="badge badge-primary cursor-pointer view-jurnal" data-code="{{ $item['no_ref'] }}" style="cursor: pointer;">
                  {{ $item['no_ref'] }}
                </span>
              </td>
              <td>{{ $item['description'] }}</td>
              <td style="font-size: 11px;">{{ $item['lawan_akun'] }}</td>
              <td class="amount {{ $item['nilai'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($item['nilai'], 0, ',', '.') }}
              </td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
              <td colspan="4">Kas Bersih dari Aktivitas Operasional</td>
              <td class="amount {{ $arusKasDirect['operasional'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($arusKasDirect['operasional'], 0, ',', '.') }}
              </td>
            </tr>
          </tbody>
        </table>
        @else
        <p class="text-muted text-center py-3">Tidak ada transaksi operasional</p>
        @endif
      </div>

      <!-- Aktivitas Investasi -->
      <div class="aktivitas-section">
        <div class="aktivitas-header investasi">
          <i class="fas fa-chart-line mr-2"></i>ARUS KAS DARI AKTIVITAS INVESTASI
        </div>
        @if(count($detailInvestasi) > 0)
        <table class="detail-table">
          <thead>
            <tr>
              <th width="100">Tanggal</th>
              <th width="120">No. Ref</th>
              <th>Keterangan</th>
              <th width="150">Akun Lawan</th>
              <th width="120" class="amount">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            @foreach($detailInvestasi as $item)
            <tr>
              <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
              <td><span class="badge badge-primary cursor-pointer view-jurnal" data-code="{{ $item['no_ref'] }}" style="cursor: pointer;">{{ $item['no_ref'] }}</span></td>
              <td>{{ $item['description'] }}</td>
              <td style="font-size: 11px;">{{ $item['lawan_akun'] }}</td>
              <td class="amount {{ $item['nilai'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($item['nilai'], 0, ',', '.') }}
              </td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
              <td colspan="4">Kas Bersih dari Aktivitas Investasi</td>
              <td class="amount {{ $arusKasDirect['investasi'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($arusKasDirect['investasi'], 0, ',', '.') }}
              </td>
            </tr>
          </tbody>
        </table>
        @else
        <p class="text-muted text-center py-3">Tidak ada transaksi investasi</p>
        @endif
      </div>

      <!-- Aktivitas Pendanaan -->
      <div class="aktivitas-section">
        <div class="aktivitas-header pendanaan">
          <i class="fas fa-hand-holding-usd mr-2"></i>ARUS KAS DARI AKTIVITAS PENDANAAN
        </div>
        @if(count($detailPendanaan) > 0)
        <table class="detail-table">
          <thead>
            <tr>
              <th width="100">Tanggal</th>
              <th width="120">No. Ref</th>
              <th>Keterangan</th>
              <th width="150">Akun Lawan</th>
              <th width="120" class="amount">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            @foreach($detailPendanaan as $item)
            <tr>
              <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
              <td><span class="badge badge-primary cursor-pointer view-jurnal" data-code="{{ $item['no_ref'] }}" style="cursor: pointer;">{{ $item['no_ref'] }}</span></td>
              <td>{{ $item['description'] }}</td>
              <td style="font-size: 11px;">{{ $item['lawan_akun'] }}</td>
              <td class="amount {{ $item['nilai'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($item['nilai'], 0, ',', '.') }}
              </td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
              <td colspan="4">Kas Bersih dari Aktivitas Pendanaan</td>
              <td class="amount {{ $arusKasDirect['pendanaan'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($arusKasDirect['pendanaan'], 0, ',', '.') }}
              </td>
            </tr>
          </tbody>
        </table>
        @else
        <p class="text-muted text-center py-3">Tidak ada transaksi pendanaan</p>
        @endif
      </div>

    @else
      <!-- METODE TIDAK LANGSUNG -->
      
      <!-- Aktivitas Operasional -->
      <div class="aktivitas-section">
        <div class="aktivitas-header operasional">
          <i class="fas fa-cog mr-2"></i>ARUS KAS DARI AKTIVITAS OPERASIONAL
        </div>
        <div class="metode-indirect-detail">
          <table>
            <tr>
              <td width="70%">Laba Bersih</td>
              <td class="amount" width="30%">{{ number_format($labaBersih, 0, ',', '.') }}</td>
            </tr>
            <tr style="font-weight: 600;">
              <td colspan="2" style="padding-top: 10px;">Penyesuaian untuk:</td>
            </tr>
            <tr>
              <td style="padding-left: 20px;">Penyusutan & Amortisasi</td>
              <td class="amount">{{ number_format($penyusutan, 0, ',', '.') }}</td>
            </tr>
            <tr>
              <td style="padding-left: 20px;">(Kenaikan) Penurunan Piutang</td>
              <td class="amount">{{ number_format(-$perubahanPiutang, 0, ',', '.') }}</td>
            </tr>
            <tr>
              <td style="padding-left: 20px;">(Kenaikan) Penurunan Persediaan</td>
              <td class="amount">{{ number_format(-$perubahanPersediaan, 0, ',', '.') }}</td>
            </tr>
            <tr>
              <td style="padding-left: 20px;">Kenaikan (Penurunan) Hutang</td>
              <td class="amount">{{ number_format($perubahanHutang, 0, ',', '.') }}</td>
            </tr>
            <tr class="subtotal-row">
              <td><strong>Kas Bersih dari Aktivitas Operasional</strong></td>
              <td class="amount"><strong>{{ number_format($arusKasIndirect['operasional'], 0, ',', '.') }}</strong></td>
            </tr>
          </table>
        </div>
      </div>

      <!-- Aktivitas Investasi -->
      <div class="aktivitas-section">
        <div class="aktivitas-header investasi">
          <i class="fas fa-chart-line mr-2"></i>ARUS KAS DARI AKTIVITAS INVESTASI
        </div>
        @if(count($detailInvestasi) > 0)
        <table class="detail-table">
          <thead>
            <tr>
              <th width="100">Tanggal</th>
              <th width="120">No. Ref</th>
              <th>Keterangan</th>
              <th width="150">Akun Lawan</th>
              <th width="120" class="amount">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            @foreach($detailInvestasi as $item)
            <tr>
              <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
              <td><span class="badge badge-primary cursor-pointer view-jurnal" data-code="{{ $item['no_ref'] }}" style="cursor: pointer;">{{ $item['no_ref'] }}</span></td>
              <td>{{ $item['description'] }}</td>
              <td style="font-size: 11px;">{{ $item['lawan_akun'] }}</td>
              <td class="amount {{ $item['nilai'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($item['nilai'], 0, ',', '.') }}
              </td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
              <td colspan="4">Kas Bersih dari Aktivitas Investasi</td>
              <td class="amount {{ $arusKasIndirect['investasi'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($arusKasIndirect['investasi'], 0, ',', '.') }}
              </td>
            </tr>
          </tbody>
        </table>
        @else
        <p class="text-muted text-center py-3">Tidak ada transaksi investasi</p>
        @endif
      </div>

      <!-- Aktivitas Pendanaan -->
      <div class="aktivitas-section">
        <div class="aktivitas-header pendanaan">
          <i class="fas fa-hand-holding-usd mr-2"></i>ARUS KAS DARI AKTIVITAS PENDANAAN
        </div>
        @if(count($detailPendanaan) > 0)
        <table class="detail-table">
          <thead>
            <tr>
              <th width="100">Tanggal</th>
              <th width="120">No. Ref</th>
              <th>Keterangan</th>
              <th width="150">Akun Lawan</th>
              <th width="120" class="amount">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            @foreach($detailPendanaan as $item)
            <tr>
              <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
              <td><span class="badge badge-primary cursor-pointer view-jurnal" data-code="{{ $item['no_ref'] }}" style="cursor: pointer;">{{ $item['no_ref'] }}</span></td>
              <td>{{ $item['description'] }}</td>
              <td style="font-size: 11px;">{{ $item['lawan_akun'] }}</td>
              <td class="amount {{ $item['nilai'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($item['nilai'], 0, ',', '.') }}
              </td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
              <td colspan="4">Kas Bersih dari Aktivitas Pendanaan</td>
              <td class="amount {{ $arusKasIndirect['pendanaan'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($arusKasIndirect['pendanaan'], 0, ',', '.') }}
              </td>
            </tr>
          </tbody>
        </table>
        @else
        <p class="text-muted text-center py-3">Tidak ada transaksi pendanaan</p>
        @endif
      </div>
    @endif

    <!-- Summary -->
    <table class="detail-table" style="margin-top: 30px;">
      <tbody>
        @php
          $arus = $mode == 'direct' ? $arusKasDirect : $arusKasIndirect;
          $kenaikanKas = $arus['operasional'] + $arus['investasi'] + $arus['pendanaan'];
        @endphp
        <tr class="total-row">
          <td>KENAIKAN (PENURUNAN) KAS BERSIH</td>
          <td class="amount {{ $kenaikanKas >= 0 ? 'positive' : 'negative' }}" width="150">
            {{ number_format($kenaikanKas, 0, ',', '.') }}
          </td>
        </tr>
        <tr>
          <td>Saldo Awal Kas</td>
          <td class="amount">{{ number_format($saldoAwal, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row" style="background: #667eea; color: white;">
          <td><strong>SALDO AKHIR KAS</strong></td>
          <td class="amount"><strong>{{ number_format($saldoAkhir, 0, ',', '.') }}</strong></td>
        </tr>
      </tbody>
    </table>

  </div>
</div>

<!-- Modal View Jurnal -->
<div class="modal fade" id="modalViewJurnal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Detail Jurnal</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="modal-jurnal-content">
        <div class="text-center p-4">
          <i class="fa fa-spinner fa-spin fa-2x"></i><br>
          Memuat data...
        </div>
      </div>
    </div>

    </div>
  </div>
</section>

@endsection

@section('footer-scripts')
<script>
// Event klik badge code untuk melihat detail jurnal
$(document).on('click', '.view-jurnal', function () {
  const code = $(this).data('code');
  $('#modalViewJurnal').modal('show');

  // Loading state
  $('#modal-jurnal-content').html(`
    <div class="text-center p-4">
      <i class="fa fa-spinner fa-spin fa-2x"></i><br>
      Memuat data...
    </div>
  `);

  // Ambil konten via AJAX
  $.ajax({
    url: '/jurnal/show/' + code,
    type: 'GET',
    success: function (html) {
      $('#modal-jurnal-content').html(html);
    },
    error: function () {
      $('#modal-jurnal-content').html(`
        <div class="alert alert-danger">
          Gagal memuat data jurnal. Silakan coba lagi.
        </div>
      `);
    }
  });
});
</script>
@endsection
