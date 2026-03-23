@extends('layout.main')
@section('title', 'Dashboard Accounting')
@section('content')

<style>
/* ── Base ─────────────────────────────────────────────────── */
.v2-wrap { padding: 0 4px; }
.v2-card {
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  margin-bottom: 16px;
}
.v2-card-hd {
  padding: 10px 16px;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.v2-card-hd-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: #888;
  margin: 0;
}
.v2-card-body { padding: 14px 16px; }

/* ── Greeting ────────────────────────────────────────────── */
.v2-greet {
  border-radius: 10px;
  background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
  color: #fff;
  padding: 16px 20px;
  margin-bottom: 16px;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
}
.v2-greet-wave { position: absolute; right: -16px; bottom: -24px; font-size: 80px; opacity: .08; pointer-events: none; }
.v2-greet-name { font-size: 17px; font-weight: 800; color: #fff; margin: 0; }
.v2-greet-sub  { font-size: 12px; color: rgba(255,255,255,.75); margin: 2px 0 0; }
.v2-greet-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.v2-link-btn {
  font-size: 11px; font-weight: 600; color: #fff;
  border: 1px solid rgba(255,255,255,.35); border-radius: 6px;
  padding: 5px 12px; text-decoration: none;
  background: rgba(255,255,255,.15);
  transition: background .15s; white-space: nowrap;
}
.v2-link-btn:hover { background: rgba(255,255,255,.28); color: #fff; text-decoration: none; }

/* ── Top stat cards (matching transaction page) ──────────── */
.ac-stats { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
.ac-stat-card {
  flex: 1; min-width: 140px;
  padding: 14px 16px;
  border-radius: 8px;
  border: 1px solid transparent;
}
.ac-stat-lbl  { font-size: 12px; margin-bottom: 6px; }
.ac-stat-val  { font-size: 1.15rem; font-weight: 800; line-height: 1; }

.ac-grey  { background: #e9ecef; border-color: #dee2e6; }
.ac-grey  .ac-stat-lbl { color: #495057; }
.ac-grey  .ac-stat-val { color: #343a40; }

.ac-green { background: #d4edda; border-color: #c3e6cb; }
.ac-green .ac-stat-lbl { color: #155724; }
.ac-green .ac-stat-val { color: #155724; }

.ac-blue  { background: #d1ecf1; border-color: #bee5eb; }
.ac-blue  .ac-stat-lbl { color: #0c5460; }
.ac-blue  .ac-stat-val { color: #0c5460; }

.ac-red   { background: #f8d7da; border-color: #f5c6cb; }
.ac-red   .ac-stat-lbl { color: #721c24; }
.ac-red   .ac-stat-val { color: #721c24; }

/* ── Tables ──────────────────────────────────────────────── */
.ac-table { font-size: 12px; margin: 0; }
.ac-table thead th {
  background: #f8f9fa; font-size: 10.5px;
  text-transform: uppercase; letter-spacing: .3px;
  padding: 6px 10px; border-color: #e9ecef; font-weight: 700; color: #666;
}
.ac-table tbody td { padding: 6px 10px; vertical-align: middle; border-color: #f0f0f0; }
.ac-table tbody tr:hover { background: #fafafa; }
.badge-paid   { background: #d4edda; color: #155724; font-size: 10px; padding: 2px 7px; border-radius: 10px; font-weight: 600; }
.badge-unpaid { background: #f8d7da; color: #721c24; font-size: 10px; padding: 2px 7px; border-radius: 10px; font-weight: 600; }
.badge-overdue{ background: #fff3cd; color: #856404; font-size: 10px; padding: 2px 7px; border-radius: 10px; font-weight: 600; }

/* ── Percentage bar ─────────────────────────────────────── */
.ac-pct-bar { height: 12px; border-radius: 6px; overflow: hidden; display: flex; margin: 6px 0 10px; }
.ac-pct-bar-seg { height: 100%; transition: width .4s; }
.ac-pct-legend { display: flex; flex-wrap: wrap; gap: 10px; }
.ac-pct-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 4px; flex-shrink: 0; }
.ac-pct-item { display: flex; align-items: center; font-size: 11px; color: #555; }
.ac-pct-num  { font-weight: 700; color: #333; margin-left: 3px; }

/* ── Ticket pills (v5) ───────────────────────────────────── */
.v5-tkt-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
.v5-tkt-pill { flex: 1; min-width: 70px; text-align: center; padding: 8px 6px; border-radius: 7px; }
.v5-tkt-pill .num { font-size: 1.2rem; font-weight: 800; line-height: 1; }
.v5-tkt-pill .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: .4px; margin-top: 3px; opacity: .85; }
</style>

<div class="v2-wrap">

  {{-- ── Greeting ──────────────────────────────────────────── --}}
  <div class="v2-greet">
    <div>
      <p class="v2-greet-name">
        <i class="fas fa-calculator mr-2"></i>Dashboard Accounting
      </p>
      <p class="v2-greet-sub">
        {{ Auth::user()->name }} &mdash; {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM YYYY') }}
      </p>
    </div>
    <div class="v2-greet-actions">
      <a href="{{ url('suminvoice') }}" class="v2-link-btn">
        <i class="fas fa-file-invoice-dollar mr-1"></i>Invoice
      </a>
      <a href="{{ url('suminvoice/transaction') }}" class="v2-link-btn">
        <i class="fas fa-exchange-alt mr-1"></i>Transaksi
      </a>
      <a href="{{ url('akun') }}" class="v2-link-btn">
        <i class="fas fa-book mr-1"></i>Buku Akun
      </a>
      <a href="{{ url('my-attendance') }}" class="v2-link-btn" style="background:rgba(255,255,255,.25);border-color:rgba(255,255,255,.5)">
        <i class="fas fa-fingerprint mr-1"></i>Absen
      </a>
    </div>
    <span class="v2-greet-wave"><i class="fas fa-calculator"></i></span>
  </div>

  {{-- ── Top: 4 Stat Cards + Chart ────────────────────────── --}}
  <div class="row mb-0">

    {{-- Stat cards (2x2) --}}
    <div class="col-lg-5 mb-3">
      <div class="row">

        <div class="col-6 mb-3">
          <div class="ac-stat-card ac-grey h-100">
            <div class="ac-stat-lbl">
              <i class="fas fa-wallet mr-1"></i>Total Receivable
            </div>
            <div class="ac-stat-val">
              Rp {{ number_format($acctTotalReceivable, 0, ',', '.') }}
            </div>
          </div>
        </div>

        <div class="col-6 mb-3">
          <div class="ac-stat-card ac-green h-100">
            <div class="ac-stat-lbl">
              <i class="fas fa-university mr-1"></i>This Month
            </div>
            <div class="ac-stat-val">
              Rp {{ number_format($acctPaymentThisMonth, 0, ',', '.') }}
            </div>
          </div>
        </div>

        <div class="col-6 mb-3">
          <div class="ac-stat-card ac-blue h-100">
            <div class="ac-stat-lbl">
              <i class="fas fa-chart-line mr-1"></i>This Week
            </div>
            <div class="ac-stat-val">
              Rp {{ number_format($acctPaymentThisWeek, 0, ',', '.') }}
            </div>
          </div>
        </div>

        <div class="col-6 mb-3">
          <div class="ac-stat-card ac-red h-100">
            <div class="ac-stat-lbl">
              <i class="fas fa-chart-bar mr-1"></i>Today
            </div>
            <div class="ac-stat-val">
              Rp {{ number_format($acctPaymentToday, 0, ',', '.') }}
            </div>
          </div>
        </div>

      </div>
    </div>

    {{-- Daily Transaction Chart --}}
    <div class="col-lg-7 mb-3">
      <div class="v2-card h-100">
        <div class="v2-card-hd">
          <span class="v2-card-hd-title"><i class="fas fa-chart-area mr-1" style="color:#2e7d32"></i>Transaksi Harian (Bulan Ini)</span>
        </div>
        <div class="v2-card-body" style="padding:10px 12px">
          <canvas id="acctDailyChart" style="width:100%;height:220px;max-height:220px"></canvas>
        </div>
      </div>
    </div>

  </div>{{-- /row top --}}

  {{-- ── Middle: Grouped by User + Recent Paid ─────────────── --}}
  <div class="row">

    {{-- By User this month --}}
    <div class="col-md-4 mb-3">
      <div class="v2-card h-100">
        <div class="v2-card-hd">
          <span class="v2-card-hd-title"><i class="fas fa-users mr-1" style="color:#1e88e5"></i>Per Kasir (Bulan Ini)</span>
        </div>
        <div class="v2-card-body p-0">
          <div class="table-responsive" style="max-height:280px;overflow-y:auto">
            <table class="table table-sm ac-table mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Kasir</th>
                  <th class="text-center">Trx</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                @forelse($acctGroupedByUser as $i => $row)
                <tr>
                  <td>{{ $i+1 }}</td>
                  <td>{{ $row->user_name ?? '-' }}</td>
                  <td class="text-center">{{ $row->trx_count }}</td>
                  <td class="text-right font-weight-bold">{{ number_format($row->total_payment,0,',','.') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data</td></tr>
                @endforelse
              </tbody>
              @if($acctGroupedByUser->count())
              <tfoot>
                <tr class="font-weight-bold" style="background:#f8f9fa">
                  <td colspan="3" class="text-right small">Total:</td>
                  <td class="text-right">{{ number_format($acctGroupedByUser->sum('total_payment'),0,',','.') }}</td>
                </tr>
              </tfoot>
              @endif
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Recent Paid Invoices --}}
    <div class="col-md-8 mb-3">
      <div class="v2-card">
        <div class="v2-card-hd">
          <span class="v2-card-hd-title"><i class="fas fa-check-circle mr-1" style="color:#43a047"></i>Pembayaran Terbaru</span>
          <a href="{{ url('suminvoice/transaction') }}" class="small text-muted" style="font-size:11px">Lihat Semua &rarr;</a>
        </div>
        <div class="v2-card-body p-0">
          <div class="table-responsive" style="max-height:280px;overflow-y:auto">
            <table class="table table-sm ac-table mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>No Invoice</th>
                  <th>Customer</th>
                  <th>Tgl Bayar</th>
                  <th class="text-right">Jumlah</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse($acctRecentPaid as $i => $inv)
                <tr>
                  <td>{{ $i+1 }}</td>
                  <td>
                    <a href="{{ url('suminvoice/'.$inv->id) }}" class="text-primary" style="font-size:11px">
                      {{ $inv->number ?? '-' }}
                    </a>
                  </td>
                  <td>{{ optional($inv->customer)->name ?? '-' }}</td>
                  <td>{{ $inv->payment_date ? \Carbon\Carbon::parse($inv->payment_date)->format('d/m/y') : '-' }}</td>
                  <td class="text-right font-weight-bold">{{ number_format($inv->recieve_payment,0,',','.') }}</td>
                  <td><span class="badge-paid">Lunas</span></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>{{-- /row middle --}}

  {{-- ── Perbandingan Invoice (Paid / Receivable / Cancel) ──── --}}
  @php
    $acctTotalSafe = $acctTotalCount > 0 ? $acctTotalCount : 1;
    $pctPaid    = round($acctPaidCount   / $acctTotalSafe * 100, 1);
    $pctUnpaid  = round($acctUnpaidCount / $acctTotalSafe * 100, 1);
    $pctCancel  = round($acctCancelCount / $acctTotalSafe * 100, 1);
  @endphp
  <div class="v2-card mb-3">
    <div class="v2-card-hd">
      <span class="v2-card-hd-title"><i class="fas fa-percentage mr-1" style="color:#2e7d32"></i>Perbandingan Invoice</span>
      <span class="small text-muted" style="font-size:11px">Total {{ number_format($acctTotalCount) }} invoice</span>
    </div>
    <div class="v2-card-body">
      <div class="row">

        {{-- Bar chart --}}
        <div class="col-md-8">
          <div class="ac-pct-bar">
            <div class="ac-pct-bar-seg" style="width:{{ $pctPaid }}%;background:#43a047" title="Paid {{ $pctPaid }}%"></div>
            <div class="ac-pct-bar-seg" style="width:{{ $pctUnpaid }}%;background:#e53935" title="Receivable {{ $pctUnpaid }}%"></div>
            <div class="ac-pct-bar-seg" style="width:{{ $pctCancel }}%;background:#bdbdbd" title="Cancel {{ $pctCancel }}%"></div>
          </div>
          <div class="ac-pct-legend">
            <div class="ac-pct-item">
              <span class="ac-pct-dot" style="background:#43a047"></span>
              Lunas / Paid
              <span class="ac-pct-num ml-1">{{ $acctPaidCount }}</span>
              <span class="text-muted ml-1">({{ $pctPaid }}%)</span>
            </div>
            <div class="ac-pct-item">
              <span class="ac-pct-dot" style="background:#e53935"></span>
              Piutang / Receivable
              <span class="ac-pct-num ml-1">{{ $acctUnpaidCount }}</span>
              <span class="text-muted ml-1">({{ $pctUnpaid }}%)</span>
            </div>
            <div class="ac-pct-item">
              <span class="ac-pct-dot" style="background:#bdbdbd"></span>
              Dibatalkan / Cancel
              <span class="ac-pct-num ml-1">{{ $acctCancelCount }}</span>
              <span class="text-muted ml-1">({{ $pctCancel }}%)</span>
            </div>
          </div>
        </div>

        {{-- Stat summary --}}
        <div class="col-md-4">
          <div class="d-flex justify-content-around text-center pt-1">
            <div>
              <div style="font-size:1.4rem;font-weight:800;color:#43a047">{{ $pctPaid }}%</div>
              <div style="font-size:10px;color:#888;text-transform:uppercase;letter-spacing:.4px">Lunas</div>
              <div style="font-size:11px;color:#155724">Rp {{ number_format($acctPaymentThisMonth,0,',','.') }}<br><span class="text-muted" style="font-size:10px">bulan ini</span></div>
            </div>
            <div style="border-left:1px solid #eee;padding-left:14px">
              <div style="font-size:1.4rem;font-weight:800;color:#e53935">{{ $pctUnpaid }}%</div>
              <div style="font-size:10px;color:#888;text-transform:uppercase;letter-spacing:.4px">Piutang</div>
              <div style="font-size:11px;color:#721c24">Rp {{ number_format($acctTotalReceivable,0,',','.') }}<br><span class="text-muted" style="font-size:10px">nilai total</span></div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- ── My Ticket + Group Ticket ────────────────────────────── --}}
  <div class="row">

    {{-- My Ticket --}}
    <div class="col-md-6 mb-3">
      <div class="v2-card h-100">
        <div class="v2-card-hd">
          <span class="v2-card-hd-title"><i class="fas fa-ticket-alt mr-1" style="color:#1e88e5"></i>Tiket Saya</span>
          <a href="{{ url('ticket') }}?assign={{ Auth::user()->id }}" class="small text-muted" style="font-size:11px">Semua &rarr;</a>
        </div>
        <div class="v2-card-body">
          {{-- Summary counts --}}
          <div class="v5-tkt-row mb-2">
            @php
              $tktColors = ['Open'=>['#fff3cd','#856404'],'Pending'=>['#fde6d8','#c0392b'],'Inprogress'=>['#d1ecf1','#0c5460'],'Solve'=>['#d4edda','#155724'],'Close'=>['#e9ecef','#495057']];
            @endphp
            @foreach($myTicketsByStatus as $st => $cnt)
            @php [$bg,$fg] = $tktColors[$st] ?? ['#e9ecef','#495057']; @endphp
            <div class="v5-tkt-pill" style="background:{{ $bg }};color:{{ $fg }}">
              <div class="num">{{ $cnt }}</div>
              <div class="lbl">{{ $st }}</div>
            </div>
            @endforeach
          </div>
          {{-- extra stats row --}}
          <div class="d-flex gap-3" style="gap:12px;font-size:11px;color:#555;flex-wrap:wrap">
            <span><i class="fas fa-calendar-day mr-1 text-primary"></i>Hari ini: <strong>{{ $myTicketsToday }}</strong></span>
            <span><i class="fas fa-calendar-week mr-1 text-success"></i>Minggu ini: <strong>{{ $myTicketsThisWeek }}</strong></span>
            <span><i class="fas fa-calendar-alt mr-1 text-warning"></i>Bulan ini: <strong>{{ $myTicketsThisMonth }}</strong></span>
          </div>
          {{-- Active ticket list --}}
          @if($myActiveTickets->count())
          <div class="mt-2" style="max-height:200px;overflow-y:auto">
            @foreach($myActiveTickets->take(5) as $tkt)
            <div class="d-flex align-items-center py-1" style="border-bottom:1px solid #f5f5f5">
              <span class="badge badge-sm mr-2" style="font-size:9px;padding:2px 6px;background:{{ ($tktColors[$tkt->status] ?? ['#e9ecef','#495057'])[0] }};color:{{ ($tktColors[$tkt->status] ?? ['#e9ecef','#495057'])[1] }};border-radius:10px">{{ $tkt->status }}</span>
              <a href="{{ url('ticket/'.$tkt->id) }}" class="text-dark" style="font-size:11px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:200px" title="{{ $tkt->complaint }}">{{ Str::limit($tkt->complaint,45) }}</a>
              <span class="ml-auto text-muted" style="font-size:10px;white-space:nowrap">{{ optional($tkt->customer)->name ?? '-' }}</span>
            </div>
            @endforeach
          </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Group Ticket --}}
    <div class="col-md-6 mb-3">
      <div class="v2-card h-100">
        <div class="v2-card-hd">
          <span class="v2-card-hd-title"><i class="fas fa-users mr-1" style="color:#8e24aa"></i>Tiket Tim ({{ $myJobTitle ?? 'Semua' }})</span>
          <a href="{{ url('ticket') }}" class="small text-muted" style="font-size:11px">Semua &rarr;</a>
        </div>
        <div class="v2-card-body">
          <div class="v5-tkt-row mb-2">
            @foreach($groupTicketsByStatus as $st => $cnt)
            @php [$bg,$fg] = $tktColors[$st] ?? ['#e9ecef','#495057']; @endphp
            <div class="v5-tkt-pill" style="background:{{ $bg }};color:{{ $fg }}">
              <div class="num">{{ $cnt }}</div>
              <div class="lbl">{{ $st }}</div>
            </div>
            @endforeach
          </div>
          {{-- Active group tickets --}}
          @if($groupActiveTickets->count())
          <div class="mt-2" style="max-height:200px;overflow-y:auto">
            @foreach($groupActiveTickets->take(6) as $tkt)
            <div class="d-flex align-items-center py-1" style="border-bottom:1px solid #f5f5f5">
              <span class="badge badge-sm mr-2" style="font-size:9px;padding:2px 6px;background:{{ ($tktColors[$tkt->status] ?? ['#e9ecef','#495057'])[0] }};color:{{ ($tktColors[$tkt->status] ?? ['#e9ecef','#495057'])[1] }};border-radius:10px">{{ $tkt->status }}</span>
              <a href="{{ url('ticket/'.$tkt->id) }}" class="text-dark" style="font-size:11px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:180px" title="{{ $tkt->complaint }}">{{ Str::limit($tkt->complaint,40) }}</a>
              <span class="ml-auto text-muted" style="font-size:10px;white-space:nowrap">{{ optional($tkt->customer)->name ?? '-' }}</span>
            </div>
            @endforeach
          </div>
          @endif
        </div>
      </div>
    </div>

  </div>{{-- /row tickets --}}

</div>{{-- /v2-wrap --}}

@endsection

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
(function () {
  var labels = {!! json_encode($acctDailyTransactions->pluck('date')->map(function($d){ return \Carbon\Carbon::parse($d)->format('d M'); })) !!};
  var volumes = {!! json_encode($acctDailyTransactions->pluck('volume')) !!};
  var totals  = {!! json_encode($acctDailyTransactions->pluck('total_paid')->map(function($v){ return (float)$v; })) !!};

  var ctx = document.getElementById('acctDailyChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Jumlah Transaksi',
          data: volumes,
          backgroundColor: 'rgba(30,136,229,.35)',
          borderColor: 'rgba(30,136,229,.8)',
          borderWidth: 1,
          yAxisID: 'yLeft',
          type: 'bar',
        },
        {
          label: 'Total Pembayaran',
          data: totals,
          borderColor: 'rgba(229,57,53,.8)',
          backgroundColor: 'rgba(229,57,53,.08)',
          borderWidth: 2,
          pointRadius: 3,
          fill: true,
          yAxisID: 'yRight',
          type: 'line',
          tension: 0.3,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 16 } },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              if (ctx.datasetIndex === 1) {
                return ' Rp ' + new Intl.NumberFormat('id-ID').format(ctx.raw);
              }
              return ' ' + ctx.raw + ' transaksi';
            }
          }
        }
      },
      scales: {
        x: { ticks: { font: { size: 10 } }, grid: { display: false } },
        yLeft: {
          position: 'left',
          beginAtZero: true,
          title: { display: true, text: 'Jumlah', font: { size: 10 } },
          ticks: { font: { size: 10 }, stepSize: 1 },
          grid: { color: 'rgba(0,0,0,.05)' }
        },
        yRight: {
          position: 'right',
          beginAtZero: true,
          title: { display: true, text: 'Total Pembayaran', font: { size: 10 } },
          ticks: {
            font: { size: 10 },
            callback: function(v) {
              if (v >= 1000000) return 'Rp ' + (v/1000000).toFixed(1) + 'jt';
              if (v >= 1000) return 'Rp ' + (v/1000).toFixed(0) + 'rb';
              return 'Rp ' + v;
            }
          },
          grid: { drawOnChartArea: false }
        }
      }
    }
  });
})();
</script>
@endsection
