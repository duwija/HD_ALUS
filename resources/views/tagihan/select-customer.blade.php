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
        .greeting-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            color: #fff;
        }
        .greeting-card h2 {
            margin: 0 0 6px;
            font-size: 24px;
            font-weight: 700;
        }
        .greeting-card p {
            margin: 0 0 14px;
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        .tagihan-summary {
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .tagihan-summary .label {
            font-size: 11px;
            color: rgba(255,255,255,0.85);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .tagihan-summary .amount {
            font-size: 22px;
            font-weight: 800;
            line-height: 1.2;
            margin-top: 3px;
        }
        .greeting-meta {
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.22);
        }
        .greeting-meta-text {
            font-size: 13px;
            color: rgba(255,255,255,0.92);
            margin: 0;
        }
        .greeting-meta-text strong {
            color: #fff;
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
        .alert-portal {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .addon-order-box {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px dashed #d9d9d9;
        }
        .addon-actions {
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .addon-active-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .addon-chip {
            display: inline-flex;
            align-items: center;
            font-size: 11px;
            font-weight: 700;
            color: #1d7a46;
            background: #d7f5e4;
            border-radius: 999px;
            padding: 3px 8px;
        }
        .addon-toggle-btn {
            background: #fff;
            color: #5b63d3;
            border: 1px solid #ced4ff;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
        }
        .addon-toggle-btn:hover {
            background: #eef1ff;
            color: #4c54c7;
        }
        .addon-order-panel {
            display: none;
            margin-top: 12px;
            padding: 12px;
            border: 1px solid #eceffd;
            border-radius: 12px;
            background: #fafbff;
        }
        .addon-order-panel.show {
            display: block;
        }
        .addon-order-title {
            font-size: 14px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        .addon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }
        .addon-option {
            position: relative;
            border: 1px solid #e4e7ec;
            border-radius: 12px;
            padding: 12px 14px;
            background: #fafbff;
        }
        .addon-option.active {
            background: #f2fff7;
            border-color: #b9ebcc;
        }
        .addon-option input {
            position: absolute;
            top: 14px;
            left: 14px;
        }
        .addon-option label {
            display: block;
            margin: 0;
            padding-left: 26px;
            cursor: pointer;
        }
        .addon-option-name {
            display: block;
            font-weight: 700;
            color: #2f3542;
            margin-bottom: 3px;
        }
        .addon-option-price {
            display: block;
            font-size: 13px;
            color: #667eea;
            font-weight: 700;
        }
        .addon-option-desc {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.45;
        }
        .addon-badge {
            display: inline-block;
            margin-top: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #1d7a46;
            background: #d7f5e4;
            border-radius: 999px;
            padding: 2px 8px;
        }
        .btn-addon-order {
            background: linear-gradient(135deg, #ff8a00 0%, #e65c00 100%);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 700;
        }
        .btn-addon-order:hover {
            color: #fff;
            box-shadow: 0 5px 15px rgba(230, 92, 0, 0.28);
        }
        .addon-help {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    @php
        $companyName = tenant_config('APP_NAME', config('app.name', 'INTERNET SERVICE PROVIDER'));
        $companyAddress1 = tenant_config('COMPANY_ADDRESS1', tenant_config('company_address1', ''));
        $companyAddress2 = tenant_config('COMPANY_ADDRESS2', tenant_config('company_address2', ''));
    @endphp
    <div class="container">
        <div class="selection-container">
            @if(session('success'))
            <div class="alert alert-success alert-portal">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger alert-portal">
                <i class="fas fa-exclamation-circle"></i>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
            @endif

            <div class="greeting-card">
                <h2>Halo, {{ Auth::guard('customer')->user()->name ?? 'Pelanggan' }} 👋</h2>
                <p>Selamat datang di Portal Pelanggan</p>
                <div class="tagihan-summary">
                    <div>
                        <div class="label">Total Tagihan Belum Bayar</div>
                        <div class="amount">Rp {{ number_format($unpaidTotal ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <!-- <a href="{{ route('app.tagihan') }}"
                       style="background:#fff;color:var(--primary);padding:8px 14px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;">
                        Lihat
                    </a> -->
                </div>
                <div class="greeting-meta">
                    <p class="greeting-meta-text">
                        Ditemukan <strong>{{ count($customers) }}</strong> akun untuk email <strong>{{ $email }}</strong>
                    </p>
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

                    @if(!empty($customer->plan) && !empty($customer->plan->name))
                    <div class="detail-item">
                        <i class="fas fa-wifi"></i>
                        <span>Paket: {{ $customer->plan->name }}</span>
                    </div>
                    @endif

                    <div class="detail-item">
                        <i class="fas fa-plus-circle"></i>
                        <span>
                            Add-on aktif: {{ $customer->addons->count() }}
                        </span>
                    </div>

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

                @php
                    $currentAddonIds = $customer->addons->pluck('id')->all();
                    $addonList = $availableAddons ?? collect();
                    $hasOrderableAddons = $addonList->contains(function ($addon) use ($currentAddonIds) {
                        return !in_array($addon->id, $currentAddonIds);
                    });
                @endphp

                <div class="addon-order-box">
                    <div class="addon-actions">
                        <div class="addon-active-list">
                            @if($customer->addons->count() > 0)
                                @foreach($customer->addons as $activeAddon)
                                <span class="addon-chip">{{ $activeAddon->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted" style="font-size:12px;">Belum ada add-on aktif.</span>
                            @endif
                        </div>
                        <button type="button" class="addon-toggle-btn" onclick="toggleAddonPanel('{{ $customer->id }}')">
                            <i class="fas fa-cart-plus"></i> Tambah Add-on
                        </button>
                    </div>

                    <div id="addon-panel-{{ $customer->id }}" class="addon-order-panel">
                        <div class="addon-order-title">
                            Pilih Add-on
                        </div>
                        <div class="addon-help">
                            Centang add-on yang diinginkan, lalu tekan Order.
                        </div>

                        @php
                            $orderableAddons = $addonList->reject(function ($addon) use ($currentAddonIds) {
                                return in_array($addon->id, $currentAddonIds);
                            });
                        @endphp

                        @if($addonList->isEmpty())
                            <div class="text-muted" style="font-size:13px;">Belum ada add-on yang tersedia saat ini.</div>
                        @elseif($orderableAddons->isEmpty())
                            <div class="text-muted" style="font-size:13px;">Semua add-on sudah aktif di akun ini.</div>
                        @else
                        <form method="POST" action="{{ route('customer.addons.order', $customer->id) }}">
                            @csrf
                            <div class="addon-grid">
                                @foreach($orderableAddons as $addon)
                                <div class="addon-option">
                                    <input type="checkbox" name="addons[]" value="{{ $addon->id }}" id="addon-{{ $customer->id }}-{{ $addon->id }}">
                                    <label for="addon-{{ $customer->id }}-{{ $addon->id }}">
                                        <span class="addon-option-name">{{ $addon->name }}</span>
                                        <span class="addon-option-price">Rp {{ number_format($addon->price, 0, ',', '.') }}</span>
                                        @if(!empty($addon->description))
                                        <span class="addon-option-desc">{{ $addon->description }}</span>
                                        @endif
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-addon-order">
                                <i class="fas fa-paper-plane"></i> Order
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

            <div class="text-center mt-4">
                <p class="text-white">
                    <i class="fas fa-info-circle"></i> Pilih <strong>Tagihan</strong> untuk melihat invoice, atau <strong>Status Tiket</strong> untuk monitor pengaduan
                </p>
            </div>

            <div class="text-center mt-3">
                <p class="mb-1 text-white" style="font-size: 0.85rem; opacity: 0.95;">
                    <strong>{{ $companyName }}</strong>
                </p>
                @if(!empty($companyAddress1) || !empty($companyAddress2))
                    <p class="mb-0 text-white" style="font-size: 0.8rem; opacity: 0.85; line-height: 1.4;">
                        {{ trim($companyAddress1 . ' ' . $companyAddress2) }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ url('dashboard/plugins/sweetalert2/sweetalert2.all.js') }}"></script>
    <script>
        function toggleAddonPanel(customerId) {
            var panel = document.getElementById('addon-panel-' + customerId);
            if (!panel) return;
            panel.classList.toggle('show');
        }

        @if(session('addon_order_popup'))
        window.addEventListener('load', function () {
            var popupMessage = @json(session('addon_order_popup'));
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Order berhasil',
                    text: popupMessage,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#667eea'
                });
                return;
            }

            alert(popupMessage);
        });
        @endif
    </script>
</body>
</html>
