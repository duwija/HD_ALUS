@extends('layout.main')

@section('content')
<style>
  /* ============================================================
     DASHBOARD — Dark Mode Component Overrides
     ============================================================ */

  /* Info Box */
  .info-box {
    background: var(--bg-surface) !important;
    border: 1px solid var(--border) !important;
    border-radius: 12px !important;
    box-shadow: var(--shadow-sm) !important;
    color: var(--text-primary) !important;
    transition: background 0.25s ease, box-shadow 0.2s ease;
  }
  .info-box:hover { box-shadow: var(--shadow-md) !important; }
  .info-box.bg-light {
    background: var(--bg-surface) !important;
  }
  .info-box-content  { color: var(--text-primary) !important; }
  .info-box-text     { color: var(--text-secondary) !important; }
  .info-box-number   { color: var(--text-primary) !important; font-weight: 700; }

  /* Dashboard title */
  h5.mb-0 { color: var(--text-primary) !important; }
  .content-header h1 { color: var(--text-primary) !important; }

  /* bg-light / text-muted overrides dark mode */
  body.dark-mode .bg-light {
    background-color: var(--bg-surface-2) !important;
    color: var(--text-primary) !important;
  }
  body.dark-mode .text-muted { color: var(--text-muted) !important; }

  /* Status badge (timeline) */
  .status-badge {
    position: absolute;
    top: 10px;
    right: 12px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.3px;
  }
  .status-badge.badge-danger    { background: #d94f3d; }
  .status-badge.badge-warning   { background: #e0963a; color: #fff; }
  .status-badge.badge-info      { background: #3b9ec9; }
  .status-badge.badge-secondary { background: #6b7280; }
  .status-badge.badge-primary   { background: #4a76bd; }
  body.dark-mode .status-badge.badge-danger    { background: rgba(239,68,68,0.25);    color: #fca5a5; border: 1px solid rgba(239,68,68,0.4); }
  body.dark-mode .status-badge.badge-warning   { background: rgba(245,158,11,0.25);   color: #fcd34d; border: 1px solid rgba(245,158,11,0.4); }
  body.dark-mode .status-badge.badge-info      { background: rgba(59,130,246,0.25);   color: #93c5fd; border: 1px solid rgba(59,130,246,0.4); }
  body.dark-mode .status-badge.badge-secondary { background: rgba(107,114,128,0.25);  color: #d1d5db; border: 1px solid rgba(107,114,128,0.4); }
  body.dark-mode .status-badge.badge-primary   { background: rgba(74,118,189,0.25);   color: #93c5fd; border: 1px solid rgba(74,118,189,0.4); }

  /* Workflow progress */
  .workflow-wrapper .base-line {
    height: 3px;
    top: 12px;
    background: var(--border) !important;
  }
  .workflow-wrapper .progress-line {
    height: 3px;
    top: 12px;
    background: var(--brand) !important;
    transition: width 1s ease;
  }
  .step-label { color: var(--text-secondary) !important; font-size: 10px; }
  .step-dot {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 11px;
    position: relative;
    z-index: 1;
    transition: background 0.2s ease, border 0.2s ease;
  }
  .step-dot.done     { background: var(--brand);      color: #fff;  border: 2px solid var(--brand-dark); }
  .step-dot.active   { background: #3b9ec9;            color: #fff;  border: 2px solid #2a7a9e; box-shadow: 0 0 0 3px rgba(59,158,201,0.25); }
  .step-dot.pending  { background: var(--bg-surface-2);color: var(--text-muted); border: 2px solid var(--border); }

  /* Date filter form label */
  .form-inline label { color: var(--text-secondary) !important; }

  /* Section divider */
  body.dark-mode .bg-red { background: var(--brand) !important; color: #fff !important; }

  /* Chart card area */
  .card canvas { display: block; }
</style>
<div class="container-fluid"> <!-- atau pakai container biasa -->
  <div class="row justify-content-center">
    <div class="col-12 col-md-10"> <!-- lebar 10 dari 12 -->
     <div class="row mb-3">
      <div class="col-md-6 d-flex align-items-center">
        <!-- Bisa diisi judul, info, dsb -->
        <h5 class="mb-0">Dashboard Statistik</h5>
      </div>
      <div class="col-md-6 d-none d-md-flex justify-content-end">
        <form method="GET" action="/home" class="form-inline">
          <label for="date_start" class="mr-2">Dari:</label>
          <input type="date" id="date_start" name="date_start" class="form-control mr-2"
          value="{{ request('date_start', date('Y-m-d')) }}">
          <label for="date_end" class="mr-2">s/d</label>
          <input type="date" id="date_end" name="date_end" class="form-control mr-2"
          value="{{ request('date_end', date('Y-m-d')) }}">
          <button type="submit" class="btn btn-primary">Show</button>
        </form>
      </div>
    </div>
    <div class="row">
     <!-- KIRI: Info Box -->


     <div class="col-md-4">
      <div class="row">

        <!-- Info Box 1 -->
        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
          <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><a href="/ticket"><i class="fas fa-ticket-alt"></i></a></span>
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
        </div>


        <!-- Info Box 2 -->
        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
          <div class="info-box">
            <span class="info-box-icon bg-danger elevation-1"><a href="/suminvoice"><i class="fas fa-money-check-alt"></i></a></span>
            <div class="info-box-content">
              <span class="info-box-text">Pending Invoice</span>
              <span class="info-box-number">{{$invoice_count}}</span>
            </div>
          </div>
        </div>

        <!-- Info Box 3 -->
        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
          <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><a href="/suminvoice/transaction"><i class="fas fa-cash-register"></i></a></span>
            <div class="info-box-content">
              <span class="info-box-text">Transaction</span>
              <span class="info-box-number">{{$invoice_paid}}</span>
            </div>
          </div>
        </div>

        <!-- Info Box 4 -->
        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
          <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><a href="/customer"><i class="fas fa-users"></i></a></span>
            <div class="info-box-content" style="font-size: 14px">
              <span class="info-box-text">Active Customer: <b>{{$cust_active}}</b></span>
              <span class="info-box-text">Blocked Customer: <b>{{$cust_block}}</b></span>
              <span class="info-box-text">Inactive Customer: <b>{{$cust_inactive}}</b></span>
              <span class="info-box-text">Potential Customer: <b>{{$cust_potensial}}</b></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- KANAN: Chart -->
    <div class="card  d-none d-md-block col-md-5">

      <div class="card-body" style="height: 360px">
        <canvas id="myChart"></canvas>
      </div>
    </div>




    <div class="card d-none d-md-block col-md-3">
      <div class="card-body d-flex justify-content-center align-items-center" style="height: 360px;">
        <div style="height:320px;width:320px;">
          <canvas id="pieTagChart" width="300" height="300" style="display:block; margin:auto;"></canvas>
        </div>
      </div>
    </div>

  </div>
  <!-- /.row -->
  <div class="row">
    @foreach($jobTickets as $job => $statusList)
    @php
    $progress = collect($jobTitleProgress)->firstWhere('job_title', $job);
    $percent = $progress['percent'] ?? 0;
    $count = $progress['count'] ?? 0;
    $bgClass = ['bg-info', 'bg-success', 'bg-warning', 'bg-danger', 'bg-primary'];
    $color = $bgClass[crc32($job) % count($bgClass)];
    $tooltipText = "Progress {$job}: {$percent}% dari {$count} tiket";
    @endphp
    <div class="col-md-4 col-lg-3">
      <div class="info-box mb-3 bg-light shadow-sm">
        <span class="info-box-icon {{ $color }} elevation-1"><i class="fas fa-user-tie"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">
            <strong>{{ $job }}</strong>
            <span class="text-muted">({{ $count }})</span>
          </span>
          <span class="info-box-number mb-2">
            @foreach(['Open','Pending','Inprogress','Solve','Close'] as $status)
            <span
            class="badge badge-soft-{{ 
              $status == 'Open' ? 'danger' :
              ($status == 'Pending' ? 'warning' :
              ($status == 'Inprogress' ? 'info' :
              ($status == 'Solve' ? 'success' : 'secondary')))
            }} badge-status"
            data-toggle="tooltip"
            data-placement="top"
            title="Jumlah tiket {{ strtolower($status) }}: {{ $statusList[$status] ?? 0 }}"
            >
            {{ $status }}: {{ $statusList[$status] ?? 0 }}
          </span>
          @endforeach
        </span>

        <!-- Animated Progress Bar + Tooltip -->
        <div class="progress" style="height: 15px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated {{ $color }}"
          role="progressbar"
          style="width: {{ $percent }}%; transition: width 1s;"
          aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"
          data-toggle="tooltip" data-placement="bottom"
          title="{{ $tooltipText }}">
          {{ $percent }}%
        </div>
      </div>
    </div>
  </div>
</div>
@endforeach
</div>



{{-- Di bawah sini tempatkan --}}
@php
$labels = $ticket_report->pluck('name');
$data = $ticket_report->pluck('count');
@endphp


<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">

    <h1>Job Schedule</h1>
  </div>


</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">


    <!-- Timelime example  -->
    <div class="row">
      <div class="">
        <!-- The time line -->
        <div class="timeline bg">
          <!-- timeline time label -->
          <div class="time-label">
            <span class="bg-red">{{ $date_start }} s/d {{ $date_end }}</span>
          </div>
          <!-- /.timeline-label -->
          <!-- timeline item -->
          <div class="timeline bg" id="timeline-list">
            @include('partials.timeline_items', ['tickets' => $ticket])
          </div>
          <div class="text-center my-2" id="load-more-info" style="display: {{ count($ticket) >= 10 ? 'block' : 'none' }}">
            <span class="spinner-border spinner-border-sm mr-2 d-none" id="timeline-loading"></span>
            <button class="btn btn-outline-primary btn-sm" id="load-more-timeline">Load More</button>
          </div>
          <input type="hidden" id="page" value="1">





        </div>
      </div>
      <!-- /.col -->
    </div>
  </div>
  <!-- /.timeline -->

</section>
<!-- /.content -->

<!-- /.content-wrapper -->
</div>
</div>
</div>
@endsection
@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<!-- Dark mode chart helpers — defined before any chart init -->
<script>
  const isDark         = () => document.body.classList.contains('dark-mode');
  const chartTextColor = () => isDark() ? '#9ba3b2' : '#6b7280';
  const chartGridColor = () => isDark() ? '#333845' : '#e5e7eb';
</script>

<script>
  $(function () {
    $('[data-toggle="tooltip"]').tooltip();
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
  const tagLabels = {!! json_encode($tagLabels) !!};
  const tagData = {!! json_encode($tagData) !!};
  const ctxPie = document.getElementById('pieTagChart').getContext('2d');
  const pieChart = new Chart(ctxPie, {
    type: 'pie',
    data: {
      labels: tagLabels,
      datasets: [{
        data: tagData,
        backgroundColor: [
          '#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#1f8ef1', '#fd5d93'
          ]
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }, // Legend bawah tidak ditampilkan
        title: { display: true, text: 'Tiket Berdasarkan Tag', color: chartTextColor() },
        datalabels: {
          color: document.body.classList.contains('dark-mode') ? '#e8eaf0' : '#000',
          font: {

            size: 9
          },
           anchor: 'center', // <-- ini bikin rata tengah di PIE
      align: 'center',  // <-- ini juga penting!
      offset: 0,
      clamp: true,
      formatter: function(value, context) {
        let total = context.dataset.data.reduce((a, b) => a + b, 0);
        let percentage = Math.round((value / total) * 100);
        let label = context.chart.data.labels[context.dataIndex];
  if (percentage < 0.5) return ''; // tidak tampil kalau < 5%
  return label + ' ' + percentage + '%';
}
}
}
},
plugins: [ChartDataLabels]
});
</script>

<script>
  let page = 1;
  let loading = false;
  let hasMore = {{ count($ticket) >= 10 ? 'true' : 'false' }};

  function loadMoreTimeline() {
    if (!hasMore || loading) return;
    loading = true;
    $('#timeline-loading').removeClass('d-none');
    page++;

    $.ajax({
      url: '{{ route("jobschedule.ajax") }}',
      data: {
        page: page,
        date_start: '{{ $date_start }}',
        date_end: '{{ $date_end }}'
      },
      success: function(res) {
        $('#timeline-list').append(res.html);
        $('[data-toggle="tooltip"]').tooltip();
        hasMore = res.hasMore;
        if (!hasMore) {
          $('#load-more-info').hide();
        }
      },
      complete: function() {
        $('#timeline-loading').addClass('d-none');
        loading = false;
      }
    });
  }

  $('#load-more-timeline').on('click', function() {
    loadMoreTimeline();
  });

// Infinite scroll trigger (otomatis load jika scroll ke bawah)
  $(window).on('scroll', function() {
    if (!hasMore || loading) return;
    let scrollHeight = $(document).height() - $(window).height();
    if ($(window).scrollTop() > scrollHeight - 200) {
      loadMoreTimeline();
    }
  });
</script>


<script>
  const labels = {!! json_encode($labels) !!};
  const data = {!! json_encode($data) !!};

  const ctx = document.getElementById('myChart').getContext('2d');
  const barChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Ticket Count',
        data: data,
        backgroundColor: 'rgba(54, 162, 235, 0.7)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1,
        borderRadius: 5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: {
          display: true,
          text: 'Tickets by Category',
          color: chartTextColor(),
          font: { size: 16 }
        },
        tooltip: { mode: 'index', intersect: false },
        legend: { display: false }
      },
      scales: {
        x: {
          ticks: { color: chartTextColor() },
          grid:  { color: chartGridColor() }
        },
        y: {
          beginAtZero: true,
          ticks: { color: chartTextColor() },
          grid:  { color: chartGridColor() }
        }
      }
    }
  });

  // Re-apply chart colors when dark mode is toggled
  document.getElementById('toggleDarkMode').addEventListener('click', function() {
    // Wait for class to be toggled
    setTimeout(function() {
      barChart.options.plugins.title.color = chartTextColor();
      barChart.options.scales.x.ticks.color = chartTextColor();
      barChart.options.scales.x.grid.color  = chartGridColor();
      barChart.options.scales.y.ticks.color = chartTextColor();
      barChart.options.scales.y.grid.color  = chartGridColor();
      barChart.update();

      pieChart.options.plugins.title.color = chartTextColor();
      pieChart.options.plugins.datalabels.color = isDark() ? '#e8eaf0' : '#000';
      pieChart.update();
    }, 50);
  });
</script>
<script>
  $(function () {
  // Inisialisasi tooltip dengan delay (jika ingin tetap pakai delay)
    $('[data-toggle="tooltip"]').tooltip({
      delay: { show: 300, hide: 150 }
    });

  // Tutup tooltip lain saat yang baru dibuka
    $('[data-toggle="tooltip"]').on('show.bs.tooltip', function () {
      $('[data-toggle="tooltip"]').not(this).tooltip('hide');
    });
  });
</script>


@endsection
