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
                        <a href="{{ route('admin.tenants.backups', $tenant->id) }}" class="btn btn-primary btn-sm" title="Backups">
                            <i class="fas fa-folder-open"></i> <span class="d-none d-lg-inline">Backups</span>
                        </a>
                        <a href="{{ route('admin.tenants.payment-gateway', $tenant->id) }}" class="btn btn-info btn-sm" title="Payment Gateway">
                            <i class="fas fa-credit-card"></i> <span class="d-none d-lg-inline">Payment</span>
                        </a>
                        <a href="{{ route('admin.tenants.edit', $tenant->id) }}" class="btn btn-warning btn-sm" title="Edit">
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
@endsection
