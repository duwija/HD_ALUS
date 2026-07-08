
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
              <div style="height: 220px; position: relative;">
                <canvas id="deletedCustomersChart"></canvas>
              </div>
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
        <label for="deletion_type_filter">Status Berhenti</label>
        <select name="deletion_type_filter" id="deletion_type_filter" class="form-control">
          <option value="">All</option>
          <option value="terminate">Berhenti Berlangganan</option>
          <option value="cancel">Tidak Jadi Berlangganan</option>
        </select>
      </div>

      <div class="form-group col-md-2">
        <label for="start_date">Deleted Dari Tanggal</label>
        <input type="date" id="start_date" name="start_date" class="form-control">
      </div>

      <div class="form-group col-md-2">
        <label for="end_date">Deleted Sampai Tanggal</label>
        <input type="date" id="end_date" name="end_date" class="form-control">
      </div>

      <div class="form-group col-md-2">
        <label for="id_plan">Plan</label>
        <select name="id_plan" id="id_plan" class="form-control select2" data-placeholder="Cari plan...">
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
      
      @if($deletedCustomersCount > 0)
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> Found {{ $deletedCustomersCount }} deleted customer(s)
        </div>
      @else
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle"></i> No deleted customers found
        </div>
      @endif
      
      <div class="table-responsive">
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
            <th scope="col">Status Berhenti</th>
            <th scope="col">Alasan Hapus</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      </div>
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
                <th>Status Berhenti:</th>
                <td id="modal-deletion-type"></td>
              </tr>
              <tr>
                <th>Alasan Hapus:</th>
                <td id="modal-deletion-reason"></td>
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
  var deletedByTypeData = @json($dailyDeletedByType);
  
  var labels = [];
  var data = [];
  var berhentiData = [];
  var tidakJadiData = [];
  
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

    var berhentiFound = deletedByTypeData.find(function(item) {
      return item.date === dateStr && item.deletion_type === 'terminate';
    });
    berhentiData.push(berhentiFound ? berhentiFound.count : 0);

    var tidakJadiFound = deletedByTypeData.find(function(item) {
      return item.date === dateStr && item.deletion_type === 'cancel';
    });
    tidakJadiData.push(tidakJadiFound ? tidakJadiFound.count : 0);
  }
  
  var deletedChart = new Chart(deletedCtx, {
    type: 'line',
    data: {
      labels: labels.map(function(date) {
        var parts = date.split('-');
        return parts[2] + '/' + parts[1];
      }),
      datasets: [{
        label: 'Total Delete',
        data: data,
        borderColor: 'rgb(239, 68, 68)',
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        tension: 0.35,
        fill: false
      }, {
        label: 'Berhenti Berlangganan',
        data: berhentiData,
        borderColor: 'rgb(22, 163, 74)',
        backgroundColor: 'rgba(22, 163, 74, 0.1)',
        tension: 0.35,
        fill: false
      }, {
        label: 'Tidak Jadi Berlangganan',
        data: tidakJadiData,
        borderColor: 'rgb(245, 158, 11)',
        backgroundColor: 'rgba(245, 158, 11, 0.1)',
        tension: 0.35,
        fill: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
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
      responsive: false,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: true, position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
        tooltip: { callbacks: { label: function(ctx) { return ctx.label + ': ' + ctx.parsed; } } }
      }
    }
  });

  // Destroy DataTable auto-init from layout before re-init custom server-side table
  if ($.fn.DataTable.isDataTable('#example')) {
    $('#example').DataTable().destroy();
  }

  var table = $('#example').DataTable({
    responsive: false,
    lengthChange: true,
    autoWidth: false,
    buttons: ["copy", "csv", "excel", "pdf", "print"],
    pageLength: 25,
    processing: true,
    serverSide: true,
    order: [[8, 'desc']],
    ajax: {
      url: '{{ route('trash.data') }}',
      data: function(d) {
        d.filter = $('#filter').val();
        d.parameter = $('#parameter').val();
        d.id_merchant = $('#id_merchant').val();
        d.id_status = $('#id_status').val();
        d.deletion_type = $('#deletion_type_filter').val();
        d.start_date = $('#start_date').val();
        d.end_date = $('#end_date').val();
        d.id_plan = $('#id_plan').val();
        d.id_tag = $('#id_tag').val() || [];
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'customer_id', name: 'customer_id' },
      { data: 'name', name: 'name' },
      { data: 'phone', name: 'phone', orderable: false },
      { data: 'address', name: 'address', orderable: false },
      { data: 'merchant', name: 'merchant', orderable: false, searchable: false },
      { data: 'plan', name: 'plan', orderable: false, searchable: false },
      { data: 'status', name: 'status', orderable: false, searchable: false },
      { data: 'deleted_at', name: 'deleted_at' },
      { data: 'deletion_type', name: 'deletion_type', orderable: false, searchable: false },
      { data: 'deletion_reason', name: 'deletion_reason', orderable: false, searchable: false },
      { data: 'action', name: 'action', orderable: false, searchable: false }
    ]
  });
  
  table.buttons().container().appendTo('#example_wrapper .col-md-6:eq(0)');

  if ($.fn.select2) {
    $('#id_plan').select2({
      width: '100%',
      allowClear: true,
      placeholder: 'Cari plan...'
    });

    $('#id_tag').select2({
      width: '100%',
      placeholder: 'Semua Tag'
    });
  }

  function applyFilters() {
    table.ajax.reload();
  }

  $('#trash_filter').on('click', applyFilters);
  $('#id_merchant, #id_status, #deletion_type_filter, #id_plan, #id_tag, #start_date, #end_date').on('change', applyFilters);
  $('#parameter').on('keypress', function(e) {
    if (e.which === 13) {
      e.preventDefault();
      applyFilters();
    }
  });

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
    var deletionType = $(this).data('deletion-type');
    var deletionReason = $(this).data('deletion-reason');
    var deleted = $(this).data('deleted');
    var updatedBy = $(this).data('updated-by');

    var deletionTypeLabel = '-';
    if (deletionType === 'terminate') {
      deletionTypeLabel = 'Berhenti Berlangganan';
    } else if (deletionType === 'cancel') {
      deletionTypeLabel = 'Tidak Jadi Berlangganan';
    }

    $('#modal-customer-id').text(customerId);
    $('#modal-name').text(name);
    $('#modal-email').text(email || '-');
    $('#modal-phone').text(phone || '-');
    $('#modal-merchant').text(merchant);
    $('#modal-status').text(status).removeClass().addClass('badge badge-secondary');
    $('#modal-plan').text(plan);
    $('#modal-price').text(price);
    $('#modal-deleted').text(deleted);
    $('#modal-deletion-type').text(deletionTypeLabel);
    $('#modal-deletion-reason').text(deletionReason || '-');
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
 