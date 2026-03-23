@extends('layout.main')

@section('content')
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
  new Chart(ctxPie, {
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
        title: { display: true, text: 'Tiket Berdasarkan Tag' },
        datalabels: {
          color: '#000',
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
  new Chart(ctx, {
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
          font: {
            size: 18
          }
        },
        tooltip: {
          mode: 'index',
          intersect: false
        },
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
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
