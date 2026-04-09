@extends('layout.main')
@section('title', 'Rasio Keuangan')

@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title font-weight-bold m-0">Laporan Rasio Keuangan</h3>

    </div>

    <div class="card-body">
      {{-- Filter --}}
      <form method="GET" class="mb-3 row">
        <div class="col-md-3">
          <label>Tanggal Awal</label>
          <input type="text" id="rk_awal_display" class="form-control" autocomplete="off" readonly
            value="{{ \Carbon\Carbon::parse($tanggalAwal)->format('d/m/Y') }}">
          <input type="hidden" name="tanggal_awal" id="rk_awal_hidden" value="{{ $tanggalAwal }}">
        </div>
        <div class="col-md-3">
          <label>Tanggal Akhir</label>
          <input type="text" id="rk_akhir_display" class="form-control" autocomplete="off" readonly
            value="{{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}">
          <input type="hidden" name="tanggal_akhir" id="rk_akhir_hidden" value="{{ $tanggalAkhir }}">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary ">
            <i class="fas fa-filter"></i> Filter
          </button>
        </div>
    <!--     <div class="col-md-3 d-flex align-items-end">
          <div class="form-group mb-0 text-md-right mt-3 mt-md-0">
            <a href="{{ url()->current().'?'.http_build_query(request()->all()).'&export=pdf' }}" class="btn btn-danger btn-sm">
              <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="{{ url()->current().'?'.http_build_query(request()->all()).'&export=excel' }}" class="btn btn-success btn-sm">
              <i class="fas fa-file-excel"></i> Export Excel
            </a>
          </div>
        </div> -->
      </form>

      {{-- ====== RASIO POSISI ====== --}}
      <h5 class="mt-4">📌 Rasio Posisi (Snapshot - Per {{ $tanggalAkhir }})</h5>
      <table class="table table-bordered text-center align-middle">
        <thead class="thead-dark">
          <tr>
            <th style="width:35%">Rasio</th>
            <th style="width:25%">Nilai</th>
            <th style="width:40%">Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rasioPosisi as $r)
          <tr class="@if(str_contains($r['status'],'❌')) table-danger @elseif(str_contains($r['status'],'⚠')) table-warning @else table-success @endif">
            <td class="text-left">
              <strong>{{ $r['nama'] }}</strong><br>
              <small class="text-muted">
                @switch($r['nama'])
                @case('Current Ratio') Mengukur kemampuan bayar hutang jangka pendek dengan aset lancar. @break
                @case('Quick Ratio') Likuiditas tanpa persediaan (lebih konservatif). @break
                @case('Cash Ratio') Berapa banyak hutang lancar bisa dibayar hanya dengan kas. @break
                @case('Debt to Equity') Proporsi hutang terhadap modal sendiri. @break
                @case('Debt to Asset') Persentase aset yang dibiayai hutang. @break
                @endswitch
              </small>
            </td>
            <td class="text-right">{{ number_format($r['nilai'] * 100, 2) }} %</td>
            <td>{{ $r['status'] }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>

      {{-- ====== RASIO KINERJA ====== --}}
      <h5 class="mt-4">📆 Rasio Kinerja (Periode: {{ \Carbon\Carbon::parse($tanggalAwal)->format('d M Y') }} - {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d M Y') }})</h5>
      <table class="table table-bordered text-center align-middle">
        <thead class="thead-dark">
          <tr>
            <th style="width:35%">Rasio</th>
            <th style="width:25%">Nilai</th>
            <th style="width:40%">Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rasioKinerja as $r)
          <tr class="@if(str_contains($r['status'],'❌')) table-danger @elseif(str_contains($r['status'],'⚠')) table-warning @else table-success @endif">
            <td class="text-left">
              <strong>{{ $r['nama'] }}</strong><br>
              <small class="text-muted">
                @switch($r['nama'])
                @case('Gross Profit Margin') Persentase laba kotor dari penjualan. @break
                @case('Net Profit Margin') Laba bersih dibandingkan penjualan. @break
                @case('ROA') Efektivitas aset menghasilkan laba. @break
                @case('ROE') Tingkat pengembalian modal pemilik. @break
                @case('Perputaran Piutang') Seberapa cepat piutang tertagih. @break
                @case('Perputaran Persediaan') Seberapa cepat persediaan habis. @break
                @endswitch
              </small>
            </td>
            <td class="text-right">{{ number_format($r['nilai'] * 100, 2) }} %</td>
            <td>{{ $r['status'] }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>

      {{-- ====== BASIS ====== --}}
      <h6 class="mt-4">Basis Perhitungan:</h6>
      <table class="table table-sm table-bordered">
        <tr><td>Total Aset</td><td class="text-right">Rp {{ number_format($totalAset, 2) }}</td></tr>
        <tr><td>Total Hutang</td><td class="text-right">Rp {{ number_format($totalHutang, 2) }}</td></tr>
        <tr><td>Modal / Ekuitas</td><td class="text-right">Rp {{ number_format($modal, 2) }}</td></tr>
        <tr><td>Laba Kotor</td><td class="text-right">Rp {{ number_format($labaKotor, 2) }}</td></tr>
        <tr><td>Laba Bersih</td><td class="text-right">Rp {{ number_format($labaBersih, 2) }}</td></tr>
      </table>

      <p class="text-muted"><small>
        📌 = Snapshot (akhir periode), 📆 = Kinerja periode. <br>
        Warna hijau = sehat, kuning = peringatan, merah = perlu perhatian serius.
      </small></p>
    </div>
  </div>
</section>




@endsection

@section('footer-scripts')
<script>
$(document).ready(function() {
  var dpOpts = { format: 'dd/mm/yyyy', todayHighlight: true, autoclose: true };
  $('#rk_awal_display').datepicker(dpOpts).on('changeDate', function(e) {
    var d = e.date;
    $('#rk_awal_hidden').val(d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'));
  });
  $('#rk_akhir_display').datepicker(dpOpts).on('changeDate', function(e) {
    var d = e.date;
    $('#rk_akhir_hidden').val(d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'));
  });
});
</script>
@endsection
