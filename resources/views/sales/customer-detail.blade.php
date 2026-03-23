<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Detail Customer - {{ $customer->name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px 0;
        }
        .navbar-custom .navbar-brand {
            color: white;
            font-weight: 600;
            font-size: 18px;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .nav-username {
            color: rgba(255,255,255,0.9);
            font-size: 13px;
            white-space: nowrap;
        }
        @media (max-width: 576px) {
            .navbar-custom .navbar-brand { font-size: 15px; }
            .nav-username { display: none; }
        }
        .detail-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .detail-card h4 {
            color: #333;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }
        .info-group {
            margin-bottom: 20px;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
            font-size: 16px;
        }
        .badge-custom {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-back:hover {
            transform: scale(1.05);
            color: white;
        }
        #customerMap {
            height: 400px;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            margin-top: 10px;
        }
        .map-container {
            margin-top: 20px;
        }
        .step-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
            z-index: 1;
        }
        .step-line {
            flex: 1;
            height: 3px;
            min-width: 20px;
        }
        .step-label {
            font-size: 10px;
            text-align: center;
            margin-top: 4px;
            color: #555;
            max-width: 68px;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-custom">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <a class="navbar-brand" href="{{ url('/sales') }}">
                <i class="fas fa-user-tie"></i> Portal Sales
            </a>
            <div class="nav-right">
                <span class="nav-username"><i class="fas fa-user mr-1"></i>{{ $sales->name }}</span>
                <a href="{{ url('/sales/logout') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="mt-4 d-flex align-items-center" style="gap:8px">
            <a href="{{ url('/sales') }}" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <a href="{{ url('/sales/customer/' . $customer->id . '/edit') }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Customer
            </a>
        </div>

        <div class="detail-card">
            <h4><i class="fas fa-user-circle"></i> Detail Pelanggan</h4>

            <div class="row">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Customer ID (CID)</div>
                        <div class="info-value"><strong>{{ $customer->customer_id }}</strong></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            @php
                                $statusConfig = [
                                    1 => ['name' => 'Potensial', 'color' => '#3bacd9'],
                                    2 => ['name' => 'Active', 'color' => '#2bd93a'],
                                    3 => ['name' => 'Inactive', 'color' => '#959c9a'],
                                    4 => ['name' => 'Block', 'color' => '#e32510'],
                                    5 => ['name' => 'Company_Properti', 'color' => '#8866aa']
                                ];
                                $status = $statusConfig[$customer->id_status] ?? ['name' => 'Unknown', 'color' => '#999'];
                            @endphp
                            <span class="badge-custom" style="background-color: {{ $status['color'] }}; color: #fff">
                                {{ $status['name'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value">{{ $customer->name }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ $customer->email ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Nomor Telepon</div>
                        <div class="info-value">{{ $customer->phone ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Paket Internet</div>
                        <div class="info-value">
                            @if($customer->plan_name)
                                {{ $customer->plan_name->name }} (Rp {{ number_format($customer->plan_name->price, 0, ',', '.') }})
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">Alamat</div>
                <div class="info-value">{{ $customer->address ?? '-' }}</div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Tanggal Mulai Billing</div>
                        <div class="info-value">{{ $customer->billing_start ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Username PPPoE</div>
                        <div class="info-value">{{ $customer->pppoe ?? '-' }}</div>
                    </div>
                </div>
            </div>


            @if($customer->merchant_name)
            <div class="info-group">
                <div class="info-label">Merchant</div>
                <div class="info-value">{{ $customer->merchant_name->name }}</div>
            </div>
            @endif

            @if($customer->coordinate)
            <div class="info-group">
                <div class="info-label">Koordinat</div>
                <div class="info-value">{{ $customer->coordinate }}</div>
            </div>

            <div class="map-container">
                <div class="info-label">
                    <i class="fas fa-map-marker-alt"></i> Lokasi Pelanggan
                </div>
                <div id="customerMap"></div>
            </div>
            @endif
        </div>

        {{-- Lead Info Card --}}
        @if($customer->lead_source || $customer->lead_notes || $customer->expected_close_date || $customer->conversion_probability !== null)
        <div class="detail-card mt-3">
            <h4><i class="fas fa-funnel-dollar"></i> Informasi Lead</h4>
            <div class="row">
                @if($customer->lead_source)
                <div class="col-md-3">
                    <div class="info-group">
                        <div class="info-label">Sumber Lead</div>
                        <div class="info-value">{{ $customer->lead_source }}</div>
                    </div>
                </div>
                @endif
                @if($customer->expected_close_date)
                <div class="col-md-3">
                    <div class="info-group">
                        <div class="info-label">Target Closing</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($customer->expected_close_date)->format('d M Y') }}</div>
                    </div>
                </div>
                @endif
                @if($customer->conversion_probability !== null)
                <div class="col-md-3">
                    <div class="info-group">
                        <div class="info-label">Peluang Konversi</div>
                        <div class="info-value"><strong>{{ $customer->conversion_probability }}%</strong></div>
                    </div>
                </div>
                @endif
                @if($customer->lead_notes)
                <div class="col-md-12">
                    <div class="info-group">
                        <div class="info-label">Catatan Follow-up</div>
                        <div class="info-value">{{ $customer->lead_notes }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Workflow Card (hanya jika status Potensial) --}}
        @if($customer->id_status == 1)
        @php
            $currentStage = $workflowStages->firstWhere('id', $customer->workflow_stage_id);
            $currentOrder = $currentStage ? $currentStage->order : 0;
        @endphp
        <div class="detail-card mt-3">
            <h4><i class="fas fa-tasks"></i> Progress Workflow</h4>

            {{-- Stepper --}}
            <div class="d-flex align-items-start mb-4" style="overflow-x:auto;padding-bottom:8px">
                @foreach($workflowStages as $stage)
                @php
                    $isDone   = $currentOrder > 0 && $stage->order < $currentOrder;
                    $isActive = $customer->workflow_stage_id == $stage->id;
                @endphp
                <div style="display:flex;flex-direction:column;align-items:center;min-width:68px">
                    <div style="display:flex;align-items:center">
                        @if(!$loop->first)
                        <div class="step-line" style="background:{{ $isDone || $isActive ? '#667eea' : '#dee2e6' }}"></div>
                        @endif
                        <div class="step-circle" style="background:{{ $isActive ? '#667eea' : ($isDone ? '#28a745' : '#dee2e6') }};color:{{ ($isActive || $isDone) ? '#fff' : '#999' }}">
                            @if($isDone)
                                <i class="fas fa-check"></i>
                            @elseif($isActive)
                                <i class="fas fa-circle"></i>
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </div>
                        @if(!$loop->last)
                        <div class="step-line" style="background:{{ $isDone ? '#667eea' : '#dee2e6' }}"></div>
                        @endif
                    </div>
                    <div class="step-label">{{ $stage->name }}</div>
                </div>
                @endforeach
            </div>

            @if($currentStage)
            <div class="alert alert-info py-2 mb-3">
                <i class="fas fa-map-marker-alt mr-1"></i>
                Tahap saat ini: <strong>{{ $currentStage->name }}</strong>
            </div>
            @endif

            @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif

            <form action="{{ url('/sales/customer/'.$customer->id.'/stage') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="font-weight-bold">Update Tahap</label>
                            <select name="workflow_stage_id" class="form-control">
                                <option value="">-- Pilih Tahap --</option>
                                @foreach($workflowStages as $stage)
                                <option value="{{ $stage->id }}" {{ $customer->workflow_stage_id == $stage->id ? 'selected' : '' }}>{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="form-group">
                            <label class="font-weight-bold">Catatan Follow-up</label>
                            <textarea name="lead_notes" class="form-control" rows="2" placeholder="Update catatan...">{{ $customer->lead_notes }}</textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-save mr-1"></i> Simpan Progress
                </button>
            </form>
        </div>
        @endif

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    @if($customer->coordinate)
    <script>
        $(document).ready(function() {
            // Parse koordinat (format: "lat,lng" atau "lat, lng")
            var coordinateStr = "{{ $customer->coordinate }}";
            var coords = coordinateStr.split(',').map(function(c) { return parseFloat(c.trim()); });
            
            if (coords.length === 2 && !isNaN(coords[0]) && !isNaN(coords[1])) {
                var lat = coords[0];
                var lng = coords[1];
                
                // Initialize map
                var map = L.map('customerMap').setView([lat, lng], 16);
                
                // Add OpenStreetMap tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(map);
                
                // Custom icon
                var customerIcon = L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });
                
                // Add marker
                var marker = L.marker([lat, lng], {icon: customerIcon}).addTo(map);
                
                // Popup content
                var popupContent = '<div style="text-align: center;">' +
                    '<strong>{{ $customer->name }}</strong><br>' +
                    '<small>{{ $customer->customer_id }}</small><br>' +
                    '<small>{{ $customer->address }}</small><br>' +
                    '<small class="text-muted">Lat: ' + lat + ', Lng: ' + lng + '</small>' +
                    '</div>';
                
                marker.bindPopup(popupContent).openPopup();
                
                // Add circle radius (optional - 50 meter radius)
                var circle = L.circle([lat, lng], {
                    color: '#667eea',
                    fillColor: '#667eea',
                    fillOpacity: 0.2,
                    radius: 50
                }).addTo(map);
            } else {
                $('#customerMap').html('<div class="alert alert-warning">Format koordinat tidak valid. Harap gunakan format: latitude,longitude</div>');
            }
        });
    </script>
    @endif
</body>
</html>
