# Payment Gateway Admin Panel Configuration

## File yang perlu dibuat (Future Enhancement)

### 1. Route
File: `routes/web.php`

```php
// Payment Gateway Configuration
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/payment-gateway', [AdminController::class, 'paymentGatewayConfig'])->name('admin.payment.gateway');
    Route::post('/admin/payment-gateway', [AdminController::class, 'updatePaymentGatewayConfig'])->name('admin.payment.gateway.update');
});
```

### 2. Controller Method
File: `app/Http/Controllers/AdminController.php`

```php
use App\Tenant;

/**
 * Show payment gateway configuration form
 */
public function paymentGatewayConfig()
{
    // Get current tenant
    $currentDomain = request()->getHost();
    $tenant = Tenant::where('domain', $currentDomain)->first();
    
    if (!$tenant) {
        abort(404, 'Tenant not found');
    }
    
    return view('admin.payment-gateway-config', compact('tenant'));
}

/**
 * Update payment gateway configuration
 */
public function updatePaymentGatewayConfig(Request $request)
{
    $validated = $request->validate([
        'payment_bumdes_enabled' => 'required|in:0,1',
        'payment_winpay_enabled' => 'required|in:0,1',
        'payment_tripay_enabled' => 'required|in:0,1',
    ]);
    
    $currentDomain = request()->getHost();
    $tenant = Tenant::where('domain', $currentDomain)->first();
    
    if (!$tenant) {
        return redirect()->back()->with('error', 'Tenant not found');
    }
    
    $tenant->update([
        'payment_bumdes_enabled' => $request->payment_bumdes_enabled,
        'payment_winpay_enabled' => $request->payment_winpay_enabled,
        'payment_tripay_enabled' => $request->payment_tripay_enabled,
    ]);
    
    // Clear config cache
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
    
    return redirect()->back()->with('success', 'Payment gateway configuration updated successfully!');
}
```

### 3. View (Blade Template)
File: `resources/views/admin/payment-gateway-config.blade.php`

```blade
@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment Gateway Configuration</h3>
                </div>
                
                <form method="POST" action="{{ route('admin.payment.gateway.update') }}">
                    @csrf
                    
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        
                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif
                        
                        <p class="text-muted">
                            Configure which payment gateways are available for customers on invoice page.
                        </p>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="payment_bumdes_enabled" value="0">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       id="bumdes_enabled" 
                                       name="payment_bumdes_enabled" 
                                       value="1"
                                       {{ $tenant->payment_bumdes_enabled ? 'checked' : '' }}>
                                <label class="custom-control-label" for="bumdes_enabled">
                                    <strong>Bumdes / Payment Point</strong>
                                    <br>
                                    <small class="text-muted">Enable physical payment point locations</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="payment_winpay_enabled" value="0">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       id="winpay_enabled" 
                                       name="payment_winpay_enabled" 
                                       value="1"
                                       {{ $tenant->payment_winpay_enabled ? 'checked' : '' }}>
                                <label class="custom-control-label" for="winpay_enabled">
                                    <strong>Winpay Gateway</strong>
                                    <br>
                                    <small class="text-muted">Multi-bank VA & retail outlets (Rp 2.500 fee)</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="payment_tripay_enabled" value="0">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       id="tripay_enabled" 
                                       name="payment_tripay_enabled" 
                                       value="1"
                                       {{ $tenant->payment_tripay_enabled ? 'checked' : '' }}>
                                <label class="custom-control-label" for="tripay_enabled">
                                    <strong>Tripay Gateway</strong>
                                    <br>
                                    <small class="text-muted">Bank VA, E-Wallet, QRIS & retail (variable fee)</small>
                                </label>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Changes will take effect immediately on invoice pages.
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Configuration
                        </button>
                        <a href="{{ url()->previous() }}" class="btn btn-default">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Current Configuration</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-store {{ $tenant->payment_bumdes_enabled ? 'text-success' : 'text-danger' }}"></i>
                            Bumdes: 
                            <strong>{{ $tenant->payment_bumdes_enabled ? 'Enabled' : 'Disabled' }}</strong>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-building-columns {{ $tenant->payment_winpay_enabled ? 'text-success' : 'text-danger' }}"></i>
                            Winpay: 
                            <strong>{{ $tenant->payment_winpay_enabled ? 'Enabled' : 'Disabled' }}</strong>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-credit-card {{ $tenant->payment_tripay_enabled ? 'text-success' : 'text-danger' }}"></i>
                            Tripay: 
                            <strong>{{ $tenant->payment_tripay_enabled ? 'Enabled' : 'Disabled' }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Gateway Information</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Bumdes</dt>
                        <dd class="col-sm-8">Physical payment locations</dd>
                        
                        <dt class="col-sm-4">Winpay</dt>
                        <dd class="col-sm-8">Fixed fee Rp 2.500</dd>
                        
                        <dt class="col-sm-4">Tripay</dt>
                        <dd class="col-sm-8">Variable fees by method</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

### 4. Model Update
File: `app/Tenant.php`

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'domain',
        'app_name',
        'signature',
        'rescode',
        'db_host',
        'db_port',
        'db_database',
        'db_username',
        'db_password',
        'mail_from',
        'whatsapp_token',
        'xendit_key',
        'features',
        'payment_bumdes_enabled',  // Add this
        'payment_winpay_enabled',   // Add this
        'payment_tripay_enabled',   // Add this
        'is_active',
        'notes',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'payment_bumdes_enabled' => 'integer',
        'payment_winpay_enabled' => 'integer',
        'payment_tripay_enabled' => 'integer',
    ];
}
```

### 5. Navigation Menu
Add to sidebar menu:

```blade
<li class="nav-item">
    <a href="{{ route('admin.payment.gateway') }}" class="nav-link">
        <i class="nav-icon fas fa-credit-card"></i>
        <p>Payment Gateways</p>
    </a>
</li>
```

## API Alternative (Optional)

If you prefer API approach:

```php
// Route
Route::post('/api/tenant/payment-config', [TenantApiController::class, 'updatePaymentConfig']);

// Controller
public function updatePaymentConfig(Request $request)
{
    $tenant = auth()->user()->tenant; // or get from middleware
    
    $tenant->update([
        'payment_bumdes_enabled' => $request->bumdes ?? 1,
        'payment_winpay_enabled' => $request->winpay ?? 1,
        'payment_tripay_enabled' => $request->tripay ?? 1,
    ]);
    
    return response()->json(['success' => true]);
}
```

## Notes
- Middleware `auth` dan `admin` harus sudah ada
- Sesuaikan dengan struktur admin panel yang sudah ada
- Cache clearing otomatis setelah update
- Switch UI menggunakan Bootstrap 4 custom-switch
