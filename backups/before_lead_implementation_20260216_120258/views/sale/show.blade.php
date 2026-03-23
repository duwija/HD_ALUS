
@extends('layout.main')
@section('title','Sales Dashboard - ' . $sale->name)
@section('content')
@inject('suminvoice', 'App\Suminvoice')

<div class="container-fluid px-4">
  <section class="content-header">
    <div class="row mb-3">
      <div class="col-12">
        <h2 class="font-weight-bold">Sales Dashboard - {{ $sale->name }}</h2>
      </div>
    </div>

    <!-- Date Range Filter -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <form method="GET" action="{{ url('/sale/' . $sale->id) }}" class="form-inline">
              <div class="form-group mr-3">
                <label for="date_from" class="mr-2">Dari Tanggal:</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $dateFrom }}">
              </div>
              <div class="form-group mr-3">
                <label for="date_to" class="mr-2">Sampai Tanggal:</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $dateTo }}">
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter Grafik
              </button>
              <a href="{{ url('/sale/' . $sale->id) }}" class="btn btn-secondary ml-2">
                <i class="fas fa-redo"></i> Reset
              </a>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
      <div class="col-lg-3 col-md-6">
        <div class="card bg-info">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-0">Total Customers</h6>
                <h3 class="text-white font-weight-bold mb-0">{{ $totalCustomers }}</h3>
              </div>
              <i class="fas fa-users fa-3x text-white opacity-50"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card bg-success">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-0">Active</h6>
                <h3 class="text-white font-weight-bold mb-0">{{ $activeCustomers }}</h3>
              </div>
              <i class="fas fa-check-circle fa-3x text-white opacity-50"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card bg-danger">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-0">Blocked</h6>
                <h3 class="text-white font-weight-bold mb-0">{{ $blockCustomers }}</h3>
              </div>
              <i class="fas fa-ban fa-3x text-white opacity-50"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="card bg-warning">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-white mb-0">Inactive</h6>
                <h3 class="text-white font-weight-bold mb-0">{{ $inactiveCustomers }}</h3>
              </div>
              <i class="fas fa-pause-circle fa-3x text-white opacity-50"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
      <div class="col-lg-5">
        <div class="card">
          <div class="card-header bg-primary">
            <h5 class="card-title text-white mb-0">Customer Distribution by Status</h5>
          </div>
          <div class="card-body">
            <canvas id="statusChart" style="height: 300px;"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="card">
          <div class="card-header bg-success">
            <h5 class="card-title text-white mb-0">Monthly Customer Growth ({{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }})</h5>
          </div>
          <div class="card-body">
            <canvas id="growthChart" style="height: 300px;"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Revenue Charts Row -->
    <div class="row mb-4">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header bg-warning">
            <h5 class="card-title text-white mb-0">Monthly Revenue ({{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }})</h5>
          </div>
          <div class="card-body">
            <canvas id="revenueChart" style="height: 300px;"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card">
          <div class="card-header bg-info">
            <h5 class="card-title text-white mb-0">Revenue by Plan</h5>
          </div>
          <div class="card-body">
            <canvas id="planRevenueChart" style="height: 300px;"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Sales Info Card -->
    <div class="card card-info mb-3">
      <div class="card-header">
        <h3 class="card-title font-weight-bold"> Sales Information </h3>
      </div>
      
      <div class="card-body">
        <div class="table-bordered p-3 rounded-sm">
         <div class="row">
          <div class="form-group col-md-3">
            <label style="width: 25%;" for="nama" class="text-right">Name :</label>
            <a class="p-md-2">{{$sale->name}}</a>
          </div>
          <div class="form-group col-md-8">
            <label style="width: 10%;" for="full_name" class="text-right">Full Name :  </label>
            <a class="p-md-2">{{$sale->full_name}}</a>
          </div>
        </div>

        <div class="row">
          <div class="form-group col-md-3">
            <label style="width: 25%;" for="phone" class="text-right">Phone :</label>
            <a class="p-md-2">{{$sale->phone}}</a>
          </div>
          <div class="form-group col-md-8">
            <label style="width: 10%;" for="address" class="text-right">Address :</label>
            <a class="p-md-2">{{$sale->address}}</a>
          </div>
        </div>

        <div class="row">
          <div class="form-group col-md-3">
           <label style="width: 25%;" for="sale_type" class="text-right"> Sale Type : </label>
           <a class="p-md-2">{{$sale->sale_type}}</a>
         </div>
         <div class="form-group col-md-8">
          <label style="width: 10%;" for="description" class="text-right"> Note :</label>
          <a class="p-md-2">{{$sale->description}}</a>
        </div>
      </div>
    </div>
  </div>
