@extends('layout.main')
@section('title', 'Pajak & Kewajiban ISP')

@section('content')
@php
  $rp = fn($v) => 'Rp ' . number_format($v, 0, ',', '.');
  $tgl = fn($d) => $d->translatedFormat('d M Y');
  $periodLabel = $tgl($from) . ' s/d ' . $tgl($to);
@endphp
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold m-0">
        <i class="fas fa-receipt text-danger"></i> Pajak &amp; Kewajiban ISP &middot; {{ tenant_name() ?: config('app.name') }}
      </h3>
    </div>

    <div class="card-body">
      {{-- Filter periode --}}
      <form method="GET" class="form-row align-items-end mb-2">
        <div class="col-sm-auto mb-2">
          <label class="mb-1 small font-weight-bold">Dari tanggal</label>
          <input type="date" name="from" class="form-control form-control-sm" value="{{ $from->format('Y-m-d') }}" required>
        </div>
        <div class="col-sm-auto mb-2">
          <label class="mb-1 small font-weight-bold">Sampai tanggal</label>
          <input type="date" name="to" class="form-control form-control-sm" value="{{ $to->format('Y-m-d') }}" required>
        </div>
        <div class="col-sm-auto mb-2">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Tampilkan</button>
        </div>
        <div class="col-sm mb-2 text-sm-right">
          @foreach ($presets as $label => [$pFrom, $pTo])
            @php $active = $pFrom->isSameDay($from) && $pTo->isSameDay($to); @endphp
            <a href="?from={{ $pFrom->format('Y-m-d') }}&to={{ $pTo->format('Y-m-d') }}"
               class="btn btn-sm {{ $active ? 'btn-secondary' : 'btn-outline-secondary' }} mb-1">{{ $label }}</a>
          @endforeach
        </div>
      </form>

      @if ($limited)
        <div class="alert alert-warning py-2 small">
          <i class="fas fa-exclamation-triangle"></i> Periode maksimal 36 bulan &mdash; tanggal awal disesuaikan menjadi {{ $tgl($from) }}.
        </div>
      @endif

      <p class="text-muted mb-3"><i class="far fa-calendar-alt"></i> Periode: <strong>{{ $periodLabel }}</strong></p>

      <div class="row">
        <div class="col-lg col-md-4 col-6 mb-3">
          <div class="small-box bg-dark mb-0"><div class="inner">
            <h5 class="font-weight-bold">{{ $rp($totals['revenue']) }}</h5><p class="mb-0">Pendapatan</p>
          </div></div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-3">
          <div class="small-box bg-secondary mb-0"><div class="inner">
            <h5 class="font-weight-bold">{{ $rp($totals['tax']['dpp']) }}</h5><p class="mb-0">DPP</p>
          </div></div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-3">
          <div class="small-box bg-info mb-0"><div class="inner">
            <h5 class="font-weight-bold">{{ $rp($totals['tax']['ppn']) }}</h5><p class="mb-0">PPN {{ number_format($totals['tax']['ppn_rate'], 0, ',', '.') }}%</p>
          </div></div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-3">
          <div class="small-box mb-0" style="background:#6d28d9;color:#fff"><div class="inner">
            <h5 class="font-weight-bold">{{ $rp($totals['tax']['bhp']) }}</h5><p class="mb-0">BHP Tel {{ number_format($totals['tax']['bhp_rate'], 1, ',', '.') }}%</p>
          </div></div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-3">
          <div class="small-box bg-warning mb-0"><div class="inner">
            <h5 class="font-weight-bold">{{ $rp($totals['tax']['uso']) }}</h5><p class="mb-0">USO {{ number_format($totals['tax']['uso_rate'], 2, ',', '.') }}%</p>
          </div></div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-3">
          <div class="small-box bg-danger mb-0"><div class="inner">
            <h5 class="font-weight-bold">{{ $rp($totals['tax']['total_kewajiban']) }}</h5><p class="mb-0">Total Kewajiban</p>
          </div></div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover table-sm mb-0">
          <thead class="thead-dark">
            <tr>
              <th>Periode</th>
              <th class="text-right">Pendapatan</th>
              <th class="text-right">DPP</th>
              <th class="text-right">PPN {{ number_format($totals['tax']['ppn_rate'], 0, ',', '.') }}%</th>
              <th class="text-right">BHP Tel {{ number_format($totals['tax']['bhp_rate'], 1, ',', '.') }}%</th>
              <th class="text-right">USO {{ number_format($totals['tax']['uso_rate'], 2, ',', '.') }}%</th>
              <th class="text-right">Total Kewajiban</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($rows as $row)
            @php
              $fullMonth = $row['from']->day === 1 && $row['to']->isSameDay($row['to']->copy()->endOfMonth());
            @endphp
            <tr>
              <td>
                <strong>{{ $row['from']->translatedFormat('F Y') }}</strong>
                @unless ($fullMonth)
                  <br><small class="text-muted">{{ $row['from']->format('j') }}&ndash;{{ $row['to']->format('j') }} {{ $row['to']->translatedFormat('M Y') }}</small>
                @endunless
              </td>
              <td class="text-right">{{ $rp($row['revenue']) }}</td>
              <td class="text-right">{{ $rp($row['tax']['dpp']) }}</td>
              <td class="text-right">{{ $rp($row['tax']['ppn']) }}</td>
              <td class="text-right">{{ $rp($row['tax']['bhp']) }}</td>
              <td class="text-right">{{ $rp($row['tax']['uso']) }}</td>
              <td class="text-right font-weight-bold text-danger">{{ $rp($row['tax']['total_kewajiban']) }}</td>
            </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr class="font-weight-bold bg-light">
              <td class="text-right">Total periode</td>
              <td class="text-right">{{ $rp($totals['revenue']) }}</td>
              <td class="text-right">{{ $rp($totals['tax']['dpp']) }}</td>
              <td class="text-right">{{ $rp($totals['tax']['ppn']) }}</td>
              <td class="text-right">{{ $rp($totals['tax']['bhp']) }}</td>
              <td class="text-right">{{ $rp($totals['tax']['uso']) }}</td>
              <td class="text-right text-danger">{{ $rp($totals['tax']['total_kewajiban']) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      {{-- Rincian per kas/bank --}}
      <h5 class="mt-4 mb-2 font-weight-bold"><i class="fas fa-wallet text-success"></i> Rincian per Kas &middot; {{ $periodLabel }}</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0" id="kas-table">
          <thead class="thead-light">
            <tr>
              <th>Kas / Bank</th>
              <th class="text-right">Transaksi</th>
              <th class="text-right">Pendapatan</th>
              <th class="text-right">DPP</th>
              <th class="text-right">PPN</th>
              <th class="text-right">BHP Tel</th>
              <th class="text-right">USO</th>
              <th class="text-right">Total Kewajiban</th>
              <th class="text-center" width="130">Detail</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($kasRows as $i => $kas)
            <tr>
              <td>
                <strong>{{ $kas['name'] }}</strong><br>
                <small class="text-muted">{{ $kas['code'] }}
                  &middot; {{ $totals['revenue'] > 0 ? number_format($kas['revenue'] / $totals['revenue'] * 100, 1, ',', '.') : 0 }}% dari pendapatan</small>
              </td>
              <td class="text-right">{{ number_format($kas['count'], 0, ',', '.') }}</td>
              <td class="text-right">{{ $rp($kas['revenue']) }}</td>
              <td class="text-right">{{ $rp($kas['tax']['dpp']) }}</td>
              <td class="text-right">{{ $rp($kas['tax']['ppn']) }}</td>
              <td class="text-right">{{ $rp($kas['tax']['bhp']) }}</td>
              <td class="text-right">{{ $rp($kas['tax']['uso']) }}</td>
              <td class="text-right font-weight-bold text-danger">{{ $rp($kas['tax']['total_kewajiban']) }}</td>
              <td class="text-center">
                <button type="button" class="btn btn-outline-primary btn-sm kas-toggle" data-kas="{{ $kas['code'] }}" data-target="#kas-detail-{{ $i }}">
                  <i class="fas fa-list"></i> Lihat transaksi
                </button>
              </td>
            </tr>
            <tr id="kas-detail-{{ $i }}" class="kas-detail" style="display:none">
              <td colspan="9" class="bg-light">
                <div class="kas-detail-body p-2"><span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Memuat transaksi...</span></div>
              </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted py-3">Tidak ada pembayaran pada periode ini.</td></tr>
            @endforelse
          </tbody>
          @if (count($kasRows))
          <tfoot>
            <tr class="font-weight-bold bg-light">
              <td class="text-right">Total semua kas</td>
              <td class="text-right">{{ number_format($kasTotals['count'], 0, ',', '.') }}</td>
              <td class="text-right">{{ $rp($kasTotals['revenue']) }}</td>
              <td class="text-right">{{ $rp($kasTotals['tax']['dpp']) }}</td>
              <td class="text-right">{{ $rp($kasTotals['tax']['ppn']) }}</td>
              <td class="text-right">{{ $rp($kasTotals['tax']['bhp']) }}</td>
              <td class="text-right">{{ $rp($kasTotals['tax']['uso']) }}</td>
              <td class="text-right text-danger">{{ $rp($kasTotals['tax']['total_kewajiban']) }}</td>
              <td></td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
      <small class="text-muted d-block mt-1">
        Kas = akun tujuan pembayaran invoice. Pajak per kas dihitung dari total pendapatan kas tersebut dalam periode,
        jadi jumlahnya bisa selisih beberapa rupiah (pembulatan) dari total per bulan di atas.
      </small>
    </div>

    <div class="card-footer small text-muted">
      Pendapatan = pembayaran invoice yang diterima (lunas) dengan tanggal bayar dalam periode, sudah termasuk PPN.
      DPP = Pendapatan &divide; 1,11 &middot; PPN = Pendapatan &minus; DPP &middot; BHP Telekomunikasi &amp; USO dihitung dari DPP.
      Pajak dihitung per bulan; total periode = jumlah per bulan.
    </div>
  </div>
</section>
@endsection

@section('footer-scripts')
<script>
(function () {
  var txUrl = '{{ url('isp/pajak/transaksi') }}';
  var period = { from: '{{ $from->format('Y-m-d') }}', to: '{{ $to->format('Y-m-d') }}' };
  var esc = function (s) { return $('<div>').text(s == null ? '' : String(s)).html(); };
  var rupiah = function (v) { return 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 }); };

  $('#kas-table').on('click', '.kas-toggle', function () {
    var $btn = $(this);
    var $row = $($btn.data('target'));
    var $body = $row.find('.kas-detail-body');

    $row.toggle();
    $btn.html($row.is(':visible') ? '<i class="fas fa-times"></i> Tutup' : '<i class="fas fa-list"></i> Lihat transaksi');
    if (!$row.is(':visible') || $row.data('loaded')) return;
    $row.data('loaded', true);

    $.getJSON(txUrl, $.extend({ kas: $btn.data('kas') }, period)).done(function (res) {
      var $table = $('<table class="table table-sm table-striped table-bordered bg-white mb-0" style="width:100%">' +
        '<thead><tr><th>#</th><th>Tanggal Bayar</th><th>No. Invoice</th><th>Pelanggan</th><th class="text-right">Nominal</th></tr></thead></table>');
      $body.empty().append($table);
      $table.DataTable({
        data: res.data,
        order: [[1, 'asc']],
        pageLength: 25,
        columns: [
          { data: null, orderable: false, render: function (d, t, r, meta) { return meta.row + 1; } },
          { data: 'payment_date' },
          { data: 'number', render: function (d, t, r) { return t === 'display' ? '<a href="/suminvoice/' + r.id + '" target="_blank">' + esc(d) + '</a>' : d; } },
          { data: 'customer_name', render: function (d, t, r) {
              if (t !== 'display') return (r.customer_code || '') + ' ' + (d || '');
              return r.customer_id ? '<a href="/customer/' + r.customer_id + '" target="_blank">' + esc(d || '-') + '</a> <small class="text-muted">' + esc(r.customer_code) + '</small>' : '-';
          } },
          { data: 'amount', className: 'text-right', render: function (d, t) { return t === 'display' ? rupiah(d) : d; } }
        ],
        language: { search: 'Cari:', lengthMenu: 'Tampil _MENU_', info: '_START_–_END_ dari _TOTAL_ transaksi', infoEmpty: 'Tidak ada transaksi', infoFiltered: '(disaring dari _MAX_)', zeroRecords: 'Tidak ada transaksi yang cocok', paginate: { previous: '&lsaquo;', next: '&rsaquo;' } }
      });
    }).fail(function () {
      $row.data('loaded', false);
      $body.html('<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Gagal memuat transaksi. Coba lagi.</span>');
    });
  });
})();
</script>
@endsection
