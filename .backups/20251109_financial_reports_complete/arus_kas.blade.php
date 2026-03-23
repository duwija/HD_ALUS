@extends('layout.main')
@section('title', 'Laporan Arus Kas')

@section('content')
<style>
  .aruskascontainer {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    max-width: 1200px;
    margin: 0 auto;
  }
  .aruskas-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
  }
  .aruskas-title {
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
  }
  .aruskas-subtitle {
    font-size: 14px;
    color: #7f8c8d;
  }
  .filter-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
  }
  .saldo-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    background: #f8f9fa;
    padding: 12px 15px;
    font-weight: 600;
    color: #2c3e50;
    border-left: 4px solid;
    margin-bottom: 10px;
    font-size: 16px;
  }
  .aktivitas-header.operasional {
    border-left-color: #3498db;
  }
  .aktivitas-header.investasi {
    border-left-color: #e74c3c;
  }
  .aktivitas-header.pendanaan {
    border-left-color: #f39c12;
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
</style>

<div class="container-fluid">
  <div class="aruskascontainer">
    
    <!-- Header -->
    <div class="aruskas-header">
      <h3 style="margin: 0 0 5px 0; font-size: 16px; font-weight: 600; color: #2c3e50;">
        {{ config('app.company', env('COMPANY','Perusahaan')) }}
      </h3>
      <div class="aruskas-title">LAPORAN ARUS KAS</div>
      <div class="aruskas-subtitle">
        Metode: <strong>{{ $mode == 'direct' ? 'Langsung (Direct)' : 'Tidak Langsung (Indirect)' }}</strong><br>
        Periode {{ \Carbon\Carbon::parse($tanggalAwal)->translatedFormat('d F Y') }} 
        s/d {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d F Y') }}
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <form method="GET" action="{{ url('jurnal/arus-kas') }}" class="form-inline">
        <div class="form-group mr-2">
          <label class="mr-2" style="font-weight: 500;">Dari:</label>
          <input type="date" name="tanggal_awal" class="form-control form-control-sm" value="{{ $tanggalAwal }}" style="min-width: 150px;">
        </div>
        <div class="form-group mr-2">
          <label class="mr-2" style="font-weight: 500;">Sampai:</label>
          <input type="date" name="tanggal_akhir" class="form-control form-control-sm" value="{{ $tanggalAkhir }}" style="min-width: 150px;">
        </div>
        <div class="form-group mr-2">
          <label class="mr-2" style="font-weight: 500;">Metode:</label>
          <select name="mode" class="form-control form-control-sm" style="min-width: 150px;">
            <option value="direct" {{ $mode=='direct' ? 'selected' : '' }}>Langsung (Direct)</option>
            <option value="indirect" {{ $mode=='indirect' ? 'selected' : '' }}>Tidak Langsung (Indirect)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm mr-2">
          <i class="fas fa-filter"></i> Tampilkan
        </button>
        <a href="{{ url('jurnal/arus-kas/pdf?tanggal_awal=' . $tanggalAwal . '&tanggal_akhir=' . $tanggalAkhir . '&mode=' . $mode) }}" class="btn btn-sm btn-outline-danger mr-2" target="_blank">
          <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a href="{{ url('jurnal/arus-kas/excel?tanggal_awal=' . $tanggalAwal . '&tanggal_akhir=' . $tanggalAkhir . '&mode=' . $mode) }}" class="btn btn-sm btn-outline-success">
          <i class="fas fa-file-excel"></i> Excel
        </a>
      </form>
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
