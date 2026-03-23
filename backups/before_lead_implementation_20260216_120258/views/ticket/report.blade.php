@extends('layout.main')
@section('title','Ticket Analytics Report')

@section('header-styles')
<style>
  /* Modern Card Styling */
  .card {
    border-radius: 10px;
    border: none;
  }
  
  .card-header {
    border-radius: 10px 10px 0 0 !important;
    font-weight: 600;
  }
  
  .shadow-sm {
    box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.075) !important;
  }
  
  /* Statistics Card */
  .stats-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }
  
  .stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
  }
  
  .stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
  }
  
  .stats-card-primary::before { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
  .stats-card-success::before { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); }
  .stats-card-warning::before { background: linear-gradient(135deg, #f09819 0%, #edde5d 100%); }
  .stats-card-info::before { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
  
  .stats-icon {
    font-size: 45px;
    opacity: 0.15;
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
  }
  
  .stats-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    color: #2c3e50;
  }
  
  .stats-label {
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 5px;
    font-weight: 600;
  }
  
  /* Chart Container */
  .chart-container {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
  }
  
  .chart-container:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
  }
  
  .chart-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 3px solid;
    border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;
  }
  
  .chart-title i {
    margin-right: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  
  /* Table Styling */
  .table-container {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }
  
  .table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }
  
  .table thead th {
    border: none;
    padding: 15px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    color: white;
  }
  
  .table tbody tr {
    transition: all 0.3s ease;
  }
  
  .table tbody tr:hover {
    background: #f8f9fa;
    transform: scale(1.01);
  }
  
  .rank-badge {
    display: inline-block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    text-align: center;
    border-radius: 50%;
    font-weight: 700;
    color: white;
    font-size: 1rem;
  }
  
  .rank-1 { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #333 !important; }
  .rank-2 { background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%); color: #333 !important; }
  .rank-3 { background: linear-gradient(135deg, #cd7f32 0%, #e8a87c 100%); }
  .rank-other { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
  
  /* Filter Card */
  .filter-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }
  
  .filter-card .form-control {
    border-radius: 8px;
    border: none;
    padding: 12px 15px;
    font-weight: 500;
  }
  
  .filter-card .form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
  }
  
  .filter-card label {
    color: white;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.875rem;
  }
  
  /* Button Styling */
  .btn-filter {
    background: white;
    color: #667eea;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 700;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.875rem;
  }
  
  .btn-filter:hover {
    background: rgba(255,255,255,0.9);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
  }
  
  .btn-view {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 20px;
    padding: 8px 20px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s ease;
  }
  
  .btn-view:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    color: white;
  }
  
  /* Content Header */
  .content-header h1 {
    font-weight: 700;
    color: #2c3e50;
  }
  
  .page-subtitle {
    color: #6c757d;
    font-size: 1rem;
    margin-top: 5px;
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .stats-number {
      font-size: 1.75rem;
    }
    
    .stats-icon {
      font-size: 35px;
    }
    
    .chart-container {
      padding: 20px;
    }
  }
</style>
@endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-12">
        <h1 class="m-0"><i class="fas fa-chart-line text-primary"></i> Ticket Analytics Report</h1>
        <p class="page-subtitle">Comprehensive ticket statistics and insights</p>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    
    <!-- Date Filter Card -->
    <div class="filter-card">
      <form role="form" method="post" action="/ticket/reportsrc">
        @csrf
        <div class="row align-items-end">
          <div class="col-md-4">
            <label><i class="fas fa-calendar-alt mr-2"></i>From Date</label>
            <input type="date" name="date_from" class="form-control" value="{{$date_from}}" required />
          </div>
          <div class="col-md-4">
            <label><i class="fas fa-calendar-alt mr-2"></i>To Date</label>
            <input type="date" name="date_end" class="form-control" value="{{$date_end}}" required />
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-filter btn-block">
              <i class="fas fa-filter mr-2"></i>Apply Filter
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- MTTR Statistics Card -->
    <div class="row mb-4">
      <div class="col-lg-12">
        <div class="stats-card stats-card-primary">
          <i class="fas fa-clock stats-icon"></i>
          <div class="row">
            <div class="col-md-6">
              <p class="stats-number">{{ $mttr }} <span style="font-size: 1rem; color: #6c757d;">hours</span></p>
              <p class="stats-label">Mean Time To Resolution (MTTR)</p>
              <p style="color: #6c757d; font-size: 0.85rem; margin-top: 10px;">
                <i class="fas fa-info-circle mr-1"></i>
                Average time from ticket creation to completion based on workflow steps
              </p>
            </div>
            <div class="col-md-6 text-right">
              <div style="font-size: 1rem; color: #6c757d; margin-top: 20px;">
                <i class="fas fa-check-circle mr-2" style="color: #28a745;"></i>
                <span style="font-weight: 600;">{{ $mttr_count }}</span> tickets completed
              </div>
              <div style="font-size: 0.9rem; color: #6c757d; margin-top: 10px;">
                @if($mttr < 24)
                  <span class="badge badge-success" style="padding: 8px 15px; font-size: 0.85rem;">
                    <i class="fas fa-thumbs-up mr-1"></i>Excellent Response Time
                  </span>
                @elseif($mttr < 48)
                  <span class="badge badge-info" style="padding: 8px 15px; font-size: 0.85rem;">
                    <i class="fas fa-check mr-1"></i>Good Response Time
                  </span>
                @else
                  <span class="badge badge-warning" style="padding: 8px 15px; font-size: 0.85rem;">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Needs Improvement
                  </span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics Cards - Hidden -->
    <!--
    <div class="row">
      <div class="col-lg-3 col-md-6">
        <div class="stats-card stats-card-primary">
          <i class="fas fa-ticket-alt stats-icon"></i>
          <p class="stats-number">{{ $ticket_report->sum('count') }}</p>
          <p class="stats-label">Total Tickets</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stats-card stats-card-info">
          <i class="fas fa-tags stats-icon"></i>
          <p class="stats-number">{{ $ticket_report->count() }}</p>
          <p class="stats-label">Categories</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stats-card stats-card-success">
          <i class="fas fa-users stats-icon"></i>
          <p class="stats-number">{{ $ticket_customer->count() }}</p>
          <p class="stats-label">Active Customers</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stats-card stats-card-warning">
          <i class="fas fa-calendar-day stats-icon"></i>
          <p class="stats-number">{{ $ticket_date->count() }}</p>
          <p class="stats-label">Active Days</p>
        </div>
      </div>
    </div>
    -->

    <!-- Charts Section - All in One Row -->
    <div class="row">
      <div class="col-lg-4">
        <div class="chart-container">
          <h3 class="chart-title"><i class="fas fa-chart-pie"></i>Tickets by Category</h3>
          <canvas id="myChart" style="height: 250px;"></canvas>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="chart-container">
          <h3 class="chart-title"><i class="fas fa-chart-line"></i>Tickets by Date</h3>
          <canvas id="myChartDate" style="height: 250px;"></canvas>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="chart-container">
          <h3 class="chart-title"><i class="fas fa-chart-area"></i>Created vs Closed Tickets</h3>
          <canvas id="myChartTrend" style="height: 250px;"></canvas>
        </div>
      </div>
    </div>

    <!-- Average Time Per Step Chart -->
    @if(count($avg_step_time) > 0)
    <div class="row mb-4">
      <div class="col-lg-12">
        <div class="chart-container">
          <h3 class="chart-title"><i class="fas fa-hourglass-half"></i>Average Time Per Workflow Step</h3>
          <canvas id="myChartSteps" style="height: 300px;"></canvas>
        </div>
      </div>
    </div>
    @endif

    <!-- Top Customers Table -->
    <div class="table-container">
      <h3 class="chart-title"><i class="fas fa-trophy"></i>Top 10 Customers by Ticket Volume</h3>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th style="width: 80px;">Rank</th>
              <th>Customer Name</th>
              <th style="width: 150px;" class="text-center">Total Tickets</th>
              <th style="width: 150px;" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach( $ticket_customer as $customer)
            <tr>
              <td>
                <span class="rank-badge rank-{{ $loop->iteration <= 3 ? $loop->iteration : 'other' }}">
                  {{ $loop->iteration }}
                </span>
              </td>
              <td style="font-weight: 600; color: #333;">
                <i class="fas fa-user mr-2" style="color: #667eea;"></i>{{ $customer->name }}
              </td>
              <td class="text-center">
                <span class="badge badge-primary" style="font-size: 14px; padding: 8px 15px; border-radius: 20px;">
                  {{ $customer->count }} tickets
                </span>
              </td>
              <td class="text-center">
                <a href="/ticket/view/{{ $customer->cust_id }}" class="btn btn-sm btn-view">
                  <i class="fas fa-eye mr-1"></i>View Tickets
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    
  </div>
</section>

@endsection

@section('footer-scripts')

@section('footer-scripts')
<?php
$count = "";
$name = "";
$colors = ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#fee140', '#30cfd0'];
$colorIndex = 0;
$chartColors = "";

foreach($ticket_report as $ticket)
{
  if (!empty($count))
  {
    $count  = $count.", ". $ticket->count; 
    $name  = $name.',"'.$ticket->name.'"';
    $chartColors = $chartColors.',"'.$colors[$colorIndex % count($colors)].'"';
  }
  else
  {
    $count = $ticket->count; 
    $name = '"'.$ticket->name.'"';
    $chartColors = '"'.$colors[$colorIndex % count($colors)].'"';
  }
  $colorIndex++;
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
  // Category Chart (Doughnut)
  var xValues = [<?php echo $name; ?>];
  var yValues = [<?php echo $count; ?>];
  var barColors = [<?php echo $chartColors; ?>];

  new Chart("myChart", {
    type: "doughnut",
    data: {
      labels: xValues,
      datasets: [{
        backgroundColor: barColors,
        data: yValues,
        borderWidth: 3,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      legend: {
        display: true,
        position: 'bottom',
        labels: {
          padding: 20,
          fontSize: 13,
          fontColor: '#333',
          fontFamily: "'Segoe UI', sans-serif",
          usePointStyle: true
        }
      },
      tooltips: {
        backgroundColor: 'rgba(0,0,0,0.8)',
        titleFontSize: 14,
        bodyFontSize: 13,
        cornerRadius: 8,
        displayColors: true,
        xPadding: 15,
        yPadding: 15,
        callbacks: {
          label: function(tooltipItem, data) {
            var label = data.labels[tooltipItem.index] || '';
            var value = data.datasets[0].data[tooltipItem.index];
            var total = data.datasets[0].data.reduce((a, b) => a + b, 0);
            var percentage = ((value / total) * 100).toFixed(1);
            return ' ' + label + ': ' + value + ' (' + percentage + '%)';
          }
        }
      },
      cutoutPercentage: 60
    }
  });
</script>

<?php
$countx = "";
$date = "";
foreach($ticket_date as $ticket)
{
  if (!empty($countx))
  {
    $countx  = $countx.", ". $ticket->countdate; 
    $date  = $date.',"'.$ticket->date.'"';
  }
  else
  {
    $countx = $ticket->countdate; 
    $date = '"'.$ticket->date.'"';
  }
}
?>

<script>
  // Date Chart (Line)
  var xValuesx = [<?php echo $date; ?>];
  var yValuesx = [<?php echo $countx; ?>];

  new Chart("myChartDate", {
    type: "line",
    data: {
      labels: xValuesx,
      datasets: [{
        label: 'Tickets per Day',
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        borderColor: '#667eea',
        data: yValuesx,
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointBackgroundColor: '#667eea',
        pointBorderColor: '#fff',
        pointBorderWidth: 3,
        pointRadius: 6,
        pointHoverRadius: 8,
        pointHoverBackgroundColor: '#764ba2',
        pointHoverBorderWidth: 3
      }]
    },
    options:{
      responsive: true,
      maintainAspectRatio: true,
      scales: {
        yAxes: [{
          ticks: {
            beginAtZero: true,
            fontSize: 12,
            fontColor: '#666',
            fontFamily: "'Segoe UI', sans-serif",
            padding: 10
          },
          gridLines: {
            color: 'rgba(0, 0, 0, 0.05)',
            zeroLineColor: 'rgba(0, 0, 0, 0.1)',
            drawBorder: false
          }
        }],
        xAxes: [{
          ticks: {
            fontSize: 11,
            fontColor: '#666',
            fontFamily: "'Segoe UI', sans-serif",
            padding: 10,
            maxRotation: 45,
            minRotation: 0
          },
          gridLines: {
            display: false,
            drawBorder: false
          }
        }]
      },
      legend: {
        display: true,
        position: 'top',
        labels: {
          fontSize: 13,
          fontColor: '#333',
          padding: 15,
          usePointStyle: true,
          fontFamily: "'Segoe UI', sans-serif"
        }
      },
      tooltips: {
        backgroundColor: 'rgba(0,0,0,0.8)',
        titleFontSize: 14,
        bodyFontSize: 13,
        cornerRadius: 8,
        displayColors: false,
        xPadding: 15,
        yPadding: 15,
        callbacks: {
          title: function(tooltipItems, data) {
            return 'Date: ' + tooltipItems[0].xLabel;
          },
          label: function(tooltipItem, data) {
            return 'Tickets: ' + tooltipItem.yLabel;
          }
        }
      }
    }
  });
</script>

<?php
// Prepare data for Created Tickets
$created_count = "";
$created_dates = "";
foreach($ticket_created as $ticket)
{
  if (!empty($created_count))
  {
    $created_count  = $created_count.", ". $ticket->count; 
    $created_dates  = $created_dates.',"'.$ticket->date.'"';
  }
  else
  {
    $created_count = $ticket->count; 
    $created_dates = '"'.$ticket->date.'"';
  }
}

// Prepare data for Closed Tickets
$closed_count = "";
$closed_dates = "";
foreach($ticket_closed as $ticket)
{
  if (!empty($closed_count))
  {
    $closed_count  = $closed_count.", ". $ticket->count; 
    $closed_dates  = $closed_dates.',"'.$ticket->date.'"';
  }
  else
  {
    $closed_count = $ticket->count; 
    $closed_dates = '"'.$ticket->date.'"';
  }
}

// Merge dates for x-axis
$all_dates_array = array_unique(array_merge(
  $ticket_created->pluck('date')->toArray(),
  $ticket_closed->pluck('date')->toArray()
));
sort($all_dates_array);

$all_dates_created = [];
$all_dates_closed = [];

foreach($all_dates_array as $date) {
  $created = $ticket_created->firstWhere('date', $date);
  $closed = $ticket_closed->firstWhere('date', $date);
  
  $all_dates_created[] = $created ? $created->count : 0;
  $all_dates_closed[] = $closed ? $closed->count : 0;
}

$merged_dates = '"' . implode('","', $all_dates_array) . '"';
$merged_created = implode(',', $all_dates_created);
$merged_closed = implode(',', $all_dates_closed);
?>

<script>
  // Created vs Closed Tickets Trend Chart
  var trendDates = [<?php echo $merged_dates; ?>];
  var createdData = [<?php echo $merged_created; ?>];
  var closedData = [<?php echo $merged_closed; ?>];

  new Chart("myChartTrend", {
    type: "line",
    data: {
      labels: trendDates,
      datasets: [{
        label: 'Created Tickets',
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        borderColor: '#667eea',
        data: createdData,
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointBackgroundColor: '#667eea',
        pointBorderColor: '#fff',
        pointBorderWidth: 3,
        pointRadius: 5,
        pointHoverRadius: 8,
        pointHoverBackgroundColor: '#667eea',
        pointHoverBorderWidth: 3
      },
      {
        label: 'Closed Tickets',
        backgroundColor: 'rgba(40, 167, 69, 0.1)',
        borderColor: '#28a745',
        data: closedData,
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointBackgroundColor: '#28a745',
        pointBorderColor: '#fff',
        pointBorderWidth: 3,
        pointRadius: 5,
        pointHoverRadius: 8,
        pointHoverBackgroundColor: '#28a745',
        pointHoverBorderWidth: 3
      }]
    },
    options:{
      responsive: true,
      maintainAspectRatio: true,
      scales: {
        yAxes: [{
          ticks: {
            beginAtZero: true,
            fontSize: 12,
            fontColor: '#666',
            fontFamily: "'Segoe UI', sans-serif",
            padding: 10,
            stepSize: 1
          },
          gridLines: {
            color: 'rgba(0, 0, 0, 0.05)',
            zeroLineColor: 'rgba(0, 0, 0, 0.1)',
            drawBorder: false
          }
        }],
        xAxes: [{
          ticks: {
            fontSize: 11,
            fontColor: '#666',
            fontFamily: "'Segoe UI', sans-serif",
            padding: 10,
            maxRotation: 45,
            minRotation: 0
          },
          gridLines: {
            display: false,
            drawBorder: false
          }
        }]
      },
      legend: {
        display: true,
        position: 'top',
        labels: {
          fontSize: 14,
          fontColor: '#333',
          padding: 20,
          usePointStyle: true,
          fontFamily: "'Segoe UI', sans-serif"
        }
      },
      tooltips: {
        backgroundColor: 'rgba(0,0,0,0.8)',
        titleFontSize: 14,
        bodyFontSize: 13,
        cornerRadius: 8,
        displayColors: true,
        xPadding: 15,
        yPadding: 15,
        mode: 'index',
        intersect: false,
        callbacks: {
          title: function(tooltipItems, data) {
            return 'Date: ' + tooltipItems[0].xLabel;
          },
          label: function(tooltipItem, data) {
            var label = data.datasets[tooltipItem.datasetIndex].label || '';
            return ' ' + label + ': ' + tooltipItem.yLabel;
          }
        }
      }
    }
  });

  // ========================================
  // Average Time Per Step Chart (Horizontal Bar)
  // ========================================
  @if(count($avg_step_time) > 0)
  <?php
  $stepNames = "";
  $stepHours = "";
  $stepColors = ['#667eea', '#43e97b', '#fa709a', '#4facfe', '#f093fb', '#30cfd0', '#fee140', '#764ba2'];
  $stepColorIndex = 0;
  $bgColors = "";

  foreach($avg_step_time as $name => $hours) {
    if (!empty($stepNames)) {
      $stepNames .= ',"' . $name . '"';
      $stepHours .= ',' . $hours;
      $bgColors .= ',"' . $stepColors[$stepColorIndex % count($stepColors)] . '"';
    } else {
      $stepNames = '"' . $name . '"';
      $stepHours = $hours;
      $bgColors = '"' . $stepColors[$stepColorIndex % count($stepColors)] . '"';
    }
    $stepColorIndex++;
  }
  ?>

  var stepLabels = [<?php echo $stepNames; ?>];
  var stepData = [<?php echo $stepHours; ?>];
  var stepBarColors = [<?php echo $bgColors; ?>];

  new Chart("myChartSteps", {
    type: "horizontalBar",
    data: {
      labels: stepLabels,
      datasets: [{
        label: 'Average Hours',
        backgroundColor: stepBarColors,
        borderColor: stepBarColors,
        borderWidth: 2,
        data: stepData
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      indexAxis: 'y',
      scales: {
        xAxes: [{
          ticks: {
            beginAtZero: true,
            fontSize: 12,
            fontColor: '#666',
            fontFamily: "'Segoe UI', sans-serif",
            callback: function(value) {
              return value + ' hrs';
            }
          },
          gridLines: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          scaleLabel: {
            display: true,
            labelString: 'Hours',
            fontSize: 13,
            fontColor: '#666',
            fontStyle: 'bold'
          }
        }],
        yAxes: [{
          ticks: {
            fontSize: 13,
            fontColor: '#333',
            fontFamily: "'Segoe UI', sans-serif",
            fontStyle: '600'
          },
          gridLines: {
            display: false,
            drawBorder: false
          }
        }]
      },
      legend: {
        display: false
      },
      tooltips: {
        backgroundColor: 'rgba(0,0,0,0.8)',
        titleFontSize: 14,
        bodyFontSize: 13,
        cornerRadius: 8,
        xPadding: 15,
        yPadding: 15,
        callbacks: {
          label: function(tooltipItem, data) {
            var hours = tooltipItem.xLabel;
            var days = Math.floor(hours / 24);
            var remainingHours = hours % 24;
            
            if (days > 0) {
              return ' ' + days + ' day(s) ' + remainingHours.toFixed(1) + ' hour(s)';
            } else {
              return ' ' + hours + ' hour(s)';
            }
          }
        }
      }
    }
  });
  @endif
</script>

@endsection