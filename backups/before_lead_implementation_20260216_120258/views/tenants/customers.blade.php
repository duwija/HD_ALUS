@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-users"></i> Customers: {{ $tenant->app_name }}
                    </h3>
                    <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <label for="filter">Filter By</label>
                            <select name="filter" id="filter" class="form-control">
                                <option value="name">Name</option>
                                <option value="customer_id">Customer ID</option>
                                <option value="address">Address</option>
                                <option value="phone">Phone</option>
                                <option value="id_card">ID Card</option>
                                <option value="billing_start">Billing Start</option>
                                <option value="isolir_date">Isolir Date</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="parameter">Parameter</label>
                            <input type="text" id="parameter" name="parameter" class="form-control" placeholder="Leave blank for all">
                        </div>

                        <div class="col-md-2">
                            <label for="id_status">Status</label>
                            <select name="id_status" id="id_status" class="form-control">
                                <option value="">All</option>
                                @foreach ($statuses as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="id_plan">Plan</label>
                            <select name="id_plan" id="id_plan" class="form-control">
                                <option value="">All</option>
                                @foreach ($plans as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="id_merchant">Merchant</label>
                            <select name="id_merchant" id="id_merchant" class="form-control">
                                <option value="">All</option>
                                @foreach ($merchants as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" id="customer_filter" class="btn btn-warning btn-block">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <div class="row text-center">
                                    <div class="col-md-2">
                                        <strong>Total:</strong> <span id="stat-total" class="badge badge-primary">0</span>
                                    </div>
                                    <div class="col-md-2">
                                        <strong>Potential:</strong> <span id="stat-potential" class="badge badge-info">0</span>
                                    </div>
                                    <div class="col-md-2">
                                        <strong>Active:</strong> <span id="stat-active" class="badge badge-success">0</span>
                                    </div>
                                    <div class="col-md-2">
                                        <strong>Inactive:</strong> <span id="stat-inactive" class="badge badge-secondary">0</span>
                                    </div>
                                    <div class="col-md-2">
                                        <strong>Block:</strong> <span id="stat-block" class="badge badge-danger">0</span>
                                    </div>
                                    <div class="col-md-2">
                                        <strong>Company:</strong> <span id="stat-company" class="badge badge-warning">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Table -->
                    <div class="table-responsive">
                        <table id="table-customer" class="table table-bordered table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Customer ID</th>
                                    <th>Name</th>
                                    <th>Address</th>
                                    <th>Merchant</th>
                                    <th>Plan</th>
                                    <th>Billing Start</th>
                                    <th>Isolir Date</th>
                                    <th>Status</th>
                                    <th>Invoice</th>
                                    <th>Notif</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#table-customer').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.tenants.customers.data', $tenant->id) }}',
            data: function(d) {
                d.filter = $('#filter').val();
                d.parameter = $('#parameter').val();
                d.id_status = $('#id_status').val();
                d.id_plan = $('#id_plan').val();
                d.id_merchant = $('#id_merchant').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'customer_id', name: 'customer_id' },
            { data: 'name', name: 'name' },
            { data: 'address', name: 'address' },
            { data: 'merchant', name: 'merchant', orderable: false },
            { data: 'plan', name: 'plan', orderable: false },
            { data: 'billing_start', name: 'billing_start' },
            { data: 'isolir_date', name: 'isolir_date' },
            { data: 'status_cust', name: 'status_cust', orderable: false },
            { data: 'invoice', name: 'invoice', orderable: false },
            { data: 'notification', name: 'notification', orderable: false }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        drawCallback: function(settings) {
            var api = this.api();
            var json = api.ajax.json();
            
            // Update statistics
            if (json && json.stats) {
                $('#stat-total').text(json.stats.total || 0);
                $('#stat-potential').text(json.stats.potential || 0);
                $('#stat-active').text(json.stats.active || 0);
                $('#stat-inactive').text(json.stats.inactive || 0);
                $('#stat-block').text(json.stats.block || 0);
                $('#stat-company').text(json.stats.company_properti || 0);
            }
        }
    });

    // Filter button click
    $('#customer_filter').click(function() {
        table.ajax.reload();
    });

    // Enter key on parameter field
    $('#parameter').keypress(function(e) {
        if (e.which == 13) {
            table.ajax.reload();
        }
    });
});
</script>
@endsection
