
@extends('layout.main')
@section('title','Customer On Trash List')
@section('content')
@inject('suminvoice', 'App\Suminvoice')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">TRASH </h3>
    </div>

    <!-- Chart Section -->
    <div class="card-body py-2">
      <div class="row mb-2">
        <div class="col-md-8">
          <div class="card card-info mb-0">
            <div class="card-header py-1">
              <h3 class="card-title" style="font-size:0.85rem;">
                <i class="fas fa-chart-line"></i> Deleted Customers Trend (Last 30 Days)
              </h3>
              <div class="card-tools">
                <span class="badge badge-danger">Total: {{ $totalDeletedCustomers }}</span>
              </div>
            </div>
            <div class="card-body p-2">
              <canvas id="deletedCustomersChart" height="55"></canvas>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-warning mb-0">
            <div class="card-header py-1">
              <h3 class="card-title" style="font-size:0.85rem;">
                <i class="fas fa-chart-pie"></i> By Plan
              </h3>
            </div>
            <div class="card-body p-2">
              <canvas id="planChart" height="115"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="form-row mb-4 p-3">
      <div class="form-group col-md-2">
        <label for="filter">Filter By</label>
        <select name="filter" id="filter" class="form-control">
          <option value="name">Name</option>
          <option value="customer_id">Customer ID</option>
          <option value="address">Address</option>
          <option value="phone">Phone</option>
          <option value="id_card">ID Card</option>
          <option value="billing_start">Billing Start</option>
          <option value="deleted_at">Deleted Date</option>
        </select>
      </div>

      <div class="form-group col-md-2">
        <label for="parameter">Parameter</label>
        <input
          type="text"
          id="parameter"
          name="parameter"
          class="form-control"
          placeholder="Leave blank for all"
        >
      </div>

      <div class="form-group col-md-2">
        <label for="id_merchant">Merchant</label>
        <select name="id_merchant" id="id_merchant" class="form-control">
          <option value="">All</option>
          @foreach ($merchant as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group col-md-2">
        <label for="id_status">Status</label>
        <select name="id_status" id="id_status" class="form-control">
          <option value="">All</option>
          @foreach ($status as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group col-md-2">
        <label for="id_plan">Plan</label>
        <select name="id_plan" id="id_plan" class="form-control">
          <option value="">All</option>
          @foreach ($plan as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group col-md-1">
        <label for="id_tag">Tag</label>
        <select name="id_tag[]" id="id_tag" class="form-control select2" multiple data-placeholder="Semua Tag">
          @foreach ($tags as $id => $name)
          <option value="{{ $id }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group col-md-1 d-flex align-items-end">
        <button
          type="button"
          id="trash_filter"
          class="btn btn-warning btn-block"
        >
          Filter
        </button>
      </div>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      
      @if($customers->count() > 0)
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> Found {{ $customers->count() }} deleted customer(s)
        </div>
      @else
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle"></i> No deleted customers found
        </div>
      @endif
      
      <table id="example" class="table table-bordered table-striped">

        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Customer Id</th>
            <th scope="col">Name</th>
            <th scope="col">Phone</th>
            <th scope="col">Address</th>
            <th scope="col">Merchant</th>
            <th scope="col">Plan</th>
            <th scope="col">Status</th>
            <th scope="col">Deleted At</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
         @foreach( $customers as $cust)
         <tr data-merchant-id="{{ $cust->id_merchant ?? '' }}"
             data-status-id="{{ $cust->id_status ?? '' }}"
             data-plan-id="{{ $cust->id_plan ?? '' }}"
             data-tag-ids="{{ $cust->tags->pluck('id')->implode(',') }}"
             data-customer-id="{{ $cust->customer_id }}"
             data-name="{{ strtolower($cust->name) }}"
             data-address="{{ strtolower($cust->address) }}"
             data-phone="{{ $cust->phone ?? '' }}"
             data-id-card="{{ $cust->id_card ?? '' }}">
          <td>{{ $loop->iteration }}</td>
          <td><span class="badge badge-secondary">{{ $cust->customer_id }}</span></td>
          <td>{{ $cust->name }}</td>
          <td><small class="text-muted">{{ $cust->phone ?? '-' }}</small></td>
          <td><small class="text-muted">{{ $cust->address }}</small></td>
          <td>
            @if($cust->id_merchant)
              {{ $cust->merchant_name->name }}
            @else
              <span class="badge badge-light">No Merchant</span>
            @endif
          </td>
          <td>
            @if($cust->id_plan)
              {{ $cust->plan_name->name }} <small class="text-muted">(Rp {{ number_format($cust->plan_name->price)}})</small>
            @else
              <span class="badge badge-light">No Plan</span>
            @endif
          </td>
          <td>
            @if($cust->id_status)
              @php
                $badge_sts = "badge-secondary";
                if ($cust->status_name->name == 'Active') $badge_sts = "badge-success";
                elseif ($cust->status_name->name == 'Inactive') $badge_sts = "badge-secondary";
                elseif ($cust->status_name->name == 'Block') $badge_sts = "badge-danger";
                elseif ($cust->status_name->name == 'Company_Properti') $badge_sts = "badge-primary";
              @endphp
              <span class="badge {{$badge_sts}}">{{ $cust->status_name->name }}</span>
            @else
              <span class="badge badge-light">No Status</span>
            @endif
          </td>
          <td class="text-center">
            <small class="text-danger">
              <i class="far fa-calendar-times"></i> {{ $cust->deleted_at->format('d M Y') }}<br>
              <i class="far fa-clock"></i> {{ $cust->deleted_at->format('H:i:s') }}
            </small>
          </td>
          <td>
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-info btn-sm view-detail" 
                      data-id="{{ $cust->id }}"
                      data-customer-id="{{ $cust->customer_id }}"
                      data-name="{{ $cust->name }}"
                      data-email="{{ $cust->email ?? '-' }}"
                      data-phone="{{ $cust->phone ?? '-' }}"
                      data-address="{{ $cust->address }}"
                      data-coordinate="{{ $cust->coordinate ?? '' }}"
                      data-merchant="{{ $cust->id_merchant ? $cust->merchant_name->name : 'No Merchant' }}"
                      data-status="{{ $cust->id_status ? $cust->status_name->name : 'No Status' }}"
                      data-plan="{{ $cust->id_plan ? $cust->plan_name->name : 'No Plan' }}"
                      data-price="{{ $cust->id_plan ? number_format($cust->plan_name->price) : '0' }}"
                      data-deleted="{{ $cust->deleted_at->format('d M Y H:i:s') }}"
                      data-updated-by="{{ $cust->updated_by ?? '-' }}"
                      data-toggle="modal" 
                      data-target="#detailModal"
                      title="View Detail">
                <i class="fas fa-eye"></i>
              </button>
              
              <form action="/customer/restore/{{$cust->id}}" method="POST" class="d-inline item-restore">
                @method('patch')
                @csrf
                <button title="Restore Customer" type="submit" class="btn btn-warning btn-sm">
                  <i class="fas fa-undo"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
        </tbody>
      </table>
  </div>
</div>

</section>

<!-- Modal Detail Customer -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info">
        <h5 class="modal-title" id="detailModalLabel">
          <i class="fas fa-user"></i> Customer Detail
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <table class="table table-sm table-borderless">
              <tr>
                <th width="40%">Customer ID:</th>
                <td><span class="badge badge-secondary" id="modal-customer-id"></span></td>
              </tr>
              <tr>
                <th>Name:</th>
                <td id="modal-name" class="font-weight-bold"></td>
              </tr>
              <tr>
                <th>Email:</th>
                <td id="modal-email"></td>
              </tr>
              <tr>
                <th>Phone:</th>
                <td id="modal-phone"></td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-sm table-borderless">
              <tr>
                <th width="40%">Plan:</th>
                <td id="modal-plan" class="font-weight-bold"></td>
              </tr>
              <tr>
                <th>Price:</th>
                <td>Rp <span id="modal-price"></span></td>
              </tr>
              <tr>
                <th>Merchant:</th>
                <td id="modal-merchant" class="font-weight-bold"></td>
              </tr>
              <tr>
                <th>Status:</th>
                <td><span id="modal-status" class="badge"></span></td>
              </tr>
              <tr>
                <th>Deleted At:</th>
                <td class="text-danger" id="modal-deleted"></td>
              </tr>
              <tr>
                <th>Updated By:</th>
                <td id="modal-updated-by"></td>
              </tr>
            </table>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-12">
            <h6 class="font-weight-bold">Address:</h6>
            <p id="modal-address" class="text-muted"></p>
          </div>
        </div>
        <div class="row" id="map-section" style="display:none;">
          <div class="col-12">
            <h6 class="font-weight-bold">Location:</h6>
            <a id="modal-map-link" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
              <i class="fas fa-map-marked-alt"></i> View on Google Maps
            </a>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('footer-scripts')
<script>
$(document).ready(function() {
  // Render Deleted Customers Chart
  var deletedCtx = document.getElementById('deletedCustomersChart').getContext('2d');
  var deletedData = @json($dailyDeletedCustomers);
  
  var labels = [];
  var data = [];
  
  // Generate all dates for last 30 days
  var endDate = new Date();
  var startDate = new Date();
  startDate.setDate(startDate.getDate() - 30);
  
  for (var d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
    var dateStr = d.getFullYear() + '-' + 
                  String(d.getMonth() + 1).padStart(2, '0') + '-' + 
                  String(d.getDate()).padStart(2, '0');
    labels.push(dateStr);
    
    // Find count for this date
    var found = deletedData.find(function(item) {
      return item.date === dateStr;
    });
    
    data.push(found ? found.count : 0);
  }
  
  var deletedChart = new Chart(deletedCtx, {
    type: 'line',
    data: {
      labels: labels.map(function(date) {
        var parts = date.split('-');
        return parts[2] + '/' + parts[1];
      }),
      datasets: [{
        label: 'Deleted Customers',
        data: data,
        borderColor: 'rgb(239, 68, 68)',
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: true,
          position: 'top'
        },
        tooltip: {
          mode: 'index',
          intersect: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        },
        x: {
          ticks: {
            maxRotation: 45,
            minRotation: 45
          }
        }
      }
    }
  });

  // Render Plan Chart
  var planCtx = document.getElementById('planChart').getContext('2d');
  var planChart = new Chart(planCtx, {
    type: 'doughnut',
    data: {
      labels: @json($planLabels),
      datasets: [{
        data: @json($planData),
        backgroundColor: [
          'rgba(54,162,235,0.8)','rgba(255,99,132,0.8)','rgba(255,206,86,0.8)',
          'rgba(75,192,192,0.8)','rgba(153,102,255,0.8)','rgba(255,159,64,0.8)',
          'rgba(199,199,199,0.8)','rgba(83,102,255,0.8)','rgba(255,99,255,0.8)',
          'rgba(99,255,132,0.8)'
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: true, position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
        tooltip: { callbacks: { label: function(ctx) { return ctx.label + ': ' + ctx.parsed; } } }
      }
    }
  });

  // Destroy DataTable yang sudah diinisialisasi oleh layout (tapi simpan HTML-nya)
  if ($.fn.DataTable.isDataTable('#example1')) {
    $('#example').DataTable().destroy();
    // Jangan clear HTML, biarkan tbody tetap ada
  }
  
  // DataTable initialization - simple without custom filters
  var table = $('#example').DataTable({
    "responsive": true,
    "lengthChange": true,
    "autoWidth": false,
    "buttons": ["copy", "csv", "excel", "pdf", "print"],
    "pageLength": 25,
    "order": [[8, 'desc']] // Deleted at column (index 8 sekarang karena ada phone)
  });
  
  table.buttons().container().appendTo('#example_wrapper .col-md-6:eq(0)');

  // Filter button click
  $('#trash_filter').on('click', function() {
    applyFilters();
  });

  // Auto filter on dropdown change
  $('#id_merchant, #id_status, #id_plan, #id_tag').on('change', function() {
    applyFilters();
  });

  // Enter key on parameter input
  $('#parameter').on('keypress', function(e) {
    if (e.which == 13) {
      e.preventDefault();
      applyFilters();
    }
  });

  function applyFilters() {
    var filter = $('#filter').val();
    var parameter = $('#parameter').val().toLowerCase();
    var id_merchant = $('#id_merchant').val();
    var id_status = $('#id_status').val();
    var id_plan = $('#id_plan').val();
    var id_tags = $('#id_tag').val() || [];

    // Show all rows first
    $('#example tbody tr').show();

    // Apply filters
    $('#example tbody tr').each(function() {
      var $row = $(this);
      var show = true;

      // Filter by parameter based on selected filter field
      if (parameter !== '') {
        var filterValue = '';
        if (filter === 'name') {
          filterValue = $row.data('name');
        } else if (filter === 'customer_id') {
          filterValue = $row.data('customer-id').toString().toLowerCase();
        } else if (filter === 'address') {
          filterValue = $row.data('address');
        } else if (filter === 'phone') {
          filterValue = $row.data('phone').toString().toLowerCase();
        } else if (filter === 'id_card') {
          filterValue = $row.data('id-card').toString().toLowerCase();
        }
        
        if (filterValue.indexOf(parameter) === -1) {
          show = false;
        }
      }

      // Filter by merchant
      if (show && id_merchant !== '') {
        var merchantId = $row.data('merchant-id').toString();
        if (merchantId !== id_merchant) {
          show = false;
        }
      }

      // Filter by status
      if (show && id_status !== '') {
        var statusId = $row.data('status-id').toString();
        if (statusId !== id_status) {
          show = false;
        }
      }

      // Filter by plan
      if (show && id_plan !== '') {
        var planId = $row.data('plan-id').toString();
        if (planId !== id_plan) {
          show = false;
        }
      }

      // Filter by tag (AND: customer must have ALL selected tags)
      if (show && id_tags.length > 0) {
        var tagIds = ($row.data('tag-ids') || '').toString();
        var tagArr = tagIds ? tagIds.split(',').map(function(t) { return t.trim(); }) : [];
        var hasAll = id_tags.every(function(tid) {
          return tagArr.indexOf(String(tid)) !== -1;
        });
        if (!hasAll) show = false;
      }

      if (!show) {
        $row.hide();
      }
    });

    // Redraw table
    table.draw(false);
  }

  // View detail modal
  $(document).on('click', '.view-detail', function() {
    var customerId = $(this).data('customer-id');
    var name = $(this).data('name');
    var email = $(this).data('email');
    var phone = $(this).data('phone');
    var address = $(this).data('address');
    var coordinate = $(this).data('coordinate');
    var merchant = $(this).data('merchant');
    var status = $(this).data('status');
    var plan = $(this).data('plan');
    var price = $(this).data('price');
    var deleted = $(this).data('deleted');
    var updatedBy = $(this).data('updated-by');

    $('#modal-customer-id').text(customerId);
    $('#modal-name').text(name);
    $('#modal-email').text(email || '-');
    $('#modal-phone').text(phone || '-');
    $('#modal-merchant').text(merchant);
    $('#modal-status').text(status).removeClass().addClass('badge badge-secondary');
    $('#modal-plan').text(plan);
    $('#modal-price').text(price);
    $('#modal-deleted').text(deleted);
    $('#modal-updated-by').text(updatedBy || '-');
    $('#modal-address').text(address || '-');

    // Show map section if coordinate exists
    if (coordinate) {
      $('#map-section').show();
      $('#modal-map-link').attr('href', 'https://www.google.com/maps/place/' + coordinate);
    } else {
      $('#map-section').hide();
    }
  });

  // Restore confirmation
  $(document).on('submit', '.item-restore', function(e) {
    e.preventDefault();
    var form = this;
    Swal.fire({
      title: 'Restore Customer?',
      text: "Customer will be restored to active list",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, restore it!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  });
});
</script>
@endsection
 