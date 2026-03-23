<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Akun Pelanggan - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .selection-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
        }
        .header-card h3 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .header-card p {
            color: #666;
            margin-bottom: 0;
        }
        .customer-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid transparent;
        }
        .customer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-color: #667eea;
        }
        .customer-card .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .customer-card .customer-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            margin-right: 20px;
        }
        .customer-card .customer-name {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .customer-card .customer-id {
            font-size: 14px;
            color: #999;
            margin: 0;
        }
        .customer-card .customer-info {
            flex: 1;
        }
        .customer-card .customer-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .customer-card .detail-item {
            display: flex;
            align-items: center;
        }
        .customer-card .detail-item i {
            color: #667eea;
            margin-right: 10px;
            width: 20px;
        }
        .customer-card .detail-item span {
            color: #666;
            font-size: 14px;
        }
        .customer-card .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .customer-card .btn-view:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .logout-btn {
            background: white;
            color: #667eea;
            border: 2px solid white;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="selection-container">
            <div class="header-card">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h3>Pilih Akun Pelanggan</h3>
                <p>Ditemukan {{ count($customers) }} akun yang terdaftar dengan email <strong>{{ $email }}</strong></p>
                <div class="mt-3">
                    <a href="{{ url('/tagihan/logout') }}" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            @foreach($customers as $customer)
            <div class="customer-card">
                <div class="card-header">
                    <div style="display: flex; align-items: center; flex: 1;">
                        <div class="customer-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="customer-info">
                            <h5 class="customer-name">{{ $customer->name }}</h5>
                            <p class="customer-id">CID: {{ $customer->customer_id }}</p>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
                        <a href="{{ url('/tagihan/view-invoice/' . $customer->id) }}" class="btn btn-view" onclick="event.stopPropagation()">
                            <i class="fas fa-file-invoice"></i> Tagihan
                        </a>
                        <a href="{{ url('/tagihan/tickets/' . $customer->id) }}" class="btn btn-view" style="background: linear-gradient(135deg, #20c997 0%, #17a673 100%);" onclick="event.stopPropagation()">
                            <i class="fas fa-ticket-alt"></i> Status Tiket
                        </a>
                    </div>
                </div>

                <div class="customer-details">
                    @if($customer->address)
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>{{ $customer->address }}</span>
                    </div>
                    @endif

                    @if($customer->phone)
                    <div class="detail-item">
                        <i class="fas fa-phone"></i>
                        <span>{{ $customer->phone }}</span>
                    </div>
                    @endif

                    @if(isset($customer->plan->name))
                    <div class="detail-item">
                        <i class="fas fa-wifi"></i>
                        <span>Paket: {{ $customer->plan->name }}</span>
                    </div>
                    @endif

                    @if($customer->id_status)
                    <div class="detail-item">
                        <i class="fas fa-signal"></i>
                        <span>
                            @php
                                $statusConfig = [
                                    1 => ['name' => 'Potensial', 'bg' => '#3bacd9', 'text' => '#fff'],
                                    2 => ['name' => 'Active', 'bg' => '#2bd93a', 'text' => '#fff'],
                                    3 => ['name' => 'Inactive', 'bg' => '#959c9a', 'text' => '#fff'],
                                    4 => ['name' => 'Block', 'bg' => '#e32510', 'text' => '#fff'],
                                    5 => ['name' => 'Company_Properti', 'bg' => '#8866aa', 'text' => '#fff']
                                ];
                                $status = $statusConfig[$customer->id_status] ?? ['name' => 'Unknown', 'bg' => '#999', 'text' => '#fff'];
                            @endphp
                            <span class="status-badge" style="background-color: {{ $status['bg'] }}; color: {{ $status['text'] }}">
                                {{ $status['name'] }}
                            </span>
                        </span>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach

            <div class="text-center mt-4">
                <p class="text-white">
                    <i class="fas fa-info-circle"></i> Pilih <strong>Tagihan</strong> untuk melihat invoice, atau <strong>Status Tiket</strong> untuk monitor pengaduan
                </p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
