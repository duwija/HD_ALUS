@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-money-bill-wave"></i> Transactions: {{ $tenant->app_name }}
                    </h3>
                    <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card-body">
                    <!-- Statistics Section -->
                    <div class="row mb-4">
                        <!-- Info Boxes -->
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-12 mb-3">
                                    <div class="small-box bg-navy">
                                        <div class="inner">
                                            <h4>Rp. {{ number_format($totalReceivable,0,',','.') }}</h4>
                                            <p>Total Receivable</p>
                                        </div>
                                        <div class="icon"><i class="fas fa-wallet"></i></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-12 mb-3">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h4>Rp. {{ number_format($totalTransactionThisMonth,0,',','.') }}</h4>
                                            <p>This Month</p>
                                        </div>
                                        <div class="icon"><i class="fas fa-university"></i></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-12 mb-3">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h4>Rp. {{ number_format($totalTransactionThisWeek,0,',','.') }}</h4>
                                            <p>This Week</p>
                                        </div>
                                        <div class="icon"><i class="fas fa-chart-line"></i></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-12 mb-3">
                                    <div class="small-box bg-pink">
                                        <div class="inner">
                                            <h4>Rp. {{ number_format($totalPaymentToday,0,',','.') }}</h4>
                                            <p>Today</p>
                                        </div>
                                        <div class="icon"><i class="fas fa-chart-bar"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart -->
                        <div class="col-lg-6">
                            <div class="card mb-0" style="height: 100%;">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0"><i class="fas fa-chart-line"></i> Daily Transaction Chart</h5>
                                </div>
                                <div class="card-body" style="height: 300px;">
                                    <canvas id="dailyTransactionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-filter"></i> Filters</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <label class="small mb-1">Transaction Start:</label>
                                    <div class="input-group input-group-sm date" id="dateStartPicker" data-target-input="nearest">
                                        <input type="text" name="dateStart" class="form-control datetimepicker-input" data-target="#dateStartPicker" value="{{ date('Y-m-01') }}" />
                                        <div class="input-group-append" data-target="#dateStartPicker" data-toggle="datetimepicker">
                                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-2">
                                    <label class="small mb-1">Transaction End:</label>
                                    <div class="input-group input-group-sm date" id="dateEndPicker" data-target-input="nearest">
                                        <input type="text" name="dateEnd" class="form-control datetimepicker-input" data-target="#dateEndPicker" value="{{ date('Y-m-d') }}" />
                                        <div class="input-group-append" data-target="#dateEndPicker" data-toggle="datetimepicker">
                                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-2">
                                    <label class="small mb-1">Merchant:</label>
                                    <select name="id_merchant" id="id_merchant" class="form-control form-control-sm select2">
                                        <option value="">All</option>
                                        @foreach ($merchant as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 mb-2">
                                    <label class="small mb-1">Parameter:</label>
                                    <input placeholder="INV | CID | Name" type="text" name="parameter" id="parameter" class="form-control form-control-sm" />
                                </div>

                                <div class="col-md-3 mb-2">
                                    <label class="small mb-1">Receive By:</label>
                                    <select name="updatedBy" id="updatedBy" class="form-control form-control-sm select2">
                                        <option value="">ALL</option>
                                        @foreach($user as $transaction)
                                            @if(is_numeric($transaction->updated_by))
                                                <option value="{{$transaction->updated_by}}">{{ $transaction->updated_by }}</option>
                                            @else
                                                <option value="{{$transaction->updated_by}}">{{ $transaction->updated_by }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 mb-2">
                                    <label class="small mb-1">Kas Bank:</label>
                                    <select name="kasbank" id="kasbank" class="form-control form-control-sm select2">
                                        <option value="">All</option>
                                        @foreach ($kasbank as $akun)
                                        <option value="{{ $akun->akun_code }}">{{ $akun->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-warning btn-sm" id="transaction_filter">
                                        <i class="fas fa-filter"></i> Apply Filter
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm ml-2" id="reset_filter">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grouped Tables -->
                    <div class="row mb-4">
                        <div class="col-lg-4 mb-3">
                            <div class="card card-primary card-outline">
                                <div class="card-header">
                                    <h3 class="card-title"><strong>Payments by Recipient</strong></h3>
                                </div>
                                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-bordered table-striped table-sm mb-0 text-nowrap">
                                        <thead class="bg-light sticky-top">
                                            <tr>
                                                <th style="width: 40px;">No</th>
                                                <th>Received By</th>
                                                <th class="text-right" style="width: 120px;">Payment</th>
                                            </tr>
                                        </thead>
                                        <tbody name='groupedTransactionsUser' id='groupedTransactionsUser'>
                                        </tbody>
                                        <tfoot class="bg-light font-weight-bold">
                                            <tr>
                                                <th colspan="2">Total</th>
                                                <th name="totalPayment" id="totalPayment" class="text-right">0</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <div class="card card-warning card-outline">
                                <div class="card-header">
                                    <h3 class="card-title"><strong>Payments by Kasbank</strong></h3>
                                </div>
                                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-bordered table-striped table-sm mb-0 text-nowrap">
                                        <thead class="bg-light sticky-top">
                                            <tr>
                                                <th style="width: 40px;">No</th>
                                                <th>Kas Bank</th>
                                                <th class="text-right" style="width: 120px;">Payment</th>
                                            </tr>
                                        </thead>
                                        <tbody name='groupedTransactionsKasbank' id='groupedTransactionsKasbank'>
                                        </tbody>
                                        <tfoot class="bg-light font-weight-bold">
                                            <tr>
                                                <th colspan="2">Total</th>
                                                <th name="totalPaymentKasbank" id="totalPaymentKasbank" class="text-right">0</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <div class="card card-info card-outline">
                                <div class="card-header">
                                    <h3 class="card-title"><strong>Payments by Merchant</strong></h3>
                                </div>
                                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-bordered table-striped table-sm mb-0 text-nowrap">
                                        <thead class="bg-light sticky-top">
                                            <tr>
                                                <th style="width: 40px;">No</th>
                                                <th>Merchant</th>
                                                <th class="text-right" style="width: 120px;">Payment</th>
                                            </tr>
                                        </thead>
                                        <tbody name='groupedTransactionsMerchant' id='groupedTransactionsMerchant'>
                                        </tbody>
                                        <tfoot class="bg-light font-weight-bold">
                                            <tr>
                                                <th colspan="2">Total</th>
                                                <th name="totalPaymentMerchant" id="totalPaymentMerchant" class="text-right">0</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Transaction Table -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="float-right">
                                        <span class="badge badge-success badge-lg px-3 py-2 mr-2">
                                            <div><small>Total Amount</small></div>
                                            <div><strong>Rp. <span id='total_paid'>0</span></strong></div>
                                        </span>
                                        <span class="badge badge-primary badge-lg px-3 py-2 mr-2">
                                            <div><small>Total Fee</small></div>
                                            <div><strong>Rp. <span id='fee_counter'>0</span></strong></div>
                                        </span>
                                        <span class="badge badge-navy badge-lg px-3 py-2">
                                            <div><small>Total Payment</small></div>
                                            <div><strong>Rp. <span id='total_payment'>0</span></strong></div>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="table-transaction-list" class="table table-bordered table-striped table-sm text-nowrap">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th>Invoice Date</th>
                                            <th>Invoice NO</th>
                                            <th>CID</th>
                                            <th>Name</th>
                                            <th>Merchant</th>
                                            <th>Address</th>
                                            <th>Note</th>
                                            <th>Periode</th>
                                            <th class="text-right">Total Amount</th>
                                            <th class="text-right">Fee</th>
                                            <th>Status</th>
                                            <th>Kasbank</th>
                                            <th>Recieve By</th>
                                            <th>Transaction Date</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap4.min.css">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<!-- Tempus Dominus DateTimePicker CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css" />

<style>
    /* Small Box Colors */
    .small-box {
        border-radius: 0.25rem;
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        display: block;
        margin-bottom: 20px;
        position: relative;
    }
    
    .small-box > .inner {
        padding: 10px;
    }
    
    .small-box > .small-box-footer {
        background: rgba(0,0,0,.1);
        color: rgba(255,255,255,.8);
        display: block;
        padding: 3px 0;
        position: relative;
        text-align: center;
        text-decoration: none;
        z-index: 10;
    }
    
    .small-box .icon {
        color: rgba(0,0,0,.15);
        z-index: 0;
        position: absolute;
        top: -10px;
        right: 10px;
        transition: all .3s linear;
        font-size: 70px;
    }
    
    .bg-navy {
        background-color: #001f3f !important;
        color: #fff !important;
    }
    
    .bg-pink {
        background-color: #e83e8c !important;
        color: #fff !important;
    }
    
    .badge-navy {
        background-color: #001f3f !important;
        color: #fff !important;
    }
    
    .badge-lg {
        font-size: 0.9rem;
        padding: 0.5rem 1rem !important;
    }
    
    .small-box .inner h4 {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0 0 10px 0;
    }
    
    .small-box .inner p {
        font-size: 0.9rem;
        margin: 0;
    }
    
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .table-sm th, .table-sm td {
        padding: 0.4rem;
        font-size: 0.85rem;
    }
    
    .card-title {
        font-size: 0.95rem;
    }
    
    /* Improve DataTable button styling */
    .dt-buttons {
        margin-bottom: 10px;
    }
    
    .dt-button {
        background-color: #007bff !important;
        color: white !important;
        border: none !important;
        padding: 5px 10px !important;
        margin-right: 5px !important;
        border-radius: 3px !important;
        font-size: 0.85rem !important;
    }
    
    .dt-button:hover {
        background-color: #0056b3 !important;
    }
</style>
@endsection

@section('scripts')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Tempus Dominus Bootstrap 4 (DateTimePicker) JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    console.log('Transaction page initializing...');
    
    // Initialize date pickers
    $('#dateStartPicker').datetimepicker({
        format: 'YYYY-MM-DD'
    });
    
    $('#dateEndPicker').datetimepicker({
        format: 'YYYY-MM-DD'
    });
    
    // Initialize select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Daily transaction chart
    const dailyData = @json($dailyTransactions);
    const labels = dailyData.map(item => item.date);
    const volumes = dailyData.map(item => item.volume);
    const totals = dailyData.map(item => item.total_paid);

    new Chart(document.getElementById('dailyTransactionChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Jumlah Transaksi',
                    data: volumes,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    yAxisID: 'y'
                },
                {
                    label: 'Total Pembayaran (Rp)',
                    data: totals,
                    type: 'line',
                    fill: false,
                    borderColor: 'rgba(255, 99, 132, 0.8)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { 
                        display: true, 
                        text: 'Jumlah Transaksi' 
                    },
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { 
                        display: true, 
                        text: 'Total Pembayaran (Rp)' 
                    },
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });

    // Filter button click
    $('#transaction_filter').click(function() {
        $('#table-transaction-list').DataTable().ajax.reload();
    });

    // Check if table element exists
    if ($('#table-transaction-list').length === 0) {
        console.error('Table element #table-transaction-list not found!');
        return;
    }
    
    console.log('Initializing DataTable...');

    // Initialize DataTable
    var table = $('#table-transaction-list').DataTable({
        "responsive": true,
        "autoWidth": false,
        "searching": false,
        "language": {
            "processing": "<span class='fa-stack fa-lg'>\n\
                <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
                </span>&emsp;Processing ..."
        },
        dom: 'Bfrtip',
        buttons: [
            'pageLength', 'copy', 'excel', 'pdf', 'csv', 'print'
        ],
        "lengthMenu": [[50, 100, 200, 500, 1000], [50, 100, 200, 500, 1000]],
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.tenants.transactions.data", $tenant->id) }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: function(d) {
                return $.extend({}, d, {
                    "dateStart": $('[name="dateStart"]').val(),
                    "dateEnd": $('[name="dateEnd"]').val(),
                    "parameter": $('[name="parameter"]').val(),
                    "updatedBy": $('[name="updatedBy"]').val(),
                    "id_merchant": $('[name="id_merchant"]').val(),
                    "kasbank": $('[name="kasbank"]').val(),
                });
            },
            error: function(xhr, error, code) {
                console.error('DataTables Ajax Error:', xhr.responseText);
                alert('Error loading data. Please check console for details.');
            },
            "dataSrc": function(json) {
                // Check if response has required data
                if (!json.data) {
                    console.error('Invalid response:', json);
                    return [];
                }
                
                // Update grouped tables
                updateGroupedTransactionsUser(json);
                updateGroupedTransactionsMerchant(json);
                updateGroupedTransactionsKasbank(json);
                
                // Update summary
                $('#total_paid').text(new Intl.NumberFormat('id-ID').format(json.totalAmount || 0));
                $('#fee_counter').text(new Intl.NumberFormat('id-ID').format(json.totalFee || 0));
                $('#total_payment').text(new Intl.NumberFormat('id-ID').format(json.totalPayment || 0));
                
                return json.data;
            },
        },
        columns: [
            { data: null, render: function(data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }},
            { data: 'invoice_date', defaultContent: '-' },
            { data: 'invoice_no', defaultContent: '-' },
            { data: 'customer_id', defaultContent: '-' },
            { data: 'customer_name', defaultContent: '-' },
            { data: 'merchant_name', defaultContent: '-' },
            { data: 'address', defaultContent: '-' },
            { data: 'note', defaultContent: '-' },
            { data: 'periode', defaultContent: '-' },
            { data: 'total_amount', defaultContent: 0, render: function(data) {
                return new Intl.NumberFormat('id-ID').format(data || 0);
            }},
            { data: 'payment_point_fee', defaultContent: 0, render: function(data) {
                return new Intl.NumberFormat('id-ID').format(data || 0);
            }},
            { data: 'payment_status', defaultContent: 0, render: function(data) {
                return data == 1 ? '<span class="badge badge-success">Paid</span>' : '<span class="badge badge-warning">Unpaid</span>';
            }},
            { data: 'kasbank_name', defaultContent: '-' },
            { data: 'updated_by', defaultContent: '-' },
            { data: 'payment_date', defaultContent: '-' }
        ]
    });
    
    console.log('DataTable initialized successfully');

    function updateGroupedTransactionsUser(json) {
        var html = '';
        var totalPayment = 0;

        if (!json.groupedTransactionsUser || !Array.isArray(json.groupedTransactionsUser)) {
            $('#groupedTransactionsUser').html('<tr><td colspan="3" class="text-center">No data</td></tr>');
            $('#totalPayment').text('Rp. 0');
            return;
        }

        json.groupedTransactionsUser.forEach(function(item, index) {
            var userName = item.updated_by;
            if (json.users && json.users.length) {
                var user = json.users.find(u => u.id == item.updated_by);
                if (user) userName = user.name;
            }
            
            totalPayment += parseFloat(item.total_payment || 0);

            html += '<tr>' +
                '<td>' + (index + 1) + '</td>' +
                '<td>' + userName + '</td>' +
                '<td class="text-right"><strong>Rp. ' + new Intl.NumberFormat('id-ID').format(item.total_payment) + '</strong></td>' +
                '</tr>';
        });

        $('#groupedTransactionsUser').html(html);
        $('#totalPayment').text('Rp. ' + new Intl.NumberFormat('id-ID').format(totalPayment));
    }

    function updateGroupedTransactionsMerchant(json) {
        var html = '';
        var totalPayment = 0;

        if (!json.groupedTransactionsMerchant || !Array.isArray(json.groupedTransactionsMerchant)) {
            $('#groupedTransactionsMerchant').html('<tr><td colspan="3" class="text-center">No data</td></tr>');
            $('#totalPaymentMerchant').text('Rp. 0');
            return;
        }

        json.groupedTransactionsMerchant.forEach(function(item, index) {
            var merchantName = 'No Merchant';
            if (json.merchants && json.merchants.length) {
                var merchant = json.merchants.find(m => m.id == item.id_merchant);
                if (merchant) merchantName = merchant.name;
            }
            
            totalPayment += parseFloat(item.total_payment || 0);

            html += '<tr>' +
                '<td>' + (index + 1) + '</td>' +
                '<td>' + merchantName + '</td>' +
                '<td class="text-right"><strong>Rp. ' + new Intl.NumberFormat('id-ID').format(item.total_payment) + '</strong></td>' +
                '</tr>';
        });

        $('#groupedTransactionsMerchant').html(html);
        $('#totalPaymentMerchant').text('Rp. ' + new Intl.NumberFormat('id-ID').format(totalPayment));
    }

    function updateGroupedTransactionsKasbank(json) {
        var html = '';
        var totalPayment = 0;

        if (!json.groupedTransactionsKasbank || !Array.isArray(json.groupedTransactionsKasbank)) {
            $('#groupedTransactionsKasbank').html('<tr><td colspan="3" class="text-center">No data</td></tr>');
            $('#totalPaymentKasbank').text('Rp. 0');
            return;
        }

        json.groupedTransactionsKasbank.forEach(function(item, index) {
            var kasbankName = item.payment_point;
            if (json.kasbanks && json.kasbanks.length) {
                var akun = json.kasbanks.find(k => k.akun_code == item.payment_point);
                if (akun) kasbankName = akun.name;
            }
            
            totalPayment += parseFloat(item.total_payment || 0);

            html += '<tr>' +
                '<td>' + (index + 1) + '</td>' +
                '<td>' + kasbankName + '</td>' +
                '<td class="text-right"><strong>Rp. ' + new Intl.NumberFormat('id-ID').format(item.total_payment) + '</strong></td>' +
                '</tr>';
        });

        $('#groupedTransactionsKasbank').html(html);
        $('#totalPaymentKasbank').text('Rp. ' + new Intl.NumberFormat('id-ID').format(totalPayment));
    }

    // Reset filter button
    $('#reset_filter').click(function() {
        $('[name="dateStart"]').val('{{ date('Y-m-01') }}');
        $('[name="dateEnd"]').val('{{ date('Y-m-d') }}');
        $('[name="parameter"]').val('');
        $('[name="updatedBy"]').val('').trigger('change');
        $('[name="id_merchant"]').val('').trigger('change');
        $('[name="kasbank"]').val('').trigger('change');
        $('#table-transaction-list').DataTable().ajax.reload();
    });
});
</script>
@endsection
