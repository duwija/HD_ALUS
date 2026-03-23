@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-credit-card"></i> Payment Gateway Configuration
                    </h3>
                    <div>
                        <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <form method="POST" action="{{ route('admin.tenants.payment-gateway.update', $tenant->id) }}">
                    @csrf
                    
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
                        
                        <div class="mb-3">
                            <h5>Tenant: <strong>{{ $tenant->app_name }}</strong></h5>
                            <p class="text-muted mb-4">
                                Configure which payment gateways are available for customers on invoice page.
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch custom-control-lg">
                                <input type="hidden" name="payment_bumdes_enabled" value="0">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       id="bumdes_enabled" 
                                       name="payment_bumdes_enabled" 
                                       value="1"
                                       {{ $tenant->payment_bumdes_enabled ? 'checked' : '' }}>
                                <label class="custom-control-label" for="bumdes_enabled">
                                    <i class="fas fa-store text-danger mr-2"></i>
                                    <strong>Bumdes / Payment Point</strong>
                                    <br>
                                    <small class="text-muted ml-4">Enable physical payment point locations</small>
                                </label>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch custom-control-lg">
                                <input type="hidden" name="payment_winpay_enabled" value="0">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       id="winpay_enabled" 
                                       name="payment_winpay_enabled" 
                                       value="1"
                                       {{ $tenant->payment_winpay_enabled ? 'checked' : '' }}>
                                <label class="custom-control-label" for="winpay_enabled">
                                    <i class="fas fa-building-columns text-info mr-2"></i>
                                    <strong>Winpay Gateway</strong>
                                    <br>
                                    <small class="text-muted ml-4">Multi-bank VA & retail outlets (Rp 2.500 fee)</small>
                                </label>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch custom-control-lg">
                                <input type="hidden" name="payment_tripay_enabled" value="0">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       id="tripay_enabled" 
                                       name="payment_tripay_enabled" 
                                       value="1"
                                       {{ $tenant->payment_tripay_enabled ? 'checked' : '' }}>
                                <label class="custom-control-label" for="tripay_enabled">
                                    <i class="fas fa-credit-card text-success mr-2"></i>
                                    <strong>Tripay Gateway</strong>
                                    <br>
                                    <small class="text-muted ml-4">Bank VA, E-Wallet, QRIS & retail (variable fee)</small>
                                </label>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> 
                            Changes will take effect immediately on invoice pages.
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Configuration
                        </button>
                        <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="btn btn-default">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list-check"></i> Current Configuration
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <i class="fas fa-store {{ $tenant->payment_bumdes_enabled ? 'text-success' : 'text-danger' }}"></i>
                            <strong>Bumdes:</strong> 
                            <span class="badge badge-{{ $tenant->payment_bumdes_enabled ? 'success' : 'secondary' }}">
                                {{ $tenant->payment_bumdes_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-building-columns {{ $tenant->payment_winpay_enabled ? 'text-success' : 'text-danger' }}"></i>
                            <strong>Winpay:</strong> 
                            <span class="badge badge-{{ $tenant->payment_winpay_enabled ? 'success' : 'secondary' }}">
                                {{ $tenant->payment_winpay_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-credit-card {{ $tenant->payment_tripay_enabled ? 'text-success' : 'text-danger' }}"></i>
                            <strong>Tripay:</strong> 
                            <span class="badge badge-{{ $tenant->payment_tripay_enabled ? 'success' : 'secondary' }}">
                                {{ $tenant->payment_tripay_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Gateway Information
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Bumdes</dt>
                        <dd class="col-sm-7">Physical payment locations</dd>
                        
                        <dt class="col-sm-5">Winpay</dt>
                        <dd class="col-sm-7">Fixed fee Rp 2.500</dd>
                        
                        <dt class="col-sm-5">Tripay</dt>
                        <dd class="col-sm-7 mb-0">Variable fees by method</dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-warning">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle"></i> Important Notes
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Disabling a gateway will hide it from invoice pages</li>
                        <li>Configuration is per-tenant</li>
                        <li>Cache is cleared automatically after save</li>
                        <li>Changes take effect immediately</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-control-lg .custom-control-label {
    font-size: 1.05rem;
    padding-top: 0.25rem;
}

.custom-control-lg .custom-control-label::before,
.custom-control-lg .custom-control-label::after {
    width: 2.5rem;
    height: 1.5rem;
    border-radius: 3rem;
}

.custom-control-lg .custom-control-label::after {
    width: 1.25rem;
    height: 1.25rem;
}

.custom-control-lg .custom-control-input:checked ~ .custom-control-label::after {
    transform: translateX(1rem);
}
</style>
@endsection