</div>

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">{{$sale->name}}'s Customers List</h3>
    </div>

    <div class="row pt-2 pl-4">
     <div class="form-group col-md-2">
      <label for="site location">  Filter By </label>
      <div class="input-group mb-3">
        <select name="filter" id="filter" class="form-control">
          <option value="name">Name</option>
          <option value="customer_id">Customer ID</option>
          <option value="address">Address</option>
          <option value="phone">Phone</option>
          <option value="id_card">Id Card</option>
          <option value="billing_start">Billing Start</option>
        </select>
      </div>
    </div>
    
    <div class="form-group col-md-2">
      <label for="site location">  Parameter </label>
      <div class="input-group mb-3">
        <input class="form-control" type="text" id="parameter" name="parameter" placeholder="Leave blank for all">
      </div>
    </div>

    <div class="form-group col-md-2">
      <label for="site location">  Status </label>
      <div class="input-group mb-3">
        <select name="id_status" id="id_status" class="form-control">
         <option value="">All</option> 
         @foreach ($status as $id => $name)
         <option value="{{ $id }}">{{ $name }}</option>
         @endforeach
       </select>
     </div>
   </div>
   
   <div class="form-group col-md-2">
    <label for="site location">  Plan </label>
    <div class="input-group mb-3">
      <select name="id_plan" id="id_plan" class="form-control">
       <option value="">All</option> 
       @foreach ($plan as $id => $name)
       <option value="{{ $id }}">{{ $name }}</option>
       @endforeach
     </select>
   </div>
 </div>
 
 <input type="hidden" value="{{$sale->id}}" name="id_sale" id="id_sale">
 
 <div class="form-group col-md-2">
  <label for="site location">   </label>
  <div class="input-group p-1 col-md-3">
   <button type="button" id="sale_customer_filter" name="customer_filter" class="btn btn-warning">Filter</button>
 </div> 
</div>
</div>

<!-- /.card-header -->
<div class="card-body">
 <form role="form" method="post" action="/sale/customer/{{$sale->id}}">
   @method('patch')
   @csrf
   <table id="table-sale-customer" class="table table-bordered table-striped">
    <thead>
      <tr>
        <th scope="col">#</th>
        <th scope="col">Customer Id</th>
        <th scope="col">Name</th>
        <th scope="col">Address</th>
        <th scope="col">Plan</th>
        <th scope="col">Price</th>
        <th scope="col">Billing Start</th>
        <th scope="col">Status</th>
        <th scope="col">Invoice</th>
      </tr>
    </thead>
  </table>
</form>
</div>
</div>

</section>
</div>

@endsection

@section('footer-scripts')
<script>
$(document).ready(function() {
    // Destroy existing charts if they exist
    if (window.statusChart instanceof Chart) {
        window.statusChart.destroy();
    }
    if (window.revenueChart instanceof Chart) {
        window.revenueChart.destroy();
    }
    if (window.planRevenueChart instanceof Chart) {
        window.planRevenueChart.destroy();
    }
    if (window.growthChart instanceof Chart) {
        window.growthChart.destroy();
    }
    
    // Status Doughnut Chart
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    window.statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: {!! $statusLabels !!},
            datasets: [{
                data: {!! $statusData !!},
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    fontSize: 12
                }
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var label = data.labels[tooltipItem.index] || '';
                        if (label) {
                            label += ': ';
                        }
                        label += data.datasets[0].data[tooltipItem.index] + ' customers';
                        return label;
                    }
                }
            }
        }
    });

    // Monthly Revenue Chart
    var revenueCtx = document.getElementById('revenueChart').getContext('2d');
    window.revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: {!! $monthLabels !!},
            datasets: [
                {
                    label: 'New Revenue',
                    data: {!! $monthlyRevenueNew !!},
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2
                },
                {
                    label: 'Lost Revenue',
                    data: {!! $monthlyRevenueLost !!},
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    fontSize: 12
                }
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(tooltipItem, data) {
                        var label = data.datasets[tooltipItem.datasetIndex].label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += 'Rp ' + tooltipItem.yLabel.toLocaleString('id-ID');
                        return label;
                    }
                }
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }]
            }
        }
    });

    // Revenue by Plan Chart
    var planRevenueCtx = document.getElementById('planRevenueChart').getContext('2d');
    window.planRevenueChart = new Chart(planRevenueCtx, {
        type: 'doughnut',
        data: {
            labels: {!! $planLabels !!},
            datasets: [{
                data: {!! $planRevenue !!},
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(199, 199, 199, 0.8)',
                    'rgba(83, 102, 255, 0.8)',
                    'rgba(255, 99, 255, 0.8)',
                    'rgba(99, 255, 132, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(199, 199, 199, 1)',
                    'rgba(83, 102, 255, 1)',
                    'rgba(255, 99, 255, 1)',
                    'rgba(99, 255, 132, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom',
                labels: {
                    padding: 10,
                    fontSize: 11
                }
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var label = data.labels[tooltipItem.index] || '';
                        if (label) {
                            label += ': ';
                        }
                        label += 'Rp ' + data.datasets[0].data[tooltipItem.index].toLocaleString('id-ID');
                        return label;
                    }
                }
            }
        }
    });

    // Monthly Growth Line Chart
    var growthCtx = document.getElementById('growthChart').getContext('2d');
    window.growthChart = new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: {!! $monthLabels !!},
            datasets: [
                {
                    label: 'New Customers',
                    data: {!! $monthlyNew !!},
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    lineTension: 0.3
                },
                {
                    label: 'Lost Customers',
                    data: {!! $monthlyLost !!},
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: true,
                    lineTension: 0.3
                },
                {
                    label: 'Net Growth',
                    data: {!! $monthlyNet !!},
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true,
                    lineTension: 0.3,
                    borderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    fontSize: 12
                }
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(tooltipItem, data) {
                        var label = data.datasets[tooltipItem.datasetIndex].label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += tooltipItem.yLabel + ' customers';
                        return label;
                    }
                }
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1
                    }
                }]
            }
        }
    });
});
</script>
@endsection
