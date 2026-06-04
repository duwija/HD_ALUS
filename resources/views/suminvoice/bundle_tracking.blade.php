@extends('layout.main')
@section('title','Bundle Payment Tracking')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Bundle Payment Tracking</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ url('suminvoice/transaction') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Kembali ke Payment
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-md-3 col-6 mb-2">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h4>{{ number_format($stats['total_bundles'] ?? 0) }}</h4>
                        <p>Total Bundle</p>
                    </div>
                    <div class="icon"><i class="fas fa-layer-group"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h4>{{ number_format($stats['paid_count'] ?? 0) }}</h4>
                        <p>Paid</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h4>{{ number_format($stats['pending_count'] ?? 0) }}</h4>
                        <p>Pending</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h4>{{ number_format($stats['expired_count'] ?? 0) }}</h4>
                        <p>Expired / Canceled</p>
                    </div>
                    <div class="icon"><i class="fas fa-ban"></i></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Filter Tracking</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ url('suminvoice/bundle-tracking') }}" id="bundleTrackingFilterForm">
                    <div class="row">
                        <div class="col-md-2 mb-2">
                            <label>Gateway</label>
                            <select name="gateway" id="filter_gateway" class="form-control form-control-sm">
                                <option value="">Semua</option>
                                <option value="winpay" {{ ($filters['gateway'] ?? '') === 'winpay' ? 'selected' : '' }}>Winpay</option>
                                <option value="winpay2" {{ ($filters['gateway'] ?? '') === 'winpay2' ? 'selected' : '' }}>Winpay2</option>
                                <option value="duitku" {{ ($filters['gateway'] ?? '') === 'duitku' ? 'selected' : '' }}>Duitku</option>
                                <option value="duitku2" {{ ($filters['gateway'] ?? '') === 'duitku2' ? 'selected' : '' }}>Duitku2</option>
                                <option value="tripay" {{ ($filters['gateway'] ?? '') === 'tripay' ? 'selected' : '' }}>Tripay</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>Status</label>
                            <select name="status" id="filter_status" class="form-control form-control-sm">
                                <option value="">Semua</option>
                                <option value="0" {{ (string)($filters['status'] ?? '') === '0' ? 'selected' : '' }}>Pending</option>
                                <option value="1" {{ (string)($filters['status'] ?? '') === '1' ? 'selected' : '' }}>Paid</option>
                                <option value="2" {{ (string)($filters['status'] ?? '') === '2' ? 'selected' : '' }}>Expired/Canceled</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>Dari Tanggal</label>
                            <input type="date" name="date_from" id="filter_date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>Sampai Tanggal</label>
                            <input type="date" name="date_end" id="filter_date_end" class="form-control form-control-sm" value="{{ $filters['date_end'] ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Cari (Bundle Ref / CID / Nama / No Invoice)</label>
                            <input type="text" name="search" id="filter_search" class="form-control form-control-sm" value="{{ $filters['search'] ?? '' }}" placeholder="Contoh: MULTI-, CID, nama, no invoice">
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="button" id="btnApplyFilter" class="btn btn-primary btn-sm">
                            <i class="fas fa-search mr-1"></i>Terapkan Filter
                        </button>
                        <a href="{{ url('suminvoice/bundle-tracking') }}" class="btn btn-default btn-sm">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Daftar Bundle Payment</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="bundleTrackingTable" class="table table-striped table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Bundle Ref</th>
                                <th>Gateway</th>
                                <th>Customer</th>
                                <th>Invoice</th>
                                <th>Status</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Paid</th>
                                <th>Payment URL</th>
                                <th>Dibuat</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
$(function () {
    var table = $('#bundleTrackingTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        autoWidth: false,
        pageLength: 25,
        ajax: {
            url: '{{ url('/suminvoice/bundle-tracking/table') }}',
            type: 'POST',
            data: function (d) {
                d._token = '{{ csrf_token() }}';
                d.gateway = $('#filter_gateway').val();
                d.status = $('#filter_status').val();
                d.date_from = $('#filter_date_from').val();
                d.date_end = $('#filter_date_end').val();
                d.search_filter = $('#filter_search').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'bundle_ref', name: 'bundle_ref' },
            { data: 'gateway', name: 'gateway' },
            { data: 'customer_info', name: 'customer_info', orderable: false, searchable: false },
            { data: 'invoice_info', name: 'invoice_info', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
            { data: 'total_amount', name: 'total_amount', className: 'text-right' },
            { data: 'paid_amount', name: 'paid_amount', className: 'text-right' },
            { data: 'payment_url_action', name: 'payment_url_action', orderable: false, searchable: false },
            { data: 'created_info', name: 'created_info', orderable: false, searchable: false }
        ]
    });

    $('#btnApplyFilter').on('click', function () {
        table.ajax.reload();
    });

    $('#bundleTrackingFilterForm').on('submit', function (e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#filter_search').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            table.ajax.reload();
        }
    });
});
</script>
@endsection
