@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h3 class="mb-2 mb-md-0">
                        <i class="fas fa-info-circle"></i> Detail Tenant: {{ $tenant->app_name }}
                    </h3>
                    <div class="btn-group flex-wrap" role="group">
                        <a href="{{ route('admin.tenants.customers', $tenant->id) }}" class="btn btn-success btn-sm" title="Customers">
                            <i class="fas fa-users"></i> <span class="d-none d-lg-inline">Customers</span>
                        </a>
                        <a href="{{ route('admin.tenants.users', $tenant->id) }}" class="btn btn-secondary btn-sm" title="Users Tenant">
                            <i class="fas fa-user-cog"></i> <span class="d-none d-lg-inline">Users</span>
                        </a>
                        <a href="{{ route('admin.tenants.transactions', $tenant->id) }}" class="btn btn-warning btn-sm" title="Transactions">
                            <i class="fas fa-money-bill-wave"></i> <span class="d-none d-lg-inline">Transactions</span>
                        </a>
                        <a href="{{ route('admin.tenants.backups', $tenant->id) }}" class="btn btn-primary btn-sm" title="Backups">
                            <i class="fas fa-folder-open"></i> <span class="d-none d-lg-inline">Backups</span>
                        </a>
                        <a href="{{ route('admin.tenants.payment-gateway', $tenant->id) }}" class="btn btn-info btn-sm" title="Payment Gateway">
                            <i class="fas fa-credit-card"></i> <span class="d-none d-lg-inline">Payment</span>
                        </a>
                        <a href="{{ route('admin.tenants.payment-points', $tenant->id) }}" class="btn btn-secondary btn-sm" title="Lokasi Pembayaran (Bumdes)">
                            <i class="fas fa-store"></i> <span class="d-none d-lg-inline">Lokasi Bayar</span>
                        </a>
                        <a href="{{ route('admin.tenants.edit', $tenant->id) }}" class="btn btn-dark btn-sm" title="Edit">
                            <i class="fas fa-edit"></i> <span class="d-none d-lg-inline">Edit</span>
                        </a>
                        <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary btn-sm" title="Kembali">
                            <i class="fas fa-arrow-left"></i> <span class="d-none d-lg-inline">Kembali</span>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle"></i> {{ session('info') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Terjadi kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2 mb-3">Informasi Umum</h5>
                            
                            <table class="table table-sm">
                                <tr>
                                    <th width="150">Domain</th>
                                    <td>
                                        <strong>{{ $tenant->domain }}</strong>
                                        <a href="https://{{ $tenant->domain }}" target="_blank" class="ml-2">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Sales Page</th>
                                    <td>
                                        <a href="https://{{ $tenant->domain }}/sales" target="_blank" class="text-primary">
                                            <i class="fas fa-shopping-cart"></i> {{ $tenant->domain }}/sales
                                            <i class="fas fa-external-link-alt ml-1 small"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Customer Login</th>
                                    <td>
                                        <a href="https://{{ $tenant->domain }}/tagihan" target="_blank" class="text-success">
                                            <i class="fas fa-sign-in-alt"></i> {{ $tenant->domain }}/tagihan
                                            <i class="fas fa-external-link-alt ml-1 small"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>App Name</th>
                                    <td>{{ $tenant->app_name }}</td>
                                </tr>
                                <tr>
                                    <th>Signature</th>
                                    <td>{{ $tenant->signature }}</td>
                                </tr>
                                <tr>
                                    <th>Rescode</th>
                                    <td><span class="badge badge-info badge-lg">{{ $tenant->rescode }}</span></td>
                                </tr>
                                <tr>
                                    <th>Email From</th>
                                    <td>{{ $tenant->mail_from }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @if($tenant->is_active)
                                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>
                                        @else
                                            <span class="badge badge-secondary"><i class="fas fa-times-circle"></i> Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created</th>
                                    <td>{{ $tenant->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Updated</th>
                                    <td>{{ $tenant->updated_at->format('d M Y H:i') }}</td>
                                </tr>
                            </table>

                            @if($tenant->notes)
                            <div class="alert alert-info">
                                <strong>Catatan:</strong><br>
                                {{ $tenant->notes }}
                            </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2 mb-3">Konfigurasi Database</h5>
                            
                            <table class="table table-sm">
                                <tr>
                                    <th width="150">Database</th>
                                    <td><code>{{ $tenant->db_database }}</code></td>
                                </tr>
                                <tr>
                                    <th>Host</th>
                                    <td><code>{{ $tenant->db_host }}</code></td>
                                </tr>
                                <tr>
                                    <th>Port</th>
                                    <td><code>{{ $tenant->db_port }}</code></td>
                                </tr>
                                <tr>
                                    <th>Username</th>
                                    <td><code>{{ $tenant->db_username }}</code></td>
                                </tr>
                                <tr>
                                    <th>Password</th>
                                    <td>
                                        <span class="text-muted">
                                            <i class="fas fa-lock"></i> Encrypted
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <h5 class="border-bottom pb-2 mb-3 mt-4">Customer Statistics</h5>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card bg-light mb-3">
                                        <div class="card-body p-3">
                                            <div class="row text-center">
                                                <div class="col-4 col-md-3 mb-3">
                                                    <div class="border-right">
                                                        <h4 class="mb-0 text-primary">{{ $customerStats['total'] }}</h4>
                                                        <small class="text-muted">Total</small>
                                                    </div>
                                                </div>
                                                <div class="col-4 col-md-3 mb-3">
                                                    <div class="border-right">
                                                        <h4 class="mb-0 text-success">{{ $customerStats['active'] }}</h4>
                                                        <small class="text-muted">Active</small>
                                                    </div>
                                                </div>
                                                <div class="col-4 col-md-3 mb-3">
                                                    <div class="border-right">
                                                        <h4 class="mb-0 text-info">{{ $customerStats['potential'] }}</h4>
                                                        <small class="text-muted">Potential</small>
                                                    </div>
                                                </div>
                                                <div class="col-4 col-md-3 mb-3">
                                                    <h4 class="mb-0 text-danger">{{ $customerStats['block'] }}</h4>
                                                    <small class="text-muted">Block</small>
                                                </div>
                                                <div class="col-4 col-md-4 mb-3">
                                                    <div class="border-right">
                                                        <h4 class="mb-0 text-secondary">{{ $customerStats['inactive'] }}</h4>
                                                        <small class="text-muted">Inactive</small>
                                                    </div>
                                                </div>
                                                <div class="col-4 col-md-4 mb-3">
                                                    <div class="border-right">
                                                        <h4 class="mb-0 text-warning">{{ $customerStats['company_properti'] }}</h4>
                                                        <small class="text-muted">Company Properti</small>
                                                    </div>
                                                </div>
                                                <div class="col-4 col-md-4 mb-3">
                                                    <h4 class="mb-0 text-dark">{{ $customerStats['deleted'] }}</h4>
                                                    <small class="text-muted">Deleted</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="border-bottom pb-2 mb-3 mt-4">Features</h5>
                            
                            <div class="row">
                                <div class="col-6 mb-2">
                                    @if($tenant->features['accounting'] ?? false)
                                        <i class="fas fa-check-circle text-success"></i> Accounting
                                    @else
                                        <i class="fas fa-times-circle text-muted"></i> Accounting
                                    @endif
                                </div>
                                <div class="col-6 mb-2">
                                    @if($tenant->features['ticketing'] ?? false)
                                        <i class="fas fa-check-circle text-success"></i> Ticketing
                                    @else
                                        <i class="fas fa-times-circle text-muted"></i> Ticketing
                                    @endif
                                </div>
                                <div class="col-6 mb-2">
                                    @if($tenant->features['whatsapp'] ?? false)
                                        <i class="fas fa-check-circle text-success"></i> WhatsApp
                                    @else
                                        <i class="fas fa-times-circle text-muted"></i> WhatsApp
                                    @endif
                                </div>
                                <div class="col-6 mb-2">
                                    @if($tenant->features['payment_gateway'] ?? false)
                                        <i class="fas fa-check-circle text-success"></i> Payment Gateway
                                    @else
                                        <i class="fas fa-times-circle text-muted"></i> Payment Gateway
                                    @endif
                                </div>
                            </div>

                            <h5 class="border-bottom pb-2 mb-3 mt-4">License & Quota</h5>

                            @php
                                $activeCount = $customerStats['active'];
                                $plan = $tenant->licensePlan;
                                $maxCust = $plan ? $plan->max_customers : null;
                                $isUnlimited = $plan && $plan->isUnlimited();
                                $quotaPercent = (!$isUnlimited && $maxCust > 0)
                                    ? min(100, round($activeCount / $maxCust * 100))
                                    : ($isUnlimited ? null : 0);
                                $statusColors = [
                                    'active'    => 'success',
                                    'trial'     => 'info',
                                    'suspended' => 'warning',
                                    'expired'   => 'danger',
                                ];
                                $licenseStatus = $tenant->license_status ?? 'active';
                                $statusColor = $statusColors[$licenseStatus] ?? 'secondary';
                            @endphp

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0 font-weight-bold">
                                                    <i class="fas fa-id-card text-primary mr-1"></i>
                                                    {{ $plan ? $plan->name : 'Tidak ada plan' }}
                                                </h6>
                                                <span class="badge badge-{{ $statusColor }}">
                                                    {{ ucfirst($licenseStatus) }}
                                                </span>
                                            </div>
                                            @if($plan && $plan->price_monthly > 0)
                                                <div class="small text-muted mb-1">
                                                    <i class="fas fa-money-bill-wave mr-1"></i>
                                                    {{ $plan->priceFormatted() }} / bulan
                                                </div>
                                            @endif
                                            @if($plan && $plan->description)
                                                <div class="small text-muted mb-2">{{ $plan->description }}</div>
                                            @endif
                                            @if($tenant->license_expires_at)
                                                @php $expired = $tenant->license_expires_at->isPast(); @endphp
                                                <div class="small {{ $expired ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                    Berakhir: {{ $tenant->license_expires_at->format('d M Y') }}
                                                    @if($expired) <span class="badge badge-danger ml-1">Expired</span> @endif
                                                </div>
                                            @else
                                                <div class="small text-muted"><i class="fas fa-infinity mr-1"></i> Tidak ada batas waktu</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <h6 class="mb-2 font-weight-bold">
                                                <i class="fas fa-users text-success mr-1"></i> Kuota Pelanggan
                                            </h6>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="small font-weight-bold">{{ $activeCount }} Aktif</span>
                                                <span class="small text-muted">
                                                    @if($isUnlimited) Unlimited @elseif($maxCust !== null) dari {{ $maxCust }} @else - @endif
                                                </span>
                                            </div>
                                            @if(!$isUnlimited && $quotaPercent !== null)
                                                @php
                                                    $barColor = $quotaPercent >= 100 ? 'danger' : ($quotaPercent >= 80 ? 'warning' : 'success');
                                                @endphp
                                                <div class="progress mb-2" style="height:10px;">
                                                    <div class="progress-bar bg-{{ $barColor }}" style="width:{{ $quotaPercent }}%"></div>
                                                </div>
                                                @if($quotaPercent >= 100)
                                                    <div class="small text-danger font-weight-bold">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i> Kuota penuh!
                                                    </div>
                                                @elseif($maxCust !== null)
                                                    <div class="small text-muted">Sisa: {{ max(0, $maxCust - $activeCount) }} slot</div>
                                                @endif
                                            @elseif($isUnlimited)
                                                <div class="small text-success"><i class="fas fa-infinity mr-1"></i> Unlimited pelanggan</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick License Update Form -->
                            <div class="card border-left-primary shadow-sm mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-edit mr-1"></i> Update Lisensi</span>
                                    <button class="btn btn-sm btn-link p-0" type="button"
                                        data-toggle="collapse" data-target="#licenseUpdateForm">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                                <div class="collapse" id="licenseUpdateForm">
                                    <div class="card-body">
                                        <form action="{{ route('admin.tenants.license.update', $tenant->id) }}" method="POST">
                                            @csrf
                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label class="small font-weight-bold">Plan Lisensi</label>
                                                    <select name="license_plan_id" class="form-control form-control-sm">
                                                        <option value="">-- Tanpa Plan --</option>
                                                        @foreach($licensePlans as $lp)
                                                            <option value="{{ $lp->id }}"
                                                                {{ $tenant->license_plan_id == $lp->id ? 'selected' : '' }}>
                                                                {{ $lp->name }}
                                                                ({{ $lp->isUnlimited() ? 'Unlimited' : $lp->max_customers . ' pelanggan' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label class="small font-weight-bold">Status Lisensi</label>
                                                    <select name="license_status" class="form-control form-control-sm">
                                                        <option value="active" {{ $licenseStatus === 'active' ? 'selected' : '' }}>Active</option>
                                                        <option value="trial" {{ $licenseStatus === 'trial' ? 'selected' : '' }}>Trial</option>
                                                        <option value="suspended" {{ $licenseStatus === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                                        <option value="expired" {{ $licenseStatus === 'expired' ? 'selected' : '' }}>Expired</option>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label class="small font-weight-bold">Tanggal Berakhir</label>
                                                    <input type="date" name="license_expires_at"
                                                        class="form-control form-control-sm"
                                                        value="{{ $tenant->license_expires_at ? $tenant->license_expires_at->format('Y-m-d') : '' }}">
                                                    <small class="text-muted">Kosongkan jika tidak terbatas</small>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save mr-1"></i> Simpan Lisensi
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <h5 class="border-bottom pb-2 mb-3 mt-4">Storage Paths</h5>
                            
                            <div class="mb-3">
                                <strong class="text-primary"><i class="fas fa-lock"></i> Private Storage:</strong>
                                <div class="mt-2">
                                    @foreach($storageStatus['private'] as $key => $status)
                                    <div class="mb-3 p-2 border-left border-primary" style="border-left-width: 3px !important;">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center flex-grow-1">
                                                @if($status['exists'])
                                                    @if($status['writable'])
                                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                                    @else
                                                        <i class="fas fa-exclamation-triangle text-warning mr-2" title="Not Writable"></i>
                                                    @endif
                                                @else
                                                    <i class="fas fa-times-circle text-danger mr-2" title="Not Exists"></i>
                                                @endif
                                                <div class="flex-grow-1">
                                                    <code class="small">{{ $status['path'] }}</code>
                                                    @if($status['exists'])
                                                        <div class="small text-muted mt-1">
                                                            <i class="fas fa-file"></i> {{ $status['file_count'] }} file(s) 
                                                            <span class="mx-1">•</span>
                                                            <i class="fas fa-hdd"></i> {{ $status['size_formatted'] }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong class="text-success"><i class="fas fa-folder-open"></i> Public Storage:</strong>
                                <div class="mt-2">
                                    @foreach($storageStatus['public'] as $key => $status)
                                    <div class="mb-3 p-2 border-left border-success" style="border-left-width: 3px !important;">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center flex-grow-1">
                                                @if($status['exists'])
                                                    @if($status['writable'])
                                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                                    @else
                                                        <i class="fas fa-exclamation-triangle text-warning mr-2" title="Not Writable"></i>
                                                    @endif
                                                @else
                                                    <i class="fas fa-times-circle text-danger mr-2" title="Not Exists"></i>
                                                @endif
                                                <div class="flex-grow-1">
                                                    <code class="small">{{ $status['path'] }}</code>
                                                    @if($status['exists'])
                                                        <div class="small text-muted mt-1">
                                                            <i class="fas fa-file"></i> {{ $status['file_count'] }} file(s) 
                                                            <span class="mx-1">•</span>
                                                            <i class="fas fa-hdd"></i> {{ $status['size_formatted'] }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="alert alert-info small mb-0">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Legend:</strong>
                                <span class="ml-2"><i class="fas fa-check-circle text-success"></i> Exists & Writable</span>
                                <span class="ml-2"><i class="fas fa-exclamation-triangle text-warning"></i> Exists but Not Writable</span>
                                <span class="ml-2"><i class="fas fa-times-circle text-danger"></i> Not Exists</span>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-images"></i> Tenant Assets Management
                            </h5>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Upload gambar untuk kustomisasi branding tenant. File akan disimpan di <code>public/tenants/{{ $tenant->rescode }}/img/</code>
                            </div>

                            <form action="{{ route('admin.tenants.upload-assets', $tenant->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                {{-- Debug Validation Errors --}}
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <strong>Validation Errors:</strong>
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                
                                <div class="row">
                                    <!-- Favicon Upload -->
                                    <div class="col-md-4">
                                        <div class="card mb-3">
                                            <div class="card-body text-center">
                                                <h6 class="card-title">Favicon</h6>
                                                @php
                                                    $faviconPath = public_path("tenants/{$tenant->rescode}/img/favicon.png");
                                                    $faviconExists = file_exists($faviconPath);
                                                @endphp
                                                
                                                @if($faviconExists)
                                                    <img src="{{ asset("tenants/{$tenant->rescode}/img/favicon.png") }}?v={{ time() }}" 
                                                         alt="Favicon" class="img-thumbnail mb-2" style="max-width: 100px;">
                                                    <div class="small text-success">
                                                        <i class="fas fa-check-circle"></i> File exists
                                                    </div>
                                                @else
                                                    <div class="text-muted mb-2">
                                                        <i class="far fa-image fa-3x"></i>
                                                        <div class="small mt-2">No file uploaded</div>
                                                    </div>
                                                @endif
                                                
                                                <div class="mt-3">
                                                    <input type="file" name="favicon" class="form-control-file" accept="image/png,image/x-icon,image/vnd.microsoft.icon">
                                                    <small class="text-muted">PNG or ICO (Max 2MB)</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Login Logo Upload -->
                                    <div class="col-md-4">
                                        <div class="card mb-3">
                                            <div class="card-body text-center">
                                                <h6 class="card-title">Login Logo</h6>
                                                @php
                                                    $loginLogoPath = public_path("tenants/{$tenant->rescode}/img/trikamedia.png");
                                                    $loginLogoExists = file_exists($loginLogoPath);
                                                @endphp
                                                
                                                @if($loginLogoExists)
                                                    <img src="{{ asset("tenants/{$tenant->rescode}/img/trikamedia.png") }}?v={{ time() }}" 
                                                         alt="Login Logo" class="img-thumbnail mb-2" style="max-width: 150px;">
                                                    <div class="small text-success">
                                                        <i class="fas fa-check-circle"></i> File exists
                                                    </div>
                                                @else
                                                    <div class="text-muted mb-2">
                                                        <i class="far fa-image fa-3x"></i>
                                                        <div class="small mt-2">No file uploaded</div>
                                                    </div>
                                                @endif
                                                
                                                <div class="mt-3">
                                                    <input type="file" name="login_logo" class="form-control-file" accept="image/*">
                                                    <small class="text-muted">PNG/JPG (Max 2MB)</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Invoice Logo Upload -->
                                    <div class="col-md-4">
                                        <div class="card mb-3">
                                            <div class="card-body text-center">
                                                <h6 class="card-title">Invoice Logo</h6>
                                                @php
                                                    $invoiceLogoPath = public_path("tenants/{$tenant->rescode}/img/logoinv.png");
                                                    $invoiceLogoExists = file_exists($invoiceLogoPath);
                                                @endphp
                                                
                                                @if($invoiceLogoExists)
                                                    <img src="{{ asset("tenants/{$tenant->rescode}/img/logoinv.png") }}?v={{ time() }}" 
                                                         alt="Invoice Logo" class="img-thumbnail mb-2" style="max-width: 150px;">
                                                    <div class="small text-success">
                                                        <i class="fas fa-check-circle"></i> File exists
                                                    </div>
                                                @else
                                                    <div class="text-muted mb-2">
                                                        <i class="far fa-image fa-3x"></i>
                                                        <div class="small mt-2">No file uploaded</div>
                                                    </div>
                                                @endif
                                                
                                                <div class="mt-3">
                                                    <input type="file" name="invoice_logo" class="form-control-file" accept="image/*">
                                                    <small class="text-muted">PNG/JPG (Max 2MB)</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-3">
                                    <button type="submit" class="btn btn-primary" id="uploadButton">
                                        <i class="fas fa-upload"></i> Upload Assets
                                    </button>
                                </div>
                            </form>
                            
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const form = document.querySelector('form[action*="upload-assets"]');
                                const submitButton = document.getElementById('uploadButton');
                                
                                form.addEventListener('submit', function(e) {
                                    const faviconInput = document.querySelector('input[name="favicon"]');
                                    const loginLogoInput = document.querySelector('input[name="login_logo"]');
                                    const invoiceLogoInput = document.querySelector('input[name="invoice_logo"]');
                                    
                                    const hasFiles = faviconInput.files.length > 0 || 
                                                    loginLogoInput.files.length > 0 || 
                                                    invoiceLogoInput.files.length > 0;
                                    
                                    if (!hasFiles) {
                                        e.preventDefault();
                                        alert('Silakan pilih minimal satu file untuk di-upload!');
                                        return false;
                                    }
                                    
                                    // Show loading state
                                    submitButton.disabled = true;
                                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                                });
                            });
                            </script>
                        </div>
                    </div>

                    {{-- Laravel Log Link --}}
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">Laravel Log</h5>
                            <p class="text-muted small mb-2">
                                <code>storage/logs/tenant_{{ $tenant->rescode }}/laravel.log</code>
                            </p>
                            @php
                                $tenantLogPath   = storage_path('logs/tenant_'.$tenant->rescode.'/laravel.log');
                                $tenantLogExists = file_exists($tenantLogPath);
                            @endphp
                            @if($tenantLogExists)
                            <a href="{{ route('admin.tenants.log', $tenant->id) }}" class="btn btn-sm btn-outline-dark">
                                <i class="fas fa-file-code mr-1"></i> Lihat Laravel Log
                            </a>
                            <span class="ml-2 text-muted small">
                                {{ number_format(filesize($tenantLogPath)/1024, 1) }} KB
                                &bull; {{ date('d M Y H:i', filemtime($tenantLogPath)) }}
                            </span>
                            @else
                            <span class="text-warning small"><i class="fas fa-exclamation-triangle mr-1"></i> File log belum ada</span>
                            @endif
                        </div>
                    </div>

                    @if($tenant->env_variables)
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">Environment Variables</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Key</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tenant->env_variables as $key => $value)
                                        <tr>
                                            <td><code>{{ $key }}</code></td>
                                            <td>
                                                @if(str_contains($key, 'password') || str_contains($key, 'key') || str_contains($key, 'token'))
                                                    <span class="text-muted"><i class="fas fa-lock"></i> ********</span>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====== QUEUE WORKER MONITOR ====== --}}
<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-info">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <i class="fas fa-cogs mr-1"></i>
                        <strong>Queue Worker Monitor</strong>
                        <span class="badge badge-secondary ml-2" id="queue-timestamp">--:--:--</span>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary mr-1" onclick="toggleQueueSettings()" title="Queue Worker Settings">
                            <i class="fas fa-sliders-h"></i> Settings
                        </button>
                        <button class="btn btn-sm btn-outline-info mr-1" onclick="refreshQueueStatus()">
                            <i class="fas fa-sync-alt" id="refresh-icon"></i> Refresh
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="restartWorker()">
                            <i class="fas fa-redo"></i> Restart Worker
                        </button>
                    </div>
                </div>
                <div class="card-body p-0" id="queue-monitor-body">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Memuat status worker...</p>
                    </div>
                </div>
                {{-- Queue Worker Settings Panel --}}
                <div id="queue-settings-panel" class="card-footer border-top" style="display:none; background:#f8f9fa;">
                    <h6 class="mb-3"><i class="fas fa-sliders-h mr-1 text-secondary"></i>Worker Parameters <small class="text-muted">(disimpan ke ENV variable &amp; diupdate di supervisor conf)</small></h6>
                    <div class="row">
                        <div class="col-md-3 col-6">
                            <div class="form-group mb-2">
                                <label style="font-size:0.75rem;" class="text-muted mb-1"><code>--sleep</code> <span class="text-muted">(detik)</span></label>
                                <input type="number" id="qs-sleep" class="form-control form-control-sm" value="3" min="1" max="60">
                                <small class="text-muted">Jeda saat queue kosong</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="form-group mb-2">
                                <label style="font-size:0.75rem;" class="text-muted mb-1"><code>--tries</code></label>
                                <input type="number" id="qs-tries" class="form-control form-control-sm" value="3" min="1" max="20">
                                <small class="text-muted">Maks retry sebelum failed</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="form-group mb-2">
                                <label style="font-size:0.75rem;" class="text-muted mb-1"><code>--timeout</code> <span class="text-muted">(detik)</span></label>
                                <input type="number" id="qs-timeout" class="form-control form-control-sm" value="120" min="30" max="3600">
                                <small class="text-muted">Timeout per job</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="form-group mb-2">
                                <label style="font-size:0.75rem;" class="text-muted mb-1"><code>--max-jobs</code></label>
                                <input type="number" id="qs-maxjobs" class="form-control form-control-sm" value="500" min="10" max="5000">
                                <small class="text-muted">Restart otomatis setelah N job</small>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-1">
                        <button id="qs-save-btn" class="btn btn-sm btn-primary" onclick="saveQueueConfig()">
                            <i class="fas fa-save mr-1"></i> Simpan &amp; Restart Worker
                        </button>
                        <span id="qs-save-msg" class="ml-3 small"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function backupDatabase(tenantId) {
    Swal.fire({
        title: 'Backup Database?',
        html: 'Database tenant ini akan di-backup.<br><small class="text-muted">Proses mungkin memakan waktu beberapa detik...</small>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-database"></i> Ya, Backup!',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            // Get CSRF token from meta tag or fallback to form token
            let csrfToken = '';
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                csrfToken = metaTag.getAttribute('content');
            } else {
                // Fallback: get from hidden input
                const tokenInput = document.querySelector('input[name="_token"]');
                if (tokenInput) {
                    csrfToken = tokenInput.value;
                } else {
                    csrfToken = '{{ csrf_token() }}';
                }
            }
            
            return fetch('/admin/tenants/' + tenantId + '/backup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Backup failed');
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(
                    `Request failed: ${error}`
                );
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Backup Berhasil!',
                html: result.value.message + '<br><small class="text-muted">' + result.value.filename + '</small>',
                confirmButtonColor: '#28a745'
            }).then(() => {
                location.reload();
            });
        }
    });
}
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
const TENANT_ID = {{ $tenant->id }};
const CSRF_TOKEN = '{{ csrf_token() }}';

function statusBadge(status) {
    const map = {
        'RUNNING': '<span class="badge badge-success px-2">RUNNING</span>',
        'STOPPED': '<span class="badge badge-secondary px-2">STOPPED</span>',
        'STARTING': '<span class="badge badge-info px-2">STARTING</span>',
        'BACKOFF': '<span class="badge badge-warning px-2">BACKOFF</span>',
        'EXITED': '<span class="badge badge-danger px-2">EXITED</span>',
        'FATAL': '<span class="badge badge-danger px-2">FATAL</span>',
        'UNKNOWN': '<span class="badge badge-secondary px-2">UNKNOWN</span>',
    };
    return map[status] || `<span class="badge badge-secondary px-2">${status}</span>`;
}

function refreshQueueStatus() {
    const icon = document.getElementById('refresh-icon');
    icon.classList.add('fa-spin');

    fetch(`/admin/tenants/${TENANT_ID}/queue-status`, {
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        icon.classList.remove('fa-spin');
        document.getElementById('queue-timestamp').textContent = data.timestamp;

        const confBadge = data.conf_exists
            ? '<span class="badge badge-success">Config Found</span>'
            : '<span class="badge badge-danger">Config Missing</span>';

        let workersHtml = '';
        if (data.workers.length === 0) {
            workersHtml = `<tr><td colspan="3" class="text-center text-muted">Tidak ada worker ditemukan untuk program <strong>${data.program}</strong></td></tr>`;
        } else {
            data.workers.forEach(w => {
                workersHtml += `<tr>
                    <td><code>${w.name}</code></td>
                    <td>${statusBadge(w.status)}</td>
                    <td class="text-muted small">${w.info}</td>
                </tr>`;
            });
        }

        document.getElementById('queue-monitor-body').innerHTML = `
            <div class="row m-0">
                <div class="col-md-3 border-right text-center py-3">
                    <div class="text-muted small mb-1">Program</div>
                    <strong><code>${data.program}</code></strong><br>
                    <div class="mt-1">${confBadge}</div>
                </div>
                <div class="col-md-3 border-right text-center py-3">
                    <div class="text-muted small mb-1">Pending Jobs</div>
                    <h3 class="${data.pending_jobs > 0 ? 'text-warning' : 'text-success'} mb-0">
                        ${data.pending_jobs}
                    </h3>
                    <small class="text-muted">in queue</small>
                </div>
                <div class="col-md-3 border-right text-center py-3">
                    <div class="text-muted small mb-1">Failed Jobs</div>
                    <h3 class="${data.failed_jobs > 0 ? 'text-danger' : 'text-success'} mb-0">
                        ${data.failed_jobs}
                    </h3>
                    <small class="text-muted">failed</small>
                </div>
                <div class="col-md-3 text-center py-3">
                    <div class="text-muted small mb-1">Workers</div>
                    <h3 class="text-primary mb-0">${data.workers.length}</h3>
                    <small class="text-muted">processes</small>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Process Name</th>
                            <th>Status</th>
                            <th>Info</th>
                        </tr>
                    </thead>
                    <tbody>${workersHtml}</tbody>
                </table>
            </div>`;

        // Populate settings form with current values
        if (data.queue_settings) {
            document.getElementById('qs-sleep').value   = data.queue_settings.sleep;
            document.getElementById('qs-tries').value   = data.queue_settings.tries;
            document.getElementById('qs-timeout').value = data.queue_settings.timeout;
            document.getElementById('qs-maxjobs').value = data.queue_settings.max_jobs;
        }
    })
    .catch(() => {
        icon.classList.remove('fa-spin');
        document.getElementById('queue-monitor-body').innerHTML =
            '<div class="alert alert-danger m-3"><i class="fas fa-exclamation-triangle mr-1"></i>Gagal mengambil status worker.</div>';
    });
}

function toggleQueueSettings() {
    const panel = document.getElementById('queue-settings-panel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function saveQueueConfig() {
    const btn = document.getElementById('qs-save-btn');
    const msg = document.getElementById('qs-save-msg');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
    msg.innerHTML = '';

    const payload = {
        queue_sleep:    parseInt(document.getElementById('qs-sleep').value),
        queue_tries:    parseInt(document.getElementById('qs-tries').value),
        queue_timeout:  parseInt(document.getElementById('qs-timeout').value),
        queue_max_jobs: parseInt(document.getElementById('qs-maxjobs').value),
    };

    fetch(`/admin/tenants/${TENANT_ID}/queue-config`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan & Restart Worker';
        msg.innerHTML = '<span class="text-success"><i class="fas fa-check-circle mr-1"></i>Tersimpan. Worker sedang direstart...</span>';
        setTimeout(() => { msg.innerHTML = ''; refreshQueueStatus(); }, 2500);
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan & Restart Worker';
        msg.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Gagal: ${err}</span>`;
    });
}

function restartWorker() {
    Swal.fire({
        title: 'Restart Queue Worker?',
        html: 'Semua job yang sedang diproses akan dibatalkan.<br><small class="text-muted">Jobs akan diproses ulang setelah worker kembali RUNNING.</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-redo"></i> Ya, Restart!',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/admin/tenants/${TENANT_ID}/queue-restart`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' }
            })
            .then(r => {
                if (!r.ok) throw new Error(`Server error: HTTP ${r.status}`);
                return r.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Gagal: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then(result => {
        if (result.isConfirmed && result.value) {
            Swal.fire({ icon: 'success', title: 'Restarted!', text: result.value.output, confirmButtonColor: '#28a745' })
                .then(() => refreshQueueStatus());
        }
    });
}

// Auto-load on page ready
document.addEventListener('DOMContentLoaded', () => refreshQueueStatus());
// Auto-refresh every 30 seconds
setInterval(refreshQueueStatus, 30000);
</script>

@endsection
