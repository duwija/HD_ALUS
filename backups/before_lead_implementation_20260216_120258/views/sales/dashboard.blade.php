<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sales - {{ $sales->name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
    <style>
        body {
            background: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 0;
        }
        .navbar-custom .navbar-brand {
            color: white;
            font-weight: 600;
            font-size: 20px;
        }
        .navbar-custom .nav-link {
            color: white !important;
        }
        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .welcome-card h3 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .welcome-card p {
            color: #666;
            margin-bottom: 0;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stats-card i {
            font-size: 40px;
            margin-bottom: 15px;
        }
        .stats-card h4 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .stats-card p {
            color: #666;
            margin-bottom: 0;
        }
        .customer-table {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .customer-table h4 {
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            border-top: none;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #555;
        }
        .badge-custom {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 8px;
            font-size: 12px;
            transition: all 0.3s;
        }
        .btn-view:hover {
            transform: scale(1.05);
            color: white;
        }
        .form-control {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        .card-header {
            border: none;
            padding: 15px 20px;
        }
        .bg-gradient-info {
            background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
        }
        .bg-gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .bg-gradient-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ url('/sales') }}">
                <i class="fas fa-user-tie"></i> Portal Sales
            </a>
            <div class="ml-auto">
                <span class="navbar-text text-white mr-3">
                    <i class="fas fa-user"></i> {{ $sales->name }}
                </span>
                <a href="{{ url('/sales/logout') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="welcome-card">
            <h3>Selamat Datang, {{ $sales->full_name ?? $sales->name }}!</h3>
            <p>Email: {{ $sales->email }} | Telepon: {{ $sales->phone }}</p>
            @if($sales->last_login_at)
            <p class="text-muted small">Login terakhir: {{ \Carbon\Carbon::parse($sales->last_login_at)->format('d M Y H:i') }}</p>
            @endif
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-users text-primary"></i>
                    <h4>{{ $totalCustomers }}</h4>
                    <p>Total Pelanggan</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-check-circle text-success"></i>
                    <h4>{{ $activeCustomers }}</h4>
                    <p>Aktif</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-ban text-danger"></i>
                    <h4>{{ $blockCustomers }}</h4>
                    <p>Block</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-pause-circle text-secondary"></i>
                    <h4>{{ $inactiveCustomers }}</h4>
                    <p>Inactive</p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-4">
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-gradient-info text-white">
                        <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Status</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-gradient-success text-white">
                        <h6 class="mb-0"><i class="fas fa-chart-line"></i> Pertumbuhan {{ \Carbon\Carbon::now()->year }}</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="growthChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-gradient-warning text-white">
                        <h6 class="mb-0"><i class="fas fa-store"></i> Per Merchant</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="merchantChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-gradient-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-box"></i> Per Paket</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="planChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="customer-table mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-list"></i> Daftar Pelanggan Anda ({{ $totalCustomers }} Total)</h4>
                <a href="{{ url('/sales/customer/create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah Customer Baru
                </a>
            </div>
            
            <!-- Filter Form -->
            <div class="row mb-3">
                <div class="col-md-2">
                    <label for="filter">Filter By</label>
                    <select name="filter" id="filter" class="form-control">
                        <option value="customer_id">CID</option>
                        <option value="name">Nama</option>
                        <option value="address">Alamat</option>
                        <option value="phone">Telepon</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="parameter">Parameter</label>
                    <input type="text" id="parameter" name="parameter" class="form-control" placeholder="Ketik untuk cari...">
                </div>
                <div class="col-md-2">
                    <label for="id_status">Status</label>
                    <select name="id_status" id="id_status" class="form-control">
                        <option value="">Semua</option>
                        @foreach ($status as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="id_plan">Paket</label>
                    <select name="id_plan" id="id_plan" class="form-control">
                        <option value="">Semua</option>
                        @foreach ($plan as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-2">
                    <label for="billing_start_from">Mulai Langganan Dari</label>
                    <input type="date" id="billing_start_from" name="billing_start_from" class="form-control">
                </div>
                <div class="col-md-2">
                    <label for="billing_start_to">Sampai</label>
                    <input type="date" id="billing_start_to" name="billing_start_to" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="btn-filter" class="btn btn-primary btn-block">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="btn-reset" class="btn btn-secondary btn-block">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="table-customers" class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>CID</th>
                            <th>Nama</th>
                            <th>Alamat</th>
                            <th>Telepon</th>
                            <th>Mulai Langganan</th>
                            <th>Paket</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Status Pie Chart
            var statusCtx = document.getElementById('statusChart').getContext('2d');
            var statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! $statusLabels !!},
                    datasets: [{
                        data: {!! $statusData !!},
                        backgroundColor: [
                            '#ffc107',
                            '#28a745', 
                            '#6c757d',
                            '#dc3545',
                            '#17a2b8'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 8,
                                font: {
                                    size: 10
                                },
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed || 0;
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });

            // Initialize Growth Line Chart
            var growthCtx = document.getElementById('growthChart').getContext('2d');
            var growthChart = new Chart(growthCtx, {
                type: 'line',
                data: {
                    labels: {!! $monthLabels !!},
                    datasets: [
                        {
                            label: 'Customer Baru',
                            data: {!! $monthlyNew !!},
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Customer Hilang',
                            data: {!! $monthlyLost !!},
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Net Growth',
                            data: {!! $monthlyNet !!},
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 8,
                                font: {
                                    size: 10
                                },
                                usePointStyle: true,
                                boxWidth: 12
                            }
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
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Initialize Merchant Pie Chart
            var merchantCtx = document.getElementById('merchantChart').getContext('2d');
            var merchantChart = new Chart(merchantCtx, {
                type: 'pie',
                data: {
                    labels: {!! $merchantLabels !!},
                    datasets: [{
                        data: {!! $merchantData !!},
                        backgroundColor: [
                            '#ff6384',
                            '#36a2eb',
                            '#ffce56',
                            '#4bc0c0',
                            '#9966ff',
                            '#ff9f40',
                            '#c9cbcf',
                            '#7cb342'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 8,
                                font: {
                                    size: 10
                                },
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed || 0;
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });

            // Initialize Plan Bar Chart
            var planCtx = document.getElementById('planChart').getContext('2d');
            var planChart = new Chart(planCtx, {
                type: 'bar',
                data: {
                    labels: {!! $planLabels !!},
                    datasets: [{
                        label: 'Pelanggan',
                        data: {!! $planData !!},
                        backgroundColor: '#667eea',
                        borderColor: '#764ba2',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed.y + ' pelanggan';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 9
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 9
                                }
                            }
                        }
                    }
                }
            });

            // Initialize Select2 only for Plan dropdown
            $('#id_plan').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Paket',
                allowClear: true,
                width: '100%'
            });

            var table = $('#table-customers').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ url('/sales/table-customer') }}',
                    data: function(d) {
                        d.filter = $('#filter').val();
                        d.parameter = $('#parameter').val();
                        d.id_status = $('#id_status').val();
                        d.id_plan = $('#id_plan').val();
                        d.billing_start_from = $('#billing_start_from').val();
                        d.billing_start_to = $('#billing_start_to').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'customer_id', name: 'customer_id' },
                    { data: 'name', name: 'name' },
                    { data: 'address', name: 'address' },
                    { data: 'phone', name: 'phone' },
                    { data: 'billing_start', name: 'billing_start' },
                    { data: 'plan', name: 'plan', orderable: false },
                    { data: 'status_cust', name: 'status_cust', orderable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    processing: "Memuat data...",
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    zeroRecords: "Tidak ada data yang cocok",
                    emptyTable: "Tidak ada data tersedia",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                }
            });

            // Filter button click
            $('#btn-filter').on('click', function() {
                table.ajax.reload();
            });

            // Reset button click
            $('#btn-reset').on('click', function() {
                $('#filter').val('customer_id');
                $('#parameter').val('');
                $('#id_status').val('');
                $('#id_plan').val('').trigger('change');
                $('#billing_start_from').val('');
                $('#billing_start_to').val('');
                table.ajax.reload();
            });

            // Enter key on parameter input
            $('#parameter').on('keypress', function(e) {
                if (e.which == 13) {
                    table.ajax.reload();
                }
            });

            // Auto reload on status/plan change
            $('#id_status').on('change', function() {
                table.ajax.reload();
            });
            
            $('#id_plan').on('change', function() {
                table.ajax.reload();
            });

            // Auto reload on date change
            $('#billing_start_from, #billing_start_to').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
</body>
</html>
