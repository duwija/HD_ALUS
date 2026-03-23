@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-edit"></i> Edit Tenant</h3>
                    <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.tenants.update', $tenant->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 mb-3">Informasi Umum</h5>
                                
                                <div class="form-group">
                                    <label for="domain">Domain <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('domain') is-invalid @enderror" 
                                           id="domain" 
                                           name="domain" 
                                           value="{{ old('domain', $tenant->domain) }}" 
                                           required
                                           placeholder="contoh.alus.co.id">
                                    @error('domain')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="app_name">App Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('app_name') is-invalid @enderror" 
                                           id="app_name" 
                                           name="app_name" 
                                           value="{{ old('app_name', $tenant->app_name) }}" 
                                           required
                                           placeholder="PT CONTOH INTERNET">
                                    @error('app_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="signature">Signature</label>
                                    <input type="text" 
                                           class="form-control @error('signature') is-invalid @enderror" 
                                           id="signature" 
                                           name="signature" 
                                           value="{{ old('signature', $tenant->signature) }}"
                                           placeholder="Hormat kami, Tim IT">
                                    @error('signature')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="rescode">Rescode <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control bg-light" 
                                           id="rescode" 
                                           value="{{ $tenant->rescode }}" 
                                           disabled
                                           title="Rescode tidak bisa diubah">
                                    <small class="form-text text-muted">Rescode tidak dapat diubah setelah tenant dibuat</small>
                                </div>

                                <div class="form-group">
                                    <label for="mail_from">Email From <span class="text-danger">*</span></label>
                                    <input type="email" 
                                           class="form-control @error('mail_from') is-invalid @enderror" 
                                           id="mail_from" 
                                           name="mail_from" 
                                           value="{{ old('mail_from', $tenant->mail_from) }}" 
                                           required
                                           placeholder="no-reply@contoh.alus.co.id">
                                    @error('mail_from')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 mb-3">Konfigurasi Database</h5>
                                
                                <div class="form-group">
                                    <label for="db_host">DB Host <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('db_host') is-invalid @enderror" 
                                           id="db_host" 
                                           name="db_host" 
                                           value="{{ old('db_host', $tenant->db_host) }}" 
                                           required>
                                    @error('db_host')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="db_port">DB Port <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('db_port') is-invalid @enderror" 
                                           id="db_port" 
                                           name="db_port" 
                                           value="{{ old('db_port', $tenant->db_port) }}" 
                                           required>
                                    @error('db_port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="db_database">DB Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('db_database') is-invalid @enderror" 
                                           id="db_database" 
                                           name="db_database" 
                                           value="{{ old('db_database', $tenant->db_database) }}" 
                                           required>
                                    @error('db_database')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="db_username">DB Username <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('db_username') is-invalid @enderror" 
                                           id="db_username" 
                                           name="db_username" 
                                           value="{{ old('db_username', $tenant->db_username) }}" 
                                           required>
                                    @error('db_username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="db_password">DB Password</label>
                                    <input type="password" 
                                           class="form-control @error('db_password') is-invalid @enderror" 
                                           id="db_password" 
                                           name="db_password" 
                                           placeholder="Kosongkan jika tidak ingin mengubah">
                                    <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                    @error('db_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 mb-3">Features</h5>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="features[accounting]" 
                                           id="accounting" 
                                           value="1"
                                           {{ (old('features.accounting', $tenant->features['accounting'] ?? false)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="accounting">
                                        <i class="fas fa-calculator"></i> Accounting
                                    </label>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="features[ticketing]" 
                                           id="ticketing" 
                                           value="1"
                                           {{ (old('features.ticketing', $tenant->features['ticketing'] ?? false)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ticketing">
                                        <i class="fas fa-ticket-alt"></i> Ticketing
                                    </label>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="features[whatsapp]" 
                                           id="whatsapp" 
                                           value="1"
                                           {{ (old('features.whatsapp', $tenant->features['whatsapp'] ?? false)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="whatsapp">
                                        <i class="fab fa-whatsapp"></i> WhatsApp Integration
                                    </label>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="features[payment_gateway]" 
                                           id="payment_gateway" 
                                           value="1"
                                           {{ (old('features.payment_gateway', $tenant->features['payment_gateway'] ?? false)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="payment_gateway">
                                        <i class="fas fa-credit-card"></i> Payment Gateway
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-cogs"></i> Custom Environment Variables
                                    <small class="text-muted">(Override global .env per tenant)</small>
                                </h5>
                                
                                <div id="env-variables-container">
                                    @php
                                        $envVars = old('env_variables', $tenant->env_variables ?? []);
                                    @endphp
                                    
                                    @if(count($envVars) > 0)
                                        @foreach($envVars as $key => $value)
                                            <div class="row mb-2 env-variable-row">
                                                <div class="col-md-4">
                                                    <input type="text" 
                                                           class="form-control form-control-sm env-key-input" 
                                                           name="env_variables_keys[]" 
                                                           value="{{ $key }}"
                                                           placeholder="VARIABLE_NAME">
                                                </div>
                                                <div class="col-md-7">
                                                    <textarea class="form-control form-control-sm env-value-textarea" 
                                                              name="env_variables_values[]" 
                                                              rows="1"
                                                              placeholder="value"
                                                              style="resize: vertical; min-height: 31px;">{{ $value }}</textarea>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-sm btn-danger remove-env-var">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <button type="button" class="btn btn-sm btn-success" id="add-env-var">
                                    <i class="fas fa-plus"></i> Tambah Variable
                                </button>
                                <button type="button" class="btn btn-sm btn-info" id="toggle-env-list">
                                    <i class="fas fa-list"></i> Lihat Daftar Variables
                                </button>
                                
                                <div class="alert alert-info mt-3 mb-0">
                                    <small>
                                        <strong>Priority:</strong> Database JSON → Global .env → Default value
                                    </small>
                                </div>

                                <!-- Daftar Variables yang Dapat Ditambahkan (Initially Hidden) -->
                                <div class="card mt-3" id="env-variables-reference" style="display: none;">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">
                                            <i class="fas fa-list"></i> Daftar Environment Variables yang Tersedia
                                            <button type="button" class="close" id="close-env-list">
                                                <span>&times;</span>
                                            </button>
                                        </h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <!-- Payment Gateway: Tripay -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-primary"><i class="fas fa-credit-card"></i> Tripay Gateway</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>TRIPAY_ENDPOINT</code> - API endpoint Tripay</li>
                                                    <li><code>TRIPAY_APIKEY</code> - API Key Tripay</li>
                                                    <li><code>TRIPAY_PRIVATEKEY</code> - Private Key Tripay</li>
                                                    <li><code>TRIPAY_MERCHANTCODE</code> - Merchant Code Tripay</li>
                                                </ul>
                                            </div>

                                            <!-- Payment Gateway: Winpay -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-info"><i class="fas fa-building-columns"></i> Winpay Gateway</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>WINPAY_ENDPOINT</code> - API endpoint Winpay</li>
                                                    <li><code>WINPAY_KEY</code> - API Key Winpay</li>
                                                    <li><code>WINPAY_SECRET</code> - Secret Key Winpay</li>
                                                    <li><code>winpay_fee</code> - Biaya transaksi Winpay (numeric, optional)</li>
                                                </ul>
                                            </div>

                                            <!-- Payment Gateway: Duitku -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-warning"><i class="fas fa-wallet"></i> Duitku Gateway</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>DUITKU_MERCHANT_CODE</code> - Merchant Code Duitku</li>
                                                    <li><code>DUITKU_API_KEY</code> - API Key Duitku</li>
                                                    <li><code>DUITKU_SANDBOX</code> - Mode sandbox: <code>true</code> / <code>false</code></li>
                                                </ul>
                                            </div>

                                            <!-- Kas Bank Online Payment -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-secondary"><i class="fas fa-university"></i> Kas Bank Pembayaran Online</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li>
                                                        <code>PAYMENT_POINT_TRIPAY</code> - Kode akun kas bank untuk Tripay
                                                        <br><span class="text-muted">Contoh: <code>1-10403</code></span>
                                                    </li>
                                                    <li class="mt-1">
                                                        <code>PAYMENT_POINT_DUITKU</code> - Kode akun kas bank untuk Duitku
                                                        <br><span class="text-muted">Contoh: <code>1-10403</code></span>
                                                    </li>
                                                    <li class="mt-1">
                                                        <code>PAYMENT_POINT_WINPAY</code> - Kode akun kas bank untuk Winpay
                                                        <br><span class="text-muted">Contoh: <code>1-10040</code></span>
                                                    </li>
                                                </ul>
                                            </div>

                                            <!-- Payment Gateway: Xendit -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-success"><i class="fas fa-wallet"></i> Xendit Gateway</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>XENDIT_KEY</code> - Secret Key Xendit</li>
                                                    <li><code>XENDIT_CALLBACK_KEY</code> - Callback Key Xendit</li>
                                                    <li><code>XENDIT_FEE</code> - Admin fee Xendit (numeric)</li>
                                                </ul>
                                            </div>

                                            <!-- WhatsApp Integration -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-success"><i class="fab fa-whatsapp"></i> WhatsApp Integration</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>wa_provider</code> - Provider WA: <code>gateway</code> (default) atau <code>qontak</code></li>
                                                    <li><code>WAPISENDER_USER</code> - Username RuangWA</li>
                                                    <li><code>WAPISENDER_KEY</code> - API Key RuangWA</li>
                                                    <li><code>WAPISENDER_GROUPPAYMENT</code> - Group ID Payment</li>
                                                    <li><code>WAPISENDER_GROUPTICKET</code> - Group ID Ticket</li>
                                                    <li><code>WAPISENDER_STATUS</code> - Status (enable/disable)</li>
                                                    <li><code>WA_GATEWAY_URL</code> - WA Gateway URL</li>
                                                    <li><code>WA_GROUP_PAYMENT</code> - WhatsApp Group Payment</li>
                                                    <li><code>WA_GROUP_SUPPORT</code> - WhatsApp Group Support</li>
                                                    <li><code>payment_wa</code> - Nomor WA CS untuk info pembayaran di invoice</li>
                                                    <li>
                                                        <code>whatsapp_noc</code> - Nomor WA NOC/Support untuk tombol "Buat Laporan" di portal pelanggan
                                                        <span class="badge badge-success badge-sm">Aktif</span>
                                                        <br>
                                                        <span class="text-muted">
                                                            Format: <code>6281234567890</code> atau <code>081234567890</code> (otomatis dikonversi).
                                                            Jika tidak diisi, tombol "Buat Laporan via WhatsApp" tidak akan muncul di halaman laporan tiket pelanggan.
                                                        </span>
                                                    </li>
                                                    <li><code>ACCESS_TOKEN</code> - Qontak Access Token (jika pakai qontak)</li>
                                                    <li><code>WHATSAPP_API_URL</code> - Qontak API URL (jika pakai qontak)</li>
                                                    <li><code>WA_CHANNEL_INTEGRATION_ID</code> - Qontak Channel ID (jika pakai qontak)</li>
                                                    <li><code>WA_TAMPLATE_ID_4</code> - Qontak Template ID untuk reminder (jika pakai qontak)</li>
                                                </ul>
                                            </div>

                                            <!-- Email Configuration -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-danger"><i class="fas fa-envelope"></i> Email Configuration</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>MAIL_MAILER</code> - smtp, sendmail, etc</li>
                                                    <li><code>MAIL_HOST</code> - SMTP host</li>
                                                    <li><code>MAIL_PORT</code> - SMTP port</li>
                                                    <li><code>MAIL_USERNAME</code> - SMTP username</li>
                                                    <li><code>MAIL_PASSWORD</code> - SMTP password</li>
                                                    <li><code>MAIL_ENCRYPTION</code> - tls/ssl</li>
                                                    <li><code>MAIL_FROM_ADDRESS</code> - From email</li>
                                                    <li><code>MAIL_FROM_NAME</code> - From name</li>
                                                </ul>
                                            </div>

                                            <!-- Google Maps -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-warning"><i class="fas fa-map-marked-alt"></i> Google Maps API</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>GOOGLE_MAPS_API_KEY</code> - Google Maps API Key</li>
                                                </ul>
                                            </div>

                                            <!-- Monitoring -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-primary"><i class="fas fa-heartbeat"></i> Monitoring Configuration</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>probe_key</code> - API Key untuk Probe monitoring</li>
                                                </ul>
                                            </div>

                                            <!-- PPPoE / Network Configuration -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-danger"><i class="fas fa-network-wired"></i> PPPoE / Network Configuration</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li>
                                                        <code>pppoe_password</code> - Password default PPPoE saat buat pelanggan baru
                                                        <span class="badge badge-success badge-sm">Aktif</span>
                                                        <br>
                                                        <span class="text-muted">Contoh: <code>isp@12345</code> &nbsp;|&nbsp; Digunakan sebagai nilai default field PPPOE Password di form tambah customer.</span>
                                                    </li>
                                                    <li class="mt-1">
                                                        <code>router_host</code> - IP/hostname Mikrotik router utama
                                                    </li>
                                                    <li>
                                                        <code>router_username</code> - Username login Mikrotik
                                                    </li>
                                                    <li>
                                                        <code>router_password</code> - Password login Mikrotik
                                                    </li>
                                                    <li>
                                                        <code>coordinate_center</code> - Koordinat pusat peta tenant
                                                        <br>
                                                        <span class="text-muted">Format: <code>-8.5598, 115.1057</code></span>
                                                    </li>
                                                </ul>
                                            </div>

                                            <!-- Company Information -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-secondary"><i class="fas fa-building"></i> Company Information</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>COMPANY_ADDRESS1</code> - Alamat baris 1 <span class="badge badge-info badge-sm">multiline</span></li>
                                                    <li><code>COMPANY_ADDRESS2</code> - Alamat baris 2 <span class="badge badge-info badge-sm">multiline</span></li>
                                                    <li><code>SIGNATURE</code> - Signature untuk invoice <span class="badge badge-info badge-sm">multiline</span></li>
                                                    <li><code>INV_NOTE</code> - Catatan invoice footer <span class="badge badge-info badge-sm">multiline</span></li>
                                                </ul>
                                            </div>

                                            <!-- Accounting IDs -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-dark"><i class="fas fa-calculator"></i> Accounting Configuration</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>IKHTISAR_LABA_RUGI_ID</code> - ID akun ikhtisar</li>
                                                    <li><code>MODAL_ID</code> - ID akun modal</li>
                                                    <li><code>DEVIDEN_ID</code> - ID akun deviden</li>
                                                </ul>
                                            </div>

                                            <!-- FTP Configuration -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-info"><i class="fas fa-server"></i> FTP Configuration</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>FTP_USER</code> - Username FTP untuk backup</li>
                                                    <li><code>FTP_PASSWORD</code> - Password FTP untuk backup</li>
                                                    <li><code>FTP_HOST</code> - Host FTP server (optional)</li>
                                                    <li><code>FTP_PORT</code> - Port FTP server (optional, default: 21)</li>
                                                </ul>
                                                <p class="small text-muted ml-3 mb-0">
                                                    <i class="fas fa-info-circle"></i> Digunakan untuk automated backup database ke remote FTP server, file sync, dan disaster recovery.
                                                </p>
                                            </div>

                                            <!-- Notification Delay -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-warning"><i class="fas fa-clock"></i> Notification Delay</h6>
                                                <ul class="list-unstyled ml-3 small">
                                                    <li><code>NOTIF_DELAY_MIN</code> - Delay minimum antar pesan <span class="text-muted">(detik, default: 180 = 3 menit)</span></li>
                                                    <li><code>NOTIF_DELAY_MAX</code> - Delay maximum antar pesan <span class="text-muted">(detik, default: 360 = 6 menit)</span></li>
                                                    <li><code>NOTIF_LONG_PAUSE_EVERY</code> - Long pause setiap N pesan <span class="text-muted">(default: 20)</span></li>
                                                    <li><code>NOTIF_LONG_PAUSE_EXTRA</code> - Extra delay saat long pause <span class="text-muted">(detik, default: 600 = 10 menit)</span></li>
                                                </ul>
                                                <p class="small text-muted ml-3 mb-0">
                                                    <i class="fas fa-info-circle"></i> Digunakan untuk mengatur kecepatan kirim notifikasi WA agar tidak terkena rate-limit gateway.
                                                </p>
                                            </div>

                                            <!-- Custom Variables -->
                                            <div class="col-md-6 mb-0">
                                                <h6 class="text-muted"><i class="fas fa-cog"></i> Custom Variables</h6>
                                                <p class="small ml-3 mb-0">
                                                    Anda dapat menambahkan variable custom lainnya sesuai kebutuhan tenant.
                                                    Gunakan format <code>UPPERCASE_WITH_UNDERSCORE</code> untuk konsistensi.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="alert alert-success mt-3 mb-2">
                                            <i class="fas fa-info-circle"></i> 
                                            <small>
                                                <strong>Support Multiline:</strong> 
                                                Field value menggunakan textarea yang support newline (Enter). 
                                                Textarea akan auto-expand sesuai content. Cocok untuk alamat, catatan, atau signature.
                                            </small>
                                        </div>

                                        <div class="alert alert-warning mt-2 mb-0">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            <small>
                                                <strong>Catatan Keamanan:</strong> 
                                                Credentials sensitif seperti API keys disimpan di database terpisah (isp_master). 
                                                Pastikan hanya admin yang memiliki akses.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2 mb-3">Status & Catatan</h5>
                                
                                <div class="form-group">
                                    <label for="is_active">Status</label>
                                    <select class="form-control" id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', $tenant->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active', $tenant->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="notes">Catatan</label>
                                    <textarea class="form-control" 
                                              id="notes" 
                                              name="notes" 
                                              rows="4"
                                              placeholder="Catatan tambahan tentang tenant ini">{{ old('notes', $tenant->notes) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Tenant
                            </button>
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
    // Toggle environment variables reference list
    const toggleBtn = document.getElementById('toggle-env-list');
    const closeBtn = document.getElementById('close-env-list');
    const envList = document.getElementById('env-variables-reference');
    
    if (toggleBtn && envList) {
        toggleBtn.addEventListener('click', function() {
            if (envList.style.display === 'none') {
                envList.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Sembunyikan Daftar';
                toggleBtn.classList.remove('btn-info');
                toggleBtn.classList.add('btn-secondary');
            } else {
                envList.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-list"></i> Lihat Daftar Variables';
                toggleBtn.classList.remove('btn-secondary');
                toggleBtn.classList.add('btn-info');
            }
        });
        
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                envList.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-list"></i> Lihat Daftar Variables';
                toggleBtn.classList.remove('btn-secondary');
                toggleBtn.classList.add('btn-info');
            });
        }
    }

    // Auto-expand textarea on input
    function autoExpandTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.max(31, textarea.scrollHeight) + 'px';
    }

    // Apply auto-expand to all existing textareas
    document.querySelectorAll('.env-value-textarea').forEach(function(textarea) {
        autoExpandTextarea(textarea);
        textarea.addEventListener('input', function() {
            autoExpandTextarea(this);
        });
    });

    // Add new environment variable row
    document.getElementById('add-env-var').addEventListener('click', function() {
        const container = document.getElementById('env-variables-container');
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2 env-variable-row';
        newRow.innerHTML = `
            <div class="col-md-4">
                <input type="text" 
                       class="form-control form-control-sm env-key-input" 
                       name="env_variables_keys[]" 
                       placeholder="VARIABLE_NAME">
            </div>
            <div class="col-md-7">
                <textarea class="form-control form-control-sm env-value-textarea" 
                          name="env_variables_values[]" 
                          rows="1"
                          placeholder="value"
                          style="resize: vertical; min-height: 31px;"></textarea>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-danger remove-env-var">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
        
        // Add auto-expand to the new textarea
        const newTextarea = newRow.querySelector('.env-value-textarea');
        newTextarea.addEventListener('input', function() {
            autoExpandTextarea(this);
        });
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
