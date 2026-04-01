@extends('layout.main')
@section('title', 'Ticket Analytics Report')

@section('content')
<style>
  /* ============================================================
     TICKET REPORT — Dark/Light Mode, senada main layout
     ============================================================ */

  .report-page-header {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-left: 4px solid var(--brand);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    box-shadow: var(--shadow-sm);
  }
  .report-page-header h4 {
    color: var(--text-primary); font-weight: 700; margin: 0; font-size: 18px;
  }
  .report-page-header p { color: var(--text-muted); margin: 0; font-size: 13px; }


  /* Summary stat cards */
  .stat-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 18px;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.2s;
    box-shadow: var(--shadow-sm);
  }
  .stat-card:hover { box-shadow: var(--shadow-md); }
  .stat-card .stat-icon {
    position: absolute; right: 14px; top: 14px;
    font-size: 28px; opacity: 0.12;
    color: var(--brand);
  }
  .stat-card .stat-number {
    font-size: 30px; font-weight: 700;
    color: var(--text-primary); line-height: 1;
    margin-bottom: 4px;
  }
  .stat-card .stat-label {
    font-size: 11px; font-weight: 700; letter-spacing: 0.6px;
    text-transform: uppercase; color: var(--text-muted);
  }
  .stat-card .stat-sub {
    font-size: 12px; color: var(--text-secondary); margin-top: 4px;
  }
  .stat-card-accent-red    { border-left: 4px solid #ef4444; }
  .stat-card-accent-green  { border-left: 4px solid #10b981; }
  .stat-card-accent-blue   { border-left: 4px solid #4a76bd; }
  .stat-card-accent-yellow { border-left: 4px solid #f59e0b; }
  .stat-card-accent-purple { border-left: 4px solid #8b5cf6; }
  .stat-card-accent-cyan   { border-left: 4px solid #06b6d4; }

  /* Section heading */
  .section-title {
    font-size: 15px; font-weight: 700;
    color: var(--text-primary);
    padding-bottom: 8px;
    border-bottom: 2px solid var(--border);
    margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
  }
  .section-title i { color: var(--brand); }

  /* Chart wrapper */
  .chart-box {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 18px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;
  }
  .chart-box canvas { max-height: 220px; }

  /* Tables */
  .report-table th {
    font-size: 11px !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
    color: var(--text-secondary) !important;
    background: var(--bg-surface-2) !important;
    border-color: var(--border) !important;
  }
  .report-table td {
    vertical-align: middle;
    font-size: 13px;
    border-color: var(--border) !important;
    color: var(--text-primary) !important;
    background: var(--bg-surface) !important;
  }
  .report-table tbody tr:hover td { background: var(--brand-light) !important; }

  /* Rank badge */
  .rank-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 50%;
    font-weight: 700; font-size: 12px; color: #fff;
  }
  .rank-1 { background: #f59e0b; }
  .rank-2 { background: #9ca3af; }
  .rank-3 { background: #92400e; }
  .rank-other { background: var(--bg-surface-2); color: var(--text-secondary); border: 1px solid var(--border); }

  /* MTTR badges */
  .mttr-good    { background: rgba(16,185,129,.12);  color: #10b981; border: 1px solid rgba(16,185,129,.25); }
  .mttr-medium  { background: rgba(245,158,11,.12);  color: #f59e0b; border: 1px solid rgba(245,158,11,.25); }
  .mttr-bad     { background: rgba(239, 68, 68,.12); color: #ef4444; border: 1px solid rgba(239,68,68,.25); }
  .mttr-badge {
    display: inline-block; padding: 3px 9px;
    border-radius: 20px; font-size: 12px; font-weight: 600;
  }

  /* Status badge pills */
  .pill-open       { background: rgba(239,68,68,.12);  color: #ef4444; }
  .pill-pending    { background: rgba(245,158,11,.12); color: #f59e0b; }
  .pill-inprogress { background: rgba(59,130,246,.12); color: #3b82f6; }
  .pill-solve      { background: rgba(16,185,129,.12); color: #10b981; }
  .pill-close      { background: rgba(107,114,128,.12);color: #6b7280; }
  .status-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
  }

  /* Progress bar for agent performance */
  .agent-bar-track {
    background: var(--border); border-radius: 4px; height: 6px; flex: 1;
  }
  .agent-bar-fill {
    height: 6px; border-radius: 4px;
    background: var(--brand);
    transition: width 0.6s ease;
  }

  /* MTTR gauge display */
  .mttr-hero {
    text-align: center; padding: 10px 0;
  }
  .mttr-hero .big-value {
    font-size: 42px; font-weight: 800;
    color: var(--brand); line-height: 1;
  }
  .mttr-hero .big-unit { font-size: 16px; font-weight: 400; color: var(--text-muted); }
</style>

<div class="container-fluid">

  {{-- ===== PAGE HEADER + FILTER ===== --}}
  <div class="report-page-header">
    <div>
      <h4><i class="fas fa-chart-line mr-2"></i>Ticket Analytics Report</h4>
      <p>{{ \Carbon\Carbon::parse($date_from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($date_end)->format('d M Y') }}</p>
    </div>
    <form method="post" action="/ticket/reportsrc" class="d-flex flex-wrap align-items-center" style="gap:8px">
      @csrf
      <div class="d-flex align-items-center" style="gap:6px">
        <span style="color:var(--text-secondary);font-size:12px;white-space:nowrap">Dari</span>
        <input type="date" name="date_from" value="{{ $date_from }}" required
          style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);border-radius:8px;padding:5px 10px;font-size:12px;outline:none;">
      </div>
      <div class="d-flex align-items-center" style="gap:6px">
        <span style="color:var(--text-secondary);font-size:12px;white-space:nowrap">s/d</span>
        <input type="date" name="date_end" value="{{ $date_end }}" required
          style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);border-radius:8px;padding:5px 10px;font-size:12px;outline:none;">
      </div>
      <button type="submit"
        style="background:var(--brand);color:#fff;border:1px solid var(--brand);border-radius:8px;padding:5px 14px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;">
        <i class="fas fa-filter mr-1"></i>Filter
      </button>
    </form>
  </div>

  {{-- ===== SUMMARY STATS ===== --}}
  <div class="row mb-3">
    <div class="col-6 col-md-2 mb-2">
      <div class="stat-card stat-card-accent-blue">
        <i class="fas fa-ticket-alt stat-icon"></i>
        <div class="stat-number">{{ $ticket_report->sum('count') }}</div>
        <div class="stat-label">Total Tickets</div>
        <div class="stat-sub">Periode ini</div>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="stat-card stat-card-accent-green">
        <i class="fas fa-check-circle stat-icon"></i>
        <div class="stat-number">{{ ($ticket_status['Close'] ?? 0) + ($ticket_status['Solve'] ?? 0) }}</div>
        <div class="stat-label">Resolved</div>
        <div class="stat-sub">Close + Solve</div>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="stat-card stat-card-accent-red">
        <i class="fas fa-exclamation-circle stat-icon"></i>
        <div class="stat-number">{{ ($ticket_status['Open'] ?? 0) + ($ticket_status['Pending'] ?? 0) }}</div>
        <div class="stat-label">Unresolved</div>
        <div class="stat-sub">Open + Pending</div>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="stat-card stat-card-accent-purple">
        <i class="fas fa-clock stat-icon"></i>
        <div class="stat-number">{{ $mttr_avg }}<span style="font-size:14px;font-weight:400"> jam</span></div>
        <div class="stat-label">MTTR Avg</div>
        <div class="stat-sub">{{ $mttr_count }} tiket resolved</div>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="stat-card stat-card-accent-yellow">
        <i class="fas fa-tags stat-icon"></i>
        <div class="stat-number">{{ $ticket_report->count() }}</div>
        <div class="stat-label">Kategori</div>
        <div class="stat-sub">Ticket</div>
      </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
      <div class="stat-card stat-card-accent-cyan">
        <i class="fas fa-calendar-day stat-icon"></i>
        <div class="stat-number">{{ $ticket_date->count() }}</div>
        <div class="stat-label">Hari Aktif</div>
        <div class="stat-sub">Ticket</div>
      </div>
    </div>
  </div>

  {{-- ===== STATUS BREAKDOWN ===== --}}
  <div class="row mb-3">
    <div class="col-12">
      <div class="chart-box" style="padding: 12px 18px;">
        <div class="d-flex flex-wrap" style="gap: 8px; align-items: center;">
          <span class="section-title mb-0 mr-3"><i class="fas fa-circle-notch"></i>Status</span>
          @foreach(['Open'=>'pill-open','Pending'=>'pill-pending','Inprogress'=>'pill-inprogress','Solve'=>'pill-solve','Close'=>'pill-close'] as $s => $cls)
          <span class="status-pill {{ $cls }}">
            <i class="fas fa-circle" style="font-size:7px"></i>
            {{ $s }}: <strong>{{ $ticket_status[$s] ?? 0 }}</strong>
          </span>
          @endforeach
          @php
            $total = $ticket_report->sum('count');
            $resolved = ($ticket_status['Close'] ?? 0) + ($ticket_status['Solve'] ?? 0);
            $resolveRate = $total > 0 ? round($resolved / $total * 100, 1) : 0;
          @endphp
          <span class="ml-auto" style="font-size:13px; color:var(--text-secondary)">
            Resolution Rate: <strong style="color:var(--brand)">{{ $resolveRate }}%</strong>
          </span>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== CHARTS ROW ===== --}}
  <div class="row mb-3">
    <div class="col-md-5">
      <div class="chart-box">
        <div class="section-title"><i class="fas fa-chart-pie"></i>Tiket per Kategori</div>
        <canvas id="chartCategory"></canvas>
      </div>
    </div>
    <div class="col-md-7">
      <div class="chart-box">
        <div class="section-title"><i class="fas fa-chart-line"></i>Tiket per Hari</div>
        <canvas id="chartDate"></canvas>
      </div>
    </div>
  </div>

  {{-- ===== MTTR SECTION ===== --}}
  <div class="section-title mt-2"><i class="fas fa-stopwatch"></i>MTTR — Mean Time To Resolve</div>
  <div class="row mb-3">
    {{-- MTTR summary cards --}}
    <div class="col-md-4">
      <div class="chart-box" style="display:flex; flex-direction:column; gap:12px;">
        <div class="mttr-hero">
          <div class="small text-muted mb-1">Rata-rata MTTR</div>
          <div class="big-value">{{ $mttr_avg }}<span class="big-unit"> jam</span></div>
          <div class="small text-muted mt-1">dari {{ $mttr_count }} tiket terselesaikan</div>
        </div>
        <hr style="border-color:var(--border); margin: 4px 0;">
        <div class="d-flex justify-content-around text-center">
          <div>
            <div style="font-size:20px;font-weight:700;color:var(--text-primary)">{{ $mttr_min }}</div>
            <div style="font-size:11px;color:var(--text-muted)">Min (jam)</div>
          </div>
          <div>
            <div style="font-size:20px;font-weight:700;color:var(--text-primary)">{{ $mttr_max }}</div>
            <div style="font-size:11px;color:var(--text-muted)">Max (jam)</div>
          </div>
          <div>
            @php
              $sla = $mttr_avg <= 4 ? 'good' : ($mttr_avg <= 24 ? 'medium' : 'bad');
              $slaLabel = $sla === 'good' ? 'SLA OK' : ($sla === 'medium' ? 'Perlu Perhatian' : 'SLA Breach');
            @endphp
            <div>
              <span class="mttr-badge mttr-{{ $sla }}">{{ $slaLabel }}</span>
            </div>
            <div style="font-size:11px;color:var(--text-muted)">Status SLA</div>
          </div>
        </div>
      </div>
    </div>

    {{-- MTTR trend chart --}}
    <div class="col-md-8">
      <div class="chart-box">
        <div class="section-title"><i class="fas fa-chart-area"></i>Tren MTTR per Hari (jam)</div>
        <canvas id="chartMttrTrend"></canvas>
      </div>
    </div>
  </div>

  {{-- MTTR by category --}}
  <div class="chart-box mb-4">
    <div class="section-title"><i class="fas fa-layer-group"></i>MTTR per Kategori</div>
    <div class="table-responsive">
      <table class="table report-table table-hover mb-0">
        <thead>
          <tr>
            <th>Kategori</th>
            <th class="text-center">Jumlah Resolved</th>
            <th class="text-center">Avg MTTR (jam)</th>
            <th class="text-center">Min (jam)</th>
            <th class="text-center">Max (jam)</th>
            <th class="text-center">SLA</th>
          </tr>
        </thead>
        <tbody>
          @forelse($mttr_by_category as $row)
          @php
            $slaClass = $row->avg_hours <= 4 ? 'good' : ($row->avg_hours <= 24 ? 'medium' : 'bad');
            $slaText  = $slaClass === 'good' ? '✓ OK' : ($slaClass === 'medium' ? '⚠ Perhatian' : '✗ Breach');
          @endphp
          <tr>
            <td><i class="fas fa-tag mr-1" style="color:var(--brand);font-size:11px"></i>{{ $row->category }}</td>
            <td class="text-center"><strong>{{ $row->count }}</strong></td>
            <td class="text-center"><strong>{{ $row->avg_hours }}</strong></td>
            <td class="text-center text-success">{{ $row->min_hours }}</td>
            <td class="text-center text-danger">{{ $row->max_hours }}</td>
            <td class="text-center">
              <span class="mttr-badge mttr-{{ $slaClass }}">{{ $slaText }}</span>
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-center text-muted">Tidak ada data MTTR</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ===== AGENT PERFORMANCE ===== --}}
  <div class="chart-box mb-4">
    <div class="section-title"><i class="fas fa-user-shield"></i>Performa Agent</div>
    <div class="table-responsive">
      <table class="table report-table table-hover mb-0">
        <thead>
          <tr>
            <th>Agent</th>
            <th class="text-center">Total Assigned</th>
            <th class="text-center">Resolved</th>
            <th style="min-width:160px">Resolution Rate</th>
            <th class="text-center">Avg MTTR (jam)</th>
          </tr>
        </thead>
        <tbody>
          @forelse($user_performance as $agent)
          @php
            $rate = $agent->total > 0 ? round($agent->resolved / $agent->total * 100, 1) : 0;
            $agentSla = !$agent->mttr ? 'secondary' : ($agent->mttr <= 4 ? 'good' : ($agent->mttr <= 24 ? 'medium' : 'bad'));
          @endphp
          <tr>
            <td><i class="fas fa-user-circle mr-1" style="color:var(--brand)"></i>{{ $agent->agent }}</td>
            <td class="text-center">{{ $agent->total }}</td>
            <td class="text-center text-success"><strong>{{ $agent->resolved }}</strong></td>
            <td>
              <div class="d-flex align-items-center gap-2" style="gap:8px">
                <div class="agent-bar-track">
                  <div class="agent-bar-fill" style="width:{{ $rate }}%"></div>
                </div>
                <span style="font-size:12px;font-weight:700;color:var(--text-primary);min-width:38px">{{ $rate }}%</span>
              </div>
            </td>
            <td class="text-center">
              @if($agent->mttr)
                <span class="mttr-badge mttr-{{ $agentSla }}">{{ $agent->mttr }} jam</span>
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ===== TOP CUSTOMER TABLE ===== --}}
  <div class="chart-box mb-4">
    <div class="section-title"><i class="fas fa-trophy"></i>Top 10 Customer — Tiket Terbanyak</div>
    <div class="table-responsive">
      <table class="table report-table table-hover mb-0">
        <thead>
          <tr>
            <th style="width:60px">Rank</th>
            <th>Nama Customer</th>
            <th class="text-center" style="width:140px">Jumlah Tiket</th>
            <th class="text-center" style="width:130px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($ticket_customer as $customer)
          @php $iter = $loop->iteration; @endphp
          <tr>
            <td>
              <span class="rank-badge rank-{{ $iter <= 3 ? $iter : 'other' }}">{{ $iter }}</span>
            </td>
            <td style="font-weight:600"><i class="fas fa-user mr-1" style="color:var(--brand);font-size:11px"></i>{{ $customer->name }}</td>
            <td class="text-center">
              <span class="badge" style="background:var(--brand);color:#fff;font-size:13px;padding:4px 12px;border-radius:20px;">{{ $customer->count }}</span>
            </td>
            <td class="text-center">
              <a href="/ticket/view/{{ $customer->cust_id }}" class="btn btn-sm btn-primary" style="border-radius:20px;font-size:12px;padding:4px 14px;">
                <i class="fas fa-eye mr-1"></i>Lihat
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const isDark         = () => document.body.classList.contains('dark-mode');
  const chartText      = () => isDark() ? '#9ba3b2' : '#6b7280';
  const chartGrid      = () => isDark() ? '#333845' : '#e5e7eb';
  const brand          = '#a3301c';

  // === Category Doughnut ===
  const categoryLabels = {!! json_encode($ticket_report->pluck('name')) !!};
  const categoryData   = {!! json_encode($ticket_report->pluck('count')) !!};
  const catColors = ['#4a76bd','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#e83e8c','#f97316','#a3301c','#14b8a6'];

  const chartCat = new Chart(document.getElementById('chartCategory'), {
    type: 'doughnut',
    data: {
      labels: categoryLabels,
      datasets: [{ data: categoryData, backgroundColor: catColors, borderWidth: 0 }]
    },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: {
        legend: { position: 'bottom', labels: { color: chartText(), font: { size: 11 }, padding: 12 } },
        tooltip: { callbacks: { label: function(t) {
          const total = t.dataset.data.reduce((a,b)=>a+b,0);
          return ` ${t.label}: ${t.raw} (${((t.raw/total)*100).toFixed(1)}%)`;
        }}}
      }
    }
  });

  // === Date line chart ===
  const dateLabels = {!! json_encode($ticket_date->pluck('date')) !!};
  const dateData   = {!! json_encode($ticket_date->pluck('countdate')) !!};

  const chartD = new Chart(document.getElementById('chartDate'), {
    type: 'line',
    data: {
      labels: dateLabels,
      datasets: [{
        label: 'Tiket/Hari',
        data: dateData,
        borderColor: brand,
        backgroundColor: 'rgba(163,48,28,0.1)',
        fill: true, tension: 0.3, borderWidth: 2,
        pointBackgroundColor: brand, pointRadius: 4,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: { legend: { labels: { color: chartText() } } },
      scales: {
        x: { ticks: { color: chartText(), maxRotation: 45 }, grid: { color: chartGrid() } },
        y: { beginAtZero: true, ticks: { color: chartText() }, grid: { color: chartGrid() } }
      }
    }
  });

  // === MTTR trend ===
  const mttrTrendLabels = {!! json_encode($mttr_trend->pluck('date')) !!};
  const mttrTrendData   = {!! json_encode($mttr_trend->pluck('avg_hours')) !!};

  const chartMttr = new Chart(document.getElementById('chartMttrTrend'), {
    type: 'bar',
    data: {
      labels: mttrTrendLabels,
      datasets: [
        {
          type: 'bar',
          label: 'MTTR (jam)',
          data: mttrTrendData,
          backgroundColor: mttrTrendData.map(v => v <= 4 ? 'rgba(16,185,129,0.65)' : (v <= 24 ? 'rgba(245,158,11,0.65)' : 'rgba(239,68,68,0.65)')),
          borderRadius: 4,
        },
        {
          type: 'line',
          label: 'Trend',
          data: mttrTrendData,
          borderColor: brand,
          backgroundColor: 'transparent',
          borderDash: [4,3],
          tension: 0.3,
          pointRadius: 3,
          borderWidth: 1.5,
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: { legend: { labels: { color: chartText() } } },
      scales: {
        x: { ticks: { color: chartText(), maxRotation: 45 }, grid: { color: chartGrid() } },
        y: { beginAtZero: true, ticks: { color: chartText(), callback: v => v + 'j' }, grid: { color: chartGrid() } }
      }
    }
  });

  // === Update charts on dark mode toggle ===
  document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('toggleDarkMode');
    if (!toggle) return;
    toggle.addEventListener('click', function() {
      setTimeout(function() {
        [chartCat, chartD, chartMttr].forEach(function(c) {
          if (!c) return;
          if (c.options.plugins?.legend?.labels) c.options.plugins.legend.labels.color = chartText();
          if (c.options.scales?.x) {
            c.options.scales.x.ticks.color = chartText();
            c.options.scales.x.grid.color  = chartGrid();
          }
          if (c.options.scales?.y) {
            c.options.scales.y.ticks.color = chartText();
            c.options.scales.y.grid.color  = chartGrid();
          }
          c.update();
        });
      }, 50);
    });
  });
</script>
@endsection
