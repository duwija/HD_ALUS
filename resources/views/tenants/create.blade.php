@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-plus-circle"></i> Tambah Tenant Baru
                    </h3>
                    <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.tenants.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <strong>Informasi Dasar</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Domain <span class="text-danger">*</span></label>
                                            <input type="text" name="domain" class="form-control" 
                                                   placeholder="example.com" value="{{ old('domain') }}" required>
                                            <small class="form-text text-muted">Domain untuk mengakses tenant</small>
                                        </div>

                                        <div class="form-group">
                                            <label>App Name <span class="text-danger">*</span></label>
                                            <input type="text" name="app_name" class="form-control" 
                                                   placeholder="PT Example ISP" value="{{ old('app_name') }}" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Signature</label>
                                            <input type="text" name="signature" class="form-control" 
                                                   placeholder="Example Network Provider" value="{{ old('signature') }}">
                                            <small class="form-text text-muted">Kosongkan untuk menggunakan App Name</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Rescode <span class="text-danger">*</span></label>
                                            <input type="text" name="rescode" class="form-control" 
                                                   placeholder="EX" value="{{ old('rescode') }}" 
                                                   maxlength="10" required style="text-transform: uppercase;">
                                            <small class="form-text text-muted">2-3 huruf unik (akan digunakan untuk nama folder)</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Email From</label>
                                            <input type="email" name="mail_from" class="form-control" 
                                                   placeholder="noreply@example.com" value="{{ old('mail_from') }}">
                                            <small class="form-text text-muted">Email pengirim untuk tenant ini</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <strong>Konfigurasi Database</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Database Name <span class="text-danger">*</span></label>
                                            <input type="text" name="db_database" class="form-control" 
                                                   placeholder="example_db" value="{{ old('db_database') }}" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Database Host</label>
                                            <input type="text" name="db_host" class="form-control" 
                                                   placeholder="127.0.0.1" value="{{ old('db_host', '127.0.0.1') }}">
                                        </div>

                                        <div class="form-group">
                                            <label>Database Port</label>
                                            <input type="number" name="db_port" class="form-control" 
                                                   placeholder="3306" value="{{ old('db_port', '3306') }}">
                                        </div>

                                        <div class="form-group">
                                            <label>Database Username <span class="text-danger">*</span></label>
                                            <input type="text" name="db_username" class="form-control" 
                                                   placeholder="root" value="{{ old('db_username', 'root') }}" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Database Password <span class="text-danger">*</span></label>
                                            <input type="password" name="db_password" class="form-control" 
                                                   placeholder="Password" required>
                                            <small class="form-text text-muted">Password akan di-encrypt otomatis</small>
                                        </div>

                                        <div class="form-check">
                                            <input type="checkbox" name="create_database" class="form-check-input" 
                                                   id="create_database" value="1" checked>
                                            <label class="form-check-label" for="create_database">
                                                <strong>Create database dan import struktur otomatis</strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-copy"></i> Struktur tabel akan di-clone dari database <code>kencana</code> (model/template default).
                                                    Hanya struktur yang di-copy, data tidak ikut.
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <strong>Features</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="feature_accounting" class="form-check-input" 
                                                   id="feature_accounting" value="1" checked>
                                            <label class="form-check-label" for="feature_accounting">
                                                Accounting
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="feature_ticketing" class="form-check-input" 
                                                   id="feature_ticketing" value="1" checked>
                                            <label class="form-check-label" for="feature_ticketing">
                                                Ticketing
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="feature_whatsapp" class="form-check-input" 
                                                   id="feature_whatsapp" value="1" checked>
                                            <label class="form-check-label" for="feature_whatsapp">
                                                WhatsApp
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="feature_payment" class="form-check-input" 
                                                   id="feature_payment" value="1" checked>
                                            <label class="form-check-label" for="feature_payment">
                                                Payment Gateway
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <strong><i class="fas fa-cogs"></i> Custom Environment Variables (Optional)</strong>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">
                                    Tambahkan environment variables yang spesifik untuk tenant ini. 
                                    Priority: <strong>Database JSON → Global .env → Default value</strong>
                                </p>
                                
                                <div id="env-variables-container">
                                    <!-- ENV variables akan ditambahkan di sini -->
                                </div>

                                <button type="button" class="btn btn-sm btn-success" id="add-env-var">
                                    <i class="fas fa-plus"></i> Tambah Variable
                                </button>
                                
                                <div class="alert alert-info mt-3 mb-0">
                                    <small>
                                        <strong>Contoh:</strong> MARKETING_EMAIL, WHATSAPP_TOKEN, WHATSAPP_NUMBER, XENDIT_SECRET, XENDIT_PUBLIC_KEY, SMTP_USERNAME, SMTP_PASSWORD, FTP_USER, FTP_PASSWORD, dll.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-secondary text-white">
                                <strong>Catatan</strong>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-0">
                                    <textarea name="notes" class="form-control" rows="3" 
                                              placeholder="Catatan tambahan untuk tenant ini...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Simpan Tenant
                            </button>
                            <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>

                        <div class="alert alert-warning">
                            <strong><i class="fas fa-exclamation-triangle"></i> Perhatian:</strong>
                            Setelah tenant dibuat, jangan lupa untuk:
                            <ol class="mb-0 mt-2">
                                <li>Setup nginx configuration</li>
                                <li>Generate SSL certificate dengan Let's Encrypt</li>
                                <li>Pointing DNS domain ke server</li>
                            </ol>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
document.addEventListener('DOMContentLoaded', function() {
    function addEnvVarRow(key, value) {
        const container = document.getElementById('env-variables-container');
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2 env-variable-row';
        newRow.innerHTML = `
            <div class="col-md-4">
                <input type="text" 
                       class="form-control form-control-sm" 
                       name="env_variables_keys[]" 
                       placeholder="VARIABLE_NAME"
                       value="${key || ''}">
            </div>
            <div class="col-md-7">
                <input type="text" 
                       class="form-control form-control-sm" 
                       name="env_variables_values[]" 
                       placeholder="value"
                       value="${value || ''}">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-danger remove-env-var">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
    }

    // Add new environment variable row
    document.getElementById('add-env-var').addEventListener('click', function() {
        addEnvVarRow('', '');
    });

    // Remove environment variable row (event delegation)
    document.getElementById('env-variables-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-env-var') || e.target.parentElement.classList.contains('remove-env-var')) {
            const row = e.target.closest('.env-variable-row');
            row.remove();
        }
    });
});
</script>

@endsection
