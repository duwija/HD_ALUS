@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">

        {{-- ── FORM KIRI ──────────────────────────────────────────── --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-credit-card"></i> Payment Gateway
                        <small class="text-muted">/ {{ $tenant->app_name }}</small>
                    </h3>
                    <a href="{{ route('admin.tenants.payment-points', $tenant->id) }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-store"></i> Kelola Lokasi Bayar (Bumdes)
                    </a>
                    <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="btn btn-default btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <form method="POST" action="{{ route('admin.tenants.payment-gateway.update', $tenant->id) }}">
                    @csrf

                    <div class="card-body">

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle"></i> {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            </div>
                        @endif

                        @if(isset($paymentGatewayTableReady) && !$paymentGatewayTableReady)
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Tabel <code>payment_gateways</code> belum ada di database tenant ini. Jalankan migrasi tenant terlebih dahulu.
                            </div>
                        @elseif($gateways->isEmpty())
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Provider payment gateway belum terisi. Jalankan: <code>php artisan db:seed --class=PaymentGatewaySeeder</code>
                            </div>
                        @else

                        @foreach($gateways as $provider => $gw)
                        <div class="card mb-3 border-{{ $gw->enabled ? 'success' : 'secondary' }}">
                            <div class="card-body py-3">
                                <div class="row align-items-center">

                                    {{-- Toggle enabled --}}
                                    <div class="col-auto pr-0">
                                        <div class="custom-control custom-switch">
                                            <input type="hidden"   name="providers[{{ $provider }}][enabled]" value="0">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="enabled_{{ $provider }}"
                                                   name="providers[{{ $provider }}][enabled]"
                                                   value="1"
                                                   {{ $gw->enabled ? 'checked' : '' }}
                                                   onchange="toggleRow('{{ $provider }}', this.checked)">
                                            <label class="custom-control-label" for="enabled_{{ $provider }}"></label>
                                        </div>
                                    </div>

                                    {{-- Ikon + Nama --}}
                                    <div class="col-auto">
                                        <i class="{{ $gw->icon }} fa-lg" style="width:20px; text-align:center;"></i>
                                    </div>
                                    <div class="col">
                                        <strong>{{ $gw->label }}</strong>
                                        <small class="text-muted d-block">
                                            {{ optional(json_decode($gw->settings, true))['subtitle'] ?? '' }}
                                        </small>
                                    </div>

                                    {{-- Sort order --}}
                                    <div class="col-md-2">
                                        <label class="small text-muted mb-0">Urutan</label>
                                        <input type="number" class="form-control form-control-sm"
                                               name="providers[{{ $provider }}][sort_order]"
                                               value="{{ $gw->sort_order }}" min="0" max="99">
                                    </div>

                                </div>

                                {{-- Settings row (api_key, merchant_code, dll) --}}
                                  @php $settings = json_decode($gw->settings ?? '{}', true) ?? []; @endphp
                                                                    @if(in_array($provider, ['duitku', 'duitku2', 'winpay', 'winpay2']))
                                <div class="row mt-2" id="settingsRow_{{ $provider }}"
                                     style="{{ $gw->enabled ? '' : 'display:none;' }}">
                                                                        @if(in_array($provider, ['duitku', 'duitku2']))
                                    <div class="col-md-3">
                                        <label class="small text-muted mb-1">Merchant Code</label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="providers[{{ $provider }}][settings][merchant_code]"
                                               value="{{ $settings['merchant_code'] ?? '' }}"
                                               placeholder="DXXXX">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="small text-muted mb-1">API Key</label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="providers[{{ $provider }}][settings][api_key]"
                                               value="{{ $settings['api_key'] ?? '' }}"
                                               placeholder="API Key">
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <div class="alert alert-info py-2 px-3 small mb-0">
                                            <i class="fas fa-info-circle"></i>
                                            @if($provider === 'duitku2')
                                            Duitku 2 memakai merchant / credential berbeda dari Duitku utama.
                                            Cocok untuk akun checkout Duitku kedua. Metode pembayaran tetap dipilih pelanggan di halaman checkout Duitku.
                                            @else
                                            <strong>Metode pembayaran</strong> diatur langsung di
                                            <a href="https://dashboard.duitku.com" target="_blank">Duitku Merchant Dashboard</a>
                                            &rarr; <em>Payment Method</em>.
                                            Sistem menggunakan <strong>Checkout Page</strong> — pelanggan memilih sendiri saat pembayaran.
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end pb-1">
                                        <div class="custom-control custom-checkbox">
                                            <input type="hidden" name="providers[{{ $provider }}][settings][sandbox]" value="0">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="sandbox_{{ $provider }}"
                                                   name="providers[{{ $provider }}][settings][sandbox]"
                                                   value="1"
                                                   {{ !empty($settings['sandbox']) ? 'checked' : '' }}>
                                            <label class="custom-control-label text-warning" for="sandbox_{{ $provider }}">Sandbox</label>
                                        </div>
                                    </div>
                                    @elseif(in_array($provider, ['winpay', 'winpay2']))
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1">API Endpoint</label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="providers[{{ $provider }}][settings][endpoint]"
                                               value="{{ $settings['endpoint'] ?? '' }}"
                                               placeholder="https://api.winpay.id">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1">API Key</label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="providers[{{ $provider }}][settings][api_key]"
                                               value="{{ $settings['api_key'] ?? '' }}"
                                               placeholder="Winpay API Key">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1">Secret Key</label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="providers[{{ $provider }}][settings][secret_key]"
                                               value="{{ $settings['secret_key'] ?? '' }}"
                                               placeholder="Winpay Secret Key">
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <div class="alert alert-info py-2 px-3 small mb-0">
                                            <i class="fas fa-info-circle"></i>
                                            @if($provider === 'winpay2')
                                            Winpay 2 memakai merchant / credential berbeda dari Winpay utama.
                                            Cocok untuk akun checkout Winpay kedua, misalnya khusus QRIS dan E-Wallet.
                                            Channel tetap dipilih di sisi Winpay checkout, bukan dari aplikasi ini.
                                            Fee customer tetap bisa diatur dari panel ini bila merchant kedua memang membutuhkan charge terpisah.
                                            @else
                                            Konfigurasi Winpay disimpan langsung per tenant di halaman ini.
                                            Jika tenant lama masih punya <code>WINPAY_ENDPOINT</code>, <code>WINPAY_KEY</code>, atau <code>WINPAY_SECRET</code>
                                            di Edit Tenant, nilainya akan disalin otomatis sekali ke settings Winpay saat halaman ini dibuka.
                                            Karena Winpay memakai <strong>checkout page</strong>, metode bayar akhir dipilih di sisi Winpay.
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                {{-- Fee row --}}
                                <div class="row mt-2" id="feeRow_{{ $provider }}"
                                     style="{{ $gw->enabled ? '' : 'display:none;' }}">
                                    {{-- Invoice display row --}}
                                    <div class="col-md-5">
                                        <label class="small text-muted mb-1"><i class="fas fa-file-invoice"></i> Label di Invoice <span class="text-secondary">(opsional)</span></label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="providers[{{ $provider }}][settings][invoice_label]"
                                               value="{{ $settings['invoice_label'] ?? '' }}"
                                               placeholder="{{ strtoupper($provider) }} — nama yang muncul di invoice">
                                    </div>
                                    <div class="col-md-7">
                                        <label class="small text-muted mb-1"><i class="fas fa-tags"></i> Keterangan Metode <span class="text-secondary">(opsional)</span></label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="providers[{{ $provider }}][settings][invoice_note]"
                                               value="{{ $settings['invoice_note'] ?? '' }}"
                                               placeholder="mis: VA BNI/BRI/Mandiri, QRIS, DANA">
                                    </div>
                                    <div class="col-12 mt-1 mb-2">
                                        <hr class="mt-1 mb-0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small text-muted mb-1">Tipe Biaya</label>
                                        <select class="form-control form-control-sm"
                                                name="providers[{{ $provider }}][fee_type]"
                                                id="feeType_{{ $provider }}"
                                                onchange="toggleFeeAmount('{{ $provider }}', this.value)">
                                            <option value="none"    {{ $gw->fee_type === 'none'    ? 'selected' : '' }}>Gratis (None)</option>
                                            <option value="fixed"   {{ $gw->fee_type === 'fixed'   ? 'selected' : '' }}>Fixed (Rp)</option>
                                            <option value="percent" {{ $gw->fee_type === 'percent' ? 'selected' : '' }}>Persen (%)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3" id="feeAmountCol_{{ $provider }}"
                                         style="{{ $gw->fee_type === 'none' ? 'display:none;' : '' }}">
                                        <label class="small text-muted mb-1">
                                            Jumlah
                                            <span id="feeUnit_{{ $provider }}">
                                                {{ $gw->fee_type === 'percent' ? '(%)' : '(Rp)' }}
                                            </span>
                                        </label>
                                        <input type="number" class="form-control form-control-sm"
                                               id="feeAmount_{{ $provider }}"
                                               name="providers[{{ $provider }}][fee_amount]"
                                               value="{{ $gw->fee_amount }}"
                                               min="0"
                                                 step="any"
                                               max="{{ $gw->fee_type === 'percent' ? '100' : '' }}"
                                               inputmode="decimal">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted mb-1">Label Biaya</label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="providers[{{ $provider }}][fee_label]"
                                               value="{{ $gw->fee_label ?? 'Biaya Transaksi' }}"
                                               placeholder="Biaya Admin">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end pb-1">
                                        @if($gw->fee_type !== 'none' && $gw->fee_amount > 0)
                                            <span class="badge badge-warning">
                                                @if($gw->fee_type === 'fixed')
                                                    Rp {{ number_format($gw->fee_amount, 0, ',', '.') }}
                                                @else
                                                    {{ $gw->fee_amount }}%
                                                @endif
                                            </span>
                                        @else
                                            <span class="badge badge-success">Gratis</span>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </div>
                        @endforeach

                        @endif

                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i>
                            Perubahan langsung aktif di halaman invoice. Untuk tambah provider baru, jalankan
                            <code>php artisan db:seed --class="\PaymentGatewaySeeder"</code> di server tenant.
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Konfigurasi
                        </button>
                        <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="btn btn-default">Batal</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── SUMMARY KANAN ──────────────────────────────────────── --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list-check"></i> Status Saat Ini</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gateways as $gw)
                            <tr>
                                <td><i class="{{ $gw->icon }}"></i> {{ $gw->label }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $gw->enabled ? 'success' : 'secondary' }}">
                                        {{ $gw->enabled ? 'Aktif' : 'Off' }}
                                    </span>
                                </td>
                                <td class="text-right text-nowrap">
                                    @if($gw->fee_type === 'fixed' && $gw->fee_amount > 0)
                                        <span class="text-warning">Rp {{ number_format($gw->fee_amount, 0, ',', '.') }}</span>
                                    @elseif($gw->fee_type === 'percent' && $gw->fee_amount > 0)
                                        <span class="text-warning">{{ $gw->fee_amount }}%</span>
                                    @else
                                        <span class="text-success">Gratis</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted">Belum ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3 border-warning">
                <div class="card-header bg-warning">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Info Penting</h3>
                </div>
                <div class="card-body">
                    <ul class="mb-0 pl-3 small">
                        <li>Menonaktifkan gateway menyembunyikannya dari halaman invoice customer.</li>
                        <li>Biaya <strong>Fixed</strong>: Rupiah tetap, misal <code>2500</code> = Rp 2.500.</li>
                        <li>Biaya <strong>Persen</strong>: % dari tagihan, misal <code>2.5</code> = 2,5%.</li>
                        <li><strong>Winpay 2</strong> dapat memakai merchant Winpay kedua dengan fee customer terpisah jika diperlukan.</li>
                        <li><strong>Duitku 2</strong> dapat memakai merchant Duitku kedua dengan fee customer terpisah jika diperlukan.</li>
                        <li>Urutan mengatur tampilan pilihan gateway di invoice.</li>
                        <li>Perubahan langsung aktif, tidak perlu restart server.</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
.custom-switch { transform: scale(1.2); }
</style>
<script>
function setSectionInputsDisabled(section, disabled) {
    if (!section) return;
    section.querySelectorAll('input, select, textarea').forEach(function (field) {
        if (field.type === 'hidden') return;
        field.disabled = disabled;
    });
}

function toggleRow(provider, enabled) {
    var feeRow      = document.getElementById('feeRow_'      + provider);
    var settingsRow = document.getElementById('settingsRow_' + provider);
    if (feeRow) {
        feeRow.style.display = enabled ? '' : 'none';
        setSectionInputsDisabled(feeRow, !enabled);
    }
    if (settingsRow) {
        settingsRow.style.display = enabled ? '' : 'none';
        setSectionInputsDisabled(settingsRow, !enabled);
    }
    if (enabled) {
        var feeType = document.getElementById('feeType_' + provider);
        if (feeType) {
            toggleFeeAmount(provider, feeType.value);
        }
    }
}

function toggleFeeAmount(provider, feeType) {
    var col  = document.getElementById('feeAmountCol_' + provider);
    var unit = document.getElementById('feeUnit_' + provider);
    var input = document.getElementById('feeAmount_' + provider);
    if (col)  col.style.display  = feeType === 'none' ? 'none' : '';
    if (unit) unit.textContent   = feeType === 'percent' ? '(%)' : '(Rp)';
    if (input) {
        input.min = '0';
        if (feeType === 'percent') {
            input.step = 'any';
            input.max = '100';
            input.placeholder = 'contoh: 1 atau 2.5';
            input.disabled = false;
        } else {
            input.step = 'any';
            input.removeAttribute('max');
            input.placeholder = 'contoh: 2500';
            input.disabled = feeType === 'none';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[id^="enabled_"]').forEach(function (checkbox) {
        var provider = checkbox.id.replace('enabled_', '');
        toggleRow(provider, checkbox.checked);
    });
    document.querySelectorAll('[id^="feeType_"]').forEach(function (select) {
        var provider = select.id.replace('feeType_', '');
        toggleFeeAmount(provider, select.value);
    });
});
</script>
@endsection
