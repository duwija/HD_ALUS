@extends('layout.main')
@section('title', 'Network Dashboard')

@section('content')
<style>
  /* === Compact Dashboard Layout === */
  .card {
    min-height: auto !important;
    height: auto !important;
    margin-bottom: 0.6rem !important;
  }
  .card-body { padding: 6px 8px !important; }
  .card-header { padding: 6px 10px !important; min-height: 30px !important; position: relative; }
  .card-title { font-size: 14px; margin: 0; line-height: 1.2; }
  .card-body canvas { max-height: 180px !important; }
  .info-box .info-box-content { line-height: 1.3; }
  .info-box { margin-bottom: 0.6rem !important; }

  @media (max-width: 768px) {
    .card-body canvas { max-height: 130px !important; }
  }

  /* ============================================================
     INFO BOX
     ============================================================ */
  .info-box {
    background: var(--bg-surface) !important;
    border: 1px solid var(--border) !important;
    border-radius: 12px !important;
    box-shadow: var(--shadow-sm) !important;
    color: var(--text-primary) !important;
    transition: background 0.25s, box-shadow 0.2s;
  }
  .info-box:hover { box-shadow: var(--shadow-md) !important; }
  .info-box-content { color: var(--text-primary) !important; }
  .info-box-text    { color: var(--text-secondary) !important; }
  .info-box-number  { color: var(--text-primary) !important; font-weight: 700; }

  /* ============================================================
     REFRESH BUTTONS
     ============================================================ */
  .card-header .refresh-router,
  .card-header .refresh-olt {
    position: absolute;
    right: 8px; top: 6px;
    background: var(--bg-surface) !important;
    border: 1px solid var(--border);
    color: #3b82f6;
    padding: 2px 7px;
    font-size: 11px;
    border-radius: 6px;
    transition: background 0.2s, color 0.2s;
  }
  .card-header .refresh-router:hover,
  .card-header .refresh-olt:hover {
    background: var(--brand-light) !important;
    color: var(--brand);
  }

  /* ============================================================
     ROUTER & OLT CARDS
     ============================================================ */
  .router-card, .olt-card {
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    box-shadow: var(--shadow-sm);
    background: var(--bg-surface) !important;
    margin-bottom: 12px;
    position: relative;
  }
  .router-card:hover, .olt-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
  }
  .router-card  { border-color: #60a5fa; }
  .router-card:hover { border-color: #3b82f6; }
  .olt-card     { border-color: #22d3ee; }
  .olt-card:hover { border-color: #06b6d4; }

  /* Card headers — gradient stays, works in both modes */
  .router-card .card-header {
    background: linear-gradient(135deg, #93c5fd 0%, #3b82f6 100%) !important;
    padding: 12px 14px !important;
    border: none; border-bottom: none !important;
  }
  .olt-card .card-header {
    background: linear-gradient(135deg, #67e8f9 0%, #06b6d4 100%) !important;
    padding: 12px 14px !important;
    border: none; border-bottom: none !important;
  }
  .router-card .card-title,
  .olt-card .card-title {
    color: #fff !important;
    font-weight: 700; font-size: 15px; margin: 0;
    display: flex; align-items: center; gap: 8px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }
  .router-card .card-title i,
  .olt-card .card-title i { color: #fff !important; font-size: 18px; }

  .router-card .card-body,
  .olt-card .card-body {
    padding: 12px 14px !important;
    background: var(--bg-surface) !important;
  }

  /* Badges inside router/olt cards */
  .router-card .badge, .olt-card .badge {
    padding: 5px 10px; border-radius: 6px; font-weight: 600;
    font-size: 11px; margin: 2px; box-shadow: 0 2px 4px rgba(0,0,0,0.12);
  }
  .router-card .badge-info,  .olt-card .badge-info  { background:#3b9ec9; color:#fff; }
  .router-card .badge-success,.olt-card .badge-success{ background:#10b981; color:#fff; }
  .router-card .badge-danger, .olt-card .badge-danger { background:#ef4444; color:#fff; }
  .router-card .badge-warning,.olt-card .badge-warning{ background:#f59e0b; color:#fff; }
  .router-card .badge-primary,.olt-card .badge-primary{ background:#4a76bd; color:#fff; }
  .router-card .badge-secondary,.olt-card .badge-secondary{ background:#6b7280; color:#fff; }

  .router-card a, .olt-card a {
    color: #3b82f6; text-decoration: none;
    font-weight: 600; font-size: 13px;
    transition: color 0.2s;
  }
  .router-card a:hover, .olt-card a:hover { color: #2563eb; }
  .router-card .small, .olt-card .small { font-size: 12px; color: var(--text-muted); }

  /* ============================================================
     SECTION HEADERS
     ============================================================ */
  h5.mt-1 {
    font-size: 16px; font-weight: 600;
    color: var(--text-primary);
    padding: 10px 0;
    border-bottom: 2px solid var(--border);
    margin-bottom: 15px !important;
    transition: color 0.25s, border-color 0.25s;
  }
  h5.mt-1 i { margin-right: 8px; color: var(--brand); }

  /* ============================================================
     TIMELINE
     ============================================================ */
  .timeline-item {
    background: var(--bg-surface) !important;
    border-radius: 12px;
    border: 1px solid var(--border) !important;
    box-shadow: var(--shadow-sm);
    padding: 12px 15px !important;
    margin-bottom: 15px !important;
    transition: box-shadow 0.2s, transform 0.2s, background 0.25s;
    position: relative;
  }
  .timeline-item:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
  }
  .timeline-item .status-badge { position: absolute; top: 12px; right: 12px; z-index: 10; }
  .timeline-item .time {
    background: var(--bg-surface-2) !important;
    padding: 4px 10px !important;
    border-radius: 6px;
    font-size: 13px;
    color: var(--text-secondary);
    font-weight: 500;
  }
  .timeline-item strong { color: var(--text-primary); font-size: 14px; }
  .timeline-item .small { color: var(--text-muted) !important; font-size: 12px; }
  .timeline-header { margin: 8px 0; }
  .timeline-header a { color: var(--text-primary); text-decoration: none; font-weight: 500; }
  .timeline-header a:hover { color: var(--brand); }
  .timeline-body { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
  .timeline-body strong { color: var(--text-primary); font-size: 15px; line-height: 1.5; }
  .timeline-item .col-md-5 { font-size: 13px; color: var(--text-secondary); }

  /* ============================================================
     TICKET ID BADGE
     ============================================================ */
  .badge-modern {
    padding: 8px 16px; border-radius: 6px; font-weight: 700;
    font-size: 14px; display: inline-block;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    transition: all 0.3s ease; letter-spacing: 0.3px;
  }
  .badge-modern:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.18); }

  .badge-Open       { background: #dc3545; color: #fff; }
  .badge-Pending    { background: #ffc107; color: #212529; }
  .badge-Inprogress { background: #17a2b8; color: #fff; }
  .badge-Solve      { background: #28a745; color: #fff; }
  .badge-Close      { background: #6c757d; color: #fff; }


  /* ============================================================
     STATUS BADGE (light)
     ============================================================ */
  .status-badge {
    display: inline-flex; align-items: center;
    padding: 5px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; letter-spacing: 0.3px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    transition: all 0.2s ease;
  }
  .status-badge:before {
    content: ''; width: 6px; height: 6px;
    border-radius: 50%; margin-right: 6px;
    background: currentColor; opacity: 0.8;
  }
  .status-badge:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }

  .status-badge.badge-danger    { background: #fee2e2;  color: #dc3545; border: 1px solid rgba(220,53,69,.25); }
  .status-badge.badge-warning   { background: #fff8e1;  color: #d97706; border: 1px solid rgba(217,119,6,.25); }
  .status-badge.badge-info      { background: #e0f7fa;  color: #17a2b8; border: 1px solid rgba(23,162,184,.25); }
  .status-badge.badge-success   { background: #e8f5e9;  color: #28a745; border: 1px solid rgba(40,167,69,.25); }
  .status-badge.badge-secondary { background: #f3f4f6;  color: #6c757d; border: 1px solid rgba(108,117,125,.25); }
  .status-badge.badge-primary   { background: #e3f2fd;  color: #4a76bd; border: 1px solid rgba(74,118,189,.25); }

  /* Dark mode status badges */
  body.dark-mode .status-badge.badge-danger    { background: rgba(239,68,68,.15);   color: #fca5a5; border-color: rgba(239,68,68,.3); }
  body.dark-mode .status-badge.badge-warning   { background: rgba(245,158,11,.15);  color: #fcd34d; border-color: rgba(245,158,11,.3); }
  body.dark-mode .status-badge.badge-info      { background: rgba(23,162,184,.15);  color: #67e8f9; border-color: rgba(23,162,184,.3); }
  body.dark-mode .status-badge.badge-success   { background: rgba(40,167,69,.15);   color: #6ee7b7; border-color: rgba(40,167,69,.3); }
  body.dark-mode .status-badge.badge-secondary { background: rgba(107,114,128,.15); color: #d1d5db; border-color: rgba(107,114,128,.3); }
  body.dark-mode .status-badge.badge-primary   { background: rgba(74,118,189,.15);  color: #93c5fd; border-color: rgba(74,118,189,.3); }

  /* ============================================================
     WORKFLOW PROGRESS
     ============================================================ */
  .workflow-wrapper { padding: 15px 0; margin: 10px 0; }
  .base-line    { height: 2px; background: var(--border) !important; top: 15px; left: 0; }
  .progress-line{ height: 2px; background: var(--brand) !important; top: 15px; left: 0; transition: width 0.3s ease; }

  .step-dot {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 8px; position: relative; z-index: 2;
    transition: all 0.3s ease;
  }
  .step-dot.pending  { background: var(--bg-surface-2); border: 2px solid var(--border); color: var(--text-muted); }
  .step-dot.active   { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: 2px solid #667eea; color: #fff; box-shadow: 0 0 0 4px rgba(102,126,234,0.2); animation: pulse 2s infinite; }
  .step-dot.done     { background: var(--brand); border: 2px solid var(--brand-dark); color: #fff; }

  @keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 4px rgba(102,126,234,0.2); }
    50%       { box-shadow: 0 0 0 8px rgba(102,126,234,0.1); }
  }
  .step-label { font-size: 11px; color: var(--text-muted); font-weight: 500; padding: 0 4px; }

  /* ============================================================
     MISC
     ============================================================ */
  .ribbon-wrapper .ribbon { display: none; }

  .timeline-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
  }
  @media (max-width: 992px) { .timeline-grid { grid-template-columns: 1fr; } }
  .timeline-grid .timeline-item { margin-bottom: 0 !important; }

  #load-more-timeline {
    padding: 8px 24px; border-radius: 8px;
    font-weight: 500; transition: all 0.2s ease;
  }
  #load-more-timeline:hover { transform: translateY(-1px); box-shadow: var(--shadow-sm); }

  /* Card outline borders — respect dark mode */
  body.dark-mode .card.card-outline.card-success { border-top-color: #10b981 !important; }
  body.dark-mode .card.card-outline.card-warning { border-top-color: #f59e0b !important; }
  body.dark-mode .card.card-outline.card-primary { border-top-color: #4a76bd !important; }
  body.dark-mode .card.card-outline.card-info    { border-top-color: #3b9ec9 !important; }
</style>

<div class="container-fluid">

  {{-- Header --}}
  <div class="row mb-2">
    <div class="col-md-6 d-flex align-items-center">
      <!-- <h5 class="mb-0">📡 Network Dashboard</h5> -->
    </div>
<!--     <div class="col-md-6 text-right">
      <form method="GET" action="/home/network" class="form-inline justify-content-end">
        <label for="date_start" class="mr-2">Dari:</label>
        <input type="date" id="date_start" name="date_start" class="form-control mr-2"
        value="{{ request('date_start', date('Y-m-d')) }}">
        <label for="date_end" class="mr-2">s/d</label>
        <input type="date" id="date_end" name="date_end" class="form-control mr-2"
        value="{{ request('date_end', date('Y-m-d')) }}">
        <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
      </form>
    </div> -->
  </div>

  {{-- ================== INFO BOX ================== --}}
  <div class="row">
    <div class="col-md-3">
      {{-- Ticket Info --}}
      <div class="info-box">
        <span class="info-box-icon bg-info elevation-1"><a href="/ticket"><i class="fas fa-ticket-alt text-white"></i></a></span>
        <div class="info-box-content">
          <span class="info-box-text mb-1"><strong>Tickets</strong></span>
          <span>
            <span class="badge badge-danger mr-1">Open: <b>{{ $ticket_count_per_status['Open'] ?? 0 }}</b></span>
            <span class="badge badge-warning mr-1">Pending: <b>{{ $ticket_count_per_status['Pending'] ?? 0 }}</b></span>
            <span class="badge badge-info mr-1">Inprogress: <b>{{ $ticket_count_per_status['Inprogress'] ?? 0 }}</b></span>
            <span class="badge badge-success mr-1">Solve: <b>{{ $ticket_count_per_status['Solve'] ?? 0 }}</b></span>
            <span class="badge badge-secondary">Close: <b>{{ $ticket_count_per_status['Close'] ?? 0 }}</b></span>
          </span>
        </div>
      </div>

      {{-- Invoice Info --}}
      <div class="info-box">
        <span class="info-box-icon bg-danger elevation-1"><a href="/suminvoice"><i class="fas fa-money-check-alt text-white"></i></a></span>
        <div class="info-box-content">
          <span class="info-box-text">Pending Invoice</span>
          <span class="info-box-number">{{ $invoice_count }}</span>
        </div>
      </div>

      {{-- Transaction Info --}}
      <div class="info-box">
        <span class="info-box-icon bg-success elevation-1"><a href="/suminvoice/transaction"><i class="fas fa-cash-register text-white"></i></a></span>
        <div class="info-box-content">
          <span class="info-box-text">Transaction</span>
          <span class="info-box-number">{{ $invoice_paid }}</span>
        </div>
      </div>

      {{-- Customer Info --}}
      <div class="info-box">
        <span class="info-box-icon bg-warning elevation-1"><a href="/customer"><i class="fas fa-users text-white"></i></a></span>
        <div class="info-box-content" style="font-size: 14px">
          <span class="info-box-text">Active: <b>{{ $cust_active }}</b></span>
          <span class="info-box-text">Blocked: <b>{{ $cust_block }}</b></span>
          <span class="info-box-text">Inactive: <b>{{ $cust_inactive }}</b></span>
          <span class="info-box-text">Potential: <b>{{ $cust_potensial }}</b></span>
        </div>
      </div>
    </div>

    {{-- ================== CHART AREA ================== --}}
    <div class="col-md-9">
      <div class="row">
        {{-- Customer Status --}}
        <div class="col-md-6 d-flex align-items-stretch">
          <div class="card card-success card-outline flex-fill">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0">Customer Status</h3>
            </div>
            <div class="card-body p-2">
              <canvas id="customerStatusChart" height="150"></canvas>
            </div>
          </div>
        </div>

        {{-- Tickets by Category --}}
        <div class="col-md-6 d-flex align-items-stretch">
          <div class="card card-warning card-outline flex-fill">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0">Tickets by Category</h3>
            </div>
            <div class="card-body p-2">
              <canvas id="ticketCategoryChart" height="150"></canvas>
            </div>
          </div>
        </div>

        {{-- New Customers --}}
        <div class="col-md-6 d-flex align-items-stretch">
          <div class="card card-primary card-outline flex-fill">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0">New Customers</h3>
              <span class="badge badge-info badge-pill">Total: {{ $totalNewCustomers ?? 0 }}</span>
            </div>
            <div class="card-body p-2">
              <canvas id="dailyNewCustomersChart" height="150"></canvas>
            </div>
          </div>
        </div>

        {{-- Daily Transactions - Only for Admin & Accounting --}}
        @if(Auth::check() && in_array(Auth::user()->privilege, ['admin', 'accounting']))
        <div class="col-md-6 d-flex align-items-stretch">
          <div class="card card-info card-outline flex-fill">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0">Daily Transactions</h3>
            </div>
            <div class="card-body p-2">
              <canvas id="dailyTransactionChart" height="150"></canvas>
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>

  {{-- ================== DISTRIBUTION ROUTERS ================== --}}
  <h5 class="mt-1 mb-1"><i class="fas fa-network-wired"></i> Distribution Routers</h5>
  <div class="row" id="router-card-list">
    @foreach($distrouter as $router)
    <div class="col-lg-3 col-md-4 mb-3">
      <div class="card shadow-sm border-0 router-card" data-id="{{ $router->id }}" data-ip="{{ $router->ip }}">
        <div class="card-header bg-gradient-primary text-white py-2 d-flex justify-content-between align-items-center">
          <span><i class="fas fa-server mr-1"></i> {{ $router->name }}</span>
          <button class="btn btn-light btn-xs refresh-router" data-id="{{ $router->id }}"><i class="fas fa-sync"></i></button>
        </div>
        <div class="card-body p-2 text-center">
          <div id="pppoe-{{ $router->id }}" class="my-2 small text-muted">Loading...</div>
          <a href="/distrouter/{{ $router->id }}" target="_blank" class="d-block small text-primary">
            <i class="fas fa-globe"></i> {{ $router->ip }}
          </a>

        </div>
      </div>
    </div>
    @endforeach
  </div>

  {{-- ================== ZTE OLT LIST ================== --}}
  <h5 class="mt-1 mb-1"><i class="fas fa-microchip"></i> OLT Devices</h5>
  <div class="row" id="olt-card-list">
    @foreach($olts as $olt)
    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
      <div class="card shadow-sm border-0 olt-card" data-id="{{ $olt->id }}">
        <div class="card-header bg-gradient-info text-white py-2 d-flex justify-content-between align-items-center">
          <span><i class="fas fa-microchip mr-1"></i> {{ $olt->name }}</span>
          <button class="btn btn-light btn-xs refresh-olt" data-id="{{ $olt->id }}"><i class="fas fa-sync"></i></button>
        </div>
        <div class="card-body text-center p-2">
          <div id="olt-info-{{ $olt->id }}" class="my-2 small text-muted">Loading...</div>
          <a href="/olt/{{ $olt->id }}" target="_blank" class="d-block small text-primary">
           <i class="fas fa-map-marker-alt"></i> {{ $olt->ip ?? '-' }}
         </a>
         <!-- <div class="mt-2"><button class="btn btn-sm btn-outline-info show-olt-detail" data-id="{{ $olt->id }}">Detail</button></div> -->
       </div>
     </div>
   </div>
   @endforeach
 </div>

  <h5 class="mt-1 mb-1"><i class="fas fa-clipboard-list"></i> Tickets & Job Schedule</h5>
 <section class="content">
  <div class="container-fluid">
    <!-- Timelime example  -->
    <div class="row">
      <div class="col-12">
        <!-- The time line -->
        <div class="timeline-grid" id="timeline-list">
          @include('partials.timeline_items', ['tickets' => $ticket])
        </div>
        <!-- /.timeline -->
        
        <div class="text-center my-3" id="load-more-info" style="display: {{ count($ticket) >= 10 ? 'block' : 'none' }}">
          <span class="spinner-border spinner-border-sm mr-2 d-none" id="timeline-loading"></span>
          <button class="btn btn-outline-primary btn-sm" id="load-more-timeline">Load More</button>
        </div>
        <input type="hidden" id="page" value="1">
      </div>
      <!-- /.col -->
    </div>
  </div>
  <!-- /.timeline -->

</section>
<!-- /.content -->

</div>
@endsection

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // === Dark mode chart helpers ===
  const isDark         = () => document.body.classList.contains('dark-mode');
  const chartTextColor = () => isDark() ? '#9ba3b2' : '#6b7280';
  const chartGridColor = () => isDark() ? '#333845' : '#e5e7eb';

  const defaultChartOptions = () => ({
    responsive: true,
    plugins: { legend: { labels: { color: chartTextColor() } } },
    scales: {
      x: { ticks: { color: chartTextColor() }, grid: { color: chartGridColor() } },
      y: { ticks: { color: chartTextColor() }, grid: { color: chartGridColor() }, beginAtZero: true }
    }
  });

  // === CHART 1: Customer Status ===
  const customerStatusChart = new Chart(document.getElementById('customerStatusChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: ['Potential', 'Active', 'Inactive', 'Blocked'],
      datasets: [{ data: [{{ $cust_potensial }}, {{ $cust_active }}, {{ $cust_inactive }}, {{ $cust_block }}],
        backgroundColor: ['#FFCC00','#10b981','#6b7280','#ef4444'],
        borderRadius: 5 }]
    },
    options: { ...defaultChartOptions(), plugins: { legend: { display: false } } }
  });

  // === CHART 2: Tickets by Category ===
  const ticketReport = @json($ticket_report);
  const ticketCategoryChart = new Chart(document.getElementById('ticketCategoryChart').getContext('2d'), {
    type: 'pie',
    data: {
      labels: ticketReport.map(t => t.name),
      datasets: [{ data: ticketReport.map(t => t.count),
        backgroundColor: ['#4a76bd','#10b981','#f59e0b','#ef4444','#8b5cf6','#14b8a6','#3b9ec9','#e83e8c']
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom', labels: { color: chartTextColor(), font: { size: 11 } } },
        tooltip: { callbacks: { label: (t) => {
          const total = ticketReport.reduce((a,b)=>a+b.count,0);
          return `${t.label}: ${t.raw} (${((t.raw/total)*100).toFixed(1)}%)`;
        }}}
      }
    }
  });

  // === CHART 3: New Customers ===
  const dailyNewCustomers = @json($dailyNewCustomers);
  const dailyNewCustomersChart = new Chart(document.getElementById('dailyNewCustomersChart').getContext('2d'), {
    type: 'line',
    data: {
      labels: dailyNewCustomers.map(i=>i.date),
      datasets: [{ label: 'New Customers', data: dailyNewCustomers.map(i=>i.new_count),
        borderColor: '#4a76bd', backgroundColor: 'rgba(74,118,189,0.15)',
        fill: true, tension: 0.3, pointRadius: 4 }]
    },
    options: defaultChartOptions()
  });

  // === CHART 4: Daily Transactions (Admin & Accounting only) ===
  @if(Auth::check() && in_array(Auth::user()->privilege, ['admin', 'accounting']))
  const dailyData = @json($dailyTransactions);
  const dailyTransactionChart = new Chart(document.getElementById('dailyTransactionChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: dailyData.map(i=>i.date),
      datasets: [
        { label: 'Jumlah Transaksi', data: dailyData.map(i=>i.volume), backgroundColor: 'rgba(74,118,189,0.65)', borderRadius: 4 },
        { label: 'Total Pembayaran', data: dailyData.map(i=>i.total_paid), type: 'line', borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: false, tension: 0.3 }
      ]
    },
    options: defaultChartOptions()
  });
  @endif

  // === Update all charts when dark mode is toggled ===
  document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('toggleDarkMode');
    if (!toggle) return;
    toggle.addEventListener('click', function() {
      setTimeout(function() {
        const allCharts = [
          customerStatusChart, ticketCategoryChart, dailyNewCustomersChart
          @if(Auth::check() && in_array(Auth::user()->privilege, ['admin', 'accounting']))
          , dailyTransactionChart
          @endif
        ];
        allCharts.forEach(function(chart) {
          if (!chart) return;
          // axis ticks & grid
          if (chart.options.scales && chart.options.scales.x) {
            chart.options.scales.x.ticks.color = chartTextColor();
            chart.options.scales.x.grid.color  = chartGridColor();
            chart.options.scales.y.ticks.color = chartTextColor();
            chart.options.scales.y.grid.color  = chartGridColor();
          }
          // legend labels
          if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels) {
            chart.options.plugins.legend.labels.color = chartTextColor();
          }
          chart.update();
        });
      }, 50);
    });
  });

  // === ROUTER & OLT AUTO REFRESH ===
  $(function(){
    function renderRouterSummary(container,data){
      let html = `
      <div class="badge badge-info mr-1 p-1">Total: ${data.pppUserCount||0}</div>
      <div class="badge badge-success mr-1 p-1">Active: ${data.pppActiveCount||0}</div>
      <div class="badge badge-danger mr-1 p-1">Offline: ${data.pppOfflineCount||0}</div>
      <div class="badge badge-secondary p-1">Disabled: ${data.pppDisabledCount||0}</div>`;
      $(container).html(html);
    }

    function fetchRouterInfo(id){
      const target='#pppoe-'+id;
      $(target).html('<span class="spinner-border spinner-border-sm text-muted"></span>');
      $.get(`/distrouter/getrouterinfo/${id}`,r=>{
        if(r.success) renderRouterSummary(target,r);
        else $(target).html('<span class="text-warning small">No data</span>');
      }).fail(()=>$(target).html('<span class="text-danger small">Error</span>'));
    }

    function renderOltSummary(container,data){
      let o=data.oltInfo;
      let html=`<div class="badge badge-primary mr-1 p-1">Total: ${o.onuCount||0}</div>
      <div class="badge badge-success mr-1 p-1">Online: ${o.working||0}</div>
      <div class="badge badge-danger mr-1 p-1">LOS: ${o.los||0}</div>
      <div class="badge badge-warning mr-1 p-1">Dyinggasp: ${o.dyinggasp||0}</div>
      <div class="badge badge-secondary p-1">Offline: ${o.offline||0}</div>`;
      $(container).html(html);
    }

    function fetchOltInfo(id){
      const target='#olt-info-'+id;
      $(target).html('<span class="spinner-border spinner-border-sm text-muted"></span>');
      $.get(`/olt/getoltinfo/${id}`,r=>{
        if(r.success) renderOltSummary(target,r);
        else $(target).html('<span class="text-warning small">No data</span>');
      }).fail(()=>$(target).html('<span class="text-danger small">Error</span>'));
    }

    @foreach($distrouter as $r) fetchRouterInfo({{ $r->id }}); @endforeach
    @foreach($olts as $o) fetchOltInfo({{ $o->id }}); @endforeach

    $(document).on('click','.refresh-router',e=>fetchRouterInfo($(e.currentTarget).data('id')));
    $(document).on('click','.refresh-olt',e=>fetchOltInfo($(e.currentTarget).data('id')));

    // Auto-refresh tiap 3 menit
    setInterval(()=>{
      @foreach($distrouter as $r) fetchRouterInfo({{ $r->id }}); @endforeach
      @foreach($olts as $o) fetchOltInfo({{ $o->id }}); @endforeach
    },180000);
  });
</script>
@endsection
